<?php

use App\Services\RetireKamadRoleService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('kamad_role_retirement_records')) {
            Schema::create('kamad_role_retirement_records', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->string('record_type', 32);
                $table->string('role_id', 36);
                $table->string('old_role_name')->nullable();
                $table->string('old_role_label')->nullable();
                $table->string('archived_role_name')->nullable();
                $table->string('archived_role_label')->nullable();
                $table->string('user_id', 36)->nullable();
                $table->string('tenant_id', 36)->nullable();
                $table->string('users_role_before')->nullable();
                $table->string('users_role_after')->nullable();
                $table->string('permission_id', 36)->nullable();
                $table->timestamp('migrated_at');
                $table->index(['record_type', 'user_id']);
                $table->index(['role_id', 'record_type']);
            });
        }

        app(RetireKamadRoleService::class)->retire();
    }

    public function down(): void
    {
        if (Schema::hasTable('kamad_role_retirement_records')) {
            app(RetireKamadRoleService::class)->rollback();
            Schema::drop('kamad_role_retirement_records');
        }
    }
};
