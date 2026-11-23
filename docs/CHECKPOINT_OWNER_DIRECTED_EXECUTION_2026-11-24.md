# CHECKPOINT — OWNER-DIRECTED EXECUTION

Tanggal: 24 November 2026 (WIB)
Repository: zane0184/cbt_november
Branch kerja: work/solo-web-cbt-completion-20261123-execution

## Keputusan yang mengikat
- Satu owner, satu eksekutor ChatGPT; tidak ada agent implementasi lain.
- GitHub satu-satunya sumber kebenaran; jangan buka ZIP baseline, Project Files atau Library/Pustaka.
- Fokus Backend + Web Admin + Web Guru + Web Siswa. Android dan Web Super Admin ditunda sampai Juni 2027.
- Satu ZIP generik untuk semua madrasah, instalasi dan database terpisah per madrasah. Tidak ada multi-tenant akademik/data sharing.
- Role aktif hanya ADMIN_MADRASAH, GURU, SISWA. Kamad/Operator dibekukan/dihapus; Proktor hanya menu/penugasan sementara pada Guru.
- Kurikulum Merdeka + KBC satu kesatuan bersama Pembelajaran Mendalam dan 8 dimensi profil lulusan.

## Status aktual yang telah diverifikasi
- Source HEAD yang diperiksa pada branch execution: acc24ed24ea9c37baf1355067235e97af06ca325.
- CI #45228426438 pada exact SHA: 42 failed, 476 passed, 1,863 assertions; full PHPUnit FAIL.
- Focused login/tenant smoke tests pada run tersebut PASS. Ini bukan bukti full suite atau kesiapan produksi.
- Log CI memuat failure fixture academic_year_id, query role_user.tenant_id, serta workflow Guru yang gagal membentuk Exam. Akar masalah harus dikonfirmasi dengan audit source/migration terkait sebelum perubahan.
- Source ExamListContractTest yang diperiksa telah memiliki assignment dan canonical binding; jangan mengulang patch assignment.
- Belum ada browser E2E nyata seluruh portal, UAT, staging VPS, backup/restore acceptance, atau ZIP final siap distribusi.

## Pekerjaan yang dilakukan pada checkpoint ini
- Backup dan pembaruan docs/AGENT_START_HERE.md untuk menghapus ownership multi-agent lama dan menetapkan satu eksekutor.
- Prompt agent pengganti dibuat pada docs/PROMPT_AGENT_PENGGANTI_CBT_2026-11-24.md.
- Laporan audit dibuat pada docs/REPORT_OWNER_DIRECTED_GITHUB_AUDIT_2026-11-24.md.
- Tidak ada perubahan source aplikasi.

## Langkah berikutnya
1. Verifikasi ulang exact HEAD/CI terbaru.
2. P0 backend: triage akar masalah dan remediasi PHPUnit tanpa melemahkan policy.
3. Web Siswa real Laravel E2E.
4. Web Guru end-to-end.
5. Web Admin ADM-01..23.
6. Kurikulum Merdeka + KBC end-to-end, snapshot dan redaction.
7. Fresh install, upgrade, backup/restore, security, stability, UAT.
8. ZIP generik final dan staging/VPS setelah persetujuan.

Status: IN PROGRESS — NOT READY FOR DISTRIBUTION.

## Update remediasi PHPUnit — 24 November 2026
- Backup byte-exact sebelum patch:
  - `backend-laravel/backup_before_patch/P0_CANONICAL_FIXTURE_SCOPE_20261124/CreatesCanonicalCurriculumContext.php.before` (blob asal `cf9b85004f97d0bc8fe16f7d8e62e0ef24849096`, backup commit `1a41208f54363513048e6079affdca4e3f9fc65a`).
  - `backend-laravel/backup_before_patch/P0_CANONICAL_FIXTURE_SCOPE_20261124/Phase7DHistoricalRecalculationAndAnalyticsTest.php.before` (blob asal `8f7abd9652a1c9b8975920e60be20db558f40eea`, backup commit `f8e61b8362ee8e9f27a0cf630ba103c90bd6d959`).
- Perubahan helper: mapping assessment fixture kini mengisi `academic_year_id` dan `semester_id` yang wajib pada MySQL strict mode.
- Perubahan Phase7D: assessment historis menggunakan ulang registration/version assignment yang sudah dipasang saat setup; tidak lagi mengganti composite-FK scope assignment yang telah direferensikan TP/structure mappings.
- Commits source: `8f3dd62f064be8f2940eb3afc30f6a6d2d28fdd4`, `3ada2353b9471b2986a7138665636d458fcca4f9`.
- Belum diuji ulang pada CI. Run lama #45228426438 adalah bukti kegagalan sebelum patch, bukan hasil validasi perubahan.
- Berikutnya: jalankan CI exact HEAD, lanjutkan perbaikan test heartbeat/rate-limit hanya setelah membedakan kekurangan fixture dari regresi proteksi aktual.


## Fokus remediasi PHPUnit — audit pola dan patch tahap berikutnya (24 November 2026)

### Baseline CI terbaru sebelum patch tahap ini
- Run #45236488920 pada SHA `30aa1bfd36ec4a24e689ef0fc88ac9ce7a95f02f`: **26 failed, 492 passed, 2,017 assertions**.
- Ini menggantikan angka 42 failed/476 passed pada run sebelumnya. Angka 41 yang disebut dalam permintaan adalah hitungan tahap terdahulu; audit ini memakai output CI yang dapat diverifikasi.
- Kegagalan yang dikelompokkan dari output tersebut:
  1. 17 kegagalan snapshot/answer-key: `ExamSnapshotService` menolak QuestionVersion yang tidak berstatus PUBLISHED dan parent Question yang belum PUBLISHED; satu jalur snapshot juga menguji assignment yang tidak cocok dengan kontrak konteks.
  2. 1 ExamBuilder endpoint 404: fixture soal/versi soal belum memenuhi status terbit yang diwajibkan snapshot service.
  3. 4 ExamManagementHardening: timestamp startAt pada menit saat ini menjadi masa lalu saat validasi detik; validasi rombel belum eksplisit dan nilai ALL tidak ditolak.
  4. 1 Phase7D reopen: test memakai ADMIN untuk aksi reopen skor, bertentangan dengan batas kewenangan Guru.
  5. 2 RateLimit/heartbeat: route throttle bawaan berbasis IP membuat pengguna di belakang IP yang sama berbagi kuota; assertion timestamp MySQL presisi detik tidak memajukan waktu.
  6. 1 TeacherPracticeWorkflow: startAt dibentuk pada menit saat ini sehingga validasi after_or_equal:now gagal dan exam tidak pernah dibuat.

### Patch kode aplikasi
- `ExamManagement.php`: menolak `selectedRombelId=ALL`, mewajibkan UUID Rombel, dan memastikan Rombel yang dipilih sama dengan Rombel pada assignment aktif. Ini perbaikan batas akses, bukan sekadar pelonggaran test.
- `AppServiceProvider.php` dan `routes/api.php`: limiter heartbeat bernama `exam-heartbeat` memakai key tenant+user (fallback IP hanya bila tidak ada user terautentikasi), 30 permintaan/menit per pengguna. Menghindari kuota global per-IP/NAT.

### Patch fixture/test
- ExamBuilder, ExamSnapshot, AnswerKeyLeakage: status Question dan QuestionVersion yang memang menjadi sumber snapshot kini dinyatakan PUBLISHED; fixture yang menguji versi nonpublished tetap dipertahankan sesuai tujuan tes.
- ExamManagementHardening dan TeacherPracticeWorkflow: jadwal ujian valid dipindah ke waktu mendatang.
- Phase7D: aksi reopen yang diharapkan berhasil dijalankan sebagai Guru, bukan Admin, sesuai ExamAttemptPolicy dan RolePermissionBootstrapper.
- RateLimiting: waktu uji dimajukan satu detik sebelum assertion mutasi last_activity_at agar sesuai presisi timestamp MySQL.

### Backup
- Backup byte-exact untuk sembilan file sebelum patch tersedia di `backend-laravel/backup_before_patch/P0_PHPUNIT_26_FAILURES_20261124/`.
- Backup sebelumnya untuk fixture kanonis dan ExamManagement pivot tetap disimpan di folder backup P0 masing-masing.
- Semua patch hanya pada branch eksekusi; tidak ada migration, database produksi, VPS, atau PR merge.

### Status pengujian
- Patch tahap ini belum boleh dinyatakan lulus sampai satu run PHPUnit lengkap pada HEAD terbaru selesai. Run lama #45238343063 berasal dari SHA sebelum patch lengkap dan tidak boleh dipakai sebagai bukti final.
- Langkah berikut: validasi full suite pada HEAD terbaru, ambil semua stack trace baru, kelompokkan ulang, lalu perbaiki akar masalah sampai run penuh hijau.


## Fokus PHPUnit — hasil akhir terverifikasi (24 November 2026)

- Permintaan fokus: kelompokkan seluruh failure baseline, pisahkan bug aplikasi dari fixture/test, dan perbaiki sampai full PHPUnit hijau.
- Baseline yang dihitung dari job log #45228426438: **42 failed, 476 passed (1,863 assertions)**. Log berisi 42 baris FAILED, bukan 41.
- Taksonomi rinci, kategori akar masalah, pemisahan bug aplikasi/test fixture, daftar file, backup dan commit tercatat pada `docs/REPORT_PHPUNIT_FAILURE_TAXONOMY_AND_GREEN_2026-11-24.md`.
- Final validation: [GitHub Actions #45243430635](https://github.com/zane0184/cbt_november/actions/runs/45243430635), exact SHA `9e5a2697ad29e2aaa15d2b7522b7b44a59c5eb51`.
- Environment: PHP 8.3 + disposable MySQL 8.0/InnoDB.
- Focused Web login/tenant smoke: **27 passed (90 assertions)**.
- Full PHPUnit: **518 passed (2,177 assertions), 0 failed**.
- Perbaikan kode aplikasi terbatas pada query pivot `role_user`, validasi batas Rombel Guru, dan isolasi rate limit heartbeat per user/credential. Penyesuaian selebihnya berupa fixture/helper/test sesuai kontrak akademik, snapshot, jadwal, auth token dan fencing.
- Tidak ada perubahan production DB/migration, deployment VPS, merge PR atau pekerjaan di luar remediasi PHPUnit.
- Status fokus: **SELESAI — FULL PHPUNIT HIJAU pada SHA di atas**. Revalidasi wajib jika source berubah setelah SHA tersebut.


## Handover agent pengganti — 24 November 2026
- Laporan handover lengkap: [HANDOVER_AGENT_BARU_LANJUTKAN_BUKAN_MENGULANG_2026-11-24.md](HANDOVER_AGENT_BARU_LANJUTKAN_BUKAN_MENGULANG_2026-11-24.md).
- Prompt agent diperbarui: [PROMPT_AGENT_PENGGANTI_CBT_2026-11-24.md](PROMPT_AGENT_PENGGANTI_CBT_2026-11-24.md).
- Entry point diperbarui: [AGENT_START_HERE.md](AGENT_START_HERE.md).
- Pesan utama: agent baru wajib meneruskan pekerjaan tersisa, bukan mengulang. Full PHPUnit tetap CLOSED/GREEN pada SHA `9e5a2697ad29e2aaa15d2b7522b7b44a59c5eb51`, CI #45243430635 (518 passed, 2,177 assertions, 0 failed). Jangan mengulang triage/patch/full suite tanpa pemicu sah.
- Branch kerja saat handover dimulai: `work/solo-web-cbt-completion-20261123-execution`; HEAD terverifikasi sebelum pembaruan dokumen: `a8ad92d9a4319f87827291b6cc02419aab964db9`. Pembaruan dokumen ini menghasilkan commit baru; agent berikutnya wajib membaca HEAD terbaru dan tidak menganggap SHA lama sebagai HEAD aktif.
- CI #45248980063 untuk commit dokumentasi sebelumnya cancelled karena tersupersesi; bukan regresi source. Baseline source green tetap dibuktikan oleh CI #45243430635.
- Fokus lanjutan: Issue #20 ADM-01..ADM-23 berdasarkan source aktual, kemudian Web Siswa E2E, Web Guru E2E, kurikulum dan acceptance gates. Tidak ada merge/deploy/migration produksi tanpa approval owner.


## Update koordinasi W1 — 24 November 2026

### Source dan status branch
- Branch: `work/solo-web-cbt-completion-20261123-execution`
- Exact HEAD saat addendum ini disusun: `413fc1f017e4d515a9f373fa99683a521cf31f17`.
- Backup checkpoint sebelum update tersimpan di `docs/backup/CHECKPOINT_OWNER_DIRECTED_EXECUTION_2026-11-24.md.before-w1-update`; source blob `ff4510b58235511285b2d305eb73567ef8d0190a`, backup telah diverifikasi identik.
- Laporan W1: [REPORT_W1_ADMIN_TEACHER_LIVEWIRE_REAUTH_2026-11-24.md](REPORT_W1_ADMIN_TEACHER_LIVEWIRE_REAUTH_2026-11-24.md).
- Matriks ADM diperbarui: [MATRIKS_ADM_01_23_AUDIT_2026-11-23.md](MATRIKS_ADM_01_23_AUDIT_2026-11-23.md).
- Issue koordinasi #20 tetap OPEN; update report diposting sebagai komentar GitHub #7112420074.

### AUDIT
- Empat halaman Guru hasil/analitik dan beberapa komponen Admin memerlukan reauthorization pada setiap render/request Livewire.
- Penugasan Proktor masih BLOCKED karena canonical central exam eligibility marker belum tersedia. `EXTERNAL` bukan bukti ujian pusat.
- Web Siswa source terkini sudah memiliki IndexedDB queue/receipt/conflict/submit barrier dan media authorization yang lebih lengkap daripada audit spesifikasi lamanya, tetapi browser E2E masih NOT RUN.
- ADM-01..ADM-23 tetap NOT VERIFIED secara end-to-end. ADM-20 tetap BLOCKED.

### IMPLEMENTASI dan backup
- Admin reauthorization: Dashboard, UserManagement, AcademicManagement, CurriculumManagement, ProctorAssignmentManagement callback.
- Guru reauthorization: ResultList, ResultDetail, AnalyticsDashboard, ItemAnalysis.
- Regression tests: `AdminLivewireReauthorizationTest` (5), `TeacherResultLivewireReauthorizationTest` (4).
- Fix directive Blade pada `result-detail.blade.php`: `@error` penutup salah menjadi `@enderror`.
- Backup byte-exact untuk source sebelum patch tersedia di `backend-laravel/docs/backup/livewire-admin-render-reauth-2026-11-24/` dan `backend-laravel/docs/backup/teacher-result-render-reauth-2026-11-24/`.
- Tidak ada migration atau mutasi database, tidak ada VPS/deployment, tidak ada merge PR, Android, atau Super Admin.

### PENGUJIAN
1. Run #45251932629, exact SHA `b4592f8a93fd4d306cdab5105c244ba9c6a37eb0`: focused Admin 5/5 PASS; full PHPUnit disposable MySQL 8/InnoDB **523 passed, 2,182 assertions, 0 failed**.
2. Run #45254047440, SHA `1c0b72260eb0c19d36fb48fddd092bc58a649e53`: focused Guru gagal 2/4 karena Blade syntax error pada ResultDetail; tidak ada full-suite run.
3. Run #45254396061 pada SHA `cbce0df331784ecfbef50c4da29c3e781a03d7d7` dibatalkan oleh `cancel-in-progress` setelah perubahan docs; tidak dianggap hasil test.
4. Run terbaru #45254547688 pada exact HEAD `413fc1f017e4d515a9f373fa99683a521cf31f17` masih IN PROGRESS ketika addendum dibuat; focused login, Admin, Guru dan full suite belum selesai. Status ini wajib diperiksa ulang sebelum klaim Guru lulus.

### Berikutnya
1. Selesaikan CI #45254547688 pada exact HEAD; baca focused Teacher logs dan full suite result.
2. Lanjutkan W2–W8 dari issue #20 dan master spec, satu tahap dengan backup, test serta report.
3. Tutup canonical curriculum/KBC, QuestionVersion/ExamSnapshot provenance, DOCX import contract, Student browser E2E dan Guru acceptance.
4. Selesaikan fresh install, upgrade, backup/restore, security, stability dan UAT sebelum ZIP generik final.
5. Deployment aaPanel/VPS hanya setelah staging gate dan persetujuan owner.

Status proyek tetap **IN PROGRESS — NOT READY FOR DISTRIBUTION / NOT READY FOR PRODUCTION**.
