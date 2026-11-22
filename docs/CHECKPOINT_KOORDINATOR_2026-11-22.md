# CHECKPOINT CBT MADRASAH — 22 NOVEMBER 2026

## Posisi kerja
- Repository: `zane0184/cbt_november`
- Branch kerja: `work/coordinator-web-admin-audit-20261122`
- Scope aktif: Web Admin P0 dan konsistensi role/penugasan Proktor.
- Branch `main` tidak diubah. Tidak ada merge, PR, atau deployment.

## Keputusan desain tetap
- Portal aktif hanya ADMIN_MADRASAH, GURU, SISWA.
- KAMAD adalah atribut `tenants.principal_name`, bukan role/portal.
- OPERATOR bukan role; fungsi administrasinya berada pada Admin Madrasah.
- PROKTOR adalah penugasan sementara pada akun Guru, bukan role permanen.
- Admin tidak otomatis mendapat akses bank soal, authoring, answer key, jawaban, atau nilai.
- Guru mengelola ujian sesuai penugasan dan tanpa approval Admin.
- Android siswa native; pengerjaan Android ditunda sampai jadwal proyek yang disepakati.
- Tidak ada migration produksi tanpa backup, clone test, rollback rehearsal, preflight, dan persetujuan eksplisit.

## AUDIT terakhir
- Source aktual branch diperiksa: RolePermissionBootstrapper, ProctorAssignmentManagement, ExamProctorAssignmentService, ExamPolicy, ExamAttemptPolicy, User, TeacherAccess, dan ProctorAssignmentSecurityTest.
- RolePermissionBootstrapper memberi `exam.monitor` kepada GURU; ExamPolicy.monitor tetap membatasi monitoring berdasarkan cakupan mengajar atau assignment Proktor aktif yang spesifik exam dan tenant.
- ExamAttemptPolicy tetap membatasi akses jawaban/nilai Guru melalui TeacherAccess dan permission report/grade; assignment Proktor tidak digunakan sebagai jalur otorisasi detail jawaban/nilai.
- UI kandidat Proktor sebelumnya belum memfilter exact-one role meskipun service sudah menolaknya. Patch koreksi telah dibuat.

## IMPLEMENTASI terakhir
File source:
- `backend-laravel/app/Livewire/Admin/ProctorAssignmentManagement.php`
  - Query pemilihan dan daftar kandidat kini mensyaratkan tepat satu role GURU dan TeacherProfile aktif pada tenant yang sama.
- `backend-laravel/tests/Feature/ProctorAssignmentSecurityTest.php`
  - Ditambah test negatif untuk Guru multi-role agar tidak tampil sebagai kandidat.

Backup:
- `docs/backup/coordinator-web-admin-2026-11-22/ProctorAssignmentManagement.php.before-exact-guru-role-ui-filter`
- `docs/backup/coordinator-web-admin-2026-11-22/ProctorAssignmentSecurityTest.php.before-exact-guru-role-ui-filter`
- `docs/backup/coordinator-web-admin-2026-11-22/AUDIT_KOORDINATOR_WEB_ADMIN_2026-11-22.md.before-exact-guru-role-ui-filter`

Dokumentasi:
- Addendum dicatat pada `docs/AUDIT_KOORDINATOR_WEB_ADMIN_2026-11-22.md`.

## PENGUJIAN
- Fetch ulang source/test branch: dilakukan.
- Backup source: blob SHA sebelum patch sama dengan blob backup.
- PHPUnit, PHP lint, MySQL 8 disposable, concurrency, browser/E2E: belum dijalankan.
- GitHub Actions sebelumnya gagal sebelum steps dan tidak memberi log; status test tetap NOT VERIFIED.
- Tidak ada runtime test PASS yang dapat diklaim.

## Gerbang berikutnya
1. Pastikan workflow runner dapat mengeksekusi job dan menampilkan log.
2. Jalankan PHP lint dan PHPUnit terkait pada PHP 8.3 + MySQL 8 disposable.
3. Tambah/validasi tes eksplisit bahwa Proktor tidak dapat membaca detail jawaban, answer key, nilai, melakukan grading, atau mengubah ujian.
4. Selesaikan audit seluruh route/action Admin dan tenant isolation sebelum acceptance Web Admin.
5. Jangan mulai portal Guru/Siswa sebelum Web Admin diterima.
6. Jangan menjalankan migration atau tindakan pada VPS aktif.

## Status
WEB ADMIN: NOT ACCEPTED / P0 OPEN.
DEVICE LEASE/FENCING: belum boleh dianggap siap produksi; perlu audit integrasi dan tes multi-device/race.
STAGING: BLOCKED sampai ada bukti preflight kedua VPS.
DISTRIBUSI: ZIP final belum dibuat dan belum siap dinyatakan.


## Pembaruan koordinator setelah checkpoint awal
### Source / role boundaries
- Guard Admin yang kini mewajibkan tepat satu role ADMIN_MADRASAH: AdminDashboard, UserManagement, AcademicManagement, StudentRombelManagement, TenantProfileManagement, ProctorAssignmentManagement, CurriculumManagement.
- Guard authoring CBT yang kini mewajibkan tepat satu role GURU: QuestionBank dan ExamManagement.
- Policy hasil/asesmen/ujian sudah tidak memberi jalur KAMAD atau OPERATOR. Proktor assignment hanya membuka monitoring melalui assignment aktif dan tidak memberi detail jawaban/nilai.
- Template Kamad dashboard yatim dihapus setelah backup; blok tanda tangan PDF tetap menggunakan principal_name.
- Test Admin stale-role diperbaiki dan diperluas untuk menolak akses multi-role pada seluruh komponen Admin/Guru yang diperiksa. Test profil Kamad diselaraskan dengan nilai principal yang benar.
- Workflow isolasi ditambah regresi untuk role canonical, retirement Kamad + rollback, penugasan Proktor Guru, dan full WebAdminAuthTest (penolakan login legacy).

### Backup tambahan
Backup file sebelum patch tersimpan di docs/backup/coordinator-web-admin-2026-11-22/, termasuk AcademicManagement, StudentRombelManagement, QuestionBank, ExamManagement, AdminUserManagementScopeTest, TenantProfileManagementTest, ProctorAssignmentManagement, ExamProctorAssignmentService, ProctorAssignmentSecurityTest, empat Policy, view Proktor, dan workflow CI.

### Pengujian terbaru
- Actions run terakhir untuk perubahan workflow: #45038056004, commit workflow efe79d51585704de24fb0e9d9a53e2e5c5d671c9.
- Job safe-runtime-tests failure tanpa steps dan tanpa logs_url; tidak ada artifact. Unduhan log menghasilkan BlobNotFound.
- PHPUnit/PHP lint tetap NOT VERIFIED. PHP CLI lokal yang tersedia hanya PHP 8.4.24; PHP 8.3, Composer, MySQL dan PDO driver tidak tersedia. Tidak ada test runtime lokal yang diklaim.
- GitHub source branch dapat dibaca; workspace /mnt/data/CBT_MADRASAH_RECONSTRUCTION/ tidak terpasang pada sesi ini dan ZIP baseline CBT REVISI 6 NOPEMBER 2026.zip tidak ditemukan pada /mnt/data maupun hasil pencarian Project files. Tidak ada permintaan upload ulang; pekerjaan branch yang sedang berjalan tetap berdasarkan source GitHub Paket 1 yang tercatat di atas.

### Pekerjaan tersisa sebelum Web Admin acceptance
1. Pulihkan/temukan runner CI yang benar-benar mengeksekusi job; jangan ulang job secara membabi buta.
2. Jalankan lint/PHPUnit pada PHP 8.3 + MySQL 8/InnoDB disposable, termasuk test tambahan yang baru dimasukkan ke workflow.
3. Selesaikan audit action/tenant isolation untuk setiap komponen Admin; khususnya stale TenantContext, rombel concurrency/unique constraint, profile upload lifecycle, dan edit/deactivate/reset/import akun.
4. Audit detail bahwa Proktor tidak dapat membaca answer key, detail jawaban/nilai, grading, atau mengubah ujian.
5. Lengkapi UAT browser/responsive dan acceptance Admin sebelum mulai Guru/Siswa.
6. Migration legacy role tetap tidak boleh dijalankan pada database aktif tanpa inventory, backup terverifikasi, clone-upgrade, rollback rehearsal, preflight dan persetujuan.

**Status terkini tetap: WEB ADMIN NOT ACCEPTED / P0 OPEN.** Tidak ada merge, production migration, deployment, atau ZIP final.


### ADMIN DATA SCOPE — IDENTITAS TUNGGAL DAN AKUN NONAKTIF
- AdminDashboard kini menghitung Guru/Siswa hanya bila akun memiliki tepat satu role yang sesuai.
- StudentRombelManagement hanya menampilkan akun Siswa aktif dengan tepat satu role SISWA; aksi penempatan memeriksa tenant, status akun, dan role tunggal kembali di server.
- UserManagement mengecualikan akun dengan deleted_at terisi dari daftar aktif.
- Regresi AdminUserManagementScopeTest ditambah untuk memastikan akun multi-role Guru+Siswa tidak dihitung sebagai Guru/Siswa dan akun Siswa soft-deleted tidak muncul pada dashboard, daftar pengguna, maupun pemilih rombel.
- Audit skema menemukan unique constraint lama pada pasangan (rombel_id, student_id) masih ada setelah hardening tenant 230003 menambahkan index tenant lookup dan composite foreign keys. Karena itu pencegahan duplikasi membership sudah memiliki lapisan DB selain pemeriksaan transaksi pada Livewire; tidak dibuat migration unik baru.
- Test/runtime belum dieksekusi karena GitHub Actions gagal sebelum steps; semua hasil tetap NOT VERIFIED sampai runner tersedia.
