<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportJob extends Model
{
    protected $fillable = [
        'user_id', 'original_name', 'status', 'processed', 'created_count',
        'updated_count', 'skipped_count', 'errors',
    ];

    protected function casts(): array
    {
        return ['errors' => 'array'];
    }
}
