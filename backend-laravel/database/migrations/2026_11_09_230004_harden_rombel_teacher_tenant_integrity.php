<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const COMPOSITE_FK = 'rombel_teacher_tenant_fk';
    private const LEGACY_FK = 'rombels_teacher_id_foreign';

    public function up(): void
    {
        $this->assertDriverAndPrerequisites();
        $foreignKeys = Schema::getForeignKeys('rombels');
        $legacy = collect($foreignKeys)->first(fn (array $fk) => ($fk['name'] ?? null) === self::LEGACY_FK && ($fk['columns'] ?? []) === ['teacher_id'] && ($fk['foreign_table'] ?? null) === 'teacher_profiles' && ($fk['foreign_columns'] ?? []) === ['id']);
        $composite = collect($foreignKeys)->first(fn (array $fk) => ($fk['name'] ?? null) === self::COMPOSITE_FK);
        if (! $legacy || $composite) throw new RuntimeException('230004 partial/unexpected FK state; no DDL executed. Inspect rombels teacher_id constraints manually.');

        $orphans = DB::table('rombels as r')->leftJoin('teacher_profiles as tp', 'tp.id', '=', 'r.teacher_id')->whereNotNull('r.teacher_id')->whereNull('tp.id')->count();
        $crossTenant = DB::table('rombels as r')->join('teacher_profiles as tp', 'tp.id', '=', 'r.teacher_id')->whereColumn('r.tenant_id', '<>', 'tp.tenant_id')->count();
        if ($orphans > 0 || $crossTenant > 0) throw new RuntimeException("230004 preflight failed: orphan_teacher={$orphans}, cross_tenant_teacher={$crossTenant}. No rows were changed.");

        Schema::table('rombels', function (Blueprint $table): void {
            $table->dropForeign(self::LEGACY_FK);
            $table->foreign(['teacher_id', 'tenant_id'], self::COMPOSITE_FK)
                ->references(['id', 'tenant_id'])->on('teacher_profiles')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        $this->assertDriverAndPrerequisites();
        $foreignKeys = Schema::getForeignKeys('rombels');
        $composite = collect($foreignKeys)->first(fn (array $fk) => ($fk['name'] ?? null) === self::COMPOSITE_FK && ($fk['columns'] ?? []) === ['teacher_id', 'tenant_id'] && ($fk['foreign_table'] ?? null) === 'teacher_profiles' && ($fk['foreign_columns'] ?? []) === ['id', 'tenant_id']);
        $legacy = collect($foreignKeys)->first(fn (array $fk) => ($fk['name'] ?? null) === self::LEGACY_FK);
        if (! $composite || $legacy) throw new RuntimeException('230004 rollback partial/unexpected FK state; no DDL executed. Inspect rombels teacher_id constraints manually.');

        $orphans = DB::table('rombels as r')->leftJoin('teacher_profiles as tp', 'tp.id', '=', 'r.teacher_id')->whereNotNull('r.teacher_id')->whereNull('tp.id')->count();
        $crossTenant = DB::table('rombels as r')->join('teacher_profiles as tp', 'tp.id', '=', 'r.teacher_id')->whereColumn('r.tenant_id', '<>', 'tp.tenant_id')->count();
        if ($orphans > 0 || $crossTenant > 0) throw new RuntimeException("230004 rollback preflight failed: orphan_teacher={$orphans}, cross_tenant_teacher={$crossTenant}. No DDL executed.");

        Schema::table('rombels', function (Blueprint $table): void {
            $table->dropForeign(self::COMPOSITE_FK);
            $table->foreign('teacher_id', self::LEGACY_FK)->references('id')->on('teacher_profiles')->nullOnDelete();
        });
    }

    private function assertDriverAndPrerequisites(): void
    {
        $driver = DB::connection()->getDriverName();
        if (! in_array($driver, ['mysql', 'sqlite'], true)) throw new RuntimeException('230004 supports only MySQL 8+ and SQLite test connections.');
        foreach (['rombels', 'teacher_profiles'] as $table) if (! Schema::hasTable($table)) throw new RuntimeException("230004 prerequisite table missing: {$table}.");
        foreach ([['rombels', 'tenant_id'], ['rombels', 'teacher_id'], ['teacher_profiles', 'id'], ['teacher_profiles', 'tenant_id']] as [$table, $column]) if (! Schema::hasColumn($table, $column)) throw new RuntimeException("230004 prerequisite column missing: {$table}.{$column}.");
        if (! Schema::hasIndex('teacher_profiles', 'teacher_profiles_id_tenant_unique')) throw new RuntimeException('230004 requires teacher_profiles(id,tenant_id) candidate key from migration 210002.');
        if (DB::connection()->getDriverName() === 'mysql') {
            $column = DB::selectOne("SELECT IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'rombels' AND COLUMN_NAME = 'teacher_id'");
            if (! $column || strtoupper((string) $column->IS_NULLABLE) !== 'YES') throw new RuntimeException('230004 requires nullable rombels.teacher_id to preserve unassigned classes.');
        }
        if ($driver === 'mysql') {
            $engines = DB::select("SELECT TABLE_NAME, ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('rombels','teacher_profiles')");
            if (count($engines) !== 2 || collect($engines)->contains(fn ($row) => strtolower((string) $row->ENGINE) !== 'innodb')) throw new RuntimeException('230004 requires InnoDB on rombels and teacher_profiles.');
        }
    }
};
