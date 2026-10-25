<?php

namespace App\Services\Pricing;

use Illuminate\Support\Facades\Http;

class HttpPriceProvider implements PriceProvider
{
    public function key(): string
    {
        return 'http';
    }

    public function isConfigured(): bool
    {
        return filled(config('prices.http.url')) && filled(config('prices.http.token'));
    }

    public function fetch(): array
    {
        $response = Http::timeout((int) config('prices.http.timeout', 10))
            ->withToken((string) config('prices.http.token'))
            ->acceptJson()
            ->get(rtrim((string) config('prices.http.url'), '/').'/quotes');

        $response->throw();

        $quotes = $response->json('quotes', []);

        return is_array($quotes) ? $quotes : [];
    }
}
