# Checkpoint Branding Login — 26 November 2026

## Posisi
- Repository: `zane0184/cbt_november`
- Branch: `audit/profile-login-branding-20261126`
- Basis: `work/guru-docx-rich-content-20261126`

## Temuan utama
Halaman login sebelumnya sudah mengambil tenant, logo aplikasi, dan favicon melalui `TenantBrandingService`. Kekurangan UI yang terbukti adalah heading utama masih hardcoded `CBT MADRASAH`. Heading telah diubah menjadi nama singkat madrasah, fallback ke nama resmi, lalu fallback lama.

## File
- Diubah: `backend-laravel/resources/views/auth/login.blade.php`
- Test: `backend-laravel/tests/Feature/WebAdminAuthTest.php`
- Backup: `docs/backup_before_patch/LOGIN_BRANDING_20261126/`

## Status pengujian
- Perubahan source dibaca ulang dari GitHub: PASS.
- PHPUnit/CI: belum ada hasil.
- VPS: tidak diuji ulang dan tidak diperlukan untuk tahap source.
- Logo/favicon runtime: belum diklaim selesai; service existing dipertahankan.

## Berikutnya
1. Jalankan CI/test terkait login.
2. Tambahkan/konfirmasi regression test logo dan favicon login dengan file branding yang sah.
3. Periksa sinkronisasi layout authenticated: heading sidebar juga masih literal `CBT MADRASAH`, sehingga perlu ditangani sebagai cakupan branding terpisah bila target owner mencakup seluruh portal.
4. Jangan merge sebelum CI dan review diff.
5. Jangan mengubah VPS. ZIP disiapkan setelah rangkaian perbaikan fitur selesai.
