<?php

namespace Tests\Feature;

use App\Contexts\TenantContext;
use App\Livewire\Admin\CurriculumRegistryManagement;
use App\Livewire\Admin\TeacherSubjectAssignmentManagement;
use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\MultiCurriculumStructure;
use App\Models\MultiCurriculumVersion;
use App\Models\Permission;
use App\Models\Rombel;
use App\Models\Role;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\TeacherSubjectAssignment;
use App\Models\Tenant;
use App\Models\TenantCurriculumRegistration;
use App\Models\TenantCurriculumSubjectMapping;
use App\Models\User;
use App\Services\AcademicYearManagementService;
use App\Services\TenantAcademicBootstrapService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TeacherSubjectAssignmentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_register_official_version_map_subject_and_assign_teacher_to_rombel_period(): void
    {
        $this->seed(DatabaseSeeder::class);

        $tenant = Tenant::create(['name' => 'MIN Teacher Assignment Test', 'code' => 'MINTEACHASSIGN']);
        $tenantId = (string) $tenant->id;
        app(TenantContext::class)->setTenantId($tenantId);

        $adminRole = Role::where('name', 'ADMIN_MADRASAH')->firstOrFail();
        $guruRole = Role::where('name', 'GURU')->firstOrFail();
        $academicPermission = Permission::firstOrCreate(['name' => 'academic.manage'], ['label' => 'Kelola Data Akademik']);
        $registryPermission = Permission::firstOrCreate(['name' => 'curriculum.registry.manage'], ['label' => 'Kelola Registry Kurikulum']);
        $adminRole->permissions()->syncWithoutDetaching([$academicPermission->id, $registryPermission->id]);

        $admin = User::create([
            'tenant_id' => $tenantId,
            'name' => 'Admin Assignment Test',
            'username' => 'admin.assignment.test',
            'password' => bcrypt('StrongTestPassword123!'),
        ]);
        $admin->roles()->sync([$adminRole->id]);
        $this->actingAs($admin);

        app(TenantAcademicBootstrapService::class)->provisionForTenant($tenantId);

        $teacherUser = User::create([
            'tenant_id' => $tenantId,
            'name' => 'Guru Assignment Test',
            'username' => 'guru.assignment.test',
            'password' => bcrypt('StrongTestPassword123!'),
        ]);
        $teacherUser->roles()->sync([$guruRole->id]);
        $teacherProfile = TeacherProfile::create($this->withSyntheticTeacherIdentity([
            'tenant_id' => $tenantId,
            'user_id' => $teacherUser->id,
            'full_name' => 'Guru Assignment Test',
            'employment_status' => 'PNS',
            'nip' => '198001012006041001',
        ]));

        $subject = Subject::create([
            'tenant_id' => $tenantId,
            'code' => 'MAT',
            'name' => 'Matematika',
            'status' => 'ACTIVE',
        ]);

        $academicYear = AcademicYear::query()
            ->where('tenant_id', $tenantId)
            ->where('name', '2026/2027')
            ->firstOrFail();
        $gradeLevel = GradeLevel::query()
            ->where('tenant_id', $tenantId)
            ->where('code', '1')
            ->firstOrFail();
        $rombel = Rombel::create([
            'academic_year_id' => $academicYear->id,
            'grade_level_id' => $gradeLevel->id,
            'name' => 'Kelas 1A',
            'status' => 'ACTIVE',
        ]);
        $semester = Semester::query()
            ->where('tenant_id', $tenantId)
            ->where('academic_year_id', $academicYear->id)
            ->where('term', 'GANJIL')
            ->firstOrFail();

        app(AcademicYearManagementService::class)
            ->activateSemesterForTenant($tenantId, (string) $semester->id);

        $systemTenantId = Tenant::SYSTEM_TENANT_ID;
        $version = MultiCurriculumVersion::query()
            ->where('tenant_id', $systemTenantId)
            ->where('version_code', 'KMA-1503-2025')
            ->firstOrFail();

        Livewire::test(CurriculumRegistryManagement::class)
            ->call('registerVersion', (string) $version->id)
            ->assertHasNoErrors();

        $registration = TenantCurriculumRegistration::query()
            ->where('tenant_id', $tenantId)
            ->where('multi_curriculum_version_id', $version->id)
            ->where('curriculum_owner_id', $systemTenantId)
            ->firstOrFail();

        $subjectNode = MultiCurriculumStructure::query()
            ->where('tenant_id', $systemTenantId)
            ->where('multi_curriculum_version_id', $version->id)
            ->where('node_type', 'SUBJECT')
            ->where('code', 'FASE_A_MATEMATIKA')
            ->firstOrFail();

        Livewire::test(CurriculumRegistryManagement::class)
            ->set('selectedRegistrationId', (string) $registration->id)
            ->set('selectedSubjectId', (string) $subject->id)
            ->set('selectedStructureId', (string) $subjectNode->id)
            ->call('mapSubject')
            ->assertHasNoErrors();

        $mapping = TenantCurriculumSubjectMapping::query()
            ->where('tenant_id', $tenantId)
            ->where('subject_id', $subject->id)
            ->where('tenant_curriculum_registration_id', $registration->id)
            ->where('multi_curriculum_structure_id', $subjectNode->id)
            ->where('status', 'ACTIVE')
            ->firstOrFail();

        Livewire::test(TeacherSubjectAssignmentManagement::class)
            ->set('selectedTeacherProfileId', (string) $teacherProfile->id)
            ->set('selectedSubjectId', (string) $subject->id)
            ->set('selectedRombelId', (string) $rombel->id)
            ->set('selectedSemesterId', (string) $semester->id)
            ->set('selectedRegistrationId', (string) $registration->id)
            ->set('startsAt', now()->toDateString())
            ->set('endsAt', now()->addMonths(6)->toDateString())
            ->call('createAssignment')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('teacher_subject_assignments', [
            'tenant_id' => $tenantId,
            'teacher_profile_id' => $teacherProfile->id,
            'subject_id' => $subject->id,
            'rombel_id' => $rombel->id,
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
            'tenant_curriculum_registration_id' => $registration->id,
            'multi_curriculum_version_id' => $version->id,
            'curriculum_owner_id' => $systemTenantId,
            'status' => 'ACTIVE',
        ]);

        $assignment = TeacherSubjectAssignment::query()
            ->where('tenant_id', $tenantId)
            ->where('teacher_profile_id', $teacherProfile->id)
            ->where('subject_id', $subject->id)
            ->where('rombel_id', $rombel->id)
            ->firstOrFail();

        $this->assertSame('FASE_A', $assignment->curriculum_context_snapshot['phase']['code']);
        $this->assertSame('MI_1', $assignment->curriculum_context_snapshot['grade']['code']);
        $this->assertSame($subjectNode->id, $assignment->curriculum_context_snapshot['subject']['official_structure_id']);
        $this->assertSame($mapping->id, $mapping->fresh()->id);
    }
}
