# MATRIKS AUDIT WEB ADMIN — TEMPLATE WAJIB

Gunakan salinan tabel/format ini untuk setiap modul. Jangan mengisi status berdasarkan nama route atau keberadaan file saja.

## 1. Klasifikasi status

- **SUDAH ADA DAN BERFUNGSI:** UI + backend + authorization + validasi + tenant guard + persistensi + audit + test relevan terbukti.
- **ADA TAPI BELUM LENGKAP**
- **BACKEND ONLY**
- **UI ONLY**
- **BELUM ADA**
- **BERMASALAH/BERISIKO**
- **BELUM TERVERIFIKASI**

## 2. Bukti per fitur

| ID | Modul/fitur | Status | Route/URL | UI file/component | Action/Livewire method | Controller/service | Model/tabel/query | Permission/policy | Tenant guard | Validation | Transaction/idempotency | Audit log | Test file/case | Test aktual (PASS/FAIL/BLOCKED/NOT RUN) | Bukti commit/path/line | Gap/risiko | Rekomendasi |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| ADM-001 | Dashboard | BELUM TERVERIFIKASI | | | | | | | | | | | | | | | |
| ADM-002 | Identitas Madrasah | BELUM TERVERIFIKASI | | | | | | | | | | | | | | | |
| ADM-003 | Tahun ajaran/semester | BELUM TERVERIFIKASI | | | | | | | | | | | | | | | |
| ADM-004 | Kelas/fase/rombel/wali kelas | BELUM TERVERIFIKASI | | | | | | | | | | | | | | | |
| ADM-005 | Data Guru administratif | BELUM TERVERIFIKASI | | | | | | | | | | | | | | | |
| ADM-006 | Penugasan Guru | BELUM TERVERIFIKASI | | | | | | | | | | | | | | | |
| ADM-007 | Data Siswa administratif | BELUM TERVERIFIKASI | | | | | | | | | | | | | | | |
| ADM-008 | Penempatan/promosi/tinggal kelas | BELUM TERVERIFIKASI | | | | | | | | | | | | | | | |
| ADM-009 | Kelulusan kelas 6/alumni/histori | BELUM TERVERIFIKASI | | | | | | | | | | | | | | | |
| ADM-010 | Master mapel MI | BELUM TERVERIFIKASI | | | | | | | | | | | | | | | |
| ADM-011 | Mulok | BELUM TERVERIFIKASI | | | | | | | | | | | | | | | |
| ADM-012 | Kurmer/KBC/versioning | BELUM TERVERIFIKASI | | | | | | | | | | | | | | | |
| ADM-013 | CP/TP/ATP | BELUM TERVERIFIKASI | | | | | | | | | | | | | | | |
| ADM-014 | Panca Cinta/Pembelajaran Mendalam/DPL | BELUM TERVERIFIKASI | | | | | | | | | | | | | | | |
| ADM-015 | Impor Excel Siswa | BELUM TERVERIFIKASI | | | | | | | | | | | | | | | |
| ADM-016 | Impor Excel Guru | BELUM TERVERIFIKASI | | | | | | | | | | | | | | | |
| ADM-017 | Impor Excel Mata Pelajaran | BELUM TERVERIFIKASI | | | | | | | | | | | | | | | |
| ADM-018 | Laporan administratif | BELUM TERVERIFIKASI | | | | | | | | | | | | | | | |
| ADM-019 | Backup/restore | BELUM TERVERIFIKASI | | | | | | | | | | | | | | | |
| ADM-020 | Profil/password/preferensi | BELUM TERVERIFIKASI | | | | | | | | | | | | | | | |
| ADM-021 | Audit log/kesehatan sistem | BELUM TERVERIFIKASI | | | | | | | | | | | | | | | |
| ADM-022 | Sidebar/topbar/responsive shell | BELUM TERVERIFIKASI | | | | | | | | | | | | | | | |
| ADM-023 | Daftar Ujian & Penugasan Proktor | BELUM TERVERIFIKASI | | | | | | | | | | | | | | | |

## 3. Matriks authorization

Buat satu baris untuk setiap kombinasi role × route/action. Jangan mengasumsikan label UI sama dengan permission backend.

| Role | Route/action | Allowed? | Permission/policy | Tenant binding | Assignment/scope | Negative test | Evidence |
|---|---|---|---|---|---|---|---|
| Super Admin/publisher | Akses operasional tenant | Belum ditentukan pada scope ini | | | | | |
| Admin Madrasah/Operator | Modul administratif | Harus sesuai permission | | | | | |
| Admin Madrasah/Operator | Authoring soal/ujian Guru | **Dilarang** | | | | | |
| Admin Madrasah/Operator | List ujian Guru read-only | Diizinkan sesuai scope | | | | | |
| Admin Madrasah/Operator | Assign Guru pengawas | Diizinkan sesuai scope | | | | | |
| Guru | Portal Guru | Di luar scope sekarang | | | | | |
| Siswa | Portal Siswa | Di luar scope sekarang | | | | | |
| Proktor sementara | Akses hanya dari assignment aktif | Desain integrasi; bukan portal permanen | | | | | |

## 4. Jalur penelusuran wajib

Untuk tiap fitur telusuri dan catat:

1. Menu/navigation → route.
2. Middleware/authentication.
3. Livewire component/controller dan action.
4. Policy/gate/permission.
5. Validasi request/form.
6. TenantContext dan query scoping.
7. Service/domain logic.
8. Transaction, lock, idempotency, uniqueness.
9. Model/DB schema/persistence.
10. Audit log/event.
11. Response/UI state.
12. Unit/feature/security/regression test.
13. Bukti test aktual: command, exit code, runtime, commit.

## 5. Audit lintas modul

- [ ] Role Admin = Operator satu role; tidak ada duplikasi portal.
- [ ] Tidak ada link Admin yang membuka authoring Guru.
- [ ] Tidak ada route Admin yang dapat mengubah soal/jadwal akademik ujian Guru.
- [ ] Daftar ujian Admin membaca record ujian Guru yang sama; bukan tabel kalender duplikat.
- [ ] Penetapan Proktor hanya Guru aktif tenant yang sama.
- [ ] Konflik jadwal ditangani secara atomik.
- [ ] Perubahan jadwal oleh Guru menandai assignment terdampak perlu ditinjau.
- [ ] Riwayat perubahan assignment tercatat.
- [ ] Data siswa/Guru dibatasi tenant dan permission.
- [ ] Import tidak membuat duplikasi; preview dan commit dipisahkan.
- [ ] Promosi/kelulusan tidak menghapus histori.
- [ ] Seed kurikulum idempotent dan tidak menimpa custom lokal.
- [ ] Ekspor/download memeriksa permission dan scope.
- [ ] Tidak ada kebocoran PII pada dashboard/log/error.

## 6. Prioritas risiko

- **P0:** kebocoran lintas tenant/PII, privilege escalation, kehilangan/korupsi data, destructive migration, bypass auth.
- **P1:** alur akademik salah, import duplikat, histori hilang, konflik assignment, audit tidak lengkap.
- **P2:** UX, performa, aksesibilitas, inkonsistensi visual yang tidak memblokir integritas.



# REKONSILIASI STATUS AUDIT — SOURCE HEAD ade5daa6

Semua status di bawah dibandingkan kembali dengan branch fix/web-portal-usability-rbac-20261120 pada SHA ade5daa6cf853c164c4f51ec6e25ca68be40b4f2. SHA source sama dengan baseline audit 21 November; tidak ada source commit baru pada branch tersebut.

| ID | Modul | Status | Catatan pembuktian |
|---|---|---|---|
| ADM-001 | Dashboard | MASIH BERLAKU | OperatorDashboard dan label Operator masih digunakan; KPI/health belum lengkap. |
| ADM-002 | Identitas Madrasah | MASIH BERLAKU | UI profil/kop madrasah belum ditemukan. |
| ADM-003 | Tahun ajaran/semester | MASIH BERLAKU | Model dan fitur dasar ada; lifecycle/histori lengkap belum terbukti. |
| ADM-004 | Kelas/fase/rombel/wali kelas | MASIH BERLAKU | Rombel dasar ada; fase MI/wali kelas/histori belum lengkap. |
| ADM-005 | Data Guru | MASIH BERLAKU | UserManagement belum membuktikan lifecycle lengkap. |
| ADM-006 | Penugasan Guru | MASIH BERLAKU | Backend model/API ada; UI Admin belum ditemukan. |
| ADM-007 | Data Siswa | MASIH BERLAKU | Create dasar ada; lifecycle lengkap belum terbukti. |
| ADM-008 | Penempatan/promosi/tinggal kelas | MASIH BERLAKU | Penempatan ada; promosi/tinggal kelas transaksional lintas tahun belum terbukti. |
| ADM-009 | Kelulusan/alumni/histori | MASIH BERLAKU | Workflow Admin belum ditemukan. |
| ADM-010 | Master mapel | MASIH BERLAKU | Tambah mapel dasar ada; lifecycle master belum lengkap. |
| ADM-011 | Mulok | MASIH BERLAKU | UI Mulok belum ditemukan. |
| ADM-012 | Kurmer/KBC/versioning | MASIH BERLAKU | Fondasi curriculum ada; versioning/provenance belum lengkap. |
| ADM-013 | CP/TP/ATP | MASIH BERLAKU | CP/TP dasar ada; ATP/source/version/history belum lengkap. |
| ADM-014 | Panca Cinta/Deep Learning/DPL | MASIH BERLAKU | Modul referensi Admin belum ditemukan. |
| ADM-015 | Impor Siswa | MASIH BERLAKU | Importer master data belum ditemukan. |
| ADM-016 | Impor Guru | MASIH BERLAKU | Importer master data belum ditemukan. |
| ADM-017 | Impor Mapel | MASIH BERLAKU | Importer master data belum ditemukan. |
| ADM-018 | Laporan administratif | MASIH BERLAKU | Laporan hasil ujian Guru bukan laporan Admin. |
| ADM-019 | Backup/restore | MASIH BERLAKU | UI/workflow Admin belum ditemukan. |
| ADM-020 | Profil/preferensi/notifikasi | MASIH BERLAKU | Modul Admin lengkap belum ditemukan. |
| ADM-021 | Audit log/health | MASIH BERLAKU | Service ada; cakupan mutasi dan health belum lengkap. |
| ADM-022 | Shell/sidebar | MASIH BERLAKU | Brand Admin & Guru, Dashboard Operator, menu lintas portal, sidebar mobile belum sesuai. |
| ADM-023 | Daftar ujian/Proktor | MASIH BERLAKU + KOREKSI | Picker PROKTOR, portal Proktor, belum ada class/overlap/review. Interval akses sudah [start,end); service sudah lock row Guru, tetapi belum query overlap. |

## Temuan keamanan yang direkonsiliasi

| ID | Status | Hasil terkini |
|---|---|---|
| SEC-01 Admin/Operator terpisah | MASIH BERLAKU | ROLE_GRANTS ADMIN_MADRASAH dan OPTIONAL_ROLE_GRANTS OPERATOR berbeda. |
| SEC-02 Portal/role Proktor | MASIH BERLAKU | Route /proktor/assignments, redirect, layout, dan ProktorDashboard masih ada. |
| SEC-03 Picker Proktor | MASIH BERLAKU | Component dan service mensyaratkan role PROKTOR; bukan semua Guru aktif. |
| SEC-04 Admin exam boundary | MASIH BERLAKU | UI ExamManagement gabungan; policy create/update Guru-only, tetapi Admin masih dapat monitor/close melalui permission/policy. |
| SEC-05 Layout | MASIH BERLAKU | Sidebar dan label belum sesuai portal tunggal Admin. |
| SEC-06 KPI | MASIH BERLAKU | Definisi aktif akademik belum lengkap. |
| SEC-07 Audit | MASIH BERLAKU | AuditService ada; mutasi UserManagement/AcademicManagement belum konsisten. |
| SEC-08 Teacher assignment UI | MASIH BERLAKU | Backend API/model ada; UI Admin belum ditemukan. |
| SEC-09 Curriculum provenance | MASIH BERLAKU | Official/custom/source/version belum lengkap. |
| SEC-10 Import master data | MASIH BERLAKU | Question importer bukan importer Guru/Siswa/mapel. |
| SEC-11 Tenant fail-closed | SUDAH ADA DI SOURCE (dengan batas model) | TenantScoped memakai whereRaw 1=0 saat context kosong dan menolak create tanpa tenant; IdentifyTenant mengabaikan override. User tidak memakai TenantScoped, sehingga jalur User/withoutGlobalScopes tetap harus diuji. |
| SEC-12 Conflict concurrency | BERUBAH/KOREKSI | Service sudah lock exam dan row Guru. Tidak ada overlap query setelah lock, sehingga konflik tetap dapat tersimpan. |
| SEC-13 Admin permission terlalu luas | MASIH BERLAKU | Grant exam.monitor/close/token/grade/reopen/manage belum sesuai batas final Admin. |

Catatan: “belum ditemukan” berarti tidak ada bukti UI/route/service yang memadai pada tree branch yang ditelusuri; bukan klaim bahwa tidak ada kode eksternal atau branch lain.

## Bukti source yang dipakai

- routes/web.php — c22fed7d54871bf4971acf626514fc02c4a68ce9
- ProctorAssignmentManagement.php — 4e8bd91dd54757aa863d35983aace718def2ca2c
- ExamProctorAssignmentService.php — e16ef5db308714aa98bf283dc5a1545608da0477
- ExamProctorAssignment.php — dde7511ebb5035a76287d34dc75339a6a2bffdd8
- Proctor assignment migration — e51ef878e14d13078d81585fbbc10e1d00b76e25
- ExamManagement.php — 478aaa170ee297b0c8399f049cd9b1ba56cbfb10
- ExamPolicy.php — 350c1ddc6bed8738d1af17cacd6c95bc367b7c29
- RolePermissionBootstrapper.php — fac82573de84c52b52867afbbf7e1637164835b4
- TenantScoped.php — cf192a8ccb6ca530122cb105ed3086adb09a3c5a
- IdentifyTenant.php — 376a4b2771c3d9a6b128ad847c150d47ac2977db
- ProctorAssignmentSecurityTest.php — a5eca4ea2ce05d62d4fa76e6b9cd5a21a17dd668



# ADDENDUM REKONSILIASI SOURCE CANONICAL & ROLE — 21 NOVEMBER 2026

| ID | Modul | Status | Bukti source / catatan |
|---|---|---|---|
| ADM-001 | Dashboard | MASIH BERLAKU | OperatorDashboard.php 75ef8da...; operator-dashboard.blade.php 4e33946...; label Operator masih ada. |
| ADM-002 | Identitas madrasah | BERUBAH / PARTIAL | InstallerService.php 62dcecd... dan LocalInstallationIdentity.php d6f364... membuat identitas instalasi; UI Admin edit profil/branding belum terbukti. |
| ADM-003 | Tahun ajaran/semester | MASIH BERLAKU / PARTIAL | AcademicManagement.php 5afd078...; model AcademicYear/Semester; lifecycle/history end-to-end belum terbukti. |
| ADM-004 | Kelas/fase/rombel/wali | MASIH BERLAKU / PARTIAL | StudentRombelManagement.php, Rombel.php 9a900c...; fase MI/wali/histori belum terbukti lengkap. |
| ADM-005 | Data Guru | MASIH BERLAKU / PARTIAL | UserManagement.php 5f90e38..., User.php 74dd080..., TeacherProfile.php; lifecycle aktif/nonaktif/reset/histori belum lengkap. |
| ADM-006 | Penugasan Guru | BERUBAH / PARTIAL | TeacherSubjectAssignmentController dan model/API ada; UI Admin lifecycle/histori belum terbukti. |
| ADM-007 | Data Siswa | MASIH BERLAKU / PARTIAL | UserManagement.php, StudentProfile.php; lifecycle administratif lengkap belum terbukti. |
| ADM-008 | Penempatan/promosi/tinggal kelas | MASIH BERLAKU / PARTIAL | StudentRombelManagement.php, RombelStudent.php; promosi/tinggal kelas lintas tahun transaksional belum terbukti. |
| ADM-009 | Kelulusan/alumni/histori | BELUM DAPAT DIVERIFIKASI | Tidak ada modul/route/model Admin yang terbukti pada tree aktif; tree SHA f5cc0a... |
| ADM-010 | Master mapel | BERUBAH / PARTIAL | AcademicManagement.php; Subject model ada, Mulok/versioned catalog belum lengkap. |
| ADM-011 | Mulok | BELUM DAPAT DIVERIFIKASI | Modul Mulok khusus Admin tidak ditemukan pada tree aktif f5cc0a... |
| ADM-012 | Kurmer/KBC/versioning | BERUBAH / PARTIAL | CurriculumManagement.php e342265..., MultiCurriculum models; provenance/versioning Kurmer/KBC end-to-end belum terbukti. |
| ADM-013 | CP/TP/ATP | BERUBAH / PARTIAL | LearningOutcome/LearningObjective/LearningPath controllers/models ada; ATP source/version/history belum terbukti lengkap. |
| ADM-014 | Panca Cinta/Deep Learning/DPL | BELUM DAPAT DIVERIFIKASI | Modul referensi Admin dengan sumber resmi/versioning tidak ditemukan pada tree aktif. |
| ADM-015 | Import Excel Siswa | BELUM DAPAT DIVERIFIKASI | Importer master data Siswa tidak ditemukan; Question importer bukan importer siswa. |
| ADM-016 | Import Excel Guru | BELUM DAPAT DIVERIFIKASI | Importer master data Guru tidak ditemukan. |
| ADM-017 | Import Excel Mapel | BELUM DAPAT DIVERIFIKASI | Importer master data Subject/Mulok tidak ditemukan. |
| ADM-018 | Laporan administratif | BERUBAH / PARTIAL | ReportPolicy/ExamResult reports ada, tetapi bukan laporan administratif Admin terpisah dari hasil ujian Guru. |
| ADM-019 | Backup/restore Admin | BELUM DAPAT DIVERIFIKASI | Workflow backup/restore Admin dan disposable restore belum ditemukan. |
| ADM-020 | Profil/preferensi/notifikasi Admin | BELUM DAPAT DIVERIFIKASI | Modul Admin lengkap belum ditemukan. |
| ADM-021 | Audit log/health | BERUBAH / PARTIAL | AuditService/AuditLog dan health /up ada; UI audit/health dan cakupan mutasi belum lengkap. |
| ADM-022 | Shell/sidebar/responsive | MASIH BERLAKU | layouts/app.blade.php 0163813...: Panel Admin & Guru, Dashboard Operator, Proktor portal, menu shared; mobile scroll/drawer final belum terbukti. |
| ADM-023 | Daftar ujian/assignment Proktor | BERUBAH / PARTIAL | Assignment service/model/migration ada; role PROKTOR masih wajib, rombel/overlap/review jadwal belum ada. |

## Status keamanan dan identitas
| Temuan | Status | Bukti |
|---|---|---|
| Admin dan Operator satu role | MASIH BERLAKU (belum sesuai) | DatabaseSeeder/RolePermissionBootstrapper/InstallerService masih membuat OPERATOR; UserManagement dan AcademicManagement memberi akses berbeda. |
| ADMIN alias | MASIH BERLAKU | LoginIdentityResolver, AcademicManagement, CurriculumManagement, KamadDashboard, OperatorDashboard masih menerima ADMIN. |
| Proktor permanen | MASIH BERLAKU (belum sesuai) | LoginIdentityResolver, AuthWebController, ProktorDashboard, ExamPolicy, service/picker, routes masih menggunakan PROKTOR. |
| Admin boundary | MASIH BERLAKU (belum sesuai) | ExamPolicy close/monitor, ExamAttemptPolicy result/finalize/reopen, ReportPolicy, RolePermissionBootstrapper memberi Admin hak lebih luas dari metadata/assignment. |
| TenantScoped fail-closed | SUDAH ADA DI SOURCE | TenantScoped.php cf192a8...: missing context → whereRaw 1=0; create without context throws. |
| One tenant per DB | SUDAH ADA DI SOURCE | InstallationIdentityService 66115b...: count Tenant harus 1 dan identity max 1; LoginIdentityResolver juga checks count 1. |
| Domain binding existing identity | BELUM DAPAT DIVERIFIKASI / GAP | InstallationIdentityService tidak membandingkan registered_domain existing dengan APP_URL host pada return existing. |
| X-Tenant-Override | SUDAH DITOLAK | IdentifyTenant.php 376a4b...: override diabaikan, tenant dari user. |
| User model fail-closed | BELUM DAPAT DIVERIFIKASI | User tidak memakai TenantScoped; perlu test semua jalur User/withoutGlobalScopes. |
| Proktor overlap | MASIH BERLAKU (belum sesuai) | ExamProctorAssignmentService e16ef5... mengunci Guru tetapi tidak query overlap. |
| Schedule review | MASIH BERLAKU (belum ada) | ExamController::update dan ExamManagement::saveExam belum memicu NEEDS_REVIEW. |
| Assignment expiry/revoke | SUDAH ADA SEBAGIAN | ExamProctorAssignment dde751... scopeActiveNow memeriksa ACTIVE, revoked_at null, start<=now, expiry>now. |
| NEEDS_REVIEW capability revoke | BELUM ADA IMPLEMENTASI | Status NEEDS_REVIEW belum ada; desain mengharuskan status bukan ACTIVE tidak memberi capability. |

## Source evidence utama
Semua blob dari branch kandidat Web Admin ade5daa6cf853c164c4f51ec6e25ca68be40b4f2:
- DatabaseSeeder.php bb9a55dd16ac7b7e56b79dc8f5ccaca689416868
- RolePermissionBootstrapper.php fac82573de84c52b52867afbbf7e1637164835b4
- InstallerService.php 62dcecd5db10574067cef41529ce531a08e02999
- AuthWebController.php 1f76179dbbf304eb11271b74fbe1d1d38c74a401
- LoginIdentityResolver.php ec1da249e643b48094459bc3eb51ce1b5e58ab39
- LicenseStatusController.php 2e6a718c54e8aac573790cf3b117c1822759f72b
- AcademicManagement.php 5afd0780fa642354c11954fce307d8e6f7e4576f
- CurriculumManagement.php e3422650ea71709cba5ae07cd2b887cf812166a8
- UserManagement.php 5f90e3873cadeb77a1bcd5a6aa2134953e60beed
- OperatorDashboard.php 75ef8da79896994c98b00b9941f6f976a36b671c
- KamadDashboard.php 8a0fb4d402f69e5e2542cb256e704c0ecf6f7089
- ExamManagement.php 478aaa170ee297b0c8399f049cd9b1ba56cbfb10
- ProctorAssignmentManagement.php 4e8bd91dd54757aa863d35983aace718def2ca2c
- ExamProctorAssignmentService.php e16ef5db308714aa98bf283dc5a1545608da0477
- ExamProctorAssignment.php dde7511ebb5035a76287d34dc75339a6a2bffdd8
- ExamPolicy.php 350c1ddc6bed8738d1af17cacd6c95bc367b7c29
- ExamAttemptPolicy.php b19d6d39d34884d8be464f786340c1a2ab7108b6
- AssessmentPolicy.php 79a0367c8c83723ae3cc9e581fb8dda8a6e2d02a
- QuestionPolicy.php af94614a2043206922149b0c44a8ec1e3d87767d
- ReportPolicy.php e53be3994c52d067c1e31fe37edc450c24a0f4b6
- IdentifyTenant.php 376a4b2771c3d9a6b128ad847c150d47ac2977db
- TenantScoped.php cf192a8ccb6ca530122cb105ed3086adb09a3c5a
- InstallationIdentityService.php 66115b340189581b34bcf027f73c1049281560e5
- layouts/app.blade.php 0163813cafc0226b9eae8af789223da0fb793f64
- RolePermissionBootstrapperTest.php cfacac702f967667c8e1e4f6dff18000fc105f68
- RoleDashboardVerificationTest.php 359ebbaeb817bf2d76d4137673a84eeedf237681
- WebAdminAuthTest.php 93a822f44f7d21d4c25913599bda2bcfe7d32e34
- ProctorAssignmentSecurityTest.php a5eca4ea2ce05d62d4fa76e6b9cd5a21a17dd668

