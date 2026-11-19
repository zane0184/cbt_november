<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamParticipant;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class RateLimitingAndAbuseProtectionTest extends TestCase
{
    use RefreshDatabase;

    private $tenant;
    private $userA;
    private $userB;
    private $exam;
    private $attemptA;
    private $attemptB;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear cache so rate limiters start fresh
        Cache::flush();

        $this->tenant = Tenant::create(['name' => 'Madrasah Throttle Test', 'code' => 'MTT']);

        $context = app(\App\Contexts\TenantContext::class);
        $context->setTenantId($this->tenant->id);

        $siswaRole = Role::firstOrCreate(['name' => 'SISWA'], ['label' => 'Siswa']);

        $this->userA = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Student Throttle User A',
            'email' => 'studenta_throttle@example.com',
            'password' => bcrypt('password')
        ]);
        $this->userA->roles()->attach($siswaRole);

        $this->userB = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Student Throttle User B',
            'email' => 'studentb_throttle@example.com',
            'password' => bcrypt('password')
        ]);
        $this->userB->roles()->attach($siswaRole);

        $year = AcademicYear::create(['tenant_id' => $this->tenant->id, 'name' => '2024/2025']);
        $assessment = Assessment::create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $year->id,
            'title' => 'Sumatif Rate Limit',
            'assessment_type' => 'SUMMATIVE',
            'created_by' => $this->userA->id
        ]);

        $this->exam = Exam::create([
            'tenant_id' => $this->tenant->id,
            'assessment_id' => $assessment->id,
            'title' => 'Ujian Rate Limit Test',
            'duration_seconds' => 3600,
            'start_at' => now()->subMinutes(5)->toIso8601String(),
            'end_at' => now()->addHours(2)->toIso8601String(),
            'timezone' => 'Asia/Jakarta',
            'status' => 'PUBLISHED',
            'attempt_limit' => 1,
            'require_token' => true,
            'token' => 'SECRET123',
            'created_by' => $this->userA->id,
            'locked_at' => now()
        ]);

        $participantA = ExamParticipant::create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'user_id' => $this->userA->id,
            'status' => 'STARTED'
        ]);

        $participantB = ExamParticipant::create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'user_id' => $this->userB->id,
            'status' => 'STARTED'
        ]);

        $this->attemptA = ExamAttempt::create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'participant_id' => $participantA->id,
            'attempt_number' => 1,
            'status' => 'IN_PROGRESS',
            'device_fingerprint' => 'device-user-a',
            'started_at' => now(),
            'expires_at' => now()->addHour(),
            'server_start_reference' => now()->toIso8601String(),
            'last_activity_at' => now()->subMinutes(10)
        ]);

        $this->attemptB = ExamAttempt::create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'participant_id' => $participantB->id,
            'attempt_number' => 1,
            'status' => 'IN_PROGRESS',
            'device_fingerprint' => 'device-user-b',
            'started_at' => now(),
            'expires_at' => now()->addHour(),
            'server_start_reference' => now()->toIso8601String(),
            'last_activity_at' => now()->subMinutes(10)
        ]);

        $context->setTenantId(null);
        \App\Contexts\TenantContext::resetInstance();
    }

    public function test_test_a_normal_request_under_limit_accepted()
    {
        $this->actingAs($this->userA, 'sanctum');

        // Send 5 normal heartbeat requests (limit is 30/min)
        for ($i = 0; $i < 5; $i++) {
            $response = $this->withHeader('X-Device-Binding-ID', 'device-user-a')
                ->postJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attemptA->id}/heartbeat");

            $response->assertStatus(200);
            $response->assertJson(['success' => true]);
        }
    }

    public function test_test_b_burst_requests_trigger_http_429()
    {
        $this->actingAs($this->userA, 'sanctum');

        $acceptedCount = 0;
        $rejected429Count = 0;
        $first429RequestNumber = null;

        // Send 35 requests (limit is 30 per minute)
        for ($i = 1; $i <= 35; $i++) {
            $response = $this->withHeader('X-Device-Binding-ID', 'device-user-a')
                ->postJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attemptA->id}/heartbeat");

            if ($response->status() === 200) {
                $acceptedCount++;
            } elseif ($response->status() === 429) {
                $rejected429Count++;
                if ($first429RequestNumber === null) {
                    $first429RequestNumber = $i;
                }
            }
        }

        $this->assertEquals(30, $acceptedCount, "Expected exactly 30 requests to be accepted before rate limit.");
        $this->assertEquals(5, $rejected429Count, "Expected 5 requests to be rejected with HTTP 429.");
        $this->assertEquals(31, $first429RequestNumber, "First HTTP 429 should occur on request 31.");
    }

    public function test_test_c_rate_limit_response_and_headers()
    {
        $this->actingAs($this->userA, 'sanctum');

        // Exhaust the 30 requests limit for heartbeat
        for ($i = 0; $i < 30; $i++) {
            $this->withHeader('X-Device-Binding-ID', 'device-user-a')
                ->postJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attemptA->id}/heartbeat");
        }

        // 31st request triggers 429
        $response = $this->withHeader('X-Device-Binding-ID', 'device-user-a')
            ->postJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attemptA->id}/heartbeat");

        $response->assertStatus(429);
        $this->assertTrue($response->headers->has('Retry-After') || $response->headers->has('X-RateLimit-Reset') || $response->headers->has('X-RateLimit-Limit'));

        // Test custom controller RateLimiter on Exam Token (limit = 5 hits)
        // Reset participant attempt count so store() gets to token check
        $this->attemptA->delete();
        $this->exam->participants()->where('user_id', $this->userA->id)->update(['status' => 'ASSIGNED']);

        for ($i = 0; $i < 5; $i++) {
            $res = $this->withHeader('X-Device-Binding-ID', 'device-user-a')
                ->postJson("/api/v1/exams/{$this->exam->id}/attempts", ['token' => 'BAD123']);
            $res->assertStatus(422); // Invalid token
        }

        // 6th attempt with invalid token triggers 429
        $resLock = $this->withHeader('X-Device-Binding-ID', 'device-user-a')
            ->postJson("/api/v1/exams/{$this->exam->id}/attempts", ['token' => 'BAD123']);

        $resLock->assertStatus(429);
        $resLock->assertJson(['success' => false]);
        $this->assertStringContainsString('Too many invalid token attempts', $resLock->json('message'));
    }

    public function test_test_d_mutation_safety_during_rate_limit()
    {
        $this->actingAs($this->userA, 'sanctum');

        // Record last_activity_at before reaching limit
        $initialActivityAt = $this->attemptA->fresh()->last_activity_at;

        // Exhaust 30 heartbeat requests
        for ($i = 0; $i < 30; $i++) {
            $this->withHeader('X-Device-Binding-ID', 'device-user-a')
                ->postJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attemptA->id}/heartbeat");
        }

        $acceptedActivityAt = $this->attemptA->fresh()->last_activity_at;
        $this->assertNotEquals($initialActivityAt, $acceptedActivityAt, "Accepted heartbeat should update last_activity_at.");

        // Send 5 more requests that get HTTP 429
        for ($i = 0; $i < 5; $i++) {
            $res = $this->withHeader('X-Device-Binding-ID', 'device-user-a')
                ->postJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attemptA->id}/heartbeat");
            $res->assertStatus(429);
        }

        $post429ActivityAt = $this->attemptA->fresh()->last_activity_at;
        $this->assertEquals($acceptedActivityAt->toIso8601String(), $post429ActivityAt->toIso8601String(), "Requests receiving 429 must NOT mutate last_activity_at.");
    }

    public function test_test_e_parameter_bypass_attempt_is_blocked()
    {
        $this->actingAs($this->userA, 'sanctum');

        // Exhaust 30 heartbeat requests
        for ($i = 0; $i < 30; $i++) {
            $this->withHeader('X-Device-Binding-ID', 'device-user-a')
                ->postJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attemptA->id}/heartbeat");
        }

        // Try bypassing rate limit with query params
        $res1 = $this->withHeader('X-Device-Binding-ID', 'device-user-a')
            ->postJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attemptA->id}/heartbeat?bypass=true&time=" . time());
        $res1->assertStatus(429);

        // Try bypassing with extra body payload
        $res2 = $this->withHeader('X-Device-Binding-ID', 'device-user-a')
            ->postJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attemptA->id}/heartbeat", ['random_field' => 'random_val']);
        $res2->assertStatus(429);
    }

    public function test_test_f_rate_limit_key_isolation_per_user()
    {
        // 1. User A exhausts limit (30 requests)
        $this->actingAs($this->userA, 'sanctum');
        for ($i = 0; $i < 30; $i++) {
            $this->withHeader('X-Device-Binding-ID', 'device-user-a')
                ->postJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attemptA->id}/heartbeat");
        }

        // 31st request for User A is 429
        $resA = $this->withHeader('X-Device-Binding-ID', 'device-user-a')
            ->postJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attemptA->id}/heartbeat");
        $resA->assertStatus(429);

        // 2. Switch to User B (different authenticated user)
        $this->actingAs($this->userB, 'sanctum');

        // User B's heartbeat should succeed with 200 OK because keying is isolated per user
        $resB = $this->withHeader('X-Device-Binding-ID', 'device-user-b')
            ->postJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attemptB->id}/heartbeat");

        $resB->assertStatus(200);
        $resB->assertJson(['success' => true]);
    }

    public function test_test_g_window_reset_restores_access()
    {
        $this->actingAs($this->userA, 'sanctum');

        // Exhaust limit (30 requests)
        for ($i = 0; $i < 30; $i++) {
            $this->withHeader('X-Device-Binding-ID', 'device-user-a')
                ->postJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attemptA->id}/heartbeat");
        }

        // Confirm 429
        $res429 = $this->withHeader('X-Device-Binding-ID', 'device-user-a')
            ->postJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attemptA->id}/heartbeat");
        $res429->assertStatus(429);

        // Reset cache / simulate window expiration
        Cache::flush();

        // Send request after window reset
        $resReset = $this->withHeader('X-Device-Binding-ID', 'device-user-a')
            ->postJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attemptA->id}/heartbeat");

        $resReset->assertStatus(200);
        $resReset->assertJson(['success' => true]);
    }
}
