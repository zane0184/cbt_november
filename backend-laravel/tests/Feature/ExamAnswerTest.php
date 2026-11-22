<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Assessment;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamParticipant;
use App\Models\StudentProfile;
use App\Models\AcademicYear;
use App\Models\Question;
use App\Models\QuestionVersion;
use App\Models\QuestionOption;
use App\Models\ExamQuestionSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ExamAnswerTest extends TestCase
{
    use RefreshDatabase;

    private $tenant;
    private $student;
    private $exam;
    private $attempt;
    private $snapshot;
    private string $apiToken;
    private string $secondApiToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withHeader('X-CBT-Fence-Generation', '1');
        $this->tenant = Tenant::create(['name' => 'T1', 'code' => 'T1']);
        app(\App\Contexts\TenantContext::class)->setTenantId($this->tenant->id);

        $this->student = User::create(['tenant_id' => $this->tenant->id, 'name' => 'S1', 'email' => 's1@e.com', 'password' => 'p']);
        $this->student->roles()->attach(
            \App\Models\Role::firstOrCreate(['name' => 'SISWA'], ['label' => 'Siswa'])
        );

        StudentProfile::create([
            'user_id' => $this->student->id,
            'nis' => 'ANS-' . Str::random(8),
            'full_name' => $this->student->name,
        ]);

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
        $this->apiToken = $this->student->createToken('answer-test-session')->plainTextToken;
        $this->secondApiToken = $this->student->createToken('second-preexisting-session')->plainTextToken;
        $tokenId = explode('|', $this->apiToken, 2)[0];
        $sessionHash = hash_hmac('sha256', 'sanctum-token:'.$tokenId, (string) config('app.key'));
        $this->attempt->update([
            'question_order' => [(string) $this->snapshot->id],
            'auth_session_hash' => $sessionHash,
            'auth_session_type' => 'SANCTUM_TOKEN',
            'auth_session_bound_at' => now(),
            'fence_generation' => 1,
        ]);
        DeviceLease::create([
            'tenant_id' => $this->tenant->id,
            'exam_attempt_id' => $this->attempt->id,
            'registered_device_id' => null,
            'auth_session_hash' => $sessionHash,
            'fence_generation' => 1,
            'status' => 'ACTIVE',
            'leased_at' => now(),
            'expires_at' => now()->addMinutes(5),
        ]);

        $this->withHeader('X-Device-Binding-ID', 'device-test')
            ->withHeader('Authorization', 'Bearer '.$this->apiToken);
    }


    public function test_matching_payload_rejects_foreign_or_duplicate_pair_ids()
    {
        $this->withHeader('Authorization', 'Bearer '.$this->apiToken);
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
        $this->withHeader('Authorization', 'Bearer '.$this->apiToken);

        $response = $this->withHeader('X-Device-Binding-ID', 'another-device')
            ->getJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/answers");

        $response->assertStatus(409)
            ->assertJsonPath('code', 'CONCURRENT_DEVICE_DENIED');
    }

    public function test_answer_restore_response_omits_internal_and_identity_fields()
    {
        $this->withHeader('Authorization', 'Bearer '.$this->apiToken);
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
        $this->withHeader('Authorization', 'Bearer '.$this->apiToken);
        $response = $this->putJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/answers/{$this->snapshot->id}", [
            'client_request_id' => Str::uuid(),
            'answer_payload' => null,
            'answer_state' => 'ANSWERED',
        ]);

        $response->assertStatus(422);
    }

    public function test_future_base_revision_is_rejected_for_a_new_answer(): void
    {
        $this->withHeader('Authorization', 'Bearer '.$this->apiToken);

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
        $this->withHeader('Authorization', 'Bearer '.$this->apiToken);
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
        $this->withHeader('Authorization', 'Bearer '.$this->apiToken);

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
        $this->withHeader('Authorization', 'Bearer '.$this->apiToken);

        $response = $this->putJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/answers/{$this->snapshot->id}", [
            'client_request_id' => Str::uuid(),
            'answer_payload' => ['selected_option_id' => Str::uuid()], // Random UUID not in snapshot
            'answer_state' => 'ANSWERED'
        ]);

        $response->assertStatus(422);
    }

    public function test_revision_conflict_detection()
    {
        $this->withHeader('Authorization', 'Bearer '.$this->apiToken);
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

        $this->withHeader('Authorization', 'Bearer '.$otherStudent->createToken('foreign-answer-test')->plainTextToken);

        $response = $this->getJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/answers");
        $response->assertStatus(403);
    }

    public function test_idempotent_request_handling()
    {
        $this->withHeader('Authorization', 'Bearer '.$this->apiToken);
        $optionId = $this->snapshot->snapshot_options[0]['id'];
        $requestId = (string) Str::uuid();

        // 1. Initial Request
        $firstResponse = $this->putJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/answers/{$this->snapshot->id}", [
            'client_request_id' => $requestId,
            'answer_payload' => ['selected_option_id' => $optionId],
            'answer_state' => 'ANSWERED'
        ]);
        $firstResponse->assertStatus(200);

        $revision1 = \App\Models\ExamAttemptAnswer::first()->revision_number;

        // 2. Duplicate Request
        $response = $this->putJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/answers/{$this->snapshot->id}", [
            'client_request_id' => $requestId,
            'answer_payload' => ['selected_option_id' => $optionId],
            'answer_state' => 'ANSWERED'
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('idempotent', true);
        $response->assertJsonPath('data.revision_number', $revision1);
        $this->assertEquals($revision1, \App\Models\ExamAttemptAnswer::first()->revision_number);
    }

    public function test_reused_request_id_with_different_payload_is_rejected_without_mutation()
    {
        $this->withHeader('Authorization', 'Bearer '.$this->apiToken);
        $requestId = (string) Str::uuid();
        $url = "/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/answers/{$this->snapshot->id}";

        $this->putJson($url, [
            'client_request_id' => $requestId,
            'answer_payload' => ['selected_option_id' => $this->snapshot->snapshot_options[0]['id']],
            'answer_state' => 'ANSWERED',
            'base_revision' => 0,
        ])->assertStatus(200);

        $response = $this->putJson($url, [
            'client_request_id' => $requestId,
            'answer_payload' => null,
            'answer_state' => 'CLEARED',
            'base_revision' => 1,
        ]);

        $response->assertStatus(409)->assertJsonPath('code', 'IDEMPOTENCY_KEY_REUSED');
        $this->assertDatabaseCount('exam_attempt_answers', 1);
        $this->assertDatabaseHas('exam_attempt_answers', [
            'exam_attempt_id' => $this->attempt->id,
            'exam_question_snapshot_id' => $this->snapshot->id,
            'revision_number' => 1,
            'answer_state' => 'ANSWERED',
        ]);
    }

    public function test_reused_request_id_on_another_assigned_snapshot_is_rejected()
    {
        $this->withHeader('Authorization', 'Bearer '.$this->apiToken);
        $requestId = (string) Str::uuid();
        $url = "/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/answers/{$this->snapshot->id}";

        $this->putJson($url, [
            'client_request_id' => $requestId,
            'answer_payload' => ['selected_option_id' => $this->snapshot->snapshot_options[0]['id']],
            'answer_state' => 'ANSWERED',
            'base_revision' => 0,
        ])->assertStatus(200);

        $anotherSnapshot = $this->createUnassignedSnapshot('Second assigned question');
        $this->attempt->update([
            'question_order' => array_values(array_merge(
                $this->attempt->fresh()->question_order ?? [],
                [(string) $anotherSnapshot->id]
            )),
        ]);

        $response = $this->putJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/answers/{$anotherSnapshot->id}", [
            'client_request_id' => $requestId,
            'answer_payload' => ['selected_option_id' => $this->snapshot->snapshot_options[0]['id']],
            'answer_state' => 'ANSWERED',
            'base_revision' => 0,
        ]);

        $response->assertStatus(409)->assertJsonPath('code', 'IDEMPOTENCY_KEY_REUSED');
        $this->assertDatabaseCount('exam_attempt_answers', 1);
    }

    public function test_answer_read_and_write_are_rejected_after_attempt_is_terminal_or_expired()
    {
        $this->withHeader('Authorization', 'Bearer '.$this->apiToken);
        $url = "/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/answers";

        $this->attempt->update(['status' => 'SUBMITTED']);
        $this->getJson($url)->assertStatus(409)->assertJsonPath('code', 'ATTEMPT_NOT_RESUMABLE');
        $this->putJson($url . "/{$this->snapshot->id}", [
            'client_request_id' => (string) Str::uuid(),
            'answer_payload' => ['selected_option_id' => $this->snapshot->snapshot_options[0]['id']],
            'answer_state' => 'ANSWERED',
        ])->assertStatus(422)->assertJsonPath('code', 'ATTEMPT_CLOSED');

        $this->attempt->update(['status' => 'IN_PROGRESS', 'expires_at' => now()->subSecond()]);
        $this->getJson($url)->assertStatus(409)->assertJsonPath('code', 'ATTEMPT_NOT_RESUMABLE');
        $this->putJson($url . "/{$this->snapshot->id}", [
            'client_request_id' => (string) Str::uuid(),
            'answer_payload' => ['selected_option_id' => $this->snapshot->snapshot_options[0]['id']],
            'answer_state' => 'ANSWERED',
        ])->assertStatus(422)->assertJsonPath('code', 'ATTEMPT_EXPIRED');
    }

    public function test_all_question_types_payload_validation()
    {
        $this->withHeader('Authorization', 'Bearer '.$this->apiToken);

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

            if ($type === 'SHORT_ANSWER') {
                $response->assertStatus(422);
                $this->assertDatabaseMissing('exam_attempt_answers', [
                    'exam_attempt_id' => $this->attempt->id,
                    'exam_question_snapshot_id' => $snapshot->id,
                ]);
            } else {
                $response->assertStatus(200);
            }
        }
    }

    public function test_same_exam_snapshot_not_in_attempt_question_order_is_rejected()
    {
        $this->withHeader('Authorization', 'Bearer '.$this->apiToken);

        $unassignedSnapshot = $this->createUnassignedSnapshot('Not assigned to this attempt');

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
        $this->withHeader('Authorization', 'Bearer '.$this->apiToken);

        $unassignedSnapshot = $this->createUnassignedSnapshot('Legacy unassigned answer');

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

        $this->withHeader('Authorization', 'Bearer '.$this->apiToken);

        $response = $this->putJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/answers/{$otherSnapshot->id}", [
            'client_request_id' => Str::uuid(),
            'answer_payload' => null,
            'answer_state' => 'CLEARED'
        ]);

        $response->assertStatus(404);
    }

    public function test_live_question_bank_isolation()
    {
        $this->withHeader('Authorization', 'Bearer '.$this->apiToken);

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
        $this->withHeader('Authorization', 'Bearer '.$this->apiToken);

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
        $this->withHeader('Authorization', 'Bearer '.$this->apiToken);

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
        $this->withHeader('Authorization', 'Bearer '.$this->apiToken);
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
        $this->withHeader('Authorization', 'Bearer '.$this->apiToken);
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
        $this->withHeader('Authorization', 'Bearer '.$this->apiToken);

        foreach (['ESSAY'] as $type) {
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
    /**
     * Create a different source question so the exam/source-question unique
     * constraint remains intact while testing an unassigned attempt snapshot.
     */
    private function createUnassignedSnapshot(string $stem): ExamQuestionSnapshot
    {
        $question = new Question(['created_by' => $this->student->id]);
        $question->tenant_id = $this->tenant->id;
        $question->save();

        $version = new QuestionVersion([
            'question_id' => $question->id,
            'major_version' => 1,
            'minor_version' => 0,
            'question_type' => 'MC',
            'stem' => $stem,
            'is_current' => true,
            'created_by' => $this->student->id,
        ]);
        $version->tenant_id = $this->tenant->id;
        $version->save();

        $snapshot = new ExamQuestionSnapshot([
            'id' => (string) Str::uuid(),
            'exam_id' => $this->exam->id,
            'source_question_id' => $question->id,
            'source_question_version_id' => $version->id,
            'snapshot_stem' => $stem,
            'snapshot_options' => $this->snapshot->snapshot_options,
            'metadata' => ['question_type' => 'MC'],
        ]);
        $snapshot->tenant_id = $this->tenant->id;
        $snapshot->save();

        return $snapshot;
    }

    public function test_late_retry_of_superseded_request_replays_receipt_without_overwriting_newer_answer()
    {
        $this->withHeader('Authorization', 'Bearer '.$this->apiToken);
        $url = "/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/answers/{$this->snapshot->id}";
        $firstRequestId = (string) Str::uuid();
        $secondRequestId = (string) Str::uuid();
        $optionId = $this->snapshot->snapshot_options[0]['id'];

        $this->putJson($url, [
            'client_request_id' => $firstRequestId,
            'base_revision' => 0,
            'answer_payload' => ['selected_option_id' => $optionId],
            'answer_state' => 'ANSWERED',
        ])->assertStatus(200);

        $this->putJson($url, [
            'client_request_id' => $secondRequestId,
            'base_revision' => 1,
            'answer_payload' => ['selected_option_id' => $optionId, 'note' => 'newer edit'],
            'answer_state' => 'ANSWERED',
        ])->assertStatus(200);

        $lateRetry = $this->putJson($url, [
            'client_request_id' => $firstRequestId,
            'base_revision' => 0,
            'answer_payload' => ['selected_option_id' => $optionId],
            'answer_state' => 'ANSWERED',
        ]);

        $lateRetry->assertOk()
            ->assertJsonPath('idempotent', true)
            ->assertJsonPath('data.revision_number', 1);
        $this->assertDatabaseCount('exam_attempt_answers', 1);
        $this->assertDatabaseHas('exam_attempt_answers', [
            'exam_attempt_id' => $this->attempt->id,
            'exam_question_snapshot_id' => $this->snapshot->id,
            'revision_number' => 2,
            'client_request_id' => $secondRequestId,
        ]);
    }


    public function test_second_authentication_token_cannot_use_same_attempt_even_with_same_device_id(): void
    {
        $secondToken = $this->secondApiToken;
        $base = "/api/v1/exams/{$this->exam->id}";
        $attempt = "/attempts/{$this->attempt->id}";

        $this->withHeader('Authorization', 'Bearer '.$secondToken)
            ->getJson($base.'/execution')
            ->assertStatus(409)
            ->assertJsonPath('code', 'ATTEMPT_SESSION_MISMATCH');

        $this->withHeader('Authorization', 'Bearer '.$secondToken)
            ->getJson($base.$attempt.'/answers')
            ->assertStatus(409)
            ->assertJsonPath('code', 'ATTEMPT_SESSION_MISMATCH');

        $this->withHeader('Authorization', 'Bearer '.$secondToken)
            ->putJson($base.$attempt.'/answers/'.$this->snapshot->id, [
                'client_request_id' => 'second-session-write',
                'base_revision' => 0,
                'answer_payload' => ['selected_option_id' => 'foreign-option'],
                'answer_state' => 'ANSWERED',
            ])
            ->assertStatus(409)
            ->assertJsonPath('code', 'ATTEMPT_SESSION_MISMATCH');

        $this->withHeader('Authorization', 'Bearer '.$secondToken)
            ->postJson($base.$attempt.'/heartbeat')
            ->assertStatus(409)
            ->assertJsonPath('code', 'ATTEMPT_SESSION_MISMATCH');

        $this->withHeader('Authorization', 'Bearer '.$secondToken)
            ->postJson($base.$attempt.'/submit')
            ->assertStatus(409)
            ->assertJsonPath('code', 'ATTEMPT_SESSION_MISMATCH');

        $this->withHeader('Authorization', 'Bearer '.$secondToken)
            ->getJson($base.$attempt)
            ->assertNotFound();

        $this->assertDatabaseCount('exam_attempt_answers', 0);
        $this->assertSame('IN_PROGRESS', $this->attempt->fresh()->status);

        $this->withHeader('Authorization', 'Bearer '.$this->apiToken)
            ->getJson($base.$attempt.'/answers')
            ->assertOk();
    }

    public function test_old_idempotency_request_replay_returns_original_receipt_without_overwriting_newer_revision(): void
    {
        $this->withHeader('Authorization', 'Bearer '.$this->apiToken);
        $url = "/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/answers/{$this->snapshot->id}";
        $optionA = (string) data_get($this->snapshot->snapshot_options, '0.id');
        $optionB = (string) Str::uuid();
        $this->snapshot->update(['snapshot_options' => array_merge(
            $this->snapshot->snapshot_options,
            [['id' => $optionB, 'content' => 'Option 2']]
        )]);
        $firstPayload = ['selected_option_id' => $optionA];
        $secondPayload = ['selected_option_id' => $optionB];

        $first = $this->putJson($url, [
            'client_request_id' => 'immutable-request-1',
            'base_revision' => 0,
            'answer_payload' => $firstPayload,
            'answer_state' => 'ANSWERED',
        ]);
        $first->assertOk()->assertJsonPath('data.revision_number', 1);
        $firstReceipt = $first->json('receipt.id');

        $second = $this->putJson($url, [
            'client_request_id' => 'immutable-request-2',
            'base_revision' => 1,
            'answer_payload' => $secondPayload,
            'answer_state' => 'ANSWERED',
        ]);
        $second->assertOk()->assertJsonPath('data.revision_number', 2);

        $replay = $this->putJson($url, [
            'client_request_id' => 'immutable-request-1',
            'base_revision' => 0,
            'answer_payload' => $firstPayload,
            'answer_state' => 'ANSWERED',
        ]);
        $replay->assertOk()
            ->assertJsonPath('idempotent', true)
            ->assertJsonPath('data.revision_number', 1)
            ->assertJsonPath('receipt.id', $firstReceipt);

        $this->assertDatabaseHas('exam_attempt_answers', [
            'exam_attempt_id' => $this->attempt->id,
            'revision_number' => 2,
        ]);
        $savedAnswer = ExamAttemptAnswer::query()
            ->where('tenant_id', $this->tenant->id)
            ->where('exam_attempt_id', $this->attempt->id)
            ->firstOrFail();
        $this->assertSame($secondPayload, $savedAnswer->answer_payload);
        $this->assertDatabaseCount('exam_attempt_answer_requests', 2);
    }



    public function test_expired_lease_can_only_be_recovered_by_the_same_bound_auth_session_and_advances_fence(): void
    {
        DeviceLease::query()->where('exam_attempt_id', $this->attempt->id)->update([
            'expires_at' => now()->subSecond(),
        ]);

        $this->withHeader('Authorization', 'Bearer '.$this->apiToken)
            ->postJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/heartbeat")
            ->assertOk();

        $this->assertSame(2, (int) $this->attempt->fresh()->fence_generation);
        $this->assertDatabaseHas('device_leases', [
            'exam_attempt_id' => $this->attempt->id,
            'fence_generation' => 1,
            'status' => 'EXPIRED',
        ]);
        $this->assertDatabaseHas('device_leases', [
            'exam_attempt_id' => $this->attempt->id,
            'fence_generation' => 2,
            'status' => 'ACTIVE',
        ]);

        $this->withHeader('Authorization', 'Bearer '.$this->secondApiToken)
            ->postJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/heartbeat")
            ->assertStatus(409)
            ->assertJsonPath('code', 'ATTEMPT_SESSION_MISMATCH');
    }

    public function test_submit_releases_lease_and_increments_fence_generation(): void
    {
        $this->withHeader('Authorization', 'Bearer '.$this->apiToken)
            ->postJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/submit")
            ->assertOk();

        $this->assertSame('SUBMITTED', $this->attempt->fresh()->status);
        $this->assertSame(2, (int) $this->attempt->fresh()->fence_generation);
        $this->assertDatabaseHas('device_leases', [
            'exam_attempt_id' => $this->attempt->id,
            'fence_generation' => 1,
            'status' => 'RELEASED',
            'release_reason' => 'STUDENT_SUBMITTED',
        ]);
    }



    public function test_student_media_requires_the_same_bound_auth_session_and_active_lease(): void
    {
        Storage::fake('local');
        $path = 'private/tenants/'.$this->tenant->id.'/media/exam-figure.pdf';
        Storage::disk('local')->put($path, 'exam media');

        $media = MediaAsset::create([
            'tenant_id' => $this->tenant->id,
            'owner_type' => 'QUESTION',
            'owner_id' => $this->snapshot->source_question_id,
            'file_name' => 'exam-figure.pdf',
            'original_name' => 'exam-figure.pdf',
            'storage_path' => $path,
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'file_size' => 10,
            'disk' => 'local',
            'uploaded_by' => $this->student->id,
        ]);
        $this->snapshot->update([
            'snapshot_media' => [['id' => $media->id, 'role' => 'STEM', 'mime_type' => 'application/pdf']],
        ]);

        $url = "/api/v1/media/{$media->id}";
        $this->withHeader('Authorization', 'Bearer '.$this->secondApiToken)
            ->get($url)
            ->assertForbidden();

        $this->withHeader('Authorization', 'Bearer '.$this->apiToken)
            ->get($url)
            ->assertOk();
    }


}
