<?php

declare(strict_types=1);

namespace App\Utils;

use DateTimeImmutable;
use DateTimeZone;

/**
 * Maps an Estonian calendar day to the UTC window the Elering day-ahead API expects.
 * Because UTC boundaries are derived from the local Europe/Tallinn midnight,
 * DST transitions (23h / 25h days) are handled automatically.
 */
final class DayBoundaries {
    public function __construct(
        private readonly DateTimeZone $tz = new DateTimeZone('Europe/Tallinn'),
    ) {
    }

    /**
     * @return array{startUtc: int, endUtc: int} Unix timestamps (seconds).
     */
    public function forDate(string $dateYmd): array {
        $startLocal = new DateTimeImmutable($dateYmd . ' 00:00:00', $this->tz);
        $endLocal = $startLocal->modify('+1 day');

        return [
            'startUtc' => $startLocal->getTimestamp(),
            'endUtc' => $endLocal->getTimestamp(),
        ];
    }

    public function todayYmd(): string {
        return (new DateTimeImmutable('now', $this->tz))->format('Y-m-d');
    }
}
