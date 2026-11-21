<?php

namespace Tests\Feature;

use App\Exports\Import\ImportTemplateExport;
use App\Exports\Import\ImportTemplateVersion;
use App\Models\Tenant;
use App\Models\Curriculum;
use App\Models\Phase;
use App\Models\GradeLevel;
use App\Models\AcademicYear;
use App\Models\Semester;
use App\Models\Rombel;
use App\Models\Subject;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use Tests\TestCase;

class ImportTemplateGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_guru_template_contains_versioned_instruction_data_assignment_and_text_identifiers(): void
    {
        $tenant = Tenant::create(['name' => 'Madrasah Template Guru', 'code' => 'TMPG']);
        $this->setTenantContext($tenant->id);
        Storage::fake('local');

        $this->assertTrue(Excel::store(new ImportTemplateExport($tenant->id, 'GURU'), 'template-guru.xlsx', 'local'));
        $book = IOFactory::load(Storage::disk('local')->path('template-guru.xlsx'));

        $this->assertSame(['Petunjuk', 'Data Guru', 'Penugasan Guru', 'Referensi'], $book->getSheetNames());
        $this->assertSame(ImportTemplateVersion::VERSION, (string) $book->getSheetByName('Petunjuk')->getCell('B2')->getValue());
        $this->assertSame('GURU', (string) $book->getSheetByName('Petunjuk')->getCell('B3')->getValue());
        $this->assertSame(DataType::TYPE_STRING, $book->getSheetByName('Data Guru')->getCell('B2')->getDataType());
        $this->assertSame('@', $book->getSheetByName('Data Guru')->getStyle('B2')->getNumberFormat()->getFormatCode());
        $this->assertSame('@', $book->getSheetByName('Data Guru')->getStyle('D2')->getNumberFormat()->getFormatCode());
        $this->assertSame('@', $book->getSheetByName('Data Guru')->getStyle('H2')->getNumberFormat()->getFormatCode());
        $this->assertSame('@', $book->getSheetByName('Penugasan Guru')->getStyle('F2')->getNumberFormat()->getFormatCode());
        $this->assertSame('@', $book->getSheetByName('Penugasan Guru')->getStyle('G2')->getNumberFormat()->getFormatCode());
        $this->assertSame('@', $book->getSheetByName('Penugasan Guru')->getStyle('A2')->getNumberFormat()->getFormatCode());
        $this->assertSame('hidden', $book->getSheetByName('Referensi')->getSheetState());
    }

    public function test_student_template_contains_versioned_instruction_and_text_identifiers(): void
    {
        $tenant = Tenant::create(['name' => 'Madrasah Template Siswa', 'code' => 'TMPS']);
        $this->setTenantContext($tenant->id);
        Storage::fake('local');

        $this->assertTrue(Excel::store(new ImportTemplateExport($tenant->id, 'SISWA'), 'template-siswa.xlsx', 'local'));
        $book = IOFactory::load(Storage::disk('local')->path('template-siswa.xlsx'));

        $this->assertSame(['Petunjuk', 'Data Siswa', 'Referensi'], $book->getSheetNames());
        $this->assertSame(ImportTemplateVersion::VERSION, (string) $book->getSheetByName('Petunjuk')->getCell('B2')->getValue());
        $this->assertSame('SISWA', (string) $book->getSheetByName('Petunjuk')->getCell('B3')->getValue());
        $this->assertSame('@', $book->getSheetByName('Data Siswa')->getStyle('A2')->getNumberFormat()->getFormatCode());
        $this->assertSame('@', $book->getSheetByName('Data Siswa')->getStyle('B2')->getNumberFormat()->getFormatCode());
        $this->assertSame('@', $book->getSheetByName('Data Siswa')->getStyle('F2')->getNumberFormat()->getFormatCode());
        $this->assertSame('@', $book->getSheetByName('Data Siswa')->getStyle('H2')->getNumberFormat()->getFormatCode());
        $this->assertSame('hidden', $book->getSheetByName('Referensi')->getSheetState());
    }

    public function test_tenant_master_references_are_written_to_hidden_sheet_and_used_by_dropdowns(): void
    {
        $tenant = Tenant::create(['name' => 'Madrasah Referensi', 'code' => 'REFX']);
        $this->setTenantContext($tenant->id);
        $curriculum = Curriculum::create(['name' => 'Kurikulum Merdeka', 'code' => 'KM', 'status' => 'ACTIVE', 'is_active' => true]);
        $phase = Phase::create(['curriculum_id' => $curriculum->id, 'name' => 'Fase A', 'code' => 'A', 'status' => 'ACTIVE']);
        $grade = GradeLevel::create(['phase_id' => $phase->id, 'name' => 'Kelas 1', 'code' => '1', 'level' => 1, 'status' => 'ACTIVE']);
        $year = AcademicYear::create(['name' => '2026/2027', 'is_active' => true]);
        Semester::create(['academic_year_id' => $year->id, 'term' => 'GANJIL', 'is_active' => true]);
        Rombel::create(['academic_year_id' => $year->id, 'grade_level_id' => $grade->id, 'name' => '1A', 'status' => 'ACTIVE']);
        Subject::create(['code' => 'MTK', 'name' => 'Matematika', 'status' => 'ACTIVE']);
        Storage::fake('local');

        Excel::store(new ImportTemplateExport($tenant->id, 'GURU'), 'template-guru-ref.xlsx', 'local');
        Excel::store(new ImportTemplateExport($tenant->id, 'SISWA'), 'template-siswa-ref.xlsx', 'local');
        $guru = IOFactory::load(Storage::disk('local')->path('template-guru-ref.xlsx'));
        $siswa = IOFactory::load(Storage::disk('local')->path('template-siswa-ref.xlsx'));

        $this->assertSame('MTK — Matematika', $guru->getSheetByName('Referensi')->getCell('A2')->getValue());
        $this->assertSame('1A — 2026/2027', $guru->getSheetByName('Referensi')->getCell('B2')->getValue());
        $this->assertSame('2026/2027', $guru->getSheetByName('Referensi')->getCell('C2')->getValue());
        $this->assertSame('GANJIL — 2026/2027', $guru->getSheetByName('Referensi')->getCell('D2')->getValue());
        $this->assertSame('CONTOH - HAPUS BARIS INI', $guru->getSheetByName('Penugasan Guru')->getCell('A2')->getValue());
        $this->assertSame('=SubjectsList', $guru->getSheetByName('Penugasan Guru')->getCell('B2')->getDataValidation()->getFormula1());
        $this->assertSame('=RombelsList', $guru->getSheetByName('Penugasan Guru')->getCell('C2')->getDataValidation()->getFormula1());
        $this->assertSame('=AcademicYearsList', $guru->getSheetByName('Penugasan Guru')->getCell('D2')->getDataValidation()->getFormula1());
        $this->assertSame('=SemestersList', $guru->getSheetByName('Penugasan Guru')->getCell('E2')->getDataValidation()->getFormula1());
        $this->assertSame('=RombelsList', $siswa->getSheetByName('Data Siswa')->getCell('J2')->getDataValidation()->getFormula1());
        $this->assertSame('=AcademicYearsList', $siswa->getSheetByName('Data Siswa')->getCell('K2')->getDataValidation()->getFormula1());
        $this->assertSame('=SubjectsList', $guru->getSheetByName('Penugasan Guru')->getCell('B5001')->getDataValidation()->getFormula1());
        $this->assertSame('=RombelsList', $siswa->getSheetByName('Data Siswa')->getCell('J5001')->getDataValidation()->getFormula1());
    }

    public function test_admin_can_download_real_guru_and_student_xlsx_templates(): void
    {
        $tenant = Tenant::create(['name' => 'Madrasah Download', 'code' => 'DWNX']);
        $this->setTenantContext($tenant->id);
        $role = Role::create(['name' => 'ADMIN_MADRASAH', 'label' => 'Admin Madrasah']);
        $permission = Permission::create(['name' => 'user.manage', 'label' => 'Kelola Pengguna']);
        $role->permissions()->attach($permission->id);
        $admin = User::create(['name' => 'Admin Download', 'email' => 'admin.download@madrasah.test', 'password' => Hash::make('StrongPassword123!')]);
        $admin->roles()->attach($role);
        $this->actingAs($admin);

        $guru = $this->get('/admin/import/template/GURU');
        $guru->assertOk();
        $this->assertStringContainsString('filename="Template Import Guru.xlsx"', (string) $guru->headers->get('content-disposition'));

        $siswa = $this->get('/admin/import/template/SISWA');
        $siswa->assertOk();
        $this->assertStringContainsString('filename="Template Import Siswa.xlsx"', (string) $siswa->headers->get('content-disposition'));
    }

}
