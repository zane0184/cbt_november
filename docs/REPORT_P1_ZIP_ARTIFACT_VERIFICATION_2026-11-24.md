# VERIFIKASI ARTEFAK ZIP P1 — 24 NOVEMBER 2026

## Identitas artefak
- Workflow run: [#45230720738](https://github.com/zane0184/cbt_november/actions/runs/45230720738)
- Workflow: Build Standalone Web CBT Deployment ZIP
- Source branch: `work/p1-seven-targets-20261124`
- Exact source SHA: `535a3af9c93ccb3c144cf9e9ed8c160d7146d525`
- GitHub artifact: `cbt-madrasah-web-deployment-535a3af9c93ccb3c144cf9e9ed8c160d7146d525`
- Artifact ID: `14847312148`
- Artifact container size: 19,762,548 bytes
- Artifact container digest: `sha256:2d1b8aac1948948a1b35404f13ec799da4316846c2db420fc3d4ccd483506f9a`
- Inner deployment ZIP: `CBT_MADRASAH_WEB_DEPLOYMENT_535a3af9c93c.zip`
- Inner ZIP size: 18,714,545 bytes
- Inner ZIP SHA-256: `23a6ec9ff02dba485457ca98142b49f3b478da6466ba4883cf5c00003de95bc7`

## Pengujian yang benar-benar dilakukan
1. Download GitHub artifact #14847312148 ke runtime audit.
2. Verifikasi SHA-256 inner deployment ZIP terhadap sidecar: PASS.
3. `unzip -t` pada inner ZIP: PASS, tidak ada error kompresi.
4. Ekstrak ke direktori pemeriksaan bersih: PASS.
5. Verifikasi SHA-256 seluruh file melalui `SHA256SUMS.txt`: PASS.
6. Hitung 8.723 file pada struktur hasil ekstraksi.
7. Forbidden path scan pada `backend-laravel`: 0 temuan untuk .env, PHPUnit config/cache, backup_before_patch, tests, demo/runtime seeder.
8. File wajib tersedia dan tidak kosong:
   - backend-laravel/artisan
   - composer.json dan composer.lock
   - vendor/autoload.php
   - .env.example
   - README_PEMASANGAN.md
   - PANDUAN_AKTIVASI_PRE_ACTIVATION_2026.md
   - SHA256SUMS.txt

## Isi manifest
- PHP build runtime 8.3.37.
- Composer 2.11.2.
- Laravel Framework 13.42.0.
- Satu instalasi dan satu database khusus per madrasah.
- Android dan Publisher Super Admin tidak termasuk; ditunda sampai Juni 2027.
- Manifest menyebut paket sebagai staging/deployment candidate, bukan production approval.

## Kesimpulan
**BUILD & ARSIP: PASS**
Artefak ini lulus pemeriksaan integritas ZIP, checksum, struktur, file wajib dan forbidden paths pada exact source SHA di atas.

**Artefak ini belum dinyatakan ZIP distribusi final.** Pemeriksaan ini tidak membuktikan instalasi di aaPanel/VPS, konfigurasi Nginx/PHP-FPM, wizard install pada server staging, migration chain pada server, browser E2E, backup/restore, security/load testing, atau UAT madrasah. ZIP final hanya boleh disiapkan setelah acceptance aplikasi dan staging selesai.
