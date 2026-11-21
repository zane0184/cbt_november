# AUDIT AWAL — MODUL WEB ADMIN SETELAH IMPORT

Tanggal: 22 November 2026  
Repository: zane0184/cbt_november  
Branch pemeriksaan lanjutan: work/stage4a-monitoring-rbac-20261122  
Source dasar: 17c20c66284deb7a18e3a6dde2b3d47f9c966bd9  
Catatan: temuan awal direkonsiliasi terhadap keputusan pemilik proyek terbaru.

## STATUS

Ini adalah inventaris dan audit awal source Web Admin aktif, bukan pernyataan seluruh Web Admin telah selesai. Fitur Import sudah melalui review final terpisah. Modul lain belum diubah dalam tahap ini.

## AUDIT

### Modul yang sudah ada di source aktif
- AdminDashboard
- TenantProfileManagement
- AcademicManagement
- UserManagement
- StudentRombelManagement
- CurriculumManagement
- ImportManagement
- ProctorAssignmentManagement
- Komponen Guru untuk QuestionBank, ExamManagement, dan hasil ujian

### Temuan arsitektur portal dan role

1. **Super Admin belum memiliki portal aktif**
   - `AuthWebController` tidak memasukkan `SUPER_ADMIN` ke role login yang diizinkan dan sesi Super Admin yang sudah aktif ditolak pada halaman login.
   - `DatabaseSeeder` dan `RolePermissionBootstrapper` belum membentuk matriks role/permission Super Admin.
   - Tidak ditemukan route, controller, atau Livewire dashboard Super Admin di source aktif.
   - `InventoryLegacySuperAdminCommand` hanya melakukan inventaris read-only; belum menjadi portal pengelolaan tenant, lisensi, dan platform.

2. **Kamad belum terhubung ke portal Admin**
   - Login mengizinkan role `KAMAD`, tetapi root route hanya mengarahkan Admin Madrasah, Guru, dan Siswa.
   - `KamadDashboard` ada sebagai komponen, tetapi tidak ditemukan route aktif yang menggunakannya.
   - Role matrix memberi Kamad permission `curriculum.view`, `exam.monitor`, dan `report.view`, tetapi `CurriculumManagement` saat ini membatasi akses pada Admin Madrasah/role legacy ADMIN.
   - Ini harus diselesaikan sebagai akses terbatas/read-only di portal Admin, bukan membuat portal keempat.

3. **Admin Madrasah wajib ditolak dari seluruh permukaan CBT**
   - `RolePermissionBootstrapper` memberi Admin `exam.proctor.assign`, tetapi tidak memberi `exam.monitor` atau `report.view`.
   - `ExamPolicy::monitor` menolak Admin Madrasah.
   - Route URL monitoring dan API monitoring memakai policy `monitor`; akses langsung tetap harus menghasilkan 403.
   - Keputusan pemilik proyek terbaru menegaskan Admin tidak boleh mengakses fitur maupun data CBT, termasuk monitoring. Admin hanya mengelola penugasan Proktor sementara dan administrasi madrasah; penugasan tersebut tidak memberi capability operasional CBT kepada Admin.

4. **Penugasan Proktor belum konsisten sebagai penugasan sementara**
   - Source aktif tidak memiliki portal/dashboard Proktor permanen; ini sesuai keputusan proyek.
   - Namun `ExamProctorAssignmentService` dan `ProctorAssignmentManagement` masih mensyaratkan user dengan role permanen `PROKTOR`.
   - `LegacyRoleMigrationService` memetakan role legacy `PROKTOR` menjadi `GURU`; setelah migrasi, filter role permanen tersebut tidak lagi cocok.
   - `ExamPolicy::monitor` tidak memeriksa record `ExamProctorAssignment`, sehingga record assignment belum menjadi sumber otorisasi monitoring.
   - `ProctorAssignmentSecurityTest` masih mengimpor `App\\Livewire\\Proktor\\ProktorDashboard`, sedangkan komponen tersebut tidak ada di source aktif. Test ini juga belum dimasukkan ke workflow CI terpilih.
   - Tahap berikut harus mengikat capability proktor pada record assignment sementara, jadwal, tenant, dan exam; bukan portal/role permanen.

### Modul akademik dan pengguna

5. **Penugasan Guru belum mempunyai UI administrasi**
   - Model/tabel `TeacherSubjectAssignment` dan impor sheet Penugasan Guru tersedia.
   - Tidak ditemukan komponen Livewire Admin untuk membuat, mengubah masa berlaku, menonaktifkan, atau melihat assignment secara terkelola setelah proses import.
   - Ini penting karena akses Guru ke bank soal/ujian bergantung pada assignment yang valid.

6. **UserManagement masih terbatas**
   - Komponen aktif menyediakan alur pembuatan user dan profil.
   - Belum ditemukan alur lengkap edit profil, aktif/nonaktif, reset password terotorisasi, pemulihan akun, dan histori perubahan lifecycle.

7. **StudentRombelManagement masih add-only**
   - Komponen menyediakan penambahan Siswa ke Rombel.
   - Belum ditemukan alur perpindahan rombel, kenaikan kelas, tinggal kelas, keluar/kelulusan, atau penutupan keanggotaan dengan histori.
   - Pemeriksaan duplikasi saat ini berfokus pada pasangan Siswa-Rombel yang sama; perlu audit dan aturan eksplisit untuk mencegah satu Siswa aktif pada lebih dari satu Rombel dalam tahun akademik yang sama.

8. **CurriculumManagement belum mewakili seluruh rancangan kurikulum**
   - UI saat ini berpusat pada LearningOutcome/CP dan LearningObjective/TP serta pembuatan TP.
   - Model fondasi multi-curriculum dan versioning ada, tetapi belum ditemukan alur UI Admin lengkap untuk registrasi/versi kurikulum, ATP/Learning Path, dan pemetaan Kurikulum Merdeka, KBC/Panca Cinta, serta Pembelajaran Mendalam.
   - Akses read-only Kamad juga belum terhubung.

9. **Administrasi platform dan dukungan**
   - AdminDashboard dan TenantProfileManagement sudah tersedia.
   - Report API/service ada, tetapi belum ditemukan modul Web Admin khusus untuk laporan administratif yang lengkap.
   - Belum ditemukan halaman Web Admin untuk monitoring sinkronisasi Android, dukungan teknis/tiket, backup/restore, dan pusat pembaruan sistem.
   - Ini adalah temuan pada lapisan UI Web Admin; keberadaan API/service terkait harus diaudit terpisah sebelum menyatakan fungsi backend tidak ada.

### Cakupan pengujian yang belum lengkap

Workflow CI terpilih saat ini mencakup dashboard, academic periods, role migration, tenant profile, user scope, Import, autentikasi, isolasi tenant, dan regresi Guru/Siswa. Belum menjadi bukti kelulusan untuk CurriculumManagement, StudentRombelManagement, ProctorAssignmentManagement, Kamad, Super Admin, Admin monitoring, dan modul laporan/sinkronisasi.

## DESAIN DAN URUTAN PENYELESAIAN

Urutan kerja berikut menjaga tiga portal yang ditetapkan: Super Admin, Admin Madrasah, dan Guru. Kamad menjadi role terbatas di portal Admin; Proktor hanya capability operasional sementara.

1. **Stage 4A — Pemisahan portal dan RBAC Admin/Guru/Siswa**
   - Pisahkan portal Admin Madrasah, Guru, dan Siswa.
   - Admin Madrasah tidak dapat mengakses fitur atau data CBT, termasuk monitoring, URL langsung, dan API.
   - Guru mengelola CBT sesuai role, permission, dan penugasan akademik.
   - Proktor adalah capability sementara bagi Guru yang memenuhi syarat, berbasis assignment aktif; tidak ada role/portal Proktor permanen.
   - Kamad, Super Admin, Android, dan deployment berada di luar ruang lingkup Stage 4A ini.

2. **Stage 4B — Proctor assignment lifecycle**
   - Status operasional PENDING, CONFIRMED, NEEDS_REVIEW, REVOKED, EXPIRED, COMPLETED.
   - Validasi tenant, exam, rombel, jadwal, revisi jadwal, konflik, dan audit.
   - Capability dicabut ketika assignment dicabut, kedaluwarsa, atau jadwal berubah.

3. **Stage 4C — TeacherSubjectAssignment Management**
   - CRUD terkontrol tanpa menghapus histori; validasi teacher/subject/rombel/year/semester dan effective dates.
   - Tenant lock, audit, idempotensi, dan tes race/concurrency.

4. **Stage 4D — User dan Student lifecycle**
   - Edit/status/reset password dan audit pengguna.
   - Perpindahan/kenaikan/tinggal kelas/kelulusan dengan histori akademik tetap terjaga.

5. **Stage 4E — Curriculum Management**
   - Versi kurikulum, CP/TP/ATP, Kurikulum Merdeka, KBC/Panca Cinta, Pembelajaran Mendalam dan akses role yang sesuai.

6. **Stage 4F — Operasional Admin**
   - Laporan administratif, monitoring sync, dukungan teknis, backup/restore, konfigurasi dan pusat pembaruan.

## IMPLEMENTASI

- Tidak ada source modul Web Admin selain Import yang diubah dalam audit awal.
- Koreksi scope Stage 4A berikutnya dicatat pada `docs/IMPLEMENTASI_STAGE4A_MONITORING_RBAC_2026-11-22.md`.
- Tidak ada migration, perubahan permission produksi, role migration produksi, PR, merge, atau deployment.
- Sebelum patch berikutnya: backup setiap file, catat blob SHA, buat regression test, jalankan MySQL 8 disposable CI.

## PENGUJIAN

- Bukti CI Import dan baseline Admin tersedia di GitHub Actions #44944158355.
- Temuan modul lain di atas adalah hasil inspeksi source aktif; belum dianggap lulus pengujian integrasi.
- Stage 4A menjadi pekerjaan implementasi berikutnya.
