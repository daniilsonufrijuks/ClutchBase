<?php

namespace App\Services\Pricing;

class PriceProviderManager
{
    public function current(): PriceProvider
    {
        return match (config('prices.provider')) {
            'http' => new HttpPriceProvider,
            default => new DemoPriceProvider,
        };
    }
}
