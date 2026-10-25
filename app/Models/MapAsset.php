<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MapAsset extends Model
{
    protected $fillable = ['name', 'slug', 'image_path', 'license_note', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function strategies(): HasMany
    {
        return $this->hasMany(Strategy::class);
    }
}
