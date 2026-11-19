<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use App\Contexts\TenantContext;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        TenantContext::resetInstance();
        if (app()->bound(TenantContext::class)) {
            app(TenantContext::class)->setTenantId(null);
        }
    }

    protected function bootstrapCbtRolePermissions(): void
    {
        foreach ([
            'ADMIN_MADRASAH' => 'Admin Madrasah',
            'KAMAD' => 'Kepala Madrasah',
            'GURU' => 'Guru',
        ] as $name => $label) {
            \App\Models\Role::firstOrCreate(['name' => $name], ['label' => $label]);
        }

        app(\App\Services\RolePermissionBootstrapper::class)->bootstrap();
    }

    protected function setTenantContext(?string $tenantId): void
    {
        $context = app(TenantContext::class);
        $context->setTenantId($tenantId);
        if ($instance = TenantContext::getInstance()) {
            $instance->setTenantId($tenantId);
        }
    }

    public function actingAs(\Illuminate\Contracts\Auth\Authenticatable $user, $guard = null)
    {
        if (isset($user->tenant_id)) {
            $this->setTenantContext($user->tenant_id);
        }

        return parent::actingAs($user, $guard);
    }


    /**
     * Build a real, tenant-scoped teacher assignment for tests that exercise
     * teacher exam APIs. This mirrors the production authorization contract:
     * subject + academic year + semester + rombel + every participant.
     */
    protected function grantTeacherAccessToExam(\App\Models\User $teacher, \App\Models\Exam $exam): void
    {
        $tenantId = (string) $teacher->tenant_id;
        $this->setTenantContext($tenantId);

        $assessment = $exam->assessment()->firstOrFail();
        $suffix = substr(str_replace('-', '', (string) \Illuminate\Support\Str::uuid()), 0, 10);

        $academicYear = \App\Models\AcademicYear::query()
            ->where('tenant_id', $tenantId)
            ->whereKey($assessment->academic_year_id)
            ->first();

        if (!$academicYear) {
            $academicYear = \App\Models\AcademicYear::create([
                'name' => 'TEST-' . $suffix,
                'is_active' => true,
            ]);
        }

        $semester = \App\Models\Semester::query()
            ->where('tenant_id', $tenantId)
            ->where('academic_year_id', $academicYear->id)
            ->first();

        if (!$semester) {
            $semester = \App\Models\Semester::create([
                'academic_year_id' => $academicYear->id,
                'term' => 'GANJIL',
                'is_active' => true,
            ]);
        }

        $subject = \App\Models\Subject::query()
            ->where('tenant_id', $tenantId)
            ->whereKey($assessment->subject_id)
            ->first();

        if (!$subject) {
            $subject = \App\Models\Subject::create([
                'code' => 'TEST-' . $suffix,
                'name' => 'Mata Pelajaran Uji ' . $suffix,
                'short_name' => 'UJI',
                'status' => 'ACTIVE',
            ]);
        }

        $assessment->forceFill([
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
            'subject_id' => $subject->id,
        ])->save();

        $curriculum = \App\Models\Curriculum::create([
            'name' => 'Kurikulum Uji ' . $suffix,
            'code' => 'TEST-' . $suffix,
            'is_active' => true,
        ]);
        $phase = \App\Models\Phase::create([
            'curriculum_id' => $curriculum->id,
            'name' => 'Fase A',
            'code' => 'A',
        ]);
        $gradeLevel = \App\Models\GradeLevel::create([
            'phase_id' => $phase->id,
            'name' => 'Kelas 1',
            'code' => '1',
            'level' => 1,
        ]);
        $rombel = \App\Models\Rombel::create([
            'academic_year_id' => $academicYear->id,
            'grade_level_id' => $gradeLevel->id,
            'name' => 'Rombel Uji ' . $suffix,
            'status' => 'ACTIVE',
        ]);

        $teacherProfile = \App\Models\TeacherProfile::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $teacher->id)
            ->first();

        if (!$teacherProfile) {
            $teacherProfile = \App\Models\TeacherProfile::create([
                'user_id' => $teacher->id,
                'full_name' => $teacher->name,
            ]);
        }

        $participants = $exam->participants()
            ->where('exam_participants.tenant_id', $tenantId)
            ->get();

        if ($participants->isEmpty()) {
            throw new \RuntimeException('Teacher exam fixture requires at least one same-tenant participant.');
        }

        foreach ($participants as $participant) {
            $student = \App\Models\User::query()
                ->where('tenant_id', $tenantId)
                ->whereKey($participant->user_id)
                ->first();

            if (!$student) {
                throw new \RuntimeException('Teacher exam fixture rejected a cross-tenant or missing participant.');
            }

            $studentProfile = \App\Models\StudentProfile::query()
                ->where('tenant_id', $tenantId)
                ->where('user_id', $student->id)
                ->first();

            if (!$studentProfile) {
                $studentProfile = \App\Models\StudentProfile::create([
                    'user_id' => $student->id,
                    'full_name' => $student->name,
                ]);
            }

            $membershipExists = \Illuminate\Support\Facades\DB::table('rombel_student')
                ->where('tenant_id', $tenantId)
                ->where('rombel_id', $rombel->id)
                ->where('student_id', $studentProfile->id)
                ->exists();

            if (!$membershipExists) {
                \Illuminate\Support\Facades\DB::table('rombel_student')->insert([
                    'id' => (string) \Illuminate\Support\Str::uuid(),
                    'tenant_id' => $tenantId,
                    'rombel_id' => $rombel->id,
                    'student_id' => $studentProfile->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        \App\Models\TeacherSubjectAssignment::create([
            'teacher_profile_id' => $teacherProfile->id,
            'subject_id' => $subject->id,
            'rombel_id' => $rombel->id,
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
            'starts_at' => now()->subDay()->toDateString(),
            'status' => 'ACTIVE',
            'created_by' => $teacher->id,
        ]);

        $this->bootstrapCbtRolePermissions();
        $teacher->unsetRelation('roles');
    }

    protected function tearDown(): void
    {
        if (app()->bound(TenantContext::class)) {
            app(TenantContext::class)->setTenantId(null);
        }
        TenantContext::resetInstance();
        parent::tearDown();
    }
}
