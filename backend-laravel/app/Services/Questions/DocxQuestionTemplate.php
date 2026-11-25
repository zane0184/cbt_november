<?php

namespace App\Services\Questions;

use Illuminate\Support\Str;
use ZipArchive;

class DocxQuestionTemplate
{
    public function download()
    {
        abort_unless(class_exists(ZipArchive::class), 503, 'Ekstensi PHP ZIP belum aktif pada server.');

        $lines = [
            'TEMPLATE IMPORT SOAL CBT MADRASAH',
            'Petunjuk: Ganti contoh di bawah dengan soal Guru. Simpan sebagai Microsoft Word Document (.docx).',
            'Gunakan satu nomor untuk satu soal. Jangan menaruh tabel kisi-kisi sebagai isi soal.',
            'Tipe yang dapat disimpan: PG, PGK, Benar/Salah 3 pernyataan, dan Uraian (penilaian manual).',
            '',
            'SOAL 1. [PG] Hasil dari 4 + 5 adalah ....',
            'A. 7',
            'B. 8',
            'C. 9',
            'D. 10',
            'KUNCI: C',
            '',
            'SOAL 2. [PGK] Pilih semua bilangan genap berikut.',
            'A. 2',
            'B. 3',
            'C. 4',
            'D. 5',
            'KUNCI: A, C',
            '',
            'SOAL 3. [B/S] Tentukan benar atau salah setiap pernyataan berikut.',
            'a. 2 adalah bilangan genap.',
            'b. 5 lebih kecil daripada 3.',
            'c. 10 dapat dibagi 2 tanpa sisa.',
            'KUNCI: B, S, B',
            '',
            'SOAL 4. [ESSAY] Jelaskan mengapa kita perlu menjaga kebersihan lingkungan.',
            '',
            'FORMAT KUNCI DI AKHIR DOKUMEN (alternatif):',
            'KUNCI JAWABAN',
            '1. C',
            '2. A, C',
            '3. B, S, B',
            '',
            'STIMULUS BERSAMA (opsional):',
            'STIMULUS [BACAAN-A]: Tuliskan bacaan atau stimulus di sini.',
            'SOAL 5. [PG] Pertanyaan yang menggunakan stimulus di atas.',
            'A. Pilihan A',
            'B. Pilihan B',
            'KUNCI: A',
            'AKHIR STIMULUS',
            '',
            'Catatan: Template ini contoh struktur, bukan kewajiban format dokumen. Dokumen Word biasa dapat diunggah. Struktur ambigu atau tipe yang belum didukung akan ditandai untuk ditinjau dan tidak akan disimpan diam-diam.',
        ];

        $paragraphs = '';
        foreach ($lines as $line) {
            $text = htmlspecialchars($line, ENT_XML1 | ENT_COMPAT, 'UTF-8');
            $paragraphs .= '<w:p><w:r><w:t xml:space="preserve">' . $text . '</w:t></w:r></w:p>';
        }

        $documentXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            . '<w:body>' . $paragraphs
            . '<w:sectPr><w:pgSz w:w="11906" w:h="16838"/><w:pgMar w:top="1440" w:right="1440" w:bottom="1440" w:left="1440"/></w:sectPr>'
            . '</w:body></w:document>';

        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            . '</Types>';

        $relationships = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
            . '</Relationships>';

        $path = tempnam(sys_get_temp_dir(), 'cbt-docx-template-');
        if ($path === false) {
            abort(500, 'Tidak dapat menyiapkan template DOCX.');
        }

        try {
            $zip = new ZipArchive();
            if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                abort(500, 'Tidak dapat membuat paket DOCX.');
            }
            $zip->addFromString('[Content_Types].xml', $contentTypes);
            $zip->addFromString('_rels/.rels', $relationships);
            $zip->addFromString('word/document.xml', $documentXml);
            $zip->close();
        } catch (\Throwable $exception) {
            @unlink($path);
            throw $exception;
        }

        return response()->download(
            $path,
            'template-import-soal-cbt-madrasah.docx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']
        )->deleteFileAfterSend(true);
    }
}
