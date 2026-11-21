<?php

namespace Tests\Feature;

use App\Contexts\TenantContext;
use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\AtpAchievement;
use App\Models\CpAchievement;
use App\Models\Curriculum;
use App\Models\Element;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptScore;
use App\Models\ExamAttemptScoreCurrent;
use App\Models\ExamParticipant;
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
use App\Models\Subject;
use App\Models\Tenant;
use App\Models\TpAchievement;
use App\Models\User;
use App\Services\CurriculumAchievementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tests\TestCase;

class Phase7DCurriculumAchievementTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenantA;
    private Tenant $tenantB;
    private User $studentA;
    private User $studentB;
    private User $teacherA;
    private AcademicYear $academicYear;
    private Semester $semester;
    private Subject $subject;
    private CurriculumAchievementService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantA = Tenant::create(['name' => 'MI Negeri 1 Malang', 'code' => 'MIN1']);
        $this->tenantB = Tenant::create(['name' => 'MI Negeri 2 Surabaya', 'code' => 'MIN2']);

        $this->setTenantContext($this->tenantA->id);

        $studentRole = Role::firstOrCreate(['name' => 'SISWA'], ['label' => 'Siswa']);
        $teacherRole = Role::firstOrCreate(['name' => 'GURU'], ['label' => 'Guru']);

        $this->studentA = User::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Siswa A',
            'email' => 'siswa.a@min1.sch.id',
            'password' => bcrypt('password'),
        ]);
        $this->studentA->roles()->attach($studentRole);

        $this->teacherA = User::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Guru A',
            'email' => 'guru.a@min1.sch.id',
            'password' => bcrypt('password'),
        ]);
        $this->teacherA->roles()->attach($teacherRole);

        $this->studentB = User::create([
            'tenant_id' => $this->tenantB->id,
            'name' => 'Siswa B',
            'email' => 'siswa.b@min2.sch.id',
            'password' => bcrypt('password'),
        ]);
        $this->studentB->roles()->attach($studentRole);

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

        $this->service = new CurriculumAchievementService();
    }

    /**
     * Test AC-7D.1: Weighted Assessment -> TP calculation and 2-decimal rounding.
     */
    public function test_tp_achievement_weighted_calculation_and_rounding(): void
    {
        $this->setTenantContext($this->tenantA->id);

        $curriculum = Curriculum::create(['tenant_id' => $this->tenantA->id, 'name' => 'Kurikulum Merdeka', 'code' => 'KM']);
        $phase = Phase::create(['tenant_id' => $this->tenantA->id, 'curriculum_id' => $curriculum->id, 'name' => 'Fase A', 'code' => 'A']);

        $outcome = LearningOutcome::create([
            'tenant_id' => $this->tenantA->id,
            'curriculum_id' => $curriculum->id,
            'phase_id' => $phase->id,
            'subject_id' => $this->subject->id,
            'code' => 'CP_MATH_1',
        ]);

        $tp = LearningObjective::create([
            'tenant_id' => $this->tenantA->id,
            'learning_outcome_id' => $outcome->id,
            'code' => 'TP_MATH_1',
            'title' => 'Mengenal Bilangan Cacah',
            'description' => 'Siswa dapat mengenal bilangan 1-100',
        ]);

        // Create 2 Assessments with weights 2.00 and 3.00
        $ass1 = Assessment::create([
            'tenant_id' => $this->tenantA->id,
            'academic_year_id' => $this->academicYear->id,
            'semester_id' => $this->semester->id,
            'curriculum_id' => $curriculum->id,
            'subject_id' => $this->subject->id,
            'title' => 'Assesmen Harian 1',
            'assessment_type' => 'FORMATIVE',
            'status' => 'PUBLISHED',
            'weight' => 2.00,
            'created_by' => $this->teacherA->id,
        ]);
        $ass1->learningObjectives()->attach($tp->id, ['tenant_id' => $this->tenantA->id, 'id' => (string)Str::uuid()]);

        $ass2 = Assessment::create([
            'tenant_id' => $this->tenantA->id,
            'academic_year_id' => $this->academicYear->id,
            'semester_id' => $this->semester->id,
            'curriculum_id' => $curriculum->id,
            'subject_id' => $this->subject->id,
            'title' => 'Sumatif Akhir Bab',
            'assessment_type' => 'SUMMATIVE',
            'status' => 'PUBLISHED',
            'weight' => 3.00,
            'created_by' => $this->teacherA->id,
        ]);
        $ass2->learningObjectives()->attach($tp->id, ['tenant_id' => $this->tenantA->id, 'id' => (string)Str::uuid()]);

        // Exam for Ass 1 (Score = 80%)
        $exam1 = Exam::create([
            'tenant_id' => $this->tenantA->id,
            'assessment_id' => $ass1->id,
            'title' => 'Ujian AH1',
            'duration_seconds' => 3600,
            'status' => 'LOCKED',
            'created_by' => $this->teacherA->id,
        ]);
        $part1 = ExamParticipant::create([
            'tenant_id' => $this->tenantA->id,
            'exam_id' => $exam1->id,
            'user_id' => $this->studentA->id,
        ]);
        $attempt1 = ExamAttempt::create([
            'tenant_id' => $this->tenantA->id,
            'exam_id' => $exam1->id,
            'participant_id' => $part1->id,
            'attempt_number' => 1,
            'status' => 'SUBMITTED',
            'started_at' => now()->subHour(),
            'submitted_at' => now(),
        ]);
        $score1 = ExamAttemptScore::create([
            'tenant_id' => $this->tenantA->id,
            'attempt_id' => $attempt1->id,
            'exam_id' => $exam1->id,
            'student_id' => $this->studentA->id,
            'raw_score' => 80,
            'max_score' => 100,
            'percentage' => 80.00,
            'grading_status' => 'FINALIZED',
            'scoring_version' => 1,
        ]);
        ExamAttemptScoreCurrent::create([
            'tenant_id' => $this->tenantA->id,
            'attempt_id' => $attempt1->id,
            'exam_id' => $exam1->id,
            'student_id' => $this->studentA->id,
            'current_score_id' => $score1->id,
            'scoring_version' => 1,
        ]);

        // Exam for Ass 2 (Score = 60%)
        $exam2 = Exam::create([
            'tenant_id' => $this->tenantA->id,
            'assessment_id' => $ass2->id,
            'title' => 'Ujian SAB',
            'duration_seconds' => 3600,
            'status' => 'LOCKED',
            'created_by' => $this->teacherA->id,
        ]);
        $part2 = ExamParticipant::create([
            'tenant_id' => $this->tenantA->id,
            'exam_id' => $exam2->id,
            'user_id' => $this->studentA->id,
        ]);
        $attempt2 = ExamAttempt::create([
            'tenant_id' => $this->tenantA->id,
            'exam_id' => $exam2->id,
            'participant_id' => $part2->id,
            'attempt_number' => 1,
            'status' => 'SUBMITTED',
            'started_at' => now()->subHour(),
            'submitted_at' => now(),
        ]);
        $score2 = ExamAttemptScore::create([
            'tenant_id' => $this->tenantA->id,
            'attempt_id' => $attempt2->id,
            'exam_id' => $exam2->id,
            'student_id' => $this->studentA->id,
            'raw_score' => 60,
            'max_score' => 100,
            'percentage' => 60.00,
            'grading_status' => 'FINALIZED',
            'scoring_version' => 1,
        ]);
        ExamAttemptScoreCurrent::create([
            'tenant_id' => $this->tenantA->id,
            'attempt_id' => $attempt2->id,
            'exam_id' => $exam2->id,
            'student_id' => $this->studentA->id,
            'current_score_id' => $score2->id,
            'scoring_version' => 1,
        ]);

        // Set KKTP = 70.00
        KktpVersion::create([
            'tenant_id' => $this->tenantA->id,
            'learning_objective_id' => $tp->id,
            'version' => 1,
            'threshold' => 70.00,
        ]);

        // Calculate TP achievement
        // Formula: (80*2 + 60*3) / 5 = (160 + 180) / 5 = 340 / 5 = 68.00
        $tpAch = $this->service->calculateTpAchievement(
            $this->studentA, $tp, $this->academicYear, $this->semester, $this->subject
        );

        $this->assertNotNull($tpAch);
        $this->assertEquals('68.00', (string)$tpAch->score);
        $this->assertEquals('BELUM_TERCAPAI', $tpAch->status); // 68.00 < 70.00
        $this->assertEquals(2, $tpAch->contributions->count());
    }

    /**
     * Test AC-7D.2: Rejection on missing or invalid Assessment weight.
     */
    public function test_invalid_or_missing_weight_rejected(): void
    {
        $this->setTenantContext($this->tenantA->id);

        $curriculum = Curriculum::create(['tenant_id' => $this->tenantA->id, 'name' => 'KM', 'code' => 'KM']);
        $phase = Phase::create(['tenant_id' => $this->tenantA->id, 'curriculum_id' => $curriculum->id, 'name' => 'Fase A', 'code' => 'A']);
        $outcome = LearningOutcome::create([
            'tenant_id' => $this->tenantA->id,
            'curriculum_id' => $curriculum->id,
            'phase_id' => $phase->id,
            'subject_id' => $this->subject->id,
            'code' => 'CP_MATH_2',
        ]);
        $tp = LearningObjective::create([
            'tenant_id' => $this->tenantA->id,
            'learning_outcome_id' => $outcome->id,
            'code' => 'TP_MATH_2',
            'title' => 'Operasi Penjumlahan',
            'description' => 'Penjumlahan 1-10',
        ]);

        $invalidAss = Assessment::create([
            'tenant_id' => $this->tenantA->id,
            'academic_year_id' => $this->academicYear->id,
            'semester_id' => $this->semester->id,
            'curriculum_id' => $curriculum->id,
            'subject_id' => $this->subject->id,
            'title' => 'Tugas',
            'assessment_type' => 'FORMATIVE',
            'status' => 'PUBLISHED',
            'weight' => 0.00, // Invalid weight <= 0
            'created_by' => $this->teacherA->id,
        ]);
        $invalidAss->learningObjectives()->attach($tp->id, ['tenant_id' => $this->tenantA->id, 'id' => (string)Str::uuid()]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Assessment [{$invalidAss->id}] has invalid or unconfigured weight.");

        $this->service->calculateTpAchievement(
            $this->studentA, $tp, $this->academicYear, $this->semester, $this->subject
        );
    }

    /**
     * Test AC-7D.3: Completeness Gate yields BELUM_DINILAI when contributor is unfinalized.
     */
    public function test_completeness_gate_yields_belum_dinilai_when_contributor_unfinalized(): void
    {
        $this->setTenantContext($this->tenantA->id);

        $curriculum = Curriculum::create(['tenant_id' => $this->tenantA->id, 'name' => 'KM', 'code' => 'KM']);
        $phase = Phase::create(['tenant_id' => $this->tenantA->id, 'curriculum_id' => $curriculum->id, 'name' => 'Fase A', 'code' => 'A']);
        $outcome = LearningOutcome::create([
            'tenant_id' => $this->tenantA->id,
            'curriculum_id' => $curriculum->id,
            'phase_id' => $phase->id,
            'subject_id' => $this->subject->id,
            'code' => 'CP_MATH_3',
        ]);
        $tp = LearningObjective::create([
            'tenant_id' => $this->tenantA->id,
            'learning_outcome_id' => $outcome->id,
            'code' => 'TP_MATH_3',
            'title' => 'Pengurangan',
            'description' => 'Pengurangan 1-10',
        ]);

        $ass = Assessment::create([
            'tenant_id' => $this->tenantA->id,
            'academic_year_id' => $this->academicYear->id,
            'semester_id' => $this->semester->id,
            'curriculum_id' => $curriculum->id,
            'subject_id' => $this->subject->id,
            'title' => 'Formatif Pengurangan',
            'assessment_type' => 'FORMATIVE',
            'status' => 'PUBLISHED',
            'weight' => 1.00,
            'created_by' => $this->teacherA->id,
        ]);
        $ass->learningObjectives()->attach($tp->id, ['tenant_id' => $this->tenantA->id, 'id' => (string)Str::uuid()]);

        // Exam with score in AUTO_SCORED state (not FINALIZED)
        $exam = Exam::create([
            'tenant_id' => $this->tenantA->id,
            'assessment_id' => $ass->id,
            'title' => 'Ujian Unfinalized',
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
            'raw_score' => 90,
            'max_score' => 100,
            'percentage' => 90.00,
            'grading_status' => 'AUTO_SCORED', // NOT FINALIZED
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

        $tpAch = $this->service->calculateTpAchievement(
            $this->studentA, $tp, $this->academicYear, $this->semester, $this->subject
        );

        $this->assertNotNull($tpAch);
        $this->assertNull($tpAch->score);
        $this->assertEquals('BELUM_DINILAI', $tpAch->status);
    }

    /**
     * Test AC-7D.4: Equal arithmetic mean aggregation for ATP and CP.
     */
    public function test_atp_and_cp_arithmetic_mean_aggregation(): void
    {
        $this->setTenantContext($this->tenantA->id);

        $curriculum = Curriculum::create(['tenant_id' => $this->tenantA->id, 'name' => 'KM', 'code' => 'KM']);
        $phase = Phase::create(['tenant_id' => $this->tenantA->id, 'curriculum_id' => $curriculum->id, 'name' => 'Fase A', 'code' => 'A']);
        $outcome = LearningOutcome::create([
            'tenant_id' => $this->tenantA->id,
            'curriculum_id' => $curriculum->id,
            'phase_id' => $phase->id,
            'subject_id' => $this->subject->id,
            'code' => 'CP_MATH_4',
        ]);

        $tp1 = LearningObjective::create([
            'tenant_id' => $this->tenantA->id,
            'learning_outcome_id' => $outcome->id,
            'code' => 'TP_MATH_4A',
            'title' => 'Geometri 1',
            'description' => 'Mengenal Bangun Datar',
        ]);
        $tp2 = LearningObjective::create([
            'tenant_id' => $this->tenantA->id,
            'learning_outcome_id' => $outcome->id,
            'code' => 'TP_MATH_4B',
            'title' => 'Geometri 2',
            'description' => 'Mengenal Bangun Ruang',
        ]);

        // Directly persist two complete TP achievements: TP1 = 80.00 (TERCAPAI), TP2 = 90.00 (TERCAPAI)
        TpAchievement::create([
            'tenant_id' => $this->tenantA->id,
            'student_id' => $this->studentA->id,
            'learning_objective_id' => $tp1->id,
            'academic_year_id' => $this->academicYear->id,
            'semester_id' => $this->semester->id,
            'subject_id' => $this->subject->id,
            'kktp_threshold_snapshot' => 70.00,
            'score' => 80.00,
            'status' => 'TERCAPAI',
            'calculation_version' => 1,
            'is_current' => true,
            'calculated_at' => now(),
        ]);
        TpAchievement::create([
            'tenant_id' => $this->tenantA->id,
            'student_id' => $this->studentA->id,
            'learning_objective_id' => $tp2->id,
            'academic_year_id' => $this->academicYear->id,
            'semester_id' => $this->semester->id,
            'subject_id' => $this->subject->id,
            'kktp_threshold_snapshot' => 70.00,
            'score' => 90.00,
            'status' => 'TERCAPAI',
            'calculation_version' => 1,
            'is_current' => true,
            'calculated_at' => now(),
        ]);

        // Learning Path (ATP)
        $atp = LearningPath::create([
            'tenant_id' => $this->tenantA->id,
            'curriculum_id' => $curriculum->id,
            'subject_id' => $this->subject->id,
            'phase_id' => $phase->id,
            'code' => 'ATP_MATH_1',
            'name' => 'Alur Geometri',
        ]);
        $lpVer = LearningPathVersion::create([
            'tenant_id' => $this->tenantA->id,
            'learning_path_id' => $atp->id,
            'major_version' => 1,
            'minor_version' => 0,
            'is_current' => true,
        ]);
        $lpVer->objectives()->attach([
            $tp1->id => ['tenant_id' => $this->tenantA->id, 'id' => (string)Str::uuid(), 'order_index' => 1],
            $tp2->id => ['tenant_id' => $this->tenantA->id, 'id' => (string)Str::uuid(), 'order_index' => 2],
        ]);

        // Calculate ATP: mean(80, 90) = 85.00
        $atpAch = $this->service->calculateAtpAchievement(
            $this->studentA, $atp, $this->academicYear, $this->semester, $this->subject
        );

        $this->assertNotNull($atpAch);
        $this->assertEquals('85.00', (string)$atpAch->score);
        $this->assertEquals('TERCAPAI', $atpAch->status);

        // Calculate CP: mean(85.00) = 85.00
        $cpAch = $this->service->calculateCpAchievement(
            $this->studentA, $outcome, $this->academicYear, $this->semester, $this->subject
        );

        $this->assertNotNull($cpAch);
        $this->assertEquals('85.00', (string)$cpAch->score);
        $this->assertEquals('TERCAPAI', $cpAch->status);
    }

    /**
     * Test AC-7D.5a: Historical master config immutability.
     */
    public function test_historical_master_config_immutability(): void
    {
        $this->setTenantContext($this->tenantA->id);

        $curriculum = Curriculum::create(['tenant_id' => $this->tenantA->id, 'name' => 'KM', 'code' => 'KM']);
        $phase = Phase::create(['tenant_id' => $this->tenantA->id, 'curriculum_id' => $curriculum->id, 'name' => 'Fase A', 'code' => 'A']);
        $outcome = LearningOutcome::create([
            'tenant_id' => $this->tenantA->id,
            'curriculum_id' => $curriculum->id,
            'phase_id' => $phase->id,
            'subject_id' => $this->subject->id,
            'code' => 'CP_MATH_5',
        ]);
        $tp = LearningObjective::create([
            'tenant_id' => $this->tenantA->id,
            'learning_outcome_id' => $outcome->id,
            'code' => 'TP_MATH_5',
            'title' => 'Pengukuran',
            'description' => 'Mengenal Panjang',
        ]);

        $kktpV1 = KktpVersion::create([
            'tenant_id' => $this->tenantA->id,
            'learning_objective_id' => $tp->id,
            'version' => 1,
            'threshold' => 70.00,
        ]);

        $ass = Assessment::create([
            'tenant_id' => $this->tenantA->id,
            'academic_year_id' => $this->academicYear->id,
            'semester_id' => $this->semester->id,
            'curriculum_id' => $curriculum->id,
            'subject_id' => $this->subject->id,
            'title' => 'Tugas Pengukuran',
            'assessment_type' => 'FORMATIVE',
            'status' => 'PUBLISHED',
            'weight' => 2.00,
            'created_by' => $this->teacherA->id,
        ]);
        $ass->learningObjectives()->attach($tp->id, ['tenant_id' => $this->tenantA->id, 'id' => (string)Str::uuid()]);

        $exam = Exam::create([
            'tenant_id' => $this->tenantA->id,
            'assessment_id' => $ass->id,
            'title' => 'Ujian Pengukuran',
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
            'raw_score' => 75,
            'max_score' => 100,
            'percentage' => 75.00,
            'grading_status' => 'FINALIZED',
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

        // Initial achievement calculation
        $tpAch1 = $this->service->calculateTpAchievement(
            $this->studentA, $tp, $this->academicYear, $this->semester, $this->subject
        );

        $this->assertEquals('75.00', (string)$tpAch1->score);
        $this->assertEquals('70.00', (string)$tpAch1->kktp_threshold_snapshot);
        $this->assertEquals('TERCAPAI', $tpAch1->status);

        // Update master KKTP version to 80.00 and Assessment Weight to 10.00 in master tables
        KktpVersion::create([
            'tenant_id' => $this->tenantA->id,
            'learning_objective_id' => $tp->id,
            'version' => 2,
            'threshold' => 80.00,
        ]);
        $ass->update(['weight' => 10.00]);

        // Fetch past finalized tpAch1 record directly from DB
        $reloadedTpAch1 = TpAchievement::find($tpAch1->id);

        // Verify past record is NOT mutated
        $this->assertEquals('75.00', (string)$reloadedTpAch1->score);
        $this->assertEquals('70.00', (string)$reloadedTpAch1->kktp_threshold_snapshot);
        $this->assertEquals('TERCAPAI', $reloadedTpAch1->status);
    }

    /**
     * Test Section 5 / AC-7D.8: Multi-tenant isolation on achievements.
     */
    public function test_multi_tenant_isolation_on_curriculum_achievements(): void
    {
        $this->setTenantContext($this->tenantA->id);

        $curriculum = Curriculum::create(['tenant_id' => $this->tenantA->id, 'name' => 'KM', 'code' => 'KM']);
        $phase = Phase::create(['tenant_id' => $this->tenantA->id, 'curriculum_id' => $curriculum->id, 'name' => 'Fase A', 'code' => 'A']);
        $outcome = LearningOutcome::create([
            'tenant_id' => $this->tenantA->id,
            'curriculum_id' => $curriculum->id,
            'phase_id' => $phase->id,
            'subject_id' => $this->subject->id,
            'code' => 'CP_MATH_ISOL',
        ]);
        $tp = LearningObjective::create([
            'tenant_id' => $this->tenantA->id,
            'learning_outcome_id' => $outcome->id,
            'code' => 'TP_MATH_ISOL',
            'title' => 'Isolasi',
            'description' => 'Test Isolasi',
        ]);

        TpAchievement::create([
            'tenant_id' => $this->tenantA->id,
            'student_id' => $this->studentA->id,
            'learning_objective_id' => $tp->id,
            'academic_year_id' => $this->academicYear->id,
            'semester_id' => $this->semester->id,
            'subject_id' => $this->subject->id,
            'kktp_threshold_snapshot' => 70.00,
            'score' => 85.00,
            'status' => 'TERCAPAI',
            'calculation_version' => 1,
            'is_current' => true,
            'calculated_at' => now(),
        ]);

        // Switch tenant context to Tenant B
        $this->setTenantContext($this->tenantB->id);

        // Querying TpAchievement under Tenant B must return 0 records
        $achievementsTenantB = TpAchievement::where('student_id', $this->studentA->id)->get();
        $this->assertTrue($achievementsTenantB->isEmpty());
    }
}
