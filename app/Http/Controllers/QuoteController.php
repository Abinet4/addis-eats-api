<?php

declare(strict_types=1);

namespace App\Http\Controllers;

/** Day 52 exercise: GET /api/quote prices a hard-coded three-line order for Bole. */
class QuoteController extends Controller
{
    public function __invoke(): array
    {
        $lines = [
            ['item' => 'Doro Wat', 'qty' => 2, 'price' => 18000],
            ['item' => 'Tibs', 'qty' => 1, 'price' => 22000],
            ['item' => 'Shiro', 'qty' => 3, 'price' => 9000],
        ];

        return orderTotal($lines, 'Bole'); // ['subtotal' => 85000, 'fee' => 0, 'total' => 85000]
    }
}
