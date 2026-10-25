<?php

namespace Tests\Feature;

use App\Models\CaseContent;
use App\Models\DataSource;
use App\Models\Exterior;
use App\Models\Item;
use App\Models\ItemPrice;
use App\Models\Marketplace;
use App\Models\PriceHistory;
use App\Models\Rarity;
use App\Models\Weapon;
use App\Models\WeaponCase;
use App\Services\Pricing\PriceRecorder;
use App\Support\Slugs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_and_catalog_pages_render(): void
    {
        $item = Item::factory()->create(['name' => 'Harbor Sample', 'type' => 'skin']);

        $this->get('/')->assertOk()->assertInertia(fn ($page) => $page->component('Home')->where('seo.canonical', url('/')));
        $this->get('/skins')->assertOk()->assertInertia(fn ($page) => $page->component('Items/Index')->has('items.data'));
        $this->get($item->canonicalPath())->assertOk()->assertInertia(fn ($page) => $page
            ->component('Items/Show')
            ->where('item.name', 'Harbor Sample')
            ->where('item.min_float', null)
            ->where('seo.canonical', url($item->canonicalPath()))
            ->where('seo.jsonLd.0.@type', 'BreadcrumbList')
        );
    }

    public function test_search_matches_partial_names_case_insensitively_and_paginates(): void
    {
        Item::factory()->create(['name' => 'Alpha Harbor Finish']);
        Item::factory()->create(['name' => 'Other Finish']);

        $this->get('/search?q=hArBo')->assertOk()->assertInertia(fn ($page) => $page
            ->component('Search/Index')
            ->where('seo.robots', 'noindex,follow')
            ->has('items.data', 1)
        );
    }

    public function test_verified_price_sort_ignores_demo_quotes(): void
    {
        $cheap = Item::factory()->create(['name' => 'Cheap Verified']);
        $demo = Item::factory()->create(['name' => 'Demo Expensive']);
        $market = Marketplace::query()->create(['name' => 'Board', 'slug' => 'board']);
        $source = DataSource::query()->create(['name' => 'Test', 'slug' => 'test', 'driver' => 'http']);
        $recorder = app(PriceRecorder::class);
        $recorder->record($cheap, null, 'standard', $market->id, '5.00', 'USD', $source->id, false, true);
        $recorder->record($demo, null, 'standard', $market->id, '1.00', 'USD', $source->id, true, false);

        $this->get('/items?sort=price')->assertOk()->assertInertia(fn ($page) => $page
            ->where('items.data.0.name', 'Cheap Verified')
        );
    }

    public function test_price_history_is_kept_and_missing_prices_stay_null(): void
    {
        $item = Item::factory()->create();
        $market = Marketplace::query()->create(['name' => 'Board', 'slug' => 'board']);
        $source = DataSource::query()->create(['name' => 'Test', 'slug' => 'test', 'driver' => 'http']);
        $recorder = app(PriceRecorder::class);
        $recorder->record($item, null, 'standard', $market->id, '4.00', 'USD', $source->id, false, true, now()->subDay());
        $recorder->record($item, null, 'standard', $market->id, '6.50', 'USD', $source->id, false, true, now());

        $this->assertSame(2, PriceHistory::query()->count());
        $this->assertSame('6.50', ItemPrice::query()->first()->amount);

        $this->get($item->canonicalPath())->assertOk()->assertInertia(fn ($page) => $page
            ->where('item.history.0.amount', '4.00')
            ->where('item.history.1.amount', '6.50')
            ->where('item.price.is_verified', true)
        );

        $empty = Item::factory()->create();
        $this->get($empty->canonicalPath())->assertInertia(fn ($page) => $page->where('item.price', null));
    }

    public function test_product_schema_is_omitted_for_demo_records(): void
    {
        $item = Item::factory()->create(['attribute_status' => 'demo']);
        $this->get($item->canonicalPath())->assertInertia(fn ($page) => $page
            ->where('seo.jsonLd', fn ($blocks) => collect($blocks)->every(fn ($block) => $block['@type'] !== 'Product'))
        );
    }

    public function test_case_contents_and_slug_redirects(): void
    {
        $item = Item::factory()->create();
        $case = WeaponCase::factory()->create(['slug' => 'old-case']);
        CaseContent::query()->create([
            'case_id' => $case->id,
            'item_id' => $item->id,
            'probability_known' => false,
        ]);
        $case->update(['slug' => 'new-case']);
        Slugs::remember($case, 'case', 'old-case');

        $this->get('/cases/new-case')->assertOk()->assertInertia(fn ($page) => $page
            ->component('Cases/Show')
            ->has('caseItem.groups')
        );
        $this->get('/cases/old-case')->assertRedirect('/cases/new-case');
    }

    public function test_exterior_filter_requires_a_stored_variant(): void
    {
        $exterior = Exterior::query()->create(['name' => 'Factory New', 'slug' => 'factory-new', 'abbreviation' => 'FN', 'sort_order' => 1]);
        $item = Item::factory()->create(['name' => 'Listed Exterior']);
        Item::factory()->create(['name' => 'No Exterior']);
        $item->exteriors()->create(['exterior_id' => $exterior->id, 'is_verified' => false]);

        $this->get('/skins?exterior=factory-new')->assertInertia(fn ($page) => $page->has('items.data', 1)->where('items.data.0.name', 'Listed Exterior'));
    }

    public function test_rarity_filter(): void
    {
        $rare = Rarity::factory()->create(['name' => 'Covert', 'slug' => 'covert', 'sort_order' => 6]);
        Item::factory()->create(['name' => 'Red One', 'rarity_id' => $rare->id]);
        Item::factory()->create(['name' => 'Other']);

        $this->get('/skins?rarity=covert')->assertInertia(fn ($page) => $page->has('items.data', 1));
    }

    public function test_weapon_page_lists_its_finishes(): void
    {
        $weapon = Weapon::factory()->create(['name' => 'AK-47', 'slug' => 'ak-47']);
        Item::factory()->create(['weapon_id' => $weapon->id, 'name' => 'Linked Finish']);

        $this->get('/weapons/ak-47')->assertOk()->assertInertia(fn ($page) => $page->component('Weapons/Show')->has('items', 1));
    }
}
