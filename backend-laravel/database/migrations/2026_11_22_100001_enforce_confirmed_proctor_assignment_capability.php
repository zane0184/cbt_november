<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('exam_proctor_assignments')) {
            return;
        }

        // Legacy ACTIVE rows are not trusted as confirmed. Fail closed until Admin reviews them.
        DB::table('exam_proctor_assignments')
            ->where('status', 'ACTIVE')
            ->update([
                'status' => 'NEEDS_REVIEW',
                'updated_at' => now(),
            ]);

        DB::statement("ALTER TABLE exam_proctor_assignments ALTER COLUMN status SET DEFAULT 'PENDING'");
    }

    public function down(): void
    {
        if (!Schema::hasTable('exam_proctor_assignments')) {
            return;
        }

        $hasCanonicalState = DB::table('exam_proctor_assignments')
            ->whereNotIn('status', ['ACTIVE'])
            ->exists();

        if ($hasCanonicalState) {
            throw new RuntimeException(
                'Rollback ditolak: status assignment kanonis sudah digunakan. Jangan mengembalikan capability status ACTIVE.'
            );
        }

        DB::statement("ALTER TABLE exam_proctor_assignments ALTER COLUMN status SET DEFAULT 'ACTIVE'");
    }
};
