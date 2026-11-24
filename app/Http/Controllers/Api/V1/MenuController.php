<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\MenuService;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** GET /api/v1/menu?area=Bole&budget=25000. Thin: read two values, call the service. */
class MenuController extends Controller
{
    public function __construct(private readonly MenuService $menu) {}

    public function __invoke(Request $request): JsonResponse
    {
        $area = $request->string('area', 'Bole')->trim()->toString();
        $budget = max(0, $request->integer('budget', 100000));

        // OutsideZoneException renders itself as a 422, so no try/catch is needed here.
        $dishes = $this->menu->affordableIn($area, $budget);

        return ApiResponse::ok($dishes, ['count' => $dishes->count(), 'area' => $area, 'budget_santim' => $budget]);
    }
}
