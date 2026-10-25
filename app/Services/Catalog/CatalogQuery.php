<?php

namespace App\Services\Catalog;

use App\Models\Item;
use App\Models\ItemCollection;
use App\Models\Rarity;
use App\Models\WeaponCase;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class CatalogQuery
{
    public function items(Request $request): LengthAwarePaginator
    {
        $query = Item::query()
            ->published()
            ->with(['weapon', 'rarity', 'collection', 'prices.marketplace', 'prices.exterior']);

        $type = (string) $request->input('type', '');
        if (in_array($type, Item::TYPES, true)) {
            $query->where('type', $type);
        }

        $this->applyTerm($query, trim((string) $request->input('q', '')));

        if ($weapon = (string) $request->input('weapon', '')) {
            $query->whereHas('weapon', fn (Builder $builder) => $builder->where('slug', $weapon));
        }

        if ($collection = (string) $request->input('collection', '')) {
            $query->whereHas('collection', fn (Builder $builder) => $builder->where('slug', $collection));
        }

        if ($rarity = (string) $request->input('rarity', '')) {
            $query->whereHas('rarity', fn (Builder $builder) => $builder->where('slug', $rarity));
        }

        if ($exterior = (string) $request->input('exterior', '')) {
            $query->whereHas('exteriors.exterior', fn (Builder $builder) => $builder->where('slug', $exterior));
        }

        $min = $request->input('min_price');
        $max = $request->input('max_price');
        if (($min !== null && $min !== '') || ($max !== null && $max !== '')) {
            $query->whereHas('prices', function (Builder $prices) use ($min, $max) {
                $prices->where('is_verified', true)->where('is_demo', false);
                if ($min !== null && $min !== '') {
                    $prices->where('amount', '>=', $min);
                }
                if ($max !== null && $max !== '') {
                    $prices->where('amount', '<=', $max);
                }
            });
        }

        $this->applySpecial($query, 'stattrak', 'has_stattrak', (string) $request->input('stattrak', ''));
        $this->applySpecial($query, 'souvenir', 'has_souvenir', (string) $request->input('souvenir', ''));

        $sort = (string) $request->input('sort', 'name');
        if ($sort === 'rarity') {
            $query->orderBy(
                Rarity::query()->select('sort_order')->whereColumn('rarities.id', 'items.rarity_id')
            )->orderBy('items.name');
        } elseif ($sort === 'price') {
            $verified = '(select item_prices.amount from item_prices where item_prices.priceable_type = ? and item_prices.priceable_id = items.id and item_prices.is_verified = 1 and item_prices.is_demo = 0 order by item_prices.amount asc limit 1)';
            $query->orderByRaw($verified.' is null', ['item'])
                ->orderByRaw($verified.' asc', ['item'])
                ->orderBy('items.name');
        } else {
            $query->orderBy('name');
        }

        return $query->paginate(24)->withQueryString();
    }

    public function suggest(string $term): array
    {
        $like = $this->like($term);

        $items = Item::query()->published()->with('rarity')
            ->where('name', 'like', $like)
            ->orderBy('name')
            ->limit(6)
            ->get()
            ->map(fn (Item $item) => [
                'kind' => 'item',
                'name' => $item->name,
                'href' => $item->canonicalPath(),
                'meta' => $item->type,
                'color' => $item->rarity?->color,
            ]);

        $cases = WeaponCase::query()->listed()
            ->where('name', 'like', $like)
            ->orderBy('name')
            ->limit(3)
            ->get()
            ->map(fn (WeaponCase $case) => [
                'kind' => 'case',
                'name' => $case->name,
                'href' => '/cases/'.$case->slug,
                'meta' => 'case',
                'color' => '#d7a35a',
            ]);

        $collections = ItemCollection::query()
            ->where('name', 'like', $like)
            ->orderBy('name')
            ->limit(3)
            ->get()
            ->map(fn (ItemCollection $collection) => [
                'kind' => 'collection',
                'name' => $collection->name,
                'href' => '/collections/'.$collection->slug,
                'meta' => 'collection',
                'color' => '#8eb6d9',
            ]);

        return $items->concat($cases)->concat($collections)->values()->all();
    }

    private function applyTerm(Builder $query, string $term): void
    {
        if ($term === '') {
            return;
        }

        $like = $this->like($term);
        $query->where(function (Builder $inner) use ($like) {
            $inner->where('name', 'like', $like)
                ->orWhereHas('weapon', fn (Builder $weapon) => $weapon->where('name', 'like', $like))
                ->orWhereHas('collection', fn (Builder $collection) => $collection->where('name', 'like', $like));
        });
    }

    private function applySpecial(Builder $query, string $input, string $column, string $value): void
    {
        if (! in_array($value, ['yes', 'no'], true)) {
            return;
        }

        $query->where('attribute_status', 'verified')->where($column, $value === 'yes');
    }

    private function like(string $term): string
    {
        return '%'.addcslashes($term, '%_\\').'%';
    }
}
