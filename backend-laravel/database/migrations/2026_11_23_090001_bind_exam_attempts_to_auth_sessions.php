<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->string('auth_session_hash', 64)->nullable()->after('device_fingerprint');
            $table->string('auth_session_type', 24)->nullable()->after('auth_session_hash');
            $table->timestamp('auth_session_bound_at')->nullable()->after('auth_session_type');
            $table->index(['tenant_id', 'auth_session_hash'], 'idx_attempt_auth_session');
        });

        Schema::table('device_leases', function (Blueprint $table) {
            $table->foreignUuid('registered_device_id')->nullable()->change();
            $table->string('auth_session_hash', 64)->nullable()->after('registered_device_id');
            $table->index(['tenant_id', 'exam_attempt_id', 'status', 'expires_at'], 'idx_device_lease_runtime');
        });
    }

    public function down(): void
    {
        throw new RuntimeException(
            'Attempt auth-session binding and fencing lease history are security state. This migration is forward-only.'
        );
    }
};
