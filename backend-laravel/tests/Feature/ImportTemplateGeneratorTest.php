<?php

namespace Tests\Feature;

use App\Exports\Import\ImportTemplateExport;
use App\Exports\Import\ImportTemplateVersion;
use App\Models\Tenant;
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
        $this->assertSame('hidden', $book->getSheetByName('Referensi')->getSheetState());
    }
}
