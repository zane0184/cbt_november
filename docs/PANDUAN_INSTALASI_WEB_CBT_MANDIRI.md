# Panduan Instalasi Web CBT Madrasah Mandiri

Dokumen ini berlaku untuk paket Web CBT Madrasah pada commit yang tercantum di `PACKAGE_MANIFEST.txt`.

## 1. Arsitektur distribusi

- Satu ZIP source dipakai sebagai paket aplikasi.
- Setiap madrasah memasang aplikasi secara mandiri pada servernya.
- Setiap instalasi memiliki database MySQL, akun database, konfigurasi `.env`, storage, backup, dan data operasional sendiri.
- Tidak ada database operasional bersama atau pertukaran data CBT antar-madrasah.
- Database lokal boleh memuat satu identitas tenant madrasah untuk kebutuhan isolasi internal aplikasi; ini bukan database multi-madrasah bersama.
- Portal Super Admin publisher dan aplikasi Android berada di luar ruang lingkup paket Web ini; pengembangan Android ditunda sampai Juni 2027.

## 2. Persyaratan server

- PHP 8.3.x.
- MySQL Oracle 8.0+ dengan InnoDB sebagai default storage engine. MariaDB tidak memenuhi pemeriksaan installer saat ini.
- Ekstensi PHP: bcmath, ctype, curl, dom, fileinfo, filter, gd, hash, json, mbstring, openssl, pcre, pdo, pdo_mysql, session, tokenizer, xml, xmlwriter, zip, sodium.
- Nginx melalui aaPanel, HTTPS aktif, dan PHP-FPM menggunakan PHP 8.3.
- Composer dependencies sudah disertakan di folder `vendor/` dalam paket.
- Akses tulis terbatas untuk `storage/` dan `bootstrap/cache/`; jangan gunakan permission 777. Direktori source aplikasi dan file `.env` boleh tetap read-only bagi PHP-FPM.

Pastikan versi PHP CLI yang dipakai saat menjalankan perintah sama dengan versi PHP-FPM pada website aaPanel.

## 3. Persiapan instalasi baru

1. Buat website/subdomain di aaPanel dan aktifkan SSL/HTTPS.
2. Buat satu database MySQL kosong khusus untuk instalasi madrasah tersebut dan satu user database khusus. Jangan memakai database produksi lain atau database milik madrasah lain.
3. Berikan hak yang diperlukan aplikasi untuk membuat dan mengubah tabel/index/foreign key pada database CBT tersebut.
4. Ekstrak ZIP. Folder aplikasi berada di `backend-laravel/`.
5. Atur document root Nginx ke folder `backend-laravel/public`, bukan ke root project. Pertahankan konfigurasi PHP-FPM yang dibuat aaPanel.
6. Salin `.env.example` menjadi `.env`. Jangan pernah menyalin `.env` dari instalasi madrasah lain.
7. Isi `APP_URL` dengan URL HTTPS madrasah, pastikan `APP_ENV=production` dan `APP_DEBUG=false`.
8. Buat APP_KEY unik untuk instalasi ini dengan perintah `php artisan key:generate --force`.
9. Isi `CBT_INSTALLER_BOOTSTRAP_TOKEN` dengan nilai acak rahasia minimal 32 karakter. Contoh membuat nilai acak di server: `openssl rand -hex 32`. Jangan masukkan token ke laporan publik atau chat.
10. Pastikan `SESSION_DRIVER=file`, `CACHE_STORE=file`, dan `QUEUE_CONNECTION=sync` tetap digunakan pada proses instalasi awal. Ini menghindari ketergantungan pada tabel session/cache/queue sebelum skema database terbentuk. Jangan mengubah session ke database; paket ini belum menyediakan migration tabel sessions khusus.
11. Jalankan `php artisan config:clear`, `php artisan route:clear`, dan `php artisan view:clear`.
12. Pastikan folder `storage/` dan `bootstrap/cache/` dapat ditulis oleh user PHP-FPM. Direktori root aplikasi (`backend-laravel/`) dan `.env` tidak perlu writable oleh PHP-FPM.

## 4. Jalankan wizard installer

1. Buka `https://domain-madrasah/install/authorize`.
2. Masukkan bootstrap token yang sudah dikonfigurasi di `.env`.
3. Periksa seluruh persyaratan server pada halaman installer.
4. Masukkan host, port, nama database, username, dan password database khusus madrasah.
5. Jalankan tes koneksi. Installer harus menyatakan database benar-benar kosong dan server MySQL 8+ menggunakan InnoDB.
6. Setelah konfigurasi database disimpan, installer menyimpan kredensial ke `storage/app/private/installer-database.php` dengan permission 0600. File `.env` tidak ditimpa oleh wizard; jangan mengubah permission direktori source menjadi writable hanya untuk mengatasi kegagalan penyimpanan.
7. Jalankan migration melalui wizard. Jangan menjalankan `php artisan migrate` manual dan jangan melewati pemeriksaan installer.
8. Buat profil madrasah dan akun Admin Madrasah awal dengan password kuat.
9. Selesaikan aktivasi sesuai mode yang dipilih untuk instalasi ini:
   - **Publisher (default):** pertahankan `CBT_LICENSE_MODE=PUBLISHER`. Mode ini memerlukan endpoint Publisher HTTPS dan lisensi/lease bertanda tangan yang sah.
   - **Pra-Publisher 2026 (pilot yang telah disetujui):** hanya untuk instalasi madrasah baru yang memang ditetapkan sebagai pilot. Pada file `.env` instalasi tersebut saja, ubah menjadi `CBT_LICENSE_MODE=PRE_ACTIVATION_2026` sebelum wizard dijalankan. Jangan mengubah `.env.example`, konfigurasi instalasi lain, atau konfigurasi produksi yang sudah berjalan. Baca `PANDUAN_AKTIVASI_PRE_ACTIVATION_2026.md` yang disertakan dalam ZIP.
10. Setelah installer menyatakan selesai, verifikasi halaman login dan dashboard. Installer dirancang terkunci setelah instalasi selesai; token bootstrap dinonaktifkan melalui konfigurasi runtime privat.

Jika migration gagal atau state installer menunjukkan kondisi parsial, hentikan. Jangan menghapus tabel, mengulang migration secara paksa, atau mengedit migration historis. Lakukan audit database dan recovery pada salinan terisolasi terlebih dahulu.

## 4A. Ketentuan mode Pra-Publisher 2026

- Mode ini adalah **pilot sementara**, bukan lisensi Publisher komersial dan bukan aktivasi bertanda tangan Publisher.
- Penerbitan pilot ditutup pada **31 Desember 2026**.
- Setiap instalasi mendapat masa berlaku maksimal **180 hari** sejak identitas pilot disiapkan pada wizard.
- Masa berlaku disimpan pada state installer dan `installed.lock`, lalu diverifikasi pada setiap request aplikasi. Mengubah tanggal di salah satu file saja tidak memperpanjang pilot.
- Setelah masa pilot berakhir, operasi baru diblokir. Attempt yang sudah berjalan sebelum batas waktu hanya diberi jalur pemulihan terbatas sampai waktu attempt itu sendiri habis. Data, jawaban, dan nilai tidak dihapus.
- Jalur konversi pasca-instalasi dari pilot ke Publisher masih merupakan pekerjaan lanjutan yang wajib selesai sebelum pilot pertama kedaluwarsa. Jangan menunggu sampai hari terakhir.
- Jangan mengaktifkan mode ini pada instalasi yang sudah berisi data atau mengganti mode pada instalasi berjalan tanpa rencana, backup, dan persetujuan tertulis.

## 5. Konfigurasi setelah instalasi

- Pertahankan HTTPS, `APP_DEBUG=false`, `SESSION_DRIVER=file`, dan `CACHE_STORE=file` sampai konfigurasi produksi diuji.
- `QUEUE_CONNECTION=sync` merupakan default konservatif. Jangan mengubah ke database/Redis sebelum queue table, worker, dan scheduler dirancang serta diuji.
- Tailwind CSS dan font Plus Jakarta Sans pada halaman saat ini dimuat melalui CDN eksternal; browser pengguna memerlukan akses internet untuk memperoleh aset tersebut.
- Atur backup database dan folder `storage/app` secara terjadwal. Simpan backup di lokasi terpisah dari webroot dan uji pemulihannya.
- Batasi akses SSH, aaPanel, database, dan file `.env`. Jangan pernah memasukkan private key publisher ke server madrasah.

## 6. Batasan status dan larangan

- Paket ini adalah kandidat deployment untuk uji staging, bukan sertifikat siap produksi.
- CI GitHub dan Fresh Chain Gate tidak menggantikan uji instalasi langsung di VPS, uji browser, backup/restore, keamanan, beban, dan UAT madrasah.
- Device Lease/Fencing belum boleh dinyatakan siap produksi; integrasi end-to-end, race condition, multi-device, dan validasi klien masih harus dibuktikan.
- Jangan mengaktifkan enforcement lease pada lingkungan produksi yang kliennya belum menerapkan protokol yang sama.
- Jangan melakukan migration pada database produksi yang sudah berisi data.
- Jangan menggunakan ZIP ini untuk mengganti source pada webroot aktif tanpa backup, preflight, rencana pemulihan, dan persetujuan eksplisit.
- Android native dan portal Super Admin publisher bukan bagian dari distribusi Web CBT ini.
