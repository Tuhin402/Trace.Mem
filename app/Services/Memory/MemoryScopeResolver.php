<?php

namespace App\Services\Memory;

use Illuminate\Http\Request;

class MemoryScopeResolver
{
    public function resolve(Request $request): array
    {
        if ($scope = $request->attributes->get('resolved_scope')) {
            return [
                'tenant_id' => (string) ($scope['tenant_id'] ?? ''),
                'user_id' => (string) ($scope['user_id'] ?? ''),
            ];
        }

        $user = $request->user();

        if ($user) {
            return [
                'tenant_id' => (string) $user->tenant_scope_id,
                'user_id' => (string) $user->id,
            ];
        }

        $tenantId = $request->input('tenant_id');
        $userId = $request->input('user_id');

        if (filled($tenantId) && filled($userId)) {
            return [
                'tenant_id' => (string) $tenantId,
                'user_id' => (string) $userId,
            ];
        }

        abort(401, 'Unauthenticated request and no scope provided.');
    }
}