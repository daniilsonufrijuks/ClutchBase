<?php

namespace App\Support;

use App\Models\SlugRedirect;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Slugs
{
    public static function unique(string $modelClass, string $source, ?int $ignoreId = null): string
    {
        $base = Str::slug($source) ?: 'record';
        $slug = $base;
        $suffix = 2;

        while (self::taken($modelClass, $slug, $ignoreId)) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    public static function remember(Model $model, string $type, string $previous): void
    {
        if ($previous === '' || $previous === $model->slug) {
            return;
        }

        SlugRedirect::query()->updateOrCreate(
            ['slug' => $previous],
            ['subject_type' => $type, 'subject_id' => $model->getKey()],
        );
    }

    private static function taken(string $modelClass, string $slug, ?int $ignoreId): bool
    {
        $query = $modelClass::query()->where('slug', $slug);

        if ($ignoreId) {
            $query->whereKeyNot($ignoreId);
        }

        return $query->exists() || SlugRedirect::query()->where('slug', $slug)->exists();
    }
}
