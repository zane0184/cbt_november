# Checkpoint — Import Guru dan Kredensial Import
Tanggal: 2026-11-26

- Repository: `zane0184/cbt_november`.
- Branch implementasi: `feature/admin-teacher-import-20261126-r1`.
- PR: [#27 — Import Guru Web Admin](https://github.com/zane0184/cbt_november/pull/27), sebelumnya draft/open dan belum merge saat checkpoint ini disusun.
- Source target branch terakhir diperiksa: `work/guru-docx-import-e2e-20261125` @ `2dd2baaa891e01d2595df4f4aa586c1c9254f301`. Upstream bergerak 4 commit sejak common base branch fitur; perubahan non-overlap pada DOCX/Question Bank. Koreksi syntax test upstream diselaraskan ke branch fitur.
- Head branch fitur yang diuji: `4533367434593304a700f66c855d603af5370cc5`.

## Status pekerjaan
- Import Guru: service, komponen, view, route, migration batch, dan feature test tersedia.
- Import Guru: PNS/PPPK login memakai NIP 18 digit; Honorer memakai NIK 16 digit; NUPTK opsional.
- Import Siswa: alur impor dipertahankan; hasil kredensial dikembalikan oleh service setelah batch sukses.
- Kedua service membuat password acak 24 karakter dan hanya menyimpan hash di database.
- Setelah impor, Admin diarahkan ke `/admin/users`; panel sekali tampil memuat nama, identitas login, jenis identitas, dan password awal.
- Halaman otomatis memfilter GURU/SISWA sesuai batch, dapat mencari NIP/NIK/NISN, dan mengunduh kredensial dalam format XLSX saja.
- Menutup panel menghapus kredensial dari state Livewire. Password plaintext tidak disimpan permanen dan tidak bisa dipulihkan; gunakan Reset Password jika daftar sudah ditutup.
- Template Import Siswa kini satu worksheet Data Siswa agar cocok dengan XlsxPackagePreflight; petunjuk pengisian ada di halaman impor.
- Template input tetap mendukung XLSX dan CSV; file hasil unduhan kredensial wajib XLSX.
- Backup seluruh file yang diedit tersedia di `docs/backup/TEACHER_IMPORT_20261126/`.
- Migration batch Guru belum dijalankan di VPS/database produksi.
- Tidak ada perubahan CBT Engine atau kurikulum.

## Pengujian
- GitHub Actions [Tenant Backend CI run #2305](https://github.com/zane0184/cbt_november/actions/runs/45709784302) pada SHA `4533367434593304a700f66c855d603af5370cc5`: **success**.
- PHP lint: PASS.
- Pemeriksaan migration/login identity pada MySQL 8.0.46 disposable: PASS.
- PHPUnit lengkap: **678 passed, 3.564 assertions, 187,57 detik**.
- Test menguji login Web/API untuk NISN, NIP, NIK; Hash password; tampilan daftar kredensial; unduhan XLSX; dan template Import Siswa satu worksheet.
- Tidak ada test pada database produksi atau VPS pada tahap ini.

## Langkah berikutnya
1. Segarkan metadata PR #27 terhadap source branch terkini dan pastikan mergeability.
2. Tandai PR siap review dan integrasikan perubahan source jika tidak ada konflik.
3. Setelah merge, migration `teacher_import_batches` tetap memerlukan backup produksi, preflight, dan persetujuan sebelum dijalankan pada VPS.
4. Admin Madrasah dapat menguji impor nyata setelah source dan migration terpasang; gunakan kredensial XLSX hasil impor untuk uji login Web/Android.
