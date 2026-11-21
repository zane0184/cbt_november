# AUDIT AWAL — MODUL WEB ADMIN SETELAH IMPORT

Tanggal: 22 November 2026  
Repository: zane0184/cbt_november  
Branch pemeriksaan: work/import-guru-siswa-implementation-20261122  
Source aktif diperiksa sampai commit: a3522e1c8be02224ef9b369051105f05b2704e39

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

2. **Batas kewenangan Admin Madrasah terhadap CBT**
   - `RolePermissionBootstrapper` memberi Admin `exam.proctor.assign`, tetapi secara eksplisit mencabut `exam.monitor` dan `report.view`.
   - `ExamPolicy::monitor` menolak Admin Madrasah.
   - Route monitoring menggunakan policy tersebut. Batas ini harus dipertahankan: Admin mengelola administrasi dan penugasan Proktor sementara saja, bukan monitoring CBT, bank soal, ujian, jawaban, peserta, atau penilaian.

3. **Batas akses Guru dan Siswa harus diuji menyeluruh**
   - Guru mengelola bank soal dan ujian berdasarkan role, permission, penugasan mata pelajaran/rombel, serta tenant aktif.
   - Siswa hanya mengakses ujian dan hasil yang ditujukan kepadanya; tidak boleh mengakses data peserta lain.
   - Seluruh batas harus diuji melalui URL langsung, Livewire action, API, serta relasi lintas tenant.

4. **Penugasan Proktor belum konsisten sebagai penugasan sementara**
   - Source aktif tidak memiliki portal/dashboard Proktor permanen; ini sesuai keputusan proyek.
   - Namun `ExamProctorAssignmentService` dan `ProctorAssignmentManagement` masih mensyaratkan user dengan role permanen `PROKTOR`.
   - `LegacyRoleMigrationService` memetakan role legacy `PROKTOR` menjadi `GURU`; setelah migrasi, filter role permanen tersebut tidak lagi cocok.
   - `ExamPolicy::monitor` belum memeriksa record `ExamProctorAssignment` sebagai capability monitoring yang terikat tenant, exam, dan waktu.
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

9. **Administrasi platform dan dukungan**
   - AdminDashboard dan TenantProfileManagement sudah tersedia.
   - Report API/service ada, tetapi belum ditemukan modul Web Admin khusus untuk laporan administratif yang lengkap.
   - Belum ditemukan halaman Web Admin untuk monitoring sinkronisasi Android, dukungan teknis/tiket, backup/restore, dan pusat pembaruan sistem.
   - Ini adalah temuan pada lapisan UI Web Admin; keberadaan API/service terkait harus diaudit terpisah sebelum menyatakan fungsi backend tidak ada.

### Cakupan pengujian yang belum lengkap


## DESAIN DAN URUTAN PENYELESAIAN

Urutan kerja mengikuti portal aktif yang telah disepakati: Super Admin, Admin Madrasah, dan Guru; Siswa menggunakan antarmuka peserta. Proktor hanya penugasan operasional sementara kepada Guru yang memenuhi syarat.

1. **Stage 4A — Pemisahan portal dan RBAC**
   - Pisahkan portal Admin Madrasah, Guru, dan antarmuka Siswa sesuai kewenangan.
   - Admin Madrasah tidak boleh mengakses fitur maupun data CBT.
   - Guru dibatasi role, permission, penugasan, tenant, dan kepemilikan data.
   - Penugasan Proktor sementara diberikan kepada Guru aktif yang memenuhi syarat; tanpa role/portal Proktor permanen.
   - Uji akses langsung URL/API, Livewire action, dan percobaan lintas tenant/kewenangan.

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
   - Versi kurikulum, CP/TP/ATP, Kurikulum Merdeka, KBC/Panca Cinta, Pembelajaran Mendalam dan akses role Guru/Admin sesuai kewenangan.

6. **Stage 4F — Operasional Admin**
   - Laporan administratif, monitoring sync, dukungan teknis, backup/restore, konfigurasi dan pusat pembaruan.

## IMPLEMENTASI

- Tidak ada source modul Web Admin selain Import yang diubah dalam audit ini.
- Tidak ada migration, perubahan permission produksi, role migration produksi, PR, merge, atau deployment.
- Sebelum patch berikutnya: backup setiap file, catat blob SHA, buat regression test, jalankan MySQL 8 disposable CI.

## PENGUJIAN

- Bukti CI Import dan baseline Admin tersedia di GitHub Actions #44944158355.
- Temuan modul lain di atas adalah hasil inspeksi source aktif; belum dianggap lulus pengujian integrasi.
- Stage 4A menjadi pekerjaan implementasi berikutnya.
