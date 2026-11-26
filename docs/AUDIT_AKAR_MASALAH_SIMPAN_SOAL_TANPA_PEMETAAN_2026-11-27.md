# AUDIT AKAR MASALAH PENYIMPANAN SOAL TANPA PEMETAAN KURIKULUM
Tanggal: 27 November 2026
Repository: `zane0184/cbt_november`
Branch: `fix/manual-question-optional-curriculum-20261127`
PR: #44 (tetap OPEN / DRAFT; tidak di-merge)

## Ringkasan
Ditemukan defect pada layanan bersama `QuestionVersionCurriculumMappingService::sync()`. Kondisi CP dan TP/ATP kosong memang dikenali sebagai `NOT_MAPPED`, tetapi pencarian `TenantCurriculumRegistration` dilakukan sebelum cabang `NOT_MAPPED`. Karena itu, penyimpanan soal tanpa pemetaan tetap gagal jika registrasi kurikulum pada penugasan tidak tersedia/tidak cocok. Exception terjadi di dalam transaksi pemanggil sehingga insert soal, versi, opsi, dan batch DOCX ikut rollback.

## Bukti source
1. `backend-laravel/app/Livewire/Admin/QuestionBank.php`
   - Validasi `selectedAssignmentId` dan `selectedSubjectId` masih wajib.
   - Validasi `selectedCpIds` dan `selectedOutcomeIds` nullable.
   - Setelah membuat/memperbarui soal dan opsi, komponen tetap memanggil `QuestionVersionCurriculumMappingService::sync()` walaupun kedua array kosong.
2. `backend-laravel/app/Services/Questions/DocxQuestionDraftImporter.php`
   - Kondisi kedua array kosong diperlakukan sebagai `NOT_MAPPED`.
   - Importer tetap memanggil layanan mapping untuk menyimpan keadaan tanpa pemetaan.
3. `backend-laravel/app/Services/TeacherPortal/QuestionVersionCurriculumMappingService.php` sebelum patch
   - Assignment Guru divalidasi.
   - Query `TenantCurriculumRegistration::...->firstOrFail()` dijalankan sebelum `if (!$isMapped)`.
   - Dengan demikian, cabang tanpa mapping tidak pernah bebas dari ketergantungan registrasi kurikulum.

## AUDIT
- Akar defect code-level terkonfirmasi: lookup registrasi kurikulum mendahului early return `NOT_MAPPED`.
- Penugasan Guru dan mata pelajaran tetap harus tervalidasi untuk menjaga otorisasi, tenant scope, dan subject ownership.
- CP/TP/ATP tidak boleh menjadi prasyarat penyimpanan draft.
- Belum dapat memastikan apakah ini satu-satunya penyebab kegagalan pada VPS pengguna karena versi file yang sedang terpasang dan pesan/log error VPS belum diperiksa.
- Pengujian PHPUnit/runtime/browser belum dijalankan. Checkpoint terdahulu melarang menjalankan tes/CI/runtime sebelum instruksi pengguna; perubahan ini baru diverifikasi lewat diff source GitHub.

## IMPLEMENTASI
Perubahan minimum dilakukan hanya pada:
`backend-laravel/app/Services/TeacherPortal/QuestionVersionCurriculumMappingService.php`

- Memindahkan query `TenantCurriculumRegistration` ke bawah cabang `if (!$isMapped)`.
- Bila CP dan TP/ATP kosong, layanan tetap memvalidasi Guru dan penugasan aktif, menghapus mapping lama untuk versi tersebut, mencatat audit `NOT_MAPPED`, lalu return tanpa mensyaratkan registrasi kurikulum.
- Bila CP/TP/ATP dipilih, validasi registrasi, CP resmi, TP/ATP terpublikasi, dan struktur kurikulum tetap berjalan seperti sebelumnya.
- Tidak menghapus modul kurikulum, tabel, histori, registry, CP, TP, ATP, KBC, atau Pembelajaran Mendalam dari sistem.

## BACKUP
- Backup dibuat sebelum perubahan:
  `backend-laravel/backups/curriculum-mapping-optional-20261127/QuestionVersionCurriculumMappingService.php`
- SHA blob backup/original: `bcb9a2722c013b9216b6663f7693b58f2d310e16`
- SHA blob source hasil patch: `e259e1b44206524926a2c67a557b6addb36a1ce8`
- Backup berhasil dibaca ulang dan SHA blob-nya sama dengan SHA source sebelum patch.

## PENGUJIAN
- Diff commit diperiksa: hanya memindahkan lookup registrasi dari sebelum cabang NOT_MAPPED menjadi sesudah cabang tersebut.
- Pemeriksaan ulang source dan backup GitHub: PASS.
- PHPUnit, Livewire browser test, DOCX import integration test, dan tes VPS: BELUM DIJALANKAN.
- Belum ada bukti bahwa tombol Simpan pada VPS pengguna sudah berhasil.

## BATAS DAN LANGKAH BERIKUTNYA
1. Pastikan VPS menggunakan commit patch ini, bukan source lama.
2. Uji manual save tanpa CP/TP/ATP dengan assignment Guru aktif.
3. Uji DOCX preview lalu import tanpa CP/TP/ATP.
4. Pastikan soal, versi, opsi, audit log, dan batch import tersimpan; pastikan mapping berstatus NOT_MAPPED.
5. Uji negatif: assignment tidak sah/lintas tenant tetap ditolak.
6. Jika masih gagal, kumpulkan pesan validasi Livewire dan log Laravel untuk melacak error berikutnya.
7. Jangan jalankan migration produksi. Tidak ada migration pada patch ini.
8. Jangan menghapus kurikulum global; perbaikan ini hanya memisahkan penyimpanan soal dari kewajiban pemetaan.
