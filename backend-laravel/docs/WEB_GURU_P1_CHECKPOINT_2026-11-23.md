# Checkpoint P1 Web Guru — 23 November 2026

## Source
- Repository: `zane0184/cbt_november`
- Branch: `work/web-guru-phase1-audit-implementation-20261122`
- HEAD source yang diuji: `f2b88ec88e7dd2a89f26d7a7470302001805aba8`
- Tidak menggunakan ZIP baseline atau Library/Pustaka ChatGPT.
- Tidak ada merge ke main, deployment, atau perubahan database produksi.

## AUDIT
Setelah P0 lulus, audit P1 memastikan:
- Route hasil, detail, analitik, analisis butir, dan ekspor belum semuanya memakai named route.
- Sidebar Guru masih menandai Penilaian & Hasil dan Analytics & Ekspor sebagai disabled.
- QuestionBank Web tidak menyediakan UI import workbook.
- Template import masih menampilkan SHORT_ANSWER sebagai pilihan meskipun jalur accepted-answer belum memenuhi gate produksi.
- API dan importer menerima jenis soal yang belum boleh diaktifkan.

## IMPLEMENTASI
1. Named routes ditambahkan untuk:
   - `teacher.exams.results`
   - `teacher.exams.results.detail`
   - `teacher.exams.analytics`
   - `teacher.exams.analytics.items`
   - `teacher.exams.export.excel`
   - `teacher.exams.export.pdf`
2. Sidebar Guru mengarahkan Penilaian & Hasil dan Analytics & Ekspor ke daftar ujian; daftar ujian menyediakan tautan per ujian.
3. Hasil, detail, analitik, analisis butir, dan ekspor menggunakan named routes, bukan URL hardcoded.
4. SHORT_ANSWER dihapus dari allowed types QuestionController dan ExcelQuestionImporter.
5. Template tidak lagi menawarkan SHORT_ANSWER pada dropdown jenis soal atau ACCEPTED_ANSWER pada dropdown detail. Baris referensi menjelaskan status BLOCKED.
6. Regression test menegaskan endpoint import menolak SHORT_ANSWER.
7. Tidak ada perubahan ke data soal existing atau scoring history.

## BACKUP
- Navigasi: `.github/backups/web-guru-p1-navigation-20261123/`
- Short answer gate/import: `.github/backups/web-guru-p1-short-answer-20261123/`
- P0: `.github/backups/web-guru-p0-20261123/`
- Setiap manifest mencatat Git blob SHA source sebelum patch dan lokasi salinan.
- Tidak ada source yang dihapus.

## PENGUJIAN — GitHub Actions
Run: `45062651829`
Commit diuji: `f2b88ec88e7dd2a89f26d7a7470302001805aba8`
Hasil: **SUCCESS**
- PHPUnit: **68 tests passed, 318 assertions**
- PHP lint: PASS
- Route registration: PASS
- Composer locked dependency validation: PASS
- Disposable MySQL 8.0.46: PASS
- Database migration pada disposable DB: PASS
- InnoDB: **80/80 base tables**
- Cleanup disposable database/user dan stop containers: PASS

Test yang dijalankan termasuk:
- `CurrentScoreAnalyticsTest`
- `ItemAnalysisTest`
- `TeacherResultAccessTest`
- `TeacherResultNavigationTest`
- `QuestionImportTest`
- `QuestionImportTemplateDownloadTest`
- `TeacherPortalFoundationTest`
- `TeacherPracticeWorkflowTest`
- `PortalLoginRoleBoundaryTest`

## STATUS
**P0: PASS. P1 navigation dan SHORT_ANSWER gate: PASS pada cakupan CI di atas.**

Portal Web Guru belum selesai. XLSX import end-to-end tetap belum aktif karena workbook masih memerlukan parser, preview, kontrak referensi akademik, validasi tenant/assignment, dan transaksi konfirmasi. Media upload UI belum tersedia. Mapping ATP/KBC/Pembelajaran Mendalam/delapan dimensi profil lulusan belum terintegrasi menyeluruh.

## NEXT WORK
1. Integrasi mapping CP/TP/ATP/KBC pada question version dengan tenant curriculum registrations dan sejarah mapping yang tidak merusak versi terbit.
2. Tetapkan kontrak workbook import berdasarkan template yang sudah ada, lalu parser → preview → validasi → confirm transaction → audit.
3. Integrasi media authoring menggunakan jalur tenant-private yang sudah ada, dengan validasi owner/question/version.
4. Lengkapi Pembelajaran Mendalam, Panca Cinta, dan delapan dimensi hanya dari referensi resmi/terdaftar; jangan membuat data akademik fiktif.
5. Acceptance test setiap alur pada MySQL 8 GitHub Actions.

Tidak ada migration produksi, merge, atau deployment.
