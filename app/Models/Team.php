<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    protected $fillable = ['owner_id', 'name', 'slug'];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(TeamMember::class);
    }

    public function folders(): HasMany
    {
        return $this->hasMany(StrategyFolder::class);
    }

    public function strategies(): HasMany
    {
        return $this->hasMany(Strategy::class);
    }

    public function invites(): HasMany
    {
        return $this->hasMany(TeamInvite::class);
    }
}
