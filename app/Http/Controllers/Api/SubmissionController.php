<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Submission;
use App\Http\Controllers\Controller;
use App\Services\PriceService;
use App\Services\SubmissionService;
use App\Utils\DayBoundaries;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class SubmissionController extends Controller {
    public function __construct(
        private readonly PriceService $priceService,
        private readonly SubmissionService $submissionService,
        private readonly DayBoundaries $boundaries,
    ) {
    }

    public function store(Request $request): RedirectResponse {
        $date = (string) ($request->query('date') ?? $this->boundaries->todayYmd());
        $window = max(1, min(6, (int) ($request->query('window') ?? 1)));

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
            return redirect()
                ->route('prices', ['date' => $date, 'window' => $window])
                ->with('status', 'Tulemus saadetud edukalt aadressile elarmust98@gmail.com.');
        } catch (\RuntimeException $e) {
            return redirect()
                ->route('prices', ['date' => $date, 'window' => $window])
                ->with('error', 'Saatmine ebaõnnestus: ' . $e->getMessage());
        }
    }
}
