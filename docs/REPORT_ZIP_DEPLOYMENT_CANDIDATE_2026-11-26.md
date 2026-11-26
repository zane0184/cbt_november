# Laporan Koordinator — ZIP Kandidat VPS dari Source GitHub Terbaru
Tanggal: 26 November 2026 (WIB)

## Ringkasan keputusan
ZIP deployment berhasil diambil dari GitHub Actions untuk source branch integrasi terbaru yang diperiksa. Paket ini dapat dipakai untuk **uji pemasangan dan validasi fitur di VPS**, tetapi belum boleh disebut final production release karena beberapa acceptance aplikasi masih terbuka.

- Repository: `zane0184/cbt_november`
- Source branch: `work/rombel-docx-completion-20261126`
- Exact source SHA: `841a1dd90dbe0277026fade1511a39b5dc9216b6`
- PR #32: draft/open; base `work/combined-feature-completion-20261126`
- PR #31 (integrasi profil, mapel, periode akademik, Import Guru): draft/open; source branch combined menjadi ancestor paket ini.
- Paket dibangun dari branch integrasi tersebut, bukan dari `main` yang pada saat audit masih tertinggal dan hanya memuat 43 tree entries. Tidak ada merge PR atau perubahan branch implementer.

## AUDIT
1. Source branch memuat 2.053 tree entries dan merupakan source Web CBT yang lebih lengkap daripada default branch `main`.
2. Source/fitur yang tercakup pada paket:
   - Backend Laravel, Web Admin Madrasah, Web Guru, Web Siswa browser.
   - Profil dan branding madrasah.
   - Data akademik, tahun pelajaran/semester, katalog mapel dan sinkronisasi mapel resmi lokal.
   - Import Guru dan Siswa administratif, dengan kredensial awal sekali tampil dan ekspor XLSX.
   - Detail Rombel.
   - Import soal DOCX dengan dukungan media JPEG/PNG privat, RTL, format teks, OMML/MathML dan tabel bertingkat; konten kompleks tetap memerlukan review Guru.
   - Bank Soal, asesmen/ujian, snapshot, hasil, laporan dan installer yang telah ada pada source dasar.
3. Arsitektur paket konsisten dengan instalasi mandiri: satu instalasi dan satu database per madrasah; tidak ada database akademik bersama atau akses data antar-madrasah. Tenant scope yang masih ada adalah lingkup lokal/legacy instalasi, bukan shared multi-tenant antar-madrasah.
4. Paket tidak menyertakan Android native peserta maupun Web Super Admin Publisher; keduanya ditunda sampai Juni 2027. Koneksi pusat tetap hanya untuk lisensi/aktivasi/entitlement/pembayaran.
5. Role bootstrap memberi grants hanya kepada `ADMIN_MADRASAH`, `GURU`, dan `SISWA`. OPERATOR/KAMAD/PROKTOR/SUPER_ADMIN tercatat sebagai role legacy yang tidak diberi grants; source legacy Kamad/Operator masih dipertahankan untuk kompatibilitas/histori, bukan portal aktif.
6. **Gap yang belum tertutup pada source**:
   - Route penugasan Proktor masih berada di portal Admin (`/admin/proctor-assignments`); menu/route Proktor sebagai capability tambahan di Web Guru belum ditemukan pada source ini.
   - Provenance CP resmi, Pembelajaran Mendalam, dan delapan dimensi profil lulusan belum accepted dengan artefak resmi/checksum.
   - Mapping QuestionVersion → kurikulum → immutable Exam Snapshot dan Web Siswa browser-to-real-Laravel E2E belum memiliki bukti acceptance lengkap.
   - Belum ada uji VPS/UAT untuk paket exact SHA ini.
7. Migration baru termasuk dalam source paket (antara lain batch Import Guru dan kategori Mapel lokal). Migration produksi tidak dijalankan oleh pekerjaan ini.

## DESAIN
- Satu ZIP Web CBT generik per madrasah, tanpa Android dan tanpa portal Super Admin pusat.
- Data, database, storage dan backup tetap terisolasi per instalasi.
- Kurmer dan KBC diperlakukan sebagai satu kerangka terpadu pada kontrak akademik/pembelajaran/asesmen; jangan memisahkannya menjadi dua kurikulum.
- Role aktif tetap tiga: Admin Madrasah, Guru, Siswa. Proktor adalah tugas tambahan Guru, bukan role permanen.
- Paket ini ditujukan untuk uji VPS langsung, bukan pernyataan bahwa semua acceptance produksi telah selesai.

## IMPLEMENTASI
Tidak ada perubahan source aplikasi, migration, workflow, database, VPS, atau PR pada branch implementer.

Artifact GitHub Actions:
- Workflow: Build Standalone Web CBT Deployment ZIP
- Run ID: `45756318417`
- Status workflow packaging: SUCCESS
- Artifact ID: `15087294979`
- Artifact bytes: `19,819,959`
- Artifact digest: `sha256:bacb9e8a7133e416c7b7ffa7b747158799137ff82c5a442a29634d7ef6b13dcf`
- Run URL: https://github.com/zane0184/cbt_november/actions/runs/45756318417

ZIP deployment di dalam artifact:
- Nama: `CBT_MADRASAH_WEB_DEPLOYMENT_841a1dd90dbe.zip`
- Ukuran: `18,774,759 bytes`
- SHA-256: `9f3a03b840278c5dd5fc89930c27fe52cf8be70c6ed572f844cc52d015360451`
- Manifest: 8.707 file terverifikasi; ZIP berisi 9.996 entry termasuk direktori.
- Isi utama: `backend-laravel/`, `vendor/`, `artisan`, `composer.json`, `composer.lock`, `.env.example`, `README_PEMASANGAN.md`, panduan aktivasi dan manifest checksum.
- Tidak memasukkan `.env`, backup, tests, docs internal, dump/database, private key/certificate, log/temp, atau symlink.
- README paket menjelaskan ekstraksi Windows Explorer, kebutuhan PHP 8.3/MySQL, konfigurasi dari `.env.example`, installer, storage dan bootstrap/cache.
- Salinan byte ZIP yang sama disiapkan pada path unduhan sesi; tidak dikemas ulang.

## PENGUJIAN
Dilakukan untuk tugas pengemasan ini:
- Download artifact GitHub: berhasil.
- Outer artifact ZIP: integritas ZIP lulus.
- Inner deployment ZIP: `unzip -t` lulus.
- SHA-256 inner ZIP cocok dengan checksum bawaan.
- SHA-256 seluruh 8.707 file manifest: lulus; cakupan manifest sama dengan file hasil ekstraksi.
- Pemeriksaan file terlarang: 0 temuan; symlink: 0.
- Pemeriksaan keberadaan Laravel artisan, Composer lock, vendor autoload, .env.example, dan README: lulus.
- Workflow packaging sendiri menjalankan Composer install/check platform, Laravel version/installer route checks, scanner, clean extraction dan checksum coverage.
- PHPUnit dan Tenant Backend CI **tidak dijalankan oleh koordinator untuk tugas ini**, sesuai instruksi pengguna. Status workflow packaging tidak boleh disalahartikan sebagai kelulusan PHPUnit/CI.
- Tidak ada pengujian runtime di VPS, migrasi produksi, browser UAT, atau validasi madrasah.

## Instruksi penggunaan dan batas klaim
1. **PENTING: README paket ini menetapkan clean extraction/instalasi baru. Jangan ekstrak menimpa webroot aktif atau database CBT yang sudah berjalan.** Untuk uji VPS, ekstrak ke direktori kosong terpisah dan gunakan database staging kosong yang terisolasi.
2. Untuk instalasi baru/staging, ikuti `README_PEMASANGAN.md`, termasuk document root ke `backend-laravel/public`, penyalinan `.env.example` menjadi `.env`, serta wizard installer.
3. Untuk instalasi yang sudah berjalan, ini belum merupakan paket upgrade in-place. Perlu audit migration ledger, backup source+database, rencana upgrade forward-only, dan preflight sebelum mengganti source atau menjalankan migration.
4. Jangan menimpa `.env` yang sudah ada; konfigurasi rahasia tetap milik instalasi madrasah.
5. Belum boleh menyatakan siap produksi atau menutup backlog Proktor Guru, provenance kurikulum, snapshot mapping, Web Siswa E2E, dan UAT.

**Status: ZIP KANDIDAT VPS TERSEDIA — PAKET TERVERIFIKASI SECARA STRUKTURAL — BUKAN PAKET UPGRADE IN-PLACE — ACCEPTANCE PRODUKSI MASIH TERBUKA.**
