# Backup Manifest — P0-02A.1 Provenance Test Fixture Correction

Tanggal: 2026-11-25

- File yang diubah: `backend-laravel/tests/Feature/TeacherLearningOutcomeProvenanceTest.php`
- Baseline ref: `7b7ed1da69aee1e0954f4896f7ecd66a65be49c5`
- Original Git blob SHA-1: `58343c391e239f8724af382c2669c66768f03890`
- Ukuran UTF-8: `10,997 bytes`
- SHA-256: `d2f6750efc59a1cf980b78b786cba078271c2dabae5a5516a60279f21ed64594`
- Backup path: `backend-laravel/backup_before_patch/P0_02A1_PROVENANCE_TEST_FIXTURE_20261125/TeacherLearningOutcomeProvenanceTest.php.before`
- Backup commit: `5630d6297ebb81dd77fa43dd397c23e04f9b8e54`
- Backup Git blob SHA-1: `58343c391e239f8724af382c2669c66768f03890`
- Verifikasi fetch ulang: source sebelum perubahan dan backup identik byte-for-byte; ukuran serta Git blob SHA-1 sama.

Perubahan test mengganti fixture parent CP NULL (ditolak oleh NOT NULL/FK database) menjadi CP fixture pada struktur mapel lain, sehingga pengujian mencapai guard service tanpa melemahkan constraint database. Fixture ini sintetis dan bukan bukti CP resmi.