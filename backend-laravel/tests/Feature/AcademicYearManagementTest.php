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
use App\Services\TenantAcademicBootstrapService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Exceptions\HttpResponseException;
use Livewire\Livewire;
use Tests\TestCase;

class AcademicYearManagementTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->tenant = Tenant::create(['name' => 'MIN Academic Test', 'code' => 'MINACADEMIC']);
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

    public function test_admin_can_create_academic_year_with_both_semesters_and_switch_active_period_without_losing_history(): void
    {
        Livewire::test(AcademicManagement::class)
            ->call('setTab', 'academic_year')
            ->set('academicYearName', '2026/2027')
            ->call('createAcademicYear')
            ->assertHasNoErrors();

        $firstYear = AcademicYear::query()->where('tenant_id', $this->tenant->id)->where('name', '2026/2027')->firstOrFail();
        $this->assertTrue($firstYear->is_active);
        $this->assertDatabaseHas('semesters', [
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $firstYear->id,
            'term' => 'GANJIL',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('semesters', [
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $firstYear->id,
            'term' => 'GENAP',
            'is_active' => false,
        ]);

        $gradeLevelId = \Illuminate\Support\Facades\DB::table('grade_levels')
            ->where('tenant_id', $this->tenant->id)
            ->where('code', 'MI_1')
            ->value('id');
        $rombel = Rombel::create([
            'academic_year_id' => $firstYear->id,
            'grade_level_id' => $gradeLevelId,
            'name' => 'Kelas 1A',
            'status' => 'ACTIVE',
        ]);

        Livewire::test(AcademicManagement::class)
            ->call('setTab', 'academic_year')
            ->set('academicYearName', '2027/2028')
            ->call('createAcademicYear')
            ->assertHasNoErrors();

        $secondYear = AcademicYear::query()->where('tenant_id', $this->tenant->id)->where('name', '2027/2028')->firstOrFail();
        $this->assertFalse($secondYear->is_active);
        $this->assertTrue($firstYear->fresh()->is_active);

        Livewire::test(AcademicManagement::class)
            ->call('activateAcademicYear', $secondYear->id)
            ->assertHasNoErrors();

        $this->assertFalse($firstYear->fresh()->is_active);
        $this->assertTrue($secondYear->fresh()->is_active);
        $this->assertDatabaseHas('semesters', [
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $secondYear->id,
            'term' => 'GANJIL',
            'is_active' => true,
        ]);

        $genapId = \Illuminate\Support\Facades\DB::table('semesters')
            ->where('tenant_id', $this->tenant->id)
            ->where('academic_year_id', $secondYear->id)
            ->where('term', 'GENAP')
            ->value('id');

        Livewire::test(AcademicManagement::class)
            ->call('activateSemester', $genapId)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('semesters', [
            'id' => $genapId,
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('rombels', [
            'id' => $rombel->id,
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $firstYear->id,
        ]);
        $this->assertSame(2, AcademicYear::query()->where('tenant_id', $this->tenant->id)->count());
    }

    public function test_admin_cannot_manage_another_tenants_academic_year(): void
    {
        $otherTenant = Tenant::create(['name' => 'MIN Other Tenant', 'code' => 'MINOTHER']);
        $otherContext = app(TenantContext::class);
        $previous = $otherContext->getTenantId();
        $otherContext->setTenantId((string) $otherTenant->id);

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
            $otherYear = app(\App\Services\AcademicYearManagementService::class)
                ->createForTenant((string) $otherTenant->id, '2028/2029');
        } finally {
            $otherContext->setTenantId($previous);
            $this->actingAs($this->admin);
            app(TenantContext::class)->setTenantId((string) $this->tenant->id);
        }

        try {
            app(\App\Services\AcademicYearManagementService::class)
                ->activateYearForTenant((string) $this->tenant->id, (string) $otherYear->id);
            $this->fail('Cross-tenant academic-year activation must be rejected.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $exception) {
            $this->assertDatabaseHas('academic_years', [
                'id' => $otherYear->id,
                'tenant_id' => $otherTenant->id,
                'is_active' => true,
            ]);
        }
    }

    public function test_non_admin_cannot_open_academic_management_component(): void
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

        $this->get('/admin/academic')->assertForbidden();
    }
}
