<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Assessment;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamParticipant;
use App\Models\AcademicYear;
use App\Models\Question;
use App\Models\QuestionVersion;
use App\Models\ExamAttemptAnswer;
use App\Models\ExamAttemptScore;
use App\Models\ExamQuestionScore;
use App\Services\ScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Str;

class ScoringEngineTest extends TestCase
{
    use RefreshDatabase;

    private $tenant;
    private $teacher;
    private $student;
    private $exam;
    private $attempt;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'Madrasah', 'code' => 'M1']);
        app(\App\Contexts\TenantContext::class)->setTenantId($this->tenant->id);
        $this->teacher = User::create(['tenant_id' => $this->tenant->id, 'name' => 'Guru', 'email' => 'guru@m.com', 'password' => 'p']);
        $this->teacher->roles()->create(['name' => 'GURU', 'label' => 'Guru']);

        $this->student = User::create(['tenant_id' => $this->tenant->id, 'name' => 'Siswa', 'email' => 'siswa@m.com', 'password' => 'p']);
        $this->student->roles()->create(['name' => 'SISWA', 'label' => 'Siswa']);

        $ay = AcademicYear::create(['tenant_id' => $this->tenant->id, 'name' => '2024']);
        $assessment = Assessment::create(['tenant_id' => $this->tenant->id, 'academic_year_id' => $ay->id, 'title' => 'A1', 'assessment_type' => 'DAILY', 'created_by' => $this->teacher->id]);

        $this->exam = Exam::create([
            'tenant_id' => $this->tenant->id,
            'assessment_id' => $assessment->id,
            'title' => 'E1',
            'duration_seconds' => 3600,
            'created_by' => $this->teacher->id,
            'status' => 'DRAFT',
            'start_at' => now()->subHour(),
            'end_at' => now()->addHour(),
            'locked_at' => now()
        ]);

        $participant = $this->exam->participants()->create(['tenant_id' => $this->tenant->id, 'user_id' => $this->student->id]);
        $this->attempt = ExamAttempt::create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'participant_id' => $participant->id,
            'attempt_number' => 1,
            'status' => 'SUBMITTED',
            'started_at' => now()->subMinutes(30),
            'expires_at' => now()->addMinutes(30),
            'submitted_at' => now()
        ]);
    }

    public function test_multiple_choice_scoring()
    {
        // 1. Create MC Question Snapshot
        $q = Question::create(['tenant_id' => $this->tenant->id, 'created_by' => $this->teacher->id]);
        $v = $q->versions()->create(['tenant_id' => $this->tenant->id, 'major_version' => 1, 'minor_version' => 0, 'question_type' => 'MC', 'stem' => '1+1?', 'created_by' => $this->teacher->id]);
        $opt1 = $v->options()->create(['tenant_id' => $this->tenant->id, 'content' => '2', 'is_correct' => true, 'score_weight' => 1]);
        $opt2 = $v->options()->create(['tenant_id' => $this->tenant->id, 'content' => '3', 'is_correct' => false, 'score_weight' => 0]);

        $snapshot = $this->createSnapshot([
            'id' => (string) Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'source_question_id' => $q->id,
            'source_question_version_id' => $v->id,
            'snapshot_stem' => $v->stem,
            'snapshot_options' => [
                ['id' => $opt1->id, 'content' => '2', 'is_correct' => true],
                ['id' => $opt2->id, 'content' => '3', 'is_correct' => false]
            ],
            'points' => 10,
            'metadata' => ['question_type' => 'MC']
        ]);

        // 2. Student Answer Correct
        ExamAttemptAnswer::create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'exam_attempt_id' => $this->attempt->id,
            'exam_question_snapshot_id' => $snapshot->id,
            'student_id' => $this->student->id,
            'answer_payload' => ['selected_option_id' => $opt1->id],
            'answer_state' => 'ANSWERED'
        ]);

        $service = new ScoringService();
        $score = $service->scoreAttempt($this->attempt);

        $this->assertEquals(10, $score->raw_score);
        $this->assertEquals(100, $score->percentage);

        // 3. Change to Wrong Answer
        ExamAttemptAnswer::where('exam_question_snapshot_id', $snapshot->id)->update([
            'answer_payload' => ['selected_option_id' => $opt2->id]
        ]);

        $score = $service->scoreAttempt($this->attempt, true); // force
        $this->assertEquals(0, $score->raw_score);
    }

    public function test_multiple_response_partial_scoring()
    {
        $q = Question::create(['tenant_id' => $this->tenant->id, 'created_by' => $this->teacher->id]);
        $v = $q->versions()->create(['tenant_id' => $this->tenant->id, 'major_version' => 1, 'minor_version' => 0, 'question_type' => 'MULTIPLE_RESPONSE', 'stem' => 'Select prime numbers', 'created_by' => $this->teacher->id]);
        $opt2 = $v->options()->create(['tenant_id' => $this->tenant->id, 'content' => '2', 'is_correct' => true]);
        $opt3 = $v->options()->create(['tenant_id' => $this->tenant->id, 'content' => '3', 'is_correct' => true]);
        $opt4 = $v->options()->create(['tenant_id' => $this->tenant->id, 'content' => '4', 'is_correct' => false]);

        $snapshot = $this->createSnapshot([
            'id' => (string) Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'source_question_id' => $q->id,
            'source_question_version_id' => $v->id,
            'snapshot_stem' => $v->stem,
            'snapshot_options' => [
                ['id' => $opt2->id, 'content' => '2', 'is_correct' => true],
                ['id' => $opt3->id, 'content' => '3', 'is_correct' => true],
                ['id' => $opt4->id, 'content' => '4', 'is_correct' => false]
            ],
            'points' => 10,
            'metadata' => ['question_type' => 'MULTIPLE_RESPONSE']
        ]);

        // Student selects only ONE correct out of TWO.
        ExamAttemptAnswer::create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'exam_attempt_id' => $this->attempt->id,
            'exam_question_snapshot_id' => $snapshot->id,
            'student_id' => $this->student->id,
            'answer_payload' => ['selected_option_ids' => [$opt2->id]],
            'answer_state' => 'ANSWERED'
        ]);

        $service = new ScoringService();
        $score = $service->scoreAttempt($this->attempt);

        // (1 correct / 2 total correct) * 10 points = 5
        $this->assertEquals(5, $score->raw_score);

        // If selects one INCORRECT, score should be 0
        ExamAttemptAnswer::where('exam_question_snapshot_id', $snapshot->id)->update([
            'answer_payload' => ['selected_option_ids' => [$opt2->id, $opt4->id]]
        ]);
        $score = $service->scoreAttempt($this->attempt, true);
        $this->assertEquals(0, $score->raw_score);
    }

    public function test_short_answer_normalization()
    {
        $q = Question::create(['tenant_id' => $this->tenant->id, 'created_by' => $this->teacher->id]);
        $v = QuestionVersion::create(['tenant_id' => $this->tenant->id, 'question_id' => $q->id, 'major_version' => 1, 'minor_version' => 0, 'question_type' => 'SHORT_ANSWER', 'stem' => 'Capital', 'created_by' => $this->teacher->id]);
        $snapshot = $this->createSnapshot([
            'id' => (string) Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'source_question_id' => $q->id,
            'source_question_version_id' => $v->id,
            'snapshot_stem' => 'Capital of Indonesia?',
            'snapshot_options' => [],
            'points' => 5,
            'metadata' => [
                'question_type' => 'SHORT_ANSWER',
                'accepted_answers' => ['Jakarta', 'DKI Jakarta']
            ]
        ]);

        // Correct with weird casing and spacing
        ExamAttemptAnswer::create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'exam_attempt_id' => $this->attempt->id,
            'exam_question_snapshot_id' => $snapshot->id,
            'student_id' => $this->student->id,
            'answer_payload' => ['text' => '  jaKARTA  '],
            'answer_state' => 'ANSWERED'
        ]);

        $service = new ScoringService();
        $score = $service->scoreAttempt($this->attempt);

        $this->assertEquals(5, $score->raw_score);
    }

    public function test_essay_requires_manual_review()
    {
        $q = Question::create(['tenant_id' => $this->tenant->id, 'created_by' => $this->teacher->id]);
        $v = QuestionVersion::create(['tenant_id' => $this->tenant->id, 'question_id' => $q->id, 'major_version' => 1, 'minor_version' => 0, 'question_type' => 'ESSAY', 'stem' => 'Describe', 'created_by' => $this->teacher->id]);
        $snapshot = $this->createSnapshot([
            'id' => (string) Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'source_question_id' => $q->id,
            'source_question_version_id' => $v->id,
            'snapshot_stem' => 'Describe yourself',
            'snapshot_options' => [],
            'points' => 20,
            'metadata' => ['question_type' => 'ESSAY']
        ]);

        ExamAttemptAnswer::create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'exam_attempt_id' => $this->attempt->id,
            'exam_question_snapshot_id' => $snapshot->id,
            'student_id' => $this->student->id,
            'answer_payload' => ['text' => 'I am a student'],
            'answer_state' => 'ANSWERED'
        ]);

        $service = new ScoringService();
        $score = $service->scoreAttempt($this->attempt);

        $this->assertEquals(0, $score->raw_score);
        $this->assertEquals('MANUAL_REVIEW', $score->grading_status);

        $qScore = ExamQuestionScore::where('snapshot_id', $snapshot->id)->first();
        $this->assertEquals('MANUAL_REVIEW', $qScore->scoring_status);
    }

    public function test_snapshot_integrity_when_bank_changes()
    {
        $q = Question::create(['tenant_id' => $this->tenant->id, 'created_by' => $this->teacher->id]);
        $v = $q->versions()->create(['tenant_id' => $this->tenant->id, 'major_version' => 1, 'minor_version' => 0, 'question_type' => 'MC', 'stem' => 'Old Stem', 'created_by' => $this->teacher->id]);
        $opt = $v->options()->create(['tenant_id' => $this->tenant->id, 'content' => 'Correct', 'is_correct' => true]);

        $snapshot = $this->createSnapshot([
            'id' => (string) Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'source_question_id' => $q->id,
            'source_question_version_id' => $v->id,
            'snapshot_stem' => 'Old Stem',
            'snapshot_options' => [['id' => $opt->id, 'content' => 'Correct', 'is_correct' => true]],
            'points' => 10,
            'metadata' => ['question_type' => 'MC']
        ]);

        ExamAttemptAnswer::create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'exam_attempt_id' => $this->attempt->id,
            'exam_question_snapshot_id' => $snapshot->id,
            'student_id' => $this->student->id,
            'answer_payload' => ['selected_option_id' => $opt->id],
            'answer_state' => 'ANSWERED'
        ]);

        // Update Question Bank to make this option INCORRECT
        $opt->update(['is_correct' => false]);

        $service = new ScoringService();
        $score = $service->scoreAttempt($this->attempt);

        // Should still be 10 because it uses snapshot
        $this->assertEquals(10, $score->raw_score);
    }

    public function test_matching_scoring_logic()
    {
        $q = Question::create(['tenant_id' => $this->tenant->id, 'created_by' => $this->teacher->id]);
        $v = QuestionVersion::create(['tenant_id' => $this->tenant->id, 'question_id' => $q->id, 'major_version' => 1, 'minor_version' => 0, 'question_type' => 'MATCHING', 'stem' => 'Match', 'created_by' => $this->teacher->id]);
        $snapshot = $this->createSnapshot([
            'id' => (string) Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'source_question_id' => $q->id,
            'source_question_version_id' => $v->id,
            'snapshot_stem' => 'Match pairs',
            'snapshot_options' => [
                ['id' => 'L1', 'content' => 'Left 1', 'option_key' => 'pair_1'],
                ['id' => 'R1', 'content' => 'Right 1', 'matching_key' => 'pair_1'],
                ['id' => 'L2', 'content' => 'Left 2', 'option_key' => 'pair_2'],
                ['id' => 'R2', 'content' => 'Right 2', 'matching_key' => 'pair_2']
            ],
            'points' => 10,
            'metadata' => ['question_type' => 'MATCHING']
        ]);

        // Student matches L1->R1 (correct) and L2->R1 (incorrect)
        ExamAttemptAnswer::create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'exam_attempt_id' => $this->attempt->id,
            'exam_question_snapshot_id' => $snapshot->id,
            'student_id' => $this->student->id,
            'answer_payload' => [
                'matches' => [
                    ['left_id' => 'L1', 'right_id' => 'R1'],
                    ['left_id' => 'L2', 'right_id' => 'R1']
                ]
            ],
            'answer_state' => 'ANSWERED'
        ]);

        $service = new ScoringService();
        $score = $service->scoreAttempt($this->attempt);

        // 1 correct out of 2 total pairs = 0.5 * 10 = 5 points
        $this->assertEquals(5, $score->raw_score);
    }

    public function test_cross_tenant_scoring_access_denied()
    {
        $tenantB = Tenant::create(['name' => 'Tenant B', 'code' => 'B1']);
        app(\App\Contexts\TenantContext::class)->setTenantId($tenantB->id);
        $guruRole = \App\Models\Role::firstOrCreate(['name' => 'GURU'], ['label' => 'Guru']);
        $userB = User::create(['tenant_id' => $tenantB->id, 'name' => 'Guru B', 'email' => 'guru@b.com', 'password' => 'p']);
        $userB->roles()->attach($guruRole);

        $this->actingAs($userB);

        // Attempt from Tenant A (setup in setUp)
        $response = $this->postJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/score");

        // Should be 403 or 404 depending on how scoping works.
        // With TenantScoped, it might be 404 because the record is not found in Tenant B's scope.
        $response->assertStatus(404);
    }

    public function test_multiple_choice_and_true_false_regression_scoring()
    {
        // 1. Multiple Choice with MULTIPLE_CHOICE type string
        $q1 = Question::create(['tenant_id' => $this->tenant->id, 'created_by' => $this->teacher->id]);
        $v1 = $q1->versions()->create(['tenant_id' => $this->tenant->id, 'major_version' => 1, 'minor_version' => 0, 'question_type' => 'MULTIPLE_CHOICE', 'stem' => 'MC?', 'created_by' => $this->teacher->id]);
        $opt1 = $v1->options()->create(['tenant_id' => $this->tenant->id, 'content' => 'A', 'is_correct' => true, 'score_weight' => 1]);
        $opt2 = $v1->options()->create(['tenant_id' => $this->tenant->id, 'content' => 'B', 'is_correct' => false, 'score_weight' => 0]);

        $snap1 = $this->createSnapshot([
            'id' => (string) Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'source_question_id' => $q1->id,
            'source_question_version_id' => $v1->id,
            'snapshot_stem' => $v1->stem,
            'snapshot_options' => [
                ['id' => $opt1->id, 'content' => 'A', 'is_correct' => true],
                ['id' => $opt2->id, 'content' => 'B', 'is_correct' => false]
            ],
            'points' => 10,
            'metadata' => ['question_type' => 'MULTIPLE_CHOICE']
        ]);

        ExamAttemptAnswer::create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'exam_attempt_id' => $this->attempt->id,
            'exam_question_snapshot_id' => $snap1->id,
            'student_id' => $this->student->id,
            'answer_payload' => ['selected_option_id' => $opt1->id],
            'answer_state' => 'ANSWERED'
        ]);

        // 2. True / False with TF type string
        $q2 = Question::create(['tenant_id' => $this->tenant->id, 'created_by' => $this->teacher->id]);
        $v2 = $q2->versions()->create(['tenant_id' => $this->tenant->id, 'major_version' => 1, 'minor_version' => 0, 'question_type' => 'TF', 'stem' => 'TF?', 'created_by' => $this->teacher->id]);
        $opt3 = $v2->options()->create(['tenant_id' => $this->tenant->id, 'content' => 'Benar', 'is_correct' => true, 'score_weight' => 1]);
        $opt4 = $v2->options()->create(['tenant_id' => $this->tenant->id, 'content' => 'Salah', 'is_correct' => false, 'score_weight' => 0]);

        $snap2 = $this->createSnapshot([
            'id' => (string) Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'source_question_id' => $q2->id,
            'source_question_version_id' => $v2->id,
            'snapshot_stem' => $v2->stem,
            'snapshot_options' => [
                ['id' => $opt3->id, 'content' => 'Benar', 'is_correct' => true],
                ['id' => $opt4->id, 'content' => 'Salah', 'is_correct' => false]
            ],
            'points' => 10,
            'metadata' => ['question_type' => 'TF']
        ]);

        ExamAttemptAnswer::create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'exam_attempt_id' => $this->attempt->id,
            'exam_question_snapshot_id' => $snap2->id,
            'student_id' => $this->student->id,
            'answer_payload' => ['selected_option_id' => $opt3->id],
            'answer_state' => 'ANSWERED'
        ]);

        $service = new ScoringService();
        $score = $service->scoreAttempt($this->attempt);

        $this->assertEquals('AUTO_SCORED', $score->grading_status);
        $this->assertEquals(20, $score->raw_score);
        $this->assertEquals(100, $score->percentage);
    }

    public function test_essay_requires_manual_review_and_prevents_finalization()
    {
        $q = Question::create(['tenant_id' => $this->tenant->id, 'created_by' => $this->teacher->id]);
        $v = $q->versions()->create(['tenant_id' => $this->tenant->id, 'major_version' => 1, 'minor_version' => 0, 'question_type' => 'ESSAY', 'stem' => 'Essay?', 'created_by' => $this->teacher->id]);

        $snap = $this->createSnapshot([
            'id' => (string) Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'source_question_id' => $q->id,
            'source_question_version_id' => $v->id,
            'snapshot_stem' => $v->stem,
            'snapshot_options' => [],
            'points' => 20,
            'metadata' => ['question_type' => 'ESSAY']
        ]);

        ExamAttemptAnswer::create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'exam_attempt_id' => $this->attempt->id,
            'exam_question_snapshot_id' => $snap->id,
            'student_id' => $this->student->id,
            'answer_payload' => ['text' => 'Essay response'],
            'answer_state' => 'ANSWERED'
        ]);

        $service = new ScoringService();
        $score = $service->scoreAttempt($this->attempt);

        $this->assertEquals('MANUAL_REVIEW', $score->grading_status);

        // Attempting to finalize via API should be rejected
        $response = $this->actingAs($this->teacher)->postJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/finalize");
        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $this->assertStringContainsString('manual review', $response->json('message'));
    }

    private function createSnapshot(array $attributes)
    {
        $snapshot = $this->exam->snapshots()->create($attributes);
        $order = $this->attempt->fresh()->question_order ?? [];
        $order[] = (string) $snapshot->id;
        $this->attempt->update(['question_order' => array_values(array_unique($order))]);

        return $snapshot;
    }

}
