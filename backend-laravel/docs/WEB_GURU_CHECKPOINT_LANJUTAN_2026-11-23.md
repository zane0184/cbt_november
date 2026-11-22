# Checkpoint Lanjutan Portal Web Guru — 23 November 2026

## Source dan branch
- Repository: `zane0184/cbt_november`
- Branch aktif: `work/web-guru-phase1-audit-implementation-20261122`
- HEAD checkpoint: `84cbdf4b44272650631d273cc1e9ec12c036fafd`
- Verifikasi: branch dibandingkan dengan SHA checkpoint di atas menghasilkan `identical`, 0 ahead, 0 behind.
- Source aplikasi tetap pada commit `b056e8289805cb71f492e9997288a715e460ce70`; commit lanjutan hanya dokumentasi.
- Audit lengkap lanjutan: `backend-laravel/docs/WEB_GURU_AUDIT_LANJUTAN_2026-11-23.md`.

## Status
**AUDIT SOURCE LANJUTAN SELESAI — IMPLEMENTASI PERBAIKAN BELUM DIMULAI.**
Portal Web Guru belum lengkap dan belum boleh dinyatakan siap produksi.

## Temuan prioritas
### P0 — Security hasil dan analytics integrity
1. ResultList, ResultDetail, AnalyticsDashboard, dan ItemAnalysis hanya memeriksa policy saat mount; belum ada reauthorization boundary untuk setiap request Livewire seperti halaman Guru lainnya.
2. Result/report policies tidak menegakkan invariant satu-satunya role GURU yang diterapkan oleh TeacherPortalAccess pada halaman Dashboard, Bank Soal, dan Exam Builder.
3. ExamAnalyticsService, ItemAnalysisService, StudentAnalyticsService, RombelAnalyticsService, SubjectAnalyticsService, dan ExamSummaryReportService mengagregasi score FINALIZED tanpa membatasi pada current score pointer.
4. ItemAnalysisService dan QuestionReportService mengagregasi question score tanpa ikatan attempt_id + scoring_version aktif.
5. QuestionReportService memakai variabel $exam di dalam closure tanpa menangkapnya; SubjectAnalyticsService memakai $subject di dalam closure tanpa menangkapnya. Runtime belum dijalankan untuk mengonfirmasi respons endpoint, tetapi cacat scope variabel tampak pada source.
6. Menu Penilaian & Hasil dan Analytics & Ekspor masih disabled.

### P1 — Kelengkapan Web Guru
1. Pembelajaran Mendalam dan delapan dimensi profil lulusan belum tersedia; pemetaan Panca Cinta dan provenance CP belum lengkap; jadwal pembelajaran belum memiliki model sumber data.
2. Bank Soal belum menyediakan upload media dan import melalui UI Guru. Backend API media/import ada, sedangkan route Web Guru yang diperiksa hanya menyediakan unduhan template.
3. Authoring soal yang diperiksa memetakan CP/TP, belum tampak pemetaan ATP, editor persamaan, atau editor RTL khusus.
4. Belum ada acceptance test end-to-end yang membuktikan seluruh alur create/publish soal, snapshot, peserta, token, jadwal, grading, finalisasi, reopen, analytics, export, dan navigasi.

## Bukti pengujian yang tersedia
- CI run `45057135828` pada commit source `b056e828...`: SUCCESS; bukti terdahulu mencatat 45 tests dan 239 assertions, PHP 8.3, MySQL 8.0.46, 80/80 tabel InnoDB.
- CI tersebut membuktikan fondasi keamanan/data scope, bukan seluruh acceptance Web Guru.
- Tidak ada PHPUnit lokal atau runtime test yang dijalankan pada audit lanjutan ini.
- Tidak ada CI baru pada source karena tidak ada perubahan source aplikasi.

## Implementasi dan keselamatan perubahan
- Tidak ada source, test, migration, konfigurasi, atau database yang diubah.
- Tidak ada file Agent 2/Agent 4/komponen bersama yang diubah.
- Tidak ada merge, deployment, atau migration produksi.
- Audit report dan checkpoint ini adalah file dokumentasi baru. Path target diperiksa tidak ada sebelum dibuat; tidak ada file lama yang ditimpa.
- ZIP baseline dan Library/Pustaka tidak dibuka sesuai batas instruksi pengguna.

## Urutan kerja berikutnya
1. Buat regression tests untuk role tunggal/multi-role, role/permission/assignment revocation, direct URL, dan Livewire hydration pada hasil/analytics.
2. Desain lalu perbaiki current-pointer population untuk seluruh service analytics/reporting tanpa mengubah ExamSnapshotService.
3. Perbaiki closure scope QuestionReportService dan SubjectAnalyticsService.
4. Tambahkan regression tests current vs historical scoring version, multiple attempts, invalid/missing pointer, item score per attempt/version.
5. Setelah P0 lulus, sambungkan navigasi hasil/analytics dan implementasikan kelengkapan UI yang disepakati.
6. Setiap perubahan source: verifikasi HEAD/diff agent lain, baca dependency/test, backup byte-exact dan checksum, patch kecil, jalankan tes terisolasi, lalu update checkpoint.

## Gate
**NOT READY.** Jangan merge, deploy, atau menjalankan migration produksi. Jangan menyatakan Device Lease/fencing, integrasi Android, atau stabilitas produksi siap; seluruhnya berada di gate terpisah.
