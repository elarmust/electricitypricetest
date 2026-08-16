<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Submission;
use App\Http\Controllers\Controller;
use App\Services\PriceService;
use App\Services\SubmissionService;
use App\Utils\DayBoundaries;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SubmissionController extends Controller {
    public function __construct(
        private readonly PriceService $priceService,
        private readonly SubmissionService $submissionService,
        private readonly DayBoundaries $boundaries,
    ) {
    }

    public function store(Request $request): JsonResponse {
        $dateInput = $request->input('date');
        $date = is_string($dateInput) ? $dateInput : $this->boundaries->todayYmd();

        $windowInput = $request->input('window');
        $window = is_numeric($windowInput) ? max(1, min(6, (int) $windowInput)) : 1;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email'],
            'phone' => ['required', 'string', 'regex:/^[+\d][\d\s\-()]{5,20}$/'],
        ], [
            'name.required' => 'Nimi on kohustuslik.',
            'name.max' => 'Nimi ei tohi olla pikem kui 120 tähemärki.',
            'email.required' => 'E-posti aadress on kohustuslik.',
            'email.email' => 'Sisestage kehtiv e-posti aadress.',
            'phone.required' => 'Telefoninumber on kohustuslik.',
            'phone.regex' => 'Sisestage kehtiv telefoninumber.',
        ]);

        $report = $this->priceService->getDayReport($date, $window);

        $submission = new Submission($validated['name'], $validated['email'], $validated['phone']);

        try {
            $this->submissionService->submit($submission, $report);
        } catch (\RuntimeException $e) {
            return response()->json([
                'error' => 'Saatmine ebaõnnestus: ' . $e->getMessage(),
            ], 422);
        }

        $recipient = (string) config('electricity.recipient_email');

        return response()->json([
            'status' => 'Tulemus saadetud edukalt aadressile ' . $recipient . '.',
        ]);
    }
}
