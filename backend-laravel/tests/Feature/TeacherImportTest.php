<?php

namespace Tests\Feature;

use App\Contexts\TenantContext;
use App\Livewire\Admin\TeacherImport;
use App\Models\Permission;
use App\Models\Role;
use App\Models\TeacherProfile;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AuditService;
use App\Services\TeacherImportService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class TeacherImportTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'Madrasah Guru Import', 'code' => 'GURUIMP']);
        app(TenantContext::class)->setTenantId($this->tenant->id);

        $adminRole = Role::firstOrCreate(['name' => 'ADMIN_MADRASAH', 'label' => 'Admin Madrasah']);
        $teacherRole = Role::firstOrCreate(['name' => 'GURU', 'label' => 'Guru']);
        $permission = Permission::firstOrCreate(['name' => 'user.manage', 'label' => 'Manajemen Pengguna']);
        $adminRole->permissions()->syncWithoutDetaching([$permission->id]);

        $this->admin = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Admin Guru Import',
            'password' => bcrypt('PasswordAdmin#2026'),
            'is_active' => true,
        ]);
        $this->admin->roles()->sync([$adminRole->id]);
    }

    public function test_admin_can_download_teacher_xlsx_template(): void
    {
        $this->actingAs($this->admin);
        Livewire::test(TeacherImport::class)
            ->call('downloadXlsxTemplate')
            ->assertFileDownloaded('template-import-guru.xlsx');
    }

    public function test_admin_can_preview_and_import_asn_and_honorer_teachers_atomically(): void
    {
        $this->actingAs($this->admin);
        $csv = "nama,status_kepegawaian,nip,nik,nuptk,jenis_kelamin,telepon,email\n"
            ."Guru ASN,PNS,199001012026041001,,1234567890123456,L,081234567890,guru.asn@example.test\n"
            ."Guru Honorer,HONORER,,3171010101900001,,P,081298765432,\n";
        $file = UploadedFile::fake()->createWithContent('guru.csv', $csv);

        Livewire::test(TeacherImport::class)
            ->set('file', $file)
            ->call('preview')
            ->assertHasNoErrors()
            ->assertSee('Guru ASN')
            ->assertSee('Guru Honorer')
            ->assertSet('summary.total', 2)
            ->assertSet('summary.valid', 2)
            ->call('import')
            ->assertHasNoErrors()
            ->assertSee('2 akun Guru berhasil diimpor');

        $asn = TeacherProfile::query()->where('nip', '199001012026041001')->firstOrFail();
        $honorer = TeacherProfile::query()->where('nik', '3171010101900001')->firstOrFail();

        $this->assertSame('PNS', $asn->employment_status);
        $this->assertSame('199001012026041001', $asn->employee_identifier);
        $this->assertSame('1234567890123456', $asn->nuptk);
        $this->assertTrue($asn->user->hasExactRole('GURU'));
        $this->assertSame('HONORER', $honorer->employment_status);
        $this->assertSame('3171010101900001', $honorer->employee_identifier);
        $this->assertTrue($honorer->user->hasExactRole('GURU'));
        $this->assertDatabaseHas('teacher_import_batches', [
            'tenant_id' => $this->tenant->id,
            'total_rows' => 2,
            'imported_rows' => 2,
            'status' => 'COMPLETED',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $this->tenant->id,
            'action' => 'TEACHER_IMPORT_BATCH_COMPLETED',
        ]);
    }

    public function test_duplicate_teacher_identity_inside_file_blocks_entire_import(): void
    {
        $this->actingAs($this->admin);
        $csv = "nama,status_kepegawaian,nip,nik,nuptk\n"
            ."Guru Satu,PPPK,199001012026041001,,\n"
            ."Guru Dua,PNS,199001012026041001,,\n";
        $file = UploadedFile::fake()->createWithContent('duplikat.csv', $csv);

        Livewire::test(TeacherImport::class)
            ->set('file', $file)
            ->call('preview')
            ->assertHasNoErrors()
            ->assertSet('summary.invalid', 1)
            ->assertSee('NIP duplikat di dalam file');

        $this->assertDatabaseCount('teacher_profiles', 0);
        $this->assertDatabaseCount('teacher_import_batches', 0);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_successful_teacher_import_cannot_be_replayed(): void
    {
        $this->actingAs($this->admin);
        $csv = "nama,status_kepegawaian,nip,nik,nuptk\nGuru Idempotent,PNS,199001012026041002,,\n";
        $file = UploadedFile::fake()->createWithContent('guru.csv', $csv);

        app(TeacherImportService::class)->import(
            $file->getRealPath(),
            'csv',
            $this->tenant->id,
            $this->admin->id
        );

        $second = UploadedFile::fake()->createWithContent('guru.csv', $csv);
        Livewire::test(TeacherImport::class)
            ->set('file', $second)
            ->call('preview')
            ->assertHasNoErrors()
            ->assertSee('sudah pernah berhasil diimpor');

        $this->assertDatabaseCount('teacher_profiles', 1);
        $this->assertDatabaseCount('teacher_import_batches', 1);
    }

    public function test_invalid_employment_identity_combination_is_rejected(): void
    {
        $this->actingAs($this->admin);
        $file = UploadedFile::fake()->createWithContent(
            'guru.csv',
            "nama,status_kepegawaian,nip,nik,nuptk\nGuru Tidak Valid,HONORER,199001012026041003,,\n"
        );

        Livewire::test(TeacherImport::class)
            ->set('file', $file)
            ->call('preview')
            ->assertHasNoErrors()
            ->assertSet('summary.invalid', 1)
            ->assertSee('Guru Honorer wajib memiliki NIK tepat 16 digit')
            ->call('import')
            ->assertHasErrors('file');

        $this->assertDatabaseCount('teacher_profiles', 0);
        $this->assertDatabaseCount('teacher_import_batches', 0);
    }

    public function test_non_admin_cannot_open_teacher_import_page(): void
    {
        $teacherRole = Role::firstOrCreate(['name' => 'GURU', 'label' => 'Guru']);
        $teacher = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Guru Biasa',
            'password' => bcrypt('PasswordGuru#2026'),
            'is_active' => true,
        ]);
        $teacher->roles()->sync([$teacherRole->id]);

        $this->actingAs($teacher);
        $this->get('/admin/teachers/import')->assertForbidden();
    }

    public function test_service_rejects_direct_invocation_by_teacher(): void
    {
        $teacherRole = Role::firstOrCreate(['name' => 'GURU', 'label' => 'Guru']);
        $teacher = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Guru Tidak Berwenang',
            'password' => bcrypt('PasswordGuru#2026'),
            'is_active' => true,
        ]);
        $teacher->roles()->sync([$teacherRole->id]);
        $this->actingAs($teacher);

        $this->expectException(AuthorizationException::class);
        app(TeacherImportService::class)->preview('/not-used.csv', 'csv', $this->tenant->id);
    }

    public function test_audit_failure_rolls_back_the_entire_teacher_import_batch(): void
    {
        $this->actingAs($this->admin);
        $file = UploadedFile::fake()->createWithContent(
            'guru.csv',
            "nama,status_kepegawaian,nip,nik,nuptk\nGuru Rollback,PNS,199001012026041004,,\n"
        );

        $this->mock(AuditService::class, function ($mock): void {
            $mock->shouldReceive('log')->once()->andThrow(new \RuntimeException('Simulated audit storage failure.'));
        });

        try {
            app(TeacherImportService::class)->import(
                $file->getRealPath(),
                'csv',
                $this->tenant->id,
                $this->admin->id
            );
            $this->fail('Audit failure must roll back the complete teacher import.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Simulated audit storage failure.', $exception->getMessage());
        }

        $this->assertDatabaseCount('teacher_profiles', 0);
        $this->assertDatabaseCount('teacher_import_batches', 0);
        $this->assertDatabaseCount('users', 1);
    }
}
