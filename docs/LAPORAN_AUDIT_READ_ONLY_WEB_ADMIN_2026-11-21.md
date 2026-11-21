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
