# Addendum Audit — Media Authorization dan Early-Close
Tanggal: 25 November 2026
Repository: `zane0184/cbt_november`
Branch: `work/coordinator-web-cbt-p0-20261125`
Source SHA yang diperiksa: `08b591624fef7cd9f62186453ab977fcee07ca12`

## Pemeriksaan
- `ExamController::close()` ditemukan sebagai method, tetapi tidak ditemukan route API yang mengeksposnya pada `routes/api.php`.
- Tidak ditemukan action close aktif pada komponen Livewire Exam Management yang diperiksa.
- `ExamAttemptController::store()` membatasi expiry Attempt agar tidak melewati `exam.end_at`.
- Method `ExamController::archive()` juga tidak ditemukan sebagai route aktif dalam pemeriksaan ini.

## Kesimpulan
Belum ada bukti jalur operasional early-close yang dapat menutup ujian sementara Attempt tetap sah dan belum expired. Karena itu, filter status pada `MediaController` tidak diubah. Mengubah filter tanpa jalur produk dan test yang dapat direproduksi akan menjadi patch spekulatif.

`MediaController.php` dan `ExamSessionHardeningTest.php` sudah dibackup sebelum audit, tetapi tidak diubah. Jika early-close route/action ditambahkan pada tahap berikutnya, wajib dibuat integration test:
1. Siswa memulai Attempt dan mendapat lease sah.
2. Exam ditutup lebih awal oleh aktor berotorisasi.
3. Attempt yang masih IN_PROGRESS dan belum expired tetap dapat mengambil media yang direferensikan snapshot.
4. Attempt expired/invalid, tenant mismatch, user mismatch, lease stale, dan media yang tidak direferensikan tetap ditolak.

Tidak ada perubahan database atau VPS pada audit ini.
