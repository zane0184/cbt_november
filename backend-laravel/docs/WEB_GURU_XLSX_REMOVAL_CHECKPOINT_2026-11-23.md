# CHECKPOINT WEB GURU — PENGHENTIAN IMPORT XLSX SOAL
Tanggal: 23 November 2026
Repository: `zane0184/cbt_november`
Branch: `work/web-guru-remove-xlsx-20261123`
Baseline: `work/web-guru-phase1-audit-implementation-20261122`
Baseline SHA: `5962646bdcf5a2702ef162c43373815483906e5c`

## AUDIT
- Keputusan owner 23-11-2026: XLSX/template import soal dihentikan; DOCX menjadi format utama.
- Audit pemanggil membuktikan `ExcelQuestionImporter` bukan parser file XLSX: ia dipakai oleh `QuestionController::import()` untuk menerima payload API `rows[]`, serta test Bank Soal. Karena itu kelas tersebut, interface `QuestionImportService`, endpoint API JSON, dan test generik dipertahankan.
- `maatwebsite/excel` dan PhpSpreadsheet tidak dihapus dari Composer karena masih dipakai oleh template import Guru/Siswa Admin dan ekspor hasil ujian (`ExamResultsExport`).
- XLSX-only yang aman dihapus: template khusus soal, controller download, workbook parser, Livewire wizard, view wizard, route import/template, tombol/link UI, dan test khusus alur XLSX.
- DOCX belum memiliki approval kontrak rinci Agent 2. Catatan Agent 2 yang tersedia hanya mengusulkan DOCX sebagai adapter ke satu `QuestionDraftDTO` dan lifecycle `ImportBatch/ImportBatchRow`; detail final DTO, source locator, parser profile, warning schema, keamanan, retention, idempotency dan media masih NEEDS DESIGN/PENDING APPROVAL.

## BACKUP
- Lokasi: `.github/backups/web-guru-remove-xlsx-20261123/`
- Manifest: `.github/backups/web-guru-remove-xlsx-20261123/MANIFEST.md`
- 12 file yang akan diubah/dihapus telah di-snapshot sebelum perubahan.
- Git blob SHA backup dibandingkan dengan baseline source: **12/12 MATCH**.
- Tidak ada source diubah sebelum backup terverifikasi.

## IMPLEMENTASI
### Dihapus
1. `backend-laravel/app/Exports/QuestionImport/QuestionImportTemplateExport.php`
2. `backend-laravel/app/Http/Controllers/Teacher/QuestionImportTemplateController.php`
3. `backend-laravel/app/Services/QuestionImport/QuestionImportWorkbookParser.php`
4. `backend-laravel/app/Livewire/Teacher/QuestionImportWizard.php`
5. `backend-laravel/resources/views/livewire/teacher/question-import-wizard.blade.php`
6. `backend-laravel/tests/Feature/QuestionImportTemplateDownloadTest.php`
7. `backend-laravel/tests/Feature/QuestionImportWizardTest.php`

### Diubah
- `backend-laravel/routes/teacher.php`: route XLSX wizard/template dan import yang terkait dihapus.
- `backend-laravel/resources/views/livewire/admin/question-bank.blade.php`: tombol Impor XLSX dan Unduh Template dihapus.
- `backend-laravel/resources/views/layouts/teacher.blade.php`: tautan template import soal desktop/mobile dihapus.
- `backend-laravel/tests/Feature/QuestionImportTest.php`: pengujian generik rollback seluruh batch dipindahkan dari test wizard; test API JSON dan SHORT_ANSWER blocked tetap ada.
- `.github/workflows/web-guru-foundation-ci.yml`: branch ini dimasukkan ke trigger; test `QuestionBankTest` ditambahkan untuk menjaga coverage Bank Soal/generic row importer; filter test XLSX-only dihapus.

### Sengaja dipertahankan
- `ExcelQuestionImporter` dan `QuestionImportService`: saat ini digunakan oleh endpoint API JSON `POST /api/v1/questions/import`, bukan untuk membaca file XLSX.
- `QuestionController::import()`, `QuestionImportTest.php`, dan pengujian Bank Soal generik.
- `maatwebsite/excel` / PhpSpreadsheet untuk fitur import data Guru/Siswa Admin dan ekspor hasil ujian.
- SHORT_ANSWER tetap BLOCKED; ESSAY tetap manual review.

## PENGUJIAN
- Belum ada klaim PASS untuk source setelah perubahan.
- Perubahan workflow telah mengaktifkan push trigger untuk branch ini. CI GitHub Actions harus menjalankan PHP lint, route registration, PHPUnit pada disposable MySQL 8, dan pemeriksaan InnoDB.
- Test suite sekarang mencakup `QuestionBankTest` dan `QuestionImportTest` untuk memastikan fungsi row-based/API tidak ikut rusak.
- Browser E2E, DOCX extraction, concurrency, load test, UAT dan production readiness belum dilakukan.

## CHECKPOINT LANJUTAN
1. Verifikasi GitHub Actions pada commit terbaru branch ini; periksa log dan source SHA yang benar.
2. Jika CI gagal, perbaiki hanya kegagalan yang berasal dari perubahan ini; backup ulang file sebelum patch berikutnya.
3. Audit ulang referensi route/class dan pastikan tidak ada link XLSX soal tersisa di source aktif.
4. Minta Agent 2 menetapkan kontrak DOCX lengkap dan ownership/approval. Jangan menambah parser dependency, schema, migration, batch service atau shared service sebelum disetujui.
5. Setelah kontrak DOCX disetujui, implementasikan UI preview/correction/revalidate/confirm/report di Web Guru sesuai DTO/lifecycle bersama.
6. Jangan merge ke main, deployment, atau migration produksi tanpa persetujuan owner.

## STATUS
**Penghapusan jalur XLSX soal pada branch kerja telah diimplementasikan; validasi CI masih PENDING. DOCX belum diimplementasikan. Tidak ada merge atau deployment.**
