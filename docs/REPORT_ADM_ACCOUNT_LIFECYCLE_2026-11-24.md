# Laporan Milestone Web Admin — Siklus Hidup Akun Guru dan Siswa

Tanggal: 24 November 2026  
Repository: `zane0184/cbt_november`  
Branch: `work/p1-seven-targets-20261124`  
Source SHA yang diuji: `19b0e230a42192fcdcae706043ec8dc6539cb48f`  
PR integrasi: #23 — OPEN / DRAFT / NOT MERGED

## A. AUDIT

Pemeriksaan source menemukan bahwa User Management sebelumnya menyediakan pembuatan dan daftar akun, tetapi belum memiliki status akun aktif/nonaktif yang ditegakkan pada seluruh request, pencabutan sesi/token, reset password yang diaudit, maupun pencatatan login terakhir.

Tabel `users` memiliki `deleted_at`, tetapi model User tidak memakai SoftDeletes. Karena itu, `deleted_at` tidak boleh dianggap sebagai enforcement status akun yang memadai.

Kebutuhan owner:
- Admin Madrasah mengelola akun Guru dan Siswa.
- Admin tidak mengambil alih authoring bank soal dan operasi ujian Guru.
- Isolasi tenant harus berlaku pada operasi akun.
- Aktivasi, deaktivasi, dan reset password harus tercatat.
- Data akun dan histori ujian tidak boleh dihapus hanya karena akun dinonaktifkan.

## B. DESAIN

1. Menambahkan `users.is_active` dan `users.last_login_at`.
2. Migration baru melakukan preflight read-only dan berhenti jika menemukan baris `users.deleted_at IS NOT NULL`; tidak ada klasifikasi otomatis atau penghapusan data.
3. Login resolver dan kedua login controller hanya menerima akun aktif.
4. Middleware `active.user` membaca status kanonis langsung dari database dengan kombinasi ID dan tenant, bukan mempercayai objek User yang mungkin stale di memori.
5. Admin hanya dapat mengaktifkan, menonaktifkan, atau reset password akun yang memiliki tepat satu role GURU atau SISWA dalam tenant yang sama.
6. Deaktivasi dan reset password mencabut Sanctum token, sesi database, dan remember token.
7. Deaktivasi/reset password Siswa ditolak ketika masih ada attempt ujian aktif.
8. Password baru dibuat acak dan ditampilkan satu kali di UI; password tidak dimasukkan ke audit log.
9. `last_login_at` diperbarui hanya setelah login berhasil.
10. Migration bersifat forward-only; `down()` menolak rollback otomatis.

## C. IMPLEMENTASI

### Source files

- `backend-laravel/database/migrations/2026_11_24_210001_add_user_account_state.php` (baru)
- `backend-laravel/app/Http/Middleware/EnsureActiveUser.php` (baru)
- `backend-laravel/app/Services/UserAccountService.php` (baru)
- `backend-laravel/app/Models/User.php`
- `backend-laravel/app/Services/LoginIdentityResolver.php`
- `backend-laravel/app/Http/Controllers/AuthWebController.php`
- `backend-laravel/app/Http/Controllers/Api/V1/AuthController.php`
- `backend-laravel/bootstrap/app.php`
- `backend-laravel/routes/web.php`
- `backend-laravel/routes/api.php`
- `backend-laravel/routes/teacher.php`
- `backend-laravel/app/Livewire/Admin/UserManagement.php`
- `backend-laravel/resources/views/livewire/admin/user-management.blade.php`
- `backend-laravel/tests/Feature/UserAccountLifecycleTest.php` (baru)

### Fitur UI dan service

- Status akun Aktif/Nonaktif.
- Waktu login terakhir.
- Aktivasi akun Guru/Siswa.
- Deaktivasi akun Guru/Siswa dengan pencabutan sesi dan token.
- Reset password dengan password baru yang ditampilkan sekali.
- Audit event: `USER_ACTIVATED`, `USER_DEACTIVATED`, `USER_PASSWORD_RESET`.
- Admin tidak mendapat tombol lifecycle untuk akun Admin atau role lain.
- Siswa yang masih mengerjakan ujian aktif tidak dapat dinonaktifkan/reset melalui jalur administrasi biasa.

### Backup

Backup byte-exact dan manifest SHA tersedia:

- `backend-laravel/backup_before_patch/ADM_USER_ACCOUNT_LIFECYCLE_20261124/`
- `backend-laravel/backup_before_patch/ADM_ACTIVE_USER_CANONICAL_STATUS_20261124/`
- `backend-laravel/backup_before_patch/ADM_ACCOUNT_LIFECYCLE_TEST_FIXTURE_20261124/`

Seluruh source lama yang diubah memiliki pasangan backup dengan blob SHA yang cocok.

## D. PENGUJIAN

Exact code SHA: `19b0e230a42192fcdcae706043ec8dc6539cb48f`

GitHub Actions:

- Tenant Backend CI: [Run #45401251292](https://github.com/zane0184/cbt_november/actions/runs/45401251292) — SUCCESS.
- Build Standalone Web CBT Deployment ZIP: [Run #45401251207](https://github.com/zane0184/cbt_november/actions/runs/45401251207) — SUCCESS.

Bukti CI:

| Pemeriksaan | Hasil |
|---|---|
| PHP lint | PASS |
| Composer lock/platform | PASS |
| MySQL 8.0.46/InnoDB migration gate | PASS |
| Full PHPUnit | 561 passed |
| Assertions | 2.440 |
| Durasi full PHPUnit | 167,86 detik |
| Benchmark disposable MySQL | PASS |
| ReportController/ReportPolicy | PASS |
| Pemeriksaan sisa schema benchmark | PASS |
| UserAccountLifecycleTest | PASS |

ZIP workflow menghasilkan artifact kandidat:
- Artifact ID: `14923211658`
- Nama: `cbt-madrasah-web-deployment-19b0e230a42192fcdcae706043ec8dc6539cb48f`
- Ukuran: 19.784.633 byte

Artifact ini bukan ZIP distribusi final. Fresh install, upgrade, backup/restore, staging dua instalasi, browser E2E, dan acceptance seluruh portal belum selesai.

## E. STATUS

**ADM ACCOUNT LIFECYCLE — IMPLEMENTASI DAN CI PASS; FITUR ADM LAIN MASIH PARTIAL.**

| Kebutuhan | Status |
|---|---|
| Status akun aktif/nonaktif di database | PASS pada migration/test MySQL |
| Login menolak akun nonaktif | PASS pada feature test |
| Protected API/web request memeriksa status kanonis | PASS pada feature test |
| Deaktivasi mencabut Sanctum token dan web session | PASS |
| Aktivasi akun | PASS |
| Reset password dan pencabutan sesi/token | PASS |
| Audit event lifecycle | PASS |
| Login terakhir | Implemented; full suite PASS |
| Isolasi tenant target akun | Implemented; test suite PASS |
| Edit/detail profil akun | NOT DONE |
| Import/export akun Guru/Siswa | NOT DONE |
| Database instalasi nyata | NOT VERIFIED |
| Staging migration | BLOCKED |
| Produksi | NOT TOUCHED |

Catatan deployment:
- Migration `210001` belum dijalankan pada database staging/produksi.
- Jika ada baris users dengan `deleted_at` terisi, migration berhenti dan meminta pemeriksaan eksplisit.
- Jangan menjalankan migration sebelum `login-identity:preflight`, backup database, dan persetujuan owner.
- Middleware menambah satu pembacaan status akun pada request terlindungi agar deaktivasi segera berlaku.

## F. LANGKAH SELANJUTNYA

1. Lanjutkan ADM-02–05: profil madrasah, branding, logo/favicon, dan kop/header laporan.
2. Lengkapi ADM-06–08: detail/edit profil, lifecycle lanjutan, dan kebijakan akun.
3. Lengkapi ADM-09–12: import/export data Guru/Siswa, kelas/rombel, dan mata pelajaran sesuai master spec.
4. Lengkapi ADM-13–23 sesuai matriks penerimaan; jangan mengambil alih operasi ujian Guru.
5. Lanjutkan Web Guru, DOCX import soal, Web Siswa, dan E2E ketiga portal.
6. Staging clone dua instalasi tetap menunggu akses operator; jangan menyentuh produksi.
7. ZIP final hanya setelah seluruh acceptance criteria, fresh install, upgrade, backup/restore, dan E2E lulus.

Tidak ada klaim siap produksi atau siap distribusi pada milestone ini.
