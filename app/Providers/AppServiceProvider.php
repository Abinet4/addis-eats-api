<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Pricing\DeliveryZones;
use App\Domain\Pricing\FreeDeliveryRule;
use App\Domain\Pricing\PricingRule;
use App\Domain\Pricing\PromoRule;
use App\Domain\Pricing\StandardRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Built once from config (never the env helper), so config:cache is safe.
        $this->app->singleton(DeliveryZones::class, fn () => new DeliveryZones(
            config('addiseats.zones'),
            (int) config('addiseats.free_delivery_from'),
        ));

        // The ONLY place in the application that names a concrete pricing rule.
        // Swap to PromoRule with ADDIS_EATS_PRICING_RULE=promo, no code change.
        $this->app->bind(PricingRule::class, fn ($app) => match (config('addiseats.pricing_rule')) {
            'promo' => $app->make(PromoRule::class),
            'free' => $app->make(FreeDeliveryRule::class),
            default => $app->make(StandardRule::class),
        });
    }

    public function boot(): void
    {
        // Outside production, touching a relation that was not eager-loaded throws,
        // so an N+1 query fails a test instead of slowing production down.
        Model::preventLazyLoading(! $this->app->isProduction());

        // Global route constraints: a bad id never reaches the binder (clean 404).
        Route::pattern('dish', '[0-9]+');
        Route::pattern('order', '[0-9]+');
        // Not whereAlphaNumeric: that would reject the hyphen in "yod-abyssinia".
        Route::pattern('restaurant', '[a-z0-9]+(?:-[a-z0-9]+)*');
    }
}
