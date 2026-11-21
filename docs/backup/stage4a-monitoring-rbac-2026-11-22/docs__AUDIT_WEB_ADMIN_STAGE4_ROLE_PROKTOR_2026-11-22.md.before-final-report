# AUDIT AWAL — WEB ADMIN STAGE 4: ROLE DAN PENUGASAN PROKTOR
Tanggal: 22 November 2026
Repository: zane0184/cbt_november
Branch audit: work/stage4a-monitoring-rbac-20261122
Source baseline: 17c20c66284deb7a18e3a6dde2b3d47f9c966bd9

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
- Admin hanya membuat/mengelola penugasan Proktor sementara. Admin tidak memperoleh hak monitoring atau data CBT setelah membuat assignment.
- Target assignment adalah Guru aktif dengan TeacherProfile aktif, bukan role Proktor permanen.
- Assignment lifecycle PENDING/CONFIRMED/NEEDS_REVIEW/REVOKED/EXPIRED/COMPLETED adalah desain lanjutan yang belum diimplementasikan pada baseline ini; status ACTIVE yang ada belum memenuhi desain final.
- Hanya assignment yang telah dikonfirmasi, masih berlaku, belum dicabut/kedaluwarsa, dan valid pada tenant serta ruang lingkup tugasnya yang dapat memberi capability.
- Ruang dan sesi harus menjadi bagian dari scope capability setelah entitas dan relasinya tersedia. Source baseline belum memiliki model/tabel ExamRoom/ExamSession atau kolom relasi assignment ke keduanya; tidak boleh mengklaim pembatasan ruang/sesi telah selesai.
- Perubahan jadwal harus mencabut capability sampai assignment ditinjau ulang; conflict/overlap harus ditolak secara transaksional.
- Kamad, Super Admin, portal Proktor permanen, Android, dan deployment bukan bagian dari pekerjaan ini.

## RENCANA IMPLEMENTASI
1. Selesaikan fixture monitoring dengan Guru pembuat ujian; Admin wajib ditolak melalui URL/API termasuk saat memiliki stale Guru role/permission.
2. Ubah kandidat penugasan menjadi Guru dengan TeacherProfile aktif; Admin hanya mengelola assignment.
3. Terapkan policy capability dari assignment aktif dan tenant-local, tanpa memberi akses CBT kepada Admin.
4. Rancang model/relasi ruang dan sesi setelah inventaris domain dikonfirmasi; jangan menciptakan relasi berbasis asumsi.
5. Implementasikan lifecycle PENDING/CONFIRMED/NEEDS_REVIEW/REVOKED/EXPIRED/COMPLETED, scope ruang/sesi, schedule revision, konflik, dan audit sebagai tahap terpisah.
6. Tambahkan pengujian URL/API langsung, multi-tenant, revoke/expiry, serta penolakan akses ke token, soal, peserta, jawaban, nilai, dan laporan.
7. Jalankan seluruh suite relevan pada MySQL 8 disposable.
8. Setelah Stage 4A/assignment lulus, lanjutkan UserManagement, StudentRombelManagement, CurriculumManagement, laporan administratif, konfigurasi, backup/restore, dan UAT Web Admin.

## PENGUJIAN
- Tahap ini hanya audit source dan test yang tersedia; belum menjalankan suite khusus Proktor yang direvisi.
- Tidak ada klaim bahwa assignment Proktor siap atau capability sudah aman.
