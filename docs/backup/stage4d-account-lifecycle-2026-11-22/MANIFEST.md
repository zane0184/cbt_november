# Backup manifest — Stage 4D parallel work

Tanggal: 22 November 2026
Repository: zane0184/cbt_november
Branch: work/stage4d-account-lifecycle-20261122
Base source HEAD: 0631a3d84d058c8c049dcb71dc54b2e7cbb849a6

| Source path | Backup path | Original blob SHA | UTF-8 bytes | Backup commit |
|---|---|---|---:|---|
| backend-laravel/tests/Feature/TeacherSubjectAssignmentManagementTest.php | docs/backup/stage4c-teacher-subject-assignment-2026-11-22/TeacherSubjectAssignmentManagementTest.php.before-ci-fixture-fix | bfd4b0950fa7293cde7e59cf7a36e5c6e9fe6bd9 | 9412 | cfb7856abffb353539ec3685c79c08a4f4475a5c |
| .github/workflows/wa01-alternative-isolated-runtime.yml | docs/backup/stage4d-account-lifecycle-2026-11-22/wa01-alternative-isolated-runtime.yml.before-branch-enable | 24b9b01d1c4ff63f13bb2d64592cecf03fe68cb5 | 24874 | 97b41b89f96e996a1419abc2ad44777a18540340 |

No application migration, production database, VPS, Android source, or baseline ZIP was changed.


## Snapshot tambahan — koreksi fixture setelah CI 44981468061

| Source path | Backup path | Original blob SHA | UTF-8 bytes | Backup commit |
|---|---|---|---:|---|
| backend-laravel/tests/Feature/TeacherSubjectAssignmentManagementTest.php | docs/backup/stage4d-account-lifecycle-2026-11-22/TeacherSubjectAssignmentManagementTest.php.before-nonadmin-fixture-fix | b402b25a88518d426da37d13b54708492b8b7e45 | 9897 | 9c18882e4202c6a43fcf660614b1436c46167f20 |

Perubahan setelah snapshot hanya memperbaiki fixture test non-Admin agar memakai role GURU yang sudah dibuat setUp(), bukan membuat role duplikat. Source runtime tidak diubah.
