<?php

namespace App\Services\Pricing;

class DemoPriceProvider implements PriceProvider
{
    public function key(): string
    {
        return 'demo';
    }

    public function isConfigured(): bool
    {
        return false;
    }

    public function fetch(): array
    {
        return [];
    }
}
