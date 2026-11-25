# Indeks Backup — Import Guru dan Kredensial Import
Tanggal: 2026-11-26

Semua salinan di bawah dibuat sebelum file sumber terkait diubah. File baru (service, component, view, migration, dan test TeacherImport) tidak memiliki versi lama pada base sehingga tidak memerlukan backup awal. Backup disimpan di direktori ini.

## Source dan komponen
- `web.php.before` — routes/web.php sebelum route Import Guru.
- `user-management.blade.php.before` — halaman Manajemen Pengguna sebelum tombol Import Guru.
- `TeacherImportService.php.before-one-time-credentials`
- `TeacherImport.php.before-one-time-credentials`
- `teacher-import.blade.php.before-one-time-credentials`
- `StudentImportService.php.before-one-time-credentials`
- `StudentImport.php.before-one-time-credentials`
- `student-import.blade.php.before-one-time-credentials`
- `UserManagement.php.before-one-time-credentials`
- `user-management.blade.php.before-one-time-credentials`
- `UserManagement.php.before-search-and-filter-ux`
- `user-management.blade.php.before-search-and-filter-ux`
- `student-import.blade.php.before-final-credential-docs`
- `teacher-import.blade.php.before-final-credential-docs`
- `StudentImport.php.before-student-template-single-sheet-fix`
- `student-import.blade.php.before-student-template-single-sheet-fix`
- `ExamManagementHardeningTest.php.before-upstream-syntax-sync` — sebelum menyelaraskan koreksi syntax yang sudah ada di source branch.

## Test
- `StudentImportTest.php.before-login-credential-tests`
- `TeacherImportTest.php.before-login-credential-tests`
- `StudentImportTest.php.before-web-login-assertions`
- `TeacherImportTest.php.before-web-login-assertions`
- `StudentImportTest.php.before-student-template-single-sheet-fix`
- `StudentImportTest.php.before-native-api-login-tests`
- `TeacherImportTest.php.before-native-api-login-tests`

## Workflow CI
- `tenant-backend-ci.yml.before-import-ci-trigger`
- `tenant-backend-ci.yml.before-skip-unrelated-benchmark`
- `tenant-backend-ci.yml.before-feature-run-queue`

## Dokumentasi
- `REPORT_IMPLEMENTASI_IMPORT_GURU_2026-11-26.md.before-latest-base-note`
- `CHECKPOINT_IMPORT_GURU_2026-11-26.md.before-latest-base-note`
- `REPORT_IMPLEMENTASI_IMPORT_GURU_2026-11-26.md.before-final-credential-docs`
- `CHECKPOINT_IMPORT_GURU_2026-11-26.md.before-final-credential-docs`
- `ADDITIONAL_BACKUPS.md.before-complete-backup-index` — salinan indeks backup sebelum daftar ini diperluas.

Backup sumber utama juga tercatat di `MANIFEST.md`.

- `REPORT_IMPLEMENTASI_IMPORT_GURU_2026-11-26.md.before-post-merge-update` — laporan sebelum diperbarui setelah PR #27 merged.
- `CHECKPOINT_IMPORT_GURU_2026-11-26.md.before-post-merge-update` — checkpoint sebelum diperbarui setelah PR #27 merged.
- `ADDITIONAL_BACKUPS.md.before-post-merge-update` — indeks backup sebelum penambahan catatan pasca-merge.
