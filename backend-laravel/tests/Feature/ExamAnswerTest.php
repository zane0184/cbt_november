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
use App\Models\QuestionOption;
use App\Models\ExamQuestionSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Str;

class ExamAnswerTest extends TestCase
{
    use RefreshDatabase;

    private $tenant;
    private $student;
    private $exam;
    private $attempt;
    private $snapshot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'T1', 'code' => 'T1']);
        app(\App\Contexts\TenantContext::class)->setTenantId($this->tenant->id);

        $this->student = User::create(['tenant_id' => $this->tenant->id, 'name' => 'S1', 'email' => 's1@e.com', 'password' => 'p']);
        $this->student->roles()->attach(
            \App\Models\Role::firstOrCreate(['name' => 'SISWA'], ['label' => 'Siswa'])
        );

        $ay = AcademicYear::create(['tenant_id' => $this->tenant->id, 'name' => '2024']);
        $assessment = Assessment::create(['tenant_id' => $this->tenant->id, 'academic_year_id' => $ay->id, 'title' => 'A1', 'assessment_type' => 'DAILY', 'created_by' => $this->student->id]);

        $this->exam = Exam::create([
            'tenant_id' => $this->tenant->id,
            'assessment_id' => $assessment->id,
            'title' => 'E1',
            'duration_seconds' => 3600,
            'created_by' => $this->student->id,
            'status' => 'DRAFT',
            'start_at' => now()->subMinutes(10),
            'end_at' => now()->addMinutes(50),
            'locked_at' => now(),
            'require_token' => false
        ]);

        $participant = new ExamParticipant(['user_id' => $this->student->id]);
        $participant->tenant_id = $this->tenant->id;
        $participant->exam_id = $this->exam->id;
        $participant->save();

        $this->attempt = new ExamAttempt([
            'exam_id' => $this->exam->id,
            'participant_id' => $participant->id,
            'attempt_number' => 1,
            'status' => 'IN_PROGRESS',
            'started_at' => now(),
            'expires_at' => now()->addHour(),
            'device_fingerprint' => 'device-test'
        ]);
        $this->attempt->tenant_id = $this->tenant->id;
        $this->attempt->save();

        $q = new Question(['created_by' => $this->student->id]);
        $q->tenant_id = $this->tenant->id;
        $q->save();

        $v = new QuestionVersion([
            'question_id' => $q->id,
            'major_version' => 1, 'minor_version' => 0, 'question_type' => 'MC', 'stem' => 'S', 'is_current' => true, 'created_by' => $this->student->id
        ]);
        $v->tenant_id = $this->tenant->id;
        $v->save();

        $opt = new QuestionOption([
            'content' => 'Option 1',
            'is_correct' => true,
            'score_weight' => 1,
            'question_version_id' => $v->id
        ]);
        $opt->tenant_id = $this->tenant->id;
        $opt->save();

        $this->snapshot = new ExamQuestionSnapshot([
            'id' => (string) Str::uuid(),
            'source_question_id' => $q->id,
            'source_question_version_id' => $v->id,
            'snapshot_stem' => 'S',
            'snapshot_options' => [['id' => $opt->id, 'content' => 'Option 1']],
            'metadata' => ['question_type' => 'MC']
        ]);
        $this->snapshot->tenant_id = $this->tenant->id;
        $this->snapshot->exam_id = $this->exam->id;
        $this->snapshot->save();
        $this->attempt->update(['question_order' => [(string) $this->snapshot->id]]);

        $this->withHeader('X-Device-Binding-ID', 'device-test');
    }


    public function test_matching_payload_rejects_foreign_or_duplicate_pair_ids()
    {
        $this->actingAs($this->student);
        $this->snapshot->update([
            'snapshot_options' => [
                ['id' => 'LEFT-1', 'content' => 'Kiri 1', 'option_key' => 'PAIR-1'],
                ['id' => 'LEFT-2', 'content' => 'Kiri 2', 'option_key' => 'PAIR-2'],
                ['id' => 'RIGHT-1', 'content' => 'Kanan 1', 'matching_key' => 'PAIR-1'],
                ['id' => 'RIGHT-2', 'content' => 'Kanan 2', 'matching_key' => 'PAIR-2'],
            ],
            'metadata' => ['question_type' => 'MATCHING'],
        ]);

        $url = "/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/answers/{$this->snapshot->id}";
        $foreignId = $this->putJson($url, [
            'client_request_id' => Str::uuid(),
            'answer_payload' => ['matches' => [['left_id' => 'FOREIGN', 'right_id' => 'RIGHT-1']]],
            'answer_state' => 'ANSWERED',
        ]);
        $foreignId->assertStatus(422);

        $duplicateRight = $this->putJson($url, [
            'client_request_id' => Str::uuid(),
            'answer_payload' => ['matches' => [
                ['left_id' => 'LEFT-1', 'right_id' => 'RIGHT-1'],
                ['left_id' => 'LEFT-2', 'right_id' => 'RIGHT-1'],
            ]],
            'answer_state' => 'ANSWERED',
        ]);
        $duplicateRight->assertStatus(422);
    }

    public function test_answer_restore_rejects_different_device_binding()
    {
        $this->actingAs($this->student);

        $response = $this->withHeader('X-Device-Binding-ID', 'another-device')
            ->getJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/answers");

        $response->assertStatus(409)
            ->assertJsonPath('code', 'CONCURRENT_DEVICE_DENIED');
    }

    public function test_answer_restore_response_omits_internal_and_identity_fields()
    {
        $this->actingAs($this->student);
        \App\Models\ExamAttemptAnswer::create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'exam_attempt_id' => $this->attempt->id,
            'exam_question_snapshot_id' => $this->snapshot->id,
            'student_id' => $this->student->id,
            'answer_payload' => ['selected_option_id' => $this->snapshot->snapshot_options[0]['id']],
            'answer_state' => 'ANSWERED',
            'revision_number' => 1,
            'client_request_id' => 'private-client-request-id',
            'saved_at' => now(),
        ]);

        $response = $this->getJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/answers");
        $response->assertOk()
            ->assertJsonMissingPath('data.0.student_id')
            ->assertJsonMissingPath('data.0.client_request_id')
            ->assertJsonMissingPath('data.0.exam_attempt_id')
            ->assertJsonPath('data.0.revision_number', 1);
    }


    public function test_answered_state_requires_non_null_payload()
    {
        $this->actingAs($this->student);
        $response = $this->putJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/answers/{$this->snapshot->id}", [
            'client_request_id' => Str::uuid(),
            'answer_payload' => null,
            'answer_state' => 'ANSWERED',
        ]);

        $response->assertStatus(422);
    }

    public function test_future_base_revision_is_rejected_for_a_new_answer(): void
    {
        $this->actingAs($this->student);

        $response = $this->putJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/answers/{$this->snapshot->id}", [
            'client_request_id' => Str::uuid(),
            'base_revision' => 7,
            'answer_payload' => ['selected_option_id' => $this->snapshot->snapshot_options[0]['id']],
            'answer_state' => 'ANSWERED',
        ]);

        $response->assertStatus(409)
            ->assertJsonPath('error', 'ANSWER_REVISION_CONFLICT')
            ->assertJsonPath('current_revision', 0)
            ->assertJsonPath('current_answer', null);
        $this->assertDatabaseMissing('exam_attempt_answers', [
            'exam_attempt_id' => $this->attempt->id,
            'exam_question_snapshot_id' => $this->snapshot->id,
        ]);
    }

    public function test_student_can_save_mc_answer()
    {
        $this->actingAs($this->student);
        $this->withHeader('X-Device-Binding-ID', 'device-test');

        $optionId = $this->snapshot->snapshot_options[0]['id'];

        $response = $this->putJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/answers/{$this->snapshot->id}", [
            'client_request_id' => Str::uuid(),
            'answer_payload' => ['selected_option_id' => $optionId],
            'answer_state' => 'ANSWERED'
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('exam_attempt_answers', [
            'exam_attempt_id' => $this->attempt->id,
            'answer_state' => 'ANSWERED'
        ]);
    }

    public function test_student_can_clear_answer()
    {
        $this->actingAs($this->student);

        $response = $this->putJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/answers/{$this->snapshot->id}", [
            'client_request_id' => Str::uuid(),
            'answer_payload' => null,
            'answer_state' => 'CLEARED'
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('exam_attempt_answers', [
            'exam_attempt_id' => $this->attempt->id,
            'answer_state' => 'CLEARED'
        ]);
    }

    public function test_invalid_option_id_rejected()
    {
        $this->actingAs($this->student);

        $response = $this->putJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/answers/{$this->snapshot->id}", [
            'client_request_id' => Str::uuid(),
            'answer_payload' => ['selected_option_id' => Str::uuid()], // Random UUID not in snapshot
            'answer_state' => 'ANSWERED'
        ]);

        $response->assertStatus(422);
    }

    public function test_revision_conflict_detection()
    {
        $this->actingAs($this->student);
        $optionId = $this->snapshot->snapshot_options[0]['id'];

        // 1. First Save (Revision 1)
        $this->putJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/answers/{$this->snapshot->id}", [
            'client_request_id' => Str::uuid(),
            'answer_payload' => ['selected_option_id' => $optionId],
            'answer_state' => 'ANSWERED'
        ]);

        // 2. Second Save with stale base_revision (0 < 1)
        $response = $this->putJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/answers/{$this->snapshot->id}", [
            'client_request_id' => Str::uuid(),
            'base_revision' => 0,
            'answer_payload' => ['selected_option_id' => $optionId],
            'answer_state' => 'ANSWERED'
        ]);

        $response->assertStatus(409);
        $response->assertJson(['error' => 'ANSWER_REVISION_CONFLICT']);
        $response->assertJsonStructure([
            'current_revision',
            'current_answer' => ['answer_payload', 'answer_state'],
            'server_now'
        ]);
    }

    public function test_student_cannot_access_other_student_attempt_answers()
    {
        $otherStudent = User::create(['tenant_id' => $this->tenant->id, 'name' => 'S2', 'email' => 's2@e.com', 'password' => 'p']);
        $otherStudent->roles()->attach(
            \App\Models\Role::firstOrCreate(['name' => 'SISWA'], ['label' => 'Siswa'])
        );

        $this->actingAs($otherStudent);

        $response = $this->getJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/answers");
        $response->assertStatus(403);
    }

    public function test_idempotent_request_handling()
    {
        $this->actingAs($this->student);
        $optionId = $this->snapshot->snapshot_options[0]['id'];
        $requestId = (string) Str::uuid();

        // 1. Initial Request
        $this->putJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/answers/{$this->snapshot->id}", [
            'client_request_id' => $requestId,
            'answer_payload' => ['selected_option_id' => $optionId],
            'answer_state' => 'ANSWERED'
        ]);

        $revision1 = \App\Models\ExamAttemptAnswer::first()->revision_number;

        // 2. Duplicate Request
        $response = $this->putJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/answers/{$this->snapshot->id}", [
            'client_request_id' => $requestId,
            'answer_payload' => ['selected_option_id' => $optionId],
            'answer_state' => 'ANSWERED'
        ]);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Idempotent request handled.']);
        $this->assertEquals($revision1, \App\Models\ExamAttemptAnswer::first()->revision_number);
    }

    public function test_all_question_types_payload_validation()
    {
        $this->actingAs($this->student);

        $types = [
            'MULTIPLE_RESPONSE' => ['selected_option_ids' => []],
            'TRUE_FALSE' => ['selected_option_id' => $this->snapshot->snapshot_options[0]['id']],
            'SHORT_ANSWER' => ['text' => 'hello'],
            'ESSAY' => ['text' => 'essay content']
        ];

        foreach ($types as $type => $payload) {
            $q = new Question(['created_by' => $this->student->id]);
            $q->tenant_id = $this->tenant->id;
            $q->save();

            $v = new QuestionVersion([
                'question_id' => $q->id,
                'major_version' => 1, 'minor_version' => 0, 'question_type' => $type, 'stem' => 'S', 'is_current' => true, 'created_by' => $this->student->id
            ]);
            $v->tenant_id = $this->tenant->id;
            $v->save();

            $snapshot = new ExamQuestionSnapshot([
                'id' => (string) Str::uuid(),
                'exam_id' => $this->exam->id,
                'source_question_id' => $q->id,
                'source_question_version_id' => $v->id,
                'snapshot_stem' => 'S',
                'snapshot_options' => [['id' => $this->snapshot->snapshot_options[0]['id'], 'content' => 'O1']],
                'metadata' => ['question_type' => $type]
            ]);
            $snapshot->tenant_id = $this->tenant->id;
            $snapshot->save();
            $this->attempt->update([
                'question_order' => array_values(array_merge($this->attempt->fresh()->question_order ?? [], [(string) $snapshot->id])),
            ]);

            $response = $this->putJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/answers/{$snapshot->id}", [
                'client_request_id' => Str::uuid(),
                'answer_payload' => $payload,
                'answer_state' => 'ANSWERED'
            ]);

            $response->assertStatus(200);
        }
    }

    public function test_same_exam_snapshot_not_in_attempt_question_order_is_rejected()
    {
        $this->actingAs($this->student);

        $unassignedSnapshot = new ExamQuestionSnapshot([
            'id' => (string) Str::uuid(),
            'exam_id' => $this->exam->id,
            'source_question_id' => $this->snapshot->source_question_id,
            'source_question_version_id' => $this->snapshot->source_question_version_id,
            'snapshot_stem' => 'Not assigned to this attempt',
            'snapshot_options' => $this->snapshot->snapshot_options,
            'metadata' => ['question_type' => 'MC'],
        ]);
        $unassignedSnapshot->tenant_id = $this->tenant->id;
        $unassignedSnapshot->save();

        $response = $this->putJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/answers/{$unassignedSnapshot->id}", [
            'client_request_id' => Str::uuid(),
            'answer_payload' => ['selected_option_id' => $this->snapshot->snapshot_options[0]['id']],
            'answer_state' => 'ANSWERED',
        ]);

        $response->assertStatus(404);
        $this->assertDatabaseMissing('exam_attempt_answers', [
            'exam_attempt_id' => $this->attempt->id,
            'exam_question_snapshot_id' => $unassignedSnapshot->id,
        ]);
    }

    public function test_answer_restore_hides_rows_for_snapshots_not_assigned_to_attempt()
    {
        $this->actingAs($this->student);

        $unassignedSnapshot = new ExamQuestionSnapshot([
            'id' => (string) Str::uuid(),
            'exam_id' => $this->exam->id,
            'source_question_id' => $this->snapshot->source_question_id,
            'source_question_version_id' => $this->snapshot->source_question_version_id,
            'snapshot_stem' => 'Legacy unassigned answer',
            'snapshot_options' => $this->snapshot->snapshot_options,
            'metadata' => ['question_type' => 'MC'],
        ]);
        $unassignedSnapshot->tenant_id = $this->tenant->id;
        $unassignedSnapshot->save();

        \App\Models\ExamAttemptAnswer::create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'exam_attempt_id' => $this->attempt->id,
            'exam_question_snapshot_id' => $unassignedSnapshot->id,
            'student_id' => $this->student->id,
            'answer_payload' => ['selected_option_id' => $this->snapshot->snapshot_options[0]['id']],
            'answer_state' => 'ANSWERED',
            'revision_number' => 1,
            'client_request_id' => 'legacy-unassigned-row',
            'saved_at' => now(),
        ]);

        $response = $this->getJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/answers");
        $response->assertOk()
            ->assertJsonMissing(['exam_question_snapshot_id' => $unassignedSnapshot->id]);
    }

    public function test_snapshot_from_another_exam_rejected()
    {
        $otherExam = Exam::create([
            'tenant_id' => $this->tenant->id,
            'assessment_id' => $this->exam->assessment_id,
            'title' => 'E2',
            'duration_seconds' => 60,
            'created_by' => $this->student->id,
            'status' => 'DRAFT',
            'locked_at' => now()
        ]);

        $q = new Question(['created_by' => $this->student->id]);
        $q->tenant_id = $this->tenant->id;
        $q->save();

        $v = new QuestionVersion([
            'question_id' => $q->id,
            'major_version' => 1, 'minor_version' => 0, 'question_type' => 'MC', 'stem' => 'S', 'is_current' => true, 'created_by' => $this->student->id
        ]);
        $v->tenant_id = $this->tenant->id;
        $v->save();

        $otherSnapshot = new ExamQuestionSnapshot([
            'id' => (string) Str::uuid(),
            'exam_id' => $otherExam->id,
            'source_question_id' => $q->id,
            'source_question_version_id' => $v->id,
            'snapshot_stem' => 'S',
            'snapshot_options' => []
        ]);
        $otherSnapshot->tenant_id = $this->tenant->id;
        $otherSnapshot->save();

        $this->actingAs($this->student);

        $response = $this->putJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/answers/{$otherSnapshot->id}", [
            'client_request_id' => Str::uuid(),
            'answer_payload' => null,
            'answer_state' => 'CLEARED'
        ]);

        $response->assertStatus(404);
    }

    public function test_live_question_bank_isolation()
    {
        $this->actingAs($this->student);

        // 1. Snapshot exists with Option 1
        $optionId = $this->snapshot->snapshot_options[0]['id'];

        // 2. Delete the original question in the bank
        Question::where('id', $this->snapshot->source_question_id)->delete();

        // 3. Attempt to save answer using the snapshot
        $response = $this->putJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/answers/{$this->snapshot->id}", [
            'client_request_id' => Str::uuid(),
            'answer_payload' => ['selected_option_id' => $optionId],
            'answer_state' => 'ANSWERED'
        ]);

        // Should still succeed because it uses snapshots
        $response->assertStatus(200);
    }

    public function test_answer_mutation_on_submitted_attempt_rejected()
    {
        $this->attempt->update(['status' => 'SUBMITTED']);
        $this->actingAs($this->student);

        $response = $this->putJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/answers/{$this->snapshot->id}", [
            'client_request_id' => Str::uuid(),
            'answer_payload' => ['selected_option_id' => $this->snapshot->snapshot_options[0]['id']],
            'answer_state' => 'ANSWERED'
        ]);

        $response->assertStatus(422);
        $this->assertEquals('Attempt is not in progress.', $response->json('message'));
    }

    public function test_answer_mutation_on_expired_attempt_rejected()
    {
        $this->attempt->update(['expires_at' => now()->subMinute()]);
        $this->actingAs($this->student);

        $response = $this->putJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/answers/{$this->snapshot->id}", [
            'client_request_id' => Str::uuid(),
            'answer_payload' => ['selected_option_id' => $this->snapshot->snapshot_options[0]['id']],
            'answer_state' => 'ANSWERED'
        ]);

        $response->assertStatus(422);
        $this->assertEquals('Attempt has expired.', $response->json('message'));
    }

    public function test_concurrent_revision_conflict_prevention()
    {
        $this->actingAs($this->student);
        $optionId = $this->snapshot->snapshot_options[0]['id'];

        // 1. Initial State (Revision 1)
        $this->putJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/answers/{$this->snapshot->id}", [
            'client_request_id' => Str::uuid(),
            'answer_payload' => ['selected_option_id' => $optionId],
            'answer_state' => 'ANSWERED'
        ]);

        // 2. Request A (base_revision 1 -> 2)
        $requestIdA = (string) Str::uuid();
        $responseA = $this->putJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/answers/{$this->snapshot->id}", [
            'client_request_id' => $requestIdA,
            'base_revision' => 1,
            'answer_payload' => ['selected_option_id' => $optionId],
            'answer_state' => 'ANSWERED'
        ]);
        $responseA->assertStatus(200);

        // 3. Request B (stale base_revision 1 -> rejected)
        $responseB = $this->putJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/answers/{$this->snapshot->id}", [
            'client_request_id' => Str::uuid(),
            'base_revision' => 1,
            'answer_payload' => ['selected_option_id' => $optionId],
            'answer_state' => 'ANSWERED'
        ]);

        $responseB->assertStatus(409);
        $responseB->assertJson(['error' => 'ANSWER_REVISION_CONFLICT']);
    }

    public function test_idempotency_same_request_id_no_mutation()
    {
        $this->actingAs($this->student);
        $optionId = $this->snapshot->snapshot_options[0]['id'];
        $requestId = (string) Str::uuid();

        // 1. First call
        $this->putJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/answers/{$this->snapshot->id}", [
            'client_request_id' => $requestId,
            'answer_payload' => ['selected_option_id' => $optionId],
            'answer_state' => 'ANSWERED'
        ]);

        $answerBefore = \App\Models\ExamAttemptAnswer::first();
        $revisionBefore = $answerBefore->revision_number;
        $updatedAtBefore = $answerBefore->updated_at->toDateTimeString();

        // 2. Second call with SAME request ID
        $response = $this->putJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/answers/{$this->snapshot->id}", [
            'client_request_id' => $requestId,
            'answer_payload' => ['selected_option_id' => $optionId],
            'answer_state' => 'ANSWERED'
        ]);

        $response->assertStatus(200);
        $answerAfter = $answerBefore->fresh();

        $this->assertEquals($revisionBefore, $answerAfter->revision_number);
        $this->assertEquals($updatedAtBefore, $answerAfter->updated_at->toDateTimeString());
    }

    public function test_text_questions_draft_validation()
    {
        $this->actingAs($this->student);

        foreach (['SHORT_ANSWER', 'ESSAY'] as $type) {
            $q = new Question(['created_by' => $this->student->id]);
            $q->tenant_id = $this->tenant->id;
            $q->save();

            $v = new QuestionVersion([
                'question_id' => $q->id,
                'major_version' => 1, 'minor_version' => 0, 'question_type' => $type, 'stem' => 'Stem', 'is_current' => true, 'created_by' => $this->student->id
            ]);
            $v->tenant_id = $this->tenant->id;
            $v->save();

            $snapshot = new ExamQuestionSnapshot([
                'id' => (string) Str::uuid(),
                'exam_id' => $this->exam->id,
                'source_question_id' => $q->id,
                'source_question_version_id' => $v->id,
                'snapshot_stem' => 'Stem',
                'snapshot_options' => [],
                'metadata' => ['question_type' => $type]
            ]);
            $snapshot->tenant_id = $this->tenant->id;
            $snapshot->save();
            $this->attempt->update([
                'question_order' => array_values(array_merge($this->attempt->fresh()->question_order ?? [], [(string) $snapshot->id])),
            ]);

            // 1. Normal text answer
            $res1 = $this->putJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/answers/{$snapshot->id}", [
                'client_request_id' => (string) Str::uuid(),
                'answer_payload' => ['text' => 'Jawaban normal'],
                'answer_state' => 'ANSWERED'
            ]);
            $res1->assertStatus(200);

            // 2. Empty string draft
            $res2 = $this->putJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/answers/{$snapshot->id}", [
                'client_request_id' => (string) Str::uuid(),
                'base_revision' => 1,
                'answer_payload' => ['text' => ''],
                'answer_state' => 'ANSWERED'
            ]);
            $res2->assertStatus(200);

            // 3. Null value draft
            $res3 = $this->putJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/answers/{$snapshot->id}", [
                'client_request_id' => (string) Str::uuid(),
                'base_revision' => 2,
                'answer_payload' => ['text' => null],
                'answer_state' => 'ANSWERED'
            ]);
            $res3->assertStatus(200);

            // 4. Missing key 'text' -> Rejected 422
            $res4 = $this->putJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/answers/{$snapshot->id}", [
                'client_request_id' => (string) Str::uuid(),
                'base_revision' => 3,
                'answer_payload' => ['wrong_key' => 'val'],
                'answer_state' => 'ANSWERED'
            ]);
            $res4->assertStatus(422);

            // 5. Invalid type (e.g. array instead of string/null) -> Rejected 422
            $res5 = $this->putJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/answers/{$snapshot->id}", [
                'client_request_id' => (string) Str::uuid(),
                'base_revision' => 3,
                'answer_payload' => ['text' => ['invalid', 'array']],
                'answer_state' => 'ANSWERED'
            ]);
            $res5->assertStatus(422);
        }
    }
}
