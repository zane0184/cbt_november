# P0.96G — Perbaikan Installer Private Runtime Configuration
Tanggal: 20 November 2026
Repository: `zane0184/cbt_november`
Base source: `work/schema-dump-export-20261119` @ `ff0393274891f7774511f09b59a1d8e97f6f745b`
Working branch: `fix/p0-96g-installer-private-db-config-20261120`

## AUDIT
- VPS MIN 15 menggunakan PHP-FPM `www`; direktori `backend-laravel` dimiliki `root:root` mode 755; `.env` dimiliki `root:www` mode 640.
- `InstallerService::writeEnvValues()` membuat temporary file pada direktori aplikasi lalu melakukan atomic rename ke `.env`. PHP-FPM tidak memiliki izin tulis pada direktori aplikasi, sehingga wizard gagal menyimpan konfigurasi.
- Jalur finalisasi `lockInstaller()` juga menulis `.env` untuk mengubah `APP_INSTALLED` dan mengosongkan bootstrap token. Jadi masalah izin tidak hanya terjadi pada langkah database.
- Penyebab ini konsisten dengan ErrorException yang tercatat di log, namun belum ada reproduksi runtime VPS setelah patch pada checkpoint ini.

## DESAIN
- Kredensial database installer disimpan sebagai PHP array di `storage/app/private/installer-database.php`, bukan ke `.env` atau installer state.
- File ditulis atomik di direktori private yang memang disiapkan writable oleh PHP-FPM, dengan mode 0600.
- `config/database.php` membaca override privat saat Laravel bootstrap. Nilai default database selain MySQL tetap mengikuti konfigurasi standar sebelum wizard menyimpan DB.
- Token bootstrap dinonaktifkan setelah finalisasi melalui `storage/app/private/installer-runtime.php`, juga mode 0600.
- `.env`, direktori source, dan kepemilikan file aplikasi tidak perlu dibuat writable oleh PHP-FPM.
- Backup konfigurasi privat lama dan config cache disimpan di `storage/app/private/installer-backups`.

## IMPLEMENTASI
Perubahan dibuat pada branch terpisah; branch kerja sebelumnya dan main tidak ditimpa.
- `backend-laravel/config/database.php`: muat override database privat.
- `backend-laravel/config/installer.php`: muat override token runtime privat.
- `backend-laravel/app/Services/InstallerService.php`: simpan kredensial dan revoke token tanpa memodifikasi `.env`; hapus jalur penulisan `.env` dari installer.
- `backend-laravel/tests/Feature/InstallerEnvSerializationTest.php`: tes persistensi database privat, permission 0600, pembacaan config, dan revoke token.
- `docs/PANDUAN_INSTALASI_WEB_CBT_MANDIRI.md`: jelaskan kebutuhan izin baru.

Tidak ada perubahan database, migration, konfigurasi VPS, atau secret produksi.

## PENGUJIAN
- Pemeriksaan source dan diff dilakukan melalui GitHub.
- CI PHPUnit/MySQL 8 dan Fresh Chain Gate masih menunggu dijalankan terhadap branch perbaikan.
- Deployment ZIP baru belum dibangun.
- Belum dilakukan uji ulang wizard pada VPS MIN 15.
- Belum ada migration yang dijalankan pada database VPS.

## LANGKAH LANJUT
1. Jalankan CI lengkap terhadap branch ini dan perbaiki semua kegagalan.
2. Jalankan Fresh Chain Gate dan audit package exclusions.
3. Build ZIP deployment dari commit yang sama dengan source yang lulus CI.
4. Verifikasi SHA-256, isi arsip, dan manifest.
5. Baru setelah itu pengguna mengganti paket pada staging/VPS dengan backup dan melanjutkan wizard. Database harus tetap kosong sebelum wizard menjalankan migration.
