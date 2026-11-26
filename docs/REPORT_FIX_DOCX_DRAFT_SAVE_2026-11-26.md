# Perbaikan Simpan Draft Import DOCX — 2026-11-26

## AUDIT
- Komponen `QuestionDocxImport::importDrafts()` sebelumnya memasukkan semua soal yang dicentang ke batch, tanpa memfilter `status` hasil parser.
- Parser DOCX memberi status `READY` atau `NEEDS_REVIEW`. Importer layanan memvalidasi setiap soal dalam satu transaksi; satu soal yang tidak memenuhi syarat dapat menggagalkan seluruh batch.
- Layanan `DocxQuestionDraftImporter` juga mensyaratkan minimal satu CP dan satu TP/ATP untuk setiap soal. Syarat ini sebelumnya tidak dijelaskan dekat tombol simpan.
- Karena screenshot tidak menampilkan pesan respons setelah tombol diklik, penyebab spesifik pada sesi browser belum dapat dipastikan; temuan di atas adalah cacat alur source yang nyata dan perlu diperbaiki.

## DESAIN
- Hanya soal berstatus `READY` dan dicentang yang dikirim ke layanan penyimpanan.
- Jika tidak ada soal valid terpilih, tampilkan pesan validasi yang menjelaskan tindakan berikutnya.
- Tegaskan kebutuhan pemetaan CP serta TP/ATP sebelum menyimpan.
- Pertahankan transaksi, tenant isolation, otorisasi Guru, dan status DRAFT.

## IMPLEMENTASI
- Backup PHP: `backend-laravel/backups/docx-save-validation-fix-20261126/QuestionDocxImport.php.before`.
- Backup Blade: `backend-laravel/backups/docx-save-validation-fix-20261126/question-docx-import.blade.php.before`.
- PHP: `backend-laravel/app/Livewire/Teacher/QuestionDocxImport.php` — filter READY dan pesan validasi yang lebih jelas.
- Blade: `backend-laravel/resources/views/livewire/teacher/question-docx-import.blade.php` — keterangan CP/TP wajib dan soal NEEDS_REVIEW tidak ikut disimpan.
- Commit PHP: `2c1a3f6a33d35f5166d7f40c544628bf97c313f8`.
- Commit Blade: `47bd92ad2a37a79d1f50011452ce7bfd7d02fa7f`.

## PENGUJIAN
- Source difetch ulang setelah perubahan.
- Pemeriksaan statis: {"filters_ready":true,"ready_error_message":true,"mapping_requirement":true,"invalid_not_saved_notice":true}.
- Belum ada PHPUnit/browser runtime atau uji simpan ke database test pada sesi ini.
- Tidak ada database produksi/VPS yang disentuh.
