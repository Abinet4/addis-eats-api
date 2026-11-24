<?php

declare(strict_types=1);

use App\Models\Dish;
use App\Models\Order;

it('caps and sanitises per_page', function (string $qs, int $expected) {
    Dish::factory()->count(120)->create(['area' => 'Bole']);
    $this->getJson("/api/v1/dishes?{$qs}")->assertJsonPath('meta.per_page', $expected);
})->with([
    ['', 15], ['per_page=1000000', 100], ['per_page=0', 15], ['per_page=-5', 15], ['per_page=abc', 15], ['per_page=7', 7],
]);

it('carries filters into the pagination links', function () {
    Dish::factory()->count(40)->create(['area' => 'Bole']);
    Dish::factory()->count(5)->create(['area' => 'Piassa']);
    $res = $this->getJson('/api/v1/dishes?area=Bole&sort=-price&per_page=15&page=2')->assertOk();
    expect($res->json('links.next'))->toContain('area=Bole')->toContain('sort=-price')->toContain('page=3')
        ->and($res->json('meta.total'))->toBe(40)
        ->and($res->json('meta.last_page'))->toBe(3);
});

it('returns an empty data array for a page past the end', function () {
    Dish::factory()->count(3)->create();
    $this->getJson('/api/v1/dishes?page=99')->assertOk()->assertJsonCount(0, 'data');
});

it('pages through orders with a cursor and never repeats a row', function () {
    $this->seed();
    foreach (range(1, 7) as $i) {
        $this->postJson('/api/v1/orders', ['customer_name' => "C{$i}", 'phone' => '0911234567', 'area' => 'Bole',
            'lines' => [['dish_id' => 1, 'qty' => 1]]])->assertCreated();
    }
    $first = $this->getJson('/api/v1/orders?per_page=5')->assertOk();
    expect($first->json('data'))->toHaveCount(5)->and($first->json('meta'))->not->toHaveKey('total');
    $second = $this->getJson($first->json('links.next'))->assertOk();
    $ids = array_merge($first->json('data.*.id'), $second->json('data.*.id'));
    expect($ids)->toHaveCount(7)->and(array_unique($ids))->toHaveCount(7);
});

it('places an order: 201, Location, exact totals, merged lines', function () {
    $this->seed();
    $d = Dish::find(1);
    $res = $this->postJson('/api/v1/orders', [
        'customer_name' => 'Almaz Bekele', 'phone' => '0911234567', 'area' => 'Bole',
        'lines' => [['dish_id' => 1, 'qty' => 1], ['dish_id' => 1, 'qty' => 1]],
    ])->assertCreated();

    $subtotal = $d->price * 2;
    $fee = $subtotal >= 50000 ? 0 : 4500;
    expect($res->json('data.subtotal_santim'))->toBe($subtotal)
        ->and($res->json('data.fee_santim'))->toBe($fee)
        ->and($res->json('data.total_santim'))->toBe($subtotal + $fee)
        ->and($res->json('data.lines'))->toHaveCount(1)
        ->and($res->json('data.lines.0.qty'))->toBe(2)
        ->and($res->json('data.status'))->toBe('placed')
        ->and($res->headers->get('Location'))->toEndWith('/api/v1/orders/'.$res->json('data.id'));
});

it('refuses bad orders with the right code', function (array $body, int $status, string $code) {
    $this->seed();
    $this->postJson('/api/v1/orders', $body + ['customer_name' => 'Dawit', 'phone' => '0911234567'])
        ->assertStatus($status)->assertJsonPath('error.code', $code);
    expect(Order::count())->toBe(0);
})->with([
    'outside zone' => [['area' => 'Sululta', 'lines' => [['dish_id' => 1, 'qty' => 1]]], 422, 'validation_failed'],
    'empty lines' => [['area' => 'Bole', 'lines' => []], 422, 'validation_failed'],
    'unknown dish' => [['area' => 'Bole', 'lines' => [['dish_id' => 9999, 'qty' => 1]]], 422, 'validation_failed'],
    'qty zero' => [['area' => 'Bole', 'lines' => [['dish_id' => 1, 'qty' => 0]]], 422, 'validation_failed'],
    'qty as text' => [['area' => 'Bole', 'lines' => [['dish_id' => 1, 'qty' => 'two']]], 422, 'validation_failed'],
]);

function placeOrder($test): int
{
    $test->seed();

    return $test->postJson('/api/v1/orders', ['customer_name' => 'Almaz', 'phone' => '0911234567', 'area' => 'Bole',
        'lines' => [['dish_id' => 2, 'qty' => 1]]])->json('data.id');
}

it('moves status only along legal transitions (409 otherwise)', function () {
    $id = placeOrder($this);
    $this->patchJson("/api/v1/orders/{$id}/status", ['status' => 'delivered'])
        ->assertStatus(409)->assertJsonPath('error.code', 'illegal_transition');
    foreach (['preparing', 'on_the_way', 'delivered'] as $s) {
        $this->patchJson("/api/v1/orders/{$id}/status", ['status' => $s])->assertOk()->assertJsonPath('data.status', $s);
    }
    $this->patchJson("/api/v1/orders/{$id}/status", ['status' => 'teleported'])->assertStatus(422);
    $this->postJson("/api/v1/orders/{$id}/cancellation")->assertStatus(409);
});

it('cancels once, with a reason', function () {
    $id = placeOrder($this);
    $this->postJson("/api/v1/orders/{$id}/cancellation", ['reason' => 'customer_unreachable'])
        ->assertOk()->assertJsonPath('data.status', 'cancelled')
        ->assertJsonPath('data.cancellation_reason', 'customer_unreachable');
    $this->postJson("/api/v1/orders/{$id}/cancellation")->assertStatus(409);
});

it('marks paid idempotently with PUT', function () {
    $id = placeOrder($this);
    $a = $this->putJson("/api/v1/orders/{$id}/payment", ['reference' => 'TB-100'])->assertOk()->json('data.paid_at');
    $b = $this->putJson("/api/v1/orders/{$id}/payment", ['reference' => 'TB-100'])->assertOk()->json('data.paid_at');
    expect($a)->toBe($b);
    $this->putJson("/api/v1/orders/{$id}/payment", ['reference' => 'TB-200'])
        ->assertStatus(409)->assertJsonPath('error.code', 'already_paid');
    $this->getJson('/api/v1/orders/abc')->assertNotFound();
});

it('refuses to reuse one payment reference for two orders (409, not 500)', function () {
    $first = placeOrder($this);
    $second = $this->postJson('/api/v1/orders', ['customer_name' => 'Dawit', 'phone' => '0911234567', 'area' => 'Bole',
        'lines' => [['dish_id' => 2, 'qty' => 1]]])->json('data.id');

    $this->putJson("/api/v1/orders/{$first}/payment", ['reference' => 'TB-555'])->assertOk();
    $this->putJson("/api/v1/orders/{$second}/payment", ['reference' => 'TB-555'])
        ->assertStatus(409)->assertJsonPath('error.code', 'payment_reference_taken');
    expect(Order::find($second)->paid_at)->toBeNull();
});
