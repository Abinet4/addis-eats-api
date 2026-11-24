<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Dish;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Dish> */
class DishFactory extends Factory
{
    public function definition(): array
    {
        $price = fake()->numberBetween(120, 600) * 100; // whole birr, 120.00 to 600.00 ETB

        return [
            'name' => fake()->randomElement([
                'Doro Wat', 'Shiro', 'Kitfo', 'Tibs', 'Firfir', 'Misir Wat',
                'Beyaynetu', 'Gomen', 'Dulet', 'Ful', 'Chechebsa', 'Tegabino',
            ]),
            'restaurant_id' => Restaurant::factory(),
            'price' => $price,
            'cost_price' => intdiv($price, 2),
            'is_fasting' => fake()->boolean(),
            'area' => fake()->randomElement(array_keys(config('addiseats.zones'))),
        ];
    }
}
