<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StrategyPlayer extends Model
{
    protected $fillable = [
        'strategy_id', 'slot', 'nickname', 'role', 'start_x', 'start_y',
        'responsibilities', 'contingency',
    ];

    protected function casts(): array
    {
        return [
            'start_x' => 'decimal:6',
            'start_y' => 'decimal:6',
        ];
    }

    public function strategy(): BelongsTo
    {
        return $this->belongsTo(Strategy::class);
    }
}
