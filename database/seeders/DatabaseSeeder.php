<?php

namespace Database\Seeders;

use App\Models\CaseContent;
use App\Models\DataSource;
use App\Models\Exterior;
use App\Models\FeaturedPlacement;
use App\Models\Guide;
use App\Models\Item;
use App\Models\ItemCollection;
use App\Models\MapAsset;
use App\Models\Marketplace;
use App\Models\Rarity;
use App\Models\SearchQuery;
use App\Models\Strategy;
use App\Models\User;
use App\Models\Weapon;
use App\Models\WeaponCase;
use App\Services\Accounts\AccountProvisioner;
use App\Services\Pricing\PriceRecorder;
use App\Support\PlaceholderArt;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $rarities = collect([
            ['Consumer Grade', '#b0c3d9', 1],
            ['Industrial Grade', '#5e98d9', 2],
            ['Mil-Spec', '#4b69ff', 3],
            ['Restricted', '#8847ff', 4],
            ['Classified', '#d32ce6', 5],
            ['Covert', '#eb4b4b', 6],
            ['Extraordinary', '#e4ae39', 7],
        ])->mapWithKeys(function ($row) {
            $model = Rarity::query()->create([
                'name' => $row[0],
                'slug' => Str::slug($row[0]),
                'color' => $row[1],
                'sort_order' => $row[2],
            ]);

            return [$row[0] => $model];
        });

        foreach ([
            ['Factory New', 'fn', 1],
            ['Minimal Wear', 'mw', 2],
            ['Field-Tested', 'ft', 3],
            ['Well-Worn', 'ww', 4],
            ['Battle-Scarred', 'bs', 5],
        ] as $index => $row) {
            Exterior::query()->create([
                'name' => $row[0],
                'slug' => Str::slug($row[0]),
                'abbreviation' => strtoupper($row[1]),
                'sort_order' => $row[2],
            ]);
        }

        $weapons = collect([
            ['AK-47', 'rifle'],
            ['M4A4', 'rifle'],
            ['M4A1-S', 'rifle'],
            ['AWP', 'sniper'],
            ['USP-S', 'pistol'],
            ['Glock-18', 'pistol'],
            ['Desert Eagle', 'pistol'],
            ['Karambit', 'knife'],
            ['Sport Gloves', 'glove'],
        ])->mapWithKeys(function ($row) {
            $weapon = Weapon::query()->create([
                'name' => $row[0],
                'slug' => Str::slug($row[0]),
                'category' => $row[1],
            ]);

            return [$row[0] => $weapon];
        });

        $harbor = ItemCollection::query()->create([
            'name' => 'Harbor Collection',
            'slug' => 'harbor-collection',
            'description' => 'Demo collection used to show relationships. Release details are not verified.',
            'attribute_status' => 'demo',
        ]);

        $catalog = [
            ['AK-47 | Harbor Line', 'skin', 'AK-47', 'Classified'],
            ['M4A4 | Circuit Mint', 'skin', 'M4A4', 'Restricted'],
            ['AWP | Glass Comet', 'skin', 'AWP', 'Covert'],
            ['USP-S | Ferry Light', 'skin', 'USP-S', 'Restricted'],
            ['Glock-18 | Yard Line', 'skin', 'Glock-18', 'Mil-Spec'],
            ['Desert Eagle | Brass Works', 'skin', 'Desert Eagle', 'Classified'],
            ['M4A1-S | Quiet Current', 'skin', 'M4A1-S', 'Covert'],
            ['Karambit | Night Ferry', 'knife', 'Karambit', 'Extraordinary'],
            ['Sport Gloves | Dry Heat', 'glove', 'Sport Gloves', 'Extraordinary'],
            ['Sticker | Sample Mark', 'sticker', null, 'Remarkable'],
            ['Agent | Sample Captain', 'agent', null, 'Superior'],
        ];

        if (! isset($rarities['Remarkable'])) {
            $rarities['Remarkable'] = Rarity::query()->create(['name' => 'Remarkable', 'slug' => 'remarkable', 'color' => '#8847ff', 'sort_order' => 8]);
            $rarities['Superior'] = Rarity::query()->create(['name' => 'Superior', 'slug' => 'superior', 'color' => '#d32ce6', 'sort_order' => 9]);
        }

        $items = [];
        foreach ($catalog as $row) {
            $slug = Str::slug($row[0]);
            $item = Item::query()->create([
                'type' => $row[1],
                'weapon_id' => $row[2] ? $weapons[$row[2]]->id : null,
                'rarity_id' => $rarities[$row[3]]->id,
                'collection_id' => in_array($row[1], ['skin', 'knife', 'glove'], true) ? $harbor->id : null,
                'name' => $row[0],
                'slug' => $slug,
                'description' => 'Sample record for local development. Weapon names refer to CS2 equipment, but this finish, rarity link, and any price are demo data.',
                'status' => 'published',
                'attribute_status' => 'demo',
                'team_side' => $row[1] === 'agent' ? 'ct' : null,
            ]);
            PlaceholderArt::writeItem(public_path('media/items/'.$slug.'.svg'), $item->name, 'Demo record', $rarities[$row[3]]->color);
            $items[$row[0]] = $item;
        }

        $case = WeaponCase::query()->create([
            'name' => 'Sample Harbor Case',
            'slug' => 'sample-harbor-case',
            'description' => 'Demo case. Contents are sample links. Drop rates are unknown.',
            'status' => 'unknown',
            'category' => 'case',
            'collection_id' => $harbor->id,
            'attribute_status' => 'demo',
            'requires_key' => null,
        ]);
        PlaceholderArt::writeItem(public_path('media/cases/'.$case->slug.'.svg'), $case->name, 'Demo case');

        foreach (['AK-47 | Harbor Line', 'M4A4 | Circuit Mint', 'AWP | Glass Comet', 'USP-S | Ferry Light', 'Glock-18 | Yard Line'] as $name) {
            CaseContent::query()->create([
                'case_id' => $case->id,
                'item_id' => $items[$name]->id,
                'drop_group' => 'standard',
                'is_special' => false,
                'probability_known' => false,
            ]);
        }
        CaseContent::query()->create([
            'case_id' => $case->id,
            'item_id' => $items['Karambit | Night Ferry']->id,
            'drop_group' => 'rare',
            'is_special' => true,
            'probability_known' => false,
        ]);

        $capsule = WeaponCase::query()->create([
            'name' => 'Sample Mark Capsule',
            'slug' => 'sample-mark-capsule',
            'description' => 'Demo capsule with one sticker.',
            'status' => 'unknown',
            'category' => 'capsule',
            'attribute_status' => 'demo',
        ]);
        PlaceholderArt::writeItem(public_path('media/cases/'.$capsule->slug.'.svg'), $capsule->name, 'Demo capsule');
        CaseContent::query()->create([
            'case_id' => $capsule->id,
            'item_id' => $items['Sticker | Sample Mark']->id,
            'probability_known' => false,
        ]);

        $board = Marketplace::query()->create(['name' => 'Demo Board', 'slug' => 'demo-board', 'is_active' => true]);
        $stall = Marketplace::query()->create(['name' => 'Demo Stall', 'slug' => 'demo-stall', 'is_active' => true]);
        $source = DataSource::query()->create([
            'name' => 'ClutchBase demo dataset',
            'slug' => 'demo',
            'driver' => 'demo',
            'is_enabled' => false,
            'notes' => 'Seeded sample observations. Not a live market feed.',
            'last_status' => 'demo',
        ]);

        $recorder = app(PriceRecorder::class);
        $anchor = $items['AK-47 | Harbor Line'];
        foreach ([
            ['9.50', now()->subDays(21)],
            ['10.25', now()->subDays(14)],
            ['11.00', now()->subDays(7)],
            ['12.40', now()->subDay()],
        ] as [$amount, $when]) {
            $recorder->record($anchor, null, 'standard', $board->id, $amount, 'USD', $source->id, true, false, $when);
        }
        $recorder->record($anchor, null, 'standard', $stall->id, '13.10', 'USD', $source->id, true, false, now()->subHours(6));
        $recorder->record($items['AWP | Glass Comet'], null, 'standard', $board->id, '48.00', 'USD', $source->id, true, false, now()->subDays(2));
        $recorder->record($case, null, 'standard', $board->id, '1.25', 'USD', $source->id, true, false, now()->subHours(3));

        foreach ([
            ['popular', $anchor],
            ['popular', $items['AWP | Glass Comet']],
            ['knife', $items['Karambit | Night Ferry']],
            ['noteworthy', $items['Sport Gloves | Dry Heat']],
            ['noteworthy', $items['M4A1-S | Quiet Current']],
        ] as $position => [$slot, $item]) {
            FeaturedPlacement::query()->create([
                'slot' => $slot,
                'position' => $position,
                'subject_type' => 'item',
                'subject_id' => $item->id,
            ]);
        }
        FeaturedPlacement::query()->create([
            'slot' => 'case',
            'position' => 0,
            'subject_type' => 'case',
            'subject_id' => $case->id,
        ]);

        Guide::query()->create([
            'title' => 'How this catalog labels data',
            'slug' => 'how-this-catalog-labels-data',
            'excerpt' => 'Demo, unverified, and verified mean different things on ClutchBase.',
            'body' => "ClutchBase never fills a missing price with a guess.\n\nDemo records are sample rows for local development. They use original placeholder art and fictional finishes.\n\nUnverified means a person or import supplied a value that has not been checked.\n\nVerified means an administrator marked the stored attributes as checked. A verified price also records its source and the time it was observed.\n\nFloat ranges, pattern notes, and StatTrak availability stay empty until a value is actually stored.",
            'is_published' => true,
            'meta_description' => 'How ClutchBase separates demo, unverified, and verified catalog data.',
        ]);
        Guide::query()->create([
            'title' => 'What the Steam page shows',
            'slug' => 'what-the-steam-page-shows',
            'excerpt' => 'Player counts, news, and achievements come from the Steamworks Web API.',
            'body' => "The Steam page calls documented read-only methods for Counter-Strike 2, app 730.\n\nGetNumberOfCurrentPlayers, GetNewsForApp, and GetGlobalAchievementPercentagesForApp do not need a publisher key.\n\nProfile lookup uses ResolveVanityURL, GetPlayerSummaries, GetRecentlyPlayedGames, and GetPlayerAchievements. Those need STEAM_WEB_API_KEY on the server.\n\nNone of these calls read or place Steam Market orders.",
            'is_published' => true,
        ]);
        Guide::query()->create([
            'title' => 'Planning a round',
            'slug' => 'planning-a-round',
            'excerpt' => 'The strategy planner stores team notes. It does not ship verified utility lineups.',
            'body' => "Pick a map, a side, and an objective. Assign five players, then place them on the schematic.\n\nMap drawings in ClutchBase are original diagrams, not official radar images. Coordinates are stored as fractions of the diagram, from 0 to 1.\n\nTemplate timings are examples you can edit. They are not guaranteed executes.\n\nShare a private link when the plan is ready. Teammates can view it, and an edit link can add a collaborator after they sign in.",
            'is_published' => true,
        ]);

        $admin = User::query()->create([
            'name' => 'Avery Quinn',
            'email' => 'admin@clutchbase.test',
            'password' => Hash::make('password'),
        ]);
        $admin->forceFill(['role' => 'admin'])->save();
        $member = User::query()->create([
            'name' => 'Riley Chen',
            'email' => 'user@clutchbase.test',
            'password' => Hash::make('password'),
        ]);
        $provisioner = app(AccountProvisioner::class);
        $provisioner->provision($admin);
        $provisioner->provision($member);

        $this->mapsAndTemplates($admin);

        SearchQuery::query()->create(['term' => 'harbor', 'results_count' => 3, 'created_at' => now()->subDay()]);
        SearchQuery::query()->create(['term' => 'harbor', 'results_count' => 3, 'created_at' => now()->subHours(3)]);
        SearchQuery::query()->create(['term' => 'comet', 'results_count' => 1, 'created_at' => now()->subHours(2)]);
    }

    private function mapsAndTemplates(User $admin): void
    {
        $maps = [
            'dust-ii' => ['Dust II schematic', [
                ['T spawn', 80, 760, 280, 160],
                ['CT spawn', 640, 80, 260, 150],
                ['A site', 620, 250, 280, 180],
                ['Mid', 360, 420, 280, 180],
                ['B site', 80, 220, 240, 180],
            ]],
            'mirage' => ['Mirage schematic', [
                ['T spawn', 80, 780, 300, 140],
                ['CT spawn', 620, 80, 280, 140],
                ['A site', 650, 240, 250, 170],
                ['Mid', 370, 400, 260, 180],
                ['B site', 80, 240, 230, 170],
            ]],
            'inferno' => ['Inferno schematic', [
                ['T spawn', 700, 760, 220, 150],
                ['CT spawn', 80, 80, 240, 150],
                ['Banana', 80, 420, 220, 280],
                ['Mid', 360, 360, 240, 200],
                ['A site', 620, 160, 280, 180],
            ]],
            'nuke' => ['Nuke schematic', [
                ['T spawn', 80, 760, 260, 150],
                ['Outside', 360, 620, 280, 160],
                ['A site', 640, 200, 260, 180],
                ['Ramp', 360, 360, 200, 180],
                ['B site', 80, 200, 240, 180],
            ]],
        ];

        $created = [];
        foreach ($maps as $slug => [$name, $zones]) {
            $path = public_path('media/maps/'.$slug.'.svg');
            if (! is_dir(dirname($path))) {
                mkdir(dirname($path), 0775, true);
            }
            file_put_contents($path, $this->mapSvg($name, $zones));
            $created[$slug] = MapAsset::query()->create([
                'name' => $name,
                'slug' => $slug,
                'image_path' => '/media/maps/'.$slug.'.svg',
                'license_note' => 'Original schematic diagram created for ClutchBase. It is not an official Valve map render.',
                'is_active' => true,
            ]);
        }

        $this->template($admin, $created['mirage'], 'Example Mirage A execute', 't', 'a_execute', 'A site');
        $this->template($admin, $created['inferno'], 'Example Inferno B execute', 't', 'b_execute', 'Banana');
        $this->template($admin, $created['dust-ii'], 'Example Dust II mid control', 't', 'mid_control', 'Mid');
        $this->template($admin, $created['nuke'], 'Example Nuke site defense', 'ct', 'site_defense', 'A site');
    }

    private function template(User $admin, MapAsset $map, string $title, string $side, string $objective, string $zone): void
    {
        $roles = array_keys(config('strategy.roles.'.$side));
        $strategy = Strategy::query()->create([
            'map_asset_id' => $map->id,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
            'title' => $title,
            'slug' => Str::slug($title),
            'side' => $side,
            'objective' => $objective,
            'template_key' => $objective,
            'is_template' => true,
            'status' => 'active',
            'visibility' => 'private',
            'revision' => 1,
            'summary' => 'Example template. Timings and utility notes are illustrations, not verified lineups.',
        ]);

        foreach (range(1, 5) as $slot) {
            $strategy->players()->create([
                'slot' => $slot,
                'nickname' => 'Player '.$slot,
                'role' => $roles[$slot - 1],
                'start_x' => 0.15 + ($slot * 0.08),
                'start_y' => $side === 't' ? 0.82 : 0.18,
                'responsibilities' => 'Edit this responsibility for the '.$zone.' example.',
            ]);
        }

        $strategy->markers()->create([
            'kind' => 'smoke',
            'player_slot' => 3,
            'x' => 0.62,
            'y' => 0.34,
            'label' => 'Example smoke',
            'step_number' => 1,
            'sort_order' => 0,
        ]);
        $strategy->utilityActions()->create([
            'player_slot' => 3,
            'utility_type' => 'smoke',
            'land_x' => 0.62,
            'land_y' => 0.34,
            'timing_seconds' => 15,
            'step_number' => 1,
            'notes' => 'Example utility. Replace this with a lineup your team has actually practiced.',
        ]);
        $strategy->timelineSteps()->create([
            'seconds' => 0,
            'title' => 'Round begins',
            'instructions' => 'Confirm roles and starting positions.',
            'sort_order' => 0,
        ]);
        $strategy->timelineSteps()->create([
            'seconds' => 15,
            'title' => 'Example utility',
            'instructions' => 'This timing is a placeholder, not a universal execute.',
            'sort_order' => 1,
        ]);
        $strategy->paths()->create([
            'player_slot' => 1,
            'label' => 'Example path',
            'points' => [
                ['x' => 0.23, 'y' => $side === 't' ? 0.82 : 0.18],
                ['x' => 0.48, 'y' => 0.5],
                ['x' => 0.7, 'y' => 0.32],
            ],
        ]);
    }

    private function mapSvg(string $name, array $zones): string
    {
        $rects = '';
        foreach ($zones as [$label, $x, $y, $w, $h]) {
            $safe = htmlspecialchars($label, ENT_QUOTES);
            $rects .= '<rect x="'.$x.'" y="'.$y.'" width="'.$w.'" height="'.$h.'" rx="16" fill="#2a3142" stroke="#d7a35a"/>';
            $rects .= '<text x="'.($x + $w / 2).'" y="'.($y + $h / 2).'" text-anchor="middle" fill="#edf0f5" font-family="Segoe UI, sans-serif" font-size="24">'.$safe.'</text>';
        }

        $safeName = htmlspecialchars($name, ENT_QUOTES);

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><rect width="1000" height="1000" fill="#171b24"/><text x="40" y="56" fill="#b7c0ce" font-family="Segoe UI, sans-serif" font-size="28">'.$safeName.'</text>'.$rects.'</svg>';
    }
}
