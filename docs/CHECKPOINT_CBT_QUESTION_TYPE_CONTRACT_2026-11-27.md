# CHECKPOINT CBT MADRASAH — QUESTION TYPE CONTRACT HARDENING — 27 NOVEMBER 2026

## AUDIT
- Repo: `zane0184/cbt_november`
- PR #44: https://github.com/zane0184/cbt_november/pull/44
- Branch: `fix/manual-question-optional-curriculum-20261127`
- Base: `integration/zip-vps-php83-20261126`
- Head terverifikasi: `01bbfd77293d8dfc8463fa7d23bbf72a8d1ed311`
- PR OPEN, DRAFT, belum merged.
- Compare: 66 commit ahead, 0 behind.
- Editor Guru menerima: `MULTIPLE_CHOICE`, `MULTIPLE_RESPONSE`, `TRUE_FALSE`, `TRUE_FALSE_GROUP`, `MATCHING`, `ESSAY`.
- DOCX importer menerima: `MULTIPLE_CHOICE`, `MULTIPLE_RESPONSE`, `TRUE_FALSE`, `TRUE_FALSE_GROUP`, `ESSAY`; MATCHING belum didukung DOCX.
- API answer menerima MC/MULTIPLE_CHOICE, TRUE_FALSE, TRUE_FALSE_GROUP, MULTIPLE_RESPONSE, MATCHING, ESSAY; SHORT_ANSWER ditolak.
- Scoring service memiliki evaluator MC/MULTIPLE_CHOICE/TRUE_FALSE/TF, TRUE_FALSE_GROUP, MULTIPLE_RESPONSE, MATCHING, SHORT_ANSWER, ESSAY.
- Temuan: snapshot builder tidak memiliki allowlist tipe soal menyeluruh; tipe yang belum dikenal bisa lolos ke snapshot. API answer switch juga tidak memiliki default rejection. Alias legacy `TF` didukung scoring tetapi tidak pada validasi jawaban dan validasi kunci snapshot.
- SHORT_ANSWER tetap diblokir oleh snapshot dan API answer sesuai keputusan pengguna. Keberadaan evaluator lama tidak berarti tipe tersebut aktif.

## DESAIN
- Snapshot hanya boleh memuat tipe yang memiliki kontrak jawaban dan penilaian yang didukung: `MC`, `MULTIPLE_CHOICE`, `MULTIPLE_RESPONSE`, `TRUE_FALSE`, `TF`, `TRUE_FALSE_GROUP`, `MATCHING`, `ESSAY`.
- `SHORT_ANSWER` tetap ditolak dengan pesan khusus yang sudah ada.
- Tipe lain/masa depan ditolak saat snapshot dibuat.
- API answer harus fail-closed untuk tipe yang tidak dikenali.
- Alias `TF` diperlakukan identik dengan `TRUE_FALSE` pada validasi payload dan integritas opsi snapshot.
- MATCHING tetap tidak didukung oleh DOCX importer; tidak ditambahkan tanpa audit parser dan format dokumen.

## IMPLEMENTASI
1. `backend-laravel/app/Services/ExamSnapshotService.php`
   - Menambahkan allowlist tipe soal untuk snapshot.
   - Tipe tidak dikenal ditolak dengan 422.
   - Alias `MC`/`TF` ikut pemeriksaan jumlah opsi dan kunci jawaban.
   - Blob SHA: `da10e1f13a0ec4269ca729fbb83c12c3e99a8721`
2. `backend-laravel/app/Http/Controllers/Api/V1/ExamAnswerController.php`
   - Alias `TF` memakai validasi pilihan jawaban yang sama dengan `TRUE_FALSE`.
   - Menambahkan default rejection untuk tipe soal yang tidak dikenal.
   - Blob SHA: `276ccf346f73382dbbcdc2ed380336c904acce12`
3. `backend-laravel/tests/Feature/ExamSnapshotTest.php`
   - Test tipe soal tak dikenal ditolak pada snapshot.
   - Blob SHA: `9f9ab032a0f01409878be79fede1d4c6c0aee483`
4. `backend-laravel/tests/Feature/ExamAnswerTest.php`
   - Menambahkan payload alias `TF` pada kontrak validasi semua tipe.
   - Blob SHA: `511d9a9a7e8c1f37ae6d7a52362c933513c8d734`

## BACKUP
- `backend-laravel/backups/matching-essay-hardening-20261127/ExamSnapshotService.php.pre-question-type-allowlist`
- `backend-laravel/backups/matching-essay-hardening-20261127/ExamAnswerController.php.pre-question-type-fail-closed`
- `backend-laravel/backups/matching-essay-hardening-20261127/ExamSnapshotTest.php.pre-unknown-question-type-test`
- `backend-laravel/backups/matching-essay-hardening-20261127/ExamAnswerTest.php.pre-tf-alias-test`
- `backend-laravel/backups/matching-essay-hardening-20261127/ExamSnapshotService.php.pre-legacy-type-alias-integrity`

## PENGUJIAN
- PHPUnit, CI, runtime, integration tests tidak dijalankan sesuai instruksi pengguna.
- Verifikasi statis: file terbaru di-fetch ulang dari GitHub; SHA diverifikasi; allowlist, alias TF, default rejection, dan test baru ditemukan di source.
- Belum ada bukti test pass atau runtime.
- Tidak ada database, migration, atau data produksi yang disentuh.

## STATUS DAN LANJUTAN
- PR #44 OPEN, DRAFT, belum merged.
- 66 commit ahead, 0 behind.
- Berikutnya audit jalur penilaian dan finalisasi, termasuk konsistensi current score pointer, manual grading ESSAY, serta publikasi hasil siswa.
- SHORT_ANSWER tetap tidak aktif; tidak ada perubahan ke Android.
