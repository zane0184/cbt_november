# HANDOVER AGENT — CBT MADRASAH / P0.96G INSTALLER FIX
Tanggal checkpoint: 20 November 2026
Bahasa kerja: Indonesia
Prioritas: lanjutkan pekerjaan dari source aktual; jangan mulai ulang.

## 0. TUJUAN HANDOVER
Pengguna meminta seluruh perbaikan dilakukan pada source GitHub agar paket ZIP yang akan dipasang ulang di VPS sudah benar. Target pengalaman pengguna: unggah/ekstrak ZIP yang sudah diverifikasi, lalu lanjutkan Installation Wizard. Jangan meminta pengguna mengakali permission VPS atau mengulang pekerjaan investigasi yang sudah tercatat.

## 1. REPOSITORY DAN STATUS GIT
- Repository: `zane0184/cbt_november` (private).
- Branch baseline yang sebelumnya dipakai untuk deployment P0.96F: `work/schema-dump-export-20261119`.
- Commit baseline yang dipakai sebagai titik awal investigasi: `ff0393274891f7774511f09b59a1d8e97f6f745b`.
- Branch patch saat ini: `fix/p0-96g-installer-private-db-config-20261120`.
- HEAD source patch setelah dua commit escaping: `eecdf5ae404d0a6eadedecc6a4282f853d1967a1`.
- Commit dokumen handover terbaru yang ditambahkan setelah itu: `c91723f2014d244ccf91d7de2d4ed6cf20a777bb` (branch HEAD sekarang).
- Draft PR #4: https://github.com/zane0184/cbt_november/pull/4
- PR #4 belum merge, masih Draft, mergeable=false, melaporkan 485 changed files dan 363 commits.
- PENTING: PR #4 berasal dari branch yang diturunkan dari branch kerja lama, bukan branch bersih yang hanya berisi patch P0.96G. Jangan merge PR #4. Sebelum integrasi, buat branch bersih dari main dan cherry-pick/port hanya perubahan P0.96G yang diperlukan, setelah membandingkan main, baseline, dan branch aktual. Pastikan perubahan lama yang relevan tidak hilang dan tidak membawa 485 file yang tidak dimaksud.
- Tidak ada commit ke main yang dilakukan oleh sesi ini.

## 2. KONDISI VPS YANG SUDAH TERVERIFIKASI
Target: MIN 15 Jakarta Selatan, https://cbt.min15jakarta.sch.id
- OS AlmaLinux 8.10; aaPanel 8.0.7.
- Nginx 1.26.3; MySQL 8.0.45.
- PHP-FPM website CBT memakai PHP 8.3.36, socket `/tmp/php-cgi-83.sock`, pool user/group `www:www`.
- PHP 7.4.33 juga terpasang untuk situs lain. Dilarang mengubah PHP 7.4 atau konfigurasi situs lain.
- Laravel source: `/www/wwwroot/cbt.min15jakarta.sch.id/backend-laravel`.
- Document root: `/www/wwwroot/cbt.min15jakarta.sch.id/backend-laravel/public`.
- `.env`: `/www/wwwroot/cbt.min15jakarta.sch.id/backend-laravel/.env`, owner root:www, mode 640.
- `backend-laravel` directory owner root:root, mode 755. Ini sengaja menjaga source read-only dari PHP-FPM.
- `storage/` dan `bootstrap/cache/` dimiliki www:www, mode 755.
- `storage/app/private/installer-state.lock` owner www:www, mode 644.
- `storage/app/private/installer-state.json` tidak ditemukan pada pemeriksaan sebelumnya.
- `bootstrap/cache/config.php` tidak ada pada pemeriksaan sebelumnya.
- CLI PHP dengan OPcache aktif pernah segfault. Artisan hanya berjalan dengan bypass CLI: `-d opcache.enable=0 -d opcache.enable_cli=0 -d opcache.jit=0`. Jangan mengubah konfigurasi PHP global atau restart PHP-FPM.
- Nginx config test pernah sukses dengan warning existing `conflicting server name "_" on 0.0.0.0:80, ignored`. Jangan menyentuh konfigurasi global.
- User sudah memberi izin eksplisit dan melakukan restart Nginx lewat aaPanel sebelumnya. Jangan mengulang restart atau mengubah vhost tanpa alasan dan persetujuan baru.
- Root URL CBT sebelumnya merespons HTTP/2 200 dan menampilkan halaman status; itu bukan bukti integrasi/isolasi tenant siap produksi.

## 3. KONDISI INSTALLER DAN DATABASE DI VPS
- Bootstrap authorization berhasil; wizard menerima token dan mengeluarkan sesi installer 60 menit.
- Step 1 System Requirements lulus: PHP 8.3.36, extensions yang dibutuhkan, storage/cache permission 775 saat pemeriksaan wizard, HTTPS aktif, APP_KEY ada, memory 512M, upload/post 500M.
- Dedicated database CBT MIN15: `cbt_zane_min15jakarta`, user database dengan nama yang sama, MySQL 8.0.45, target terverifikasi kosong (0 tabel) pada pemeriksaan sebelumnya.
- Password database tidak boleh diminta atau dicantumkan dalam laporan/chat.
- Tombol “Simpan & Lanjut Migrasi” pada ZIP P0.96F gagal dengan pesan: “Konfigurasi database gagal disimpan secara aman. Periksa izin file .env dan storage.”
- Log menampilkan `CBT installer failed to save database configuration` dengan exception class `ErrorException`.
- Belum ada migration yang dijalankan. Database tetap kosong pada checkpoint sebelumnya. Jangan anggap sudah berubah tanpa pemeriksaan ulang.
- Pengguna sudah diminta tidak mengulangi wizard dengan ZIP P0.96F. Jangan lanjutkan wizard sampai paket baru lulus gate dan di-deploy dengan rencana backup.
- Installer route authorization: `https://cbt.min15jakarta.sch.id/install/authorize`.

## 4. ROOT CAUSE AUDIT
Pemeriksaan source aktual VPS menemukan:
- `InstallerService::writeDatabaseEnvironment()` memanggil `writeEnvValues()`.
- `writeEnvValues()` menggunakan `writeAtomicFile()` untuk membuat temporary file di direktori yang sama dengan `.env`, yaitu root aplikasi `backend-laravel/`, kemudian rename ke `.env`.
- Rename atomic membutuhkan hak write pada parent directory. Direktori source adalah root:root 755; PHP-FPM user www tidak dapat membuat temp file/rename di sana.
- File .env 640 root:www bukan akar tunggal; yang dibutuhkan oleh atomic replacement adalah write permission pada direktori parent.
- Ditemukan juga `lockInstaller()` lama memodifikasi .env lagi untuk APP_INSTALLED dan menghapus bootstrap token. Maka patch hanya pada save DB tidak cukup.
- Kesimpulan: mekanisme installer lama bertentangan dengan praktik deployment source read-only. Jangan menyelesaikan dengan chmod 777 atau menjadikan seluruh source writable.

## 5. P0.96G PATCH YANG SUDAH DIBUAT DI BRANCH
File utama yang telah diubah:
1. `backend-laravel/app/Services/InstallerService.php`
   - Kredensial database ditulis ke `storage/app/private/installer-database.php`, atomic, mode 0600.
   - Backup config privat lama dan config cache disimpan di `storage/app/private/installer-backups`.
   - Mengubah konfigurasi runtime Laravel untuk koneksi mysql.
   - Finalisasi menulis `storage/app/private/installer-runtime.php` untuk menonaktifkan bootstrap token; tidak lagi memodifikasi .env.
   - Jalur writer .env dihapus dari InstallerService.
   - Pesan error diperbarui agar merujuk pada storage private dan bootstrap/cache.
2. `backend-laravel/config/database.php`
   - Memuat override mysql dari `storage/app/private/installer-database.php` jika tersedia.
3. `backend-laravel/config/installer.php`
   - Memuat override token dari `storage/app/private/installer-runtime.php`; jika key bootstrap_token ada, termasuk null, override mengalahkan env.
4. `backend-laravel/tests/Feature/InstallerEnvSerializationTest.php`
   - Tes penyimpanan DB privat, mode 0600, pembacaan config, .env tidak berubah, dan bootstrap token runtime null.
5. `backend-laravel/tests/Feature/InstallerEndToEndTest.php`
   - Ditambahkan assertion bahwa .env tidak berubah setelah save database config dan setelah finalization.
   - Menambahkan private config/runtime file ke daftar file yang dipulihkan oleh E2E test.
6. `docs/PANDUAN_INSTALASI_WEB_CBT_MANDIRI.md`
   - Panduan menyatakan source root dan .env tidak perlu writable oleh PHP-FPM.
   - Menjelaskan DB config disimpan di storage/app/private dengan mode 0600.
7. `.github/workflows/p0-95d-fresh-chain.yml`
   - Ditambahkan pull_request trigger untuk main, checkout commit PR head, serta branch P0.96G dalam filter push.
8. `.github/workflows/build-web-deployment.yml`
   - Ditambahkan pull_request trigger untuk main, checkout commit PR head, branch source dari GITHUB_HEAD_REF, serta branch P0.96G dalam filter push.
9. `docs/CHECKPOINT_P0_96G_INSTALLER_PRIVATE_DB_CONFIG_2026-11-20.md`
   - Checkpoint implementasi sebelumnya; handover ini adalah checkpoint yang lebih baru dan lebih lengkap.

## 6. CATATAN PENTING TENTANG ESCAPING PHP
- Saat audit ulang terakhir, ditemukan string pembentuk file PHP runtime sempat memakai dua backslash sebelum n (karakter `\\n`) pada source PHP, yang dapat menulis literal backslash-n alih-alih newline.
- Sudah dibuat perbaikan di:
  - `InstallerService.php`
  - `InstallerEnvSerializationTest.php`
- Perubahan tersebut adalah dua commit terakhir:
  - `32cc451294949a50860e767803389bc1555660e2`
  - `eecdf5ae404d0a6eadedecc6a4282f853d1967a1`
- CI PASS yang ada di bawah ini berjalan sebelum dua commit escaping tersebut. Karena itu CI PASS tersebut bukan validasi HEAD terkini. WAJIB rerun CI untuk HEAD `eecdf5ae...` dan cek isi file PHP hasil generate.

## 7. PENGUJIAN GITHUB YANG SUDAH ADA
- Run Tenant Backend CI #44709380932:
  https://github.com/zane0184/cbt_november/actions/runs/44709380932
  Status completed / success pada commit `924b8db57b007673948ed58f35b1115f7dba9d91`.
  Step sukses: disposable MySQL identity, composer validate, composer install, package discover, isolated installer DB creation, focused Web login/tenant smoke tests, complete PHPUnit suite MySQL 8.
- Namun run ini terjadi sebelum fix escaping dua commit terakhir, jadi perlu rerun.
- Pada baseline `ff0393274891f7774511f09b59a1d8e97f6f745b`, run lama sukses:
  - Tenant Backend CI: run 44686668176 / PR event success.
  - P0.95D Fresh Chain Migration Gate: run 44686661944 / push success.
  - Tenant Backend CI: run 44686661932 / push success.
  - Build Standalone Web CBT Deployment ZIP: run 44686661859 / push success.
  Semua itu adalah bukti untuk baseline lama, bukan branch P0.96G.
- Per 20 Nov 2026, belum ada run Fresh Chain Gate yang terkonfirmasi pada branch P0.96G.
- Belum ada ZIP P0.96G yang dibangun/diunduh/diverifikasi.
- Tidak ada uji wizard ulang pada VPS dengan patch P0.96G.

## 8. TINDAKAN BERIKUTNYA — URUTAN WAJIB AGENT PENERUS
### A. AUDIT SOURCE / PR STRUCTURE
1. Buka branch HEAD aktual dan PR #4. Jangan percaya status lama; cek HEAD SHA.
2. Audit seluruh perubahan P0.96G dan dependencies-nya.
3. Investigasi mengapa PR #4 menunjukkan 485 files / 363 commits dan mergeable=false.
4. Jangan merge PR #4. Buat branch bersih dari main dan port patch yang memang diperlukan. Lakukan diff per file dan pertahankan source lain.
5. Pastikan tidak ada credential produksi atau data sensitif di source/PR/CI logs.

### B. AUDIT DESAIN / KEAMANAN IMPLEMENTASI
1. Periksa mekanisme Laravel config cache: apakah override file privat dibaca saat config cache dibuat? Pastikan wizard menonaktifkan/clear cache dengan aman dan setelah setiap request/worker baru kredensial DB tetap terbaca.
2. Periksa koneksi database yang digunakan oleh `inspectDatabase()`, `databaseFingerprint()`, `currentDatabaseParams()`, `runMigrations()`, `verifyInstallationPostconditions()`, dan rollback. Pastikan semuanya membaca kredensial baru dari sumber yang sama, tidak ada yang masih mengandalkan env.
3. Periksa urutan persist config privat -> update config -> DB::purge -> state write. Jika state write gagal setelah config tersimpan, definisikan rollback yang aman agar tidak meninggalkan credential config tak terpakai.
4. Periksa backup file privat: mode 0600, parent dir mode 0700, tidak disajikan melalui web, dan tidak ada symlink traversal. Pastikan storage private memang non-public.
5. Pastikan private runtime override untuk token tidak membuat installer bisa dibuka lagi, termasuk saat config cache aktif dan setelah restart PHP-FPM.
6. Pastikan config file privat yang dibaca menggunakan validasi array/keys dan error handling fail-closed.
7. Periksa apakah file private installer perlu dikecualikan dari package ZIP dan backup distribusi.
8. Periksa tes benar-benar menjalankan skenario PHP-FPM/source-root read-only atau minimal mensimulasikan parent source tidak writable.

### C. PENGUJIAN CI
1. Rerun Tenant Backend CI pada HEAD terbaru `eecdf5ae...`.
2. Jalankan Fresh Chain Migration Gate pada branch patch (pastikan workflow run muncul; jangan menganggap hanya karena YAML diubah).
3. Jalankan Build Standalone Web CBT Deployment ZIP dari commit yang sama.
4. Jika workflow tidak berjalan otomatis, cari sebab trigger/filter dan perbaiki.
5. Audit seluruh log job yang gagal; jangan hanya melihat status agregat.

### D. PACKAGE
1. Bangun ZIP hanya dari commit yang sudah lulus CI, Fresh Chain, dan package workflow.
2. Verifikasi ZIP test, manifest, jumlah file, composer.lock/vendor, document root structure, .env.example, README, exclusions.
3. Pastikan tidak ada .env, APP_KEY, bootstrap token, database credentials, storage private runtime/state, log, backup internal, dump DB, debug/test scripts yang dilarang.
4. Pastikan ZIP bukan sekadar patch file; harus paket mandiri penuh.
5. Catat SHA-256 dan commit source dalam manifest. Jangan sebut siap didistribusikan sebelum seluruh verifikasi lulus.

### E. DEPLOYMENT VPS (SETELAH PACKAGE LULUS)
1. Jangan melakukan perubahan saat ini. Tunggu ZIP final dan persetujuan eksplisit.
2. Backup ulang source yang sedang ada, vhost khusus CBT, .env secara aman (jangan tampilkan isi), storage dan installer state; verifikasi hash.
3. Pertahankan DB khusus `cbt_zane_min15jakarta`; pastikan kosong dengan query read-only sebelum wizard.
4. Jangan menyentuh DB lain, situs RDM, PHP 7.4, global PHP/Nginx/MySQL/Redis, DNS, SSL atau layanan lain.
5. Ekstrak paket baru sesuai layout; jangan timpa .env produksi dengan .env.example.
6. Pertahankan source root read-only untuk PHP-FPM; hanya storage dan bootstrap/cache yang writable.
7. Periksa APP_KEY, APP_URL, APP_ENV=production, APP_DEBUG=false, bootstrap token dan CBT_LICENSE_MODE=PRE_ACTIVATION_2026. Jangan minta user mengirim secret ke chat.
8. Uji halaman authorize/requirements/DB connection. Migration hanya lewat wizard setelah user memberi persetujuan pada langkah tersebut.
9. Jangan pernah menjalankan `php artisan migrate` manual.
10. Setelah migration, verifikasi tabel/migration count, state installer, tenant/admin, login, audit log, HTTPS, dan isolation.
11. Jika wizard gagal, STOP; jangan hapus tabel atau reset installer state.

## 9. BATASAN KERAS / PERSETUJUAN
- Jangan ubah production database tanpa backup, preflight, rencana rollback, dan persetujuan eksplisit.
- Jangan meminta/menampilkan .env, DB password, APP_KEY, bootstrap token, signing keys.
- Jangan chmod 777 atau memberi write akses permanen ke source root.
- Jangan restart service global.
- Jangan menyebut Device Lease/Fencing siap produksi; integrasi dan multi-device belum terbukti.
- Jangan mengklaim CBT siap produksi hanya karena CI atau ZIP berhasil.
- Android native tetap ditunda sampai Juni 2027.
- Mode pilot PRE_ACTIVATION_2026 berlaku untuk pilot terbatas, bukan lisensi Publisher produksi.

## 10. STATUS AKHIR CHECKPOINT
- AUDIT: penyebab kegagalan installer P0.96F sudah ditemukan dengan bukti source dan permission VPS.
- DESAIN: konfigurasi DB/token privat di storage telah dirancang dan diimplementasikan pada branch.
- IMPLEMENTASI: perubahan sudah ada di branch P0.96G, belum terintegrasi ke main dan belum menjadi ZIP final.
- PENGUJIAN: satu run CI sukses pada commit sebelum fix escaping terakhir; HEAD terkini belum diuji ulang. Fresh Chain, ZIP build, dan uji VPS belum lulus/belum dijalankan.
- VPS: tidak ada migration; jangan menganggap instalasi selesai.
- Agent berikutnya harus melanjutkan dari HEAD branch aktual, mengaudit PR base issue, rerun CI, Fresh Chain, build ZIP, lalu handover deployment. Jangan mulai dari P0.96F atau mengulang investigasi awal.
