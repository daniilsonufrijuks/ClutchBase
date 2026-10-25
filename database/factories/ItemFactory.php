<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\Rarity;
use App\Models\Weapon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Item> */
class ItemFactory extends Factory
{
    protected $model = Item::class;

    public function definition(): array
    {
        $name = ucfirst(fake()->unique()->words(3, true));

        return [
            'type' => 'skin',
            'weapon_id' => Weapon::factory(),
            'rarity_id' => Rarity::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('###'),
            'status' => 'published',
            'attribute_status' => 'demo',
            'description' => 'Demo catalog record.',
        ];
    }
}
