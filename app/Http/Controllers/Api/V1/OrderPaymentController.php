<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\OrderService;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\OrderResource;
use App\Http\Responses\ApiResponse;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * PUT /api/v1/orders/{order}/payment  {"reference": "TB-123456"}
 * A singleton sub-resource set with PUT, because PUT is idempotent: a mobile client
 * that lost the response can retry and the order is not "paid twice".
 */
class OrderPaymentController extends Controller
{
    public function __construct(private readonly OrderService $orders) {}

    public function __invoke(Request $request, Order $order): JsonResponse
    {
        $data = $request->validate(['reference' => ['required', 'string', 'max:64']]);

        $order = $this->orders->markPaid($order, $data['reference']);

        return ApiResponse::ok(new OrderResource($order->load('lines')));
    }
}
