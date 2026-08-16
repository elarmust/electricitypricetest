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

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $this->errorResponse(
                400,
                'Kuupäeva vorming peab olema YYYY-MM-DD.',
            );
        }

        try {
            $report = $this->priceService->getDayReport($date, $window);
        } catch (\RuntimeException $e) {
            return $this->errorResponse(
                502,
                'Tekkis viga hindade laadimisel! Eleringi teenus ei ole hetkel kättesaadav - proovi mõne aja pärast uuesti.',
            );
        }

        $feeEurMwh = (float) config('electricity.network_fee_eur_per_mwh', 0.0);
        $marginEurMwh = (float) config('electricity.supplier_margin_eur_per_mwh', 0.0);
        $vatMultiplier = 1.0 + (float) config('electricity.vat_rate', 0.24);

        $payload = $this->payloadService->build($report, $feeEurMwh, $marginEurMwh, $vatMultiplier);

        return response()->json($payload);
    }

    /**
     * Build a failure payload that the UI can distinguish from a genuine
     * "no data published yet" response (which keeps its own informative text).
     */
    private function errorResponse(int $status, string $message): JsonResponse {
        return response()->json([
            'error' => true,
            'isEmpty' => true,
            'message' => $message,
            'points' => [],
        ], $status);
    }
}
