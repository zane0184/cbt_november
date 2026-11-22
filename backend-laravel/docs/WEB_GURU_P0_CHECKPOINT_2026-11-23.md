# Checkpoint P0 Web Guru — 23 November 2026

## Source of truth
- Repository: `zane0184/cbt_november`
- Branch: `work/web-guru-phase1-audit-implementation-20261122`
- HEAD source checkpoint: `81eaf6ee179b8ce45f30dd75f384cfc58f6a23b6`
- Commit ini diverifikasi sebagai HEAD branch sebelum checkpoint ditulis.
- Tidak menggunakan ZIP baseline atau Library/Pustaka ChatGPT.
- Tidak ada merge ke main, deployment, atau koneksi ke database produksi.

## AUDIT
P0 menutup dua blocker:
1. Analytics/reporting sebelumnya menghitung semua score FINALIZED sehingga histori regrade/reopen berpotensi ikut masuk statistik.
2. Halaman hasil/analitik belum memeriksa ulang role, tenant, dan policy pada setiap siklus Livewire hydration.

Audit tambahan saat implementasi menemukan:
- Query median ExamAnalytics dan RombelAnalytics masih membawa projection agregat saat memanggil pluck; keduanya diperbaiki.
- Tampilan ResultDetail memiliki penutup directive Blade yang salah; diperbaiki.
- Filter finalized student report belum membandingkan scoring_version score dengan scoring_version pointer; diperbaiki.
- QuestionReport closure tidak menangkap exam; SubjectAnalytics closure tidak menangkap subject; diperbaiki.

## DESAIN
- Satu definisi query current score pada `ExamAttemptScore::scopeCurrent()`, berdasarkan kecocokan current_score_id, attempt_id, tenant_id, dan scoring_version.
- Question score untuk analisis butir diikat ke attempt_id dan scoring_version yang ditunjuk pointer aktif.
- Result surface memakai base Livewire `TeacherResultComponent` untuk reauthorization setelah mount/hydration.
- Role GURU wajib menjadi satu-satunya role persisted; jalur KAMAD yang diizinkan policy tetap dipertahankan.
- SHORT_ANSWER tetap diblokir sesuai keputusan proyek sampai accepted answers authoring, persistence, snapshot, dan scoring tervalidasi. Jangan mengaktifkan hanya karena importer lama memiliki kode untuknya.

## IMPLEMENTASI
Source yang berubah:
- Model: `ExamAttemptScore.php`
- Analytics: `ExamAnalyticsService.php`, `ItemAnalysisService.php`, `StudentAnalyticsService.php`, `RombelAnalyticsService.php`, `SubjectAnalyticsService.php`
- Reporting: `ExamSummaryReportService.php`, `QuestionReportService.php`, `StudentResultReportService.php`
- Security: `ReportPolicy.php`, `ExamAttemptPolicy.php`, komponen baru `TeacherResultComponent.php`
- Livewire: `ResultList.php`, `ResultDetail.php`, `AnalyticsDashboard.php`, `ItemAnalysis.php`
- Blade: `resources/views/livewire/teacher/result-detail.blade.php`
- Tests: `CurrentScoreAnalyticsTest.php`, `TeacherResultAccessTest.php`, fixture `ItemAnalysisTest.php`
- CI: workflow menambahkan test class baru ke suite MySQL 8.

Backup source asli:
`.github/backups/web-guru-p0-20261123/`
Manifest mencatat Git blob SHA source. 16 file awal telah diverifikasi cocok; dua backup tambahan untuk ResultDetail Blade dan StudentResultReportService juga diverifikasi cocok. Tidak ada source asli yang dihapus/ditimpa tanpa backup.

## PENGUJIAN — bukti GitHub Actions
Run: `45061908270`
Commit diuji: `81eaf6ee179b8ce45f30dd75f384cfc58f6a23b6`
Workflow: `web-guru-foundation-ci.yml`
Hasil: **SUCCESS**
- PHPUnit: **59 tests passed, 283 assertions**
- PHP lint: PASS
- Route registration: PASS
- Composer locked dependency validation: PASS
- Disposable MySQL 8.0.46 provisioning and identity verification: PASS
- Database schema migration in disposable test DB: PASS
- InnoDB verification: **80/80 base tables**
- Disposable DB and user cleanup: PASS
- Containers stopped: PASS

Regression coverage includes:
- Historical finalized score vs current pointer score.
- Current score distribution and exam/student/subject/rombel summaries.
- Item analysis and question report scoped to active attempt/scoring version.
- Missing pointer and mismatched scoring version fail-closed.
- Result list/detail/analytics Livewire role revocation during active session.
- Multi-role Guru rejection.
- KAMAD result access preservation.
- Existing teacher foundation, exam-builder, login boundary, and item analysis tests.

Run sebelumnya `45061862919` gagal pada versi sebelum perbaikan projection median Rombel; kegagalan tersebut ditangani, dan run `45061908270` pada commit sesudah perbaikan lulus. Kegagalan tidak disembunyikan.

## STATUS GATE
**P0 SECURITY + CURRENT-SCORE INTEGRITY: PASS pada cakupan regression suite di atas.**

Ini tidak berarti seluruh Portal Web Guru sudah selesai atau siap produksi. Belum dibuktikan:
- Full acceptance untuk seluruh fitur Web Guru.
- XLSX import end-to-end; template saat ini sendiri menyatakan import belum aktif.
- Media authoring UI.
- Pemetaan ATP dan integrasi Pembelajaran Mendalam/delapan dimensi/Panca Cinta secara menyeluruh.
- Semua browser/responsive/manual workflow madrasah.
- Security/stability/load tests menyeluruh.

## NEXT — P1
1. Navigation: beri route name untuk result/detail/analytics/item analysis; aktifkan jalur navigasi dari kartu ujian dan sidebar.
2. Question import: audit dan tetapkan kontrak workbook → parser → preview → validasi tenant/assignment/kurikulum → confirm transaction → audit log. Jangan import otomatis atau mengarang referensi.
3. Tetap blokir SHORT_ANSWER pada API/import/template selectable sampai seluruh rantai accepted-answer lulus.
4. Media UI: integrasikan upload/attach/detach dengan kontrak MediaController, private tenant storage, owner, draft-only, locking, audit, dan snapshot reference safety.
5. Curriculum integration: petakan ATP dan Panca Cinta serta sumber referensi resmi tanpa membuat data kurikulum fiktif; implementasikan hanya setelah struktur source/DB diverifikasi.
6. Tambah acceptance tests setiap alur dan jalankan kembali melalui MySQL 8 GitHub Actions.

Tidak ada perubahan produksi, merge, atau deployment yang dilakukan.
