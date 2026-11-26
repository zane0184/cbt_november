# Audit Akar Masalah dan Perbaikan Simpan Import DOCX Web Guru
Tanggal: 26 November 2026
Repository: zane0184/cbt_november
Branch: fix/docx-draft-save-root-cause-20261126

## AUDIT

Pemeriksaan dilakukan terhadap source PR #32, #33, #39 dan source integrasi yang menjadi basis paket Web CBT.

### Akar masalah 1 — status parser awal menjadi gerbang mutlak penyimpanan
- `DocxQuestionParser::buildQuestions()` menetapkan `status = NEEDS_REVIEW` jika ada error pembacaan awal. Termasuk jenis soal yang perlu dikonfirmasi, penanda PG/PGK yang tidak eksplisit, tabel yang belum terklasifikasi, rumus OMML, dan tabel bersarang.
- `QuestionDocxImport::importDrafts()` sebelumnya hanya mengambil kartu dari state Livewire yang memiliki `status === READY`.
- Guru dapat memperbaiki jenis soal, teks, dan opsi pada kartu preview, tetapi perubahan itu tidak menghitung ulang status parser. Akibatnya, kartu yang semula NEEDS_REVIEW tetap dikeluarkan dari penyimpanan meskipun data hasil edit sudah memenuhi validasi importer.
- Ada ketidaksesuaian desain: parser menandai rumus/tabel kompleks perlu ditinjau, sedangkan importer sudah memiliki dukungan menyimpan OOXML/MathML/tabel sebagai metadata DRAFT dan menandai publikasi perlu review. Gerbang READY mencegah jalur DRAFT tersebut tercapai.

### Akar masalah 2 — pemetaan kurikulum memblokir pembuatan DRAFT
- `DocxQuestionDraftImporter` sebelumnya mewajibkan setiap soal sudah memiliki CP dan TP/ATP sebelum membuat Question/QuestionVersion.
- Importer kemudian memanggil `QuestionVersionCurriculumMappingService::sync()` di dalam transaksi penyimpanan utama.
- Service pemetaan menolak data yang belum memenuhi provenance CP resmi, TP/ATP terpublikasi, hubungan CP induk, serta struktur Panca Cinta, Pembelajaran Mendalam, dan dimensi profil lulusan.
- Jika validasi pemetaan melempar ValidationException, transaksi utama membatalkan Question, QuestionVersion, opsi, media, audit, dan batch import. Tidak ada DRAFT yang tertinggal.
- PR #39 hanya menampilkan error `curriculumMapping` dan status loading. Itu memperjelas pesan, tetapi tidak mengubah ketergantungan transaksi DRAFT pada pemetaan lengkap.

### Akar masalah 3 — status READY dari browser bukan sumber kebenaran
- Status `READY/NEEDS_REVIEW` pada state Livewire dapat berubah/tidak sinkron dengan hasil parser terbaru dan bukan data yang boleh dipercaya sebagai otoritas penyimpanan.
- Jalur simpan harus membangun ulang baris soal dari parse server terbaru, lalu mengambil hanya field yang memang dapat diedit Guru dari preview.

### Catatan skema
- Migration `2026_11_25_020001_add_question_version_metadata_and_docx_import_batches.php` memang tersedia pada source integrasi. Migration tersebut menambahkan kolom metadata dan tabel idempotensi batch. Karena itu, audit source tidak menemukan dasar untuk menyatakan tabel batch sama sekali belum dibuat dalam source.
- Keberadaan migration pada source tidak membuktikan migration sudah diterapkan pada database VPS. Hal tersebut tidak diverifikasi karena pengguna melarang pengujian runtime/VPS pada tahap ini.

## DESAIN PERBAIKAN

1. Hasil parse server pada saat Simpan menjadi otoritas untuk source locator, identitas soal, relasi media, blocked features, OOXML, dan alasan review.
2. State browser hanya boleh menyumbang pilihan sertakan/tidak serta field yang memang diedit Guru (jenis soal, stem, opsi/pernyataan, tingkat kesulitan, dan pemetaan per soal).
3. Status READY dari browser tidak lagi menjadi syarat simpan. Validasi akhir menggunakan field aktual yang akan disimpan dan validasi domain importer.
4. DRAFT dapat disimpan walaupun pemetaan CP/TP/ATP belum lengkap atau belum memenuhi provenance. Status pemetaan dicatat sebagai PENDING_TEACHER; tidak dibuat mapping palsu atau parsial.
5. Jika pemetaan diberikan dan lolos validasi, mapping resmi disimpan. Jika validasi domain pemetaan menolak, perubahan mapping diisolasi/di-rollback pada transaksi pemetaan dan DRAFT tetap tersimpan dengan alasan pending. Error database, storage, foreign key, atau exception non-validasi tetap membatalkan seluruh transaksi.
6. Publikasi tetap fail-closed. Guru harus melengkapi pemetaan resmi dan meninjau konten kompleks sebelum soal dapat diterbitkan.

## IMPLEMENTASI

File runtime yang diubah:
- `backend-laravel/app/Livewire/Teacher/QuestionDocxImport.php`
- `backend-laravel/app/Services/Questions/DocxQuestionDraftImporter.php`
- `backend-laravel/app/Livewire/Admin/QuestionBank.php`
- `backend-laravel/resources/views/livewire/teacher/question-docx-import.blade.php`

Perubahan:
- Rebuild baris soal dari parser server saat Simpan; identitas sequence/nomor diverifikasi terhadap preview.
- Tidak lagi memfilter kartu berdasarkan `status === READY` dari state browser.
- Field yang boleh diedit disalin secara eksplisit dari preview; source locator, media, OOXML, blocked features, dan error parser tetap berasal dari parse server.
- Validasi akhir tetap membatasi jenis soal yang didukung dan memeriksa stem, opsi, pernyataan, tingkat kesulitan, dan blocked media.
- Alasan review dari parser server disimpan pada metadata versi. Flag `requires_manual_review` aktif untuk soal yang memerlukan review, sehingga publikasi tetap terkunci.
- Pemetaan CP/TP/ATP yang belum lengkap atau ditolak validasi dicatat sebagai `curriculum_mapping.status = PENDING_TEACHER`; DRAFT tetap disimpan.
- Jika mapping resmi valid, status dicatat MAPPED. Jika Guru kemudian melengkapi mapping melalui editor Bank Soal dan service ketat berhasil, marker pending diperbarui menjadi MAPPED.
- Pesan sukses menyebutkan jumlah soal yang masih menunggu pemetaan.
- UI menjelaskan bahwa pemetaan boleh diselesaikan setelah DRAFT tersimpan, tetapi wajib sebelum publikasi.
- PR #39 tetap disertakan untuk indikator tombol dan tampilan error validasi.

## BACKUP
Backup source sebelum patch disimpan di:
`backend-laravel/backups/docx-draft-save-root-cause-20261126/`

File backup:
- `QuestionDocxImport.php.before`
- `DocxQuestionDraftImporter.php.before`
- `question-docx-import.blade.php.before`
- `QuestionBank.php.before`

Branch baseline sebelum patch tetap tersedia:
`release/docx-import-combined-20261126`

## PENGUJIAN
- PHPUnit: tidak dijalankan sesuai instruksi.
- CI: tidak dijalankan sesuai instruksi.
- Runtime/browser/VPS: tidak dijalankan sesuai instruksi.
- Tidak ada klaim PASS atau bukti transaksi database runtime.
- Yang dilakukan adalah audit dan perubahan source berdasarkan alur kode, kontrak model/migration, serta validasi service yang terlihat pada source GitHub.

## STATUS
Perbaikan source sudah ditulis pada branch kerja. Status transaksi database aktual masih belum terverifikasi sampai paket dipasang dan diuji pada staging/VPS oleh pengguna.
