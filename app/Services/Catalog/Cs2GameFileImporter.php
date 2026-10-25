<?php

namespace App\Services\Catalog;

use App\Models\CaseContent;
use App\Models\Exterior;
use App\Models\FeaturedPlacement;
use App\Models\Item;
use App\Models\ItemCollection;
use App\Models\ItemExterior;
use App\Models\Rarity;
use App\Models\SlugRedirect;
use App\Models\Weapon;
use App\Models\WeaponCase;
use App\Support\Slugs;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Cs2GameFileImporter
{
    /** @var array<string, int> */
    private array $itemsBySource = [];

    /** @var array<string, int> */
    private array $casesBySource = [];

    /** @var array<string, int> */
    private array $collectionsBySource = [];

    /** @var array<string, true> */
    private array $itemSlugs = [];

    /** @var array<string, true> */
    private array $caseSlugs = [];

    /** @var array<string, true> */
    private array $collectionSlugs = [];

    public function archiveDemoCatalog(): void
    {
        Item::query()->where('attribute_status', 'demo')->update(['status' => 'archived']);
        WeaponCase::query()->where('attribute_status', 'demo')->update(['status' => 'archived']);
        FeaturedPlacement::query()->delete();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public function importCollections(array $rows): int
    {
        $this->rememberSlugs();
        $count = 0;

        foreach ($rows as $row) {
            if (! is_array($row) || empty($row['id']) || empty($row['name'])) {
                continue;
            }

            $this->upsertCollection($row);
            $count++;
        }

        return $count;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public function importSkins(array $rows): int
    {
        $this->rememberSlugs();
        $count = 0;

        foreach (array_chunk($rows, 100) as $chunk) {
            DB::transaction(function () use ($chunk, &$count) {
                foreach ($chunk as $row) {
                    if (! is_array($row) || empty($row['id']) || empty($row['name'])) {
                        continue;
                    }

                    $this->upsertItem($row, $this->skinType($row));
                    $count++;
                }
            });
        }

        return $count;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public function importTyped(array $rows, string $type): int
    {
        $this->rememberSlugs();
        $count = 0;

        foreach (array_chunk($rows, 100) as $chunk) {
            DB::transaction(function () use ($chunk, $type, &$count) {
                foreach ($chunk as $row) {
                    if (! is_array($row) || empty($row['id']) || empty($row['name'])) {
                        continue;
                    }

                    $this->upsertItem($row, $type);
                    $count++;
                }
            });
        }

        return $count;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public function importCrates(array $rows): int
    {
        $this->rememberSlugs();
        $count = 0;

        foreach ($rows as $row) {
            if (! is_array($row) || empty($row['id']) || empty($row['name'])) {
                continue;
            }

            DB::transaction(function () use ($row) {
                $case = $this->upsertCase($row);
                $this->syncContents($case, $row['contains'] ?? [], false);
                $this->syncContents($case, $row['contains_rare'] ?? [], true);
            });
            $count++;
        }

        return $count;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public function applyKeys(array $rows): int
    {
        WeaponCase::query()->whereNotNull('source_id')->update(['requires_key' => null]);
        $linked = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            foreach ($row['crates'] ?? [] as $crate) {
                $sourceId = is_array($crate) ? ($crate['id'] ?? null) : null;
                if ($sourceId) {
                    $linked[(string) $sourceId] = true;
                }
            }
        }

        if ($linked === []) {
            return 0;
        }

        return WeaponCase::query()
            ->whereIn('source_id', array_keys($linked))
            ->update(['requires_key' => true]);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public function linkCollectionCrates(array $rows): void
    {
        foreach ($rows as $row) {
            if (! is_array($row) || empty($row['id'])) {
                continue;
            }

            $collectionId = $this->collectionsBySource[(string) $row['id']] ?? ItemCollection::query()->where('source_id', $row['id'])->value('id');
            if (! $collectionId) {
                continue;
            }

            $crateIds = collect($row['crates'] ?? [])->pluck('id')->filter()->all();
            if ($crateIds !== []) {
                WeaponCase::query()->whereIn('source_id', $crateIds)->whereNull('collection_id')->update([
                    'collection_id' => $collectionId,
                ]);
            }
        }
    }

    public function featureRealRecords(): void
    {
        $slots = [
            'popular' => ['AK-47 | Redline', 'AWP | Asiimov', 'M4A1-S | Printstream'],
            'knife' => ['★ Karambit | Fade', '★ Butterfly Knife | Doppler'],
            'noteworthy' => ['AK-47 | Fire Serpent', 'Sport Gloves | Vice'],
        ];

        foreach ($slots as $slot => $names) {
            $position = 1;
            foreach ($names as $name) {
                $item = Item::query()->published()->where('name', $name)->first();
                if (! $item) {
                    continue;
                }

                FeaturedPlacement::query()->create([
                    'slot' => $slot,
                    'position' => $position++,
                    'subject_type' => 'item',
                    'subject_id' => $item->id,
                ]);
            }
        }

        $position = 1;
        foreach (['Kilowatt Case', 'Revolution Case'] as $name) {
            $case = WeaponCase::query()->listed()->where('name', $name)->first();
            if (! $case) {
                continue;
            }

            FeaturedPlacement::query()->create([
                'slot' => 'case',
                'position' => $position++,
                'subject_type' => 'case',
                'subject_id' => $case->id,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function upsertCollection(array $row): ItemCollection
    {
        $sourceId = (string) $row['id'];
        $collection = ItemCollection::query()->where('source_id', $sourceId)->first() ?? new ItemCollection;
        $previous = $collection->slug ?? '';
        $collection->fill([
            'source_id' => $sourceId,
            'name' => Str::limit((string) $row['name'], 180, ''),
            'description' => $this->plain($row['description'] ?? null),
            'image_path' => $this->image($row['image'] ?? null),
            'released_on' => $this->date($row['release_date'] ?? null),
            'attribute_status' => 'verified',
        ]);

        if (! $collection->exists || $collection->isDirty('name')) {
            $collection->slug = $this->claimSlug($this->collectionSlugs, ItemCollection::class, $collection->name, $collection->id);
        }

        $collection->save();
        Slugs::remember($collection, 'collection', $previous);
        $this->collectionsBySource[$sourceId] = $collection->id;

        return $collection;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function upsertItem(array $row, string $type): Item
    {
        $sourceId = (string) $row['id'];
        $item = Item::query()->where('source_id', $sourceId)->first() ?? new Item;
        $previous = $item->slug ?? '';
        $weapon = isset($row['weapon']['name']) ? $this->weapon((string) $row['weapon']['name'], (string) ($row['category']['name'] ?? 'other')) : null;
        $collectionId = $this->collectionId($row['collections'][0] ?? null);

        $item->fill([
            'source_id' => $sourceId,
            'market_hash_name' => $this->nullableString($row['market_hash_name'] ?? null, 255),
            'type' => $type,
            'weapon_id' => $weapon?->id,
            'rarity_id' => $this->rarity($row['rarity'] ?? null)?->id,
            'collection_id' => $collectionId,
            'name' => Str::limit((string) $row['name'], 180, ''),
            'description' => $this->plain($row['description'] ?? null),
            'image_path' => $this->image($row['image'] ?? null),
            'min_float' => $this->floatBound($row['min_float'] ?? null, $row['max_float'] ?? null, 'min'),
            'max_float' => $this->floatBound($row['min_float'] ?? null, $row['max_float'] ?? null, 'max'),
            'has_stattrak' => array_key_exists('stattrak', $row) ? (bool) $row['stattrak'] : null,
            'has_souvenir' => array_key_exists('souvenir', $row) ? (bool) $row['souvenir'] : null,
            'finish_style' => $this->nullableString($row['style']['name'] ?? null, 255),
            'team_side' => $this->teamSide($row['team']['id'] ?? null),
            'status' => 'published',
            'attribute_status' => 'verified',
            'verified_at' => now(),
        ]);

        if (! $item->exists || $item->isDirty('name')) {
            $item->slug = $this->claimSlug($this->itemSlugs, Item::class, $item->name, $item->id);
        }

        $item->save();
        Slugs::remember($item, 'item', $previous);
        $this->itemsBySource[$sourceId] = $item->id;
        $this->syncWears($item, $row['wears'] ?? []);

        return $item;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function upsertCase(array $row): WeaponCase
    {
        $sourceId = (string) $row['id'];
        $case = WeaponCase::query()->where('source_id', $sourceId)->first() ?? new WeaponCase;
        $previous = $case->slug ?? '';
        $case->fill([
            'source_id' => $sourceId,
            'market_hash_name' => $this->nullableString($row['market_hash_name'] ?? null, 255),
            'name' => Str::limit((string) $row['name'], 180, ''),
            'description' => $this->plain($row['description'] ?? null),
            'image_path' => $this->image($row['image'] ?? null),
            'released_on' => $this->date($row['first_sale_date'] ?? null),
            'status' => 'unknown',
            'category' => Str::limit((string) ($row['type'] ?? 'case'), 32, ''),
            'attribute_status' => 'verified',
        ]);

        if (! $case->exists || $case->isDirty('name')) {
            $case->slug = $this->claimSlug($this->caseSlugs, WeaponCase::class, $case->name, $case->id);
        }

        $case->save();
        Slugs::remember($case, 'case', $previous);
        $this->casesBySource[$sourceId] = $case->id;

        return $case;
    }

    private function syncContents(WeaponCase $case, mixed $entries, bool $special): void
    {
        if (! is_array($entries)) {
            return;
        }

        foreach ($entries as $entry) {
            if (! is_array($entry) || empty($entry['id'])) {
                continue;
            }

            $itemId = $this->itemsBySource[(string) $entry['id']] ?? null;
            if (! $itemId) {
                $itemId = Item::query()->where('source_id', $entry['id'])->value('id');
            }

            if (! $itemId) {
                $itemId = $this->upsertItem($entry, $this->rareType((string) ($entry['name'] ?? '')))->id;
            }

            CaseContent::query()->updateOrCreate(
                ['case_id' => $case->id, 'item_id' => $itemId],
                [
                    'drop_group' => $special ? 'rare' : 'standard',
                    'is_special' => $special,
                    'probability_known' => false,
                    'probability_bps' => null,
                ],
            );
        }
    }

    private function syncWears(Item $item, mixed $wears): void
    {
        if (! is_array($wears)) {
            return;
        }

        $ids = [];
        foreach ($wears as $wear) {
            $name = is_array($wear) ? ($wear['name'] ?? null) : null;
            if (! is_string($name) || $name === '') {
                continue;
            }

            $exterior = Exterior::query()->firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'abbreviation' => strtoupper(Str::substr(Str::slug($name), 0, 8)), 'sort_order' => 0],
            );
            $ids[] = $exterior->id;
        }

        ItemExterior::query()->where('item_id', $item->id)->whereNotIn('exterior_id', $ids ?: [0])->delete();
        foreach ($ids as $id) {
            ItemExterior::query()->updateOrCreate(
                ['item_id' => $item->id, 'exterior_id' => $id],
                ['is_verified' => true],
            );
        }
    }

    /**
     * @param  array<string, mixed>|null  $rarity
     */
    private function rarity(?array $rarity): ?Rarity
    {
        $name = $rarity['name'] ?? null;
        if (! is_string($name) || $name === '') {
            return null;
        }

        $color = is_string($rarity['color'] ?? null) && preg_match('/^#[0-9a-fA-F]{6}$/', $rarity['color']) ? $rarity['color'] : '#8b93a7';

        return Rarity::query()->firstOrCreate(
            ['slug' => Str::slug($name)],
            ['name' => $name, 'color' => $color, 'sort_order' => 0],
        );
    }

    private function weapon(string $name, string $category): Weapon
    {
        $weapon = Weapon::query()->where('name', $name)->first();
        if ($weapon) {
            return $weapon;
        }

        return Weapon::query()->create([
            'name' => Str::limit($name, 180, ''),
            'slug' => Slugs::unique(Weapon::class, $name),
            'category' => Str::limit(Str::slug($category) ?: 'other', 32, ''),
        ]);
    }

    private function collectionId(mixed $collection): ?int
    {
        if (! is_array($collection) || empty($collection['id'])) {
            return null;
        }

        $sourceId = (string) $collection['id'];
        if (isset($this->collectionsBySource[$sourceId])) {
            return $this->collectionsBySource[$sourceId];
        }

        if (! empty($collection['name'])) {
            return $this->upsertCollection($collection)->id;
        }

        return ItemCollection::query()->where('source_id', $sourceId)->value('id');
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function skinType(array $row): string
    {
        $category = strtolower((string) ($row['category']['name'] ?? ''));
        $weapon = strtolower((string) ($row['weapon']['id'] ?? ''));

        if (str_contains($category, 'glove') || str_contains($weapon, 'glove')) {
            return 'glove';
        }

        if (str_contains($category, 'knife') || str_contains($weapon, 'knife') || str_contains($weapon, 'bayonet')) {
            return 'knife';
        }

        return 'skin';
    }

    private function rareType(string $name): string
    {
        $lower = strtolower($name);

        if (str_contains($lower, 'glove')) {
            return 'glove';
        }

        if (str_contains($lower, 'knife') || str_contains($name, '★')) {
            return 'knife';
        }

        return 'skin';
    }

    private function teamSide(mixed $id): ?string
    {
        return match ((string) $id) {
            'terrorists', 't' => 't',
            'counter-terrorists', 'ct' => 'ct',
            default => null,
        };
    }

    private function floatBound(mixed $min, mixed $max, string $which): ?string
    {
        if (! is_numeric($min) || ! is_numeric($max)) {
            return null;
        }

        $min = (float) $min;
        $max = (float) $max;
        if ($min < 0 || $max > 1 || $min > $max) {
            return null;
        }

        return number_format($which === 'min' ? $min : $max, 6, '.', '');
    }

    private function date(mixed $value): ?string
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        return Carbon::parse($value)->toDateString();
    }

    private function image(mixed $value): ?string
    {
        if (! is_string($value) || ! str_starts_with($value, 'https://')) {
            return null;
        }

        return $value;
    }

    private function plain(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $value = preg_replace('/<br\s*\/?>/i', "\n", $value) ?? $value;
        $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = trim(preg_replace("/[ \t]+\n/", "\n", $value) ?? $value);

        return $value === '' ? null : mb_substr($value, 0, 4000);
    }

    private function nullableString(mixed $value, int $limit): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return Str::limit(trim($value), $limit, '');
    }

    private function rememberSlugs(): void
    {
        if ($this->itemSlugs !== []) {
            return;
        }

        $this->itemSlugs = array_fill_keys(Item::query()->pluck('slug')->all(), true);
        $this->caseSlugs = array_fill_keys(WeaponCase::query()->pluck('slug')->all(), true);
        $this->collectionSlugs = array_fill_keys(ItemCollection::query()->pluck('slug')->all(), true);
        foreach (SlugRedirect::query()->pluck('slug') as $slug) {
            $this->itemSlugs[$slug] = true;
            $this->caseSlugs[$slug] = true;
            $this->collectionSlugs[$slug] = true;
        }
    }

    /**
     * @param  array<string, true>  $taken
     */
    private function claimSlug(array &$taken, string $model, string $name, ?int $ignoreId): string
    {
        $base = Str::slug($name) ?: 'record';
        $slug = $base;
        $suffix = 2;

        while (isset($taken[$slug]) && ! $this->slugBelongsTo($model, $slug, $ignoreId)) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        $taken[$slug] = true;

        return $slug;
    }

    private function slugBelongsTo(string $model, string $slug, ?int $ignoreId): bool
    {
        if (! $ignoreId) {
            return false;
        }

        return $model::query()->whereKey($ignoreId)->where('slug', $slug)->exists();
    }
}
