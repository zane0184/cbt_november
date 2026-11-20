# LAPORAN P0.96A — Finalisasi Kandidat Paket Distribusi Web CBT
Tanggal: 20 November 2026
Repository: `zane0184/cbt_november`
Branch: `work/schema-dump-export-20261119`
Commit kerja saat laporan disusun: `5767a44209a96b8e90f24aa30101fc34dfb999af`
PR #3: OPEN + Draft

## 1. AUDIT

### 1.1 Bukti CI source sebelumnya
Pada commit P0.95D.2BC `fa7d087a8555404fb00471ad29af8fb21d04397b`:
- Tenant Backend CI run `44668120105`: SUCCESS; job log mencatat 450 test passed, 1 skipped, 1.824 assertions; PHP 8.3 dan MySQL 8.0.46.
- Fresh Chain Gate run `44668114625`: SUCCESS; semua 31 langkah job selesai, termasuk validasi runtime, composer lock, fresh migration, schema dump/replay, rollback guard, audit schema, dan penghapusan database disposable.
- Kedua bukti tersebut berlaku untuk commit `fa7d087a`, bukan otomatis untuk commit P0.96A setelahnya.

### 1.2 Installer dan distribusi
- Installer web sudah ada dan memiliki bootstrap token, pemeriksaan persyaratan PHP/extension, verifikasi MySQL 8/InnoDB, verifikasi target database kosong, wizard migration, pembuatan Admin awal, langkah lisensi, dan proteksi re-install.
- Paket runtime perlu memuat dependency Composer production; karena itu workflow menjalankan `composer install --no-dev` lalu memasukkan `vendor/` ke arsip.
- File test, backup internal, demo seeder, debug scripts, environment rahasia, dan cache/runtime tidak boleh masuk paket.
- Document root harus diarahkan ke `backend-laravel/public`.

### 1.3 Temuan environment installer
Sebelum patch, `.env.example` memakai `SESSION_DRIVER=database`, `CACHE_STORE=database`, dan `QUEUE_CONNECTION=database`. Pada saat wizard bootstrap, tabel database belum terbentuk. Cache database dipakai oleh rate limiter, sedangkan migration yang terinventarisasi tidak menyediakan tabel sessions. Default diubah menjadi file/file/sync untuk bootstrap instalasi mandiri yang fail-safe. Backup source sebelum perubahan tersedia.

### 1.4 Batasan yang belum dibuktikan
- Belum ada instalasi aktual pada VPS staging.
- Belum ada uji browser end-to-end untuk Admin/Guru dan alur ujian pada server staging.
- Belum ada bukti backup/restore, load/stress, penetration/security test independen, atau UAT madrasah.
- Device Lease/Fencing belum tervalidasi end-to-end dan tidak dinyatakan siap produksi.
- Aset Tailwind dan font masih mengambil CDN eksternal.

## 2. DESAIN
Paket ini mengikuti keputusan distribusi terakhir:
- Satu ZIP source aplikasi Web CBT untuk semua madrasah.
- Setiap madrasah menjalankan instalasi terpisah, database MySQL sendiri, konfigurasi sendiri, storage sendiri, dan backup sendiri.
- Tidak ada database operasional bersama maupun sinkronisasi data ujian antar-madrasah.
- Portal Web Admin Madrasah dan Guru menjadi ruang lingkup sekarang.
- Android native dan Portal Super Admin publisher bukan bagian paket Web ini; Android ditunda sampai Juni 2027.
- Database produksi tidak boleh disentuh selama tahap build/paket.

## 3. IMPLEMENTASI
### 3.1 Backup dan perubahan environment
- Backup: `backend-laravel/backup_before_patch/P0_96A_distribution_env_driver_defaults_20261120/.env.example.before`
- Backup commit: `cde74216a0b9ca070e389e7995b2c8f5b62d8de0`
- Perubahan commit: `c3ec7b915d7c1664ed4bc4407dba8d0f9bf7ab29`
- Nilai baru: `SESSION_DRIVER=file`, `CACHE_STORE=file`, `QUEUE_CONNECTION=sync`.

### 3.2 Dokumentasi
- `docs/PANDUAN_INSTALASI_WEB_CBT_MANDIRI.md` menjelaskan kebutuhan server, persiapan database kosong, .env, APP_KEY, bootstrap token, wizard, lisensi, dan batasan deployment.

### 3.3 Workflow paket
- `.github/workflows/build-web-deployment.yml`
- Build production dependencies Composer tanpa dev.
- Membuat struktur distribusi `CBT_MADRASAH_WEB_DEPLOYMENT/backend-laravel`.
- Mengecualikan .env, backup, tests, demo seeder, debug script, cache/runtime, dan file konfigurasi PHPUnit.
- Memeriksa artisan, composer files, vendor/autoload, migration, forbidden files, ZIP integrity, dan SHA-256.
- Mengunggah hasil sebagai GitHub Actions artifact.

## 4. PENGUJIAN
- P0.95D.2BC: PASS sebagaimana bukti run di atas.
- P0.96A: menunggu hasil workflow pada commit lengkap yang mencakup environment default, panduan, dan packaging workflow. Hasil tidak boleh diklaim sebelum run selesai.
- VPS, browser UAT, restore, dan load test: NOT RUN.

## 5. STATUS
**Kandidat paket deployment Web CBT sedang difinalisasi. Belum dinyatakan siap produksi.**
PR #3 tetap Draft dan belum merge. Branch main tidak berubah.
