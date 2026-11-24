# Backup Manifest — CSV Record Bounds Hardening
Tanggal: 25 November 2026
Branch sumber: work/benchmark-cleanup-proof-20261125
Source commit: 653412daace51fa973c697f98195a9fa8a91219a
Branch kerja: work/p0-csv-import-hardening-20261125
Backup dibuat sebelum perubahan kode.

| Source | Backup | Git blob SHA-1 | SHA-256 |
|---|---|---|---|
| backend-laravel/app/Services/StudentImportService.php | backend-laravel/backup_before_patch/CSV_RECORD_BOUNDS_20261125/StudentImportService.php.before | 0c218d8ac481e6d3a5287c4a1cdc50bdfed88285 | 9a335dad3c2c0371b515b1a37850e86f1c47da5765c22388264d0f6fbb31b897 |
| backend-laravel/tests/Feature/StudentImportTest.php | backend-laravel/backup_before_patch/CSV_RECORD_BOUNDS_20261125/StudentImportTest.php.before | e0a4ab94142388552446e6d7f1474516494f9cc6 | 3398c48358d50bf86b3a762f5db9e386db3d060b6590d6f3e88c924207e3149d |
| .github/workflows/docx-import-parser-ci.yml | backend-laravel/backup_before_patch/CSV_RECORD_BOUNDS_20261125/docx-import-parser-ci.yml.before | d9bca1b84256ce6d2b6c9b973fcf086c599d0fe8 | 67e3f9d5f8e070d5dbb63ca540a1dbbce7d2db0d261d6acb92da5456f83dacb4 |

Backup byte content diambil dari branch sumber dan dicadangkan sebelum patch. Distribusi ZIP harus mengecualikan folder backend-laravel/backup_before_patch.
