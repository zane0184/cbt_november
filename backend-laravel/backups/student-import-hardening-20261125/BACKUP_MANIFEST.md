# Backup manifest — Student Import Hardening

Pre-patch exact branch HEAD: `fd57037957043e1199cb8546966f1315775c45d6`

| Source file | Byte-exact backup | Git blob SHA (source = backup) | SHA-256 | UTF-8 bytes |
|---|---|---|---|---:|
| `backend-laravel/app/Services/StudentImportService.php` | `backend-laravel/backups/student-import-hardening-20261125/StudentImportService.php.before_fd570379` | `adc094bb037e9033343675cc24a8cac890ec7193` | `3b10f3dc1e9f6f02c0194703547e994c1b18ae395d923aed0b86d1eb58fb9877` | 16863 |
| `backend-laravel/tests/Feature/StudentImportTest.php` | `backend-laravel/backups/student-import-hardening-20261125/StudentImportTest.php.before_fd570379` | `6e3a8dcfa0d4a71ec605d27f7c611dcc190024a9` | `f1ec804bf09b97b98e48d0e77b19941e01d1e18d7fa1a5a1e7c045b93ad65ac1` | 13824 |
| `backend-laravel/app/Livewire/Admin/StudentImport.php` | `backend-laravel/backups/student-import-hardening-20261125/StudentImport.php.before_fd570379` | `fb1153c71a077ef3e5bb96226f47f4ee6f2f0e56` | `af9924476891504617e0ec4b463fab3294999425130e0ed79f60d3c1d3e622e0` | 4813 |
| `backend-laravel/resources/views/livewire/admin/student-import.blade.php` | `backend-laravel/backups/student-import-hardening-20261125/student-import.blade.php.before_fd570379` | `641313efbef119563b8ad4058b38d4de89dac97c` | `b547e8d0e1c0423ba290ea1d3935b3d1c32c3aeec7989190f67b33706278a3c0` | 5696 |
| `backend-laravel/app/Services/UserAccountService.php` | `backend-laravel/backups/student-import-hardening-20261125/UserAccountService.php.before_fd570379` | `66397a0d877f8742ab84ad7ddf30b3037134c4ef` | `bfd311316283dcf72c7074b49e5231c0648a244690a649f92a59b8866528b0f2` | 3374 |
| `backend-laravel/app/Livewire/Admin/UserManagement.php` | `backend-laravel/backups/student-import-hardening-20261125/UserManagement.php.before_fd570379` | `531ea3c0effd085e188acb18fc49cc037fec3e23` | `9ec0f42501841ac802e220ab8074c0e9f5567d22486d73889bea88a4bb475c2c` | 10671 |
| `backend-laravel/resources/views/livewire/admin/user-management.blade.php` | `backend-laravel/backups/student-import-hardening-20261125/user-management.blade.php.before_fd570379` | `c2ac5d3caea189de3d31c59f85b7d26fb9f10943` | `f0b11d67a735c157e30030b6c08522a8c95828d0da213771c590b9e75759ca3e` | 10899 |
| `backend-laravel/routes/web.php` | `backend-laravel/backups/student-import-hardening-20261125/web.php.before_fd570379` | `74b9c6ce75be962ccf7bd858943fb8e4edda1e1b` | `c56e60268dbf5922e0df822639bd1fc3fb7a8dafded7f603298a245d6f1a32db` | 6719 |
| `backend-laravel/tests/Feature/UserAccountLifecycleTest.php` | `backend-laravel/backups/student-import-hardening-20261125/UserAccountLifecycleTest.php.before_fd570379` | `dc8f1f585bd50c51352e6dcb34b0c88d229c582f` | `cdff1db7553ba431a118af7f31199ec161a3aaab8c1b6634e5d4ee3cc6901cc9` | 7420 |

## Verification
- Nine source files were fetched at the same exact pre-patch HEAD.
- Each backup was re-fetched from GitHub after creation.
- Git blob SHA matched for all nine source/backup pairs.
- SHA-256 was calculated from the exact fetched UTF-8 content.
- No source file was changed by the backup operation.
- New files (credential model/service/controller/view/migration and preflight helpers) do not require a pre-existing file backup.
