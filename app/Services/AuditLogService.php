<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogService
{
    /**
     * Record a security audit event.
     */
    public static function log(
        string $action,
        string $eventCategory,
        ?string $resourceType = null,
        ?int $resourceId = null,
        array $metadata = [],
        string $severity = 'info',
        ?int $overrideTenantId = null,
        ?int $overrideUserId = null
    ): AuditLog {
        $user = Auth::user();

        $tenantId = $overrideTenantId ?? ($user ? $user->tenant_id : null);
        $userId = $overrideUserId ?? ($user ? $user->id : null);

        return AuditLog::create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'action' => strtoupper($action),
            'event_category' => $eventCategory,
            'ip_address' => Request::ip() ?? '127.0.0.1',
            'user_agent' => Request::userAgent() ?? 'CLI / Automated System',
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'metadata' => $metadata,
            'severity' => $severity,
        ]);
    }
}
