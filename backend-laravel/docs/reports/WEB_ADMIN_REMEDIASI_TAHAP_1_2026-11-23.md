# LAPORAN REMEDIASI WEB ADMIN — TAHAP 1
Tanggal: 23 November 2026
Status: IMPLEMENTASI AWAL — BELUM LULUS PENGUJIAN

## AUDIT
- Branch sumber sebelum perubahan: `work/web-admin-implementation-20261123`
- Commit basis: `e137e5b4f1ac42c899cd7cd308754fad79940e17`
- Branch kerja terisolasi: `work/web-admin-remediation-20261123`
- PR draft: https://github.com/zane0184/cbt_november/pull/18
- Main tidak diubah. Tidak ada merge, deployment, atau perubahan database.
- Audit ulang menemukan Admin masih melihat ringkasan total ujian dan tautan ke daftar ujian generik. Komponen manajemen ujian generik menerima Admin/Operator/Guru. Policy juga mengizinkan Admin membaca daftar ujian umum.
- Migration konsolidasi role dapat gagal pada fresh install karena migration berjalan sebelum DatabaseSeeder membuat role ADMIN_MADRASAH.
- Klasifikasi ujian terpusat UTS/UAS/UM dan model/tabel/relasi ruang ujian belum dapat diverifikasi pada source yang diperiksa. Karena itu filter jenis ujian dan pengikatan ruang belum diimplementasikan; tidak boleh dianggap selesai.

## DESAIN
- Admin tidak boleh masuk ke manajemen ujian generik atau melihat daftar ujian umum.
- Manajemen ujian reguler tetap pada Guru.
- Penugasan sementara Proktor hanya untuk role ADMIN_MADRASAH; OPERATOR bukan role aktif.
- Perbaikan migration hanya memastikan role ADMIN_MADRASAH tersedia untuk proses mapping legacy pada instalasi baru. Permission tetap dikelola RolePermissionBootstrapper/DatabaseSeeder.
- Filter UTS/UAS/UM dan room binding ditahan sampai sumber klasifikasi dan struktur ruang yang kanonis teridentifikasi.

## IMPLEMENTASI
Branch kerja: `work/web-admin-remediation-20261123`

File source diubah:
1. `backend-laravel/app/Livewire/Admin/Dashboard.php` — menghapus query dan data total ujian.
2. `backend-laravel/resources/views/livewire/admin/dashboard.blade.php` — menghapus kartu Total Ujian dan tautan daftar ujian generik.
3. `backend-laravel/app/Livewire/Admin/ExamManagement.php` — mount hanya mengizinkan Guru.
4. `backend-laravel/app/Policies/ExamPolicy.php` — menghapus hak baca umum Admin pada daftar ujian.
5. `backend-laravel/app/Livewire/Admin/ProctorAssignmentManagement.php` — menghapus penerimaan role OPERATOR; hanya ADMIN_MADRASAH.
6. `backend-laravel/database/migrations/2026_11_23_130001_consolidate_legacy_local_roles.php` — membuat row role ADMIN_MADRASAH jika belum ada sebelum pemetaan role legacy.

File test disesuaikan:
7. `backend-laravel/tests/Feature/AdminUiVerificationTest.php` — Admin tidak lagi diharapkan dapat membuka manajemen ujian.
8. `backend-laravel/tests/Feature/ProctorAssignmentSecurityTest.php` — Guru tidak boleh membuka halaman manajemen ujian Admin.

### Backup
Salinan sebelum perubahan:
- `backend-laravel/docs/backup/web-admin-remediation-2026-11-23/app/Livewire/Admin/Dashboard.php.before`
  SHA sumber: `34cdd0887b4e39b993468170d2b9af6f9e272389`
- `backend-laravel/docs/backup/web-admin-remediation-2026-11-23/app/Livewire/Admin/ExamManagement.php.before`
  SHA sumber: `d4354b292c979e98b1a31512fdf0d2fb7baa241f`
- `backend-laravel/docs/backup/web-admin-remediation-2026-11-23/app/Livewire/Admin/ProctorAssignmentManagement.php.before`
  SHA sumber: `5395e274f1dc528b97cf777f3a3803e68aca8505`
- `backend-laravel/docs/backup/web-admin-remediation-2026-11-23/app/Policies/ExamPolicy.php.before`
  SHA sumber: `43ae9800eda99b7fbcc4c1d2ab1317650146d6c3`
- `backend-laravel/docs/backup/web-admin-remediation-2026-11-23/resources/views/livewire/admin/dashboard.blade.php.before`
  SHA sumber: `38deb37b06824054af2c6d4f5c7c7735b3950ac6`
- `backend-laravel/docs/backup/web-admin-remediation-2026-11-23/tests/Feature/AdminUiVerificationTest.php.before`
  SHA sumber: `11743103373842b638400c49d4fbee7d594a9a32`
- `backend-laravel/docs/backup/web-admin-remediation-2026-11-23/tests/Feature/ProctorAssignmentSecurityTest.php.before`
  SHA sumber: `9e8928da969faf1d3d867e8ba461a35d079f33d7`
- Migration lama sudah memiliki backup terpisah:
  `backend-laravel/docs/backup/role-architecture-final-2026-11-23/2026_11_23_130001_consolidate_legacy_local_roles.php.before`
  SHA: `4e6c8efd87401e280bef408474479bc56808ea71`

Semua backup disalin dari branch sumber yang tidak diubah. File source asli pada branch sumber tetap utuh.

## PENGUJIAN
- Test otomatis belum dijalankan oleh lingkungan ini.
- Pemeriksaan GitHub Actions pada head PR saat pengecekan belum menampilkan workflow run atau status check.
- Tidak ada klaim PASS.
- Fresh-install migration belum dibuktikan melalui MySQL disposable CI.
- Tidak ada migration dijalankan pada database produksi.

## RISIKO / PEKERJAAN TERTUNDA
1. Temukan klasifikasi kanonis UTS/UAS/UM dan validasi enum/constraint-nya.
2. Temukan atau rancang sesuai sumber model ruang ujian yang sudah ada; jangan membuat foreign key ke tabel yang belum terverifikasi.
3. Setelah itu batasi query, policy, service, UI, dan test penugasan hanya pada ujian terpusat dan ruang yang sah.
4. Audit ulang akses Admin pada dashboard, route, policy, export, monitoring, hasil, dan laporan agar tidak ada jalur samping.
5. Jalankan CI lengkap dan tes fresh install pada MySQL disposable; jangan gunakan database produksi.

## BATASAN
Tidak ada merge PR #18, tidak ada deployment, tidak ada perubahan .env/secrets, tidak ada migration produksi, dan tidak ada perubahan Android/Super Admin.
