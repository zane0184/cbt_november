<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptAnswer;
use App\Models\ExamParticipant;
use App\Models\ExamQuestionSnapshot;
use App\Models\Question;
use App\Models\QuestionVersion;
use App\Models\QuestionOption;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ServerTimeAndClockTamperingTest extends TestCase
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

        $this->student = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'S1',
            'email' => 's1@e.com',
            'password' => bcrypt('password')
        ]);
        $this->student->roles()->attach(
            \App\Models\Role::firstOrCreate(['name' => 'SISWA'], ['label' => 'Siswa'])
        );

        $ay = AcademicYear::create(['tenant_id' => $this->tenant->id, 'name' => '2024']);
        $assessment = Assessment::create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $ay->id,
            'title' => 'A1',
            'assessment_type' => 'DAILY',
            'created_by' => $this->student->id
        ]);

        $this->exam = Exam::create([
            'tenant_id' => $this->tenant->id,
            'assessment_id' => $assessment->id,
            'title' => 'Server Time Test Exam',
            'duration_seconds' => 1800,
            'created_by' => $this->student->id,
            'status' => 'PUBLISHED',
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
            'device_fingerprint' => 'device-6g4',
            'started_at' => now()->subMinutes(5),
            'expires_at' => now()->addMinutes(25)
        ]);
        $this->attempt->tenant_id = $this->tenant->id;
        $this->attempt->save();

        $q = new Question(['created_by' => $this->student->id]);
        $q->tenant_id = $this->tenant->id;
        $q->save();

        $v = new QuestionVersion([
            'question_id' => $q->id,
            'major_version' => 1,
            'minor_version' => 0,
            'question_type' => 'MC',
            'stem' => 'What is 1+1?',
            'is_current' => true,
            'created_by' => $this->student->id
        ]);
        $v->tenant_id = $this->tenant->id;
        $v->save();

        $opt1 = new QuestionOption([
            'content' => '2',
            'is_correct' => true,
            'score_weight' => 10,
            'question_version_id' => $v->id
        ]);
        $opt1->tenant_id = $this->tenant->id;
        $opt1->save();

        $this->snapshot = new ExamQuestionSnapshot([
            'id' => (string) Str::uuid(),
            'source_question_id' => $q->id,
            'source_question_version_id' => $v->id,
            'sequence' => 1,
            'points' => 10,
            'snapshot_stem' => 'What is 1+1?',
            'snapshot_options' => [
                ['id' => $opt1->id, 'option_text' => '2', 'is_correct' => true, 'score_weight' => 10]
            ],
            'snapshot_media' => [],
            'metadata' => ['question_type' => 'MC']
        ]);
        $this->snapshot->tenant_id = $this->tenant->id;
        $this->snapshot->exam_id = $this->exam->id;
        $this->snapshot->save();

        $this->attempt->update([
            'question_order' => [$this->snapshot->id],
            'options_order' => [$this->snapshot->id => [$opt1->id]]
        ]);
    }

    public function test_test_a_normal_server_time_and_answer()
    {
        $this->actingAs($this->student, 'sanctum');

        $response = $this->withHeader('X-Device-Binding-ID', 'device-6g4')
            ->getJson("/api/v1/exams/{$this->exam->id}/execution");

        $response->assertStatus(200);
        $serverNow = $response->json('data.exam.server_now');
        $expiresAt = $response->json('data.attempt.expires_at');

        $this->assertNotNull($serverNow);
        $this->assertNotNull($expiresAt);

        $optId = $this->snapshot->snapshot_options[0]['id'];

        // Submit answer
        $ansResponse = $this->withHeader('X-Device-Binding-ID', 'device-6g4')
            ->putJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/answers/{$this->snapshot->id}", [
                'client_request_id' => 'req-normal-1',
                'answer_state' => 'ANSWERED',
                'answer_payload' => ['selected_option_id' => $optId]
            ]);

        $ansResponse->assertStatus(200);
        $this->assertEquals($optId, ExamAttemptAnswer::where('exam_attempt_id', $this->attempt->id)->first()->answer_payload['selected_option_id']);
    }

    public function test_test_b_and_c_device_clock_tampering_ignored_by_server()
    {
        $this->actingAs($this->student, 'sanctum');

        $optId = $this->snapshot->snapshot_options[0]['id'];

        // Even if client sends fake Date/Timestamp headers or payload with client time, server relies on Carbon now()
        $response = $this->withHeaders([
            'X-Device-Binding-ID' => 'device-6g4',
            'Date' => 'Mon, 01 Jan 2030 00:00:00 GMT', // Client clock far in future
            'X-Client-Timestamp' => '2030-01-01T00:00:00Z'
        ])->getJson("/api/v1/exams/{$this->exam->id}/execution");

        $response->assertStatus(200);
        $serverNow = $response->json('data.exam.server_now');

        // Server now must NOT follow client's 2030 timestamp
        $this->assertStringNotContainsString('2030', $serverNow);

        // Answers are saved based on actual server time
        $ansResponse = $this->withHeaders([
            'X-Device-Binding-ID' => 'device-6g4',
            'Date' => 'Sun, 01 Jan 2010 00:00:00 GMT' // Client clock far in past
        ])->putJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/answers/{$this->snapshot->id}", [
            'client_request_id' => 'req-clock-1',
            'answer_state' => 'ANSWERED',
            'answer_payload' => ['selected_option_id' => $optId]
        ]);

        $ansResponse->assertStatus(200);
    }

    public function test_test_d_expired_attempt_answer_rejected_regardless_of_client_time()
    {
        $this->actingAs($this->student, 'sanctum');

        $optId = $this->snapshot->snapshot_options[0]['id'];

        // Set attempt expires_at to past on server
        $this->attempt->update([
            'expires_at' => now()->subMinutes(10)
        ]);

        // Attempt answer submission while expired
        $response = $this->withHeader('X-Device-Binding-ID', 'device-6g4')
            ->putJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/answers/{$this->snapshot->id}", [
                'client_request_id' => 'req-expired-1',
                'answer_state' => 'ANSWERED',
                'answer_payload' => ['selected_option_id' => $optId]
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Attempt has expired.'
        ]);
    }

    public function test_test_e_client_timestamp_manipulation_has_no_effect()
    {
        $this->actingAs($this->student, 'sanctum');

        $optId = $this->snapshot->snapshot_options[0]['id'];

        // Send a custom timestamp payload
        $response = $this->withHeader('X-Device-Binding-ID', 'device-6g4')
            ->putJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/answers/{$this->snapshot->id}", [
                'client_request_id' => 'req-ts-1',
                'answer_state' => 'ANSWERED',
                'answer_payload' => ['selected_option_id' => $optId],
                'client_timestamp' => '1999-01-01T00:00:00Z'
            ]);

        $response->assertStatus(200);
        $savedAnswer = ExamAttemptAnswer::where('exam_attempt_id', $this->attempt->id)->first();

        // The saved_at must be server time, not 1999
        $this->assertStringNotContainsString('1999', $savedAnswer->saved_at->toIso8601String());
    }

    public function test_test_f_server_now_consistency()
    {
        $this->actingAs($this->student, 'sanctum');

        $optId = $this->snapshot->snapshot_options[0]['id'];

        $execRes = $this->withHeader('X-Device-Binding-ID', 'device-6g4')
            ->getJson("/api/v1/exams/{$this->exam->id}/execution");
        $execRes->assertStatus(200);
        $serverNow1 = $execRes->json('data.exam.server_now');

        $ansRes = $this->withHeader('X-Device-Binding-ID', 'device-6g4')
            ->putJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/answers/{$this->snapshot->id}", [
                'client_request_id' => 'req-consistency-1',
                'answer_state' => 'ANSWERED',
                'answer_payload' => ['selected_option_id' => $optId]
            ]);
        $ansRes->assertStatus(200);
        $serverNow2 = $ansRes->json('data.server_now');

        $this->assertNotNull($serverNow1);
        $this->assertNotNull($serverNow2);
    }
}
