<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceAlert extends Model
{
    protected $fillable = [
        'user_id', 'item_id', 'exterior_id', 'variant', 'threshold', 'direction', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'threshold' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
