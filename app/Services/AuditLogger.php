<?php

namespace App\Services;

use App\Models\AuditLogModel;

/**
 * Append-only audit trail for status changes and security events.
 */
class AuditLogger
{
    public static function log(
        string $action,
        string $entityType,
        ?int $entityId = null,
        ?array $old = null,
        ?array $new = null,
        ?string $description = null,
        ?int $userId = null,
        ?string $role = null,
    ): void {
        $auth    = service('auth');
        $request = service('request');
        $isWeb   = $request instanceof \CodeIgniter\HTTP\IncomingRequest;

        model(AuditLogModel::class)->insert([
            'user_id'     => $userId ?? $auth->id(),
            'user_role'   => $role ?? $auth->role(),
            'action'      => $action,
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'description' => $description !== null ? mb_substr($description, 0, 255) : null,
            'old_values'  => $old !== null ? json_encode($old, JSON_UNESCAPED_UNICODE) : null,
            'new_values'  => $new !== null ? json_encode($new, JSON_UNESCAPED_UNICODE) : null,
            'ip_address'  => $isWeb ? $request->getIPAddress() : null,
            'user_agent'  => $isWeb ? mb_substr((string) $request->getUserAgent(), 0, 255) : 'cli',
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
    }
}
