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


---

## 10. ADDENDUM AUDIT PHASE 3 — tenant-boundary, query, cache, export, schema (21 November 2026)

### 10.1 P1-09 — Foreign key tenant binding belum lengkap pada peserta/jawaban/skor ujian

Bukti migration:
- `database/migrations/2024_01_01_000017_create_exam_participants_table.php`: `exam_participants.user_id` memakai FK tunggal ke `users.id`; composite FK yang ada hanya `(exam_id, tenant_id)` → `exams(id, tenant_id)`.
- `database/migrations/2024_01_01_000020_create_exam_attempt_answers_table.php`: `exam_attempt_answers.student_id` memakai FK tunggal ke `users.id`; composite FK yang ada mengikat exam dan attempt ke tenant, bukan student ke tenant.
- `database/migrations/2024_01_01_000021_create_scoring_tables.php`: `exam_attempt_scores.student_id`, `exam_question_scores.student_id`, `grader_id`, dan `finalized_by` memakai FK tunggal ke users; composite FK mengikat exam/attempt, tetapi tidak mengikat user actor/student ke tenant.
- `database/migrations/2026_11_07_210002_harden_teacher_assignment_tenant_foreign_keys.php` menambahkan unique candidate key `users(id, tenant_id)`, tetapi tidak menambahkan composite FK user-tenant untuk tabel ujian di atas.
- `database/migrations/2026_11_20_100002_harden_device_lease_tenant_constraints.php` memeriksa beberapa mismatch perangkat/attempt, tetapi daftar constraint baru tidak mencakup `exam_participants.user_id + tenant_id` maupun student/grader pada tabel jawaban/skor.

Penilaian: ini adalah gap defense-in-depth integritas referensial tenant. Jalur aplikasi yang ditinjau memiliki beberapa validasi tenant eksplisit (misalnya ExamParticipantController), sehingga belum ada bukti bahwa cross-tenant row telah berhasil dibuat atau dieksploitasi. Namun database belum menolak seluruh kombinasi user-tenant yang tidak cocok pada relasi peserta/jawaban/skor. Perlu inventaris data disposable dan rancangan migration terpisah; **tidak boleh dijalankan pada database produksi** tanpa preflight, backup, persetujuan dan rencana rollback yang disetujui.

### 10.2 Review User query dan withoutGlobalScopes — hasil sementara

- `User` tidak memakai `TenantScoped`; hook `creating` memaksa tenant context dan username unik per tenant, tetapi tidak membatasi query baca global secara default.
- Direct query User yang diperiksa pada ReportController, ExamParticipantController, ExamProctorAssignmentService, Admin UserManagement dan ProctorAssignmentManagement menggunakan filter tenant eksplisit.
- `LoginIdentityResolver` menggunakan `withoutGlobalScopes()`, tetapi query User/TeacherProfile/StudentProfile dan final user lookup difilter dengan tenant ID dari satu installation identity; kandidat harus unik dan role divalidasi.
- `InstallerService` menggunakan unscoped lookup username/email saat recovery installer; postcondition mensyaratkan admin.tenant_id = tenant.id, nama/kode/username cocok dan password hash cocok. Ini jalur bootstrap khusus, tetap perlu test recovery conflict.
- `EnsureLicenseActive` memakai unscoped ExamAttempt pada recovery attempt; jalur yang diperiksa memvalidasi tenant terhadap installation/user, exam ID pada path, status/expiry, serta identitas peserta. Satu cabang perlu negative test relasi participant tenant mismatch.
- `CleanupOrphanedMediaCommand` memakai unscoped query untuk soft-deleted media, tetapi setiap row diikat ulang dengan tenant_id, lock, pemeriksaan referensi dan path tenant-safe sebelum file/row dihapus.
- `LicenseStatusController` membatasi lookup identity berdasarkan tenant; LocalLicenseState dicari berdasarkan identity yang sudah dipastikan.
- Tidak ditemukan `Route::bind`, `Route::model`, atau custom `resolveRouteBinding` di source app/routes yang diperiksa. Implicit binding menggunakan query model; untuk model TenantScoped scope fail-closed, tetapi User tidak memiliki scope tersebut.
- Hasil sementara: tidak ada direct cross-tenant User query yang terkonfirmasi pada daftar query eksplisit yang diperiksa. Risiko defense-in-depth tetap OPEN karena User tidak scoped dan relasi User tidak otomatis membuktikan tenant pasangan.

### 10.3 Cache, export, dan jobs

- `AnalyticsController` membentuk cache key dengan prefix tenant ID untuk summary/distribution/item/rombel analytics; `AnalyticsCacheService` menghapus key dengan tenant ID.
- Export yang ditemukan: `app/Exports/ExamResultsExport.php`. Caller pada `ReportController` menjalankan authorization `viewExamResults` dan membentuk hasil melalui report service sebelum export; tetap perlu test export negative Admin dan tenant mismatch.
- Tidak ditemukan class di `app/Jobs` pada snapshot yang diperiksa.
- Cache key tenant namespace adalah kontrol positif, bukan pengganti policy authorization atau pengujian invalidation.

### 10.4 PENGUJIAN statis dan inventaris test

- Ditemukan 64 file `*Test.php` di `tests/Feature` dan `tests/Unit`.
- Scan nama method konvensional `test_*` / `test*` menghasilkan 433 deklarasi; angka ini adalah hasil grep statis, bukan jumlah test PHPUnit yang dieksekusi.
- CI #692 tetap mencatat 480 test passed / 1,942 assertions pada commit lama; angka tersebut tidak boleh dianggap menjalankan T-001–T-173.
- Belum ada mapping satu-per-satu T-001–T-173 ↔ test file/method/evidence run. Seluruh acceptance matrix tetap NOT VERIFIED sampai mapping dan run evidence dibuat.

### 10.5 Status temuan tambahan

- P1-09: OPEN — composite FK user-tenant untuk participant/answer/score belum lengkap.
- Review User query: PARTIAL — explicit tenant filters ditemukan di query utama; User model global scope masih tidak ada; route/relationship negative tests belum dijalankan.
- Cache/export/jobs: PARTIAL — tenant namespacing dan authorization caller terlihat; negative HTTP/export tests belum dijalankan; tidak ada job class ditemukan.
- Test inventory: 64 test files, 433 conventional method declarations (static count); T-001–T-173 mapping belum dilakukan.


---

## 11. ADDENDUM AUDIT PHASE 4 — Assessment relation serialization (21 November 2026)

### P0-06 — Endpoint detail Assessment memuat seluruh Exam beserta token untuk Admin

Bukti:
- `app/Policies/AssessmentPolicy.php` method `view()` mengizinkan ADMIN_MADRASAH pada tenant yang sama.
- `app/Http/Controllers/Api/V1/AssessmentController.php` method `show()` menjalankan `$this->authorize('view', $assessment)`, lalu mengembalikan `$assessment->load('exams')` secara langsung.
- `app/Models/Exam.php` memasukkan atribut `token` ke model fillable dan tidak mendefinisikan hidden attribute atau DTO resource yang menyaringnya.

Dampak: Admin yang membaca detail Assessment dapat menerima koleksi seluruh Exam terkait, termasuk atribut token dan konfigurasi ujian, melalui serialisasi Eloquent. Jalur ini terpisah dari ExamController list/detail dan memperluas permukaan P0-03. Selain itu, policy view Assessment mengizinkan Admin; perlu DTO metadata minimum terpisah untuk kebutuhan Admin, bukan eager-load Exam mentah.

Status: **P0 OPEN**. Belum ada HTTP response test yang dijalankan untuk mengukur payload aktual; kesimpulan berasal dari alur controller/policy/model yang dibaca statis. Perlu negative response assertions untuk token, instructions, snapshot, roster, attempt, score, dan metadata yang tidak termasuk allowlist Admin.


---

## 12. ADDENDUM AUDIT PHASE 5 — Admin UI/result surface, policy paths, curriculum CRUD (21 November 2026)

### 12.1 P0-07 — Admin dapat membuka UI hasil ujian dan membaca jawaban/per-soal

Bukti:
- `routes/web.php` mendaftarkan `GET /exams/{exam}/results` → `App\\Livewire\\Teacher\\ResultList` dan `GET /exams/{exam}/results/{attempt}` → `App\\Livewire\\Teacher\\ResultDetail`; route berada pada group `auth, tenant, license`, tanpa role middleware.
- `app/Livewire/Teacher/ResultList.php::mount()` hanya mengandalkan Gate `viewExamResults`; `ReportPolicy::viewExamResults()` mengizinkan ADMIN_MADRASAH tenant-local dengan permission `report.view`.
- `ResultList::render()` membaca `ExamAttempt`, `participant.user`, dan `score`; Blade `resources/views/livewire/teacher/result-list.blade.php` menampilkan nama peserta, status attempt, skor mentah, nilai maksimum, persentase, status penilaian.
- `app/Livewire/Teacher/ResultDetail.php::mount()` mengotorisasi melalui `viewExamResults` dan `ExamAttemptPolicy::view`; policy tersebut mengizinkan Admin dengan `report.view`.
- Blade `resources/views/livewire/teacher/result-detail.blade.php` menampilkan evaluated answer snapshot, skor per butir, feedback dan rincian jawaban. Ini bukan metadata administratif minimum.

Dampak: Admin dapat membaca data hasil dan jawaban siswa melalui route UI Guru yang dipasang tanpa role middleware khusus. Temuan ini memperjelas P0-04/P0-07. UI menu tersembunyi bukan kontrol keamanan karena route dan Livewire method tetap menjadi jalur server.

### 12.2 P0-08 — Admin dapat melakukan finalisasi massal, finalisasi, reopen, dan close ujian

Bukti:
- `app/Livewire/Teacher/ResultList.php::confirmFinalizeAll()` memeriksa `viewExamResults`, lalu untuk setiap attempt memanggil `authorize('finalize', $attempt)`. `ExamAttemptPolicy::finalize()` menerima ADMIN_MADRASAH dengan `exam.grade`; RolePermissionBootstrapper memberikan grant tersebut kepada Admin. Admin dapat memfinalisasi banyak attempt dari route hasil.
- `app/Livewire/Teacher/ResultDetail.php::finalize()` menggunakan `finalize`; `reopen()` menggunakan `reopen`. `ExamAttemptPolicy::reopen()` mengizinkan ADMIN_MADRASAH dengan `exam.reopen`.
- `app/Http/Controllers/Api/V1/ScoringController.php::finalize()` dan `::reopen()` menggunakan policy yang sama; akses tidak terbatas pada tombol Blade.
- `app/Policies/ExamPolicy.php::close()` menerima ADMIN_MADRASAH dengan `exam.close`; `ExamController::close()` memanggil policy tersebut. RolePermissionBootstrapper memberi Admin permission `exam.close`.
- `ExamPolicy::monitor()` menerima Admin dengan `exam.monitor`; MonitoringController summary/participants/attempt/events memanggil policy tersebut.

Dampak: Admin memiliki capability operasional CBT yang dilarang, termasuk close ujian, finalisasi nilai, reopen hasil, dan monitoring. Ini P0 privilege boundary, bukan sekadar kebocoran tampilan.

### 12.3 P1-10 — Endpoint CRUD kurikulum tanpa authorization method-level

Route:
- `routes/api.php` memasang seluruh resource di group `auth:sanctum, tenant, license`. Tidak ada role/permission middleware per resource.
- TenantScoped global scope pada model membatasi baris tenant, tetapi tidak membuktikan authorization role/action.

Temuan method:
- `GradeLevelController::index/store/show/update/destroy`: tidak memanggil policy/Gate/permission.
- `ElementController::index/store/show/update/destroy`: tidak memanggil policy/Gate/permission.
- `LearningObjectiveController::index/store/show/update/destroy`: tidak memanggil policy/Gate/permission.
- `LearningPathController::index/store/show/update/destroy`: tidak memanggil policy/Gate/permission.
- `MaterialController::index/store/show/update/destroy`: tidak memanggil policy/Gate/permission.
- `TagController::index/store/destroy`: tidak memanggil policy/Gate/permission; controller juga tidak memakai AuditService.
- `LearningOutcomeController::index/show/update/destroy`: tidak memanggil policy/Gate/permission. `store()` memakai `StoreLearningOutcomeRequest::authorize()` dengan `learning_outcome.manage`; permission ini tidak didefinisikan dalam daftar permission RolePermissionBootstrapper yang diperiksa, sehingga intended grant perlu diverifikasi.
- Kontrol yang sudah ada: `CurriculumController` dan `PhaseController` memanggil `authorize()` pada seluruh CRUD dan BasePolicy memeriksa permission; `CurriculumPolicy`/ `PhasePolicy` memakai prefix curriculum.

Dampak: seluruh identitas authenticated tenant yang mencapai API group berpotensi membaca/mengubah/menghapus master data pada controller tanpa authorization method-level. Model TenantScoped membantu membatasi tenant record; hal itu tidak membatasi role. Ini temuan unauthorized in-tenant mutation, belum bukti cross-tenant exploit.

### 12.4 P1-11 — Audit event tidak konsisten pada master akademik Admin

Bukti:
- `AcademicManagement::createRombel()` dan `::createSubject()` melakukan persistensi tanpa memanggil AuditService.
- `CurriculumManagement::createTp()` membuat LearningObjective dalam transaksi tetapi tidak menulis AuditService event.
- `UserManagement::createUser()` membuat User, Role pivot dan profile dalam transaksi; tidak memanggil AuditService.
- Beberapa controller API Element/GradeLevel/LearningObjective/LearningOutcome/LearningPath/Material memang menulis AuditService pada sebagian mutasi; TagController tidak.
- `StudentRombelManagement::addStudentToRombel()` menulis audit event (kontrol positif).

Dampak: jejak actor/reason/before-after untuk mutasi master data Admin tidak seragam; transaksi data tidak selalu memiliki audit event atomik.

### 12.5 P1-12 — Alias ADMIN/OPERATOR dan portal Proktor masih aktif pada jalur UI/login

Bukti:
- `AuthWebController::login()` allowlist menerima ADMIN_MADRASAH, OPERATOR, KAMAD, GURU, PROKTOR, SISWA; `redirectForUser()` mengarahkan ADMIN_MADRASAH/OPERATOR ke `/operator/dashboard` dan PROKTOR ke `/proktor/assignments`.
- `routes/web.php` tetap memetakan OperatorDashboard dan ProktorDashboard.
- `resources/views/layouts/app.blade.php` menampilkan label Panel Admin & Guru, Dashboard Operator, dan menu Proktor berbasis role PROKTOR; menu Bank Soal dan Manajemen Ujian ditampilkan juga untuk Admin.
- `AcademicManagement::authorizeAcademicManagement()` dan `CurriculumManagement::authorizeCurriculumManagement()` masih menerima role ADMIN sebagai alias.
- `RolePermissionBootstrapper::ROLE_GRANTS` memberi Admin permission exam.manage/assign/monitor/close/token/grade/reopen/report; OPTIONAL_ROLE_GRANTS masih membuat grant OPERATOR.
- `ExamPolicy` dan `AssessmentPolicy` masih mengandung OPERATOR/PROKTOR branches.

Dampak: source runtime tidak sesuai keputusan final ADMIN_MADRASAH-only, no ADMIN/OPERATOR alias, dan Proktor bukan role/portal permanen.

### 12.6 Tenant and authorization cross-check

- `bootstrap/app.php` memberi middleware priority Authenticate → IdentifyTenant → SubstituteBindings; ini mendukung tenant context sebelum implicit route-model binding.
- `IdentifyTenant::handle()` mereset context dan mengambil tenant dari user; X-Tenant-Override diabaikan. Jika user tidak memiliki tenant_id, middleware saat ini meneruskan request dengan context null, tidak abort eksplisit. TenantScoped models fail-closed dengan 1=0; User model tidak memakai TenantScoped.
- Dari 56 model aktif, model User, Role, Permission, AuditLog, Tenant, local installation/license records, MultiCurriculum family, QuestionMultiCurriculumMapping, RombelStudent dan TenantCurriculumRegistration tidak memakai TenantScoped. Sebagian memang global/installation-scoped; masing-masing query harus diperiksa sesuai domain. User tidak scoped merupakan risiko khusus.
- AcademicManagement query Rombel/Subject/GradeLevel/AcademicYear mengandalkan TenantScoped global scope (keempat model scoped); tidak semua query menuliskan tenant_id eksplisit. TenantScoped bukan authorization.
- UserManagement menulis list query dengan where tenant_id eksplisit.
- TeacherSubjectAssignmentController::canManage() membatasi Admin pada ADMIN_MADRASAH + academic.manage; tetapi `assertSameTenantAndAcademicContext()` memvalidasi user tidak deleted dan role GURU, belum membuktikan status aktif TeacherProfile/user secara menyeluruh.

### 12.7 Pengujian dan bukti aktual

- Pemeriksaan statis tambahan: 14 Livewire components, 24 API controller files, 11 policy files, 56 model files, 34 Blade view files, 64 test files.
- Route inventory dibaca dari `routes/web.php` dan `routes/api.php`; method/action dibandingkan dengan policy/Gate/permission.
- Test yang relevan ada, antara lain:
  - `AdminUiVerificationTest::admin_can_access_all_admin_routes` — fixture Admin diizinkan membuka route Admin termasuk /admin/questions dan /admin/exams; ini justru mengabadikan akses yang kini dilarang.
  - `AdminUiVerificationTest::admin_cannot_create_or_configure_exams_through_livewire` — hanya menguji create/configure, bukan list/token/monitoring/results/finalize/close.
  - `RoleDashboardVerificationTest::monitoring_access_control_and_tenant_isolation` — secara eksplisit mengharapkan Admin dapat membuka monitoring.
  - `ScoringHardeningTest::test_reopen_score_is_allowed_only_for_admin_roles_in_same_tenant` — mengharapkan Admin dapat reopen.
  - `Security/AnswerKeyLeakageTest::test_non_teacher_staff_cannot_preview_answer_key` — hanya preview route; tidak menguji QuestionController index/show atau Admin direct API.
  - `RBACEnforcementTest::test_admin_can_access_curricula_store` — menguji Admin create Curriculum; tidak menutup unauthorized access ke GradeLevel/Element/TP/Path/Material/Tag endpoints.
- Tidak ada HTTP test baru yang dijalankan dalam audit ini. Test method di atas adalah bukti keberadaan assertion/ekspektasi source test, bukan hasil run pada tahap ini.
- Bukti CI terdahulu tetap aggregate CI #692: 480 tests / 1,942 assertions pada source commit lama; belum ada mapping T-001–T-173 ke test run individual.

### 12.8 Blob SHA source utama pada snapshot W0

| Path | Git blob SHA-1 |
|---|---|
| routes/web.php | c22fed7d54871bf4971acf626514fc02c4a68ce9 |
| routes/api.php | f7e08e9080f4d492b374d5783a67304bfe975f94 |
| bootstrap/app.php | d1137fce9e10bc490a044f950170008d55cab914 |
| resources/views/layouts/app.blade.php | 0163813cafc0226b9eae8af789223da0fb793f64 |
| resources/views/livewire/admin/exam-management.blade.php | 5705d815cc3e2539d167b22a26d3508586a77439 |
| resources/views/livewire/admin/question-bank.blade.php | 72feaf1cc1be17253868c91861781dbb223b5aa9 |
| resources/views/livewire/teacher/result-list.blade.php | f9c8de95b18954379b0c1e93555e90acad246983 |
| resources/views/livewire/teacher/result-detail.blade.php | c72c6b95703a105225a084ffab8344a83f9ec6dc |
| app/Livewire/Admin/ExamManagement.php | 478aaa170ee297b0c8399f049cd9b1ba56cbfb10 |
| app/Livewire/Admin/QuestionBank.php | a3013b528015daf4214dd1aa567eba950b03663a |
| app/Livewire/Teacher/ResultList.php | fd60539cb33bd33d0c183b980a84f567681a4d68 |
| app/Livewire/Teacher/ResultDetail.php | 6f959b61eb04ca9d0ee8005009a5e82b3b98a016 |
| app/Policies/ExamPolicy.php | 350c1ddc6bed8738d1af17cacd6c95bc367b7c29 |
| app/Policies/ExamAttemptPolicy.php | b19d6d39d34884d8be464f786340c1a2ab7108b6 |
| app/Policies/AssessmentPolicy.php | 79a0367c8c83723ae3cc9e581fb8dda8a6e2d02a |
| app/Policies/QuestionPolicy.php | af94614a2043206922149b0c44a8ec1e3d87767d |
| app/Policies/ReportPolicy.php | e53be3994c52d067c1e31fe37edc450c24a0f4b6 |
| app/Http/Controllers/Api/V1/AssessmentController.php | 28e6f8fe07c8e2b3dad22ecd190e9aa108947e78 |
| app/Http/Controllers/Api/V1/ExamController.php | bf373858869b470b235e2256e5be6169fbb1f561 |
| app/Http/Controllers/Api/V1/QuestionController.php | 7ded53423dd4d96788123819e8039fd543ce4fba |
| app/Http/Controllers/Api/V1/MediaController.php | 52254b81a55db8e2e45538158ef202b8ccf09b70 |
| app/Http/Controllers/Api/V1/MonitoringController.php | 335a32029f4d5ab4788ea31fd81088dfdb5acde2 |
| app/Http/Controllers/Api/V1/ScoringController.php | 42b96176162a6e69e3661d468a6d3362b05f59c5 |
| app/Http/Controllers/Api/V1/ReportController.php | c78442de0f5b4b40a7773bb2af0cf0ffd852d055 |
| app/Http/Controllers/Api/V1/GradeLevelController.php | 8d063a89445fbce767e88caf9931e5a8d7c5c2dd |
| app/Http/Controllers/Api/V1/ElementController.php | b503b903fd7ce8e85ecb466a209e0ff8bad960c2 |
| app/Http/Controllers/Api/V1/LearningObjectiveController.php | 97c0f8cc1da01567d2ca2092421652bfb89d94e6 |
| app/Http/Controllers/Api/V1/LearningOutcomeController.php | bddb5db70fa2f6bccd12f00505a8be4acca84791 |
| app/Http/Controllers/Api/V1/LearningPathController.php | 6b4d1762697bea60fe25ad856c091a37a1fc4e10 |
| app/Http/Controllers/Api/V1/MaterialController.php | abdbcb845089e57e1b6f98dde5978e6e35df317d |
| app/Http/Controllers/Api/V1/TagController.php | a9d5101ac2e63a047d0527d0c82d141ee5bb5301 |
| app/Models/User.php | 74dd080f703d7ef3256b406176e53dad5786a937 |
| app/Traits/TenantScoped.php | cf192a8ccb6ca530122cb105ed3086adb09a3c5a |
| app/Http/Middleware/IdentifyTenant.php | 376a4b2771c3d9a6b128ad847c150d47ac2977db |
| app/Services/RolePermissionBootstrapper.php | fac82573de84c52b52867afbbf7e1637164835b4 |

### 12.9 Status

Temuan P0-07/P0-08 dan P1-10/P1-11/P1-12 masih OPEN. Ini pemeriksaan statis mendalam atas jalur Admin UI, hasil, policy, dan API kurikulum. Belum merupakan audit menyeluruh akhir: seluruh relasi model, seluruh serialisasi/resource, query lintas domain, export/download/media, cache invalidation, test mapping 173 ID dan 23 modul harus dituntaskan.


---

## 13. ADDENDUM AUDIT PHASE 6 — model relations, response serialization, referential tenant integrity (21 November 2026)

### 13.1 P0-09 — Admin read access to Question API/publication/media payloads

Bukti tambahan:
- `QuestionController::index()` and `::show()` call `QuestionPolicy::viewAny/view`, then eager-load `currentVersion.options`, learning outcome/version, objective, tags and serialize Eloquent models.
- `QuestionOption` exposes `is_correct` and `score_weight`; `QuestionVersion` exposes `stem` and `explanation`; neither defines hidden attributes or a redacted resource DTO.
- `QuestionPolicy::view()` permits Admin for tenant-local `owner_type=MADRASAH`; `RolePermissionBootstrapper` grants Admin `question.view`.
- `QuestionPublicationController::index()` calls `viewAny` and loads `question.currentVersion`, exposing question content to Admin.
- `MediaController::index()` explicitly allows ADMIN_MADRASAH and returns tenant QUESTION media metadata; `MediaController::show()` allows Admin when QuestionPolicy::view passes and returns the actual private file bytes. The storage path is hidden in index response and path safety/tenant checks exist, but authorization role remains too broad.

Status: P0 OPEN. This is direct API exposure in addition to the Livewire Bank Soal screen. No direct HTTP request was run in this audit session.

### 13.2 P0-10 — Exam and Assessment serialization expose raw operational fields

- `ExamController::index()` uses `ExamPolicy::viewAny` and returns paginated Exam models. `ExamController::show()` uses `ExamPolicy::view` and returns Exam with Assessment; student response has a dedicated snapshot transform, but non-student Admin response serializes raw Exam.
- `Exam` fillable/casts include `instructions`, `token`, token mode, scheduling, attempt settings, show-result flags, and other CBT configuration; model has no hidden list or resource DTO.
- `AssessmentController::show()` returns `$assessment->load('exams')`; Admin is allowed by AssessmentPolicy::view. This serializes the associated raw Exam collection.
- Admin list UI `ExamManagement::render()` and Blade also display token, snapshot count, participant count, and links to operational monitoring/results/analytics.

Status: P0 OPEN. Raw serialization and UI both violate metadata-only Admin boundary.

### 13.3 P0-11 — Monitoring and scoring endpoints are authorized for Admin

- `ExamAttemptController::show()` routes non-student users through `authorize('monitor', $exam)`; its response DTO is minimal, but Admin still receives attempt operational status/timing.
- `MonitoringController::summary/participants/attempt/events` call `authorize('monitor', $exam)`. Admin is permitted by `ExamPolicy::monitor()` with `exam.monitor`.
- `MonitoringController::participants()` returns student name, participant ID, attempt status/timestamps, remaining time, answered/unanswered counts and sync health.
- `MonitoringController::events()` serializes AuditLog records for Exam/ExamAttempt. Event old/new JSON and metadata are not passed through an Admin-safe DTO.
- `ScoringController::show()` calls `authorize('viewScore', $attempt)`; ExamAttemptPolicy allows Admin with `report.view`, and returns score plus question-level score rows.
- `ReportController` exam results, summary, question report, student results, subject results, Excel/PDF exports and AnalyticsController exam analytics use policies that allow Admin tenant-local with `report.view`.

Status: P0 OPEN. Admin has direct API access to monitoring, roster, attempt, answer/score and report/analytics data that are reserved for Guru. Some response DTOs redact internal device/fencing fields, but redaction does not authorize the Admin access itself.

### 13.4 P1-13 — Remaining tenant-aware foreign-key gaps across User relationships

Positive controls:
- `2026_11_07_210002_harden_teacher_assignment_tenant_foreign_keys.php` creates `users(id,tenant_id)` candidate key and composite FK for `teacher_profiles(user_id,tenant_id)`; TeacherSubjectAssignment relations also use composite tenant FKs.
- Proctor assignment migration uses composite FK for exam, proctor_user_id, assigned_by, revoked_by against tenant.

Gaps observed in schema:
- `2024_01_01_000001_create_profiles_tables.php`: `student_profiles.user_id` is a single-column FK to users; no `(user_id,tenant_id)` composite FK.
- `2024_01_01_000017_create_exam_participants_table.php`: `exam_participants.user_id` is single-column FK; only exam_id+tenant_id is composite.
- `2024_01_01_000020_create_exam_attempt_answers_table.php`: `student_id` is single-column FK to users; exam/attempt are tenant composite.
- `2024_01_01_000021_create_scoring_tables.php`: score student_id, finalized_by and question-score student_id/grader_id are single-column user FKs; exam/attempt are tenant composite.
- `2026_09_13_110007_create_tp_achievements_table.php`, `110009_create_atp_achievements_table.php`, `110011_create_cp_achievements_table.php`: student_id and finalized_by are single-column User FKs; rombel_id and semester_id are not composite tenant FKs, while some academic-year/subject/learning-objective/path references are tenant composite.
- `2024_01_01_000002_create_academic_tables.php`: `rombel_student` pivot has single-column FKs to rombels and student_profiles; no composite tenant FK pair.

Impact: application methods perform explicit tenant checks on many write paths, but database integrity does not reject every mismatched User/profile/tenant or Rombel/student pivot. This is a defense-in-depth integrity risk; no cross-tenant row exploit was demonstrated. It requires disposable-data preflight and separately approved DB migration design; no DDL was run.

### 13.5 P1-14 — AuditLog attribution and append-only guarantee

- `AuditLog` model has no immutable/append-only guard, no guarded update/delete override, and no tenant global scope.
- Migration `2024_01_01_000003_create_audit_logs_table.php` uses nullable tenant_id/user_id with ON DELETE SET NULL; hard deletion can erase tenant/actor attribution.
- `AuditService::log()` writes actor, tenant, action, entity, old/new JSON, IP, user agent and timestamp, but no consistent reason/correlation ID parameter.
- Mutations in CurriculumController/PhaseController/GradeLevelController/ElementController/LearningObjectiveController/LearningPathController/MaterialController and some Livewire Admin methods call AuditService after data write without wrapping both mutation and audit in one transaction. Some other flows do use DB::transaction.
- No DB trigger/permission model proving append-only was found in migrations.

Status: P1 OPEN. AuditService existence is not proof of tamper-resistant or transactionally complete audit history.

### 13.6 P1-15 — Relation paths to User are not tenant-constrained by Eloquent global scope

- User model does not use TenantScoped. Relationships such as ExamParticipant::user, ExamAttemptAnswer::student, ExamQuestionScore::student, ExamAttemptScore::student, StudentProfile::user, and achievement student/finalizer relations resolve User by primary key without automatic tenant predicate.
- Most application creation flows validate tenant before insert; however schema gaps in §13.4 mean DB does not enforce all these pairs.
- `RombelStudent` pivot boot validates pivot tenant against parent/context and StudentProfile::rombels filters pivot tenant; this is a positive application control, but migration lacks composite tenant FKs.
- `MonitoringController::participants()` joins users through participant.user; it does not independently compare each user.tenant_id in the query projection. The controller's tenant-scoped participant relation plus application data integrity are relied upon.
- Result/report flows often apply explicit tenant checks/TeacherAccess filters; negative HTTP tests for every relationship path remain absent.

Status: P1 OPEN; no confirmed cross-tenant response from valid application-created rows, but User relations and FK gaps require hardening and tests.

### 13.7 PENGUJIAN — scope hasil fase 6

- Static review performed on model relation definitions, migrations, serializers/JSON response paths, media show/download, monitoring, scoring and reporting.
- No database connection, migration execution, HTTP request, browser test or concurrency test was performed.
- Existing CI aggregate #692 remains 480 tests / 1,942 assertions on old source commit; no per-T-ID evidence and no direct Admin negative assertions for all newly mapped routes.
- No source or database changed.


---

## 14. ADDENDUM AUDIT PHASE 7 — module and test matrix publication (21 November 2026)

### Published detailed matrices

1. `docs/MATRIKS_AUDIT_WEB_ADMIN_DETAIL_2026-11-21.md`
   - 23/23 modules ADM-001–ADM-023 mapped to source paths/methods, audit status, test references and actual evidence limitation.
   - Commit: `d7b290d9438e3f3a8a4d693faf61797a41907f6e`
   - Blob SHA: `29d15bb18a550b70475dd0e2f7886b11148f2469`
   - Backup: `docs/backup/web-admin-read-only-2026-11-21/MATRIKS_AUDIT_WEB_ADMIN_DETAIL_2026-11-21.md.pre-publication` (same blob SHA).

2. `docs/MATRIKS_TEST_WEB_ADMIN_T001_T173_MAPPING_2026-11-21.md`
   - 173/173 test IDs mapped to source area, candidate test file/method or explicit gap, and per-ID status.
   - All IDs remain **NOT VERIFIED** per-ID. CI #692 aggregate 480/1,942 cannot be attributed to a specific T-ID.
   - Commit: `cf6f84db5a43ea64d1c5aabea88be7b38cbe7f7c`
   - Blob SHA: `10a7fe3dc32e522e94e80c5e39b3e1633685960c`
   - Backup: `docs/backup/web-admin-read-only-2026-11-21/MATRIKS_TEST_WEB_ADMIN_T001_T173_MAPPING_2026-11-21.md.pre-publication` (same blob SHA).

### Source branch verification
- `work/web-admin-w0-20261121` was compared with source pin `2b4321ce032fd5e67cf022da8564a5287b8cef6f`: identical, 0 ahead/0 behind.
- Compare `ade5daa6cf853c164c4f51ec6e25ca68ce40b4f2` → W0 branch: 25 commits ahead, 0 behind. Returned changed paths are documentation/workflow/manifest only; no application source path appeared. The verified source snapshot remains the W0 application source basis.
- Documentation branch checkpoint HEAD at this publication point: `cf6f84db5a43ea64d1c5aabea88be7b38cbe7f7c`; branch compare identical to that SHA at verification time.

### Audit status
- ADM-001–ADM-023 mapping is published, but no module is accepted as complete based only on static source.
- T-001–T-173 mapping is published; all per-ID runtime outcomes remain NOT VERIFIED. Existing candidate test methods include legacy expectations contradictory to final Admin/Proktor decisions.
- Audit is still NOT COMPLETE: remaining query/relationship, cache/export/download, authorization matrix, source-to-test trace, and final patch-plan dependency/rollback review remain.
- No source, DB, migration, config, role, permission, Web Guru, PR or deployment was changed.


---

## 15. ADDENDUM AUDIT PHASE 8 — route-by-route access and Livewire hydration authorization (21 November 2026)

### 15.1 P0-12 — API and Web Admin/Guru route matrix confirms broad Admin CBT access

Route evidence:
- `routes/api.php` line group 27–122 protects endpoints with only `auth:sanctum, tenant, license`; no role middleware is attached per route. Authorization is delegated to controller/policy methods.
- `routes/web.php` lines 61–100 uses `auth, tenant, license` for monitoring, Admin Livewire pages, Teacher result pages, exports and analytics; role authorization is in Livewire mount/action or policy, not route middleware.
- Web routes `/exams/{exam}/results`, `/results/{attempt}`, `/exams/{exam}/analytics`, `/exams/{exam}/analytics/items`, `/exams/{exam}/monitoring`, and export paths are reachable by Admin under current ReportPolicy/ExamPolicy/ExamAttemptPolicy.
- API routes `/exams/{exam}/monitoring*`, `/exams/{exam}/attempts/{attempt}`, `/attempts/{attempt}/score`, `/finalize`, `/reopen`, `/close`, `/reports/*`, and `/analytics/exams/*` similarly pass Admin policies.
- Student execution/answer routes contain self/participant checks; Guru-only question/exam authoring is generally policy-guarded. This does not compensate for the Admin read/close/finalize/reopen grants.

### 15.2 P1-16 — Livewire permission checks are mount-only on several data-bearing render surfaces

Livewire calls mount only on initial component creation; subsequent requests hydrate the component and call public actions/render. The following render paths do not re-run the permission/role assertion before reading data:
- `AcademicManagement::render()`: mount calls `authorizeAcademicManagement()`; render reads rombels, subjects, grade levels and academic years without repeating it.
- `CurriculumManagement::render()`: mount checks curriculum.view; render reads outcomes, versions, objectives and subjects without aborting if view permission is later revoked.
- `UserManagement::render()`: mount calls `authorizeUserManagement()`; render lists tenant users, role names, TeacherProfile and StudentProfile without rechecking user.manage.
- `ProctorAssignmentManagement::render()`: mount calls `assertCanManage()`; render lists exams, Proktor accounts and assignment/assigner/revoker records without rechecking exam.proctor.assign. `updatedSelectedExamId()` also has no repeated permission check.
- `ExamManagement::render()`: mount checks allowed role; render loads exams with participants, attempts, snapshots, question data and token-bearing Exam models. It does not repeat role/policy check. When current user no longer has either Admin/Guru role, its query has no role-specific filter and still starts from all tenant Exams.
- `QuestionBank::render()`: mount checks question.view and Admin/Guru role; render does not repeat authorization. If neither role condition matches after a role change, its query has no owner-type restriction and can enumerate all tenant questions/options.
- `ResultList::render()`, `ResultDetail::render()`, `AnalyticsDashboard::render()`, and `ItemAnalysis::render()`: mount authorizes viewExamResults/view analytics, but render does not reauthorize each hydrated request. ResultDetail includes answer snapshots and question-level scores; ResultList includes participant names and scores.
- `OperatorDashboard::render()` and `ProktorDashboard::render()` likewise depend on mount checks for their role surfaces.

Mutation actions in many components do recheck authorization (e.g. UserManagement::createUser, ProctorAssignmentManagement::assign/revoke, QuestionBank::saveQuestion/publish/delete, ExamManagement::saveExam/generateToken/lock, ResultList finalization/publish, ResultDetail grading/finalize/reopen). The finding is specifically stale read authorization/render and some state-updater actions, not a claim that all mutation actions bypass policy.

Dampak: if role/permission is revoked or changed while a Livewire component remains active, subsequent hydrated requests can continue returning data. In ExamManagement and QuestionBank, missing role filters in the render query can widen the data set to the entire tenant. This needs per-request reauthorization and fail-closed query filters, with regression tests for role revocation during an active Livewire session. Status P1 OPEN; severity may be escalated if HTTP reproduction demonstrates access after revocation.

### 15.3 P1-17 — Some Admin data mutations lack consistent AuditService coverage

Reconfirmed:
- AcademicManagement createRombel/createSubject and CurriculumManagement createTp persist data without AuditService.
- UserManagement createUser creates user/profile/role transactionally but does not write AuditService event.
- TagController CRUD does not call AuditService.
- Proctor assignment and StudentRombel membership do log events; other API curriculum controllers log some mutations but transaction/audit atomicity varies.

### 15.4 API route access matrix — concise result

| Route family | Authorization observed | Admin result under current source | Audit status |
|---|---|---|---|
| `/api/v1/curricula`, `/phases` | CurriculumPolicy/PhasePolicy via BasePolicy | Allowed according to curriculum permissions | PARTIAL; policy method reviewed |
| `/grade-levels`, `/elements`, `/learning-outcomes`, `/learning-objectives`, `/learning-paths`, `/materials`, `/tags` | No consistent method-level policy; LearningOutcome store FormRequest only | Admin allowed; other authenticated tenant roles can also mutate several endpoints | P1 OPEN |
| `/questions`, versions, publish/import | QuestionPolicy | Admin view/list/detail allowed for MADRASAH-owned; create/update/publish denied | P0 read exposure |
| `/question-publications` | viewAny/update Question policy | Admin index allowed; mutation denied | P0 read exposure |
| `/media` | role check + QuestionPolicy | Admin can list and read tenant QUESTION media file; cannot attach/update question due update policy | P0 read exposure |
| `/teacher-subject-assignments` | controller canManage + tenant context | Admin_Madrasah with academic.manage allowed | PARTIAL |
| `/assessments` | AssessmentPolicy | Admin list/detail allowed; detail serializes related Exams | P0 |
| `/exams` | ExamPolicy | Admin list/detail allowed; raw Exam token/config serialized | P0 |
| `/exams/{exam}/monitoring*` | ExamPolicy.monitor | Admin with exam.monitor allowed | P0 |
| `/exams/{exam}/participants` GET | monitor policy | Admin can read roster | P0 |
| `/exams/{exam}/participants` POST/DELETE | manageParticipants/update policy | Admin denied; Guru owner only | POSITIVE |
| `/exams/{exam}/attempts/{attempt}` GET | monitor policy for non-student | Admin can read operational attempt DTO | P0 |
| `/answers` GET/PUT, execution, submit | student self/participant/device checks | Admin denied | POSITIVE |
| `/score` GET | ExamAttemptPolicy.viewScore | Admin report.view allowed | P0 |
| `/score` POST/recalculate/manual grading | score/grade policy | Guru assignment only; Admin denied | POSITIVE |
| `/finalize`, `/reopen` | ExamAttemptPolicy | Admin exam.grade/exam.reopen allowed | P0 |
| `/close` | ExamPolicy.close | Admin exam.close allowed | P0 |
| `/reports/*`, exports | ReportPolicy | Admin report.view allowed for exam/student/subject results | P0 |
| `/analytics/exams/*` | ReportPolicy/ExamPolicy | Admin report.view allowed | P0 |
| `/analytics/curriculum/*` | controller checks tenant and role scope | Admin can read curriculum achievement analytics; distinguish from exam analytics | REVIEW/likely Admin academic reporting |
| `/tags` | auth/tenant/license only | All authenticated tenant roles can create/delete tags | P1 |

### 15.5 Test evidence and gap

- Existing methods that demonstrate current legacy expectations:
  - `AdminUiVerificationTest::admin_can_access_all_admin_routes`
  - `RoleDashboardVerificationTest::monitoring_access_control_and_tenant_isolation` (expects Admin monitoring allowed)
  - `ScoringHardeningTest::test_reopen_score_is_allowed_only_for_admin_roles_in_same_tenant`
  - `WebAdminAuthTest::test_admin_madrasah_login_redirects_to_operator_dashboard`
- No test method found for Livewire permission revocation after mount/hydration and no direct Admin-denial test covering the complete route families.
- No HTTP/browser/Livewire tests executed in this phase. Existing method references are source inspection only.

### 15.6 Blob SHA source evidence — Livewire

| Path | Git blob SHA-1 |
|---|---|
| app/Livewire/Admin/AcademicManagement.php | 5afd0780fa642354c11954fce307d8e6f7e4576f |
| app/Livewire/Admin/CurriculumManagement.php | e3422650ea71709cba5ae07cd2b887cf812166a8 |
| app/Livewire/Admin/ProctorAssignmentManagement.php | 4e8bd91dd54757aa863d35983aace718def2ca2c |
| app/Livewire/Admin/UserManagement.php | 5f90e3873cadeb77a1bcd5a6aa2134953e60beed |
| app/Livewire/Admin/StudentRombelManagement.php | 0da3f15787fbaf1fe6ea6f0eaec1439f413231f1 |
| app/Livewire/Teacher/AnalyticsDashboard.php | 90cabf8c0b61775ac79ede8a6d2d6e5417dfa336 |
| app/Livewire/Teacher/ItemAnalysis.php | b82649219c0c32310b1ce4ea84a4a21d1fb071b9 |
| app/Livewire/Operator/OperatorDashboard.php | 75ef8da79896994c98b00b9941f6f976a36b671c |
| app/Livewire/Proktor/ProktorDashboard.php | 172fff1aa4a101193aad9061a729cc17ccb11ecc |

### 15.7 Status

Audit route-by-route and Livewire hydration review adds P0-12/P1-16/P1-17. Audit remains OPEN; no source or configuration changed. Patch plan final remains pending full query/export/cache/resource review and final per-file dependency analysis.


---

## 16. ADDENDUM AUDIT PHASE 9 — login role acceptance and missing tenant fail-closed guard (21 November 2026)

### P1-18 — API login still issues tokens to legacy ADMIN/OPERATOR/PROKTOR identities

Bukti:
- `LoginIdentityResolver::resolve()` defines staff roles `ADMIN_MADRASAH, ADMIN, OPERATOR, KAMAD, PROKTOR`; any one of those passes staff-role matching if username identity matches.
- `AuthController::login()` uses that resolver and rejects only missing user, invalid password or SUPER_ADMIN. It does not apply a separate allowlist that restricts the active Admin role.
- Therefore an account with legacy `ADMIN`, `OPERATOR`, or `PROKTOR` can receive a Sanctum API token if its tenant installation identity and credentials pass.
- Web login `AuthWebController::login()` allowlist excludes `ADMIN` but still accepts `OPERATOR` and `PROKTOR`; redirectForUser routes Admin/Operator to `/operator/dashboard` and Proktor to `/proktor/assignments`.
- Additional legacy runtime paths: `LicenseStatusController` accepts ADMIN_MADRASAH/OPERATOR; `OperatorDashboard::mount()` accepts ADMIN_MADRASAH/ADMIN/OPERATOR; `KamadDashboard::mount()` accepts ADMIN_MADRASAH/ADMIN/KAMAD; `AcademicManagement` and `CurriculumManagement` accept ADMIN; DatabaseSeeder creates OPERATOR; InstallerService role map still includes OPERATOR; RolePermissionBootstrapper optional grants OPERATOR; ExamPolicy/AssessmentPolicy contain OPERATOR and PROKTOR branches.

Dampak: keputusan final ADMIN_MADRASAH-only belum diterapkan pada authentication/runtime. API token path specifically bypasses the web allowlist's partial exclusion of ADMIN. Status P1 OPEN; no login request was executed in this phase.

### P1-19 — IdentifyTenant tidak menolak authenticated user tanpa tenant_id

Bukti:
- `IdentifyTenant::handle()` resets TenantContext, rejects SUPER_ADMIN, reads `$user->tenant_id`, and sets context only if tenantId is truthy.
- If an authenticated user has null tenant_id, middleware currently calls next($request) instead of aborting 403.
- `TenantScoped::bootTenantScoped()` fails closed (1=0) when context is absent, and create hook throws without context. This is a positive control for models using the trait.
- `User` does not use TenantScoped. Several route-bound User records and User relationships are resolved by primary key; controller/policy checks must therefore reject null/mismatched tenant explicitly.
- `LoginIdentityResolver` normal login requires exactly one local installation tenant and filters candidate users by that tenant; however old Sanctum tokens/legacy sessions are not necessarily re-resolved through LoginIdentityResolver on every request.

Dampak: fail-closed behavior is inconsistent at middleware boundary. No cross-tenant data disclosure was demonstrated. Require middleware to reject authenticated user without tenant, and negative HTTP tests for all unscoped User/resource routes.

### 16.3 Additional positive tenant controls

- Middleware priority in `bootstrap/app.php`: Authenticate → IdentifyTenant → SubstituteBindings. This allows tenant context to be established before implicit route model binding.
- X-Tenant-Override is ignored; tenant derives from authenticated user.
- `TenantScoped` forces tenant_id from context on create and returns no rows without context.
- LoginIdentityResolver requires exactly one installation identity and exactly one Tenant, then filters username, TeacherProfile and StudentProfile candidates by that tenant.
- User query sites reviewed in ExamParticipantController, ReportController, Admin UserManagement, ExamManagement, ProctorAssignmentManagement, OperatorDashboard and ExamProctorAssignmentService generally include explicit tenant_id filters. Unscoped bootstrap/maintenance uses are separately documented in §10.2 and §13.6.
- No custom Route::bind/Route::model/resolveRouteBinding was found; implicit binding uses model query scopes where available.

### 16.4 Final source path inventory for role branches

- `app/Services/LoginIdentityResolver.php::resolve`
- `app/Http/Controllers/Api/V1/AuthController.php::login`
- `app/Http/Controllers/AuthWebController.php::login/redirectForUser`
- `app/Http/Controllers/LicenseStatusController.php::__invoke/refresh`
- `app/Livewire/Operator/OperatorDashboard.php::mount/render`
- `app/Livewire/Proktor/ProktorDashboard.php::mount/render`
- `app/Livewire/Kamad/KamadDashboard.php::mount`
- `app/Livewire/Admin/AcademicManagement.php::authorizeAcademicManagement`
- `app/Livewire/Admin/CurriculumManagement.php::authorizeCurriculumManagement`
- `app/Services/RolePermissionBootstrapper.php::ROLE_GRANTS/OPTIONAL_ROLE_GRANTS/bootstrap`
- `database/seeders/DatabaseSeeder.php::run`
- `app/Services/InstallerService.php` role creation/bootstrap
- `app/Policies/ExamPolicy.php`, `AssessmentPolicy.php`

### 16.5 Status

P1-18 and P1-19 OPEN. Authentication, role branch and middleware static review complete for the listed paths; no login/API request or tenant-null HTTP test was executed. No source/config/database changed.


---

## 17. ADDENDUM AUDIT PHASE 10 — exhaustive tenant/query inventory, audit coverage and active-user state (21 November 2026)

### 17.1 Corrected inventory counts

The detailed source inventory supersedes preliminary counts:
- Models: **56 total; 40 TenantScoped; 16 without the trait**.
- `withoutGlobalScopes()`: 25 calls.
- `withoutGlobalScope(...)`: 5 calls.
- Total: **30 occurrences across 8 active PHP files**, not the preliminary 36/10 estimate in the first audit note.
- The 16 unscoped models and their classification/controls are listed in `docs/MATRIKS_TENANT_QUERY_RELATION_WEB_ADMIN_2026-11-21.md`.
- The unscoped classes include expected global/installation records (Tenant, Role, Permission, LocalLicense*, MultiCurriculum*) and special pivot/log models (RombelStudent, AuditLog); User is the main tenant-identity model lacking a global scope.

### 17.2 User and unscoped query review — completed static inventory

The 30 occurrences were enumerated in eight files and reviewed:
- `CleanupOrphanedMediaCommand`: cross-tenant maintenance scan, but re-fetch is constrained by row ID + tenant_id, file path must match `private/tenants/{tenant}/media/{filename}`, and snapshot references are tenant-filtered.
- `ExpireExamAttemptsCommand`: cross-tenant scheduler scans candidates, sets TenantContext per candidate, then locks/revalidates Exam → Participant → Attempt → Snapshot with tenant IDs and restores prior context in finally.
- `LicenseStatusController`: explicit authenticated tenant filter.
- `EnsureLicenseActive`: attempt-recovery unscoped lookup compares installation tenant, exam ID, status/expiry and participant user; collection path filters tenant/user.
- `User::creating`: unscoped username uniqueness check explicitly filters tenant_id.
- `InstallerService`: pre-auth recovery lookup by username/email is global initially but fails closed unless exact tenant identity, tenant code, user ID, name, username, email, password hash and exact role ADMIN_MADRASAH match. Postcondition lookup compares admin tenant to tenant row.
- `InstallationIdentityService`: requires exactly one Tenant and requested tenant exists.
- `LoginIdentityResolver`: requires exactly one installation identity and one Tenant; User/profile candidate queries filter tenant_id and reject ambiguity.
- Direct User query sites in ExamParticipantController, ReportController, UserManagement, ExamManagement, ProctorAssignmentManagement, OperatorDashboard and ExamProctorAssignmentService were reviewed; request-path queries generally add explicit tenant_id. Role/profile relations to User remain unscoped by Eloquent and rely on DB/application consistency.

No direct cross-tenant User query exploit was confirmed in the static review. This is not a runtime negative-test result.

### 17.3 P1-20 — Normal license middleware does not bind authenticated tenant to installation tenant

Bukti:
- `IdentifyTenant::handle()` sets TenantContext from authenticated user. It does not compare that user.tenant_id with LocalInstallationIdentity.tenant_id or assert exactly one tenant.
- `EnsureLicenseActive::handle()` normal active-license path loads LocalLicenseState + installation and calls `LicenseArtifactVerifier::assertOperational()`, which verifies signed installation_id/license claims but does not compare request user tenant_id with `state.installation.tenant_id`.
- The temporary pre-activation guard does compare installer tenant, installed.lock tenant, DB identity and authenticated user tenant. The normal Publisher license path lacks the equivalent request-tenant comparison.
- `LoginIdentityResolver` normally blocks login if the database contains multiple tenants, but existing Sanctum tokens/sessions are not re-resolved through that resolver on every request.

Impact: under the intended one-database/one-madrasah model, this is a defense-in-depth installation binding gap rather than a demonstrated cross-tenant exploit. If extra tenant rows or old tokens exist in one database, an authenticated user from a different tenant could receive a TenantContext while the active license remains bound to the installation's original tenant. Middleware should fail closed unless authenticated user tenant equals the local installation tenant.

### 17.4 P1-21 — No explicit active/deactivated account state for Guru

Bukti:
- Base `users` migration has tenant_id, name, email, password, legacy role string, timestamps and softDeletes; no `is_active`, `status`, `suspended_at` or equivalent account state.
- `User` model does not define an active-state method/field.
- `TeacherProfile` has SoftDeletes only; no active/inactive status.
- `UserManagement` supports create only; no Admin activation/deactivation/restore lifecycle.
- `ExamProctorAssignmentService::assign()` checks same-tenant User, `deleted_at IS NULL`, and role PROKTOR; it does not require TeacherProfile and has no separate account-active status.
- `TeacherSubjectAssignmentController::assertSameTenantAndAcademicContext()` checks profile exists, user not soft-deleted, tenant matches and role GURU; no explicit active status field exists.

Impact: final requirement “Guru aktif + TeacherProfile valid/aktif” cannot be fully represented/verified as a separate lifecycle state in the current schema. Soft-delete is not equivalent to reversible administrative deactivation. Proktor service also uses the wrong role model.

### 17.5 P1-22 — Curriculum CRUD permission inconsistency

- `RolePermissionBootstrapper` defines 15 permissions; it does not define `learning_outcome.manage` or `material.manage`.
- `StoreLearningOutcomeRequest::authorize()` requires `learning_outcome.manage`; without external/manual grant, intended store operation is denied to Admin/Guru.
- `LearningOutcomePolicy` and `MaterialPolicy` exist with BasePolicy prefixes but their controllers do not consistently call them.
- GradeLevel, Element, LearningObjective, LearningPath, Material, Tag and LearningOutcome index/show/update/destroy lack method-level authorization as detailed in §12.3.
- Thus some curriculum mutations are open to any authenticated tenant role while LearningOutcome store may be unavailable to all normal roles. This is inconsistent with Admin_Madrasah curriculum authority and Guru read/authoring boundaries.

### 17.6 Audit coverage and transaction observations

- AuditService call-site inventory was searched across all app PHP. It covers most API curriculum/exam/question/scoring/report operations, teacher-subject assignment, Proktor assignment, student-rombel membership and some Livewire operations.
- Missing Admin mutation audit: AcademicManagement::createRombel/createSubject; CurriculumManagement::createTp; UserManagement::createUser; TagController CRUD.
- Non-atomic audit patterns exist where controller persists first and calls AuditService afterward without a surrounding transaction (examples: CurriculumController, PhaseController, GradeLevelController, ElementController, LearningObjectiveController, LearningPathController, MaterialController, AssessmentController, some ExamController archive/publish/delete paths). If audit write fails, data mutation may already persist.
- Positive atomic patterns: TeacherSubjectAssignmentController store/update/deactivate, ExamProctorAssignmentService, ExamAttempt lifecycle, ScoringService/ScoringController, ExamParticipantController store/destroy, ExpireExamAttemptsCommand, several ExamManagement/QuestionBank/StudentRombel flows.
- AuditLog still lacks append-only enforcement and uses ON DELETE SET NULL for actor/tenant.

### 17.7 Export, cache, media and resource review

- Export call sites: `ReportController::exportExcel` uses `ExamResultsExport`; PDF render paths are all in ReportController. Authorization runs before export, but ReportPolicy currently permits Admin to read/export exam/student/subject results (P0 boundary defect).
- Cache usage in app is limited to four AnalyticsController Cache::remember keys; all include tenant_id and authorization runs before cache. Score changes invalidate exam and current rombel keys through ScoringService/ScoringController/ResultList/ResultDetail.
- Cache invalidation gap: `StudentRombelManagement::addStudentToRombel()` does not invalidate tenant+rombel+exam analytics cache. RombelAnalyticsService derives result membership from current StudentProfile.rombels; after a student is added to a new rombel, a cached report for that rombel can remain stale up to the one-hour TTL. No runtime cache invalidation test was run.
- Media files are stored under private tenant paths and MediaController checks path/tenant ownership. Admin still has read access to question media through QuestionPolicy; this is a role authorization defect, not evidence of public storage exposure.
- Only two API JsonResource classes were found, both LearningOutcome resources. Exam/Question/Score/Monitoring endpoints mostly serialize Eloquent models or arrays directly; there is no general Admin-redacted Exam/Question/Score DTO/resource.
- No app/Jobs classes were found. Two console commands are tenant-aware maintenance as described in §17.2.

### 17.8 Test inventory status

- T-001–T-173 map is published separately. Every per-ID status remains NOT VERIFIED.
- Test source candidate methods and blob SHAs are recorded in mapping document.
- Existing CI #692 aggregate 480 tests / 1,942 assertions does not provide per-ID traceability.
- No new HTTP/Livewire/MySQL/browser test executed during this phase.

### 17.9 Tenant matrix

Detailed model/query/FK matrix: `docs/MATRIKS_TENANT_QUERY_RELATION_WEB_ADMIN_2026-11-21.md` (published and backed up on the documentation branch).

Audit remains OPEN pending final source-to-test trace validation and per-file patch plan. No implementation or DB change was performed.


---

## 18. ADDENDUM AUDIT PHASE 11 — raw query, relationship joins, login token and file/media paths (21 November 2026)

### 18.1 Raw query inventory

All `DB::table`, `DB::select`, join and relevant raw query sites in `app/` were inventoried. Key request-path controls:
- `ExamController::hasLegacySnapshotHistory()`: raw `exam_attempt_answers` and `exam_question_scores` lookups include tenant_id + exam_id + snapshot IDs.
- `MediaController::attachToQuestionVersion/detachFromQuestionVersion/questionHasActiveAttempt()`: pivot and exam/attempt/participant joins include tenant_id in the relevant join/filter conditions; file references are scoped by tenant.
- `TeacherAccess`: raw joins include user.tenant_id = student_profile.tenant_id, pivot tenant_id = profile tenant_id, rombel tenant_id and assignment tenant. This is a positive defense-in-depth implementation.
- `ExamSnapshotService::createSnapshots()`: raw history queries include tenant_id + exam_id + snapshot IDs; QuestionVersion and media are reloaded with tenant_id under locks.
- `CurriculumAchievementService`: raw joins tie current score, attempt and tenant_id; inserts carry tenant_id in each achievement/contribution/source row.
- `StudentRombelManagement`: pivot existence/insert includes tenant_id; parent Rombel and StudentProfile are tenant-scoped.
- `ExpireExamAttemptsCommand`: cross-tenant candidate scan is maintenance-only; each candidate is revalidated under tenant context and parent locks.
- `CleanupOrphanedMediaCommand`: global candidate scan is maintenance-only; row re-fetch, reference check and storage path are tenant-bound.
- `Phase7dBenchmarkCommand`: explicitly switches to isolated SQLite `database/benchmark.sqlite`, runs migrate:fresh on that connection, and cleans up only the benchmark file; destructive benchmark deletes occur only on that isolated DB connection.
- `InventoryLegacySuperAdminCommand`: reads users/roles/tenant data globally for CLI inventory, has an explicit no-mutation contract; no web route invokes it.
- Installer migration table query is read-only and installer-only.

### 18.2 P1-23 — Monitoring roster join does not independently bind User tenant

- `MonitoringController::participants()` joins `users` to `exam_participants` on `user_id = users.id` and orders by users.name; it does not add `users.tenant_id = exam_participants.tenant_id` in the join.
- `ExamParticipantController::index()` eager-loads `participants.user`; User has no TenantScoped trait.
- Application participant creation validates User tenant_id equals Exam tenant, and ExamParticipant has tenant scope. However `exam_participants.user_id` lacks composite FK `(user_id, tenant_id)` to User, so a corrupt/legacy row could resolve a different-tenant User through these relations.
- This is a defense-in-depth data exposure risk. It is not a demonstrated valid-path cross-tenant exploit. Add explicit tenant equality to join/relationship query and composite FK migration after disposable DB preflight.

### 18.3 Login and session controls — confirmed source details

- `LoginIdentityResolver::resolve()` explicitly filters `deleted_at IS NULL` for User, TeacherProfile and StudentProfile, so soft-deleted users/profiles cannot log in through the normal identifier resolver.
- It requires exactly one LocalInstallationIdentity and exactly one Tenant, then filters candidates by identity tenant. This is a positive control.
- It accepts staff roles ADMIN_MADRASAH, ADMIN, OPERATOR, KAMAD and PROKTOR; API AuthController issues tokens without a narrower active-role allowlist. Web AuthController allows ADMIN_MADRASAH, OPERATOR, KAMAD, GURU, PROKTOR and SISWA.
- AuthWebController sets TenantContext to installation identity tenant after login; IdentifyTenant middleware then derives context from authenticated user on protected requests. Normal login resolver ensures the two match; old Sanctum tokens are not re-resolved through the login resolver.
- `AuthController::me()` returns the authenticated user's role/permission names; it does not return other users' data.
- `User` table retains legacy string column `role` (default STUDENT), but runtime role authorization uses role pivots/hasRole; no active application access to `$user->role` was found. Preserve/inventory legacy column during any future migration; do not treat it as role source of truth.

### 18.4 Media and file handling

- Media uploads validate file type/size and write under private tenant media paths; public storage is not used for question media.
- `MediaController::show()` returns the file response after media owner/tenant and QuestionPolicy checks. Admin can pass the current QuestionPolicy view check and therefore read question media; the remaining issue is the Admin authorization boundary, not public storage.
- Media attachment/detachment locks Question → QuestionVersion → MediaAsset in consistent order and calls QuestionPolicy update. The explicit Admin role check in the controller is followed by policy update, which denies Admin; Admin cannot attach/detach question media through this path.
- Orphan cleanup validates the exact tenant storage path and skips files outside the expected private path.

### 18.5 API resources and serialization

- Only two API Resource classes exist in the active tree: LearningOutcomeResource and LearningOutcomeVersionResource. They are not used by the LearningOutcomeController paths inspected.
- Question, Exam, Assessment, Score and some Monitoring endpoints serialize Eloquent models or hand-built arrays directly. There is no common Admin-safe metadata DTO/resource for Exam or Assessment.
- QuestionOption/QuestionVersion and Exam models do not hide key/token fields globally; any future Admin DTO must be allowlist-based, not rely on hidden attributes that would break Guru authoring.

### 18.6 Test evidence status

- Static source references are recorded; no direct raw SQL tenant-mismatch fixture or monitoring user-tenant mismatch test was run.
- Existing `UserSecurityTest::test_cannot_add_cross_tenant_user_to_exam` verifies application-level rejection; it does not prove database composite FK enforcement.
- Existing `TeacherAccess` coverage and `RombelStudentPivotTest` prove selected tenant/pivot behavior in CI aggregate; no per-T-ID pass is asserted.
- All T-001–T-173 per-ID outcomes remain NOT VERIFIED.

### 18.7 Status

Raw query, file/media and login path review completed statically for all inventoried call sites. P1-23 OPEN. Overall audit still needs final evidence reconciliation, source path coverage checklist and per-file patch plan. No source/DB/config changes.


---

## 19. PENUTUP AUDIT READ-ONLY DAN PATCH PLAN FINAL — 21 November 2026

### 19.1 Cakupan source yang diinventarisasi

Pada source snapshot W0 yang telah diverifikasi:
- 24 API controller files.
- 29 controller files total.
- 14 Livewire components.
- 11 policy files.
- 56 model files (40 TenantScoped, 16 unscoped/custom/global).
- 34 Blade view files.
- 58 migration files.
- 64 test files.
- 30 withoutGlobalScope occurrences across 8 active PHP files (25 plural, 5 singular).

Pemeriksaan jalur akses meliputi routes/web.php, routes/api.php, middleware, login/identity, policies/gates, Livewire mount/action/render/hydration, controller CRUD, services, model relationships, raw queries, serialization, media/private storage, export, cache, audit events, and tenant foreign keys. File inventory and relevant source/test blob references are recorded in the matrices and this report.

### 19.2 Dokumen final yang tersedia di GitHub

1. Audit report: `docs/LAPORAN_AUDIT_READ_ONLY_WEB_ADMIN_2026-11-21.md`.
2. Checkpoint: `docs/CHECKPOINT_WEB_ADMIN_READ_ONLY_2026-11-21.md`.
3. Module matrix: `docs/MATRIKS_AUDIT_WEB_ADMIN_DETAIL_2026-11-21.md`.
4. Test mapping: `docs/MATRIKS_TEST_WEB_ADMIN_T001_T173_MAPPING_2026-11-21.md`.
5. Tenant/query/relation matrix: `docs/MATRIKS_TENANT_QUERY_RELATION_WEB_ADMIN_2026-11-21.md`.
6. Final per-file patch plan: `docs/PATCH_PLAN_FINAL_WEB_ADMIN_2026-11-21.md`.

Patch plan final:
- Commit after no-Web-Guru scope correction: `ba6e8f59fa685b3e341cb12c85f9948d753cb257`.
- Blob SHA: `449cd5ac0d05902bfd2e9be493faf99d8ae081fd`.
- Backup before scope correction: `docs/backup/web-admin-read-only-2026-11-21/PATCH_PLAN_FINAL_WEB_ADMIN_2026-11-21.md.before-scope-correction-no-guru-changes`, commit `03cf99c9bea2720002f310c36a3d96718cbf2f40`, blob `ed4a0277cca12b7f5cecaf0b8266b9a237318f48`.
- Patch plan explicitly excludes changes to `app/Livewire/Teacher/*` and Guru views. Shared schedule service integration into Guru paths remains blocked until separate approval.

### 19.3 Final audit status

- **AUDIT READ-ONLY source review: COMPLETE** for the Web Admin access surfaces inventoried above.
- ADM-001–ADM-023: 23/23 mapped; no module declared complete without full evidence.
- T-001–T-173: 173/173 mapped; every per-ID status remains NOT VERIFIED because no current per-ID run artifact exists. CI #692 aggregate is not substituted for per-ID PASS.
- Existing PHP lint evidence: 338 active PHP files passed syntax check; CI #692 aggregate: 480 tests / 1,942 assertions on source commit `ade5daa6cf853c164c4f51ec6e25ca68ce40b4f2`; W0 snapshot restore 531/531. These are historical evidence only, not proof that the newly defined Admin boundary or final acceptance tests pass.
- Runtime HTTP, Livewire hydration, browser, MySQL 8 composite-FK/overlap concurrency, restore and Madrasah UAT: NOT RUN in this audit session.
- P0/P1 findings remain OPEN until implemented and proven by tests.
- Patch plan final is ready but **NOT APPROVED FOR IMPLEMENTATION**.

### 19.4 Final scope and handover

No source file, database, migration, role, permission, configuration, Web Guru feature, Android, VPS, PR, merge, deployment or production environment was changed. No PR was created/modified/merged/closed.

Next action is not coding: wait for explicit user approval of the final patch plan and any separately identified cross-scope dependencies. The next agent must start from the latest GitHub checkpoint and verify source branch/commit/blob SHAs again before any patch.


---

## 20. FINAL REVIEW CLOSURE — 21 November 2026

Bagian ini adalah status final yang berlaku dan mengungguli setiap narasi fase historis pada bagian sebelumnya.

### 20.1 Status audit dan acceptance
- **Audit source statis Web Admin: SELESAI** untuk source paths/access surfaces yang tercatat pada laporan, matriks dan patch plan.
- **Acceptance testing: BELUM SELESAI.** Tidak ada pernyataan bahwa Web Admin siap diterima atau siap produksi.
- ADM-001–ADM-023: 23/23 memiliki status statis aktual di matriks modul. Status tersebut menggambarkan keadaan source, bukan PASS acceptance.
- T-001–T-173: 173/173 dipetakan; semua status aktual tetap **NOT VERIFIED**. Test method, deklarasi method, source path atau CI aggregate historis tidak dapat mengubah status menjadi PASS.
- P0/P1 tetap OPEN sampai patch yang disetujui diimplementasikan dan pengujian langsung menghasilkan bukti.

### 20.2 Source provenance final recheck
- Patch plan mencantumkan 71 existing source paths dengan blob SHA.
- Seluruh 71/71 path di-fetch ulang dari branch source work/web-admin-w0-20261121; semua blob SHA cocok dengan patch plan.
- Compare source branch terhadap pin 2b4321ce032fd5e67cf022da8564a5287b8cef6f menghasilkan identical, 0 ahead / 0 behind.
- SHA source pada patch plan berasal dari branch source, bukan branch dokumentasi. Dokumen dan backup tetap berada di branch dokumentasi.
- File NEW belum memiliki blob SHA pre-change; file WA-07 yang belum ditemukan tidak boleh dianggap tersedia atau diberi SHA asumsi.

### 20.3 Keputusan batas WA-03 dan dependency Guru
WA-03 tidak memberi izin mengubah jalur Guru. Admin assignment core hanya dapat dikerjakan setelah approval dan harus dipisahkan dari jadwal Guru.

Berstatus **CROSS-SCOPE BLOCKED / memerlukan persetujuan terpisah**:
- Shared ExamScheduleChangeService jika dipakai oleh jalur Guru.
- Integrasi perubahan jadwal pada ExamController.
- Schedule-save pada shared Livewire Admin/ExamManagement bila branch Guru berada di komponen yang sama.
- Perubahan ExamPolicy/shared authorization yang mengubah capability Guru.
- API, route, service, middleware, Livewire, view, atau perilaku lain yang mengubah alur Guru.
- Semua file app/Livewire/Teacher/* dan view Guru.

Jika isolasi Admin/Guru tidak dapat dibuktikan pada file shared, file itu harus dikeluarkan dari patch Admin sampai ada persetujuan lintas-scope. Tidak boleh melakukan perubahan “sekalian” pada Guru.

### 20.4 Dependency status Guru aktif — belum diputuskan
Source W0 menunjukkan User model tidak mendeklarasikan SoftDeletes dan tidak menampilkan atribut status aktif; TeacherProfile memakai SoftDeletes tetapi tidak memiliki atribut status kepegawaian aktif. Tidak soft-deleted atau keberadaan profil saja tidak membuktikan Guru aktif.

Pilihan desain yang belum diterapkan:
1. **Direkomendasikan — dua status eksplisit:** users.account_status (ACTIVE, SUSPENDED, INACTIVE) dan teacher_profiles.employment_status (ACTIVE, ON_LEAVE, INACTIVE, ENDED). Capability hanya jika keduanya ACTIVE, role GURU, relasi TeacherProfile tunggal dan tenant-local, serta permission/scope/schedule checks lolos. Dampak: migration additive untuk dua kolom, indeks/constraint sesuai kebutuhan, audit transisi, dan proses review data legacy. Tidak boleh backfill otomatis ke ACTIVE.
2. **Alternatif minimal:** users.is_active dan teacher_profiles.is_active boolean. Dampak dua kolom additive; kurang ekspresif untuk cuti/berakhir dan tetap membutuhkan review data legacy.
3. **Turunan dari soft-delete/profil saja:** ditolak sebagai definisi aktif karena tidak membedakan suspended, cuti, berhenti, dan profil yang dihapus sementara.

Pilihan 1 adalah rekomendasi desain saja. Schema/migration User dan TeacherProfile harus diperiksa pada salinan MySQL 8 sebelum keputusan. Baris legacy yang tidak dapat dibuktikan harus NEEDS_REVIEW; tidak boleh otomatis memperoleh capability.

### 20.5 Risiko yang belum dapat dibuktikan secara statis
- Data aktual: orphan FK, tenant mismatch, duplikasi, nilai legacy ambigu, schema drift, constraint yang sudah ada dan migration preflight.
- Concurrency Proktor: overlap, double-confirm, revoke-vs-confirm, schedule-change-vs-confirm, expiry, deadlock, retry, lock timeout, isolation-level dan clock skew.
- Perilaku DDL MySQL: kompatibilitas tipe/charset/collation/index, implicit commit, metadata lock, downtime, dan rollback/restore.
- Livewire hydration, stale browser tab/session, Sanctum token, permission revocation dan cache invalidation pada request paralel.
- Serialization/error response/media/export/download/query/cache/queue yang dapat membuka data pada kombinasi role/tenant yang belum diuji runtime.
- Audit tampering via raw SQL/privileged DB account, trigger bypass, cascade, restore dan transaction failure.
- Dampak role migration pada akun lama, password/profile/history dan login Guru/Admin.
- Regresi jadwal/ujian aktif dan penerimaan operasional oleh Admin, Guru, Siswa dan madrasah.

### 20.6 Pengujian wajib MySQL 8 / InnoDB
SQLite tidak boleh dipakai untuk menyimpulkan keamanan concurrency Proktor atau kompatibilitas DDL/FK MySQL. Pengujian wajib pada database MySQL 8/InnoDB disposable hasil restore:
- Dua atau lebih koneksi independen untuk assignment overlap, same Guru/multi exam, multi Guru, multi tenant, revoke/confirm/reschedule/expiry race; konflik harus ditolak secara konsisten.
- Locking current read, isolation level, deadlock, retry/idempotency, lock timeout dan rollback.
- Boundary server-time, timezone, stale schedule_revision, jadwal berubah setelah attempt mulai, dan NEEDS_REVIEW.
- Apply/verify/rollback migration WA-03/WA-04/WA-06 setelah preflight; uji metadata lock dan integritas data.
- Direct SQL mismatch insert/update untuk semua composite FK; database harus menolak tanpa bantuan Eloquent.
- AuditLog update/delete/raw SQL dan privilege bypass; transaksi mutation-audit harus atomik sesuai rancangan.
- Negative Admin HTTP/API/Livewire tests yang memeriksa payload/HTML/media/export; positive Guru regression hanya sebagai uji perilaku existing, bukan perubahan fitur Guru.
- Browser multi-session dan UAT Madrasah pada lingkungan terisolasi.

### 20.7 Stop gate
Patch plan final dan checkpoint telah diperbarui pada branch dokumentasi. Backup dan manifest final review disimpan di docs/backup/web-admin-final-review-2026-11-21/. Source branch tidak berubah. Tidak ada source aplikasi, database, migration, seeder, role, permission, konfigurasi, Web Guru, Android, VPS atau PR yang diubah. **Audit dokumentasi berhenti di sini; tunggu persetujuan eksplisit pengguna sebelum implementasi.**
