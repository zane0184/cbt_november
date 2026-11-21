# LAPORAN REVIEW FINAL — IMPORT MASSAL GURU DAN SISWA
Tanggal: 22 November 2026
Repository: zane0184/cbt_november
Branch: work/import-guru-siswa-implementation-20261122
Source diuji: a3522e1c8be02224ef9b369051105f05b2704e39
CI: https://github.com/zane0184/cbt_november/actions/runs/44944158355

## KESIMPULAN
REVIEW KODE: PASS pada cakupan delapan area yang diminta dan 14 suite CI MySQL 8.
Bukan pernyataan siap produksi. Belum dilakukan UI UAT, multi-process stress, filesystem staging di VPS, deployment, atau validasi operasional madrasah.
Tidak ada PR, merge, migration database produksi, atau deployment VPS.

## 1. AUDIT

### 1.1 Tautan aktivasi
Temuan awal: tautan aktivasi berada pada properti publik Livewire activationLinks.
Perbaikan:
- Properti publik activationLinks dihapus.
- Tautan hanya diserahkan melalui session flash setelah redirect.
- Render mengambil session flash dengan pull, sehingga payload tidak dipertahankan sebagai state publik Livewire dan hanya ditampilkan satu kali.
- Token database berupa hash SHA-256, bukan token rahasia plaintext.
- URL bertanda tangan, masa berlaku 48 jam, aktivasi memakai row lock, token ditandai used_at, dan token lain untuk akun yang sama dinonaktifkan.
- Halaman aktivasi mengirim Referrer-Policy no-referrer dan Cache-Control no-store/private.
Regression test membuktikan tidak ada properti publik activationLinks dan session flash dikonsumsi satu kali.

### 1.2 Transaksi batch, transaksi per baris, retry, dan deadlock
- Preview batch dibuat dalam transaksi, termasuk batch row, payload terenkripsi, dan audit preview.
- Konfirmasi memindahkan batch ke PROCESSING secara transaksional.
- Setiap baris diproses dalam transaksi sendiri dengan row lock dan retry transaksi hingga tiga kali.
- Baris yang sudah selesai tidak diproses ulang; batch PROCESSING dapat dilanjutkan setelah request terputus.
- Tenant row lock digunakan untuk menyerialisasi import dan mencegah race identity antara import dan pembuatan user manual.
- Status akhir dihitung ulang dari baris: COMPLETED atau PARTIAL; jumlah imported/skipped/failed diperbarui.
- Kegagalan satu baris tidak merusak baris lain yang sudah committed.
- Regression test MySQL 1213 menginjeksikan QueryException deadlock untuk memastikan retry transaksi dan tidak membuat akun ganda.
Batas pengujian: deadlock retry diuji melalui injeksi error kode 1213, bukan deadlock dua koneksi yang benar-benar dibuat bersamaan. Uji multi-process/load tetap terbuka.

### 1.3 Keamanan workbook
Sebelum PhpSpreadsheet memuat workbook:
- File maksimum 10 MB.
- Arsip maksimum 100 entry.
- Total expanded size maksimum 50 MB dan satu entry maksimum 30 MB.
- Jalur absolut, path traversal, backslash/path tidak aman ditolak.
- Hanya struktur XLSX yang diizinkan; komponen tambahan, macro, embedded object, koneksi eksternal ditolak.
- External relationship ditolak.
- DTD/ENTITY XML ditolak sebelum XML parser.
Setelah struktur arsip diperiksa:
- Nama dan jumlah sheet harus tepat sesuai template; sheet tambahan ditolak.
- Status hidden/visible harus sesuai.
- Formula ditolak pada semua sheet.
- Batas baris/kolom dan panjang isi sel diterapkan.
- Versi template dan header diverifikasi.
- Ekstensi dan MIME diperiksa di Livewire; struktur ZIP/XLSX diverifikasi lagi oleh service.
Regression test mencakup ZIP bomb, formula, external relationship, DTD/entity, sheet tambahan, file rusak, MIME/ekstensi salah, ukuran upload, versi/header, tanggal, dan isi sel.

### 1.4 Relasi Data Guru dan Penugasan Guru
- Data Guru diurutkan sebelum Penugasan Guru.
- Setiap baris memiliki transaksi sendiri.
- Pembuatan User, Role, TeacherProfile, dan activation token berada dalam transaksi baris Guru.
- Assignment baru dibuat setelah Guru tersedia pada tenant yang sama dan referensi subject/rombel/tahun/semester tervalidasi.
- Jika pembuatan Guru gagal, baris assignment gagal dan tidak meninggalkan assignment yatim.
Regression test memaksa kegagalan pembuatan Guru dan membuktikan tidak ada TeacherProfile atau TeacherSubjectAssignment parsial.

### 1.5 Retensi staging, enkripsi, riwayat, dan laporan
- Payload baris disimpan menggunakan Crypt::encryptString.
- encrypted_payload dan token_hash disembunyikan dari serialization model.
- File workbook asli tidak diarsipkan oleh service import sebagai file permanen; pemrosesan menggunakan temporary upload Livewire dan staging payload terenkripsi.
- Preview atau PROCESSING yang tidak diperbarui selama tujuh hari kedaluwarsa; payload dibersihkan, row belum selesai menjadi FAILED, batch menjadi EXPIRED, dan audit tetap disimpan.
- Riwayat/report dibatasi Admin Madrasah dengan permission user.manage pada tenant yang sama.
- Token aktivasi tidak ditampilkan dari riwayat; Admin pembuat batch dapat menerbitkan ulang sebelum akun diaktivasi.
Batas: retensi temporary upload filesystem belum diuji pada VPS; yang diuji adalah retensi payload staging di database disposable.

### 1.6 Pesan exception
- Exception tak terduga tidak lagi dikirim mentah ke Admin.
- Alasan gagal pada row memakai pesan aman dan generik.
- Log menyimpan konteks minimum seperti tenant, user, batch/row, dan kelas exception, bukan token rahasia atau payload mentah.
Regression test memastikan pesan gagal tidak menampilkan query/internal class detail.

### 1.7 Isolasi tenant
- Service memeriksa actor yang sedang login, user ID, tenant ID, tenant context, role ADMIN_MADRASAH, dan permission user.manage.
- Lock tenant aktif diterapkan pada operasi kritis.
- Batch, row, user, profile, rombel, tahun pelajaran, semester, subject, dan assignment dibatasi tenant.
- Foreign key tenant-aware yang tersedia tetap digunakan; tidak ada identitas lintas tenant yang diterima.
Regression test mencakup tenant context mismatch, cross-tenant reference, tenant isolation, dan preview race identity.

### 1.8 Batas Admin terhadap CBT
- Import hanya membuat akun Guru/Siswa dan profile yang sesuai.
- Import tidak menambahkan permission CBT pada Admin.
- Admin tidak diberi role GURU/SISWA melalui import.
- Service mensyaratkan ADMIN_MADRASAH + user.manage; modul QuestionBank/ExamManagement tetap menolak ADMIN_MADRASAH dan khusus Guru.
Regression test memastikan Admin tetap Admin setelah import.

## 2. DESAIN
Aturan bisnis dipertahankan:
- Guru PNS memakai NIP; Guru honorer memakai NIK; Siswa memakai NISN.
- NUPTK bukan identitas login.
- Data existing dilewati; duplikat dalam file ditolak; tidak ada auto-update.
- Template dibuat dari referensi tenant; preview dan konfirmasi eksplisit.
- Password tidak dicantumkan di workbook; aktivasi satu kali digunakan untuk menetapkan password.
- Tidak menambahkan unique constraint pada identitas lama tanpa preflight.

## 3. IMPLEMENTASI
Perubahan review final yang terverifikasi:
- ImportManagement.php: state activationLinks publik dihapus; handover session flash satu kali; sanitasi exception dan resume.
- ImportWorkbookService.php: retry transaksi, resume PROCESSING, heartbeat, per-row transaction, sanitasi failure, ZIP/XML hardening.
- ImportWorkbookValidationException.php: validasi workbook terkontrol.
- routes/console.php: command/scheduler retensi staging.
- ImportWorkbookServiceTest.php dan ImportDeadlockRetryTest.php: regression tests.
- Checkpoint dan backup manifest diperbarui.
- Backup berada pada docs/backup/import-review-final-2026-11-22/; setiap snapshot dan blob SHA tercatat di MANIFEST.md.
- Tidak ada perubahan identity unique constraint, tidak ada merge, tidak ada deployment.

## 4. PENGUJIAN
GitHub Actions run 44944158355: SUCCESS pada source commit a3522e1c8be02224ef9b369051105f05b2704e39.
- 14 suite.
- 93 test.
- 502 assertions.
- Semua PASS.
- PHP lint PASS.
- MySQL 8.0.46 disposable; 75 base tables; 0 non-InnoDB; required schema tersedia.
- Database dan user aplikasi disposable berhasil dibersihkan.
- Artifact evidence: wa01-alternative-runtime-44944158355-1, ID 14723455279.
- SHA-256 artifact: 680a4db83b9f51a004e623e747230ddbb5523cccfc6be7d1058d8e4051f540cf.
- Retensi artifact sampai 21 Desember 2026.

## 5. BATASAN / RISIKO TERSISA
- Tidak ada uji browser/UI manual oleh Admin Madrasah.
- Tidak ada uji deadlock dua koneksi yang benar-benar bersamaan; yang diuji adalah jalur retry MySQL error 1213 secara deterministik.
- Tidak ada uji beban multi-proses dan filesystem staging pada VPS.
- Tidak ada UAT sekolah, integrasi Android, atau deployment staging/VPS.
- Karena itu, PASS ini adalah PASS review kode dan CI terpilih, bukan status siap produksi.

## 6. KEPUTUSAN DAN TINDAK LANJUT
- Import Guru/Siswa selesai pada tahap REVIEW FINAL kode.
- Lanjutkan penyelesaian modul Web Admin lainnya pada branch terisolasi.
- Tahap berikutnya diprioritaskan: role consolidation dan penugasan Proktor sementara berbasis Guru aktif; kemudian lifecycle User, lifecycle Rombel Siswa, integrasi kurikulum, laporan dan administrasi.
- Tidak membuat PR, merge, menjalankan migration produksi, atau deployment VPS tanpa persetujuan eksplisit.
