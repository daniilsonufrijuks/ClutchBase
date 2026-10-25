<?php

namespace App\Services\Pricing;

interface PriceProvider
{
    public function key(): string;

    public function isConfigured(): bool;

    /**
     * @return list<array<string, mixed>>
     */
    public function fetch(): array;
}
