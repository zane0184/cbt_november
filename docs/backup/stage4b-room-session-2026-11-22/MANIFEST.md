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

## Additional snapshots — Admin direct API boundary and InnoDB preflight

Source HEAD before Admin endpoint tests: `e705342d4e98f462119eb5ccb927eb016be587fb`

| Original path | Snapshot path | Source Git blob SHA | UTF-8 bytes |
|---|---|---|---:|
| backend-laravel/tests/Feature/MonitoringDuplicationTest.php | docs/backup/stage4b-room-session-2026-11-22/MonitoringDuplicationTest.php.before-admin-direct-api-boundary | 52f10c0935f52c59f281c58ff7ba827fe3020b4e | 26798 |
| backend-laravel/tests/Feature/ExamRoomSessionConcurrencyTest.php | docs/backup/stage4b-room-session-2026-11-22/ExamRoomSessionConcurrencyTest.php.before-innodb-engine-count | bf48ad24af8fcddecfc36106b8c840e01e5f5ce5 | 9485 |

Both source files were snapshotted before the corresponding test-only changes. No production database or source baseline ZIP was modified.

## Additional snapshot — complete Proktor data-surface matrix

| Original path | Snapshot path | Source Git blob SHA | UTF-8 bytes |
|---|---|---|---:|
| backend-laravel/tests/Feature/MonitoringDuplicationTest.php | docs/backup/stage4b-room-session-2026-11-22/MonitoringDuplicationTest.php.before-complete-proctor-data-surface-matrix | 186f338d6861a38ec418539d8080820f55a49626 | 29962 |

This snapshot predates the expanded assertions for in-scope monitoring attempt DTOs, answer-payload exclusion, and the complete report/analytics endpoint denial matrix.

## Additional snapshots — final schedule ownership and concurrent Guru assignment tests

| Original path | Snapshot path | Source Git blob SHA | UTF-8 bytes |
|---|---|---|---:|
| backend-laravel/tests/Feature/ExamRoomSessionManagementTest.php | docs/backup/stage4b-room-session-2026-11-22/ExamRoomSessionManagementTest.php.before-teacher-owned-exam-window-assertions | 36c8b2f4514b2537c5157c978d32987b8ee2f4b5 | 24019 |
| backend-laravel/tests/Feature/ExamRoomSessionConcurrencyTest.php | docs/backup/stage4b-room-session-2026-11-22/ExamRoomSessionConcurrencyTest.php.before-guru-assignment-concurrency-test | 04a87feafdb6ed4d130a74a39364607c2adb3cc1 | 9643 |

These snapshots preserve the test sources before adding explicit assertions that session edits never overwrite the teacher-owned overall exam window and before adding the second-connection Guru assignment mutex test.

## Additional snapshot — cross-tenant fail-closed expectation correction

| Original path | Snapshot path | Source Git blob SHA | UTF-8 bytes |
|---|---|---|---:|
| backend-laravel/tests/Feature/MonitoringDuplicationTest.php | docs/backup/stage4b-room-session-2026-11-22/MonitoringDuplicationTest.php.before-cross-tenant-404-expectation-fix | e0a233a001b3100843bbd9fcf4b9121fe62539f7 | 31062 |

The test now accepts either 403 (authorization denial) or 404 (tenant-scoped route binding concealment) as a fail-closed result. It never accepts a successful response.

## Additional snapshots — monitoring lifecycle and exclusive Guru role guard

| Original path | Snapshot path | Source Git blob SHA | UTF-8 bytes |
|---|---|---|---:|
| backend-laravel/app/Policies/ExamPolicy.php | docs/backup/stage4b-room-session-2026-11-22/ExamPolicy.php.before-guru-single-role-monitoring-guard | 839df41c82a5b45706072d60431706ca5861cc6c | 9423 |
| backend-laravel/tests/Feature/MonitoringDuplicationTest.php | docs/backup/stage4b-room-session-2026-11-22/MonitoringDuplicationTest.php.before-nonconfirmed-and-role-double-tests | b1b0e2599d19ccbf6a50cd5e4cb28a6c5fe8acd9 | 31237 |

The policy now requires exactly one active GURU role for Guru author/temporary Proktor monitoring. New tests cover PENDING, NEEDS_REVIEW, REVOKED, EXPIRED, COMPLETED, legacy ACTIVE, revision mismatch, and GURU+SISWA mixed-role denial.

## Additional snapshot — Proktor exam index behavior

| Original path | Snapshot path | Source Git blob SHA | UTF-8 bytes |
|---|---|---|---:|
| backend-laravel/tests/Feature/MonitoringDuplicationTest.php | docs/backup/stage4b-room-session-2026-11-22/MonitoringDuplicationTest.php.before-proctor-empty-exam-index-assertion | 7944ae1543d024a5520580946f1ff8810aa1857f | 33644 |

The Guru exam index is a teacher-scope listing: a Proktor without independent TeacherAccess must receive an empty result, while direct exam details/questions/preview remain denied.

## Additional snapshots — final security and schedule-ownership refinements

| Original path | Snapshot path | Source Git blob SHA | UTF-8 bytes |
|---|---|---|---:|
| backend-laravel/tests/Feature/MonitoringDuplicationTest.php | docs/backup/stage4b-room-session-2026-11-22/MonitoringDuplicationTest.php.before-exam-and-question-api-denial-matrix | ff911ededb86ec39cc3f8a0d554d0eb501240b73 | 33268 |
| backend-laravel/tests/Feature/MonitoringDuplicationTest.php | docs/backup/stage4b-room-session-2026-11-22/MonitoringDuplicationTest.php.before-proctor-empty-exam-index-assertion | 7944ae1543d024a5520580946f1ff8810aa1857f | 33644 |
| docs/DESAIN_STAGE4B_RUANG_SESI_PROKTOR_2026-11-22.md | docs/backup/stage4b-room-session-2026-11-22/DESAIN_STAGE4B_RUANG_SESI_PROKTOR_2026-11-22.md.before-teacher-owned-exam-window-clarification | 920dbf3fc2fa1f32436694c23d36412c1242e28a | 9595 |

The final source refinement enforces a single active GURU role for Guru monitoring and expands denial tests to exam/question/preview routes. The Stage 4B design now explicitly preserves `Exam.start_at/end_at` as Guru-owned and confines Admin schedule edits to the session window.

## Additional snapshot — Admin participant-data boundary clarification

| Original path | Snapshot path | Source Git blob SHA | UTF-8 bytes |
|---|---|---|---:|
| docs/DESAIN_STAGE4B_RUANG_SESI_PROKTOR_2026-11-22.md | docs/backup/stage4b-room-session-2026-11-22/DESAIN_STAGE4B_RUANG_SESI_PROKTOR_2026-11-22.md.before-admin-participant-scope-clarification | 61acfc92a901a0f52e85cb8fe21455baa24b3c89 | 10078 |

The design now makes explicit that Admin maps rombel to room-session only. Admin does not receive individual participant lists or participant-level placement controls; any future split-room workflow must be a separately authorized Guru-owner capability.

## Additional snapshots — Admin Livewire room UI tests

| Original path | Snapshot path | Source Git blob SHA | UTF-8 bytes |
|---|---|---|---:|
| backend-laravel/tests/Feature/ExamRoomSessionManagementTest.php | docs/backup/stage4b-room-session-2026-11-22/ExamRoomSessionManagementTest.php.before-room-livewire-test | 5b82975b1f4db193f17c371e39d6900a2702fcfc | 24755 |
| backend-laravel/tests/Feature/ProctorAssignmentSecurityTest.php | docs/backup/stage4b-room-session-2026-11-22/ProctorAssignmentSecurityTest.php.before-proctor-livewire-test | 38d016f570e1449e020159d93371befd7d1dd495 | 13957 |

The Admin room UI test exercises the Livewire edit/save/deactivation path. The Proktor assignment UI already had Livewire assignment/confirmation/revocation tests before this checkpoint.

## Additional snapshot — monitoring attempt DTO assertion correction

| Original path | Snapshot path | Source Git blob SHA | UTF-8 bytes |
|---|---|---|---:|
| backend-laravel/tests/Feature/MonitoringDuplicationTest.php | docs/backup/stage4b-room-session-2026-11-22/MonitoringDuplicationTest.php.before-monitoring-attempt-dto-path-fix | 00ea1d597aeac638018b981a1748482611ff28fa | 33697 |

The monitoring endpoint returns the operational attempt under `data.attempt`; the test now asserts that exact response shape and verifies answer payloads are absent from that nested DTO.

## Additional snapshots — remove participant operational data from Admin room UI

| Original path | Snapshot path | Source Git blob SHA | UTF-8 bytes |
|---|---|---|---:|
| backend-laravel/app/Livewire/Admin/ExamRoomSessionManagement.php | docs/backup/stage4b-room-session-2026-11-22/ExamRoomSessionManagement.php.before-remove-participant-operational-data-from-admin | 93959c9376cb938c5d0391207d6f16d3aaeace88 | 13155 |
| backend-laravel/resources/views/livewire/admin/exam-room-session-management.blade.php | docs/backup/stage4b-room-session-2026-11-22/exam-room-session-management.blade.php.before-remove-participant-operational-data-from-admin | 59f725909b6c62d8b282317cdf1827162eff6d36 | 19984 |
| backend-laravel/tests/Feature/ExamRoomSessionManagementTest.php | docs/backup/stage4b-room-session-2026-11-22/ExamRoomSessionManagementTest.php.before-admin-participant-data-assertion | c3d9732eeb7b25a2e994b4cfa48e81c5d3e9fc7f | 25663 |

The Admin Livewire component no longer eager-loads participant room assignments, and the view no longer exposes an operational participant count. Capacity remains a room metadata field. A regression assertion prevents participant names/counts from appearing in this Admin UI.
