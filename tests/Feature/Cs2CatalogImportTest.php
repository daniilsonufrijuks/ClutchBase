<?php

namespace Tests\Feature;

use App\Models\CaseContent;
use App\Models\Item;
use App\Models\ItemPrice;
use App\Models\WeaponCase;
use App\Services\Catalog\Cs2GameFileImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Cs2CatalogImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_game_file_import_publishes_real_records_without_prices_or_drop_rates(): void
    {
        Item::factory()->create(['name' => 'Harbor Line', 'attribute_status' => 'demo', 'status' => 'published']);
        WeaponCase::factory()->create(['name' => 'Sample Harbor Case', 'attribute_status' => 'demo', 'status' => 'unknown']);

        $importer = new Cs2GameFileImporter;
        $importer->archiveDemoCatalog();
        $importer->importCollections([[
            'id' => 'collection-set-test',
            'name' => 'The Test Collection',
            'release_date' => '2024-01-16',
            'image' => 'https://cdn.steamstatic.com/apps/730/icons/econ/set_icons/set_test.png',
            'crates' => [['id' => 'crate-1', 'name' => 'Test Case']],
        ]]);
        $importer->importSkins([[
            'id' => 'skin-1',
            'name' => 'AK-47 | Redline',
            'description' => 'Painted.<br><i>Sample</i>',
            'weapon' => ['id' => 'weapon_ak47', 'name' => 'AK-47'],
            'category' => ['name' => 'Rifles'],
            'min_float' => 0.1,
            'max_float' => 0.7,
            'stattrak' => true,
            'souvenir' => false,
            'rarity' => ['name' => 'Classified', 'color' => '#d32ce6'],
            'wears' => [['name' => 'Field-Tested']],
            'collections' => [['id' => 'collection-set-test', 'name' => 'The Test Collection']],
            'image' => 'https://cdn.steamstatic.com/apps/730/icons/econ/default_generated/weapon_ak47_redline.png',
        ], [
            'id' => 'skin-bad-float',
            'name' => 'AWP | Guess',
            'weapon' => ['id' => 'weapon_awp', 'name' => 'AWP'],
            'category' => ['name' => 'Sniper Rifles'],
            'min_float' => 0.9,
            'max_float' => 0.1,
            'image' => 'not-a-url',
        ], [
            'id' => 'skin-gloves',
            'name' => 'Sport Gloves | Vice',
            'weapon' => ['id' => 'weapon_sport_gloves', 'name' => 'Sport Gloves'],
            'category' => ['name' => 'Gloves'],
            'min_float' => 0.06,
            'max_float' => 0.8,
            'stattrak' => false,
        ]]);
        $importer->importCrates([[
            'id' => 'crate-1',
            'name' => 'Test Case',
            'type' => 'Case',
            'first_sale_date' => '2024-01-16',
            'market_hash_name' => 'Test Case',
            'image' => 'https://community.akamai.steamstatic.com/economy/image/example',
            'contains' => [['id' => 'skin-1', 'name' => 'AK-47 | Redline']],
            'contains_rare' => [['id' => 'skin-vanilla-knife', 'name' => '★ Kukri Knife', 'rarity' => ['name' => 'Extraordinary', 'color' => '#e4ae39']]],
        ]]);
        $importer->applyKeys([['id' => 'key-1', 'crates' => [['id' => 'crate-1']]]]);
        $importer->linkCollectionCrates([['id' => 'collection-set-test', 'crates' => [['id' => 'crate-1']]]]);

        $this->assertSame('archived', Item::query()->where('name', 'Harbor Line')->value('status'));
        $skin = Item::query()->where('source_id', 'skin-1')->first();
        $this->assertNotNull($skin);
        $this->assertSame('skin', $skin->type);
        $this->assertSame('verified', $skin->attribute_status);
        $this->assertSame('0.100000', $skin->min_float);
        $this->assertEquals(true, $skin->has_stattrak);
        $this->assertEquals(false, $skin->has_souvenir);
        $this->assertStringStartsWith('https://', $skin->imageUrl());
        $this->assertSame('Field-Tested', $skin->exteriors()->first()->exterior->name);
        $this->assertSame('glove', Item::query()->where('source_id', 'skin-gloves')->value('type'));
        $this->assertNull(Item::query()->where('source_id', 'skin-bad-float')->value('min_float'));

        $case = WeaponCase::query()->where('source_id', 'crate-1')->first();
        $this->assertEquals(true, $case->requires_key);
        $this->assertSame('2024-01-16', $case->released_on->toDateString());
        $this->assertNotNull($case->collection_id);
        $rare = CaseContent::query()->where('case_id', $case->id)->where('is_special', true)->first();
        $this->assertFalse($rare->probability_known);
        $this->assertNull($rare->probability_bps);
        $this->assertSame('knife', $rare->item->type);
        $this->assertSame(0, ItemPrice::query()->count());
    }
}
