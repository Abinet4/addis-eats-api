<?php

declare(strict_types=1);

namespace App\Domain;

use App\Domain\Pricing\PricingRule;
use App\Exceptions\EmptyOrderException;
use App\Exceptions\IllegalTransitionException;
use App\Exceptions\OutsideZoneException;
use App\Support\FormatsEtb;
use Illuminate\Support\Collection;

/** Day 53 homework: the plain-PHP order engine. Persistence arrives with App\Models\Order. */
final class Order
{
    use FormatsEtb;

    /** @var list<OrderLine> */
    private array $lines = [];

    private OrderStatus $status = OrderStatus::Placed;

    public function __construct(
        public readonly int $id,
        public readonly string $area,
        private readonly PricingRule $rule, // any rule: Standard, Promo, FreeDelivery
    ) {}

    public function addLine(Dish $dish, int $qty): static
    {
        $this->lines[] = new OrderLine($dish, $qty);

        return $this;
    }

    /** @return Collection<int, OrderLine> */
    public function lines(): Collection
    {
        return collect($this->lines);
    }

    public function subtotal(): int
    {
        return $this->lines()->sum(fn (OrderLine $l) => $l->amount());
    }

    /** @throws EmptyOrderException|OutsideZoneException */
    public function fee(): int
    {
        if ($this->lines === []) {
            throw new EmptyOrderException;
        }

        return $this->rule->feeFor($this->area, $this->subtotal());
    }

    public function total(): int
    {
        return $this->subtotal() + $this->fee();
    }

    public function status(): OrderStatus
    {
        return $this->status;
    }

    public function moveTo(OrderStatus $next): void
    {
        if (! $this->status->canTransitionTo($next)) {
            throw new IllegalTransitionException($this->status, $next);
        }
        $this->status = $next;
    }
}
