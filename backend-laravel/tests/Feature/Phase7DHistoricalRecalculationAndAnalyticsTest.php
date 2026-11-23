<?php

namespace Tests\Feature;

use Tests\Concerns\CreatesCanonicalTeacherAssignment;
use Tests\Concerns\CreatesCanonicalCurriculumContext;

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\AssessmentCurriculumMapping;
use App\Models\AtpAchievement;
use App\Models\AuditLog;
use App\Models\CpAchievement;
use App\Models\Curriculum;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptScore;
use App\Models\ExamAttemptScoreCurrent;
use App\Models\ExamAttemptAnswer;
use App\Models\Question;
use App\Models\QuestionVersion;
use App\Models\ExamParticipant;
use App\Models\ExamQuestionSnapshot;
use App\Models\ExamQuestionScore;
use App\Models\GradeLevel;
use App\Models\KktpVersion;
use App\Models\LearningObjective;
use App\Models\LearningOutcome;
use App\Models\LearningOutcomeVersion;
use App\Models\LearningPath;
use App\Models\LearningPathVersion;
use App\Models\Phase;
use App\Models\Role;
use App\Models\Rombel;
use App\Models\Semester;
use App\Models\StudentProfile;
use App\Models\Subject;
use App\Models\Tenant;
use App\Models\TeacherProfile;
use App\Models\TeacherSubjectAssignment;
use App\Models\TpAchievement;
use App\Models\User;
use App\Services\RolePermissionBootstrapper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class Phase7DHistoricalRecalculationAndAnalyticsTest extends TestCase
{
    use RefreshDatabase;
    use CreatesCanonicalTeacherAssignment;
    use CreatesCanonicalCurriculumContext;

    private Tenant $tenantA;
    private Tenant $tenantB;
    private User $adminA;
    private User $teacherA;
    private User $studentA;
    private User $studentB;
    private AcademicYear $academicYear;
    private Semester $semester;
    private Subject $subject;
    private Curriculum $curriculum;
    private Phase $phase;
    private Rombel $rombel;
    private TeacherSubjectAssignment $assignment;
    private array $canonicalContext = [];
    private LearningOutcome $outcome;
    private LearningObjective $tp;
    private LearningPath $atp;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantA = Tenant::create(['name' => 'MI Negeri 1 Malang', 'code' => 'MIN1']);
        $this->tenantB = Tenant::create(['name' => 'MI Negeri 2 Surabaya', 'code' => 'MIN2']);

        $this->setTenantContext($this->tenantA->id);

        $adminRole = Role::firstOrCreate(['name' => 'ADMIN_MADRASAH'], ['label' => 'Admin']);
        $teacherRole = Role::firstOrCreate(['name' => 'GURU'], ['label' => 'Guru']);
        $studentRole = Role::firstOrCreate(['name' => 'SISWA'], ['label' => 'Siswa']);
        app(RolePermissionBootstrapper::class)->bootstrap();

        $this->adminA = User::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Admin A',
            'email' => 'admin.a@min1.sch.id',
            'password' => bcrypt('password'),
        ]);
        $this->adminA->roles()->attach($adminRole);

        $this->teacherA = User::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Guru A',
            'email' => 'guru.a@min1.sch.id',
            'password' => bcrypt('password'),
        ]);
        $this->teacherA->roles()->attach($teacherRole);

        $this->studentA = User::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Siswa A',
            'email' => 'siswa.a@min1.sch.id',
            'password' => bcrypt('password'),
        ]);
        $this->studentA->roles()->attach($studentRole);

        $studentProfile = StudentProfile::create([
            'tenant_id' => $this->tenantA->id,
            'user_id' => $this->studentA->id,
            'full_name' => 'Siswa A',
            'nis' => '12345',
            'nisn' => '1234567890',
        ]);

        $this->academicYear = AcademicYear::create([
            'tenant_id' => $this->tenantA->id,
            'name' => '2025/2026',
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
            'status' => 'ACTIVE',
        ]);

        $this->semester = Semester::create([
            'tenant_id' => $this->tenantA->id,
            'academic_year_id' => $this->academicYear->id,
            'term' => 'GANJIL',
            'is_active' => true,
        ]);

        $this->subject = Subject::create([
            'tenant_id' => $this->tenantA->id,
            'code' => 'MATH',
            'name' => 'Matematika',
        ]);

        $this->curriculum = Curriculum::create(['tenant_id' => $this->tenantA->id, 'name' => 'Kurikulum Merdeka', 'code' => 'KM']);
        $this->phase = Phase::create(['tenant_id' => $this->tenantA->id, 'curriculum_id' => $this->curriculum->id, 'name' => 'Fase A', 'code' => 'A']);

        $gradeLevel = GradeLevel::create([
            'tenant_id' => $this->tenantA->id,
            'phase_id' => $this->phase->id,
            'code' => '1',
            'name' => 'Kelas 1',
            'level' => 1,
        ]);

        $this->rombel = Rombel::create([
            'tenant_id' => $this->tenantA->id,
            'academic_year_id' => $this->academicYear->id,
            'grade_level_id' => $gradeLevel->id,
            'name' => 'Kelas 1A',
        ]);
        $studentProfile->rombels()->attach($this->rombel->id, ['id' => (string)Str::uuid(), 'tenant_id' => $this->tenantA->id]);

        $teacherProfile = TeacherProfile::create([
            'tenant_id' => $this->tenantA->id,
            'user_id' => $this->teacherA->id,
            'full_name' => 'Guru A',
            'employee_identifier' => 'GURU-A-001',
        ]);
        $this->assignment = TeacherSubjectAssignment::create([
            'tenant_id' => $this->tenantA->id,
            'teacher_profile_id' => $teacherProfile->id,
            'subject_id' => $this->subject->id,
            'rombel_id' => $this->rombel->id,
            'academic_year_id' => $this->academicYear->id,
            'semester_id' => $this->semester->id,
            'starts_at' => now()->subDay()->toDateString(),
            'ends_at' => now()->addYears(5)->toDateString(),
            'status' => 'ACTIVE',
            'created_by' => $this->teacherA->id,
        ]);
        $this->canonicalContext = $this->createCanonicalCurriculumContext($this->assignment, $this->teacherA);

        $this->studentB = User::create([
            'tenant_id' => $this->tenantB->id,
            'name' => 'Siswa B',
            'email' => 'siswa.b@min2.sch.id',
            'password' => bcrypt('password'),
        ]);
        $this->studentB->roles()->attach($studentRole);

        $this->outcome = LearningOutcome::create([
            'tenant_id' => $this->tenantA->id,
            'curriculum_id' => $this->curriculum->id,
            'phase_id' => $this->phase->id,
            'subject_id' => $this->subject->id,
            'code' => 'CP_MATH_HIST',
        ]);

        $this->tp = LearningObjective::create([
            'tenant_id' => $this->tenantA->id,
            'learning_outcome_id' => $this->outcome->id,
            'code' => 'TP_MATH_HIST',
            'title' => 'Konsep Histori',
            'description' => 'Test Histori',
        ]);

        $this->atp = LearningPath::create([
            'tenant_id' => $this->tenantA->id,
            'curriculum_id' => $this->curriculum->id,
            'subject_id' => $this->subject->id,
            'phase_id' => $this->phase->id,
            'code' => 'ATP_MATH_HIST',
            'name' => 'Alur Histori',
        ]);
        $lpVer = LearningPathVersion::create([
            'tenant_id' => $this->tenantA->id,
            'learning_path_id' => $this->atp->id,
            'major_version' => 1,
            'minor_version' => 0,
            'is_current' => true,
        ]);
        $lpVer->objectives()->attach([
            $this->tp->id => ['tenant_id' => $this->tenantA->id, 'id' => (string)Str::uuid(), 'order_index' => 1],
        ]);

        KktpVersion::create([
            'tenant_id' => $this->tenantA->id,
            'learning_objective_id' => $this->tp->id,
            'version' => 1,
            'threshold' => 70.00,
        ]);
    }

    /**
     * Bind a historical assessment to the canonical context already assigned
     * in setUp(). Never replace the assignment's registration/version here:
     * existing TP/structure mappings have composite FKs to that exact scope.
     */
    private function bindAssessmentToExistingAssignmentContext(Assessment $assessment): void
    {
        $context = $this->canonicalContext;
        $assignment = $this->assignment->fresh();

        $assessment->forceFill([
            'teacher_subject_assignment_id' => $assignment->id,
            'tenant_curriculum_registration_id' => $context['registration']->id,
            'curriculum_version_id' => $context['version']->id,
            'curriculum_owner_id' => Tenant::SYSTEM_TENANT_ID,
            'curriculum_context_snapshot' => $assignment->curriculum_context_snapshot,
            'assessment_taxonomy_owner_id' => Tenant::SYSTEM_TENANT_ID,
            'assessment_function_option_id' => $context['taxonomy']['function']->id,
            'assessment_scope_option_id' => $context['taxonomy']['scope']->id,
            'instrument_type_option_id' => $context['taxonomy']['instrument']->id,
            'delivery_mode_option_id' => $context['taxonomy']['delivery']->id,
            'assessment_origin' => 'INTERNAL',
            'activity_name' => 'Kegiatan Asesmen PHPUnit',
        ])->save();

        AssessmentCurriculumMapping::query()->create([
            'tenant_id' => $this->tenantA->id,
            'assessment_id' => $assessment->id,
            'teacher_subject_assignment_id' => $assignment->id,
            'tenant_curriculum_registration_id' => $context['registration']->id,
            'multi_curriculum_version_id' => $context['version']->id,
            'curriculum_owner_id' => Tenant::SYSTEM_TENANT_ID,
            'multi_learning_outcome_id' => $context['cp']->id,
            'tenant_learning_outcome_id' => $context['tp']->id,
            'academic_year_id' => $this->academicYear->id,
            'semester_id' => $this->semester->id,
            'mapping_role' => 'PRIMARY',
            'weight' => 1,
            'is_primary' => true,
            'created_by' => $this->teacherA->id,
        ]);
    }

    /**
     * Test 1: Initial finalization calculates curriculum achievements for TP, ATP, and CP.
     */
    public function test_1_initial_finalization_calculates_curriculum_achievements(): array
    {
        $this->setTenantContext($this->tenantA->id);

        $ass = Assessment::create([
            'tenant_id' => $this->tenantA->id,
            'academic_year_id' => $this->academicYear->id,
            'semester_id' => $this->semester->id,
            'curriculum_id' => $this->curriculum->id,
            'subject_id' => $this->subject->id,
            'title' => 'Assesmen Formatif 1',
            'assessment_type' => 'FORMATIVE',
            'status' => 'PUBLISHED',
            'weight' => 2.00,
            'created_by' => $this->teacherA->id,
        ]);
        $this->bindAssessmentToExistingAssignmentContext($ass);
        $ass->learningObjectives()->attach($this->tp->id, ['tenant_id' => $this->tenantA->id, 'id' => (string)Str::uuid()]);

        $exam = Exam::create([
            'tenant_id' => $this->tenantA->id,
            'assessment_id' => $ass->id,
            'title' => 'Ujian Formatif 1',
            'duration_seconds' => 3600,
            'status' => 'LOCKED',
            'created_by' => $this->teacherA->id,
        ]);
        $part = ExamParticipant::create([
            'tenant_id' => $this->tenantA->id,
            'exam_id' => $exam->id,
            'user_id' => $this->studentA->id,
        ]);
        $attempt = ExamAttempt::create([
            'tenant_id' => $this->tenantA->id,
            'exam_id' => $exam->id,
            'participant_id' => $part->id,
            'attempt_number' => 1,
            'status' => 'SUBMITTED',
            'started_at' => now()->subHour(),
            'submitted_at' => now(),
        ]);

        // Reopen creates a new score version through ScoringService, so this
        // historical attempt must carry a valid immutable snapshot order.
        $question = Question::create([
            'tenant_id' => $this->tenantA->id,
            'created_by' => $this->teacherA->id,
        ]);
        $questionVersion = QuestionVersion::create([
            'tenant_id' => $this->tenantA->id,
            'question_id' => $question->id,
            'stem' => 'Jawaban uraian untuk rekalkulasi historis',
            'question_type' => 'ESSAY',
            'version_number' => 1,
            'status' => 'PUBLISHED',
            'created_by' => $this->teacherA->id,
        ]);
        $snapshot = $exam->snapshots()->create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $this->tenantA->id,
            'source_question_id' => $question->id,
            'source_question_version_id' => $questionVersion->id,
            'snapshot_stem' => 'Jawaban uraian untuk rekalkulasi historis',
            'snapshot_options' => [],
            'points' => 100,
            'metadata' => ['question_type' => 'ESSAY'],
        ]);
        $attempt->update(['question_order' => [(string) $snapshot->id]]);
        ExamAttemptAnswer::create([
            'tenant_id' => $this->tenantA->id,
            'exam_id' => $exam->id,
            'exam_attempt_id' => $attempt->id,
            'exam_question_snapshot_id' => $snapshot->id,
            'student_id' => $this->studentA->id,
            'answer_payload' => ['text' => 'Jawaban essay untuk uji rekalkulasi'],
            'answer_state' => 'ANSWERED',
        ]);
        $score = ExamAttemptScore::create([
            'tenant_id' => $this->tenantA->id,
            'attempt_id' => $attempt->id,
            'exam_id' => $exam->id,
            'student_id' => $this->studentA->id,
            'raw_score' => 60,
            'max_score' => 100,
            'percentage' => 60.00,
            'grading_status' => 'AUTO_SCORED',
            'scoring_version' => 1,
        ]);
        ExamAttemptScoreCurrent::create([
            'tenant_id' => $this->tenantA->id,
            'attempt_id' => $attempt->id,
            'exam_id' => $exam->id,
            'student_id' => $this->studentA->id,
            'current_score_id' => $score->id,
            'scoring_version' => 1,
        ]);

        $response = $this->actingAs($this->teacherA, 'sanctum')
            ->postJson("/api/v1/exams/{$exam->id}/attempts/{$attempt->id}/finalize");

        $response->assertStatus(200);
        $response->assertJsonPath('data.grading_status', 'FINALIZED');

        // Verify TP achievement calculation V1
        $tpAch = TpAchievement::where('student_id', $this->studentA->id)
            ->where('learning_objective_id', $this->tp->id)
            ->where('calculation_version', 1)
            ->first();

        $this->assertNotNull($tpAch);
        $this->assertEquals('60.00', (string)$tpAch->score);
        $this->assertEquals('BELUM_TERCAPAI', $tpAch->status); // 60 < 70
        $this->assertTrue($tpAch->is_current);

        // Verify ATP achievement V1
        $atpAch = AtpAchievement::where('student_id', $this->studentA->id)
            ->where('learning_path_id', $this->atp->id)
            ->where('calculation_version', 1)
            ->first();
        $this->assertNotNull($atpAch);
        $this->assertEquals('60.00', (string)$atpAch->score);

        // Verify CP achievement V1
        $cpAch = CpAchievement::where('student_id', $this->studentA->id)
            ->where('learning_outcome_id', $this->outcome->id)
            ->where('calculation_version', 1)
            ->first();
        $this->assertNotNull($cpAch);
        $this->assertEquals('60.00', (string)$cpAch->score);

        return ['exam' => $exam, 'attempt' => $attempt, 'ass' => $ass];
    }

    /**
     * Test 2, 3, 4, 5, 6, 7: Option A historical snapshot recalculation, reopen RBAC, version retention, and audit logs.
     */
    public function test_reopen_and_refinalization_uses_option_a_historical_snapshot(): void
    {
        $fixture = $this->test_1_initial_finalization_calculates_curriculum_achievements();
        $exam = $fixture['exam'];
        $attempt = $fixture['attempt'];

        // Step 2: Modify master mapping by adding a SECOND assessment to the TP in master tables
        $ass2 = Assessment::create([
            'tenant_id' => $this->tenantA->id,
            'academic_year_id' => $this->academicYear->id,
            'semester_id' => $this->semester->id,
            'curriculum_id' => $this->curriculum->id,
            'subject_id' => $this->subject->id,
            'title' => 'Assesmen Master Baru',
            'assessment_type' => 'FORMATIVE',
            'status' => 'PUBLISHED',
            'weight' => 5.00,
            'created_by' => $this->teacherA->id,
        ]);
        $this->bindAssessmentToExistingAssignmentContext($ass2);
        $ass2->learningObjectives()->attach($this->tp->id, ['tenant_id' => $this->tenantA->id, 'id' => (string)Str::uuid()]);

        // Step 3: Reopen attempt enforcement
        // Non-admin/guru (Student) cannot reopen
        $this->actingAs($this->studentA, 'sanctum')
            ->postJson("/api/v1/exams/{$exam->id}/attempts/{$attempt->id}/reopen", ['reason' => 'Ingin perbaikan'])
            ->assertStatus(403);

        // Empty reason rejected
        $this->actingAs($this->teacherA, 'sanctum')
            ->postJson("/api/v1/exams/{$exam->id}/attempts/{$attempt->id}/reopen", ['reason' => '   '])
            ->assertStatus(422);

        // Assigned Guru with valid reason reopens score
        $reopenResp = $this->actingAs($this->teacherA, 'sanctum')
            ->postJson("/api/v1/exams/{$exam->id}/attempts/{$attempt->id}/reopen", [
                'reason' => 'Koreksi penilaian guru atas sanggahan siswa'
            ]);
        $reopenResp->assertStatus(200);
        $reopenResp->assertJsonPath('data.scoring_version', 2);

        // Grade the reopened essay question through the authorized scoring API.
        // Finalization must derive the aggregate score from question-level marks.
        $questionScoreV2 = ExamQuestionScore::where('attempt_id', $attempt->id)
            ->where('scoring_version', 2)
            ->firstOrFail();
        $this->actingAs($this->teacherA, 'sanctum')
            ->putJson("/api/v1/exams/{$exam->id}/attempts/{$attempt->id}/grading/{$questionScoreV2->id}", [
                'awarded_score' => 90,
                'feedback' => 'Penilaian ulang setelah sanggahan',
            ])
            ->assertStatus(200);

        // Step 4 & 5: Re-finalize attempt version 2
        $finalV2Resp = $this->actingAs($this->teacherA, 'sanctum')
            ->postJson("/api/v1/exams/{$exam->id}/attempts/{$attempt->id}/finalize");
        $finalV2Resp->assertStatus(200);

        // Verify Version 1 TP achievement remains in DB with is_current = false
        $tpAchV1 = TpAchievement::where('student_id', $this->studentA->id)
            ->where('learning_objective_id', $this->tp->id)
            ->where('calculation_version', 1)
            ->first();
        $this->assertNotNull($tpAchV1);
        $this->assertEquals('60.00', (string)$tpAchV1->score);
        $this->assertFalse($tpAchV1->is_current);

        // Verify Version 2 TP achievement created with calculation_version = 2 and is_current = true
        $tpAchV2 = TpAchievement::where('student_id', $this->studentA->id)
            ->where('learning_objective_id', $this->tp->id)
            ->where('calculation_version', 2)
            ->first();
        $this->assertNotNull($tpAchV2);
        $this->assertTrue($tpAchV2->is_current);

        // Verify Option A: Version 2 uses ONLY historical mapped assessments from V1 (1 contribution, NOT 2)
        $this->assertEquals(1, $tpAchV2->contributions->count());
        $this->assertEquals('90.00', (string)$tpAchV2->score); // 90.00 >= 70.00
        $this->assertEquals('TERCAPAI', $tpAchV2->status);

        // Step 6: Verify derived ATP and CP recalculated for V2
        $atpAchV2 = AtpAchievement::where('student_id', $this->studentA->id)
            ->where('learning_path_id', $this->atp->id)
            ->where('calculation_version', 2)
            ->first();
        $this->assertNotNull($atpAchV2);
        $this->assertEquals('90.00', (string)$atpAchV2->score);
        $this->assertTrue($atpAchV2->is_current);

        $cpAchV2 = CpAchievement::where('student_id', $this->studentA->id)
            ->where('learning_outcome_id', $this->outcome->id)
            ->where('calculation_version', 2)
            ->first();
        $this->assertNotNull($cpAchV2);
        $this->assertEquals('90.00', (string)$cpAchV2->score);
        $this->assertTrue($cpAchV2->is_current);

        // Step 7: Verify AuditLog append-only history
        $reopenAudit = AuditLog::where('action', 'REOPEN_SCORE')->first();
        $this->assertNotNull($reopenAudit);
        $this->assertEquals('Koreksi penilaian guru atas sanggahan siswa', $reopenAudit->new_values['reason'] ?? null);

        $finalizeAuditCount = AuditLog::where('action', 'FINALIZE_SCORE')->count();
        $this->assertGreaterThanOrEqual(2, $finalizeAuditCount);
    }

    /**
     * Test 8: Cross-tenant access rejection on curriculum analytics API.
     */
    public function test_8_curriculum_analytics_api_rejects_cross_tenant_access(): void
    {
        // Authenticate with Student B (Tenant B) and request Tenant A student analytics
        $response = $this->actingAs($this->studentB, 'sanctum')
            ->getJson("/api/v1/analytics/curriculum/students/{$this->studentA->id}");

        $response->assertStatus(403);

        // Request Tenant A Rombel analytics
        $responseRombel = $this->actingAs($this->studentB, 'sanctum')
            ->getJson("/api/v1/analytics/curriculum/rombels/{$this->rombel->id}");

        $responseRombel->assertStatus(403);
    }

    /**
     * Test 9: Students can view only their own achievement summary; Admin
     * cannot access student grades or Guru curriculum analytics endpoints.
     */
    public function test_9_curriculum_analytics_api_enforces_portal_boundaries(): void
    {
        $this->test_1_initial_finalization_calculates_curriculum_achievements();

        $studentResponse = $this->actingAs($this->studentA, 'sanctum')
            ->getJson("/api/v1/analytics/curriculum/students/{$this->studentA->id}");

        $studentResponse->assertStatus(200);
        $studentResponse->assertJsonStructure([
            'success',
            'data' => [
                'student' => ['id', 'name'],
                'tp_achievements',
                'atp_achievements',
                'cp_achievements',
            ]
        ]);

        $this->actingAs($this->adminA, 'sanctum')
            ->getJson("/api/v1/analytics/curriculum/students/{$this->studentA->id}")
            ->assertStatus(403);

        $this->actingAs($this->adminA, 'sanctum')
            ->getJson("/api/v1/analytics/curriculum/rombels/{$this->rombel->id}")
            ->assertStatus(403);

        $this->actingAs($this->adminA, 'sanctum')
            ->getJson("/api/v1/analytics/curriculum/subjects/{$this->subject->id}")
            ->assertStatus(403);

        $this->actingAs($this->adminA, 'sanctum')
            ->getJson("/api/v1/analytics/curriculum/tps/{$this->tp->id}")
            ->assertStatus(403);
    }

    /**
     * Test 10: Transaction failure leaves no partial or inconsistent state.
     */
    public function test_10_transaction_failure_leaves_no_partial_state(): void
    {
        $this->setTenantContext($this->tenantA->id);

        $unconfigAss = Assessment::create([
            'tenant_id' => $this->tenantA->id,
            'academic_year_id' => $this->academicYear->id,
            'semester_id' => $this->semester->id,
            'curriculum_id' => $this->curriculum->id,
            'subject_id' => $this->subject->id,
            'teacher_subject_assignment_id' => $this->assignment->id,
            'tenant_curriculum_registration_id' => $this->assignment->tenant_curriculum_registration_id,
            'curriculum_version_id' => $this->assignment->multi_curriculum_version_id,
            'curriculum_owner_id' => $this->assignment->curriculum_owner_id,
            'curriculum_context_snapshot' => $this->assignment->curriculum_context_snapshot,
            'title' => 'Assesmen Bobot Kosong',
            'assessment_type' => 'FORMATIVE',
            'status' => 'PUBLISHED',
            'weight' => 0.00, // Invalid / unconfigured weight <= 0
            'created_by' => $this->teacherA->id,
        ]);
        $unconfigAss->learningObjectives()->attach($this->tp->id, ['tenant_id' => $this->tenantA->id, 'id' => (string)Str::uuid()]);

        $exam = Exam::create([
            'tenant_id' => $this->tenantA->id,
            'assessment_id' => $unconfigAss->id,
            'title' => 'Ujian Bobot Kosong',
            'duration_seconds' => 3600,
            'status' => 'LOCKED',
            'created_by' => $this->teacherA->id,
        ]);
        $part = ExamParticipant::create([
            'tenant_id' => $this->tenantA->id,
            'exam_id' => $exam->id,
            'user_id' => $this->studentA->id,
        ]);
        $attempt = ExamAttempt::create([
            'tenant_id' => $this->tenantA->id,
            'exam_id' => $exam->id,
            'participant_id' => $part->id,
            'attempt_number' => 1,
            'status' => 'SUBMITTED',
            'started_at' => now()->subHour(),
            'submitted_at' => now(),
        ]);
        $score = ExamAttemptScore::create([
            'tenant_id' => $this->tenantA->id,
            'attempt_id' => $attempt->id,
            'exam_id' => $exam->id,
            'student_id' => $this->studentA->id,
            'raw_score' => 85,
            'max_score' => 100,
            'percentage' => 85.00,
            'grading_status' => 'AUTO_SCORED',
            'scoring_version' => 1,
        ]);
        ExamAttemptScoreCurrent::create([
            'tenant_id' => $this->tenantA->id,
            'attempt_id' => $attempt->id,
            'exam_id' => $exam->id,
            'student_id' => $this->studentA->id,
            'current_score_id' => $score->id,
            'scoring_version' => 1,
        ]);

        $response = $this->actingAs($this->teacherA, 'sanctum')
            ->postJson("/api/v1/exams/{$exam->id}/attempts/{$attempt->id}/finalize");

        // Fails with HTTP 422
        $response->assertStatus(422);

        // Score remains AUTO_SCORED (rolled back completely)
        $reloadedScore = ExamAttemptScore::find($score->id);
        $this->assertEquals('AUTO_SCORED', $reloadedScore->grading_status);

        // Zero achievements created
        $tpCount = TpAchievement::where('student_id', $this->studentA->id)->count();
        $this->assertEquals(0, $tpCount);
    }

    /**
     * P0-D regression: Guru TP analytics derives subject from the linked CP,
     * then enforces the exact active teacher assignment scope.
     */
    public function test_teacher_tp_analytics_uses_subject_from_linked_learning_outcome(): void
    {
        $this->test_1_initial_finalization_calculates_curriculum_achievements();

        $response = $this->actingAs($this->teacherA, 'sanctum')
            ->getJson("/api/v1/analytics/curriculum/tps/{$this->tp->id}?" . http_build_query([
                'academic_year_id' => $this->academicYear->id,
                'semester_id' => $this->semester->id,
            ]));

        $response->assertStatus(200);
        $response->assertJsonPath('data.tp.id', $this->tp->id);
        $response->assertJsonPath('data.summary.total_students', 1);
    }

}
