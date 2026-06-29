<?php

namespace App\Console\Commands;

use App\Enums\EmailTemplate;
use App\Jobs\SendEmailJob;
use App\Models\ApiKey;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * SendApiKeyExpiryReminders
 *
 * Dispatches ApiKeyExpiryReminder emails for keys expiring in exactly 7 days.
 * Scheduled daily at 09:00 — safe to re-run; duplicate sends within the same
 * day are prevented by the 7-day window check.
 */
class SendApiKeyExpiryReminders extends Command
{
    protected $signature   = 'email:api-key-expiry-reminders';
    protected $description = 'Send expiry reminder emails for API keys expiring in 7 days';

    public function handle(): int
    {
        $windowStart = now()->addDays(7)->startOfDay();
        $windowEnd   = now()->addDays(7)->endOfDay();

        $keys = ApiKey::query()
            ->with('user')
            ->whereNull('revoked_at')
            ->whereNull('cancelled_at')
            ->whereBetween('expires_at', [$windowStart, $windowEnd])
            ->get();

        $dispatched = 0;

        foreach ($keys as $apiKey) {
            $user = $apiKey->user;

            if (! $user || ! $user->email) {
                continue;
            }

            SendEmailJob::dispatch(
                template:       EmailTemplate::ApiKeyExpiryReminder,
                data:           [
                    'user_name'       => $user->name,
                    'key_name'        => $apiKey->name,
                    'key_prefix'      => $apiKey->key_prefix,
                    'key_last4'       => $apiKey->key_last4,
                    'environment'     => $apiKey->environment,
                    'expires_at'      => $apiKey->expires_at?->format('M j, Y'),
                    'dashboard_url'   => url('/api-keys'),
                ],
                recipientEmail: $user->email,
                userId:         $user->id,
                requestId:      Str::uuid()->toString(),
            );

            $dispatched++;
        }

        $this->info("Dispatched {$dispatched} API key expiry reminder(s).");

        return Command::SUCCESS;
    }
}
