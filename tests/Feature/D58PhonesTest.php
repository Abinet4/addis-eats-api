<?php

declare(strict_types=1);

use App\Models\Order;
use App\Models\Restaurant;

/**
 * Reads day58/phones.txt so the fixture and the behaviour cannot drift apart.
 *
 * @return array<string, array{string, bool}>
 */
function phoneFixture(): array
{
    $rows = [];
    foreach (file(dirname(__DIR__, 2).'/day58/phones.txt', FILE_IGNORE_NEW_LINES) as $line) {
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        [$number, $expect] = array_map('trim', explode('|', $line));
        $rows[$number] = [$number, $expect === 'valid'];
    }

    return $rows;
}

it('has forty numbers, twenty-six of them valid', function () {
    $rows = phoneFixture();
    expect($rows)->toHaveCount(40)
        ->and(array_filter($rows, fn ($r) => $r[1]))->toHaveCount(26);
});

it('accepts and normalises, or rejects, every number in phones.txt', function (string $number, bool $valid) {
    $yod = Restaurant::create(['name' => 'Yod Abyssinia', 'area' => 'Bole', 'phone' => '0911000001']);
    $dish = $yod->dishes()->create(['name' => 'Doro Wat', 'price' => 42000, 'is_fasting' => false, 'area' => 'Bole']);

    $res = $this->postJson('/api/v1/orders', [
        'customer_name' => 'Almaz', 'phone' => $number, 'area' => 'Bole',
        'lines' => [['dish_id' => $dish->id, 'qty' => 1]],
    ]);

    if ($valid) {
        $res->assertCreated();
        expect(Order::sole()->phone)->toMatch('/^09\d{8}$/');
    } else {
        $res->assertStatus(422)->assertJsonPath('error.code', 'validation_failed');
        expect($res->json('error.meta'))->toHaveKey('phone');
        expect(Order::count())->toBe(0);
    }
})->with(phoneFixture());
