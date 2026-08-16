<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * Provides price points for a region within a UTC time window.
 */
interface PriceProviderInterface {
    public function getPrices(string $region, int $startUtc, int $endUtc): PriceCollection;
}
