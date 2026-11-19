<?php

namespace Tests\Feature;

use App\Contexts\TenantContext;
use App\Models\AcademicYear;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\Curriculum;
use App\Models\Phase;
use App\Models\GradeLevel;
use App\Models\Rombel;
use App\Models\StudentProfile;
use App\Models\TeacherProfile;
use App\Models\TeacherSubjectAssignment;
use App\Models\Assessment;
use App\Models\AuditLog;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptAnswer;
use App\Models\ExamAttemptScore;
use App\Models\ExamAttemptScoreCurrent;
use App\Models\ExamQuestionScore;
use App\Models\Question;
use App\Models\QuestionVersion;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class Phase7BManualReviewTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenantA;
    private Tenant $tenantB;
    private User $teacherA;
    private User $adminA;
    private User $studentA;
    private User $proctorA;
    private User $teacherB;
    private Exam $examA;
    private ExamAttempt $attemptA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantA = Tenant::create(['name' => 'Madrasah Tenant A', 'code' => 'TENA']);
        $this->tenantB = Tenant::create(['name' => 'Madrasah Tenant B', 'code' => 'TENB']);

        app(TenantContext::class)->setTenantId($this->tenantA->id);

        $guruRole = Role::firstOrCreate(['name' => 'GURU'], ['label' => 'Guru']);
        $adminRole = Role::firstOrCreate(['name' => 'ADMIN_MADRASAH'], ['label' => 'Admin Madrasah']);
        $siswaRole = Role::firstOrCreate(['name' => 'SISWA'], ['label' => 'Siswa']);
        $proctorRole = Role::firstOrCreate(['name' => 'PROKTOR'], ['label' => 'Proktor']);

        $this->teacherA = User::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Guru Tenant A',
            'email' => 'guru.a@m.com',
            'password' => bcrypt('password'),
        ]);
        $this->teacherA->roles()->attach($guruRole);

        $this->adminA = User::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Admin Tenant A',
            'email' => 'admin.a@m.com',
            'password' => bcrypt('password'),
        ]);
        $this->adminA->roles()->attach($adminRole);

        $this->studentA = User::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Siswa Tenant A',
            'email' => 'siswa.a@m.com',
            'password' => bcrypt('password'),
        ]);
        $this->studentA->roles()->attach($siswaRole);

        $this->proctorA = User::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Proctor Tenant A',
            'email' => 'proctor.a@m.com',
            'password' => bcrypt('password'),
        ]);
        $this->proctorA->roles()->attach($proctorRole);

        // Teacher B under Tenant B
        app(TenantContext::class)->setTenantId($this->tenantB->id);
        $this->teacherB = User::create([
            'tenant_id' => $this->tenantB->id,
            'name' => 'Guru Tenant B',
            'email' => 'guru.b@m.com',
            'password' => bcrypt('password'),
        ]);
        $this->teacherB->roles()->attach($guruRole);

        // Switch back to Tenant A for test exam setup
        app(TenantContext::class)->setTenantId($this->tenantA->id);

        $ay = AcademicYear::create([
            'tenant_id' => $this->tenantA->id,
            'name' => '2024/2025',
        ]);

        $semester = Semester::create([
            'tenant_id' => $this->tenantA->id,
            'academic_year_id' => $ay->id,
            'term' => 'GANJIL',
            'is_active' => true,
        ]);
        $curriculum = Curriculum::create([
            'name' => 'Kurikulum Merdeka',
            'code' => 'KM',
            'is_active' => true,
        ]);
        $phase = Phase::create([
            'curriculum_id' => $curriculum->id,
            'name' => 'Fase A',
            'code' => 'A',
        ]);
        $subject = Subject::create([
            'tenant_id' => $this->tenantA->id,
            'code' => 'MAT',
            'name' => 'Matematika',
            'short_name' => 'MTK',
            'status' => 'ACTIVE',
        ]);
        $gradeLevel = GradeLevel::create([
            'phase_id' => $phase->id,
            'name' => 'Kelas 1',
            'code' => '1',
            'level' => 1,
        ]);
        $rombel = Rombel::create([
            'tenant_id' => $this->tenantA->id,
            'academic_year_id' => $ay->id,
            'grade_level_id' => $gradeLevel->id,
            'name' => 'Phase 7B 1A',
            'status' => 'ACTIVE',
        ]);
        $teacherProfile = TeacherProfile::create([
            'tenant_id' => $this->tenantA->id,
            'user_id' => $this->teacherA->id,
            'full_name' => 'Guru Penugasan',
        ]);
        TeacherSubjectAssignment::create([
            'tenant_id' => $this->tenantA->id,
            'teacher_profile_id' => $teacherProfile->id,
            'subject_id' => $subject->id,
            'rombel_id' => $rombel->id,
            'academic_year_id' => $ay->id,
            'semester_id' => $semester->id,
            'starts_at' => now()->subDay()->toDateString(),
            'status' => 'ACTIVE',
            'created_by' => $this->teacherA->id,
        ]);
        $studentProfile = StudentProfile::create([
            'tenant_id' => $this->tenantA->id,
            'user_id' => $this->studentA->id,
            'full_name' => 'Siswa Penugasan',
        ]);
        $rombel->students()->attach($studentProfile->id, [
            'tenant_id' => $this->tenantA->id,
        ]);

        $assessment = Assessment::create([
            'tenant_id' => $this->tenantA->id,
            'academic_year_id' => $ay->id,
            'semester_id' => $semester->id,
            'subject_id' => $subject->id,
            'title' => 'Assesmen Harian Phase 7B',
            'assessment_type' => 'DAILY',
            'created_by' => $this->teacherA->id,
        ]);

        $this->examA = Exam::create([
            'tenant_id' => $this->tenantA->id,
            'assessment_id' => $assessment->id,
            'title' => 'Ujian Phase 7B',
            'duration_seconds' => 3600,
            'created_by' => $this->teacherA->id,
            'status' => 'SCHEDULED',
            'start_at' => now()->subHour(),
            'end_at' => now()->addHour(),
            'locked_at' => now(),
            'show_result' => true,
        ]);

        $participant = $this->examA->participants()->create([
            'tenant_id' => $this->tenantA->id,
            'user_id' => $this->studentA->id,
        ]);

        $this->attemptA = ExamAttempt::create([
            'tenant_id' => $this->tenantA->id,
            'exam_id' => $this->examA->id,
            'participant_id' => $participant->id,
            'attempt_number' => 1,
            'status' => 'SUBMITTED',
            'started_at' => now()->subMinutes(30),
            'expires_at' => now()->addMinutes(30),
            'submitted_at' => now(),
        ]);
        $this->bootstrapCbtRolePermissions();
        $this->adminA->unsetRelation('roles');
    }

    /** A. ROLE AUTHORIZATION (RBAC) **/

    public function test_authorized_teacher_or_admin_can_grade_manual_questions()
    {
        $snapshot = $this->createEssaySnapshot(20);
        $this->submitAnswer($snapshot->id, ['text' => 'Jawaban essay siswa']);

        $scoringService = new ScoringService();
        $scoringService->scoreAttempt($this->attemptA);

        $qScore = ExamQuestionScore::where('snapshot_id', $snapshot->id)->first();

        // Teacher A can grade
        $this->actingAs($this->teacherA, 'sanctum');
        $resTeacher = $this->putJson("/api/v1/exams/{$this->examA->id}/attempts/{$this->attemptA->id}/grading/{$qScore->id}", [
            'awarded_score' => 15,
            'feedback' => 'Penjelasan baik'
        ]);
        $resTeacher->assertStatus(200);

        // Admin A can also grade
        $this->actingAs($this->adminA, 'sanctum');
        $this->adminA->unsetRelation('roles');
        $this->assertTrue($this->adminA->hasRole('ADMIN_MADRASAH'));
        $this->assertTrue($this->adminA->hasPermission('exam.grade'));
        $this->assertTrue(
            app(\App\Policies\ExamAttemptPolicy::class)->grade($this->adminA, $this->attemptA),
            'Admin policy must allow grading when the tenant role has exam.grade.'
        );
        $resAdmin = $this->putJson("/api/v1/exams/{$this->examA->id}/attempts/{$this->attemptA->id}/grading/{$qScore->id}", [
            'awarded_score' => 18,
            'feedback' => 'Diperbarui oleh Admin'
        ]);
        $resAdmin->assertStatus(200);
        $this->assertEquals(18, $qScore->fresh()->awarded_score);
    }

    public function test_unauthorized_student_cannot_grade_or_finalize()
    {
        $snapshot = $this->createEssaySnapshot(20);
        $this->submitAnswer($snapshot->id, ['text' => 'Jawaban essay']);

        (new ScoringService())->scoreAttempt($this->attemptA);
        $qScore = ExamQuestionScore::where('snapshot_id', $snapshot->id)->first();

        $this->actingAs($this->studentA, 'sanctum');

        $resGrading = $this->putJson("/api/v1/exams/{$this->examA->id}/attempts/{$this->attemptA->id}/grading/{$qScore->id}", [
            'awarded_score' => 20
        ]);
        $resGrading->assertStatus(403);

        $resFinalize = $this->postJson("/api/v1/exams/{$this->examA->id}/attempts/{$this->attemptA->id}/finalize");
        $resFinalize->assertStatus(403);
    }

    public function test_unauthorized_proctor_cannot_grade_or_finalize()
    {
        $snapshot = $this->createEssaySnapshot(20);
        $this->submitAnswer($snapshot->id, ['text' => 'Jawaban essay']);

        (new ScoringService())->scoreAttempt($this->attemptA);
        $qScore = ExamQuestionScore::where('snapshot_id', $snapshot->id)->first();

        $this->actingAs($this->proctorA, 'sanctum');

        $resGrading = $this->putJson("/api/v1/exams/{$this->examA->id}/attempts/{$this->attemptA->id}/grading/{$qScore->id}", [
            'awarded_score' => 15
        ]);
        $resGrading->assertStatus(403);

        $resFinalize = $this->postJson("/api/v1/exams/{$this->examA->id}/attempts/{$this->attemptA->id}/finalize");
        $resFinalize->assertStatus(403);
    }

    /** B. SCORE BOUNDS & VALIDATION **/

    public function test_valid_manual_score_accepted_and_recalculated()
    {
        $snapshot = $this->createEssaySnapshot(20);
        $this->submitAnswer($snapshot->id, ['text' => 'Jawaban essay']);

        (new ScoringService())->scoreAttempt($this->attemptA);
        $qScore = ExamQuestionScore::where('snapshot_id', $snapshot->id)->first();

        $this->actingAs($this->teacherA, 'sanctum');
        $res = $this->putJson("/api/v1/exams/{$this->examA->id}/attempts/{$this->attemptA->id}/grading/{$qScore->id}", [
            'awarded_score' => 16.5,
            'feedback' => 'Sangat bagus'
        ]);
        $res->assertStatus(200);

        $freshAttemptScore = ExamAttemptScore::where('attempt_id', $this->attemptA->id)->first();
        $this->assertEquals(16.5, $freshAttemptScore->raw_score);
        $this->assertEquals(82.5, $freshAttemptScore->percentage);
        $this->assertEquals('GRADED', $freshAttemptScore->grading_status);
    }

    public function test_negative_manual_score_rejected()
    {
        $snapshot = $this->createEssaySnapshot(20);
        $this->submitAnswer($snapshot->id, ['text' => 'Jawaban']);

        (new ScoringService())->scoreAttempt($this->attemptA);
        $qScore = ExamQuestionScore::where('snapshot_id', $snapshot->id)->first();

        $this->actingAs($this->teacherA, 'sanctum');
        $res = $this->putJson("/api/v1/exams/{$this->examA->id}/attempts/{$this->attemptA->id}/grading/{$qScore->id}", [
            'awarded_score' => -5
        ]);
        $res->assertStatus(422);
    }

    public function test_manual_score_exceeding_max_score_rejected()
    {
        $snapshot = $this->createEssaySnapshot(20);
        $this->submitAnswer($snapshot->id, ['text' => 'Jawaban']);

        (new ScoringService())->scoreAttempt($this->attemptA);
        $qScore = ExamQuestionScore::where('snapshot_id', $snapshot->id)->first();

        $this->actingAs($this->teacherA, 'sanctum');
        $res = $this->putJson("/api/v1/exams/{$this->examA->id}/attempts/{$this->attemptA->id}/grading/{$qScore->id}", [
            'awarded_score' => 25
        ]);
        $res->assertStatus(422);
    }

    public function test_non_numeric_manual_score_rejected()
    {
        $snapshot = $this->createEssaySnapshot(20);
        $this->submitAnswer($snapshot->id, ['text' => 'Jawaban']);

        (new ScoringService())->scoreAttempt($this->attemptA);
        $qScore = ExamQuestionScore::where('snapshot_id', $snapshot->id)->first();

        $this->actingAs($this->teacherA, 'sanctum');
        $res = $this->putJson("/api/v1/exams/{$this->examA->id}/attempts/{$this->attemptA->id}/grading/{$qScore->id}", [
            'awarded_score' => 'invalid_number'
        ]);
        $res->assertStatus(422);
    }

    /** C. MANUAL REVIEW & ESSAY GRADED WORKFLOW **/

    public function test_essay_starts_in_manual_review_state()
    {
        $snapshot = $this->createEssaySnapshot(25);
        $this->submitAnswer($snapshot->id, ['text' => 'Jawaban essay awal']);

        $score = (new ScoringService())->scoreAttempt($this->attemptA);

        $this->assertEquals('MANUAL_REVIEW', $score->grading_status);
        $this->assertEquals(0, $score->raw_score);

        $qScore = ExamQuestionScore::where('snapshot_id', $snapshot->id)->first();
        $this->assertEquals('MANUAL_REVIEW', $qScore->scoring_status);
        $this->assertEquals(0, $qScore->awarded_score);
    }

    public function test_manual_grading_updates_awarded_score_and_feedback()
    {
        $snapshot = $this->createEssaySnapshot(20);
        $this->submitAnswer($snapshot->id, ['text' => 'Jawaban']);

        (new ScoringService())->scoreAttempt($this->attemptA);
        $qScore = ExamQuestionScore::where('snapshot_id', $snapshot->id)->first();

        $this->actingAs($this->teacherA, 'sanctum');
        $this->putJson("/api/v1/exams/{$this->examA->id}/attempts/{$this->attemptA->id}/grading/{$qScore->id}", [
            'awarded_score' => 14,
            'feedback' => 'Penjelasan perlu dilengkapi'
        ])->assertStatus(200);

        $freshQScore = $qScore->fresh();
        $this->assertEquals(14, $freshQScore->awarded_score);
        $this->assertEquals('Penjelasan perlu dilengkapi', $freshQScore->feedback);
        $this->assertEquals('GRADED', $freshQScore->scoring_status);
        $this->assertEquals($this->teacherA->id, $freshQScore->grader_id);
    }

    public function test_all_manual_questions_graded_transitions_attempt_status_to_graded_not_finalized()
    {
        $snap1 = $this->createEssaySnapshot(10);
        $snap2 = $this->createEssaySnapshot(10);
        $this->submitAnswer($snap1->id, ['text' => 'Ans 1']);
        $this->submitAnswer($snap2->id, ['text' => 'Ans 2']);

        (new ScoringService())->scoreAttempt($this->attemptA);
        $scoreInitial = ExamAttemptScore::where('attempt_id', $this->attemptA->id)->first();
        $this->assertEquals('MANUAL_REVIEW', $scoreInitial->grading_status);

        $qScore1 = ExamQuestionScore::where('snapshot_id', $snap1->id)->first();
        $qScore2 = ExamQuestionScore::where('snapshot_id', $snap2->id)->first();

        $this->actingAs($this->teacherA, 'sanctum');

        // Grade snap1 only -> status remains MANUAL_REVIEW
        $this->putJson("/api/v1/exams/{$this->examA->id}/attempts/{$this->attemptA->id}/grading/{$qScore1->id}", ['awarded_score' => 8]);
        $scorePartial = ExamAttemptScore::where('attempt_id', $this->attemptA->id)->first();
        $this->assertEquals('MANUAL_REVIEW', $scorePartial->grading_status);

        // Grade snap2 -> status transitions to GRADED, NOT FINALIZED
        $this->putJson("/api/v1/exams/{$this->examA->id}/attempts/{$this->attemptA->id}/grading/{$qScore2->id}", ['awarded_score' => 9]);
        $scoreAllGraded = ExamAttemptScore::where('attempt_id', $this->attemptA->id)->first();
        $this->assertEquals('GRADED', $scoreAllGraded->grading_status);
        $this->assertNull($scoreAllGraded->finalized_at);
        $this->assertNull($scoreAllGraded->finalized_by);
    }

    /** D. FINALIZATION PREFLIGHT & ATOMICITY **/

    public function test_finalization_blocked_if_manual_review_questions_remain()
    {
        $snap = $this->createEssaySnapshot(20);
        $this->submitAnswer($snap->id, ['text' => 'Jawaban']);
        (new ScoringService())->scoreAttempt($this->attemptA);

        $this->actingAs($this->teacherA, 'sanctum');
        $res = $this->postJson("/api/v1/exams/{$this->examA->id}/attempts/{$this->attemptA->id}/finalize");

        $res->assertStatus(422);
        $res->assertJsonFragment(['success' => false]);

        $score = ExamAttemptScore::where('attempt_id', $this->attemptA->id)->first();
        $this->assertEquals('MANUAL_REVIEW', $score->grading_status);
        $this->assertNull($score->finalized_at);
    }

    public function test_finalization_blocked_if_attempt_is_in_progress()
    {
        $this->attemptA->update(['status' => 'IN_PROGRESS']);

        $this->actingAs($this->teacherA, 'sanctum');
        $res = $this->postJson("/api/v1/exams/{$this->examA->id}/attempts/{$this->attemptA->id}/finalize");

        $res->assertStatus(422);
    }

    public function test_finalization_succeeds_when_all_grading_complete()
    {
        $snap = $this->createEssaySnapshot(20);
        $this->submitAnswer($snap->id, ['text' => 'Jawaban']);
        (new ScoringService())->scoreAttempt($this->attemptA);

        $qScore = ExamQuestionScore::where('snapshot_id', $snap->id)->first();

        $this->actingAs($this->teacherA, 'sanctum');
        $this->putJson("/api/v1/exams/{$this->examA->id}/attempts/{$this->attemptA->id}/grading/{$qScore->id}", ['awarded_score' => 15]);

        $resFinalize = $this->postJson("/api/v1/exams/{$this->examA->id}/attempts/{$this->attemptA->id}/finalize");
        $resFinalize->assertStatus(200);

        $score = ExamAttemptScore::where('attempt_id', $this->attemptA->id)->first();
        $this->assertEquals('FINALIZED', $score->grading_status);
        $this->assertNotNull($score->finalized_at);
        $this->assertEquals($this->teacherA->id, $score->finalized_by);
    }

    /** E. PROTECTION OF FINALIZED RESULTS **/

    public function test_manual_score_update_after_finalized_rejected()
    {
        $snap = $this->createEssaySnapshot(20);
        $this->submitAnswer($snap->id, ['text' => 'Jawaban']);
        (new ScoringService())->scoreAttempt($this->attemptA);
        $qScore = ExamQuestionScore::where('snapshot_id', $snap->id)->first();

        $this->actingAs($this->teacherA, 'sanctum');
        $this->putJson("/api/v1/exams/{$this->examA->id}/attempts/{$this->attemptA->id}/grading/{$qScore->id}", ['awarded_score' => 15]);
        $this->postJson("/api/v1/exams/{$this->examA->id}/attempts/{$this->attemptA->id}/finalize")->assertStatus(200);

        // Try to update manual score after finalized -> 422
        $resUpdate = $this->putJson("/api/v1/exams/{$this->examA->id}/attempts/{$this->attemptA->id}/grading/{$qScore->id}", ['awarded_score' => 18]);
        $resUpdate->assertStatus(422);

        $this->assertEquals(15, $qScore->fresh()->awarded_score);
    }

    public function test_recalculation_after_finalized_rejected()
    {
        $snap = $this->createEssaySnapshot(20);
        $this->submitAnswer($snap->id, ['text' => 'Jawaban']);
        (new ScoringService())->scoreAttempt($this->attemptA);
        $qScore = ExamQuestionScore::where('snapshot_id', $snap->id)->first();

        $this->actingAs($this->teacherA, 'sanctum');
        $this->putJson("/api/v1/exams/{$this->examA->id}/attempts/{$this->attemptA->id}/grading/{$qScore->id}", ['awarded_score' => 15]);
        $this->postJson("/api/v1/exams/{$this->examA->id}/attempts/{$this->attemptA->id}/finalize")->assertStatus(200);

        // Recalculate after finalized -> 422
        $resRecalc = $this->postJson("/api/v1/exams/{$this->examA->id}/attempts/{$this->attemptA->id}/recalculate");
        $resRecalc->assertStatus(422);
    }

    public function test_second_finalization_is_idempotent_and_safe()
    {
        $snap = $this->createEssaySnapshot(20);
        $this->submitAnswer($snap->id, ['text' => 'Jawaban']);
        (new ScoringService())->scoreAttempt($this->attemptA);
        $qScore = ExamQuestionScore::where('snapshot_id', $snap->id)->first();

        $this->actingAs($this->teacherA, 'sanctum');
        $this->putJson("/api/v1/exams/{$this->examA->id}/attempts/{$this->attemptA->id}/grading/{$qScore->id}", ['awarded_score' => 15]);

        $res1 = $this->postJson("/api/v1/exams/{$this->examA->id}/attempts/{$this->attemptA->id}/finalize");
        $res1->assertStatus(200);

        $finalizedAt1 = ExamAttemptScore::where('attempt_id', $this->attemptA->id)->first()->finalized_at;

        // Second finalization
        $res2 = $this->postJson("/api/v1/exams/{$this->examA->id}/attempts/{$this->attemptA->id}/finalize");
        $res2->assertStatus(200);

        $finalizedAt2 = ExamAttemptScore::where('attempt_id', $this->attemptA->id)->first()->finalized_at;
        $this->assertEquals($finalizedAt1, $finalizedAt2);
    }

    /** F. TENANT ISOLATION **/

    public function test_cross_tenant_grading_request_denied()
    {
        $snap = $this->createEssaySnapshot(20);
        $this->submitAnswer($snap->id, ['text' => 'Jawaban Tenant A']);
        (new ScoringService())->scoreAttempt($this->attemptA);
        $qScore = ExamQuestionScore::where('snapshot_id', $snap->id)->first();

        // Switch to Teacher B (Tenant B)
        $this->actingAs($this->teacherB, 'sanctum');

        $resGrading = $this->putJson("/api/v1/exams/{$this->examA->id}/attempts/{$this->attemptA->id}/grading/{$qScore->id}", [
            'awarded_score' => 10
        ]);
        $resGrading->assertStatus(404);
    }

    public function test_cross_tenant_finalization_request_denied()
    {
        $snap = $this->createEssaySnapshot(20);
        $this->submitAnswer($snap->id, ['text' => 'Jawaban Tenant A']);
        (new ScoringService())->scoreAttempt($this->attemptA);

        $this->actingAs($this->teacherB, 'sanctum');

        $resFinalize = $this->postJson("/api/v1/exams/{$this->examA->id}/attempts/{$this->attemptA->id}/finalize");
        $resFinalize->assertStatus(404);
    }

    public function test_uuid_manipulation_across_tenants_blocked()
    {
        // Foreign question score ID passed to Tenant A attempt endpoint
        $fakeQuestionScoreId = (string) Str::uuid();

        $this->actingAs($this->teacherA, 'sanctum');
        $res = $this->putJson("/api/v1/exams/{$this->examA->id}/attempts/{$this->attemptA->id}/grading/{$fakeQuestionScoreId}", [
            'awarded_score' => 10
        ]);
        $res->assertStatus(404);
    }

    /** G. AUDITABILITY **/

    public function test_manual_grading_event_logged_to_audit_log_with_before_after()
    {
        $snap = $this->createEssaySnapshot(20);
        $this->submitAnswer($snap->id, ['text' => 'Jawaban']);
        (new ScoringService())->scoreAttempt($this->attemptA);
        $qScore = ExamQuestionScore::where('snapshot_id', $snap->id)->first();

        $this->actingAs($this->teacherA, 'sanctum');
        $this->putJson("/api/v1/exams/{$this->examA->id}/attempts/{$this->attemptA->id}/grading/{$qScore->id}", [
            'awarded_score' => 17,
            'feedback' => 'Catatan audit'
        ]);

        $auditLog = AuditLog::where('action', 'UPDATE_MANUAL_SCORE')
            ->where('entity_id', $qScore->id)
            ->first();

        $this->assertNotNull($auditLog);
        $this->assertEquals($this->tenantA->id, $auditLog->tenant_id);
        $this->assertEquals($this->teacherA->id, $auditLog->user_id);
        $this->assertNotNull($auditLog->old_values);
        $this->assertNotNull($auditLog->new_values);
    }

    public function test_finalization_event_logged_to_audit_log()
    {
        $snap = $this->createEssaySnapshot(20);
        $this->submitAnswer($snap->id, ['text' => 'Jawaban']);
        (new ScoringService())->scoreAttempt($this->attemptA);
        $qScore = ExamQuestionScore::where('snapshot_id', $snap->id)->first();

        $this->actingAs($this->teacherA, 'sanctum');
        $this->putJson("/api/v1/exams/{$this->examA->id}/attempts/{$this->attemptA->id}/grading/{$qScore->id}", ['awarded_score' => 15]);
        $this->postJson("/api/v1/exams/{$this->examA->id}/attempts/{$this->attemptA->id}/finalize");

        $auditLog = AuditLog::where('action', 'FINALIZE_SCORE')->first();
        $this->assertNotNull($auditLog);
        $this->assertEquals($this->tenantA->id, $auditLog->tenant_id);
        $this->assertEquals($this->teacherA->id, $auditLog->user_id);
    }

    /** H. SERVER AUTHORITY & ANTI-INJECTION **/

    public function test_server_ignores_malicious_payload_fields_for_grading_status_and_scores()
    {
        $snap = $this->createEssaySnapshot(20);
        $this->submitAnswer($snap->id, ['text' => 'Jawaban']);
        (new ScoringService())->scoreAttempt($this->attemptA);
        $qScore = ExamQuestionScore::where('snapshot_id', $snap->id)->first();

        $this->actingAs($this->teacherA, 'sanctum');

        // Client passes malicious extra fields attempting to force FINALIZED status or fake scores
        $this->putJson("/api/v1/exams/{$this->examA->id}/attempts/{$this->attemptA->id}/grading/{$qScore->id}", [
            'awarded_score' => 12,
            'grading_status' => 'FINALIZED',
            'scoring_status' => 'FINALIZED',
            'finalized_at' => '2020-01-01T00:00:00Z',
            'finalized_by' => $this->studentA->id,
            'raw_score' => 999,
            'percentage' => 100,
            'tenant_id' => $this->tenantB->id
        ])->assertStatus(200);

        $score = ExamAttemptScore::where('attempt_id', $this->attemptA->id)->first();
        $this->assertEquals('GRADED', $score->grading_status);
        $this->assertEquals(12, $score->raw_score);
        $this->assertNull($score->finalized_at);
        $this->assertNull($score->finalized_by);
        $this->assertEquals($this->tenantA->id, $score->tenant_id);
    }

    /** I. VERSION INTEGRITY & CURRENT POINTER **/

    public function test_manual_grading_operates_on_current_scoring_version_only()
    {
        $snap = $this->createEssaySnapshot(20);
        $this->submitAnswer($snap->id, ['text' => 'Jawaban']);
        $scoringService = new ScoringService();

        // Version 1
        $scoreV1 = $scoringService->scoreAttempt($this->attemptA);
        $qScoreV1 = ExamQuestionScore::where('snapshot_id', $snap->id)->where('scoring_version', 1)->first();

        // Create Version 2
        $scoreV2 = $scoringService->scoreAttempt($this->attemptA, true, true);
        $qScoreV2 = ExamQuestionScore::where('snapshot_id', $snap->id)->where('scoring_version', 2)->first();

        $this->actingAs($this->teacherA, 'sanctum');

        // Updating V1 (historical) fails with 422
        $resV1Update = $this->putJson("/api/v1/exams/{$this->examA->id}/attempts/{$this->attemptA->id}/grading/{$qScoreV1->id}", [
            'awarded_score' => 10
        ]);
        $resV1Update->assertStatus(422);

        // Updating V2 (current version) succeeds
        $resV2Update = $this->putJson("/api/v1/exams/{$this->examA->id}/attempts/{$this->attemptA->id}/grading/{$qScoreV2->id}", [
            'awarded_score' => 18
        ]);
        $resV2Update->assertStatus(200);

        $currentPointer = ExamAttemptScoreCurrent::where('attempt_id', $this->attemptA->id)->first();
        $this->assertEquals($scoreV2->id, $currentPointer->current_score_id);
        $this->assertEquals(2, $currentPointer->scoring_version);
    }

    private function createEssaySnapshot(float $points)
    {
        $q = Question::create(['tenant_id' => $this->tenantA->id, 'created_by' => $this->teacherA->id]);
        $v = QuestionVersion::create([
            'tenant_id' => $this->tenantA->id,
            'question_id' => $q->id,
            'stem' => 'Stem Essay',
            'question_type' => 'ESSAY',
            'version_number' => 1,
            'status' => 'PUBLISHED',
            'created_by' => $this->teacherA->id,
        ]);

        $snapshot = $this->examA->snapshots()->create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $this->tenantA->id,
            'source_question_id' => $q->id,
            'source_question_version_id' => $v->id,
            'snapshot_stem' => 'Stem Essay',
            'snapshot_options' => [],
            'points' => $points,
            'metadata' => ['question_type' => 'ESSAY'],
        ]);
        $order = $this->attemptA->fresh()->question_order ?? [];
        $order[] = (string) $snapshot->id;
        $this->attemptA->update(['question_order' => array_values(array_unique($order))]);

        return $snapshot;
    }

    private function submitAnswer(string $snapshotId, array $payload)
    {
        ExamAttemptAnswer::create([
            'tenant_id' => $this->tenantA->id,
            'exam_id' => $this->examA->id,
            'exam_attempt_id' => $this->attemptA->id,
            'exam_question_snapshot_id' => $snapshotId,
            'student_id' => $this->studentA->id,
            'answer_payload' => $payload,
            'answer_state' => 'ANSWERED',
        ]);
    }
}
