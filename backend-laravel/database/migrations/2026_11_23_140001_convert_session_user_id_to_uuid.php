<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Existing installations may already have the framework-default BIGINT
     * sessions.user_id column even though users.id is UUID. Convert the column
     * before session revocation queries rely on UUID user identifiers.
     */
    public function up(): void
    {
        if (!Schema::hasTable('sessions') || !Schema::hasColumn('sessions', 'user_id')) {
            return;
        }

        Schema::table('sessions', function (Blueprint $table): void {
            $table->string('user_id', 36)->nullable()->change();
        });
    }

    public function down(): void
    {
        throw new RuntimeException(
            'UUID session ownership cannot be safely downgraded to BIGINT. Restore a verified database backup instead.'
        );
    }
};
