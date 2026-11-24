# Laporan Tahap — Import Siswa XLSX/CSV
Tanggal: 25 November 2026
Repository: `zane0184/cbt_november`
Branch: `work/docx-import-parser-20261125`
Commit source yang diuji: `584f0db85cdebd964900c033c9c66c7a1dd9737a`
CI run: `45461795586`

## AUDIT

- Checkout source penuh diperoleh dari GitHub Actions pada exact commit di atas. Arsip sumber diverifikasi terhadap artifact digest `sha256:fb088c02a1e8068d25fb0e319d9e82b0e33b49c631b5931af186fc39d471b31f`.
- Workspace agent: `/mnt/data/CBT_MADRASAH_RECONSTRUCTION/`; snapshot berisi backend Laravel, dokumentasi, konfigurasi, migrations, dan tests.
- Import Siswa XLSX/CSV belum tersedia pada baseline. Importer Excel lama adalah importer soal dan tidak diaktifkan.
- NISN tersimpan sebagai string dan dibatasi unique per tenant. Relasi Siswa–Rombel memakai tenant-aware pivot dan foreign key.
- Manajemen akun menggunakan Admin Madrasah dengan role `ADMIN_MADRASAH` dan permission `user.manage`.
- `AuditService` dan sanitizer tersedia; audit batch hanya menyimpan ringkasan dan hash file, bukan NISN/PII baris.
- Tidak ada perubahan atau koneksi ke database produksi.

## DESAIN

- Admin memilih satu Rombel aktif dari tenant/instalasi yang sedang aktif.
- File XLSX/CSV dibaca melalui header `nama`, `nisn` dan kolom opsional identitas akademik.
- NISN wajib berupa teks tepat 10 digit. Sel XLSX bertipe numerik ditolak agar nol di depan tidak hilang.
- Preview memeriksa baris kosong, header duplikat, NISN invalid, duplikasi dalam file, duplikasi database, gender, tanggal, panjang kolom, serta relasi Rombel/kelas/tenant.
- Import bersifat all-or-nothing dalam transaksi. Kegagalan audit atau database membatalkan seluruh batch.
- Idempotency key mengikat tenant, Rombel, versi kontrak, dan SHA-256 file.
- Password akun Siswa dibuat acak dan disimpan sebagai hash. Admin perlu menggunakan alur Reset Password yang tersedia untuk menerbitkan kredensial kepada Siswa.

## IMPLEMENTASI

File baru:
- `backend-laravel/app/Livewire/Admin/StudentImport.php`
- `backend-laravel/app/Services/StudentImportService.php`
- `backend-laravel/database/migrations/2026_11_25_010001_create_student_import_batches_table.php`
- `backend-laravel/resources/views/livewire/admin/student-import.blade.php`
- `backend-laravel/tests/Feature/StudentImportTest.php`
- `backend-laravel/backup_before_patch/STUDENT_IMPORT_20261125/BACKUP_MANIFEST.md`
- `backend-laravel/backup_before_patch/STUDENT_IMPORT_20261125/web.php.before`
- `.github/workflows/source-checkout-artifact.yml`

File dimodifikasi:
- `backend-laravel/routes/web.php`
- `backend-laravel/resources/views/livewire/admin/user-management.blade.php`
- `.github/workflows/docx-import-parser-ci.yml` (menambahkan test gate terarah Import Siswa)

Backup sebelum perubahan:
- Source: `backend-laravel/routes/web.php`
- Backup: `backend-laravel/backup_before_patch/STUDENT_IMPORT_20261125/web.php.before`
- Git blob SHA-1 source dan backup: `1fca336b285415935558a3ad913972297093c4a2`
- SHA-256 source dan backup: `2030337dd8092758e29fe6edc2c4196863d5aae07b6dbd2b084815c28339123c`
- Verifikasi byte hash: PASS.

Tidak ada perubahan `composer.json`, `composer.lock`, atau migration produksi. Migration baru hanya dijalankan pada database disposable CI.

## PENGUJIAN

Exact commit `584f0db85cdebd964900c033c9c66c7a1dd9737a`, MySQL 8.0/InnoDB disposable:

| Pemeriksaan | PHP 8.3 | PHP 8.4 |
|---|---:|---:|
| Checkout exact commit | PASS | PASS |
| MySQL 8.0/InnoDB identity check | PASS | PASS |
| Composer lock/install | PASS | PASS |
| PHP lint | PASS | PASS |
| Import Siswa acceptance tests | 10 passed, 0 failed, 46 assertions | 10 passed, 0 failed, 46 assertions |
| Full PHPUnit suite | 592 passed, 1 failed, 3 skipped; 2.634 assertions | 592 passed, 1 failed, 3 skipped; 2.634 assertions |

Acceptance tests mencakup:
1. CSV preview dan import, NISN nol depan tetap terjaga.
2. XLSX dengan NISN text.
3. Penolakan NISN XLSX bertipe numerik.
4. Penolakan NISN tidak 10 digit.
5. Duplikasi NISN di dalam file.
6. Duplikasi NISN yang sudah ada di database tenant.
7. Penolakan Rombel tenant lain.
8. Penolakan akses non-Admin.
9. Penolakan replay batch (idempotency).
10. Rollback seluruh batch jika audit gagal.

Durasi test terarah: PHP 8.3 28,30 detik; PHP 8.4 14,75 detik.
Durasi full suite: PHP 8.3 138,79 detik; PHP 8.4 86,72 detik.

### Kegagalan full suite yang masih terbuka

Kedua versi gagal pada test yang sama: `Tests\\Feature\\Phase7dBenchmarkCommandSafetyTest`, baris 164. Test mengharapkan `policyDecisions = [false]`, tetapi hasil yang diamati `[]`. Pesan test menyatakan `ReportPolicy` seharusnya menolak ekspor karena permission `report.view` tidak diberikan. Ini bukan kegagalan Import Siswa; namun full suite tetap merah dan akar masalah tersebut belum diperbaiki.

### Warning

Per job:
- 54 warning deprecation PHPUnit karena metadata test masih memakai doc-comment; PHPUnit 12 tidak lagi mendukung metadata tersebut.
- 2 ringkasan test suite berstatus WARN, terkait test installer dan branding; 3 test di seluruh suite berstatus skipped.
- MySQL container: 9 warning server pada PHP 8.3 dan 10 pada PHP 8.4; termasuk `--skip-host-cache` deprecated, sertifikat self-signed, izin PID file, password plugin deprecated, dan peringatan redo-log pada salah satu job.
- 6 warning pemuatan tabel zona waktu.
- 1 warning GitHub Actions tentang `actions/checkout@v4` yang masih menggunakan target Node.js 20.

Warning tersebut terpisah dari hasil test terarah Import Siswa, yang lulus tanpa skipped/failure.

## STATUS DAN BATASAN

- Import Siswa: implementasi dan acceptance tests terarah PASS pada PHP 8.3 dan 8.4.
- Full PHPUnit suite: FAIL karena satu test Phase7d yang tidak terkait import.
- PR #23 tetap OPEN / DRAFT / UNMERGED; base dan head tidak diubah.
- Tidak ada deployment VPS, migration produksi, perubahan database produksi, atau ZIP distribusi.
- DOCX parser dan Import Guru belum dimulai dalam tahap ini.
- Langkah berikutnya: lanjut audit/implementasi parser DOCX fleksibel sesuai kontrak Owner; Import Guru tetap sesudah DOCX.
