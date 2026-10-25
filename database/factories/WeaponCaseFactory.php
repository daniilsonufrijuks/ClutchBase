<?php

namespace Database\Factories;

use App\Models\WeaponCase;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<WeaponCase> */
class WeaponCaseFactory extends Factory
{
    protected $model = WeaponCase::class;

    public function definition(): array
    {
        $name = ucfirst(fake()->unique()->words(2, true)).' Case';

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('###'),
            'status' => 'unknown',
            'category' => 'case',
            'attribute_status' => 'demo',
            'description' => 'Demo case record.',
        ];
    }
}
