<?php

namespace App\Http\Controllers;

use App\Models\Guide;
use App\Models\Item;
use App\Models\ItemCollection;
use App\Models\Weapon;
use App\Models\WeaponCase;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Disallow: /account',
            'Disallow: /admin',
            'Disallow: /strategies',
            'Disallow: /teams',
            'Disallow: /s/',
            'Disallow: /login',
            'Disallow: /register',
            'Disallow: /search',
            'Sitemap: '.url('/sitemap.xml'),
        ];

        return response(implode("\n", $lines), 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function sitemap(): Response
    {
        $urls = collect([url('/')])
            ->merge(Item::query()->published()->get()->map(fn (Item $item) => url($item->canonicalPath())))
            ->merge(WeaponCase::query()->listed()->pluck('slug')->map(fn ($slug) => url('/cases/'.$slug)))
            ->merge(ItemCollection::query()->pluck('slug')->map(fn ($slug) => url('/collections/'.$slug)))
            ->merge(Weapon::query()->pluck('slug')->map(fn ($slug) => url('/weapons/'.$slug)))
            ->merge(Guide::query()->published()->pluck('slug')->map(fn ($slug) => url('/guides/'.$slug)))
            ->merge([
                url('/skins'), url('/knives'), url('/gloves'), url('/stickers'), url('/agents'), url('/cases'), url('/collections'), url('/guides'), url('/steam'),
            ]);

        $body = $urls->unique()->map(fn ($url) => '<url><loc>'.e($url).'</loc></url>')->implode('');
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.$body.'</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
