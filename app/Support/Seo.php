<?php

namespace App\Support;

class Seo
{
    public static function make(
        string $title,
        string $description,
        string $canonical,
        string $robots = 'index,follow',
        ?array $jsonLd = null,
        ?string $image = null,
    ): array {
        return [
            'title' => $title.' · ClutchBase',
            'description' => $description,
            'canonical' => $canonical,
            'robots' => $robots,
            'jsonLd' => $jsonLd,
            'image' => $image,
        ];
    }
}
