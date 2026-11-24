<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Restaurant extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'area', 'phone', 'opening_hours'];

    /** Route model binding looks restaurants up by slug everywhere, nested routes included. */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected static function booted(): void
    {
        // Slugs are generated, not typed: "Yod Abyssinia" -> "yod-abyssinia".
        static::saving(function (Restaurant $r) {
            if (blank($r->slug)) {
                $r->slug = Str::slug($r->name);
            }
        });
    }

    public function dishes(): HasMany
    {
        return $this->hasMany(Dish::class);
    }
}
