<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\OrderService;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\PayOrderRequest;
use App\Http\Resources\V1\OrderResource;
use App\Http\Responses\ApiResponse;
use App\Models\Order;
use Illuminate\Http\JsonResponse;

/**
 * PUT /api/v1/orders/{order}/payment  {"reference": "TB-123456"}
 * A singleton sub-resource set with PUT, because PUT is idempotent: a mobile client
 * that lost the response can retry and the order is not "paid twice".
 */
class OrderPaymentController extends Controller
{
    public function __construct(private readonly OrderService $orders) {}

    public function __invoke(PayOrderRequest $request, Order $order): JsonResponse
    {
        $order = $this->orders->markPaid($order, $request->validated('reference'));

        return ApiResponse::ok(new OrderResource($order->load('lines')));
    }
}
