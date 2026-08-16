<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\DayReport;
use App\Domain\Price;
use App\Domain\Window;

/**
 * Builds the API JSON payload (points + statistics + windows) from a DayReport.
 */
final class ReportPayloadService {
    /**
     * @return array<string, mixed>
     */
    public function build(DayReport $report, float $networkFeeEurMwh, float $marginEurMwh, float $vatMultiplier): array {
        $all = $report->prices->all();

        $points = [];
        foreach ($all as $p) {
            $points[$p->timestampUtc] = [
                'realBase' => round($p->realBaseEurPerMwh, 4),
                'adjustedBase' => round($p->adjustedBaseEurPerMwh, 4),
                'adjustedWithVat' => round($p->adjustedWithVatEurPerMwh, 4),
            ];
        }

        return [
            'date' => $report->dateYmd,
            'region' => $report->region,
            'windowHours' => $report->windowHours,
            'vatMultiplier' => $vatMultiplier,
            'networkFeeEurMwh' => $networkFeeEurMwh,
            'marginEurMwh' => $marginEurMwh,
            'average' => round($report->statistics->averagePriceEurPerMwh, 4),
            'min' => round($report->statistics->minPriceEurPerMwh, 4),
            'max' => round($report->statistics->maxPriceEurPerMwh, 4),
            'cheapestWindow' => $this->windowData($report->statistics->cheapestWindow, $all),
            'mostExpensiveWindow' => $this->windowData($report->statistics->mostExpensiveWindow, $all),
            'isEmpty' => $report->isEmpty(),
            'message' => $report->isEmpty()
                ? 'Andmeid pole saadaval. Tulevasi päevi ei pruugi enne kella 14:00 avaldada.'
                : '',
            'points' => $points,
        ];
    }

    /**
     * @param  array<int, Price>  $prices
     * @return array<string, mixed>|null
     */
    private function windowData(?Window $window, array $prices): ?array {
        if ($window === null) {
            return null;
        }

        $periodSeconds = 3600;
        if (count($prices) >= 2 && ($prices[1]->timestampUtc - $prices[0]->timestampUtc) > 0) {
            $periodSeconds = (int) ($prices[1]->timestampUtc - $prices[0]->timestampUtc);
        }

        $start = $prices[$window->startIndex] ?? null;
        $lengthHours = max(1, (int) round($window->length * $periodSeconds / 3600));

        return [
            'startTimestamp' => $start?->timestampUtc,
            'length' => $window->length,
            'lengthHours' => $lengthHours,
            'average' => round($window->averagePriceEurPerMwh, 4),
        ];
    }
}
