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
            'average' => number_format($stats->averagePriceEurPerMwh, 2),
            'min' => number_format($stats->minPriceEurPerMwh, 2),
            'max' => number_format($stats->maxPriceEurPerMwh, 2),
            'cheapestStart' => $cheapestStart,
            'cheapestAvg' => $stats->cheapestWindow !== null
                ? number_format($stats->cheapestWindow->averagePriceEurPerMwh, 2)
                : 'n/a',
            'sentAt' => $sentAt,
            'phpVersion' => PHP_VERSION,
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
