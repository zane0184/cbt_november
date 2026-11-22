<?php

namespace App\Exports\QuestionImport;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class QuestionImportTemplateExport implements WithMultipleSheets
{
    public const VERSION = 'CBT-QUESTION-1';

    public function sheets(): array
    {
        return [
            new QuestionImportTemplateSheet('Petunjuk', [
                ['CBT MADRASAH — TEMPLATE IMPORT SOAL'],
                ['TEMPLATE_VERSION', self::VERSION],
                ['STATUS', 'Template struktur awal. Import XLSX belum diaktifkan sampai kontrak backend disetujui.'],
                ['ALUR', 'Isi workbook → unggah → validasi server → preview → koreksi → konfirmasi Guru.'],
                ['DRAFT', 'Soal hasil import wajib berstatus DRAFT dan tidak otomatis dipublikasikan.'],
                ['DATA SOAL', 'Satu baris pada sheet Data Soal mewakili satu soal. Gunakan Nomor Soal yang sama pada sheet Detail Opsi.'],
                ['DETAIL OPSI', 'Satu baris per opsi, pasangan matching, atau accepted answer. MATCHING dapat memiliki beberapa pasangan.'],
                ['JENIS SOAL', 'Jenis yang dapat dipilih saat ini hanya MULTIPLE_CHOICE, MULTIPLE_RESPONSE, TRUE_FALSE, MATCHING, dan ESSAY. Opsi aktif maksimal A-D; tidak ada opsi E.'], 
                ['TRUE_FALSE', 'Gunakan dua opsi dengan option key A dan B. Jangan membuat pilihan selain A/B.'],
                ['SHORT_ANSWER', 'BLOCKED. Jangan dipilih atau diimpor sampai accepted-answer authoring, persistence, snapshot, dan ScoringService lulus uji integrasi.'], 
                ['ESSAY', 'Rubrik/pembahasan dapat diisi pada Data Soal. Penilaian ESSAY tetap memerlukan pemeriksaan manual.'],
                ['UNICODE', 'Simpan teks sebagai teks biasa. Unicode, Bahasa Arab dan harakat dapat dimasukkan, tetapi preservasi RTL/format belum dijamin oleh import yang belum tersedia.'],
                ['RUMUS', 'Rumus dapat ditulis sebagai teks/LaTeX sederhana. Rendering rumus Web/Android belum dijamin oleh template ini.'],
                ['MEDIA', 'Media Ref hanya untuk referensi MediaAsset yang nantinya disetujui backend. Gambar tertanam tidak diekstrak oleh template ini.'],
                ['REFERENSI AKADEMIK', 'Sheet referensi belum diisi otomatis. Jangan mengarang CP, TP, KBC, subject, kelas, atau tahun ajaran.'],
                ['KURIKULUM', 'Kurikulum Merdeka dan KBC/Panca Cinta adalah satu konteks terintegrasi, bukan pilihan yang saling menggantikan.'],
                ['CONTOH', 'Sheet Contoh Soal hanya panduan. Jangan menyalin baris contoh sebagai data nyata tanpa mengganti seluruh isinya.'],
                ['FORMULA', 'Jangan menambahkan formula, macro, external link, object, atau embedded file ke workbook.'],
                ['VERSI', 'Jangan mengubah TEMPLATE_VERSION. Versi template yang tidak dikenal harus ditolak oleh importer.'],
            ]),
            new QuestionImportTemplateSheet('Data Soal', [[
                'External Row ID', 'Nomor Soal', 'Kode Soal', 'Mata Pelajaran Ref', 'Kelas Ref',
                'Tahun Ajaran Ref', 'Materi/Topik', 'CP Ref', 'TP Ref', 'KBC Ref',
                'Elemen KBC Ref', 'Jenis Soal', 'Pertanyaan', 'Pembahasan/Rubrik',
                'Bobot', 'Kesulitan', 'Indikator', 'Sumber/Referensi', 'Media Ref',
            ]], [
                'L' => '"MULTIPLE_CHOICE,MULTIPLE_RESPONSE,TRUE_FALSE,MATCHING,ESSAY"',
                'P' => '"EASY,MEDIUM,HARD"',
            ]),
            new QuestionImportTemplateSheet('Detail Opsi', [[
                'Nomor Soal', 'Tipe Detail', 'Urutan', 'Option Key', 'Isi Opsi/Pasangan',
                'Matching Key', 'Benar (TRUE/FALSE)', 'Accepted Answer', 'Media Ref',
            ]], [
                'B' => '"OPTION,MATCH_PAIR"',
                'D' => '"A,B,C,D"',
                'G' => '"TRUE,FALSE"',
            ]),
            new QuestionImportTemplateSheet('Referensi Jenis Soal', [
                ['Kode Jenis', 'Bentuk Jawaban', 'Validasi Pokok', 'Status Bukti Source'],
                ['MULTIPLE_CHOICE', 'Satu option key terpilih', 'Opsi A-D; tepat satu kunci benar', 'Dikenali source; integrasi file belum diuji'],
                ['MULTIPLE_RESPONSE', 'Daftar option key terpilih', 'Opsi A-D; satu atau lebih kunci benar', 'Dikenali source; integrasi file belum diuji'],
                ['TRUE_FALSE', 'Satu pilihan A atau B', 'Dua opsi A/B; satu kunci benar', 'Dikenali source; integrasi file belum diuji'],
                ['MATCHING', 'Pasangan left/right', 'Beberapa pasangan; setiap pasangan memiliki matching key', 'Dikenali source; integrasi file belum diuji'],
                ['SHORT_ANSWER', 'Teks jawaban', 'Memerlukan accepted answers yang tersimpan dan masuk snapshot', 'BLOCKED — storage/snapshot accepted answers belum terbukti'],
                ['ESSAY', 'Teks panjang', 'Rubrik; penilaian manual', 'Dikenali source; integrasi file belum diuji'],
                ['CATATAN', 'Opsi aktif A-D saja', 'Opsi E tidak didukung pada kontrak saat ini', 'Jangan menambahkan opsi E'],
            ]),
            new QuestionImportTemplateSheet('Contoh Soal', [
                ['Nomor Soal', 'Jenis Soal', 'Pertanyaan', 'Pembahasan/Rubrik', 'Bobot', 'Kesulitan', 'Catatan'],
                ['CONTOH-01', 'MULTIPLE_CHOICE', 'Manakah hasil 2 + 2?', 'Contoh pembahasan', '1', 'EASY', 'Hapus/ganti sebelum digunakan'],
                ['CONTOH-02', 'MULTIPLE_RESPONSE', 'Pilih bilangan genap.', 'Contoh pembahasan', '1', 'EASY', 'Hapus/ganti sebelum digunakan'],
                ['CONTOH-03', 'TRUE_FALSE', 'Matahari terbit dari timur.', 'Contoh pembahasan', '1', 'EASY', 'Gunakan opsi A/B pada Detail Opsi'],
                ['CONTOH-04', 'MATCHING', 'Pasangkan istilah dan keterangannya.', 'Contoh pembahasan', '2', 'MEDIUM', 'Masukkan setiap pasangan sebagai baris tersendiri'],
                ['CONTOH-05', 'SHORT_ANSWER (BLOCKED)', 'Tuliskan hasil 5 × 3.', 'Contoh pembahasan', '1', 'EASY', 'Contoh ini tidak dapat dipilih atau diimpor'], 
                ['CONTOH-06', 'ESSAY', 'Jelaskan proses daur air.', 'Rubrik: ketepatan konsep, urutan, dan kelengkapan', '5', 'MEDIUM', 'Penilaian manual'],
                ['PERINGATAN', '', 'Sheet ini hanya contoh. Tidak boleh diimpor otomatis.', '', '', '', ''],
            ]),
            new QuestionImportTemplateSheet('Referensi Akademik', [
                ['Jenis Referensi', 'Ref ID', 'Kode', 'Nama/Deskripsi', 'Lingkup Tenant', 'Versi', 'Status Verifikasi'],
                ['STATUS', '', '', 'Referensi akademik tenant-scoped belum dimuat ke template statis ini.', '', '', 'NEEDS_BACKEND_CONTRACT'],
                ['CP/TP', '', '', 'Gunakan hanya CP/TP yang benar-benar tersedia dan sesuai subject/kelas/tenant.', '', '', 'JANGAN MENGARANG'],
                ['KBC', '', '', 'Gunakan hanya KBC_CINTA dan struktur aktif/terdaftar yang tervalidasi.', '', '', 'JANGAN MENGARANG'],
            ]),
        ];
    }
}

/**
 * Small reusable worksheet implementation kept in the same PSR-4 file.
 * It creates no formulas or external workbook relationships.
 */
final class QuestionImportTemplateSheet implements FromArray, WithTitle, ShouldAutoSize, WithStyles, WithEvents
{
    public function __construct(
        private readonly string $sheetTitle,
        private readonly array $rows,
        private readonly array $validations = [],
    ) {}

    public function title(): string
    {
        return $this->sheetTitle;
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('1:1')->getFont()->setBold(true);
        $sheet->getStyle('1:1')->getAlignment()->setWrapText(true)->setVertical('top');
        $sheet->freezePane('A2');

        if ($this->sheetTitle === 'Data Soal' || $this->sheetTitle === 'Detail Opsi') {
            $sheet->setAutoFilter('A1:'.$sheet->getHighestColumn().'1');
            $sheet->getRowDimension(1)->setRowHeight(38);
        }

        if ($this->sheetTitle === 'Petunjuk') {
            $sheet->mergeCells('A1:B1');
            $sheet->getStyle('A1:B1')->getFont()->setSize(14);
            $sheet->getStyle('A1:B'.count($this->rows))->getAlignment()->setWrapText(true)->setVertical('top');
            $sheet->getColumnDimension('A')->setWidth(24);
            $sheet->getColumnDimension('B')->setWidth(105);
            $sheet->getStyle('A2:A'.count($this->rows))->getFont()->setBold(true);
        }

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $lastRow = 2001;

                foreach ($this->validations as $column => $formula) {
                    for ($row = 2; $row <= $lastRow; $row++) {
                        $validation = $sheet->getCell($column.$row)->getDataValidation();
                        $validation->setType(DataValidation::TYPE_LIST);
                        $validation->setErrorStyle(DataValidation::STYLE_STOP);
                        $validation->setAllowBlank(true);
                        $validation->setShowInputMessage(true);
                        $validation->setShowErrorMessage(true);
                        $validation->setErrorTitle('Nilai tidak valid');
                        $validation->setError('Gunakan salah satu nilai yang tersedia pada daftar.');
                        $validation->setFormula1($formula);
                    }
                }
            },
        ];
    }
}
