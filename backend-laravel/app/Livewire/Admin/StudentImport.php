<?php

namespace App\Livewire\Admin;

use App\Models\Rombel;
use App\Services\StudentImportService;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class StudentImport extends Component
{
    use WithFileUploads;

    public $file;
    public string $selectedRombelId = '';
    public array $previewRows = [];
    public array $summary = [];
    public ?string $previewHash = null;

    public function mount(): void
    {
        $this->authorizeImport();
    }

    private function authorizeImport(): void
    {
        $user = auth()->user();
        abort_unless(
            $user && $user->tenant_id
            && $user->hasExactRole('ADMIN_MADRASAH')
            && $user->hasPermission('user.manage'),
            403,
            'Akses ditolak. Diperlukan Admin Madrasah dan permission user.manage.'
        );
    }

    private function validationRules(): array
    {
        $tenantId = auth()->user()->tenant_id;

        return [
            'file' => ['required', 'file', 'mimes:xlsx,csv,txt', 'max:10240'],
            'selectedRombelId' => [
                'required',
                'uuid',
                Rule::exists('rombels', 'id')->where(fn ($query) => $query
                    ->where('tenant_id', $tenantId)
                    ->where('status', 'ACTIVE')),
            ],
        ];
    }

    public function preview(): void
    {
        $this->authorizeImport();
        $this->validate($this->validationRules());

        $tenantId = auth()->user()->tenant_id;
        $path = $this->file->getRealPath();
        $extension = strtolower($this->file->getClientOriginalExtension());

        $result = app(StudentImportService::class)->preview(
            $path,
            $extension,
            $tenantId,
            $this->selectedRombelId
        );

        $this->previewRows = $result['rows'];
        $this->summary = [
            'total' => $result['total'],
            'valid' => $result['valid'],
            'invalid' => $result['invalid'],
            'rombel_name' => $result['rombel_name'],
        ];
        $this->previewHash = $result['file_sha256'];
    }

    public function import()
    {
        $this->authorizeImport();
        $this->validate($this->validationRules());

        $tenantId = auth()->user()->tenant_id;
        $path = $this->file->getRealPath();
        $extension = strtolower($this->file->getClientOriginalExtension());
        $result = app(StudentImportService::class)->preview(
            $path,
            $extension,
            $tenantId,
            $this->selectedRombelId
        );

        $this->previewRows = $result['rows'];
        $this->summary = [
            'total' => $result['total'],
            'valid' => $result['valid'],
            'invalid' => $result['invalid'],
            'rombel_name' => $result['rombel_name'],
        ];
        $this->previewHash = $result['file_sha256'];

        if ($result['invalid'] > 0 || $result['total'] < 1) {
            throw ValidationException::withMessages([
                'file' => 'Import belum dapat diproses. Perbaiki seluruh error pada preview.',
            ]);
        }

        $imported = app(StudentImportService::class)->import(
            $result,
            $tenantId,
            auth()->user()->id,
            $this->selectedRombelId
        );

        $this->reset(['file', 'selectedRombelId', 'previewRows', 'summary', 'previewHash']);
        session()->flash('import_credentials', $imported['credentials']);
        session()->flash('import_credentials_label', 'Siswa');
        session()->flash('import_batch_id', $imported['batch_id']);
        session()->flash('message', $imported['imported'] . ' akun Siswa berhasil diimpor. Kredensial awal ditampilkan satu kali pada halaman daftar pengguna.');
        return redirect('/admin/users');
    }

    public function downloadXlsxTemplate()
    {
        $this->authorizeImport();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Siswa');

        $headers = [
            'nama', 'nisn', 'jenis_kelamin', 'tempat_lahir',
            'tanggal_lahir', 'nama_orang_tua', 'telepon', 'alamat',
        ];
        foreach ($headers as $index => $header) {
            $column = Coordinate::stringFromColumnIndex($index + 1);
            $sheet->setCellValue($column . '1', $header);
        }

        $sheet->setCellValue('A2', 'Contoh Nama');
        $sheet->setCellValueExplicit('B2', '0001234567', DataType::TYPE_STRING);
        $sheet->setCellValue('C2', 'L');
        $sheet->setCellValue('D2', 'Jakarta');
        $sheet->setCellValueExplicit('E2', '2015-01-31', DataType::TYPE_STRING);
        $sheet->setCellValue('F2', 'Nama Orang Tua');
        $sheet->setCellValueExplicit('G2', '081234567890', DataType::TYPE_STRING);
        $sheet->setCellValue('H2', 'Alamat Siswa');

        $sheet->getStyle('A1:H1')->getFont()->setBold(true);
        $sheet->getStyle('A1:H1')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('B2:B5001')->getNumberFormat()->setFormatCode('@');
        $sheet->getStyle('E2:E5001')->getNumberFormat()->setFormatCode('@');
        $sheet->getStyle('G2:G5001')->getNumberFormat()->setFormatCode('@');
        foreach (['A' => 28, 'B' => 16, 'C' => 18, 'D' => 20, 'E' => 18, 'F' => 28, 'G' => 20, 'H' => 42] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:H2');

        $instructions = $spreadsheet->createSheet();
        $instructions->setTitle('Petunjuk');
        $rows = [
            ['PETUNJUK TEMPLATE IMPORT SISWA'],
            ['Kolom', 'Ketentuan'],
            ['nama', 'Wajib diisi, maksimal 255 karakter.'],
            ['nisn', 'Wajib tepat 10 digit dan harus berupa teks. Jangan ubah menjadi angka.'],
            ['jenis_kelamin', 'Opsional: L atau P.'],
            ['tempat_lahir', 'Opsional, maksimal 100 karakter.'],
            ['tanggal_lahir', 'Opsional dengan format teks YYYY-MM-DD.'],
            ['nama_orang_tua', 'Opsional, maksimal 255 karakter.'],
            ['telepon', 'Opsional, maksimal 40 karakter; simpan sebagai teks.'],
            ['alamat', 'Opsional, maksimal 2.000 karakter.'],
            ['Catatan', 'Hapus atau ganti baris Contoh Nama pada sheet Data Siswa sebelum mengunggah.'],
            ['Catatan', 'Pilih Rombel tujuan, unggah file, lalu periksa Preview sebelum konfirmasi.'],
            ['Keamanan', 'Import hanya diproses jika seluruh baris valid. NISN duplikat ditolak.'],
        ];
        foreach ($rows as $rowIndex => $row) {
            foreach ($row as $columnIndex => $value) {
                $cell = Coordinate::stringFromColumnIndex($columnIndex + 1) . ($rowIndex + 1);
                $instructions->setCellValue($cell, $value);
            }
        }
        $instructions->getStyle('A1:B2')->getFont()->setBold(true);
        $instructions->getColumnDimension('A')->setWidth(24);
        $instructions->getColumnDimension('B')->setWidth(100);
        $instructions->getStyle('A1:B13')->getAlignment()->setWrapText(true);
        $instructions->freezePane('A3');

        return response()->streamDownload(
            function () use ($spreadsheet): void {
                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');
                $spreadsheet->disconnectWorksheets();
            },
            'template-import-siswa.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    public function downloadCsvTemplate()
    {
        $this->authorizeImport();

        $csv = "nama,nisn,jenis_kelamin,tempat_lahir,tanggal_lahir,nama_orang_tua,telepon,alamat\n";
        $csv .= "Contoh Nama,0001234567,L,Jakarta,2015-01-31,Nama Orang Tua,081234567890,Alamat Siswa\n";

        return response()->streamDownload(
            fn () => print($csv),
            'template-import-siswa.csv',
            ['Content-Type' => 'text/csv; charset=UTF-8']
        );
    }

    public function render()
    {
        $this->authorizeImport();
        $tenantId = auth()->user()->tenant_id;
        $rombels = Rombel::query()
            ->with(['academicYear', 'gradeLevel'])
            ->where('tenant_id', $tenantId)
            ->where('status', 'ACTIVE')
            ->orderBy('name')
            ->get();

        return view('livewire.admin.student-import', compact('rombels'))
            ->layout('layouts.app');
    }
}
