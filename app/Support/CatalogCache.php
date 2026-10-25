<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

class CatalogCache
{
    public static function remember(string $name, int $seconds, Closure $callback): mixed
    {
        if (app()->environment('testing')) {
            return $callback();
        }

        return Cache::remember(self::key($name), $seconds, $callback);
    }

    public static function version(): int
    {
        return (int) Cache::get('catalog.version', 1);
    }

    public static function bump(): void
    {
        if (! Cache::has('catalog.version')) {
            Cache::forever('catalog.version', 1);
        }

        Cache::increment('catalog.version');
    }

    public static function key(string $name): string
    {
        return 'catalog.'.self::version().'.'.$name;
    }
}
