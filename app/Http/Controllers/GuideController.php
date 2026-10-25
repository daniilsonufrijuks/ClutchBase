<?php

namespace App\Http\Controllers;

use App\Models\Guide;
use App\Support\Seo;
use Inertia\Inertia;
use Inertia\Response;

class GuideController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Guides/Index', [
            'guides' => Guide::query()->published()->orderBy('title')->get(['title', 'slug', 'excerpt']),
            'seo' => Seo::make('Guides', 'Notes on how ClutchBase labels data, prices, and Steam figures.', url('/guides')),
        ]);
    }

    public function show(string $slug): Response
    {
        $guide = Guide::query()->published()->where('slug', $slug)->firstOrFail();

        return Inertia::render('Guides/Show', [
            'guide' => $guide->only(['title', 'slug', 'excerpt', 'body']),
            'breadcrumbs' => [
                ['label' => 'Home', 'href' => '/'],
                ['label' => 'Guides', 'href' => '/guides'],
                ['label' => $guide->title, 'href' => null],
            ],
            'seo' => Seo::make($guide->meta_title ?: $guide->title, $guide->meta_description ?: (string) $guide->excerpt, url('/guides/'.$guide->slug)),
        ]);
    }
}
