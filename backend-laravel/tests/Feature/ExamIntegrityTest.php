<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Assessment;
use App\Models\Exam;
use App\Models\Rombel;
use App\Models\AcademicYear;
use App\Models\StudentProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private $tenantA;
    private $tenantB;
    private $userA;
    private $userB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenantA = Tenant::create(['name' => 'A', 'code' => 'A']);
        $this->tenantB = Tenant::create(['name' => 'B', 'code' => 'B']);

        $context = app(\App\Contexts\TenantContext::class);

        $context->setTenantId($this->tenantA->id);
        $this->userA = User::create(['tenant_id' => $this->tenantA->id, 'name' => 'A', 'email' => 'a@a.com', 'password' => 'p']);

        $context->setTenantId($this->tenantB->id);
        $this->userB = User::create(['tenant_id' => $this->tenantB->id, 'name' => 'B', 'email' => 'b@b.com', 'password' => 'p']);

        $context->setTenantId(null);
    }

    public function test_tenant_a_cannot_assign_student_from_tenant_b()
    {
        $context = app(\App\Contexts\TenantContext::class);
        $context->setTenantId($this->tenantA->id);

        $this->userA->roles()->attach(
            \App\Models\Role::firstOrCreate(['name' => 'ADMIN_MADRASAH'], ['label' => 'Admin'])
        );
        $ayA = AcademicYear::create(['tenant_id' => $this->tenantA->id, 'name' => '2024']);
        $assessmentA = Assessment::create(['tenant_id' => $this->tenantA->id, 'academic_year_id' => $ayA->id, 'title' => 'A', 'assessment_type' => 'DAILY', 'created_by' => $this->userA->id]);
        $examA = Exam::create(['tenant_id' => $this->tenantA->id, 'assessment_id' => $assessmentA->id, 'title' => 'A', 'duration_seconds' => 60, 'created_by' => $this->userA->id]);

        $this->actingAs($this->userA);

        $response = $this->postJson("/api/v1/exams/{$examA->id}/participants", [
            'user_id' => $this->userB->id // Student from Tenant B
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['user_id']);
    }

    public function test_attempt_lifecycle_restrictions()
    {
        $context = app(\App\Contexts\TenantContext::class);
        $context->setTenantId($this->tenantA->id);

        $ayA = AcademicYear::create(['tenant_id' => $this->tenantA->id, 'name' => '2024']);
        $assessmentA = Assessment::create(['tenant_id' => $this->tenantA->id, 'academic_year_id' => $ayA->id, 'title' => 'A', 'assessment_type' => 'DAILY', 'created_by' => $this->userA->id]);

        // Exam in DRAFT
        $examA = Exam::create([
            'tenant_id' => $this->tenantA->id,
            'assessment_id' => $assessmentA->id,
            'title' => 'A',
            'duration_seconds' => 60,
            'created_by' => $this->userA->id,
            'status' => 'DRAFT',
            'start_at' => now()->addHour(),
            'end_at' => now()->addHours(2)
        ]);

        // Student participant
        $studentUser = User::create(['tenant_id' => $this->tenantA->id, 'name' => 'S', 'email' => 's@s.com', 'password' => 'p']);
        $studentUser->roles()->attach(
            \App\Models\Role::firstOrCreate(['name' => 'SISWA'], ['label' => 'Siswa'])
        );
        $participant = new \App\Models\ExamParticipant(['user_id' => $studentUser->id]);
        $participant->tenant_id = $this->tenantA->id;
        $participant->exam_id = $examA->id;
        $participant->save();

        $this->actingAs($studentUser);

        // Attempt before locked/scheduled
        $response = $this->withHeader('X-Device-Binding-ID', 'dev-1')
                         ->postJson("/api/v1/exams/{$examA->id}/attempts");
        $response->assertStatus(403);
    }
}
