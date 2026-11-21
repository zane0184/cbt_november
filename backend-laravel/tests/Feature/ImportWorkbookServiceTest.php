<?php

namespace Tests\Feature;

use App\Exports\Import\ImportTemplateVersion;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Curriculum;
use App\Models\GradeLevel;
use App\Models\LocalInstallationIdentity;
use App\Models\Phase;
use App\Models\Role;
use App\Models\Permission;
use App\Livewire\Admin\ImportManagement;
use App\Models\Rombel;
use App\Models\Semester;
use App\Models\StudentProfile;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Import\ImportWorkbookService;
use App\Services\LoginIdentityResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;
use Livewire\Livewire;

class ImportWorkbookServiceTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $admin;
    private array $temporaryFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'Madrasah Import A', 'code' => 'IMPA']);
        $this->setTenantContext($this->tenant->id);
        $adminRole = Role::create(['name' => 'ADMIN_MADRASAH', 'label' => 'Admin Madrasah']);
        $permission = Permission::create(['name' => 'user.manage', 'label' => 'Kelola Pengguna']);
        $adminRole->permissions()->attach($permission->id);
        $this->admin = User::create(['name' => 'Admin Import', 'email' => 'admin.import@madrasah.test', 'password' => Hash::make('StrongPassword123!')]);
        $this->admin->roles()->attach($adminRole);
        $this->actingAs($this->admin);
    }

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $path) if (is_file($path)) @unlink($path);
        parent::tearDown();
    }

    public function test_imports_pns_and_honorer_with_leading_zero_identity_and_one_time_activation(): void
    {
        Role::create(['name' => 'GURU', 'label' => 'Guru']);
        $file = $this->guruWorkbook([
            ['PNS', '001234567890123456', '', '0012345678901235', 'Guru PNS Contoh', 'L', '', '081234567890'],
            ['HONORER', '', '0012345678901234', '', 'Guru Honorer Contoh', 'P', '', '081234567891'],
        ]);

        LocalInstallationIdentity::create([
            'tenant_id' => $this->tenant->id,
            'installation_id' => '00000000-0000-4000-8000-000000000001',
            'key_id' => 'test-import-key',
            'public_key' => 'test-public-key',
            'encrypted_private_key' => 'test-encrypted-private-key',
            'key_fingerprint' => str_repeat('a', 64),
            'registered_domain' => 'madrasah.test',
            'product_code' => 'CBT_MADRASAH',
            'product_version' => 'test',
        ]);
        $service = app(ImportWorkbookService::class);
        $batch = $service->preview($file, 'GURU', $this->tenant->id, $this->admin->id);
        $this->assertSame(2, $batch->valid_rows);
        $this->assertSame('PREVIEW', $batch->status);

        $result = $service->confirm($batch->id, $this->tenant->id, $this->admin->id);
        $this->assertSame('COMPLETED', $result['batch']->status);
        $this->assertCount(2, $result['activation_links']);
        $this->assertDatabaseHas('teacher_profiles', ['tenant_id' => $this->tenant->id, 'nip' => '001234567890123456']);
        $this->assertDatabaseHas('teacher_profiles', ['tenant_id' => $this->tenant->id, 'nik' => '0012345678901234']);
        $resolver = app(LoginIdentityResolver::class);
        $this->assertSame('Guru PNS Contoh', $resolver->resolve('001234567890123456')?->name);
        $this->assertSame('Guru Honorer Contoh', $resolver->resolve('0012345678901234')?->name);
        $this->assertSame('001234567890123456', TeacherProfile::query()->where('nip', '001234567890123456')->firstOrFail()->user->username);
        $this->assertSame('0012345678901234', TeacherProfile::query()->where('nik', '0012345678901234')->firstOrFail()->user->username);
        $this->assertSame(2, $result['batch']->valid_rows);
        $this->assertDatabaseHas('import_activation_tokens', ['tenant_id' => $this->tenant->id]);
        $this->assertDatabaseHas('audit_logs', ['tenant_id' => $this->tenant->id, 'action' => 'IMPORT_PREVIEW_CREATED']);
        $this->assertDatabaseHas('audit_logs', ['tenant_id' => $this->tenant->id, 'action' => 'IMPORT_BATCH_CONFIRMED']);
        $this->assertSame(2, AuditLog::query()->where('tenant_id', $this->tenant->id)->where('action', 'USER_CREATED')->count());

        $activationUrl = $result['activation_links'][0]['url'];
        $guruRow = $result['batch']->rows->firstWhere('sheet_name', 'Data Guru');
        $reissued = $service->regenerateActivation($result['batch']->id, $guruRow->id, $this->tenant->id, $this->admin->id);
        $this->get($activationUrl)->assertGone();
        $activationUrl = $reissued['url'];
        $this->get($activationUrl)->assertOk()->assertHeader('Referrer-Policy', 'no-referrer');
        $this->post($activationUrl, [
            'password' => 'StrongPassword123!',
            'password_confirmation' => 'StrongPassword123!',
        ])->assertOk();
        $activatedTeacher = TeacherProfile::query()->where('tenant_id', $this->tenant->id)->where('nip', '001234567890123456')->firstOrFail();
        $this->assertTrue(Hash::check('StrongPassword123!', $activatedTeacher->user->password));
        $this->get($activationUrl)->assertGone();
        $this->assertDatabaseHas('audit_logs', ['tenant_id' => $this->tenant->id, 'action' => 'IMPORT_ACCOUNT_ACTIVATED']);
        $this->assertDatabaseHas('audit_logs', ['tenant_id' => $this->tenant->id, 'action' => 'IMPORT_ACTIVATION_LINK_REISSUED']);
    }

    public function test_imports_student_and_links_tenant_owned_rombel_and_academic_year(): void
    {
        Role::create(['name' => 'SISWA', 'label' => 'Siswa']);
        [$year, $rombel] = $this->academicSetup();
        $file = $this->studentWorkbook([
            ['000123', '0012345678', 'Siswa Uji', 'P', 'Jakarta', '2015-03-04', 'Orang Tua', '081234567890', 'Alamat Siswa', '1A — 2026/2027', '2026/2027'],
        ]);

        LocalInstallationIdentity::create([
            'tenant_id' => $this->tenant->id,
            'installation_id' => '00000000-0000-4000-8000-000000000001',
            'key_id' => 'test-import-key',
            'public_key' => 'test-public-key',
            'encrypted_private_key' => 'test-encrypted-private-key',
            'key_fingerprint' => str_repeat('a', 64),
            'registered_domain' => 'madrasah.test',
            'product_code' => 'CBT_MADRASAH',
            'product_version' => 'test',
        ]);
        $service = app(ImportWorkbookService::class);
        $batch = $service->preview($file, 'SISWA', $this->tenant->id, $this->admin->id);
        $this->assertSame(1, $batch->valid_rows);
        $result = $service->confirm($batch->id, $this->tenant->id, $this->admin->id);

        $student = StudentProfile::query()->where('tenant_id', $this->tenant->id)->where('nisn', '0012345678')->firstOrFail();
        $this->assertSame('000123', $student->nis);
        $this->assertSame('Siswa Uji', app(LoginIdentityResolver::class)->resolve('0012345678')?->name);
        $this->assertSame('0012345678', $student->user->username);
        $this->assertDatabaseHas('rombel_student', [
            'tenant_id' => $this->tenant->id,
            'rombel_id' => $rombel->id,
            'student_id' => $student->id,
        ]);
        $this->assertCount(1, $result['activation_links']);
        $this->assertSame($year->id, $rombel->academic_year_id);
    }

    public function test_teacher_assignment_resolves_subject_rombel_semester_and_year_in_same_tenant(): void
    {
        Role::create(['name' => 'GURU', 'label' => 'Guru']);
        [$year, $rombel, $semester, $subject] = $this->academicSetup(true);
        $file = $this->guruWorkbook(
            [['PNS', '001234567890123456', '', '', 'Guru Penugasan', 'L', '', '']],
            [['001234567890123456', 'MTK — Matematika', '1A — 2026/2027', '2026/2027', 'GANJIL — 2026/2027', '2026-07-01', '', 'ACTIVE']]
        );

        $service = app(ImportWorkbookService::class);
        $batch = $service->preview($file, 'GURU', $this->tenant->id, $this->admin->id);
        $this->assertSame(2, $batch->valid_rows);
        $result = $service->confirm($batch->id, $this->tenant->id, $this->admin->id);

        $this->assertDatabaseHas('teacher_subject_assignments', [
            'tenant_id' => $this->tenant->id,
            'subject_id' => $subject->id,
            'rombel_id' => $rombel->id,
            'academic_year_id' => $year->id,
            'semester_id' => $semester->id,
            'status' => 'ACTIVE',
        ]);
        $this->assertSame('COMPLETED', $result['batch']->status);
    }

    public function test_duplicate_rows_in_workbook_are_rejected_and_existing_identity_is_skipped(): void
    {
        Role::create(['name' => 'GURU', 'label' => 'Guru']);
        $existing = User::create(['name' => 'Guru Lama', 'email' => 'guru.lama@madrasah.test', 'password' => Hash::make('StrongPassword123!')]);
        TeacherProfile::create(['user_id' => $existing->id, 'nip' => '198001012005011001', 'full_name' => 'Guru Lama']);

        $file = $this->guruWorkbook([
            ['PNS', '198001012005011001', '', '', 'Guru Existing', 'L', '', ''],
            ['PNS', '001234567890123456', '', '', 'Guru Duplikat 1', 'L', '', ''],
            ['PNS', '001234567890123456', '', '', 'Guru Duplikat 2', 'L', '', ''],
        ]);
        $batch = app(ImportWorkbookService::class)->preview($file, 'GURU', $this->tenant->id, $this->admin->id);
        $rows = $batch->rows->sortBy('row_number')->values();
        $this->assertSame('SKIPPED', $rows[0]->status);
        $this->assertSame('INVALID', $rows[1]->status);
        $this->assertSame('INVALID', $rows[2]->status);

        $result = app(ImportWorkbookService::class)->confirm($batch->id, $this->tenant->id, $this->admin->id);
        $this->assertSame('Guru Lama', User::query()->whereKey($existing->id)->value('name'));
        $this->assertSame('Guru Lama', TeacherProfile::query()->where('user_id', $existing->id)->value('full_name'));
        $this->assertSame(1, TeacherProfile::query()->where('tenant_id', $this->tenant->id)->where('nip', '198001012005011001')->count());
        $this->assertSame(1, $result['batch']->skipped_rows);
        $this->assertSame(2, $result['batch']->failed_rows);
    }

    public function test_unknown_rombel_and_cross_tenant_reference_are_invalid(): void
    {
        Role::create(['name' => 'SISWA', 'label' => 'Siswa']);
        $this->academicSetup();
        $tenantB = Tenant::create(['name' => 'Madrasah Import B', 'code' => 'IMPB']);
        $this->setTenantContext($tenantB->id);
        [$foreignYear, $foreignRombel] = $this->academicSetup();
        Rombel::create(['academic_year_id' => $foreignYear->id, 'grade_level_id' => $foreignRombel->grade_level_id, 'name' => '2B', 'status' => 'ACTIVE']);
        $this->setTenantContext($this->tenant->id);

        $file = $this->studentWorkbook([
            ['000001', '0098765432', 'Siswa Rombel Salah', 'L', '', '', '', '', '', '2B — 2026/2027', '2026/2027'],
        ]);
        $batch = app(ImportWorkbookService::class)->preview($file, 'SISWA', $this->tenant->id, $this->admin->id);
        $this->assertSame('INVALID', $batch->rows->first()->status);
        $this->assertStringContainsString('Rombel', $batch->rows->first()->reason);
    }

    public function test_reupload_is_idempotent_and_corrupt_workbook_is_rejected(): void
    {
        Role::create(['name' => 'GURU', 'label' => 'Guru']);
        $file = $this->guruWorkbook([['PNS', '001234567890123456', '', '', 'Guru Idempotent', 'L', '', '']]);
        $service = app(ImportWorkbookService::class);
        $first = $service->preview($file, 'GURU', $this->tenant->id, $this->admin->id);
        $second = $service->preview($file, 'GURU', $this->tenant->id, $this->admin->id);
        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('import_batches', 1);

        $corruptPath = tempnam(sys_get_temp_dir(), 'cbt-corrupt-');
        file_put_contents($corruptPath, 'not an xlsx archive');
        $this->temporaryFiles[] = $corruptPath;
        $corrupt = new UploadedFile($corruptPath, 'corrupt.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
        $rejected = false;
        try {
            $service->preview($corrupt, 'GURU', $this->tenant->id, $this->admin->id);
        } catch (\Throwable $exception) {
            $rejected = true;
        }
        $this->assertTrue($rejected, 'Workbook rusak seharusnya ditolak.');
    }

    public function test_admin_can_discard_preview_without_importing_and_upload_again(): void
    {
        Role::create(['name' => 'GURU', 'label' => 'Guru']);
        $file = $this->guruWorkbook([
            ['PNS', '001234567890123456', '', '', 'Guru Batal', 'L', '', ''],
        ]);
        $service = app(ImportWorkbookService::class);
        $first = $service->preview($file, 'GURU', $this->tenant->id, $this->admin->id);

        $service->discard($first->id, $this->tenant->id, $this->admin->id);

        $this->assertDatabaseMissing('import_batches', ['id' => $first->id, 'tenant_id' => $this->tenant->id]);
        $this->assertDatabaseHas('audit_logs', ['tenant_id' => $this->tenant->id, 'action' => 'IMPORT_PREVIEW_DISCARDED']);
        $this->assertSame(0, TeacherProfile::query()->where('tenant_id', $this->tenant->id)->count());

        $second = $service->preview($file, 'GURU', $this->tenant->id, $this->admin->id);
        $this->assertNotSame($first->id, $second->id);
        $this->assertSame('PREVIEW', $second->status);
    }

    public function test_missing_role_causes_row_failure_without_partial_account_and_audits_batch(): void
    {
        $file = $this->guruWorkbook([['PNS', '001234567890123456', '', '', 'Guru Tanpa Role', 'L', '', '']]);
        $service = app(ImportWorkbookService::class);
        $batch = $service->preview($file, 'GURU', $this->tenant->id, $this->admin->id);
        $result = $service->confirm($batch->id, $this->tenant->id, $this->admin->id);

        $this->assertSame('PARTIAL', $result['batch']->status);
        $this->assertSame(1, $result['batch']->failed_rows);
        $this->assertSame(0, TeacherProfile::query()->where('tenant_id', $this->tenant->id)->count());
        $this->assertDatabaseHas('audit_logs', ['tenant_id' => $this->tenant->id, 'action' => 'IMPORT_BATCH_CONFIRMED']);
    }

    private function academicSetup(bool $withSubject = false): array
    {
        $curriculum = \App\Models\Curriculum::create(['name' => 'Kurikulum Merdeka', 'code' => 'KM', 'status' => 'ACTIVE', 'is_active' => true]);
        $phase = Phase::create(['curriculum_id' => $curriculum->id, 'name' => 'Fase A', 'code' => 'A', 'status' => 'ACTIVE']);
        $grade = GradeLevel::create(['phase_id' => $phase->id, 'name' => 'Kelas 1', 'code' => '1', 'level' => 1, 'status' => 'ACTIVE']);
        $year = AcademicYear::create(['name' => '2026/2027', 'is_active' => true]);
        $semester = Semester::create(['academic_year_id' => $year->id, 'term' => 'GANJIL', 'is_active' => true]);
        $rombel = Rombel::create(['academic_year_id' => $year->id, 'grade_level_id' => $grade->id, 'name' => '1A', 'status' => 'ACTIVE']);
        $subject = $withSubject ? Subject::create(['code' => 'MTK', 'name' => 'Matematika', 'status' => 'ACTIVE']) : null;
        return $withSubject ? [$year, $rombel, $semester, $subject] : [$year, $rombel];
    }

    private function numericCellWorkbook(UploadedFile $file, string $sheetName, string $cell, int|float $value): UploadedFile
    {
        $book = IOFactory::load($file->getRealPath());
        $book->getSheetByName($sheetName)->getCell($cell)->setValueExplicit($value, DataType::TYPE_NUMERIC);
        $path = tempnam(sys_get_temp_dir(), 'cbt-import-numeric-').'.xlsx';
        (new Xlsx($book))->save($path);
        $this->temporaryFiles[] = $path;
        return new UploadedFile($path, 'import-numeric.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    private function guruWorkbook(array $dataRows, array $assignmentRows = []): UploadedFile
    {
        $headers = ['Kategori Kepegawaian*', 'NIP* / kosongkan jika honorer', 'NIK* / kosongkan jika PNS', 'NUPTK (opsional)', 'Nama Lengkap*', 'Jenis Kelamin (L/P)', 'Email (opsional)', 'No. HP (opsional)'];
        $assignmentHeaders = ['Identitas Guru (NIP/NIK)*', 'Mata Pelajaran*', 'Rombel*', 'Tahun Pelajaran*', 'Semester*', 'Tanggal Mulai (YYYY-MM-DD)', 'Tanggal Selesai (YYYY-MM-DD)', 'Status (ACTIVE/INACTIVE)'];
        $book = new Spreadsheet();
        $guide = $book->getActiveSheet();
        $guide->setTitle('Petunjuk');
        $guide->setCellValue('B2', ImportTemplateVersion::VERSION);
        $guide->setCellValue('B3', 'GURU');
        $data = $book->createSheet();
        $data->setTitle('Data Guru');
        $this->writeRows($data, array_merge([$headers], $dataRows));
        $assignments = $book->createSheet();
        $assignments->setTitle('Penugasan Guru');
        $this->writeRows($assignments, array_merge([$assignmentHeaders], $assignmentRows));
        return $this->saveWorkbook($book);
    }

    private function studentWorkbook(array $dataRows): UploadedFile
    {
        $headers = ['NIS (opsional)', 'NISN*', 'Nama Lengkap*', 'Jenis Kelamin (L/P)', 'Tempat Lahir', 'Tanggal Lahir (YYYY-MM-DD)', 'Nama Orang Tua/Wali', 'No. HP Orang Tua/Wali', 'Alamat', 'Rombel*', 'Tahun Pelajaran*'];
        $book = new Spreadsheet();
        $guide = $book->getActiveSheet();
        $guide->setTitle('Petunjuk');
        $guide->setCellValue('B2', ImportTemplateVersion::VERSION);
        $guide->setCellValue('B3', 'SISWA');
        $data = $book->createSheet();
        $data->setTitle('Data Siswa');
        $this->writeRows($data, array_merge([$headers], $dataRows));
        return $this->saveWorkbook($book);
    }

    private function writeRows($sheet, array $rows): void
    {
        foreach ($rows as $rowIndex => $row) {
            foreach ($row as $columnIndex => $value) {
                $coordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex + 1).($rowIndex + 1);
                $sheet->setCellValueExplicit($coordinate, (string) $value, DataType::TYPE_STRING);
            }
        }
    }

    private function saveWorkbook(Spreadsheet $book): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'cbt-import-').'.xlsx';
        (new Xlsx($book))->save($path);
        $this->temporaryFiles[] = $path;
        return new UploadedFile($path, 'import-fixture.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }
    public function test_numeric_phone_and_date_cells_are_rejected_instead_of_silently_coerced(): void
    {
        Role::create(['name' => 'GURU', 'label' => 'Guru']);
        $guruFile = $this->numericCellWorkbook(
            $this->guruWorkbook([
                ['PNS', '001234567890123456', '', '', 'Guru Nomor', 'L', '', '081234567890'],
            ]),
            'Data Guru',
            'H2',
            81234567890
        );
        $guruBatch = app(ImportWorkbookService::class)->preview($guruFile, 'GURU', $this->tenant->id, $this->admin->id);
        $this->assertSame(0, $guruBatch->valid_rows);
        $this->assertSame(1, $guruBatch->failed_rows);
        $this->assertStringContainsString('Nomor HP harus disimpan sebagai teks', $guruBatch->rows->first()->reason);

        Role::create(['name' => 'SISWA', 'label' => 'Siswa']);
        [, $rombel] = $this->academicSetup();
        $studentFile = $this->numericCellWorkbook(
            $this->studentWorkbook([
                ['000123', '0012345678', 'Siswa Tanggal', 'P', 'Jakarta', '2015-03-04', 'Orang Tua', '081234567890', 'Alamat', '1A — 2026/2027', '2026/2027'],
            ]),
            'Data Siswa',
            'F2',
            42000
        );
        $studentBatch = app(ImportWorkbookService::class)->preview($studentFile, 'SISWA', $this->tenant->id, $this->admin->id);
        $this->assertSame(0, $studentBatch->valid_rows);
        $this->assertSame(1, $studentBatch->failed_rows);
        $this->assertStringContainsString('Tanggal lahir harus berformat YYYY-MM-DD', $studentBatch->rows->first()->reason);
    }

    public function test_import_rejects_login_identifier_that_collides_with_another_username(): void
    {
        Role::create(['name' => 'GURU', 'label' => 'Guru']);
        User::create([
            'name' => 'Akun Username Bentrok Guru',
            'username' => '001234567890123456',
            'password' => Hash::make('StrongPassword123!'),
        ]);
        $guruBatch = app(ImportWorkbookService::class)->preview(
            $this->guruWorkbook([
                ['PNS', '001234567890123456', '', '', 'Guru Bentrok', 'L', '', ''],
            ]),
            'GURU',
            $this->tenant->id,
            $this->admin->id
        );
        $this->assertSame(0, $guruBatch->valid_rows);
        $this->assertSame(1, $guruBatch->failed_rows);
        $this->assertStringContainsString('username akun lain', $guruBatch->rows->first()->reason);

        Role::create(['name' => 'SISWA', 'label' => 'Siswa']);
        User::create([
            'name' => 'Akun Username Bentrok Siswa',
            'username' => '0012345678',
            'password' => Hash::make('StrongPassword123!'),
        ]);
        $this->academicSetup();
        $studentBatch = app(ImportWorkbookService::class)->preview(
            $this->studentWorkbook([
                ['000123', '0012345678', 'Siswa Bentrok', 'P', 'Jakarta', '2015-03-04', 'Orang Tua', '081234567890', 'Alamat', '1A — 2026/2027', '2026/2027'],
            ]),
            'SISWA',
            $this->tenant->id,
            $this->admin->id
        );
        $this->assertSame(0, $studentBatch->valid_rows);
        $this->assertSame(1, $studentBatch->failed_rows);
        $this->assertStringContainsString('username akun lain', $studentBatch->rows->first()->reason);
    }

    public function test_encrypted_payload_and_activation_token_hash_are_hidden_from_serialization(): void
    {
        $this->assertContains('encrypted_payload', (new ImportBatchRow())->getHidden());
        $this->assertContains('token_hash', (new ImportActivationToken())->getHidden());
    }

    public function test_livewire_admin_can_upload_preview_and_confirm_guru_workbook(): void
    {
        Role::create(['name' => 'GURU', 'label' => 'Guru']);
        $sourceFile = $this->guruWorkbook([
            ['PNS', '001234567890123456', '', '', 'Guru Livewire', 'L', '', ''],
        ]);
        $file = UploadedFile::fake()->createWithContent('import-fixture.xlsx', file_get_contents($sourceFile->getRealPath()));

        Livewire::test(ImportManagement::class)
            ->set('type', 'GURU')
            ->set('upload', $file)
            ->call('previewUpload')
            ->assertHasNoErrors()
            ->assertSee('Preview berhasil')
            ->assertSee('Guru Livewire')
            ->call('confirmImport')
            ->assertSee('Tautan aktivasi satu kali')
            ->assertSee('Guru Livewire')
            ->assertSee('Riwayat Import Madrasah Ini')
            ->assertSee('import-fixture.xlsx')
            ->assertSee('COMPLETED');

        $this->assertDatabaseHas('teacher_profiles', [
            'tenant_id' => $this->tenant->id,
            'nip' => '001234567890123456',
        ]);
        $this->assertDatabaseHas('import_batches', [
            'tenant_id' => $this->tenant->id,
            'status' => 'COMPLETED',
        ]);
    }

    public function test_two_previews_with_same_identity_do_not_create_duplicate_accounts(): void
    {
        Role::create(['name' => 'GURU', 'label' => 'Guru']);
        $service = app(ImportWorkbookService::class);
        $firstFile = $this->guruWorkbook([
            ['PNS', '001234567890123456', '', '', 'Guru Batch Pertama', 'L', '', ''],
        ]);
        $secondFile = $this->guruWorkbook([
            ['PNS', '001234567890123456', '', '', 'Guru Batch Kedua', 'L', '', ''],
        ]);

        $firstPreview = $service->preview($firstFile, 'GURU', $this->tenant->id, $this->admin->id);
        $secondPreview = $service->preview($secondFile, 'GURU', $this->tenant->id, $this->admin->id);
        $this->assertSame(1, $firstPreview->valid_rows);
        $this->assertSame(1, $secondPreview->valid_rows);

        $service->confirm($firstPreview->id, $this->tenant->id, $this->admin->id);
        $secondResult = $service->confirm($secondPreview->id, $this->tenant->id, $this->admin->id);

        $this->assertSame(1, $secondResult['batch']->skipped_rows);
        $this->assertSame(0, $secondResult['batch']->valid_rows);
        $this->assertSame(1, TeacherProfile::query()->where('tenant_id', $this->tenant->id)->where('nip', '001234567890123456')->count());
        $this->assertSame(1, User::query()->where('tenant_id', $this->tenant->id)->where('name', 'Guru Batch Pertama')->count());
        $this->assertSame(0, User::query()->where('tenant_id', $this->tenant->id)->where('name', 'Guru Batch Kedua')->count());
    }

}
