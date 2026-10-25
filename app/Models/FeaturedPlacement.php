<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FeaturedPlacement extends Model
{
    protected $fillable = ['slot', 'position', 'subject_type', 'subject_id'];

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
