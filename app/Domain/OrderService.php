<?php

declare(strict_types=1);

namespace App\Domain;

use App\Domain\Pricing\PricingRule;
use App\Exceptions\AlreadyPaidException;
use App\Exceptions\DishWithdrawnException;
use App\Exceptions\EmptyOrderException;
use App\Exceptions\IllegalTransitionException;
use App\Exceptions\PaymentReferenceTakenException;
use App\Exceptions\UnknownDishException;
use App\Models\Dish;
use App\Models\Order;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Every business rule about orders lives here, so controllers only translate HTTP. */
final class OrderService
{
    public function __construct(private readonly PricingRule $rule) {}

    /**
     * @param  Collection<int, array{dish_id: int, qty: int}>  $lines
     */
    public function place(string $customerName, ?string $phone, string $area, Collection $lines): Order
    {
        if ($lines->isEmpty()) {
            throw new EmptyOrderException;
        }

        // Merge duplicate dish lines: [{3,1},{3,2}] becomes one line of qty 3.
        $qtyByDish = $lines->groupBy('dish_id')->map(fn (Collection $g) => (int) $g->sum('qty'));

        $dishes = Dish::withTrashed()->whereIn('id', $qtyByDish->keys())->get()->keyBy('id');

        // Safety net: the Form Request already turns unknown ids into a 422.
        $missing = $qtyByDish->keys()->diff($dishes->keys())->values();
        if ($missing->isNotEmpty()) {
            throw new UnknownDishException($missing->all());
        }

        // The world changed, not the payload: 409, so the client refreshes its menu.
        $withdrawn = $dishes->filter(fn (Dish $d) => $d->trashed())->keys()->values();
        if ($withdrawn->isNotEmpty()) {
            throw new DishWithdrawnException($withdrawn->all());
        }

        $subtotal = $qtyByDish->reduce(fn (int $sum, int $qty, int $id) => $sum + $dishes[$id]->price * $qty, 0);
        $fee = $this->rule->feeFor($area, $subtotal); // throws OutsideZoneException -> 422

        return DB::transaction(function () use ($customerName, $phone, $area, $subtotal, $fee, $qtyByDish, $dishes) {
            $order = new Order(['customer_name' => $customerName, 'phone' => $phone, 'area' => $area]);
            $order->forceFill([
                'subtotal' => $subtotal,
                'fee' => $fee,
                'total' => $subtotal + $fee,
                'status' => OrderStatus::Placed,
            ])->save();

            foreach ($qtyByDish as $dishId => $qty) {
                $order->lines()->create([
                    'dish_id' => $dishId,
                    'dish_name' => $dishes[$dishId]->name,
                    'unit_price' => $dishes[$dishId]->price,
                    'qty' => $qty,
                ]);
            }

            return $order->load('lines');
        });
    }

    public function transition(Order $order, OrderStatus $next): Order
    {
        if (! $order->status->canTransitionTo($next)) {
            throw new IllegalTransitionException($order->status, $next);
        }
        $order->forceFill(['status' => $next])->save();

        return $order;
    }

    public function cancel(Order $order, ?string $reason): Order
    {
        $this->transition($order, OrderStatus::Cancelled);
        $order->forceFill(['cancellation_reason' => $reason])->save();

        return $order;
    }

    /**
     * Idempotent: the same reference twice is a no-op, so a client may safely retry a PUT.
     * A different reference on an already-paid order is a 409.
     */
    public function markPaid(Order $order, string $reference): Order
    {
        if ($order->paid_at !== null) {
            if ($order->payment_reference === $reference) {
                return $order;
            }
            throw new AlreadyPaidException($order->payment_reference);
        }
        if ($order->status === OrderStatus::Cancelled) {
            throw new IllegalTransitionException($order->status, $order->status);
        }

        // A reference identifies one real payment, so it may settle only one order.
        $taken = Order::query()->where('payment_reference', $reference)->whereKeyNot($order->getKey())->exists();
        if ($taken) {
            throw new PaymentReferenceTakenException($reference);
        }

        try {
            $order->forceFill(['payment_reference' => $reference, 'paid_at' => now()])->save();
        } catch (UniqueConstraintViolationException) {
            // Two requests raced past the check above; the unique index is the real guard.
            throw new PaymentReferenceTakenException($reference);
        }

        return $order;
    }
}
