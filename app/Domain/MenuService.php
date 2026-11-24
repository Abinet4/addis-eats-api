<?php

declare(strict_types=1);

namespace App\Domain;

use App\Domain\Pricing\DeliveryZones;
use App\Exceptions\OutsideZoneException;
use App\Models\Dish as DishModel;
use App\Support\FormatsEtb;
use Illuminate\Support\Collection;

/**
 * Day 53 -> Day 54. The Day 53 version took a Collection in its constructor; the
 * container cannot know which collection you mean (it would silently inject an
 * EMPTY one), so the data now comes from Eloquent and the zone list from config.
 */
final class MenuService
{
    use FormatsEtb;

    public function __construct(private readonly DeliveryZones $zones) {}

    /**
     * Dishes in $area priced at or under $budget santim, cheapest first.
     *
     * @return Collection<int, array{id: int, name: string, restaurant: string, price: string, price_santim: int}>
     *
     * @throws OutsideZoneException
     */
    public function affordableIn(string $area, int $budget): Collection
    {
        if (! $this->zones->serves($area)) {
            throw new OutsideZoneException($area);
        }

        return DishModel::query()
            ->with('restaurant')
            ->inArea($area)
            ->under($budget)          // filtered in SQL, not in PHP
            ->orderBy('price')
            ->orderBy('id')           // deterministic tie-break
            ->get()
            ->map(fn (DishModel $d) => [
                'id' => $d->id,
                'name' => $d->name,
                'restaurant' => $d->restaurant?->name,
                'price' => $this->toEtb($d->price),
                'price_santim' => $d->price,
            ])
            ->values();
    }
}
