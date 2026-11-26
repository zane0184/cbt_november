<?php

namespace App\Livewire\Admin;

use App\Services\TeacherImportService;
use Livewire\Component;
use Livewire\WithFileUploads;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class TeacherImport extends Component
{
    use WithFileUploads;

    public $file;
    public array $previewRows = [];
    public array $summary = [];

    public function mount(): void
    {
        $this->authorizeImport();
    }

    private function authorizeImport(): void
    {
        $user = auth()->user();
        abort_unless($user && $user->tenant_id && $user->hasExactRole('ADMIN_MADRASAH')
            && $user->hasPermission('user.manage'), 403,
            'Akses ditolak. Diperlukan Admin Madrasah dan permission user.manage.');
    }

    public function preview(): void
    {
        $this->authorizeImport();
        $this->validate(['file' => ['required', 'file', 'mimes:xlsx,csv,txt', 'max:10240']]);
        $result = app(TeacherImportService::class)->preview(
            $this->file->getRealPath(),
            strtolower($this->file->getClientOriginalExtension()),
            auth()->user()->tenant_id
        );
        $this->previewRows = $result['rows'];
        $this->summary = [
            'total' => $result['total'],
            'valid' => $result['valid'],
            'invalid' => $result['invalid'],
        ];
    }

    public function import()
    {
        $this->authorizeImport();
        $this->validate(['file' => ['required', 'file', 'mimes:xlsx,csv,txt', 'max:10240']]);
        $result = app(TeacherImportService::class)->import(
            $this->file->getRealPath(),
            strtolower($this->file->getClientOriginalExtension()),
            auth()->user()->tenant_id,
            auth()->user()->id
        );
        $this->reset(['file', 'previewRows', 'summary']);
        session()->flash('import_credentials', $result['credentials']);
        session()->flash('import_credentials_label', 'Guru');
        session()->flash('import_batch_id', $result['batch_id']);
        session()->flash('message', $result['imported'].' akun Guru berhasil diimpor. Kredensial awal ditampilkan satu kali pada halaman daftar pengguna.'); 
        return redirect('/admin/users');
    }

    public function downloadXlsxTemplate()
    {
        $this->authorizeImport();
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Guru');
        $headers = ['nama', 'status_kepegawaian', 'nip', 'nik', 'nuptk', 'jenis_kelamin', 'telepon'];
        foreach ($headers as $index => $header) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($index + 1).'1', $header);
        }
        $sheet->getStyle('A1:G1')->getFont()->setBold(true);
        $sheet->getStyle('A1:G1')->getAlignment()->setHorizontal('center');
        foreach (['C' => '@', 'D' => '@', 'E' => '@', 'G' => '@'] as $column => $format) {
            $sheet->getStyle($column.'2:'.$column.'5001')->getNumberFormat()->setFormatCode($format);
        }
        foreach (['A' => 30, 'B' => 22, 'C' => 22, 'D' => 22, 'E' => 22, 'F' => 18, 'G' => 22] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:H1');

        return response()->streamDownload(function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, 'template-import-guru.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function downloadCsvTemplate()
    {
        $this->authorizeImport();
        $csv = "nama,status_kepegawaian,nip,nik,nuptk,jenis_kelamin,telepon\n";
        return response()->streamDownload(fn () => print($csv), 'template-import-guru.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function render()
    {
        $this->authorizeImport();
        return view('livewire.admin.teacher-import')->layout('layouts.app');
    }
}
