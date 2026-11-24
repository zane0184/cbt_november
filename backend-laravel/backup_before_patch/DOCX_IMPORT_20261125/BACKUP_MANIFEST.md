# Backup manifest — DOCX import 25 November 2026

Branch: `work/docx-import-parser-20261125`
Baseline branch: `work/p1-seven-targets-20261124`
All backups were fetched from the baseline branch before any source patch. Git blob SHA equality was re-verified by fetching both source and backup.

| Source | Baseline blob SHA | Backup path | Backup blob SHA | Verification |
|---|---|---|---|---|
| backend-laravel/app/Services/QuestionImportService.php | 1bdbcec7305b9c4abdfc010492cc7382b42ba352 | backend-laravel/backup_before_patch/DOCX_IMPORT_20261125/QuestionImportService.php.before | 1bdbcec7305b9c4abdfc010492cc7382b42ba352 | MATCH |
| backend-laravel/app/Livewire/Admin/QuestionBank.php | c827e2fafdbec4696f7fbaa6f2cbbce2e3299ee3 | backend-laravel/backup_before_patch/DOCX_IMPORT_20261125/QuestionBank.php.before | c827e2fafdbec4696f7fbaa6f2cbbce2e3299ee3 | MATCH |
| backend-laravel/routes/web.php | 1fca336b285415935558a3ad913972297093c4a2 | backend-laravel/backup_before_patch/DOCX_IMPORT_20261125/web.php.before | 1fca336b285415935558a3ad913972297093c4a2 | MATCH |
| backend-laravel/tests/Feature/QuestionImportTest.php | 8f6faee74c1076dd38f3a7ce4b1f19f046febbb1 | backend-laravel/backup_before_patch/DOCX_IMPORT_20261125/QuestionImportTest.php.before | 8f6faee74c1076dd38f3a7ce4b1f19f046febbb1 | MATCH |

The source files remain unchanged. No production database, migration, deployment, or PR state was changed.
