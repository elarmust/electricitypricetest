<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * A single hourly price point enriched with everything the UI/email needs.
 *
 * - realBaseEurPerMwh:       raw Elering day-ahead spot (no fee/margin/VAT)
 * - adjustedBaseEurPerMwh:   billing base = realBase + network fee + margin (VAT excluded)
 * - adjustedWithVatEurPerMwh: adjustedBase * (1 + vat)
 */
final class Price {
    public function __construct(
        public readonly int $timestampUtc,
        public readonly float $realBaseEurPerMwh,
        public readonly float $adjustedBaseEurPerMwh,
        public readonly float $adjustedWithVatEurPerMwh,
    ) {
    }

    public static function fromSpot(int $timestampUtc, float $spotEurPerMwh): self {
        return new self($timestampUtc, $spotEurPerMwh, $spotEurPerMwh, $spotEurPerMwh);
    }

    public function getRealBaseEurPerKwh(): float {
        return $this->realBaseEurPerMwh / 1000.0;
    }

    public function getAdjustedBaseEurPerKwh(): float {
        return $this->adjustedBaseEurPerMwh / 1000.0;
    }

    public function getAdjustedWithVatEurPerKwh(): float {
        return $this->adjustedWithVatEurPerMwh / 1000.0;
    }
}
