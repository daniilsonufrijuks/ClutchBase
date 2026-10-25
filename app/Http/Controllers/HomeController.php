<?php

namespace App\Http\Controllers;

use App\Models\FeaturedPlacement;
use App\Models\Guide;
use App\Models\Item;
use App\Models\ItemPrice;
use App\Models\SearchQuery;
use App\Models\WeaponCase;
use App\Services\Catalog\CatalogPresenter;
use App\Support\CatalogCache;
use App\Support\Seo;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(CatalogPresenter $presenter): Response
    {
        $payload = CatalogCache::remember('home', 600, function () use ($presenter) {
            $placements = FeaturedPlacement::query()->orderBy('position')->get();
            $items = Item::query()->published()->with(['weapon', 'rarity', 'collection', 'prices.marketplace', 'prices.exterior'])
                ->whereIn('id', $placements->where('subject_type', 'item')->pluck('subject_id'))
                ->get()
                ->keyBy('id');
            $cases = WeaponCase::query()->with(['prices.marketplace', 'prices.exterior'])
                ->whereIn('id', $placements->where('subject_type', 'case')->pluck('subject_id'))
                ->get()
                ->keyBy('id');

            $cards = function (string $slot) use ($placements, $items, $cases, $presenter) {
                return $placements->where('slot', $slot)->map(function ($placement) use ($items, $cases, $presenter) {
                    if ($placement->subject_type === 'item' && isset($items[$placement->subject_id])) {
                        return $presenter->card($items[$placement->subject_id]);
                    }
                    if ($placement->subject_type === 'case' && isset($cases[$placement->subject_id])) {
                        return $presenter->caseCard($cases[$placement->subject_id]);
                    }

                    return null;
                })->filter()->values();
            };

            $recent = Item::query()->published()->with(['weapon', 'rarity', 'collection', 'prices.marketplace'])
                ->latest('updated_at')->limit(8)->get()->map(fn (Item $item) => $presenter->card($item));

            $latestPrices = ItemPrice::query()->where('is_demo', false)->with(['marketplace', 'exterior'])->latest('observed_at')->limit(6)->get()
                ->map(fn (ItemPrice $price) => $presenter->pricePayload($price) + [
                    'subject' => $price->priceable_type,
                    'subject_id' => $price->priceable_id,
                ]);

            return [
                'popular' => $cards('popular'),
                'cases' => $cards('case'),
                'knives' => $cards('knife'),
                'noteworthy' => $cards('noteworthy'),
                'recent' => $recent,
                'prices' => $latestPrices,
                'trending' => SearchQuery::query()
                    ->selectRaw('term, count(*) as total')
                    ->where('created_at', '>=', now()->subDays(14))
                    ->groupBy('term')
                    ->orderByDesc('total')
                    ->limit(6)
                    ->get(),
                'guides' => Guide::query()->published()->latest()->limit(3)->get(['title', 'slug', 'excerpt']),
            ];
        });

        return Inertia::render('Home', [
            ...$payload,
            'categories' => [
                ['label' => 'Skins', 'href' => '/skins'],
                ['label' => 'Knives', 'href' => '/knives'],
                ['label' => 'Gloves', 'href' => '/gloves'],
                ['label' => 'Cases', 'href' => '/cases'],
                ['label' => 'Stickers', 'href' => '/stickers'],
                ['label' => 'Agents', 'href' => '/agents'],
            ],
            'seo' => Seo::make(
                'CS2 skins, cases, and strategy planner',
                'Browse a CS2 item catalog, compare labeled prices, check Steam stats, and plan five-player executes.',
                url('/'),
            ),
        ]);
    }
}
