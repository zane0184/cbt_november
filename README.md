# CBT Madrasah

Repositori source aplikasi CBT Madrasah untuk distribusi ke banyak madrasah melalui **instalasi mandiri**.

## Model produk dan instalasi (keputusan arsitektur 23 November 2026)

- Setiap madrasah memasang aplikasi sendiri dan memiliki **database terpisah milik madrasah tersebut**.
- Tidak ada database bersama, tenant lintas madrasah, atau akses data antar-madrasah.
- Layanan pusat (Super Admin) hanya untuk lisensi, aktivasi/validasi lisensi, paket fitur, batas penggunaan, masa berlaku, dan pembayaran/langganan. Tidak ada sinkronisasi data siswa, akademik, soal, jawaban, attempt, nilai, atau laporan pendidikan ke layanan pusat.
- Satu instalasi lokal beroperasi mandiri terhadap database madrasahnya.
- Istilah "multi-tenant" tidak digunakan untuk menggambarkan banyak madrasah di satu database. Jika `tenant_id` masih ada pada source, itu hanya lingkup instalasi lokal/kompatibilitas sampai audit dan refactor aman selesai.

**Portal lokal hanya Admin Madrasah, Guru, dan Siswa.**
- Seluruh fungsi Operator dilebur ke Admin Madrasah. Tidak ada akun, role, atau portal Operator baru.
- Tidak ada akun/role Kamad. Kepala Madrasah hanya jabatan/metadata operasional dalam laporan atau pengaturan.
- Tidak ada Proktor permanen. Guru dapat diberi tugas pengawasan sementara berdasarkan assignment aktif.
- Role legacy OPERATOR/KAMAD/PROKTOR hanya untuk proses upgrade yang diaudit; bukan untuk akun baru atau otorisasi setelah transisi.

Web Super Admin dan aplikasi Android Super Admin ditunda sampai **Juni 2027**. Penundaan ini tidak berlaku untuk aplikasi Android native peserta CBT.

Dokumen wajib seluruh agent:
[`docs/KEPUTUSAN_ARSITEKTUR_INSTALASI_MANDIRI_DAN_ROLE_2026-11-23.md`](docs/KEPUTUSAN_ARSITEKTUR_INSTALASI_MANDIRI_DAN_ROLE_2026-11-23.md)

## Teknologi target

- Backend: Laravel 13 / PHP 8.3.
- Database: MySQL 8+ / InnoDB.
- Web: Blade + Livewire.
- Aplikasi Android peserta: Kotlin, Jetpack Compose, Room, WorkManager, Retrofit.

## Aturan keselamatan

1. Jangan commit `.env`, kredensial, private key, dump DB, data siswa, atau backup.
2. Jangan menjalankan migration pada database produksi dari CI.
3. PHPUnit/CI hanya boleh memakai database disposable per job.
4. Fresh install dan upgrade adalah alur berbeda. Jangan menebak cohort database.
5. Jangan mengedit migration historis; strategi upgrade harus forward-only dan didokumentasikan.
6. Jangan menghapus bukti akademik, histori ujian, atau log audit.
7. Setiap perubahan source wajib diawali backup dan diverifikasi byte-exact.
8. Jangan merge PR, deployment, atau menjalankan migration produksi tanpa persetujuan eksplisit.
9. Device Lease/Fencing, integrasi, pemulihan, concurrency, dan uji operasional harus lulus sebelum ada klaim siap produksi.

## Pengembangan lokal

1. Salin `.env.example` menjadi `.env` dan isi konfigurasi database lokal madrasah sendiri.
2. Jalankan Composer sesuai prosedur proyek.
3. Gunakan database disposable khusus pengembangan/pengujian.
4. Fresh install dan upgrade dari versi lama wajib diuji terpisah.

## Status

Repositori ini adalah workspace source dan pengujian, **bukan bukti siap produksi atau siap didistribusikan**.
