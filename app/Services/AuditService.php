<?php

namespace App\Services;

use App\Models\Platform\PlatformAuditLog;
use App\Models\Tenant\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditService
{
    public function logTenant(
        string $action,
        string $resourceType = null,
        int $resourceId = null,
        array $oldValues = null,
        array $newValues = null,
        string $description = null,
    ): void {
        AuditLog::on('tenant')->create([
            'user_id'       => Auth::guard('tenant')->id(),
            'action'        => $action,
            'resource_type' => $resourceType,
            'resource_id'   => $resourceId,
            'old_values'    => $oldValues,
            'new_values'    => $newValues,
            'description'   => $description,
            'ip_address'    => Request::ip(),
            'user_agent'    => Request::userAgent(),
            'created_at'    => now(),
        ]);
    }

    public function logPlatform(
        string $action,
        string $resourceType = null,
        int $resourceId = null,
        int $tenantId = null,
        array $oldValues = null,
        array $newValues = null,
        string $description = null,
    ): void {
        PlatformAuditLog::create([
            'user_id'       => Auth::guard('platform')->id(),
            'action'        => $action,
            'resource_type' => $resourceType,
            'resource_id'   => $resourceId,
            'tenant_id'     => $tenantId,
            'old_values'    => $oldValues,
            'new_values'    => $newValues,
            'description'   => $description,
            'ip_address'    => Request::ip(),
            'user_agent'    => Request::userAgent(),
            'created_at'    => now(),
        ]);
    }
}
