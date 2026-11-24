<?php

namespace Database\Seeders;

use App\Models\Dish;
use App\Models\Restaurant;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /** Twelve restaurants, forty dishes across the first three. Run: php artisan migrate:fresh --seed */
    public function run(): void
    {
        $restaurants = collect([
            ['Yod Abyssinia', 'Bole', '0911000001'],
            ['Kategna', 'Kazanchis', '0911000002'],
            ['Sishu', 'Piassa', '0911000003'],
            ['Habesha 2000', 'Bole', '0911000004'],
            ['Totot', 'Megenagna', '0911000005'],
            ['Lucy Lounge', 'Piassa', '0911000006'],
            ['Effoi Pizza', 'Bole', '0911000007'],
            ['Saro Maria', 'Kazanchis', '0911000008'],
            ['Tomoca', 'Piassa', '0911000009'],
            ['Chanoly', 'CMC', '0911000010'],
            ['Ayat Grill', 'Ayat', '0911000011'],
            ['Gusto', 'Megenagna', '0911000012'],
        ])->map(fn (array $r) => Restaurant::create([
            'name' => $r[0], 'area' => $r[1], 'phone' => $r[2], 'opening_hours' => '08:00-22:00',
        ]));

        $owners = $restaurants->take(3)->values();

        foreach (range(1, 40) as $i) {
            $owner = $owners[$i % 3];
            Dish::factory()->create([
                'restaurant_id' => $owner->id,
                'restaurant' => $owner->name,
                'area' => $owner->area,
            ]);
        }
    }
}
