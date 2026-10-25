<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemExterior extends Model
{
    protected $fillable = ['item_id', 'exterior_id', 'is_verified'];

    protected function casts(): array
    {
        return ['is_verified' => 'boolean'];
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
