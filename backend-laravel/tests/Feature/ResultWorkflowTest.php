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
use App\Models\ExamAttemptScoreCurrent;
use App\Models\ExamQuestionScore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Str;

class ResultWorkflowTest extends TestCase
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
        $this->tenant = Tenant::create(['name' => 'M1', 'code' => 'M1']);
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
            'status' => 'SCHEDULED',
            'start_at' => now()->subHour(),
            'end_at' => now()->addHour(),
            'locked_at' => now(),
            'show_result' => true
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
        $this->bootstrapCbtRolePermissions();
        $this->grantTeacherAccessToExam($this->teacher, $this->exam);
    }


    public function test_score_relation_uses_current_pointer_not_highest_scoring_version(): void
    {
        $current = ExamAttemptScore::create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'attempt_id' => $this->attempt->id,
            'student_id' => $this->student->id,
            'raw_score' => 35,
            'max_score' => 100,
            'percentage' => 35,
            'scoring_version' => 1,
            'grading_status' => 'FINALIZED',
            'calculated_at' => now(),
            'finalized_at' => now(),
        ]);

        $historicalHigherVersion = ExamAttemptScore::create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'attempt_id' => $this->attempt->id,
            'student_id' => $this->student->id,
            'raw_score' => 95,
            'max_score' => 100,
            'percentage' => 95,
            'scoring_version' => 2,
            'grading_status' => 'AUTO_SCORED',
            'calculated_at' => now(),
        ]);

        ExamAttemptScoreCurrent::create([
            'tenant_id' => $this->tenant->id,
            'attempt_id' => $this->attempt->id,
            'current_score_id' => $current->id,
            'scoring_version' => 1,
        ]);

        $freshAttempt = $this->attempt->fresh();
        $this->assertSame($current->id, $freshAttempt->score?->id);
        $this->assertNotSame($historicalHigherVersion->id, $freshAttempt->score?->id);

        // The relation must fail closed when no current pointer exists.
        $attemptWithoutPointer = ExamAttempt::create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'participant_id' => $this->attempt->participant_id,
            'attempt_number' => 2,
            'status' => 'SUBMITTED',
            'started_at' => now()->subMinutes(20),
            'expires_at' => now()->addMinutes(20),
            'submitted_at' => now(),
        ]);
        ExamAttemptScore::create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'attempt_id' => $attemptWithoutPointer->id,
            'student_id' => $this->student->id,
            'raw_score' => 80,
            'max_score' => 100,
            'percentage' => 80,
            'scoring_version' => 9,
            'grading_status' => 'FINALIZED',
            'calculated_at' => now(),
            'finalized_at' => now(),
        ]);

        $this->assertNull($attemptWithoutPointer->fresh()->score);
        try {
            (new \App\Services\ScoringService())->scoreAttempt($attemptWithoutPointer);
            $this->fail('Scoring must fail closed when score history exists without a current pointer.');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('current score pointer is missing', strtolower($e->getMessage()));
        }
    }

    public function test_current_score_pointer_rejects_cross_attempt_and_version_mismatch(): void
    {
        $otherAttempt = ExamAttempt::create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'participant_id' => $this->attempt->participant_id,
            'attempt_number' => 2,
            'status' => 'SUBMITTED',
            'started_at' => now()->subMinutes(20),
            'expires_at' => now()->addMinutes(20),
            'submitted_at' => now(),
        ]);

        $foreignAttemptScore = ExamAttemptScore::create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'attempt_id' => $otherAttempt->id,
            'student_id' => $this->student->id,
            'raw_score' => 70,
            'max_score' => 100,
            'percentage' => 70,
            'scoring_version' => 1,
            'grading_status' => 'FINALIZED',
            'calculated_at' => now(),
            'finalized_at' => now(),
        ]);

        $pointer = ExamAttemptScoreCurrent::create([
            'tenant_id' => $this->tenant->id,
            'attempt_id' => $this->attempt->id,
            'current_score_id' => $foreignAttemptScore->id,
            'scoring_version' => 1,
        ]);

        $attempt = $this->attempt->fresh()->load('scoreCurrentPointer.currentScore');
        $this->assertNull($attempt->resolvedCurrentScore());
        $this->assertNull($attempt->score);

        $pointer->update([
            'current_score_id' => $foreignAttemptScore->id,
            'scoring_version' => 2,
        ]);

        $attempt = $this->attempt->fresh()->load('scoreCurrentPointer.currentScore');
        $this->assertNull($attempt->resolvedCurrentScore());
        $this->assertNull($attempt->score);
    }

    public function test_essay_grading_and_finalization_flow()
    {
        // 1. Setup Essay Question
        $q = Question::create(['tenant_id' => $this->tenant->id, 'created_by' => $this->teacher->id]);
        $v = QuestionVersion::create(['tenant_id' => $this->tenant->id, 'question_id' => $q->id, 'major_version' => 1, 'minor_version' => 0, 'question_type' => 'ESSAY', 'stem' => 'Essay?', 'created_by' => $this->teacher->id]);

        $snapshot = $this->createSnapshotForAttempt($this->attempt, [
            'id' => (string) Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'source_question_id' => $q->id,
            'source_question_version_id' => $v->id,
            'snapshot_stem' => 'Essay?',
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
            'answer_payload' => ['text' => 'My answer'],
            'answer_state' => 'ANSWERED'
        ]);

        // Trigger initial scoring
        $this->actingAs($this->teacher)->postJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/score");

        $qScore = ExamQuestionScore::where('snapshot_id', $snapshot->id)->first();
        $this->assertEquals('MANUAL_REVIEW', $qScore->scoring_status);

        // 2. Student cannot see score yet
        $this->actingAs($this->student, 'sanctum');
        $response = $this->getJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/score");
        $response->assertStatus(403);

        // 3. Teacher grades Essay
        $this->actingAs($this->teacher, 'sanctum');
        $this->putJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/grading/{$qScore->id}", [
            'awarded_score' => 15,
            'feedback' => 'Good job'
        ])->assertStatus(200);

        $this->assertEquals(15, ExamAttemptScore::first()->raw_score);
        $this->assertEquals('GRADED', ExamQuestionScore::first()->scoring_status);

        // 4. Finalize
        $this->postJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/finalize")->assertStatus(200);
        $this->assertEquals('FINALIZED', ExamAttemptScore::first()->grading_status);

        // 5. Student can now see score
        $this->actingAs($this->student, 'sanctum');
        $response = $this->getJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/score");
        $response->assertStatus(200)
                 ->assertJsonPath('data.summary.raw_score', "15.0000");

        // 6. Immutability check
        $this->actingAs($this->teacher, 'sanctum');
        $this->putJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/grading/{$qScore->id}", [
            'awarded_score' => 20
        ])->assertStatus(422);
    }

    public function test_finalize_all_preflight_logic()
    {
        // 1. Setup MC Question Snapshot & Answer for Attempt 1
        $q1 = Question::create(['tenant_id' => $this->tenant->id, 'created_by' => $this->teacher->id]);
        $v1 = QuestionVersion::create(['tenant_id' => $this->tenant->id, 'question_id' => $q1->id, 'major_version' => 1, 'minor_version' => 0, 'question_type' => 'MC', 'stem' => 'MC?', 'created_by' => $this->teacher->id]);
        $optId = (string) Str::uuid();
        $snapshot1 = $this->createSnapshotForAttempt($this->attempt, [
            'id' => (string) Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'source_question_id' => $q1->id,
            'source_question_version_id' => $v1->id,
            'snapshot_stem' => 'MC?',
            'snapshot_options' => [['id' => $optId, 'content' => 'A', 'is_correct' => true]],
            'points' => 10,
            'metadata' => ['question_type' => 'MC']
        ]);
        ExamAttemptAnswer::create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'exam_attempt_id' => $this->attempt->id,
            'exam_question_snapshot_id' => $snapshot1->id,
            'student_id' => $this->student->id,
            'answer_payload' => ['selected_option_id' => $optId],
            'answer_state' => 'ANSWERED'
        ]);
        (new \App\Services\ScoringService())->scoreAttempt($this->attempt);

        // A newer historical row must not supersede the current pointer in
        // ResultList preflight or bulk finalization.
        $historicalScore = ExamAttemptScore::create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'attempt_id' => $this->attempt->id,
            'student_id' => $this->student->id,
            'raw_score' => 0,
            'max_score' => 100,
            'percentage' => 0,
            'scoring_version' => 2,
            'grading_status' => 'MANUAL_REVIEW',
            'calculated_at' => now(),
        ]);

        // 2. Setup Attempt 2: Essay Question (pending manual review)
        $student2 = User::create(['tenant_id' => $this->tenant->id, 'name' => 'Siswa 2', 'email' => 'siswa2@m.com', 'password' => 'p']);
        $participant2 = $this->exam->participants()->create(['tenant_id' => $this->tenant->id, 'user_id' => $student2->id]);
        $attempt2 = ExamAttempt::create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'participant_id' => $participant2->id,
            'attempt_number' => 1,
            'status' => 'SUBMITTED',
            'started_at' => now()->subMinutes(30),
            'expires_at' => now()->addMinutes(30),
            'submitted_at' => now()
        ]);
        $q2 = Question::create(['tenant_id' => $this->tenant->id, 'created_by' => $this->teacher->id]);
        $v2 = QuestionVersion::create(['tenant_id' => $this->tenant->id, 'question_id' => $q2->id, 'major_version' => 1, 'minor_version' => 0, 'question_type' => 'ESSAY', 'stem' => 'Essay?', 'created_by' => $this->teacher->id]);
        $snapshot2 = $this->createSnapshotForAttempt($attempt2, [
            'id' => (string) Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'source_question_id' => $q2->id,
            'source_question_version_id' => $v2->id,
            'snapshot_stem' => 'Essay?',
            'snapshot_options' => [],
            'points' => 20,
            'metadata' => ['question_type' => 'ESSAY']
        ]);
        ExamAttemptAnswer::create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'exam_attempt_id' => $attempt2->id,
            'exam_question_snapshot_id' => $snapshot2->id,
            'student_id' => $student2->id,
            'answer_payload' => ['text' => 'Essay ans'],
            'answer_state' => 'ANSWERED'
        ]);
        (new \App\Services\ScoringService())->scoreAttempt($attempt2);

        // 3. Setup Attempt 3: Already finalized
        $student3 = User::create(['tenant_id' => $this->tenant->id, 'name' => 'Siswa 3', 'email' => 'siswa3@m.com', 'password' => 'p']);
        $participant3 = $this->exam->participants()->create(['tenant_id' => $this->tenant->id, 'user_id' => $student3->id]);
        $attempt3 = ExamAttempt::create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'participant_id' => $participant3->id,
            'attempt_number' => 1,
            'status' => 'SUBMITTED',
            'started_at' => now()->subMinutes(30),
            'expires_at' => now()->addMinutes(30),
            'submitted_at' => now()
        ]);
        $attempt3->update(['question_order' => [(string) $snapshot1->id]]);
        (new \App\Services\ScoringService())->scoreAttempt($attempt3);
        $attempt3->score->update(['grading_status' => 'FINALIZED', 'finalized_at' => now()]);

        // Ensure all participants in the bulk operation have valid tenant-scoped
        // StudentProfile/Rombel membership under the Guru's active assignment.
        $this->grantTeacherAccessToExam($this->teacher, $this->exam);

        // 4. Test Livewire ResultList preflight logic
        $this->actingAs($this->teacher);
        $component = \Livewire\Livewire::test(\App\Livewire\Teacher\ResultList::class, ['exam' => $this->exam])
            ->call('startFinalizeAll');

        $preflight = $component->get('preflight');
        $this->assertEquals(3, $preflight['total']);
        $this->assertEquals(1, $preflight['eligible']);
        $this->assertEquals(1, $preflight['pending_review']);
        $this->assertEquals(1, $preflight['already_finalized']);

        // 5. Confirm finalize all and verify only ready attempt was finalized
        $component->call('confirmFinalizeAll');

        $this->assertEquals('FINALIZED', $this->attempt->score->fresh()->grading_status);
        $this->assertEquals('MANUAL_REVIEW', $attempt2->score->fresh()->grading_status);
        $this->assertEquals('FINALIZED', $attempt3->score->fresh()->grading_status);
        $this->assertEquals('FINALIZED', $this->attempt->fresh()->score->grading_status);
        $this->assertEquals('MANUAL_REVIEW', $historicalScore->fresh()->grading_status);
    }

    public function test_exam_result_publication_workflow_and_student_visibility()
    {
        // 1. Setup a valid immutable attempt snapshot and answer before scoring.
        $question = Question::create([
            'tenant_id' => $this->tenant->id,
            'created_by' => $this->teacher->id,
        ]);
        $version = QuestionVersion::create([
            'tenant_id' => $this->tenant->id,
            'question_id' => $question->id,
            'major_version' => 1,
            'minor_version' => 0,
            'question_type' => 'MC',
            'stem' => 'Berapakah 1 + 1?',
            'default_points' => 10,
            'created_by' => $this->teacher->id,
        ]);
        $snapshot = $this->createSnapshotForAttempt($this->attempt, [
            'id' => (string) Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'source_question_id' => $question->id,
            'source_question_version_id' => $version->id,
            'sequence' => 1,
            'snapshot_stem' => 'Berapakah 1 + 1?',
            'snapshot_options' => [[
                'id' => (string) Str::uuid(),
                'content' => '2',
                'is_correct' => true,
            ]],
            'snapshot_media' => [],
            'points' => 10,
            'metadata' => ['question_type' => 'MC'],
        ]);
        ExamAttemptAnswer::create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'exam_attempt_id' => $this->attempt->id,
            'exam_question_snapshot_id' => $snapshot->id,
            'student_id' => $this->student->id,
            'answer_payload' => ['selected_option_id' => $snapshot->snapshot_options[0]['id']],
            'answer_state' => 'ANSWERED',
        ]);

        (new \App\Services\ScoringService())->scoreAttempt($this->attempt);
        $this->attempt->score->update(['grading_status' => 'FINALIZED', 'finalized_at' => now()]);

        // 2. Set exam show_result to false (unpublished)
        $this->exam->update(['show_result' => false]);

        // 3. Student cannot view score when unpublished
        $this->actingAs($this->student, 'sanctum');
        $this->getJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/score")
            ->assertStatus(403);

        // 4. Teacher uses Livewire ResultList to view publish preflight and publish results
        $this->actingAs($this->teacher);
        $component = \Livewire\Livewire::test(\App\Livewire\Teacher\ResultList::class, ['exam' => $this->exam])
            ->call('startPublishModal');

        $publishPreflight = $component->get('publishPreflight');
        $this::assertFalse($publishPreflight['is_published']);
        $this::assertEquals(1, $publishPreflight['total']);
        $this::assertEquals(1, $publishPreflight['finalized']);

        // 5. Confirm publication
        $component->call('toggleResultPublication');
        $this::assertTrue($this->exam->fresh()->show_result);

        // 6. Student can now view score
        $this->actingAs($this->student, 'sanctum');
        $this->getJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/score")
            ->assertStatus(200);

        // 7. Teacher unpublishes results
        $this->actingAs($this->teacher);
        \Livewire\Livewire::test(\App\Livewire\Teacher\ResultList::class, ['exam' => $this->exam])
            ->call('toggleResultPublication');

        $this::assertFalse($this->exam->fresh()->show_result);

        // 8. Student blocked again
        $this->actingAs($this->student, 'sanctum');
        $this->getJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/score")
            ->assertStatus(403);
    }

    private function createSnapshotForAttempt(ExamAttempt $attempt, array $attributes)
    {
        $snapshot = $this->exam->snapshots()->create($attributes);
        $order = $attempt->fresh()->question_order ?? [];
        $order[] = (string) $snapshot->id;
        $attempt->update(['question_order' => array_values(array_unique($order))]);

        return $snapshot;
    }

}
