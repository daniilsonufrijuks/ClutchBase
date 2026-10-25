<?php

namespace Database\Factories;

use App\Models\Rarity;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Rarity> */
class RarityFactory extends Factory
{
    protected $model = Rarity::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('###'),
            'color' => '#4b69ff',
            'sort_order' => fake()->numberBetween(1, 8),
        ];
    }
}
