<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * Complete, render-ready result for one day.
 */
final class DayReport {
    public function __construct(
        public readonly string $dateYmd,
        public readonly string $region,
        public readonly int $windowHours,
        public readonly PriceCollection $prices,
        public readonly DayStatistics $statistics,
    ) {
    }

    public function isEmpty(): bool {
        return $this->prices->count() === 0;
    }
}
