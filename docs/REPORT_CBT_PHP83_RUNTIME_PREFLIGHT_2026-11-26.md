# Laporan Persiapan Runtime CBT Curriculum Flow — 26 November 2026

## AUDIT
- Repositori yang digunakan: `zane0184/cbt_november`.
- Basis kerja: branch `work/rombel-docx-completion-20261126`; persiapan dipisahkan ke branch `work/cbt-curriculum-flow-php83-20261126`.
- `backend-laravel/composer.json` mengunci constraint PHP `^8.3` dan Laravel `^13.0`.
- Workflow lama `tenant-backend-ci.yml` menjalankan PHPUnit; instruksi tahap ini adalah tidak menjalankan PHPUnit/CI lama.
- Runtime eksekusi lokal yang tersedia sebelumnya bukan PHP 8.3, sehingga tidak dipakai untuk menjalankan aplikasi.
- Tidak ada koneksi VPS atau database produksi yang digunakan.

## DESAIN
Menyiapkan workflow manual terpisah yang menyediakan:
1. PHP 8.3.x secara eksplisit dan Composer 2.
2. Ekstensi `pdo_mysql` serta ekstensi PHP yang diperlukan aplikasi.
3. Service MySQL 8.0 ephemeral dengan database khusus `cbt_curriculum_flow_test`.
4. Pemeriksaan fail-closed terhadap versi PHP, identitas database, versi MySQL, dan InnoDB.
5. Instalasi dependency sesuai lock file, migrasi hanya ke database disposable, pemeriksaan tabel, dan registrasi rute API.
6. Tidak memanggil PHPUnit.

## IMPLEMENTASI
- File baru: `.github/workflows/cbt-curriculum-flow-php83.yml`.
- File baru: `docs/REPORT_CBT_PHP83_RUNTIME_PREFLIGHT_2026-11-26.md`.
- File baru: `docs/CHECKPOINT_CBT_PHP83_RUNTIME_PREFLIGHT_2026-11-26.md`.
- Tidak ada file source aplikasi, migration, konfigurasi produksi, atau data database yang diubah.
- Karena semua file adalah file baru pada branch kerja terpisah, tidak ada file lama yang ditimpa dan tidak diperlukan backup file sebelum perubahan.
- Commit workflow: `4003688375f1a24e8442c61c69e5e26861295c4f`.

## PENGUJIAN
- Workflow belum dijalankan. Belum ada bukti hasil provisioning PHP 8.3, migrasi, atau pemeriksaan rute.
- Belum ada pengujian alur Admin → Guru (TP/ATP) → Bank Soal → Snapshot Ujian → Siswa.
- PHPUnit tidak dijalankan.
- Status saat checkpoint: **DISIAPKAN, BELUM DIEKSEKUSI**.

## LANGKAH BERIKUTNYA
1. Jalankan workflow manual `CBT Curriculum Flow - PHP 8.3 Runtime Preflight` pada branch ini.
2. Jika preflight lulus, lanjutkan menyiapkan runner integrasi aplikasi non-PHPUnit untuk alur Admin → Guru → TP/ATP → Bank Soal → Snapshot Ujian → Siswa, dengan fixture sintetis diberi label tegas dan tidak dianggap sebagai bukti CP resmi.
3. Pisahkan acceptance alur aplikasi dari verifikasi sumber CP resmi. Selector CP resmi tetap diblokir sampai provenance/checksum dan QA sumber resmi terbukti.
4. Ambil bukti log, hasil pemeriksaan database disposable, dan bukti teardown sebelum memberi status PASS.
