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
        $sanitizer = app(AuditPayloadSanitizer::class);

        AuditLog::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $user?->tenant_id,
            'user_id' => $user?->id,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_values' => $sanitizer->sanitize($oldValues),
            'new_values' => $sanitizer->sanitize($newValues),
            'ip_address' => $sanitizer->anonymizeIpAddress(Request::ip()),
            'user_agent' => $sanitizer->fingerprintUserAgent(Request::userAgent()),
            'created_at' => now(),
        ]);
    }
}
