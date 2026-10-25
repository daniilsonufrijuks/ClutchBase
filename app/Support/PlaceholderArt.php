<?php

namespace App\Support;

class PlaceholderArt
{
    public static function writeItem(string $path, string $title, string $subtitle, string $accent = '#d7a35a'): void
    {
        $directory = dirname($path);

        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        file_put_contents($path, self::svg($title, $subtitle, $accent));
    }

    public static function svg(string $title, string $subtitle, string $accent): string
    {
        $title = self::escape($title);
        $subtitle = self::escape($subtitle);

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 480" role="img" aria-label="{$title}">
  <rect width="640" height="480" fill="#1a1e27"/>
  <rect x="36" y="36" width="568" height="408" rx="18" fill="#222733" stroke="{$accent}" stroke-width="3"/>
  <path d="M90 340h460" stroke="#343b4a" stroke-width="2"/>
  <circle cx="320" cy="190" r="64" fill="none" stroke="{$accent}" stroke-width="8"/>
  <path d="M292 190h56M320 162v56" stroke="{$accent}" stroke-width="6" stroke-linecap="round"/>
  <text x="320" y="300" text-anchor="middle" fill="#edf0f5" font-family="Segoe UI, sans-serif" font-size="28">{$title}</text>
  <text x="320" y="378" text-anchor="middle" fill="#b7c0ce" font-family="Segoe UI, sans-serif" font-size="16">{$subtitle}</text>
  <text x="320" y="408" text-anchor="middle" fill="#8b95a7" font-family="Segoe UI, sans-serif" font-size="13">Original placeholder artwork</text>
</svg>
SVG;
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
