<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicateYearTenant = DB::table('academic_years')
            ->select('tenant_id')
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->groupBy('tenant_id')
            ->havingRaw('COUNT(*) > 1')
            ->value('tenant_id');

        $duplicateSemesterTenant = DB::table('semesters')
            ->select('tenant_id')
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->groupBy('tenant_id')
            ->havingRaw('COUNT(*) > 1')
            ->value('tenant_id');

        if ($duplicateYearTenant || $duplicateSemesterTenant) {
            throw new \RuntimeException(
                'Academic period integrity preflight failed: multiple active years or semesters exist for a tenant. Reconcile explicitly before applying this migration; no period was changed automatically.'
            );
        }

        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            if (!DB::table('information_schema.statistics')
                ->where('table_schema', DB::getDatabaseName())
                ->where('table_name', 'academic_years')
                ->where('index_name', 'academic_years_one_active_per_tenant_unique')
                ->exists()) {
                DB::statement(
                    'CREATE UNIQUE INDEX academic_years_one_active_per_tenant_unique ON academic_years ((CASE WHEN is_active = 1 AND deleted_at IS NULL THEN tenant_id ELSE NULL END))'
                );
            }

            if (!DB::table('information_schema.statistics')
                ->where('table_schema', DB::getDatabaseName())
                ->where('table_name', 'semesters')
                ->where('index_name', 'semesters_one_active_per_tenant_unique')
                ->exists()) {
                DB::statement(
                    'CREATE UNIQUE INDEX semesters_one_active_per_tenant_unique ON semesters ((CASE WHEN is_active = 1 AND deleted_at IS NULL THEN tenant_id ELSE NULL END))'
                );
            }

            return;
        }

        if ($driver === 'sqlite') {
            DB::statement(
                'CREATE UNIQUE INDEX IF NOT EXISTS academic_years_one_active_per_tenant_unique ON academic_years (tenant_id) WHERE is_active = 1 AND deleted_at IS NULL'
            );
            DB::statement(
                'CREATE UNIQUE INDEX IF NOT EXISTS semesters_one_active_per_tenant_unique ON semesters (tenant_id) WHERE is_active = 1 AND deleted_at IS NULL'
            );

            return;
        }

        throw new RuntimeException('Single active academic period constraint is supported only on MySQL 8+ or SQLite test runtime.');
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            if (DB::table('information_schema.statistics')->where('table_schema', DB::getDatabaseName())->where('table_name', 'academic_years')->where('index_name', 'academic_years_one_active_per_tenant_unique')->exists()) {
                DB::statement('DROP INDEX academic_years_one_active_per_tenant_unique ON academic_years');
            }
            if (DB::table('information_schema.statistics')->where('table_schema', DB::getDatabaseName())->where('table_name', 'semesters')->where('index_name', 'semesters_one_active_per_tenant_unique')->exists()) {
                DB::statement('DROP INDEX semesters_one_active_per_tenant_unique ON semesters');
            }

            return;
        }

        if (DB::getDriverName() === 'sqlite') {
            DB::statement('DROP INDEX IF EXISTS academic_years_one_active_per_tenant_unique');
            DB::statement('DROP INDEX IF EXISTS semesters_one_active_per_tenant_unique');
        }
    }
};
