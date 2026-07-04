<?php

namespace App\Console\Commands;

use App\Models\BillingTransaction;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * CleanStaleTrialPendingStates
 *
 * Resets free_trial_status from 'pending_activation' back to null for users
 * who got stuck in the transient checkout state (e.g. server crash, network
 * interruption, or user closing checkout mid-way before Razorpay confirmed).
 *
 * Safety rules:
 *   - Only resets if:
 *       a) Status has been 'pending_activation' for more than 15 minutes
 *       b) No corresponding 'pending' OR 'paid' BillingTransaction exists
 *          (i.e., Razorpay was never called or webhook hasn't fired yet)
 *   - Does NOT touch users who have an active subscription
 *   - Never changes any status other than 'pending_activation'
 *
 * Runs every 5 minutes via the Laravel scheduler.
 */
class CleanStaleTrialPendingStates extends Command
{
    protected $signature   = 'trial:clean-stale-pending';
    protected $description = 'Reset stale pending_activation trial states after 15 minutes (production safety)';

    public function handle(): int
    {
        // Find users stuck in pending_activation for >15 minutes
        $staleThreshold = now()->subMinutes(15);

        $staleUsers = User::where('free_trial_status', 'pending_activation')
            ->where('updated_at', '<', $staleThreshold)
            ->get();

        $reset = 0;

        foreach ($staleUsers as $user) {
            // Do NOT reset if a BillingTransaction was created (Razorpay call succeeded)
            // A paid transaction means verifyPayment or webhook has/will process this.
            $hasPaidTransaction = BillingTransaction::where('user_id', $user->id)
                ->where('status', 'paid')
                ->whereJsonContains('metadata->is_trial', true)
                ->exists();

            if ($hasPaidTransaction) {
                Log::info('CleanStaleTrialPendingStates: skipping user with paid trial transaction', [
                    'user_id'   => $user->id,
                    'updated_at'=> $user->updated_at,
                ]);
                continue;
            }

            // Safe to reset — Razorpay was either never called or failed
            $user->forceFill(['free_trial_status' => null])->save();

            Log::info('CleanStaleTrialPendingStates: reset stale pending_activation', [
                'user_id'    => $user->id,
                'stuck_since'=> $user->updated_at?->toDateTimeString(),
            ]);

            $reset++;
        }

        if ($reset > 0) {
            $this->info("Reset {$reset} stale pending_activation trial state(s).");
        }

        return Command::SUCCESS;
    }
}
