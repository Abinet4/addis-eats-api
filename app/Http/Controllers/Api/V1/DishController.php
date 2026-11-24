<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Pricing\DeliveryZones;
use App\Exceptions\OutsideZoneException;
use App\Http\Controllers\Api\V1\Concerns\ListsResources;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\DishResource;
use App\Http\Responses\ApiResponse;
use App\Models\Dish;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class DishController extends Controller
{
    use ListsResources;

    private const SORTABLE = ['name', 'price', 'created_at'];

    public function __construct(private readonly DeliveryZones $zones) {}

    /** GET /api/v1/dishes?area=&max_price=&fasting=&q=&sort=&page=&per_page= */
    public function index(Request $request): JsonResponse
    {
        $area = $request->filled('area') ? $request->string('area')->toString() : null;
        if ($area !== null && ! $this->zones->serves($area)) {
            throw new OutsideZoneException($area);
        }

        [$column, $direction] = $this->sort($request, self::SORTABLE, 'price');

        $dishes = Dish::query()
            ->with('restaurant')                // one extra query, not one per dish
            ->inArea($area)
            ->under($request->filled('max_price') ? $request->integer('max_price') : null)
            ->fasting($request->boolean('fasting'))
            ->search($request->filled('q') ? $request->string('q')->trim()->toString() : null)
            ->orderBy($column, $direction)
            ->orderBy('id')                    // stable order across pages
            ->paginate($this->perPage($request))
            ->withQueryString();               // page 2 keeps the filters

        return ApiResponse::paginated(DishResource::collection($dishes));
    }

    public function show(Dish $dish): JsonResponse
    {
        return ApiResponse::ok(new DishResource($dish->load('restaurant')));
    }

    public function store(Request $request): JsonResponse
    {
        $dish = Dish::create($request->validate($this->rules(partial: false)));

        return ApiResponse::created(new DishResource($dish->load('restaurant')), route('v1.dishes.show', $dish));
    }

    /** PUT replaces (every field required), PATCH changes only what is sent. */
    public function update(Request $request, Dish $dish): JsonResponse
    {
        $dish->update($request->validate($this->rules(partial: $request->isMethod('patch'))));

        return ApiResponse::ok(new DishResource($dish->refresh()->load('restaurant')));
    }

    public function destroy(Dish $dish): Response
    {
        $dish->delete(); // soft delete: past orders can still name it

        return ApiResponse::noContent();
    }

    /** Minimal rules so bad input is a 422, never a 500. Day 58 moves these into Form Requests. */
    private function rules(bool $partial): array
    {
        $req = $partial ? 'sometimes' : 'required';

        return [
            'name' => [$req, 'string', 'max:100'],
            'price' => [$req, 'integer', 'min:1', 'max:100000000'],
            'is_fasting' => [$req, 'boolean'],
            'area' => [$req, Rule::in($this->zones->names())],
            'restaurant_id' => [$req, 'integer', 'exists:restaurants,id'],
        ];
    }
}
