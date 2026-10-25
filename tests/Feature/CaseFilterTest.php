<?php

namespace Tests\Feature;

use App\Models\CaseContent;
use App\Models\Item;
use App\Models\ItemCollection;
use App\Models\Weapon;
use App\Models\WeaponCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaseFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_case_index_filters_by_name_category_weapon_and_key(): void
    {
        $weapon = Weapon::factory()->create(['name' => 'AK-47', 'slug' => 'ak-47']);
        $other = Weapon::factory()->create(['name' => 'AWP', 'slug' => 'awp']);
        $collection = ItemCollection::factory()->create(['name' => 'Phoenix', 'slug' => 'phoenix']);
        $match = WeaponCase::factory()->create([
            'name' => 'Phoenix Case',
            'slug' => 'phoenix-case',
            'category' => 'Case',
            'requires_key' => true,
            'collection_id' => $collection->id,
            'released_on' => '2020-01-01',
        ]);
        $skip = WeaponCase::factory()->create([
            'name' => 'Sticker Capsule',
            'slug' => 'sticker-capsule',
            'category' => 'Sticker Capsule',
            'requires_key' => false,
            'released_on' => '2024-01-01',
        ]);
        $item = Item::factory()->create(['weapon_id' => $weapon->id]);
        Item::factory()->create(['weapon_id' => $other->id]);
        CaseContent::query()->create(['case_id' => $match->id, 'item_id' => $item->id, 'probability_known' => false]);

        $this->get('/cases?q=phoenix&category=Case&collection=phoenix&weapon=ak-47&key=yes')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Cases/Index')
                ->has('cases.data', 1)
                ->where('cases.data.0.slug', 'phoenix-case')
            );

        $this->get('/cases?sort=newest')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('cases.data.0.slug', $skip->slug));

        $this->get('/cases/phoenix-case')->assertOk()->assertDontSee('Open simulator');
    }
}
