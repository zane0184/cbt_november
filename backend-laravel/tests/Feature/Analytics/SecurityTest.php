<?php

namespace Tests\Feature\Analytics;

use Tests\TestCase;
use App\Models\User;
use App\Models\Exam;
use App\Models\Tenant;
use App\Models\Role;
use App\Models\Assessment;
use App\Models\Rombel;
use App\Models\Subject;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptScore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function unauthenticated_request_is_rejected()
    {
        $tenant = Tenant::factory()->create();
        $this->setTenantContext($tenant->id);
        $exam = Exam::factory()->create();

        $this->setTenantContext(null);
        $response = $this->getJson("/api/v1/analytics/exams/{$exam->id}");
        $response->assertStatus(401);
    }

    /** @test */
    public function tenant_a_cannot_access_tenant_b_exam_analytics()
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $this->setTenantContext($tenantA->id);
        $adminA = User::factory()->create(['tenant_id' => $tenantA->id]);
        $adminA->roles()->attach(Role::firstOrCreate(['name' => 'ADMIN_MADRASAH'], ['label' => 'Admin']));

        $this->setTenantContext($tenantB->id);
        $examB = Exam::factory()->create(['tenant_id' => $tenantB->id]);

        $this->actingAs($adminA);

        $response = $this->getJson("/api/v1/analytics/exams/{$examB->id}");
        $response->assertStatus(404);
    }

    /** @test */
    public function tenant_a_cannot_access_tenant_b_rombel_analytics()
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $this->setTenantContext($tenantA->id);
        $adminA = User::factory()->create(['tenant_id' => $tenantA->id]);
        $adminA->roles()->attach(Role::firstOrCreate(['name' => 'ADMIN_MADRASAH'], ['label' => 'Admin']));

        $this->setTenantContext($tenantB->id);
        $rombelB = Rombel::factory()->create(['tenant_id' => $tenantB->id]);
        $examB = Exam::factory()->create(['tenant_id' => $tenantB->id]);

        $this->actingAs($adminA);

        $response = $this->getJson("/api/v1/analytics/rombels/{$rombelB->id}/exams/{$examB->id}");
        $response->assertStatus(404);
    }

    /** @test */
    public function tenant_a_cannot_access_tenant_b_subject_analytics()
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $this->setTenantContext($tenantA->id);
        $adminA = User::factory()->create(['tenant_id' => $tenantA->id]);
        $adminA->roles()->attach(Role::firstOrCreate(['name' => 'ADMIN_MADRASAH'], ['label' => 'Admin']));

        $this->setTenantContext($tenantB->id);
        $subjectB = Subject::factory()->create(['tenant_id' => $tenantB->id]);

        $this->actingAs($adminA);

        $response = $this->getJson("/api/v1/analytics/subjects/{$subjectB->id}");
        $response->assertStatus(404);
    }

    /** @test */
    public function student_cannot_access_item_analysis()
    {
        $tenant = Tenant::factory()->create();
        $this->setTenantContext($tenant->id);
        $student = User::factory()->create(['tenant_id' => $tenant->id]);
        $exam = Exam::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($student);

        $response = $this->getJson("/api/v1/analytics/exams/{$exam->id}/items");
        $response->assertStatus(403);
    }

    /** @test */
    public function student_cannot_access_other_student_analytics()
    {
        $tenant = Tenant::factory()->create();
        $this->setTenantContext($tenant->id);
        $studentA = User::factory()->create(['tenant_id' => $tenant->id]);
        $studentB = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($studentA);

        $response = $this->getJson("/api/v1/analytics/students/{$studentB->id}");
        $response->assertStatus(403);
    }

    /** @test */
    public function show_result_false_blocks_student_analytics()
    {
        $tenant = Tenant::factory()->create();
        $this->setTenantContext($tenant->id);
        $student = User::factory()->create(['tenant_id' => $tenant->id]);
        $student->roles()->attach(Role::firstOrCreate(['name' => 'SISWA'], ['label' => 'Siswa']));

        $exam = Exam::factory()->create(['tenant_id' => $tenant->id, 'show_result' => false]);

        $this->actingAs($student, 'sanctum');

        $response = $this->getJson("/api/v1/analytics/students/{$student->id}");
        fwrite(STDERR, "RESPONSE CONTENT: " . $response->getContent() . "\n");
        $response->assertStatus(200);
        $response->assertJsonCount(0, 'data.history');
    }

    /** @test */
    public function unfinalized_result_is_not_exposed_to_student()
    {
        $tenant = Tenant::factory()->create();
        $this->setTenantContext($tenant->id);
        $student = User::factory()->create(['tenant_id' => $tenant->id]);
        $student->roles()->attach(Role::firstOrCreate(['name' => 'SISWA'], ['label' => 'Siswa']));

        $exam = Exam::factory()->create(['tenant_id' => $tenant->id, 'show_result' => true]);

        $attempt = ExamAttempt::factory()->create(['exam_id' => $exam->id, 'tenant_id' => $tenant->id]);
        $score = ExamAttemptScore::factory()->create([
            'attempt_id' => $attempt->id,
            'student_id' => $student->id,
            'exam_id' => $exam->id,
            'tenant_id' => $tenant->id,
            'grading_status' => 'AUTO_SCORED' // Not FINALIZED
        ]);

        $this->actingAs($student, 'sanctum');

        $response = $this->getJson("/api/v1/analytics/students/{$student->id}");
        $response->assertStatus(200);
        $response->assertJsonCount(0, 'data.history');
    }

    /** @test */
    public function unauthorized_role_rejected()
    {
        $tenant = Tenant::factory()->create();
        $this->setTenantContext($tenant->id);
        // User with no roles
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $exam = Exam::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($user);

        $response = $this->getJson("/api/v1/analytics/exams/{$exam->id}");
        $response->assertStatus(403);
    }
}
