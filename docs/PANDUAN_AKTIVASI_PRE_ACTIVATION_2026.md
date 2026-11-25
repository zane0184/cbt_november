# Panduan Aktivasi Pra-Publisher 2026 — CBT Madrasah

## Tujuan dan batas penggunaan

Mode PRE_ACTIVATION_2026 disediakan sebagai jalur pilot sementara untuk instalasi Web CBT mandiri per madrasah, karena portal Publisher belum digunakan pada tahap 2026.

Mode ini bukan lisensi Publisher komersial, bukan signed license, dan tidak boleh diperlakukan sebagai aktivasi permanen. Setiap madrasah tetap memiliki VPS, file .env, database MySQL, storage, dan data sendiri.

## Syarat sebelum mengaktifkan

1. Gunakan hanya pada instalasi baru yang memiliki database kosong khusus madrasah.
2. Siapkan domain HTTPS, PHP 8.3, MySQL Oracle 8.0+ InnoDB, APP_KEY unik, dan bootstrap token acak.
3. Salin backend-laravel/.env.example menjadi .env untuk instalasi tersebut.
4. Ubah hanya nilai CBT_LICENSE_MODE di .env instalasi baru itu menjadi:

   CBT_LICENSE_MODE=PRE_ACTIVATION_2026

5. Jangan mengubah .env.example, file .env instalasi madrasah lain, atau konfigurasi produksi yang sudah berjalan.
6. Simpan catatan persetujuan pilot. Jangan mengirim token, password, APP_KEY, atau private key ke chat/laporan.

## Batas masa pilot

- Penerbitan pilot ditutup pada 31 Desember 2026.
- Setiap instalasi memperoleh masa berlaku maksimal 180 hari sejak wizard menyiapkan identitas pilot.
- Tanggal penerbitan dan kedaluwarsa dicatat pada state installer dan storage/installed.lock.
- Perubahan manual pada salah satu file tidak memperpanjang masa pilot; kedua catatan harus cocok dan identitas database harus sesuai.
- Ketika masa pilot habis, akses operasional baru diblokir. Sistem tetap mempertahankan database, jawaban, nilai, dan file madrasah.
- Attempt yang sudah dimulai sebelum masa pilot berakhir hanya dapat melanjutkan penyimpanan jawaban/submit/heartbeat sampai batas attempt itu sendiri, dengan identitas peserta dan tenant yang cocok.
- Status pilot dapat dilihat oleh Admin Madrasah/Operator pada /license/status.

## Urutan instalasi

1. Unggah dan ekstrak ZIP ke VPS staging atau instalasi baru.
2. Atur document root ke backend-laravel/public.
3. Siapkan .env khusus instalasi dan database kosong khusus madrasah.
4. Atur CBT_LICENSE_MODE=PRE_ACTIVATION_2026 hanya pada .env instalasi yang disetujui.
5. Jalankan php artisan key:generate --force, isi bootstrap token acak minimal 32 karakter, dan bersihkan config/route/view cache.
6. Buka /install/authorize, masukkan token, jalankan preflight, tes database, migration melalui wizard, dan buat Admin Madrasah.
7. Verifikasi ringkasan masa pilot pada halaman finalisasi, kunci installer, lalu login.
8. Periksa /license/status untuk memastikan status pilot dan tanggal kedaluwarsa sesuai.
9. Lakukan UAT, backup, dan restore pada lingkungan terisolasi sebelum madrasah menggunakan sistem untuk ujian sungguhan.

## Ketentuan operasional dan keamanan

- Default paket tetap CBT_LICENSE_MODE=PUBLISHER; mode pilot tidak diaktifkan otomatis.
- Jangan memakai mode pilot untuk mengganti Publisher pada instalasi yang sudah berjalan.
- Jangan menghapus installed.lock, state installer, identitas instalasi, atau tabel lisensi untuk mengatasi kegagalan.
- Jika finalisasi gagal, jangan mengulang migration. Periksa state dan log; perbaikan retry finalisasi sudah disediakan pada P0.96E.
- Mode pilot belum membuktikan keamanan produksi, stabilitas, backup/restore, load, atau kesiapan operasional madrasah.
- Jalur konversi instalasi pilot yang sudah terkunci ke Publisher masih harus diselesaikan dan diuji sebelum pilot pertama berakhir. Sampai jalur tersebut tersedia, gunakan paket ini untuk staging dan validasi pilot terbatas, bukan distribusi produksi massal.
