<?php

declare(strict_types=1);

namespace App\Domain\Pricing;

use App\Exceptions\OutsideZoneException;

interface PricingRule
{
    /**
     * Delivery fee in santim.
     *
     * The slide signature is feeFor(string $area): int. The subtotal is added
     * because a free-delivery threshold cannot be decided from the area alone.
     *
     * @throws OutsideZoneException
     */
    public function feeFor(string $area, int $subtotal): int;
}
