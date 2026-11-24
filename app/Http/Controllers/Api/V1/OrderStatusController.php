<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\OrderService;
use App\Domain\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\OrderResource;
use App\Http\Responses\ApiResponse;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** PATCH /api/v1/orders/{order}/status  e.g. {"status": "delivered"}. Transition rules live in OrderStatus. */
class OrderStatusController extends Controller
{
    public function __construct(private readonly OrderService $orders) {}

    public function __invoke(Request $request, Order $order): JsonResponse
    {
        $request->validate([
            // cancelled has its own endpoint because it carries a reason
            'status' => ['required', Rule::enum(OrderStatus::class)->except([OrderStatus::Cancelled, OrderStatus::Placed])],
        ]);

        $order = $this->orders->transition($order, $request->enum('status', OrderStatus::class));

        return ApiResponse::ok(new OrderResource($order->load('lines')));
    }
}
