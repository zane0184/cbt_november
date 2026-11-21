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

2. **Kamad berada di luar ruang lingkup pekerjaan aktif**
   - Pemilik proyek secara eksplisit menetapkan bahwa Kamad tidak termasuk Stage 4A maupun portal aktif saat ini.
   - Jangan menambahkan route, dashboard, role aktif, permission, fitur, atau alur Kamad tanpa keputusan baru pemilik proyek.
   - Temuan lama tentang Kamad pada source dipertahankan sebagai catatan historis saja dan tidak menjadi pekerjaan lanjutan.

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

Urutan kerja mengikuti keputusan aktif terbaru: pemisahan area portal Admin Madrasah, Guru, dan Siswa; Proktor hanya capability operasional sementara bagi Guru. Kamad, Super Admin, Android, dan deployment tidak dikerjakan dalam rangkaian ini.

1. **Stage 4A — Core monitoring RBAC**
   - Fixture Guru pembuat ujian, Guru Proktor, Guru tanpa assignment, dan Admin telah direvisi dan diuji.
   - Admin ditolak melalui URL/API langsung; status PENDING/legacy ACTIVE tidak memberi capability; CONFIRMED memberi capability sesuai exam dan interval.
   - Stage 4A belum ditutup karena scope ruang/sesi dan pemetaan peserta belum tersedia.

2. **Stage 4B — Ruang, sesi, dan Proktor lifecycle**
   - Admin menentukan Guru Proktor, ruang, dan sesi; peserta berasal dari rombel.
   - Buat model/relasi ruang-sesi dan mapping rombel/peserta ke ruang-sesi; Proktor hanya melihat peserta pada ujian, ruang, dan sesi tugasnya.
   - Lengkapi NEEDS_REVIEW, EXPIRED, COMPLETED, schedule revision/reschedule, konflik multi-koneksi, dan audit.
   - Tidak ada portal Proktor permanen.

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

- Implementasi Stage 4A core dicatat pada `docs/IMPLEMENTASI_STAGE4A_MONITORING_RBAC_2026-11-22.md` dan `docs/CHECKPOINT_STAGE4A_MONITORING_RBAC_2026-11-22.md`.
- Source aplikasi terakhir teruji: 88f1510a7d1992f6cd8d4b727daf93fe28a3afd3; CI 44952383038 SUCCESS pada MySQL 8.0.46 disposable.
- Belum ada migration produksi, perubahan permission produksi, role migration produksi, PR, merge, atau deployment.
- Sebelum patch berikutnya: backup setiap file, catat blob SHA, buat regression test, jalankan MySQL 8 disposable CI.

## PENGUJIAN

- Bukti Import tersedia di GitHub Actions #44944158355.
- Bukti Stage 4A core tersedia di GitHub Actions #44952383038: 109 test / 578 assertions PASS.
- Modul Web Admin lain tetap harus diaudit dan diuji; hasil audit tidak dianggap bukti kelulusan integrasi.
- Lanjutkan Stage 4B ruang/sesi, kemudian Stage 4C–4G sampai seluruh Web Admin selesai.
