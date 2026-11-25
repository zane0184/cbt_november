# Checkpoint — Import Guru dan Kredensial Import
Tanggal: 2026-11-26

- Repository source: `zane0184/cbt_november`.
- Branch implementasi aktif: `feature/admin-teacher-import-20261126-r1`.
- PR: [#27 — Import Guru Web Admin](https://github.com/zane0184/cbt_november/pull/27), status draft/open, belum merge.
- Source target branch: `work/guru-docx-import-e2e-20261125`. Common base branch fitur `bc3d9a497a2775fcbeda9deed57ee05bcf53e2e8`; terdapat tiga commit upstream non-overlap setelahnya yang mengubah alur DOCX/question bank/tests/backups. Periksa status PR dan base/head terkini sebelum merge.

## Status pekerjaan
- Import Guru: service, komponen, view, route, migration batch, dan feature test sudah ditambahkan.
- Import Guru: PNS/PPPK login memakai NIP 18 digit; Honorer memakai NIK 16 digit; NUPTK tambahan.
- Import Siswa: mekanisme impor yang sudah ada dipertahankan; hasil kredensial sekarang dikembalikan oleh service setelah batch berhasil.
- Kedua service membuat password acak 24 karakter dan hanya menyimpan hash di database.
- Setelah impor, Admin diarahkan ke `/admin/users`; panel sekali tampil memuat nama, identitas login, jenis identitas, dan password awal.
- Halaman otomatis memfilter GURU/SISWA sesuai batch, dapat mencari NIP/NIK/NISN, dan mengunduh kredensial dalam format XLSX saja.
- Menutup panel menghapus kredensial dari state Livewire. Password plaintext tidak disimpan permanen dan tidak bisa dipulihkan; gunakan Reset Password jika daftar sudah ditutup.
- Template input tetap mendukung XLSX dan CSV sesuai fitur import yang ada; file hasil unduhan kredensial wajib XLSX.
- Backup seluruh file yang diedit tersedia di `docs/backup/TEACHER_IMPORT_20261126/`.
- Migration batch Guru belum dijalankan di VPS/database produksi.
- Tidak ada perubahan CBT Engine atau kurikulum.

## Pengujian
- Tes baru memeriksa pemetaan NISN/NIP/NIK melalui `LoginIdentityResolver`, verifikasi password hash, login Web, dan ekspor XLSX.
- GitHub Actions `Tenant Backend CI` telah dipicu pada branch fitur. Catat run ID, job, dan hasil akhir setelah selesai.
- Jangan klaim PASS sampai hasil workflow dan test suite benar-benar selesai.
- Tidak ada test yang dijalankan terhadap database produksi.

## Langkah berikutnya
1. Ambil status workflow `Tenant Backend CI` terbaru dan periksa job/log yang gagal.
2. Perbaiki seluruh kegagalan dan jalankan ulang CI.
3. Periksa ulang route, redirect session flash, panel kredensial, file XLSX, dan login NIP/NIK/NISN.
4. Bandingkan PR #27 dengan source branch terkini; jangan merge bila ada konflik atau test gagal.
5. Setelah lolos, update laporan/checkpoint dengan SHA dan bukti workflow. Deployment/migration tetap memerlukan prosedur dan persetujuan tersendiri.
