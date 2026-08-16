<?php

declare(strict_types=1);

namespace App\Infrastructure;

use App\Domain\Price;
use App\Domain\PriceCollection;
use App\Domain\PriceProviderInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Talks to the Elering day-ahead price API and maps the JSON response into
 * domain Price objects for a single region.
 * Uses caching to avoid API spam.
 */
final class EleringPriceProvider implements PriceProviderInterface {
    public function __construct(
        private readonly string $baseUrl,
        private readonly int $cacheTtlSeconds,
    ) {
    }

    public function getPrices(string $region, int $startUtc, int $endUtc): PriceCollection {
        $cacheKey = sprintf('elering.prices.%s.%d.%d', strtolower($region), $startUtc, $endUtc);

        $result = \cache()->remember($cacheKey, $this->cacheTtlSeconds, function () use ($region, $startUtc, $endUtc): PriceCollection {
            $url = sprintf(
                '%s?start=%s&end=%s&fields=%s',
                $this->baseUrl,
                gmdate('Y-m-d\TH:i:s.v\Z', $startUtc),
                gmdate('Y-m-d\TH:i:s.v\Z', $endUtc - 1),
                strtolower($region),
            );

            try {
                $response = Http::acceptJson()->timeout(10)->get($url);
            } catch (ConnectionException $e) {
                throw new RuntimeException('Elering API connection failed: ' . $e->getMessage(), 0, $e);
            }

            if (!$response->successful()) {
                throw new RuntimeException('Elering API request failed with status ' . $response->status());
            }

            $decoded = $response->json();
            if (!is_array($decoded) || ($decoded['success'] ?? false) !== true) {
                throw new RuntimeException('Elering API returned an unsuccessful response.');
            }

            $data = $decoded['data'] ?? [];
            if (!is_array($data)) {
                throw new RuntimeException('Elering API returned a malformed data section.');
            }

            $series = $data[strtolower($region)] ?? [];
            if (!is_array($series)) {
                throw new RuntimeException('No price series present for region ' . $region);
            }

            $prices = [];
            foreach ($series as $point) {
                if (!is_array($point)) {
                    continue;
                }

                $timestamp = $point['timestamp'] ?? null;
                $price = $point['price'] ?? null;
                if (!is_int($timestamp) || !is_numeric($price)) {
                    continue;
                }

                $prices[] = Price::fromSpot($timestamp, (float) $price);
            }

            return new PriceCollection($prices);
        });

        if ($result->count() === 0) {
            \cache()->forget($cacheKey);
        }

        return $result;
    }
}
