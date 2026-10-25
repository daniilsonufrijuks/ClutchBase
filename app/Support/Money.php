<?php

namespace App\Support;

use InvalidArgumentException;

class Money
{
    public static function normalize(string|int|float $amount): string
    {
        $normalized = trim((string) $amount);

        if (! preg_match('/^\d{1,10}(\.\d{1,2})?$/', $normalized)) {
            throw new InvalidArgumentException('Amount must be a non-negative decimal with up to two places.');
        }

        return bcadd($normalized, '0', 2);
    }

    public static function format(?string $amount, string $currency = 'USD'): ?string
    {
        if ($amount === null || $amount === '') {
            return null;
        }

        return $currency.' '.number_format((float) $amount, 2, '.', ',');
    }
}
