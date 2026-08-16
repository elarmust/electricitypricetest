<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PriceService;
use App\Services\ReportPayloadService;
use App\Utils\DayBoundaries;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PriceController extends Controller {
    public function __construct(
        private readonly PriceService $priceService,
        private readonly ReportPayloadService $payloadService,
        private readonly DayBoundaries $boundaries,
    ) {
    }

    public function index(Request $request): JsonResponse {
        $date = (string) ($request->query('date') ?? $this->boundaries->todayYmd());
        $window = max(1, min(6, (int) ($request->query('window') ?? 1)));

        try {
            $report = $this->priceService->getDayReport($date, $window);
        } catch (\RuntimeException $e) {
            $report = new \App\Domain\DayReport(
                $date,
                (string) config('electricity.region', 'EE'),
                $window,
                new \App\Domain\PriceCollection([]),
                new \App\Domain\DayStatistics(0.0, 0.0, 0.0, null, null),
            );
        }

        $feeEurMwh = (float) config('electricity.network_fee_eur_per_mwh', 0.0);
        $marginEurMwh = (float) config('electricity.supplier_margin_eur_per_mwh', 0.0);
        $vatMultiplier = 1.0 + (float) config('electricity.vat_rate', 0.24);

        $payload = $this->payloadService->build($report, $feeEurMwh, $marginEurMwh, $vatMultiplier);

        return response()->json($payload);
    }
}
