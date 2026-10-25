<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    protected $fillable = [
        'user_id', 'plan', 'status', 'provider', 'provider_reference', 'ends_at',
    ];

    protected function casts(): array
    {
        return ['ends_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActivePremium(): bool
    {
        if ($this->plan !== 'premium' || $this->status !== 'active') {
            return false;
        }

        return $this->ends_at === null || $this->ends_at->isFuture();
    }
}
