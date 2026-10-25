<?php

namespace App\Services\Catalog;

use App\Models\CaseContent;
use App\Models\ImportJob;
use App\Models\Item;
use App\Models\ItemCollection;
use App\Models\Rarity;
use App\Models\User;
use App\Models\Weapon;
use App\Models\WeaponCase;
use App\Support\CatalogCache;
use App\Support\PlaceholderArt;
use App\Support\Slugs;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class CatalogImporter
{
    public function import(UploadedFile $file, ?User $user): ImportJob
    {
        $job = ImportJob::query()->create([
            'user_id' => $user?->id,
            'original_name' => $file->getClientOriginalName(),
            'status' => 'pending',
            'errors' => [],
        ]);

        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            $job->update(['status' => 'failed', 'errors' => [['row' => 0, 'message' => 'The file could not be read.']]]);

            return $job;
        }

        $header = fgetcsv($handle);
        if (! is_array($header)) {
            fclose($handle);
            $job->update(['status' => 'failed', 'errors' => [['row' => 1, 'message' => 'The CSV header is missing.']]]);

            return $job;
        }

        $header = array_map(fn ($column) => strtolower(trim((string) $column)), $header);
        $errors = [];
        $seen = [];
        $rowNumber = 1;
        $created = 0;
        $updated = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;
            if ($this->blank($row)) {
                continue;
            }

            $record = $this->combine($header, $row);
            $job->increment('processed');

            try {
                $outcome = DB::transaction(fn () => $this->importRow($record, $seen));
                $seen[] = $outcome['key'];
                if ($outcome['status'] === 'created') {
                    $created++;
                } else {
                    $updated++;
                }
            } catch (\Throwable $exception) {
                $skipped++;
                $errors[] = ['row' => $rowNumber, 'message' => $exception->getMessage()];
            }
        }

        fclose($handle);
        CatalogCache::bump();

        $job->update([
            'status' => 'completed',
            'created_count' => $created,
            'updated_count' => $updated,
            'skipped_count' => $skipped,
            'errors' => $errors,
        ]);

        return $job->refresh();
    }

    private function importRow(array $record, array $seen): array
    {
        $type = $record['record_type'] ?? 'item';

        return match ($type) {
            'case' => $this->importCase($record, $seen),
            'case_content' => $this->importContent($record),
            'item' => $this->importItem($record, $seen),
            default => throw new \InvalidArgumentException('record_type must be item, case, or case_content.'),
        };
    }

    private function importItem(array $record, array $seen): array
    {
        $name = trim((string) ($record['name'] ?? ''));
        if ($name === '') {
            throw new \InvalidArgumentException('Item name is required.');
        }

        $itemType = trim((string) ($record['type'] ?? 'skin'));
        if (! in_array($itemType, Item::TYPES, true)) {
            throw new \InvalidArgumentException('Unknown item type.');
        }

        $weapon = null;
        if (in_array($itemType, ['skin', 'knife', 'glove'], true)) {
            $weaponSlug = trim((string) ($record['weapon_slug'] ?? ''));
            $weapon = Weapon::query()->where('slug', $weaponSlug)->first();
            if (! $weapon) {
                throw new \InvalidArgumentException('Unknown weapon_slug.');
            }
        }

        $rarity = null;
        if (($record['rarity_slug'] ?? '') !== '') {
            $rarity = Rarity::query()->where('slug', $record['rarity_slug'])->first();
            if (! $rarity) {
                throw new \InvalidArgumentException('Unknown rarity_slug.');
            }
        }

        $collection = null;
        if (($record['collection_slug'] ?? '') !== '') {
            $collection = ItemCollection::query()->where('slug', $record['collection_slug'])->first();
            if (! $collection) {
                throw new \InvalidArgumentException('Unknown collection_slug.');
            }
        }

        [$min, $max] = $this->floats($record);
        $slug = trim((string) ($record['slug'] ?? '')) ?: Slugs::unique(Item::class, trim(($weapon?->name ?? '').' '.$name));

        if (in_array('item:'.$slug, $seen, true)) {
            throw new \InvalidArgumentException('Duplicate slug in this file.');
        }

        $existing = Item::query()->where('slug', $slug)->first();
        $payload = [
            'type' => $itemType,
            'weapon_id' => $weapon?->id,
            'rarity_id' => $rarity?->id,
            'collection_id' => $collection?->id,
            'name' => $name,
            'description' => $this->nullable($record['description'] ?? null),
            'min_float' => $min,
            'max_float' => $max,
            'has_stattrak' => $this->nullableBool($record['has_stattrak'] ?? null),
            'has_souvenir' => $this->nullableBool($record['has_souvenir'] ?? null),
            'released_on' => $this->nullable($record['released_on'] ?? null),
            'attribute_status' => 'unverified',
            'status' => 'published',
        ];

        if ($existing) {
            $existing->update($payload);

            return ['status' => 'updated', 'key' => 'item:'.$slug];
        }

        $item = Item::query()->create($payload + ['slug' => $slug]);
        PlaceholderArt::writeItem(
            public_path('media/items/'.$item->slug.'.svg'),
            $item->name,
            'Unverified import',
            $rarity?->color ?? '#d7a35a',
        );

        return ['status' => 'created', 'key' => 'item:'.$slug];
    }

    private function importCase(array $record, array $seen): array
    {
        $name = trim((string) ($record['name'] ?? ''));
        if ($name === '') {
            throw new \InvalidArgumentException('Case name is required.');
        }

        $slug = trim((string) ($record['slug'] ?? '')) ?: Slugs::unique(WeaponCase::class, $name);
        if (in_array('case:'.$slug, $seen, true)) {
            throw new \InvalidArgumentException('Duplicate case slug in this file.');
        }

        $payload = [
            'name' => $name,
            'description' => $this->nullable($record['description'] ?? null),
            'category' => $this->nullable($record['category'] ?? null) ?? 'case',
            'status' => 'unknown',
            'attribute_status' => 'unverified',
        ];

        $existing = WeaponCase::query()->where('slug', $slug)->first();
        if ($existing) {
            $existing->update($payload);

            return ['status' => 'updated', 'key' => 'case:'.$slug];
        }

        $case = WeaponCase::query()->create($payload + ['slug' => $slug]);
        PlaceholderArt::writeItem(public_path('media/cases/'.$case->slug.'.svg'), $case->name, 'Case placeholder');

        return ['status' => 'created', 'key' => 'case:'.$slug];
    }

    private function importContent(array $record): array
    {
        $case = WeaponCase::query()->where('slug', trim((string) ($record['case_slug'] ?? '')))->first();
        $item = Item::query()->where('slug', trim((string) ($record['item_slug'] ?? '')))->first();

        if (! $case || ! $item) {
            throw new \InvalidArgumentException('case_content requires existing case_slug and item_slug.');
        }

        $content = CaseContent::query()->updateOrCreate(
            ['case_id' => $case->id, 'item_id' => $item->id],
            [
                'drop_group' => $this->nullable($record['drop_group'] ?? null),
                'is_special' => filter_var($record['is_special'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'probability_known' => false,
                'probability_bps' => null,
            ],
        );

        return ['status' => $content->wasRecentlyCreated ? 'created' : 'updated', 'key' => 'content:'.$case->id.':'.$item->id];
    }

    private function floats(array $record): array
    {
        $min = $this->nullable($record['min_float'] ?? null);
        $max = $this->nullable($record['max_float'] ?? null);

        foreach (['min_float' => $min, 'max_float' => $max] as $label => $value) {
            if ($value === null) {
                continue;
            }
            if (! is_numeric($value) || (float) $value < 0 || (float) $value > 1) {
                throw new \InvalidArgumentException($label.' must be empty or between 0 and 1.');
            }
        }

        if ($min !== null && $max !== null && (float) $min > (float) $max) {
            throw new \InvalidArgumentException('min_float cannot exceed max_float.');
        }

        return [$min, $max];
    }

    private function combine(array $header, array $row): array
    {
        $values = array_slice(array_pad($row, count($header), null), 0, count($header));

        return array_combine($header, array_map(fn ($value) => is_string($value) ? trim($value) : $value, $values));
    }

    private function blank(array $row): bool
    {
        return collect($row)->every(fn ($value) => trim((string) $value) === '');
    }

    private function nullable(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function nullableBool(mixed $value): ?bool
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }
}
