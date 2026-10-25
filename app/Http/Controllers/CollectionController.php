<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ItemCollection;
use App\Services\Catalog\CatalogPresenter;
use App\Support\Seo;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CollectionController extends Controller
{
    public function index(): Response
    {
        $collections = ItemCollection::query()->withCount('items')->orderBy('name')->paginate(24);

        return Inertia::render('Collections/Index', [
            'collections' => $collections,
            'seo' => Seo::make('Collections', 'Browse item collections and the skins linked to them.', url('/collections')),
        ]);
    }

    public function show(Request $request, string $slug, CatalogPresenter $presenter): Response
    {
        $collection = ItemCollection::query()->where('slug', $slug)->firstOrFail();
        $items = Item::query()->published()->with(['weapon', 'rarity', 'collection', 'prices.marketplace'])
            ->where('collection_id', $collection->id)
            ->when($request->filled('weapon'), fn ($query) => $query->whereHas('weapon', fn ($weapon) => $weapon->where('slug', $request->string('weapon'))))
            ->when($request->filled('rarity'), fn ($query) => $query->whereHas('rarity', fn ($rarity) => $rarity->where('slug', $request->string('rarity'))))
            ->orderBy('name')
            ->get()
            ->map(fn (Item $item) => $presenter->card($item));

        return Inertia::render('Collections/Show', [
            'collection' => [
                'name' => $collection->name,
                'slug' => $collection->slug,
                'description' => $collection->description,
                'released_on' => $collection->released_on?->toDateString(),
                'attribute_status' => $collection->attribute_status,
            ],
            'items' => $items,
            'filters' => $request->only(['weapon', 'rarity']),
            'breadcrumbs' => [
                ['label' => 'Home', 'href' => '/'],
                ['label' => 'Collections', 'href' => '/collections'],
                ['label' => $collection->name, 'href' => null],
            ],
            'seo' => Seo::make($collection->meta_title ?: $collection->name, $collection->meta_description ?: 'Items linked to '.$collection->name.'.', url('/collections/'.$collection->slug)),
        ]);
    }
}
