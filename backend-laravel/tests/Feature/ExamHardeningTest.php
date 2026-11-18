<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Assessment;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamParticipant;
use App\Models\AcademicYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamHardeningTest extends TestCase
{
    use RefreshDatabase;

    private $tenant;
    private $admin;
    private $siswa;
    private $exam;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'Madrasah', 'code' => 'M']);

        $context = app(\App\Contexts\TenantContext::class);
        $context->setTenantId($this->tenant->id);

        $this->admin = User::create(['tenant_id' => $this->tenant->id, 'name' => 'Admin', 'email' => 'admin@m.com', 'password' => 'p']);
        $this->admin->roles()->attach(
            \App\Models\Role::firstOrCreate(['name' => 'ADMIN_MADRASAH'], ['label' => 'Admin'])
        );

        $this->siswa = User::create(['tenant_id' => $this->tenant->id, 'name' => 'Siswa', 'email' => 'siswa@m.com', 'password' => 'p']);
        $this->siswa->roles()->attach(
            \App\Models\Role::firstOrCreate(['name' => 'SISWA'], ['label' => 'Siswa'])
        );

        $ay = AcademicYear::create(['tenant_id' => $this->tenant->id, 'name' => '2024']);
        $assessment = Assessment::create(['tenant_id' => $this->tenant->id, 'academic_year_id' => $ay->id, 'title' => 'Daily', 'assessment_type' => 'DAILY', 'created_by' => $this->admin->id]);

        $this->exam = Exam::create([
            'tenant_id' => $this->tenant->id,
            'assessment_id' => $assessment->id,
            'title' => 'Math',
            'duration_seconds' => 3600,
            'created_by' => $this->admin->id,
            'status' => 'DRAFT',
            'start_at' => now()->subMinutes(10),
            'end_at' => now()->addMinutes(50),
            'locked_at' => now() // Locked
        ]);

        $participant = new ExamParticipant(['user_id' => $this->siswa->id]);
        $participant->exam_id = $this->exam->id;
        $participant->save();

        $context->setTenantId(null);
    }

    public function test_siswa_cannot_update_exam()
    {
        $this->actingAs($this->siswa);
        $response = $this->putJson("/api/v1/exams/{$this->exam->id}", ['title' => 'Hacked']);
        $response->assertStatus(403);
    }

    public function test_admin_cannot_update_locked_exam()
    {
        $this->actingAs($this->admin);
        $response = $this->putJson("/api/v1/exams/{$this->exam->id}", ['title' => 'Changed']);
        $response->assertStatus(403); // Per policy update returns false if locked
    }

    public function test_duplicate_submission_prevention()
    {
        $context = app(\App\Contexts\TenantContext::class);
        $context->setTenantId($this->tenant->id);

        $participant = ExamParticipant::where('exam_id', $this->exam->id)->where('user_id', $this->siswa->id)->first();
        $attempt = new ExamAttempt([
            'exam_id' => $this->exam->id,
            'participant_id' => $participant->id,
            'attempt_number' => 1,
            'status' => 'IN_PROGRESS',
            'started_at' => now()
        ]);
        $attempt->save();

        $context->setTenantId(null);
        $this->actingAs($this->siswa);
        $this->withHeader('X-Device-Binding-ID', 'device-1');

        // First submission
        $response = $this->postJson("/api/v1/exams/{$this->exam->id}/attempts/{$attempt->id}/submit");
        $response->assertStatus(200);

        // Second submission
        $response = $this->postJson("/api/v1/exams/{$this->exam->id}/attempts/{$attempt->id}/submit");
        $response->assertStatus(422);
        $response->assertJson(['message' => 'Attempt is not in progress.']);
    }

    public function test_cross_tenant_attempt_submission_denied()
    {
        $tenantB = Tenant::create(['name' => 'B', 'code' => 'B']);

        $context = app(\App\Contexts\TenantContext::class);
        $context->setTenantId($tenantB->id);

        $userB = User::create(['tenant_id' => $tenantB->id, 'name' => 'B', 'email' => 'b@b.com', 'password' => 'p']);
        $userB->roles()->attach(
            \App\Models\Role::firstOrCreate(['name' => 'SISWA'], ['label' => 'Siswa'])
        );

        $context->setTenantId($this->tenant->id);
        $participant = ExamParticipant::where('exam_id', $this->exam->id)->where('user_id', $this->siswa->id)->first();
        $attempt = new ExamAttempt([
            'exam_id' => $this->exam->id,
            'participant_id' => $participant->id,
            'attempt_number' => 1,
            'status' => 'IN_PROGRESS',
            'started_at' => now()
        ]);
        $attempt->save();

        $context->setTenantId(null);
        $this->actingAs($userB);
        $this->withHeader('X-Device-Binding-ID', 'device-2');
        $response = $this->postJson("/api/v1/exams/{$this->exam->id}/attempts/{$attempt->id}/submit");

        // Depending on how global scopes work, it might be 404 or 403.
        // IdentifyTenant middleware will set context to Tenant B.
        // Attempt search in controller will fail or find nothing.
        $response->assertStatus(404);
    }
}
