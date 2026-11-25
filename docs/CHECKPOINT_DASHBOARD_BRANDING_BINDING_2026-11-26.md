# Checkpoint Binding Branding Dashboard — 2026-11-26

- Branch: `fix/madrasah-branding-dashboard-20261126`
- PR #29: Draft, belum merge/deploy; branch induk memuat perubahan PR #28.
- Implementasi: sidebar dan navbar layout bersama memakai short_name/name dan logo dari TenantBrandingService.
- Test terarah: `RoleDashboardVerificationTest::test_admin_dashboard_renders_latest_madrasah_name_and_logo`.
- Cakupan: hanya Dashboard Admin, nama resmi, nama singkat, URL logo. Tidak menjalankan suite penuh.
- Perintah: dari `backend-laravel/`, jalankan `php artisan test --filter=test_admin_dashboard_renders_latest_madrasah_name_and_logo`.
- Pemeriksaan statis source/layout/service/route/test selesai.
- PHPUnit terarah BELUM dieksekusi: Composer, vendor/, dan checkout workspace tidak tersedia pada runner ini; koneksi langsung GitHub gagal DNS.
- Workflow CI existing tidak dipakai karena menjalankan test luas, tidak sesuai scope.
- DB/schema/migration/service branding/tenant identity/production config tidak diubah.
- Operator route tidak ada dan portal Operator tidak ditambahkan.
- Berikutnya: jalankan satu test terarah pada checkout dengan dependensi terpasang; bila PASS, lakukan pemeriksaan browser Admin setelah perubahan profil.
