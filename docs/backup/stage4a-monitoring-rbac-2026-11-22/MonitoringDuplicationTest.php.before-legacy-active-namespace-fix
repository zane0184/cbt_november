<?php

namespace Tests\Feature;

use App\Contexts\TenantContext;
use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\Curriculum;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamParticipant;
use App\Models\GradeLevel;
use App\Models\Permission;
use App\Models\Phase;
use App\Models\Role;
use App\Models\Rombel;
use App\Models\Semester;
use App\Models\StudentProfile;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\TeacherSubjectAssignment;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ExamProctorAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitoringDuplicationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $admin;
    private User $examAuthor;
    private User $assignedGuru;
    private User $unassignedGuru;
    private User $student;
    private Exam $exam;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create(['name' => 'Tenant Monitoring', 'code' => 'MON1']);
        app(TenantContext::class)->setTenantId($this->tenant->id);

        $adminRole = Role::create(['name' => 'ADMIN_MADRASAH', 'label' => 'Admin Madrasah']);
        $guruRole = Role::create(['name' => 'GURU', 'label' => 'Guru']);
        $siswaRole = Role::create(['name' => 'SISWA', 'label' => 'Siswa']);

        $monitorPermission = Permission::create(['name' => 'exam.monitor', 'label' => 'Monitoring ujian']);
        $assignPermission = Permission::create(['name' => 'exam.proctor.assign', 'label' => 'Menugaskan Proktor sementara']);
        $guruRole->permissions()->attach($monitorPermission->id);
        $adminRole->permissions()->attach($assignPermission->id);

        $this->admin = User::create(['tenant_id' => $this->tenant->id, 'name' => 'Admin', 'email' => 'admin-monitor@example.test', 'password' => bcrypt('password')]);
        $this->admin->roles()->attach($adminRole->id);

        $this->examAuthor = $this->createGuru($guruRole, 'author', 'Guru Pembuat Ujian');
        $this->assignedGuru = $this->createGuru($guruRole, 'proctor', 'Guru Proktor Sementara');
        $this->unassignedGuru = $this->createGuru($guruRole, 'unassigned', 'Guru Tanpa Assignment');

        $this->student = User::create(['tenant_id' => $this->tenant->id, 'name' => 'Siswa', 'email' => 'siswa-monitor@example.test', 'password' => bcrypt('password')]);
        $this->student->roles()->attach($siswaRole->id);

        $year = AcademicYear::create(['tenant_id' => $this->tenant->id, 'name' => '2026/2027', 'is_active' => true]);
        $semester = Semester::create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $year->id,
            'term' => 'GANJIL',
            'is_active' => true,
        ]);
        $subject = Subject::create(['tenant_id' => $this->tenant->id, 'code' => 'MTK-MON', 'name' => 'Matematika', 'status' => 'ACTIVE']);
        $curriculum = Curriculum::create(['tenant_id' => $this->tenant->id, 'name' => 'Kurikulum Merdeka', 'code' => 'KM-MON', 'status' => 'ACTIVE', 'is_active' => true]);
        $phase = Phase::create(['tenant_id' => $this->tenant->id, 'curriculum_id' => $curriculum->id, 'name' => 'Fase A', 'code' => 'A-MON', 'status' => 'ACTIVE']);
        $grade = GradeLevel::create(['tenant_id' => $this->tenant->id, 'phase_id' => $phase->id, 'name' => 'Kelas 1', 'code' => '1-MON', 'level' => 1, 'status' => 'ACTIVE']);
        $rombel = Rombel::create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $year->id,
            'grade_level_id' => $grade->id,
            'name' => '1A-MON',
            'status' => 'ACTIVE',
        ]);

        $studentProfile = StudentProfile::create(['tenant_id' => $this->tenant->id, 'user_id' => $this->student->id, 'full_name' => 'Siswa Monitoring']);
        $rombel->students()->attach($studentProfile->id);

        $teacherProfile = $this->examAuthor->teacherProfile;
        TeacherSubjectAssignment::create([
            'tenant_id' => $this->tenant->id,
            'teacher_profile_id' => $teacherProfile->id,
            'subject_id' => $subject->id,
            'rombel_id' => $rombel->id,
            'academic_year_id' => $year->id,
            'semester_id' => $semester->id,
            'status' => 'ACTIVE',
            'created_by' => $this->examAuthor->id,
        ]);

        $assessment = Assessment::create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $year->id,
            'semester_id' => $semester->id,
            'subject_id' => $subject->id,
            'title' => 'Asesmen Monitoring',
            'assessment_type' => 'DAILY',
            'created_by' => $this->examAuthor->id,
        ]);

        $this->exam = Exam::create([
            'tenant_id' => $this->tenant->id,
            'assessment_id' => $assessment->id,
            'title' => 'Ujian Monitoring',
            'duration_seconds' => 3600,
            'start_at' => now()->subMinutes(10),
            'end_at' => now()->addHour(),
            'status' => 'PUBLISHED',
            'created_by' => $this->examAuthor->id,
            'locked_at' => now(),
        ]);
        $this->exam->participants()->create(['tenant_id' => $this->tenant->id, 'user_id' => $this->student->id]);
    }

    private function createGuru(Role $guruRole, string $suffix, string $name): User
    {
        $guru = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => $name,
            'email' => $suffix.'-monitor@example.test',
            'password' => bcrypt('password'),
        ]);
        $guru->roles()->attach($guruRole->id);
        TeacherProfile::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $guru->id,
            'full_name' => $name,
        ]);

        return $guru->fresh();
    }

    private function createPendingTemporaryProctorAssignment(): \App\Models\ExamProctorAssignment
    {
        $this->actingAs($this->admin);

        return app(ExamProctorAssignmentService::class)->assign(
            $this->admin,
            $this->exam,
            $this->assignedGuru->id,
            now()->subMinute(),
            now()->addMinutes(30),
            'Penugasan sementara untuk pengawasan ujian'
        );
    }

    private function assignTemporaryProctor(): void
    {
        $assignment = $this->createPendingTemporaryProctorAssignment();
        app(ExamProctorAssignmentService::class)->confirm($this->admin, $this->exam, $assignment);
    }

    public function test_exam_author_can_monitor_and_participant_rows_are_not_duplicated_by_attempts(): void
    {
        $participant = ExamParticipant::query()
            ->where('tenant_id', $this->tenant->id)
            ->where('exam_id', $this->exam->id)
            ->where('user_id', $this->student->id)
            ->firstOrFail();

        $firstAttempt = ExamAttempt::create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'participant_id' => $participant->id,
            'student_id' => $this->student->id,
            'attempt_number' => 1,
            'status' => 'SUBMITTED',
        ]);
        $firstAttempt->created_at = now()->subHour();
        $firstAttempt->save();

        $latestAttempt = ExamAttempt::create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'participant_id' => $participant->id,
            'student_id' => $this->student->id,
            'attempt_number' => 2,
            'status' => 'IN_PROGRESS',
        ]);
        $latestAttempt->created_at = now();
        $latestAttempt->save();

        $this->actingAs($this->examAuthor, 'sanctum');
        $response = $this->getJson("/api/v1/exams/{$this->exam->id}/monitoring/participants");

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertSame('IN_PROGRESS', $response->json('data.0.attempt.status'));
    }

    public function test_admin_cannot_monitor_even_after_admin_assigns_a_guru_as_temporary_proctor(): void
    {
        $this->assignTemporaryProctor();

        // Simulate stale legacy Guru grants in memory only; no permission pivot is mutated.
        $adminRole = $this->admin->roles()->firstOrFail();
        $staleGuruRole = new Role(['name' => 'GURU', 'label' => 'Legacy Guru']);
        $staleGuruRole->setRelation('permissions', collect([
            new Permission(['name' => 'exam.monitor', 'label' => 'Stale monitoring permission']),
        ]));
        $adminRole->setRelation('permissions', collect([
            new Permission(['name' => 'exam.monitor', 'label' => 'Stale monitoring permission']),
            new Permission(['name' => 'exam.proctor.assign', 'label' => 'Temporary assignment permission']),
        ]));
        $this->admin->setRelation('roles', collect([$adminRole, $staleGuruRole]));

        $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/v1/exams/{$this->exam->id}/monitoring/participants")
            ->assertForbidden();

        $this->actingAs($this->admin)
            ->get("/exams/{$this->exam->id}/monitoring")
            ->assertForbidden();
    }

    public function test_pending_assignment_does_not_grant_monitoring_until_admin_confirms(): void
    {
        $assignment = $this->createPendingTemporaryProctorAssignment();
        $this->assertSame('PENDING', $assignment->fresh()->status);

        $this->actingAs($this->assignedGuru, 'sanctum')
            ->getJson("/api/v1/exams/{$this->exam->id}/monitoring")
            ->assertForbidden();

        $this->actingAs($this->admin);
        app(ExamProctorAssignmentService::class)->confirm($this->admin, $this->exam, $assignment);
        $this->assertSame('CONFIRMED', $assignment->fresh()->status);

        $this->actingAs($this->assignedGuru, 'sanctum')
            ->getJson("/api/v1/exams/{$this->exam->id}/monitoring")->assertOk();
    }

    public function test_legacy_active_status_never_grants_monitoring_capability(): void
    {
        $assignment = $this->createPendingTemporaryProctorAssignment();
        \\Illuminate\\Support\\Facades\\DB::table('exam_proctor_assignments')
            ->where('id', $assignment->id)
            ->update(['status' => 'ACTIVE']);

        $this->actingAs($this->assignedGuru, 'sanctum')
            ->getJson("/api/v1/exams/{$this->exam->id}/monitoring")
            ->assertForbidden();
    }

    public function test_assigned_guru_can_monitor_only_the_exam_with_a_confirmed_assignment(): void
    {
        $this->assignTemporaryProctor();

        $this->actingAs($this->assignedGuru, 'sanctum')
            ->getJson("/api/v1/exams/{$this->exam->id}/monitoring")
            ->assertOk();

        $otherExam = Exam::create([
            'tenant_id' => $this->tenant->id,
            'assessment_id' => $this->exam->assessment_id,
            'title' => 'Ujian Lain Tanpa Assignment',
            'duration_seconds' => 3600,
            'start_at' => now()->subMinutes(10),
            'end_at' => now()->addHour(),
            'status' => 'PUBLISHED',
            'created_by' => $this->examAuthor->id,
            'locked_at' => now(),
        ]);

        $this->getJson("/api/v1/exams/{$otherExam->id}/monitoring")
            ->assertForbidden();
    }

    public function test_guru_without_exam_authority_or_active_proctor_assignment_cannot_monitor(): void
    {
        $this->actingAs($this->unassignedGuru, 'sanctum')
            ->getJson("/api/v1/exams/{$this->exam->id}/monitoring")
            ->assertForbidden();
    }

    public function test_revoked_or_expired_temporary_assignment_removes_monitoring_access(): void
    {
        $this->assignTemporaryProctor();
        $assignment = \App\Models\ExamProctorAssignment::query()->firstOrFail();

        app(ExamProctorAssignmentService::class)->revoke(
            $this->admin,
            $this->exam,
            $assignment,
            'Penugasan selesai lebih awal'
        );

        $this->actingAs($this->assignedGuru, 'sanctum')
            ->getJson("/api/v1/exams/{$this->exam->id}/monitoring")
            ->assertForbidden();

        $this->assignTemporaryProctor();
        \App\Models\ExamProctorAssignment::query()->whereKey($assignment->id)
            ->update(['status' => 'CONFIRMED', 'revoked_at' => null, 'revoked_by' => null, 'revoke_reason' => null, 'expires_at' => now()->subSecond()]);

        $this->actingAs($this->assignedGuru, 'sanctum')
            ->getJson("/api/v1/exams/{$this->exam->id}/monitoring")
            ->assertForbidden();
    }
}
