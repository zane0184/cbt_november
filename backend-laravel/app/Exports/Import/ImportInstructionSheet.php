<?php

namespace App\Exports\Import;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ImportInstructionSheet implements FromArray, WithTitle, ShouldAutoSize, WithStyles
{
    public function __construct(private readonly string $type) {}

    public function title(): string { return 'Petunjuk'; }

    public function array(): array
    {
        $isGuru = $this->type === 'GURU';
        return [
            ['CBT MADRASAH — TEMPLATE IMPORT '.$this->type],
            ['TEMPLATE_VERSION', ImportTemplateVersion::VERSION],
            ['JENIS_DATA', $this->type],
            ['PENTING', 'Baris CONTOH wajib dihapus sebelum upload. Baris contoh tidak boleh dianggap data nyata.'],
            ['WAJIB', 'Kolom bertanda * wajib diisi kecuali terdapat keterangan kondisional.'],
            ['IDENTITAS', 'Semua nomor identitas adalah TEKS. Jangan ubah format sel menjadi angka.'],
            ['KEBIJAKAN', 'Data baru diimpor; data existing dilewati; duplikat dalam file ditolak; data existing tidak diperbarui.'],
            ['AKUN', 'Password tidak ada di workbook. Pengguna harus mengatur password melalui tautan aktivasi satu kali.'],
            ['FORMAT TANGGAL', 'Gunakan YYYY-MM-DD, contoh 2026-07-01.'],
            ['GENDER', 'Gunakan L untuk laki-laki dan P untuk perempuan.'],
            ['REFERENSI', 'Gunakan pilihan dropdown yang tersedia. Referensi berasal dari tenant madrasah yang sedang login.'],
            ['VERSI', 'Jangan mengubah atau menghapus baris TEMPLATE_VERSION. Template versi berbeda akan ditolak.'],
            ['PENGISIAN', $isGuru
                ? 'Guru PNS wajib mengisi NIP dan mengosongkan NIK. Guru HONORER wajib mengisi NIK dan mengosongkan NIP. NUPTK opsional.'
                : 'NISN wajib. NIS opsional. Rombel dan Tahun Pelajaran harus dipilih dari daftar.'],
            ['PENUGASAN', $isGuru ? 'Isi sheet Penugasan Guru terpisah. Identitas Guru harus sama dengan NIP/NIK pada Data Guru.' : 'Tidak berlaku.'],
            ['CONTOH', 'Data contoh menggunakan nama CONTOH dan wajib dihapus. Data ini tidak boleh diimpor.'],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->mergeCells('A1:B1');
        $sheet->getStyle('A1:B1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1:B14')->getAlignment()->setWrapText(true)->setVertical('top');
        $sheet->getColumnDimension('A')->setWidth(24);
        $sheet->getColumnDimension('B')->setWidth(105);
        $sheet->getStyle('A2:A14')->getFont()->setBold(true);
        return [];
    }
}
