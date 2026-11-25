# Laporan Perbaikan Web CBT — Import Siswa XLSX
Tanggal: 25 November 2026
Repository: `zane0184/cbt_november`
Branch: `work/ui-import-improvements-20261125`
Commit paket: `498c84348f5a20178997b06412badb1a79d3ae1b`
Base: `3df188b338a03b8513dfeea12d80b1201399be81`

## AUDIT

1. Portal Guru menggunakan komponen `App\\Livewire\\Admin\\QuestionBank` pada route `/guru/bank-soal`. Komponen dan tampilan yang aktif tidak memiliki kontrol import soal DOCX; tampilan hanya menyediakan pembuatan soal manual dan unggah media.
2. Portal Admin memiliki halaman Import Data Siswa dan service yang menerima XLSX maupun CSV. Namun tombol template yang ditampilkan hanya mengunduh CSV.
3. Service import membaca NISN sebagai teks dan menolak sel XLSX numerik agar angka nol di depan tidak hilang. Template XLSX perlu mengatur kolom identitas sebagai teks.
4. DOCX parser belum tersedia. Checkpoint parser sebelumnya menyatakan kontrak engine untuk shared stimulus dan TRUE_FALSE_GROUP belum ada; implementasi save-to-draft yang aman tidak boleh mengarang kontrak atau mengubah snapshot/scoring tanpa desain dan uji E2E.

## DESAIN

- XLSX menjadi format template utama Import Siswa; CSV tetap disediakan sebagai alternatif kompatibilitas.
- Workbook memuat sheet `Data Siswa` dan `Petunjuk`.
- Kolom NISN, tanggal lahir, dan telepon ditetapkan sebagai teks; contoh NISN memakai explicit string agar nol di depan tidak hilang.
- Tidak ada perubahan kontrak import, validasi tenant, idempotency, audit batch, transaksi, maupun database schema.
- DOCX tetap menjadi jalur utama Import Soal Guru, tetapi parser dan kontrak snapshot harus diselesaikan sebelum tombol import diaktifkan.

## IMPLEMENTASI

File aplikasi diubah:
- `backend-laravel/app/Livewire/Admin/StudentImport.php`: menambah `downloadXlsxTemplate()` dengan workbook dua sheet, header, lebar kolom, freeze pane, filter, dan format teks.
- `backend-laravel/resources/views/livewire/admin/student-import.blade.php`: tombol XLSX dijadikan unduhan utama; CSV tetap tersedia sebagai alternatif.
- `backend-laravel/tests/Feature/StudentImportTest.php`: menambah acceptance test untuk unduhan XLSX.

File dokumentasi:
- `backend-laravel/backups/ui-import-improvements-20261125/BACKUP_MANIFEST.md`
- Backup sebelum perubahan disimpan di `backend-laravel/backups/ui-import-improvements-20261125/`.

Workflow:
- Branch ini ditambahkan ke trigger workflow DOCX Import Parser CI dan Build Standalone Web CBT Deployment ZIP.
- Kedua workflow sebelum perubahan juga dibackup di direktori yang sama.

Tidak ada migration, perubahan database produksi, deployment VPS, atau perubahan source di branch utama.

## PENGUJIAN

- Build Standalone Web CBT Deployment ZIP: **PASS**, workflow run `45573588765`, exact commit `498c84348f5a20178997b06412badb1a79d3ae1b`.
- Artifact SHA-256: `6eba8e6b7e31643dd83f43db321f6febff616a067d975d09a0e350ada5603ac3`.
- Inner deployment ZIP SHA-256: `f1559f4b72cb2e5425c64ada010717aee3330d7c4809d70591d79b87d77a349e`.
- Outer dan inner ZIP: `unzip -t` PASS.
- SHA256SUMS deployment: 8.690 entri diperiksa, 0 kegagalan.
- Pemeriksaan isi paket: method `downloadXlsxTemplate` dan label `Unduh Template XLSX` ditemukan di source yang dikemas.
- DOCX Import Parser CI run `45573505811` pada commit `8eef083db46ed82ab3520d67f42701b053b1ab3b` menjalankan dua job PHP 8.3 dan PHP 8.4 dengan MySQL 8.0/InnoDB. PHP lint, Composer validation, clean-install installer E2E, Student Import acceptance tests, dan benchmark cleanup denial-path test seluruhnya berstatus **success** pada kedua job. Full PHPUnit suite masih berstatus **in_progress** saat laporan ini diperbarui; hasil akhir suite belum boleh dinyatakan PASS.
- Browser E2E/live UI dan hasil pemasangan VPS belum dijalankan.

## BATASAN / PEKERJAAN LANJUTAN

1. Uji acceptance XLSX dan full suite harus menunggu CI berjalan; jika antrean workflow tetap macet, jalankan melalui workflow_dispatch atau runner CI lain yang tersedia.
2. Import Soal DOCX belum diimplementasikan. Sebelum parser menyimpan data, perlu finalisasi kontrak shared stimulus, TRUE_FALSE_GROUP tiga pernyataan, payload jawaban, dan kebijakan penskoran; jangan aktifkan MATCHING sebelum E2E runtime.
3. Tampilan Admin/Guru lainnya masih perlu audit visual dan browser E2E. Perbaikan visual yang memerlukan preferensi penggunaan akan dilakukan bersama Owner setelah paket dipasang.
4. Paket ini adalah kandidat review UI, bukan rilis produksi.
