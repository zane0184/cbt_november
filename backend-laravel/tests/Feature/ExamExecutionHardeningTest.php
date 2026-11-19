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
use App\Services\ExamSnapshotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class ExamExecutionHardeningTest extends TestCase
{
    use RefreshDatabase;

    private $tenant;
    private $student;
    private $exam;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'T1', 'code' => 'T1']);

        $context = app(\App\Contexts\TenantContext::class);
        $context->setTenantId($this->tenant->id);

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
            'require_token' => true,
            'token' => null,
        ]);

        $question = Question::create([
            'tenant_id' => $this->tenant->id,
            'owner_type' => 'TENANT',
            'owner_user_id' => $this->student->id,
            'question_code' => 'EXEC-1',
            'created_by' => $this->student->id,
        ]);
        $version = QuestionVersion::create([
            'tenant_id' => $this->tenant->id,
            'question_id' => $question->id,
            'major_version' => 1,
            'minor_version' => 0,
            'question_type' => 'ESSAY',
            'stem' => 'Soal uji eksekusi',
            'is_current' => true,
            'created_by' => $this->student->id,
        ]);
        app(ExamSnapshotService::class)->createSnapshots($this->exam, [$version->id]);
        $this->exam->update([
            'status' => 'PUBLISHED',
            'locked_at' => now(),
            'published_at' => now(),
            'require_token' => true,
            'token' => 'ABC123',
        ]);

        $participant = new ExamParticipant(['user_id' => $this->student->id]);
        $participant->exam_id = $this->exam->id;
        $participant->save();

        $context->setTenantId(null);
    }

    /** 1. CONCURRENT / DUPLICATE START **/
    public function test_duplicate_start_resumes_same_attempt_for_same_device()
    {
        $this->actingAs($this->student, 'sanctum');
        $this->withHeader('X-Device-Binding-ID', 'device-1');

        // Simulate two sequential requests that pass the initial checks
        // In a real environment, we'd use multiple processes, but here we test the logic
        $response1 = $this->postJson("/api/v1/exams/{$this->exam->id}/attempts", ['token' => 'ABC123']);
        $response1->assertStatus(201);
        $attemptId1 = $response1->json('data.id');

        $response2 = $this->postJson("/api/v1/exams/{$this->exam->id}/attempts", ['token' => 'ABC123']);
        $response2->assertStatus(200); // Should resume
        $attemptId2 = $response2->json('data.id');

        $this->assertEquals($attemptId1, $attemptId2);
        $this->assertEquals(1, ExamAttempt::count());
    }

    /** 2. SERVER-AUTHORITATIVE EXPIRATION **/
    public function test_expired_attempt_cannot_be_resumed()
    {
        $this->actingAs($this->student);
        $this->withHeader('X-Device-Binding-ID', 'device-1');

        // 1. Start attempt
        $response = $this->postJson("/api/v1/exams/{$this->exam->id}/attempts");
        $response->assertStatus(201);
        $attempt = ExamAttempt::first();

        // 2. Mock time to after expiration
        Carbon::setTestNow(now()->addSeconds($this->exam->duration_seconds + 1));

        // 3. Try to resume (should create new attempt if limit allowed, or fail)
        // In this case, duration is 3600.
        $response = $this->postJson("/api/v1/exams/{$this->exam->id}/attempts");

        // If limit is 1, and first is expired, it's counted as an attempt.
        // Current logic: store() count attempts.
        $response->assertStatus(422);

        // 4. Try to access execution endpoint
        $response = $this->getJson("/api/v1/exams/{$this->exam->id}/execution");
        $response->assertStatus(404); // No active attempt found

        Carbon::setTestNow();
    }

    /** 4. STRICT STUDENT AUTHORIZATION **/
    public function test_student_cannot_access_other_student_execution()
    {
        $context = app(\App\Contexts\TenantContext::class);
        $context->setTenantId($this->tenant->id);

        $otherStudent = User::create(['tenant_id' => $this->tenant->id, 'name' => 'S2', 'email' => 's2@e.com', 'password' => 'p']);
        $otherStudent->roles()->attach(
            \App\Models\Role::firstOrCreate(['name' => 'SISWA'], ['label' => 'Siswa'])
        );

        $context->setTenantId(null);
        $this->actingAs($this->student);
        $this->withHeader('X-Device-Binding-ID', 'device-1');
        $this->postJson("/api/v1/exams/{$this->exam->id}/attempts");

        $this->actingAs($otherStudent, 'sanctum');
        $this->withHeader('X-Device-Binding-ID', 'device-2');
        $response = $this->getJson("/api/v1/exams/{$this->exam->id}/execution");
        $response->assertStatus(404); // Participant not found for S2
    }

    public function test_student_cannot_access_other_tenant_exam()
    {
        $otherTenant = Tenant::create(['name' => 'T2', 'code' => 'T2']);

        $context = app(\App\Contexts\TenantContext::class);
        $context->setTenantId($otherTenant->id);

        $otherStudent = User::create(['tenant_id' => $otherTenant->id, 'name' => 'S2', 'email' => 's2@e.com', 'password' => 'p']);
        $otherStudent->roles()->attach(
            \App\Models\Role::firstOrCreate(['name' => 'SISWA'], ['label' => 'Siswa'])
        );

        $this->actingAs($otherStudent);
        $this->withHeader('X-Device-Binding-ID', 'device-2');
        $response = $this->getJson("/api/v1/exams/{$this->exam->id}/execution");
        $response->assertStatus(404); // Exam not found in Tenant 2 scope
    }

    /** 5. SNAPSHOT / ORDER IMMUTABILITY **/
    public function test_repeated_execution_returns_identical_order()
    {
        $this->actingAs($this->student);
        $this->withHeader('X-Device-Binding-ID', 'device-1');
        $this->postJson("/api/v1/exams/{$this->exam->id}/attempts");

        $response1 = $this->getJson("/api/v1/exams/{$this->exam->id}/execution");
        $order1 = collect($response1->json('data.questions'))->pluck('id')->toArray();

        $response2 = $this->getJson("/api/v1/exams/{$this->exam->id}/execution");
        $order2 = collect($response2->json('data.questions'))->pluck('id')->toArray();

        $this->assertEquals($order1, $order2);
    }
}
