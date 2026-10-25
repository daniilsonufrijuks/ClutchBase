<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StrategyPath extends Model
{
    protected $fillable = ['strategy_id', 'player_slot', 'label', 'points'];

    protected function casts(): array
    {
        return ['points' => 'array'];
    }
}
