<?php

namespace App\Services\Accounts;

use App\Models\User;
use Carbon\CarbonInterface;

class Entitlements
{
    public function wishlistLimit(User $user): int
    {
        return $user->hasPremium()
            ? (int) config('clutchbase.premium_wishlists')
            : (int) config('clutchbase.free_wishlists');
    }

    public function canCreateWishlist(User $user): bool
    {
        return $user->wishlists()->count() < $this->wishlistLimit($user);
    }

    public function alertLimit(User $user): int
    {
        return $user->hasPremium()
            ? (int) config('clutchbase.premium_alerts')
            : (int) config('clutchbase.free_alerts');
    }

    public function canCreateAlert(User $user): bool
    {
        return $user->priceAlerts()->count() < $this->alertLimit($user);
    }

    public function historySince(?User $user): ?CarbonInterface
    {
        if ($user?->hasPremium()) {
            return null;
        }

        return now()->subDays((int) config('clutchbase.history_days_free'));
    }
}
