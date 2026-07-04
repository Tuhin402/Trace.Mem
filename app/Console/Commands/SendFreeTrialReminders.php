<?php

namespace App\Console\Commands;

use App\Enums\EmailTemplate;
use App\Jobs\SendEmailJob;
use App\Models\ApiKey;
use App\Models\Memory;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Billing\FreeTrialAnalyticsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * SendFreeTrialReminders
 *
 * Dispatches free trial reminder emails for users whose trials end in
 * exactly 7, 3, or 1 day(s). Reminder emails include dynamic usage stats
 * (memories stored, API requests made, active API keys) and the next billing
 * amount — all read from the DB, never hardcoded.
 *
 * Idempotency: Uses email_logs to avoid sending the same reminder twice.
 * Scheduled daily at 09:30 via the Laravel scheduler.
 *
 * Production safety:
 *   - Processes users in chunks (no full table load)
 *   - Failures per-user are caught and logged; they do not abort the command
 *   - Never touches free_trial_status — reminder-only, no state mutations
 */
class SendFreeTrialReminders extends Command
{
    protected $signature   = 'email:free-trial-reminders';
    protected $description = 'Send reminder emails for Founding Offer trials ending in 7, 3, or 1 day(s)';

    public function __construct(
        private readonly FreeTrialAnalyticsService $analytics,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $reminderDays = [7, 3, 1];
        $dispatched   = 0;

        foreach ($reminderDays as $daysRemaining) {
            $windowStart = now()->addDays($daysRemaining)->startOfDay();
            $windowEnd   = now()->addDays($daysRemaining)->endOfDay();

            // Chunk through users with active trials ending in this window
            User::query()
                ->where('free_trial_status', 'activated')
                ->whereBetween('free_trial_ends_at', [$windowStart, $windowEnd])
                ->with([])
                ->chunkById(100, function ($users) use ($daysRemaining, &$dispatched) {
                    foreach ($users as $user) {
                        try {
                            $this->processUser($user, $daysRemaining);
                            $dispatched++;
                        } catch (\Throwable $e) {
                            // Per-user failures must not abort the whole batch
                            Log::error("SendFreeTrialReminders: failed for user [{$user->id}]", [
                                'error'          => $e->getMessage(),
                                'days_remaining' => $daysRemaining,
                            ]);
                        }
                    }
                });
        }

        $this->info("Dispatched {$dispatched} free trial reminder(s).");

        return Command::SUCCESS;
    }

    /**
     * Build and dispatch a reminder email for a single user.
     */
    private function processUser(User $user, int $daysRemaining): void
    {
        // ── Idempotency: skip if this exact reminder was already sent today ──
        $logKey = "free_trial_reminder_{$daysRemaining}d_{$user->id}";
        $alreadySent = DB::table('email_logs')
            ->where('user_id', $user->id)
            ->where('template_name', EmailTemplate::FreeTrialReminder->value)
            ->whereDate('created_at', today())
            ->whereJsonContains('metadata->days_remaining', $daysRemaining)
            ->exists();

        if ($alreadySent) {
            Log::info("SendFreeTrialReminders: already sent {$daysRemaining}d reminder to user [{$user->id}] today — skipping");
            return;
        }

        // ── Next billing amount from DB (never hardcoded) ────────────────
        $plan = $user->free_trial_plan_id
            ? SubscriptionPlan::find($user->free_trial_plan_id)
            : SubscriptionPlan::where('slug', 'semantic-starter')->first();

        $nextBillingAmount = '₹' . number_format((float) ($plan?->price_monthly ?? 0), 0);

        // ── Dynamic usage stats ──────────────────────────────────────────
        // Memories stored — scoped by user_id
        $memoriesCount = Memory::where('user_id', $user->id)
            ->whereNull('archived_at')
            ->count();

        // API keys (active/non-revoked)
        $activeApiKeys = $user->apiKeys()->whereNull('revoked_at')->count();

        // API requests made via user's keys
        $keyIds = $user->apiKeys()->pluck('id');
        $apiRequestsCount = $keyIds->isNotEmpty()
            ? \App\Models\ApiUsageLog::whereIn('api_key_id', $keyIds)->count()
            : 0;

        // ── Dispatch reminder email ──────────────────────────────────────
        SendEmailJob::dispatch(
            template:       EmailTemplate::FreeTrialReminder,
            data:           [
                'user_name'             => $user->name,
                'plan_name'             => $plan?->name ?? 'Semantic Starter',
                'days_remaining'        => $daysRemaining,
                'trial_end_date'        => $user->free_trial_ends_at?->format('M j, Y'),
                'next_billing_amount'   => $nextBillingAmount,
                'memories_count'        => $memoriesCount,
                'api_requests_count'    => $apiRequestsCount,
                'active_api_keys_count' => $activeApiKeys,
                'billing_url'           => url('/billing'),
            ],
            recipientEmail: $user->email,
            userId:         $user->id,
            requestId:      Str::uuid()->toString(),
        )->onQueue('emails');

        // Track analytics event
        $this->analytics->track($user, 'trial_reminder_sent', [
            'days_remaining'   => $daysRemaining,
            'memories_count'   => $memoriesCount,
            'api_requests'     => $apiRequestsCount,
            'active_api_keys'  => $activeApiKeys,
        ]);

        Log::info("SendFreeTrialReminders: dispatched {$daysRemaining}d reminder", [
            'user_id'        => $user->id,
            'trial_ends_at'  => $user->free_trial_ends_at?->toDateString(),
            'memories_count' => $memoriesCount,
        ]);
    }
}
