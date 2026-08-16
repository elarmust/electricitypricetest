<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\DayReport;
use App\Domain\DayStatistics;
use App\Domain\PriceProviderInterface;
use App\Utils\DayBoundaries;
use App\Utils\PriceCalculator;

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
        $range = $this->boundaries->forDate($dateYmd);
        $region = (string) config('electricity.region', 'EE');

        $rawPrices = $this->provider->getPrices($region, $range['startUtc'], $range['endUtc']);

        $networkFee = (float) config('electricity.network_fee_eur_per_mwh', 0.0);
        $margin = (float) config('electricity.supplier_margin_eur_per_mwh', 0.0);
        $vatMultiplier = 1.0 + (float) config('electricity.vat_rate', 0.0);
        $prices = $rawPrices->enrichBilling($networkFee, $margin, $vatMultiplier);

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
