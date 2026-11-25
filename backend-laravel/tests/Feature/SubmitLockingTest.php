<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptAnswer;
use App\Models\ExamAttemptScore;
use App\Models\ExamParticipant;
use App\Models\ExamQuestionSnapshot;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\QuestionVersion;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SubmitLockingTest extends TestCase
{
    use RefreshDatabase;

    private $tenant;
    private $student;
    private $exam;
    private $participant;
    private $attempt;
    private $snapshot1;
    private $snapshot2;
    private $option1;
    private string $studentToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create(['name' => 'Tenant 6G7', 'code' => 'T6G7']);
        app(\App\Contexts\TenantContext::class)->setTenantId($this->tenant->id);

        $this->student = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Siswa 6G7',
            'email' => 'siswa6g7@test.com',
            'password' => bcrypt('password')
        ]);
        $this->student->roles()->attach(
            \App\Models\Role::firstOrCreate(['name' => 'SISWA'], ['label' => 'Siswa'])
        );

        $ay = AcademicYear::create(['tenant_id' => $this->tenant->id, 'name' => '2024/2025']);
        $assessment = Assessment::create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $ay->id,
            'title' => 'Assessment 6G7',
            'assessment_type' => 'DAILY',
            'created_by' => $this->student->id
        ]);

        $this->exam = Exam::create([
            'tenant_id' => $this->tenant->id,
            'assessment_id' => $assessment->id,
            'title' => 'Exam Phase 6G7',
            'duration_seconds' => 3600,
            'created_by' => $this->student->id,
            'status' => 'PUBLISHED',
            'published_at' => now(),
            'start_at' => now()->subMinutes(10),
            'end_at' => now()->addMinutes(50),
            'locked_at' => now(),
            'require_token' => false
        ]);

        $this->participant = new ExamParticipant([
            'user_id' => $this->student->id,
            'status' => 'ASSIGNED'
        ]);
        $this->participant->tenant_id = $this->tenant->id;
        $this->participant->exam_id = $this->exam->id;
        $this->participant->save();

        // Question 1
        $q1 = new Question(['created_by' => $this->student->id]);
        $q1->tenant_id = $this->tenant->id;
        $q1->save();

        $v1 = new QuestionVersion([
            'question_id' => $q1->id,
            'major_version' => 1,
            'minor_version' => 0,
            'question_type' => 'MC',
            'stem' => 'Stem Q1',
            'is_current' => true,
            'created_by' => $this->student->id
        ]);
        $v1->tenant_id = $this->tenant->id;
        $v1->save();

        $this->option1 = new QuestionOption([
            'content' => 'Option 1 Correct',
            'is_correct' => true,
            'score_weight' => 1,
            'question_version_id' => $v1->id
        ]);
        $this->option1->tenant_id = $this->tenant->id;
        $this->option1->save();

        $this->snapshot1 = new ExamQuestionSnapshot([
            'id' => (string) Str::uuid(),
            'source_question_id' => $q1->id,
            'source_question_version_id' => $v1->id,
            'snapshot_stem' => 'Stem Q1',
            'snapshot_options' => [['id' => $this->option1->id, 'content' => 'Option 1 Correct', 'is_correct' => true, 'score_weight' => 1]],
            'metadata' => ['question_type' => 'MC'],
            'points' => 10
        ]);
        $this->snapshot1->tenant_id = $this->tenant->id;
        $this->snapshot1->exam_id = $this->exam->id;
        $this->snapshot1->save();

        // Question 2 (No answer created before submit)
        $q2 = new Question(['created_by' => $this->student->id]);
        $q2->tenant_id = $this->tenant->id;
        $q2->save();

        $v2 = new QuestionVersion([
            'question_id' => $q2->id,
            'major_version' => 1,
            'minor_version' => 0,
            'question_type' => 'MC',
            'stem' => 'Stem Q2',
            'is_current' => true,
            'created_by' => $this->student->id
        ]);
        $v2->tenant_id = $this->tenant->id;
        $v2->save();

        $opt2 = new QuestionOption([
            'content' => 'Option 2 Correct',
            'is_correct' => true,
            'score_weight' => 1,
            'question_version_id' => $v2->id
        ]);
        $opt2->tenant_id = $this->tenant->id;
        $opt2->save();

        $this->snapshot2 = new ExamQuestionSnapshot([
            'id' => (string) Str::uuid(),
            'source_question_id' => $q2->id,
            'source_question_version_id' => $v2->id,
            'snapshot_stem' => 'Stem Q2',
            'snapshot_options' => [['id' => $opt2->id, 'content' => 'Option 2 Correct', 'is_correct' => true, 'score_weight' => 1]],
            'metadata' => ['question_type' => 'MC'],
            'points' => 10
        ]);
        $this->snapshot2->tenant_id = $this->tenant->id;
        $this->snapshot2->exam_id = $this->exam->id;
        $this->snapshot2->save();
        // Create the Attempt through the official endpoint so auth-session
        // binding, DeviceLease, and initial fence generation are persisted.
        $this->studentToken = $this->student->createToken('submit-locking-tests')->plainTextToken;
        $this->withToken($this->studentToken)->withHeaders([
            'X-Device-Binding-ID' => 'device-6g7',
            'X-CBT-Fence-Generation' => '1',
        ]);
        $startResponse = $this->postJson("/api/v1/exams/{$this->exam->id}/attempts", []);
        $startResponse->assertStatus(201);
        $this->attempt = ExamAttempt::query()->findOrFail($startResponse->json('data.id'));
    }

    /**
     * TEST A — NORMAL SUBMIT
     */
    public function test_A_normal_submit()
    {
        $this->withToken($this->studentToken);

        // Pre-fill answer for snapshot 1
        $this->putJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/answers/{$this->snapshot1->id}", [
            'client_request_id' => (string) Str::uuid(),
            'answer_payload' => ['selected_option_id' => $this->option1->id],
            'answer_state' => 'ANSWERED'
        ])->assertStatus(200);

        // Submit attempt
        $response = $this->postJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/submit");

        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'message' => 'Attempt submitted.']);

        $this->attempt->refresh();
        $this->participant->refresh();

        $this->assertEquals('SUBMITTED', $this->attempt->status);
        $this->assertNotNull($this->attempt->submitted_at);
        $this->assertEquals('COMPLETED', $this->participant->status);
    }

    /**
     * TEST B — ANSWER AFTER SUBMIT REJECTION
     */
    public function test_B_answer_mutation_after_submit_rejected()
    {
        $this->withToken($this->studentToken);

        // Save initial answer
        $this->putJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/answers/{$this->snapshot1->id}", [
            'client_request_id' => (string) Str::uuid(),
            'answer_payload' => ['selected_option_id' => $this->option1->id],
            'answer_state' => 'ANSWERED'
        ])->assertStatus(200);

        $answerBefore = ExamAttemptAnswer::where('exam_attempt_id', $this->attempt->id)->first();
        $revBefore = $answerBefore->revision_number;
        $payloadBefore = $answerBefore->answer_payload;
        $updatedAtBefore = $answerBefore->updated_at->toIso8601String();

        // Submit
        $this->postJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/submit")->assertStatus(200);

        // Attempt answer update after submit
        $response = $this->putJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/answers/{$this->snapshot1->id}", [
            'client_request_id' => (string) Str::uuid(),
            'base_revision' => $revBefore,
            'answer_payload' => ['selected_option_id' => 'hacked-option-id'],
            'answer_state' => 'ANSWERED'
        ]);

        $response->assertStatus(422);
        $response->assertJson(['success' => false, 'message' => 'Attempt is not in progress.']);

        $answerAfter = $answerBefore->fresh();
        $this->assertEquals($revBefore, $answerAfter->revision_number);
        $this->assertEquals($payloadBefore, $answerAfter->answer_payload);
        $this->assertEquals($updatedAtBefore, $answerAfter->updated_at->toIso8601String());
    }

    /**
     * TEST C — CREATE NEW ANSWER AFTER SUBMIT REJECTION
     */
    public function test_C_new_answer_after_submit_rejected()
    {
        $this->withToken($this->studentToken);

        // Submit attempt without answering question 2
        $this->postJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/submit")->assertStatus(200);

        $countBefore = ExamAttemptAnswer::where('exam_attempt_id', $this->attempt->id)->count();

        // Try to create/upsert answer for question 2 after submit
        $response = $this->putJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/answers/{$this->snapshot2->id}", [
            'client_request_id' => (string) Str::uuid(),
            'answer_payload' => ['selected_option_id' => 'some-option-id'],
            'answer_state' => 'ANSWERED'
        ]);

        $response->assertStatus(422);
        $response->assertJson(['success' => false, 'message' => 'Attempt is not in progress.']);

        $countAfter = ExamAttemptAnswer::where('exam_attempt_id', $this->attempt->id)->count();
        $this->assertEquals($countBefore, $countAfter);
    }

    /**
     * TEST D — DUPLICATE SUBMIT HANDLING
     */
    public function test_D_duplicate_submit_handling()
    {
        $this->withToken($this->studentToken);

        // Pre-fill answer
        $this->putJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/answers/{$this->snapshot1->id}", [
            'client_request_id' => (string) Str::uuid(),
            'answer_payload' => ['selected_option_id' => $this->option1->id],
            'answer_state' => 'ANSWERED'
        ])->assertStatus(200);

        // First submit
        $res1 = $this->postJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/submit");
        $res1->assertStatus(200);

        $this->attempt->refresh();
        $submittedAt1 = $this->attempt->submitted_at->toIso8601String();
        $status1 = $this->attempt->status;

        // Second submit (Duplicate)
        $res2 = $this->postJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/submit");
        // Repeated submit is an idempotent success after the server has
        // already committed the terminal Attempt state.
        $res2->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('idempotent', true)
            ->assertJsonPath('message', 'Attempt already closed.');

        $this->attempt->refresh();
        $this->assertEquals($status1, $this->attempt->status);
        $this->assertEquals($submittedAt1, $this->attempt->submitted_at->toIso8601String());
    }


    public function test_expired_attempt_submit_closes_as_expired_and_is_idempotent_for_same_device()
    {
        $this->withToken($this->studentToken);
        $this->attempt->update(['expires_at' => now()->subSecond()]);

        $this->withHeaders(['X-Device-Binding-ID' => 'device-6g7', 'X-CBT-Fence-Generation' => '1']);
        $first = $this->postJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/submit");
        $first->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'EXPIRED');

        $this->assertSame('EXPIRED', $this->attempt->fresh()->status);
        $this->assertNotNull(ExamAttemptScore::where('attempt_id', $this->attempt->id)->first());

        $second = $this->postJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/submit");
        $second->assertStatus(200)->assertJsonPath('data.status', 'EXPIRED');
    }

    /**
     * TEST E — OLD REQUEST AFTER SUBMIT REJECTION
     */
    public function test_E_old_offline_request_after_submit_rejected()
    {
        $this->withToken($this->studentToken);

        // Save answer pre-submit
        $oldReqId = (string) Str::uuid();
        $this->putJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/answers/{$this->snapshot1->id}", [
            'client_request_id' => $oldReqId,
            'answer_payload' => ['selected_option_id' => $this->option1->id],
            'answer_state' => 'ANSWERED'
        ])->assertStatus(200);

        // Submit attempt
        $this->postJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/submit")->assertStatus(200);

        // Send delayed/queued request with valid pre-submit base_revision (1)
        $response = $this->putJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/answers/{$this->snapshot1->id}", [
            'client_request_id' => (string) Str::uuid(),
            'base_revision' => 1,
            'answer_payload' => ['selected_option_id' => $this->option1->id],
            'answer_state' => 'ANSWERED'
        ]);

        $response->assertStatus(422);
        $response->assertJson(['success' => false, 'message' => 'Attempt is not in progress.']);
    }

    /**
     * TEST F — ATOMIC LOCKING & CONCURRENCY VERIFICATION
     */
    public function test_F_atomic_locking_verification()
    {
        $this->withToken($this->studentToken);

        // Verify that when attempt status is updated to SUBMITTED inside transaction,
        // any subsequent or concurrent check fails.
        $this->attempt->update(['status' => 'SUBMITTED']);

        $response = $this->putJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/answers/{$this->snapshot1->id}", [
            'client_request_id' => (string) Str::uuid(),
            'answer_payload' => ['selected_option_id' => $this->option1->id],
            'answer_state' => 'ANSWERED'
        ]);

        $response->assertStatus(422);
    }

    /**
     * TEST G — FINAL DATA INTEGRITY
     */
    public function test_G_final_data_integrity()
    {
        $this->withToken($this->studentToken);

        // Answer Q1
        $this->putJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/answers/{$this->snapshot1->id}", [
            'client_request_id' => (string) Str::uuid(),
            'answer_payload' => ['selected_option_id' => $this->option1->id],
            'answer_state' => 'ANSWERED'
        ])->assertStatus(200);

        $stateBefore = [
            'attempt_status' => $this->attempt->status,
            'submitted_at' => $this->attempt->submitted_at,
            'answer_count' => ExamAttemptAnswer::where('exam_attempt_id', $this->attempt->id)->count(),
            'answer1_option' => ExamAttemptAnswer::where('exam_attempt_id', $this->attempt->id)->first()->answer_payload['selected_option_id'],
            'answer1_revision' => ExamAttemptAnswer::where('exam_attempt_id', $this->attempt->id)->first()->revision_number,
        ];

        // Submit
        $this->postJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/submit")->assertStatus(200);

        $this->attempt->refresh();
        $this->participant->refresh();
        $score = ExamAttemptScore::where('attempt_id', $this->attempt->id)->first();

        $stateAfterSubmit = [
            'attempt_status' => $this->attempt->status,
            'submitted_at' => $this->attempt->submitted_at,
            'participant_status' => $this->participant->status,
            'answer_count' => ExamAttemptAnswer::where('exam_attempt_id', $this->attempt->id)->count(),
            'answer1_option' => ExamAttemptAnswer::where('exam_attempt_id', $this->attempt->id)->first()->answer_payload['selected_option_id'],
            'answer1_revision' => ExamAttemptAnswer::where('exam_attempt_id', $this->attempt->id)->first()->revision_number,
            'has_score' => !is_null($score),
            'total_score' => $score ? $score->total_score : null
        ];

        $this->assertEquals('IN_PROGRESS', $stateBefore['attempt_status']);
        $this->assertNull($stateBefore['submitted_at']);

        $this->assertEquals('SUBMITTED', $stateAfterSubmit['attempt_status']);
        $this->assertNotNull($stateAfterSubmit['submitted_at']);
        $this->assertEquals('COMPLETED', $stateAfterSubmit['participant_status']);
        $this->assertEquals(1, $stateAfterSubmit['answer_count']);
        $this->assertEquals($this->option1->id, $stateAfterSubmit['answer1_option']);
        $this->assertEquals(1, $stateAfterSubmit['answer1_revision']);
        $this->assertTrue($stateAfterSubmit['has_score']);

        // Attempt illegal post-submit answer update
        $this->putJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/answers/{$this->snapshot1->id}", [
            'client_request_id' => (string) Str::uuid(),
            'answer_payload' => ['selected_option_id' => 'illegal_option'],
            'answer_state' => 'ANSWERED'
        ])->assertStatus(422);

        // Verify state after illegal request remains unchanged
        $this->attempt->refresh();
        $stateAfterIllegal = [
            'attempt_status' => $this->attempt->status,
            'submitted_at' => $this->attempt->submitted_at,
            'participant_status' => $this->participant->status,
            'answer_count' => ExamAttemptAnswer::where('exam_attempt_id', $this->attempt->id)->count(),
            'answer1_option' => ExamAttemptAnswer::where('exam_attempt_id', $this->attempt->id)->first()->answer_payload['selected_option_id'],
            'answer1_revision' => ExamAttemptAnswer::where('exam_attempt_id', $this->attempt->id)->first()->revision_number,
        ];

        $this->assertEquals($stateAfterSubmit['attempt_status'], $stateAfterIllegal['attempt_status']);
        $this->assertEquals($stateAfterSubmit['submitted_at']->toIso8601String(), $stateAfterIllegal['submitted_at']->toIso8601String());
        $this->assertEquals($stateAfterSubmit['answer1_option'], $stateAfterIllegal['answer1_option']);
        $this->assertEquals($stateAfterSubmit['answer1_revision'], $stateAfterIllegal['answer1_revision']);
    }
}
