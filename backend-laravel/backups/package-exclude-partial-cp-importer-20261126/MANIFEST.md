# Backup Manifest — Exclude Partial CP Importer from Deployment ZIP
Tanggal: 26 November 2026
Branch: integration/zip-vps-php83-20261126
Base commit: 482c5b7109f62760301792325a27e55235499440

File asli: .github/workflows/build-web-deployment.yml
Blob SHA sebelum perubahan: 96ff8da1392e98004ef4650f591ad30905293aad
Backup byte-exact: backend-laravel/backups/package-exclude-partial-cp-importer-20261126/build-web-deployment.yml.before
Alasan: audit arsip CI membuktikan ImportBahasaIndonesiaFaseCCpSeeder.php ikut masuk ZIP. Seeder itu hanya memuat empat CP Bahasa Indonesia Fase C dan tidak layak berada pada paket deployment sebelum registry CP lengkap.
Perubahan yang diizinkan: menambahkan exclusion eksplisit pada rsync staging dan assertion fail-closed bahwa file tersebut tidak ada pada staging.
Tidak ada source seeder atau database yang diubah.
