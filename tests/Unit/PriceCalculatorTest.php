<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Price;
use App\Domain\PriceCollection;
use App\Utils\PriceCalculator;
use PHPUnit\Framework\TestCase;

final class PriceCalculatorTest extends TestCase {
    private PriceCalculator $calculator;

    protected function setUp(): void {
        $this->calculator = new PriceCalculator();
    }

    /**
     * @param  list<array{0: int, 1: float}>  $points
     */
    private function collection(array $points): PriceCollection {
        return new PriceCollection(array_map(
            static fn (array $p): Price => Price::fromSpot($p[0], $p[1]),
            $points
        ));
    }

    public function testCalculatesMinMaxAndAverage(): void {
        $prices = $this->collection([
            [1000, 10.0],
            [4600, 20.0],
            [8200, 30.0],
        ]);

        $stats = $this->calculator->calculate($prices, 1);

        self::assertSame(10.0, $stats->minPriceEurPerMwh);
        self::assertSame(30.0, $stats->maxPriceEurPerMwh);
        self::assertEqualsWithDelta(20.0, $stats->averagePriceEurPerMwh, 1e-9);
    }

    public function testFindsCheapestWindow(): void {
        $prices = $this->collection([
            [1000, 10.0],
            [4600, 50.0],
            [8200, 20.0],
            [11800, 60.0],
            [15400, 5.0],
        ]);

        $window = $this->calculator->findWindow($prices, 2, true);

        self::assertNotNull($window);
        self::assertSame(0, $window->startIndex);
        self::assertSame(2, $window->length);
        self::assertEqualsWithDelta(30.0, $window->averagePriceEurPerMwh, 1e-9);
    }

    public function testFindsMostExpensiveWindow(): void {
        $prices = $this->collection([
            [1000, 10.0],
            [4600, 50.0],
            [8200, 20.0],
            [11800, 60.0],
            [15400, 5.0],
        ]);

        $window = $this->calculator->findWindow($prices, 2, false);

        self::assertNotNull($window);
        self::assertSame(2, $window->startIndex);
        self::assertEqualsWithDelta(40.0, $window->averagePriceEurPerMwh, 1e-9);
    }

    public function testResolveWindowPeriodsForHourlyData(): void {
        $prices = $this->collection([
            [1000, 10.0],
            [4600, 20.0],
            [8200, 30.0],
        ]);

        self::assertSame(2, $this->calculator->resolveWindowPeriods($prices, 2));
    }

    public function testResolveWindowPeriodsForFifteenMinuteData(): void {
        $points = [];
        for ($i = 0; $i < 10; $i++) {
            $points[] = [1000 + $i * 900, (float) $i];
        }
        $prices = $this->collection($points);

        self::assertSame(8, $this->calculator->resolveWindowPeriods($prices, 2));
    }

    public function testHandlesNegativePrices(): void {
        $prices = $this->collection([
            [1000, -5.0],
            [4600, 10.0],
            [8200, -2.0],
        ]);

        $stats = $this->calculator->calculate($prices, 1);

        self::assertSame(-5.0, $stats->minPriceEurPerMwh);
        self::assertEqualsWithDelta(1.0, $stats->averagePriceEurPerMwh, 1e-9);
    }

    public function testEmptyCollectionReturnsZeros(): void {
        $stats = $this->calculator->calculate(new PriceCollection([]), 1);

        self::assertSame(0.0, $stats->minPriceEurPerMwh);
        self::assertSame(0.0, $stats->maxPriceEurPerMwh);
        self::assertNull($stats->cheapestWindow);
        self::assertNull($stats->mostExpensiveWindow);
    }

    public function testWindowLargerThanCollectionReturnsNull(): void {
        $prices = $this->collection([[1000, 10.0], [4600, 20.0]]);
        self::assertNull($this->calculator->findWindow($prices, 5, true));
    }
}
