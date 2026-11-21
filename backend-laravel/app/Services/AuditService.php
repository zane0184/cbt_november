<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

class AuditService
{
    public function log(string $action, string $entityType, ?string $entityId = null, ?array $oldValues = null, ?array $newValues = null): void
    {
        $user = auth()->user();

        AuditLog::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $user?->tenant_id,
            'user_id' => $user?->id,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'created_at' => now(),
        ]);
    }
}
