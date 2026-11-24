<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dish extends Model
{
    use HasFactory, SoftDeletes;

    /** What a client may mass-assign. cost_price is deliberately absent. */
    protected $fillable = [
        'name', 'price', 'is_fasting', 'area', 'restaurant_id',
    ];

    protected function casts(): array
    {
        return [
            'is_fasting' => 'boolean',
            'price' => 'integer',
            'cost_price' => 'integer',
        ];
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function orderLines(): HasMany
    {
        return $this->hasMany(OrderLine::class);
    }

    // ---- Query scopes (Day 56). Each is a no-op when its argument is empty. ----

    public function scopeInArea(Builder $query, ?string $area): Builder
    {
        return $query->when($area, fn (Builder $q) => $q->where('area', $area));
    }

    public function scopeUnder(Builder $query, ?int $santim): Builder
    {
        // !== null, not truthiness: a max_price of 0 must return nothing, not everything.
        return $query->when($santim !== null, fn (Builder $q) => $q->where('price', '<=', $santim));
    }

    public function scopeFasting(Builder $query, bool $only): Builder
    {
        return $query->when($only, fn (Builder $q) => $q->where('is_fasting', true));
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        // LIKE wildcards in the user's term are escaped so "%" searches for a literal percent sign.
        return $query->when($term, function (Builder $q) use ($term) {
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $term);
            $q->whereRaw("lower(name) like ? escape '\\'", ['%'.mb_strtolower($escaped).'%']);
        });
    }
}
