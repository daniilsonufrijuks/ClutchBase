<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Weapon;
use App\Services\Catalog\CatalogPresenter;
use App\Support\Seo;
use Inertia\Inertia;
use Inertia\Response;

class WeaponController extends Controller
{
    public function show(string $slug, CatalogPresenter $presenter): Response
    {
        $weapon = Weapon::query()->where('slug', $slug)->firstOrFail();
        $items = Item::query()->published()->with(['weapon', 'rarity', 'collection', 'prices.marketplace'])
            ->where('weapon_id', $weapon->id)
            ->orderBy('name')
            ->get()
            ->map(fn (Item $item) => $presenter->card($item));

        return Inertia::render('Weapons/Show', [
            'weapon' => ['name' => $weapon->name, 'slug' => $weapon->slug, 'category' => $weapon->category],
            'items' => $items,
            'breadcrumbs' => [
                ['label' => 'Home', 'href' => '/'],
                ['label' => 'Weapons', 'href' => '/skins'],
                ['label' => $weapon->name, 'href' => null],
            ],
            'seo' => Seo::make($weapon->name.' skins', 'Published finishes linked to the '.$weapon->name.'.', url('/weapons/'.$weapon->slug)),
        ]);
    }
}
