<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StrategyVersion extends Model
{
    public $timestamps = false;

    protected $fillable = ['strategy_id', 'revision', 'user_id', 'snapshot', 'created_at'];

    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
