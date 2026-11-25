<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\OrderService;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\CancelOrderRequest;
use App\Http\Resources\V1\OrderResource;
use App\Http\Responses\ApiResponse;
use App\Models\Order;
use Illuminate\Http\JsonResponse;

/** POST /api/v1/orders/{order}/cancellation  {"reason": "customer_unreachable"} */
class OrderCancellationController extends Controller
{
    public function __construct(private readonly OrderService $orders) {}

    public function __invoke(CancelOrderRequest $request, Order $order): JsonResponse
    {
        $order = $this->orders->cancel($order, $request->validated('reason'));

        return ApiResponse::ok(new OrderResource($order->load('lines')));
    }
}
