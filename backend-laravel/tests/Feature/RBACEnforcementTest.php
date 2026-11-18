<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Exam;
use App\Models\Assessment;
use App\Models\AcademicYear;
use App\Models\Curriculum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RBACEnforcementTest extends TestCase
{
    use RefreshDatabase;

    private $tenant;
    private $admin;
    private $student;
    private $guru;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create(['name' => 'Test Tenant', 'code' => 'TEST']);

        // Set context for initial data creation
        $context = app(\App\Contexts\TenantContext::class);
        $context->setTenantId($this->tenant->id);

        $adminRole = Role::create(['name' => 'ADMIN_MADRASAH', 'label' => 'Admin']);
        $studentRole = Role::create(['name' => 'SISWA', 'label' => 'Student']);
        $guruRole = Role::create(['name' => 'GURU', 'label' => 'Teacher']);

        // Give Admin curricula permissions
        $curriculumManage = Permission::create(['name' => 'curriculum.manage', 'label' => 'Manage Curricula']);
        $adminRole->permissions()->attach($curriculumManage);

        $this->admin = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password')
        ]);
        $this->admin->roles()->attach($adminRole);

        $this->student = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Student',
            'email' => 'student@test.com',
            'password' => bcrypt('password')
        ]);
        $this->student->roles()->attach($studentRole);

        $this->guru = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Guru',
            'email' => 'guru@test.com',
            'password' => bcrypt('password')
        ]);
        $this->guru->roles()->attach($guruRole);

        // Reset context to null to ensure tests handle authentication correctly
        $context->setTenantId(null);
    }

    public function test_admin_without_academic_manage_cannot_create_teacher_assignment()
    {
        $this->actingAs($this->admin);

        $response = $this->postJson('/api/v1/teacher-subject-assignments', []);
        $response->assertStatus(403);
    }

    public function test_admin_with_academic_manage_reaches_teacher_assignment_validation()
    {
        $adminRole = $this->admin->roles()->where('name', 'ADMIN_MADRASAH')->firstOrFail();
        $permission = Permission::firstOrCreate(['name' => 'academic.manage'], ['label' => 'Kelola Data Akademik']);
        $adminRole->permissions()->syncWithoutDetaching([$permission->id]);
        $this->admin->unsetRelation('roles');
        $this->actingAs($this->admin);

        $response = $this->postJson('/api/v1/teacher-subject-assignments', []);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['teacher_profile_id', 'subject_id', 'rombel_id', 'academic_year_id', 'semester_id']);
    }

    public function test_student_cannot_create_exam()
    {
        $this->actingAs($this->student);

        $response = $this->postJson('/api/v1/exams', [
            'title' => 'Student Exam',
            'assessment_id' => \Illuminate\Support\Str::uuid()->toString(),
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_create_exam()
    {
        $this->actingAs($this->admin);

        $year = AcademicYear::create(['name' => '2024/2025', 'is_active' => true]);

        $assessment = Assessment::create([
            'academic_year_id' => $year->id,
            'title' => 'Test Assessment',
            'assessment_type' => 'SUMMATIVE',
            'created_by' => $this->admin->id
        ]);

        $response = $this->postJson('/api/v1/exams', [
            'assessment_id' => $assessment->id,
            'title' => 'Admin Exam',
            'duration_seconds' => 3600,
            'start_at' => now()->addDay()->toIso8601String(),
            'end_at' => now()->addDays(2)->toIso8601String(),
            'timezone' => 'Asia/Jakarta',
        ]);

        $response->assertStatus(201);
    }

    public function test_student_cannot_access_curricula_store()
    {
        $this->actingAs($this->student);

        $response = $this->postJson('/api/v1/curricula', ['name' => 'New Cur']);
        $response->assertStatus(403);
    }

    public function test_admin_can_access_curricula_store()
    {
        $this->actingAs($this->admin);

        $response = $this->postJson('/api/v1/curricula', [
            'name' => 'New Cur',
            'code' => 'K-MERDEKA',
            'is_active' => true
        ]);

        $response->assertStatus(201);
    }
}
