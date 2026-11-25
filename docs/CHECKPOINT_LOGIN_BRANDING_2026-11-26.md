# Checkpoint Branding Login — 26 November 2026

## Posisi
- Repository: `zane0184/cbt_november`
- Branch: `audit/profile-login-branding-20261126`
- Basis: `work/guru-docx-rich-content-20261126`

## Temuan utama
Halaman login sebelumnya sudah mengambil tenant, logo aplikasi, dan favicon melalui `TenantBrandingService`. Kekurangan UI yang terbukti adalah heading utama masih hardcoded `CBT MADRASAH`. Heading telah diubah menjadi nama singkat madrasah, fallback ke nama resmi, lalu fallback lama.

## File
- Diubah: `backend-laravel/resources/views/auth/login.blade.php`
- Test: `backend-laravel/tests/Feature/WebAdminAuthTest.php` (nama madrasah, logo dan favicon login; fixture product code diperbaiki)
- Backup: `docs/backup_before_patch/LOGIN_BRANDING_20261126/`

## Status pengujian
- Perubahan source dibaca ulang dari GitHub: PASS.
- PHPUnit/CI: belum ada hasil; regression test tambahan belum dinyatakan PASS.
- VPS: tidak diuji ulang dan tidak diperlukan untuk tahap source.
- Logo/favicon runtime: belum diklaim selesai; service existing dipertahankan.

## Berikutnya
1. Jalankan CI/test terkait login dan tinjau hasilnya.
2. Jika ada kegagalan, perbaiki hanya dalam lingkup profil/login.
3. Jangan merge sebelum CI dan review diff.
4. Jangan mengubah VPS atau membuat ZIP.
