<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Pricing\DeliveryZones;
use App\Http\Controllers\Api\V1\Concerns\ListsResources;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\DishResource;
use App\Http\Responses\ApiResponse;
use App\Models\Restaurant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** The dishes OF a restaurant: list and create need the parent; everything else uses /dishes/{dish}. */
class RestaurantDishController extends Controller
{
    use ListsResources;

    private const SORTABLE = ['name', 'price', 'created_at'];

    public function __construct(private readonly DeliveryZones $zones) {}

    public function index(Request $request, Restaurant $restaurant): JsonResponse
    {
        [$column, $direction] = $this->sort($request, self::SORTABLE, 'price');

        $dishes = $restaurant->dishes()
            ->under($request->filled('max_price') ? $request->integer('max_price') : null)
            ->fasting($request->boolean('fasting'))
            ->search($request->filled('q') ? $request->string('q')->trim()->toString() : null)
            ->orderBy($column, $direction)
            ->orderBy('id')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return ApiResponse::paginated(DishResource::collection($dishes));
    }

    public function store(Request $request, Restaurant $restaurant): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'price' => ['required', 'integer', 'min:1', 'max:100000000'],
            'is_fasting' => ['required', 'boolean'],
        ]);

        // Restaurant name and area come from the parent, not from the client.
        $dish = $restaurant->dishes()->create($data + [
            'restaurant' => $restaurant->name,
            'area' => $restaurant->area,
        ]);

        return ApiResponse::created(new DishResource($dish), route('v1.dishes.show', $dish));
    }
}
