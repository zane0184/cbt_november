# Perbaikan Registry CP Bahasa Indonesia Fase C — 2026-11-26

## AUDIT
- Pada source sebelum patch, `CurriculumRegistryBootstrapSeeder` membuat versi Kurikulum Merdeka, fase, jenjang, dan node mata pelajaran MI, tetapi tidak membuat baris `multi_learning_outcomes` bertipe CP.
- Untuk penugasan Fase C Bahasa Indonesia, `TeacherCurriculumContextService::forAssignment()` mencari CP pada node mata pelajaran `FASE_C_BAHASA_INDONESIA`, lalu hanya memberi status selectable bila sumber dan CP berstatus `ARTIFACT_CHECKSUM_VERIFIED` dan SHA-256 sama.
- Karena belum ada outcome CP dan belum ada checksum artefak BSKAP 046/H/KR/2025, import DOCX tidak menampilkan CP yang dapat dipilih.
- Portal Super Admin dan Android tidak diperlukan untuk perbaikan ini.

## DESAIN
- Sediakan one-off seeder yang hanya mengisi CP resmi Bahasa Indonesia Fase C (empat elemen) pada registry pusat/system tenant.
- Jangan membuat TP/ATP contoh. TP/ATP tetap dikembangkan Guru setelah CP tersedia.
- Sumber PDF resmi wajib diarsipkan pada `storage/app/curriculum-references/BSKAP_046_H_KR_2025.pdf`; seeder menghitung SHA-256 dari file tersebut dan mengikat checksum yang sama pada setiap CP.
- Seeder tidak dipanggil dari `DatabaseSeeder`; eksekusi harus eksplisit setelah file sumber disiapkan.
- Seeder sumber regulasi umum harus mempertahankan status/checksum artefak yang sudah diverifikasi saat dijalankan ulang.

## IMPLEMENTASI
- File baru: `backend-laravel/database/seeders/ImportBahasaIndonesiaFaseCCpSeeder.php`.
  - Memeriksa keberadaan, header PDF, ukuran minimum, dan SHA-256 file sumber.
  - Memastikan sumber, versi KMA-1503-2025, dan node Fase C Bahasa Indonesia ada.
  - Mengisi 4 CP: Menyimak; Membaca dan Memirsa; Berbicara dan Mempresentasikan; Menulis.
  - Menetapkan CP sebagai official/read-only, ACTIVE, versi efektif 16 Juli 2025, dengan provenance dan checksum sumber.
  - Idempotent untuk identitas node + kode; konflik identitas menghentikan transaksi.
- File diubah: `backend-laravel/database/seeders/CurriculumReferenceSourceSeeder.php`.
  - Backup: `backend-laravel/backups/curriculum-cp-phase-c-20261126/CurriculumReferenceSourceSeeder.php.before`.
  - Mempertahankan `ARTIFACT_CHECKSUM_VERIFIED`, `checksum_sha256`, `archive_path`, dan metadata artefak ketika seeder referensi dijalankan ulang.
- Tidak ada migration, perubahan schema, perubahan role, atau perubahan portal Super Admin/Android.
- Perubahan ini berada pada branch integrasi PR #38, belum di-merge ke main.

## CARA MENYIAPKAN DI VPS (SETELAH BACKUP)
1. Unduh PDF BSKAP 046/H/KR/2025 dari URL resmi yang tercantum pada file seeder.
2. Simpan sebagai `storage/app/curriculum-references/BSKAP_046_H_KR_2025.pdf`.
3. Backup database sebelum seeding.
4. Upload file seeder baru dan file `CurriculumReferenceSourceSeeder.php` yang diperbarui; simpan backup file lama.
5. Jalankan hanya:
   `php artisan db:seed --class=Database\\Seeders\\ImportBahasaIndonesiaFaseCCpSeeder`
6. Jangan menjalankan seeder ini sebelum PDF yang benar diarsipkan. Jangan jalankan `db:seed` global untuk pekerjaan ini.

## PENGUJIAN
- Pemeriksaan source seeder dan skema model dilakukan; field yang dipakai tersedia pada model/migrasi.
- Belum dijalankan terhadap database VPS pengguna.
- Belum ada bukti browser bahwa CP muncul pada halaman import DOCX; harus diverifikasi setelah seeder dijalankan.
- CI PHP 8.3 dan build ZIP perlu diperiksa pada commit terbaru sebelum dinyatakan lolos.
- Tidak ada database/VPS pengguna yang diakses atau diubah.
