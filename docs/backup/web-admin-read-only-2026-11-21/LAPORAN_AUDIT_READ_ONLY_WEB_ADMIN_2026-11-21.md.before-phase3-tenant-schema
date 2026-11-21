# LAPORAN AUDIT READ-ONLY WEB ADMIN CBT MADRASAH

- Tanggal pemeriksaan: 21 November 2026 (WIB)
- Repository: `zane0184/cbt_november`
- Branch dokumentasi: `docs/canonical-reconciliation-admin-guru-20261121`
- Basis source: `work/web-admin-w0-20261121`, HEAD terakhir terdokumentasi `2b4321ce032fd5e67cf022da8564a5287b8cef6f`
- Source pin/recovery: `7fc45bf71ac1ebd375eba44fd57c983bf18d66e0`
- PR #5 source HEAD: `ade5daa6cf853c164c4f51ec6e25ca68ce40b4f2`
- Status: **AUDIT AWAL — BELUM MENYELURUH; READ-ONLY**
- Commit dokumentasi publikasi: akan dicatat pada checkpoint setelah commit dibuat.

## 1. Ringkasan eksekutif

Audit awal menemukan pelanggaran P0 pada batas kewenangan Admin Madrasah: source mengizinkan akses ke konten Bank Soal, media soal, token ujian, monitoring, roster, attempt, hasil/skor, analitik, serta tindakan close/finalize/reopen yang seharusnya merupakan operasi Guru. Temuan ini berasal dari source snapshot W0 yang diverifikasi. Audit belum selesai menyeluruh: seluruh komponen, query User, relasi, ekspor, cache, job, route binding, dan pemetaan T-001–T-173 masih harus diperiksa.

Tidak ada patch atau perubahan source. Implementasi tetap dilarang sampai audit lengkap, patch plan final, dan persetujuan eksplisit pengguna.

## 2. AUDIT

### 2.1 Provenance dan integritas source

- Repository: `zane0184/cbt_november`.
- Branch source: `work/web-admin-w0-20261121`; HEAD terakhir yang tercatat pada checkpoint W0 adalah `2b4321ce032fd5e67cf022da8564a5287b8cef6f`. Nilai ini adalah HEAD terdokumentasi, bukan klaim ref live terbaru.
- Source pin: `recovery/source-preservation-20261121` @ `7fc45bf71ac1ebd375eba44fd57c983bf18d66e0`.
- PR #5 source HEAD: `ade5daa6cf853c164c4f51ec6e25ca68ce40b4f2`; PR #5/#6/#7 terakhir tercatat OPEN, Draft, Unmerged dan tidak disentuh.
- Compare PR #5 → source pin: 7 ahead, 0 behind; 5 path docs berbeda.
- Compare source pin → W0: 18 ahead, 0 behind; 4 path docs/workflow/manifest berbeda. Source aplikasi pada W0 sama dengan source aplikasi di PR #5 HEAD.
- Artifact snapshot W0: 531 file. SHA-256 531/531 cocok; missing=0, changed=0, extra=0.
- ZIP artifact SHA-256: `efe94178561b17ccb956f8bcd373d01e28c28a90c42718bfdc2eb4c673cd0bd8`.
- Root snapshot lokal yang diperiksa: `/mnt/data/cbt_audit_webadmin_w0_source/backend-laravel`.

### 2.2 P0 — pelanggaran batas kewenangan Admin

**P0-01 — Konten Bank Soal, opsi, kunci, pembahasan.**  
File: `app/Livewire/Admin/QuestionBank.php`, `app/Policies/QuestionPolicy.php`, `app/Http/Controllers/Api/V1/QuestionController.php`, `app/Models/QuestionOption.php`, `app/Models/QuestionVersion.php`.  
Admin diizinkan melihat Bank Soal melalui UI/policy/permission. API daftar/detail memuat `currentVersion.options`; model option/version tidak menyembunyikan `is_correct`, `score_weight`, `stem`, dan `explanation`. Bertentangan dengan keputusan bahwa Admin tidak boleh membaca konten soal, opsi, kunci, rubrik, pembahasan, maupun media.

**P0-02 — Media dan publikasi soal.**  
File: `app/Http/Controllers/Api/V1/MediaController.php`, `app/Http/Controllers/Api/V1/QuestionPublicationController.php`, `app/Policies/QuestionPolicy.php`.  
`MediaController::index()` mengizinkan Admin untuk daftar media QUESTION; `show()` memakai policy yang juga mengizinkan Admin. Publication index memakai `viewAny` dan memuat versi soal.

**P0-03 — Token ujian melalui serialisasi Exam.**  
File: `app/Http/Controllers/Api/V1/ExamController.php`, `app/Policies/ExamPolicy.php`, `app/Models/Exam.php`.  
Policy view mengizinkan Admin dalam tenant yang sama; index/show mengembalikan model Exam; atribut `token` tidak disembunyikan pada model. Dengan demikian token dapat terekspos melalui list/detail generik meskipun endpoint generate token khusus dibatasi.

**P0-04 — Monitoring, roster, attempt, hasil, skor, analytics dan operasi ujian.**  
File: `app/Policies/ExamPolicy.php`, `app/Policies/ExamAttemptPolicy.php`, `app/Policies/ReportPolicy.php`, `app/Http/Controllers/Api/V1/MonitoringController.php`, `ExamParticipantController.php`, `ReportController.php`, `ScoringController.php`, `AnalyticsController.php`.  
Grant/policy Admin memungkinkan monitor/close, akses peserta dan attempt, report/export/analytics, score/finalize/reopen. Ini pelanggaran same-tenant privilege boundary, bukan bukti eksploitasi lintas tenant. Admin hanya boleh menerima metadata ujian minimum untuk assignment Proktor.

### 2.3 P1 — role, Proktor, tenancy, audit

1. **Role runtime legacy.** `DatabaseSeeder`, `InstallerService`, `RolePermissionBootstrapper`, `LoginIdentityResolver`, `AuthWebController`, routes dan dashboard masih memuat OPERATOR/ADMIN/PROKTOR. Grant Admin masih mencakup question.view, exam.manage/assign/monitor/close/token/grade/reopen, report.view dan permission CBT lain. Keputusan final: ADMIN_MADRASAH satu-satunya role aktif Admin; ADMIN dan OPERATOR bukan alias runtime; Proktor bukan role/portal permanen. Akun legacy harus dipertahankan dan direkonsiliasi terpisah, tanpa delete/merge.
2. **Model assignment Proktor lama.** `ProctorAssignmentManagement.php` dan `ExamProctorAssignmentService.php` mencari role PROKTOR serta menulis status ACTIVE. Source aktif belum memuat CONFIRMED/NEEDS_REVIEW, rombel_id, schedule_revision, TeacherProfile/active Guru validation dan overlap guard lintas assignment. Migration memiliki beberapa composite tenant FK yang positif, tetapi unique exam/proctor bukan overlap guard.
3. **Schedule service bersama belum ditemukan.** Tidak ditemukan `ExamScheduleChangeService`, `schedule_revision`, atau `NEEDS_REVIEW` pada source aktif. `ExamController::update()` dan `ExamManagement::saveExam()` mengubah jadwal langsung. Web Admin hanya mendefinisikan kontrak integrasi; Web Guru tidak dikembangkan.
4. **Tenant hardening.** `User` tidak memakai `TenantScoped` global scope. `IdentifyTenant` mengambil tenant dari user terautentikasi dan mengabaikan `X-Tenant-Override` (positif), tetapi request user tanpa tenant belum terlihat fail-closed. Ditemukan 36 pemanggilan `withoutGlobalScopes()` di 10 file aktif yang perlu di-review/allowlist. Belum ada bukti eksploitasi lintas tenant dari jalur report yang diperiksa; tetap wajib negative route/export tests.
5. **Audit log.** `AuditLog` belum terbukti append-only secara teknis. FK tenant/user memakai SET NULL sehingga hard delete dapat menghilangkan atribusi. Reason/correlation ID dan konsistensi transaksi belum seragam.
6. **Installation identity.** `InstallationIdentityService::getOrCreate()` belum membandingkan registered_domain existing dengan host APP_URL pada pemanggilan berikutnya.

### 2.4 P2 — kelengkapan modul dan UI

- Matriks 23 modul ADM-001–ADM-023 masih mayoritas PARTIAL/OPEN.
- Import master Guru/Siswa/Mapel dan Mulok belum ditemukan; importer yang ditemukan terutama bank soal.
- Modul alumni/lulusan, master Panca Cinta/DPL, backup/restore Admin, profil/preferensi/notifikasi belum terverifikasi/tersedia.
- CurriculumManagement terutama CP/TP; ATP belum lengkap. Source mencantumkan MERDEKA/KBC/K13/CUSTOM sebagai kode kurikulum; requirement W6 menempatkan Kurmer sebagai kerangka dan KBC terintegrasi, bukan kurikulum tandingan. Jangan mengubah W6.
- Layout masih mengandung label Panel Admin & Guru, dashboard Operator, sidebar desktop tanpa nav scroll terpisah; mobile drawer setara belum ditemukan.

## 3. DESAIN — patch plan awal (BELUM DISETUJUI)

- **W1-A Tenant hardening:** audit semua query/relasi/route binding/exports/jobs/cache dan `withoutGlobalScopes()`; desain User scope dan middleware fail-closed dengan pengecualian login/installer yang terdokumentasi. Jangan migrasi akun.
- **W1-B Admin boundary:** cabut akses Admin pada Question/Media/Publication/Exam token/monitor/roster/attempt/score/report/analytics/close/finalize/reopen; sediakan DTO metadata minimum khusus assignment. Uji negative direct API dan positive regression Guru.
- **W1-C Role legacy:** hanya ADMIN_MADRASAH runtime; ADMIN/OPERATOR/PROKTOR bukan alias. Inventaris dan rencana migrasi akun terpisah dengan pelestarian ID, hash, tenant, profil, relasi, histori dan audit.
- **W2 Proktor:** Guru aktif + TeacherProfile; status PENDING, CONFIRMED, NEEDS_REVIEW, REVOKED, EXPIRED, COMPLETED; hanya CONFIRMED memberi capability setelah validasi tenant/exam/rombel/schedule revision/permission/overlap/server time. Reschedule harus melalui kontrak bersama pemilik alur Guru; jangan mengubah Web Guru.
- **W3+:** audit append-only, master administrasi/import, histori akademik, laporan admin, backup/restore, preferensi dan UI responsif.
- Setiap patch kelak harus punya per-file backup/hash, kompatibilitas, dampak DB, test, rollback dan acceptance. Tidak ada migration produksi.

## 4. IMPLEMENTASI

**NOT STARTED.** Tidak ada source, database, migration, seeder, .env, konfigurasi, PR, branch source, W6, Android atau deployment yang diubah. Tidak ada Web Guru yang dikembangkan.

## 5. PENGUJIAN — bukti aktual dan batas

- PHP lint: 338 file PHP aktif (non-backup/non-vendor) PASS, 0 syntax error.
- PHP CLI lokal: 8.4.24; Composer tidak tersedia; vendor/autoload.php dan .env tidak ada; PDO driver tidak tersedia. Laravel boot, PHPUnit lokal, endpoint HTTP lokal dan MySQL concurrency tambahan: NOT RUN/BLOCKED.
- GitHub CI run #692 pada source commit `ade5daa6cf853c164c4f51ec6e25ca68ce40b4f2`: PHP 8.3.37, Composer 2.11.2, MySQL 8.0.46; 480 tests passed, 1,942 assertions; focused tenant/auth 23 passed, 83 assertions. Terdapat test dua koneksi MySQL untuk serialisasi publish/snapshot.
- W0 restore workflow #2: 531 expected/restored; missing/extra/changed=0; PASS.
- CI lama bukan bukti acceptance final. Test masih mengasumsikan Admin mengakses question/exam, login Operator/Proktor, assignment PROKTOR/ACTIVE. T-001–T-173 belum dipetakan satu per satu; tidak boleh ditandai PASS.
- Browser/UAT, direct endpoint denial Admin, overlap/multi-assignment, integrasi Guru, sync/recovery: NOT RUN.

## 6. STATUS DAN GERBANG

Audit source kritis awal selesai; audit menyeluruh path-by-path, semua query User, route binding, export/cache/job, pemetaan 23 modul dan 173 test masih OPEN. Patch plan di atas masih awal. W0 belum ditutup; W1 belum dimulai. Tidak ada klaim aman/siap produksi. Setelah audit lengkap dan patch plan final, berhenti menunggu persetujuan eksplisit pengguna.

## 7. Referensi source

Semua path di atas relatif terhadap `backend-laravel/` pada source snapshot W0. Snapshot root: `/mnt/data/cbt_audit_webadmin_w0_source/backend-laravel`. Manifest integritas: 531 entri; ZIP SHA-256 dicatat pada §2.1. Laporan rinci awal lokal yang menjadi bahan penerbitan: SHA-256 `6cdf47db1897aa7dc5976c5f4df2798054ef1834fe1ef9911f6e40bccdfb47f2`.

## 8. Riwayat publikasi GitHub

Dokumen ini diterbitkan sebagai file baru pada branch dokumentasi yang tercantum di atas. Backup publikasi disimpan terpisah sebelum file resmi dibuat. Commit dan blob SHA publikasi harus dicatat dalam checkpoint setelah verifikasi GitHub. Tidak ada PR dibuat/diubah/di-merge.


---

## 9. ADDENDUM AUDIT PHASE 2 — Web Admin UI dan API akademik (21 November 2026)

### 9.1 P0-05 — Halaman Admin Ujian menampilkan token dan data operasional

Bukti:
- `routes/web.php`: route `GET /admin/exams` dipetakan ke `App\\Livewire\\Admin\\ExamManagement` dalam middleware `auth, tenant, license`.
- `app/Livewire/Admin/ExamManagement.php`: `mount()` menerima role `ADMIN_MADRASAH` maupun `GURU`; `render()` memuat `assessment.subject`, `participants`, `attempts`, dan `snapshots` untuk daftar ujian.
- `resources/views/livewire/admin/exam-management.blade.php`: menampilkan jumlah snapshot (baris 74), jumlah peserta (baris 77), dan token ujian aktual untuk ujian PUBLISHED/locked (baris 87–101). Tautan Monitoring, Hasil & Finalisasi, dan Analisis ditampilkan pada baris 104–115.
- `app/Policies/ExamPolicy.php`: `viewAny()` mengizinkan ADMIN_MADRASAH; `view()` juga mengizinkan Admin tenant yang sama. Policy `monitor()` memberi akses Admin jika memiliki `exam.monitor`.

Kesimpulan: ini bukan sekadar metadata minimum untuk penugasan Proktor. Halaman Admin menampilkan token ujian dan statistik roster/snapshot serta memberi tautan ke jalur operasional. Ini menguatkan P0-03/P0-04 dan merupakan jalur UI langsung yang perlu negative test. `ExamPolicy::create/update` memang Guru-only; temuan ini khusus akses baca/list UI, bukan klaim Admin dapat membuat atau mengubah ujian lewat Gate tersebut.

### 9.2 P1-07 — API master kurikulum tidak menerapkan authorization pada beberapa resource

Bukti:
- `routes/api.php` baris 27–36 menempatkan seluruh `apiResource` kurikulum di dalam middleware umum `auth:sanctum, tenant, license`, tanpa middleware role/permission per-resource.
- `app/Http/Controllers/Api/V1/ElementController.php`: index/store/show/update/destroy tidak memanggil policy/Gate/permission check.
- `GradeLevelController.php`: seluruh CRUD tidak memanggil authorization.
- `LearningObjectiveController.php`: seluruh CRUD tidak memanggil authorization.
- `LearningPathController.php`: seluruh CRUD tidak memanggil authorization.
- `MaterialController.php`: seluruh CRUD tidak memanggil authorization.
- `TagController.php`: index/store/destroy tidak memanggil authorization; store/destroy juga tidak menulis audit event.
- `LearningOutcomeController.php`: store memakai `StoreLearningOutcomeRequest::authorize()` dengan `learning_outcome.manage`, tetapi index/show/update/destroy tidak memanggil policy. Dengan demikian kontrol store tidak melindungi operasi lain.
- Model-model Element, GradeLevel, LearningObjective, LearningOutcome, LearningPath, Material dan Tag memakai `TenantScoped`, sehingga scope tenant membantu membatasi record; tetapi scope tenant bukan pengganti role/permission authorization.

Dampak: setiap identitas yang lolos middleware autentikasi tenant berpotensi membaca/mengubah/menghapus data master akademik melalui endpoint tersebut, tergantung endpoint/model. Ini adalah temuan authorization/data-integrity dalam tenant; belum merupakan bukti akses lintas tenant. Prioritas P1 sampai test HTTP role-negatif dan policy review membuktikan batas aktual.

### 9.3 P1-08 — Alias role ADMIN masih dipakai pada komponen administrasi akademik

Bukti:
- `app/Livewire/Admin/AcademicManagement.php` memeriksa `roles()->whereIn('name', ['ADMIN_MADRASAH', 'ADMIN'])`.
- `app/Livewire/Admin/CurriculumManagement.php` memakai pemeriksaan yang sama pada operasi manage; `mount()` hanya memeriksa permission `curriculum.view`.
- Ini bertentangan dengan keputusan final bahwa `ADMIN_MADRASAH` satu-satunya role Admin aktif dan `ADMIN` bukan alias runtime. Perlu audit grant/role dan test legacy-account denial; jangan menghapus atau menggabungkan akun legacy.

### 9.4 PENGUJIAN statis tambahan

- Daftar route Web Admin dan API dibaca langsung dari `routes/web.php` dan `routes/api.php`.
- 14 Livewire components, 29 controller files, 11 policy files, dan 56 model files ditemukan pada snapshot source root (jumlah file kelas per direktori, bukan jumlah seluruh fitur).
- Pemeriksaan authorization statis menemukan resource curriculum yang tidak memanggil policy/Gate/permission check sebagaimana dirinci pada P1-07.
- Belum ada HTTP test yang dijalankan untuk membuktikan role Siswa/Guru/Admin ditolak pada endpoint tersebut; status runtime tetap NOT RUN.

### 9.5 Perubahan status audit

Temuan P0-05 memperjelas jalur UI token dan data operasional Admin. P1-07/P1-08 menambah cakupan audit authorization API akademik dan role alias. Audit menyeluruh masih OPEN: seluruh controller, query User/relasi, export, cache, jobs, route binding, ADM-001–ADM-023 dan T-001–T-173 belum selesai dipetakan satu per satu.
