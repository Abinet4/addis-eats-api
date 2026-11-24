<?php

declare(strict_types=1);

use App\Domain\Pricing\PricingRule;
use App\Domain\Pricing\PromoRule;
use App\Domain\Pricing\StandardRule;
use App\Models\Dish;
use Illuminate\Support\Facades\File;

it('answers the Day 52 routes', function () {
    $this->getJson('/api/health')->assertOk()->assertJsonStructure(['status', 'service', 'time'])
        ->assertJson(['status' => 'ok', 'service' => 'addis-eats-api']);
    $this->getJson('/api/areas')->assertOk()->assertJsonCount(6);
    $this->getJson('/api/quote')->assertExactJson(['subtotal' => 85000, 'fee' => 0, 'total' => 85000]);
});

it('serves the menu for an area within budget, cheapest first', function () {
    Dish::factory()->create(['name' => 'Kitfo', 'area' => 'Bole', 'price' => 38000]);
    Dish::factory()->create(['name' => 'Shiro', 'area' => 'Bole', 'price' => 18000]);
    Dish::factory()->create(['name' => 'Tibs', 'area' => 'Bole', 'price' => 22000]);
    Dish::factory()->create(['name' => 'Firfir', 'area' => 'Piassa', 'price' => 12000]);

    $this->getJson('/api/v1/menu?area=Bole&budget=25000')
        ->assertOk()
        ->assertJsonPath('data.*.name', ['Shiro', 'Tibs'])
        ->assertJsonPath('data.0.price', '180.00 ETB')
        ->assertJsonPath('meta.count', 2);
});

it('returns an empty list, not an object, when nothing is affordable', function () {
    Dish::factory()->create(['area' => 'Bole', 'price' => 18000]);
    $res = $this->getJson('/api/v1/menu?area=Bole&budget=0')->assertOk();
    expect($res->getContent())->toContain('"data":[]');
});

it('returns 422 with the domain message for an unserved area', function () {
    $this->getJson('/api/v1/menu?area=Sululta')
        ->assertStatus(422)
        ->assertExactJson(['error' => [
            'code' => 'outside_delivery_zone',
            'message' => 'Addis Eats does not deliver to Sululta yet.',
            'meta' => ['area' => 'Sululta'],
        ]]);
});

it('binds PricingRule to StandardRule and swaps it from config alone', function () {
    expect(app(PricingRule::class))->toBeInstanceOf(StandardRule::class);
    config(['addiseats.pricing_rule' => 'promo']);
    expect(app(PricingRule::class))->toBeInstanceOf(PromoRule::class);
});

it('never calls env() outside config/', function () {
    $hits = collect(File::allFiles(app_path()))
        ->filter(fn ($f) => str_contains($f->getContents(), 'env('))
        ->map->getRelativePathname()->values()->all();
    expect($hits)->toBe([]);
});

it('never instantiates a service with new inside a controller', function () {
    $hits = collect(File::allFiles(app_path('Http/Controllers')))
        ->filter(fn ($f) => preg_match('/new\s+[A-Z]\w*(Service|Rule)\b/', $f->getContents()))
        ->map->getRelativePathname()->values()->all();
    expect($hits)->toBe([]);
});
