<?php

namespace App\Providers;

use App\Domain\PriceProviderInterface;
use App\Infrastructure\EleringPriceProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider {
    /**
     * Register any application services.
     */
    public function register(): void {
        $this->app->bind(PriceProviderInterface::class, function (): EleringPriceProvider {
            return new EleringPriceProvider(
                (string) config('electricity.elering_api_base_url', 'https://dashboard.elering.ee/api/nps/price'),
                (int) config('electricity.cache_ttl_seconds', 1800),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void {
    }
}
