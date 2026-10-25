<?php

namespace App\Services\Billing;

use App\Models\User;

class ManualSubscriptionGateway implements SubscriptionGateway
{
    public function isConfigured(): bool
    {
        return false;
    }

    public function checkoutUrl(User $user, string $plan): ?string
    {
        return null;
    }
}
