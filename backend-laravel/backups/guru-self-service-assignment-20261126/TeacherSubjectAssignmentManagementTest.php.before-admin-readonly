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
use App\Services\TenantAcademicBootstrapper;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

        app(TenantAcademicBootstrapper::class)->bootstrap($tenantId);

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
            ->where('is_active', true)
            ->firstOrFail();
        $gradeLevel = GradeLevel::query()
            ->where('tenant_id', $tenantId)
            ->where('code', 'MI_1')
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
            ->where('is_active', true)
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
            ->set('selectedSemesterId', (string) $semester->id)
            ->set('selectedRombelId', (string) $rombel->id)
            ->assertSet('selectedSemesterId', '')
            ->assertSee('Semester Ganjil — ' . $academicYear->name)
            ->set('selectedSemesterId', (string) $semester->id)
            ->set('selectedRegistrationId', (string) $registration->id)
            ->set('startsAt', now()->toDateString())
            ->set('endsAt', now()->addMonths(6)->toDateString())
            ->call('createAssignment')
            ->assertHasNoErrors()
            ->assertSee('Guru Assignment Test')
            ->assertSee('Semester Ganjil')
            ->assertSee($academicYear->name);

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

        // Bulk assignment: two mapped subjects for the same teacher and rombel
        // are created in one atomic submission.
        $bahasaSubject = Subject::create([
            'tenant_id' => $tenantId,
            'code' => 'BHS_IND',
            'name' => 'Bahasa Indonesia',
            'status' => 'ACTIVE',
        ]);
        $bahasaNode = MultiCurriculumStructure::query()
            ->where('tenant_id', $systemTenantId)
            ->where('multi_curriculum_version_id', $version->id)
            ->where('node_type', 'SUBJECT')
            ->where('code', 'FASE_A_BAHASA_INDONESIA')
            ->firstOrFail();

        Livewire::test(CurriculumRegistryManagement::class)
            ->set('selectedRegistrationId', (string) $registration->id)
            ->set('selectedSubjectId', (string) $bahasaSubject->id)
            ->set('selectedStructureId', (string) $bahasaNode->id)
            ->call('mapSubject')
            ->assertHasNoErrors();

        $secondRombel = Rombel::create([
            'academic_year_id' => $academicYear->id,
            'grade_level_id' => $gradeLevel->id,
            'name' => 'Kelas 1B',
            'status' => 'ACTIVE',
        ]);

        Livewire::test(TeacherSubjectAssignmentManagement::class)
            ->set('selectedTeacherProfileId', (string) $teacherProfile->id)
            ->set('selectedSubjectIds', [(string) $subject->id, (string) $bahasaSubject->id])
            ->set('selectedRombelId', (string) $secondRombel->id)
            ->set('selectedSemesterId', (string) $semester->id)
            ->set('selectedRegistrationId', (string) $registration->id)
            ->set('startsAt', now()->toDateString())
            ->set('endsAt', now()->addMonths(6)->toDateString())
            ->call('createBulkAssignments')
            ->assertHasNoErrors()
            ->assertDispatched('notify');

        $this->assertDatabaseHas('teacher_subject_assignments', [
            'tenant_id' => $tenantId,
            'teacher_profile_id' => $teacherProfile->id,
            'subject_id' => $subject->id,
            'rombel_id' => $secondRombel->id,
            'semester_id' => $semester->id,
            'status' => 'ACTIVE',
        ]);
        $this->assertDatabaseHas('teacher_subject_assignments', [
            'tenant_id' => $tenantId,
            'teacher_profile_id' => $teacherProfile->id,
            'subject_id' => $bahasaSubject->id,
            'rombel_id' => $secondRombel->id,
            'semester_id' => $semester->id,
            'status' => 'ACTIVE',
        ]);

        // A madrasah-defined Mulok subject is mapped to the official generic
        // Muatan Lokal node for the rombel phase without changing the registry.
        $mulokSubject = Subject::create([
            'tenant_id' => $tenantId,
            'code' => 'BUDAYA_BETAWI',
            'name' => 'Budaya Betawi',
            'short_name' => 'Budaya Betawi',
            'subject_category' => 'MULOK',
            'status' => 'ACTIVE',
        ]);

        Livewire::test(TeacherSubjectAssignmentManagement::class)
            ->assertSee('Budaya Betawi (BUDAYA_BETAWI) — Mulok')
            ->set('selectedTeacherProfileId', (string) $teacherProfile->id)
            ->set('selectedSubjectId', (string) $mulokSubject->id)
            ->set('selectedRombelId', (string) $rombel->id)
            ->set('selectedSemesterId', (string) $semester->id)
            ->set('selectedRegistrationId', (string) $registration->id)
            ->set('startsAt', now()->toDateString())
            ->set('endsAt', now()->addMonths(6)->toDateString())
            ->call('createAssignment')
            ->assertHasNoErrors();

        $mulokNode = MultiCurriculumStructure::query()
            ->where('tenant_id', $systemTenantId)
            ->where('multi_curriculum_version_id', $version->id)
            ->where('code', 'FASE_A_MUATAN_LOKAL')
            ->firstOrFail();
        $mulokMapping = TenantCurriculumSubjectMapping::query()
            ->where('tenant_id', $tenantId)
            ->where('subject_id', $mulokSubject->id)
            ->where('multi_curriculum_structure_id', $mulokNode->id)
            ->where('status', 'ACTIVE')
            ->firstOrFail();

        $this->assertSame('TENANT_DEFINED_LOCAL_CONTENT', $mulokMapping->metadata['mapping_method']);
        $this->assertDatabaseHas('teacher_subject_assignments', [
            'tenant_id' => $tenantId,
            'teacher_profile_id' => $teacherProfile->id,
            'subject_id' => $mulokSubject->id,
            'rombel_id' => $rombel->id,
            'status' => 'ACTIVE',
        ]);

        $inactiveTeacherUser = User::create([
            'tenant_id' => $tenantId,
            'name' => 'Guru Tidak Aktif',
            'username' => 'guru.assignment.inactive',
            'password' => bcrypt('StrongTestPassword123!'),
        ]);
        $inactiveTeacherUser->roles()->sync([$guruRole->id]);
        DB::table('users')->where('id', $inactiveTeacherUser->id)->update(['is_active' => false]);

        $inactiveTeacherProfile = TeacherProfile::create($this->withSyntheticTeacherIdentity([
            'tenant_id' => $tenantId,
            'user_id' => $inactiveTeacherUser->id,
            'full_name' => 'Guru Tidak Aktif',
            'employment_status' => 'PNS',
            'nip' => '198001012006041002',
        ]));

        Livewire::test(TeacherSubjectAssignmentManagement::class)
            ->set('selectedTeacherProfileId', (string) $inactiveTeacherProfile->id)
            ->set('selectedSubjectId', (string) $subject->id)
            ->set('selectedRombelId', (string) $rombel->id)
            ->set('selectedSemesterId', (string) $semester->id)
            ->set('selectedRegistrationId', (string) $registration->id)
            ->set('startsAt', now()->toDateString())
            ->set('endsAt', now()->addMonths(6)->toDateString())
            ->call('createAssignment')
            ->assertHasErrors(['selectedTeacherProfileId']);

        $this->assertDatabaseMissing('teacher_subject_assignments', [
            'tenant_id' => $tenantId,
            'teacher_profile_id' => $inactiveTeacherProfile->id,
            'subject_id' => $subject->id,
            'rombel_id' => $rombel->id,
        ]);

        // Guru creates their own class through the Guru portal. A forged
        // teacher_profile_id must never change the authenticated owner.
        $selfServiceRombel = Rombel::create([
            'academic_year_id' => $academicYear->id,
            'grade_level_id' => $gradeLevel->id,
            'name' => 'Kelas 1C',
            'status' => 'ACTIVE',
        ]);

        $this->actingAs($teacherUser);
        Livewire::test(\App\Livewire\Teacher\TeacherAssignmentsPage::class)
            ->set('selectedTeacherProfileId', '00000000-0000-4000-8000-000000000099')
            ->set('selectedSubjectIds', [(string) $subject->id])
            ->set('selectedRombelId', (string) $selfServiceRombel->id)
            ->set('selectedSemesterId', (string) $semester->id)
            ->set('startsAt', now()->toDateString())
            ->set('endsAt', now()->addMonths(6)->toDateString())
            ->call('createBulkAssignments')
            ->assertHasNoErrors()
            ->assertDispatched('notify');

        $this->assertDatabaseHas('teacher_subject_assignments', [
            'tenant_id' => $tenantId,
            'teacher_profile_id' => $teacherProfile->id,
            'created_by' => $teacherUser->id,
            'subject_id' => $subject->id,
            'rombel_id' => $selfServiceRombel->id,
            'semester_id' => $semester->id,
            'status' => 'ACTIVE',
        ]);
        $this->assertDatabaseMissing('teacher_subject_assignments', [
            'tenant_id' => $tenantId,
            'teacher_profile_id' => '00000000-0000-4000-8000-000000000099',
            'subject_id' => $subject->id,
            'rombel_id' => $selfServiceRombel->id,
        ]);
    }
}
