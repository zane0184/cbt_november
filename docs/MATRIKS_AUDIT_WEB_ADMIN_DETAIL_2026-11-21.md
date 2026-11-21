# MATRIKS AUDIT DETAIL WEB ADMIN — ADM-001 s.d. ADM-023

- Tanggal: 21 November 2026 (WIB)
- Repository: `zane0184/cbt_november`
- Branch dokumentasi: `docs/canonical-reconciliation-admin-guru-20261121`
- Basis source: `work/web-admin-w0-20261121` (snapshot W0 terverifikasi, 531/531 SHA-256 cocok; source app sama dengan PR #5 HEAD terdokumentasi `ade5daa6cf853c164c4f51ec6e25ca68ce40b4f2`)
- Status dokumen: audit statis detail; bukan hasil runtime/UAT.
- Status semua test per modul: **NOT VERIFIED** kecuali disebutkan bahwa test method kandidat ditemukan. CI #692 adalah aggregate 480 tests / 1,942 assertions pada commit lama, bukan bukti PASS per modul/ID.

## Cara membaca

- **Source/method** menunjukkan titik implementasi yang diperiksa, bukan jaminan fitur lengkap.
- **Status** adalah hasil audit source terhadap spesifikasi normatif.
- **Test evidence** menyebut test method yang ada dan kesenjangan. Keberadaan method bukan bukti eksekusi per acceptance.
- Semua hasil yang belum punya run spesifik tetap NOT RUN/NOT VERIFIED. Tidak ada pengujian baru yang dijalankan saat menyusun matriks ini.

## Matriks 23 modul

| ID | Modul | Status audit | Source dan method yang diperiksa | Bukti / gap utama | Test evidence dan hasil |
|---|---|---|---|---|---|
| ADM-001 | Dashboard | BERMASALAH | `routes/web.php::/`, `/operator/dashboard`; `AuthWebController::redirectForUser()`; `OperatorDashboard::mount/render`; `layouts/app.blade.php` | Admin diarahkan ke portal Operator; label dan menu Operator masih ada. Tidak ada dashboard Admin Madrasah yang bersih dari identitas Operator. | `WebAdminAuthTest::test_admin_madrasah_login_redirects_to_operator_dashboard`; `RoleDashboardVerificationTest::operator_dashboard_access_control`. Test mengunci perilaku lama; run per-ID NOT VERIFIED. |
| ADM-002 | Identitas madrasah | PARTIAL | `InstallerService::finalizeInstallation`; `InstallationIdentityService::getOrCreate`; `LocalInstallationIdentity::tenant/licenseState`; `LicenseStatusController` | Identitas instalasi/domain tersedia; tidak ditemukan UI Admin untuk mengubah profil, branding, alamat, logo, kontak dan preferensi madrasah. Existing identity domain mismatch masih gap. | `InstallerEndToEndTest`, `InstallerTest`, `LicenseStatusAccessTest` relevan untuk installer/license, bukan UI profil Admin. |
| ADM-003 | Tahun ajaran/semester | PARTIAL | `AcademicManagement::render`; `AcademicYear`, `Semester`; `AcademicManagement::createRombel` | UI membaca tahun ajaran/semester aktif untuk membuat rombel; tidak ada CRUD/lifecycle tahun ajaran/semester Admin yang lengkap dan versioned pada route aktif. | `AdminUiVerificationTest::admin_academic_management_can_create_rombel_and_subject`; tidak membuktikan lifecycle tahun/semester. |
| ADM-004 | Kelas/fase/rombel/wali kelas | PARTIAL | `AcademicManagement::createRombel/render`; `StudentRombelManagement::addStudentToRombel/render`; `Rombel`, `GradeLevel`, `Phase` | Pembuatan rombel dan keanggotaan ada; alur wali kelas, validasi fase MI, lifecycle rombel dan histori periode belum end-to-end. | `AdminUiVerificationTest::admin_can_add_student_to_tenant_rombel_and_duplicate_is_rejected`; `RombelStudentPivotTest::test_relationship_cannot_attach_student_profile_from_another_tenant`. |
| ADM-005 | Data Guru | PARTIAL | `UserManagement::createUser/render`; `UserManagement::rules`; `TeacherProfile`; `User` | Admin dapat membuat akun Guru dan TeacherProfile; edit, aktivasi/nonaktif, reset kredensial, arsip dan histori perubahan belum terbukti. Audit event createUser tidak ditemukan. | `AdminUiVerificationTest::admin_user_management_can_create_user`; tidak ada bukti test lifecycle lengkap. |
| ADM-006 | Penugasan Guru | BACKEND ONLY / PARTIAL | `TeacherSubjectAssignmentController::index/store/show/update/destroy`; `canManage`; `assertSameTenantAndAcademicContext`; `TeacherSubjectAssignment` | API assignment tersedia dengan Admin_Madrasah + academic.manage dan composite tenant FKs; UI Admin, histori lengkap dan validasi status Guru/TeacherProfile aktif belum lengkap. | `RBACEnforcementTest::test_admin_with_academic_manage_reaches_teacher_assignment_validation`; test ini mencapai validasi 422, bukan acceptance CRUD penuh. |
| ADM-007 | Data Siswa | PARTIAL | `UserManagement::createUser/render`; `StudentProfile`; `User` | Admin dapat membuat akun Siswa dan profile; edit, aktivasi/nonaktif, reset, arsip dan lifecycle administrasi belum lengkap. Audit event createUser tidak ditemukan. | `AdminUiVerificationTest::admin_can_create_student_with_tenant_scoped_profile`. |
| ADM-008 | Penempatan/promosi/tinggal kelas | PARTIAL | `StudentRombelManagement::addStudentToRombel/render`; `StudentProfile::rombels`; `RombelStudent::booted` | Penambahan siswa ke rombel ada dan memiliki audit event; promosi/tinggal kelas massal lintas tahun, preview, rollback, histori dan idempotensi belum ditemukan. | `RombelStudentPivotTest::test_both_relationship_directions_create_tenant_scoped_uuid_pivot_rows`; tidak ada test promosi/tinggal kelas. |
| ADM-009 | Kelulusan/alumni/histori | BELUM DITEMUKAN | Tidak ditemukan route/component/model Admin alumni/lulusan pada source tree aktif. | Belum ada bukti lifecycle kelulusan kelas 6, alumni, mutasi, atau histori lulusan. | Tidak ditemukan test khusus. |
| ADM-010 | Master mata pelajaran | PARTIAL | `AcademicManagement::createSubject/render`; `Subject` | Tambah mata pelajaran tersedia; edit/nonaktif/arsip, katalog resmi, kode nasional, Mulok dan versioning belum lengkap. createSubject tidak menulis AuditService. | `AdminUiVerificationTest::admin_academic_management_can_create_rombel_and_subject`. |
| ADM-011 | Muatan lokal (Mulok) | BELUM DITEMUKAN | Tidak ditemukan modul/route/model khusus Mulok pada Admin. | Subject model generik bukan bukti dukungan Mulok, klasifikasi, kurikulum, alokasi dan histori Mulok. | Tidak ditemukan test khusus. |
| ADM-012 | Kurmer/KBC/versioning | PARTIAL / BERISIKO | `CurriculumManagement::mount/createTp/render`; `CurriculumController`; `MultiCurriculum*`; `TenantCurriculumRegistration` | Kurikulum detail/registrasi multi-curriculum ada, tetapi integrasi Kurmer sebagai kerangka dengan KBC terintegrasi, sumber resmi, versioning dan histori Admin belum terbukti menyeluruh. Jangan mengubah W6 dalam scope audit ini. | `CurriculumIntegrityTest::test_cp_versioning_immutability`; `CurriculumVersioningTest::test_can_create_new_version_of_cp`; `Fase1cMultiCurriculumIntegrityTest`. |
| ADM-013 | CP/TP/ATP | PARTIAL / BERISIKO | `LearningOutcomeController`; `LearningObjectiveController`; `LearningPathController`; `CurriculumManagement::createTp/render`; `CurriculumAchievementService`; `TpAchievement/CpAchievement/AtpAchievement` | CP/TP/ATP model/service ada; Admin authoring/versioning ATP, source mapping, histori perubahan dan API authorization tidak konsisten. LearningOutcome CRUD selain store tidak authorize; TP/Path CRUD juga tidak authorize. | `CurriculumIntegrityTest`, `CurriculumVersioningTest`, `Phase7DCurriculumAchievementTest`; tidak membuktikan Admin authorization seluruh CRUD. |
| ADM-014 | Panca Cinta/Deep Learning/DPL | BELUM DITEMUKAN | Tidak ditemukan master data Admin Panca Cinta/DPL/Deep Learning pada source aktif. | Requirement W6 tetap menjadi acuan kebutuhan, bukan izin implementasi. | Tidak ditemukan test khusus. |
| ADM-015 | Import Excel Siswa | BELUM DITEMUKAN | Tidak ditemukan importer master Siswa; `QuestionImportService/ExcelQuestionImporter` hanya bank soal. | Belum ada template, preview, validation, duplicate handling, transaction/rollback dan error report import Siswa. | Tidak ditemukan test import Siswa. |
| ADM-016 | Import Excel Guru | BELUM DITEMUKAN | Tidak ditemukan importer master Guru. | Belum ada template, preview, duplicate handling, role/profile mapping dan error report. | Tidak ditemukan test import Guru. |
| ADM-017 | Import Excel Mapel | BELUM DITEMUKAN | Tidak ditemukan importer master Subject/Mulok. | Belum ada validasi kode, kategori, Mulok, referensi kurikulum dan duplicate handling. | Tidak ditemukan test import Mapel. |
| ADM-018 | Laporan administratif | BERMASALAH / PARTIAL | `ReportController::examResults/examSummary/studentResults/exports`; `ReportPolicy`; `ExamResultsExport`; `Phase7DReportingTest` | Laporan yang tersedia terutama laporan hasil ujian Guru; Admin justru diizinkan membaca hasil/score/analitik CBT yang dilarang. Laporan administratif madrasah terpisah belum terbukti. | `Phase7DReportingTest::test_pdf_report_export_returns_200_and_pdf_headers`; `test_multi_tenant_isolation_prevents_exporting_other_tenant_pdf`. CI aggregate lama bukan bukti Admin denial. |
| ADM-019 | Backup/restore Admin | BELUM DITEMUKAN | Tidak ditemukan route/component/service backup/restore Admin pada source aktif. | Tidak ada bukti backup terenkripsi, retensi, permission, restore disposable, integrity check atau audit restore. | Tidak ditemukan test backup/restore Admin. |
| ADM-020 | Profil/preferensi/notifikasi Admin | BELUM DITEMUKAN | Tidak ditemukan modul Admin profile/preferences/notification center. | Belum ada UI pengelolaan profil, preferensi, notifikasi dan audit pengubahan. | Tidak ditemukan test khusus. |
| ADM-021 | Audit log/health | PARTIAL / BERISIKO | `AuditService::log`; `AuditLog`; `/up`; `MonitoringController::events` | AuditService dan health route ada; UI Admin audit/health, append-only guarantee, actor/tenant retention, reason/correlation, transaction atomicity dan cakupan mutasi belum lengkap. | `ProctorAssignmentSecurityTest::test_admin_can_assign_proctor_and_assignment_is_audited`; bukan bukti append-only/global coverage. |
| ADM-022 | Shell/sidebar/responsive | BERMASALAH / PARTIAL | `resources/views/layouts/app.blade.php`; menu links; shared Admin/Guru layout | Label Panel Admin & Guru, Dashboard Operator, Proktor portal masih ada; sidebar hanya desktop (hidden md:flex), mobile drawer tidak ditemukan; nav tidak memiliki scroll container independen. | `RoleDashboardVerificationTest::guru_dashboard_access_control_and_sidebar_isolation`; tidak ada test visual/mobile/accessibility. |
| ADM-023 | Daftar ujian/assignment Proktor | BERMASALAH / P0 | `ExamManagement::mount/render`; `ProctorAssignmentManagement::assign/revoke/render`; `ExamProctorAssignmentService`; `ExamPolicy`; `ExamProctorAssignment`; `ExamController` | Admin exam list menampilkan token, snapshot/roster count dan link monitoring/results/analytics; Admin juga memiliki monitor/close/finalize/reopen/report. Proktor masih role PROKTOR/status ACTIVE; no rombel/schedule revision/overlap/schedule-review service. | `ProctorAssignmentSecurityTest`, `RoleDashboardVerificationTest::monitoring_access_control_and_tenant_isolation`, `ExamListContractTest`; tests masih menguji role/status model lama dan Admin monitoring. |

## Kesimpulan matriks ADM

- Tidak ada modul yang memenuhi definisi **SUDAH ADA DAN BERFUNGSI** berdasarkan seluruh syarat UI + backend + authorization + validation + tenant + persistence + audit + relevant test evidence.
- Temuan P0 terbuka pada ADM-018 dan ADM-023; temuan RBAC/tenant lintas modul berdampak pada ADM-001, ADM-005/007, ADM-012/013, ADM-021/022.
- ADM-009/011/014/015/016/017/019/020 belum ditemukan pada tree aktif.
- Matriks ini adalah audit statis W0. Tidak ada pengujian runtime/HTTP/browser/DB/UAT yang dijalankan untuk menyusun dokumen ini.
- Patch plan final belum disusun; audit masih harus menyelesaikan semua jalur query, export/download, cache, serialization, test mapping T-001–T-173 dan file-by-file dependency review.

## Test method references (candidate, not per-module PASS)

- Admin UI: `tests/Feature/AdminUiVerificationTest.php`
- Login: `tests/Feature/WebAdminAuthTest.php`, `AuthTest.php`
- Role/dashboard: `tests/Feature/RoleDashboardVerificationTest.php`, `RolePermissionBootstrapperTest.php`
- Curriculum: `tests/Feature/CurriculumIntegrityTest.php`, `CurriculumVersioningTest.php`, `Fase1cMultiCurriculumIntegrityTest.php`
- Tenant/relations: `tests/Feature/TenantIsolationTest.php`, `UserSecurityTest.php`, `RombelStudentPivotTest.php`
- Exam list/builder: `tests/Feature/ExamListContractTest.php`, `ExamBuilderTest.php`, `ExamManagementHardeningTest.php`
- Question/media: `tests/Feature/QuestionBankTest.php`, `QuestionImportTest.php`, `MediaAuthorizationTest.php`, `Security/AnswerKeyLeakageTest.php`
- Proktor/monitoring: `tests/Feature/ProctorAssignmentSecurityTest.php`, `MonitoringTest.php`
- Result/scoring/report: `tests/Feature/ScoringHardeningTest.php`, `StudentResultVisibilityTest.php`, `Phase7DReportingTest.php`

All above test methods remain unverified per ADM acceptance.