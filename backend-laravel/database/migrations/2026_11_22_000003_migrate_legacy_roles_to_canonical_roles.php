<?php

use App\Services\LegacyRoleMigrationService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('legacy_role_migration_records')) {
            Schema::create('legacy_role_migration_records', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->string('record_type', 32);
                $table->string('role_id', 36);
                $table->string('old_role_name');
                $table->string('old_role_label');
                $table->string('archived_role_name');
                $table->string('archived_role_label');
                $table->string('user_id', 36)->nullable();
                $table->string('tenant_id', 36)->nullable();
                $table->string('canonical_role_id', 36)->nullable();
                $table->string('users_role_before')->nullable();
                $table->string('users_role_after')->nullable();
                $table->boolean('canonical_role_was_assigned')->default(false);
                $table->timestamp('migrated_at');
                $table->index(['record_type', 'user_id']);
                $table->index(['role_id', 'old_role_name']);
            });
        }

        app(LegacyRoleMigrationService::class)->migrate();
    }

    public function down(): void
    {
        if (Schema::hasTable('legacy_role_migration_records')) {
            app(LegacyRoleMigrationService::class)->rollback();
            Schema::drop('legacy_role_migration_records');
        }
    }
};
