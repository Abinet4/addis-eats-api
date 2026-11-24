<?php

declare(strict_types=1);

namespace App\Domain\Pricing;

/** 10% off whatever the standard rule charges. A decorator, so the threshold logic is not duplicated. */
final class PromoRule implements PricingRule
{
    public function __construct(
        private readonly StandardRule $standard,
        private readonly int $percentOff = 10,
    ) {}

    public function feeFor(string $area, int $subtotal): int
    {
        $fee = $this->standard->feeFor($area, $subtotal);

        return intdiv($fee * (100 - $this->percentOff) + 50, 100); // integer rounding, half up
    }
}
