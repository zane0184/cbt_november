# CHECKPOINT WG01-TEMPLATE — 22 NOVEMBER 2026

## Identitas
- Repository: \`zane0184/cbt_november\`
- Branch Agent 3: \`work/web-guru-foundation-20261122\`
- HEAD remote sebelum penambahan laporan/checkpoint tahap template: \`a24eb549a34d9138ee209af62df2397b77aefddb\`
- Source/template latest commit: \`47d1af0c41e4cb86c660e85d87b4380fd44e902c\`
- CI workflow update: \`e000d50cf3408ee525ffe6c6919d43c14c3a8851\`
- Laporan implementasi: \`docs/WG01_TEMPLATE_IMPLEMENTATION_2026-11-22.md\`
- Issue kontrak Agent 2: https://github.com/zane0184/cbt_november/issues/8

## AUDIT
- Remote branch diverged forward by 12 commits from prior checkpoint HEAD \`c4d61feed490244b92370b2c231a3fe521c8203f\`, 0 behind.
- Tidak ada checkout Git lokal di runtime; status working tree lokal tidak diketahui.
- Workspace rekonstruksi lokal dan ZIP baseline tidak ditemukan pada listing runtime ini; remote repository source tetap dapat diperiksa.
- TeacherPortalAccess memvalidasi exact-role GURU, tenant dan satu profil Guru.
- Bank Soal utama tetap disabled; hanya link unduh template ditambahkan.
- Import soal existing tetap JSON rows; backend XLSX/PDF import belum ada.
- Agent 2 belum memberi keputusan kontrak pada saat checkpoint dibuat.

## DESAIN
- Template \`CBT-QUESTION-1\` memiliki enam sheet: Petunjuk, Data Soal, Detail Opsi, Referensi Jenis Soal, Contoh Soal, Referensi Akademik.
- Enam tipe source dikenali ditampilkan. Opsi E tidak didukung.
- SHORT_ANSWER diberi status BLOCKED karena accepted answers storage/snapshot belum terbukti.
- Referensi akademik tidak diisi dengan data asumsi; placeholder menegaskan perlu kontrak backend.
- Kurikulum Merdeka dan KBC/Panca Cinta tetap satu konteks terintegrasi.
- Download melalui route Portal Guru yang dilindungi exact-role access.

## IMPLEMENTASI
File baru:
- \`backend-laravel/app/Exports/QuestionImport/QuestionImportTemplateExport.php\`
- \`backend-laravel/app/Http/Controllers/Teacher/QuestionImportTemplateController.php\`
- \`backend-laravel/tests/Feature/QuestionImportTemplateDownloadTest.php\`

File existing diubah:
- \`backend-laravel/routes/teacher.php\`
- \`backend-laravel/resources/views/layouts/teacher.blade.php\`
- \`.github/workflows/web-guru-foundation-ci.yml\` — filter test diperluas.

Backup:
- \`docs/backup/web-guru-wg01-template-20261122/routes_teacher.php.before\`
- \`docs/backup/web-guru-wg01-template-20261122/teacher.blade.php.before\`
- \`docs/backup/web-guru-wg01-template-20261122/web-guru-foundation-ci.yml.before\`
- Manifest: \`docs/backup/web-guru-wg01-template-20261122/MANIFEST.md\`
- Ketiga blob SHA backup diverifikasi identik dengan source sebelum perubahan.

Koordinasi:
- Issue #8 dibuat dengan 13 kelompok pertanyaan kontrak.
- Tidak ada perubahan pada source Agent 1/2, schema, migration, API backend, QuestionPolicy, importer bersama, batch model/service, Composer dependency, database atau CBT Engine.

## PENGUJIAN
- PASS: remote branch compare dan verifikasi backup.
- NOT RUN: unduh workbook nyata, PHPUnit baru, PHP lint runtime, browser, responsive, accessibility.
- NOT VERIFIED: status CI terbaru; combined status yang terbaca tidak menampilkan status check.
- BLOCKED: upload/preview/correction/confirm, metadata akademik dinamis, accepted answers, PDF/OCR.
- Tidak ada klaim fitur siap pakai.

## STATUS
- WG01-CONTRACT: PENDING Agent 2.
- WG01-TEMPLATE: source implementation written; test/browser/CI verification pending.
- WG01-XLSX-UI: NOT STARTED.
- WG01-XLSX-INTEGRATION: BLOCKED by backend contract.
- WG01-PDF: BLOCKED by parser/OCR design and environment.
- PROCTOR-UI / PROCTOR-SECURITY: NOT STARTED.

## LANGKAH BERIKUTNYA
1. Audit jawaban Agent 2 pada issue #8.
2. Verifikasi CI dan workbook template pada runtime.
3. Lanjutkan UI preview/correction setelah endpoint contract tersedia.
4. Jangan mengubah Agent 1/2 source atau production database.
