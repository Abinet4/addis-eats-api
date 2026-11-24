<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Pricing\DeliveryZones;
use App\Http\Controllers\Api\V1\Concerns\ListsResources;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\RestaurantResource;
use App\Http\Responses\ApiResponse;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

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

    public function store(Request $request): JsonResponse
    {
        $restaurant = Restaurant::create($request->validate($this->rules(false)));

        return ApiResponse::created(new RestaurantResource($restaurant), route('v1.restaurants.show', $restaurant));
    }

    public function update(Request $request, Restaurant $restaurant): JsonResponse
    {
        $restaurant->update($request->validate($this->rules($request->isMethod('patch'), $restaurant)));

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

    private function rules(bool $partial, ?Restaurant $current = null): array
    {
        $req = $partial ? 'sometimes' : 'required';

        return [
            'name' => [$req, 'string', 'max:100'],
            'slug' => ['sometimes', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('restaurants', 'slug')->ignore($current?->id)],
            'area' => [$req, Rule::in($this->zones->names())],
            'phone' => [$req, 'string', 'regex:/^(\+2519|09|07)\d{8}$/'],
            'opening_hours' => ['sometimes', 'nullable', 'string', 'max:50'],
        ];
    }
}
