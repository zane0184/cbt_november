# AUDIT SOURCE STAGE 4E–4G — CBT MADRASAH

Tanggal: 22 November 2026
Branch audit: work/stage4d-account-lifecycle-20261122
Source aplikasi yang diperiksa: `0631a3d84d058c8c049dcb71dc54b2e7cbb849a6` (baseline Stage 4D) dan source aplikasi identik pada branch Agent 1 `42a3ed3a2d5d908cbcfc30340ef06de5b62b5887`.
HEAD branch dokumentasi saat audit: `71ac5363706a28a83c10584f0e881653fcae569c`.

## Catatan penomoran tahap

Dokumentasi repository belum konsisten dalam penomoran:
- `AUDIT_WEB_ADMIN_REMAINING_2026-11-22.md` mengelompokkan Stage 4E sebagai kurikulum, Stage 4F sebagai operasional Admin, dan Stage 4G sebagai integrasi/hardening.
- `IMPLEMENTASI_STAGE4B_ROOM_SESSION_2026-11-22.md` memakai urutan berbeda: Stage 4E Siswa/Rombel, Stage 4F kurikulum, Stage 4G dashboard/administrasi.

Karena itu audit ini menginventarisasi domain nyata dan tidak menganggap penomoran final sudah diputuskan. Tidak ada fitur duplikat yang dibuat.

## 1. Domain Siswa dan Rombel

### Route dan UI
- `backend-laravel/routes/web.php`: `GET /admin/rombel-students`.
- `backend-laravel/app/Livewire/Admin/StudentRombelManagement.php`.
- `backend-laravel/resources/views/livewire/admin/student-rombel-management.blade.php`.

### Model
- `StudentProfile.php`
- `Rombel.php`
- `RombelStudent.php`
- `AcademicYear.php`
- `GradeLevel.php`

### Migration
- `2024_01_01_000002_create_academic_tables.php`
- `2026_11_09_230003_harden_rombel_student_tenant_integrity.php`
- `2026_11_09_230004_harden_rombel_teacher_tenant_integrity.php`
- `2026_11_22_000001_enforce_single_active_academic_periods.php`

### Policy dan permission
- Tidak ditemukan `StudentRombelPolicy`.
- Komponen melakukan pemeriksaan role Admin Madrasah dan permission `academic.manage`.
- Permission didefinisikan melalui `RolePermissionBootstrapper.php`.

### Test
- `RombelStudentPivotTest.php`: relasi pivot dan integritas tenant.
- Belum ditemukan test khusus lifecycle pindah rombel, naik kelas, tinggal kelas, kelulusan, atau perubahan status akademik.

### Temuan
Pivot `rombel_student` memiliki `tenant_id` dan integritas tenant, tetapi belum memiliki status keanggotaan atau tanggal efektif. Penambahan Siswa ke rombel belum menutup keanggotaan sebelumnya. Tidak ditemukan model/tabel StudentAcademicHistory atau workflow append-only untuk event akademik.

## 2. Domain Kurikulum dan Pembelajaran

### Route dan UI
- `routes/web.php`: `GET /admin/curriculum`.
- `routes/api.php`: resource `curricula`, `phases`, `grade-levels`, `elements`, `learning-outcomes`, `learning-objectives`, `learning-paths`, dan `materials` di dalam middleware API authenticated/tenant/license.
- `app/Livewire/Admin/CurriculumManagement.php`.
- `resources/views/livewire/admin/curriculum-management.blade.php`.

### Controller API
- `CurriculumController.php`
- `PhaseController.php`
- `GradeLevelController.php`
- `ElementController.php`
- `LearningOutcomeController.php`
- `LearningObjectiveController.php`
- `LearningPathController.php`
- `MaterialController.php`
- `CurriculumAnalyticsController.php`

### Model
- `Curriculum.php`
- `Phase.php`
- `Element.php`
- `GradeLevel.php`
- `LearningOutcome.php`
- `LearningOutcomeVersion.php`
- `LearningObjective.php`
- `LearningPath.php`
- `LearningPathVersion.php`
- `Material.php`
- `MultiCurriculum.php`
- `MultiCurriculumVersion.php`
- `MultiCurriculumStructure.php`
- `MultiLearningOutcome.php`
- `QuestionMultiCurriculumMapping.php`
- `TenantCurriculumRegistration.php`

### Migration
- `2024_01_01_000005_create_curriculum_details_tables.php`
- `2026_09_13_110002_create_assessment_learning_objectives_table.php`
- `2026_11_05_190004_create_multi_curriculums_table.php`
- `2026_11_05_190005_create_multi_curriculum_versions_table.php`
- `2026_11_05_190006_create_multi_curriculum_structures_table.php`
- `2026_11_05_190007_create_multi_learning_outcomes_table.php`
- `2026_11_05_190008_create_question_multi_curriculum_mappings_table.php`
- `2026_11_05_200001_fase1c_multi_curriculum_integrity.php`

### Policy dan permission
Policy yang ditemukan:
- `CurriculumPolicy.php`
- `PhasePolicy.php`
- `LearningOutcomePolicy.php`
- `MaterialPolicy.php`
- `AssessmentPolicy.php`

Permission yang disediakan `RolePermissionBootstrapper.php` antara lain `curriculum.view` dan `curriculum.manage`. Policy LearningOutcome menggunakan prefix `learning_outcome`, tetapi permission `learning_outcome.manage` tidak terlihat dibuat/diberikan oleh bootstrapper tersebut.

### Temuan keamanan API
- `CurriculumController` memanggil policy untuk index/store/show/update/destroy.
- `PhaseController` memanggil policy.
- `GradeLevelController`, `ElementController`, dan `LearningObjectiveController` tidak memiliki pemeriksaan authorize/can/role/permission pada action yang diperiksa.
- `LearningPathController` tidak memiliki policy class dan tidak memiliki pemeriksaan authorize/can/role/permission pada action yang diperiksa.
- `MaterialController` memiliki `MaterialPolicy`, tetapi controller yang diperiksa tidak memanggil authorize/can.
- `LearningOutcomeController` tidak memanggil policy. Store memakai `StoreLearningOutcomeRequest` yang memeriksa ability `learning_outcome.manage`; ability tersebut tidak ditemukan di RolePermissionBootstrapper atau AppServiceProvider yang diperiksa. Index/show/update/destroy tidak melakukan guard eksplisit.
- Middleware route API yang terlihat adalah authentication, tenant, dan license; middleware tersebut tidak menggantikan pemeriksaan permission pada controller.
- `CurriculumManagement` mengizinkan role `ADMIN_MADRASAH` dan role legacy `ADMIN` untuk perubahan TP. Role legacy perlu direkonsiliasi dengan arsitektur tiga portal.
- `CurriculumAnalyticsController` memberi akses analitik rombel/mapel/TP kepada Admin pada beberapa action, sementara `ReportPolicy` menolak Admin dari laporan hasil CBT. Batas antara analitik akademik dan data hasil CBT perlu diselaraskan.

### Test
- `CurriculumVersioningTest.php`
- `CurriculumIntegrityTest.php`
- `Fase1cMultiCurriculumIntegrityTest.php`
- `Phase7DCurriculumAchievementTest.php`
- `Phase7DReportingTest.php`

Test integritas/versioning tersedia, tetapi belum ditemukan matriks test authorization yang mencakup semua API controller kurikulum di atas. Test versioning memakai Gate mock untuk `learning_outcome.manage`, sehingga tidak membuktikan permission production benar-benar terdaftar.

### Temuan cakupan kurikulum
Fondasi Multi Curriculum, CP, TP, ATP/Learning Path, versioning, dan mapping soal telah tersedia. UI Admin yang ditemukan masih berpusat pada CP dan pembuatan TP. Pencarian source aktif tidak menemukan implementasi eksplisit bernama Panca Cinta atau Pembelajaran Mendalam; fondasi generik tidak boleh dianggap sebagai bukti integrasi keduanya.

## 3. Domain Operasional Admin dan integrasi

### Route Web yang ditemukan
- `/admin/dashboard`
- `/admin/profile`
- `/admin/import` dan template import
- `/admin/users`
- `/admin/rombel-students`
- `/admin/academic`
- `/admin/teacher-subject-assignments`
- `/admin/curriculum`
- `/admin/questions`
- `/admin/exams`
- `/admin/proctor-assignments`
- `/admin/exam-rooms-sessions`
- `/operator/dashboard` hanya redirect kompatibilitas ke Admin Dashboard.

Tidak ditemukan route Admin khusus untuk Android sync monitoring, backup/restore, helpdesk/tiket, atau system update center.

### Livewire / service
- `AdminDashboard.php`
- `TenantProfileManagement.php`
- `ImportManagement.php`
- `UserManagement.php`
- `StudentRombelManagement.php`
- `AcademicManagement.php`
- `TeacherSubjectAssignmentManagement.php`
- `CurriculumManagement.php`
- `ProctorAssignmentManagement.php`
- `ExamRoomSessionManagement.php`

### Scheduler
`routes/console.php` menjadwalkan:
- `cbt:expire-attempts --limit=200` setiap menit.
- `exams:sync-room-session-lifecycle` setiap menit.
- `cbt:license:refresh` setiap enam jam.
- `imports:expire-staging --days=7` harian.

`SyncExamSessionLifecycle.php` hanya menangani lifecycle sesi ruang ujian dan penugasan Proktor sementara. Ini bukan sinkronisasi offline Android.

### Reporting dan permission
- `ReportController.php`, service laporan, PDF/Excel export tersedia.
- `ReportPolicy.php` membatasi laporan CBT dan menolak akses Admin Madrasah pada hasil ujian.
- `AdminDashboard.php` menampilkan ringkasan akademik, aktivitas audit, dan health check koneksi database; belum ditemukan dashboard sync Android/backup.
- Proktor dikelola melalui assignment sementara, bukan portal Proktor permanen.
- RolePermissionBootstrapper memberikan `user.manage`, `academic.manage`, `curriculum.view/manage`, dan `exam.proctor.assign` pada lingkup role yang diatur.

### Test
- `AdminDashboardAccessTest.php`
- `AdminUserManagementScopeTest.php`
- `AcademicPeriodManagementTest.php`
- `TenantProfileManagementTest.php`
- `ImportTemplateGeneratorTest.php`
- `ImportWorkbookServiceTest.php`
- `ImportDeadlockRetryTest.php`
- `ProctorAssignmentSecurityTest.php`
- `ExamRoomSessionManagementTest.php`
- `StudentResultVisibilityTest.php`

Belum ditemukan test untuk Android sync monitoring Admin, backup/restore, helpdesk, atau update center karena modulnya belum ditemukan.

## 4. Dampak SoftDeletes User terhadap histori

Source yang diperiksa:
- `User.php`
- `TeacherProfile.php`
- `StudentProfile.php`
- `ExamParticipant.php`
- `ExamAttemptScore.php`
- `ExamQuestionScore.php`
- `ExamProctorAssignment.php`
- `ExamParticipantRoomAssignment.php`
- `TeacherSubjectAssignment.php`
- `ImportBatch.php`
- `AuditLog.php`
- `AuthController.php`
- `LoginIdentityResolver.php`

Temuan:
1. Kolom `users.deleted_at` sudah ada, tetapi model `User` belum menggunakan trait `SoftDeletes`.
2. `LoginIdentityResolver` secara eksplisit mengecualikan `users.deleted_at`; API dan Web login menggunakannya.
3. User memakai Sanctum. Saat ini belum ditemukan workflow Admin untuk mencabut semua personal access token ketika akun dinonaktifkan.
4. Relasi `belongsTo(User::class)` pada profil, peserta ujian, nilai, assignment Proktor, assignment ruang, penugasan Guru, import batch, dan AuditLog belum memakai `withTrashed`. Jika SoftDeletes ditambahkan, relasi default tersebut tidak lagi mengembalikan akun nonaktif; relasi historis perlu dipisahkan dari relasi operasional aktif.
5. `users` memiliki unique tenant+username dan tenant+email. Karena soft delete mempertahankan row, constraint ini tetap memesan identitas dan tidak membebaskan username/email.
6. NIP/NIK/NISN di tabel profil memiliki index pencarian, tetapi source migration yang diperiksa belum menunjukkan unique constraint tenant-scoped untuk ketiga login identifier tersebut. Validasi aplikasi saja tidak cukup terhadap race condition.
7. Tabel bawaan `password_reset_tokens` memakai email sebagai primary key global, sehingga tidak cocok untuk email yang boleh sama antar-tenant dan akun tanpa email.

## 5. Rekomendasi desain, belum diimplementasikan

### Lifecycle akun
- Gunakan `SoftDeletes` pada User; jangan hard delete.
- Jangan soft delete TeacherProfile/StudentProfile saat akun dinonaktifkan. Profil dan hubungan akademik tetap dipertahankan.
- Tambahkan relasi khusus histori `withTrashed` untuk audit, nilai, peserta ujian, assignment, dan import; relasi operasional aktif tetap mengecualikan akun nonaktif.
- Saat deactivation: lock tenant dan user, set deleted_at, rotate remember_token, revoke seluruh Sanctum tokens, catat audit. Saat restore, validasi ulang status/role dan pertahankan profil yang sama.
- Pertahankan unique tenant+username/email termasuk row soft-deleted.
- Tambahkan unique tenant+NIP/NIK/NISN hanya setelah preflight duplicate; jangan otomatis menghapus atau mengubah data lama.
- Buat token reset custom tenant-scoped (bukan broker standar): hash token, expiry, used_at/revoked_at, tenant_id, user_id, issued_by, dan audit tanpa token/password plaintext. Untuk akun tanpa email, admin dapat menerbitkan one-time handoff code/link setelah verifikasi identitas; secret hanya ditampilkan sekali.

### Histori akademik
- Tambahkan event append-only tenant-scoped untuk ENROLLMENT, ROMBEL_TRANSFER, PROMOTION, RETENTION, GRADUATION, dan STATUS_CHANGE.
- Simpan student, event type, academic year, from/to rombel, tanggal efektif, old/new status, alasan, actor, dan idempotency key.
- Tambahkan status dan effective date pada keanggotaan baru. Row pivot lama tetap berstatus NULL/LEGACY_UNCLASSIFIED; jangan mengisi ACTIVE/CLOSED berdasarkan tebakan.
- Bila academic_year_id perlu ditambahkan ke pivot lama, backfill hanya dari relasi rombel yang valid setelah preflight tenant/FK; ini bukan backfill status.
- Transisi dilakukan satu transaksi dengan lock siswa dan membership aktif; tutup membership lama, buat membership baru, tulis event.
- Sediakan rekonsiliasi manual legacy dengan alasan dan audit; jangan otomatis menutup membership lama.
- Sebelum migration: backup DB terverifikasi, row counts, tenant counts, duplicate/orphan/cross-tenant preflight, index/FK inventory, dan restore test di staging.

## Status
- Audit source: selesai untuk tiga domain fungsional di atas.
- Stage4D implementasi: belum dimulai.
- Stage4E–4G implementasi: belum dimulai dari audit ini.
- Tidak ada migration/DB produksi/VPS/deployment/merge/rebase/PR.
- Tidak ada source Web Guru WG-01 yang diubah.
