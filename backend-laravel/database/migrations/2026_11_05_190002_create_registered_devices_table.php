<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registered_devices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->string('installation_uuid');
            $table->string('device_token')->unique();
            $table->string('device_token_hash');
            $table->string('device_name')->nullable();
            $table->string('platform')->default('ANDROID');
            $table->string('os_version')->nullable();
            $table->string('app_version')->nullable();
            $table->string('device_fingerprint')->nullable();
            $table->string('status')->default('ACTIVE'); // ACTIVE, REVOKED, SUSPENDED
            $table->timestamp('registered_at')->useCurrent();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignUuid('revoked_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'user_id', 'installation_uuid'], 'uniq_tenant_user_install');
            $table->index(['tenant_id', 'user_id', 'status'], 'idx_reg_dev_tenant_user_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registered_devices');
    }
};
