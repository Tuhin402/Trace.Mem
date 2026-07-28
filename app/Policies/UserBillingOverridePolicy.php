<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserBillingOverridePolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the given user can grant billing overrides.
     */
    public function grantFoundingOffer(User $user): bool
    {
        // Must be an operations administrator (e.g. owner, admin, super_admin)
        return in_array($user->platform_role, ['owner', 'admin', 'super_admin']);
    }
}
