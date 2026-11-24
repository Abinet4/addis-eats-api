<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\OrderService;
use App\Http\Controllers\Api\V1\Concerns\ListsResources;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StoreOrderRequest;
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

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $data = $request->validated();

        $order = $this->orders->place(
            $data['customer_name'],
            $data['phone'],
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
