<?php

declare(strict_types=1);

namespace App\Domain\Pricing;

final class StandardRule implements PricingRule
{
    public function __construct(private readonly DeliveryZones $zones) {}

    public function feeFor(string $area, int $subtotal): int
    {
        $base = $this->zones->baseFee($area); // zone check first, even for big orders

        return $subtotal >= $this->zones->freeDeliveryFrom ? 0 : $base;
    }
}
