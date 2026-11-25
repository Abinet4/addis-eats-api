<?php

declare(strict_types=1);

use App\Models\Dish;
use App\Models\Restaurant;

it('lists dishes with composable filters applied in SQL', function () {
    Dish::factory()->create(['name' => 'A', 'area' => 'Bole', 'price' => 20000, 'is_fasting' => true]);
    Dish::factory()->create(['name' => 'B', 'area' => 'Bole', 'price' => 30000, 'is_fasting' => true]);
    Dish::factory()->create(['name' => 'C', 'area' => 'Bole', 'price' => 20000, 'is_fasting' => false]);
    Dish::factory()->create(['name' => 'D', 'area' => 'Piassa', 'price' => 10000, 'is_fasting' => true]);

    $this->getJson('/api/v1/dishes')->assertJsonCount(4, 'data');
    $this->getJson('/api/v1/dishes?area=Bole')->assertJsonCount(3, 'data');
    $this->getJson('/api/v1/dishes?max_price=25000')->assertJsonCount(3, 'data');
    $this->getJson('/api/v1/dishes?fasting=1')->assertJsonCount(3, 'data');
    $this->getJson('/api/v1/dishes?area=Bole&fasting=true&max_price=25000')
        ->assertJsonPath('data.*.name', ['A']);
    // an empty ?area= is ignored rather than filtering for a blank area
    $this->getJson('/api/v1/dishes?area=')->assertJsonCount(4, 'data');
    // max_price=0 means nothing is cheap enough, not "no filter"
    $this->getJson('/api/v1/dishes?max_price=0')->assertJsonCount(0, 'data');
});

it('shows one dish, and gives honest 404s', function () {
    $dish = Dish::factory()->create();
    $this->getJson("/api/v1/dishes/{$dish->id}")->assertOk()->assertJsonPath('data.id', $dish->id);
    $this->getJson('/api/v1/dishes/9999')->assertNotFound()->assertJsonPath('error.code', 'not_found');
    $this->getJson('/api/v1/dishes/abc')->assertNotFound()->assertJsonPath('error.code', 'route_not_found');
});

it('publishes exactly the chosen fields and never cost_price', function () {
    $dish = Dish::factory()->create(['price' => 120000, 'cost_price' => 9000]);
    $data = $this->getJson("/api/v1/dishes/{$dish->id}")->json('data');
    expect(array_keys($data))->toBe(['id', 'name', 'restaurant', 'price_etb', 'price_santim', 'fasting', 'area'])
        ->and($data['price_etb'])->toBe('1200.00')
        ->and($data['fasting'])->toBeBool();
});

it('creates with 201 and a Location header, ignoring unlisted fields', function () {
    $res = $this->postJson('/api/v1/dishes', [
        'name' => 'Kitfo', 'restaurant_id' => Restaurant::factory()->create(['name' => 'Sishu'])->id, 'price' => 38000,
        'is_fasting' => false, 'area' => 'Kazanchis', 'cost_price' => 1, 'id' => 777,
    ])->assertCreated();

    $id = $res->json('data.id');
    expect($id)->not->toBe(777)
        ->and($res->json('data.restaurant'))->toBe('Sishu')
        ->and($res->headers->get('Location'))->toEndWith("/api/v1/dishes/{$id}")
        ->and(Dish::find($id)->cost_price)->toBeNull();
});

it('rejects bad input with 422, never 500', function () {
    $this->postJson('/api/v1/dishes', ['name' => 'X', 'price' => -5, 'area' => 'Sululta'])
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'validation_failed')
        ->assertJsonStructure(['error' => ['meta' => ['price', 'area', 'restaurant_id']]]);
    $this->postJson('/api/v1/dishes', ['price' => 'two'])->assertStatus(422);
});

it('PATCHes only what is sent and ignores cost_price (review Q6)', function () {
    $dish = Dish::factory()->create(['name' => 'Shiro', 'price' => 18000, 'cost_price' => 9000]);
    $this->patchJson("/api/v1/dishes/{$dish->id}", ['price' => 19500, 'cost_price' => 100])
        ->assertOk()->assertJsonPath('data.price_santim', 19500)->assertJsonPath('data.name', 'Shiro');
    expect($dish->refresh()->cost_price)->toBe(9000);
});

it('requires the whole resource on PUT', function () {
    $dish = Dish::factory()->create();
    $this->putJson("/api/v1/dishes/{$dish->id}", ['price' => 19500])->assertStatus(422);
    $this->patchJson('/api/v1/dishes/9999', ['price' => 1])->assertNotFound();
});

it('deletes with 204 and no body, then 404s, keeping a soft-deleted row', function () {
    $dish = Dish::factory()->create();
    $res = $this->deleteJson("/api/v1/dishes/{$dish->id}")->assertNoContent();
    expect($res->getContent())->toBe('');
    $this->deleteJson("/api/v1/dishes/{$dish->id}")->assertNotFound();
    expect(Dish::withTrashed()->find($dish->id))->not->toBeNull();
});

it('answers 405 for a verb the collection does not support', function () {
    $this->deleteJson('/api/v1/dishes')->assertStatus(405)->assertJsonPath('error.code', 'method_not_allowed');
});
