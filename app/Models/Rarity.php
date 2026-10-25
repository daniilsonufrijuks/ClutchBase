<?php

namespace App\Models;

use Database\Factories\RarityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rarity extends Model
{
    /** @use HasFactory<RarityFactory> */
    use HasFactory;

    protected $fillable = ['name', 'slug', 'color', 'sort_order'];

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }
}
