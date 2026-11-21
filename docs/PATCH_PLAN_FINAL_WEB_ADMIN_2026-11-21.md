# PATCH PLAN FINAL — WEB ADMIN CBT MADRASAH (MENUNGGU PERSETUJUAN)

- Tanggal: 21 November 2026 (WIB)
- Repository: `zane0184/cbt_november`
- Branch dokumentasi: `docs/canonical-reconciliation-admin-guru-20261121`
- Basis source: `work/web-admin-w0-20261121` @ `2b4321ce032fd5e67cf022da8564a5287b8cef6f`
- Source preservation pin: `recovery/source-preservation-20261121` @ `7fc45bf71ac1ebd375eba44fd57c983bf18d66e0`
- PR #5 source baseline: `ade5daa6cf853c164c4f51ec6e25ca68ce40b4f2`
- Status: **FINAL PATCH PLAN — NOT APPROVED FOR IMPLEMENTATION**
- Mode audit: read-only. Tidak ada source/database/migration/config/role/permission/PR/deployment yang diubah.

## 1. Keputusan dan batas yang tidak boleh berubah

1. Scope implementasi berikutnya hanya Web Admin Madrasah.
2. Web Guru tidak dikembangkan. Jika perubahan menyentuh shared API/service yang dipakai Guru, hanya kontrak integrasi yang boleh disiapkan; implementasi lintas-scope harus mendapat persetujuan tersendiri.
3. Role aktif Admin hanya `ADMIN_MADRASAH`. `ADMIN` dan `OPERATOR` bukan alias runtime. Akun lama tidak dihapus, tidak digabung, dan histori tidak dihilangkan.
4. Tidak ada role/portal Proktor permanen. Proktor adalah penugasan sementara kepada Guru aktif dengan TeacherProfile yang valid.
5. Status assignment final: `PENDING`, `CONFIRMED`, `NEEDS_REVIEW`, `REVOKED`, `EXPIRED`, `COMPLETED`. `ACTIVE` bukan status assignment. Hanya `CONFIRMED` dapat memberi capability setelah semua pemeriksaan tenant, exam, rombel, schedule revision, status Guru/TeacherProfile, permission/scope, revoke/conflict, dan server time.
6. Admin tidak boleh membaca konten Bank Soal/opsi/kunci/pembahasan/media; token/snapshot; roster/monitoring/attempt/answer/score/result/analytics CBT; dan tidak boleh close/finalize/reopen/grade atau mengubah ujian Guru.
7. Admin hanya mendapat metadata ujian minimum yang diperlukan untuk assignment Proktor.
8. TenantScoped bukan pengganti authorization. Setiap route/action harus memiliki policy/permission yang benar.
9. Tidak ada migration eksperimental atau production DDL sebelum preflight, backup DB, rencana rollback dan persetujuan eksplisit.
10. Tidak ada Gradle/Android scope dalam patch plan ini.

## 2. Kesimpulan audit yang menjadi dasar patch

### P0 — wajib ditutup sebelum Web Admin dapat diterima

- Admin dapat melihat isi Bank Soal, opsi, kunci dan pembahasan melalui QuestionBank Livewire dan QuestionController list/detail.
- Admin dapat membaca question publication dan media question melalui endpoint.
- Admin dapat membaca token/config ujian melalui ExamController raw serialization, AssessmentController `show()->load('exams')`, dan halaman ExamManagement.
- Admin dapat membuka monitoring, roster, attempt, result detail, score, question-level score, report/export, dan analytics melalui policies/Livewire/API.
- Admin dapat close exam, bulk finalize, finalize dan reopen score.
- Test lama justru mengharapkan Admin dapat membuka seluruh route Admin, monitoring dan reopen score. Itu harus direvisi setelah persetujuan.

### P1 — wajib diperbaiki sebelum acceptance

- Login API masih menerima role legacy ADMIN/OPERATOR/PROKTOR; web login masih menerima OPERATOR/PROKTOR; beberapa dashboard/policy/component masih menerima alias.
- Proktor service masih mewajibkan role PROKTOR dan status ACTIVE; belum ada rombel/schedule_revision/overlap guard/shared schedule-change service.
- Beberapa endpoint CRUD kurikulum tidak memiliki authorization method-level.
- IdentifyTenant tidak abort jika authenticated user tenant_id null; normal license path belum membandingkan tenant user dengan installation identity tenant.
- User tidak memiliki TenantScoped; relasi User tidak otomatis tenant-bound; sejumlah composite FK user-tenant/pivot/participant/answer/score/achievement belum ada.
- Livewire data-bearing render paths bergantung pada mount-only authorization; hydrated request tidak selalu reauthorize.
- AuditLog tidak append-only dan attribution dapat hilang melalui ON DELETE SET NULL; audit transaction coverage tidak konsisten.
- User/TeacherProfile tidak memiliki status active terpisah; Proktor service tidak mewajibkan Guru + TeacherProfile.
- Rombel analytics cache tidak diinvalidasi saat membership siswa berubah.
- Monitoring roster join ke users tidak menambahkan tenant equality secara eksplisit.

### P2 — kelengkapan modul Web Admin

- ADM-009 alumni/lulusan; ADM-011 Mulok; ADM-014 Panca Cinta/DPL; ADM-015/016/017 import master siswa/guru/mapel; ADM-019 backup/restore; ADM-020 profil/preferensi/notifikasi belum ditemukan.
- Tahun ajaran/semester, lifecycle Guru/Siswa, promosi/tinggal kelas, katalog mapel/Mulok, ATP, laporan administratif, audit/health dan responsive shell masih partial.
- Kurmer/KBC W6 tetap hanya requirement context; tidak diimplementasikan dalam patch ini tanpa scope/approval khusus.

## 3. Urutan work package

| Paket | Prioritas | Tujuan | Dependency | Gate |
|---|---|---|---|---|
| WA-01 | P0 | Admin CBT deny boundary + metadata-only exam assignment | Role permission map | Direct HTTP/Livewire negative tests pass |
| WA-02 | P1 | Role consolidation ADMIN_MADRASAH-only dan removal Operator/Proktor runtime | Legacy inventory + migration plan | Accounts/history preserved; no alias login |
| WA-03 | P1 | Proktor temporary Guru assignment final state | WA-02 + DB preflight + shared schedule contract | MySQL 8 concurrency, schedule/revision, capability tests pass |
| WA-04 | P1 | Tenant identity, User scope, composite FK and relation hardening | Disposable DB preflight | MySQL 8 mismatch inserts rejected |
| WA-05 | P1 | Curriculum CRUD authorization and audit consistency | Permission matrix approved | Role-negative + tenant tests pass |
| WA-06 | P1 | Livewire reauthorization + append-only audit | WA-01/02 | Hydration revocation + audit rollback tests pass |
| WA-07 | P2 | Complete 23 Admin modules and responsive/reporting/import/backup UX | Requirement breakdown and WA-01 boundary | End-to-end module acceptance + UAT |

Paket WA-01 and WA-04 should be prepared first; WA-02/03 need a preserved-account preflight before any data migration. WA-07 cannot be declared complete until every ADM module has UI/backend/auth/tenant/audit/test evidence.

## 4. File-by-file patch plan

Backup convention for every existing source file:
`backend-laravel/backup_before_patch/<WP>/<original-relative-path>.before`

Before editing:
1. Verify exact source branch/commit and current blob SHA.
2. Copy the file byte-for-byte to the mirrored backup path.
3. Record source blob SHA-1, backup SHA-256, timestamp, WP ID and target commit in `docs/backup/web-admin-implementation/<WP>/MANIFEST.tsv`.
4. Review the backup against source before patching.
5. Patch only in a dedicated source work branch after approval. Never edit the original ZIP/recovery branch.
6. New files are marked **NEW** and have no pre-change backup; their creation is recorded in the same manifest.

### WA-01 — Admin CBT boundary and metadata-only Proktor assignment

Paket eksekusi rinci dan status persetujuan: docs/WA01_PAKET_EKSEKUSI_WEB_ADMIN_MENUNGGU_PERSETUJUAN_2026-11-21.md. Paket itu supersedes daftar file/test WA-01 draft yang lebih lama.

**Perubahan source yang direncanakan: 23 existing files** (SHA harus cocok dengan W0 saat eksekusi):
1. app/Services/RolePermissionBootstrapper.php — fac82573de84c52b52867afbbf7e1637164835b4
2. app/Policies/QuestionPolicy.php — af94614a2043206922149b0c44a8ec1e3d87767d
3. app/Policies/AssessmentPolicy.php — 79a0367c8c83723ae3cc9e581fb8dda8a6e2d02a
4. app/Policies/ExamPolicy.php — 350c1ddc6bed8738d1af17cacd6c95bc367b7c29
5. app/Policies/ExamAttemptPolicy.php — b19d6d39d34884d8be464f786340c1a2ab7108b6
6. app/Policies/ReportPolicy.php — e53be3994c52d067c1e31fe37edc450c24a0f4b6
7. app/Http/Controllers/Api/V1/QuestionController.php — 7ded53423dd4d96788123819e8039fd543ce4fba
8. app/Http/Controllers/Api/V1/QuestionPublicationController.php — b0c3f6501601af8c6254feb1ed9cbcab7eeaaff1
9. app/Http/Controllers/Api/V1/MediaController.php — 52254b81a55db8e2e45538158ef202b8ccf09b70
10. app/Http/Controllers/Api/V1/AssessmentController.php — 28e6f8fe07c8e2b3dad22ecd190e9aa108947e78
11. app/Http/Controllers/Api/V1/ExamController.php — bf373858869b470b235e2256e5be6169fbb1f561
12. app/Http/Controllers/Api/V1/ExamParticipantController.php — f7014ab069cc2c9548f9b238e0e7a4f1986cb421
13. app/Http/Controllers/Api/V1/ExamAttemptController.php — ace6a83989375e067b3a7747bc4f320c21222c69
14. app/Http/Controllers/Api/V1/ExamAnswerController.php — 333e37007c072243fe828ab038d738fc98d88e8e (added after final route review)
15. app/Http/Controllers/Api/V1/MonitoringController.php — 335a32029f4d5ab4788ea31fd81088dfdb5acde2
16. app/Http/Controllers/Api/V1/ScoringController.php — 42b96176162a6e69e3661d468a6d3362b05f59c5
17. app/Http/Controllers/Api/V1/ReportController.php — c78442de0f5b4b40a7773bb2af0cf0ffd852d055
18. app/Http/Controllers/Api/V1/AnalyticsController.php — 3339103d64d06427896344c6e4f3bb45db8a680b
19. app/Livewire/Admin/QuestionBank.php — a3013b528015daf4214dd1aa567eba950b03663a
20. app/Livewire/Admin/ExamManagement.php — 478aaa170ee297b0c8399f049cd9b1ba56cbfb10
21. resources/views/livewire/admin/question-bank.blade.php — 72feaf1cc1be17253868c91861781dbb223b5aa9
22. resources/views/livewire/admin/exam-management.blade.php — 5705d815cc3e2539d167b22a26d3508586a77439
23. routes/web.php — c22fed7d54871bf4971acf626514fc02c4a68ce9

**Perubahan target:** Admin deny eksplisit pada Question/Assessment/Exam/Attempt/Report policies; controllers menolak sebelum query/service/cache/export/serialization sensitif; Admin QuestionBank dan ExamManagement Livewire ditolak pada mount/hydration/action/render. Guru branch dipertahankan tanpa perubahan perilaku. ExamAnswerController ditambahkan agar Admin multi-role tidak dapat melewati answer ownership guard.

**Metadata assignment:** Tidak dibuat AdminExamAssignmentResource atau endpoint baru. Admin hanya menggunakan halaman existing /admin/proctor-assignments, yang sudah memuat kolom Exam id/title/start_at/end_at dan assignment history minimum. ProctorAssignmentManagement, view-nya dan ExamProctorAssignmentService tidak diubah pada WA-01.

**Routing:** routes/web.php mendapat route-level guards untuk question/exam authoring dan result/detail/analytics surfaces; policy tetap mengizinkan Guru yang sah. routes/api.php tidak diubah; semua API routes diuji dan enforcement berada pada policy/controller. Tidak ada Teacher Livewire/view changes.

**Database:** tidak ada schema migration. Ketika RolePermissionBootstrapper kelak dijalankan, hanya CBT grants role ADMIN_MADRASAH pada role_permission yang dicabut; exam.proctor.assign dan permission administratif tetap. Export/snapshot pivot wajib sebelum bootstrap. Tidak ada akun/role/user-role/history yang dihapus atau digabung.

**Test files:** revisi 6 existing files: AdminUiVerificationTest.php, RoleDashboardVerificationTest.php, RolePermissionBootstrapperTest.php, ScoringHardeningTest.php, Security/AnswerKeyLeakageTest.php, MediaAuthorizationTest.php; tambah 1 file baru AdminCbtBoundaryTest.php. Semua backup byte-identical dan SHA-256 tercatat di manifest. Test IDs WA01-N-001..025, WA01-P-001..009, WA01-S-001..002 berstatus NOT RUN sampai bukti aktual tersedia.

**Gate:** paket eksekusi WA-01 disiapkan, belum disetujui untuk coding.

### WA-02 — ADMIN_MADRASAH-only runtime and legacy account preservation

| Source file (current Git blob SHA-1) | Planned change | Backup path | DB impact / dependency | Tests |
|---|---|---|---|---|
| `app/Http/Controllers/Api/V1/AuthController.php` — `6059f9debe3e8dc6a70fd68492adafce7ff2892a` | Add explicit API role allowlist; reject ADMIN/OPERATOR/PROKTOR legacy role runtime. | mirrored WA-02 path | Existing token/session compatibility; revoke/rotate only after migration plan | Auth/API login role matrix |
| `app/Http/Controllers/AuthWebController.php` — `1f76179dbbf304eb11271b74fbe1d1d38c74a401` | Remove OPERATOR/PROKTOR aliases; Admin redirects to Admin Madrasah surface, not Operator. | mirrored WA-02 path | Web session compatibility | WebAdminAuthTest |
| `app/Services/LoginIdentityResolver.php` — `ec1da249e643b48094459bc3eb51ce1b5e58ab39` | Restrict staff role acceptance to ADMIN_MADRASAH/KAMAD as separately approved; no ADMIN/OPERATOR/PROKTOR runtime aliases. Preserve tenant/soft-delete/ambiguity checks. | mirrored WA-02 path | Legacy accounts preflight required | LoginIdentityResolver tests |
| `app/Http/Controllers/LicenseStatusController.php` — `2e6a718c54e8aac573790cf3b117c1822759f72b` | Remove OPERATOR acceptance; ensure only authorized active roles can view license status. | mirrored WA-02 path | None | LicenseStatusAccessTest |
| `app/Livewire/Operator/OperatorDashboard.php` — `75ef8da79896994c98b00b9941f6f976a36b671c` | Remove Admin/ADMIN alias from Operator portal; do not route Admin here. | mirrored WA-02 path | UI routing | RoleDashboardVerificationTest |
| `app/Livewire/Proktor/ProktorDashboard.php` — `172fff1aa4a101193aad9061a729cc17ccb11ecc` | Retire permanent Proktor role/portal; replace with assignment-scoped Guru capability on permitted exam surface. | mirrored WA-02 path | Existing Proktor routes/tokens compatibility | Proktor route denial tests |
| `app/Livewire/Kamad/KamadDashboard.php` — `8a0fb4d402f69e5e2542cb256e704c0ecf6f7089` | Remove ADMIN alias only; preserve KAMAD as distinct role if retained by canonical register. | mirrored WA-02 path | None | RoleDashboardVerificationTest |
| `app/Livewire/Admin/AcademicManagement.php` — `5afd0780fa642354c11954fce307d8e6f7e4576f` | Remove ADMIN alias; keep ADMIN_MADRASAH-only and per-hydration authorization. | mirrored WA-02 path | None | Admin/Guru role matrix |
| `app/Livewire/Admin/CurriculumManagement.php` — `e3422650ea71709cba5ae07cd2b887cf812166a8` | Remove ADMIN alias; enforce curriculum.view/manage on every hydration/action. | mirrored WA-02 path | None | Curriculum RBAC tests |
| `app/Services/RolePermissionBootstrapper.php` — `fac82573de84c52b52867afbbf7e1637164835b4` | Stop seeding/granting OPERATOR runtime permissions. Do not delete legacy Role records until account migration is approved. | mirrored WA-02 path | Permission pivot changes; idempotent preflight | RolePermissionBootstrapperTest |
| `database/seeders/DatabaseSeeder.php` — `bb9a55dd16ac7b7e56b79dc8f5ccaca689416868` | Stop creating OPERATOR role as active Madrasah role. Preserve non-runtime historical records. | mirrored WA-02 path | Fresh install behavior | SeederSafetyTest |
| `app/Services/InstallerService.php` — `62dcecd5db10574067cef41529ce531a08e02999` | Installer creates only ADMIN_MADRASAH as Admin role; exact postconditions; no Operator/Proktor portal role. | mirrored WA-02 path | Existing install/recovery compatibility | InstallerEndToEndTest |
| **NEW** role mapping migration/service | Inventory ADMIN/OPERATOR/PROKTOR assignments; map only validated tenant-local Admin/Teacher accounts; no user merge/delete; preserve IDs/password hashes/profiles/history; ambiguous accounts go manual review. | New migration + preflight manifest | DB migration; must run on disposable restored DB first | Role migration matrix T-077–T-090, T-133–T-139 |

### WA-03 — Proktor as temporary assignment to active Guru

| Source file (current Git blob SHA-1) | Planned change | Backup path | DB impact / dependency | Tests |
|---|---|---|---|---|
| `app/Services/ExamProctorAssignmentService.php` — `e16ef5db308714aa98bf283dc5a1545608da0477` | Replace PROKTOR role requirement with active GURU + same-tenant active TeacherProfile; final status matrix; overlap check under transaction and locking current read. | mirrored WA-03 path | Additive assignment schema migration | ProctorAssignmentFinalStateTest |
| `app/Models/ExamProctorAssignment.php` — `dde7511ebb5035a76287d34dc75339a6a2bffdd8` | Remove ACTIVE scope/state; add CONFIRMED capability scope, rombel_id and schedule_revision; validate interval `starts_at <= serverNow < expires_at`. | mirrored WA-03 path | New columns/indexes/FKs | Status/interval/revision tests |
| `app/Livewire/Admin/ProctorAssignmentManagement.php` — `4e8bd91dd54757aa863d35983aace718def2ca2c` | Pick eligible Guru, not PROKTOR; show rombel/revision/conflict; reauthorize render and updatedSelectedExamId; require reason for change/revoke/review. | mirrored WA-03 path | Uses new fields | Admin assignment/review tests |
| `app/Policies/ExamPolicy.php` — `350c1ddc6bed8738d1af17cacd6c95bc367b7c29` | `assignProctor/revokeProctor` remain Admin_Madrasah; `monitor` grants temporary capability to confirmed Guru assignment, no PROKTOR role branch. | mirrored WA-03 path | None | Confirmed assignment capability tests |
| `app/Livewire/Proktor/ProktorDashboard.php` — `172fff1aa4a101193aad9061a729cc17ccb11ecc` | Remove permanent portal. | mirrored WA-03 path | Route/token compatibility | No permanent role/portal test |
| `routes/web.php` — `c22fed7d54871bf4971acf626514fc02c4a68ce9` | Remove /proktor/assignments permanent role portal; assignment context integrated into allowed Guru monitoring surface only after cross-scope approval. | mirrored WA-03 path | Route compatibility | Route/role tests |
| `database/migrations/2026_11_20_100001_create_exam_proctor_assignments_table.php` — `e51ef878e14d13078d81585fbbc10e1d00b76e25` | Do not edit historical migration. Add new additive migration for status/rombel/revision/confirmation audit fields and composite FK. | Existing migration preserved; new migration separately | Data migration: old ACTIVE rows become NEEDS_REVIEW unless fully provable; no auto-confirm if rombel/revision unknown. | MySQL 8 migration preflight |
| **NEW** `app/Services/ExamScheduleChangeService.php` | Shared contract/service: lock exam, reject after attempt starts, increment schedule revision, mark all affected CONFIRMED assignments NEEDS_REVIEW atomically, audit before/after. | New file | Shared service dependency | T-059–T-064, T-108–T-115, T-163–T-168 |
| `app/Http/Controllers/Api/V1/ExamController.php` — `bf373858869b470b235e2256e5be6169fbb1f561` | Schedule update integration with shared service. This touches the existing Guru API; implementation is **BLOCKED** until explicit approval for shared/Guru integration. | mirrored WA-03 path | No schema beyond revision migration | Shared service integration tests |
| `app/Livewire/Admin/ExamManagement.php` — `478aaa170ee297b0c8399f049cd9b1ba56cbfb10` | If this shared component still handles Guru schedule saves, call shared schedule service. This is **BLOCKED** under current no-Web-Guru scope until separately approved. | mirrored WA-03 path | None | API/Livewire schedule parity |
| **NEW** migration | Add rombel_id, schedule_revision, confirmed_at/by, needs_review_at/by as needed; add composite tenant FKs; add indexes for tenant+Guru+interval+status. | New migration | Preflight invalid/ambiguous assignments; old ACTIVE records preserved as NEEDS_REVIEW; no destructive delete | Two-connection MySQL 8 overlap/concurrency tests |

**DESAIN TERPILIH oleh pengguna — belum diimplementasikan:** users.account_status = ACTIVE/SUSPENDED/INACTIVE dan teacher_profiles.employment_status = ACTIVE/ON_LEAVE/INACTIVE/ENDED. Guru eligible untuk Proktor hanya jika kedua status ACTIVE, role GURU, tepat satu TeacherProfile valid dan tenant-local, tidak dihapus, serta permission/scope/assignment checks lolos.

Tidak ada migration/database write yang diizinkan sekarang. Default aman untuk data lama dan akun/profil baru adalah INACTIVE; tidak ada auto-activation. Record ambigu, role ganda, missing/duplicate profile, tenant mismatch, deleted profile, atau identitas tidak terverifikasi tetap INACTIVE/NEEDS_REVIEW. Indeks kandidat users(tenant_id,account_status,id) dan teacher_profiles(tenant_id,employment_status,deleted_at,user_id); unique tenant/user profile dan composite FK harus dikoordinasikan dengan WA-04 setelah duplicate/orphan preflight. CHECK constraints memerlukan MySQL 8.0.16+ atau revisi desain constraint. Detail transition mapping, preflight, backup, compatibility dan rollback ada pada paket WA-01 section 13; berlaku sebagai dependency desain WA-03, bukan izin migration.

**Scope guard:** Tidak ada file `app/Livewire/Teacher/*` atau view Guru yang diubah. Admin denial pada halaman hasil/monitoring/analytics Guru dilakukan melalui policy/controller/route authorization. Pada cutover WA-01/02, session Admin lama dan Sanctum token Admin/legacy dicabut agar component state lama tidak dapat dihidrasi setelah hak akses dipersempit. Perubahan shared Livewire middleware atau komponen Guru untuk revocation real-time adalah cross-scope dan memerlukan persetujuan terpisah.

| Source file (current Git blob SHA-1) | Planned change | Backup path | DB impact / dependency | Tests |
|---|---|---|---|---|
| `app/Livewire/Admin/AcademicManagement.php` — `5afd0780fa642354c11954fce307d8e6f7e4576f` | Reauthorize boot/hydration and render; remove ADMIN alias; add audit events for createRombel/createSubject. | mirrored WA-06 path | None | LivewirePermissionHydrationTest |
| `app/Livewire/Admin/CurriculumManagement.php` — `e3422650ea71709cba5ae07cd2b887cf812166a8` | Reauthorize every hydration/render/action; add audit event to createTp. | mirrored WA-06 path | None | Livewire permission revocation test |
| `app/Livewire/Admin/UserManagement.php` — `5f90e3873cadeb77a1bcd5a6aa2134953e60beed` | Reauthorize every hydration/render/action; add audit event to createUser; preserve Admin-only creation of GURU/SISWA. | mirrored WA-06 path | None | UserManagement audit/hydration |
| `app/Livewire/Admin/ProctorAssignmentManagement.php` — `4e8bd91dd54757aa863d35983aace718def2ca2c` | Reauthorize render and selected-exam updater; use new assignment service. | mirrored WA-06 path | WA-03 | Permission revocation and assignment tests |
| `app/Livewire/Admin/StudentRombelManagement.php` — `0da3f15787fbaf1fe6ea6f0eaec1439f413231f1` | Reauthorize hydration; invalidate tenant/rombel analytics cache after membership changes. | mirrored WA-06 path | None | Cache invalidation test |
| `app/Services/AuditService.php` — `e968cc401cdd8b04ab1322171d5f475332a55372` | Add reason/correlation/request ID support and enforce transaction use for sensitive mutations. | mirrored WA-06 path | Additive audit columns if approved | Audit atomicity tests |
| `app/Models/AuditLog.php` — `c8789bc5b78b915dfbb8ade7cfd031ddf72c4a4c` | Make model append-only: reject update/delete; preserve actor/tenant snapshot. | mirrored WA-06 path | Audit table constraint/trigger migration | AuditLogAppendOnlyTest |
| `database/migrations/2024_01_01_000003_create_audit_logs_table.php` — `95e443ea28c7a794e8b7f06c467dcca4e04c1688` | Do not edit historical migration. Add new migration for actor/tenant retention, reason/correlation and append-only DB guard. | Existing migration preserved; new migration separately | MySQL 8 trigger/constraint or restricted DB privileges; plan rollback carefully | MySQL audit tamper/rollback tests |

### WA-07 — Admin module completeness (separate implementation design after P0/P1)

| ADM | New/extended file plan | Dependencies / DB impact | Acceptance |
|---|---|---|---|
| ADM-001 | New AdminDashboard component/view; update OperatorDashboard routing and layout | No DB expected; Admin role only | Admin dashboard shows only tenant administrative KPIs |
| ADM-002 | MadrasahProfileManagement component + profile service | Tenant profile fields/logo/storage validation; migration only if missing fields | CRUD profile, private asset, audit |
| ADM-003 | Extend AcademicManagement or new AcademicPeriodManagement | AcademicYear/Semester lifecycle and uniqueness | Open/close periods; preserve history |
| ADM-004 | Extend AcademicManagement/StudentRombelManagement | Rombel lifecycle, homeroom teacher relation | Class/phase/rombel/wali teacher lifecycle |
| ADM-005/007 | Extend UserManagement | Existing User/TeacherProfile/StudentProfile; active state decision | Create/update/activate/archive/reset with audit |
| ADM-006 | Admin UI for TeacherSubjectAssignment | Existing API/controller; no Web Guru development | Admin assignment with conflict/history |
| ADM-008 | New StudentPromotionManagement + service | Transactional academic year transfer and history | Promote/retain/transfer idempotently |
| ADM-009 | New AlumniManagement + model/service if not already in source | Additive alumni status/history schema | Graduates retained; no student history deletion |
| ADM-010/011 | Extend Subject management for official subjects and Mulok | Subject classification/code/version fields only after schema review | Mapel/Mulok CRUD and validation |
| ADM-012/013 | Extend CurriculumManagement/API with CP/TP/ATP versioned workflows | Keep W6 requirements read-only until separate approval | Kurmer framework, CP/TP/ATP and version history |
| ADM-014 | New Panca Cinta/DPL master and mapping UI | W6 requirement approval and schema design | Panca Cinta/DPL integrated, not competing curriculum |
| ADM-015/016/017 | New import services/controllers/components for Siswa/Guru/Mapel | Preview/staging, validation, duplicate rules, transaction/rollback | Valid/invalid/duplicate/failure import tests |
| ADM-018 | New AdminAdministrativeReportController/service/views | Must not reuse Guru CBT results/score policies | Administrative reports only |
| ADM-019 | New backup/restore service/controller/component | Secure storage, encryption, retention, restore to disposable DB only | Backup hash + restore verification + audit |
| ADM-020 | New AdminProfile/Preference/Notification components | New tables only if required | Profile/preferences/notification CRUD |
| ADM-021 | AuditLog/health UI | WA-06 append-only audit | Filter by tenant/actor/action; no mutation |
| ADM-022 | Update `resources/views/layouts/app.blade.php` | Responsive nav/drawer, independent scroll | Desktop/tablet/mobile/accessibility |
| ADM-023 | Extend metadata-only ExamManagement and ProctorAssignmentManagement | WA-01 + WA-03 | Admin can assign/review/revoke only |

New files in WA-07 are intentionally not enumerated as fixed paths where source does not yet exist; create exact paths only after requirements and data model approval. W6 is not implemented by this plan.

## 5. Database migration impact and preflight

No migration is approved or run by this plan. Before any DB migration:
1. Restore a recent DB backup to a disposable MySQL 8/InnoDB environment.
2. Run SELECT-only preflight for every proposed FK pair and status mapping.
3. Export counts and row IDs for mismatches; no DML.
4. Validate migration up/down on disposable DB; test idempotency and restore.
5. Obtain explicit approval before any production backup/migration window.

### Proposed migration groups

- **WA-02 role mapping:** preserve user IDs, password hashes, tenant_id, profiles, role history, tokens and audit history. Map only unambiguous legacy ADMIN/OPERATOR to ADMIN_MADRASAH; map legacy PROKTOR to GURU only when TeacherProfile is valid; ambiguous/orphan/multi-role accounts go manual review. No merge/delete.
- **WA-03 Proktor assignment:** add rombel_id, schedule_revision, confirmation/review audit metadata; map old ACTIVE rows to NEEDS_REVIEW unless every eligibility/scope/revision fact is provable. Do not auto-confirm old assignments.
- **WA-04 tenant composite FKs:** add parent unique indexes and composite FKs for StudentProfile/User, RombelStudent/Rombel+StudentProfile, ExamParticipant/User, ExamAttempt/Participant, Answer/Student, Score/Student/Grader/Finalizer and achievement Student/Finalizer/Rombel/Semester. Abort migration if preflight mismatch count is nonzero.
- **WA-06 audit:** add reason/correlation/actor snapshot columns if required; replace ON DELETE SET NULL behavior with retained immutable attribution strategy; add MySQL append-only enforcement. Trigger/privilege design must be tested on MySQL 8 and documented for restore.
- **WA-07 modules:** each schema change gets a separate migration and acceptance plan; no broad migration bundle.

## 6. Compatibility and deployment considerations

- API v1 payloads for Guru/Siswa must remain compatible. Admin receives a new metadata-only DTO/route contract; do not remove fields from Guru responses. No Teacher Livewire component/view is changed in this Web Admin patch plan.
- Existing Admin users with legacy ADMIN/OPERATOR roles must be inventoried before login restrictions. Do not delete/merge distinct accounts. Keep role mapping audit. Revoke legacy Admin sessions/tokens at cutover while preserving user records and history.
- Existing Proktor role/token/assignment records must be preserved and moved to NEEDS_REVIEW/manual reconciliation; no permanent Proktor portal after cutover.
- Admin requests to CBT results/monitoring/score/token/question routes will intentionally return 403/404 after WA-01.
- Shared Exam schedule update path affects Guru. No Web Guru feature work is included; schedule service integration is blocked until explicit cross-scope approval. Any change to Teacher Livewire components or views is excluded.
- MySQL 8/InnoDB is authoritative for locking/FK tests. SQLite is not acceptable for Proktor overlap/concurrency or production schema acceptance.
- Cache keys are tenant-prefixed; deployment must clear relevant tenant analytics cache after changes and verify invalidation on student-rombel membership changes.
- Build/runtime/deployment/VPS are outside this patch plan.

## 7. Backup and change-control procedure

For every source file listed in §4:
- Verify its current blob SHA against the source pin; if changed since audit, stop and re-audit that file.
- Create byte-identical backup at `backend-laravel/backup_before_patch/<WP>/<relative-path>.before`.
- Compute SHA-256 of backup and record both source Git blob SHA-1 and backup SHA-256 in `docs/backup/web-admin-implementation/<WP>/MANIFEST.tsv`.
- Keep documentation commits on `docs/canonical-reconciliation-admin-guru-20261121`; keep source patch commits on a separate approved work branch. Never mix source code and docs commits.
- Do not merge, close or create PR without approval.
- No backup is created now because no source file was modified.

## 8. Test plan and required evidence

### Existing test files to revise (all current methods are candidate evidence only)

- `tests/Feature/AdminUiVerificationTest.php`
- `tests/Feature/RoleDashboardVerificationTest.php`
- `tests/Feature/ScoringHardeningTest.php`
- `tests/Feature/Security/AnswerKeyLeakageTest.php`
- `tests/Feature/ProctorAssignmentSecurityTest.php`
- `tests/Feature/RBACEnforcementTest.php`
- `tests/Feature/TenantIsolationTest.php`
- `tests/Feature/UserSecurityTest.php`
- `tests/Feature/WebAdminAuthTest.php`
- `tests/Feature/MediaAuthorizationTest.php`
- `tests/Feature/Phase7DReportingTest.php`
- `tests/Feature/RombelStudentPivotTest.php`
- `tests/Feature/RolePermissionBootstrapperTest.php`
- `tests/Feature/SeederSafetyTest.php`
- `tests/Feature/ExamListContractTest.php`
- `tests/Feature/ExamHardeningTest.php`
- `tests/Feature/ExamManagementHardeningTest.php`
- `tests/Feature/CurriculumIntegrityTest.php`
- `tests/Feature/CurriculumVersioningTest.php`
- `tests/Feature/StudentResultVisibilityTest.php`
- `tests/Feature/Analytics/SecurityTest.php`
- `tests/Feature/InstallerEndToEndTest.php`
- `tests/Feature/StudentWebLoginTest.php`

### New test files

- `AdminCbtBoundaryTest.php`: direct API/Livewire deny coverage for questions, answer keys, media, token, roster, monitoring, attempt, answers, scores, results, analytics, export, close/finalize/reopen.
- `AdminCurriculumAuthorizationTest.php`: Admin allow, Guru/Siswa deny/allow per approved permission, all GradeLevel/Element/Outcome/Objective/Path/Material/Tag methods.
- `LivewirePermissionHydrationTest.php`: revoke role/permission after mount on Admin components only; subsequent render/action must deny and query fail closed. Do not modify Teacher Livewire components.
- `TenantIdentityBindingTest.php`: null tenant, stale token, mismatched installation identity, multiple tenant rows, domain mismatch.
- `TenantCompositeForeignKeyIntegrityTest.php`: attempt invalid cross-tenant inserts on MySQL 8; every composite FK must reject.
- `ProctorAssignmentFinalStateTest.php`: Guru active + TeacherProfile, final status matrix, rombel/revision, interval boundary, overlap, two-connection concurrency, revoke/expiry and schedule review.
- `AuditLogAppendOnlyTest.php`: update/delete/tamper denied; actor/tenant attribution retained; failed audit rolls back mutation.
- `AnalyticsCacheInvalidationTest.php`: score changes and student-rombel membership changes invalidate correct tenant/rombel/exam keys.
- `AdminModuleAcceptanceTest.php`: ADM-001–ADM-023 end-to-end trace to UI/API/authorization/tenant/audit.

### Acceptance run evidence

Every T-001–T-173 must record source commit, test file/method, MySQL 8 environment, command, exit code, assertions, log/artifact, PASS/FAIL/BLOCKED/NOT RUN. The current mapping file marks all IDs NOT VERIFIED; it is not acceptance evidence.

## 9. Rollback plan

- **Code:** revert only the approved patch commit or restore the per-file backup; no force-push. Keep the original W0 and recovery branches immutable. Restore Admin sessions/tokens only if rollback is explicitly approved; do not affect Guru sessions.
- **RBAC:** restore role grants from a pre-migration export; preserve user IDs and pivot history. Do not re-enable aliases silently.
- **Proktor:** restore assignment records/status from DB backup; if new rows/status are invalid, place in NEEDS_REVIEW rather than granting capability.
- **Tenant FKs:** rollback only after confirming no dependent writes rely on the new constraint; retain the backup DB and data reconciliation report. Never drop data to make rollback succeed.
- **Audit:** append-only trigger rollback requires explicit approval and preservation of all audit rows; no truncation.
- **Cache:** clear tenant-prefixed analytics cache; cache is reconstructible from authoritative DB.
- **UI:** revert layout/view/component commit together to avoid route/view mismatch.

## 10. Acceptance criteria / stop gate

Web Admin cannot be accepted until:
1. All P0 findings are closed with direct API and Livewire negative tests; no token/question key/media/roster/attempt/score/result/analytics leaks to Admin.
2. Admin can access only metadata needed for temporary Guru assignment; cannot close/finalize/reopen or manage Guru exam content.
3. Guru operational behavior remains intact; no Web Guru feature is added.
4. ADMIN_MADRASAH is the only active Admin role; ADMIN/OPERATOR aliases are rejected; accounts/history preserved.
5. Proktor is only a temporary confirmed Guru assignment; no permanent PROKTOR role/portal; overlap and schedule revision tests pass on two MySQL 8 connections.
6. Tenant identity fails closed; User and all sensitive User relations have tenant-aware scopes/constraints.
7. Curriculum CRUD has method-level authorization for every route/action.
8. AuditLog is append-only/attributable and sensitive mutations are atomic with audit writes.
9. ADM-001–ADM-023 each has complete UI/backend/authorization/validation/tenant/audit/test evidence or an explicitly approved deferred scope.
10. T-001–T-173 each has an actual run record; no ID marked PASS from source presence or aggregate CI alone.
11. Responsive/accessibility/browser tests and operational Madrasah UAT are complete.
12. No P0/P1 unresolved; residual risks documented.
13. User explicitly approves final Web Admin acceptance.

## 11. Current source file provenance

The source blob SHA-1s in §4 identify the exact W0 snapshot files reviewed. The full SHA list is also available in the audit report sections 12.8 and 15.6. Source pin/branch must be rechecked before any implementation; if any blob differs, re-audit before patching.

## 12. Final status

- Static source-path audit: **COMPLETE for the Web Admin access paths and relevant source surfaces inventoried**.
- ADM-001–ADM-023 mapping: **23/23 published**.
- T-001–T-173 mapping: **173/173 published; all NOT VERIFIED per-ID**.
- Runtime HTTP/Livewire/browser/MySQL concurrency/UAT: **NOT RUN** in this audit session.
- P0/P1 findings: **OPEN** until approved patches and tests.
- Implementation: **NOT STARTED**.
- Patch plan: **FINAL, WAITING FOR EXPLICIT USER APPROVAL**.
- No source/database/migration/config/role/permission/Web Guru/PR/deployment changed.


## 13. Final review addendum — 21 November 2026

### Status dokumen yang berlaku
Addendum ini mengungguli narasi status historis yang masih tertinggal di dokumen lama. **Audit source statis: SELESAI. Acceptance testing: BELUM SELESAI. Implementasi: NOT STARTED dan MENUNGGU PERSETUJUAN EKSPLISIT.** Tidak ada klaim runtime PASS yang diturunkan dari keberadaan test method atau CI aggregate.

### Status 23 modul ADM
Semua ADM-001–ADM-023 mempunyai status implementasi statis pada matriks detail (23/23). Status tersebut adalah kondisi source yang ditemukan, bukan hasil acceptance. Status aktual per modul tetap persis seperti baris ADM pada matriks; tidak ada modul yang dinyatakan complete. Acceptance per modul: **NOT VERIFIED / NOT RUN** sampai ada run evidence yang ditautkan ke ID modul, commit, lingkungan MySQL 8, perintah, exit code, assertion, dan artefak.

### Status 173 test ID
T-001–T-173 lengkap dipetakan 173/173. Status aktual setiap ID tetap **NOT VERIFIED**; test method/source candidate hanya membuktikan kandidat lokasi pengujian. CI #692 adalah bukti aggregate historis pada commit yang berbeda, bukan bukti run per-ID dan bukan acceptance untuk W0. Dilarang mengubah status menjadi PASS tanpa run record individual yang dapat diaudit.

### WA-03 — batas scope yang diperjelas
**Dalam scope Web Admin, hanya setelah approval:** desain/penerapan Admin assignment UI dan authorization, assignment state machine, penyimpanan rombel_id/schedule_revision/metadata konfirmasi, validasi Guru dan tenant, audit, serta concurrency tests yang dapat diselesaikan tanpa mengubah jalur Guru. Komponen Admin hanya boleh disentuh pada cabang Admin yang terisolasi; ExamManagement adalah komponen shared sehingga perubahan schedule-save atau branch Guru tidak boleh dilakukan tanpa approval tambahan.

**Cross-scope BLOCKED — memerlukan persetujuan terpisah:** ExamScheduleChangeService jika dipakai bersama alur Guru; integrasi ExamController untuk perubahan jadwal; perubahan schedule-save pada shared ExamManagement; perubahan route/API/service/middleware/Livewire/view yang mengubah perilaku Guru; dan seluruh file app/Livewire/Teacher/* atau view Guru. Tidak ada pekerjaan WA-03 yang boleh mengubah API, service, Livewire, view, atau perilaku Guru di bawah approval Web Admin saja. File ExamController.php, ExamManagement.php, routes/web.php hanya boleh disentuh untuk bagian Admin yang terbukti terpisah; jika isolasi tidak dapat dibuktikan, file dikeluarkan dari patch dan dependency dinaikkan ke approval terpisah.

### Source provenance recheck
Semua **71 path source existing** yang memiliki blob SHA-1 pada tabel WA-01 s.d. WA-06 telah di-fetch ulang langsung dari work/web-admin-w0-20261121; seluruh 71/71 SHA cocok dengan nilai pada patch plan. Branch tersebut dibandingkan dengan source pin 2b4321ce032fd5e67cf022da8564a5287b8cef6f dan hasilnya **identical, 0 ahead / 0 behind**. SHA pada tabel merujuk ke source branch tersebut, bukan blob dokumen di branch dokumentasi. File berlabel NEW belum memiliki source blob SHA dan tidak memiliki backup pre-change; pembuatan file baru harus tercatat di manifest implementasi setelah approval. WA-07 adalah rencana modul tingkat fitur dan belum memiliki daftar file existing/blob SHA final.

### Dependency, DB dan rollback yang wajib dikunci sebelum patch
- WA-01: tanpa schema migration yang direncanakan; perlu audit route/policy/response allowlist dan regresi akses Guru. Permission seeding harus idempotent. Rollback kode/policy harus satu commit terkoordinasi; jangan mengembalikan Admin CBT access sebagai workaround.
- WA-02: perubahan role/permission pivot dan perilaku installer/login; migration data hanya setelah inventaris akun legacy, pemetaan deterministik, dan daftar manual review. Tidak boleh menghapus/merge akun. Rollback memulihkan grant/session/token secara terkontrol, bukan menghidupkan alias tanpa audit.
- WA-03: migration additive untuk status assignment/rombel/revision/confirmation hanya setelah keputusan active-state Guru dan preflight data. Baris legacy ACTIVE tidak otomatis menjadi CONFIRMED; yang tidak dapat dibuktikan masuk NEEDS_REVIEW. Rollback tidak boleh mengubah assignment ambigu menjadi capability aktif.
- WA-04: migration composite FK/indexes memerlukan parent candidate keys, pemeriksaan tipe/charset/collation/UUID, orphan/mismatch preflight, urutan constraint, dan estimasi metadata lock. DDL MySQL dapat implicit commit; rollback wajib berupa migration balik yang diuji pada clone dan restore backup, bukan asumsi transaksi DDL.
- WA-05: tidak diperkirakan perlu schema migration; policy registration/permission map harus diuji per method dan per role. Rollback mengembalikan policy/route commit tanpa membuka akses lintas tenant.
- WA-06: audit columns/trigger/constraint memerlukan migration additive; tentukan hak DB yang dapat melewati trigger, retensi actor/tenant, transaction boundary, dan strategi rollback tanpa menghapus log. Cache invalidation diuji pada tenant/rombel yang berubah.
- WA-07: setiap modul memiliki discovery/design/schema review tersendiri; tidak boleh digabung ke patch keamanan P0/P1 sebelum acceptance dasar aman.

### Risiko yang tidak dapat dibuktikan hanya dari pembacaan source
1. Apakah database aktual berisi orphan/mismatch, duplikasi, status legacy ambigu, constraint drift, atau data yang menggagalkan migration.
2. Race condition assignment overlap, schedule revision, revoke/reconfirm, simultaneous Admin actions, deadlock, lock timeout, isolation-level behavior dan server clock skew.
3. Apakah MySQL 8/InnoDB menerima semua composite FK/index dengan definisi kolom yang persis kompatibel, dan dampak metadata lock/DDL downtime.
4. Apakah Livewire hydration, browser back/restore, multi-tab, stale session, Sanctum token, permission revocation dan cache benar-benar fail-closed pada request berikutnya.
5. Apakah API serialization, exception/debug response, media download, export, pagination, queue/command dan cache dapat membocorkan data pada kombinasi role/tenant yang tidak tercakup.
6. Apakah audit tetap immutable ketika aplikasi memakai raw SQL, privileged DB account, cascade, restore, atau operasi maintenance.
7. Apakah rollback dan restore benar-benar mempertahankan UUID, FK, audit history, session/token dan tenant boundaries.
8. Apakah jadwal Guru yang berubah bersamaan dengan penugasan Proktor tidak menghasilkan assignment stale atau capability sementara.
9. Apakah perubahan role/permission memutus akses Admin/Guru yang sah atau mengunci instalasi lama.
10. Apakah UI Admin responsif, accessible, dan alur operasional dapat diterima oleh madrasah.

### Pengujian wajib pada MySQL 8 / InnoDB
SQLite tidak boleh dipakai untuk menyimpulkan keamanan concurrency Proktor atau validitas DDL/FK MySQL. Wajib gunakan database MySQL 8/InnoDB disposable yang dipulihkan dari snapshot representatif:
- Dua atau lebih koneksi independen melakukan assignment overlap bersamaan untuk Guru yang sama, Guru berbeda, ujian sama/berbeda, tenant sama/berbeda; tepat satu transaksi konflik harus ditolak dan tidak ada double-confirm.
- Uji row/gap/current locking, isolation level, deadlock, retry, lock timeout, idempotency key, revoke-vs-confirm, reschedule-vs-confirm, expiry-vs-request, dan rollback transaksi.
- Uji server time sebagai acuan, interval batas, timezone, schedule revision stale, perubahan jadwal setelah attempt dimulai, dan assignment NEEDS_REVIEW.
- Jalankan migration WA-03/WA-04/WA-06 pada clone MySQL 8: schema introspection, candidate unique indexes, FK mismatch/orphan preflight, apply, verify constraints, rollback/restore, dan uji data tetap utuh.
- Uji tenant composite FK dengan insert/update langsung yang sengaja mismatch untuk setiap relasi; database harus menolak tanpa mengandalkan Eloquent.
- Uji AuditLog update/delete/raw SQL sesuai privilege model; kegagalan audit harus membatalkan mutation sensitif bila disyaratkan.
- Uji query/API/Livewire denial Admin dan positive Guru regression; hasil harus menguji body/serialization, bukan hanya HTTP status.
- Jalankan browser multi-session dan UAT Admin/Guru/Siswa di lingkungan terisolasi. Guru hanya untuk regresi dan tidak boleh diubah tanpa approval lintas-scope.

### File count dan urutan aman
- Existing source file dengan SHA terverifikasi: **71 path** di WA-01–WA-06.
- New source/test/migration/policy files: hanya dibuat setelah approval dan dicatat sebagai NEW di manifest; WA-07 file discovery masih terbuka.
- Urutan yang direkomendasikan: (0) approval dan freeze scope; (1) WA-01 P0 Admin CBT deny/metadata boundary tanpa shared Guru edits; (2) WA-04 tenant identity/FK preflight dan migration pada clone; (3) WA-05 curriculum authorization; (4) WA-06 Admin hydration/audit; (5) WA-02 role/legacy account transition setelah inventaris dan dry-run; (6) WA-03 Admin assignment core setelah keputusan status Guru, sementara semua shared schedule/Guru integration tetap BLOCKED sampai approval terpisah; (7) WA-07 per-modul setelah P0/P1 acceptance.
Urutan dapat disesuaikan hanya berdasarkan dependency hasil test; tidak ada tahap yang otomatis disetujui oleh dokumen ini.

### Stop gate
Dokumen ini adalah final review dan patch plan, bukan izin kerja. Tidak ada source, database, migration, seeder, role, permission, config, Web Guru, Android, VPS atau PR yang diubah. **Berhenti di sini dan tunggu persetujuan eksplisit pengguna.**


### WA-03 cross-scope clarification — shared policy/service
Untuk menghilangkan ambiguitas pada tabel file WA-03: perubahan ExamPolicy.monitor yang memberi capability kepada Guru melalui assignment CONFIRMED adalah perubahan shared authorization dan **CROSS-SCOPE BLOCKED** sampai approval terpisah. ExamProctorAssignmentService juga dianggap shared bila dipanggil dari alur selain Admin assignment; pemisahan adapter/service Admin harus dibuktikan sebelum perubahan. Perubahan pada ExamController, shared ExamManagement, routes/web.php, shared API/service/middleware, atau file lain yang memengaruhi schedule/capability Guru juga BLOCKED. Tabel file WA-03 adalah daftar kandidat yang perlu dibahas, bukan izin mengubah seluruh baris. Dalam approval Web Admin saja, hanya file/branch yang dapat dibuktikan eksklusif Admin boleh dipatch; file shared yang tidak dapat dipisahkan harus dikeluarkan dan menunggu approval lintas-scope.


## 14. WA-01 execution package — preparation only

- Paket eksekusi: docs/WA01_PAKET_EKSEKUSI_WEB_ADMIN_MENUNGGU_PERSETUJUAN_2026-11-21.md
- Commit publikasi paket: 2ef53b77908f91caa2b216a6d24f29df4dce9cdb
- Backup source/test manifest: docs/backup/wa-01-source-2026-11-21/MANIFEST.md
- Backup root: docs/backup/wa-01-source-2026-11-21/backend-laravel/
- 23 source files dan 6 existing test files telah disalin byte-identical dari source W0; file baru AdminCbtBoundaryTest.php belum dibuat.
- 36 test IDs sudah direncanakan; semua hasil aktual NOT RUN.
- Desain dua status Guru telah dipilih; tidak ada migration, seeder execution atau DB change.
- WA-01 belum mendapat persetujuan coding. Stop gate tetap berlaku.
