<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SimulatedInventoryItem extends Model
{
    protected $fillable = [
        'simulated_inventory_id', 'item_id', 'exterior_id', 'variant', 'quantity',
        'reference_amount', 'currency', 'is_demo',
    ];

    protected function casts(): array
    {
        return [
            'reference_amount' => 'decimal:2',
            'is_demo' => 'boolean',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function exterior(): BelongsTo
    {
        return $this->belongsTo(Exterior::class);
    }
}
