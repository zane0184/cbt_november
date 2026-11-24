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
            'old_values' => $this->redactSensitiveValues($oldValues),
            'new_values' => $this->redactSensitiveValues($newValues),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'created_at' => now(),
        ]);
    }
    /**
     * Audit metadata must never retain raw credentials or personal identifiers.
     * Redaction is recursive so nested payloads from callers receive the same protection.
     */
    private function redactSensitiveValues(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        $sensitiveKeyFragments = [
            'password', 'passwd', 'secret', 'token', 'authorization', 'cookie',
            'credential', 'apikey', 'privatekey', 'publickey',
            'email', 'phone', 'mobile', 'telephone', 'contactnumber',
            'nip', 'nik', 'nisn', 'nuptk', 'employeeidentifier',
            'address', 'birthdate', 'birthplace', 'parentname', 'guardian',
        ];

        $redacted = [];
        foreach ($values as $key => $value) {
            if (is_string($key)) {
                $normalizedKey = strtolower((string) preg_replace('/[^a-z0-9]/i', '', $key));
                foreach ($sensitiveKeyFragments as $fragment) {
                    if (str_contains($normalizedKey, $fragment)) {
                        $redacted[$key] = '[REDACTED]';
                        continue 2;
                    }
                }
            }

            $redacted[$key] = is_array($value)
                ? $this->redactSensitiveValues($value)
                : $value;
        }

        return $redacted;
    }
}
