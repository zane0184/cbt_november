# CHECKPOINT IMPLEMENTASI IMPORT MASSAL GURU DAN SISWA — 2026-11-22

## AUDIT
- Repository: `zane0184/cbt_november`.
- Baseline pembanding: `d7e2034ede3b044fc4ab0b900335eff3669b41ed` (branch Web Admin Package 1).
- Branch implementasi terisolasi: `work/import-guru-siswa-implementation-20261122`.
- Source aktual menegaskan login tunggal: Guru PNS memakai NIP, Guru honorer memakai NIK, Siswa memakai NISN. NUPTK hanya data pendukung, bukan identitas login.
- `TeacherProfile` tidak memiliki kolom status kepegawaian, tempat/tanggal lahir, atau alamat; kategori PNS/HONORER dipakai untuk validasi workbook, bukan menambah kolom profil yang tidak tersedia.
- `StudentProfile` menyimpan NIS/NISN, nama, gender, tempat/tanggal lahir, orang tua/wali, telepon, alamat. Tahun pelajaran dan rombel direlasikan melalui data Rombel.
- Penugasan Guru menggunakan relasi tenant yang sudah ada (mata pelajaran, rombel, tahun pelajaran, semester).
- Tidak ada unique constraint baru untuk identitas Guru/Siswa; migrasi baru hanya menambah tabel batch/row/tenant import lock serta kolom payload terenkripsi untuk fitur import.
- Model `User` tidak menggunakan SoftDeletes; skenario uji soft-delete yang tidak sesuai model telah dibuang setelah gagal di CI.

## DESAIN
- Admin Madrasah mengakses menu Import Data.
- Template XLSX dibuat dari data referensi tenant; terdapat sheet Petunjuk, Data Guru, Penugasan Guru (khusus Guru), Data Siswa (khusus Siswa), dan Referensi.
- NIP/NIK/NUPTK/NIS/NISN/telepon/tanggal diperlakukan sebagai teks atau format eksplisit agar nol di depan tidak hilang dan tanggal tidak berubah diam-diam.
- Workbook divalidasi berdasarkan ekstensi/MIME, struktur sheet, versi/header, nilai, referensi tenant, duplikasi dalam file, identitas existing, username, dan email.
- Kebijakan V1: data baru diimpor; identitas existing dilewati; duplikat di workbook ditolak; tidak ada auto-update.
- Preview harus dikonfirmasi Admin. Batch memiliki idempotency/checksum, lock tenant untuk serialisasi import, transaksi per baris, audit log, laporan status per baris, dan riwayat tenant.
- Password awal tidak dicantumkan di workbook. Akun baru menggunakan tautan aktivasi satu kali untuk menetapkan password; token disimpan dalam bentuk hash dan data staging sensitif dienkripsi.
- Import hanya untuk ADMIN_MADRASAH dengan permission `user.manage`; tenant diverifikasi di komponen UI dan service.

## IMPLEMENTASI
- Branch: `work/import-guru-siswa-implementation-20261122`.
- Commit implementasi terkini saat checkpoint ini dibuat: `01ca47db0cf0b491f8e7e3f5eb4b2014bd49fbfd`.
- File inti:
  - `backend-laravel/app/Exports/Import/` (generator template dan sheet)
  - `backend-laravel/app/Services/Import/ImportWorkbookService.php`
  - `backend-laravel/app/Livewire/Admin/ImportManagement.php`
  - `backend-laravel/app/Http/Controllers/Admin/ImportTemplateController.php`
  - `backend-laravel/app/Http/Controllers/Auth/ImportAccountActivationController.php`
  - `backend-laravel/app/Models/ImportBatch.php`, `ImportBatchRow.php`, `ImportActivationToken.php`
  - `backend-laravel/database/migrations/2026_11_22_000001_create_import_batches_tables.php`
  - `backend-laravel/database/migrations/2026_11_22_000002_add_encrypted_payload_to_import_batch_rows.php`
  - Routes, layout, Blade views, serta `UserManagement.php` untuk tenant import lock.
- Backup sebelum perubahan dicatat di `docs/backup/import-guru-siswa-prepatch-2026-11-22/`. Backup memuat salinan file lama dan nama sumber; SHA Git blob diverifikasi sama saat backup dibuat.
- Baseline ZIP asli dan branch baseline tidak diubah. Belum ada merge ke branch utama, deployment VPS, atau migration database produksi.

## PENGUJIAN
- Workflow GitHub Actions menggunakan PHP 8.3 dan MySQL 8 InnoDB disposable; workflow memverifikasi database kosong, grants terbatas, Laravel memakai akun database sementara, dan seluruh tabel hasil migrasi menggunakan InnoDB.
- Workflow: `44937765148`, commit yang diuji `01ca47db0cf0b491f8e7e3f5eb4b2014bd49fbfd`.
- Status ketika checkpoint ditulis: RUNNING. PHP lint telah PASS; PHPUnit Admin CBT boundary, dashboard/tenant, academic period, legacy role migration, tenant profile, dan Admin user-management telah PASS. Suite import XLSX sedang berjalan; API auth/tenant isolation dan Guru/Siswa regression masih menunggu.
- Run sebelumnya `44936793239` gagal hanya pada skenario tambahan soft-delete yang tidak berlaku pada model User; test tersebut telah dihapus setelah diverifikasi bahwa model tidak memakai SoftDeletes. Run baru adalah bukti yang harus dipakai untuk status akhir.
- Belum ada klaim siap produksi. Hasil integrasi live, security/stress/multi-device, VPS deployment, dan validasi operasional madrasah tetap belum dilakukan.

## PEKERJAAN BERIKUTNYA
1. Tunggu hasil lengkap workflow `44937765148`; periksa log jika ada kegagalan.
2. Jika PASS, simpan hasil numerik PHPUnit dan update checkpoint final.
3. Audit ulang semua file yang berubah, migrasi import, permission, tenant boundary, aktivasi akun, idempotensi, dan audit log.
4. Review melalui pull request sebelum merge; jangan deploy/migrate ke database produksi tanpa rencana, backup, preflight, dan persetujuan eksplisit.
