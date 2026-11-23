<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\Exam;
use App\Models\ExamParticipant;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Models\QuestionVersion;
use App\Models\ExamQuestionSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Str;

class ExamSessionHardeningTest extends TestCase
{
    use RefreshDatabase;

    private $tenantA;
    private $tenantB;
    private $userA;
    private $userB;
    private $examA;
    private $participantA;
    private string $tokenA;
    private string $tokenB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantA = Tenant::create(['name' => 'Madrasah A', 'code' => 'A']);
        $this->tenantB = Tenant::create(['name' => 'Madrasah B', 'code' => 'B']);

        $context = app(\App\Contexts\TenantContext::class);

        // Set context to A for creating Tenant A data
        $context->setTenantId($this->tenantA->id);

        $siswaRole = Role::create(['name' => 'SISWA', 'label' => 'Siswa']);

        $this->userA = User::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Student A',
            'email' => 'studenta@example.com',
            'password' => bcrypt('password')
        ]);
        $this->userA->roles()->attach($siswaRole);
        $this->tokenA = $this->userA->createToken('exam-session-user-a')->plainTextToken;

        $year = AcademicYear::create(['name' => '2024/2025', 'is_active' => true]);

        $assessment = Assessment::create([
            'academic_year_id' => $year->id,
            'title' => 'Sumatif Akhir',
            'assessment_type' => 'SUMMATIVE',
            'created_by' => $this->userA->id
        ]);

        $this->examA = Exam::create([
            'assessment_id' => $assessment->id,
            'title' => 'Ujian Akhir Matematika',
            'duration_seconds' => 3600,
            'start_at' => now()->subMinutes(10)->toIso8601String(),
            'end_at' => now()->addHours(2)->toIso8601String(),
            'timezone' => 'Asia/Jakarta',
            'status' => 'PUBLISHED',
            'attempt_limit' => 2,
            'require_token' => true,
            'token' => 'ABC123',
            'created_by' => $this->userA->id,
            'locked_at' => now()
        ]);

        $this->participantA = ExamParticipant::create([
            'exam_id' => $this->examA->id,
            'user_id' => $this->userA->id,
            'status' => 'ASSIGNED'
        ]);
        $this->createSnapshot();

        // Switch context to B for creating Tenant B data
        $context->setTenantId($this->tenantB->id);
        $this->userB = User::create([
            'tenant_id' => $this->tenantB->id,
            'name' => 'Student B',
            'email' => 'studentb@example.com',
            'password' => bcrypt('password')
        ]);
        $this->userB->roles()->attach($siswaRole);
        $this->tokenB = $this->userB->createToken('exam-session-user-b')->plainTextToken;

        // Clear context for the actual test runs to ensure middleware handles it
        $context->setTenantId(null);
        \App\Contexts\TenantContext::resetInstance();
    }

    private function createSnapshot()
    {
        // Ensure context is set for Tenant A when creating snapshots
        $context = app(\App\Contexts\TenantContext::class);
        $originalTenantId = $context->getTenantId();
        $context->setTenantId($this->tenantA->id);

        try {
            $question = Question::create([
                'owner_type' => 'TENANT',
                'owner_user_id' => $this->userA->id,
                'question_code' => 'Q-' . Str::random(5),
                'created_by' => $this->userA->id
            ]);

            $version = QuestionVersion::create([
                'question_id' => $question->id,
                'major_version' => 1,
                'minor_version' => 0,
                'question_type' => 'MC',
                'stem' => 'What is 1+1?',
                'is_current' => true,
                'created_by' => $this->userA->id
            ]);

            $snapshot = ExamQuestionSnapshot::create([
                'exam_id' => $this->examA->id,
                'source_question_id' => $question->id,
                'source_question_version_id' => $version->id,
                'sequence' => 1,
                'points' => 10,
                'snapshot_stem' => 'What is 1+1?',
                'snapshot_options' => [['id' => 'opt1', 'text' => '2']],
                'metadata' => ['question_type' => 'MC']
            ]);

            return $snapshot;
        } finally {
            // Restore original context
            $context->setTenantId($originalTenantId);
        }
    }


    public function test_student_attempt_show_returns_only_public_attempt_fields(): void
    {
        $this->withToken($this->tokenA);

        $start = $this->withHeaders(['X-Device-Binding-ID' => 'device-A', 'X-CBT-Fence-Generation' => '1'])
            ->postJson("/api/v1/exams/{$this->examA->id}/attempts", ['token' => 'ABC123']);
        $start->assertStatus(201);
        $attemptId = $start->json('data.id');

        $show = $this->getJson("/api/v1/exams/{$this->examA->id}/attempts/{$attemptId}");
        $show->assertOk();
        $this->assertPublicAttemptPayload($show->json('data'));
    }

    private function assertPublicAttemptPayload(?array $payload): void
    {
        $this->assertIsArray($payload);
        $this->assertSame(
            ['id', 'status', 'attempt_number', 'started_at', 'expires_at', 'submitted_at'],
            array_keys($payload)
        );

        foreach ([
            'device_fingerprint',
            'server_start_reference',
            'question_order',
            'options_order',
            'current_device_token',
            'fence_generation',
            'device_lease_expires_at',
            'last_lease_heartbeat_at',
        ] as $sensitiveField) {
            $this->assertArrayNotHasKey($sensitiveField, $payload);
        }
    }

    public function test_missing_device_binding_header_is_rejected()
    {
        $this->withToken($this->tokenA);

        $response = $this->postJson("/api/v1/exams/{$this->examA->id}/attempts", ['token' => 'ABC123']);
        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'code' => 'MISSING_DEVICE_BINDING'
        ]);
    }

    public function test_first_device_binds_and_creates_attempt()
    {
        $this->withToken($this->tokenA);

        $response = $this->withHeaders(['X-Device-Binding-ID' => 'device-A', 'X-CBT-Fence-Generation' => '1'])
            ->postJson("/api/v1/exams/{$this->examA->id}/attempts", ['token' => 'ABC123']);

        $response->assertStatus(201);
        $this->assertPublicAttemptPayload($response->json('data'));
        $this->assertDatabaseHas('exam_attempts', [
            'exam_id' => $this->examA->id,
            'device_fingerprint' => 'device-A'
        ]);
    }

    public function test_same_device_can_resume_attempt()
    {
        $this->withToken($this->tokenA);

        $this->withHeaders(['X-Device-Binding-ID' => 'device-A', 'X-CBT-Fence-Generation' => '1'])
            ->postJson("/api/v1/exams/{$this->examA->id}/attempts", ['token' => 'ABC123']);

        $response = $this->withHeaders(['X-Device-Binding-ID' => 'device-A', 'X-CBT-Fence-Generation' => '1'])
            ->postJson("/api/v1/exams/{$this->examA->id}/attempts", ['token' => 'ABC123']);

        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'message' => 'Resuming existing attempt.']);
        $this->assertPublicAttemptPayload($response->json('data'));
    }

    public function test_different_device_resume_is_denied()
    {
        $this->withToken($this->tokenA);

        $this->withHeaders(['X-Device-Binding-ID' => 'device-A', 'X-CBT-Fence-Generation' => '1'])
            ->postJson("/api/v1/exams/{$this->examA->id}/attempts", ['token' => 'ABC123']);

        $response = $this->withHeaders(['X-Device-Binding-ID' => 'device-B', 'X-CBT-Fence-Generation' => '1'])
            ->postJson("/api/v1/exams/{$this->examA->id}/attempts", ['token' => 'ABC123']);

        $response->assertStatus(409);
        $response->assertJson([
            'success' => false,
            'code' => 'CONCURRENT_DEVICE_DENIED'
        ]);
    }

    public function test_same_device_execution_is_allowed()
    {
        $this->withToken($this->tokenA);

        $this->withHeaders(['X-Device-Binding-ID' => 'device-A', 'X-CBT-Fence-Generation' => '1'])
            ->postJson("/api/v1/exams/{$this->examA->id}/attempts", ['token' => 'ABC123']);

        $response = $this->withHeaders(['X-Device-Binding-ID' => 'device-A', 'X-CBT-Fence-Generation' => '1'])
            ->getJson("/api/v1/exams/{$this->examA->id}/execution");

        $response->assertStatus(200);
    }

    public function test_different_device_execution_is_denied()
    {
        $this->withToken($this->tokenA);

        $this->withHeaders(['X-Device-Binding-ID' => 'device-A', 'X-CBT-Fence-Generation' => '1'])
            ->postJson("/api/v1/exams/{$this->examA->id}/attempts", ['token' => 'ABC123']);

        $response = $this->withHeaders(['X-Device-Binding-ID' => 'device-B', 'X-CBT-Fence-Generation' => '1'])
            ->getJson("/api/v1/exams/{$this->examA->id}/execution");

        $response->assertStatus(409);
        $response->assertJson([
            'success' => false,
            'code' => 'CONCURRENT_DEVICE_DENIED'
        ]);
    }

    public function test_same_device_heartbeat_is_allowed()
    {
        $this->withToken($this->tokenA);

        $res = $this->withHeaders(['X-Device-Binding-ID' => 'device-A', 'X-CBT-Fence-Generation' => '1'])
            ->postJson("/api/v1/exams/{$this->examA->id}/attempts", ['token' => 'ABC123']);

        $attemptId = $res->json('data.id');

        $response = $this->withHeaders(['X-Device-Binding-ID' => 'device-A', 'X-CBT-Fence-Generation' => '1'])
            ->postJson("/api/v1/exams/{$this->examA->id}/attempts/{$attemptId}/heartbeat");

        $response->assertStatus(200);
    }

    public function test_different_device_heartbeat_is_denied()
    {
        $this->withToken($this->tokenA);

        $res = $this->withHeaders(['X-Device-Binding-ID' => 'device-A', 'X-CBT-Fence-Generation' => '1'])
            ->postJson("/api/v1/exams/{$this->examA->id}/attempts", ['token' => 'ABC123']);

        $attemptId = $res->json('data.id');

        $response = $this->withHeaders(['X-Device-Binding-ID' => 'device-B', 'X-CBT-Fence-Generation' => '1'])
            ->postJson("/api/v1/exams/{$this->examA->id}/attempts/{$attemptId}/heartbeat");

        $response->assertStatus(409);
        $response->assertJson([
            'success' => false,
            'code' => 'CONCURRENT_DEVICE_DENIED'
        ]);
    }

    public function test_answer_from_different_device_is_denied()
    {
        $this->withToken($this->tokenA);

        $res = $this->withHeaders(['X-Device-Binding-ID' => 'device-A', 'X-CBT-Fence-Generation' => '1'])
            ->postJson("/api/v1/exams/{$this->examA->id}/attempts", ['token' => 'ABC123']);

        $attemptId = $res->json('data.id');

        $snapshot = $this->createSnapshot();
        ExamAttempt::whereKey($attemptId)->update(['question_order' => [(string) $snapshot->id]]);

        $response = $this->withHeaders(['X-Device-Binding-ID' => 'device-B', 'X-CBT-Fence-Generation' => '1'])
            ->putJson("/api/v1/exams/{$this->examA->id}/attempts/{$attemptId}/answers/{$snapshot->id}", [
                'client_request_id' => 'req-1',
                'answer_state' => 'ANSWERED',
                'answer_payload' => ['selected_option_id' => 'opt1']
            ]);

        $response->assertStatus(409);
        $response->assertJson([
            'success' => false,
            'code' => 'CONCURRENT_DEVICE_DENIED'
        ]);
    }

    public function test_submit_from_different_device_is_denied()
    {
        $this->withToken($this->tokenA);

        $res = $this->withHeaders(['X-Device-Binding-ID' => 'device-A', 'X-CBT-Fence-Generation' => '1'])
            ->postJson("/api/v1/exams/{$this->examA->id}/attempts", ['token' => 'ABC123']);

        $attemptId = $res->json('data.id');

        $response = $this->withHeaders(['X-Device-Binding-ID' => 'device-B', 'X-CBT-Fence-Generation' => '1'])
            ->postJson("/api/v1/exams/{$this->examA->id}/attempts/{$attemptId}/submit");

        $response->assertStatus(409);
        $response->assertJson([
            'success' => false,
            'code' => 'CONCURRENT_DEVICE_DENIED'
        ]);
    }

    public function test_server_authoritative_timer()
    {
        $this->withToken($this->tokenA);

        $this->withHeaders(['X-Device-Binding-ID' => 'device-A', 'X-CBT-Fence-Generation' => '1'])
            ->postJson("/api/v1/exams/{$this->examA->id}/attempts", ['token' => 'ABC123']);

        $response = $this->withHeaders(['X-Device-Binding-ID' => 'device-A', 'X-CBT-Fence-Generation' => '1'])
            ->getJson("/api/v1/exams/{$this->examA->id}/execution");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'exam' => ['server_now', 'duration_seconds'],
                'attempt' => ['expires_at']
            ]
        ]);
    }

    public function test_expired_attempt_returns_appropriate_error()
    {
        $this->withToken($this->tokenA);

        $res = $this->withHeaders(['X-Device-Binding-ID' => 'device-A', 'X-CBT-Fence-Generation' => '1'])
            ->postJson("/api/v1/exams/{$this->examA->id}/attempts", ['token' => 'ABC123']);

        $attemptId = $res->json('data.id');

        // Artificially expire the attempt row
        ExamAttempt::find($attemptId)->update(['expires_at' => now()->subMinutes(1)]);

        $snapshot = $this->createSnapshot();
        ExamAttempt::whereKey($attemptId)->update(['question_order' => [(string) $snapshot->id]]);

        $response = $this->withHeaders(['X-Device-Binding-ID' => 'device-A', 'X-CBT-Fence-Generation' => '1'])
            ->putJson("/api/v1/exams/{$this->examA->id}/attempts/{$attemptId}/answers/{$snapshot->id}", [
                'client_request_id' => 'req-2',
                'answer_state' => 'ANSWERED',
                'answer_payload' => ['selected_option_id' => 'opt1']
            ]);

        $response->assertStatus(422);
        $response->assertJson(['success' => false, 'message' => 'Attempt has expired.']);
    }

    public function test_cross_tenant_attempt_access_remains_denied()
    {
        $this->withToken($this->tokenB); // User B belongs to Tenant B

        $response = $this->withHeaders(['X-Device-Binding-ID' => 'device-B', 'X-CBT-Fence-Generation' => '1'])
            ->getJson("/api/v1/exams/{$this->examA->id}/execution");

        $response->assertStatus(404);
    }
}
