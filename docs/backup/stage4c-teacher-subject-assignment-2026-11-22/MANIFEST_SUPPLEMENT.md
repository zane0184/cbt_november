# Stage 4C supplemental backup manifest

Repository: zane0184/cbt_november
Branch: work/stage4b-room-session-20261122
Implementation base HEAD: 3bf04829a07930ecc0e4e97de45501795248ad69

The original Stage 4C backup manifest remains unchanged. This supplement records subsequent pre-change snapshots.

| Original path | Backup path | Original Git blob SHA | UTF-8 bytes | Backup commit |
| backend-laravel/tests/Feature/TeacherSubjectAssignmentManagementTest.php | docs/backup/stage4c-teacher-subject-assignment-2026-11-22/TeacherSubjectAssignmentManagementTest.php.before-mysql-role-and-year-relation-fixes | bfd4b0950fa7293cde7e59cf7a36e5c6e9fe6bd9 | 9412 | 813c01fc9939d66876100b489bec762aee4e51ee |
|---|---|---|---:|---|
| backend-laravel/routes/web.php | docs/backup/stage4c-teacher-subject-assignment-2026-11-22/routes/web.php.before | 0a794fcaddaa5941226d1674d6fbcf1d38bfb647 | 7101 | 8fbb0a8bcd2d5751309c8e24552c8db9fbe295ca |
| backend-laravel/resources/views/layouts/app.blade.php | docs/backup/stage4c-teacher-subject-assignment-2026-11-22/resources/views/layouts/app.blade.php.before | 8cffb53d3592e6af33f80a5f30c3846eda300949 | 13884 | d1a731b84155029b82af58fded5b1cfbf3be3147 |
| .github/workflows/wa01-alternative-isolated-runtime.yml | docs/backup/stage4c-teacher-subject-assignment-2026-11-22/wa01-alternative-isolated-runtime.yml.before | f994e161c0bbecfa3297bc971609f70f2e675394 | 24223 | 71634333ae162642471836abbb701709c6cc7c69 |
| .github/workflows/wa01-alternative-isolated-runtime.yml | docs/backup/stage4c-teacher-subject-assignment-2026-11-22/wa01-alternative-isolated-runtime.yml.before-concurrency-test | bf3dec3a305689cb909c9e7aacc0a738f11873de | 24554 | 71e649b1246190fcb6244db5c3e740110f090700 |
| backend-laravel/tests/Feature/TeacherSubjectAssignmentManagementTest.php | docs/backup/stage4c-teacher-subject-assignment-2026-11-22/TeacherSubjectAssignmentManagementTest.php.before-tenant-and-conflict-cases | 5106cdaeb8dcd5eea97d875a34b6d5186fcd712e | 7444 | 299103acda849044880fc33a426833088c5b6404 |

New files `TeacherSubjectAssignmentManagement.php`, its Blade view, and `TeacherSubjectAssignmentConcurrencyTest.php` had no pre-existing source file to snapshot.

All backups are separate Git files. No original file or ZIP baseline was overwritten.



Note: This preserved test snapshot predates both the valid cross-year relationship fixture and canonical role reuse correction. Later changes remained test-only; production authorization and consistency guards were not relaxed.
