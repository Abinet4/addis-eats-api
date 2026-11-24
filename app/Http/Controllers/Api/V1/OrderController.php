<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\OrderService;
use App\Http\Controllers\Api\V1\Concerns\ListsResources;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\OrderResource;
use App\Http\Responses\ApiResponse;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    use ListsResources;

    public function __construct(private readonly OrderService $orders) {}

    /**
     * Cursor pagination: order history is a growing, append-mostly feed shown as
     * "load more", so no page numbers or total are needed, and new orders arriving
     * while a client pages do not shift rows between pages.
     */
    public function index(Request $request): JsonResponse
    {
        $orders = Order::query()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->toString()))
            ->orderByDesc('created_at')
            ->orderByDesc('id')               // tie-break: cursor needs a unique, deterministic order
            ->cursorPaginate($this->perPage($request))
            ->withQueryString();

        return ApiResponse::paginated(OrderResource::collection($orders));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'regex:/^(\+2519|09|07)\d{8}$/'],
            'area' => ['required', 'string'],          // zone checked by the PricingRule -> outside_delivery_zone
            'lines' => ['present', 'array', 'max:50'], // an empty array reaches the domain -> empty_order
            'lines.*.dish_id' => ['required', 'integer'],
            'lines.*.qty' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        $order = $this->orders->place(
            $data['customer_name'],
            $data['phone'] ?? null,
            $data['area'],
            collect($data['lines']),
        );

        return ApiResponse::created(new OrderResource($order), route('v1.orders.show', $order));
    }

    public function show(Order $order): JsonResponse
    {
        return ApiResponse::ok(new OrderResource($order->load('lines')));
    }
}
