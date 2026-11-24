<?php

declare(strict_types=1);

use App\Domain\Dish;
use App\Domain\Order;
use App\Domain\OrderStatus;
use App\Domain\Pricing\DeliveryZones;
use App\Domain\Pricing\FreeDeliveryRule;
use App\Domain\Pricing\PricingRule;
use App\Domain\Pricing\PromoRule;
use App\Domain\Pricing\StandardRule;
use App\Domain\Receipt;
use App\Exceptions\EmptyOrderException;
use App\Exceptions\IllegalTransitionException;
use App\Exceptions\OutsideZoneException;
use App\Support\Money;

function zones(): DeliveryZones
{
    return new DeliveryZones(['Bole' => 4500, 'Piassa' => 6000, 'Ayat' => 7500], 50000);
}

it('makes Dish readonly', function () {
    $d = new Dish('Doro Wat', 'Yod Abyssinia', 42000, false);
    expect($d->priceInEtb())->toBe('420.00 ETB');
    $d->price = 1;
})->throws(Error::class, 'Cannot modify readonly property');

it('rejects a negative price', fn () => new Dish('X', 'Y', -1, false))
    ->throws(InvalidArgumentException::class);

it('maps enum values and labels', function () {
    $s = OrderStatus::from('placed');
    expect($s->value)->toBe('placed')
        ->and($s->label())->toBe('Placed')
        ->and(OrderStatus::tryFrom('lost'))->toBeNull();
});

it('enforces the status state machine', function () {
    expect(OrderStatus::Placed->canTransitionTo(OrderStatus::Preparing))->toBeTrue()
        ->and(OrderStatus::Placed->canTransitionTo(OrderStatus::Delivered))->toBeFalse()
        ->and(OrderStatus::Delivered->canTransitionTo(OrderStatus::Cancelled))->toBeFalse()
        ->and(OrderStatus::OnTheWay->canTransitionTo(OrderStatus::Cancelled))->toBeFalse();
});

it('prices with the three rules', function () {
    $std = new StandardRule(zones());
    expect($std->feeFor('Bole', 1000))->toBe(4500)
        ->and($std->feeFor('Bole', 50000))->toBe(0)
        ->and((new PromoRule($std))->feeFor('Bole', 1000))->toBe(4050)
        ->and((new PromoRule($std))->feeFor('Ayat', 1000))->toBe(6750)
        ->and((new PromoRule($std))->feeFor('Bole', 60000))->toBe(0)
        ->and((new FreeDeliveryRule(zones()))->feeFor('Piassa', 1))->toBe(0);
});

it('carries the area on OutsideZoneException, for every rule', function (PricingRule $rule) {
    try {
        $rule->feeFor('Sululta', 999999);
        $this->fail('expected exception');
    } catch (OutsideZoneException $e) {
        expect($e->area)->toBe('Sululta')
            ->and($e->getMessage())->toBe('Addis Eats does not deliver to Sululta yet.');
    }
})->with([
    'standard' => fn () => new StandardRule(zones()),
    'promo' => fn () => new PromoRule(new StandardRule(zones())),
    'free' => fn () => new FreeDeliveryRule(zones()),
]);

it('builds an order fluently and prints a receipt', function () {
    $order = (new Order(1, 'Bole', new StandardRule(zones())))
        ->addLine(new Dish('Doro Wat', 'Yod Abyssinia', 42000, false), 1)
        ->addLine(new Dish('Shiro', 'Kategna', 18000, true), 1);

    expect($order->subtotal())->toBe(60000)
        ->and($order->fee())->toBe(0)
        ->and((new Receipt($order))->render())->toContain('600.00 ETB')->toContain('Status: Placed');
});

it('throws EmptyOrderException for an order with no lines', function () {
    (new Order(2, 'Bole', new StandardRule(zones())))->fee();
})->throws(EmptyOrderException::class);

it('refuses a quantity of zero', function () {
    (new Order(3, 'Bole', new StandardRule(zones())))->addLine(new Dish('Tibs', 'Sishu', 22000, false), 0);
})->throws(InvalidArgumentException::class);

it('refuses an illegal transition on the domain order', function () {
    (new Order(4, 'Bole', new StandardRule(zones())))->moveTo(OrderStatus::Delivered);
})->throws(IllegalTransitionException::class);

it('formats santim exactly, with no thousands separator', function (int $in, string $out) {
    expect(Money::decimal($in))->toBe($out);
})->with([[24550, '245.50'], [120000, '1200.00'], [5, '0.05'], [0, '0.00'], [-150, '-1.50']]);

it('shows why number_format is unsafe for API fields', function () {
    expect(number_format(120000 / 100, 2))->toBe('1,200.00');
});

it('shows the collection key gap and the values() fix', function () {
    $menu = [['n' => 'A', 'f' => false], ['n' => 'B', 'f' => true], ['n' => 'C', 'f' => true]];
    // filter() and map() keep the original keys, so JSON becomes an object
    $gappy = collect($menu)->filter(fn ($d) => $d['f'])->map(fn ($d) => $d['n']);
    expect(json_encode($gappy))->toBe('{"1":"B","2":"C"}')
        ->and(json_encode($gappy->values()))->toBe('["B","C"]');

    // pluck() in Laravel 11 rebuilds the array, so it happens to renumber (review Q6 nuance)
    $plucked = collect($menu)->filter(fn ($d) => $d['f'])->pluck('n');
    expect(json_encode($plucked))->toBe('["B","C"]');
});
