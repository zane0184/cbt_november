# Laporan Binding Branding Dashboard — 2026-11-26

## AUDIT
- Branch kerja: `fix/madrasah-branding-dashboard-20261126`, dibuat dari head PR #28 (`47aac14ec79b9eeb4cc236a3ee7509bce3a8edc3`).
- `layouts/app.blade.php` telah membaca tenant branding melalui `TenantBrandingService::currentTenant()`, termasuk logo dan favicon.
- Logo sudah digunakan pada sidebar, tetapi nama madrasah pada sidebar masih literal `CBT MADRASAH`.
- Navbar utama belum menampilkan logo dan nama madrasah.
- `Admin/Dashboard.php` dan view dashboard menggunakan `layouts.app`, sehingga binding pada layout bersama mencakup dashboard Admin.
- Komponen `OperatorDashboard` dan view-nya ada, tetapi `routes/web.php` pada branch yang diperiksa tidak mendaftarkan route ke komponen tersebut. Sesuai keputusan arsitektur tiga portal, route/portal Operator tidak dibuat dalam patch ini.

## DESAIN
- Menggunakan data tenant dan service branding yang sudah tersedia pada layout.
- Nama tampilan memakai `short_name`, fallback ke `name`, lalu fallback default.
- Logo memakai URL yang telah divalidasi oleh `TenantBrandingService::publicUrl()`.
- Tidak mengubah database, schema, migration, service branding, tenant identity, atau konfigurasi produksi.

## IMPLEMENTASI
- Mengubah `backend-laravel/resources/views/layouts/app.blade.php`: nama sidebar menjadi dinamis; navbar menampilkan logo dan nama madrasah dari tenant branding.
- Menambahkan regression assertion pada `backend-laravel/tests/Feature/RoleDashboardVerificationTest.php` untuk memastikan nama tenant tampil pada dashboard Admin.
- Backup sebelum perubahan:
  - `docs/backup_before_patch/DASHBOARD_BRANDING_20261126/app.blade.php.before`
  - `docs/backup_before_patch/DASHBOARD_BRANDING_20261126/RoleDashboardVerificationTest.php.before`

## PENGUJIAN
- Verifikasi baca ulang GitHub: file layout hasil patch, test hasil patch, dan kedua backup berhasil ditemukan.
- Pemeriksaan konten mengonfirmasi binding nama, logo navbar, dan assertion nama dashboard Admin.
- PHPUnit/CI belum dijalankan pada lingkungan ini; tidak diklaim lulus.
- Dashboard Operator belum dapat dinyatakan tersambung karena komponen tersebut tidak memiliki route aktif pada branch yang diperiksa.

## STATUS
Patch UI Admin telah dibuat pada branch kerja. Belum merge dan belum deploy. Perlu menindaklanjuti status route Operator sesuai arsitektur portal yang berlaku sebelum menyatakan semua tampilan selesai.
