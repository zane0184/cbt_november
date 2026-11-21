<?php

namespace Tests\Feature;

use App\Livewire\Admin\AcademicManagement;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Semester;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class AcademicPeriodManagementTest extends TestCase
{
    use RefreshDatabase;

    private function signInAcademicAdmin(Tenant $tenant): User
    {
        $this->setTenantContext($tenant->id);

        $role = Role::create(['name' => 'ADMIN_MADRASAH', 'label' => 'Admin Madrasah']);
        $user = User::create([
            'name' => 'Admin Periode',
            'email' => 'admin.period.' . strtolower($tenant->code) . '@madrasah.test',
            'password' => bcrypt('password123'),
        ]);
        $user->roles()->attach($role);

        $academicPermission = new Permission(['name' => 'academic.manage', 'label' => 'Kelola Akademik']);
        $role->setRelation('permissions', collect([$academicPermission]));
        $user->setRelation('roles', collect([$role]));

        $this->actingAs($user);

        return $user;
    }

    public function test_year_and_semester_creation_never_auto_activates_and_admin_choice_is_audited(): void
    {
        $permissionRowsBefore = Permission::query()->count();
        $permissionPivotsBefore = DB::table('permission_role')->count();

        $tenant = Tenant::create(['name' => 'Madrasah Periode', 'code' => 'PERIODE']);
        $this->signInAcademicAdmin($tenant);

        Livewire::test(AcademicManagement::class)
            ->set('academicYearName', '2026/2027')
            ->call('createAcademicYear')
            ->assertHasNoErrors();

        $yearOne = AcademicYear::query()->where('name', '2026/2027')->firstOrFail();
        $this->assertFalse((bool) $yearOne->is_active);

        Livewire::test(AcademicManagement::class)
            ->set('academicYearName', '2027/2028')
            ->call('createAcademicYear')
            ->assertHasNoErrors();

        $yearTwo = AcademicYear::query()->where('name', '2027/2028')->firstOrFail();
        $this->assertFalse((bool) $yearTwo->is_active);

        Livewire::test(AcademicManagement::class)
            ->call('activateAcademicYear', $yearOne->id)
            ->assertHasNoErrors();

        $this->assertSame(1, AcademicYear::query()->where('is_active', true)->count());

        Livewire::test(AcademicManagement::class)
            ->set('selectedPeriodYearId', $yearOne->id)
            ->set('selectedPeriodTerm', 'GANJIL')
            ->call('createSemester')
            ->assertHasNoErrors();

        $semesterOne = Semester::query()->where('academic_year_id', $yearOne->id)->where('term', 'GANJIL')->firstOrFail();
        $this->assertFalse((bool) $semesterOne->is_active);

        Livewire::test(AcademicManagement::class)
            ->call('activateSemester', $semesterOne->id)
            ->assertHasNoErrors();

        $this->assertSame(1, Semester::query()->where('is_active', true)->count());

        Livewire::test(AcademicManagement::class)
            ->call('activateAcademicYear', $yearTwo->id)
            ->assertHasNoErrors();

        $this->assertTrue((bool) $yearTwo->fresh()->is_active);
        $this->assertFalse((bool) $semesterOne->fresh()->is_active);
        $this->assertSame(0, Semester::query()->where('is_active', true)->count());

        Livewire::test(AcademicManagement::class)
            ->set('selectedPeriodYearId', $yearTwo->id)
            ->set('selectedPeriodTerm', 'GENAP')
            ->call('createSemester')
            ->assertHasNoErrors();

        $semesterTwo = Semester::query()->where('academic_year_id', $yearTwo->id)->where('term', 'GENAP')->firstOrFail();
        $this->assertFalse((bool) $semesterTwo->is_active);

        Livewire::test(AcademicManagement::class)
            ->call('activateSemester', $semesterTwo->id)
            ->assertHasNoErrors();

        $this->assertSame(1, AcademicYear::query()->where('is_active', true)->count());
        $this->assertSame(1, Semester::query()->where('is_active', true)->count());
        $this->assertSame((string) $yearTwo->id, (string) Semester::query()->where('is_active', true)->value('academic_year_id'));

        $this->assertTrue(AuditLog::query()->where('tenant_id', $tenant->id)->where('action', 'ACADEMIC_YEAR_CREATED')->exists());
        $this->assertTrue(AuditLog::query()->where('tenant_id', $tenant->id)->where('action', 'ACADEMIC_YEAR_ACTIVATED')->exists());
        $this->assertTrue(AuditLog::query()->where('tenant_id', $tenant->id)->where('action', 'SEMESTER_CREATED')->exists());
        $this->assertTrue(AuditLog::query()->where('tenant_id', $tenant->id)->where('action', 'SEMESTER_ACTIVATED')->exists());
        $this->assertTrue(AuditLog::query()->where('tenant_id', $tenant->id)->where('action', 'SEMESTER_DEACTIVATED_BY_YEAR_CHANGE')->exists());

        $this->assertSame($permissionRowsBefore, Permission::query()->count());
        $this->assertSame($permissionPivotsBefore, DB::table('permission_role')->count());
    }

    public function test_only_supported_academic_years_can_be_created(): void
    {
        $tenant = Tenant::create(['name' => 'Madrasah Tahun', 'code' => 'TAHUN']);
        $this->signInAcademicAdmin($tenant);

        Livewire::test(AcademicManagement::class)
            ->set('academicYearName', '2032/2033')
            ->call('createAcademicYear')
            ->assertHasErrors(['academicYearName']);

        $this->assertSame(0, AcademicYear::query()->count());
    }

    public function test_database_constraint_rejects_two_active_years_or_semesters_for_one_tenant(): void
    {
        $tenant = Tenant::create(['name' => 'Madrasah Constraint', 'code' => 'CONSTRAINT']);
        $this->setTenantContext($tenant->id);

        AcademicYear::create(['name' => '2026/2027', 'is_active' => true]);

        try {
            AcademicYear::create(['name' => '2027/2028', 'is_active' => true]);
            $this->fail('Second active academic year should be rejected by the database constraint.');
        } catch (\Illuminate\Database\QueryException) {
            $this->assertSame(1, AcademicYear::query()->where('is_active', true)->count());
        }

        $year = AcademicYear::query()->where('is_active', true)->firstOrFail();
        Semester::create(['academic_year_id' => $year->id, 'term' => 'GANJIL', 'is_active' => true]);

        try {
            Semester::create(['academic_year_id' => $year->id, 'term' => 'GENAP', 'is_active' => true]);
            $this->fail('Second active semester should be rejected by the database constraint.');
        } catch (\Illuminate\Database\QueryException) {
            $this->assertSame(1, Semester::query()->where('is_active', true)->count());
        }
    }
}
