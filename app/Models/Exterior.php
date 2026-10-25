<?php

namespace App\Models;

use Database\Factories\ExteriorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exterior extends Model
{
    /** @use HasFactory<ExteriorFactory> */
    use HasFactory;

    protected $fillable = ['name', 'slug', 'abbreviation', 'sort_order'];
}
