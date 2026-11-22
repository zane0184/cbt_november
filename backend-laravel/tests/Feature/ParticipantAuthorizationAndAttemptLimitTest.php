<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamParticipant;
use App\Models\ExamQuestionSnapshot;
use App\Models\Question;
use App\Models\QuestionVersion;
use App\Models\QuestionOption;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ParticipantAuthorizationAndAttemptLimitTest extends TestCase
{
    use RefreshDatabase;

    private $tenantA;
    private $tenantB;
    private $userA1; // Authorized participant
    private $userA2; // Non-participant in Tenant A
    private $userB;  // Student in Tenant B
    private $examA;
    private $participantA1;
    private $snapshot;
    private array $apiTokens = [];

    private function apiTokenFor(User $user): string
    {
        if (!isset($this->apiTokens[$user->id])) {
            $this->apiTokens[$user->id] = $user->createToken('attempt-test-session')->plainTextToken;
        }

        return $this->apiTokens[$user->id];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantA = Tenant::create(['name' => 'Madrasah A', 'code' => 'TA']);
        $this->tenantB = Tenant::create(['name' => 'Madrasah B', 'code' => 'TB']);

        $context = app(\App\Contexts\TenantContext::class);

        // --- Tenant A Setup ---
        $context->setTenantId($this->tenantA->id);
        $siswaRole = Role::firstOrCreate(['name' => 'SISWA'], ['label' => 'Siswa']);

        $this->userA1 = User::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Student A1 Authorized',
            'email' => 'studenta1@example.com',
            'password' => bcrypt('password')
        ]);
        $this->userA1->roles()->attach($siswaRole);

        $this->userA2 = User::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Student A2 NonParticipant',
            'email' => 'studenta2@example.com',
            'password' => bcrypt('password')
        ]);
        $this->userA2->roles()->attach($siswaRole);

        $yearA = AcademicYear::create(['tenant_id' => $this->tenantA->id, 'name' => '2024/2025']);
        $assessmentA = Assessment::create([
            'tenant_id' => $this->tenantA->id,
            'academic_year_id' => $yearA->id,
            'title' => 'Sumatif A',
            'assessment_type' => 'SUMMATIVE',
            'created_by' => $this->userA1->id
        ]);

        $this->examA = Exam::create([
            'tenant_id' => $this->tenantA->id,
            'assessment_id' => $assessmentA->id,
            'title' => 'Ujian Matematika Tenant A',
            'duration_seconds' => 3600,
            'start_at' => now()->subMinutes(10)->toIso8601String(),
            'end_at' => now()->addHours(2)->toIso8601String(),
            'timezone' => 'Asia/Jakarta',
            'status' => 'PUBLISHED',
            'attempt_limit' => 1,
            'require_token' => true,
            'token' => 'ABC123',
            'created_by' => $this->userA1->id,
            'locked_at' => now()
        ]);

        $this->participantA1 = ExamParticipant::create([
            'tenant_id' => $this->tenantA->id,
            'exam_id' => $this->examA->id,
            'user_id' => $this->userA1->id,
            'status' => 'ASSIGNED'
        ]);

        $q = Question::create([
            'tenant_id' => $this->tenantA->id,
            'created_by' => $this->userA1->id
        ]);

        $v = QuestionVersion::create([
            'tenant_id' => $this->tenantA->id,
            'question_id' => $q->id,
            'major_version' => 1,
            'minor_version' => 0,
            'question_type' => 'MC',
            'stem' => 'What is 2+2?',
            'is_current' => true,
            'created_by' => $this->userA1->id
        ]);

        $opt = QuestionOption::create([
            'tenant_id' => $this->tenantA->id,
            'question_version_id' => $v->id,
            'content' => '4',
            'is_correct' => true,
            'score_weight' => 10
        ]);

        $this->snapshot = ExamQuestionSnapshot::create([
            'tenant_id' => $this->tenantA->id,
            'exam_id' => $this->examA->id,
            'source_question_id' => $q->id,
            'source_question_version_id' => $v->id,
            'sequence' => 1,
            'points' => 10,
            'snapshot_stem' => 'What is 2+2?',
            'snapshot_options' => [
                ['id' => $opt->id, 'option_text' => '4', 'is_correct' => true, 'score_weight' => 10]
            ],
            'snapshot_media' => [],
            'metadata' => ['question_type' => 'MC'],
            'created_by' => $this->userA1->id
        ]);

        // --- Tenant B Setup ---
        $context->setTenantId($this->tenantB->id);
        $this->userB = User::create([
            'tenant_id' => $this->tenantB->id,
            'name' => 'Student B Foreign Tenant',
            'email' => 'studentb@example.com',
            'password' => bcrypt('password')
        ]);
        $this->userB->roles()->attach($siswaRole);

        // Clear tenant context for controller requests
        $context->setTenantId(null);
        \App\Contexts\TenantContext::resetInstance();
    }

    public function test_test_a_authorized_participant_start()
    {
        $this->withHeader('Authorization', 'Bearer '.$this->apiTokenFor($this->userA1));

        $this->assertEquals(0, $this->participantA1->attempts()->count());

        $response = $this->withHeader('X-Device-Binding-ID', 'device-a1')
            ->postJson("/api/v1/exams/{$this->examA->id}/attempts", ['token' => 'ABC123']);

        $response->assertStatus(201);
        $response->assertJson(['success' => true]);
        $this->assertNotNull($response->json('data.id'));

        $this->assertEquals(1, $this->participantA1->attempts()->count());
    }

    public function test_participant_cannot_start_before_eligibility_window()
    {
        $this->participantA1->update(['eligible_from' => now()->addMinutes(5)]);
        $this->actingAs($this->userA1, 'sanctum');

        $response = $this->withHeader('X-Device-Binding-ID', 'device-a1')
            ->postJson("/api/v1/exams/{$this->examA->id}/attempts", ['token' => 'ABC123']);

        $response->assertStatus(403);
        $this->assertEquals(0, $this->participantA1->attempts()->count());
    }

    public function test_assigned_student_can_open_exam_instruction_without_token_leak()
    {
        $this->actingAs($this->userA1);

        $response = $this->get("/student/exams/{$this->examA->id}");

        $response->assertOk();
        $response->assertSee('Ujian Matematika Tenant A');
        $response->assertSee('Token Ujian');
        $response->assertDontSee('ABC123');
    }

    public function test_unassigned_student_cannot_open_exam_instruction()
    {
        $this->actingAs($this->userA2);

        $response = $this->get("/student/exams/{$this->examA->id}");

        $response->assertForbidden();
    }

    public function test_unassigned_student_dashboard_does_not_list_exam()
    {
        $this->actingAs($this->userA2);

        $response = $this->get('/student/exams');

        $response->assertOk();
        $response->assertDontSee('Ujian Matematika Tenant A');
    }

    public function test_attempt_expiry_is_clamped_to_participant_eligibility_end()
    {
        $eligibleUntil = now()->addMinutes(20)->startOfSecond();
        $this->participantA1->update(['eligible_until' => $eligibleUntil]);
        $this->actingAs($this->userA1, 'sanctum');

        $response = $this->withHeader('X-Device-Binding-ID', 'device-a1')
            ->postJson("/api/v1/exams/{$this->examA->id}/attempts", ['token' => 'ABC123']);

        $response->assertStatus(201);
        $this->assertEquals(
            $eligibleUntil->timestamp,
            \Carbon\Carbon::parse($response->json('data.expires_at'))->timestamp
        );
    }

    public function test_test_b_non_participant_start()
    {
        $this->withHeader('Authorization', 'Bearer '.$this->apiTokenFor($this->userA2));

        $response = $this->withHeader('X-Device-Binding-ID', 'device-a2')
            ->postJson("/api/v1/exams/{$this->examA->id}/attempts", ['token' => 'ABC123']);

        $response->assertStatus(403);
        $this->assertEquals(0, ExamAttempt::where('exam_id', $this->examA->id)->count());
    }

    public function test_test_c_second_attempt_rejected_when_limit_reached()
    {
        $this->actingAs($this->userA1, 'sanctum');

        // Start first attempt
        $res1 = $this->withHeader('X-Device-Binding-ID', 'device-a1')
            ->postJson("/api/v1/exams/{$this->examA->id}/attempts", ['token' => 'ABC123']);
        $res1->assertStatus(201);
        $attemptId = $res1->json('data.id');

        // Submit first attempt so it is final/completed
        $subRes = $this->withHeader('X-Device-Binding-ID', 'device-a1')
            ->postJson("/api/v1/exams/{$this->examA->id}/attempts/{$attemptId}/submit");
        $subRes->assertStatus(200);

        // Attempt second start when limit = 1
        $res2 = $this->withHeader('X-Device-Binding-ID', 'device-a1')
            ->postJson("/api/v1/exams/{$this->examA->id}/attempts", ['token' => 'ABC123']);

        $res2->assertStatus(422);
        $res2->assertJson(['success' => false, 'message' => 'Attempt limit reached.']);

        $this->assertEquals(1, $this->participantA1->attempts()->count());
    }

    public function test_test_d_replay_start_request_resumes_without_duplicate()
    {
        $this->actingAs($this->userA1, 'sanctum');

        // Initial start
        $res1 = $this->withHeader('X-Device-Binding-ID', 'device-a1')
            ->postJson("/api/v1/exams/{$this->examA->id}/attempts", ['token' => 'ABC123']);
        $res1->assertStatus(201);
        $attemptId1 = $res1->json('data.id');

        // Replay start request 10 times
        for ($i = 0; $i < 10; $i++) {
            $replayRes = $this->withHeader('X-Device-Binding-ID', 'device-a1')
                ->postJson("/api/v1/exams/{$this->examA->id}/attempts", ['token' => 'ABC123']);

            $replayRes->assertStatus(200);
            $replayRes->assertJson(['success' => true, 'message' => 'Resuming existing attempt.']);
            $this->assertEquals($attemptId1, $replayRes->json('data.id'));
        }

        $this->assertEquals(1, $this->participantA1->attempts()->count());
    }

    public function test_test_e_foreign_user_attempt_access()
    {
        // First create an attempt for userA1
        $this->actingAs($this->userA1, 'sanctum');
        $startRes = $this->withHeader('X-Device-Binding-ID', 'device-a1')
            ->postJson("/api/v1/exams/{$this->examA->id}/attempts", ['token' => 'ABC123']);
        $attemptId = $startRes->json('data.id');

        // Now switch to userA2 (same tenant, but not owner of attempt)
        $this->actingAs($this->userA2, 'sanctum');

        // 1. Execution access
        $execRes = $this->withHeader('X-Device-Binding-ID', 'device-a2')
            ->getJson("/api/v1/exams/{$this->examA->id}/execution");
        $execRes->assertStatus(404);

        // 2. Direct attempt show access: hide another participant's attempt.
        $showRes = $this->withHeader('X-Device-Binding-ID', 'device-a2')
            ->getJson("/api/v1/exams/{$this->examA->id}/attempts/{$attemptId}");
        $showRes->assertStatus(404);

        // 3. Submit attempt
        $submitRes = $this->withHeader('X-Device-Binding-ID', 'device-a2')
            ->postJson("/api/v1/exams/{$this->examA->id}/attempts/{$attemptId}/submit");
        $submitRes->assertStatus(403);

        // 4. Update answer
        $answerRes = $this->withHeader('X-Device-Binding-ID', 'device-a2')
            ->putJson("/api/v1/exams/{$this->examA->id}/attempts/{$attemptId}/answers/{$this->snapshot->id}", [
                'client_request_id' => 'req-foreign-1',
                'answer_state' => 'ANSWERED',
                'answer_payload' => ['selected_option_id' => 'opt-1']
            ]);
        $answerRes->assertStatus(403);
    }

    public function test_test_f_foreign_tenant_attempt_access()
    {
        $this->withHeader('Authorization', 'Bearer '.$this->apiTokenFor($this->userB));

        // User B from Tenant B trying to start Exam A from Tenant A
        $startRes = $this->withHeader('X-Device-Binding-ID', 'device-b')
            ->postJson("/api/v1/exams/{$this->examA->id}/attempts", ['token' => 'ABC123']);

        $this->assertTrue(in_array($startRes->status(), [403, 404]));
        $this->assertEquals(0, ExamAttempt::where('exam_id', $this->examA->id)->count());
    }

    public function test_test_g_final_attempt_plus_limit()
    {
        $this->actingAs($this->userA1, 'sanctum');

        // Start and submit attempt 1
        $res1 = $this->withHeader('X-Device-Binding-ID', 'device-a1')
            ->postJson("/api/v1/exams/{$this->examA->id}/attempts", ['token' => 'ABC123']);
        $attemptId1 = $res1->json('data.id');

        $this->withHeader('X-Device-Binding-ID', 'device-a1')
            ->postJson("/api/v1/exams/{$this->examA->id}/attempts/{$attemptId1}/submit");

        $this->assertEquals('SUBMITTED', ExamAttempt::find($attemptId1)->status);

        // Attempt to start attempt 2
        $res2 = $this->withHeader('X-Device-Binding-ID', 'device-a1')
            ->postJson("/api/v1/exams/{$this->examA->id}/attempts", ['token' => 'ABC123']);

        $res2->assertStatus(422);
        $res2->assertJson(['success' => false, 'message' => 'Attempt limit reached.']);
        $this->assertEquals(1, $this->participantA1->attempts()->count());
    }

    public function test_test_h_uuid_parameter_manipulation()
    {
        $this->actingAs($this->userA1, 'sanctum');

        $startRes = $this->withHeader('X-Device-Binding-ID', 'device-a1')
            ->postJson("/api/v1/exams/{$this->examA->id}/attempts", ['token' => 'ABC123']);
        $attemptId = $startRes->json('data.id');

        $randomExamUuid = (string) Str::uuid();
        $randomAttemptUuid = (string) Str::uuid();

        // 1. Valid attempt with wrong Exam UUID
        $res1 = $this->withHeader('X-Device-Binding-ID', 'device-a1')
            ->postJson("/api/v1/exams/{$randomExamUuid}/attempts/{$attemptId}/submit");
        $res1->assertStatus(404);

        // 2. Valid Exam with wrong Attempt UUID
        $res2 = $this->withHeader('X-Device-Binding-ID', 'device-a1')
            ->postJson("/api/v1/exams/{$this->examA->id}/attempts/{$randomAttemptUuid}/submit");
        $res2->assertStatus(404);
    }
}
