<?php

namespace App\Services\Billing\Eligibility\Sources;

use App\Models\User;
use App\Services\Billing\Eligibility\Contracts\EligibilitySource;

class NormalEligibilitySource implements EligibilitySource
{
    public function name(): string
    {
        return 'normal_eligibility';
    }

    public function evaluate(User $user): bool
    {
        // Freshly loaded check — avoid stale model cache
        if ($user->free_trial_status !== null) {
            return false;
        }

        // Check if user has EVER had ANY subscription (any plan, any status)
        return ! $user->subscriptions()->exists();
    }
}
