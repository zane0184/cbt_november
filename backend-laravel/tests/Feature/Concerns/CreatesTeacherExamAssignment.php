<?php

namespace Tests\Feature\Concerns;

use App\Models\AcademicYear;
use App\Models\Curriculum;
use App\Models\GradeLevel;
use App\Models\Phase;
use App\Models\Rombel;
use App\Models\Semester;
use App\Models\StudentProfile;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\TeacherSubjectAssignment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Str;

trait CreatesTeacherExamAssignment
{
    protected function createTeacherAssignmentScope(User $teacher, User $student, Tenant $tenant, string $suffix): array
    {
        app(\App\Contexts\TenantContext::class)->setTenantId($tenant->id);
        $suffix = strtoupper(preg_replace('/[^A-Z0-9]/', '', $suffix));

        $academicYear = AcademicYear::create([
            'tenant_id' => $tenant->id,
            'name' => '2026/2027 ' . $suffix,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'ACTIVE',
        ]);
        $semester = Semester::create([
            'tenant_id' => $tenant->id,
            'academic_year_id' => $academicYear->id,
            'term' => 'GANJIL',
            'is_active' => true,
        ]);
        $subject = Subject::create([
            'tenant_id' => $tenant->id,
            'code' => 'MAT' . $suffix,
            'name' => 'Matematika ' . $suffix,
        ]);
        $curriculum = Curriculum::create([
            'tenant_id' => $tenant->id,
            'name' => 'Kurikulum Merdeka ' . $suffix,
            'code' => 'KM' . $suffix,
        ]);
        $phase = Phase::create([
            'tenant_id' => $tenant->id,
            'curriculum_id' => $curriculum->id,
            'name' => 'Fase A',
            'code' => 'A' . $suffix,
        ]);
        $gradeLevel = GradeLevel::create([
            'tenant_id' => $tenant->id,
            'phase_id' => $phase->id,
            'code' => '1' . $suffix,
            'name' => 'Kelas 1',
            'level' => 1,
        ]);
        $rombel = Rombel::create([
            'tenant_id' => $tenant->id,
            'academic_year_id' => $academicYear->id,
            'grade_level_id' => $gradeLevel->id,
            'name' => 'Kelas 1A ' . $suffix,
        ]);

        $studentProfile = $this->attachStudentToRombel($student, $tenant, $rombel, $suffix);
        $teacherProfile = TeacherProfile::create([
            'tenant_id' => $tenant->id,
            'user_id' => $teacher->id,
            'full_name' => $teacher->name,
        ]);
        TeacherSubjectAssignment::create([
            'tenant_id' => $tenant->id,
            'teacher_profile_id' => $teacherProfile->id,
            'subject_id' => $subject->id,
            'rombel_id' => $rombel->id,
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
            'starts_at' => now()->subDay()->toDateString(),
            'status' => 'ACTIVE',
            'created_by' => $teacher->id,
        ]);

        return compact('academicYear', 'semester', 'subject', 'curriculum', 'phase', 'gradeLevel', 'rombel', 'studentProfile', 'teacherProfile');
    }

    protected function attachStudentToRombel(User $student, Tenant $tenant, Rombel $rombel, string $suffix = ''): StudentProfile
    {
        app(\App\Contexts\TenantContext::class)->setTenantId($tenant->id);
        $digits = substr(str_replace('-', '', (string) Str::uuid()), 0, 10);
        $profile = StudentProfile::create([
            'tenant_id' => $tenant->id,
            'user_id' => $student->id,
            'full_name' => $student->name,
            'nis' => 'NIS-' . $suffix . '-' . $digits,
            'nisn' => $digits,
        ]);
        $profile->rombels()->attach($rombel->id, [
            'tenant_id' => $tenant->id,
            'id' => (string) Str::uuid(),
        ]);

        return $profile;
    }
}
