<?php

namespace Database\Factories;

use App\Models\ItemCollection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<ItemCollection> */
class ItemCollectionFactory extends Factory
{
    protected $model = ItemCollection::class;

    public function definition(): array
    {
        $name = ucfirst(fake()->unique()->words(2, true));

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('###'),
            'attribute_status' => 'demo',
        ];
    }
}
