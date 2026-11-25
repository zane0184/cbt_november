<?php

namespace Tests\Feature;

use App\Contexts\TenantContext;
use App\Services\AuditService;
use App\Services\StudentImportService;
use App\Services\LoginIdentityResolver;
use App\Livewire\Admin\StudentImport;
use App\Livewire\Admin\UserManagement;
use App\Models\AcademicYear;
use App\Models\LocalInstallationIdentity;
use App\Models\Curriculum;
use App\Models\GradeLevel;
use App\Models\Permission;
use App\Models\Phase;
use App\Models\Role;
use App\Models\Rombel;
use App\Models\StudentProfile;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
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
            'is_active' => true,
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

    public function test_admin_can_download_xlsx_template_for_student_import(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(StudentImport::class)
            ->call('downloadXlsxTemplate')
            ->assertFileDownloaded('template-import-siswa.xlsx');
    }

    public function test_admin_can_preview_and_import_csv_preserving_leading_zero_nisn_and_audit_batch(): void
    {
        $this->actingAs($this->admin);
        $file = UploadedFile::fake()->createWithContent(
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
            ->assertRedirect('/admin/users');

        $profile = StudentProfile::query()->where('nisn', '0001234567')->firstOrFail();
        $this->assertSame('0001234567', $profile->nisn);
        $this->assertSame('2019-01-02', $profile->birth_date);
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


    public function test_successful_import_batch_cannot_be_replayed(): void
    {
        $this->actingAs($this->admin);
        $csv = "nama,nisn\nSiswa Idempotent,0007654321\n";
        $first = UploadedFile::fake()->createWithContent('siswa.csv', $csv);
        Livewire::test(StudentImport::class)
            ->set('selectedRombelId', $this->rombel->id)
            ->set('file', $first)
            ->call('preview')
            ->assertHasNoErrors()
            ->call('import')
            ->assertHasNoErrors();

        $second = UploadedFile::fake()->createWithContent('siswa.csv', $csv);
        Livewire::test(StudentImport::class)
            ->set('selectedRombelId', $this->rombel->id)
            ->set('file', $second)
            ->call('preview')
            ->assertHasNoErrors()
            ->assertSee('sudah pernah berhasil diimpor');
        $this->assertDatabaseCount('student_import_batches', 1);
        $this->assertDatabaseCount('student_profiles', 1);
    }

    public function test_invalid_nisn_is_rejected_before_any_student_account_is_created(): void
    {
        $this->actingAs($this->admin);
        $file = UploadedFile::fake()->createWithContent(
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


    public function test_nisn_already_registered_in_the_database_is_reported_before_import(): void
    {
        app(TenantContext::class)->setTenantId($this->tenant->id);
        $studentRole = Role::firstOrCreate(['name' => 'SISWA', 'label' => 'Siswa']);
        $existingUser = User::create([
            'name' => 'Siswa Existing',
            'password' => bcrypt('ExistingStudent#2026'),
        ]);
        $existingUser->roles()->sync([$studentRole->id]);
        StudentProfile::create([
            'user_id' => $existingUser->id,
            'nisn' => '0003333333',
            'full_name' => 'Siswa Existing',
        ]);

        $this->actingAs($this->admin);
        $file = UploadedFile::fake()->createWithContent(
            'siswa.csv',
            "nama,nisn\nSiswa Duplikat DB,0003333333\n"
        );

        Livewire::test(StudentImport::class)
            ->set('selectedRombelId', $this->rombel->id)
            ->set('file', $file)
            ->call('preview')
            ->assertHasNoErrors()
            ->assertSee('NISN sudah terdaftar pada instalasi/madrasah ini.');

        $this->assertDatabaseCount('student_profiles', 1);
    }

    public function test_duplicate_nisn_inside_one_file_is_reported_by_row(): void
    {
        $this->actingAs($this->admin);
        $file = UploadedFile::fake()->createWithContent(
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
        $file = UploadedFile::fake()->createWithContent('siswa.csv', "nama,nisn\nSiswa,0001234567\n");

        Livewire::test(StudentImport::class)
            ->set('selectedRombelId', $otherRombel->id)
            ->set('file', $file)
            ->call('preview')
            ->assertHasErrors('selectedRombelId');

        $this->assertDatabaseCount('student_profiles', 0);
    }


    public function test_xlsx_import_preserves_nisn_when_the_cell_is_explicitly_text(): void
    {
        $this->actingAs($this->admin);
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray(['nama', 'nisn'], null, 'A1');
        $sheet->setCellValue('A2', 'Siswa XLSX');
        $sheet->setCellValueExplicit('B2', '0000000123', DataType::TYPE_STRING);
        $path = tempnam(sys_get_temp_dir(), 'student-import-') . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        $content = file_get_contents($path);
        @unlink($path);
        $spreadsheet->disconnectWorksheets();

        $file = UploadedFile::fake()->createWithContent('siswa.xlsx', $content);
        Livewire::test(StudentImport::class)
            ->set('selectedRombelId', $this->rombel->id)
            ->set('file', $file)
            ->call('preview')
            ->assertHasNoErrors()
            ->assertSee('0000000123')
            ->call('import')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('student_profiles', [
            'tenant_id' => $this->tenant->id,
            'nisn' => '0000000123',
        ]);
    }

    public function test_xlsx_numeric_nisn_cell_is_rejected_instead_of_silently_losing_leading_zeroes(): void
    {
        $this->actingAs($this->admin);
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray(['nama', 'nisn'], null, 'A1');
        $sheet->setCellValue('A2', 'Siswa Numeric');
        $sheet->setCellValue('B2', 1234567890);
        $path = tempnam(sys_get_temp_dir(), 'student-import-') . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        $content = file_get_contents($path);
        @unlink($path);
        $spreadsheet->disconnectWorksheets();

        $file = UploadedFile::fake()->createWithContent('siswa.xlsx', $content);
        Livewire::test(StudentImport::class)
            ->set('selectedRombelId', $this->rombel->id)
            ->set('file', $file)
            ->call('preview')
            ->assertHasNoErrors()
            ->assertSee('NISN harus disimpan sebagai teks');
        $this->assertDatabaseCount('student_profiles', 0);
    }


    public function test_audit_failure_rolls_back_the_entire_student_import_batch(): void
    {
        $this->actingAs($this->admin);
        $file = UploadedFile::fake()->createWithContent(
            'siswa.csv',
            "nama,nisn\nSiswa Rollback,0008888888\n"
        );
        $preview = app(StudentImportService::class)->preview(
            $file->getRealPath(),
            'csv',
            $this->tenant->id,
            $this->rombel->id
        );

        $this->mock(AuditService::class, function ($mock): void {
            $mock->shouldReceive('log')->once()->andThrow(new \RuntimeException('Simulated audit storage failure.'));
        });

        try {
            app(StudentImportService::class)->import(
                $preview,
                $this->tenant->id,
                $this->admin->id,
                $this->rombel->id
            );
            $this->fail('The simulated audit failure must abort the import transaction.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Simulated audit storage failure.', $exception->getMessage());
        }

        $this->assertDatabaseCount('student_profiles', 0);
        $this->assertDatabaseCount('rombel_student', 0);
        $this->assertDatabaseCount('student_import_batches', 0);
        $this->assertDatabaseCount('users', 1);
    }


    public function test_xlsx_worksheet_dimensions_are_rejected_before_php_spreadsheet_load(): void
    {
        $this->actingAs($this->admin);
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray(['nama', 'nisn'], null, 'A1');
        $sheet->setCellValue('A5002', 'out of bounds');
        $path = tempnam(sys_get_temp_dir(), 'student-import-') . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        $content = file_get_contents($path);
        @unlink($path);
        $spreadsheet->disconnectWorksheets();

        $file = UploadedFile::fake()->createWithContent('siswa.xlsx', $content);
        Livewire::test(StudentImport::class)
            ->set('selectedRombelId', $this->rombel->id)
            ->set('file', $file)
            ->call('preview')
            ->assertHasErrors('file')
            ->assertSee('Worksheet dimension XLSX melewati batas sebelum workbook dimuat.');

        $this->assertDatabaseCount('student_profiles', 0);
        $this->assertDatabaseCount('student_import_batches', 0);
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
    public function test_csv_rejects_more_than_32_columns(): void
    {
        $this->actingAs($this->admin);
        $headers = implode(',', array_map(fn ($index) => 'column_' . $index, range(1, 33)));
        $values = implode(',', array_fill(0, 33, 'value'));
        $file = UploadedFile::fake()->createWithContent('siswa.csv', $headers . "\n" . $values . "\n");

        Livewire::test(StudentImport::class)
            ->set('selectedRombelId', $this->rombel->id)
            ->set('file', $file)
            ->call('preview')
            ->assertHasErrors('file');

        $this->assertDatabaseCount('student_profiles', 0);
    }

    public function test_csv_rejects_cell_larger_than_4_kib(): void
    {
        $this->actingAs($this->admin);
        $file = UploadedFile::fake()->createWithContent(
            'siswa.csv',
            "nama,nisn\n" . str_repeat('A', 4097) . ",0001234567\n"
        );

        Livewire::test(StudentImport::class)
            ->set('selectedRombelId', $this->rombel->id)
            ->set('file', $file)
            ->call('preview')
            ->assertHasErrors('file');
    }

    public function test_csv_rejects_record_larger_than_256_kib(): void
    {
        $this->actingAs($this->admin);
        $file = UploadedFile::fake()->createWithContent(
            'siswa.csv',
            "nama,nisn\n" . str_repeat('A', 300 * 1024) . ",0001234567\n"
        );

        Livewire::test(StudentImport::class)
            ->set('selectedRombelId', $this->rombel->id)
            ->set('file', $file)
            ->call('preview')
            ->assertHasErrors('file');
    }

    public function test_csv_rejects_invalid_utf8_and_nul_bytes(): void
    {
        $this->actingAs($this->admin);

        foreach (["Siswa\xFF,0001234567", "Siswa\0Uji,0001234567"] as $record) {
            $file = UploadedFile::fake()->createWithContent('siswa.csv', "nama,nisn\n" . $record . "\n");
            Livewire::test(StudentImport::class)
                ->set('selectedRombelId', $this->rombel->id)
                ->set('file', $file)
                ->call('preview')
                ->assertHasErrors('file');
        }
    }

    public function test_csv_rejects_inconsistent_column_count_but_ignores_blank_lines(): void
    {
        $this->actingAs($this->admin);
        $valid = UploadedFile::fake()->createWithContent(
            'siswa.csv',
            "nama,nisn\n\nSiswa Valid,0001234567\n"
        );

        Livewire::test(StudentImport::class)
            ->set('selectedRombelId', $this->rombel->id)
            ->set('file', $valid)
            ->call('preview')
            ->assertHasNoErrors()
            ->assertSee('0001234567');

        $invalid = UploadedFile::fake()->createWithContent(
            'siswa.csv',
            "nama,nisn\nSiswa Invalid,0001234567,extra\n"
        );

        Livewire::test(StudentImport::class)
            ->set('selectedRombelId', $this->rombel->id)
            ->set('file', $invalid)
            ->call('preview')
            ->assertHasErrors('file');
    }

    public function test_csv_blank_lines_do_not_consume_5000_data_row_limit_and_keep_physical_row_numbers(): void
    {
        $this->actingAs($this->admin);
        $csv = "nama,nisn\n";
        for ($index = 1; $index <= 5000; $index++) {
            $csv .= 'Siswa ' . $index . ',' . sprintf('%010d', $index) . "\n\n";
        }
        $file = UploadedFile::fake()->createWithContent('siswa.csv', $csv);

        Livewire::test(StudentImport::class)
            ->set('selectedRombelId', $this->rombel->id)
            ->set('file', $file)
            ->call('preview')
            ->assertHasNoErrors()
            ->assertSet('summary.total', 5000)
            ->assertSet('previewRows.4999.row', 10000);

        $this->assertDatabaseCount('student_profiles', 0);
    }

    public function test_csv_rejects_more_than_5000_nonblank_data_rows(): void
    {
        $this->actingAs($this->admin);
        $csv = "nama,nisn\n";
        for ($index = 1; $index <= 5001; $index++) {
            $csv .= 'Siswa ' . $index . ',' . sprintf('%010d', $index) . "\n";
        }
        $file = UploadedFile::fake()->createWithContent('siswa.csv', $csv);

        Livewire::test(StudentImport::class)
            ->set('selectedRombelId', $this->rombel->id)
            ->set('file', $file)
            ->call('preview')
            ->assertHasErrors('file')
            ->assertSee('Maksimal 5.000 baris data per proses import.');

        $this->assertDatabaseCount('student_profiles', 0);
        $this->assertDatabaseCount('student_import_batches', 0);
    }

    public function test_service_enforces_upload_size_limit_without_livewire_validation(): void
    {
        $this->actingAs($this->admin);
        $path = tempnam(sys_get_temp_dir(), 'student-import-large-');
        file_put_contents($path, str_repeat('A', 10 * 1024 * 1024 + 1));

        try {
            $this->expectException(ValidationException::class);
            app(StudentImportService::class)->preview($path, 'csv', $this->tenant->id, $this->rombel->id);
        } finally {
            @unlink($path);
        }
    }

    public function test_service_preview_rejects_non_admin_direct_invocation(): void
    {
        $teacherRole = Role::firstOrCreate(['name' => 'GURU', 'label' => 'Guru']);
        $teacher = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Guru Bukan Admin',
            'password' => bcrypt('PasswordGuru#2026'),
            'is_active' => true,
        ]);
        $teacher->roles()->sync([$teacherRole->id]);
        $this->actingAs($teacher);

        $this->expectException(AuthorizationException::class);
        app(StudentImportService::class)->preview('/not-used.csv', 'csv', $this->tenant->id, $this->rombel->id);
    }

    public function test_service_rejects_cross_tenant_preview_even_when_admin_is_authenticated(): void
    {
        $otherTenant = Tenant::create(['name' => 'Tenant Tidak Sah', 'code' => 'TENUNSAH']);
        $this->actingAs($this->admin);

        $this->expectException(AuthorizationException::class);
        app(StudentImportService::class)->preview('/not-used.csv', 'csv', $otherTenant->id, $this->rombel->id);
    }

    public function test_service_import_rejects_actor_id_different_from_authenticated_admin(): void
    {
        $otherUser = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Akun Lain',
            'password' => bcrypt('PasswordOther#2026'),
            'is_active' => true,
        ]);
        $this->actingAs($this->admin);

        $this->expectException(AuthorizationException::class);
        app(StudentImportService::class)->import([], $this->tenant->id, $otherUser->id, $this->rombel->id);
    }

    public function test_student_import_creates_login_ready_account_and_one_time_xlsx_credentials(): void
    {
        $this->actingAs($this->admin);
        LocalInstallationIdentity::create([
            'tenant_id' => $this->tenant->id,
            'installation_id' => (string) \Illuminate\Support\Str::uuid(),
            'key_id' => 'test-key-student',
            'public_key' => 'test-public-key',
            'encrypted_private_key' => 'test-encrypted-private-key',
            'key_fingerprint' => str_repeat('a', 64),
            'registered_domain' => 'test.example',
            'product_code' => 'CBT-TEST-STUDENT',
            'product_version' => '1.0.0',
        ]);

        $file = UploadedFile::fake()->createWithContent('siswa.csv', "nama,nisn\nSiswa Kredensial,0007654321\n");
        $preview = app(StudentImportService::class)->preview(
            $file->getRealPath(), 'csv', $this->tenant->id, $this->rombel->id
        );
        $result = app(StudentImportService::class)->import(
            $preview, $this->tenant->id, $this->admin->id, $this->rombel->id
        );

        $credential = $result['credentials'][0];
        $profile = StudentProfile::query()->where('nisn', '0007654321')->firstOrFail();
        $this->assertSame('0007654321', $credential['login_identifier']);
        $this->assertSame('NISN', $credential['identifier_type']);
        $this->assertSame($profile->user_id, app(LoginIdentityResolver::class)->resolve('0007654321')?->id);
        $this->assertTrue(Hash::check($credential['password'], $profile->user->password));
        $this->assertNotSame($credential['password'], $profile->user->password);

        session()->flash('import_credentials', $result['credentials']);
        session()->flash('import_credentials_label', 'Siswa');
        session()->flash('import_batch_id', $result['batch_id']);
        Livewire::test(UserManagement::class)
            ->assertSee('Kredensial awal Siswa berhasil dibuat')
            ->assertSee('0007654321')
            ->assertSee($credential['password'])
            ->call('downloadImportCredentialsXlsx')
            ->assertFileDownloaded('kredensial-import-siswa.xlsx');
    }

}
