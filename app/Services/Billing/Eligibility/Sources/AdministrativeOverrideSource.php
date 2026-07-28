<?php

namespace App\Services\Billing\Eligibility\Sources;

use App\Models\User;
use App\Models\UserBillingOverride;
use App\Services\Billing\Eligibility\Contracts\EligibilitySource;

class AdministrativeOverrideSource implements EligibilitySource
{
    public function name(): string
    {
        return 'administrative_override';
    }

    public function evaluate(User $user): bool
    {
        return UserBillingOverride::where('user_id', $user->id)
            ->where('type', 'founding_offer')
            ->where('is_active', true)
            ->exists();
    }
}
