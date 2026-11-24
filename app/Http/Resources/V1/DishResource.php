<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** The only place that decides what a Dish looks like to the outside world. cost_price never leaves. */
class DishResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'restaurant' => $this->restaurant?->name,   // relationship, eager-loaded by the controller
            'price_etb' => Money::decimal($this->price),
            'price_santim' => $this->price,
            'fasting' => $this->is_fasting,
            'area' => $this->area,
        ];
    }
}
