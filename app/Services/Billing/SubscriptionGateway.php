<?php

namespace App\Services\Billing;

use App\Models\User;

interface SubscriptionGateway
{
    public function isConfigured(): bool;

    public function checkoutUrl(User $user, string $plan): ?string;
}
