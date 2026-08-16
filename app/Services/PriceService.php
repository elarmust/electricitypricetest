<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\DayReport;
use App\Domain\DayStatistics;
use App\Domain\PriceCalculator;
use App\Domain\PriceProviderInterface;
use App\Utils\DayBoundaries;
use RuntimeException;

/**
 * Orchestrates fetching, caching and calculation for a requested day/region,
 * and exposes a uniform DayReport to the HTTP layer.
 */
final class PriceService {
    public function __construct(
        private readonly PriceProviderInterface $provider,
        private readonly PriceCalculator $calculator,
        private readonly DayBoundaries $boundaries,
    ) {
    }

    public function getDayReport(string $dateYmd, int $windowHours): DayReport {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateYmd)) {
            throw new RuntimeException('Invalid date format. Expected YYYY-MM-DD.');
        }

        if ($windowHours < 1 || $windowHours > 6) {
            throw new RuntimeException('Window length must be between 1 and 6 hours.');
        }

        $range = $this->boundaries->forDate($dateYmd);
        $region = (string) config('electricity.region', 'EE');

        $prices = $this->provider->getPrices($region, $range['startUtc'], $range['endUtc']);

        if ($prices->count() === 0) {
            return new DayReport(
                $dateYmd,
                $region,
                $windowHours,
                $prices,
                new DayStatistics(0.0, 0.0, 0.0, null, null),
            );
        }

        $statistics = $this->calculator->calculate($prices, $windowHours);

        return new DayReport(
            $dateYmd,
            $region,
            $windowHours,
            $prices,
            $statistics,
        );
    }
}
