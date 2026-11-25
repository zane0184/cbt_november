<?php

namespace Tests\Support;

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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

trait CreatesTeacherExamScope
{
    /**
     * Build the minimum real academic assignment needed for a Guru to access
     * an assessment and its exam participants through TeacherAccess.
     *
     * @return array{academicYear: AcademicYear, semester: Semester, subject: Subject, rombel: Rombel}
     */
    protected function createTeacherExamScope(
        Tenant $tenant,
        User $teacher,
        array $students,
        string $prefix = 'SCORING'
    ): array {
        $suffix = strtoupper(substr(preg_replace('/[^A-Z0-9]/i', '', $prefix) ?: 'TEST', 0, 8));

        $curriculum = Curriculum::create([
            'name' => 'Kurikulum ' . $suffix,
            'code' => 'CUR' . $suffix,
            'is_active' => true,
        ]);
        $phase = Phase::create([
            'curriculum_id' => $curriculum->id,
            'name' => 'Fase Uji ' . $suffix,
            'code' => 'F',
        ]);
        $academicYear = AcademicYear::create([
            'tenant_id' => $tenant->id,
            'name' => '2026/2027 ' . $suffix,
        ]);
        $semester = Semester::create([
            'tenant_id' => $tenant->id,
            'academic_year_id' => $academicYear->id,
            'term' => 'GANJIL',
            'is_active' => true,
        ]);
        $gradeLevel = GradeLevel::create([
            'phase_id' => $phase->id,
            'name' => 'Kelas X ' . $suffix,
            'code' => 'X',
            'level' => 10,
        ]);
        $subject = Subject::create([
            'tenant_id' => $tenant->id,
            'code' => 'SUB' . $suffix,
            'name' => 'Mata Pelajaran ' . $suffix,
            'short_name' => 'MP',
            'status' => 'ACTIVE',
        ]);
        $rombel = Rombel::create([
            'tenant_id' => $tenant->id,
            'academic_year_id' => $academicYear->id,
            'grade_level_id' => $gradeLevel->id,
            'name' => 'Rombel ' . $suffix,
            'status' => 'ACTIVE',
        ]);

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

        foreach ($students as $student) {
            $this->attachStudentToRombel($tenant, $student, $rombel);
        }

        return [
            'academicYear' => $academicYear,
            'semester' => $semester,
            'subject' => $subject,
            'rombel' => $rombel,
        ];
    }

    protected function attachStudentToRombel(
        Tenant $tenant,
        User $student,
        Rombel $rombel
    ): StudentProfile {
        $profile = StudentProfile::create([
            'tenant_id' => $tenant->id,
            'user_id' => $student->id,
            'full_name' => $student->name,
        ]);

        DB::table('rombel_student')->insert([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenant->id,
            'rombel_id' => $rombel->id,
            'student_id' => $profile->id,
        ]);

        return $profile;
    }
}
