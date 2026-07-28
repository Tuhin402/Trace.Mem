<?php

namespace App\Policies\Control;

use App\Models\SubscriptionPlan;
use App\Models\User;

class CatalogPolicy
{
    public function viewAny(User $admin): bool
    {
        return in_array($admin->platform_role, ['owner', 'admin', 'super_admin'], true);
    }

    public function view(User $admin, SubscriptionPlan $plan): bool
    {
        return $this->viewAny($admin);
    }

    public function create(User $admin): bool
    {
        return $admin->platform_role === 'super_admin';
    }

    public function update(User $admin, SubscriptionPlan $plan): bool
    {
        return $admin->platform_role === 'super_admin';
    }

    public function archive(User $admin, SubscriptionPlan $plan): bool
    {
        return $admin->platform_role === 'super_admin';
    }

    public function restore(User $admin, SubscriptionPlan $plan): bool
    {
        return $admin->platform_role === 'super_admin';
    }

    public function forceDelete(User $admin, SubscriptionPlan $plan): bool
    {
        return $admin->platform_role === 'super_admin' && $plan->canBePhysicallyDeleted();
    }
}
