<?php

namespace Tests\Feature;

use App\Contexts\TenantContext;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Assessment;
use App\Models\Curriculum;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamParticipant;
use App\Models\ExamSessionRoom;
use App\Services\ExamRoomSessionService;
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
    private ExamSessionRoom $sessionRoom;

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
        $this->exam->participants()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->student->id,
            'rombel_id' => $rombel->id,
        ]);

        $roomService = app(ExamRoomSessionService::class);
        $room = $roomService->createRoom($this->admin, [
            'code' => 'MON-R1',
            'name' => 'Ruang Monitoring 1',
            'location' => 'Gedung A',
            'capacity' => 30,
        ]);
        $session = $roomService->createSession(
            $this->admin,
            $this->exam->id,
            'Sesi Monitoring',
            1,
            $this->exam->start_at,
            $this->exam->end_at
        );
        $this->sessionRoom = $roomService->attachRoom(
            $this->admin,
            $this->exam->id,
            $session->id,
            $room->id
        );
        $roomService->mapRombel($this->admin, $this->sessionRoom->id, $rombel->id);
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
            $this->sessionRoom->id,
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

    public function test_proctor_monitoring_is_limited_to_participants_in_the_assigned_room_session(): void
    {
        $otherRombel = Rombel::create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $this->exam->assessment->academic_year_id,
            'grade_level_id' => $this->rombelIdForFixture(),
            'name' => '1B-MON',
            'status' => 'ACTIVE',
        ]);

        $siswaRole = Role::where('name', 'SISWA')->firstOrFail();
        $otherStudent = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Siswa Ruang Dua',
            'email' => 'siswa-ruang-dua@example.test',
            'password' => bcrypt('password'),
        ]);
        $otherStudent->roles()->attach($siswaRole->id);
        $otherProfile = StudentProfile::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $otherStudent->id,
            'full_name' => 'Siswa Ruang Dua',
        ]);
        $otherRombel->students()->attach($otherProfile->id);
        $otherParticipant = $this->exam->participants()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $otherStudent->id,
            'rombel_id' => $otherRombel->id,
        ]);

        $roomService = app(ExamRoomSessionService::class);
        $roomTwo = $roomService->createRoom($this->admin, [
            'code' => 'MON-R2',
            'name' => 'Ruang Monitoring 2',
            'location' => 'Gedung B',
            'capacity' => 30,
        ]);
        $sessionTwo = $roomService->createSession(
            $this->admin,
            $this->exam->id,
            'Sesi Dua',
            2,
            $this->exam->start_at,
            $this->exam->end_at
        );
        $sessionRoomTwo = $roomService->attachRoom(
            $this->admin,
            $this->exam->id,
            $sessionTwo->id,
            $roomTwo->id
        );
        $roomService->mapRombel($this->admin, $sessionRoomTwo->id, $otherRombel->id);

        $this->assignTemporaryProctor();

        $assignedParticipant = ExamParticipant::query()
            ->where('tenant_id', $this->tenant->id)
            ->where('exam_id', $this->exam->id)
            ->where('user_id', $this->student->id)
            ->firstOrFail();
        $assignedAttempt = ExamAttempt::create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'participant_id' => $assignedParticipant->id,
            'student_id' => $this->student->id,
            'attempt_number' => 1,
            'status' => 'IN_PROGRESS',
        ]);

        $this->actingAs($this->assignedGuru, 'sanctum');
        $participants = $this->getJson("/api/v1/exams/{$this->exam->id}/monitoring/participants");
        $participants->assertOk();
        $this->assertCount(1, $participants->json('data'));
        $this->assertSame('Siswa', $participants->json('data.0.student_name'));
        $this->assertArrayNotHasKey('answer_payload', $participants->json('data.0'));
        $this->assertArrayNotHasKey('answers', $participants->json('data.0'));

        $monitoringAttemptDetail = $this->getJson(
            "/api/v1/exams/{$this->exam->id}/monitoring/attempts/{$assignedAttempt->id}"
        );
        $monitoringAttemptDetail->assertOk();
        $this->assertSame($assignedAttempt->id, $monitoringAttemptDetail->json('data.attempt.id'));
        $this->assertArrayNotHasKey('answer_payload', $monitoringAttemptDetail->json('data.attempt'));
        $this->assertArrayNotHasKey('answers', $monitoringAttemptDetail->json('data'));

        // The legacy participant index is a second route to the same operational
        // data and must use the exact same room/session allowlist.
        $directParticipants = $this->getJson("/api/v1/exams/{$this->exam->id}/participants");
        $directParticipants->assertOk();
        $this->assertCount(1, $directParticipants->json('data.data'));
        $this->assertSame('Siswa', $directParticipants->json('data.data.0.student_name'));
        $this->assertSame(
            ['id', 'student_name', 'status'],
            array_keys($directParticipants->json('data.data.0'))
        );

        $assignedAttemptDetail = $this->getJson(
            "/api/v1/exams/{$this->exam->id}/attempts/{$assignedAttempt->id}"
        );
        $assignedAttemptDetail->assertOk();
        $this->assertSame($assignedAttempt->id, $assignedAttemptDetail->json('data.id'));
        $this->assertSame(
            ['id', 'status', 'attempt_number', 'started_at', 'expires_at', 'submitted_at'],
            array_keys($assignedAttemptDetail->json('data'))
        );

        $otherAttempt = ExamAttempt::create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'participant_id' => $otherParticipant->id,
            'student_id' => $otherStudent->id,
            'attempt_number' => 1,
            'status' => 'IN_PROGRESS',
        ]);

        $this->getJson("/api/v1/exams/{$this->exam->id}/monitoring/attempts/{$otherAttempt->id}")
            ->assertNotFound();
        $this->getJson("/api/v1/exams/{$this->exam->id}/attempts/{$otherAttempt->id}")
            ->assertNotFound();

        AuditLog::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->assignedGuru->id,
            'action' => 'ATTEMPT_HEARTBEAT',
            'entity_type' => 'ExamAttempt',
            'entity_id' => $assignedAttempt->id,
            'new_values' => ['state' => 'in_scope'],
            'created_at' => now(),
        ]);
        AuditLog::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->assignedGuru->id,
            'action' => 'ATTEMPT_HEARTBEAT',
            'entity_type' => 'ExamAttempt',
            'entity_id' => $otherAttempt->id,
            'new_values' => ['state' => 'out_of_scope'],
            'created_at' => now()->addSecond(),
        ]);

        $events = $this->getJson("/api/v1/exams/{$this->exam->id}/monitoring/events");
        $events->assertOk();
        $this->assertSame(1, $events->json('data.total'));
        $this->assertSame($assignedAttempt->id, $events->json('data.data.0.entity_id'));

        // A temporary Proktor has operational monitoring capability only.
        // Answer payloads, scores, reports, analytics, and student execution
        // endpoints remain unavailable even when the attempt is in their room.
        $this->getJson('/api/v1/exams')->assertOk()->assertJsonCount(0, 'data');

        $forbiddenGetUrls = [
            "/api/v1/exams/{$this->exam->id}",
            "/api/v1/exams/{$this->exam->id}/questions",
            "/api/v1/exams/{$this->exam->id}/preview",
            "/api/v1/exams/{$this->exam->id}/attempts/{$assignedAttempt->id}/answers",
            "/api/v1/exams/{$this->exam->id}/attempts/{$assignedAttempt->id}/score",
            "/api/v1/reports/exams/{$this->exam->id}/results",
            "/api/v1/reports/exams/{$this->exam->id}/summary",
            "/api/v1/reports/exams/{$this->exam->id}/questions",
            "/api/v1/reports/exams/{$this->exam->id}/rombels/{$otherRombel->id}",
            "/api/v1/reports/students/{$otherStudent->id}/results",
            "/api/v1/reports/subjects/{$this->exam->assessment->subject_id}/results",
            "/api/v1/analytics/rombels/{$otherRombel->id}/exams/{$this->exam->id}",
            "/api/v1/analytics/students/{$otherStudent->id}",
            "/api/v1/analytics/subjects/{$this->exam->assessment->subject_id}",
            "/api/v1/reports/exams/{$this->exam->id}/export/pdf",
            "/api/v1/reports/exams/{$this->exam->id}/export/excel",
            "/api/v1/reports/exams/{$this->exam->id}/summary/export/pdf",
            "/api/v1/analytics/exams/{$this->exam->id}",
            "/api/v1/analytics/exams/{$this->exam->id}/distribution",
            "/api/v1/analytics/exams/{$this->exam->id}/items",
            "/api/v1/exams/{$this->exam->id}/execution",
        ];
        foreach ($forbiddenGetUrls as $url) {
            $this->getJson($url)->assertForbidden();
        }

        $forbiddenPostUrls = [
            "/api/v1/exams/{$this->exam->id}/attempts",
            "/api/v1/exams/{$this->exam->id}/attempts/{$assignedAttempt->id}/heartbeat",
            "/api/v1/exams/{$this->exam->id}/attempts/{$assignedAttempt->id}/submit",
            "/api/v1/exams/{$this->exam->id}/attempts/{$assignedAttempt->id}/score",
            "/api/v1/exams/{$this->exam->id}/attempts/{$assignedAttempt->id}/recalculate",
            "/api/v1/exams/{$this->exam->id}/attempts/{$assignedAttempt->id}/finalize",
            "/api/v1/exams/{$this->exam->id}/attempts/{$assignedAttempt->id}/reopen",
            "/api/v1/exams/{$this->exam->id}/token",
            "/api/v1/exams/{$this->exam->id}/lock",
            "/api/v1/exams/{$this->exam->id}/close",
            "/api/v1/exams/{$this->exam->id}/assign-rombel",
            "/api/v1/exams/{$this->exam->id}/publish-results",
            "/api/v1/exams/{$this->exam->id}/participants",
        ];
        foreach ($forbiddenPostUrls as $url) {
            $this->postJson($url)->assertForbidden();
        }

        $summary = $this->getJson("/api/v1/exams/{$this->exam->id}/monitoring");
        $summary->assertOk();
        $this->assertSame(1, $summary->json('data.summary.total_participants'));
    }

    private function rombelIdForFixture(): string
    {
        return Rombel::query()
            ->where('tenant_id', $this->tenant->id)
            ->where('name', '1A-MON')
            ->value('grade_level_id');
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

    public function test_admin_direct_api_cannot_access_monitoring_attempt_answers_scores_or_reports(): void
    {
        $participant = ExamParticipant::query()
            ->where('tenant_id', $this->tenant->id)
            ->where('exam_id', $this->exam->id)
            ->where('user_id', $this->student->id)
            ->firstOrFail();

        $attempt = ExamAttempt::create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'participant_id' => $participant->id,
            'student_id' => $this->student->id,
            'attempt_number' => 1,
            'status' => 'IN_PROGRESS',
        ]);

        $this->actingAs($this->admin, 'sanctum');

        $getUrls = [
            "/api/v1/exams/{$this->exam->id}/monitoring",
            "/api/v1/exams/{$this->exam->id}/monitoring/participants",
            "/api/v1/exams/{$this->exam->id}/monitoring/attempts/{$attempt->id}",
            "/api/v1/exams/{$this->exam->id}/monitoring/events",
            "/api/v1/exams/{$this->exam->id}/participants",
            "/api/v1/exams/{$this->exam->id}/attempts/{$attempt->id}",
            "/api/v1/exams/{$this->exam->id}/attempts/{$attempt->id}/answers",
            "/api/v1/exams/{$this->exam->id}/attempts/{$attempt->id}/score",
            "/api/v1/reports/exams/{$this->exam->id}/results",
            "/api/v1/reports/exams/{$this->exam->id}/summary",
            "/api/v1/reports/exams/{$this->exam->id}/questions",
            "/api/v1/reports/exams/{$this->exam->id}/export/pdf",
            "/api/v1/reports/exams/{$this->exam->id}/export/excel",
            "/api/v1/reports/exams/{$this->exam->id}/summary/export/pdf",
            "/api/v1/exams/{$this->exam->id}/execution",
        ];

        foreach ($getUrls as $url) {
            $this->getJson($url)->assertForbidden();
        }

        $postUrls = [
            "/api/v1/exams/{$this->exam->id}/attempts",
            "/api/v1/exams/{$this->exam->id}/attempts/{$attempt->id}/heartbeat",
            "/api/v1/exams/{$this->exam->id}/attempts/{$attempt->id}/submit",
            "/api/v1/exams/{$this->exam->id}/attempts/{$attempt->id}/score",
            "/api/v1/exams/{$this->exam->id}/attempts/{$attempt->id}/recalculate",
            "/api/v1/exams/{$this->exam->id}/attempts/{$attempt->id}/finalize",
            "/api/v1/exams/{$this->exam->id}/attempts/{$attempt->id}/reopen",
            "/api/v1/exams/{$this->exam->id}/token",
            "/api/v1/exams/{$this->exam->id}/lock",
            "/api/v1/exams/{$this->exam->id}/close",
            "/api/v1/exams/{$this->exam->id}/assign-rombel",
            "/api/v1/exams/{$this->exam->id}/publish-results",
        ];

        foreach ($postUrls as $url) {
            $this->postJson($url)->assertForbidden();
        }
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
        \Illuminate\Support\Facades\DB::table('exam_proctor_assignments')
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
        $expiredAssignment = \App\Models\ExamProctorAssignment::query()
            ->where('status', 'CONFIRMED')
            ->latest()
            ->firstOrFail();
        $expiredAssignment->update(['expires_at' => now()->subSecond()]);

        $this->actingAs($this->assignedGuru, 'sanctum')
            ->getJson("/api/v1/exams/{$this->exam->id}/monitoring")
            ->assertForbidden();
    }
    public function test_tenant_b_guru_cannot_access_tenant_a_monitoring_participants_or_attempts(): void
    {
        $participant = ExamParticipant::query()
            ->where('tenant_id', $this->tenant->id)
            ->where('exam_id', $this->exam->id)
            ->where('user_id', $this->student->id)
            ->firstOrFail();
        $attempt = ExamAttempt::create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'participant_id' => $participant->id,
            'student_id' => $this->student->id,
            'attempt_number' => 1,
            'status' => 'IN_PROGRESS',
        ]);

        $tenantB = Tenant::create(['name' => 'Tenant Monitoring B', 'code' => 'MONB']);
        app(TenantContext::class)->setTenantId($tenantB->id);
        $guruRole = Role::where('name', 'GURU')->firstOrFail();
        $foreignGuru = User::create([
            'tenant_id' => $tenantB->id,
            'name' => 'Guru Tenant B',
            'email' => 'guru-tenant-b-monitor@example.test',
            'password' => bcrypt('password'),
        ]);
        $foreignGuru->roles()->attach($guruRole->id);
        TeacherProfile::create([
            'tenant_id' => $tenantB->id,
            'user_id' => $foreignGuru->id,
            'full_name' => 'Guru Tenant B',
        ]);
        app(TenantContext::class)->setTenantId(null);
        TenantContext::resetInstance();

        $this->actingAs($foreignGuru, 'sanctum');
        $crossTenantUrls = [
            "/api/v1/exams/{$this->exam->id}/monitoring",
            "/api/v1/exams/{$this->exam->id}/monitoring/participants",
            "/api/v1/exams/{$this->exam->id}/monitoring/attempts/{$attempt->id}",
            "/api/v1/exams/{$this->exam->id}/participants",
            "/api/v1/exams/{$this->exam->id}/attempts/{$attempt->id}",
        ];
        foreach ($crossTenantUrls as $url) {
            $response = $this->getJson($url);
            $this->assertContains(
                $response->getStatusCode(),
                [403, 404],
                "Cross-tenant access must fail closed for {$url}."
            );
        }
    }

    public function test_admin_cannot_access_operational_exam_endpoints_through_direct_api_or_web_url(): void
    {
        $participant = ExamParticipant::query()
            ->where('tenant_id', $this->tenant->id)
            ->where('exam_id', $this->exam->id)
            ->where('user_id', $this->student->id)
            ->firstOrFail();
        $attempt = ExamAttempt::create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'participant_id' => $participant->id,
            'student_id' => $this->student->id,
            'attempt_number' => 1,
            'status' => 'IN_PROGRESS',
        ]);

        $this->actingAs($this->admin, 'sanctum');
        $forbiddenGetUrls = [
            "/api/v1/exams",
            "/api/v1/exams/{$this->exam->id}",
            "/api/v1/exams/{$this->exam->id}/questions",
            "/api/v1/exams/{$this->exam->id}/preview",
            "/api/v1/exams/{$this->exam->id}/monitoring",
            "/api/v1/exams/{$this->exam->id}/monitoring/participants",
            "/api/v1/exams/{$this->exam->id}/monitoring/attempts/{$attempt->id}",
            "/api/v1/exams/{$this->exam->id}/monitoring/events",
            "/api/v1/exams/{$this->exam->id}/participants",
            "/api/v1/exams/{$this->exam->id}/attempts/{$attempt->id}",
            "/api/v1/exams/{$this->exam->id}/attempts/{$attempt->id}/answers",
            "/api/v1/exams/{$this->exam->id}/attempts/{$attempt->id}/score",
            "/api/v1/reports/exams/{$this->exam->id}/results",
            "/api/v1/reports/exams/{$this->exam->id}/summary",
            "/api/v1/reports/exams/{$this->exam->id}/questions",
            "/api/v1/reports/exams/{$this->exam->id}/summary/export/pdf",
            "/api/v1/reports/exams/{$this->exam->id}/export/excel",
            "/api/v1/reports/exams/{$this->exam->id}/export/pdf",
            "/api/v1/analytics/exams/{$this->exam->id}",
            "/api/v1/analytics/exams/{$this->exam->id}/distribution",
            "/api/v1/analytics/exams/{$this->exam->id}/items",
        ];
        foreach ($forbiddenGetUrls as $url) {
            $this->getJson($url)->assertForbidden();
        }

        $forbiddenPostUrls = [
            "/api/v1/exams/{$this->exam->id}/attempts",
            "/api/v1/exams/{$this->exam->id}/attempts/{$attempt->id}/heartbeat",
            "/api/v1/exams/{$this->exam->id}/attempts/{$attempt->id}/submit",
            "/api/v1/exams/{$this->exam->id}/attempts/{$attempt->id}/score",
            "/api/v1/exams/{$this->exam->id}/attempts/{$attempt->id}/recalculate",
            "/api/v1/exams/{$this->exam->id}/attempts/{$attempt->id}/finalize",
            "/api/v1/exams/{$this->exam->id}/attempts/{$attempt->id}/reopen",
            "/api/v1/exams/{$this->exam->id}/token",
            "/api/v1/exams/{$this->exam->id}/lock",
            "/api/v1/exams/{$this->exam->id}/close",
            "/api/v1/exams/{$this->exam->id}/assign-rombel",
            "/api/v1/exams/{$this->exam->id}/publish-results",
            "/api/v1/exams/{$this->exam->id}/participants",
        ];
        foreach ($forbiddenPostUrls as $url) {
            $this->postJson($url)->assertForbidden();
        }

        $this->actingAs($this->admin);
        $this->get("/exams/{$this->exam->id}/monitoring")->assertForbidden();
    }

    public function test_nonconfirmed_lifecycle_states_and_revision_mismatch_never_grant_monitoring_scope(): void
    {
        $assignment = $this->createPendingTemporaryProctorAssignment();

        foreach (['PENDING', 'NEEDS_REVIEW', 'REVOKED', 'EXPIRED', 'COMPLETED', 'ACTIVE'] as $status) {
            $assignment->update([
                'status' => $status,
                'revoked_at' => $status === 'REVOKED' ? now() : null,
                'expires_at' => $status === 'EXPIRED' ? now()->subSecond() : now()->addMinutes(30),
            ]);

            $this->actingAs($this->assignedGuru, 'sanctum')
                ->getJson("/api/v1/exams/{$this->exam->id}/monitoring")
                ->assertForbidden();
        }

        $assignment->update([
            'status' => 'CONFIRMED',
            'revoked_at' => null,
            'expires_at' => now()->addMinutes(30),
        ]);

        $sessionRoom = ExamSessionRoom::query()->whereKey($assignment->exam_session_room_id)->firstOrFail();
        $revision = (int) \Illuminate\Support\Facades\DB::table('exam_sessions')
            ->where('id', $sessionRoom->exam_session_id)
            ->value('schedule_revision');
        \Illuminate\Support\Facades\DB::table('exam_sessions')
            ->where('id', $sessionRoom->exam_session_id)
            ->update(['schedule_revision' => $revision + 1]);

        $this->actingAs($this->assignedGuru, 'sanctum')
            ->getJson("/api/v1/exams/{$this->exam->id}/monitoring")
            ->assertForbidden();
    }

    public function test_mixed_role_guru_is_denied_even_with_confirmed_proctor_assignment(): void
    {
        $this->assignTemporaryProctor();
        $studentRole = Role::where('name', 'SISWA')->firstOrFail();
        $this->assignedGuru->roles()->attach($studentRole->id);
        $this->assignedGuru->unsetRelation('roles');

        $this->actingAs($this->assignedGuru, 'sanctum')
            ->getJson("/api/v1/exams/{$this->exam->id}/monitoring")
            ->assertForbidden();
    }

}
