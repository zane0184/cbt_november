<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Include soft-deleted profiles in the preflight. Deactivation must not
        // release NIP/NIK/NISN and permit another account to claim the identity.
        $duplicateTeacherNip = DB::table('teacher_profiles')
            ->select('tenant_id', 'nip')
            ->whereNotNull('nip')
            ->groupBy('tenant_id', 'nip')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        $duplicateTeacherNik = DB::table('teacher_profiles')
            ->select('tenant_id', 'nik')
            ->whereNotNull('nik')
            ->groupBy('tenant_id', 'nik')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        $duplicateStudentNisn = DB::table('student_profiles')
            ->select('tenant_id', 'nisn')
            ->whereNotNull('nisn')
            ->groupBy('tenant_id', 'nisn')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($duplicateTeacherNip || $duplicateTeacherNik || $duplicateStudentNisn) {
            throw new RuntimeException(
                'Tenant login identity duplicate detected in teacher_profiles (NIP/NIK) or student_profiles (NISN). Resolve duplicates including soft-deleted profiles before applying unique indexes.'
            );
        }

        if (! Schema::hasIndex('teacher_profiles', 'teacher_profiles_tenant_nip_unique')) {
            Schema::table('teacher_profiles', function (Blueprint $table): void {
                $table->unique(['tenant_id', 'nip'], 'teacher_profiles_tenant_nip_unique');
            });
        }

        if (! Schema::hasIndex('teacher_profiles', 'teacher_profiles_tenant_nik_unique')) {
            Schema::table('teacher_profiles', function (Blueprint $table): void {
                $table->unique(['tenant_id', 'nik'], 'teacher_profiles_tenant_nik_unique');
            });
        }

        if (! Schema::hasIndex('student_profiles', 'student_profiles_tenant_nisn_unique')) {
            Schema::table('student_profiles', function (Blueprint $table): void {
                $table->unique(['tenant_id', 'nisn'], 'student_profiles_tenant_nisn_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::table('student_profiles', function (Blueprint $table): void {
            $table->dropUnique('student_profiles_tenant_nisn_unique');
        });

        Schema::table('teacher_profiles', function (Blueprint $table): void {
            $table->dropUnique('teacher_profiles_tenant_nik_unique');
            $table->dropUnique('teacher_profiles_tenant_nip_unique');
        });
    }
};
