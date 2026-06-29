<?php

namespace App\Console\Commands;

use App\Enums\EmailTemplate;
use App\Jobs\SendEmailJob;
use App\Models\UserSubscription;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * SendPlanExpiryReminders
 *
 * Dispatches PlanExpiryReminder emails for active subscriptions ending in 7 days.
 * Scheduled daily at 09:00 via the Laravel scheduler.
 */
class SendPlanExpiryReminders extends Command
{
    protected $signature   = 'email:plan-expiry-reminders';
    protected $description = 'Send expiry reminder emails for subscriptions ending in 7 days';

    public function handle(): int
    {
        $windowStart = now()->addDays(7)->startOfDay();
        $windowEnd   = now()->addDays(7)->endOfDay();

        $subscriptions = UserSubscription::query()
            ->with(['user', 'subscriptionPlan'])
            ->where('is_active', true)
            ->whereNull('cancelled_at')
            ->whereBetween('ends_at', [$windowStart, $windowEnd])
            ->get();

        $dispatched = 0;

        foreach ($subscriptions as $subscription) {
            $user = $subscription->user;
            $plan = $subscription->subscriptionPlan;

            if (! $user || ! $user->email) {
                continue;
            }

            SendEmailJob::dispatch(
                template:       EmailTemplate::PlanExpiryReminder,
                data:           [
                    'user_name'     => $user->name,
                    'plan_name'     => $plan?->name ?? 'Current Plan',
                    'expires_at'    => $subscription->ends_at?->format('M j, Y'),
                    'billing_cycle' => $subscription->billing_cycle,
                    'billing_url'   => url('/billing'),
                ],
                recipientEmail: $user->email,
                userId:         $user->id,
                requestId:      Str::uuid()->toString(),
            );

            $dispatched++;
        }

        $this->info("Dispatched {$dispatched} plan expiry reminder(s).");

        return Command::SUCCESS;
    }
}
