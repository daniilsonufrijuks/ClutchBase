<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\ItemCollection;
use App\Models\Rarity;
use App\Models\User;
use App\Models\Weapon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AdminImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_and_members_cannot_open_admin(): void
    {
        $this->get('/admin')->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
    }

    public function test_admin_can_publish_an_item(): void
    {
        $admin = User::factory()->admin()->create();
        $weapon = Weapon::factory()->create();
        $rarity = Rarity::factory()->create();

        $this->actingAs($admin)->post('/admin/items', [
            'type' => 'skin',
            'name' => 'Admin Finish',
            'weapon_id' => $weapon->id,
            'rarity_id' => $rarity->id,
            'status' => 'published',
            'attribute_status' => 'unverified',
        ])->assertRedirect();

        $this->assertDatabaseHas('items', ['name' => 'Admin Finish', 'status' => 'published', 'attribute_status' => 'unverified']);
    }

    public function test_csv_import_updates_duplicates_and_reports_invalid_rows(): void
    {
        $admin = User::factory()->admin()->create();
        Weapon::factory()->create(['slug' => 'ak-47', 'name' => 'AK-47']);
        Rarity::factory()->create(['slug' => 'classified', 'name' => 'Classified']);
        ItemCollection::factory()->create(['slug' => 'harbor-collection', 'name' => 'Harbor']);

        $csv = <<<'CSV'
record_type,name,slug,type,weapon_slug,rarity_slug,collection_slug,description,min_float,max_float
item,Ferry Skin,imported-ferry-skin,skin,ak-47,classified,harbor-collection,First,,
item,Ferry Skin,imported-ferry-skin,skin,ak-47,classified,harbor-collection,Second,,
item,Bad Float,bad-float,skin,ak-47,classified,harbor-collection,Nope,1.4,0.2
item,Missing Weapon,missing-weapon,skin,no-such-weapon,classified,harbor-collection,Nope,,
CSV;

        $this->actingAs($admin)->post('/admin/imports', [
            'file' => UploadedFile::fake()->createWithContent('catalog.csv', $csv),
        ])->assertRedirect();

        $this->assertSame(1, Item::query()->where('slug', 'imported-ferry-skin')->count());
        $this->assertDatabaseMissing('items', ['slug' => 'bad-float']);
        $this->assertDatabaseHas('import_jobs', ['skipped_count' => 3, 'created_count' => 1]);
    }

    public function test_repeat_import_updates_the_existing_record(): void
    {
        $admin = User::factory()->admin()->create();
        Weapon::factory()->create(['slug' => 'ak-47']);
        Rarity::factory()->create(['slug' => 'classified']);

        $row = "record_type,name,slug,type,weapon_slug,rarity_slug,description\nitem,Ferry Skin,imported-ferry-skin,skin,ak-47,classified,Once\n";
        $again = "record_type,name,slug,type,weapon_slug,rarity_slug,description\nitem,Ferry Skin Revised,imported-ferry-skin,skin,ak-47,classified,Twice\n";

        $this->actingAs($admin)->post('/admin/imports', ['file' => UploadedFile::fake()->createWithContent('a.csv', $row)]);
        $this->actingAs($admin)->post('/admin/imports', ['file' => UploadedFile::fake()->createWithContent('b.csv', $again)])->assertRedirect();

        $this->assertDatabaseHas('items', ['slug' => 'imported-ferry-skin', 'name' => 'Ferry Skin Revised']);
        $this->assertSame(1, Item::query()->where('slug', 'imported-ferry-skin')->count());
    }
}
