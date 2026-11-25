# Laporan Binding Branding Dashboard — 2026-11-26

## AUDIT
- Branch kerja: `fix/madrasah-branding-dashboard-20261126`, turunan dari branch PR #28 `audit/profile-login-branding-20261126`.
- Layout `layouts/app.blade.php` memanggil `TenantBrandingService::currentTenant()` dan mengambil URL logo/favicon dari service yang sama.
- Sebelum patch, logo sudah tampil di sidebar, tetapi nama sidebar masih literal `CBT MADRASAH`; navbar tidak menampilkan branding madrasah.
- Dashboard Admin memakai `layouts.app`, sehingga binding layout bersama berlaku pada halaman dashboard Admin.
- `OperatorDashboard` legacy dan view-nya memang ada, tetapi `routes/web.php` tidak mendaftarkan route Operator. Desain proyek yang berlaku menetapkan portal Super Admin, Admin Madrasah, dan Guru; tidak dibuat portal Operator permanen.

## DESAIN
- Gunakan sumber profil tenant yang sudah ada; nama tampilan memakai `short_name`, fallback ke `name`, lalu default.
- Gunakan URL logo hasil validasi `TenantBrandingService::publicUrl()`.
- Layout membaca tenant pada setiap render, tanpa cache branding tambahan.
- `storeUpload()` menghasilkan nama file UUID baru untuk setiap unggahan, sehingga URL asset berubah saat logo diganti dan tidak bergantung pada cache file lama.
- Tidak mengubah database/schema, migration, service keamanan branding, tenant identity, atau konfigurasi produksi.

## IMPLEMENTASI
- `backend-laravel/resources/views/layouts/app.blade.php`: nama sidebar dinamis; navbar menampilkan logo dan nama madrasah.
- `backend-laravel/tests/Feature/RoleDashboardVerificationTest.php`: assertion nama tenant pada dashboard Admin dan test baru untuk nama resmi, nama singkat, serta URL logo terbaru.
- Test login sebelumnya pada `WebAdminAuthTest.php` mencakup nama, short name, logo, dan favicon.
- Backup sebelum patch:
  - `docs/backup_before_patch/DASHBOARD_BRANDING_20261126/app.blade.php.before`
  - `docs/backup_before_patch/DASHBOARD_BRANDING_20261126/RoleDashboardVerificationTest.php.before`
  - `docs/backup_before_patch/DASHBOARD_BRANDING_20261126/RoleDashboardVerificationTest.php.before_logo_test`

## PENGUJIAN
- Source hasil patch, test, dan backup berhasil dibaca ulang dari GitHub.
- Pemeriksaan statis mengonfirmasi pemanggilan tenant branding, logo sidebar/navbar, nama dinamis, test nama/logo dashboard Admin, dan test logo/favicon Login.
- PHPUnit/CI belum dijalankan. Lingkungan runtime lokal tidak memiliki Composer dan akses jaringan GitHub tidak tersedia; tidak diklaim lulus runtime.
- Workflow backend yang ada tidak memasukkan branch kerja ini pada filter push/pull_request; tidak ada workflow run atau status check untuk head commit.
- Belum ada uji browser pada instalasi VPS.

## STATUS
Patch UI Admin sudah tersimpan pada PR #29 (Draft), belum merge dan belum deploy. Fungsi branding pada Login telah tersedia dalam PR #28 yang menjadi branch induk. Penyelesaian runtime membutuhkan PHPUnit pada runner/lingkungan proyek serta uji browser setelah integrasi. Portal Operator legacy tidak termasuk target karena tidak memiliki route dan bukan portal permanen pada arsitektur yang disepakati.
