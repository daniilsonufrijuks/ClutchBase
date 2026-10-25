<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SavedFilter extends Model
{
    protected $fillable = ['user_id', 'name', 'query'];

    protected function casts(): array
    {
        return ['query' => 'array'];
    }
}
