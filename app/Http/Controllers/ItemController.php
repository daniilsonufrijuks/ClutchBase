<?php

namespace App\Http\Controllers;

use App\Models\Exterior;
use App\Models\Favorite;
use App\Models\Item;
use App\Models\ItemCollection;
use App\Models\Rarity;
use App\Models\SlugRedirect;
use App\Models\Weapon;
use App\Services\Catalog\CatalogPresenter;
use App\Services\Catalog\CatalogQuery;
use App\Support\CatalogCache;
use App\Support\Seo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ItemController extends Controller
{
    private array $catalog = [
        'skins' => 'skin',
        'knives' => 'knife',
        'gloves' => 'glove',
        'stickers' => 'sticker',
        'agents' => 'agent',
    ];

    public function index(Request $request, CatalogQuery $query, CatalogPresenter $presenter): Response
    {
        $catalog = (string) $request->route('catalog', '');
        if (isset($this->catalog[$catalog])) {
            $request->merge(['type' => $this->catalog[$catalog]]);
        }

        $items = $query->items($request)->through(fn (Item $item) => $presenter->card($item));
        $type = (string) $request->input('type', '');
        $title = $type ? ucfirst($type).' catalog' : 'Item catalog';

        return Inertia::render('Items/Index', [
            'items' => $items,
            'filters' => $request->only(['q', 'type', 'weapon', 'collection', 'rarity', 'exterior', 'min_price', 'max_price', 'stattrak', 'souvenir', 'sort']),
            'options' => $this->options(),
            'seo' => Seo::make($title, 'Search published CS2 catalog records. Prices and attributes stay labeled when they are demo or unverified.', url()->current()),
        ]);
    }

    public function show(Request $request, string $slug, CatalogPresenter $presenter): Response|RedirectResponse
    {
        $catalog = (string) $request->route('catalog');
        $item = Item::query()->where('slug', $slug)->first();

        if (! $item) {
            $redirect = SlugRedirect::query()->where('slug', $slug)->where('subject_type', 'item')->first();
            $target = $redirect ? Item::query()->find($redirect->subject_id) : null;
            if ($target) {
                return redirect($target->canonicalPath(), 301);
            }
            abort(404);
        }

        if ($item->status !== 'published' && ! $request->user()?->isAdmin()) {
            abort(404);
        }

        if (($this->catalog[$catalog] ?? null) !== $item->type) {
            return redirect($item->canonicalPath(), 301);
        }

        $item->load([
            'weapon', 'rarity', 'collection', 'exteriors.exterior', 'prices.marketplace', 'prices.exterior',
            'priceHistories.marketplace', 'priceHistories.exterior', 'caseContents.case',
        ]);

        $variant = in_array($request->input('variant'), ['standard', 'stattrak', 'souvenir'], true)
            ? (string) $request->input('variant')
            : 'standard';

        $detail = $presenter->detail($item, $request->string('exterior')->toString() ?: null, $variant, $request->user());
        $related = Item::query()->published()->with(['weapon', 'rarity', 'collection', 'prices.marketplace'])
            ->whereKeyNot($item->id)
            ->where(function ($builder) use ($item) {
                $builder->where('weapon_id', $item->weapon_id)
                    ->orWhere('collection_id', $item->collection_id);
            })
            ->limit(6)
            ->get()
            ->map(fn (Item $relatedItem) => $presenter->card($relatedItem));

        $favorite = false;
        if ($user = $request->user()) {
            $favorite = Favorite::query()
                ->where('user_id', $user->id)
                ->where('favoritable_type', $item->getMorphClass())
                ->where('favoritable_id', $item->id)
                ->exists();
        }

        $jsonLd = [[
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => ucfirst($catalog), 'item' => url('/'.$catalog)],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $item->name, 'item' => url($item->canonicalPath())],
            ],
        ]];

        if ($item->attribute_status === 'verified' && ($detail['price']['is_verified'] ?? false) && ! ($detail['price']['is_demo'] ?? true)) {
            $jsonLd[] = [
                '@context' => 'https://schema.org',
                '@type' => 'Product',
                'name' => $item->name,
                'description' => $item->description ?: $item->name,
                'offers' => [
                    '@type' => 'Offer',
                    'price' => $detail['price']['amount'],
                    'priceCurrency' => $detail['price']['currency'],
                ],
            ];
        }

        return Inertia::render('Items/Show', [
            'item' => $detail,
            'related' => $related,
            'favorite' => $favorite,
            'breadcrumbs' => [
                ['label' => 'Home', 'href' => '/'],
                ['label' => ucfirst($catalog), 'href' => '/'.$catalog],
                ['label' => $item->name, 'href' => null],
            ],
            'seo' => Seo::make(
                $item->meta_title ?: $item->name,
                $item->meta_description ?: ($item->description ?: 'Catalog record for '.$item->name.'. Unverified fields are labeled.'),
                url($item->canonicalPath()),
                'index,follow',
                $jsonLd,
                url($item->imageUrl()),
            ),
        ]);
    }

    private function options(): array
    {
        return CatalogCache::remember('filters', 600, fn () => [
            'weapons' => Weapon::query()->orderBy('name')->get(['name', 'slug', 'category']),
            'rarities' => Rarity::query()->orderBy('sort_order')->get(['name', 'slug', 'color']),
            'collections' => ItemCollection::query()->orderBy('name')->get(['name', 'slug']),
            'exteriors' => Exterior::query()->orderBy('sort_order')->get(['name', 'slug']),
        ]);
    }
}
