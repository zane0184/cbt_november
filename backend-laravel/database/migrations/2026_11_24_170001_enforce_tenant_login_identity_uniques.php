<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $identityIndexes = [
        ['table' => 'teacher_profiles', 'column' => 'nip', 'name' => 'teacher_profiles_tenant_nip_unique'],
        ['table' => 'teacher_profiles', 'column' => 'nik', 'name' => 'teacher_profiles_tenant_nik_unique'],
        ['table' => 'teacher_profiles', 'column' => 'nuptk', 'name' => 'teacher_profiles_tenant_nuptk_unique'],
        ['table' => 'student_profiles', 'column' => 'nisn', 'name' => 'student_profiles_tenant_nisn_unique'],
    ];

    public function up(): void
    {
        // Complete all read-only checks before the first DDL statement.
        foreach ($this->identityIndexes as $identity) {
            $table = $identity['table'];
            $column = $identity['column'];

            if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column) || !Schema::hasColumn($table, 'tenant_id')) {
                throw new RuntimeException("Login identity migration preflight failed: {$table}.{$column} or tenant_id is missing.");
            }

            $blank = DB::table($table)
                ->whereNotNull($column)
                ->whereRaw("TRIM({$column}) = ''")
                ->exists();

            if ($blank) {
                throw new RuntimeException("Login identity migration blocked: blank {$table}.{$column} values must be reconciled to NULL.");
            }

            $nonCanonical = DB::table($table)
                ->whereNotNull($column)
                ->whereRaw("LENGTH({$column}) <> LENGTH(TRIM({$column}))")
                ->exists();

            if ($nonCanonical) {
                throw new RuntimeException("Login identity migration blocked: whitespace-padded {$table}.{$column} values must be reconciled before uniqueness can be enforced.");
            }

            $duplicate = DB::table($table)
                ->select('tenant_id', $column)
                ->whereNotNull($column)
                ->groupBy('tenant_id', $column)
                ->havingRaw('COUNT(*) > 1')
                ->exists();

            if ($duplicate) {
                throw new RuntimeException("Login identity migration blocked: duplicate tenant-scoped {$table}.{$column} values exist. Run a read-only preflight and reconcile records before retrying.");
            }

            $existing = collect(Schema::getIndexes($table))
                ->firstWhere('name', $identity['name']);

            $expectedColumns = ['tenant_id', $column];
            if ($existing && (
                !($existing['unique'] ?? false)
                || ($existing['columns'] ?? []) !== $expectedColumns
            )) {
                throw new RuntimeException("Login identity migration blocked: index {$identity['name']} exists but does not enforce the expected unique tenant/identifier columns.");
            }
        }

        foreach ($this->identityIndexes as $identity) {
            $existing = collect(Schema::getIndexes($identity['table']))
                ->firstWhere('name', $identity['name']);

            if (!$existing) {
                Schema::table($identity['table'], function (Blueprint $table) use ($identity): void {
                    $table->unique(
                        ['tenant_id', $identity['column']],
                        $identity['name']
                    );
                });
            }
        }
    }

    public function down(): void
    {
        throw new LogicException('Rollback blocked: tenant-scoped login identity uniqueness is a security invariant and must not be removed automatically.');
    }
};
