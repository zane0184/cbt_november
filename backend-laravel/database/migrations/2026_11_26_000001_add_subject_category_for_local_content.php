<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('subjects', 'subject_category')) {
            Schema::table('subjects', function (Blueprint $table): void {
                $table->string('subject_category', 20)->default('WAJIB')->after('short_name');
                $table->index(['tenant_id', 'subject_category', 'status'], 'subjects_category_status_idx');
            });
        }

        DB::table('subjects')
            ->where('code', 'MUATAN_LOKAL')
            ->whereNull('deleted_at')
            ->update(['subject_category' => 'MULOK']);
    }

    public function down(): void
    {
        throw new \\RuntimeException(
            'Subject category migration is intentionally forward-only. Do not drop tenant Mulok classification data; use an approved corrective migration.'
        );
    }
};
