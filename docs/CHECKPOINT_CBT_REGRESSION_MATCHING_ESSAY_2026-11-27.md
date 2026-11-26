# CHECKPOINT CBT MADRASAH — REGRESSION TEST MATCHING & ESSAY — 27 NOVEMBER 2026

## AUDIT
- Repository: `zane0184/cbt_november`
- PR #44: https://github.com/zane0184/cbt_november/pull/44
- Branch: `fix/manual-question-optional-curriculum-20261127`
- Base: `integration/zip-vps-php83-20261126`
- Head terverifikasi: `26dfc90a61dbe6560d481906a8d061cffb78f126`
- PR masih OPEN, DRAFT, belum merged.
- Compare: 55 commit ahead, 0 behind.
- Snapshot builder menyimpan `QuestionVersion.explanation` ke `ExamQuestionSnapshot.snapshot_explanation`.
- Regression tests untuk validasi input Guru, validasi snapshot, dan keutuhan rubrik snapshot sudah ditambahkan.
- Test fixtures ESSAY lama yang memang membuat snapshot dilengkapi `explanation` dan `default_points` agar sesuai gerbang rubrik/bobot yang sekarang berlaku.
- Tidak menjalankan test suite sesuai instruksi pengguna.

## DESAIN
- MATCHING: uji penyimpanan manual dengan pasangan kosong/tidak lengkap; uji snapshot menolak opsi yang tidak berpasangan; uji snapshot menerima pasangan valid dan mempertahankan `option_key`/`matching_key`.
- ESSAY: uji penyimpanan menolak rubrik kosong dan menyimpan rubrik terisi; uji snapshot menolak rubrik kosong; uji snapshot menyalin rubrik dan tidak berubah ketika explanation versi bank soal diubah.
- Semua test hanya ditulis. Tidak dijalankan.

## IMPLEMENTASI
1. `backend-laravel/tests/Feature/QuestionBankTest.php`
   - Blob SHA: `d7be3b2e431ead4438b9856134f974c4895baf9d`
   - Test baru:
     - `test_matching_manual_save_rejects_blank_or_incomplete_pairs`
     - `test_essay_manual_save_requires_and_persists_teacher_rubric`
2. `backend-laravel/tests/Feature/ExamSnapshotTest.php`
   - Blob SHA: `2dd29366ca344872748e9d72f21418318971e8ea`
   - Test baru:
     - `test_essay_rubric_is_copied_to_immutable_snapshot`
     - `test_exam_snapshot_rejects_essay_without_teacher_rubric`
     - `test_exam_snapshot_rejects_matching_with_unpaired_options`
     - `test_valid_matching_pairs_are_copied_to_immutable_snapshot`
   - Fixture ESSAY yang perlu snapshot juga diberi rubrik dan bobot nilai eksplisit.

## BACKUP
Dibuat sebelum perubahan test:
- `backend-laravel/backups/matching-essay-hardening-20261127/QuestionBankTest.php.pre-regression-tests`
- `backend-laravel/backups/matching-essay-hardening-20261127/ExamSnapshotTest.php.pre-regression-tests`
- `backend-laravel/backups/matching-essay-hardening-20261127/ExamSnapshotTest.php.pre-valid-matching-snapshot-test`

## PENGUJIAN
- Tidak menjalankan PHPUnit, CI, runtime, atau integration tests.
- Verifikasi yang dilakukan: kedua file test dibaca ulang dari GitHub; keenam nama test baru ditemukan; SHA blob cocok; PR compare terverifikasi.
- Belum ada bukti test pass, compile Blade, atau runtime.
- Tidak ada database, migration, atau data produksi yang disentuh.

## STATUS
- PR #44 OPEN, DRAFT, belum merged.
- 55 commit ahead, 0 behind.
- SHORT_ANSWER tetap tidak diaktifkan.
- Berikutnya: audit gerbang publikasi dan tipe soal lainnya, lalu periksa konsistensi attempt/scoring/finalisasi. Tetap tidak menjalankan tests sampai ada arahan baru.
