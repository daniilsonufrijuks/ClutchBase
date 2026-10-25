<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseOpening extends Model
{
    protected $fillable = [
        'user_id', 'case_id', 'item_id', 'exterior_id', 'variant',
        'reference_amount', 'probabilities_known',
    ];

    protected function casts(): array
    {
        return [
            'reference_amount' => 'decimal:2',
            'probabilities_known' => 'boolean',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
