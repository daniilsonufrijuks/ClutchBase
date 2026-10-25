<?php

namespace App\Models;

use Database\Factories\WeaponCaseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class WeaponCase extends Model
{
    /** @use HasFactory<WeaponCaseFactory> */
    use HasFactory;

    protected $table = 'cases';

    protected $fillable = [
        'name', 'slug', 'description', 'image_path', 'released_on', 'status',
        'category', 'requires_key', 'key_amount', 'key_currency', 'key_is_demo',
        'key_is_verified', 'collection_id', 'attribute_status', 'meta_title',
        'meta_description', 'source_id', 'market_hash_name',
    ];

    protected function casts(): array
    {
        return [
            'released_on' => 'date',
            'key_amount' => 'decimal:2',
            'key_is_demo' => 'boolean',
            'key_is_verified' => 'boolean',
        ];
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(ItemCollection::class, 'collection_id');
    }

    public function contents(): HasMany
    {
        return $this->hasMany(CaseContent::class, 'case_id');
    }

    public function prices(): MorphMany
    {
        return $this->morphMany(ItemPrice::class, 'priceable');
    }

    public function priceHistories(): MorphMany
    {
        return $this->morphMany(PriceHistory::class, 'priceable');
    }

    public function scopeListed(Builder $query): Builder
    {
        return $query->where('status', '!=', 'archived');
    }

    public function imageUrl(): string
    {
        if ($this->image_path) {
            if (str_starts_with($this->image_path, 'http://') || str_starts_with($this->image_path, 'https://')) {
                return $this->image_path;
            }

            return str_starts_with($this->image_path, '/') ? $this->image_path : '/'.$this->image_path;
        }

        return '/media/cases/'.$this->slug.'.svg';
    }
}
