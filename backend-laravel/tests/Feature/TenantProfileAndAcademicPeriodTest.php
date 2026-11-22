<?php

namespace Tests\Feature;

use App\Livewire\Admin\AcademicPeriodManagement;
use App\Livewire\Admin\MadrasahProfileManagement;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Semester;
use App\Models\Tenant;
use App\Models\User;
use App\Services\RolePermissionBootstrapper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class TenantProfileAndAcademicPeriodTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create(['name' => 'Madrasah Uji', 'code' => 'MAD-UJI']);
        app(\App\Contexts\TenantContext::class)->setTenantId($this->tenant->id);

        Role::firstOrCreate(['name' => 'ADMIN_MADRASAH'], ['label' => 'Admin Madrasah']);
        Role::firstOrCreate(['name' => 'KAMAD'], ['label' => 'Kepala Madrasah']);
        Role::firstOrCreate(['name' => 'GURU'], ['label' => 'Guru']);
        app(RolePermissionBootstrapper::class)->bootstrap();

        $this->admin = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Admin Uji',
            'email' => 'admin.uji@madrasah.test',
            'password' => bcrypt('password123'),
        ]);
        $this->admin->roles()->attach(Role::where('name', 'ADMIN_MADRASAH')->firstOrFail());
    }

    public function test_admin_can_update_madrasah_identity_and_audit_log_is_written(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(MadrasahProfileManagement::class)
            ->set('name', 'MIN Uji Terpadu')
            ->set('nsm', '111111111111')
            ->set('npsn', '12345678')
            ->set('address', 'Jl. Pendidikan No. 1')
            ->set('phone', '0211234567')
            ->set('email', 'info@minuji.sch.id')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tenants', [
            'id' => $this->tenant->id,
            'name' => 'MIN Uji Terpadu',
            'nsm' => '111111111111',
            'npsn' => '12345678',
            'address' => 'Jl. Pendidikan No. 1',
            'phone' => '0211234567',
            'email' => 'info@minuji.sch.id',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->admin->id,
            'action' => 'TENANT_PROFILE_UPDATED',
            'entity_id' => $this->tenant->id,
        ]);
    }

    public function test_logo_upload_is_stored_under_the_current_tenant_and_old_file_is_not_deleted(): void
    {
        Storage::fake('public');
        $oldPath = 'tenants/'.$this->tenant->id.'/branding/old-logo.png';
        Storage::disk('public')->put($oldPath, 'old-logo');
        $this->tenant->update(['logo_path' => $oldPath]);

        $this->actingAs($this->admin);
        Livewire::test(MadrasahProfileManagement::class)
            ->set('name', $this->tenant->name)
            ->set('logo', UploadedFile::fake()->image('logo.png', 120, 120))
            ->call('save')
            ->assertHasNoErrors();

        $newPath = $this->tenant->fresh()->logo_path;
        $this->assertNotSame($oldPath, $newPath);
        Storage::disk('public')->assertExists($newPath);
        Storage::disk('public')->assertExists($oldPath);
    }

    public function test_non_admin_cannot_open_madrasah_identity_management(): void
    {
        $guruRole = Role::where('name', 'GURU')->firstOrFail();
        $guru = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Guru Uji',
            'email' => 'guru.uji@madrasah.test',
            'password' => bcrypt('password123'),
        ]);
        $guru->roles()->attach($guruRole);

        $this->actingAs($guru);
        Livewire::test(MadrasahProfileManagement::class)->assertForbidden();
    }

    public function test_duplicate_nsm_is_rejected_without_changing_tenant_identity(): void
    {
        Tenant::create(['name' => 'Madrasah Lain', 'code' => 'MAD-LAIN', 'nsm' => '222222222222']);
        $this->actingAs($this->admin);

        Livewire::test(MadrasahProfileManagement::class)
            ->set('name', 'Nama Tidak Boleh Tersimpan')
            ->set('nsm', '222222222222')
            ->call('save')
            ->assertHasErrors(['nsm']);

        $this->assertDatabaseHas('tenants', ['id' => $this->tenant->id, 'name' => 'Madrasah Uji', 'nsm' => null]);
    }

    public function test_academic_year_and_semester_activation_is_serialized_and_keeps_history(): void
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(AcademicPeriodManagement::class)
            ->set('yearName', '2026/2027')
            ->call('createAcademicYear')
            ->assertHasNoErrors();

        $yearOne = AcademicYear::where('tenant_id', $this->tenant->id)->where('name', '2026/2027')->firstOrFail();
        $component->call('activateAcademicYear', $yearOne->id)->assertHasNoErrors();
        $component->set('selectedYearId', $yearOne->id)
            ->set('semesterTerm', 'GANJIL')
            ->call('createSemester')
            ->assertHasNoErrors();

        $semesterOne = Semester::where('tenant_id', $this->tenant->id)->where('academic_year_id', $yearOne->id)->where('term', 'GANJIL')->firstOrFail();
        $component->call('activateSemester', $semesterOne->id)->assertHasNoErrors();

        $component->set('semesterTerm', 'GENAP')->call('createSemester')->assertHasNoErrors();
        $semesterTwo = Semester::where('tenant_id', $this->tenant->id)->where('academic_year_id', $yearOne->id)->where('term', 'GENAP')->firstOrFail();
        $component->call('activateSemester', $semesterTwo->id)->assertHasNoErrors();
        $this->assertDatabaseHas('semesters', ['id' => $semesterOne->id, 'is_active' => false]);
        $this->assertDatabaseHas('semesters', ['id' => $semesterTwo->id, 'is_active' => true]);

        $component->set('yearName', '2027/2028')->call('createAcademicYear')->assertHasNoErrors();
        $yearTwo = AcademicYear::where('tenant_id', $this->tenant->id)->where('name', '2027/2028')->firstOrFail();
        $component->call('activateAcademicYear', $yearTwo->id)->assertHasNoErrors();

        $this->assertDatabaseHas('academic_years', ['id' => $yearOne->id, 'is_active' => false]);
        $this->assertDatabaseHas('academic_years', ['id' => $yearTwo->id, 'is_active' => true]);
        $this->assertDatabaseHas('semesters', ['id' => $semesterOne->id, 'is_active' => false]);
        $this->assertSame(2, AcademicYear::where('tenant_id', $this->tenant->id)->count());
        $this->assertSame(2, Semester::where('tenant_id', $this->tenant->id)->count());
        $this->assertDatabaseHas('audit_logs', ['tenant_id' => $this->tenant->id, 'action' => 'ACADEMIC_YEAR_ACTIVATED']);
        $this->assertDatabaseHas('audit_logs', ['tenant_id' => $this->tenant->id, 'action' => 'SEMESTER_ACTIVATED']);
    }

    public function test_semester_cannot_be_activated_under_an_inactive_year(): void
    {
        $this->actingAs($this->admin);
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => false]);
        $semester = Semester::create(['academic_year_id' => $year->id, 'term' => 'GENAP', 'is_active' => false]);

        Livewire::test(AcademicPeriodManagement::class)
            ->call('activateSemester', $semester->id)
            ->assertHasErrors(['selectedYearId']);

        $this->assertDatabaseHas('semesters', ['id' => $semester->id, 'is_active' => false]);
    }

    public function test_admin_cannot_activate_another_tenants_academic_year(): void
    {
        $otherTenant = Tenant::create(['name' => 'Tenant B', 'code' => 'TEN-B']);
        app(\App\Contexts\TenantContext::class)->setTenantId($otherTenant->id);
        $otherYear = AcademicYear::create(['name' => '2026/2027', 'is_active' => false]);
        app(\App\Contexts\TenantContext::class)->setTenantId($this->tenant->id);

        $this->actingAs($this->admin);
        Livewire::test(AcademicPeriodManagement::class)
            ->call('activateAcademicYear', $otherYear->id)
            ->assertStatus(404);
        $this->assertDatabaseHas('academic_years', ['id' => $otherYear->id, 'tenant_id' => $otherTenant->id, 'is_active' => false]);
    }
}
