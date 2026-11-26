# Audit dan Perbaikan Error Import DOCX — 26 November 2026

Repository: zane0184/cbt_november
Branch: work/rombel-docx-completion-20261126
HEAD setelah patch: e51067c9408487faf07a998e92f2dd16626cd44c

## AUDIT
CI Tenant Backend pada commit 81eaeebfd132e61ee591f8a3e4d35a4f4823f4a4 gagal pada full PHPUnit suite: 16 failed, 674 passed (3.542 assertions).

Akar kegagalan parser terkonfirmasi dari log:
`DOMDocument::loadXML(): Namespace prefix w on p is not defined in Entity`
di `app/Services/Questions/DocxQuestionParser.php:569`.

Penyebab teknis:
- Parser mengambil child paragraph OOXML dengan `DOMDocument::saveXML($node)`.
- Deklarasi namespace `xmlns:w` dan namespace OOXML lain berada pada root `w:document`, bukan pada setiap child `w:p`.
- Ketika child `w:p` diserialisasi sendiri, deklarasi namespace warisan tidak ikut tersimpan.
- `extractRichParagraphs()` lalu mencoba memuat fragment tersebut sebagai dokumen XML mandiri. Prefix `w` menjadi tidak terdefinisi. Laravel mengubah warning libxml menjadi ErrorException sehingga berbagai test parser, fitur import, dan preview template gagal.
- Error yang sama muncul pada beberapa test karena semuanya melewati ekstraksi rich paragraph; bukan 16 akar masalah berbeda.

Ada satu kegagalan lain yang terpisah dari DOCX:
- `AdminUiVerificationTest::admin_academic_management_can_create_rombel_and_subject` memakai kode mapel `BIO-10`.
- Validasi kode mata pelajaran yang baru ditambahkan hanya mengizinkan huruf, angka, dan underscore, sehingga menolak format kode lama yang memakai tanda hubung.

## DESAIN
- Pertahankan fragment OOXML mentah dan hash sumber.
- Sebelum parsing rich paragraph, bungkus fragment dengan root sementara yang mendeklarasikan namespace OOXML yang dibutuhkan: w, m, a, r, v.
- Validasi XML tetap menggunakan `LIBXML_NONET`; tidak mengaktifkan akses jaringan atau entity.
- Aktifkan internal libxml errors secara lokal dan pulihkan state sebelumnya, agar fragment tidak valid tidak mengeluarkan warning PHP yang berubah menjadi exception tak terkendali.
- Kode mapel tetap dibatasi dan dinormalisasi, tetapi karakter tanda hubung yang sudah dipakai source lama tetap diterima.

## IMPLEMENTASI
- `DocxQuestionParser.php`: parsing rich paragraph sekarang menggunakan wrapper namespace eksplisit.
- `AcademicManagement.php`: regex kode mapel menerima huruf, angka, underscore, dan tanda hubung.
- Backup sebelum patch:
  - `backend-laravel/backups/docx-namespace-and-subject-code-fix-20261126/DocxQuestionParser.php.before`
  - `backend-laravel/backups/docx-namespace-and-subject-code-fix-20261126/AcademicManagement.php.before`

## PENGUJIAN
- Bukti gagal sebelumnya: workflow Tenant Backend CI run 45755714961 pada commit 81eaeebfd132e61ee591f8a3e4d35a4f4823f4a4.
- Detail: 16 failed, 674 passed, 3.542 assertions. Parser failures all include the undefined inherited namespace warning at DocxQuestionParser.php:569. Separate subject-code failure was BIO-10 validation.
- Patch saat ini: commit e51067c9408487faf07a998e92f2dd16626cd44c.
- CI pada HEAD patch: run 45756277372 (Tenant Backend CI) dan run 45756277317 (deployment ZIP) masih pending saat laporan dibuat. Belum dinyatakan lulus.
- Tidak ada database produksi/VPS yang disentuh. Tidak ada migration produksi.
- Format DOCX nyata dari madrasah tetap memerlukan acceptance test terpisah; passing synthetic parser tests saja bukan bukti kompatibilitas semua dokumen Word.
