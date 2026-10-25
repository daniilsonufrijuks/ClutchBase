<?php

namespace App\Models;

use Database\Factories\ItemCollectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItemCollection extends Model
{
    /** @use HasFactory<ItemCollectionFactory> */
    use HasFactory;

    protected $table = 'collections';

    protected $fillable = [
        'name', 'slug', 'description', 'released_on', 'image_path',
        'attribute_status', 'meta_title', 'meta_description', 'source_id',
    ];

    protected function casts(): array
    {
        return ['released_on' => 'date'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class, 'collection_id');
    }

    public function cases(): HasMany
    {
        return $this->hasMany(WeaponCase::class, 'collection_id');
    }
}
