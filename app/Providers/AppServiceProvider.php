<?php

namespace App\Providers;

use App\Services\Auth\SubscriptionCacheService;
use App\Services\Auth\SubscriptionEntitlementService;
use App\Services\Cache\TraceMemCache;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // TraceMemCache is a singleton — the version counter is fetched from Redis
        // once per request and reused for all key builds in that request.
        $this->app->singleton(TraceMemCache::class);

        // SubscriptionEntitlementService — registered as singleton so the cache
        // service can be setter-injected into the same instance later.
        $this->app->singleton(
            SubscriptionEntitlementService::class,
            fn () => new SubscriptionEntitlementService()
        );

        // SubscriptionCacheService — depends on TraceMemCache + EntitlementService.
        // After construction, setter-inject back into EntitlementService to break
        // the circular dependency without recursion.
        $this->app->singleton(
            SubscriptionCacheService::class,
            function ($app) {
                $cacheService = new SubscriptionCacheService(
                    $app->make(TraceMemCache::class),
                    $app->make(SubscriptionEntitlementService::class),
                );

                // Complete the bidirectional wire: EntitlementService now routes
                // resolveForUser() through cache instead of hitting DB directly.
                $app->make(SubscriptionEntitlementService::class)
                    ->setCacheService($cacheService);

                return $cacheService;
            }
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
