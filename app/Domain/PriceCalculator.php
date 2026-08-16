<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * Pure calculation logic.
 * Depends on in-memory objects, so it can be unit-tested.
 */
final class PriceCalculator {
    /**
     * Compute day statistics including the cheapest and most expensive
     * consecutive windows of the requested length (in hours).
     */
    public function calculate(PriceCollection $prices, int $windowHours): DayStatistics {
        if ($prices->count() === 0) {
            return new DayStatistics(0.0, 0.0, 0.0, null, null);
        }

        $min = null;
        $max = null;
        $sum = 0.0;

        foreach ($prices as $price) {
            $value = $price->priceEurPerMwh;
            $sum += $value;
            if ($min === null || $value < $min) {
                $min = $value;
            }

            if ($max === null || $value > $max) {
                $max = $value;
            }
        }

        $average = $sum / $prices->count();
        $windowPeriods = $this->resolveWindowPeriods($prices, $windowHours);

        return new DayStatistics(
            minPriceEurPerMwh: $min,
            maxPriceEurPerMwh: $max,
            averagePriceEurPerMwh: $average,
            cheapestWindow: $this->findWindow($prices, $windowPeriods, true),
            mostExpensiveWindow: $this->findWindow($prices, $windowPeriods, false),
        );
    }

    /**
     * Convert a window length expressed in hours into a number of consecutive periods,
     * based on the actual spacing observed in the data.
     * This keeps the code independent of period length (60min or 15min).
     */
    public function resolveWindowPeriods(PriceCollection $prices, int $windowHours): int {
        if ($prices->count() < 2) {
            return max(1, $windowHours);
        }

        $all = $prices->all();
        $periodSeconds = $all[1]->timestampUtc - $all[0]->timestampUtc;
        if ($periodSeconds <= 0) {
            return max(1, $windowHours);
        }

        $periods = (int) round(($windowHours * 3600) / $periodSeconds);

        return max(1, min($periods, $prices->count()));
    }

    /**
     * Sliding window over consecutive periods.
     * Returns the window with either the lowest (cheapest) or highest (most expensive) average price.
     */
    public function findWindow(PriceCollection $prices, int $windowPeriods, bool $cheapest): ?Window {
        $count = $prices->count();
        if ($windowPeriods < 1 || $windowPeriods > $count) {
            return null;
        }

        $all = $prices->all();
        $bestIndex = null;
        $bestAverage = null;

        for ($i = 0; $i + $windowPeriods <= $count; $i++) {
            $sum = 0.0;
            for ($j = $i; $j < $i + $windowPeriods; $j++) {
                $sum += $all[$j]->priceEurPerMwh;
            }

            $average = $sum / $windowPeriods;

            if (
                $bestAverage === null
                || ($cheapest && $average < $bestAverage)
                || (!$cheapest && $average > $bestAverage)
            ) {
                $bestAverage = $average;
                $bestIndex = $i;
            }
        }

        if ($bestIndex === null) {
            return null;
        }

        return new Window($bestIndex, $windowPeriods, $bestAverage);
    }
}
