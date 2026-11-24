<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer_name' => $this->customer_name,
            'area' => $this->area,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'subtotal_santim' => $this->subtotal,
            'fee_santim' => $this->fee,
            'total_santim' => $this->total,
            'total_etb' => Money::decimal($this->total),
            'paid' => $this->paid_at !== null,
            'paid_at' => $this->paid_at?->toIso8601String(),
            'cancellation_reason' => $this->when($this->cancellation_reason !== null, $this->cancellation_reason),
            'lines' => OrderLineResource::collection($this->whenLoaded('lines')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
