<?php

namespace App\Services\Billing\Eligibility\Contracts;

use App\Models\User;

interface EligibilitySource
{
    /**
     * Get the unique name of this eligibility source.
     */
    public function name(): string;

    /**
     * Determine if the user is eligible according to this source's rules.
     */
    public function evaluate(User $user): bool;
}
