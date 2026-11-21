# AUDIT DAN IMPLEMENTASI — WEB ADMIN STAGE 4A: RBAC MONITORING DAN PROKTOR SEMENTARA
Tanggal: 22 November 2026
Repository: zane0184/cbt_november
Branch kerja: work/stage4a-monitoring-rbac-20261122
HEAD dasar: 17c20c66284deb7a18e3a6dde2b3d47f9c966bd9
Source terakhir teruji: 88f1510a7d1992f6cd8d4b727daf93fe28a3afd3
CI: 44952383038 — SUCCESS

## STATUS
- Perbaikan fixture MonitoringDuplicationTest dan pengujian RBAC Guru/Admin: PASS.
- Penolakan Admin pada endpoint CBT langsung: PASS.
- Capability assignment Guru Proktor berbasis status CONFIRMED dan interval server: PASS pada suite terpilih.
- Integritas tenant assignment dan konflik waktu: PASS pada suite terpilih.
- Stage 4A keseluruhan: BELUM DITUTUP karena pembatasan ruang/sesi belum tersedia pada model data dan seluruh jalur monitoring belum dapat difilter berdasarkan ruang/sesi tugas.
- Tidak ada PR, merge, deployment, atau akses database produksi.

## AUDIT
1. Fixture baseline MonitoringDuplicationTest membuat Admin Madrasah sebagai pemilik Assessment/Exam, lalu mengharapkan Admin dapat membuka monitoring. Fixture tidak merepresentasikan kewenangan Guru pembuat ujian.
2. Fixture awal tidak membangun TeacherProfile dan TeacherSubjectAssignment yang diperlukan untuk menguji kewenangan Guru pembuat ujian.
3. ProctorAssignmentManagement dan ExamProctorAssignmentService menggunakan role PROKTOR permanen, tidak sesuai keputusan bahwa Proktor adalah penugasan sementara kepada Guru.
4. Assignment menggunakan status ACTIVE, padahal status kanonis adalah PENDING, CONFIRMED, NEEDS_REVIEW, REVOKED, EXPIRED, COMPLETED. ACTIVE tidak boleh memberikan capability.
5. Policy monitoring baseline tidak memeriksa assignment sementara sebagai sumber capability.
6. ExamAttempt execution/submit, ExamAnswer index/update, dan Monitoring heartbeat memeriksa header device sebelum otorisasi role. Admin tanpa header dapat memperoleh respons 400 alih-alih 403.
7. Source baseline tidak memiliki model/tabel ExamRoom, ExamSession, relasi sesi-ruang, maupun pemetaan peserta ke ruang/sesi. Assignment baseline hanya terikat pada exam dan waktu.
8. Tidak ditemukan ExamScheduleChangeService/schedule revision yang dapat mengubah assignment terdampak menjadi NEEDS_REVIEW.
9. UserManagement masih terbatas pada daftar dan pembuatan akun; StudentRombelManagement masih terbatas pada penambahan siswa; CurriculumManagement masih berfokus pada TP; belum lengkap untuk seluruh siklus Web Admin.
10. Role/portal Kamad, Super Admin, portal Proktor permanen, Android, dan deployment tidak menjadi ruang lingkup pekerjaan ini.

## DESAIN YANG DITERAPKAN
- Guru pembuat ujian dapat monitoring jika role GURU, permission exam.monitor, exam.created_by cocok, dan TeacherAccess.canManageExam membuktikan penugasan akademik/tenant yang sah.
- Guru Proktor sementara harus memiliki tepat role GURU dan TeacherProfile aktif pada tenant yang sama.
- Assignment baru berstatus PENDING. PENDING tidak memberi capability.
- Admin Madrasah mengonfirmasi assignment secara eksplisit menjadi CONFIRMED. Hanya CONFIRMED yang belum dicabut dan berada pada interval starts_at <= serverNow < expires_at yang dapat memberi capability.
- Legacy ACTIVE dipindahkan menjadi NEEDS_REVIEW; tidak diberi capability otomatis.
- Konflik waktu assignment CONFIRMED untuk Guru yang sama ditolak. Interval menggunakan semantik setengah terbuka [starts_at, expires_at).
- Lock order assignment: Exam → Guru row mutex → assignment row; pemeriksaan konflik menggunakan locking/current read.
- Admin boleh mengelola penugasan, tetapi Admin tidak memperoleh monitoring/data operasional CBT. Penugasan oleh Admin tidak memberi capability kepada Admin.
- Admin tidak boleh mengakses monitoring, peserta, attempt, jawaban, score, laporan, token, lock/close/publish melalui URL/API langsung.
- Ruang/sesi belum diimplementasikan. Keputusan operasional yang harus dibawa ke desain berikutnya: Admin menentukan Guru Proktor, ruang, dan sesi; peserta berasal dari rombel; capability Proktor dibatasi pada ujian, ruang, dan sesi tugasnya. Jangan mengklaim batas ruang/sesi selesai sebelum model, mapping peserta, query scope, dan test API selesai.

## IMPLEMENTASI
File aplikasi:
- \`backend-laravel/app/Policies/ExamPolicy.php\`
  - Monitoring Guru pembuat ujian diverifikasi melalui kepemilikan exam dan TeacherAccess.
  - Monitoring Guru Proktor mensyaratkan TeacherProfile aktif dan assignment CONFIRMED yang cocok tenant/exam/user/interval.
  - Admin tetap ditolak; permission assignment terpisah dari permission monitoring.
  - Policy confirmProctor hanya memberi kewenangan konfirmasi kepada Admin Madrasah yang berizin.
- \`backend-laravel/app/Services/ExamProctorAssignmentService.php\`
  - Target assignment harus tepat role GURU dan TeacherProfile aktif pada tenant yang sama.
  - Assignment baru/perubahan dibuat PENDING.
  - Konfirmasi eksplisit menjadi CONFIRMED, validasi waktu, konflik overlap, lock order, audit log.
  - Revoke mencabut capability dan tercatat audit.
- \`backend-laravel/app/Models/ExamProctorAssignment.php\`
  - Scope capability diganti menjadi confirmedNow; hanya CONFIRMED, belum revoked, dan interval valid.
- \`backend-laravel/app/Livewire/Admin/ProctorAssignmentManagement.php\` dan view terkait
  - Kandidat Guru aktif, aksi Konfirmasi/Cabut, label status kanonis.
- \`backend-laravel/database/migrations/2026_11_22_100001_enforce_confirmed_proctor_assignment_capability.php\`
  - Legacy ACTIVE menjadi NEEDS_REVIEW.
  - Default status baru menjadi PENDING.
  - Rollback ditolak jika status kanonis telah digunakan agar tidak menghidupkan kembali ACTIVE.
- \`backend-laravel/app/Http/Controllers/Api/V1/ExamAttemptController.php\`
  - Endpoint execution/submit memeriksa exact single-role SISWA sebelum header device.
- \`backend-laravel/app/Http/Controllers/Api/V1/ExamAnswerController.php\`
  - Endpoint jawaban memeriksa exact single-role SISWA sebelum header device.
- \`backend-laravel/app/Http/Controllers/Api/V1/MonitoringController.php\`
  - Heartbeat memeriksa exact single-role SISWA sebelum header device.
- \`backend-laravel/tests/Feature/MonitoringDuplicationTest.php\`
  - Guru pembuat ujian, Admin direct API denial, stale Admin role/permission, PENDING denial, legacy ACTIVE denial, Guru Proktor CONFIRMED, Guru tanpa assignment, revoke, expiry, dan tidak ada duplikasi peserta.
- \`backend-laravel/tests/Feature/ProctorAssignmentSecurityTest.php\`
  - Assignment Admin, Guru target, konfirmasi, konflik overlap, revoke, expiry, cross-tenant FK, dan profil Guru tidak aktif.
- \`.github/workflows/wa01-alternative-isolated-runtime.yml\`
  - Memasukkan dua suite tersebut ke pipeline MySQL 8 disposable.

Backup:
- Folder: \`docs/backup/stage4a-monitoring-rbac-2026-11-22/\`
- Snapshot mencakup seluruh file aplikasi, migration, test, workflow, dan dokumen yang diubah; ada snapshot tambahan sebelum patch lanjutan agar state antar-patch tidak saling menimpa.
- Jumlah snapshot pada source teruji: 31 file; 4 snapshot dokumen tambahan dibuat setelah pengujian. Total saat ini: 35 snapshot.
- ZIP baseline dan source asli tidak diubah.

## PENGUJIAN
GitHub Actions run 44952383038:
- Status run/job: SUCCESS; 30 langkah sukses, 0 gagal.
- Source yang diuji: 88f1510a7d1992f6cd8d4b727daf93fe28a3afd3.
- MySQL Community Server 8.0.46 disposable; PHP 8.3; Ubuntu runner.
- Total suite terpilih: 109 test, 578 assertions, seluruhnya PASS.
- MonitoringDuplicationTest: 8 test / 42 assertions PASS.
- ProctorAssignmentSecurityTest: 8 test / 34 assertions PASS.
- Suite lain mencakup AdminCbtBoundary, AdminDashboardAccess, AcademicPeriodManagement, LegacyRoleMigrationService, TenantProfileManagement, AdminUserManagementScope, ImportTemplateGenerator, ImportWorkbookService/DeadlockRetry, Auth, MultiTenantAuth, TenantIsolation, AssessmentPolicyTeacherCreate, StudentWebLogin, StudentResultVisibility.
- PHP lint: 333 file diperiksa, tidak ada syntax error.
- Preflight: required_schema_ready=true; missing_required_tables=[].
- MySQL disposable dan user aplikasi dihapus setelah pengujian.
- Run ini tidak menyentuh database produksi.

## GAP YANG MASIH TERBUKA
1. Model master ruang ujian dan sesi ujian belum ada.
2. Belum ada relasi sesi-ruang, penempatan rombel/peserta ke pasangan ruang-sesi, atau assignment Proktor pada pasangan ruang-sesi.
3. MonitoringController dan endpoint attempt/report belum memiliki query scope untuk membatasi Proktor pada peserta di ruang/sesi tugasnya.
4. Schedule revision dan ExamScheduleChangeService belum ada; perubahan jadwal belum mengubah assignment menjadi NEEDS_REVIEW.
5. Lifecycle EXPIRED/COMPLETED dan transisi NEEDS_REVIEW lengkap belum selesai; capability saat expiry sudah ditolak oleh interval server.
6. Uji konkurensi multi-koneksi untuk overlap assignment belum dijalankan; pengujian saat ini menguji penolakan overlap secara berurutan.
7. Uji UI browser manual dan UAT madrasah belum dilakukan.

## LANGKAH BERIKUTNYA — TIDAK BERHENTI DI STAGE 4A
1. Stage 4B: desain dan implementasi ruang/sesi, mapping rombel/peserta ke ruang-sesi, assignment Guru Proktor pada ruang-sesi, serta filter semua endpoint monitoring/attempt/report.
2. Stage 4B: schedule revision, NEEDS_REVIEW, expiry/completion lifecycle, overlap multi-koneksi, dan audit transisi.
3. Stage 4C: UI Web Admin penugasan Guru ke mapel/rombel/tahun/semester; backend API sudah ada, Livewire UI belum ditemukan.
4. Stage 4D–4E: lifecycle akun Guru/Siswa, perpindahan/promosi/tinggal kelas/kelulusan, dan histori akademik.
5. Stage 4F: kelengkapan Kurmer/KBC/Panca Cinta/Pembelajaran Mendalam, CP/TP/ATP dan versioning.
6. Stage 4G: laporan administratif, konfigurasi, audit log, health, backup/restore, integrasi, browser/performance, dan UAT Web Admin.
7. Tidak ada PR, merge, production migration, VPS deployment, Android, Kamad, Super Admin, atau portal Proktor permanen dalam tahap ini.
