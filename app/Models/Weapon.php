<?php

namespace App\Models;

use Database\Factories\WeaponFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Weapon extends Model
{
    /** @use HasFactory<WeaponFactory> */
    use HasFactory;

    protected $fillable = ['name', 'slug', 'category', 'image_path'];

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }
}
