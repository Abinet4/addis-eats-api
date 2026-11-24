<?php

declare(strict_types=1);

/*
 * Day 52 mini-project: the Addis Eats menu report. Plain PHP, no framework.
 * Run: php homework/d52_menu_report.php
 */

/** @var list<array{name: string, restaurant: string, price: int, is_fasting: bool}> */
$menu = [
    ['name' => 'Doro Wat',   'restaurant' => 'Yod Abyssinia', 'price' => 42000, 'is_fasting' => false],
    ['name' => 'Kitfo',      'restaurant' => 'Yod Abyssinia', 'price' => 38000, 'is_fasting' => false],
    ['name' => 'Beyaynetu',  'restaurant' => 'Yod Abyssinia', 'price' => 24500, 'is_fasting' => true],
    ['name' => 'Shiro',      'restaurant' => 'Kategna',       'price' => 18000, 'is_fasting' => true],
    ['name' => 'Tibs',       'restaurant' => 'Kategna',       'price' => 22000, 'is_fasting' => false],
    ['name' => 'Misir Wat',  'restaurant' => 'Kategna',       'price' => 16500, 'is_fasting' => true],
    ['name' => 'Burger',     'restaurant' => 'Sishu',         'price' => 35000, 'is_fasting' => false],
    ['name' => 'Fasting Burger', 'restaurant' => 'Sishu',     'price' => 30000, 'is_fasting' => true],
];

function formatEtb(int $santim): string
{
    // Format only at the moment of display; all arithmetic stays in integer santim.
    return 'ETB '.number_format($santim / 100, 2);
}

function fastingLabel(bool $isFasting): string
{
    return match ($isFasting) {
        true => 'Fasting',
        false => 'Non-fasting',
    };
}

/** @param list<array{name: string, restaurant: string, price: int, is_fasting: bool}> $menu */
function menuReport(array $menu): string
{
    if ($menu === []) {
        return "The menu is empty.\n"; // avoid division by zero and a null "cheapest"
    }

    // usort sorts in place and returns bool: never $sorted = usort(...)
    $sorted = $menu;
    usort($sorted, fn (array $a, array $b): int => [$a['price'], $a['name']] <=> [$b['price'], $b['name']]);

    $rows = array_map(
        fn (array $d): string => sprintf('%-15s %-14s %12s  %s', $d['name'], $d['restaurant'], formatEtb($d['price']), fastingLabel($d['is_fasting'])),
        $sorted,
    );

    $fasting = array_filter($menu, fn (array $d): bool => $d['is_fasting'] === true);
    $prices = array_column($menu, 'price');
    $average = intdiv(array_sum($prices) * 2 + count($prices), count($prices) * 2); // integer, rounded half up
    $cheapest = $sorted[0];

    return implode(PHP_EOL, [
        'ADDIS EATS MENU REPORT',
        str_repeat('-', 60),
        ...$rows,
        str_repeat('-', 60),
        'Fasting dishes : '.count($fasting),
        "Cheapest dish  : {$cheapest['name']} ({$cheapest['restaurant']}), ".formatEtb($cheapest['price']),
        'Average price  : '.formatEtb($average),
    ]).PHP_EOL;
}

echo menuReport($menu);
echo PHP_EOL.menuReport([]); // edge case: an empty menu must not divide by zero
