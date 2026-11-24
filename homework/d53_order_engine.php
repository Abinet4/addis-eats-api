<?php

declare(strict_types=1);

/*
 * Day 53 mini-project: the Addis Eats order engine, run outside HTTP.
 * Uses the classes in app/Domain through Composer's PSR-4 autoloader.
 * Run: php homework/d53_order_engine.php
 */

require __DIR__.'/../vendor/autoload.php';

use App\Domain\Dish;
use App\Domain\Order;
use App\Domain\Pricing\DeliveryZones;
use App\Domain\Pricing\FreeDeliveryRule;
use App\Domain\Pricing\PricingRule;
use App\Domain\Pricing\PromoRule;
use App\Domain\Pricing\StandardRule;
use App\Domain\Receipt;
use App\Exceptions\EmptyOrderException;
use App\Exceptions\OutsideZoneException;

$zones = new DeliveryZones(
    ['Bole' => 4500, 'Kazanchis' => 4500, 'Piassa' => 6000, 'Megenagna' => 6000],
    freeDeliveryFrom: 50000,
);

$doro = new Dish('Doro Wat', 'Yod Abyssinia', 42000, false);
$shiro = new Dish('Shiro', 'Kategna', 18000, true);
$tibs = new Dish('Tibs', 'Sishu', 22000, false);

/** @var array<string, callable(): Order> $orders */
$orders = [
    'Inside the zone, standard rule' => fn () => (new Order(24550, 'Piassa', new StandardRule($zones)))
        ->addLine($shiro, 1)->addLine($tibs, 1),
    'Inside the zone, promo rule' => fn () => (new Order(24551, 'Bole', new PromoRule(new StandardRule($zones))))
        ->addLine($shiro, 2),
    'Free delivery threshold reached' => fn () => (new Order(24552, 'Bole', new StandardRule($zones)))
        ->addLine($doro, 1)->addLine($tibs, 1),
    'Outside the zone' => fn () => (new Order(24553, 'Sululta', new FreeDeliveryRule($zones)))
        ->addLine($doro, 1),
    'Empty order' => fn () => new Order(24554, 'Bole', new StandardRule($zones)),
];

foreach ($orders as $title => $build) {
    echo "=== {$title} ===".PHP_EOL;
    try {
        echo (new Receipt($build()))->render().PHP_EOL;
    } catch (OutsideZoneException|EmptyOrderException $e) {
        // One handler for both broken business rules; the area travels as data, not text.
        $extra = $e instanceof OutsideZoneException ? " [area={$e->area}]" : '';
        echo 'Could not price the order: '.$e->getMessage().$extra.PHP_EOL;
    }
    echo PHP_EOL;
}

// Any PricingRule is accepted: the Order depends on the interface, never on a class.
$rules = ['standard' => new StandardRule($zones), 'promo' => new PromoRule(new StandardRule($zones)), 'free' => new FreeDeliveryRule($zones)];
foreach ($rules as $name => $rule) {
    /** @var PricingRule $rule */
    printf("%-8s fee for Bole on a 180.00 ETB order: %d santim%s", $name, $rule->feeFor('Bole', 18000), PHP_EOL);
}
