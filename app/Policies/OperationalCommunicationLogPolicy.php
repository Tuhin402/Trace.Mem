<?php

namespace App\Policies;

use App\Models\User;

class OperationalCommunicationLogPolicy
{
    /**
     * Determine if the given user can send operational communications.
     */
    public function send(User $user): bool
    {
        return in_array($user->platform_role, ['owner', 'admin', 'super_admin']);
    }

    /**
     * Determine if the given user can view operational communications history.
     */
    public function view(User $user): bool
    {
        return in_array($user->platform_role, ['owner', 'admin', 'super_admin', 'support']);
    }
}
