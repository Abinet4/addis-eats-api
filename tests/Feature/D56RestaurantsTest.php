<?php

declare(strict_types=1);

use App\Models\Dish;
use App\Models\Restaurant;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->yod = Restaurant::create(['name' => 'Yod Abyssinia', 'area' => 'Bole', 'phone' => '0911000001']);
    $this->kat = Restaurant::create(['name' => 'Kategna', 'area' => 'Kazanchis', 'phone' => '0911000002']);
    foreach ([['Doro Wat', 42000], ['Shiro', 18000], ['Doro Tibs', 30000]] as [$n, $p]) {
        $this->yod->dishes()->create(['name' => $n, 'price' => $p, 'is_fasting' => false, 'area' => 'Bole']);
    }
});

it('generates slugs and binds on them, hyphens included', function () {
    expect($this->yod->slug)->toBe('yod-abyssinia');
    $this->getJson('/api/v1/restaurants/yod-abyssinia')->assertOk()
        ->assertJsonPath('data.name', 'Yod Abyssinia')->assertJsonPath('data.dish_count', 3);
    $this->getJson('/api/v1/restaurants/no-such-place')->assertNotFound()->assertJsonPath('error.code', 'not_found');
    $this->getJson('/api/v1/restaurants/Yod_Abyssinia')->assertNotFound()->assertJsonPath('error.code', 'route_not_found');
});

it('lists a restaurant\'s dishes, sorted by the whitelist', function () {
    $this->getJson('/api/v1/restaurants/yod-abyssinia/dishes?sort=-price')
        ->assertJsonPath('data.*.name', ['Doro Wat', 'Doro Tibs', 'Shiro']);
    // a numeric id does not bind when the key is the slug (the slide's /restaurants/1/dishes)
    $this->getJson('/api/v1/restaurants/1/dishes')->assertNotFound();
});

it('falls back to the default sort for a column outside the whitelist', function () {
    $this->getJson('/api/v1/dishes?sort=-cost_price')->assertOk()
        ->assertJsonPath('data.*.name', ['Shiro', 'Doro Tibs', 'Doro Wat']);
    $this->getJson('/api/v1/dishes?sort=price;drop table dishes')->assertOk();
    expect(Dish::count())->toBe(3);
});

it('searches by name case-insensitively and treats % literally', function () {
    $this->getJson('/api/v1/dishes?q=doro')->assertJsonCount(2, 'data');
    $this->getJson('/api/v1/dishes?q=DORO&area=Bole')->assertJsonCount(2, 'data');
    $this->getJson('/api/v1/dishes?q=%25')->assertJsonCount(0, 'data');
});

it('includes dishes only when asked, using whenLoaded', function () {
    $plain = $this->getJson('/api/v1/restaurants')->json('data.0');
    expect($plain)->not->toHaveKey('dishes')->and($plain)->toHaveKey('dish_count');
    $this->getJson('/api/v1/restaurants?include=dishes')->assertJsonCount(3, 'data.1.dishes');
    $this->getJson('/api/v1/restaurants/yod-abyssinia?include=dishes')->assertJsonCount(3, 'data.dishes');
});

it('does not run one query per restaurant (no N+1)', function () {
    foreach (range(1, 38) as $i) {
        Restaurant::factory()->create();
    }
    $count = function (string $url) {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->getJson($url)->assertOk();

        return count(DB::getQueryLog());
    };
    $without = $count('/api/v1/restaurants?per_page=100');
    $with = $count('/api/v1/restaurants?per_page=100&include=dishes');
    // paginate() adds a COUNT(*) query; withCount is a subquery inside the main select
    expect($without)->toBe(2)->and($with)->toBe(3);
});

it('creates a dish under a restaurant, inheriting name and area', function () {
    $res = $this->postJson('/api/v1/restaurants/kategna/dishes', [
        'name' => 'Kitfo', 'price' => 38000, 'is_fasting' => false, 'area' => 'Ayat',
    ])->assertCreated();
    expect($res->json('data.restaurant'))->toBe('Kategna')->and($res->json('data.area'))->toBe('Kazanchis');
});

it('runs restaurant CRUD', function () {
    $res = $this->postJson('/api/v1/restaurants', ['name' => 'Totot', 'area' => 'Megenagna', 'phone' => '0911223344'])
        ->assertCreated()->assertJsonPath('data.slug', 'totot');
    expect($res->headers->get('Location'))->toEndWith('/api/v1/restaurants/totot');
    $this->patchJson('/api/v1/restaurants/totot', ['phone' => '+251 911 223 355'])->assertOk()->assertJsonPath('data.phone', '0911223355');
    $this->postJson('/api/v1/restaurants', ['name' => 'X', 'area' => 'Bole', 'phone' => '123'])->assertStatus(422);
    $this->deleteJson('/api/v1/restaurants/totot')->assertNoContent();
});

it('exposes both sides of the Restaurant and Dish relationship', function () {
    $dish = $this->yod->dishes()->first();
    expect($dish->restaurant->is($this->yod))->toBeTrue()
        ->and($this->yod->dishes)->toHaveCount(3)
        ->and($this->yod->dishes->first()->relationLoaded('restaurant'))->toBeTrue(); // chaperone
    $this->getJson("/api/v1/dishes/{$dish->id}")->assertJsonPath('data.restaurant', 'Yod Abyssinia');
});

it('lists dishes with their restaurant in two queries, whatever the page size', function () {
    Dish::factory()->count(30)->create();
    DB::flushQueryLog();
    DB::enableQueryLog();
    $this->getJson('/api/v1/dishes?per_page=50')->assertOk()->assertJsonCount(33, 'data');
    // count(*) for the paginator, the dishes page, and one IN query for restaurants
    expect(count(DB::getQueryLog()))->toBe(3);
});
