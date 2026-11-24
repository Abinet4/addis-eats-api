<?php

declare(strict_types=1);

// Day 52: procedural pricing, strict types and money as integers.

it('charges zone fees and adds the two extra areas', function () {
    expect(deliveryFee('Bole', 10000))->toBe(4500)
        ->and(deliveryFee('Kazanchis', 10000))->toBe(4500)
        ->and(deliveryFee('Piassa', 10000))->toBe(6000)
        ->and(deliveryFee('CMC', 10000))->toBe(7000)
        ->and(deliveryFee('Ayat', 10000))->toBe(7500)
        ->and(deliveryFee('Sululta', 10000))->toBe(8000);
});

it('ships free at exactly 50000 santim but not at 49999 (>= not >)', function () {
    expect(deliveryFee('Bole', 50000))->toBe(0)
        ->and(deliveryFee('Bole', 49999))->toBe(4500);
});

it('prices the slide order exactly', function () {
    $lines = [
        ['item' => 'Doro Wat', 'qty' => 2, 'price' => 18000],
        ['item' => 'Tibs', 'qty' => 1, 'price' => 22000],
        ['item' => 'Shiro', 'qty' => 3, 'price' => 9000],
    ];
    expect(orderTotal($lines, 'Bole'))->toBe(['subtotal' => 85000, 'fee' => 0, 'total' => 85000]);
});

it('prices an empty order as fee only', function () {
    expect(orderTotal([], 'Bole'))->toBe(['subtotal' => 0, 'fee' => 4500, 'total' => 4500]);
});

it('refuses a numeric string under strict_types', function () {
    deliveryFee('Bole', '7');
})->throws(TypeError::class);

it('shows why floats are banned for money', function () {
    expect(0.1 + 0.2 === 0.3)->toBeFalse()
        ->and(12010 + 12010)->toBe(24020);
});
