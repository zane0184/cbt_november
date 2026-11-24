# Laporan Milestone P1 — Login Identity Enforcement dan Admin Identity Form

Tanggal: 24 November 2026  
Repository: `zane0184/cbt_november`  
Branch: `work/p1-seven-targets-20261124`  
Source SHA yang diverifikasi: `c669ad26942abbb65508875f75ae92e493dff6ae`  
PR integrasi: #23 — OPEN / DRAFT / NOT MERGED

## A. AUDIT

### Kontrak identitas owner

| Portal/pengguna | Identitas login |
|---|---|
| Admin Madrasah | Username |
| Guru PNS/PPPK | NIP tepat 18 digit |
| Guru Honorer | NIK tepat 16 digit |
| Siswa | NISN tepat 10 digit |

NUPTK hanya atribut opsional tepat 16 digit dan bukan fallback login. Guru tidak boleh fallback ke username atau NUPTK. Siswa hanya menggunakan NISN.

### Temuan yang ditutup

1. Fixture bersama ujian membuat profil Guru/Siswa tanpa identitas yang kini diwajibkan database.
2. Test preflight lama memakai variabel MySQL `check_constraint_checks` yang tidak tersedia pada MySQL 8.0.46.
3. Temporary test tables menggunakan collation yang berbeda dari tabel `users`, sehingga query preflight gagal dengan collation mix.
4. Sejumlah test keamanan kunci jawaban membuat profil Guru tanpa identitas.
5. CHECK constraint format awal belum secara eksplisit mewajibkan `employee_identifier` non-NULL. Kondisi SQL UNKNOWN dapat diterima oleh CHECK/trigger lama.
6. Form Admin menampilkan NIP/NIK sebagai opsional pada kategori yang sebenarnya melarang identitas tersebut.

### Status sumber dan integrasi

- PR #23 tetap OPEN / DRAFT / NOT MERGED.
- HEAD saat laporan ini dibuat: `c669ad26942abbb65508875f75ae92e493dff6ae`.
- Base PR #23: `work/phpunit-41-root-cause-20261123`.
- Tidak ada PR yang di-merge dan tidak ada branch lama yang dihapus.
- Tidak ada database produksi/staging yang diakses atau dimigrasikan.

## B. DESAIN

1. Migration historis `2026_11_24_180001_enforce_login_identity_format.php` dipertahankan.
2. Guard baru ditambahkan melalui migration forward-only `2026_11_24_190001_enforce_teacher_employee_identifier_required.php`.
3. Migration baru melakukan preflight read-only dan berhenti sebelum DDL jika ada `teacher_profiles.employee_identifier IS NULL`.
4. MySQL menggunakan CHECK constraint; SQLite menggunakan trigger BEFORE INSERT dan BEFORE UPDATE.
5. Tidak ada backfill, auto-correction, trimming, atau tebakan identitas legacy.
6. `employee_identifier` Guru harus terisi dan sama dengan NIP untuk PNS/PPPK atau NIK untuk Honorer. Constraint format pada migration sebelumnya tetap menjadi pasangan guard.
7. Form Admin menampilkan NIP hanya untuk PNS/PPPK dan NIK hanya untuk Honorer. Saat status diganti, nilai identitas kategori yang tidak berlaku dibersihkan.

### Strategi pemulihan

- Migration bersifat forward-only; `down()` menolak rollback otomatis.
- Jika preflight menemukan NULL, operator harus merekonsiliasi data dengan bukti resmi dan keputusan owner. Migration tidak mengubah data.
- Jika deployment terhenti, periksa status constraint/trigger sebelum retry. Jangan menghapus guard manual.
- Backup database wajib dilakukan sebelum migration pada instalasi staging/tenant. Pemulihan darurat dilakukan dari backup database yang tervalidasi, bukan dengan menghapus CHECK/trigger.
- Migration belum dijalankan pada database staging atau produksi.

## C. IMPLEMENTASI

### Commit sumber pada PR #23

| Commit | Perubahan |
|---|---|
| `117910d40e17e35a824978a6d8c55daf4c3b7a78` | Fixture Guru/Siswa sesuai kontrak; test preflight menggunakan temporary tables; menghapus variabel MySQL yang tidak tersedia |
| `10b5286bd2bc45b5cf02611d1a4549b20fe88a0a` | Perbaikan collation temporary tables dan fixture AnswerKeyLeakage |
| `644ce25faa2fe7e5736567e25efb658caf31260f` | Perluasan negative test NIP/NIK/NISN/status/NUPTK dan UPDATE |
| `e8e97eea76d9edec6856cd235bc6825de72a4a63` | Migration forward-only employee_identifier non-NULL; test MySQL/SQLite/preflight; fixture login |
| `0c32072b61aded42464fd727dfa922014e19e801` | Form Admin dinamis berdasarkan status; clear nilai NIP/NIK yang tidak berlaku; test UI |
| `c669ad26942abbb65508875f75ae92e493dff6ae` | Koreksi test agar membuka modal sebelum assertion teks UI |

### File utama yang berubah

- `backend-laravel/database/migrations/2026_11_24_190001_enforce_teacher_employee_identifier_required.php` (baru)
- `backend-laravel/app/Livewire/Admin/UserManagement.php`
- `backend-laravel/resources/views/livewire/admin/user-management.blade.php`
- `backend-laravel/tests/Support/CreatesTeacherExamScope.php`
- `backend-laravel/tests/Feature/LoginIdentityPreflightCommandTest.php`
- `backend-laravel/tests/Feature/LoginIdentityDatabaseConstraintTest.php`
- `backend-laravel/tests/Feature/LoginIdentitySQLiteTriggerTest.php`
- `backend-laravel/tests/Feature/WebAdminAuthTest.php`
- `backend-laravel/tests/Feature/Security/AnswerKeyLeakageTest.php`
- `backend-laravel/tests/Feature/AdminUiVerificationTest.php`

### Backup

Backup byte-exact dan manifest SHA Git blob tersedia pada:

- `backend-laravel/backup_before_patch/P1_MYSQL_CHECK_FIXTURES_20261124/`
- `backend-laravel/backup_before_patch/P1_FIX_IDENTITY_COLLATION_AND_ANSWER_KEY_FIXTURES_20261124/`
- `backend-laravel/backup_before_patch/P1_IDENTITY_NEGATIVE_CASE_MATRIX_20261124/`
- `backend-laravel/backup_before_patch/P1_EMPLOYEE_IDENTIFIER_NOT_NULL_20261124/`
- `backend-laravel/backup_before_patch/P1_ADMIN_IDENTITY_FORM_CONSISTENCY_20261124/`
- `backend-laravel/backup_before_patch/P1_ADMIN_IDENTITY_FORM_TEST_FIX_20261124/`

Setiap manifest mencatat source blob SHA dan backup blob SHA; seluruh pasangan yang dibuat pada milestone ini MATCH.

## D. PENGUJIAN

Exact source SHA: `c669ad26942abbb65508875f75ae92e493dff6ae`

GitHub Actions:

- Tenant Backend CI: [Run #45397205018](https://github.com/zane0184/cbt_november/actions/runs/45397205018) — SUCCESS.
- Build Standalone Web CBT Deployment ZIP: [Run #45397204873](https://github.com/zane0184/cbt_november/actions/runs/45397204873) — SUCCESS.

Bukti backend CI:

| Pemeriksaan | Hasil |
|---|---|
| PHP lint | PASS |
| Composer lock/platform | PASS |
| Disposable MySQL identity control | PASS |
| Migration identity MySQL 8.0.46/InnoDB | PASS |
| SQLite trigger/preflight tests | PASS |
| Full PHPUnit | 556 passed |
| Assertions | 2.416 |
| Durasi full PHPUnit | 179,37 detik |
| Disposable MySQL benchmark dan ReportPolicy | PASS |

ZIP workflow menghasilkan artifact kandidat:
- Artifact ID: `14922091140`
- Nama: `cbt-madrasah-web-deployment-c669ad26942abbb65508875f75ae92e493dff6ae`
- Ukuran: 19.779.997 byte

Artifact tersebut hanya membuktikan workflow archive berhasil. Belum merupakan ZIP distribusi final karena fresh installation, upgrade, backup/restore, staging E2E, dan acceptance tiga portal belum selesai.

## E. STATUS

**P1 LOGIN IDENTITY — IMPLEMENTASI DAN CI PASS; ACCEPTANCE INSTALASI BELUM SELESAI.**

| Area | Status |
|---|---|
| Resolver login Admin/Guru/Siswa | PASS pada feature test |
| Format dan kategori identitas MySQL | PASS |
| Format dan kategori identitas SQLite | PASS |
| employee_identifier wajib dan cocok | PASS pada migration/test |
| NUPTK bukan fallback login | PASS pada feature test |
| Admin identity form | PASS pada feature test |
| Preflight legacy read-only | PASS |
| Data legacy instalasi nyata | NOT VERIFIED |
| Browser E2E tiga portal | NOT VERIFIED |
| Staging migration/upgrade | BLOCKED |
| Produksi | NOT TOUCHED |
| ZIP final distribusi | NOT READY |

Staging masih BLOCKED sebagaimana P0.6: belum ada bukti akses SSH/aaPanel Publisher dan Tenant dari sesi kerja. Jangan menjalankan migration pada database nyata hanya berdasarkan CI disposable.

## F. LANGKAH SELANJUTNYA

1. Rekonsiliasi status PR #23 dan seluruh PR bertingkat tanpa merge otomatis.
2. Lanjutkan matriks acceptance Backend dan ADM-01–23.
3. Selesaikan Web Guru, dengan DOCX sebagai prioritas import soal; jangan mengaktifkan XLSX question importer.
4. Selesaikan Web Siswa dan browser E2E: admission, IndexedDB, answer persistence, submit barrier, offline/recovery.
5. Integrasikan ketiga portal dan provenance kurikulum sampai snapshot ujian serta laporan.
6. Jalankan staging clone dua instalasi setelah akses operator dan bukti preflight tersedia.
7. Baru setelah seluruh acceptance terpenuhi, bangun serta verifikasi ZIP distribusi final.

Tidak ada klaim siap produksi atau siap distribusi pada milestone ini.
