<?php

declare(strict_types=1);

use App\Models\Dish;
use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    $this->yod = Restaurant::create(['name' => 'Yod Abyssinia', 'area' => 'Bole', 'phone' => '0911000001']);
    $this->sishu = Restaurant::create(['name' => 'Sishu', 'area' => 'Piassa', 'phone' => '0911000003']);
    $this->doro = $this->yod->dishes()->create(['name' => 'Doro Wat', 'price' => 42000, 'is_fasting' => false, 'area' => 'Bole']);
    $this->shiro = $this->yod->dishes()->create(['name' => 'Shiro', 'price' => 18000, 'is_fasting' => true, 'area' => 'Bole']);
    $this->burger = $this->sishu->dishes()->create(['name' => 'Burger', 'price' => 35000, 'is_fasting' => false, 'area' => 'Piassa']);
    $this->order = fn (array $over = []) => array_replace([
        'customer_name' => 'Almaz', 'phone' => '0911234567', 'area' => 'Bole',
        'lines' => [['dish_id' => $this->doro->id, 'qty' => 1]],
    ], $over);
});

/*
 * The sixteen bad requests from bad_requests.http. Each must be a 4xx in the one
 * envelope, and none may be a 500.
 */
it('answers every malformed payload with the right code and never a 500', function (string $method, string $uri, mixed $body, int $status, string $code, ?string $field) {
    $uri = str_replace(['{doro}', '{burger}'], [$this->doro->id, $this->burger->id], $uri);
    if (is_callable($body)) {
        $body = $body($this);
    }
    $res = is_string($body)
        ? $this->call($method, $uri, [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'], $body)
        : $this->json($method, $uri, $body);

    $res->assertStatus($status)->assertJsonPath('error.code', $code)->assertJsonStructure(['error' => ['code', 'message', 'meta']]);
    if ($field !== null) {
        expect($res->json('error.meta'))->toHaveKey($field);
    }
})->with([
    '01 empty dish body' => ['POST', '/api/v1/dishes', [], 422, 'validation_failed', 'name'],
    '02 negative price' => ['POST', '/api/v1/dishes', ['name' => 'X', 'restaurant_id' => 1, 'price' => -500, 'area' => 'Bole'], 422, 'validation_failed', 'price'],
    '03 price as decimal ETB' => ['POST', '/api/v1/dishes', ['name' => 'X', 'restaurant_id' => 1, 'price' => 685.5, 'area' => 'Bole'], 422, 'validation_failed', 'price'],
    '04 price as boolean' => ['POST', '/api/v1/dishes', ['name' => 'X', 'restaurant_id' => 1, 'price' => true, 'area' => 'Bole'], 422, 'validation_failed', 'price'],
    '05 unknown restaurant' => ['POST', '/api/v1/dishes', ['name' => 'X', 'restaurant_id' => 999, 'price' => 100, 'area' => 'Bole'], 422, 'validation_failed', 'restaurant_id'],
    '06 unserved area' => ['POST', '/api/v1/orders', fn ($t) => ($t->order)(['area' => 'Sululta']), 422, 'validation_failed', 'area'],
    '07 short phone' => ['POST', '/api/v1/orders', fn ($t) => ($t->order)(['phone' => '12345']), 422, 'validation_failed', 'phone'],
    '08 no lines' => ['POST', '/api/v1/orders', fn ($t) => ($t->order)(['lines' => []]), 422, 'validation_failed', 'lines'],
    '09 too many lines' => ['POST', '/api/v1/orders', fn ($t) => ($t->order)(['lines' => array_fill(0, 31, ['dish_id' => $t->doro->id, 'qty' => 1])]), 422, 'validation_failed', 'lines'],
    '10 qty out of range' => ['POST', '/api/v1/orders', fn ($t) => ($t->order)(['lines' => [['dish_id' => $t->doro->id, 'qty' => 1], ['dish_id' => $t->shiro->id, 'qty' => 51]]]), 422, 'validation_failed', 'lines.1.qty'],
    '11 dish that never existed' => ['POST', '/api/v1/orders', fn ($t) => ($t->order)(['lines' => [['dish_id' => 9999, 'qty' => 1]]]), 422, 'validation_failed', 'lines.0.dish_id'],
    '12 dishes from two areas' => ['POST', '/api/v1/orders', fn ($t) => ($t->order)(['lines' => [['dish_id' => $t->doro->id, 'qty' => 1], ['dish_id' => $t->burger->id, 'qty' => 1]]]), 422, 'validation_failed', 'lines'],
    '13 client tries to set the price' => ['POST', '/api/v1/orders', fn ($t) => ($t->order)(['lines' => [['dish_id' => $t->doro->id, 'qty' => 1, 'price' => 1]]]), 422, 'validation_failed', 'lines.0'],
    '14 truncated JSON body' => ['POST', '/api/v1/orders', '{"customer_name": "Almaz", "lines": [', 400, 'malformed_json', null],
    '15 PATCH price as text' => ['PATCH', '/api/v1/dishes/{doro}', ['price' => 'two hundred'], 422, 'validation_failed', 'price'],
    '16 restaurant with a non-TeleBirr phone' => ['POST', '/api/v1/restaurants', ['name' => 'Totot', 'area' => 'Megenagna', 'phone' => '0111234567'], 422, 'validation_failed', 'phone'],
]);

it('validates status and payment payloads on a real order', function () {
    $id = $this->postJson('/api/v1/orders', ($this->order)())->assertCreated()->json('data.id');
    $this->patchJson("/api/v1/orders/{$id}/status", ['status' => 'teleported'])->assertStatus(422)->assertJsonPath('error.code', 'validation_failed');
    $this->patchJson("/api/v1/orders/{$id}/status", ['status' => 'cancelled'])->assertStatus(422);
    $this->putJson("/api/v1/orders/{$id}/payment", ['reference' => 'tb 100'])->assertStatus(422)->assertJsonPath('error.meta.reference.0', 'A payment reference uses capital letters, digits and hyphens, for example TB-100234.');
    $this->patchJson("/api/v1/orders/{$id}/status", ['status' => 'delivered'])->assertStatus(409)->assertJsonPath('error.code', 'illegal_transition');
});

it('normalises international and spaced phone numbers before validating', function (string $typed) {
    $res = $this->postJson('/api/v1/orders', ($this->order)(['phone' => $typed]))->assertCreated();
    expect(Order::find($res->json('data.id'))->phone)->toBe('0911234567');
})->with(['+251911234567', '251911234567', '+251 911 234 567', '0911-234-567', '00251911234567']);

it('returns 409, not 422, for a dish withdrawn after the menu was loaded', function () {
    $this->shiro->delete();
    $this->postJson('/api/v1/orders', ($this->order)(['lines' => [['dish_id' => $this->shiro->id, 'qty' => 1]]]))
        ->assertStatus(409)->assertJsonPath('error.code', 'dish_withdrawn')->assertJsonPath('error.meta.dish_ids', [$this->shiro->id]);
});

it('drops fields that have no rule (validated(), not all())', function () {
    $id = $this->postJson('/api/v1/dishes', ['name' => 'Kitfo', 'restaurant_id' => $this->yod->id, 'price' => 38000,
        'area' => 'Bole', 'cost_price' => 1, 'is_admin' => true])->assertCreated()->json('data.id');
    expect(Dish::find($id)->cost_price)->toBeNull();
});

it('lets a PATCH send one field, and makes a PUT send them all', function () {
    $this->patchJson("/api/v1/dishes/{$this->doro->id}", ['price' => 43000])->assertOk();
    $this->putJson("/api/v1/dishes/{$this->doro->id}", ['price' => 43000])->assertStatus(422)
        ->assertJsonStructure(['error' => ['meta' => ['name', 'restaurant_id', 'area']]]);
});

it('keeps unique slugs working on update (ignore the row itself)', function () {
    $this->patchJson('/api/v1/restaurants/yod-abyssinia', ['slug' => 'yod-abyssinia', 'name' => 'Yod Abyssinia Bole'])->assertOk();
    $this->patchJson('/api/v1/restaurants/yod-abyssinia', ['slug' => 'sishu'])->assertStatus(422)->assertJsonPath('error.code', 'validation_failed');
    $this->postJson('/api/v1/restaurants', ['name' => 'Sishu', 'area' => 'Bole', 'phone' => '0911000009'])
        ->assertStatus(422)->assertJsonStructure(['error' => ['meta' => ['slug']]]);
});

it('hides internals behind a reference on an unexpected failure', function () {
    Route::get('/api/v1/boom', fn () => throw new RuntimeException('SQLSTATE secret table users_private'));
    $res = $this->getJson('/api/v1/boom')->assertStatus(500)->assertJsonPath('error.code', 'server_error');
    expect($res->json('error.meta.ref'))->toMatch('/^err_[a-z0-9]{8}$/')
        ->and($res->getContent())->not->toContain('SQLSTATE')->not->toContain('.php');
});

it('publishes every error code the API can emit in ERRORS.md', function () {
    $doc = file_get_contents(base_path('ERRORS.md'));
    $emitted = collect(File::allFiles(app_path()))
        ->merge([new SplFileInfo(base_path('bootstrap/app.php'))])
        ->flatMap(fn ($f) => preg_match_all("/ApiResponse::error\\(\\s*'([a-z_]+)'/", file_get_contents($f->getPathname()), $m) ? $m[1] : [])
        ->reject(fn (string $c) => str_ends_with($c, '_')) // 'http_'.$status is documented as http_<status>
        ->unique()->values();
    expect($emitted->count())->toBeGreaterThan(10);
    foreach ($emitted as $code) {
        expect($doc)->toContain("`{$code}`");
    }
});

it('asks for a Latin slug when one cannot be made from the name', function () {
    $this->postJson('/api/v1/restaurants', ['name' => 'ዮድ አቢሲኒያ', 'area' => 'Bole', 'phone' => '0911000010'])
        ->assertStatus(422)->assertJsonStructure(['error' => ['meta' => ['slug']]]);
    $this->postJson('/api/v1/restaurants', ['name' => 'ዮድ አቢሲኒያ', 'slug' => 'yod-amharic', 'area' => 'Bole', 'phone' => '0911000010'])
        ->assertCreated()->assertJsonPath('data.name', 'ዮድ አቢሲኒያ');
});

it('applies the 50 cap to a dish split across several lines', function () {
    $split = fn (int $a, int $b) => ($this->order)(['lines' => [
        ['dish_id' => $this->doro->id, 'qty' => $a], ['dish_id' => $this->doro->id, 'qty' => $b],
    ]]);

    $this->postJson('/api/v1/orders', $split(30, 30))
        ->assertStatus(422)->assertJsonPath('error.code', 'validation_failed')
        ->assertJsonPath('error.meta.lines.0', "At most 50 of one dish per order (dish {$this->doro->id}).");
    expect(Order::count())->toBe(0);

    $this->postJson('/api/v1/orders', $split(25, 25))->assertCreated()
        ->assertJsonPath('data.lines.0.qty', 50);
});

it('keeps validation out of controllers: no all(), only(), validate() or try/catch', function () {
    $offenders = collect(File::allFiles(app_path('Http/Controllers')))
        ->filter(fn ($f) => preg_match('/->(all|only|validate)\(|\btry\s*\{/', file_get_contents($f->getPathname())) === 1)
        ->map->getFilename()->values()->all();

    expect($offenders)->toBe([]);
});

it('treats an empty JSON body as empty, not malformed', function () {
    $this->call('DELETE', "/api/v1/dishes/{$this->doro->id}", [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'], '')
        ->assertNoContent();
});

it('answers bad order ids with the right 404', function () {
    $this->getJson('/api/v1/orders/abc')->assertNotFound()->assertJsonPath('error.code', 'route_not_found');
    $this->getJson('/api/v1/orders/999')->assertNotFound()->assertJsonPath('error.code', 'not_found');
});
