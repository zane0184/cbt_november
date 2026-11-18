<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_leases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUuid('exam_attempt_id')->constrained('exam_attempts')->onDelete('cascade');
            $table->foreignUuid('registered_device_id')->constrained('registered_devices')->onDelete('cascade');
            $table->integer('fence_generation')->default(1);
            $table->string('status')->default('ACTIVE'); // ACTIVE, RELEASED_BY_PROCTOR, REVOKED, EXPIRED
            $table->timestamp('leased_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->foreignUuid('released_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('release_reason')->nullable();
            $table->timestamps();

            $table->unique(['exam_attempt_id', 'fence_generation'], 'uniq_leases_attempt_fence');
            $table->index(['exam_attempt_id', 'fence_generation'], 'idx_leases_attempt_fence');
            $table->index(['tenant_id', 'registered_device_id', 'status'], 'idx_leases_tenant_dev_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_leases');
    }
};
