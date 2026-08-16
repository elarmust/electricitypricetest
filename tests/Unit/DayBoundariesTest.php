<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Utils\DayBoundaries;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

final class DayBoundariesTest extends TestCase {
    private DayBoundaries $boundaries;
    private DateTimeZone $tz;

    protected function setUp(): void {
        $this->boundaries = new DayBoundaries();
        $this->tz = new DateTimeZone('Europe/Tallinn');
    }

    public function testSummerDayStartsAt2100Utc(): void {
        $range = $this->boundaries->forDate('2026-07-01');
        $local = (new DateTimeImmutable('@' . $range['startUtc']))->setTimezone($this->tz);

        self::assertSame('2026-07-01 00:00:00', $local->format('Y-m-d H:i:s'));
        self::assertSame('2026-06-30 21:00:00', gmdate('Y-m-d H:i:s', $range['startUtc']));
    }

    public function testWinterDayStartsAt2200Utc(): void {
        $range = $this->boundaries->forDate('2026-01-15');
        $local = (new DateTimeImmutable('@' . $range['startUtc']))->setTimezone($this->tz);

        self::assertSame('2026-01-15 00:00:00', $local->format('Y-m-d H:i:s'));
        self::assertSame('2026-01-14 22:00:00', gmdate('Y-m-d H:i:s', $range['startUtc']));
    }

    public function testRangeSpansExactlyOneLocalDay(): void {
        $range = $this->boundaries->forDate('2026-03-29');
        $start = (new DateTimeImmutable('@' . $range['startUtc']))->setTimezone($this->tz);
        $end = (new DateTimeImmutable('@' . $range['endUtc']))->setTimezone($this->tz);

        self::assertSame('2026-03-29 00:00:00', $start->format('Y-m-d H:i:s'));
        self::assertSame('2026-03-30 00:00:00', $end->format('Y-m-d H:i:s'));
    }
}
