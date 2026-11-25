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

    public function import(): void
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
        $message = $result['imported'].' akun Guru berhasil diimpor. Password awal dibuat acak; gunakan Reset Password di Manajemen Pengguna untuk menerbitkan kredensial.';
        $this->dispatch('notify', ['type' => 'success', 'message' => $message]);
        session()->flash('message', $message);
    }

    public function downloadXlsxTemplate()
    {
        $this->authorizeImport();
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Guru');
        $headers = ['nama', 'status_kepegawaian', 'nip', 'nik', 'nuptk', 'jenis_kelamin', 'telepon', 'email'];
        foreach ($headers as $index => $header) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($index + 1).'1', $header);
        }
        $sheet->getStyle('A1:H1')->getFont()->setBold(true);
        $sheet->getStyle('A1:H1')->getAlignment()->setHorizontal('center');
        foreach (['C' => '@', 'D' => '@', 'E' => '@', 'G' => '@'] as $column => $format) {
            $sheet->getStyle($column.'2:'.$column.'5001')->getNumberFormat()->setFormatCode($format);
        }
        foreach (['A' => 30, 'B' => 22, 'C' => 22, 'D' => 22, 'E' => 22, 'F' => 18, 'G' => 22, 'H' => 34] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:H1');

        $instructions = $spreadsheet->createSheet();
        $instructions->setTitle('Petunjuk');
        $rows = [
            ['PETUNJUK TEMPLATE IMPORT GURU'],
            ['Kolom', 'Ketentuan'],
            ['nama', 'Wajib diisi, maksimal 255 karakter.'],
            ['status_kepegawaian', 'Wajib: PNS, PPPK, atau HONORER.'],
            ['nip', 'Wajib tepat 18 digit untuk PNS/PPPK; kosongkan untuk HONORER. Simpan sebagai teks.'],
            ['nik', 'Wajib tepat 16 digit untuk HONORER; kosongkan untuk PNS/PPPK. Simpan sebagai teks.'],
            ['nuptk', 'Opsional; jika diisi harus tepat 16 digit.'],
            ['jenis_kelamin', 'Opsional: L atau P.'],
            ['telepon', 'Opsional, maksimal 40 karakter.'],
            ['email', 'Opsional, harus unik di instalasi madrasah.'],
            ['Login', 'PNS/PPPK login dengan NIP; HONORER login dengan NIK. NUPTK bukan identitas login.'],
            ['Password', 'Password awal dibuat acak. Admin menerbitkan kredensial melalui Reset Password setelah import.'],
            ['Proses', 'Unggah XLSX/CSV, buat preview, perbaiki seluruh error, lalu konfirmasi import. Maksimal 5.000 baris.'],
        ];
        foreach ($rows as $rowIndex => $row) {
            foreach ($row as $columnIndex => $value) {
                $instructions->setCellValue(Coordinate::stringFromColumnIndex($columnIndex + 1).($rowIndex + 1), $value);
            }
        }
        $instructions->getStyle('A1:B2')->getFont()->setBold(true);
        $instructions->getColumnDimension('A')->setWidth(26);
        $instructions->getColumnDimension('B')->setWidth(100);
        $instructions->getStyle('A1:B13')->getAlignment()->setWrapText(true);
        $instructions->freezePane('A3');

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
        $csv = "nama,status_kepegawaian,nip,nik,nuptk,jenis_kelamin,telepon,email\n";
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
