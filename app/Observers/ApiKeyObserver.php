<?php

namespace App\Observers;

use App\Enums\EmailTemplate;
use App\Jobs\SendEmailJob;
use App\Models\ApiKey;
use Illuminate\Support\Str;

/**
 * ApiKeyObserver — dispatches transactional emails on ApiKey lifecycle events.
 *
 * This observer keeps ApiKeyService clean — no email coupling in business logic.
 * The observer fires AFTER the database write succeeds, so emails are only
 * sent for keys that were actually persisted.
 */
class ApiKeyObserver
{
    /**
     * Handle the ApiKey "created" event.
     * Fires when a new key is created via ApiKeyService::createForUser().
     */
    public function created(ApiKey $apiKey): void
    {
        // Only notify for non-replacement keys (rotations are handled separately)
        // Detect rotation by checking ApiKeyRotation — if this key appears as
        // replaced_by_api_key_id, it was created during a rotation (handled by updated)
        $user = $apiKey->user;
        if (! $user) {
            return;
        }

        SendEmailJob::dispatch(
            template:       EmailTemplate::ApiKeyCreated,
            data:           [
                'user_name'       => $user->name,
                'key_name'        => $apiKey->name,
                'key_prefix'      => $apiKey->key_prefix,
                'key_last4'       => $apiKey->key_last4,
                'environment'     => $apiKey->environment,
                'created_at'      => now()->format('M j, Y \a\t g:i A T'),
                'dashboard_url'   => url('/api-keys'),
            ],
            recipientEmail: $user->email,
            userId:         $user->id,
            requestId:      Str::uuid()->toString(),
        );
    }
}
