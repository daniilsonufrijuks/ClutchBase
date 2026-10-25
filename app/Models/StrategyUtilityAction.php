<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StrategyUtilityAction extends Model
{
    protected $fillable = [
        'strategy_id', 'player_slot', 'utility_type', 'throw_x', 'throw_y', 'land_x', 'land_y',
        'timing_seconds', 'step_number', 'notes', 'lineup_reference',
    ];

    protected function casts(): array
    {
        return [
            'throw_x' => 'decimal:6',
            'throw_y' => 'decimal:6',
            'land_x' => 'decimal:6',
            'land_y' => 'decimal:6',
        ];
    }
}
