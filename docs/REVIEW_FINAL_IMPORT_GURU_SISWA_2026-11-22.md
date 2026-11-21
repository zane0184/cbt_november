# REVIEW FINAL — IMPORT GURU DAN SISWA

Tanggal: 22 November 2026  
Repository: zane0184/cbt_november  
Branch: work/import-guru-siswa-implementation-20261122  
Source code yang diuji: a3522e1c8be02224ef9b369051105f05b2704e39  
GitHub Actions: [#44944158355 — SUCCESS](https://github.com/zane0184/cbt_november/actions/runs/44944158355)

## Kesimpulan

**PASS untuk delapan area review yang diminta**, dengan batasan yang dicatat pada bagian Pengujian. Ini bukan pernyataan siap produksi. Tidak ada PR, merge, deployment VPS, migration produksi, atau perubahan database produksi.

## AUDIT

### 1. Tautan aktivasi
- Secret URL tidak disimpan dalam properti publik Livewire.
- Link diserahkan melalui session flash setelah redirect; render mengambilnya satu kali dengan `session()->pull()`.
- Token disimpan sebagai hash, signed URL memiliki masa berlaku, token dikonsumsi satu kali, dan token lain untuk akun yang sama dinonaktifkan saat aktivasi.
- Respons halaman aktivasi memakai no-store dan no-referrer.
- Pembuatan ulang link dibatasi ke Admin pembuat batch.

### 2. Transaksi batch, baris, retry, dan deadlock
- Batch berpindah ke `PROCESSING` sebelum baris diproses.
- Setiap baris diproses dalam transaksi tersendiri; pembuatan akun/profil/assignment dan perubahan status baris bersifat atomik.
- Transaksi baris menggunakan retry sampai tiga kali untuk error concurrency/deadlock.
- Batch yang terputus dapat dilanjutkan oleh Admin pembuatnya dari UI.
- Status batch dihitung kembali dari status baris; batch `PREVIEW` atau `PROCESSING` yang tidak aktif selama tujuh hari kedaluwarsa.
- Pembaruan `updated_at` pada tiap baris yang berhasil menjadi heartbeat untuk mencegah batch aktif dianggap terbengkalai.

### 3. Keamanan workbook
Pemeriksaan dilakukan sebelum PhpSpreadsheet memuat workbook:
- Batas file 10 MB, jumlah ZIP entry, ukuran hasil ekstraksi, dan ukuran maksimum per entry.
- Path dan daftar komponen ZIP di-allowlist; komponen tambahan/embedded yang tidak didukung ditolak.
- External relationship/link, macro, OLE, DTD/ENTITY XML ditolak.
- Formula ditolak pada semua sheet, termasuk sheet tersembunyi.
- Nama sheet, status visible/hidden, batas baris/kolom, dan panjang isi sel diperiksa.
- Validasi MIME dan ekstensi XLSX tetap berlaku.

### 4. Data Guru dan Penugasan Guru
Data Guru diproses lebih dahulu. Jika pembuatan Guru gagal, baris Penugasan Guru terkait gagal dan tidak meninggalkan assignment yatim. Validasi referensi tenant, rombel, mapel, semester, dan tahun akademik dilakukan ulang saat konfirmasi.

### 5. Retensi dan enkripsi staging
- Payload baris memakai enkripsi Laravel `Crypt`; field sensitif tidak ikut serialization model.
- Payload dibersihkan setelah baris selesai, pembatalan, atau kedaluwarsa.
- Staging `PREVIEW` dan `PROCESSING` yang tidak aktif tujuh hari ditandai `EXPIRED`; baris yang belum diproses ditandai gagal dengan alasan yang aman.
- Riwayat, ringkasan laporan, dan audit tetap tersedia dalam tenant yang sama.
- Workbook asli tidak disimpan sebagai arsip permanen oleh service; temporary upload dikelola oleh mekanisme Livewire. Pembersihan filesystem live belum diuji di VPS.

### 6. Pesan exception
Exception internal tidak ditampilkan mentah kepada Admin. Pesan operasional dibuat generik; validasi workbook hanya menampilkan pesan yang telah dikendalikan. Detail teknis minimum dicatat ke log.

### 7. Isolasi tenant
Aktor yang sedang login, user ID argumen, tenant ID user, dan TenantContext harus cocok. Query batch, baris, referensi akademik, laporan, dan audit menggunakan tenant yang sama. Ada regression test untuk tenant context mismatch dan referensi lintas tenant.

### 8. Batas Admin terhadap CBT
Import mensyaratkan role `ADMIN_MADRASAH` dan permission `user.manage`. Fitur tidak menambahkan role Guru/Siswa, permission ujian, atau hak CBT ke akun Admin. Batas akses CBT Admin tetap diuji oleh `AdminCbtBoundaryTest`.

## DESAIN DAN ATURAN BISNIS

Aturan yang disepakati tidak diubah:
- Guru PNS login menggunakan NIP.
- Guru honorer login menggunakan NIK.
- Siswa login menggunakan NISN.
- NUPTK adalah data pendukung, bukan identifier login.
- Identitas disimpan sebagai teks agar angka nol di depan tidak hilang.
- Data baru diimpor; data existing dilewati; duplikat dalam file ditolak; tidak ada auto-update.
- Password tidak dicantumkan dalam workbook; akun baru memakai aktivasi satu kali.
- Tidak ditambahkan unique constraint pada NIP, NIK, NUPTK, NIS, atau NISN. Tidak ada migration baru pada patch review final.

## IMPLEMENTASI REVIEW

File yang disentuh pada patch review:
- `backend-laravel/app/Livewire/Admin/ImportManagement.php`
- `backend-laravel/app/Services/Import/ImportWorkbookService.php`
- `backend-laravel/resources/views/livewire/admin/import-management.blade.php`
- `backend-laravel/routes/console.php`
- `backend-laravel/tests/Feature/ImportWorkbookServiceTest.php`
- `backend-laravel/tests/Feature/ImportDeadlockRetryTest.php`
- `.github/workflows/wa01-alternative-isolated-runtime.yml`

Tidak ada perubahan source baseline/ZIP asli. Backup sebelum setiap patch dan blob SHA dicatat di `docs/backup/import-review-final-2026-11-22/MANIFEST.md`.

## PENGUJIAN

GitHub Actions #44944158355:
- **93 test, 502 assertion — seluruhnya PASS.**
- PHP lint: PASS.
- MySQL 8.0.46 disposable + PHP 8.3.
- 75 base table setelah migrasi; 0 tabel non-InnoDB.
- Database dan akun aplikasi disposable dibersihkan setelah tes.
- Artifact runtime: `wa01-alternative-runtime-44944158355-1.zip`, ID `14723455279`, SHA-256 `680a4db83b9f51a004e623e747230ddbb5523cccfc6be7d1058d8e4051f540cf`.

Cakupan deadlock: regression test menyuntikkan `QueryException` dengan kode MySQL 1213 untuk menguji retry transaksi tingkat atas tanpa transaksi pembungkus. Ini menguji jalur retry aplikasi, tetapi **belum** merupakan stress test deadlock multi-koneksi yang dibentuk oleh dua transaksi MySQL bersamaan.

Belum dilakukan uji UI browser manual, stress multi-proses, validasi filesystem staging di VPS, deployment staging/VPS, dan UAT madrasah.

## STATUS AKHIR

Review final Import: **LULUS pada cakupan yang diminta**.  
Status produksi: **BELUM SIAP PRODUKSI**.  
Merge, PR, migration produksi, dan deployment tetap menunggu persetujuan eksplisit.
