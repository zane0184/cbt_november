# CHECKPOINT — PERBAIKAN PENYIMPANAN SOAL TANPA CP/TP/ATP
Tanggal: 27 November 2026

## Posisi
- Repository: `zane0184/cbt_november`
- Branch: `fix/manual-question-optional-curriculum-20261127`
- PR #44 tetap OPEN / DRAFT / NOT MERGED.
- Pekerjaan scoring/publication audit tetap ditunda sesuai prioritas pengguna.
- Fokus saat ini hanya tombol Simpan soal manual dan impor DOCX.

## Temuan baru
Source PR #44 mengandung defect bersama: `QuestionVersionCurriculumMappingService::sync()` mengambil `TenantCurriculumRegistration` sebelum cabang NOT_MAPPED. Karena itu, draft tanpa CP dan TP/ATP masih dapat gagal jika registrasi kurikulum tidak tersedia.

## Implementasi terbaru
- Backup source dibuat:
  `backend-laravel/backups/curriculum-mapping-optional-20261127/QuestionVersionCurriculumMappingService.php`
- Source diperbaiki:
  `backend-laravel/app/Services/TeacherPortal/QuestionVersionCurriculumMappingService.php`
- Commit backup: `5e4694d57884126ba837b16f26cfffbadbb73ab5`
- Commit patch: `2a315b64a3299f74d2e394b287a749152d11d371`
- SHA blob sebelum patch: `bcb9a2722c013b9216b6663f7693b58f2d310e16`
- SHA blob setelah patch: `e259e1b44206524926a2c67a557b6addb36a1ce8`

## Perubahan perilaku
- CP/TP/ATP kosong: tetap memerlukan Guru, tenant, assignment aktif, dan subject yang sah; tidak lagi memerlukan TenantCurriculumRegistration.
- CP/TP/ATP dipilih: validasi kurikulum resmi tetap wajib.
- Tidak ada tabel atau data kurikulum yang dihapus.
- Tidak ada migration atau perubahan VPS/database.

## Status verifikasi
- Backup source cocok dengan SHA source asli: PASS.
- Diff source hanya memindahkan lookup registrasi ke cabang mapped: PASS.
- PHPUnit/Livewire/DOCX integration/VPS test: NOT RUN.
- Keberhasilan tombol Simpan belum boleh diklaim.

## Langkah berikutnya
1. Sinkronkan source patch terbaru ke staging VPS (backup backend dan database terlebih dahulu).
2. Uji simpan soal manual dengan assignment aktif dan CP/TP/ATP kosong.
3. Uji import DOCX dengan assignment aktif dan CP/TP/ATP kosong.
4. Catat hasil, pesan validasi, dan `storage/logs/laravel.log`.
5. Jika gagal, lanjut audit berdasarkan error aktual; jangan menghapus modul kurikulum global.
6. Setelah dua alur simpan lulus, baru lanjut audit scoring/publication sesuai arahan pengguna.
