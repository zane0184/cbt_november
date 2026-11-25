<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table) {
            if (!Schema::hasColumn('exam_attempts', 'fence_generation')) {
                $table->integer('fence_generation')->default(1)->after('status');
            }
            if (!Schema::hasColumn('exam_attempts', 'current_device_token')) {
                $table->string('current_device_token')->nullable()->after('fence_generation');
            }
            if (!Schema::hasColumn('exam_attempts', 'device_lease_expires_at')) {
                $table->timestamp('device_lease_expires_at')->nullable()->after('current_device_token');
            }
            if (!Schema::hasColumn('exam_attempts', 'last_lease_heartbeat_at')) {
                $table->timestamp('last_lease_heartbeat_at')->nullable()->after('device_lease_expires_at');
            }

            $table->index(['exam_id', 'participant_id', 'status'], 'idx_attempts_exam_part_status');
            $table->index('current_device_token', 'idx_attempts_dev_token');
        });
    }

    public function down(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->dropIndex('idx_attempts_exam_part_status');
            $table->dropIndex('idx_attempts_dev_token');

            if (Schema::hasColumn('exam_attempts', 'last_lease_heartbeat_at')) {
                $table->dropColumn('last_lease_heartbeat_at');
            }
            if (Schema::hasColumn('exam_attempts', 'device_lease_expires_at')) {
                $table->dropColumn('device_lease_expires_at');
            }
            if (Schema::hasColumn('exam_attempts', 'current_device_token')) {
                $table->dropColumn('current_device_token');
            }
            if (Schema::hasColumn('exam_attempts', 'fence_generation')) {
                $table->dropColumn('fence_generation');
            }
        });
    }
};
