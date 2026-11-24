<?php

namespace Database\Factories;

use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Restaurant> */
class RestaurantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'area' => fake()->randomElement(array_keys(config('addiseats.zones'))),
            'phone' => '09'.fake()->numerify('########'),
            'opening_hours' => '08:00-22:00',
        ];
    }
}
