<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;

class SystemTenantSeeder extends Seeder
{
    public function run(): void
    {
        static::seed();
    }

    public static function seed(): Tenant
    {
        $uuid = Tenant::SYSTEM_TENANT_ID;
        $code = Tenant::SYSTEM_TENANT_CODE;

        $tenantByUuid = Tenant::withTrashed()->find($uuid);
        $tenantByCode = Tenant::withTrashed()->where('code', $code)->first();

        if (($tenantByUuid && $tenantByUuid->trashed()) || ($tenantByCode && $tenantByCode->trashed())) {
            throw new \RuntimeException("System Tenant Seeding Halted: Reserved identity is soft-deleted; manual review is required.");
        }

        // Condition 1: Neither UUID nor Code exists -> Create record.
        if (!$tenantByUuid && !$tenantByCode) {
            $tenant = new Tenant();
            $tenant->id = $uuid;
            $tenant->name = 'System Global Tenant';
            $tenant->code = $code;
            $tenant->status = 'ACTIVE';
            $tenant->save();

            return $tenant;
        }

        // Condition 2: UUID and Code point to the exact same record -> No-op.
        if ($tenantByUuid && $tenantByCode && $tenantByUuid->id === $tenantByCode->id) {
            return $tenantByUuid;
        }

        // Condition 3: UUID exists but Code is different -> HALT.
        if ($tenantByUuid && (!$tenantByCode || $tenantByUuid->id !== $tenantByCode->id)) {
            throw new \RuntimeException(
                "System Tenant Seeding Halted: UUID '{$uuid}' exists but with code '{$tenantByUuid->code}' instead of '{$code}'."
            );
        }

        // Condition 4: Code exists but UUID is different -> HALT.
        if ($tenantByCode && (!$tenantByUuid || $tenantByCode->id !== $tenantByUuid->id)) {
            throw new \RuntimeException(
                "System Tenant Seeding Halted: Code '{$code}' exists with UUID '{$tenantByCode->id}' instead of '{$uuid}'."
            );
        }

        throw new \RuntimeException("System Tenant Seeding Halted: Unexpected state detected.");
    }
}
