# Addendum P0 Packaging — Koreksi Regex Secret Scan
Tanggal: 25 November 2026

## AUDIT
Uji lokal terhadap regex secret scan menemukan false positive: pola multiline sebelumnya memakai `\s*` di sekitar assignment. Dalam mode multiline, `\s` dapat mencakup newline sehingga nilai kosong pada `.env.example` dapat tersambung ke baris berikutnya.

## BACKUP SEBELUM PATCH
- File: `.github/workflows/build-web-deployment.yml`
- Backup: `backend-laravel/backup_before_patch/P0_PACKAGING_SECRET_REGEX_FIX_20261125/build-web-deployment.yml.before`
- Manifest: `backend-laravel/backup_before_patch/P0_PACKAGING_SECRET_REGEX_FIX_20261125/MANIFEST.md`
- Original blob SHA-1: `00930f28d7ba0915e72051747720be97a12518aa`
- Ukuran: 19.485 byte
- SHA-256: `301171241071360a2e4db79c634e04275c7f3e0b1229ce745aa07860782a7f47`
- Fetch ulang backup: blob SHA-1, ukuran, SHA-256, dan konten identik.

## IMPLEMENTASI
Kedua scanner (staging tree dan ZIP entries) diubah dari whitespace umum `\s*` menjadi horizontal whitespace `[ \t]*` pada pola assignment multiline. Ini mencegah regex melintasi batas baris. Deteksi credential, private key/certificate, AWS/GitHub/Slack/Stripe token, dan bearer token tetap dipertahankan.

Commit patch:
`8d7f010d0df87d16a3bfd7e3ba9287da06c23275`

Workflow blob setelah patch:
`b95bb4a9fb3f28ece7fdfae4b27267bbd73adb68`

## PENGUJIAN
Uji Python lokal terhadap pola regex:
- Semua 9 regex berhasil dikompilasi.
- Contoh `.env.example` dengan APP_KEY, bootstrap token, DB_PASSWORD kosong dan REDIS_PASSWORD=null: tidak ada false positive.
- Contoh DB_PASSWORD dan CBT_INSTALLER_BOOTSTRAP_TOKEN bernilai rahasia: terdeteksi.
- Contoh Authorization Bearer token: terdeteksi.

Pengujian ini hanya memvalidasi regex. Workflow GitHub Actions, ZIP nyata, clean extraction, dan artifact masih **NOT VERIFIED / NOT RUN**.

## STATUS
Koreksi regex selesai dan backup terverifikasi. P0 Packaging tetap **IMPLEMENTASI AWAL — CI/ARTIFACT BELUM TERVERIFIKASI**.
