<?php

namespace Tests\Feature;

use App\Contexts\TenantContext;
use App\Services\AuditService;
use App\Services\StudentImportService;
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

    private function createValidStudentXlsx(): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray(['nama', 'nisn'], null, 'A1');
        $sheet->setCellValue('A2', 'Siswa Security');
        $sheet->setCellValueExplicit('B2', '0000000123', DataType::TYPE_STRING);

        $temporaryPath = tempnam(sys_get_temp_dir(), 'student-import-security-');
        if ($temporaryPath === false) {
            throw new \RuntimeException('Unable to create XLSX fixture path.');
        }

        $xlsxPath = $temporaryPath . '.xlsx';
        rename($temporaryPath, $xlsxPath);

        try {
            (new Xlsx($spreadsheet))->save($xlsxPath);
            $content = file_get_contents($xlsxPath);
            if (!is_string($content)) {
                throw new \RuntimeException('Unable to read XLSX fixture.');
            }

            return $content;
        } finally {
            $spreadsheet->disconnectWorksheets();
            @unlink($xlsxPath);
        }
    }

    private function mutateXlsx(string $content, callable $mutator): string
    {
        $temporaryPath = tempnam(sys_get_temp_dir(), 'student-import-mutated-');
        if ($temporaryPath === false) {
            throw new \RuntimeException('Unable to create mutated XLSX fixture path.');
        }

        $xlsxPath = $temporaryPath . '.xlsx';
        rename($temporaryPath, $xlsxPath);

        try {
            file_put_contents($xlsxPath, $content);
            $zip = new \ZipArchive();
            if ($zip->open($xlsxPath) !== true) {
                throw new \RuntimeException('Unable to open XLSX fixture for mutation.');
            }

            try {
                $mutator($zip);
            } finally {
                $zip->close();
            }

            $mutated = file_get_contents($xlsxPath);
            if (!is_string($mutated)) {
                throw new \RuntimeException('Unable to read mutated XLSX fixture.');
            }

            return $mutated;
        } finally {
            @unlink($xlsxPath);
        }
    }

    private function assertXlsxPreviewRejected(string $content, ?string $expectedMessage = null): void
    {
        $file = UploadedFile::fake()->createWithContent('siswa.xlsx', $content);

        $component = Livewire::test(StudentImport::class)
            ->set('selectedRombelId', $this->rombel->id)
            ->set('file', $file)
            ->call('preview')
            ->assertHasErrors('file');

        if ($expectedMessage !== null) {
            $component->assertSee($expectedMessage);
        }

        $this->assertDatabaseCount('student_profiles', 0);
        $this->assertDatabaseCount('student_import_batches', 0);
    }

    private function assertCsvPreviewRejected(string $content, ?string $expectedMessage = null): void
    {
        $file = UploadedFile::fake()->createWithContent('siswa.csv', $content);

        $component = Livewire::test(StudentImport::class)
            ->set('selectedRombelId', $this->rombel->id)
            ->set('file', $file)
            ->call('preview')
            ->assertHasErrors('file');

        if ($expectedMessage !== null) {
            $component->assertSee($expectedMessage);
        }

        $this->assertDatabaseCount('student_profiles', 0);
        $this->assertDatabaseCount('student_import_batches', 0);
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
            ->assertSee('1 akun Siswa berhasil diimpor');

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


    public function test_xlsx_preflight_rejects_zip_path_traversal(): void
    {
        $this->actingAs($this->admin);
        $content = $this->mutateXlsx($this->createValidStudentXlsx(), function (\ZipArchive $zip): void {
            $this->assertTrue($zip->addFromString('../outside.xml', '<root/>'));
        });

        $this->assertXlsxPreviewRejected($content, 'Path traversal pada entri XLSX ditolak.');
    }

    public function test_xlsx_preflight_rejects_external_relationships(): void
    {
        $this->actingAs($this->admin);
        $content = $this->mutateXlsx($this->createValidStudentXlsx(), function (\ZipArchive $zip): void {
            $rels = '<?xml version="1.0"?><Relationships><Relationship Id="rId1" Target="https://example.invalid/file" TargetMode="External"/></Relationships>';
            $this->assertTrue($zip->addFromString('xl/worksheets/_rels/sheet2.xml.rels', $rels));
        });

        $this->assertXlsxPreviewRejected($content, 'External relationship pada XLSX tidak diizinkan.');
    }

    public function test_xlsx_preflight_rejects_dtd_and_entity_declarations(): void
    {
        $this->actingAs($this->admin);
        $content = $this->mutateXlsx($this->createValidStudentXlsx(), function (\ZipArchive $zip): void {
            $xml = '<?xml version="1.0"?><!DOCTYPE worksheet [<!ENTITY xxe SYSTEM "file:///etc/passwd">]><worksheet>&xxe;</worksheet>';
            $this->assertTrue($zip->addFromString('xl/worksheets/sheet2.xml', $xml));
        });

        $this->assertXlsxPreviewRejected($content, 'DTD atau XML entity pada XLSX tidak diizinkan.');
    }

    public function test_xlsx_preflight_rejects_oversized_worksheet_dimensions(): void
    {
        $this->actingAs($this->admin);
        $content = $this->mutateXlsx($this->createValidStudentXlsx(), function (\ZipArchive $zip): void {
            $xml = '<?xml version="1.0"?><worksheet><dimension ref="A1:AZ5002"/></worksheet>';
            $this->assertTrue($zip->addFromString('xl/worksheets/sheet2.xml', $xml));
        });

        $this->assertXlsxPreviewRejected($content, 'Maksimal 5.000 baris data per proses import.');
    }

    public function test_xlsx_preflight_checks_cell_references_even_when_dimension_is_forged_small(): void
    {
        $this->actingAs($this->admin);
        $content = $this->mutateXlsx($this->createValidStudentXlsx(), function (\ZipArchive $zip): void {
            $xml = '<?xml version="1.0"?><worksheet><dimension ref="A1:B2"/><sheetData><row r="9000"><c r="XFD9000"><v>1</v></c></row></sheetData></worksheet>';
            $this->assertTrue($zip->addFromString('xl/worksheets/sheet2.xml', $xml));
        });

        $this->assertXlsxPreviewRejected($content, 'Maksimal 5.000 baris data per proses import.');
    }

    public function test_xlsx_preflight_rejects_excessive_zip_entry_count(): void
    {
        $this->actingAs($this->admin);
        $content = $this->mutateXlsx($this->createValidStudentXlsx(), function (\ZipArchive $zip): void {
            for ($index = 0; $index < 260; $index++) {
                $this->assertTrue($zip->addFromString('extra/entry-' . $index . '.txt', 'x'));
            }
        });

        $this->assertXlsxPreviewRejected($content, 'Struktur XLSX memiliki jumlah entri ZIP yang tidak didukung.');
    }

    public function test_xlsx_preflight_rejects_excessive_compression_ratio(): void
    {
        $this->actingAs($this->admin);
        $content = $this->mutateXlsx($this->createValidStudentXlsx(), function (\ZipArchive $zip): void {
            $entry = 'xl/worksheetExtensions/compression-ratio.xml';
            $this->assertTrue($zip->addFromString($entry, str_repeat('A', 1024 * 1024)));
            $this->assertTrue($zip->setCompressionName($entry, \ZipArchive::CM_DEFLATE));
        });

        $this->assertXlsxPreviewRejected($content, 'Compression ratio XLSX melebihi batas keamanan.');
    }

    public function test_xlsx_preflight_rejects_single_oversized_zip_entry(): void
    {
        $this->actingAs($this->admin);
        $content = $this->mutateXlsx($this->createValidStudentXlsx(), function (\ZipArchive $zip): void {
            $entry = 'xl/custom/oversized.xml';
            $this->assertTrue($zip->addFromString($entry, str_repeat('A', 25 * 1024 * 1024)));
            $this->assertTrue($zip->setCompressionName($entry, \ZipArchive::CM_DEFLATE));
        });

        $this->assertXlsxPreviewRejected($content, 'Ukuran atau metode kompresi entri XLSX tidak didukung.');
    }

    public function test_xlsx_preflight_rejects_total_expanded_size_over_limit(): void
    {
        $this->actingAs($this->admin);
        $block = str_repeat('A', 62 * 1024) . random_bytes(2 * 1024);
        $expanded = str_repeat($block, 272);

        $content = $this->mutateXlsx($this->createValidStudentXlsx(), function (\ZipArchive $zip) use ($expanded): void {
            foreach (['expanded-a.xml', 'expanded-b.xml'] as $name) {
                $entry = 'xl/custom/' . $name;
                $this->assertTrue($zip->addFromString($entry, $expanded));
                $this->assertTrue($zip->setCompressionName($entry, \ZipArchive::CM_DEFLATE));
            }
        });

        $this->assertXlsxPreviewRejected($content, 'Ukuran hasil ekstraksi XLSX melebihi batas keamanan.');
    }

    public function test_csv_import_rejects_more_than_32_columns(): void
    {
        $this->actingAs($this->admin);
        $headers = array_merge(['nama', 'nisn'], array_map(
            static fn (int $index): string => 'extra_' . $index,
            range(1, 31)
        ));

        $this->assertCsvPreviewRejected(implode(',', $headers) . "\n", 'Jumlah kolom CSV pada header tidak konsisten atau melebihi batas.');
    }

    public function test_csv_import_rejects_oversized_records(): void
    {
        $this->actingAs($this->admin);
        $csv = "nama,nisn\n" . str_repeat('X', 70000) . ",0001234567\n";

        $this->assertCsvPreviewRejected($csv);
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


    public function test_xlsx_worksheet_dimensions_are_rejected_before_import_preview_allocation(): void
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
            ->assertSee('Maksimal 5.000 baris data');

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
}
