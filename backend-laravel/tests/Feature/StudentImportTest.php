<?php

namespace Tests\Feature;

use App\Contexts\TenantContext;
use App\Livewire\Admin\StudentImport;
use App\Models\AcademicYear;
use App\Models\Curriculum;
use App\Models\GradeLevel;
use App\Models\Permission;
use App\Models\Phase;
use App\Models\Role;
use App\Models\Rombel;
use App\Models\StudentProfile;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Tests\TestCase;

class StudentImportTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $admin;
    private Rombel $rombel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create(['name' => 'Madrasah Import', 'code' => 'MADIMP']);
        app(TenantContext::class)->setTenantId($this->tenant->id);

        $adminRole = Role::firstOrCreate(['name' => 'ADMIN_MADRASAH', 'label' => 'Admin Madrasah']);
        $studentRole = Role::firstOrCreate(['name' => 'SISWA', 'label' => 'Siswa']);
        $permission = Permission::firstOrCreate(['name' => 'user.manage', 'label' => 'Manajemen Pengguna']);
        $adminRole->permissions()->syncWithoutDetaching([$permission->id]);

        $this->admin = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Admin Import',
            'password' => bcrypt('PasswordAdmin#2026'),
        ]);
        $this->admin->roles()->sync([$adminRole->id]);

        $curriculum = Curriculum::create(['name' => 'Kurikulum Merdeka', 'code' => 'KM-IMPORT', 'is_active' => true]);
        $phase = Phase::create(['curriculum_id' => $curriculum->id, 'name' => 'Fase A', 'code' => 'A']);
        $grade = GradeLevel::create(['phase_id' => $phase->id, 'name' => 'Kelas 1', 'code' => '1', 'level' => 1]);
        $year = AcademicYear::create(['name' => '2026/2027', 'is_active' => true]);
        $this->rombel = Rombel::create([
            'academic_year_id' => $year->id,
            'grade_level_id' => $grade->id,
            'name' => 'Kelas 1-A',
            'status' => 'ACTIVE',
        ]);
    }

    public function test_admin_can_preview_and_import_csv_preserving_leading_zero_nisn_and_audit_batch(): void
    {
        $this->actingAs($this->admin);
        $file = TemporaryUploadedFile::fake()->createWithContent(
            'siswa.csv',
            "nama,nisn,jenis_kelamin,tanggal_lahir\nSiswa Uji,0001234567,L,2019-01-02\n"
        );

        Livewire::test(StudentImport::class)
            ->set('selectedRombelId', $this->rombel->id)
            ->set('file', $file)
            ->call('preview')
            ->assertHasNoErrors()
            ->assertSee('0001234567')
            ->call('import')
            ->assertHasNoErrors()
            ->assertSee('1 akun Siswa berhasil diimpor');

        $profile = StudentProfile::query()->where('nisn', '0001234567')->firstOrFail();
        $this->assertSame('0001234567', $profile->nisn);
        $this->assertSame('2019-01-02', $profile->birth_date->format('Y-m-d'));
        $this->assertTrue($profile->user->hasExactRole('SISWA'));
        $this->assertCount(1, $profile->rombels);
        $this->assertDatabaseHas('student_import_batches', [
            'tenant_id' => $this->tenant->id,
            'rombel_id' => $this->rombel->id,
            'total_rows' => 1,
            'imported_rows' => 1,
            'status' => 'COMPLETED',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $this->tenant->id,
            'action' => 'STUDENT_IMPORT_BATCH_COMPLETED',
        ]);
    }

    public function test_invalid_nisn_is_rejected_before_any_student_account_is_created(): void
    {
        $this->actingAs($this->admin);
        $file = TemporaryUploadedFile::fake()->createWithContent(
            'siswa.csv',
            "nama,nisn\nSiswa Salah,123456789\n"
        );

        Livewire::test(StudentImport::class)
            ->set('selectedRombelId', $this->rombel->id)
            ->set('file', $file)
            ->call('preview')
            ->assertHasNoErrors()
            ->assertSee('NISN wajib tepat 10 digit')
            ->call('import')
            ->assertHasErrors('file');

        $this->assertDatabaseCount('student_profiles', 0);
        $this->assertDatabaseCount('student_import_batches', 0);
    }

    public function test_duplicate_nisn_inside_one_file_is_reported_by_row(): void
    {
        $this->actingAs($this->admin);
        $file = TemporaryUploadedFile::fake()->createWithContent(
            'siswa.csv',
            "nama,nisn\nSiswa Satu,0001234567\nSiswa Dua,0001234567\n"
        );

        Livewire::test(StudentImport::class)
            ->set('selectedRombelId', $this->rombel->id)
            ->set('file', $file)
            ->call('preview')
            ->assertHasNoErrors()
            ->assertSee('NISN duplikat di dalam file (baris 2)');
    }

    public function test_import_rejects_a_rombel_from_another_tenant(): void
    {
        $otherTenant = Tenant::create(['name' => 'Madrasah Lain', 'code' => 'MADLAIN']);
        app(TenantContext::class)->setTenantId($otherTenant->id);
        $otherYear = AcademicYear::create(['name' => '2026/2027', 'is_active' => true]);
        $otherCurriculum = Curriculum::create(['name' => 'Kurikulum Lain', 'code' => 'KM-LAIN', 'is_active' => true]);
        $otherPhase = Phase::create(['curriculum_id' => $otherCurriculum->id, 'name' => 'Fase A', 'code' => 'A']);
        $otherGrade = GradeLevel::create(['phase_id' => $otherPhase->id, 'name' => 'Kelas 1', 'code' => '1', 'level' => 1]);
        $otherRombel = Rombel::create([
            'academic_year_id' => $otherYear->id,
            'grade_level_id' => $otherGrade->id,
            'name' => 'Kelas Tenant Lain',
            'status' => 'ACTIVE',
        ]);

        app(TenantContext::class)->setTenantId($this->tenant->id);
        $this->actingAs($this->admin);
        $file = TemporaryUploadedFile::fake()->createWithContent('siswa.csv', "nama,nisn\nSiswa,0001234567\n");

        Livewire::test(StudentImport::class)
            ->set('selectedRombelId', $otherRombel->id)
            ->set('file', $file)
            ->call('preview')
            ->assertHasErrors('selectedRombelId');

        $this->assertDatabaseCount('student_profiles', 0);
    }

    public function test_non_admin_cannot_open_student_import_page(): void
    {
        $studentRole = Role::firstOrCreate(['name' => 'SISWA', 'label' => 'Siswa']);
        $student = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Siswa Biasa',
            'password' => bcrypt('PasswordSiswa#2026'),
        ]);
        $student->roles()->sync([$studentRole->id]);

        $this->actingAs($student);
        $this->get('/admin/students/import')->assertForbidden();
    }
}
