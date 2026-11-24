<?php

declare(strict_types=1);

namespace App\Domain\Pricing;

/** Free delivery campaign. Still refuses areas we do not serve. */
final class FreeDeliveryRule implements PricingRule
{
    public function __construct(private readonly DeliveryZones $zones) {}

    public function feeFor(string $area, int $subtotal): int
    {
        $this->zones->baseFee($area); // throws OutsideZoneException when unserved

        return 0;
    }
}
