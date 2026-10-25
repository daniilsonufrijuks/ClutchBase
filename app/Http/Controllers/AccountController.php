<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\Item;
use App\Models\PriceAlert;
use App\Models\SavedFilter;
use App\Models\Team;
use App\Models\WeaponCase;
use App\Models\Wishlist;
use App\Services\Accounts\Entitlements;
use App\Services\Billing\ManualSubscriptionGateway;
use App\Services\Catalog\CatalogPresenter;
use App\Support\Seo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    public function show(Request $request): Response
    {
        return Inertia::render('Account/Show', [
            'preferences' => $request->user()->preferences ?? [],
            'seo' => Seo::make('Account', 'Your ClutchBase account.', url('/account'), 'noindex,nofollow'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'display_name' => ['nullable', 'string', 'max:80'],
            'currency' => ['required', 'in:USD,EUR,GBP'],
        ]);

        $request->user()->update($data);

        return back()->with('success', 'Preferences saved.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password']]);
        $user = $request->user();

        DB::transaction(function () use ($user) {
            Team::query()->where('owner_id', $user->id)->delete();
            $user->delete();
        });

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', 'Your account has been deleted.');
    }

    public function favorites(Request $request, CatalogPresenter $presenter): Response
    {
        $favorites = Favorite::query()->where('user_id', $request->user()->id)->latest()->get();
        $items = Item::query()->with(['weapon', 'rarity', 'collection', 'prices.marketplace'])
            ->whereIn('id', $favorites->where('favoritable_type', 'item')->pluck('favoritable_id'))->get()
            ->map(fn (Item $item) => $presenter->card($item));
        $cases = WeaponCase::query()->with('prices.marketplace')
            ->whereIn('id', $favorites->where('favoritable_type', 'case')->pluck('favoritable_id'))->get()
            ->map(fn (WeaponCase $case) => $presenter->caseCard($case));

        return Inertia::render('Account/Favorites', [
            'items' => $items,
            'cases' => $cases,
            'seo' => Seo::make('Favorites', 'Saved catalog records.', url('/account/favorites'), 'noindex,nofollow'),
        ]);
    }

    public function toggleFavorite(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:item,case'],
            'slug' => ['required', 'string'],
        ]);

        $model = $data['type'] === 'item'
            ? Item::query()->where('slug', $data['slug'])->firstOrFail()
            : WeaponCase::query()->where('slug', $data['slug'])->firstOrFail();

        $existing = Favorite::query()
            ->where('user_id', $request->user()->id)
            ->where('favoritable_type', $model->getMorphClass())
            ->where('favoritable_id', $model->id)
            ->first();

        if ($existing) {
            $existing->delete();

            return back()->with('success', 'Removed from favorites.');
        }

        Favorite::query()->create([
            'user_id' => $request->user()->id,
            'favoritable_type' => $model->getMorphClass(),
            'favoritable_id' => $model->id,
        ]);

        return back()->with('success', 'Saved to favorites.');
    }

    public function wishlists(Request $request, CatalogPresenter $presenter, Entitlements $entitlements): Response
    {
        $lists = Wishlist::query()->where('user_id', $request->user()->id)->with(['items.item.weapon', 'items.item.rarity', 'items.item.prices.marketplace'])->get()
            ->map(fn (Wishlist $list) => [
                'id' => $list->id,
                'name' => $list->name,
                'items' => $list->items->map(fn ($row) => [
                    'id' => $row->id,
                    'note' => $row->note,
                    'target_price' => $row->target_price,
                    'item' => $row->item ? $presenter->card($row->item) : null,
                ])->values(),
            ]);

        return Inertia::render('Account/Wishlists', [
            'wishlists' => $lists,
            'can_create' => $entitlements->canCreateWishlist($request->user()),
            'seo' => Seo::make('Wishlists', 'Your wishlists.', url('/account/wishlists'), 'noindex,nofollow'),
        ]);
    }

    public function storeWishlist(Request $request, Entitlements $entitlements): RedirectResponse
    {
        abort_unless($entitlements->canCreateWishlist($request->user()), 403, 'Additional wishlists require premium.');
        $data = $request->validate(['name' => ['required', 'string', 'max:80']]);
        Wishlist::query()->create(['user_id' => $request->user()->id, 'name' => $data['name']]);

        return back()->with('success', 'Wishlist created.');
    }

    public function storeWishlistItem(Request $request, Wishlist $wishlist): RedirectResponse
    {
        abort_unless($wishlist->user_id === $request->user()->id, 403);
        $data = $request->validate([
            'slug' => ['required', 'string'],
            'note' => ['nullable', 'string', 'max:160'],
            'target_price' => ['nullable', 'numeric', 'min:0'],
        ]);
        $item = Item::query()->where('slug', $data['slug'])->firstOrFail();
        $wishlist->items()->updateOrCreate(
            ['item_id' => $item->id],
            ['note' => $data['note'] ?? null, 'target_price' => $data['target_price'] ?? null],
        );

        return back()->with('success', 'Wishlist updated.');
    }

    public function destroyWishlistItem(Request $request, Wishlist $wishlist, int $wishlistItem): RedirectResponse
    {
        abort_unless($wishlist->user_id === $request->user()->id, 403);
        $wishlist->items()->whereKey($wishlistItem)->delete();

        return back()->with('success', 'Removed from wishlist.');
    }

    public function alerts(Request $request): Response
    {
        return Inertia::render('Account/Alerts', [
            'alerts' => PriceAlert::query()->with('item')->where('user_id', $request->user()->id)->latest()->get(),
            'checkout_configured' => (new ManualSubscriptionGateway)->isConfigured(),
            'seo' => Seo::make('Price alerts', 'Premium price alerts.', url('/account/alerts'), 'noindex,nofollow'),
        ]);
    }

    public function storeAlert(Request $request, Entitlements $entitlements): RedirectResponse
    {
        abort_unless($entitlements->canCreateAlert($request->user()), 403);
        $data = $request->validate([
            'slug' => ['required', 'exists:items,slug'],
            'threshold' => ['required', 'numeric', 'min:0'],
            'direction' => ['required', 'in:below,above'],
            'variant' => ['required', 'in:standard,stattrak,souvenir'],
        ]);
        $item = Item::query()->where('slug', $data['slug'])->firstOrFail();
        PriceAlert::query()->create([
            'user_id' => $request->user()->id,
            'item_id' => $item->id,
            'threshold' => $data['threshold'],
            'direction' => $data['direction'],
            'variant' => $data['variant'],
        ]);

        return back()->with('success', 'Alert saved.');
    }

    public function storeFilter(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'query' => ['required', 'array'],
        ]);
        SavedFilter::query()->create([
            'user_id' => $request->user()->id,
            'name' => $data['name'],
            'query' => $data['query'],
        ]);

        return back()->with('success', 'Filter preset saved.');
    }
}
