<?php

namespace Database\Factories;

use App\Models\Weapon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Weapon> */
class WeaponFactory extends Factory
{
    protected $model = Weapon::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('###'),
            'category' => 'rifle',
        ];
    }
}
