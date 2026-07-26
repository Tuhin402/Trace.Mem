<?php

namespace App\Services\Control\Overview;

use App\Models\User;

class UsersSnapshotService extends BaseOverviewService
{
    public function getUsers(): array
    {
        return $this->execute('overview:users_snapshot', CacheTiers::NEAR_REAL_TIME, function () {
            return User::latest()->take(5)->get()->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'status' => 'Active',
                    'role' => $user->platform_role ?? 'User',
                    'time' => $user->created_at->diffForHumans(),
                ];
            })->toArray();
        });
    }
}
