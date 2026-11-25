# Checkpoint — Import Guru dan Kredensial Otomatis
Tanggal: 2026-11-26

## Posisi source
- Repository: `zane0184/cbt_november`.
- Source branch: `work/guru-docx-import-e2e-20261125`.
- PR #27: **merged** — https://github.com/zane0184/cbt_november/pull/27
- Merge commit/source head saat checkpoint: `99da122eeb7081bd82ac8b6e26c3558c07a528d1`.
- Feature branch: `feature/admin-teacher-import-20261126-r1` (sudah diintegrasikan melalui PR #27).

## Status implementasi
- Import Guru sudah ditambahkan pada Web Admin Madrasah.
- Guru PNS/PPPK menggunakan NIP 18 digit untuk login; Guru Honorer menggunakan NIK 16 digit; NUPTK opsional.
- Import Siswa yang ada dipertahankan; service sekarang mengembalikan kredensial hasil impor.
- Password awal acak 24 karakter; database hanya menyimpan hash.
- Setelah impor sukses, Admin diarahkan ke Manajemen Pengguna dan melihat daftar akun batch beserta identitas login serta password awal satu kali.
- Unduhan daftar akun wajib XLSX: `nama`, `identitas_login`, `jenis_identitas`, `password_awal`.
- Halaman otomatis memfilter GURU/SISWA sesuai hasil impor dan pencarian mencakup NIP/NIK/NISN.
- Menutup panel/sesi menghapus akses ke password awal. Jika diperlukan, gunakan Reset Password.
- Template Import Siswa sekarang satu worksheet Data Siswa agar sesuai `XlsxPackagePreflight`.
- Backup sebelum setiap perubahan tersedia di `docs/backup/TEACHER_IMPORT_20261126/`.

## Pengujian
- GitHub Actions Tenant Backend CI run #2307: SUCCESS.
- SHA yang diuji: `c16cec6b6e5a2c30320236906d6fc8edd2ec85f6`.
- PHP lint: PASS.
- Migration/login identity MySQL 8.0.46 disposable: PASS.
- PHPUnit: **681 passed, 3.598 assertions, 206,43 detik**.
- Tes mencakup login Web dan API untuk NISN/NIP/NIK, hash password, panel kredensial, ekspor XLSX, dan template satu worksheet.
- Tidak ada migration atau test pada database produksi/VPS.

## Batasan dan langkah berikutnya
1. Source sudah merged; fitur baru belum dipasang ke VPS melalui tahap deployment terkontrol.
2. Migration `2026_11_26_010001_create_teacher_import_batches_table.php` belum dijalankan pada VPS/produksi. Sebelum menjalankan: rencana migration, backup terverifikasi, preflight, dan persetujuan eksplisit.
3. Setelah source dan migration terpasang, Admin Madrasah dapat melakukan uji impor nyata Guru dan Siswa lalu menggunakan XLSX untuk login Web/API/Android.
4. Tidak perlu mengulang pengujian instalasi VPS yang sudah pengguna nyatakan berhasil; fokus berikutnya adalah memasang perubahan fitur baru dan validasi fungsinya.
