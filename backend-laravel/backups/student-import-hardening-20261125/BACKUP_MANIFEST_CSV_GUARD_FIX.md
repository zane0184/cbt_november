# Backup manifest — CSV guard correction

Pre-patch exact branch HEAD: `a563642e0562edcfff955fb9f71c6397b652bc45`

| Source | Backup | Git blob SHA (equal) | SHA-256 | UTF-8 bytes |
|---|---|---|---|---:|
| `backend-laravel/app/Services/StudentImportService.php` | `backend-laravel/backups/student-import-hardening-20261125/StudentImportService.php.before_csv_guard_fix_a563642e` | `556cd348c5cfd305c7fcde3875bb95612dca1b5b` | `40dd3ab5c644cea00196483a47cea97022f99b777bb3768288f0cfc9c0d17319` | 22666 |
| `backend-laravel/tests/Feature/StudentImportTest.php` | `backend-laravel/backups/student-import-hardening-20261125/StudentImportTest.php.before_csv_guard_fix_a563642e` | `e0a4ab94142388552446e6d7f1474516494f9cc6` | `5052dddca5533990d112f05124fd75dbeba13f0887fd10b5d94c33bc15f53b21` | 13858 |

Both backups were re-fetched from GitHub and matched their source Git blob SHA before the next edit.
