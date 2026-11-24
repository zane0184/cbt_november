<?php

namespace Tests\Feature;

use App\Contexts\TenantContext;
use App\Livewire\Admin\AcademicManagement;
use App\Models\AcademicYear;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Rombel;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AcademicYearManagementService;
use App\Services\TenantAcademicBootstrapService;
use App\Services\TenantCurriculumRegistryBootstrapper;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class AcademicYearManagementTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'MIN Academic Test', 'code' => 'MINACADEMIC']);
        app(TenantCurriculumRegistryBootstrapper::class)->seedForTenant((string) $this->tenant->id);
        $context = app(TenantContext::class);
        $context->setTenantId((string) $this->tenant->id);

        $adminRole = Role::firstOrCreate(['name' => 'ADMIN_MADRASAH'], ['label' => 'Admin Madrasah']);
        $permission = Permission::firstOrCreate(['name' => 'academic.manage'], ['label' => 'Kelola Data Akademik']);
        $adminRole->permissions()->syncWithoutDetaching([$permission->id]);

        $this->admin = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Admin Academic Test',
            'email' => 'admin.academic@example.test',
            'password' => bcrypt('StrongTestPassword123!'),
        ]);
        $this->admin->roles()->sync([$adminRole->id]);
        $this->actingAs($this->admin);

        app(TenantAcademicBootstrapService::class)->provisionForTenant((string) $this->tenant->id);
        $context->setTenantId((string) $this->tenant->id);
    }

    public function test_admin_can_create_year_and_explicitly_activate_one_semester_without_losing_history(): void
    {
        $this->assertSame(6, AcademicYear::query()->where('tenant_id', $this->tenant->id)->count());
        $this->assertSame(12, DB::table('semesters')->where('tenant_id', $this->tenant->id)->count());
        $this->assertSame(0, DB::table('academic_years')->where('tenant_id', $this->tenant->id)->where('is_active', true)->count());
        $this->assertSame(0, DB::table('semesters')->where('tenant_id', $this->tenant->id)->where('is_active', true)->count());

        Livewire::test(AcademicManagement::class)
            ->call('setTab', 'academic_year')
            ->set('academicYearName', '2032/2033')
            ->call('createAcademicYear')
            ->assertHasNoErrors();

        $createdYear = AcademicYear::query()
            ->where('tenant_id', $this->tenant->id)
            ->where('name', '2032/2033')
            ->firstOrFail();

        $this->assertFalse($createdYear->is_active);
        $this->assertSame(2, $createdYear->semesters()->count());
        $this->assertSame(0, $createdYear->semesters()->where('is_active', true)->count());

        $historicalYear = AcademicYear::query()
            ->where('tenant_id', $this->tenant->id)
            ->where('name', '2026/2027')
            ->firstOrFail();
        $historicalGradeLevelId = DB::table('grade_levels')
            ->where('tenant_id', $this->tenant->id)
            ->where('code', '1')
            ->value('id');

        $rombel = Rombel::create([
            'academic_year_id' => $historicalYear->id,
            'grade_level_id' => $historicalGradeLevelId,
            'name' => 'Kelas 1A',
            'status' => 'ACTIVE',
        ]);

        $targetSemesterId = DB::table('semesters')
            ->where('tenant_id', $this->tenant->id)
            ->where('academic_year_id', $createdYear->id)
            ->where('term', 'GENAP')
            ->value('id');

        Livewire::test(AcademicManagement::class)
            ->call('activateSemester', $targetSemesterId)
            ->assertHasNoErrors();

        $this->assertSame(1, DB::table('semesters')->where('tenant_id', $this->tenant->id)->where('is_active', true)->count());
        $this->assertSame(1, DB::table('academic_years')->where('tenant_id', $this->tenant->id)->where('is_active', true)->count());
        $this->assertDatabaseHas('semesters', [
            'id' => $targetSemesterId,
            'tenant_id' => $this->tenant->id,
            'term' => 'GENAP',
            'is_active' => true,
        ]);
        $this->assertFalse($historicalYear->fresh()->is_active);
        $this->assertDatabaseHas('rombels', [
            'id' => $rombel->id,
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $historicalYear->id,
        ]);
        $this->assertDatabaseHas('academic_years', [
            'id' => $historicalYear->id,
            'tenant_id' => $this->tenant->id,
            'name' => '2026/2027',
        ]);
    }

    public function test_admin_cannot_activate_another_tenants_semester(): void
    {
        $otherTenant = Tenant::create(['name' => 'MIN Other Tenant', 'code' => 'MINOTHER']);
        $context = app(TenantContext::class);
        $previousTenantId = $context->getTenantId();
        $context->setTenantId((string) $otherTenant->id);

        try {
            $otherRole = Role::firstOrCreate(['name' => 'ADMIN_MADRASAH'], ['label' => 'Admin Madrasah']);
            $otherPermission = Permission::firstOrCreate(['name' => 'academic.manage'], ['label' => 'Kelola Data Akademik']);
            $otherRole->permissions()->syncWithoutDetaching([$otherPermission->id]);
            $otherAdmin = User::create([
                'tenant_id' => $otherTenant->id,
                'name' => 'Other Admin',
                'email' => 'other.admin@example.test',
                'password' => bcrypt('StrongTestPassword123!'),
            ]);
            $otherAdmin->roles()->sync([$otherRole->id]);
            $this->actingAs($otherAdmin);

            $otherYear = app(AcademicYearManagementService::class)
                ->createForTenant((string) $otherTenant->id, '2032/2033');
            $otherSemesterId = DB::table('semesters')
                ->where('tenant_id', $otherTenant->id)
                ->where('academic_year_id', $otherYear->id)
                ->where('term', 'GANJIL')
                ->value('id');
        } finally {
            $context->setTenantId($previousTenantId);
            $this->actingAs($this->admin);
            $context->setTenantId((string) $this->tenant->id);
        }

        try {
            app(AcademicYearManagementService::class)
                ->activateSemesterForTenant((string) $this->tenant->id, (string) $otherSemesterId);
            $this->fail('Cross-tenant semester activation must be rejected.');
        } catch (ModelNotFoundException $exception) {
            $this->assertDatabaseHas('semesters', [
                'id' => $otherSemesterId,
                'tenant_id' => $otherTenant->id,
                'is_active' => false,
            ]);
        }
    }

    public function test_non_admin_cannot_manage_academic_periods(): void
    {
        $guruRole = Role::firstOrCreate(['name' => 'GURU'], ['label' => 'Guru']);
        $guru = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Guru Academic Test',
            'email' => 'guru.academic@example.test',
            'password' => bcrypt('StrongTestPassword123!'),
        ]);
        $guru->roles()->sync([$guruRole->id]);
        $this->actingAs($guru);

        try {
            app(AcademicYearManagementService::class)
                ->createForTenant((string) $this->tenant->id, '2032/2033');
            $this->fail('A Guru must not manage Admin academic periods.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }
}
