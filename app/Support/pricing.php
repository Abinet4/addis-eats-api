<?php

declare(strict_types=1);

/*
 * Day 52 in-class exercise: procedural pricing.
 * Loaded through composer.json "autoload.files" because PSR-4 only autoloads
 * classes, never plain functions.
 *
 * Day 53 replaces this with App\Domain\Pricing\PricingRule implementations;
 * the file is kept so the Day 52 /api/quote route keeps working.
 */

if (! defined('FREE_DELIVERY_FROM')) {
    define('FREE_DELIVERY_FROM', 50000); // ETB 500.00 in santim
}

if (! function_exists('deliveryFee')) {
    function deliveryFee(string $area, int $subtotal): int
    {
        // Trap 1 from the slide: exactly 50000 ships free, so >= and not >.
        if ($subtotal >= FREE_DELIVERY_FROM) {
            return 0;
        }

        return match ($area) {
            'Bole', 'Kazanchis' => 4500,
            'Piassa', 'Megenagna' => 6000,
            'CMC' => 7000,   // extra area 1
            'Ayat' => 7500,  // extra area 2
            default => 8000,
        };
    }
}

if (! function_exists('orderTotal')) {
    /**
     * @param  list<array{item: string, qty: int, price: int}>  $lines
     * @return array{subtotal: int, fee: int, total: int}
     */
    function orderTotal(array $lines, string $area): array
    {
        $subtotal = 0;
        foreach ($lines as $line) {
            $subtotal += $line['qty'] * $line['price'];
        }

        $fee = deliveryFee($area, $subtotal);

        return [
            'subtotal' => $subtotal,
            'fee' => $fee,
            'total' => $subtotal + $fee,
        ];
    }
}
