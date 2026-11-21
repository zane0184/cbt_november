# SUPPLEMENTAL BACKUP MANIFEST — ADMIN CBT API AUTHORIZATION ORDER

| Source before patch | Backup path | Original blob SHA |
|---|---|---|
| backend-laravel/app/Http/Controllers/Api/V1/ExamAnswerController.php | docs/backup/stage4a-admin-cbt-api-boundary-2026-11-22/ExamAnswerController.php.before-auth-order-hardening | f4ac36966bbe884a5e2136c02d0d132649a50a9b |
| backend-laravel/app/Http/Controllers/Api/V1/MonitoringController.php | docs/backup/stage4a-admin-cbt-api-boundary-2026-11-22/MonitoringController.php.before-auth-order-hardening | 335a32029f4d5ab4788ea31fd81088dfdb5acde2 |
| backend-laravel/tests/Feature/MonitoringDuplicationTest.php | docs/backup/stage4a-admin-cbt-api-boundary-2026-11-22/MonitoringDuplicationTest.php.before-auth-order-hardening | 6e3f9c24d56eb7345cd8caaea0b04f589e507f33 |

The two controller changes move single-role SISWA authorization and tenant/attempt ownership checks ahead of the device-binding header requirement. This ensures Admin/Guru/other roles are denied with 403 before student-only device input validation. The student flows still require X-Device-Binding-ID after authorization.
