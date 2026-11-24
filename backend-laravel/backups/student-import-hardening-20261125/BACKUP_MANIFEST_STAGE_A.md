# Backup manifest — Student Import Stage A

Pre-patch exact branch HEAD: `653412daace51fa973c697f98195a9fa8a91219a`

| Source | Byte-exact backup | Git blob SHA (source = backup) | SHA-256 | UTF-8 bytes |
|---|---|---|---|---:|
| `backend-laravel/app/Services/StudentImportService.php` | `backend-laravel/backups/student-import-hardening-20261125/StudentImportService.php.before_stageA_653412da` | `0c218d8ac481e6d3a5287c4a1cdc50bdfed88285` | `f7b905bcfd6b68b9d8abc7c8519296b9fc4713dcd851baf97f910aa53e74cb50` | 22765 |
| `backend-laravel/tests/Feature/StudentImportTest.php` | `backend-laravel/backups/student-import-hardening-20261125/StudentImportTest.php.before_stageA_653412da` | `e0a4ab94142388552446e6d7f1474516494f9cc6` | `d4bad7e6ef1d346512a681a72682d60f0e6ce92fcd73a3d1de59ae7a9adca5b1` | 13858 |
| `backend-laravel/app/Services/Import/XlsxPackagePreflight.php` | `backend-laravel/backups/student-import-hardening-20261125/XlsxPackagePreflight.php.before_stageA_653412da` | `b95ea885dabc9f5054df9cb3e06a1f502f8efb0e` | `6b4c8a62a566deed50792cf977a3d7554aff7c1f8766eb472b4c13d300997314` | 17351 |
| `backend-laravel/tests/Feature/StudentImportXlsxPreflightTest.php` | `backend-laravel/backups/student-import-hardening-20261125/StudentImportXlsxPreflightTest.php.before_stageA_653412da` | `d458ff1ddf876a37b14b3d96c190f0dc9d4c4075` | `1fc9bbfb53332b349bbe747b29b20d26eef2ef91c32c1c283fef1bc85020045a` | 6697 |

## Verification
- All four source files were fetched at the same exact pre-patch HEAD.
- Backups are created in the same Git commit as this manifest.
- After commit, backup Git blob SHAs are checked against the source SHAs above.
- SHA-256 is calculated from the fetched source content.
