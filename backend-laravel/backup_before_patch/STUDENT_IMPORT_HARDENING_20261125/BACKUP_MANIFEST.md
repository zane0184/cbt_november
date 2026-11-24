# Backup Manifest — Student Import Hardening 2026-11-25

- Repository: zane0184/cbt_november
- Branch: work/docx-import-parser-20261125
- Baseline HEAD before hardening: 55a0c7e605bcdc6b3e720e960bcdd1f3f845b80d
- Backup location: backend-laravel/backup_before_patch/STUDENT_IMPORT_HARDENING_20261125/

| Source | Backup | Source Git blob SHA-1 | Backup Git blob SHA-1 | Verification |
|---|---|---|---|---|
| backend-laravel/app/Services/StudentImportService.php | backend-laravel/backup_before_patch/STUDENT_IMPORT_HARDENING_20261125/StudentImportService.php.before | 4144644f127736ac236e4a092192e89e9cd406cd | 4144644f127736ac236e4a092192e89e9cd406cd | PASS — identical blob |
| backend-laravel/tests/Feature/StudentImportTest.php | backend-laravel/backup_before_patch/STUDENT_IMPORT_HARDENING_20261125/StudentImportTest.php.before | 995cc05d3a1f5ac4e74b09e43cfa0b66f62302b0 | 995cc05d3a1f5ac4e74b09e43cfa0b66f62302b0 | PASS — identical blob |

The source files were fetched from the exact branch HEAD before any hardening patch. Backup files were created as new paths and fetched back from GitHub; each backup blob SHA matches its source blob SHA exactly.

No original backup was overwritten. No production database or VPS was accessed.
