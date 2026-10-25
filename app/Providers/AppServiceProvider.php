<?php

namespace App\Providers;

use App\Models\Item;
use App\Models\ItemCollection;
use App\Models\Strategy;
use App\Models\Team;
use App\Models\User;
use App\Models\WeaponCase;
use App\Policies\StrategyPolicy;
use App\Policies\TeamPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        config(['inertia.ssr.enabled' => (bool) config('clutchbase.ssr')]);

        Relation::enforceMorphMap([
            'item' => Item::class,
            'case' => WeaponCase::class,
            'collection' => ItemCollection::class,
        ]);

        Gate::policy(Strategy::class, StrategyPolicy::class);
        Gate::policy(Team::class, TeamPolicy::class);

        Gate::before(function (?User $user) {
            return $user?->isAdmin() ? true : null;
        });

        RateLimiter::for('search', fn (Request $request) => Limit::perMinute(60)->by($request->ip()));
        RateLimiter::for('steam', fn (Request $request) => Limit::perMinute(30)->by($request->ip()));
        RateLimiter::for('auth', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));
        RateLimiter::for('import', fn (Request $request) => Limit::perMinute(5)->by((string) $request->user()?->id));
    }
}
