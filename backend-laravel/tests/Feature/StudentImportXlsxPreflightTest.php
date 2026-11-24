<?php

namespace Tests\Feature;

use App\Services\Import\XlsxPackagePreflight;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;
use ZipArchive;

class StudentImportXlsxPreflightTest extends TestCase
{
    public function test_valid_single_sheet_xlsx_passes_preflight(): void
    {
        $path = $this->makeWorkbook();

        try {
            $this->assertSame('Data Siswa', app(XlsxPackagePreflight::class)->inspect($path));
        } finally {
            @unlink($path);
        }
    }

    public function test_extreme_worksheet_dimension_is_rejected_before_spreadsheet_load(): void
    {
        $path = $this->makeWorkbook();
        $this->mutateEntry($path, 'xl/worksheets/sheet1.xml', function (string $xml): string {
            $updated = preg_replace('/<dimension\b[^>]*\bref="[^"]+"[^>]*\/>/i', '<dimension ref="A1:XFD1048576"/>', $xml, 1);
            $this->assertIsString($updated);
            $this->assertNotSame($xml, $updated);
            return $updated;
        });

        try {
            $this->expectException(ValidationException::class);
            app(XlsxPackagePreflight::class)->inspect($path);
        } finally {
            @unlink($path);
        }
    }

    public function test_formula_cells_are_rejected(): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Siswa');
        $sheet->fromArray([['nama', 'nisn'], ['Siswa', '0001234567']], null, 'A1');
        $sheet->setCellValue('C2', '=1+1');
        $path = $this->writeWorkbook($spreadsheet);

        try {
            $this->expectException(ValidationException::class);
            app(XlsxPackagePreflight::class)->inspect($path);
        } finally {
            @unlink($path);
        }
    }

    public function test_external_relationship_is_rejected(): void
    {
        $path = $this->makeWorkbook();
        $this->mutateEntry($path, 'xl/_rels/workbook.xml.rels', function (string $xml): string {
            return str_replace(
                '</Relationships>',
                '<Relationship Id="rIdExternal" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/hyperlink" Target="https://example.invalid/" TargetMode="External"/></Relationships>',
                $xml
            );
        });

        try {
            $this->expectException(ValidationException::class);
            app(XlsxPackagePreflight::class)->inspect($path);
        } finally {
            @unlink($path);
        }
    }

    public function test_doctype_and_entity_declarations_are_rejected(): void
    {
        $path = $this->makeWorkbook();
        $this->mutateEntry($path, 'xl/worksheets/sheet1.xml', fn (string $xml): string => '<!DOCTYPE x [<!ENTITY e "expanded">]>' . $xml);

        try {
            $this->expectException(ValidationException::class);
            app(XlsxPackagePreflight::class)->inspect($path);
        } finally {
            @unlink($path);
        }
    }

    public function test_path_traversal_entry_is_rejected(): void
    {
        $path = $this->makeWorkbook();
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path) === true);
        $this->assertTrue($zip->addFromString('../outside.xml', '<x/>'));
        $zip->close();

        try {
            $this->expectException(ValidationException::class);
            app(XlsxPackagePreflight::class)->inspect($path);
        } finally {
            @unlink($path);
        }
    }

    public function test_macro_and_ole_package_parts_are_rejected(): void
    {
        $path = $this->makeWorkbook();
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path) === true);
        $this->assertTrue($zip->addFromString('xl/vbaProject.bin', 'not-a-real-macro'));
        $zip->close();

        try {
            $this->expectException(ValidationException::class);
            app(XlsxPackagePreflight::class)->inspect($path);
        } finally {
            @unlink($path);
        }
    }

    public function test_compression_ratio_is_bounded(): void
    {
        $path = $this->makeWorkbook();
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path) === true);
        $this->assertTrue($zip->addFromString('xl/repetitive.xml', str_repeat('A', 1024 * 1024)));
        $zip->setCompressionName('xl/repetitive.xml', ZipArchive::CM_DEFLATE, 9);
        $zip->close();

        try {
            $this->expectException(ValidationException::class);
            app(XlsxPackagePreflight::class)->inspect($path);
        } finally {
            @unlink($path);
        }
    }

    public function test_zip_entry_count_is_bounded(): void
    {
        $path = $this->makeWorkbook();
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path) === true);
        for ($i = 0; $i < 205; $i++) {
            $this->assertTrue($zip->addFromString('extras/item-' . $i . '.txt', 'x'));
        }
        $zip->close();

        try {
            $this->expectException(ValidationException::class);
            app(XlsxPackagePreflight::class)->inspect($path);
        } finally {
            @unlink($path);
        }
    }

    public function test_malformed_zip_is_rejected(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'student-xlsx-invalid-');
        file_put_contents($path, 'this is not a zip archive');

        try {
            $this->expectException(ValidationException::class);
            app(XlsxPackagePreflight::class)->inspect($path);
        } finally {
            @unlink($path);
        }
    }

    public function test_shared_string_value_over_4_kib_is_rejected(): void
    {
        $path = $this->makeWorkbook();
        $text = bin2hex(random_bytes(2049));
        $xml = '<?xml version="1.0" encoding="UTF-8"?><sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><si><t>' . $text . '</t></si></sst>';
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path) === true);
        $this->assertTrue($zip->addFromString('xl/sharedStrings.xml', $xml));
        $zip->close();

        try {
            $this->expectException(ValidationException::class);
            app(XlsxPackagePreflight::class)->inspect($path);
        } finally {
            @unlink($path);
        }
    }

    public function test_styles_entry_size_is_bounded(): void
    {
        $path = $this->makeWorkbook();
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path) === true);
        $this->assertTrue($zip->addFromString('xl/styles.xml', random_bytes(2 * 1024 * 1024 + 1)));
        $zip->close();

        try {
            $this->expectException(ValidationException::class);
            app(XlsxPackagePreflight::class)->inspect($path);
        } finally {
            @unlink($path);
        }
    }

    public function test_styles_definition_count_is_bounded(): void
    {
        $path = $this->makeWorkbook();
        $styles = '<?xml version="1.0" encoding="UTF-8"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><cellXfs count="10001">';
        for ($i = 0; $i < 10001; $i++) {
            $styles .= '<xf numFmtId="' . $i . '"/>';
        }
        $styles .= '</cellXfs></styleSheet>';

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path) === true);
        $this->assertTrue($zip->addFromString('xl/styles.xml', $styles));
        $zip->close();

        try {
            $this->expectException(ValidationException::class);
            app(XlsxPackagePreflight::class)->inspect($path);
        } finally {
            @unlink($path);
        }
    }

    public function test_total_expanded_package_size_is_bounded(): void
    {
        $path = $this->makeWorkbook();
        $chunk = random_bytes(30 * 1024);
        $expandedEntry = str_repeat($chunk, 55);
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path) === true);
        for ($i = 0; $i < 16; $i++) {
            $name = 'extras/expanded-' . $i . '.bin';
            $this->assertTrue($zip->addFromString($name, $expandedEntry));
            $zip->setCompressionName($name, ZipArchive::CM_DEFLATE, 9);
        }
        $zip->close();

        try {
            $this->expectException(ValidationException::class);
            app(XlsxPackagePreflight::class)->inspect($path);
        } finally {
            @unlink($path);
        }
    }

    private function makeWorkbook(): string
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->setTitle('Data Siswa');
        $spreadsheet->getActiveSheet()->fromArray([
            ['nama', 'nisn'],
            ['Siswa Uji', '0001234567'],
        ], null, 'A1');

        return $this->writeWorkbook($spreadsheet);
    }

    private function writeWorkbook(Spreadsheet $spreadsheet): string
    {
        $path = tempnam(sys_get_temp_dir(), 'student-xlsx-');
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        return $path;
    }

    private function mutateEntry(string $path, string $entryName, callable $mutator): void
    {
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path) === true);
        $content = $zip->getFromName($entryName);
        $this->assertIsString($content);
        $this->assertTrue($zip->addFromString($entryName, $mutator($content)));
        $zip->close();
    }
}
