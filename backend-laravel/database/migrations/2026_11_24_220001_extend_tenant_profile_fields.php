<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const COLUMNS = [
        'short_name', 'level', 'ownership_status', 'village', 'district',
        'city', 'province', 'postal_code', 'website', 'principal_name', 'additional_info',
    ];

    public function up(): void
    {
        if (!Schema::hasTable('tenants')) {
            throw new RuntimeException('Tenant profile fields require the tenants table.');
        }

        $missing = array_values(array_filter(self::COLUMNS, fn (string $column): bool => !Schema::hasColumn('tenants', $column)));
        if ($missing === []) {
            return;
        }

        Schema::table('tenants', function (Blueprint $table) use ($missing): void {
            foreach ($missing as $column) {
                match ($column) {
                    'short_name' => $table->string('short_name', 120)->nullable(),
                    'level' => $table->string('level', 16)->nullable(),
                    'ownership_status' => $table->string('ownership_status', 16)->nullable(),
                    'village', 'district', 'city', 'province' => $table->string($column, 120)->nullable(),
                    'postal_code' => $table->string('postal_code', 12)->nullable(),
                    'website' => $table->string('website', 255)->nullable(),
                    'principal_name' => $table->string('principal_name', 180)->nullable(),
                    'additional_info' => $table->text('additional_info')->nullable(),
                };
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('tenants')) {
            return;
        }

        foreach (self::COLUMNS as $column) {
            if (Schema::hasColumn('tenants', $column) && DB::table('tenants')->whereNotNull($column)->exists()) {
                throw new LogicException("Tenant profile rollback blocked: {$column} contains data.");
            }
        }

        $existing = array_values(array_filter(self::COLUMNS, fn (string $column): bool => Schema::hasColumn('tenants', $column)));
        if ($existing !== []) {
            Schema::table('tenants', fn (Blueprint $table) => $table->dropColumn($existing));
        }
    }
};
