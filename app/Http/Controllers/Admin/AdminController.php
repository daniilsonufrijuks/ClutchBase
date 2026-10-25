<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SyncMarketPrices;
use App\Models\CaseContent;
use App\Models\DataSource;
use App\Models\Exterior;
use App\Models\Favorite;
use App\Models\FeaturedPlacement;
use App\Models\ImportJob;
use App\Models\Item;
use App\Models\ItemCollection;
use App\Models\ItemPrice;
use App\Models\PriceHistory;
use App\Models\Rarity;
use App\Models\SearchQuery;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Weapon;
use App\Models\WeaponCase;
use App\Models\WishlistItem;
use App\Services\Catalog\CatalogImporter;
use App\Support\CatalogCache;
use App\Support\PlaceholderArt;
use App\Support\Seo;
use App\Support\Slugs;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    public function dashboard(): Response
    {
        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'users' => User::query()->count(),
                'items' => Item::query()->where('status', 'published')->count(),
                'cases' => WeaponCase::query()->count(),
            ],
            'searches' => SearchQuery::query()->selectRaw('term, count(*) as total')->groupBy('term')->orderByDesc('total')->limit(8)->get(),
            'imports' => ImportJob::query()->latest()->limit(5)->get(),
            'sources' => DataSource::query()->orderBy('name')->get(),
            'seo' => Seo::make('Admin', 'ClutchBase administration.', url('/admin'), 'noindex,nofollow'),
        ]);
    }

    public function items(): Response
    {
        return Inertia::render('Admin/Items', [
            'items' => Item::query()->with(['weapon', 'rarity'])->latest()->paginate(20),
            'weapons' => Weapon::query()->orderBy('name')->get(['id', 'name', 'slug']),
            'rarities' => Rarity::query()->orderBy('sort_order')->get(['id', 'name']),
            'collections' => ItemCollection::query()->orderBy('name')->get(['id', 'name']),
            'exteriors' => Exterior::query()->orderBy('sort_order')->get(['id', 'name']),
            'seo' => Seo::make('Manage items', 'Admin item editor.', url('/admin/items'), 'noindex,nofollow'),
        ]);
    }

    public function storeItem(Request $request): RedirectResponse
    {
        $item = $this->persistItem(new Item, $request, true);
        PlaceholderArt::writeItem(public_path('media/items/'.$item->slug.'.svg'), $item->name, 'Catalog placeholder', '#d7a35a');
        CatalogCache::bump();

        return back()->with('success', 'Item created.');
    }

    public function updateItem(Request $request, Item $item): RedirectResponse
    {
        $previous = $item->slug;
        $this->persistItem($item, $request, false);
        if ($previous !== $item->slug) {
            Slugs::remember($item, 'item', $previous);
        }
        CatalogCache::bump();

        return back()->with('success', 'Item updated.');
    }

    public function destroyItem(Item $item): RedirectResponse
    {
        DB::transaction(function () use ($item) {
            Favorite::query()->where('favoritable_type', 'item')->where('favoritable_id', $item->id)->delete();
            WishlistItem::query()->where('item_id', $item->id)->delete();
            CaseContent::query()->where('item_id', $item->id)->delete();
            ItemPrice::query()->where('priceable_type', 'item')->where('priceable_id', $item->id)->delete();
            PriceHistory::query()->where('priceable_type', 'item')->where('priceable_id', $item->id)->delete();
            $item->delete();
        });
        CatalogCache::bump();

        return back()->with('success', 'Item deleted.');
    }

    public function cases(): Response
    {
        return Inertia::render('Admin/Cases', [
            'cases' => WeaponCase::query()->withCount('contents')->orderBy('name')->paginate(20),
            'items' => Item::query()->orderBy('name')->get(['id', 'name', 'slug']),
            'collections' => ItemCollection::query()->orderBy('name')->get(['id', 'name']),
            'seo' => Seo::make('Manage cases', 'Admin case editor.', url('/admin/cases'), 'noindex,nofollow'),
        ]);
    }

    public function storeCase(Request $request): RedirectResponse
    {
        $data = $this->caseData($request);
        $case = WeaponCase::query()->create($data + [
            'slug' => Slugs::unique(WeaponCase::class, $data['name']),
        ]);
        $this->syncContents($case, $request->input('contents', []));
        PlaceholderArt::writeItem(public_path('media/cases/'.$case->slug.'.svg'), $case->name, 'Case placeholder');
        CatalogCache::bump();

        return back()->with('success', 'Case created.');
    }

    public function updateCase(Request $request, WeaponCase $case): RedirectResponse
    {
        $previous = $case->slug;
        $data = $this->caseData($request);
        if ($request->filled('slug') && $request->input('slug') !== $case->slug) {
            $data['slug'] = Slugs::unique(WeaponCase::class, (string) $request->input('slug'), $case->id);
        }
        $case->update($data);
        if ($previous !== $case->slug) {
            Slugs::remember($case, 'case', $previous);
        }
        $this->syncContents($case, $request->input('contents', []));
        CatalogCache::bump();

        return back()->with('success', 'Case updated.');
    }

    public function imports(): Response
    {
        return Inertia::render('Admin/Imports', [
            'jobs' => ImportJob::query()->latest()->limit(15)->get(),
            'seo' => Seo::make('Imports', 'CSV catalog imports.', url('/admin/imports'), 'noindex,nofollow'),
        ]);
    }

    public function storeImport(Request $request, CatalogImporter $importer): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:2048']]);
        $job = $importer->import($request->file('file'), $request->user());

        return back()->with('success', 'Import finished. Created '.$job->created_count.', updated '.$job->updated_count.', skipped '.$job->skipped_count.'.');
    }

    public function users(): Response
    {
        return Inertia::render('Admin/Users', [
            'users' => User::query()->with('subscription')->orderBy('name')->paginate(20),
            'seo' => Seo::make('Users', 'Manage accounts.', url('/admin/users'), 'noindex,nofollow'),
        ]);
    }

    public function updateUser(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'role' => ['required', 'in:user,admin'],
            'plan' => ['required', 'in:free,premium'],
        ]);

        if ($user->is($request->user()) && $data['role'] !== 'admin') {
            return back()->with('error', 'You cannot remove your own admin access.');
        }

        $user->forceFill(['role' => $data['role']])->save();
        Subscription::query()->updateOrCreate(
            ['user_id' => $user->id],
            ['plan' => $data['plan'], 'status' => 'active', 'provider' => 'manual'],
        );

        return back()->with('success', 'User updated.');
    }

    public function sources(): Response
    {
        return Inertia::render('Admin/Sources', [
            'sources' => DataSource::query()->orderBy('name')->get(),
            'provider' => config('prices.provider'),
            'configured' => config('prices.provider') === 'http' && filled(config('prices.http.url')) && filled(config('prices.http.token')),
            'seo' => Seo::make('Price sources', 'Price provider status.', url('/admin/sources'), 'noindex,nofollow'),
        ]);
    }

    public function syncPrices(): RedirectResponse
    {
        SyncMarketPrices::dispatchSync();

        return back()->with('success', 'Price sync finished.');
    }

    public function featured(): Response
    {
        return Inertia::render('Admin/Featured', [
            'placements' => FeaturedPlacement::query()->orderBy('slot')->orderBy('position')->get(),
            'items' => Item::query()->orderBy('name')->get(['id', 'name', 'slug']),
            'cases' => WeaponCase::query()->orderBy('name')->get(['id', 'name', 'slug']),
            'seo' => Seo::make('Featured', 'Homepage placements.', url('/admin/featured'), 'noindex,nofollow'),
        ]);
    }

    public function storeFeatured(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'slot' => ['required', 'in:popular,case,knife,noteworthy'],
            'subject_type' => ['required', 'in:item,case'],
            'subject_id' => ['required', 'integer'],
            'position' => ['required', 'integer', 'min:0', 'max:20'],
        ]);
        FeaturedPlacement::query()->create($data);
        CatalogCache::bump();

        return back()->with('success', 'Placement saved.');
    }

    private function persistItem(Item $item, Request $request, bool $creating): Item
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(Item::TYPES)],
            'name' => ['required', 'string', 'max:160'],
            'weapon_id' => ['nullable', 'exists:weapons,id'],
            'rarity_id' => ['nullable', 'exists:rarities,id'],
            'collection_id' => ['nullable', 'exists:collections,id'],
            'description' => ['nullable', 'string', 'max:5000'],
            'min_float' => ['nullable', 'numeric', 'between:0,1'],
            'max_float' => ['nullable', 'numeric', 'between:0,1'],
            'status' => ['required', 'in:draft,published,archived'],
            'attribute_status' => ['required', 'in:demo,unverified,verified'],
            'meta_title' => ['nullable', 'string', 'max:160'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'exterior_ids' => ['array'],
            'exterior_ids.*' => ['integer', 'exists:exteriors,id'],
            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        if ($creating || ($request->filled('name') && $item->name !== $data['name'] && ! $request->filled('keep_slug'))) {
            $data['slug'] = Slugs::unique(Item::class, $data['name'], $item->exists ? $item->id : null);
        }

        if ($data['attribute_status'] === 'verified') {
            $data['verified_at'] = now();
        }

        $exteriorIds = $data['exterior_ids'] ?? [];
        unset($data['exterior_ids'], $data['image']);
        $item->fill($data);
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('items', 'public');
            $item->image_path = '/storage/'.$path;
        }
        $item->save();
        $item->exteriors()->delete();
        foreach ($exteriorIds as $exteriorId) {
            $item->exteriors()->create([
                'exterior_id' => $exteriorId,
                'is_verified' => $item->attribute_status === 'verified',
            ]);
        }

        return $item;
    }

    private function caseData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:5000'],
            'category' => ['required', 'in:case,souvenir,capsule'],
            'status' => ['required', 'in:unknown,active,discontinued,rare_drop,archived'],
            'collection_id' => ['nullable', 'exists:collections,id'],
            'attribute_status' => ['required', 'in:demo,unverified,verified'],
            'requires_key' => ['nullable', 'boolean'],
            'meta_title' => ['nullable', 'string', 'max:160'],
            'meta_description' => ['nullable', 'string', 'max:320'],
        ]);
    }

    private function syncContents(WeaponCase $case, array $contents): void
    {
        $case->contents()->delete();
        foreach ($contents as $content) {
            if (empty($content['item_id'])) {
                continue;
            }
            $case->contents()->create([
                'item_id' => $content['item_id'],
                'drop_group' => $content['drop_group'] ?? null,
                'is_special' => (bool) ($content['is_special'] ?? false),
                'probability_known' => false,
                'probability_bps' => null,
            ]);
        }
    }
}
