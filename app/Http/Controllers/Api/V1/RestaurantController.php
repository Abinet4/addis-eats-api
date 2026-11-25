<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Pricing\DeliveryZones;
use App\Http\Controllers\Api\V1\Concerns\ListsResources;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StoreRestaurantRequest;
use App\Http\Requests\V1\UpdateRestaurantRequest;
use App\Http\Resources\V1\RestaurantResource;
use App\Http\Responses\ApiResponse;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RestaurantController extends Controller
{
    use ListsResources;

    private const SORTABLE = ['name', 'created_at'];

    public function __construct(private readonly DeliveryZones $zones) {}

    /** GET /api/v1/restaurants?area=&q=&sort=&include=dishes&page=&per_page= */
    public function index(Request $request): JsonResponse
    {
        [$column, $direction] = $this->sort($request, self::SORTABLE, 'name');

        $restaurants = Restaurant::query()
            ->withCount('dishes')                                  // a subquery, not N extra queries
            ->when($this->includesDishes($request), fn (Builder $q) => $q->with('dishes'))
            ->when($request->filled('area'), fn (Builder $q) => $q->where('area', $request->string('area')->toString()))
            ->when($request->filled('q'), fn (Builder $q) => $q->where('name', 'like', '%'.$request->string('q')->trim().'%'))
            ->orderBy($column, $direction)
            ->orderBy('id')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return ApiResponse::paginated(RestaurantResource::collection($restaurants));
    }

    public function show(Request $request, Restaurant $restaurant): JsonResponse
    {
        $restaurant->loadCount('dishes');
        if ($this->includesDishes($request)) {
            $restaurant->load('dishes');
        }

        return ApiResponse::ok(new RestaurantResource($restaurant));
    }

    public function store(StoreRestaurantRequest $request): JsonResponse
    {
        $restaurant = Restaurant::create($request->validated());

        return ApiResponse::created(new RestaurantResource($restaurant), route('v1.restaurants.show', $restaurant));
    }

    public function update(UpdateRestaurantRequest $request, Restaurant $restaurant): JsonResponse
    {
        $restaurant->update($request->validated());

        return ApiResponse::ok(new RestaurantResource($restaurant->refresh()));
    }

    public function destroy(Restaurant $restaurant): Response
    {
        $restaurant->delete(); // dishes keep existing; restaurant_id is set to null by the FK

        return ApiResponse::noContent();
    }

    /** include is a comma list (?include=dishes,reviews later), so split rather than compare with ===. */
    private function includesDishes(Request $request): bool
    {
        return in_array('dishes', explode(',', (string) $request->query('include', '')), true);
    }
}
