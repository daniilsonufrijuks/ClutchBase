<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StrategyCollaborator extends Model
{
    protected $fillable = ['strategy_id', 'user_id', 'permission'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
