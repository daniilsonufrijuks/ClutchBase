<?php

namespace App\Services\Catalog;

use App\Models\Item;
use App\Models\ItemPrice;
use App\Models\PriceHistory;
use App\Models\User;
use App\Models\WeaponCase;
use App\Support\Money;
use Illuminate\Support\Collection;

class CatalogPresenter
{
    public function card(Item $item): array
    {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'slug' => $item->slug,
            'href' => $item->canonicalPath(),
            'type' => $item->type,
            'image' => $item->imageUrl(),
            'image_alt' => 'Placeholder artwork for '.$item->name,
            'weapon' => $item->weapon?->name,
            'weapon_slug' => $item->weapon?->slug,
            'rarity' => $this->rarity($item),
            'collection' => $item->collection?->name,
            'collection_slug' => $item->collection?->slug,
            'attribute_status' => $item->attribute_status,
            'price' => $this->pricePayload($this->choosePrice($item->prices)),
            'stattrak' => $this->verifiedFlag($item, 'has_stattrak'),
            'souvenir' => $this->verifiedFlag($item, 'has_souvenir'),
        ];
    }

    public function detail(Item $item, ?string $exteriorSlug, string $variant, ?User $user): array
    {
        $exteriors = $item->exteriors->map(fn ($row) => [
            'slug' => $row->exterior?->slug,
            'name' => $row->exterior?->name,
            'abbreviation' => $row->exterior?->abbreviation,
            'is_verified' => (bool) $row->is_verified,
        ])->values();

        $selected = $exteriors->firstWhere('slug', $exteriorSlug) ? $exteriorSlug : null;
        $quotes = $item->prices->filter(function (ItemPrice $price) use ($selected, $variant) {
            $exteriorMatches = $selected === null
                ? true
                : $price->exterior?->slug === $selected;

            return $exteriorMatches && $price->variant === $variant;
        })->values();

        $since = $user?->hasPremium()
            ? null
            : now()->subDays((int) config('clutchbase.history_days_free'));

        $history = $item->priceHistories
            ->filter(function (PriceHistory $price) use ($selected, $variant, $since) {
                if ($price->variant !== $variant) {
                    return false;
                }
                if ($selected !== null && $price->exterior?->slug !== $selected) {
                    return false;
                }
                if ($since && $price->observed_at->lt($since)) {
                    return false;
                }

                return true;
            })
            ->sortBy('observed_at')
            ->values()
            ->map(fn (PriceHistory $price) => $this->pricePayload($price));

        $cases = $item->caseContents->map(fn ($content) => [
            'name' => $content->case?->name,
            'slug' => $content->case?->slug,
            'href' => $content->case ? '/cases/'.$content->case->slug : null,
            'attribute_status' => $content->case?->attribute_status,
            'is_special' => (bool) $content->is_special,
            'probability_known' => (bool) $content->probability_known,
        ])->values();

        return $this->card($item) + [
            'description' => $item->description,
            'min_float' => $item->min_float,
            'max_float' => $item->max_float,
            'pattern_note' => $item->pattern_note,
            'released_on' => $item->released_on?->toDateString(),
            'update_name' => $item->update_name,
            'team_side' => $item->team_side,
            'verified_at' => $item->verified_at?->toIso8601String(),
            'exteriors' => $exteriors,
            'selected_exterior' => $selected,
            'selected_variant' => $variant,
            'quotes' => $quotes->map(fn (ItemPrice $price) => $this->pricePayload($price))->values(),
            'history' => $history,
            'history_limited' => $since !== null,
            'cases' => $cases,
            'collection_href' => $item->collection ? '/collections/'.$item->collection->slug : null,
            'weapon_href' => $item->weapon ? '/weapons/'.$item->weapon->slug : null,
        ];
    }

    public function caseCard(WeaponCase $case): array
    {
        return [
            'id' => $case->id,
            'name' => $case->name,
            'slug' => $case->slug,
            'href' => '/cases/'.$case->slug,
            'image' => $case->imageUrl(),
            'image_alt' => 'Placeholder artwork for '.$case->name,
            'category' => $case->category,
            'status' => $case->status,
            'attribute_status' => $case->attribute_status,
            'requires_key' => $case->requires_key === null ? null : (bool) $case->requires_key,
            'price' => $this->pricePayload($this->choosePrice($case->prices)),
            'key_price' => $case->key_amount === null ? null : [
                'amount' => (string) $case->key_amount,
                'display' => Money::format((string) $case->key_amount, $case->key_currency ?: 'USD'),
                'currency' => $case->key_currency ?: 'USD',
                'is_demo' => (bool) $case->key_is_demo,
                'is_verified' => (bool) $case->key_is_verified,
            ],
        ];
    }

    public function choosePrice(Collection $prices): ?ItemPrice
    {
        $verified = $prices
            ->filter(fn (ItemPrice $price) => $price->is_verified && ! $price->is_demo)
            ->sortBy(fn (ItemPrice $price) => (float) $price->amount)
            ->first();

        if ($verified) {
            return $verified;
        }

        return $prices->sortByDesc(fn (ItemPrice $price) => $price->observed_at)->first();
    }

    public function pricePayload(ItemPrice|PriceHistory|null $price): ?array
    {
        if (! $price) {
            return null;
        }

        $staleDays = (int) config('clutchbase.price_stale_days');

        return [
            'amount' => (string) $price->amount,
            'display' => Money::format((string) $price->amount, $price->currency),
            'currency' => $price->currency,
            'is_demo' => (bool) $price->is_demo,
            'is_verified' => (bool) $price->is_verified,
            'stale' => $price->observed_at?->lt(now()->subDays($staleDays)) ?? false,
            'observed_at' => $price->observed_at?->toIso8601String(),
            'marketplace' => $price->marketplace?->name,
            'exterior' => $price->exterior?->name,
            'variant' => $price->variant,
        ];
    }

    private function rarity(Item $item): ?array
    {
        if (! $item->rarity) {
            return null;
        }

        return [
            'name' => $item->rarity->name,
            'slug' => $item->rarity->slug,
            'color' => $item->rarity->color,
            'verified' => $item->attribute_status === 'verified',
        ];
    }

    private function verifiedFlag(Item $item, string $attribute): ?bool
    {
        if ($item->attribute_status !== 'verified') {
            return null;
        }

        $value = $item->getAttribute($attribute);

        return $value === null ? null : (bool) $value;
    }
}
