<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderLineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'dish_id' => $this->dish_id,
            'dish_name' => $this->dish_name,
            'qty' => $this->qty,
            'unit_price_santim' => $this->unit_price,
            'line_total_etb' => Money::decimal($this->unit_price * $this->qty),
        ];
    }
}
