# CHECKPOINT WG-01 IMPORT SOAL — 22 NOVEMBER 2026

## Posisi
- Repository: `zane0184/cbt_november`
- Branch Agent 3: `work/web-guru-foundation-20261122`
- HEAD sebelum commit dokumen ini: `25fe5e74a0754bcd110ceffac20141880c549ca8`
- Source aplikasi yang diuji: `e9c6867d40b134538e791bbb02f5743afec7ea`
- CI run: `44984094200` — SUCCESS, 16/16 step.
- HEAD berbeda dari SHA CI hanya satu commit dokumentasi; tidak ada perubahan source aplikasi setelah CI.
- Agent 1 dan Agent 2 branches tidak diubah.

## AUDIT — selesai
- Existing QuestionImportService/ExcelQuestionImporter adalah import JSON rows, bukan pembacaan file XLSX.
- Existing import XLSX Guru/Siswa terpisah; ImportBatch dan ImportWorkbookService domain-specific Admin.
- QuestionBank/QuestionController/QuestionPolicy dan question schema telah diinventaris.
- Enam tipe soal source-supported; pilihan A-D saja, bukan E.
- Media API ada, PDF hanya diterima sebagai media file; tidak ada parser PDF/OCR.
- Dompdf hanya generation; PhpSpreadsheet tersedia.
- Metadata materi/indikator/sumber/tahun ajaran belum punya penyimpanan langsung yang terverifikasi.
- KBC mapping table ada tetapi belum dipakai QuestionBank/importer.
- Operasional Proktor backend assignment/monitoring ada; halaman Guru belum ada.

## DESAIN — draft awal selesai
- XLSX usulan enam sheet: Petunjuk, Data Soal, Detail Opsi, Referensi Jenis Soal, Contoh Soal, Referensi Akademik tersembunyi.
- Import selalu menghasilkan draft preview; kunci jawaban PDF hanya kandidat; Guru wajib review dan confirm.
- Batch checksum dan encrypted row staging perlu dikoordinasikan untuk reuse ImportBatch.
- PDF digital via parser/pdftotext; scan via OCR Tesseract bila environment tersedia; semua hasil confidence-marked.
- Kurmer dan KBC satu konteks terintegrasi, bukan pilihan pengganti. CP provenance belum terverifikasi; KBC mapping hanya dari versi aktif/terdaftar.

## IMPLEMENTASI
- Belum ada perubahan source aplikasi.
- Tidak ada migration, dependency Composer, route, controller, Livewire, policy, API, atau database yang diubah.
- Dokumentasi audit/desain baru ditambahkan pada branch Agent 3.

## PENGUJIAN
- PASS: CI Web Guru foundation 44984094200 pada SHA e9c6867..., 16 step.
- NOT RUN: upload XLSX, template soal, preview/correction, idempotency, duplicates, import transaction, PDF extraction, OCR, media/formula/Arabic, browser UI, Proktor Guru UI.
- FAIL capability gap: PDF parser/OCR absent; XLSX file parser absent; option E unsupported.
- Source support six types belum diganti dengan klaim runtime integration test.

## BACKUP
- Tidak ada source existing yang diubah. Backup source tidak diperlukan untuk penambahan dua dokumen baru.
- Backup Web Guru existing dan backup Agent 2 tidak diubah.

## DEPENDENCY AGENT 2
Perlu keputusan/koordinasi sebelum implementasi backend:
1. Exact-role + question.manage gate untuk QuestionPolicy/QuestionController/MediaController.
2. Reuse ImportBatch/ImportBatchRow untuk type QUESTION atau desain batch yang konsisten; retention scheduler harus kompatibel.
3. Kontrak penyimpanan metadata Materi, Indikator, Sumber, Tahun Ajaran, accepted answers.
4. Persistensi dan tenant/system-owner validation untuk QuestionMultiCurriculumMapping.
5. Endpoint/service preview-confirm untuk XLSX dan draft import PDF; idempotency row fingerprint dan concurrency.
6. PDF parser/OCR dependencies dan batas operasional VPS.

## LANGKAH BERIKUTNYA
1. Koordinasikan daftar dependency Agent 2; jangan mengubah source yang dimiliki Agent 2.
2. Implementasikan template XLSX khusus soal pada file baru milik Agent 3 dan route di routes/teacher.php setelah kontrak referensi akademik disepakati.
3. Implementasikan halaman Import Soal Guru/preview/correction UI, tanpa membuat Bank Soal baru.
4. Integrasikan ke service Question Import yang disetujui Agent 2.
5. Uji file upload, template, validasi, duplicate, idempotency, tenant, exact-role, media, dan CI pada SHA terbaru.
6. Lanjutkan PDF text extraction, OCR fallback, manual review, dan browser tests.
7. Setelah itu lanjutkan menu Operasional Proktor Guru; backend assignment/monitoring existing harus digunakan, bukan dibuat ulang.

STATUS: PARSIAL — audit/desain awal selesai, fitur belum diimplementasikan, belum siap digunakan.
