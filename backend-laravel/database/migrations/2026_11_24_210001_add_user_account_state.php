<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            throw new RuntimeException('User account state requires the users table.');
        }

        if (Schema::hasColumn('users', 'deleted_at') && DB::table('users')->whereNotNull('deleted_at')->exists()) {
            throw new RuntimeException(
                'User account state migration aborted: deleted user rows require explicit review before activation-state migration.'
            );
        }

        $hasActive = Schema::hasColumn('users', 'is_active');
        $hasLastLogin = Schema::hasColumn('users', 'last_login_at');
        if ($hasActive && $hasLastLogin) {
            return;
        }

        Schema::table('users', function (Blueprint $table) use ($hasActive, $hasLastLogin): void {
            if (!$hasActive) {
                $table->boolean('is_active')->default(true);
            }
            if (!$hasLastLogin) {
                $table->timestamp('last_login_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        throw new LogicException('User account activation state is a security invariant and cannot be rolled back automatically.');
    }
};
