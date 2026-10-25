<?php

namespace App\Services\Pricing;

use App\Models\ItemPrice;
use App\Models\PriceHistory;
use App\Support\Money;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PriceRecorder
{
    public function record(
        Model $subject,
        ?int $exteriorId,
        string $variant,
        int $marketplaceId,
        string $amount,
        string $currency,
        int $dataSourceId,
        bool $demo,
        bool $verified,
        ?CarbonInterface $observedAt = null,
    ): PriceHistory {
        $amount = Money::normalize($amount);
        $observedAt ??= now();

        return DB::transaction(function () use ($subject, $exteriorId, $variant, $marketplaceId, $amount, $currency, $dataSourceId, $demo, $verified, $observedAt) {
            $history = PriceHistory::query()->create([
                'priceable_type' => $subject->getMorphClass(),
                'priceable_id' => $subject->getKey(),
                'exterior_id' => $exteriorId,
                'variant' => $variant,
                'marketplace_id' => $marketplaceId,
                'amount' => $amount,
                'currency' => strtoupper($currency),
                'observed_at' => $observedAt,
                'data_source_id' => $dataSourceId,
                'is_demo' => $demo,
                'is_verified' => $verified && ! $demo,
            ]);

            $lookup = [
                'priceable_type' => $subject->getMorphClass(),
                'priceable_id' => $subject->getKey(),
                'exterior_id' => $exteriorId,
                'variant' => $variant,
                'marketplace_id' => $marketplaceId,
            ];

            $latest = ItemPrice::query()->where($lookup)->first();
            $payload = [
                'amount' => $amount,
                'currency' => strtoupper($currency),
                'observed_at' => $observedAt,
                'data_source_id' => $dataSourceId,
                'price_history_id' => $history->id,
                'is_demo' => $demo,
                'is_verified' => $verified && ! $demo,
            ];

            if ($latest) {
                $latest->update($payload);
            } else {
                ItemPrice::query()->create($lookup + $payload);
            }

            return $history;
        });
    }
}
