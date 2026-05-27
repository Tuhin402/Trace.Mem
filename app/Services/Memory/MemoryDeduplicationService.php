<?php

namespace App\Services\Memory;

use App\Models\Memory;

class MemoryDeduplicationService
{
    public function findDuplicate(
        string|int $tenantId,
        string|int $userId,
        string $type,
        string $contentHash
    ): ?Memory {
        return Memory::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->where('type', $type)
            ->where('content_hash', $contentHash)
            ->first();
    }

    public function exists(
        string|int $tenantId,
        string|int $userId,
        string $type,
        string $contentHash
    ): bool {
        return Memory::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->where('type', $type)
            ->where('content_hash', $contentHash)
            ->exists();
    }
}