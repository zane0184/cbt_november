# Addendum Audit Koordinator — Koreksi Scope Baca Exam Admin
Tanggal: 25 November 2026
Repository: `zane0184/cbt_november`
Branch sumber: `work/guru-docx-import-e2e-20261125`
Source SHA saat audit: `7c1cdcbe62673d50d05923cef8de9b462cd8aec9`

## Koreksi temuan
Laporan `REPORT_COORDINATOR_AUDIT_WEB_CBT_2026-11-25.md` sebelumnya menandai akses Admin ke generic Exam API sebagai pelanggaran role. Setelah memeriksa ulang `ExamPolicy` dan `RoleDashboardVerificationTest`, kesimpulan tersebut terlalu luas dan dikoreksi.

Test `RoleDashboardVerificationTest::test_admin_has_read_only_exam_register_access_and_no_teacher_cbt_operations` secara eksplisit menetapkan:
- Admin boleh membaca exam register dalam instalasi/tenant sendiri.
- Admin tidak boleh update/delete Exam.
- Admin tidak boleh mengelola soal atau peserta.
- Admin tidak boleh generate token, monitoring, close exam, atau melihat hasil.
- Admin boleh memiliki policy capability untuk penugasan Proktor, tetapi `ExamProctorAssignmentService` tetap fail-closed sampai marker ujian terpusat disepakati.

Karena itu, **read-only exam register untuk Admin dipertahankan**. Tidak boleh menghapus `viewAny/view` Admin hanya untuk membuat endpoint menjadi 403.

## Pembatasan yang tetap diperlukan
- Generic list/detail Admin tidak boleh menjadi jalur pengelolaan ujian.
- Token tidak ditampilkan melalui generic Admin list/detail sampai ada marker kanonis UTS/UAS/UM dan permission khusus untuk alur Proktor.
- Admin tidak menerima question snapshots melalui generic detail.
- Student tetap tidak menerima token rahasia dan tidak menerima snapshots melalui `GET /api/v1/exams/{exam}`. Snapshot hanya diberikan oleh execution setelah admission, Attempt sah, dan lease valid.

## Dampak implementasi
Pada branch implementasi `work/coordinator-web-cbt-p0-20261125`:
- Policy Admin read-only dipulihkan persis dari backup byte-equivalent.
- Admin tetap dapat membaca register, tetapi token di-redact pada generic list/detail.
- Admin tetap ditolak untuk mutasi Exam.
- Regression test disesuaikan untuk menguji read-only register, redaksi token, dan penolakan mutasi.

## Status
Koreksi ini hanya dokumentasi audit. Tidak mengubah source, database, atau VPS.
