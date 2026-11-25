<?php

namespace Tests\Feature;

use Tests\Concerns\CreatesCanonicalTeacherAssignment;

use App\Contexts\TenantContext;
use App\Models\AcademicYear;
use App\Models\Assessment;
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
use Tests\Support\CreatesTeacherExamScope;
use Tests\TestCase;

class Phase7AScoringTest extends TestCase
{
    use RefreshDatabase;
    use CreatesTeacherExamScope;
    use CreatesCanonicalTeacherAssignment;

    private Tenant $tenant;
    private User $teacher;
    private User $student;
    private Exam $exam;
    private ExamAttempt $attempt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create(['name' => 'Madrasah A', 'code' => 'MADA']);
        app(TenantContext::class)->setTenantId($this->tenant->id);

        $guruRole = Role::firstOrCreate(['name' => 'GURU'], ['label' => 'Guru']);
        $siswaRole = Role::firstOrCreate(['name' => 'SISWA'], ['label' => 'Siswa']);

        $this->teacher = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Guru Penguji',
            'email' => 'guru.phase7a@m.com',
            'password' => bcrypt('password'),
        ]);
        $this->teacher->roles()->attach($guruRole);

        $this->student = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Siswa Test',
            'email' => 'siswa.phase7a@m.com',
            'password' => bcrypt('password'),
        ]);
        $this->student->roles()->attach($siswaRole);

        $scope = $this->createTeacherExamScope($this->tenant, $this->teacher, [$this->student], 'PHASE7A');

        $assessment = Assessment::create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $scope['academicYear']->id,
            'subject_id' => $scope['subject']->id,
            'semester_id' => $scope['semester']->id,
            'title' => 'Assesmen Harian 1',
            'assessment_type' => 'DAILY',
            'created_by' => $this->teacher->id,
        ]);
        $this->bindCanonicalTeacherAssignment($scope['assignment'], $assessment);

        $this->exam = Exam::create([
            'tenant_id' => $this->tenant->id,
            'assessment_id' => $assessment->id,
            'title' => 'Ujian Phase 7A',
            'duration_seconds' => 3600,
            'created_by' => $this->teacher->id,
            'status' => 'SCHEDULED',
            'start_at' => now()->subHour(),
            'end_at' => now()->addHour(),
            'locked_at' => now(),
            'show_result' => true,
        ]);

        $participant = $this->exam->participants()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->student->id,
        ]);

        $this->attempt = ExamAttempt::create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'participant_id' => $participant->id,
            'attempt_number' => 1,
            'status' => 'SUBMITTED',
            'started_at' => now()->subMinutes(30),
            'expires_at' => now()->addMinutes(30),
            'submitted_at' => now(),
        ]);

        $this->bootstrapCbtRolePermissions();
    }

    /** A. AUTOMATIC SCORING **/

    public function test_mc_scoring_correct_and_incorrect()
    {
        $opt1Id = (string) Str::uuid();
        $opt2Id = (string) Str::uuid();

        $snapshot = $this->createSnapshot('MC', [
            ['id' => $opt1Id, 'content' => 'Option A', 'is_correct' => true],
            ['id' => $opt2Id, 'content' => 'Option B', 'is_correct' => false],
        ], 10);

        // Correct answer
        $this->submitAnswer($snapshot->id, ['selected_option_id' => $opt1Id]);
        $scoringService = new ScoringService();
        $score = $scoringService->scoreAttempt($this->attempt);

        $this->assertEquals(10, $score->raw_score);
        $this->assertEquals(100, $score->percentage);
        $this->assertEquals('AUTO_SCORED', $score->grading_status);

        // Update to incorrect answer
        ExamAttemptAnswer::where('exam_question_snapshot_id', $snapshot->id)->update([
            'answer_payload' => ['selected_option_id' => $opt2Id],
        ]);

        $score = $scoringService->scoreAttempt($this->attempt, true);
        $this->assertEquals(0, $score->raw_score);
        $this->assertEquals(0, $score->percentage);
    }

    public function test_true_false_scoring()
    {
        $trueOptId = (string) Str::uuid();
        $falseOptId = (string) Str::uuid();

        $snapshot = $this->createSnapshot('TRUE_FALSE', [
            ['id' => $trueOptId, 'content' => 'Benar', 'is_correct' => true],
            ['id' => $falseOptId, 'content' => 'Salah', 'is_correct' => false],
        ], 5);

        $this->submitAnswer($snapshot->id, ['selected_option_id' => $trueOptId]);
        $score = (new ScoringService())->scoreAttempt($this->attempt);

        $this->assertEquals(5, $score->raw_score);
        $this->assertEquals(100, $score->percentage);
    }

    public function test_multiple_response_partial_scoring()
    {
        $opt1 = (string) Str::uuid();
        $opt2 = (string) Str::uuid();
        $opt3 = (string) Str::uuid();

        $snapshot = $this->createSnapshot('MULTIPLE_RESPONSE', [
            ['id' => $opt1, 'content' => 'C1', 'is_correct' => true],
            ['id' => $opt2, 'content' => 'C2', 'is_correct' => true],
            ['id' => $opt3, 'content' => 'W1', 'is_correct' => false],
        ], 10);

        // Select 1 of 2 correct options
        $this->submitAnswer($snapshot->id, ['selected_option_ids' => [$opt1]]);
        $score = (new ScoringService())->scoreAttempt($this->attempt);
        $this->assertEquals(5, $score->raw_score);

        // Select 1 correct and 1 wrong option -> 0
        ExamAttemptAnswer::where('exam_question_snapshot_id', $snapshot->id)->update([
            'answer_payload' => ['selected_option_ids' => [$opt1, $opt3]],
        ]);
        $score = (new ScoringService())->scoreAttempt($this->attempt, true);
        $this->assertEquals(0, $score->raw_score);
    }

    public function test_matching_proportional_scoring()
    {
        $snapshot = $this->createSnapshot('MATCHING', [
            ['id' => 'L1', 'content' => 'Left 1', 'option_key' => 'pair_1'],
            ['id' => 'R1', 'content' => 'Right 1', 'matching_key' => 'pair_1'],
            ['id' => 'L2', 'content' => 'Left 2', 'option_key' => 'pair_2'],
            ['id' => 'R2', 'content' => 'Right 2', 'matching_key' => 'pair_2'],
        ], 10);

        // 1 correct pair, 1 incorrect pair
        $this->submitAnswer($snapshot->id, [
            'matches' => [
                ['left_id' => 'L1', 'right_id' => 'R1'],
                ['left_id' => 'L2', 'right_id' => 'R1'],
            ],
        ]);

        $score = (new ScoringService())->scoreAttempt($this->attempt);
        $this->assertEquals(5, $score->raw_score);
    }

    public function test_short_answer_whitespace_and_case_normalization()
    {
        $q = Question::create(['tenant_id' => $this->tenant->id, 'created_by' => $this->teacher->id]);
        $v = QuestionVersion::create([
            'tenant_id' => $this->tenant->id,
            'question_id' => $q->id,
            'stem' => 'Ibu kota Indonesia?',
            'question_type' => 'SHORT_ANSWER',
            'version_number' => 1,
            'status' => 'PUBLISHED',
            'created_by' => $this->teacher->id,
        ]);

        $snapshot = $this->createSnapshotRecord([
            'id' => (string) Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'source_question_id' => $q->id,
            'source_question_version_id' => $v->id,
            'snapshot_stem' => 'Ibu kota Indonesia?',
            'snapshot_options' => [],
            'points' => 10,
            'metadata' => [
                'question_type' => 'SHORT_ANSWER',
                'accepted_answers' => ['Jakarta', 'DKI Jakarta'],
            ],
        ]);

        $this->submitAnswer($snapshot->id, ['text' => '  jaKaRTa  ']);
        $score = (new ScoringService())->scoreAttempt($this->attempt);

        $this->assertEquals(10, $score->raw_score);
    }

    public function test_essay_initial_state_manual_review()
    {
        $snapshot = $this->createSnapshot('ESSAY', [], 20);
        $this->submitAnswer($snapshot->id, ['text' => 'Jawaban essay siswa']);

        $score = (new ScoringService())->scoreAttempt($this->attempt);
        $this->assertEquals(0, $score->raw_score);
        $this->assertEquals('MANUAL_REVIEW', $score->grading_status);

        $qScore = ExamQuestionScore::where('snapshot_id', $snapshot->id)->first();
        $this->assertEquals('MANUAL_REVIEW', $qScore->scoring_status);
        $this->assertEquals(0, $qScore->awarded_score);
    }


    public function test_scoring_uses_only_snapshots_assigned_in_attempt_question_order()
    {
        $assigned = $this->createSnapshot('MC', [['content' => 'Assigned', 'is_correct' => true]], 10);
        $unassigned = $this->createSnapshot('MC', [['content' => 'Unassigned', 'is_correct' => true]], 90);

        $this->attempt->update(['question_order' => [(string) $assigned->id]]);
        $this->submitAnswer($assigned->id, ['selected_option_id' => $assigned->snapshot_options[0]['id']]);
        // Simulate a corrupted/forged answer row for a snapshot from the same exam
        // that was never assigned to this attempt. Scoring must ignore it.
        $this->submitAnswer($unassigned->id, ['selected_option_id' => $unassigned->snapshot_options[0]['id']]);

        $score = (new ScoringService())->scoreAttempt($this->attempt);

        $this->assertEquals(10, (float) $score->raw_score);
        $this->assertEquals(10, (float) $score->max_score);
        $this->assertSame(1, ExamQuestionScore::where('attempt_id', $this->attempt->id)->count());
    }

    /** B. SCORE CALCULATION **/

    public function test_score_calculation_rounding_and_zero_max_score()
    {
        // Zero max score question
        $snapshotZero = $this->createSnapshot('MC', [['content' => 'A', 'is_correct' => true]], 0);
        $this->submitAnswer($snapshotZero->id, ['selected_option_id' => $snapshotZero->snapshot_options[0]['id']]);

        $score = (new ScoringService())->scoreAttempt($this->attempt);
        $this->assertEquals(0, $score->raw_score);
        $this->assertEquals(0, $score->max_score);
        $this->assertEquals(0, $score->percentage);
    }

    /** C. SNAPSHOT INTEGRITY **/

    public function test_snapshot_integrity_when_question_bank_modified()
    {
        $q = Question::create(['tenant_id' => $this->tenant->id, 'created_by' => $this->teacher->id]);
        $v = QuestionVersion::create([
            'tenant_id' => $this->tenant->id,
            'question_id' => $q->id,
            'stem' => 'Soal Asli',
            'question_type' => 'MC',
            'version_number' => 1,
            'created_by' => $this->teacher->id,
        ]);
        $opt = $v->options()->create(['tenant_id' => $this->tenant->id, 'content' => 'Benar', 'is_correct' => true]);

        $snapshot = $this->createSnapshotRecord([
            'id' => (string) Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'source_question_id' => $q->id,
            'source_question_version_id' => $v->id,
            'snapshot_stem' => 'Soal Asli',
            'snapshot_options' => [['id' => $opt->id, 'content' => 'Benar', 'is_correct' => true]],
            'points' => 10,
            'metadata' => ['question_type' => 'MC'],
        ]);

        $this->submitAnswer($snapshot->id, ['selected_option_id' => $opt->id]);

        // Modify source question option in question bank
        $opt->update(['is_correct' => false]);

        $score = (new ScoringService())->scoreAttempt($this->attempt);
        $this->assertEquals(10, $score->raw_score);
    }

    /** D. LIFECYCLE & FINALIZATION **/

    public function test_manual_review_grading_and_finalization_flow()
    {
        $snapshot = $this->createSnapshot('ESSAY', [], 20);
        $this->submitAnswer($snapshot->id, ['text' => 'Jawaban essay']);

        $scoringService = new ScoringService();
        $score = $scoringService->scoreAttempt($this->attempt);
        $this->assertEquals('MANUAL_REVIEW', $score->grading_status);

        $qScore = ExamQuestionScore::where('snapshot_id', $snapshot->id)->first();

        // Cannot finalize while pending manual review
        $this->actingAs($this->teacher);
        $response = $this->postJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/finalize");
        $response->assertStatus(422);

        // Grade essay
        $responseGrading = $this->putJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/grading/{$qScore->id}", [
            'awarded_score' => 18,
            'feedback' => 'Sangat baik',
        ]);
        $responseGrading->assertStatus(200);

        $scoreFresh = ExamAttemptScore::where('attempt_id', $this->attempt->id)->first();
        $this->assertEquals(18, $scoreFresh->raw_score);
        $this->assertEquals('GRADED', $scoreFresh->grading_status);

        // Finalize
        $responseFinalize = $this->postJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/finalize");
        $responseFinalize->assertStatus(200);

        $this->assertEquals('FINALIZED', ExamAttemptScore::where('attempt_id', $this->attempt->id)->first()->grading_status);
    }

    /** E. VERSIONING & IMMUTABILITY **/

    public function test_historical_versioning_and_current_pointer()
    {
        $snapshot = $this->createSnapshot('MC', [['content' => 'A', 'is_correct' => true]], 10);
        $this->submitAnswer($snapshot->id, ['selected_option_id' => $snapshot->snapshot_options[0]['id']]);

        $scoringService = new ScoringService();
        $scoreV1 = $scoringService->scoreAttempt($this->attempt);

        $this->assertEquals(1, $scoreV1->scoring_version);

        $currentPointer = ExamAttemptScoreCurrent::where('attempt_id', $this->attempt->id)->first();
        $this->assertNotNull($currentPointer);
        $this->assertEquals($scoreV1->id, $currentPointer->current_score_id);
        $this->assertEquals(1, $currentPointer->scoring_version);

        // Create new version explicitly
        $scoreV2 = $scoringService->scoreAttempt($this->attempt, true, true);
        $this->assertEquals(2, $scoreV2->scoring_version);

        $currentPointerFresh = ExamAttemptScoreCurrent::where('attempt_id', $this->attempt->id)->first();
        $this->assertEquals($scoreV2->id, $currentPointerFresh->current_score_id);
        $this->assertEquals(2, $currentPointerFresh->scoring_version);

        // Historical version 1 remains intact
        $scoreV1InDb = ExamAttemptScore::where('attempt_id', $this->attempt->id)->where('scoring_version', 1)->first();
        $this->assertNotNull($scoreV1InDb);
        $this->assertEquals($scoreV1->id, $scoreV1InDb->id);
    }

    public function test_finalized_score_cannot_be_modified_or_recalculated_in_place()
    {
        $snapshot = $this->createSnapshot('MC', [['content' => 'A', 'is_correct' => true]], 10);
        $this->submitAnswer($snapshot->id, ['selected_option_id' => $snapshot->snapshot_options[0]['id']]);

        (new ScoringService())->scoreAttempt($this->attempt);

        $this->actingAs($this->teacher);
        $this->postJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/finalize")->assertStatus(200);

        $qScore = ExamQuestionScore::first();

        // Manual score edit fails
        $response = $this->putJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/grading/{$qScore->id}", [
            'awarded_score' => 5,
        ]);
        $response->assertStatus(422);

        // Recalculate fails
        $responseRecalc = $this->postJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/recalculate");
        $responseRecalc->assertStatus(422);
    }

    /** F. TENANT ISOLATION **/

    public function test_cross_tenant_score_access_and_mutation_denied()
    {
        $tenantB = Tenant::create(['name' => 'Madrasah B', 'code' => 'MADB']);
        app(TenantContext::class)->setTenantId($tenantB->id);

        $guruRole = Role::firstOrCreate(['name' => 'GURU'], ['label' => 'Guru']);
        $teacherB = User::create([
            'tenant_id' => $tenantB->id,
            'name' => 'Guru Tenant B',
            'email' => 'guru.b@m.com',
            'password' => bcrypt('password'),
        ]);
        $teacherB->roles()->attach($guruRole);

        // Switch acting user to Tenant B teacher
        $this->actingAs($teacherB);

        // Attempting to view or mutate Tenant A score returns 404 / 403
        $responseView = $this->getJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/score");
        $responseView->assertStatus(404);

        $responseFinalize = $this->postJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/finalize");
        $responseFinalize->assertStatus(404);
    }

    private function createSnapshot(string $type, array $options, float $points)
    {
        $q = Question::create(['tenant_id' => $this->tenant->id, 'created_by' => $this->teacher->id]);
        $v = QuestionVersion::create([
            'tenant_id' => $this->tenant->id,
            'question_id' => $q->id,
            'stem' => 'Stem ' . $type,
            'question_type' => $type,
            'version_number' => 1,
            'status' => 'PUBLISHED',
            'created_by' => $this->teacher->id,
        ]);

        $formattedOptions = array_map(function ($o) {
            if (!isset($o['id'])) {
                $o['id'] = (string) Str::uuid();
            }
            return $o;
        }, $options);

        $snapshot = $this->exam->snapshots()->create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'source_question_id' => $q->id,
            'source_question_version_id' => $v->id,
            'snapshot_stem' => 'Stem ' . $type,
            'snapshot_options' => $formattedOptions,
            'points' => $points,
            'metadata' => ['question_type' => $type],
        ]);

        $order = $this->attempt->fresh()->question_order ?? [];
        $order[] = (string) $snapshot->id;
        $this->attempt->update(['question_order' => array_values(array_unique($order))]);

        return $snapshot;
    }

    private function submitAnswer(string $snapshotId, array $payload)
    {
        ExamAttemptAnswer::create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'exam_attempt_id' => $this->attempt->id,
            'exam_question_snapshot_id' => $snapshotId,
            'student_id' => $this->student->id,
            'answer_payload' => $payload,
            'answer_state' => 'ANSWERED',
        ]);
    }
    private function createSnapshotRecord(array $attributes)
    {
        $snapshot = $this->exam->snapshots()->create($attributes);
        $order = $this->attempt->fresh()->question_order ?? [];
        $order[] = (string) $snapshot->id;
        $this->attempt->update(['question_order' => array_values(array_unique($order))]);

        return $snapshot;
    }

}
