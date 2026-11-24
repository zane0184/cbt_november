# Laporan P1 — Rekonsiliasi Identitas Login Wajib

Tanggal: 24 November 2026  
Repository: `zane0184/cbt_november`  
Branch: `work/p1-seven-targets-20261124`  
Exact HEAD saat laporan disusun: `7ecbd882e9b5b8b5171c1d64ecf2c5b38747216f`  
PR utama: #23 — OPEN / DRAFT / NOT MERGED  
Base P1: `1c33e5d422175f9bd85d206230271a9fe5a4eecd`

## AUDIT

### 1. Kondisi repository dan riwayat

- HEAD pada prompt awal dan checkpoint sebelumnya sudah tertinggal; branch aktual saat pemeriksaan sebelum patch berada pada `206bc788415327841bcbad976784e6a642374b8e`.
- PR #23 masih OPEN dan DRAFT. Pada awal audit, PR memuat 44 commit dan 34 file berubah. Setelah backup dan patch tahap ini, branch berkembang; tidak ada merge.
- Commit `206bc78` adalah perubahan dokumentasi setelah `235501f`; source aplikasi pada keduanya tidak berubah. Namun patch identitas di laporan ini mengubah source setelahnya.
- CI historis pada `235501f` dilaporkan 522 passed, 0 failed, 2.200 assertions pada PHP 8.3 + disposable MySQL 8.0.46/InnoDB. Ini bukan bukti untuk source yang diubah pada laporan ini.
- Workflow `tenant-backend-ci.yml` memiliki trigger push untuk branch P1. Pemeriksaan status dan workflow runs dari konektor pada exact HEAD `7ecbd88` mengembalikan daftar kosong. Karena itu exact-HEAD CI tetap NOT VERIFIED.
- P0 base `1c33e5d` memiliki bukti CI historis terpisah; hasil P0 tidak digunakan sebagai bukti P1.

### 2. Konflik keputusan identitas

Master spesifikasi Web Admin/Guru 22 November dan addendum rekonsiliasi terdahulu memuat ketentuan yang lebih lama: identitas Guru opsional dan/atau username Guru dapat dipakai login. Instruksi owner terbaru pada 24 November menetapkan kontrak wajib yang berbeda dan diprioritaskan melalui `ADDENDUM_OWNER_REQUIRED_LOGIN_IDENTITIES_2026-11-24.md`.

Source sebelum patch:
- Form UserManagement menerima Guru tanpa status kepegawaian dan tanpa NIP/NIK.
- LoginIdentityResolver menerima username sebagai kandidat login Guru.
- Login page masih menyatakan Admin dan Guru menggunakan username.
- Beberapa tampilan menampilkan NIP/NIK/NISN lengkap.
- Login web menggunakan identifier mentah sebagai bagian throttle key dan mengembalikan identifier melalui old input.

### 3. Keputusan produk yang berlaku

| Role | Identifier login | Validasi wajib |
|---|---|---|
| ADMIN_MADRASAH | Username | Tidak diubah |
| GURU PNS/PPPK | NIP | 18 digit |
| GURU Honorer | NIK | 16 digit |
| SISWA | NISN | 10 digit |

Guru tidak boleh login memakai username atau NUPTK sebagai fallback. Web dan API memakai resolver yang sama. Nilai identitas disimpan sebagai string untuk mempertahankan nol di depan. Uniqueness dibatasi tenant/database instalasi; tidak ada shared academic database.

## DESAIN

1. Validasi create Guru mewajibkan status kepegawaian dan identifier sesuai kategori.
2. LoginIdentityResolver hanya menerima:
   - Admin melalui username;
   - Guru PNS/PPPK melalui NIP;
   - Guru Honorer melalui NIK;
   - Siswa melalui NISN.
3. Kandidat identitas ambigu ditolak fail-closed.
4. Unique index database dibuat tenant-scoped untuk NIP, NIK, NUPTK, dan NISN.
5. Migration melakukan preflight semua tabel/kolom, string kosong, duplikasi, dan kondisi index sebelum DDL pertama. Migration bersifat forward-only; rollback otomatis ditolak.
6. Command `login-identity:preflight` hanya membaca database, menghitung anomali tanpa menampilkan nilai mentah, dan tidak melakukan backfill.
7. Model menyembunyikan identifier dari serialisasi otomatis; tampilan Admin/Guru mem-mask nilai. Login web tidak mem-flash identifier ke session dan throttle key di-hash.
8. Tidak ada backfill data legacy, perubahan status Guru, atau rekonsiliasi identitas berdasarkan tebakan.

## IMPLEMENTASI

### Source yang diubah

- `backend-laravel/app/Livewire/Admin/UserManagement.php`
- `backend-laravel/app/Services/LoginIdentityResolver.php`
- `backend-laravel/app/Http/Controllers/AuthWebController.php`
- `backend-laravel/app/Models/TeacherProfile.php`
- `backend-laravel/app/Models/StudentProfile.php`
- `backend-laravel/bootstrap/app.php`
- `backend-laravel/resources/views/auth/login.blade.php`
- `backend-laravel/resources/views/livewire/admin/user-management.blade.php`
- `backend-laravel/resources/views/livewire/admin/student-rombel-management.blade.php`
- `backend-laravel/resources/views/livewire/teacher/portal-profile.blade.php`
- `backend-laravel/database/migrations/2026_11_24_170001_enforce_tenant_login_identity_uniques.php` (baru)
- `backend-laravel/app/Console/Commands/LoginIdentityPreflightCommand.php` (baru)
- `backend-laravel/tests/Feature/AdminUiVerificationTest.php`
- `backend-laravel/tests/Feature/WebAdminAuthTest.php`
- `backend-laravel/tests/Feature/StudentWebLoginTest.php`
- `backend-laravel/tests/Feature/LoginIdentityPreflightCommandTest.php` (baru)
- `docs/ADDENDUM_OWNER_REQUIRED_LOGIN_IDENTITIES_2026-11-24.md`

### Backup

Backup byte-exact disimpan di:

`backend-laravel/backup_before_patch/P1_REQUIRED_LOGIN_IDENTITIES_20261124/`

Manifest: `BACKUP_MANIFEST.md`.

Semua 13 file sumber yang akan diubah diverifikasi terhadap backup melalui SHA blob GitHub; seluruh pasangan cocok (13/13). Tidak ada file baseline ZIP yang diubah.

### Catatan migrasi

Migration unique index dibuat tetapi BELUM dijalankan. Tidak ada koneksi atau mutasi database produksi, staging, atau database proyek pada tahap ini. Command preflight juga belum dijalankan terhadap database proyek.

## PENGUJIAN

### Yang benar-benar diverifikasi

- Backup source: 13/13 SHA blob cocok dengan backup.
- Source dan test yang diperbarui berhasil dibaca ulang dari branch GitHub.
- PR #23 tetap OPEN/DRAFT.
- Workflow file menunjukkan push trigger untuk branch P1.

### Test yang ditambahkan tetapi belum dieksekusi

- Guru tidak dapat dibuat tanpa status kepegawaian.
- Guru PNS tanpa NIP ditolak.
- Guru Honorer tanpa NIK ditolak.
- Login Web Guru memakai NIP/NIK; username Guru ditolak.
- Login API memakai NIP dan menolak username Guru.
- Login Admin tetap memakai username; Siswa tetap memakai NISN.
- Unique constraint database menolak duplikasi NIP/NISN.
- Identitas Guru/Siswa tidak muncul pada serialisasi otomatis.
- Read-only identity preflight melaporkan konflik tanpa menampilkan nilai mentah.
- Identifier tidak di-flash ke session old input.

### NOT RUN / NOT VERIFIED

- PHP lint pada exact HEAD.
- Focused PHPUnit dan full PHPUnit pada exact HEAD.
- Disposable MySQL 8/InnoDB migration dan positive/negative unique constraint runtime.
- Duplicate/blank legacy preflight pada salinan database nyata.
- Race/concurrency saat dua request membuat identitas sama.
- API response audit lengkap untuk semua endpoint yang memuat profile.
- Audit jalur update/import/recovery identitas. Implementasi update/import belum dibuktikan pada audit ini.
- Browser E2E dan UAT Admin/Guru/Siswa.
- ZIP build, extraction, fresh install, upgrade, backup/restore.
- Staging VPS dan deployment.

Hasil CI historis pada SHA `235501f` tidak dihitung sebagai PASS untuk patch ini. Status check dan workflow run exact HEAD yang diperiksa tidak mengembalikan bukti; pengujian harus tetap berstatus NOT VERIFIED.

## CHECKPOINT

- Subtahap yang dikerjakan: rekonsiliasi identitas login wajib dan perlindungan tampilan/serialisasi.
- Status implementasi: source dan regression test ditulis.
- Status pengujian: belum terverifikasi.
- Database: tidak disentuh.
- Produksi: tidak disentuh.
- PR #23: tetap OPEN/DRAFT; tidak merge.
- ZIP: belum dibangun/di-accept.
- Device Lease/Fencing, Web Admin ADM-01..23, Web Guru end-to-end, Web Siswa browser-to-real-Laravel, kurikulum end-to-end, dan acceptance ZIP tetap merupakan backlog P1 terpisah.

### Langkah berikutnya

1. Dapatkan hasil CI exact HEAD untuk PHP lint, focused tests, full PHPUnit, dan MySQL 8/InnoDB.
2. Jika CI gagal, audit penyebab dan patch terarah dengan backup baru.
3. Jalankan `login-identity:preflight` hanya pada database disposable/salinan aman, bukan produksi.
4. Audit jalur update/import/recovery dan seluruh API response/log/audit yang dapat membawa identitas.
5. Lanjutkan matriks ADM-01..23 dan integrasi Web Guru/Siswa tanpa mengulang runner yang sudah ada.
6. Setelah seluruh portal lulus acceptance, lanjutkan staging dan verifikasi ZIP.

**Kesimpulan:** identitas wajib sudah diterapkan pada source P1 sebagai patch awal, tetapi belum dapat dinyatakan lulus pengujian atau siap produksi. **NOT READY FOR DISTRIBUTION.**
