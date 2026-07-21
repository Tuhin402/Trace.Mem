<?php

namespace App\Providers;

use App\Providers\EmailServiceProvider;
use App\Services\Auth\SubscriptionCacheService;
use App\Services\Auth\SubscriptionEntitlementService;
use App\Services\Billing\FreeTrialAnalyticsService;
use App\Services\Billing\FreeTrialEligibilityService;
use App\Services\Cache\TraceMemCache;
use App\Services\Memory\Decision\DecisionTelemetry;
use App\Services\Memory\Decision\MemoryDecisionEngine;
use App\Services\Memory\Decision\MemoryRuleRegistry;
use App\Services\Workspace\WorkspaceAuditService;
use App\Services\Workspace\WorkspaceContextService;
use App\Services\Workspace\WorkspaceService;
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

        // Email system — provider-agnostic binding + event wiring + observer registration.
        // To switch email provider: change the binding inside EmailServiceProvider only.
        $this->app->register(EmailServiceProvider::class);

        // FreeTrialEligibilityService — stateless; can be singleton.
        $this->app->singleton(FreeTrialEligibilityService::class);

        // FreeTrialAnalyticsService — fire-and-forget event tracker; singleton.
        $this->app->singleton(FreeTrialAnalyticsService::class);

        // ── MemoryDecisionEngine infrastructure ───────────────────────────────
        // MemoryRuleRegistry — singleton so config/memory_rules.php is parsed
        // exactly once per request (or once per CLI invocation). Zero DB, zero HTTP.
        $this->app->singleton(MemoryRuleRegistry::class);

        // DecisionTelemetry — singleton (reads enabled flag once, reuses across calls).
        $this->app->singleton(DecisionTelemetry::class);

        // MemoryDecisionEngine — auto-resolved by container; depends on:
        //   MemoryNormalizationService, CodeDetectionService (both stateless),
        //   MemoryRuleRegistry (singleton), DecisionTelemetry (singleton).
        $this->app->singleton(MemoryDecisionEngine::class);

        // ── Workspace infrastructure ──────────────────────────────────────────
        // WorkspaceAuditService — fire-and-forget audit writer; singleton.
        $this->app->singleton(WorkspaceAuditService::class);

        // WorkspaceContextService — single source of truth for workspace resolution.
        // Depends on SubscriptionCacheService (already a singleton above).
        $this->app->singleton(
            WorkspaceContextService::class,
            fn ($app) => new WorkspaceContextService(
                $app->make(SubscriptionCacheService::class),
            )
        );

        // WorkspaceService — workspace CRUD + audit logging.
        // Depends on ApiKeyService (auto-resolved) + WorkspaceAuditService (singleton).
        $this->app->singleton(WorkspaceService::class);
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
