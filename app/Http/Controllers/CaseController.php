<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\ItemCollection;
use App\Models\SlugRedirect;
use App\Models\Weapon;
use App\Models\WeaponCase;
use App\Services\Catalog\CatalogPresenter;
use App\Support\CatalogCache;
use App\Support\Seo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CaseController extends Controller
{
    public function index(Request $request, CatalogPresenter $presenter): Response
    {
        $filters = $request->only(['q', 'category', 'collection', 'weapon', 'key', 'sort']);
        $options = CatalogCache::remember('case-filters', 600, fn () => [
            'categories' => WeaponCase::query()->listed()->whereNotNull('category')->distinct()->orderBy('category')->pluck('category')->values(),
            'collections' => ItemCollection::query()->whereHas('cases')->orderBy('name')->get(['name', 'slug']),
            'weapons' => Weapon::query()->whereHas('items.caseContents')->orderBy('name')->get(['name', 'slug']),
        ]);
        if (! empty($filters['category'])) {
            $match = collect($options['categories'])->first(fn ($category) => strcasecmp((string) $category, (string) $filters['category']) === 0);
            $filters['category'] = $match ?? $filters['category'];
        }
        $cases = WeaponCase::query()->listed()->with(['prices.marketplace', 'collection']);

        $term = trim((string) ($filters['q'] ?? ''));
        if ($term !== '') {
            $cases->where('name', 'like', '%'.addcslashes($term, '%_\\').'%');
        }
        if (! empty($filters['category'])) {
            $cases->where('category', $filters['category']);
        }
        if (! empty($filters['collection'])) {
            $cases->whereHas('collection', fn ($query) => $query->where('slug', $filters['collection']));
        }
        if (! empty($filters['weapon'])) {
            $cases->whereHas('contents.item.weapon', fn ($query) => $query->where('slug', $filters['weapon']));
        }
        if (($filters['key'] ?? '') === 'yes') {
            $cases->where('requires_key', true);
        } elseif (($filters['key'] ?? '') === 'no') {
            $cases->where('requires_key', false);
        }

        match ($filters['sort'] ?? 'name') {
            'newest' => $cases->orderByDesc('released_on')->orderBy('name'),
            'oldest' => $cases->orderBy('released_on')->orderBy('name'),
            default => $cases->orderBy('name'),
        };

        return Inertia::render('Cases/Index', [
            'cases' => $cases->paginate(24)->withQueryString()->through(fn (WeaponCase $case) => $presenter->caseCard($case)),
            'filters' => $filters,
            'options' => $options,
            'seo' => Seo::make('CS2 cases', 'Browse case records, contents, and labeled prices.', url('/cases'), $request->query() ? 'noindex,follow' : 'index,follow'),
        ]);
    }

    public function show(Request $request, string $slug, CatalogPresenter $presenter): Response|RedirectResponse
    {
        $case = WeaponCase::query()->where('slug', $slug)->first();
        if (! $case) {
            $redirect = SlugRedirect::query()->where('slug', $slug)->where('subject_type', 'case')->first();
            $target = $redirect ? WeaponCase::query()->find($redirect->subject_id) : null;
            abort_unless($target, 404);

            return redirect('/cases/'.$target->slug, 301);
        }

        if ($case->status === 'archived' && ! $request->user()?->isAdmin()) {
            abort(404);
        }

        $case->load([
            'collection', 'prices.marketplace', 'prices.exterior', 'priceHistories.marketplace',
            'contents.item.weapon', 'contents.item.rarity', 'contents.item.collection', 'contents.item.prices.marketplace',
        ]);

        $groups = $case->contents->groupBy(fn ($content) => $content->item?->rarity?->name ?? 'Unclassified')
            ->map(fn ($rows, $name) => [
                'name' => $name,
                'color' => $rows->first()->item?->rarity?->color ?? '#b7c0ce',
                'items' => $rows->map(fn ($content) => $presenter->card($content->item) + [
                    'is_special' => (bool) $content->is_special,
                    'probability_known' => (bool) $content->probability_known,
                    'drop_group' => $content->drop_group,
                ])->values(),
            ])->values();

        $favorite = $request->user() && Favorite::query()
            ->where('user_id', $request->user()->id)
            ->where('favoritable_type', $case->getMorphClass())
            ->where('favoritable_id', $case->id)
            ->exists();

        $related = WeaponCase::query()->listed()->whereKeyNot($case->id)->with('prices.marketplace')->limit(4)->get()
            ->map(fn (WeaponCase $relatedCase) => $presenter->caseCard($relatedCase));

        return Inertia::render('Cases/Show', [
            'caseItem' => $presenter->caseCard($case) + [
                'description' => $case->description,
                'released_on' => $case->released_on?->toDateString(),
                'collection' => $case->collection ? [
                    'name' => $case->collection->name,
                    'href' => '/collections/'.$case->collection->slug,
                    'attribute_status' => $case->collection->attribute_status,
                ] : null,
                'groups' => $groups,
                'history' => $case->priceHistories->sortBy('observed_at')->values()->map(fn ($price) => $presenter->pricePayload($price)),
                'quotes' => $case->prices->map(fn ($price) => $presenter->pricePayload($price))->values(),
            ],
            'related' => $related,
            'favorite' => $favorite,
            'breadcrumbs' => [
                ['label' => 'Home', 'href' => '/'],
                ['label' => 'Cases', 'href' => '/cases'],
                ['label' => $case->name, 'href' => null],
            ],
            'seo' => Seo::make(
                $case->meta_title ?: $case->name,
                $case->meta_description ?: 'Case contents and labeled prices for '.$case->name.'.',
                url('/cases/'.$case->slug),
                'index,follow',
                [[
                    '@context' => 'https://schema.org',
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => [
                        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
                        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Cases', 'item' => url('/cases')],
                        ['@type' => 'ListItem', 'position' => 3, 'name' => $case->name, 'item' => url('/cases/'.$case->slug)],
                    ],
                ]],
                url($case->imageUrl()),
            ),
        ]);
    }
}
