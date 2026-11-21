# BACKUP MANIFEST — STAGE 4A TEMPORARY PROCTOR GURU ELIGIBILITY

Repository: zane0184/cbt_november
Branch: work/import-guru-siswa-implementation-20261122
Backup created before Stage 4A Guru-eligibility changes on 22 November 2026.

| Source | Backup path | Original blob SHA |
|---|---|---|
| backend-laravel/app/Livewire/Admin/ProctorAssignmentManagement.php | docs/backup/stage4a-proctor-eligibility-2026-11-22/ProctorAssignmentManagement.php.before-guru-eligibility | 63492d5cdde9e5cdce562f1fa8c88c9d14fc2955 |
| backend-laravel/app/Services/ExamProctorAssignmentService.php | docs/backup/stage4a-proctor-eligibility-2026-11-22/ExamProctorAssignmentService.php.before-guru-eligibility | e16ef5db308714aa98bf283dc5a1545608da0477 |
| backend-laravel/resources/views/livewire/admin/proctor-assignment-management.blade.php | docs/backup/stage4a-proctor-eligibility-2026-11-22/proctor-assignment-management.blade.php.before-guru-eligibility | a1561fe093761e658b4a309ea8269d3205e0f833 |
| backend-laravel/tests/Feature/ProctorAssignmentSecurityTest.php | docs/backup/stage4a-proctor-eligibility-2026-11-22/ProctorAssignmentSecurityTest.php.before-guru-eligibility | a5eca4ea2ce05d62d4fa76e6b9cd5a21a17dd668 |
| backend-laravel/tests/Feature/AdminCbtBoundaryTest.php | docs/backup/stage4a-proctor-eligibility-2026-11-22/AdminCbtBoundaryTest.php.before-guru-eligibility | fc8e99c971485a9021bf7c6d7ca765c6426b89ac |
| .github/workflows/wa01-alternative-isolated-runtime.yml | docs/backup/stage4a-proctor-eligibility-2026-11-22/wa01-alternative-isolated-runtime.yml.before-guru-eligibility | cbd4e76c40699b8ba328a85102fb654c5ccd2e60 |

Eligibility contract implemented in this patch:
- candidate must belong to the active tenant;
- account must not be soft-deleted;
- account must have exactly one role, GURU;
- active TeacherProfile must exist in the same tenant (soft-deleted profile is excluded);
- UI filtering and service-side enforcement both apply the same rule.

No production database, migration, main branch, PR, merge, or VPS deployment was changed.
