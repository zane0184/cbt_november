# Laporan Implementasi — Import Guru dan Kredensial Otomatis Guru/Siswa
Tanggal: 2026-11-26

## Ringkasan
PR #27 telah digabungkan ke source branch `work/guru-docx-import-e2e-20261125` melalui squash merge.
- PR: https://github.com/zane0184/cbt_november/pull/27
- Merge commit: `99da122eeb7081bd82ac8b6e26c3558c07a528d1`
- Branch implementasi sebelumnya: `feature/admin-teacher-import-20261126-r1`
- Head source setelah merge: `99da122eeb7081bd82ac8b6e26c3558c07a528d1`

## AUDIT
- Import Siswa sebelumnya membuat password acak dan menyimpannya dalam hash, tetapi tidak mengembalikan password awal kepada Admin.
- Source login resmi menggunakan NISN untuk Siswa, NIP 18 digit untuk Guru PNS/PPPK, dan NIK 16 digit untuk Guru Honorer. `LoginIdentityResolver` mengikat identitas ke tenant instalasi lokal.
- Password hash tidak dapat dibaca kembali. Kredensial plaintext karena itu hanya tersedia pada hasil transaksi dan sesi Admin sekali tampil, bukan disimpan permanen di tabel atau audit log.
- Manajemen Pengguna sebelumnya belum mencari berdasarkan NIP/NIK/NISN.
- Template XLSX Import Siswa sebelumnya memiliki dua worksheet, sedangkan `XlsxPackagePreflight` hanya menerima satu worksheet.

## DESAIN
- Setelah batch berhasil, service mengembalikan daftar kredensial sekali tampil: nama, identitas login, jenis identitas, dan password awal.
- Password awal dibuat acak 24 karakter; database hanya menyimpan hash password.
- Komponen Import Guru/Siswa mengarahkan Admin ke `/admin/users` melalui flash session.
- Halaman Manajemen Pengguna otomatis memfilter role GURU/SISWA sesuai hasil impor, menampilkan semua kredensial batch, dan menyediakan **Unduh Daftar Akun (.xlsx)**.
- XLSX berisi kolom `nama`, `identitas_login`, `jenis_identitas`, `password_awal`; semua nilai ditulis sebagai teks agar leading zero tidak hilang.
- Tombol tutup menghapus kredensial dari state Livewire. Setelah ditutup/sesi berakhir, password awal tidak dapat dipulihkan; gunakan Reset Password untuk menerbitkan password baru.
- Pencarian Manajemen Pengguna mencakup nama, username, email, NIP, NIK, dan NISN.
- Unduhan kredensial hanya XLSX. CSV tetap dapat diterima sebagai format masukan impor.
- Template Import Siswa menjadi satu worksheet Data Siswa; petunjuk pengisian tersedia pada halaman impor. Baris contoh harus diganti/dihapus sebelum upload.

## IMPLEMENTASI
- Import Guru: service, Livewire component, view, route `/admin/teachers/import`, migration batch, dan feature tests.
- Guru PNS/PPPK: login dengan NIP 18 digit. Guru Honorer: login dengan NIK 16 digit. NUPTK opsional.
- Import Siswa: service mempertahankan proses impor dan mengembalikan kredensial sekali tampil setelah transaksi sukses.
- User Management: panel hasil impor, filter role otomatis, pencarian identitas, ekspor XLSX, dan tombol menghapus tampilan kredensial.
- Kedua service membuat akun dengan role yang tepat dan profil terkait dalam transaksi batch; password hash tersimpan di database.
- Preview/validasi, duplikasi, idempotency, audit log, tenant/admin authorization, dan rollback tetap berlaku.
- Migration `teacher_import_batches` ditambahkan ke source tetapi belum dijalankan pada VPS/database produksi.
- Tidak ada perubahan CBT Engine atau model kurikulum.

Backup seluruh file yang diubah tersedia di `docs/backup/TEACHER_IMPORT_20261126/`. Indeks: `docs/backup/TEACHER_IMPORT_20261126/ADDITIONAL_BACKUPS.md`; manifest utama: `MANIFEST.md`.

## PENGUJIAN
<badge color="success">PASS — GitHub Actions Tenant Backend CI</badge>

- Run #2307: https://github.com/zane0184/cbt_november/actions/runs/45710710834
- SHA yang diuji setelah sinkronisasi source terkini: `c16cec6b6e5a2c30320236906d6fc8edd2ec85f6`
- PHP 8.3 lint aplikasi, migration, dan test: PASS.
- Isolasi database uji dan pemeriksaan migration/login identity pada MySQL 8.0.46 disposable: PASS.
- PHPUnit lengkap: **681 passed, 3.598 assertions, 206,43 detik**.
- Termasuk login Web dan API untuk Siswa (NISN), Guru ASN (NIP), Guru Honorer (NIK), verifikasi hash password, tampilan kredensial sekali tampil, unduhan XLSX, dan preflight template Import Siswa satu worksheet.
- Tidak ada pengujian atau migration pada database produksi/VPS pada tahap ini.

## STATUS
Implementasi sudah digabungkan ke source branch dan lulus CI pada branch yang sudah diselaraskan dengan source terkini. Fitur siap untuk tahap deployment terkontrol. Migration `teacher_import_batches` tetap memerlukan backup produksi, preflight, dan persetujuan sebelum dijalankan pada VPS.
