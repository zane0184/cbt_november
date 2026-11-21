# BACKUP MANIFEST — FINAL REVIEW IMPORT GURU/SISWA

Branch: work/import-guru-siswa-implementation-20261122
Base commit before review patch: aec1fc22994d5978a03e08fdf562cbb1f9fb2963
Date: 2026-11-22

Every backup entry below points to the exact original Git blob SHA captured before patching.

- backend-laravel/app/Livewire/Admin/ImportManagement.php -> docs/backup/import-review-final-2026-11-22/ImportManagement.php.before-review-final | original blob SHA: 65e7d3a00367d061444e96f1437a14c946410bfd
- backend-laravel/app/Services/Import/ImportWorkbookService.php -> docs/backup/import-review-final-2026-11-22/ImportWorkbookService.php.before-review-final | original blob SHA: 745ced552359556afc8d247e3e795023649c86c3
- backend-laravel/resources/views/livewire/admin/import-management.blade.php -> docs/backup/import-review-final-2026-11-22/import-management.blade.php.before-review-final | original blob SHA: 53de11a76f2feed9a147b5c0fea3a31b821ae466
- backend-laravel/tests/Feature/ImportWorkbookServiceTest.php -> docs/backup/import-review-final-2026-11-22/ImportWorkbookServiceTest.php.before-review-final | original blob SHA: 03646d2cc01324748b8d85baf6be5da69b1be25b
- backend-laravel/routes/console.php -> docs/backup/import-review-final-2026-11-22/console.php.before-review-final | original blob SHA: 45d2f386741bc983edf7ecb11ebb4d9ed106a5b6

## Follow-up backup before redirect/deadlock/retention test patch
Commit: 79f0542d9326a3b4725506232e6e9c8e0cd2f46b
- backend-laravel/app/Livewire/Admin/ImportManagement.php -> docs/backup/import-review-final-2026-11-22/ImportManagement.php.before-redirect-fix-and-retention-tests | blob SHA 8b3963d93b1158b8b4ada2567b6c1ec26c508e08
- backend-laravel/tests/Feature/ImportWorkbookServiceTest.php -> docs/backup/import-review-final-2026-11-22/ImportWorkbookServiceTest.php.before-deadlock-retention-tests | blob SHA 06b2899986afdc9c8b42a7eab792c2f760cd2747
- backend-laravel/routes/console.php -> docs/backup/import-review-final-2026-11-22/console.php.before-retention-audit-test | blob SHA 6f5d1010f1a20f9b2408ec9690f9faac23c27f16


## Follow-up backup before Livewire redirect correction
Commit: fb11e9c3847ce46261e4ca48123d178812001321
- backend-laravel/app/Livewire/Admin/ImportManagement.php -> docs/backup/import-review-final-2026-11-22/ImportManagement.php.before-final-void-return-fix | blob SHA a5881aad6b0d642d187a29c6aba7bb33f226e498


## Follow-up backup before tenant/ZIP/deadlock test patch
Commit: 736fe35b029bf8e2a1c090be044af19cc3e4c892
- backend-laravel/app/Services/Import/ImportWorkbookService.php -> docs/backup/import-review-final-2026-11-22/ImportWorkbookService.php.before-tenant-guard-zip-order | blob SHA bea9c7bce9c7216c4d9e69e827471aa675f9f914
- backend-laravel/tests/Feature/ImportWorkbookServiceTest.php -> docs/backup/import-review-final-2026-11-22/ImportWorkbookServiceTest.php.before-deadlock-test-isolation | blob SHA 255eda89fe1de8ff3797055e25ed71c8fe5f37dd


## Follow-up backup before PROCESSING resume UI patch
Commit: 557dc01f24d3df23e1a1b0ee353dad70f704651e
- backend-laravel/resources/views/livewire/admin/import-management.blade.php -> docs/backup/import-review-final-2026-11-22/import-management.blade.php.before-processing-resume-ui | blob SHA 53de11a76f2feed9a147b5c0fea3a31b821ae466
- backend-laravel/tests/Feature/ImportWorkbookServiceTest.php -> docs/backup/import-review-final-2026-11-22/ImportWorkbookServiceTest.php.before-processing-resume-ui | blob SHA ca7a89b5b89c2dcf10a69709d00c52a215e1994a


## Follow-up backup before isolated deadlock test registration
Commit: bf40163a004beb7d3e9f0432fa5a1ad7fa24b5f8
- backend-laravel/tests/Feature/ImportWorkbookServiceTest.php -> docs/backup/import-review-final-2026-11-22/ImportWorkbookServiceTest.php.before-isolated-deadlock-test | blob SHA ff2294464973c2e9750f4c5a7f10e72acfd0ada3
- .github/workflows/wa01-alternative-isolated-runtime.yml -> docs/backup/import-review-final-2026-11-22/wa01-alternative-isolated-runtime.yml.before-deadlock-test-registration | blob SHA 12bfb7e377bb6c431e981eca3af50edc2f63741a


## Follow-up backup before PROCESSING staging retention patch
Commit: 99befdc735bed48431895524ae63b1ffd3de5d71
- backend-laravel/routes/console.php -> docs/backup/import-review-final-2026-11-22/console.php.before-processing-retention-heartbeat | blob SHA 6f5d1010f1a20f9b2408ec9690f9faac23c27f16
- backend-laravel/app/Services/Import/ImportWorkbookService.php -> docs/backup/import-review-final-2026-11-22/ImportWorkbookService.php.before-processing-retention-heartbeat | blob SHA 5a9f9d79e3ada4af0fe1b95794c4ec5c975f7a55
- backend-laravel/tests/Feature/ImportWorkbookServiceTest.php -> docs/backup/import-review-final-2026-11-22/ImportWorkbookServiceTest.php.before-processing-retention-heartbeat | blob SHA eda811a581e74109806af77ce0fb61391881b161


## Follow-up backup before deadlock test transaction harness correction
Commit: f6f17ac966459461c908de496f92e560824887d3
- backend-laravel/tests/Feature/ImportDeadlockRetryTest.php -> docs/backup/import-review-final-2026-11-22/ImportDeadlockRetryTest.php.before-refresh-database-harness | blob SHA 396c1927a2610551fae46e9908f6bd468ae1bd15


## Follow-up backup before staging expiry audit assertion update
Commit: 1a3cf1211faa227f4e787564e58501d9da8d00b6
- backend-laravel/tests/Feature/ImportWorkbookServiceTest.php -> docs/backup/import-review-final-2026-11-22/ImportWorkbookServiceTest.php.before-staging-expiry-action-update | blob SHA 2445080aa2366776d2f4aaa3471562516db6495a


## Follow-up backup before XML DTD/entity rejection regression patch
Commit: 7ba6bc612e452d0ad6e7ddb8f7b61790b3e21c46
- backend-laravel/app/Services/Import/ImportWorkbookService.php -> docs/backup/import-review-final-2026-11-22/ImportWorkbookService.php.before-xml-entity-hardening | blob SHA 6afeea475258289a46d7851a062c725c061b3711
- backend-laravel/tests/Feature/ImportWorkbookServiceTest.php -> docs/backup/import-review-final-2026-11-22/ImportWorkbookServiceTest.php.before-second-dtd-test | blob SHA 34c20114e754c754f77e28429c96bc715d5a5da8


## Follow-up backup before XML DTD/ENTITY regression test
Commit: 95991b62ea9d157320ab8767aa5102a113d9cd7f
- backend-laravel/tests/Feature/ImportWorkbookServiceTest.php -> docs/backup/import-review-final-2026-11-22/ImportWorkbookServiceTest.php.before-xml-entity-hardening | blob SHA dd3b9b87344e88cd6002dcffb2cb36910506c595


## Backup before final review checkpoint update
Commit: a3522e1c8be02224ef9b369051105f05b2704e39
- docs/CHECKPOINT_IMPORT_GURU_SISWA_2026-11-22.md -> docs/backup/import-review-final-2026-11-22/CHECKPOINT_IMPORT_GURU_SISWA_2026-11-22.md.before-final-review-update | original blob SHA 9bafd8ab9dcfd25fb0d25701b33206f254256f3d
