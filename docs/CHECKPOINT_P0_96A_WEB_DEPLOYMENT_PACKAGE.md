# CHECKPOINT P0.96A — Finalisasi Paket Distribusi Web CBT
Tanggal: 20 November 2026

## Posisi source
- Repository: `zane0184/cbt_november`
- Branch kerja: `work/schema-dump-export-20261119`
- PR: #3 — OPEN, Draft, belum merge
- Commit saat checkpoint disusun: `5767a44209a96b8e90f24aa30101fc34dfb999af`
- Base `main`: `95736e338e517a087503adfb3e3a899709e0340c`; tidak diubah.
- Source kerja sebelumnya P0.95D.2BC: `fa7d087a8555404fb00471ad29af8fb21d04397b`.

## AUDIT
1. P0.95D.2BC pada source `fa7d087a` sudah memiliki bukti Tenant Backend CI PASS dan Fresh Chain Migration Gate PASS di MySQL 8.0.46. Backend CI menunjukkan 450 passed, 1 skipped, 1.824 assertions. Fresh Chain seluruh langkah selesai.
2. Installer, wizard, validasi MySQL 8/InnoDB, verifikasi database kosong, state installer monotonik, dan proteksi re-install sudah ada di source.
3. `.env.example` sebelumnya memakai session/cache/queue database. Pada fase installer awal, tabel database belum dibentuk; session database dan rate limiter berbasis cache database dapat membuat bootstrap bergantung pada tabel yang belum tersedia. Database migration yang terinventarisasi memiliki tabel cache, tetapi tidak ditemukan migration khusus sessions. Default awal karena itu diubah menjadi file/file/sync.
4. Tailwind CSS dan font Plus Jakarta Sans saat ini dimuat melalui CDN eksternal; browser memerlukan akses internet untuk aset tersebut.
5. Belum ada bukti instalasi di VPS, uji browser operasional, backup/restore, load test, atau UAT madrasah. CI tidak membuktikan aspek tersebut.
6. Device Lease/Fencing belum boleh disebut siap produksi atau enforcement aktif.

## DESAIN
- Satu paket Web CBT yang sama untuk semua madrasah.
- Setiap madrasah memasang aplikasi sendiri dengan database, .env, storage, dan backup tersendiri.
- Tidak ada database operasional bersama atau sinkronisasi data CBT antar-madrasah.
- Web Admin Madrasah dan Guru menjadi fokus paket ini.
- Android native dan Portal Super Admin publisher berada di luar paket ini; Android ditunda sampai Juni 2027.
- Document root Nginx harus mengarah ke `backend-laravel/public`.

## IMPLEMENTASI P0.96A
1. Backup `.env.example` sebelum perubahan:
   `backend-laravel/backup_before_patch/P0_96A_distribution_env_driver_defaults_20261120/.env.example.before`
   Backup commit: `cde74216a0b9ca070e389e7995b2c8f5b62d8de0`.
2. `.env.example` diubah dengan backup tersedia:
   - `SESSION_DRIVER=file`
   - `CACHE_STORE=file`
   - `QUEUE_CONNECTION=sync`
   Commit perubahan: `c3ec7b915d7c1664ed4bc4407dba8d0f9bf7ab29`.
3. Panduan instalasi mandiri dibuat: `docs/PANDUAN_INSTALASI_WEB_CBT_MANDIRI.md`.
4. Workflow packaging dibuat: `.github/workflows/build-web-deployment.yml`.
5. Workflow merakit source Laravel + vendor Composer production, mengecualikan .env, backup, test, demo seeder, debug script, cache/runtime, lalu memeriksa struktur ZIP, forbidden paths, dan SHA-256.
6. Tidak ada perubahan pada branch `main`, database produksi, atau ZIP baseline asli.

## PENGUJIAN
- Bukti baseline P0.95D.2BC:
  - Tenant Backend CI run `44668120105`: SUCCESS.
  - Fresh Chain Gate run `44668114625`: SUCCESS.
- P0.96A: hasil CI setelah perubahan default environment dan hasil workflow packaging masih menunggu run pada commit yang memuat seluruh perubahan P0.96A.
- Tidak ada tes VPS atau migration database produksi yang dijalankan.

## STATUS
- Paket distribusi: BUILD/VERIFICATION PENDING.
- VPS staging: NOT RUN.
- Produksi: NO-GO sampai uji staging, backup/restore, security, stability, dan UAT selesai.
- Jangan merge PR #3 tanpa review penuh dan persetujuan eksplisit.

## LANGKAH LANJUT
1. Jalankan dan periksa Tenant Backend CI, Fresh Chain Gate, dan Build Standalone Web CBT Deployment ZIP pada commit P0.96A.
2. Unduh artefak ZIP dari workflow, periksa struktur, checksum, isi vendor, file sensitif, dan panduan.
3. Perbarui checkpoint ini dengan SHA commit, run ID, artifact ID, ukuran, dan hasil verifikasi.
4. Lakukan deployment hanya pada VPS staging/database baru setelah pemilik menyetujui jadwal dan akses.
