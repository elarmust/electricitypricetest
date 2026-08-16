<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\DayReport;
use App\Domain\Submission;
use App\Mail\ResultSubmission;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

/**
 * Builds the result email from a validated submission + the day's report and dispatches it via Laravel mailer.
 */
final class SubmissionService {
    private readonly DateTimeZone $tz;

    public function __construct() {
        $this->tz = new DateTimeZone('Europe/Tallinn');
    }

    public function submit(Submission $submission, DayReport $report): void {
        if ($report->isEmpty()) {
            throw new RuntimeException('Cannot submit a report with no price data.');
        }

        $stats = $report->statistics;
        $prices = $report->prices->all();

        $vatMultiplier = 1 + (float) config('electricity.vat_rate', 0);
        $base = fn (float $eurMwh): float => $eurMwh;
        $withVat = fn (float $eurMwh): float => $eurMwh * $vatMultiplier;

        $cheapestStart = $stats->cheapestWindow !== null
            ? (new DateTimeImmutable('@' . $prices[$stats->cheapestWindow->startIndex]->timestampUtc))->setTimezone($this->tz)->format('Y-m-d H:i')
            : 'n/a';

        $sentAt = (new DateTimeImmutable('now', $this->tz))->format('Y-m-d H:i:s');

        $data = [
            'name' => $submission->name,
            'email' => $submission->email,
            'phone' => $submission->phone,
            'repo' => (string) config('electricity.github_repo_url', ''),
            'commit' => $this->resolveCommitSha(),
            'date' => $report->dateYmd,
            'region' => $report->region,
            'window' => $report->windowHours,
            'average' => number_format($base($stats->averagePriceEurPerMwh), 2),
            'averageVat' => number_format($withVat($stats->averagePriceEurPerMwh), 2),
            'min' => number_format($base($stats->minPriceEurPerMwh), 2),
            'minVat' => number_format($withVat($stats->minPriceEurPerMwh), 2),
            'max' => number_format($base($stats->maxPriceEurPerMwh), 2),
            'maxVat' => number_format($withVat($stats->maxPriceEurPerMwh), 2),
            'cheapestStart' => $cheapestStart,
            'cheapestAvg' => $stats->cheapestWindow !== null
                ? number_format($base($stats->cheapestWindow->averagePriceEurPerMwh), 2)
                : 'n/a',
            'cheapestAvgVat' => $stats->cheapestWindow !== null
                ? number_format($withVat($stats->cheapestWindow->averagePriceEurPerMwh), 2)
                : 'n/a',
            'sentAt' => $sentAt,
            'phpVersion' => PHP_VERSION,
            'transmissionFee' => number_format((float) config('electricity.network_fee_eur_per_mwh'), 2),
        ];

        $recipient = (string) config('electricity.recipient_email');

        Mail::to($recipient)->send(new ResultSubmission($data));
    }

    private function resolveCommitSha(): string {
        $sha = (string) config('electricity.github_commit_sha', '');
        if ($sha !== '') {
            return $sha;
        }

        $head = base_path('.git/HEAD');
        if (!is_file($head)) {
            return '';
        }

        $ref = trim((string) file_get_contents($head));
        if (!str_starts_with($ref, 'ref:')) {
            return $ref;
        }

        $refFile = base_path('.git/' . trim(substr($ref, 4)));

        return is_file($refFile) ? trim((string) file_get_contents($refFile)) : '';
    }
}
