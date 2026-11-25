# Backup Manifest — P0-02A Active/Deleted User Test

Tanggal: 2026-11-25

- Source: `backend-laravel/tests/Feature/P002ANonCpAcademicAcceptanceTest.php`
- Baseline ref: `e22408d775c19204bbdf8940faa2337120f99cbc`
- Original Git blob SHA-1: `a0a582f3f937358e3570fb2b88c71a77c0aa3429`
- Backup: `backend-laravel/backup_before_patch/P0_02A_ACTIVE_STATUS_TEST_20261125/P002ANonCpAcademicAcceptanceTest.php.before`
- Backup commit: `bc030dbb5c6ff392b0d295b4bb4680ce9c52afe0`
- UTF-8 size: `31,962 bytes`
- Backup Git blob SHA-1: `a0a582f3f937358e3570fb2b88c71a77c0aa3429`
- SHA-256: `1d3107119672f930a93f715a92cff3a5e2c61298e50e24d19e24926ba6fb736d`
- Verifikasi: source dan backup di-fetch ulang; konten identik byte-for-byte, ukuran dan Git blob SHA sama.
- SHA-256 implementation validated against `SHA256("test") = 9f86d081884c7d659a2feaa0c55ad015a3bf4f1b2b0b822cd15d6c15b0f00a08`.

Backup dibuat sebelum menambahkan test akun Guru nonaktif dan akun dengan `deleted_at`.
