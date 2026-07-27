<?php

namespace App\Policies;

use App\Models\User;

class CommunicationPolicy
{
    /**
     * Determine if the given user can send operational communications.
     */
    public function send(User $user): bool
    {
        // Assuming platform_role 'owner' or 'admin' has this right, 
        // or check for specific permissions.
        return in_array($user->platform_role, ['owner', 'admin']);
    }

    /**
     * Determine if the given user can view operational communications history.
     */
    public function view(User $user): bool
    {
        return in_array($user->platform_role, ['owner', 'admin', 'support']);
    }
}
