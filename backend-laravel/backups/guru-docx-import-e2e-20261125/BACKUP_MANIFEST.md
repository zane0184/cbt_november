# Manifest Backup — Import Soal Guru DOCX
Tanggal: 25 November 2026
Repository: `zane0184/cbt_november`
Branch kerja: `work/guru-docx-import-e2e-20261125`
Baseline rekonstruksi: `3df188b338a03b8513dfeea12d80b1201399be81`

Seluruh backup di bawah disimpan di `backend-laravel/backups/guru-docx-import-e2e-20261125/`. File `.before` berisi salinan UTF-8 dari source sebelum perubahan terkait. File baru (parser, importer, komponen halaman, migration, dan test baru) tidak memerlukan backup karena belum memiliki source sebelumnya.

| Source asli | File backup |
|---|---|
| `.github/workflows/build-web-deployment.yml` | `.github__workflows__build-web-deployment.yml.before` |
| `.github/workflows/docx-import-parser-ci.yml` | `.github__workflows__docx-import-parser-ci.yml.before` |
| `app/Http/Controllers/Api/V1/QuestionController.php` | `backend-laravel__app__Http__Controllers__Api__V1__QuestionController.php.before` |
| `app/Http/Controllers/Api/V1/ExamAnswerController.php` | `backend-laravel__app__Http__Controllers__Api__V1__ExamAnswerController.php.before` |
| `app/Http/Controllers/Api/V1/ExamAttemptController.php` | `backend-laravel__app__Http__Controllers__Api__V1__ExamAttemptController.php.before` |
| `app/Http/Controllers/Api/V1/ExamController.php` | `backend-laravel__app__Http__Controllers__Api__V1__ExamController.php.before` |
| `app/Livewire/Admin/QuestionBank.php` | `backend-laravel__app__Livewire__Admin__QuestionBank.php.before` |
| `app/Models/QuestionVersion.php` | `backend-laravel__app__Models__QuestionVersion.php.before` |
| `app/Services/ExamSnapshotService.php` | `backend-laravel__app__Services__ExamSnapshotService.php.before` |
| `app/Services/InstallerService.php` | `backend-laravel__app__Services__InstallerService.php.before` |
| `app/Services/ScoringService.php` | `backend-laravel__app__Services__ScoringService.php.before` |
| `resources/views/livewire/admin/question-bank.blade.php` | `backend-laravel__resources__views__livewire__admin__question-bank.blade.php.before` |
| `resources/views/livewire/student/exams/show.blade.php` | `backend-laravel__resources__views__livewire__student__exams__show.blade.php.before` |
| `routes/teacher.php` | `backend-laravel__routes__teacher.php.before` |
| `tests/Feature/ExamAnswerTest.php` | `backend-laravel__tests__Feature__ExamAnswerTest.php.before` |
| `tests/Feature/ExamSnapshotTest.php` | `backend-laravel__tests__Feature__ExamSnapshotTest.php.before` |
| `tests/Feature/QuestionBankTest.php` | `backend-laravel__tests__Feature__QuestionBankTest.php.before` |
| `tests/Feature/QuestionImportTest.php` | `backend-laravel__tests__Feature__QuestionImportTest.php.before` |
| `tests/Feature/ScoringEngineTest.php` | `backend-laravel__tests__Feature__ScoringEngineTest.php.before` |
| `app/Livewire/Admin/StudentImport.php` | `backend-laravel__app__Livewire__Admin__StudentImport.php.before` |
| `resources/views/livewire/admin/student-import.blade.php` | `backend-laravel__resources__views__livewire__admin__student-import.blade.php.before` |
| `tests/Feature/StudentImportTest.php` | `backend-laravel__tests__Feature__StudentImportTest.php.before` |

Catatan:
- Tiga file StudentImport terakhir disimpan sebagai backup tambahan; implementasi XLSX yang sudah ada tetap dipertahankan dan tidak ditimpa.
- Backup tidak dimasukkan ke ZIP deployment karena workflow packaging mengecualikan direktori backup.
- Tidak ada file di ZIP baseline asli, database produksi, atau instalasi VPS yang diubah.
