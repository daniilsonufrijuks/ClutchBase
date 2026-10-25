<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StrategyTimelineStep extends Model
{
    protected $fillable = ['strategy_id', 'seconds', 'title', 'instructions', 'sort_order'];
}
