<?php

namespace App\Actions\Control\Billing;

use App\DTOs\Control\Communications\CommunicationContext;
use App\Enums\CommunicationTemplate;
use App\Events\Billing\FoundingOfferOverrideGranted;
use App\Models\AdminAuditLog;
use App\Models\User;
use App\Models\UserBillingOverride;
use App\Services\Control\Communications\CommunicationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GrantFoundingOfferOverrideAction
{
    /**
     * Grant a manual Founding Offer override to a user.
     *
     * @param User $admin The administrator granting the override.
     * @param User $targetUser The user receiving the override.
     * @param string $reason The mandatory reason for the override.
     * @param string|null $ip The IP address of the admin.
     * @return UserBillingOverride
     * 
     * @throws ValidationException
     */
    public function execute(User $admin, User $targetUser, string $reason, ?string $ip = null): UserBillingOverride
    {
        // 1. Authorization
        if (! $admin->can('grantFoundingOffer', UserBillingOverride::class)) {
            throw ValidationException::withMessages(['consent' => 'Unauthorized.']);
        }

        // 2. Data Validation
        $reason = trim($reason);
        if (strlen($reason) < 10 || strlen($reason) > 1000) {
            throw ValidationException::withMessages(['reason' => 'Reason must be between 10 and 1000 characters.']);
        }

        // 3. Transaction with Row Locking
        return DB::transaction(function () use ($admin, $targetUser, $reason, $ip) {
            // Lock the user row to prevent race conditions during billing modifications
            $lockedUser = User::where('id', $targetUser->id)->lockForUpdate()->firstOrFail();

            // 4. Idempotency Check
            $existingActive = UserBillingOverride::where('user_id', $lockedUser->id)
                ->where('type', 'founding_offer')
                ->where('is_active', true)
                ->first();

            if ($existingActive) {
                throw ValidationException::withMessages(['consent' => 'This user already has an active Founding Offer override.']);
            }

            // Capture old state for audit
            $oldTrialStatus = $lockedUser->free_trial_status;
            $oldTrialEndsAt = $lockedUser->free_trial_ends_at;

            // 5. State Manipulation (reset normal trial properties)
            $lockedUser->forceFill([
                'free_trial_status' => null,
                'free_trial_ends_at' => null,
                'free_trial_plan_id' => null,
            ])->save();

            // 6. Record Keeping - Create Override
            $override = UserBillingOverride::create([
                'user_id' => $lockedUser->id,
                'type' => 'founding_offer',
                'is_active' => true,
                'reason' => $reason,
                'granted_by' => $admin->id,
            ]);

            // 7. Record Keeping - Audit Log
            AdminAuditLog::create([
                'user_id' => $admin->id,
                'action' => 'billing.override.founding_offer.granted',
                'entity_type' => User::class,
                'entity_id' => $lockedUser->id,
                'ip_address' => $ip,
                'old_values' => [
                    'free_trial_status' => $oldTrialStatus,
                    'free_trial_ends_at' => $oldTrialEndsAt,
                ],
                'new_values' => [
                    'reason' => $reason,
                    'override_id' => $override->id,
                ],
            ]);

            // 8. Cache Invalidation
            Cache::tags(['billing', "user:{$lockedUser->id}"])->flush();

            // 9. Domain Event
            FoundingOfferOverrideGranted::dispatch($lockedUser, $override);

            // 10. Send Email Notification (Only after successful commit)
            DB::afterCommit(function () use ($lockedUser, $admin) {
                $context = new CommunicationContext(
                    recipientUuid: $lockedUser->uuid ?? 'legacy-'.$lockedUser->id,
                    recipientType: 'user',
                    recipientEmail: $lockedUser->email,
                    recipientName: $lockedUser->name,
                    senderId: $admin->id,
                    senderName: $admin->name,
                    template: CommunicationTemplate::CustomMessage,
                    subject: 'Your Founding Offer is Active',
                    body: "Great news! We have manually activated the Founding Offer on your account.\n\nYou now have access to premium features and benefits.\n\nThank you for being part of our journey!"
                );
                app(CommunicationService::class)->dispatch($context);
            });

            return $override;
        });
    }
}
