<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Dish;
use App\Models\Restaurant;
use Illuminate\Database\Seeder;

/** Day 57: 1,200 dishes so pagination is visible. php artisan db:seed --class=LargeDishSeeder */
class LargeDishSeeder extends Seeder
{
    public function run(): void
    {
        $owners = Restaurant::query()->get();

        foreach (array_chunk(range(1, 1200), 200) as $chunk) {
            $rows = Dish::factory()->count(count($chunk))->make()->map(function (Dish $d) use ($owners) {
                $owner = $owners->random();

                return $d->forceFill([
                    'restaurant_id' => $owner?->id,
                    'area' => $owner?->area ?? $d->area,
                    'created_at' => now(), 'updated_at' => now(),
                ])->getAttributes();
            });
            Dish::insert($rows->all());
        }
    }
}
