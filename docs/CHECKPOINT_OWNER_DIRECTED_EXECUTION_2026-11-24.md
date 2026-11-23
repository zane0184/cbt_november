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


### Catatan validasi PHPUnit terisolasi
Branch validasi sementara `work/solo-web-cbt-phpunit-20261124` dibuat dari source HEAD yang sama untuk menghindari antrean runner pada branch eksekusi. Branch ini hanya untuk menjalankan workflow PHPUnit; tidak menggantikan branch eksekusi dan tidak di-merge otomatis.
