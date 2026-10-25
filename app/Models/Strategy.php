<?php

namespace App\Models;

use Database\Factories\StrategyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Strategy extends Model
{
    /** @use HasFactory<StrategyFactory> */
    use HasFactory;

    protected $fillable = [
        'team_id', 'folder_id', 'map_asset_id', 'created_by', 'updated_by', 'title', 'slug',
        'side', 'objective', 'template_key', 'is_template', 'status', 'visibility', 'revision', 'summary',
    ];

    protected function casts(): array
    {
        return [
            'is_template' => 'boolean',
            'revision' => 'integer',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(StrategyFolder::class, 'folder_id');
    }

    public function map(): BelongsTo
    {
        return $this->belongsTo(MapAsset::class, 'map_asset_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function players(): HasMany
    {
        return $this->hasMany(StrategyPlayer::class)->orderBy('slot');
    }

    public function markers(): HasMany
    {
        return $this->hasMany(StrategyMarker::class)->orderBy('sort_order');
    }

    public function paths(): HasMany
    {
        return $this->hasMany(StrategyPath::class);
    }

    public function utilityActions(): HasMany
    {
        return $this->hasMany(StrategyUtilityAction::class)->orderBy('timing_seconds');
    }

    public function timelineSteps(): HasMany
    {
        return $this->hasMany(StrategyTimelineStep::class)->orderBy('seconds')->orderBy('sort_order');
    }

    public function shares(): HasMany
    {
        return $this->hasMany(StrategyShare::class);
    }

    public function collaborators(): HasMany
    {
        return $this->hasMany(StrategyCollaborator::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(StrategyVersion::class);
    }
}
