<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PriceHistory extends Model
{
    protected $fillable = [
        'priceable_type', 'priceable_id', 'exterior_id', 'variant', 'marketplace_id',
        'amount', 'currency', 'observed_at', 'data_source_id', 'is_demo', 'is_verified',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'observed_at' => 'datetime',
            'is_demo' => 'boolean',
            'is_verified' => 'boolean',
        ];
    }

    public function priceable(): MorphTo
    {
        return $this->morphTo();
    }

    public function marketplace(): BelongsTo
    {
        return $this->belongsTo(Marketplace::class);
    }

    public function exterior(): BelongsTo
    {
        return $this->belongsTo(Exterior::class);
    }

    public function dataSource(): BelongsTo
    {
        return $this->belongsTo(DataSource::class);
    }
}
