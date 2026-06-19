<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\Billing\ApiUsageAnalyticsService;
use App\Services\Cache\TraceMemCache;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * AggregateUsageStatsJob — queue: 'default'
 *
 * Recomputes analytics for a specific user+period from the DB and stores
 * the result in cache. Dispatched only when data actually changes — never
 * on dashboard load.
 *
 * ShouldBeUnique: if 5 events fire for the same user, only one aggregation
 * job runs. The lock is held for 60 seconds (uniqueFor).
 *
 * Dispatched from:
 *   - ApiKeyController::destroy() (revoke)
 *   - ApiKeyController::rotate()
 *   - ApiKeyController::store() (create)
 *   - BillingController::cancelSubscription()
 *   - ProcessStripeWebhookJob::handle() (after successful subscription update)
 */
class AggregateUsageStatsJob implements ShouldQueue, ShouldBeUnique
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Unique lock expires after 60 seconds if job does not complete.
     * Prevents stale locks from blocking future dispatches.
     */
    public int $uniqueFor = 60;

    public function __construct(
        public readonly int $userId,
        public readonly string $period = 'all_time',
    ) {
        $this->onQueue('default');
    }

    /**
     * The unique key for deduplication — per user + period combination.
     */
    public function uniqueId(): string
    {
        return "analytics:{$this->userId}:{$this->period}";
    }

    public function handle(
        ApiUsageAnalyticsService $analyticsService,
        TraceMemCache $cache,
    ): void {
        $user = User::find($this->userId);

        if (! $user) {
            Log::warning('AggregateUsageStatsJob: user not found', [
                'user_id' => $this->userId,
                'period'  => $this->period,
            ]);
            return;
        }

        // Compute analytics fresh from DB (no cache read — this IS the recompute)
        // Map period to the filters array that forUser() expects
        $filters = $this->period === 'all_time' ? [] : ['period' => $this->period];
        $data = $analyticsService->forUser($user, $filters);

        // Forced write into cache — not remember(), because we want to replace
        // whatever might be stale in cache with the freshly computed value.
        $cache->putAnalytics($user, $this->period, $data);

        Log::info('AggregateUsageStatsJob: analytics recomputed and cached', [
            'user_id' => $this->userId,
            'period'  => $this->period,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('AggregateUsageStatsJob: failed', [
            'user_id' => $this->userId,
            'period'  => $this->period,
            'error'   => $exception->getMessage(),
        ]);
    }
}
