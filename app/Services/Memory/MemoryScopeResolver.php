<?php

namespace App\Services\Memory;

use Illuminate\Http\Request;

class MemoryScopeResolver
{
    public function resolve(Request $request): array
    {
        if ($scope = $request->attributes->get('resolved_scope')) {
            $userId = $request->input('user_id');
            if (empty($userId)) {
                abort(422, 'The user_id field is required in the request payload.');
            }

            return [
                'tenant_id'    => (string) ($scope['tenant_id'] ?? ''),
                'user_id'      => (string) $userId,
                'workspace_id' => isset($scope['workspace_id']) ? (int) $scope['workspace_id'] : null,
            ];
        }

        $user = $request->user();

        if ($user) {
            return [
                'tenant_id'    => (string) $user->tenant_scope_id,
                'user_id'      => (string) $user->id,
                'workspace_id' => $user->current_team_id ? (int) $user->current_team_id : null,
            ];
        }

        $tenantId = $request->input('tenant_id');
        $userId   = $request->input('user_id');

        if (filled($tenantId) && filled($userId)) {
            return [
                'tenant_id'    => (string) $tenantId,
                'user_id'      => (string) $userId,
                'workspace_id' => null,
            ];
        }

        abort(401, 'Unauthenticated request and no scope provided.');
    }
}