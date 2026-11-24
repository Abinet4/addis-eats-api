<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\OrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    // status, totals and payment fields are set by OrderService only, never from a request.
    protected $fillable = ['customer_name', 'phone', 'area'];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'subtotal' => 'integer',
            'fee' => 'integer',
            'total' => 'integer',
            'paid_at' => 'datetime',
        ];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(OrderLine::class);
    }
}
