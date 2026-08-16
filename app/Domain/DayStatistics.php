<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * Aggregate statistics for a single day of prices.
 * All monetary values are in EUR/MWh, unless noted.
 */
final class DayStatistics {
    public function __construct(
        public readonly float $minPriceEurPerMwh,
        public readonly float $maxPriceEurPerMwh,
        public readonly float $averagePriceEurPerMwh,
        public readonly ?Window $cheapestWindow,
        public readonly ?Window $mostExpensiveWindow,
    ) {
    }
}
