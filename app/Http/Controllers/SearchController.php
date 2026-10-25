<?php

namespace App\Http\Controllers;

use App\Models\SearchQuery;
use App\Services\Catalog\CatalogPresenter;
use App\Services\Catalog\CatalogQuery;
use App\Support\Seo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends Controller
{
    public function index(Request $request, CatalogQuery $query, CatalogPresenter $presenter): Response
    {
        $term = trim((string) $request->input('q', ''));
        $items = $query->items($request)->through(fn ($item) => $presenter->card($item));

        if (mb_strlen($term) >= 2) {
            SearchQuery::query()->create([
                'term' => mb_substr($term, 0, 120),
                'results_count' => $items->total(),
                'user_id' => $request->user()?->id,
                'created_at' => now(),
            ]);
        }

        $recent = [];
        if ($request->user()) {
            $recent = SearchQuery::query()->where('user_id', $request->user()->id)
                ->latest('created_at')->limit(20)->get()
                ->unique('term')->take(5)->pluck('term')->values();
        }

        return Inertia::render('Search/Index', [
            'items' => $items,
            'suggestions' => $term === '' ? [] : $query->suggest($term),
            'recent' => $recent,
            'filters' => $request->only(['q', 'type', 'weapon', 'collection', 'rarity', 'exterior', 'min_price', 'max_price', 'stattrak', 'souvenir', 'sort']),
            'seo' => Seo::make('Search', 'Search the catalog. Result pages are not indexed.', url('/search'), 'noindex,follow'),
        ]);
    }

    public function suggest(Request $request, CatalogQuery $query): JsonResponse
    {
        $term = trim((string) $request->input('q', ''));
        if (mb_strlen($term) < 2) {
            return response()->json(['results' => []]);
        }

        return response()->json(['results' => $query->suggest($term)]);
    }
}
