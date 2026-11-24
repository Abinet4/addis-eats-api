<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderLine extends Model
{
    protected $fillable = ['dish_id', 'dish_name', 'unit_price', 'qty'];

    protected function casts(): array
    {
        return ['unit_price' => 'integer', 'qty' => 'integer'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function dish(): BelongsTo
    {
        return $this->belongsTo(Dish::class)->withTrashed();
    }
}
