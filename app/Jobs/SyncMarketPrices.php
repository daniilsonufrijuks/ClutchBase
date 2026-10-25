<?php

namespace App\Jobs;

use App\Models\DataSource;
use App\Models\Exterior;
use App\Models\Item;
use App\Models\Marketplace;
use App\Models\WeaponCase;
use App\Services\Pricing\PriceProviderManager;
use App\Services\Pricing\PriceRecorder;
use App\Support\CatalogCache;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Throwable;

class SyncMarketPrices implements ShouldQueue
{
    use Queueable;

    public function handle(PriceProviderManager $providers, PriceRecorder $recorder): void
    {
        $provider = $providers->current();
        $source = DataSource::query()->firstOrCreate(
            ['slug' => $provider->key()],
            ['name' => strtoupper($provider->key()).' prices', 'driver' => $provider->key(), 'is_enabled' => $provider->isConfigured()],
        );

        if (! $provider->isConfigured()) {
            $source->update([
                'last_synced_at' => now(),
                'last_status' => 'skipped',
                'last_error' => 'No authorized price provider is configured. Demo prices are not refreshed from the network.',
            ]);

            return;
        }

        try {
            $quotes = $provider->fetch();
            $applied = 0;

            foreach ($quotes as $quote) {
                if (! is_array($quote)) {
                    continue;
                }

                $subject = $this->subject($quote);
                $marketplace = Marketplace::query()->where('slug', $quote['marketplace'] ?? '')->first();
                if (! $subject || ! $marketplace || ! isset($quote['amount'])) {
                    continue;
                }

                $exterior = null;
                if (! empty($quote['exterior'])) {
                    $exterior = Exterior::query()->where('slug', $quote['exterior'])->first();
                    if (! $exterior) {
                        continue;
                    }
                }

                $recorder->record(
                    $subject,
                    $exterior?->id,
                    (string) ($quote['variant'] ?? 'standard'),
                    $marketplace->id,
                    (string) $quote['amount'],
                    (string) ($quote['currency'] ?? 'USD'),
                    $source->id,
                    false,
                    (bool) ($quote['verified'] ?? false),
                    isset($quote['observed_at']) ? Carbon::parse($quote['observed_at']) : now(),
                );
                $applied++;
            }

            $source->update([
                'last_synced_at' => now(),
                'last_status' => 'ok',
                'last_error' => null,
                'notes' => 'Applied '.$applied.' quotes.',
            ]);
            CatalogCache::bump();
        } catch (Throwable $exception) {
            $source->update([
                'last_synced_at' => now(),
                'last_status' => 'error',
                'last_error' => $exception->getMessage(),
            ]);
        }
    }

    private function subject(array $quote): Item|WeaponCase|null
    {
        $slug = (string) ($quote['subject_slug'] ?? '');

        return match ($quote['subject_type'] ?? 'item') {
            'case' => WeaponCase::query()->where('slug', $slug)->first(),
            default => Item::query()->where('slug', $slug)->first(),
        };
    }
}
