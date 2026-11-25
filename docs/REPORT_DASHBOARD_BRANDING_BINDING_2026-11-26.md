# Laporan Binding Branding Dashboard — 2026-11-26

## AUDIT
- Branch kerja: `fix/madrasah-branding-dashboard-20261126`; PR #29 berbasis pada branch PR #28.
- Layout `layouts/app.blade.php` membaca tenant melalui `TenantBrandingService::currentTenant()` dan URL aset melalui `publicUrl()`.
- Sidebar dan navbar kini menggunakan nama madrasah dinamis serta logo tenant.
- Dashboard Admin memakai layout bersama `layouts.app`.
- Route Operator tidak tersedia; tidak dibuat portal Operator karena arsitektur yang disepakati hanya Super Admin, Admin Madrasah, dan Guru.

## DESAIN
- Pengujian dibatasi pada tampilan Dashboard Admin yang terkena patch.
- Satu test terarah: `RoleDashboardVerificationTest::test_admin_dashboard_renders_latest_madrasah_name_and_logo`.
- Test memeriksa respons dashboard, nama resmi, nama singkat, dan URL logo dari data profil yang diperbarui.
- Tidak menjalankan seluruh PHPUnit atau test suite modul lain.
- Tidak mengubah database/schema/migration, service branding, tenant identity, atau konfigurasi produksi.

## IMPLEMENTASI
- `backend-laravel/resources/views/layouts/app.blade.php`: sidebar dan navbar memakai nama serta logo tenant.
- `backend-laravel/tests/Feature/RoleDashboardVerificationTest.php`: test terarah nama resmi, nama singkat, dan logo Dashboard Admin.
- Backup source sebelum patch dan sebelum penambahan test tersedia di `docs/backup_before_patch/DASHBOARD_BRANDING_20261126/`.
- Backup dokumen sebelum pembaruan scope pengujian:
  - `docs/REPORT_DASHBOARD_BRANDING_BINDING_2026-11-26.before_targeted_test_scope.md`
  - `docs/CHECKPOINT_DASHBOARD_BRANDING_BINDING_2026-11-26.before_targeted_test_scope.md`

## PENGUJIAN
- Pemeriksaan statis terhadap layout, service branding, route Admin, dan test terarah selesai.
- Test belum dieksekusi: lingkungan ini memiliki PHP CLI tetapi tidak memiliki Composer, direktori workspace checkout, atau dependensi `vendor/`; akses jaringan langsung ke GitHub dari runner lokal gagal resolusi DNS.
- Workflow CI repository yang tersedia menjalankan job backend luas termasuk seluruh PHPUnit. Workflow tersebut tidak digunakan karena tidak sesuai batas pengujian yang diminta.
- Perintah untuk dijalankan pada checkout branch ini, dari direktori `backend-laravel/`:
  `php artisan test --filter=test_admin_dashboard_renders_latest_madrasah_name_and_logo`
- Status runtime: BELUM DIUJI. Tidak ada klaim PASS.

## STATUS
Patch dan test terarah sudah ada pada PR #29 (Draft). PR #28 (branding Login) masih menjadi branch induk. Belum merge, deploy, atau validasi browser/VPS. Penyelesaian task Dashboard Admin menunggu eksekusi satu test terarah dan pemeriksaan tampilan aktual.
