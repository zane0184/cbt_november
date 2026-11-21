# Stage 4B pre-change backup manifest

Repository: zane0184/cbt_november
Branch: work/stage4b-room-session-20261122
Source HEAD before patch: 32bb76634d3390f8aeefade339fbb044394c783c

The immutable source commit above is the byte-exact pre-change backup for each tracked file below. The Git blob SHA identifies the exact content retained in that commit. No original file has been overwritten.

| Original path | Git blob SHA | UTF-8 bytes |
|---|---|---:|
| backend-laravel/app/Models/Exam.php | d3094eada3b2f54758af89e82987f09b4672f8d9 | 3009 |
| backend-laravel/app/Models/ExamParticipant.php | ff9cbfbaab481ff54ba4f1b5ae21a11e73737bec | 1016 |
| backend-laravel/app/Models/ExamProctorAssignment.php | 348116dfc447dea3eebc7e9271f442b3f159cca6 | 1348 |
| backend-laravel/app/Services/ExamProctorAssignmentService.php | 1030018d797fac32ae2fe8bb067007ce1fb02049 | 11646 |
| backend-laravel/app/Policies/ExamPolicy.php | 839df41c82a5b45706072d60431706ca5861cc6c | 9423 |
| backend-laravel/app/Http/Controllers/Api/V1/MonitoringController.php | 2c920be8b86e5684643e2600c24782bf084293c2 | 13826 |
| backend-laravel/app/Http/Controllers/Api/V1/ExamController.php | bf373858869b470b235e2256e5be6169fbb1f561 | 25557 |
| backend-laravel/app/Livewire/Admin/ProctorAssignmentManagement.php | dc1852f40b55bed17078ea917307483b28b9fd04 | 5614 |
| backend-laravel/resources/views/livewire/admin/proctor-assignment-management.blade.php | f11af0eb8dccea8cc2e1fd4aad5f6038497a93e4 | 8167 |
| backend-laravel/routes/console.php | 581751b5ed8cdbdeeecf93ad666ec6605ffd54d2 | 4074 |
| backend-laravel/routes/web.php | bcac1b6935b9eec1a8ec621ed79c39d3864ffcac | 6936 |
| backend-laravel/resources/views/layouts/app.blade.php | 1725fecdd857f18ff03e1c87e4208ecdd730812b | 13150 |
| .github/workflows/wa01-alternative-isolated-runtime.yml | 735b9a13ba1fe402af98bc8d6c12a744221b23b2 | 23283 |
| backend-laravel/tests/Feature/ProctorAssignmentSecurityTest.php | bfcd7517e099ba5011af08a57b1467a1d7906031 | 12500 |
| backend-laravel/tests/Feature/MonitoringDuplicationTest.php | 93e315fd93d34aa515b4a951805bcf827befcde9 | 15520 |

Catatan: blob SHA adalah Git object SHA-1 (bukan SHA-256). Source HEAD tetap dapat dipulihkan persis dari commit tersebut.

## Additional snapshots — before Proktor endpoint-scope correction

Source HEAD: `58c47809bd977a723c7811e087f60dc1cc824dde`

| Original path | Snapshot path | Source Git blob SHA | UTF-8 bytes |
|---|---|---|---:|
| backend-laravel/app/Http/Controllers/Api/V1/ExamParticipantController.php | docs/backup/stage4b-room-session-2026-11-22/ExamParticipantController.php.before-proctor-monitoring-endpoint-scope | f7014ab069cc2c9548f9b238e0e7a4f1986cb421 | 5716 |
| backend-laravel/app/Http/Controllers/Api/V1/ExamAttemptController.php | docs/backup/stage4b-room-session-2026-11-22/ExamAttemptController.php.before-proctor-monitoring-endpoint-scope | e915bf43118026ef8eace96338fa6cad44ee29e1 | 21232 |
| backend-laravel/tests/Feature/MonitoringDuplicationTest.php | docs/backup/stage4b-room-session-2026-11-22/MonitoringDuplicationTest.php.before-proctor-monitoring-endpoint-scope | 10d967406b5ed4c2bebd183d10ec999c9c3a9b77 | 19765 |

The three snapshots were created as separate files before changing their source counterparts. Earlier Stage 4B backups remain unchanged.

## Additional snapshots — before duplicate-placement and MySQL concurrency coverage

Source HEAD before test/runner changes: `58c47809bd977a723c7811e087f60dc1cc824dde`

| Original path | Snapshot path | Source Git blob SHA | UTF-8 bytes |
|---|---|---|---:|
| backend-laravel/tests/Feature/ExamRoomSessionManagementTest.php | docs/backup/stage4b-room-session-2026-11-22/ExamRoomSessionManagementTest.php.before-duplicate-placement-and-tenant-tests | 5a11e1afb7adee1867e756c4dba16de695f5b33b | 20403 |
| .github/workflows/wa01-alternative-isolated-runtime.yml | docs/backup/stage4b-room-session-2026-11-22/wa01-alternative-isolated-runtime.yml.before-mysql-concurrency-test | 87989f8ad5c87362c4cf36b10e7bd92f7bbc2eb6 | 23812 |

New file: `backend-laravel/tests/Feature/ExamRoomSessionConcurrencyTest.php` — added as a new test file; no prior source existed to back up.

The earlier endpoint-scope snapshots and original Stage 4B baseline snapshots remain unchanged.

## Additional snapshot — MySQL concurrency test import correction

| Original path | Snapshot path | Source Git blob SHA | UTF-8 bytes |
|---|---|---|---:|
| backend-laravel/tests/Feature/ExamRoomSessionConcurrencyTest.php | docs/backup/stage4b-room-session-2026-11-22/ExamRoomSessionConcurrencyTest.php.before-laravel-test-trait-import-fix | e7ff210a9bfaaf8881c3ff4f057504dfe640575c | 8590 |

The source file was newly created for Stage 4B concurrency coverage; this snapshot preserves its exact pre-correction state. The current file imports `Illuminate\\Foundation\\Testing\\DatabaseMigrations`.
