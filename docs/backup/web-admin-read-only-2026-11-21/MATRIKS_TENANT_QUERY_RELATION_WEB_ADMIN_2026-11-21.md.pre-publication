# MATRIKS AUDIT TENANT QUERY, RELATION, DAN FOREIGN KEY — WEB ADMIN

- Tanggal: 21 November 2026 (WIB)
- Repository: `zane0184/cbt_november`
- Branch dokumentasi: `docs/canonical-reconciliation-admin-guru-20261121`
- Source branch: `work/web-admin-w0-20261121` @ `2b4321ce032fd5e67cf022da8564a5287b8cef6f`
- Status: audit statis; tidak ada database connection atau DDL yang dijalankan.

## 1. Model inventory

Snapshot W0 memiliki **56 model PHP**:
- 40 menggunakan trait `TenantScoped`.
- 16 tidak menggunakan trait tersebut; beberapa adalah katalog global/installation-level, beberapa memiliki tenant_id dan kontrol khusus.

| Model tanpa TenantScoped | Klasifikasi | Kontrol yang ditemukan | Risiko/status |
|---|---|---|---|
| AuditLog | Audit tenant-aware | MonitoringController memfilter tenant_id; AuditService mengisi tenant_id/user_id | P1: tidak append-only; FK attribution SET NULL |
| LocalActivationAttempt | Installation-level | Query berdasarkan installation_identity_id | PARTIAL; bukan data tenant UI |
| LocalInstallationIdentity | Satu identity per instalasi | LoginIdentityResolver dan InstallationIdentityService memverifikasi satu record/tenant | PARTIAL; registered_domain belum selalu dibandingkan ulang |
| LocalLicenseEvent | Installation-level | FK installation_identity_id | PARTIAL; bukan data tenant UI |
| LocalLicenseState | Installation-level | Query berdasarkan installation_identity_id | PARTIAL |
| MultiCurriculum | Global/system atau owner tenant | tenant_id + isGlobal; versi terkait | PARTIAL; W6 requirements only |
| MultiCurriculumStructure | Kurikulum global/tenant | tenant_id, version relation; Fase1c composite FK | PARTIAL |
| MultiCurriculumVersion | Kurikulum global/tenant | tenant_id; composite FK ke parent curriculum | PARTIAL |
| MultiLearningOutcome | Kurikulum global/tenant | tenant_id; composite FK structure/version/tenant | PARTIAL |
| Permission | Katalog global | Nama permission global | Expected global |
| QuestionMultiCurriculumMapping | Mapping soal/kurikulum | tenant_id, curriculum_owner_id; composite FK question-tenant, outcome-version-owner, registration triplet | PARTIAL; model hook tidak menggantikan DB FK |
| Role | Katalog global | Role name global | Expected global; tetapi legacy OPERATOR/PROKTOR masih ada |
| RombelStudent | Pivot tenant | boot hook memaksa tenant dari parent/context; relation wherePivot tenant | P1: tidak ada composite FK pair di migration |
| Tenant | Root entity | Identity/install guard | Expected global root |
| TenantCurriculumRegistration | Registrasi tenant/owner | boot hook hanya System Tenant atau tenant sendiri; composite FK version-owner triplet | PARTIAL |
| User | Identitas tenant | Banyak query explicit where tenant; user creation memaksa context | P1: tidak ada global tenant scope; relasi belongsTo(User) tidak otomatis tenant-bound |

## 2. withoutGlobalScopes inventory — koreksi hitungan

Hitungan langsung pada source snapshot aktif:
- 25 pemanggilan `withoutGlobalScopes()`.
- 5 pemanggilan `withoutGlobalScope(...)`.
- Total **30 occurrence** pada **8 file**. Angka 36 occurrence/10 file dalam catatan awal adalah hitungan sementara dan digantikan oleh inventaris source ini.

| File | Jumlah occurrence | Tujuan/query | Tenant guard yang diperiksa | Hasil statis |
|---|---:|---|---|---|
| `app/Console/Commands/CleanupOrphanedMediaCommand.php` | 4 | Scan soft-deleted media global untuk cleanup; re-fetch row dengan lock; cek snapshot references | Candidate mencatat tenant_id; re-fetch memakai id+tenant_id; storage path wajib `private/tenants/{tenant}/media/{filename}`; reference check tenant-filtered | PARTIAL/POSITIVE; maintenance global by design, tenant path guard ada |
| `app/Console/Commands/ExpireExamAttemptsCommand.php` | 5 | Worker mencari expired attempts lintas tenant dan revalidasi dengan lock | Mengambil tenant_id dari candidate, set TenantContext sementara, lalu lock Exam/Participant/Attempt/Snapshot dengan tenant_id + parent IDs; restore context di finally | POSITIVE statis; tidak ada scheduler runtime test baru |
| `app/Http/Controllers/LicenseStatusController.php` | 2 | Cari LocalInstallationIdentity dan LocalLicenseState | Identity where tenant_id dari authenticated user; state by identity ID | POSITIVE |
| `app/Http/Middleware/EnsureLicenseActive.php` | 4 | Recovery attempt untuk pre-activation/license grace | Validasi installation tenant, exam_id path, attempt tenant, status/expiry, participant user; collection path explicit tenant and user | PARTIAL/POSITIVE; negative HTTP mismatch belum dijalankan |
| `app/Models/User.php` | 1 | Cek uniqueness username saat creating | Query eksplisit tenant_id + username | POSITIVE |
| `app/Services/InstallerService.php` | 6 | Recovery installer, tenant/user identity, postconditions | Recovery mencari username/email tanpa tenant di awal, tetapi fail-closed jika tenant/user tidak cocok; verifies tenant_id, tenant code, username, email, password hash, role exact ADMIN_MADRASAH. Postcondition compares user.tenant_id with tenant.id. | PARTIAL; bootstrap pre-auth special path; no recovery conflict HTTP run |
| `app/Services/Licensing/InstallationIdentityService.php` | 3 | Menentukan satu tenant dan identity pada DB instalasi | Requires exactly one Tenant; requested tenant must exist; identity is unique installation record | POSITIVE; domain revalidation gap remains |
| `app/Services/LoginIdentityResolver.php` | 5 | Resolve username/NIP/NIK/NISN before tenant context | Requires exactly one installation identity and Tenant; User/TeacherProfile/StudentProfile queries filter tenant_id; candidate must be unique; roles validated | POSITIVE for tenant lookup; P1 role allowlist accepts ADMIN/OPERATOR/PROKTOR |

## 3. Runtime User query inventory

| File/method | Query scope | Result |
|---|---|---|
| `ExamParticipantController::store` | User query filters tenant_id and SISWA role | POSITIVE |
| `ReportController::exportStudentPdf` | User lookup filters student id + tenant_id | POSITIVE |
| `ExamManagement::saveExam` participant assignment | User query filters tenant_id and SISWA role | POSITIVE |
| `ProctorAssignmentManagement::assign/render` | User filters tenant_id, deleted_at, PROKTOR role | Tenant-local, but role/status model contradicts final Proktor decision |
| `UserManagement::render` | User filters tenant_id; eager loads roles/TeacherProfile/StudentProfile | Tenant-local; Livewire hydrated render lacks repeated user.manage check |
| `OperatorDashboard::render` | All User queries filter tenant_id | Tenant-local; portal/role legacy issue |
| `ExamProctorAssignmentService::assign` | User filters tenant_id and selected ID, then requires PROKTOR role | Tenant-local; wrong final role model |
| `LoginIdentityResolver::resolve` | Unscoped query with tenant_id filter | POSITIVE tenant filter; legacy role allowlist issue |
| `InstallerService` | Pre-auth unscoped username/email recovery | Fails closed on tenant, identity, password and exact ADMIN_MADRASAH role mismatch |
| `AuditLog::user`, `ExamParticipant::user`, `ExamAttemptAnswer::student`, score and achievement User relations | BelongsTo(User::class) primary key lookup | User has no global scope; DB composite FKs incomplete in several tables; explicit query constraints/DB hardening needed |

No runtime query returned cross-tenant rows during this audit because no DB was connected. No confirmed cross-tenant exploit is claimed.

## 4. Foreign-key integrity map

### Positive composite tenant constraints
- `teacher_profiles(user_id, tenant_id)` → `users(id, tenant_id)` via migration `2026_11_07_210002_harden_teacher_assignment_tenant_foreign_keys.php`.
- `teacher_subject_assignments` teacher, subject, rombel, academic_year, semester and creator tenant pairs are composite FKs.
- `exam_proctor_assignments` exam, proctor_user_id, assigned_by and revoked_by are composite tenant FKs.
- Fase1c multi-curriculum structure/version/outcome and registration triplet constraints are composite and covered by `Fase1cMultiCurriculumIntegrityTest`.
- `question_multi_curriculum_mappings(question_id, tenant_id)` is composite FK to Question and mapping owner/version/registration triplet is enforced by Fase1c constraints.

### Gaps
| Child relation | Current FK | Missing tenant pair | Impact |
|---|---|---|---|
| student_profiles.user_id | users.id | user_id + tenant_id | Profile can theoretically reference a user from another tenant if data inserted outside guarded application path |
| rombel_student.rombel_id/student_id | rombels.id, student_profiles.id | both parent IDs + tenant_id | Pivot has application boot/context checks and wherePivot filters, but DB does not reject every mismatched pair |
| exam_participants.user_id | users.id | user_id + tenant_id | Participant.user relation may resolve unscoped User if corrupt/mismatched row exists |
| exam_attempts.participant_id | exam_participants.id; exam_id+tenant_id composite | participant_id + tenant_id | Attempt-to-participant tenant pair not independently enforced by DB |
| exam_attempt_answers.student_id | users.id | student_id + tenant_id | Student relation is unscoped; application must keep student consistent with participant |
| exam_attempt_scores.student_id/finalized_by | users.id | each user FK + tenant_id | Score/student/finalizer identity can mismatch tenant at DB level |
| exam_question_scores.student_id/grader_id | users.id | each user FK + tenant_id | Question score student/grader can mismatch tenant at DB level |
| tp/cp/atp achievements student_id/finalized_by | users.id | each user FK + tenant_id | Achievement student/finalizer tenant not enforced by composite FK |
| tp/cp/atp achievements rombel_id/semester_id | single-column FKs | id + tenant_id (and semester/year relation) | Academic achievement may reference wrong tenant Rombel/Semester if inserted outside service |

Migration `2026_11_20_100002_harden_device_lease_tenant_constraints.php` performs mismatch preflight for device/lease rows and participant-device identity, but it does not add composite FK for `exam_participants.user_id + tenant_id`, answer/student or scoring user FKs. Do not interpret this migration as closing the gaps above.

## 5. Query and relationship controls

- `IdentifyTenant` resets context, ignores X-Tenant-Override and derives tenant from authenticated user. It currently does not abort when authenticated user tenant_id is null.
- Middleware priority is Authenticate → IdentifyTenant → SubstituteBindings.
- `TenantScoped` forces tenant_id on create and returns no rows (1=0) if context absent; it does not replace authorization.
- User is not TenantScoped. Every User direct query and relation must be checked independently.
- Report services apply explicit tenant filters on ExamAttempt, participant, User and StudentProfile; rombel/academic-year filters are applied for class reports.
- Analytics cache keys are tenant-prefixed. Four cached exam analytics routes authorize before Cache::remember; ScoringService/ScoringController/ResultList/ResultDetail invalidate relevant exam/rombel keys after score changes.
- Export surface is only `ExamResultsExport`, called by `ReportController::exportExcel` after ReportPolicy. PDF views are rendered only by ReportController after the same authorization. Admin is currently allowed by policy, which is a role-boundary defect, not a cache/export tenant leak.
- No app Jobs classes were found. Scheduled console commands `ExpireExamAttemptsCommand` and `CleanupOrphanedMediaCommand` use explicit tenant validation as described above.
- MultiCurriculum/MultiLearningOutcome/QuestionMultiCurriculumMapping have no TenantScoped trait but are not exposed through the current Admin API resource routes. Their Fase1c composite FK/model hooks are present; W6 remains read-only requirement context.

## 6. Required follow-up tests (NOT RUN)

- Negative HTTP tests for User with tenant_id NULL against all route-bound User and User relation endpoints.
- Corrupt fixture tests that attempt mismatched StudentProfile/User, Participant/User, Attempt/Participant, Answer/Student, Score/Student/Grader/Finalizer, Achievement/User/Rombel/Semester pairs; DB must reject on MySQL 8.
- Livewire hydrated request tests after permission/role revocation for every data-bearing component.
- Media orphan cleanup path traversal and tenant mismatch tests on disposable storage.
- Expire attempt worker two-tenant fixture with lock order, rollback and tenant context restoration.
- Existing T-001–T-173 mapping has no per-ID run evidence; do not mark PASS.

## 7. Status

This document is a static source/schema audit matrix. No MySQL connection, migration, production database, HTTP request or browser test was run. No source was changed. Database hardening remains a future separately approved patch/migration plan.