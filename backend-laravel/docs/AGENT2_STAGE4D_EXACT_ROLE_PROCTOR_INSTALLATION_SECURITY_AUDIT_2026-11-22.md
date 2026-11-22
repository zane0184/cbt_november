# Agent 2 — Audit Exact-Role, Proktor Assignment, KAMAD/OPERATOR, Installation Identity dan Infrastruktur

Tanggal pemeriksaan: 22 November 2026, 18:32 WIB  
Repository: zane0184/cbt_november  
Branch: work/stage4d-account-lifecycle-20261122  
HEAD sebelum dokumen: 462cadc96a95d091bd89819dbc106b4fbd67e1cd  
Source runtime yang diaudit: 74bf7178f46673e9be90523671490c0749034acd  
Status: READ-ONLY AUDIT / DESIGN — IMPLEMENTATION GATED

## 0. Ruang lingkup yang mengikat

Sampai Juni 2027, cakupan produk hanya Web Admin Madrasah, Web Guru, dan Web Siswa. Super Admin, Android native, Android Studio, integrasi perangkat Android, dan device binding khusus Android ditunda. API yang digunakan Web Siswa tetap dipertahankan: daftar/detail ujian, snapshot/soal dan media, answer save, resume, serta submit.

Topologi distribusi final: satu instalasi untuk satu madrasah, satu database akademik, storage, kredensial, konfigurasi, domain, session/cache/queue/scheduler, dan backup sendiri. Tidak ada shared academic database atau sinkronisasi akademik lintas madrasah. tenant_id tetap defense-in-depth di dalam instalasi.

Portal aktif hanya ADMIN_MADRASAH, GURU, dan SISWA. KAMAD bukan portal/akun operasional. OPERATOR adalah legacy. Proktor adalah penugasan sementara yang melekat pada akun GURU; Guru login dengan akun Guru sendiri dan membuka menu khusus Proktor di Web Guru. Tidak ada akun Siswa, login/portal Proktor terpisah, atau role permanen PROKTOR.

## 1. AUDIT — refresh branch dan sumber

### 1.1 Remote refs yang diverifikasi

| Komponen | Branch | HEAD remote |
|---|---|---|
| Agent 2 | work/stage4d-account-lifecycle-20261122 | 462cadc96a95d091bd89819dbc106b4fbd67e1cd |
| Agent 1 | work/stage4b-room-session-20261122 | 801bb1c550d7cb161c6fcd6d382aba386fc18f94 |
| Agent 3 | work/web-guru-foundation-20261122 | 6db04429613537c8219e6d8d873332d25f8c1ced |
| Agent 4 dokumentasi | work/agent4-web-siswa-audit-20261122 | edec6c2bddfda5563c907827db7d1ae77775c086 |
| Kandidat Agent 4 | work/import-guru-siswa-implementation-20261122 | f38ceec7b0653fcf3363eec40f3d4577c323cca8 |

Agent 1 setelah source baseline Stage 4B d2532792471966f1d0a9d9cbdd4d65e7c84547f0 memiliki rangkaian commit dokumentasi; commit terakhir 801bb1c menambahkan keputusan koordinator tentang tiga portal, KAMAD frozen, dan Proktor sebagai assignment Guru. Tidak ada bukti perubahan source runtime Stage 4B dalam rangkaian commit dokumentasi tersebut.

Agent 3 HEAD 6db0442 adalah commit dokumentasi setelah test source 4dc407d091fc6cf786260052cfd86d836edc5908. Test source terakhir yang dilaporkan memiliki 22 method. Source UI/helper Agent 3 tidak diubah pada rangkaian test hardening yang diperiksa.

Agent 4 dokumentasi HEAD edec6c2 merupakan branch dokumentasi. Kandidat implementasi f38ceec belum dikonfirmasi oleh owner sebagai branch resmi. Tidak ada merge/rebase/cherry-pick/PR integrasi.

### 1.2 Dokumen yang dibaca

Agent 2:
- CHECKPOINT_AGENT2_WEB_ONLY_NEXT_GATE.md
- AGENT2_SCOPE_WEB_ONLY_AND_INSTALLATION_BOUNDARY.md
- AGENT2_EXACT_ROLE_BACKEND_MATRIX.md
- AGENT2_SINGLE_MADRASAH_DATABASE_ISOLATION.md
- AGENT2_EXAM_ATTEMPT_LOCK_ORDER_RECONCILIATION.md
- AGENT2_REPORTING_CURRENT_SCORE_IMMUTABLE_SNAPSHOT.md
- AGENT2_IMPORT_XLSX_DOCX_CONTRACT_STATUS.md
- Laporan utama dan addendum continuation sebelumnya.

Agent 1:
- CHECKPOINT_STAGE4B_DECISION_CLOSURE_2026-11-22.md
- ADDENDUM_CROSS_AGENT_DECISION_CLOSURE_2026-11-22.md
- MATRIKS_OWNERSHIP_AGENT1_AGENT2_AGENT3_AGENT4_STAGE4B.md
- Matriks lock order, acceptance test, dan preflight disposable yang terkait.

Agent 3:
- CHECKPOINT_AGENT3_WEB_ONLY_NEXT_GATE.md
- AGENT3_P0_HTTP_LIVEWIRE_TEST_HARDENING_2026-11-22.md
- AGENT3_QUESTIONVERSION_SNAPSHOT_MEDIA_CONTRACT_REVIEW_2026-11-22.md
- Dokumen UI import dan curriculum alignment.

Agent 4:
- CHECKPOINT_AGENT4_WEB_SISWA_NEXT_GATE.md
- AGENT4_BACKEND_CONTRACT_REQUEST_AGENT2.md
- Kontrak IndexedDB, threat model admission/media, submit barrier, room recovery, dan reconciliation yang dirujuk checkpoint.

### 1.3 Issue dan approval

- Issue #8 XLSX/PDF: OPEN, 7 komentar.
- Issue #9 DOCX: OPEN, 2 komentar.
- Issue #10 lifecycle Exam–ExamSession lintas-agent: OPEN, 14 komentar saat refresh.

Komentar #7078261415 adalah keputusan koordinator mengenai satu instalasi per madrasah. Komentar #7078306561 adalah review teknis QuestionVersion/snapshot/media berstatus NEEDS DESIGN. Seluruh komentar Issue #10 yang diperiksa memakai author login zane0184. Karena tidak ada identitas owner Agent 1/3/4 yang terpisah dan dapat diverifikasi dari metadata tersebut, komentar koordinasi/review tidak dihitung sebagai approval owner lintas-agent.

Status approval:
- Agent 1: keputusan produk portal/KAMAD/Proktor telah dicatat pada branch koordinasi; approval final lintas-owner untuk lock order, canonical session, scheduler, dan recovery belum terbukti.
- Agent 3: NEEDS DESIGN untuk QuestionVersion/snapshot/media dan UI monitoring; bukan approval backend.
- Agent 4: owner/branch implementasi resmi PENDING.
- Agent 2: audit ini adalah keputusan desain internal Agent 2; belum ada izin patch runtime dari koordinator.

## 2. AUDIT — authentication dan exact-role

### 2.1 Login dan tenant resolver

| File/class | Blob SHA | Perilaku source | Temuan |
|---|---|---|---|
| AuthWebController | b8e04102858652c2fe9042f8f680c511fcbccf63 | Menolak role count bukan satu; allow-list ADMIN_MADRASAH, KAMAD, GURU, SISWA | KAMAD masih dapat login |
| API AuthController | ed0d7c9f51de4190c7f150b92c27a89e93703b40 | Memakai role count satu dan allow-list yang sama; membuat Sanctum token | KAMAD masih dapat login API |
| LoginIdentityResolver | c1f56029da5b8fdb5d7f80380269637b7d1c3ebe | Mensyaratkan tepat satu LocalInstallationIdentity dan satu Tenant; role count satu; allow-list masih memuat KAMAD | registered_domain tidak divalidasi terhadap request host |
| IdentifyTenant | 376a4b2771c3d9a6b128ad847c150d47ac2977db | Reset context, menolak SUPER_ADMIN, mengambil tenant dari authenticated user; mengabaikan X-Tenant-Override | Tidak membandingkan tenant user dengan LocalInstallationIdentity pada setiap request |
| LocalInstallationIdentity | d6f36438e12be0c64b8a9ecaa3d4e03994ea2d8c | Menyimpan tenant_id, installation_id, key material/fingerprint, registered_domain, product metadata | registered_domain tersedia tetapi belum dipakai resolver untuk host binding |
| bootstrap/app.php | ee03f2b1a8a0e84ced1fa6915d45ef4e46b33876 | statefulApi, alias tenant → IdentifyTenant, middleware priority Authenticate → IdentifyTenant → SubstituteBindings | Tidak terlihat konfigurasi eksplisit trustHosts/TrustProxies pada file ini |

AuthWebController::redirectForUser masih mengarahkan KAMAD ke /. Route root hanya mengenali ADMIN_MADRASAH/GURU/SISWA dan mengarahkan role lain ke /login. Ini berpotensi menghasilkan siklus redirect bagi sesi KAMAD.

### 2.2 Role portal dan jalur legacy

| Identitas | Keputusan | Source saat ini | Status |
|---|---|---|---|
| ADMIN_MADRASAH | Portal aktif | Web/API login diizinkan | Exact-one login ada; seluruh policy/entrypoint masih perlu konsistensi |
| GURU | Portal aktif | Web/API login diizinkan | Exact-one login ada; permission dan assignment harus diperiksa ulang di setiap capability |
| SISWA | Portal aktif | Web/API login diizinkan | Exact-one login ada; StudentExamController dan beberapa API masih memakai hasRole biasa |
| KAMAD | Tidak boleh login/portal/API/monitoring | Login allow-list, resolver, bootstrapper, policy, report, monitoring masih mengenali KAMAD | P0 NON-COMPLIANT |
| OPERATOR | Legacy, bukan role portal | LicenseStatusController menerima ADMIN_MADRASAH atau OPERATOR; URL /operator/dashboard masih redirect kompatibilitas ke Admin | P0 LEGACY ACCESS PATH |
| PROKTOR | Bukan role permanen; assignment Guru | LegacyRoleMigrationService memetakan PROKTOR → GURU; RolePermissionBootstrapper tidak mem-bootstrap role PROKTOR | Transisi perlu audit data dan test |
| SUPER_ADMIN | Di luar portal CBT fase ini | AuthWebController menolak; IdentifyTenant menolak | Tidak boleh menjadi jalur tenant |

RolePermissionBootstrapper blob 3eea44756b0fec6e207382a45952579a23731b95 masih memasukkan KAMAD sebagai required role, memberi curriculum.view, exam.monitor, report.view, dan mengelola grant KAMAD. Ini bertentangan dengan keputusan KAMAD frozen. Tidak boleh sekadar menghapus record role/akun tanpa inventory, backup, dan rencana preservasi appointment/history.

LegacyRoleMigrationService blob d82e0789fe4ef74ae81e2576052e69f597ac45fd memiliki pemetaan ADMIN → ADMIN_MADRASAH, OPERATOR → ADMIN_MADRASAH, PROKTOR → GURU. Jalur migrasi Proktor memeriksa user aktif, tenant, role set legacy/canonical dan TeacherProfile aktif. Ini arah transisi yang sesuai secara prinsip, tetapi migrasi aktual dan hasil data belum diverifikasi pada database disposable; jangan menjalankan pada produksi.

### 2.3 Matriks exact-role backend — temuan P0

| File/class | Blob SHA | Temuan source |
|---|---|---|
| ExamPolicy | c05d96ef932fdc81b67336240af4e1726864c3d6 | viewAny/view/preview/create/update/delete/manageQuestions/manageParticipants/generateToken/lock/close/publishResults banyak memakai hasRole; startAttempt hanya memeriksa keberadaan SISWA; jalur KAMAD masih ada |
| AssessmentPolicy | 1aea2b90d92139c9c6935c60e94ca43e825abb29 | viewAny/view/create/update/delete memakai hasRole; view mengenali OPERATOR/KAMAD |
| ExamAttemptPolicy | 00a3a4119f3983de6d940fd8f352a541336c9e12 | view/viewScore mengenali KAMAD/OPERATOR; score/grade/finalize/reopen memakai hasRole GURU |
| ReportPolicy | 0fbd934bde79ebd7ae3fe4fe08f09cb4dc9e46ce | Jalur KAMAD; role GURU/ADMIN memakai hasRole |
| StudentExamController | 981fd29d24906c158d4dda5889950fdc43f6ec3d | index/show/result memakai SISWA dan penolakan ADMIN, bukan tepat satu role SISWA |
| LicenseStatusController | 2e6a718c54e8aac573790cf3b117c1822759f72b | whereIn ADMIN_MADRASAH/OPERATOR; OPERATOR masih dapat masuk ke halaman/status license |
| ExamMonitoringScope | 11b455954f974d1c60ed5c49700368f8d37c09c1 | isExamAuthor atau KAMAD memperoleh seluruh participant query; Proktor assignment room-scoped hanya pada cabang lain |
| CurriculumAnalyticsController | 2fe90e6a3bbf398be69260d053e09471bb886396 | Gate beberapa endpoint memakai GURU atau ADMIN presence; bukan exact-one |
| ExamParticipantController | 6720e53c879f9cd2f6f82dc63c0ff37d5f62ab79 | Target peserta cukup hasRole SISWA; actor Guru diperiksa dengan hasRole; belum exact-one |
| MediaController | f852c1b3a7247efdbb0c309fb0413bbe5e3eaa62 | Beberapa action memakai GURU dan bukan ADMIN; belum exact-one GURU/SISWA untuk seluruh cabang |
| RolePermissionBootstrapper | 3eea44756b0fec6e207382a45952579a23731b95 | KAMAD masih required dan mendapat grant aktif |

QuestionPolicy pada branch Agent 2 dilaporkan memakai helper exact-GURU permission (blob af2b3be5fdee31b857c56fdf09ef54ffd02ed94e). Namun branch Agent 3 yang diverifikasi masih mempunyai QuestionPolicy lama blob 81e3cb6c82f1a2f5e15deacf8b8ea50f8a494566. Perbedaan ini wajib direkonsiliasi saat integrasi; tidak boleh menganggap kedua branch memiliki boundary yang sama.

Kesimpulan: exact-one role pada resolver login belum menjamin seluruh capability aman. API, policy, service direct invocation, Livewire hydration/action, file/media, export, queue dan scheduler harus melakukan validasi capability secara independen. Role campuran yang harus ditolak termasuk GURU+SISWA, ADMIN_MADRASAH+GURU, ADMIN_MADRASAH+SISWA, KAMAD+role portal, OPERATOR+role portal, dan role legacy lainnya.

## 3. AUDIT — Proktor harus memakai akun Guru

### 3.1 Source yang tersedia

| File/class | Branch / blob SHA | Temuan |
|---|---|---|
| ExamProctorAssignmentService | Agent 1 / c968ce68c0decace1c35a0ccb3b2b4449c057656 | assign/confirm/revoke dalam transaksi; assignment terikat tenant, Exam, SessionRoom, schedule_revision, user Guru, waktu, actor, reason, status dan audit |
| ExamProctorAssignment | Agent 1 / 88a1c2319200342eb2dc2a5993e95a8b6a32dbfc | Menyimpan proctor_user_id, assigned_by, SessionRoom, waktu, status, revoke history; confirmedNow membatasi status, periode, room/session dan schedule revision |
| ProctorAssignmentManagement | Agent 1 / c312199e473f47e2d779d08ca01c84df4b631514 | Livewire admin component; assertCanManage memakai hasRole ADMIN_MADRASAH + permission, belum exact-one |
| ExamPolicy::monitor | Agent 2 / c05d96ef932fdc81b67336240af4e1726864c3d6 | Guru diperiksa tepat satu role GURU dan exam.monitor; monitoring dibolehkan untuk exam author atau assignment Proktor aktif; KAMAD bypass masih ada |
| ExamMonitoringScope | Agent 1/2 shared / 11b455954f974d1c60ed5c49700368f8d37c09c1 | Proktor assignment membatasi participant pada SessionRoom yang ditugaskan; exam author dan KAMAD mendapat bypass seluruh peserta |
| ExamParticipantController | Agent 2 / 6720e53c879f9cd2f6f82dc63c0ff37d5f62ab79 | Endpoint list memakai ExamMonitoringScope, tetapi keamanan akhirnya bergantung pada scope dan policy di atas |

Service memeriksa calon Proktor sebagai tepat satu role GURU, user tidak soft-deleted, tenant cocok, dan memiliki TeacherProfile tenant-lokal aktif. Assignment memiliki status PENDING/CONFIRMED/NEEDS_REVIEW/REVOKED/EXPIRED/COMPLETED sesuai lifecycle source; penugasan harus memiliki periode valid, schedule_revision yang sama, SessionRoom aktif, dan tidak konflik dengan assignment CONFIRMED lain. Audit event yang terlihat meliputi EXAM_PROCTOR_ASSIGNMENT_CREATE, RENEW, CONFIRM, dan REVOKE.

### 3.2 Gap Proktor terhadap keputusan koordinator

1. Source UI yang ditemukan hanya route /admin/proctor-assignments dan komponen Livewire Admin ProctorAssignmentManagement. Tree Web Guru Agent 3 tidak menunjukkan menu/route Guru khusus Proktor. Menu khusus di Web Guru belum terbukti diimplementasikan.
2. Admin assignment component dan ExamPolicy assignment abilities masih menggunakan hasRole biasa, bukan exact-one ADMIN_MADRASAH.
3. ExamMonitoringScope memberi bypass kepada exam author dan KAMAD untuk seluruh peserta. Bypass KAMAD harus ditutup. Monitoring sebagai Guru pemilik Exam dan fungsi Proktor harus dipisah agar penugasan Proktor tetap room-scoped; penugasan Proktor tidak boleh menjadi jalan untuk melihat room lain.
4. ExamPolicy::monitor mengizinkan Guru author atau Guru dengan assignment. Kewenangan author dan kewenangan Proktor perlu dibedakan secara eksplisit di backend dan menu.
5. Urutan lock Proktor service saat ini perlu direkonsiliasi: beberapa jalur mengunci Exam/Room/SessionRoom/Session dan User sebelum Assignment. Ini belum disetujui terhadap parent-first global lock order Agent 1/2.
6. Revoke assignment mencatat actor/reason, tetapi perilaku terhadap monitoring session aktif dan kebutuhan recovery Attempt masih menunggu kontrak Agent 1/2/4.
7. Tidak ditemukan bukti test end-to-end bahwa menu Guru muncul hanya pada assignment CONFIRMED yang sedang berlaku dan langsung tertutup/ditolak setelah expiry/revoke.

### 3.3 DESAIN Proktor — keputusan produk final, kontrak backend/UI belum selesai

Model yang wajib:

GURU ACCOUNT → PROCTOR ASSIGNMENT → PROCTOR MENU DI WEB GURU

- Guru login memakai akun Guru sendiri.
- Menu Proktor hanya muncul bila Guru memiliki assignment CONFIRMED, belum revoked, berada pada periode tugas, tenant/installation cocok, Exam/Session/SessionRoom aktif, dan schedule_revision sesuai.
- Menu bukan otorisasi. Setiap route, API, Livewire boot/hydration/action, query, export, event, media, dan download wajib mengulang validasi server-side.
- Capability Proktor dibatasi pada Exam dan SessionRoom yang ditugaskan.
- Admin Madrasah yang berizin dapat mengelola assignment sesuai policy yang disetujui; assign/confirm/revoke harus exact-one ADMIN_MADRASAH dan permission yang sesuai.
- Guru Proktor tetap exact-one GURU, user aktif, TeacherProfile tenant-lokal aktif, dan assignment valid.
- Tidak ada role PROKTOR, akun Siswa, portal login Proktor, credential Proktor, atau sesi login terpisah.
- Semua tindakan menggunakan user_id akun Guru sebagai actor; assignment menyimpan assigned_by, revoked_by, reason, waktu, dan audit event.
- Histori assignment tetap disimpan walau assignment berakhir/dicabut.
- Kewenangan Guru pemilik Exam untuk mengelola/menilai ujian harus dipisahkan dari capability Proktor room-scoped.

Status: keputusan produk FINAL dari koordinator; source/menu/UI/backend authorization belum memenuhi seluruh kriteria. Approval teknis Agent 1/3 dan izin implementasi belum ada.

## 4. AUDIT — KAMAD dan OPERATOR legacy

### KAMAD
KAMAD masih dapat login Web/API karena allow-list resolver/auth. Redirect KAMAD ke / masih ada. RolePermissionBootstrapper masih mensyaratkan KAMAD dan memberi exam.monitor/report.view. ExamPolicy, AssessmentPolicy, ExamAttemptPolicy, ReportPolicy, ExamMonitoringScope dan role-related controllers masih memiliki cabang KAMAD. Ini harus ditutup secara menyeluruh.

Desain penutupan:
- Tolak KAMAD di Web login, API login, resolver, existing session validation, Sanctum token authorization, middleware, policy, Gate, controller, service, Livewire, export, media, queue dan scheduler.
- Hapus redirect KAMAD dan seluruh route/dashboard/monitoring capability KAMAD.
- Jangan hapus identitas jabatan Kepala Madrasah. Appointment/history untuk penandatangan laporan adalah data administrasi, bukan permission.
- Sebelum role/permission pivot diubah, inventaris akun dan assignment; buat backup dan audit trail; migrasi harus idempotent dan diuji pada disposable DB.
- Jangan menghapus akun/riwayat atau mengubah identitas pejabat menjadi akun Guru/Admin.

### OPERATOR
Login allow-list utama tidak memuat OPERATOR, tetapi LicenseStatusController masih menerima OPERATOR. Route /operator/dashboard masih menjadi redirect kompatibilitas ke Admin. LegacyRoleMigrationService memetakan OPERATOR → ADMIN_MADRASAH setelah preflight konflik. Status OPERATOR sebagai role aktif tidak boleh dipertahankan. Tutup semua jalur policy/license/API dan pertahankan hanya redirect kompatibilitas bila memang disetujui; redirect tidak boleh memberi hak akses baru.

## 5. AUDIT — installation identity, host/domain dan tenant

### Fakta source
- LocalInstallationIdentity memiliki registered_domain dan tenant_id.
- LoginIdentityResolver mensyaratkan tepat satu LocalInstallationIdentity dan tepat satu Tenant pada database lokal.
- Resolver mengambil tenant_id dari LocalInstallationIdentity untuk mencari username/NIP/NIK/NISN.
- Resolver tidak membandingkan registered_domain dengan request host.
- IdentifyTenant membangun TenantContext dari authenticated user dan mengabaikan X-Tenant-Override.
- IdentifyTenant tidak memeriksa tenant_id user terhadap LocalInstallationIdentity pada setiap request.
- AuthWebController setelah login mengisi TenantContext dari LocalInstallationIdentity; tidak ada validasi domain request yang terlihat pada controller.
- bootstrap/app.php mengaktifkan statefulApi dan middleware alias tenant, tetapi tidak terlihat konfigurasi eksplisit trusted host/proxy pada file yang diperiksa.
- database.php menyimpan kredensial installer di storage/app/private/installer-database.php; Redis prefix default berdasarkan APP_NAME.

### DESAIN host/installation binding
1. Normalisasi host request menjadi hostname canonical; validasi terhadap registered_domain instalasi lokal.
2. Hanya percaya forwarded host/proto dari reverse proxy yang secara eksplisit dipercaya; header dari client langsung tidak boleh mengubah host tepercaya.
3. Tolak host mismatch/unknown dan Host spoofing fail-closed sebelum memberi data tenant.
4. Pastikan tepat satu LocalInstallationIdentity, tepat satu tenant lokal, dan identity.tenant_id sama dengan tenant lokal.
5. Pada setiap request terautentikasi, pastikan user.tenant_id sama dengan installation identity tenant_id.
6. Session dan Sanctum token harus tetap terikat ke instalasi yang mengeluarkannya; token/session lama tidak boleh dipakai lintas instalasi.
7. X-Tenant-Override, query/body tenant_id, dan browser-selected tenant tidak boleh mengubah TenantContext.
8. Logging penolakan mencatat installation_id/host/request ID secara aman; jangan log password, bearer token atau secret.
9. Pertahankan API Web Siswa dan middleware compatibility; perubahan host binding harus diuji pada Web session dan Sanctum/stateful API.

Status: DESAIN PENDING; tidak ada konfigurasi production diubah.

## 6. AUDIT — isolasi infrastruktur

| Komponen | Bukti source saat ini | Status |
|---|---|---|
| Database | Installer memeriksa MySQL 8+, InnoDB, database kosong; kredensial disimpan private | Desain satu DB per instalasi didukung, uji dua instalasi belum ada |
| Storage | Installer memeriksa storage/app/private writable; tenant media path checks ada pada MediaController | NOT VERIFIED untuk root/path antar instalasi dan restore |
| Cache/Redis | database.php memakai prefix default berbasis APP_NAME; DB Redis default 0 dan cache 1 | NOT VERIFIED; prefix collision mungkin jika instalasi berbagi Redis dan APP_NAME sama |
| Queue | Tidak ditemukan bukti konfigurasi namespace/connection/worker terpisah pada file config aplikasi yang diperiksa | NOT VERIFIED |
| Session | Tidak ditemukan konfigurasi session khusus instalasi pada tree config yang diperiksa | NOT VERIFIED |
| Scheduler | routes/console.php ada; isolasi scheduler lock antar instalasi belum dibuktikan | NOT VERIFIED |
| Temporary files/export | Belum ada bukti root per instalasi dan cleanup lintas instalasi | NOT VERIFIED |
| Logs | Path/retention/permission lintas instalasi belum diuji | NOT VERIFIED |
| Backup/restore | Belum ada bukti uji backup database+storage A/B dan restore tanpa overwrite instalasi lain | NOT VERIFIED |
| Domain/proxy | registered_domain belum digunakan oleh resolver; trust proxy/host belum terbukti | NOT VERIFIED |

InstallerService blob b0ef702244edcdf8f227e7f4751334ee01ea3c4c mensyaratkan PHP 8.3+, ekstensi yang dibutuhkan, HTTPS, APP_KEY, permission storage, MySQL 8+ dan InnoDB. Ia menolak target database yang sudah berisi objek dan menyimpan kredensial database pada storage/app/private. Ini pemeriksaan installer, bukan bukti isolasi operasional dua instalasi.

### Matriks uji dua instalasi wajib
- Instalasi A dan B memakai database/kredensial berbeda; credential A tidak dapat query database B.
- Host A pada instalasi B dan Host B pada instalasi A ditolak.
- Host/X-Forwarded-Host spoofing dari client langsung ditolak; proxy tepercaya diuji.
- X-Tenant-Override, query/body tenant_id, session dan token lintas instalasi ditolak.
- File media/report/export A tidak dapat diakses dari B.
- Cache key, Redis lock, queue job, scheduler mutex dan session cookie A/B tidak bertabrakan.
- Temp file, log, dan backup berada pada root instalasi yang benar.
- Restore A ke staging A tidak mengubah database/storage B.
- Tidak ada telemetry akademik ke publisher/control plane.

Seluruh uji di atas: NOT RUN.

## 7. AUDIT — reporting dan import

### Reporting
- ExamAttempt memiliki current score pointer dan resolvedCurrentScore() yang memvalidasi attempt/tenant/scoring version.
- ExamSummaryReportService blob 45d8f97eb9b2c75164a6f10a403e052b77696552 mengagregasi histori ExamAttemptScore, bukan hanya current pointer FINALIZED.
- StudentResultReportService memakai current pointer untuk finalized_only; ExamResultReportService menggunakan relation current pointer namun perlu negative test.
- PDF template sebelumnya ditemukan menampilkan 0 ketika score null; belum dinyatakan diperbaiki.
- Immutable ReportSnapshot belum ditemukan.

Desain: preview live diberi label preview; penerbitan membuat snapshot immutable dalam transaksi; hanya current pointer valid berstatus FINALIZED masuk statistik final; NOT_SCORED dan MANUAL_REVIEW tetap eksplisit; nilai final 0 tetap berbeda dari belum dinilai. Snapshot menyimpan tenant, branding, peserta, Exam, versi nilai, signer/appointment, actor, waktu/timezone, template/schema revision, payload hash dan artifact hash. PDF final dirender dari snapshot. Revisi membuat revision baru; idempotency mencegah duplikasi. Tidak ada data antar instalasi yang tercampur.

Status: NEEDS DESIGN/NOT IMPLEMENTED/NOT TESTED.

### Import XLSX/DOCX
- ImportBatch dan ImportBatchRow tersedia dan tenant-scoped.
- Shared QuestionDraftDTO belum ada.
- XLSX dan DOCX harus menjadi adapter berbeda ke DTO dan lifecycle batch yang sama.
- Reuse ImportBatch/ImportBatchRow: APPROVED IN PRINCIPLE, bukan izin implementasi.
- Field/type/nullable/enum DTO, endpoint, error per-row, staging, retention, idempotency, duplicate/retry, media lifecycle dan curriculum mapping: NEEDS DESIGN.
- Parser DOCX dependency belum disetujui.
- accepted_answers belum diterima QuestionController dan tidak disalin ExamSnapshotService, sedangkan ScoringService membacanya dari snapshot metadata.
- SHORT_ANSWER: BLOCKED.
- ESSAY: manual review.
- PDF/OCR: deferred.
- Tidak ada auto-publish.

Issue #8/#9 masih OPEN; belum ada approval final Agent 3 untuk kontrak UI.

## 8. DESAIN — patch plan setelah izin terpisah

Tidak ada patch pada tahap ini. Setelah inventory selesai, approval lintas-agent dan izin koordinator diperoleh, patch runtime harus direncanakan sebagai rangkaian kecil dan diuji per gate:

1. Auth/role matrix: AuthWebController, API AuthController, LoginIdentityResolver, IdentifyTenant, EnsureAccountSessionVersion, RolePermissionBootstrapper, semua policy terkait, controller/service/Livewire dan LicenseStatusController.
2. KAMAD/OPERATOR retirement: deny-by-default, transisi role yang mempertahankan history/appointment, idempotent migration hanya setelah data inventory dan disposable preflight.
3. Proktor: exact-one Admin assign authority; exact-one Guru target; assignment-based menu Web Guru; distinct author-monitor vs Proktor-room capability; all monitoring data surfaces room-scoped; audit actor Guru.
4. Installation host binding: LocalInstallationIdentity, resolver, middleware, trusted host/proxy config, session/token binding.
5. Infrastructure isolation: config cache/session/queue/filesystem, Redis prefix/lock, storage root, scheduler, temp/export/log, backup/restore.
6. Reporting: current pointer FINALIZED query, null score semantics, immutable snapshot/publish/PDF/hash/idempotency.
7. Import: final DTO/API/staging/security/retention and accepted_answers end-to-end; only after Agent 3 approval.
8. API compatibility: test all Web Siswa call-sites; do not remove exam/media/answer/submit/resume APIs.

Semua file runtime yang mungkin terdampak perlu daftar final, byte-exact backup, SHA sebelum/sesudah, diff review, focused tests, full relevant suite, dan disposable MySQL 8. Tidak ada migration produksi tanpa gate terpisah, backup produksi, preflight, dan persetujuan pengguna.

## 9. PENGUJIAN — bukti dan status

### CI exact-SHA
- Agent 2 source runtime SHA 74bf7178: combined status yang dapat diakses kosong; workflow-run connector tidak mengembalikan run. Riwayat sebelumnya mencatat run #388 / 45003796892 dan #389 / 45008591113 dengan steps kosong dan log tidak tersedia. PHPUnit UNKNOWN, bukan PASS/FAIL.
- Agent 2 docs HEAD 462cadc9: combined status kosong; tidak ada bukti PHPUnit.
- Agent 3 test source 4dc407d091fc6cf786260052cfd86d836edc5908, blob 0b273618677c791d82645fa373be57aa2d961643: 22 method terverifikasi secara statis; PHPUnit/PHP lint/MySQL disposable exact-SHA NOT RUN/NOT VERIFIED.
- Agent 3 run #41 / 45009039557 pada SHA lama 88b5c135: workflow/job failure, steps kosong, log BlobNotFound, artifacts kosong; PHPUnit execution UNKNOWN.
- Agent 3 run #39 / 45003191589 pada SHA 834a59f: 18 passed, 2 failed, 86 assertions; bukan exact SHA test terbaru.
- Agent 1 run 44984303003 SUCCESS pada source lama f1d01ea; bukan current source Stage 4B dan tidak menguji operational-session guard.
- Agent 4 kandidat tidak memiliki CI exact integration SHA yang membuktikan Web Siswa dan Stage 4B terintegrasi.

Konektor workflow yang tersedia hanya mengembalikan PR-triggered runs pada endpoint tertentu; daftar kosong bukan bukti bahwa tidak pernah ada push workflow. Tidak ada rerun/dispatch dilakukan.

### Test plan wajib
1. Login Admin/Guru/Siswa valid; KAMAD/OPERATOR/PROKTOR legacy ditolak.
2. Mixed-role matrix untuk semua kombinasi portal dan legacy.
3. Exact-role pada HTTP route, API, policy, Gate, Livewire mount/boot/hydration/action, service direct invocation, queue job, scheduler, export dan media.
4. Proktor tanpa assignment, PENDING, expired, revoked, wrong room/Exam/tenant, wrong schedule_revision, user soft-deleted, profile soft-deleted, mixed role, dan assignment overlap harus ditolak.
5. Proktor valid memakai akun Guru sendiri; menu muncul hanya selama assignment valid; tindakan tercatat dengan Guru user_id.
6. KAMAD tidak dapat login, token, route, API, monitoring, report capability atau Livewire.
7. OPERATOR tidak dapat akses license/admin/CBT sebagai legacy role; migration map diuji idempotent dan konflik fail-closed.
8. Host/domain registered/mismatch/spoofed, trusted proxy, forwarded headers, session/token cross-installation.
9. Dua instalasi A/B: database, storage, cache, queue, session, scheduler, temp, export, logs, backup/restore.
10. Reporting current pointer, FINALIZED-only, null vs zero, immutable snapshot/PDF/hash/revision/idempotency.
11. Import DTO/XLSX/DOCX lifecycle, security fixture, no auto-publish, SHORT_ANSWER blocked until end-to-end.
12. Web Siswa API compatibility: metadata, exam detail/snapshot, media, start/resume, answer save, submit dan recovery.
13. MySQL 8/InnoDB concurrency untuk Answer/Submit/Force Submit/expiry/lease dan room scheduler setelah lock-order approval.
14. Browser E2E Web Admin/Guru/Siswa dan UAT madrasah.

Status semua test baru di atas: NOT RUN atau BLOCKED sesuai prasyarat. Tidak ada database produksi disentuh.

## 10. IMPLEMENTASI

Dokumen ini adalah file baru. Tidak ada source runtime, test, migration, schema, dependency, workflow, konfigurasi, database, branch Agent 1/3/4, merge/rebase/cherry-pick, PR atau deployment yang diubah.

Backup source tidak diperlukan karena tidak ada file existing yang ditimpa. Tidak ada file source yang diubah sehingga tidak ada SHA runtime before/after.

## 11. NEXT GATE

Status keseluruhan: BLOCKED — EXACT-ROLE / INSTALLATION SECURITY / PROKTOR DESIGN PENDING.

Prioritas:
1. Tutup inventory exact-role dan KAMAD/OPERATOR di seluruh entrypoint.
2. Minta Agent 1 menyetujui assignment lifecycle/lock order dan Attempt recovery boundary.
3. Minta Agent 3 mengonfirmasi menu khusus Proktor Web Guru dan room-scoped UI consumer.
4. Konfirmasi owner/branch resmi Agent 4; jangan gunakan kandidat tanpa approval.
5. Finalisasi host/domain trust dan instalasi identity.
6. Finalisasi config isolation dan backup/restore matrix.
7. Selesaikan reporting/import contract.
8. Pulihkan CI dan buktikan PHPUnit PASS pada exact source SHA.
9. Siapkan disposable MySQL 8 dan browser E2E.
10. Minta izin eksplisit terpisah sebelum patch runtime.

Tidak ada klaim siap produksi, siap ujian operasional, atau siap distribusi.
