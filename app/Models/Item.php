<?php

namespace App\Models;

use Database\Factories\ItemFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Item extends Model
{
    /** @use HasFactory<ItemFactory> */
    use HasFactory;

    public const TYPES = ['skin', 'knife', 'glove', 'sticker', 'agent'];

    public const CATALOG = [
        'skin' => 'skins',
        'knife' => 'knives',
        'glove' => 'gloves',
        'sticker' => 'stickers',
        'agent' => 'agents',
    ];

    protected $fillable = [
        'type', 'weapon_id', 'rarity_id', 'collection_id', 'name', 'slug',
        'description', 'image_path', 'min_float', 'max_float', 'has_stattrak',
        'has_souvenir', 'pattern_note', 'released_on', 'update_name', 'team_side',
        'finish_style', 'status', 'attribute_status', 'verified_at', 'meta_title',
        'meta_description', 'source_id', 'market_hash_name',
    ];

    protected function casts(): array
    {
        return [
            'min_float' => 'decimal:6',
            'max_float' => 'decimal:6',
            'released_on' => 'date',
            'verified_at' => 'datetime',
        ];
    }

    public function weapon(): BelongsTo
    {
        return $this->belongsTo(Weapon::class);
    }

    public function rarity(): BelongsTo
    {
        return $this->belongsTo(Rarity::class);
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(ItemCollection::class, 'collection_id');
    }

    public function exteriors(): HasMany
    {
        return $this->hasMany(ItemExterior::class);
    }

    public function caseContents(): HasMany
    {
        return $this->hasMany(CaseContent::class);
    }

    public function prices(): MorphMany
    {
        return $this->morphMany(ItemPrice::class, 'priceable');
    }

    public function priceHistories(): MorphMany
    {
        return $this->morphMany(PriceHistory::class, 'priceable');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function catalogPrefix(): string
    {
        return self::CATALOG[$this->type] ?? 'skins';
    }

    public function canonicalPath(): string
    {
        return '/'.$this->catalogPrefix().'/'.$this->slug;
    }

    public function imageUrl(): string
    {
        if ($this->image_path) {
            if (str_starts_with($this->image_path, 'http://') || str_starts_with($this->image_path, 'https://')) {
                return $this->image_path;
            }

            return str_starts_with($this->image_path, '/') ? $this->image_path : '/'.$this->image_path;
        }

        return '/media/items/'.$this->slug.'.svg';
    }
}
