# Laporan Audit dan Perbaikan Paket — Exclusion Importer CP Parsial
Tanggal: 26 November 2026
Repository: `zane0184/cbt_november`
Branch: `integration/zip-vps-php83-20261126`

## AUDIT

### Artefak sebelum perbaikan
Workflow Build ZIP pada HEAD `482c5b7109f62760301792325a27e55235499440` berstatus SUCCESS:
https://github.com/zane0184/cbt_november/actions/runs/45783652401

Artefak GitHub Actions yang diunduh:
- Artifact ID: `15098674390`
- Ukuran: 19,831,898 byte
- SHA-256 artefak pembungkus: `aea9c580db51b204445f93364c4fa10b1096f427dae0e07b79ebe3646a16379c`
- ZIP aplikasi di dalam artefak: `CBT_MADRASAH_WEB_DEPLOYMENT_482c5b7109f6.zip`
- Ukuran ZIP aplikasi: 18,786,598 byte
- SHA-256 ZIP aplikasi: `a288028fb8c0a686194292a89a8582836b591b7101330470eb5052af9eeac86e`
- Daftar isi ZIP: 9.997 entri.

Pemeriksaan arsip secara langsung membuktikan:
- `backend-laravel/database/seeders/ImportBahasaIndonesiaFaseCCpSeeder.php` ikut masuk ZIP.
- `backend-laravel/database/seeders/CurriculumReferenceSourceSeeder.php` ikut masuk ZIP.
- `backend-laravel/.env.example` ikut masuk ZIP, sesuai kebutuhan konfigurasi.
- Pencarian nama backup, folder backups, file .before, file .sql, .pem, dan .key pada daftar arsip tidak menemukan hasil.
- ZIP tersebut merupakan kandidat build sebelumnya dan tidak boleh dianggap paket distribusi final karena importer CP parsial ikut terkemas.

### Penilaian risiko
Importer CP Fase C hanya berisi empat outcome Bahasa Indonesia Fase C dan bukan registry CP lengkap. File tersebut tidak dipanggil oleh `DatabaseSeeder`, tetapi tetap dapat dijalankan manual dan tidak layak ikut paket instalasi madrasah. Pengguna tidak boleh menjalankan seeder itu.

## DESAIN
- Pertahankan source seeder untuk audit/rekonstruksi sementara di branch kerja.
- Keluarkan seeder parsial dari direktori staging paket.
- Tambahkan assertion fail-closed agar build gagal jika file parsial masih ada di staging.
- Jangan mengubah gate checksum TeacherCurriculumContextService.
- Jangan menjalankan importer atau mengubah database.

## IMPLEMENTASI
Backup byte-exact workflow sebelum patch:
- File asli: `.github/workflows/build-web-deployment.yml`
- Blob SHA sebelum patch: `96ff8da1392e98004ef4650f591ad30905293aad`
- Backup: `backend-laravel/backups/package-exclude-partial-cp-importer-20261126/build-web-deployment.yml.before`
- Manifest: `backend-laravel/backups/package-exclude-partial-cp-importer-20261126/MANIFEST.md`

Patch:
- Menambahkan exclusion `database/seeders/ImportBahasaIndonesiaFaseCCpSeeder.php` pada rsync staging.
- Menambahkan assertion `test ! -e backend-laravel/database/seeders/ImportBahasaIndonesiaFaseCCpSeeder.php` pada staging gate.
- Tidak mengubah source aplikasi, DatabaseSeeder, schema, migration, atau database.

Commit patch workflow: `ed445705602df4825c7d1e17e3d57e10312b7501`.

## PENGUJIAN
Sebelum patch:
- Arsip berhasil diunduh dan dibuka.
- SHA-256 artefak dan ZIP aplikasi dihitung lokal.
- Daftar isi ZIP diperiksa langsung dan importer parsial ditemukan.

Sesudah patch:
- Workflow Build ZIP run `45785051652`: PENDING saat laporan ditulis.
- Workflow PHP Runtime run `45785051711`: PENDING saat laporan ditulis.
- Belum ada artefak pascapatch yang dapat diperiksa.
- Belum boleh menyatakan exclusion sudah lulus build sampai workflow selesai SUCCESS dan artefak baru diunduh serta diperiksa.
- PHPUnit, browser UAT, VPS, MySQL disposable, migration, dan import CP belum dijalankan.

## STATUS
**PATCH WORKFLOW SUDAH DI-COMMIT — BACKUP TERSEDIA — VERIFIKASI BUILD PASCAPATCH MENUNGGU.**
Paket kandidat sebelum patch tidak boleh dipasang. Tidak ada VPS/database produksi disentuh.
