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
            'selectedRombelId' => $this->selectedRombelId === '' ? ['nullable'] : [
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
            $this->selectedRombelId !== '' ? $this->selectedRombelId : null
        );

        $this->previewRows = $result['rows'];
        $this->summary = [
            'total' => $result['total'],
            'valid' => $result['valid'],
            'invalid' => $result['invalid'],
            'rombel_name' => $result['rombel_name'],
            'rombel_will_create' => $result['rombel_will_create'],
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
            $this->selectedRombelId !== '' ? $this->selectedRombelId : null
        );

        $this->previewRows = $result['rows'];
        $this->summary = [
            'total' => $result['total'],
            'valid' => $result['valid'],
            'invalid' => $result['invalid'],
            'rombel_name' => $result['rombel_name'],
            'rombel_will_create' => $result['rombel_will_create'],
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
            $this->selectedRombelId !== '' ? $this->selectedRombelId : null
        );

        $this->reset(['file', 'selectedRombelId', 'previewRows', 'summary', 'previewHash']);
        session()->flash('import_credentials', $imported['credentials']);
        session()->flash('import_credentials_label', 'Siswa');
        session()->flash('import_batch_id', $imported['batch_id']);
        session()->flash('message', $imported['imported'] . ' akun Siswa berhasil diimpor ke Rombel ' . $imported['rombel_name'] . '. Kredensial awal ditampilkan satu kali pada halaman daftar pengguna.');
        return redirect('/admin/users');
    }

    public function downloadXlsxTemplate()
    {
        $this->authorizeImport();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Siswa');

        $headers = [
            'nama', 'nisn', 'rombel', 'tahun_pelajaran', 'kelas',
            'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir',
            'nama_orang_tua', 'telepon', 'alamat',
        ];
        foreach ($headers as $index => $header) {
            $column = Coordinate::stringFromColumnIndex($index + 1);
            $sheet->setCellValue($column . '1', $header);
        }

        $sheet->setCellValue('A2', 'Contoh Nama');
        $sheet->setCellValueExplicit('B2', '0001234567', DataType::TYPE_STRING);
        $sheet->setCellValue('C2', 'Kelas 1-A');
        $sheet->setCellValueExplicit('D2', '2026/2027', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('E2', '1', DataType::TYPE_STRING);
        $sheet->setCellValue('F2', 'L');
        $sheet->setCellValue('G2', 'Jakarta');
        $sheet->setCellValueExplicit('H2', '2015-01-31', DataType::TYPE_STRING);
        $sheet->setCellValue('I2', 'Nama Orang Tua');
        $sheet->setCellValueExplicit('J2', '081234567890', DataType::TYPE_STRING);
        $sheet->setCellValue('K2', 'Alamat Siswa');

        $sheet->getStyle('A1:K1')->getFont()->setBold(true);
        $sheet->getStyle('A1:K1')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('B2:B5001')->getNumberFormat()->setFormatCode('@');
        $sheet->getStyle('D2:E5001')->getNumberFormat()->setFormatCode('@');
        $sheet->getStyle('H2:H5001')->getNumberFormat()->setFormatCode('@');
        $sheet->getStyle('J2:J5001')->getNumberFormat()->setFormatCode('@');
        foreach (['A' => 28, 'B' => 16, 'C' => 22, 'D' => 18, 'E' => 10, 'F' => 18, 'G' => 20, 'H' => 18, 'I' => 28, 'J' => 20, 'K' => 42] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:K2');


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

        $csv = "nama,nisn,rombel,tahun_pelajaran,kelas,jenis_kelamin,tempat_lahir,tanggal_lahir,nama_orang_tua,telepon,alamat\n";
        $csv .= "Contoh Nama,0001234567,Kelas 1-A,2026/2027,1,L,Jakarta,2015-01-31,Nama Orang Tua,081234567890,Alamat Siswa\n";

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
