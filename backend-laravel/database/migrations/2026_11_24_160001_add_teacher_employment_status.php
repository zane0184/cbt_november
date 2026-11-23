<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('teacher_profiles', 'employment_status')) {
            Schema::table('teacher_profiles', function (Blueprint $table): void {
                // Nullable only for legacy records; new accounts require an explicit status.
                // Existing PNS/PPPK/Honorer status must be classified by the school, never inferred.
                $table->string('employment_status', 16)->nullable()->after('employee_identifier');
                $table->index(['tenant_id', 'employment_status'], 'teacher_profiles_tenant_employment_status_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('teacher_profiles', 'employment_status')) {
            Schema::table('teacher_profiles', function (Blueprint $table): void {
                $table->dropIndex('teacher_profiles_tenant_employment_status_idx');
                $table->dropColumn('employment_status');
            });
        }
    }
};
