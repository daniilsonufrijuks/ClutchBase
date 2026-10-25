<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StrategyMarker extends Model
{
    protected $fillable = [
        'strategy_id', 'kind', 'player_slot', 'x', 'y', 'label', 'step_number', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'x' => 'decimal:6',
            'y' => 'decimal:6',
        ];
    }
}
