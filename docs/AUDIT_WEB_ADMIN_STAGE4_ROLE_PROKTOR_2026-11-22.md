# AUDIT AWAL — WEB ADMIN STAGE 4: ROLE DAN PENUGASAN PROKTOR
Tanggal: 22 November 2026
Repository: zane0184/cbt_november
Branch audit: work/import-guru-siswa-implementation-20261122

## STATUS
AUDIT selesai untuk source yang diperiksa. Belum ada patch Stage 4 pada file aplikasi. Tidak ada migration produksi, PR, merge, atau deployment.

## AUDIT
1. LegacyRoleMigrationService sudah memetakan ADMIN/OPERATOR ke ADMIN_MADRASAH dan PROKTOR ke GURU. Migrasi PROKTOR mensyaratkan akun aktif dan TeacherProfile aktif pada tenant yang sama. Role legacy diarsipkan; permission_role tidak dimutasi.
2. RolePermissionBootstrapper tidak lagi menyediakan role PROKTOR permanen.
3. ProctorAssignmentManagement dan ExamProctorAssignmentService masih mencari role PROKTOR. Setelah role consolidation, Guru hasil migrasi tidak muncul sebagai kandidat dan service menolak Guru. Ini membuat modul tidak konsisten dengan migrasi role.
4. Service dan schema assignment masih menggunakan status ACTIVE. Kontrak final tidak mengizinkan ACTIVE; assignment baru harus PENDING dan membutuhkan konfirmasi terpisah menjadi CONFIRMED.
5. exam_proctor_assignments belum menyimpan rombel_id atau schedule_revision. Cakupan assignment saat ini hanya tenant/exam/user dan rentang waktu.
6. ExamProctorAssignment::scopeActiveNow masih memberi makna pada ACTIVE; harus diganti dengan capability check yang hanya menerima CONFIRMED.
7. ExamPolicy::monitor belum memeriksa assignment Proktor sementara sebagai capability. Monitoring saat ini berbasis role/permission Guru dan TeacherAccess; belum ada akses terbatas untuk Guru yang ditugaskan pada exam/rombel tertentu.
8. Tidak ditemukan ExamScheduleChangeService pada source aktif. Perubahan jadwal tidak mencatat schedule revision dan tidak mengubah assignment CONFIRMED menjadi NEEDS_REVIEW.
9. Tidak ada conflict/overlap check lintas assignment Guru yang komprehensif.
10. ProctorAssignmentSecurityTest masih menggunakan role PROKTOR dan status ACTIVE, serta mereferensikan App\Livewire\Proktor\ProktorDashboard yang tidak ditemukan pada tree source aktif. Test ini juga belum masuk workflow CI terpilih.
11. QuestionBank dan ExamManagement berada di namespace Admin tetapi assertCanAccess mensyaratkan role GURU dan menolak ADMIN_MADRASAH; batas CBT Admin tetap fail-closed.
12. Modul Web Admin lain belum lengkap:
   - UserManagement: daftar dan buat akun; belum ada edit, aktif/nonaktif, reset password.
   - StudentRombelManagement: tambah siswa ke rombel; belum ada perpindahan/kenaikan/tinggal kelas/kelulusan dengan histori lengkap.
   - CurriculumManagement: saat ini fokus menambahkan TP; belum merupakan pengelolaan versi kurikulum dan CP/TP/ATP lengkap.
   - AcademicManagement: master data rombel/mapel/tahun/semester tersedia, tetapi lifecycle akademik dan penugasan Guru masih perlu audit lanjutan.

## DESAIN YANG HARUS DIPERTAHANKAN
- Admin hanya membuat dan mengelola penugasan Proktor sementara; Guru tetap pemilik operasional CBT.
- Target assignment adalah Guru aktif dengan TeacherProfile aktif, bukan role Proktor permanen.
- Assignment baru PENDING; aksi konfirmasi terpisah oleh Admin mengubahnya menjadi CONFIRMED.
- Hanya CONFIRMED yang dapat memberi capability, setelah validasi tenant, exam, rombel, schedule revision, konflik, status Guru, permission, dan waktu server.
- Status yang menolak capability: PENDING, NEEDS_REVIEW, REVOKED, EXPIRED, COMPLETED. Tidak ada ACTIVE.
- Interval capability: starts_at <= serverNow < expires_at.
- Perubahan jadwal oleh Guru harus menaikkan schedule revision dan mengubah assignment terdampak menjadi NEEDS_REVIEW; capability dicabut langsung.
- Overlap/conflict ditolak. Transisi yang diizinkan: PENDING→CONFIRMED; CONFIRMED→NEEDS_REVIEW/REVOKED/EXPIRED/COMPLETED; NEEDS_REVIEW→CONFIRMED/REVOKED.

## RENCANA IMPLEMENTASI
1. Audit seluruh jalur monitoring/API dan data peserta untuk memastikan Guru Proktor hanya melihat peserta pada rombel yang ditugaskan.
2. Rancang migration terisolasi untuk rombel_id dan schedule_revision, serta backfill fail-closed: data assignment lama ACTIVE menjadi NEEDS_REVIEW sampai dikonfirmasi ulang; jangan memberi capability otomatis.
3. Implementasikan state transition PENDING/CONFIRMED/NEEDS_REVIEW/REVOKED/EXPIRED/COMPLETED dan konfirmasi terpisah oleh Admin.
4. Ganti pemilihan kandidat dari PROKTOR ke Guru aktif + TeacherProfile tenant-local.
5. Tambahkan conflict/overlap locking dan audit log.
6. Buat ExamScheduleChangeService dan sambungkan semua jalur perubahan jadwal; revisi jadwal langsung mencabut capability melalui NEEDS_REVIEW.
7. Tambahkan policy/API test untuk monitoring terbatas rombel dan penolakan token, soal, peserta, nilai, serta operasi CBT lain bagi assignment Proktor.
8. Perbarui ProctorAssignmentSecurityTest dan masukkan ke workflow MySQL 8 disposable.
9. Setelah Stage 4 lulus, lanjutkan UserManagement, StudentRombelManagement, CurriculumManagement, laporan, konfigurasi, dan UAT.

## PENGUJIAN
- Tahap ini hanya audit source dan test yang tersedia; belum menjalankan suite khusus Proktor yang direvisi.
- Tidak ada klaim bahwa assignment Proktor siap atau capability sudah aman.
