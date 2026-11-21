# MANIFEST BACKUP — WA-01 Paket 0

- Repository: `zane0184/cbt_november`
- Branch implementasi: `work/wa01-package0-implementation-20261121`
- Baseline source: `2b4321ce032fd5e67cf022da8564a5287b8cef6f`
- Root backup: `docs/backup/wa-01-package0-source-2026-11-21/`
- Metode: file asli disalin tanpa perubahan; struktur relatif backend dipertahankan.
- Verifikasi: 23 file sumber/test yang berubah telah dibandingkan Git blob SHA dengan baseline dan cocok byte-identik. File test baru `AdminCbtBoundaryTest.php` tidak memerlukan backup karena tidak menggantikan file lama.
- Database produksi, konfigurasi VPS, dan deployment tidak disentuh.

## Daftar backup (36 file)

```text
backend-laravel/app/Services/RolePermissionBootstrapper.php
backend-laravel/app/Policies/QuestionPolicy.php
backend-laravel/app/Policies/AssessmentPolicy.php
backend-laravel/app/Policies/ExamPolicy.php
backend-laravel/app/Policies/ExamAttemptPolicy.php
backend-laravel/app/Policies/ReportPolicy.php
backend-laravel/app/Http/Controllers/Api/V1/QuestionController.php
backend-laravel/app/Http/Controllers/Api/V1/QuestionPublicationController.php
backend-laravel/app/Http/Controllers/Api/V1/MediaController.php
backend-laravel/app/Http/Controllers/Api/V1/AssessmentController.php
backend-laravel/app/Http/Controllers/Api/V1/ExamController.php
backend-laravel/app/Http/Controllers/Api/V1/ExamParticipantController.php
backend-laravel/app/Http/Controllers/Api/V1/ExamAttemptController.php
backend-laravel/app/Http/Controllers/Api/V1/ExamAnswerController.php
backend-laravel/app/Http/Controllers/Api/V1/MonitoringController.php
backend-laravel/app/Http/Controllers/Api/V1/ScoringController.php
backend-laravel/app/Http/Controllers/Api/V1/ReportController.php
backend-laravel/app/Http/Controllers/Api/V1/AnalyticsController.php
backend-laravel/app/Http/Controllers/StudentExamController.php
backend-laravel/app/Livewire/Admin/QuestionBank.php
backend-laravel/app/Livewire/Admin/ExamManagement.php
backend-laravel/app/Livewire/Admin/ProctorAssignmentManagement.php
backend-laravel/app/Livewire/Operator/OperatorDashboard.php
backend-laravel/resources/views/livewire/admin/question-bank.blade.php
backend-laravel/resources/views/livewire/admin/exam-management.blade.php
backend-laravel/resources/views/livewire/admin/proctor-assignment-management.blade.php
backend-laravel/resources/views/livewire/operator/operator-dashboard.blade.php
backend-laravel/resources/views/layouts/app.blade.php
backend-laravel/routes/web.php
backend-laravel/routes/api.php
backend-laravel/tests/Feature/AdminUiVerificationTest.php
backend-laravel/tests/Feature/RoleDashboardVerificationTest.php
backend-laravel/tests/Feature/RolePermissionBootstrapperTest.php
backend-laravel/tests/Feature/ScoringHardeningTest.php
backend-laravel/tests/Feature/Security/AnswerKeyLeakageTest.php
backend-laravel/tests/Feature/MediaAuthorizationTest.php
```

## Catatan batasan

- Backup ini berada di branch implementasi, bukan pengganti ZIP baseline.
- Tidak ada file baseline yang dihapus atau ditimpa.
- Jangan menjalankan `RolePermissionBootstrapper` pada database produksi tanpa persetujuan database terpisah.
