# Backup Manifest — Student Import Security Preflight 2026-11-25

Repository: zane0184/cbt_november
Branch: work/p0-student-import-security-20261125
Baseline source SHA: b6148507bd77116dfc8a5f5697cf4cff226a4b87
Purpose: byte-exact backup before XLSX/OOXML and CSV resource-boundary hardening.

| Source | Backup | Git blob SHA-1 (source = backup) | SHA-256 (source = backup) | Verification |
|---|---|---|---|---|
| backend-laravel/app/Services/StudentImportService.php | backend-laravel/backup_before_patch/STUDENT_IMPORT_SECURITY_PREFLIGHT_20261125/StudentImportService.php.before | adc094bb037e9033343675cc24a8cac890ec7193 | b0e6ee0854d2884b3036c699719af6afc127b366415ef3ccdb6fe90e1891298f | PASS — exact Git blob and SHA-256 match |
| backend-laravel/tests/Feature/StudentImportTest.php | backend-laravel/backup_before_patch/STUDENT_IMPORT_SECURITY_PREFLIGHT_20261125/StudentImportTest.php.before | 6e3a8dcfa0d4a71ec605d27f7c611dcc190024a9 | ff1247fd264c240aebb44dfbc929c091ec975c016bfbcb155940c13932d1439f | PASS — exact Git blob and SHA-256 match |

Both source and backup files were fetched from the new work branch before application-source modification. Source and backup blob SHA-1 values match exactly; SHA-256 was independently computed over the fetched UTF-8 file contents and also matches. No original backup was overwritten.
