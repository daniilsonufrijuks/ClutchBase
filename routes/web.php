<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\CaseController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\GuideController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\SteamController;
use App\Http\Controllers\StrategyController;
use App\Http\Controllers\WeaponController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');

Route::middleware('throttle:search')->group(function () {
    Route::get('/search', [SearchController::class, 'index'])->name('search');
    Route::get('/search/suggest', [SearchController::class, 'suggest'])->name('search.suggest');
});

foreach (['skins' => 'skin', 'knives' => 'knife', 'gloves' => 'glove', 'stickers' => 'sticker', 'agents' => 'agent'] as $catalog => $type) {
    Route::get('/'.$catalog, [ItemController::class, 'index'])->name($catalog.'.index')->defaults('catalog', $catalog);
    Route::get('/'.$catalog.'/{slug}', [ItemController::class, 'show'])->name($catalog.'.show')->defaults('catalog', $catalog);
}

Route::get('/items', [ItemController::class, 'index'])->name('items.index');
Route::get('/weapons/{slug}', [WeaponController::class, 'show'])->name('weapons.show');
Route::get('/cases', [CaseController::class, 'index'])->name('cases.index');
Route::get('/cases/{slug}', [CaseController::class, 'show'])->name('cases.show');
Route::get('/collections', [CollectionController::class, 'index'])->name('collections.index');
Route::get('/collections/{slug}', [CollectionController::class, 'show'])->name('collections.show');
Route::get('/guides', [GuideController::class, 'index'])->name('guides.index');
Route::get('/guides/{slug}', [GuideController::class, 'show'])->name('guides.show');
Route::get('/steam', SteamController::class)->middleware('throttle:steam')->name('steam');

Route::middleware(['throttle:auth', 'guest'])->group(function () {
    Route::get('/login', [SessionController::class, 'create'])->name('login');
    Route::post('/login', [SessionController::class, 'store']);
    Route::get('/register', [SessionController::class, 'register'])->name('register');
    Route::post('/register', [SessionController::class, 'signup']);
});
Route::post('/logout', [SessionController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/account', [AccountController::class, 'show'])->name('account');
    Route::patch('/account', [AccountController::class, 'update']);
    Route::delete('/account', [AccountController::class, 'destroy'])->name('account.destroy');
    Route::get('/account/favorites', [AccountController::class, 'favorites'])->name('favorites');
    Route::post('/account/favorites', [AccountController::class, 'toggleFavorite'])->name('favorites.toggle');
    Route::get('/account/wishlists', [AccountController::class, 'wishlists'])->name('wishlists');
    Route::post('/account/wishlists', [AccountController::class, 'storeWishlist']);
    Route::post('/account/wishlists/{wishlist}/items', [AccountController::class, 'storeWishlistItem'])->name('wishlists.items.store');
    Route::delete('/account/wishlists/{wishlist}/items/{wishlistItem}', [AccountController::class, 'destroyWishlistItem'])->name('wishlists.items.destroy');
    Route::get('/account/alerts', [AccountController::class, 'alerts'])->name('alerts');
    Route::post('/account/alerts', [AccountController::class, 'storeAlert']);
    Route::post('/account/filters', [AccountController::class, 'storeFilter'])->middleware('premium');

    Route::get('/strategies', [StrategyController::class, 'index'])->name('strategies.index');
    Route::get('/strategies/create', [StrategyController::class, 'create'])->name('strategies.create');
    Route::post('/strategies', [StrategyController::class, 'store'])->name('strategies.store');
    Route::get('/strategies/{strategy}', [StrategyController::class, 'edit'])->name('strategies.edit');
    Route::put('/strategies/{strategy}', [StrategyController::class, 'update'])->name('strategies.update');
    Route::post('/strategies/{strategy}/duplicate', [StrategyController::class, 'duplicate'])->name('strategies.duplicate');
    Route::post('/strategies/{strategy}/archive', [StrategyController::class, 'archive'])->name('strategies.archive');
    Route::post('/strategies/{strategy}/shares', [StrategyController::class, 'share'])->name('strategies.shares.store');
    Route::delete('/strategy-shares/{share}', [StrategyController::class, 'revokeShare'])->name('strategies.shares.revoke');
    Route::get('/strategies/{strategy}/print', [StrategyController::class, 'print'])->name('strategies.print');
    Route::get('/strategies/{strategy}/export', [StrategyController::class, 'export'])->name('strategies.export');
    Route::post('/s/{token}/duplicate', [StrategyController::class, 'duplicateShare'])->name('shares.duplicate');
    Route::post('/teams', [StrategyController::class, 'storeTeam'])->name('teams.store');
    Route::post('/teams/{team}/folders', [StrategyController::class, 'storeFolder'])->name('teams.folders.store');
    Route::post('/teams/{team}/invites', [StrategyController::class, 'invite'])->name('teams.invites.store');
});

Route::get('/s/{token}', [StrategyController::class, 'openShare'])->name('shares.show');

Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/items', [AdminController::class, 'items'])->name('admin.items');
    Route::post('/items', [AdminController::class, 'storeItem']);
    Route::put('/items/{item}', [AdminController::class, 'updateItem'])->name('admin.items.update');
    Route::delete('/items/{item}', [AdminController::class, 'destroyItem'])->name('admin.items.destroy');
    Route::get('/cases', [AdminController::class, 'cases'])->name('admin.cases');
    Route::post('/cases', [AdminController::class, 'storeCase']);
    Route::put('/cases/{case}', [AdminController::class, 'updateCase'])->name('admin.cases.update');
    Route::get('/imports', [AdminController::class, 'imports'])->name('admin.imports');
    Route::post('/imports', [AdminController::class, 'storeImport'])->middleware('throttle:import');
    Route::get('/users', [AdminController::class, 'users'])->name('admin.users');
    Route::put('/users/{user}', [AdminController::class, 'updateUser'])->name('admin.users.update');
    Route::get('/sources', [AdminController::class, 'sources'])->name('admin.sources');
    Route::post('/sources/sync', [AdminController::class, 'syncPrices'])->name('admin.sources.sync');
    Route::get('/featured', [AdminController::class, 'featured'])->name('admin.featured');
    Route::post('/featured', [AdminController::class, 'storeFeatured']);
});
