# Backup Manifest — Penghapusan Import XLSX Soal Web Guru
Tanggal: 23 November 2026
Repository: `zane0184/cbt_november`
Branch: `work/web-guru-remove-xlsx-20261123`
Baseline source: `work/web-guru-phase1-audit-implementation-20261122`
Baseline HEAD: `5962646bdcf5a2702ef162c43373815483906e5c`

Seluruh snapshot disalin ke GitHub sebelum perubahan source. SHA blob backup dibandingkan dengan SHA blob file baseline melalui Git Trees API.

| File sumber | Blob SHA sumber | Lokasi backup | Blob SHA backup | Hasil |
|---|---|---|---|---|
| `.github/workflows/web-guru-foundation-ci.yml` | `176464d28612e07aaeea6843be282d8a1fb337f3` | `.github/backups/web-guru-remove-xlsx-20261123/.github/workflows/web-guru-foundation-ci.yml.before` | `176464d28612e07aaeea6843be282d8a1fb337f3` | MATCH |
| `backend-laravel/app/Exports/QuestionImport/QuestionImportTemplateExport.php` | `c50746558f7791d873f89f94b50c5ec318d41e7d` | `.github/backups/web-guru-remove-xlsx-20261123/backend-laravel/app/Exports/QuestionImport/QuestionImportTemplateExport.php.before` | `c50746558f7791d873f89f94b50c5ec318d41e7d` | MATCH |
| `backend-laravel/app/Http/Controllers/Teacher/QuestionImportTemplateController.php` | `46e7f26167bc0606f1bfec0826cfd573df1f0708` | `.github/backups/web-guru-remove-xlsx-20261123/backend-laravel/app/Http/Controllers/Teacher/QuestionImportTemplateController.php.before` | `46e7f26167bc0606f1bfec0826cfd573df1f0708` | MATCH |
| `backend-laravel/app/Livewire/Teacher/QuestionImportWizard.php` | `39b924544b014de2bd67775a3f336608473fbc8b` | `.github/backups/web-guru-remove-xlsx-20261123/backend-laravel/app/Livewire/Teacher/QuestionImportWizard.php.before` | `39b924544b014de2bd67775a3f336608473fbc8b` | MATCH |
| `backend-laravel/app/Services/QuestionImport/QuestionImportWorkbookParser.php` | `0c67000bbcc4a4a27d72e1a654f69ba59f4d4c54` | `.github/backups/web-guru-remove-xlsx-20261123/backend-laravel/app/Services/QuestionImport/QuestionImportWorkbookParser.php.before` | `0c67000bbcc4a4a27d72e1a654f69ba59f4d4c54` | MATCH |
| `backend-laravel/resources/views/layouts/teacher.blade.php` | `0ec1ff6088a7e86b49d69925e858b620733e6d0f` | `.github/backups/web-guru-remove-xlsx-20261123/backend-laravel/resources/views/layouts/teacher.blade.php.before` | `0ec1ff6088a7e86b49d69925e858b620733e6d0f` | MATCH |
| `backend-laravel/resources/views/livewire/admin/question-bank.blade.php` | `f6984ca459affee0602578be8cb7dbbee902e08e` | `.github/backups/web-guru-remove-xlsx-20261123/backend-laravel/resources/views/livewire/admin/question-bank.blade.php.before` | `f6984ca459affee0602578be8cb7dbbee902e08e` | MATCH |
| `backend-laravel/resources/views/livewire/teacher/question-import-wizard.blade.php` | `2d5700e5630a617f1bf4a6d2706cf0a045923354` | `.github/backups/web-guru-remove-xlsx-20261123/backend-laravel/resources/views/livewire/teacher/question-import-wizard.blade.php.before` | `2d5700e5630a617f1bf4a6d2706cf0a045923354` | MATCH |
| `backend-laravel/routes/teacher.php` | `1e4eb05d9093a601767de4184e90ce649c113fdd` | `.github/backups/web-guru-remove-xlsx-20261123/backend-laravel/routes/teacher.php.before` | `1e4eb05d9093a601767de4184e90ce649c113fdd` | MATCH |
| `backend-laravel/tests/Feature/QuestionImportTemplateDownloadTest.php` | `c017a5a04221b6a93ca41c16af5397c2c3fe7311` | `.github/backups/web-guru-remove-xlsx-20261123/backend-laravel/tests/Feature/QuestionImportTemplateDownloadTest.php.before` | `c017a5a04221b6a93ca41c16af5397c2c3fe7311` | MATCH |
| `backend-laravel/tests/Feature/QuestionImportTest.php` | `a764edb8fec75a2761a15c748fc03c7d13a27f36` | `.github/backups/web-guru-remove-xlsx-20261123/backend-laravel/tests/Feature/QuestionImportTest.php.before` | `a764edb8fec75a2761a15c748fc03c7d13a27f36` | MATCH |
| `backend-laravel/tests/Feature/QuestionImportWizardTest.php` | `b7948fadf8a9791f7dda4bba075f3402254f06c4` | `.github/backups/web-guru-remove-xlsx-20261123/backend-laravel/tests/Feature/QuestionImportWizardTest.php.before` | `b7948fadf8a9791f7dda4bba075f3402254f06c4` | MATCH |

**Hasil: 12/12 backup byte-exact terverifikasi MATCH.** Tidak ada file sumber yang diubah sebelum manifest ini dibuat.

## Batas penghapusan
- Dihapus: template XLSX khusus soal, controller unduh template, parser workbook, wizard Livewire, view wizard, route wizard/template, link UI, dan test khusus jalur XLSX.
- Dipertahankan: `ExcelQuestionImporter`, `QuestionImportService`, endpoint API `POST /api/v1/questions/import` yang menerima `rows[]`, serta `maatwebsite/excel` / PhpSpreadsheet karena dipakai modul import Guru/Siswa Admin dan ekspor hasil ujian.
- SHORT_ANSWER tetap BLOCKED.
- DOCX belum diimplementasikan; menunggu kontrak Agent 2 lebih rinci.
