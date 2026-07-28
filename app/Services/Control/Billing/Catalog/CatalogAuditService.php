<?php

namespace App\Services\Control\Billing\Catalog;

use App\Models\AdminAuditLog;
use App\Models\BillingCatalogAuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/** Writes both the catalog-domain and platform-wide immutable audit trails. */
class CatalogAuditService
{
    public function record(
        User $admin,
        string $action,
        Model $entity,
        ?array $before,
        ?array $after,
        ?string $ipAddress = null,
    ): BillingCatalogAuditLog {
        $request = request();
        $requestId = $request?->header('X-Request-ID');

        $catalogLog = BillingCatalogAuditLog::create([
            'action' => $action,
            'entity_type' => $entity::class,
            'entity_id' => (string) $entity->getKey(),
            'before' => $before,
            'after' => $after,
            'performed_by' => $admin->id,
            'ip_address' => $ipAddress ?? $request?->ip(),
            'request_id' => $requestId,
        ]);

        AdminAuditLog::create([
            'user_id' => $admin->id,
            'action' => $action,
            'entity_type' => $entity::class,
            'entity_id' => (string) $entity->getKey(),
            'old_values' => $before,
            'new_values' => $after,
            'ip_address' => $ipAddress ?? $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'request_id' => $requestId,
        ]);

        return $catalogLog;
    }
}
