<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseContent extends Model
{
    protected $fillable = [
        'case_id', 'item_id', 'drop_group', 'is_special', 'probability_known', 'probability_bps',
    ];

    protected function casts(): array
    {
        return [
            'is_special' => 'boolean',
            'probability_known' => 'boolean',
        ];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(WeaponCase::class, 'case_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
