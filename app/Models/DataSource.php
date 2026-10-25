<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataSource extends Model
{
    protected $fillable = [
        'name', 'slug', 'driver', 'is_enabled', 'last_synced_at', 'last_status', 'last_error', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'last_synced_at' => 'datetime',
        ];
    }
}
