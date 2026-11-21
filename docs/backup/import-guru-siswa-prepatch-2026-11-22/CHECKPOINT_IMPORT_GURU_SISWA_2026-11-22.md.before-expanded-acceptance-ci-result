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
- Status akhir workflow `44937765148`: **SUCCESS** pada commit source `01ca47db0cf0b491f8e7e3f5eb4b2014bd49fbfd`.
- PHP lint: PASS. Semua 14 suite yang dipilih lulus, total **74 test dan 417 assertions**:
  - Admin CBT boundary: 1 test / 27 assertions.
  - Admin dashboard: 3 / 28.
  - Academic period: 3 / 33.
  - Legacy role migration: 4 / 34.
  - Tenant profile: 3 / 18.
  - Admin user management: 3 / 21.
  - Template generator: 5 / 40.
  - Import workbook service: 16 / 97.
  - API auth: 7 / 38.
  - Multi-tenant auth: 3 / 11.
  - Tenant isolation: 10 / 16.
  - Web Admin auth: 1 / 5.
  - Student Web login: 8 / 27.
  - Student result visibility: 7 / 22.
- Import tests membuktikan PNS memakai NIP, honorer memakai NIK, Siswa memakai NISN; leading zero tetap terjaga; Rombel/tahun pelajaran/semester/subject tenant valid; duplikasi, data existing, email/username bentrok, referensi lintas tenant, workbook rusak, re-upload idempotent, pembatalan preview, audit, permission dan multi-preview race diuji.
- Verifikasi pascamigrasi: **75 base tables, 0 tabel non-InnoDB**, tabel wajib tersedia; disposable database dan app user dihapus setelah tes.
- Run `44936793239` sebelumnya gagal hanya pada skenario soft-delete yang tidak berlaku pada model User; skenario uji telah dibuang setelah diverifikasi bahwa model tidak memakai SoftDeletes. Run final `44937765148` sukses.
- Hasil ini membuktikan suite CI terpilih pada MySQL 8 disposable, bukan integrasi live atau kesiapan produksi. Uji security/stress yang lebih luas, integrasi Android, VPS deployment, pemulihan sinkronisasi dan validasi operasional madrasah tetap belum dilakukan.

## PEKERJAAN BERIKUTNYA
1. Lakukan review menyeluruh diff dan migrasi import, termasuk keamanan file sementara, retensi staging, aktivasi akun, permission, tenant boundary, lock, idempotensi, audit log, dan pelaporan per baris.
2. Lakukan review melalui pull request terpisah sebelum merge ke branch utama.
3. Siapkan deployment staging dan uji alur Admin secara manual; jangan deploy/migrate ke database produksi tanpa rencana, backup, preflight, dan persetujuan eksplisit.
4. Lengkapi uji beban/keamanan, Android integration, sinkronisasi/offline/recovery, dan validasi operasional madrasah sebelum menyatakan siap produksi.
