# Agent 2 — Stage 4E Exact-Role, KAMAD Freeze, OPERATOR Retirement & Installation Security Audit

Tanggal: 22 November 2026 (WIB)  
Repository: `zane0184/cbt_november`  
Branch audit: `work/stage4d-account-lifecycle-20261122`  
HEAD saat audit: `09b0ee42555b58c520b6ce3b46243b684e5a9d69`  
Source runtime baseline: `74bf7178f46673e9be90523671490c0749034acd`  
Status: **BLOCKED — GLOBAL ROLE INVENTORY / OWNER APPROVAL / RUNTIME EVIDENCE INCOMPLETE**  
Mode: **READ-ONLY AUDIT + DESIGN DOCUMENTATION ONLY**

## 1. Guardrails dan keputusan produk yang berlaku

Stage ini tidak memberi izin patch runtime, test source, migration, schema/database mutation, workflow/dependency/configuration changes, merge/rebase/cherry-pick, deployment atau production operations.

Keputusan produk final:
- Portal CBT aktif hanya ADMIN_MADRASAH, GURU, SISWA.
- Admin Madrasah tetap aktif dan menjalankan seluruh fungsi administrasi yang sebelumnya disebut Operator.
- Tidak ada akun, login, portal atau role OPERATOR.
- KAMAD tidak mempunyai akses CBT; identitas/appointment/riwayat jabatan tetap boleh disimpan sebagai data administrasi.
- Proktor adalah assignment sementara pada akun GURU, dengan menu di Web Guru; tidak ada akun/login/role/portal Proktor permanen.
- SISWA hanya peserta ujian, bukan identitas Proktor.
- Satu instalasi Web = satu madrasah = satu database akademik sendiri. Tidak ada shared academic database lintas instalasi. tenant_id tetap defense-in-depth.
- Sampai Juni 2027 pekerjaan aktif hanya Web CBT Admin Madrasah, Guru dan Siswa. Android native/Android Studio dan Web Super Admin ditunda; bukan blocker Web CBT.

Keputusan terbaru pemilik proyek tercatat pada Issue #10 komentar #7078921888. Komentar itu merupakan keputusan produk/koordinator, bukan approval teknis lintas-owner.

## 2. AUDIT — remote refs dan delta

| Branch | HEAD terverifikasi | Delta yang diperiksa |
|---|---|---|
| Agent 2 | `09b0ee42555b58c520b6ce3b46243b684e5a9d69` | 12 commit sejak source runtime baseline; seluruh 12 file yang berubah adalah dokumen baru. |
| Agent 1 | `69f16da92452fd28a85aec2a8239c5d5ba9a10dd` | 1 commit sejak HEAD lama `801bb1c550d7cb161c6fcd6d382aba386fc18f94`; hanya manifest keputusan dokumentasi. |
| Agent 3 | `4c695181bafa43ad0f02006b8a63e551b8829a03` | 7 commit sejak HEAD lama `6db04429613537c8219e6d8d873332d25f8c1ced`; perubahan source hanya file test `TeacherPortalFoundationTest.php`, ditambah laporan/checkpoint/backup. Tidak ada perubahan runtime pada delta tersebut. |
| Agent 4 dokumentasi | `05447d93da90063389b822ce2468a459c5bbac11` | 13 commit sejak `edec6c2bddfda5563c907827db7d1ae77775c086`; seluruh perubahan berupa dokumen/backup. |
| Kandidat Agent 4 | `f38ceec7b0653fcf3363eec40f3d4577c323cca8` | Satu commit source mengubah `ExamAttemptController.php`; ownership belum dikonfirmasi dan branch tidak dianggap resmi. |
| main | `feab77228f7108f7f7a45325d7f1fb878c07310b` | Commit terbaru hanya menghapus dokumen WG-01 DOCX yang tidak sengaja masuk main. |

Agent 2 compare `74bf7178...` → `09b0ee4...`: 12/12 file changed adalah dokumen. Source runtime tetap pada SHA baseline `74bf7178...`. Tidak ada merge, rebase, cherry-pick, PR, deployment atau perubahan branch agent lain.

Agent 3 tree pada HEAD aktual menunjukkan test blob `4f1bb48b390b03b2602df6348fd3b9788581e6c9`, berisi 22 test methods secara statis. Ini bukan hasil PHPUnit. Branch Agent 3 menambahkan `routes/teacher.php` pada basisnya, tetapi `bootstrap/app.php` yang diperiksa hanya mendaftarkan routes/web.php, routes/api.php dan routes/console.php; routes/teacher.php belum diregistrasikan. Selain itu, file route tersebut belum memuat menu/route Proktor Guru.

## 3. AUDIT — cakupan source yang benar-benar diperiksa

Pada baseline runtime Agent 2:
- Seluruh 181 file PHP dalam `backend-laravel/app/` di-fetch berdasarkan blob SHA dan diperiksa untuk role, permission, Gate, Policy, otorisasi dan entrypoint.
- Tiga file route (`routes/web.php`, `routes/api.php`, `routes/console.php`), `bootstrap/app.php`, `bootstrap/providers.php`, dan empat file config yang ada di tree diperiksa.
- File terkait Auth, Tenant, SessionVersion, RolePermissionBootstrapper, InstallerService, LicenseStatusController, LegacyRoleMigrationService, seluruh Policy utama, ExamMonitoringScope, controller CBT, Livewire Admin/Guru/KAMAD, serta service Proktor diperiksa.
- Migration/fixture/test yang relevan diperiksa secara terarah, termasuk migration legacy role, migration assignment Proktor, RolePermissionBootstrapperTest, LegacyRoleMigrationServiceTest, LicenseStatusAccessTest, AuthTest, StudentWebLoginTest, AdminCbtBoundaryTest, ProctorAssignmentSecurityTest, RBACEnforcementTest dan RoleDashboardVerificationTest.
- Layout utama, Kamad dashboard view, route guru Agent 3, dan checkpoint Agent 1–4 diperiksa.

**Batas inventory:** file PHP aplikasi 181/181 telah dipindai. Namun seluruh 81 file PHP database/factory/migration/seeder, seluruh 80 test PHP, seluruh 39 resource PHP/Blade, dan seluruh jalur pemanggilan tidak langsung/background belum diperiksa satu per satu. Karena itu inventory global seluruh repository belum dinyatakan selesai. Tidak ada klaim Stage 4E selesai.

## 4. AUDIT — exact-role call-site inventory P0

| File / class / entrypoint | Role yang diperiksa | Risiko statis | Arah desain |
|---|---|---|---|
| `AuthWebController::login/redirectForUser` | ADMIN_MADRASAH, KAMAD, GURU, SISWA | KAMAD masih di allowlist; redirect KAMAD ke `/`, sedangkan root tidak mengenali KAMAD dan mengarahkannya kembali ke login. | Allowlist hanya tiga role; deny KAMAD; redirect hanya tiga portal aktif. |
| API `AuthController::login` | ADMIN_MADRASAH, KAMAD, GURU, SISWA | KAMAD masih dapat memperoleh Sanctum token. | Tolak KAMAD sebelum token dibuat; audit penolakan. |
| `LoginIdentityResolver::resolve` | ADMIN_MADRASAH, KAMAD, GURU, SISWA | Exact-one role sudah diperiksa, tetapi KAMAD masih diizinkan; tidak memeriksa registered_domain terhadap Host. | Allowlist tiga role; host/installation binding fail-closed. |
| `IdentifyTenant::handle` | SUPER_ADMIN saja | Mengabaikan X-Tenant-Override (baik), tetapi tidak menolak KAMAD/OPERATOR legacy pada session/token dan tidak membandingkan tenant user dengan LocalInstallationIdentity pada setiap request. | Middleware server-side account/installation boundary untuk seluruh Web/API request. |
| `EnsureAccountSessionVersion` | Tidak memeriksa role | Memeriksa auth_version browser session, bukan pembekuan KAMAD atau binding token/session ke installation identity. | Pertahankan epoch check; tambahkan gate account status/role/install di server-side. |
| `RolePermissionBootstrapper` | ADMIN_MADRASAH, KAMAD, GURU | KAMAD masih menjadi required role dan menerima curriculum.view, exam.monitor, report.view. | Tidak mewajibkan/mengaktifkan KAMAD; jangan menghapus data historis tanpa rencana. |
| `DatabaseSeeder` / `InstallerService` | ADMIN_MADRASAH, KAMAD, GURU, SISWA; legacy ADMIN/OPERATOR/PROKTOR | Seeder/installer masih mengenal role KAMAD; installer memeriksa role legacy. | Role aktif hanya tiga; appointment/signatory disimpan sebagai data, bukan role akses. |
| `LicenseStatusController` | ADMIN_MADRASAH atau OPERATOR | OPERATOR masih diterima pada status dan refresh lisensi; route hanya auth middleware. | Exact-one ADMIN_MADRASAH + permission yang sesuai. |
| `LegacyRoleMigrationService` | ADMIN, OPERATOR, PROKTOR → role kanonis | Migration otomatis memetakan OPERATOR → ADMIN_MADRASAH dan PROKTOR → GURU. | Jangan jalankan sebelum inventory akun/permission/relasi/konflik dan persetujuan eksplisit. |
| `ExamPolicy` | ADMIN_MADRASAH, KAMAD, GURU, SISWA | Banyak method menggunakan hasRole biasa; startAttempt hanya memeriksa keberadaan SISWA; jalur KAMAD masih ada. | Exact-one actor role untuk tiap capability; KAMAD deny; Proktor assignment bukan role. |
| `AssessmentPolicy` | ADMIN_MADRASAH, OPERATOR, KAMAD, GURU, SISWA | KAMAD dan OPERATOR masih dikenali; Guru/Siswa checks bukan exact-one. | Hanya tiga role aktif dan exact-one sesuai capability. |
| `ExamAttemptPolicy` | ADMIN_MADRASAH, KAMAD, GURU, SISWA | KAMAD masih dapat view/viewScore; Guru checks non-exact; jalur self result harus mensyaratkan exact SISWA. | Pisahkan Guru grading/report dari Student self-result; exact-one. |
| `ReportPolicy` | ADMIN_MADRASAH, KAMAD, GURU, SISWA | KAMAD masih dapat melihat report; self-result return terjadi sebelum exact SISWA check. | KAMAD deny; self-result hanya exact SISWA; Guru tetap assignment/subject scoped. |
| `ExamMonitoringScope::participantQuery` | Exam author, KAMAD, Proktor assignment | Author atau KAMAD mendapatkan seluruh peserta sebelum room filter; bypass author mengaburkan capability author vs Proktor. | Pisahkan author capability dari assigned-Proktor room scope; tidak ada KAMAD path. |
| `StudentExamController` | SISWA dan pengecualian ADMIN_MADRASAH | `hasRole('SISWA') && !hasRole('ADMIN_MADRASAH')` memungkinkan mixed role selain Admin untuk lolos. | Exact-one SISWA pada setiap web entrypoint. |
| API `MediaController` | GURU, ADMIN_MADRASAH, SISWA | Sejumlah checks GURU dan bukan Admin memakai hasRole; media surface perlu room/attempt/snapshot ownership. | Exact-one actor role dan authorization per media/Attempt scope. |
| API `ExamParticipantController` | actor GURU; target SISWA | Target peserta hanya diperiksa hasRole SISWA; actor checks tidak selalu exact-one. | Exact-one SISWA target dan exact-one Guru actor; tenant/assignment. |
| API `AssessmentController`, `ExamController`, `AnalyticsController`, `CurriculumAnalyticsController`, `ScoringController`, `ReportController` | Guru/Siswa/Admin sesuai method | Banyak branch berbasis hasRole biasa meski policy/Gate juga dipanggil. | Inventory per action dan pastikan policy final tidak dapat dilewati lewat cabang controller. |
| `TeacherSubjectAssignmentController` | GURU, ADMIN_MADRASAH | Guru target dan actor Admin memakai hasRole biasa pada sebagian method. | Exact-one actor/target, active profile, subject/rombel/year assignment. |
| Livewire `AdminDashboard`, `AcademicManagement`, `CurriculumManagement`, `UserManagement`, `ExamRoomSessionManagement`, `ProctorAssignmentManagement` | ADMIN_MADRASAH; sebagian ADMIN/OPERATOR legacy | Sebagian mount/action guard memakai hasRole/whereHas role, bukan exact-one. | Exact-one Admin dan permission di mount, hydration, action, export/download. |
| Livewire `QuestionBank` / `ExamManagement` Agent 2 baseline | GURU dan bukan ADMIN_MADRASAH | Bukan exact-one; Agent 3 memiliki boundary TeacherPortalAccess exact-GURU pada branch UI-nya, tetapi belum terintegrasi/CI. | Rekonsiliasi helper Agent 2/3 dan gunakan exact-one pada seluruh HTTP/Livewire/API. |
| `KamadDashboard` + view | ADMIN, ADMIN_MADRASAH, KAMAD | Class/view masih ada dan guard justru menerima beberapa role; route aktif tidak ditemukan pada routes/web.php. | Bekukan akses server-side; pertahankan appointment/history sebagai data saja. |

### 4.1 Temuan model/helper

- `User::hasRole()` memeriksa keberadaan role pada relation; bukan jaminan tepat satu role.
- `QuestionPolicy` pada Agent 2 memiliki helper exact-GURU, tetapi `ExamPolicy` dan banyak Policy lain belum konsisten.
- `ExamAnswerController` dan sebagian jalur `ExamAttemptController` memiliki exact-one SISWA helper; itu tidak menutup jalur lain seperti StudentExamController dan seluruh response sanitization.
- `ExamProctorAssignmentService` memvalidasi target Guru dengan tepat satu role GURU dan profil Guru tenant-lokal; ini pola yang perlu direkonsiliasi ke seluruh entrypoint.

## 5. AUDIT — KAMAD freeze

Temuan:
1. Web login dan API login masih mengizinkan KAMAD.
2. Resolver masih mengizinkan KAMAD.
3. Existing browser session tidak ditolak hanya karena role KAMAD; EnsureAccountSessionVersion hanya memeriksa auth_version.
4. Existing Sanctum token tidak diblokir oleh IdentifyTenant berdasarkan KAMAD; beberapa Policy masih mengizinkan KAMAD.
5. RolePermissionBootstrapper dan DatabaseSeeder masih mengelola role/permission KAMAD.
6. ExamPolicy, AssessmentPolicy, ExamAttemptPolicy, ReportPolicy, ExamMonitoringScope dan KamadDashboard masih mengenali KAMAD.
7. Root redirect untuk KAMAD mengarah ke login loop; tidak ada alasan mempertahankan redirect tersebut.
8. Identitas/appointment Kepala Madrasah dapat tetap menjadi data signatory administratif, bukan akun/permission CBT.

Desain freeze:
- Tolak KAMAD pada resolver, Web/API login, dan server-side middleware seluruh session/token lama.
- Cabut token lama dan invalidasi session/remember-me melalui rencana terkontrol setelah inventaris akun dan persetujuan; jangan menghapus User, appointment, atau audit trail.
- Deny KAMAD pada seluruh Policy/Gate/controller/service/Livewire/report/media/export/monitoring/background path.
- Hilangkan KAMAD dari role bootstrap aktif dan portal redirect; data pejabat tetap terpisah dari hak akses.
- Catat perubahan dan access-denial audit event.
- Admin Madrasah tidak boleh ikut dibekukan.

## 6. AUDIT — OPERATOR retirement

Temuan:
- `LicenseStatusController::__invoke/refresh` masih menerima role OPERATOR.
- Route `/operator/dashboard` masih ada sebagai compatibility redirect ke `/admin/dashboard`; group memiliki auth/tenant/license, tetapi redirect route sendiri tidak melakukan exact-one Admin + permission preflight.
- `LegacyRoleMigrationService` memetakan ADMIN dan OPERATOR menjadi ADMIN_MADRASAH, PROKTOR menjadi GURU.
- Migration `2026_11_22_000003_migrate_legacy_roles_to_canonical_roles.php` memanggil service tersebut. Ini migration data yang dapat mengubah role_user dan users.role saat dijalankan; tidak dijalankan pada audit ini.
- Migration service membuat `legacy_role_migration_records`, menyimpan nilai role sebelum/sesudah, memeriksa konflik dan menjalankan transaksi. Itu tidak menggantikan kebutuhan inventory akun dan approval.
- `RoleDashboardVerificationTest` masih memuat fixture role OPERATOR/PROKTOR dan ekspektasi route `/proktor/assignments`; ini bertentangan dengan keputusan portal terbaru dan harus direkonsiliasi, bukan dianggap spesifikasi aktif.
- `RolePermissionBootstrapperTest` masih membuat KAMAD dan OPERATOR sebagai fixture historis.
- `LegacyRoleMigrationServiceTest` secara eksplisit mengharapkan OPERATOR otomatis dipetakan ke ADMIN_MADRASAH. Ini hanya test migration; bukan persetujuan menjalankannya pada data produksi.

Desain retirement:
- OPERATOR tidak dapat login, mengakses API, lisensi, route, Livewire, service atau export.
- Semua fungsi administrasi dipetakan ke ADMIN_MADRASAH dengan permission spesifik.
- Compatibility redirect hanya boleh berakhir pada exact-one ADMIN_MADRASAH + permission; OPERATOR tidak boleh mendapat akses dari redirect.
- Inventaris akun legacy dilakukan pada salinan DB disposable; identitas sensitif tidak masuk commit/laporan publik.
- Transisi akun dilakukan hanya setelah konflik role, profile, permission, assignment, audit/history, idempotency dan rollback disetujui.
- Jangan menjalankan migration legacy pada database produksi dalam tahap ini.

## 7. AUDIT — Proktor assignment dan room scope

Temuan:
- Source `ExamProctorAssignment` / `ExamProctorAssignmentService` sudah memiliki assignment tenant-scoped dengan Exam, SessionRoom, schedule_revision, target Guru, actor Admin, waktu, status, alasan, revoke dan audit event.
- Admin UI aktif berada pada `/admin/proctor-assignments`; layout menampilkan menu assignment Proktor untuk Admin.
- Tidak ditemukan menu Proktor dalam layout Web Guru pada source yang diperiksa.
- Branch Agent 3 memiliki `routes/teacher.php`, tetapi `bootstrap/app.php` pada branch itu tidak mendaftarkannya. Route file itu juga tidak mendefinisikan menu/route assignment Proktor.
- `ExamPolicy::monitor` memisahkan Guru exact-one dan assignment/author, tetapi `ExamMonitoringScope` masih bypass room filter untuk exam author dan KAMAD.
- `MonitoringController` memiliki beberapa data surface (summary, participants, attempt, events); setiap surface perlu menerapkan scope server-side yang sama.
- Test `ProctorAssignmentSecurityTest` memverifikasi assign/confirm/revoke/expiry dan akses monitoring tingkat Exam, tetapi belum membuktikan peserta/Attempt/media/export selalu room-scoped pada multi-room.
- Lock order assignment/revoke dan scheduler/recovery belum mendapat approval lintas-owner Agent 1/2; tidak boleh mengasumsikan service saat ini sudah memenuhi global lock contract.

Desain:
- Guru login dengan akun Guru sendiri; menu Proktor hanya tampil saat assignment valid.
- Assignment valid hanya jika exact-one GURU, profile aktif, tenant/install cocok, assignment CONFIRMED, belum dicabut, waktu server dalam rentang, schedule_revision cocok, Exam/Session/SessionRoom aktif, room aktif dan participant placement sah.
- Proktor hanya mendapat operasi operasional yang disetujui untuk room assignment; tidak otomatis melihat answer key, nilai, bank soal, konfigurasi, Force Submit atau assignment Guru lain.
- Exam author adalah capability berbeda. Keputusan apakah author boleh melihat seluruh peserta harus menjadi permission/ability eksplisit terpisah, bukan bypass yang otomatis memberi capability Proktor.
- Dashboard, participant list, attempt detail, progress, events, media, report, export, API, Livewire, download dan background jobs harus menerapkan scope yang sama.
- Setiap mutasi Admin/Proktor dicatat dengan actor Guru/Admin, reason, timestamp, target scope dan hasil.

## 8. AUDIT — Host/domain dan installation identity

Temuan:
- `LocalInstallationIdentity` memiliki `registered_domain`, tenant_id dan installation metadata.
- `InstallationIdentityService` membuat satu identity untuk satu tenant dan menurunkan registered_domain dari APP_URL HTTPS.
- `LoginIdentityResolver` menolak jika identity bukan tepat satu atau tenant lokal bukan tepat satu; user yang dicari dibatasi ke tenant identity. Namun resolver tidak membandingkan registered_domain dengan Host request.
- `IdentifyTenant` mengabaikan X-Tenant-Override dan menetapkan context dari tenant user, tetapi tidak membandingkan tenant user dengan LocalInstallationIdentity pada setiap request.
- `bootstrap/app.php` tidak menunjukkan konfigurasi eksplisit TrustHosts/TrustProxies. Jangan mempercayai X-Forwarded-Host dari client langsung.
- Sanctum login mengembalikan installation_id pada response, tetapi source yang diperiksa belum membuktikan bearer token terikat secara kriptografis/persisten pada installation identity penerbit.
- Host mismatch, session lintas instalasi, token lintas instalasi, identity ganda/tidak ada, tenant mismatch, X-Tenant-Override dan body/query tenant_id belum diuji pada disposable setup.

Desain host/install binding:
1. Normalisasi canonical Host dan bandingkan dengan registered_domain.
2. Trust forwarded host/proto hanya dari reverse proxy yang eksplisit dipercaya; direct client header tidak dapat mengganti host.
3. Tepat satu LocalInstallationIdentity dan tenant lokal harus berlaku.
4. identity.tenant_id harus sama dengan user.tenant_id pada setiap request.
5. Session/token harus terikat ke installation_id penerbit; token dari instalasi A tidak berlaku di B.
6. X-Tenant-Override dan tenant_id dari browser/body/query tidak boleh mengganti TenantContext.
7. Host/identity mismatch harus fail-closed untuk Web, API, Livewire, media, export dan background task yang menerima identitas instalasi.

## 9. AUDIT — isolasi dua instalasi

- `config/database.php` membaca DB credential installer dari `storage/app/private/installer-database.php`; konfigurasi Redis default prefix berasal dari slug APP_NAME.
- `.env.example` menunjukkan SESSION_DRIVER=file, SESSION_ENCRYPT=true, FILESYSTEM_DISK=local, QUEUE_CONNECTION=sync, CACHE_STORE=file; Redis default DB 0 dan cache DB 1.
- Jika beberapa instalasi memakai Redis bersama dengan APP_NAME/default REDIS_PREFIX yang sama, default prefix dapat bertabrakan. Harus ada REDIS_PREFIX unik per instalasi atau Redis/database/credential terpisah.
- File config eksplisit yang terlihat pada tree hanya database, installer, license dan sanctum; konfigurasi cache/session/queue/filesystem/logging per instalasi belum dapat dibuktikan dari file config tersebut.
- Belum ada uji Instalasi A/B untuk database credential, storage, cache key, Redis lock, queue, session/cookie, scheduler mutex, temp/export/log, backup/restore.

Desain uji A/B disposable:
- Credential DB A tidak dapat membaca DB B.
- Storage root dan file A tidak dapat diakses B.
- Cache/Redis prefix dan lock A/B unik.
- Session/cookie dan Sanctum token tidak dapat dipakai lintas instalasi.
- Queue/job dan scheduler mutex tidak tertukar.
- Temp/export/log/backup memiliki root per instalasi.
- Restore A tidak mengubah B.
- Tidak ada data akademik lintas instalasi.

Semua pengujian di atas: **NOT RUN**.

## 10. AUDIT — Reporting dan Import

### Reporting
- Source `ExamSummaryReportService` sebelumnya teridentifikasi mengagregasi histori score, bukan hanya current pointer FINALIZED.
- Immutable ReportSnapshot belum terbukti tersedia.
- Null/ungraded belum boleh disamakan dengan nilai 0.
- Desain final: current pointer FINALIZED saja; status NOT_SCORED/MANUAL_REVIEW eksplisit; preview terpisah dari final; publikasi menghasilkan immutable snapshot; revisi snapshot baru; idempotency; PDF final dirender dari snapshot; simpan actor, revision, timestamp, timezone, template/schema revision, payload hash dan artifact hash.
- Identitas signatory berasal dari appointment resmi, bukan akses KAMAD.
- Reporting belum dinyatakan siap.

### Import XLSX/DOCX
- Issue #8 dan #9 masih OPEN; semua komentar yang ditemukan berasal dari akun koordinator `zane0184`, bukan approval teknis terpisah yang dapat diverifikasi.
- Usulan satu QuestionDraftDTO, adapter XLSX/DOCX berbeda, dan reuse ImportBatch/ImportBatchRow tetap NEEDS DESIGN/PENDING APPROVAL.
- Tidak ada persetujuan parser DOCX/dependency baru, UI/API import, migration atau auto-publish.
- SHORT_ANSWER tetap BLOCKED sampai accepted_answers tersimpan di authoring, immutable snapshot dan Scoring end-to-end.
- ESSAY tetap manual review.
- PDF/OCR ditunda.

## 11. DESAIN — matriks exact-role yang wajib diuji

| Identitas | Hasil yang diharapkan |
|---|---|
| ADMIN_MADRASAH saja | Hanya portal Admin dan permission administrasi; bukan author/Student/Proktor. |
| GURU saja | Portal Guru; authoring sesuai subject/assignment; menu Proktor hanya jika assignment aktif dan valid. |
| SISWA saja | Portal Siswa dan data peserta sendiri yang eligible; bukan Guru/Admin/Proktor. |
| KAMAD saja | Deny login Web/API, session, token, route, policy, report, monitoring, media dan export. |
| OPERATOR legacy saja | Deny seluruh akses CBT sampai transisi terkontrol disetujui; tidak otomatis menjadi Admin. |
| ADMIN_MADRASAH + GURU | Deny mixed identity; tidak boleh memilih capability berdasarkan salah satu role. |
| GURU + SISWA | Deny mixed identity; tidak boleh memulai ujian sebagai Siswa atau author sebagai Guru. |
| KAMAD + GURU | Deny; KAMAD tidak boleh lolos melalui role lain. |
| OPERATOR + ADMIN_MADRASAH | Deny sampai konflik role diselesaikan dan disetujui. |
| Role kosong/unknown/duplikat | Deny; unique constraint diuji untuk duplikat pivot. |
| Proktor assignment | Bukan role; akun harus exact-one GURU dan assignment valid. |
| SISWA sebagai target Proktor | Selalu ditolak. |

Setiap kombinasi diuji pada Web login, API login, existing session, Sanctum token, middleware, Gate/Policy, route, controller, Livewire mount/hydration/action, service, report/export/media, scheduler/job dan seluruh endpoint CBT.

## 12. PENGUJIAN — status aktual

| Pemeriksaan | Status | Bukti/batas |
|---|---|---|
| Remote refs Agent 1–4, kandidat dan main | PASS (read-only) | SHA HEAD berhasil di-fetch. |
| Compare Agent 2 baseline → HEAD | PASS (read-only) | 12 file berubah, semuanya docs. |
| Scan file PHP app Agent 2 | PASS (statis) | 181/181 file PHP app dipindai untuk role/permission signals; bukan runtime test. |
| Routes/bootstrap/config | PASS (statis) | 3 route files, bootstrap dan 4 config diperiksa. |
| Seluruh migration/test/resource repository | NOT RUN / PARTIAL | Hanya file relevan dipilih; global inventory belum lengkap. |
| Issue #8/#9/#10 approval audit | PASS (read-only) | #8: 7 komentar, #9: 2, #10: 16; semua komentar tercatat dari akun zane0184. Owner approval teknis terpisah belum dapat diverifikasi. |
| Combined CI status A2 source SHA | UNKNOWN | Status checks kosong. |
| Workflow runs exact SHA A2 | UNKNOWN | Fetch commit workflow runs mengembalikan kosong. |
| CI exact SHA Agent 3 | UNKNOWN | Status checks kosong; tidak menyatakan PHPUnit PASS. |
| CI exact SHA kandidat Agent 4 | UNKNOWN | Status checks kosong. |
| PHP lint / route:list / PHPUnit | NOT RUN pada Stage 4E ini | Tidak ada eksekusi lokal. |
| MySQL 8 disposable / concurrency / two-install restore | NOT RUN | Tidak ada database disposable yang dijalankan. |
| Browser E2E / UAT | NOT RUN | Tidak ada sesi browser/madrasah. |
| Database produksi | NOT TOUCHED | Tidak ada koneksi atau perubahan produksi. |

## 13. IMPLEMENTASI

Dokumentasi audit dan checkpoint Stage 4E dibuat sebagai file baru pada branch Agent 2. Tidak ada file source/test/migration/schema/config/workflow/dependency/database yang diubah. Tidak ada file lama ditimpa sehingga tidak ada backup overwrite yang diperlukan.

## 14. NEXT GATE — Stage 4E belum selesai

1. Lanjutkan inventory migration, seeder, seluruh test, Blade/resource, route binding, Gate/Policy, exports/media, scheduler/jobs dan semua call-site tidak langsung sampai cakupan global terbukti.
2. Inventaris akun legacy KAMAD/OPERATOR/PROKTOR pada salinan database disposable; jangan akses atau mengubah DB produksi.
3. Susun patch plan terpisah untuk KAMAD freeze, OPERATOR retirement, exact-one role helper, LicenseStatusController, Installer/Seeder/RolePermissionBootstrapper, StudentExamController, Exam/Assessment/Attempt/Report Policy, media/monitoring/export dan Livewire.
4. Dapatkan jawaban owner Agent 1 untuk global lock order, scheduler, assignment lifecycle/recovery.
5. Dapatkan jawaban owner Agent 3 untuk menu Proktor Web Guru, route registration, UI boundary, QuestionPolicy reconciliation dan room-scoped surfaces.
6. Dapatkan jawaban owner Agent 4 untuk branch ownership dan kontrak Web Siswa; kandidat branch tidak diadopsi tanpa konfirmasi.
7. Finalisasi host/domain/install binding dan konfigurasi isolasi dua instalasi.
8. Finalisasi ReportSnapshot dan QuestionDraftDTO/import contract.
9. Jalankan PHP lint, PHPUnit, MySQL 8 disposable, HTTP/browser, concurrency dan restore tests pada exact SHA setelah gate/approval tersedia.
10. Ajukan rencana patch runtime terpisah untuk persetujuan koordinator. Tidak ada migration/deployment produksi dalam Stage 4E ini.

**Kesimpulan:** Stage 4E **BELUM SELESAI**. Celah P0 telah ditemukan dan dipetakan, tetapi global inventory, owner approvals, exact-SHA CI, disposable database tests dan runtime verification masih BLOCKED/NOT RUN. Sistem tidak boleh dinyatakan aman atau siap operasional hanya dari audit statis ini.
