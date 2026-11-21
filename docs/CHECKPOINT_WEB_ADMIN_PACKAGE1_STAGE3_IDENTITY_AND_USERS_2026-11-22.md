# CHECKPOINT — PAKET 1 WEB ADMIN / STAGE 3 IDENTITAS DAN PENGGUNA

Tanggal: 22 November 2026
Repository: zane0184/cbt_november
Branch: work/web-admin-package1-implementation-20261122
Commit source yang diuji: 5d7b9307e9eeb0bc8afaa5edc94cde1739cfd93e
Run: https://github.com/zane0184/cbt_november/actions/runs/44926079149

## AUDIT
- Tenant sudah memiliki kolom identitas institusi (name, nsm, npsn, address, phone, email, logo_path, code, status), sehingga tidak diperlukan migration identitas baru.
- Halaman UserManagement lama masih menyediakan filter role legacy dan belum mengaudit pembuatan akun.
- Pengelolaan Admin harus dibatasi ke Guru dan Siswa; akun Admin dan akun dengan role konflik/legacy tidak boleh muncul sebagai objek yang dapat dikelola Admin.
- Tidak ada permission rows atau permission_role pivot yang perlu dibuat untuk test; test memakai permission model in-memory.

## DESAIN
- Admin mengelola identitas tenant aktif saja; code instalasi dan status tenant tetap read-only.
- NSM 12 digit, NPSN 8 digit; logo PNG/JPG/WEBP maksimal 2 MB dan disimpan pada direktori tenant.
- Perubahan profil dilakukan dalam transaksi dengan row lock pada Tenant dan audit log.
- UserManagement hanya menampilkan akun dengan tepat satu role yang termasuk GURU atau SISWA.
- Pembuatan akun dari halaman Admin hanya menerima role GURU atau SISWA; role Admin dan legacy ditolak.
- Pembuatan akun Guru/Siswa dan profile terkait berada dalam transaksi; audit log mencatat identitas/role/profile type tanpa password.
- Tenant scope diterapkan pada daftar pengguna dan profil madrasah.

## IMPLEMENTASI
1. backend-laravel/app/Livewire/Admin/TenantProfileManagement.php — halaman profil tenant, validasi NSM/NPSN, upload logo tenant-scoped, transaction/lock/audit.
2. backend-laravel/resources/views/livewire/admin/tenant-profile-management.blade.php — formulir dan pratinjau identitas.
3. backend-laravel/routes/web.php — route /admin/profile.
4. backend-laravel/resources/views/layouts/app.blade.php — menu Profil Madrasah pada desktop/mobile.
5. backend-laravel/resources/views/livewire/admin/admin-dashboard.blade.php — tautan cepat Profil Madrasah.
6. backend-laravel/app/Livewire/Admin/UserManagement.php — audit USER_CREATED, render re-authorization, exact role scope, filter validation.
7. backend-laravel/resources/views/livewire/admin/user-management.blade.php — filter hanya Guru/Siswa dan redaksi portal yang diperbarui.
8. backend-laravel/tests/Feature/TenantProfileManagementTest.php — 3 test profil.
9. backend-laravel/tests/Feature/AdminUserManagementScopeTest.php — 3 test daftar/creation/role restrictions.
10. .github/workflows/wa01-alternative-isolated-runtime.yml — runner menambahkan dua test baru dan static guard permission writes.

Backup:
- routes-web.php.before-tenant-profile
  - SHA-256 f389775bce3ad34e03db8324710d28bf9483753063ebf203b6bb790667cfbe0f
  - blob 4a70dfd3c52f75f0244cdf51ba47e2f717d0361a
- app-layout.blade.php.before-tenant-profile-nav
  - SHA-256 97ff90cb8f3236e0d2114334cf871d014d8dbc53e92089288cfbaa6ea6855b8f
  - blob bc452b82e3ba0fa32d82e6a22b1041ecc73bbcd0
- admin-dashboard.blade.php.before-profile-link
  - SHA-256 a92daaf05171f103106f0764f45d4c35758e4baaa36dd55bf0eb5eba11c40ed8
  - blob 175850ebf5850febec3392568a6c8cfaef5f11a7
- wa01-alternative-runtime.yml.before-profile-test
  - SHA-256 a4433f1c6e32f52216469be47e759a0ec6eab5b3647db1d01c0b0e81510b9f15
  - blob b317caf36cd08802097bc9c2653f9f7404bbf5ac
- UserManagement.php.before-guru-siswa-scope
  - SHA-256 04b3f09f39d72998fcddc805b4b4320b80f1271b84297ee091cd0b313a8288f5
  - blob 5f90e3873cadeb77a1bcd5a6aa2134953e60beed
- user-management.blade.php.before-guru-siswa-filter
  - SHA-256 6bdc0dc6271f0725071bcb00888a1178abaca0f4cabf6c27ba3141866e25d730
  - blob 9284b7e3bdc8d1e8219b09edc4ba4c7d99af1693
- wa01-alternative-runtime.yml.before-admin-user-scope-test
  - SHA-256 35668e8430a7b25ece1061647e5488589ed45df81ff533971b5e985395b149e2
  - blob 0819d7e1cc4a1858426eb58e95c61f7d6d0baf0b

SHA-256 source akhir:
- routes/web.php: cfd432c071a8f3a03a03fad53cdd3faaad6c76d87218f7fc5b516f3811e0c99a
- app.blade.php: 94aee04047d663933edb580199a38c4c99e704e15815110fc5110e41ee045180
- admin-dashboard.blade.php: 657391d13df38743a740250bd87fb526c133cae3232f7ce42dd929d818a7a4ea
- TenantProfileManagement.php: f97408253940a247e95903fd23fbec3c1f2e6586b05439e13ac8d7311da5bd67
- tenant-profile-management.blade.php: cb66cff35059478115545247ad87da827d30424d89e333f9194efad7540c99da
- TenantProfileManagementTest.php: 2d94ae70a14c6ac2929d7bd58de66f2e2fc7d18ef8c2ebc2de1a65ed5d5be9f7
- UserManagement.php: 27f838e879d435b3bf74d5fab043c7e6b59767141788fdf52ef78322cafcd760
- user-management.blade.php: 98bb1b1f659cc3234c6ae10c30b7d00f7615154f0bf2e89b54013334f6c03e73
- AdminUserManagementScopeTest.php: a2f377c84b231f85e442df4538fb7fb2701f7c12a747b125ff339a86256284b2
- wa01-alternative-isolated-runtime.yml: fc6cb5f0c635deef91c8aa45c55f03de5012755bb257217d58275fb08df7caf1

## PENGUJIAN
PASS — GitHub Actions run #44926079149, commit 5d7b9307e9eeb0bc8afaa5edc94cde1739cfd93e.

Runtime:
- Ubuntu 24.04.
- PHP 8.3.37.
- Composer 2.11.2.
- MySQL 8.0.46, InnoDB.
- Hostname disposable: cbcdfca2cfe9.
- Database disposable: cbt_wa01_alt_44926079149_1.
- User aplikasi: wa01_alt_44926079149_1@%.
- GRANT hanya pada schema disposable; no global privilege/grant option.
- Schema kosong sebelum migration; tidak menggunakan production/VPS credentials.
- 71 base tables sesudah migration, semuanya InnoDB; required schema tersedia.
- Cleanup database dan user disposable berhasil.
- Artifact: wa01-alternative-runtime-44926079149-1, ID 14715696513, SHA-256 digest c068702e4eb28f2ae7501fb9418e72af8e4e920631fe53f8c5ce6fb19695850d, retention sampai 21 Desember 2026.

Test:
| Suite | Hasil | Assertions |
|---|---:|---:|
| AdminCbtBoundaryTest | 1 PASS | 27 |
| AdminDashboardAccessTest | 3 PASS | 28 |
| AcademicPeriodManagementTest | 3 PASS | 33 |
| TenantProfileManagementTest | 3 PASS | 18 |
| AdminUserManagementScopeTest | 3 PASS | 21 |
| AuthTest | 7 PASS | 38 |
| MultiTenantAuthTest | 3 PASS | 11 |
| TenantIsolationTest | 10 PASS | 16 |
| WebAdminAuthTest — Guru NIP | 1 PASS | 5 |
| StudentWebLoginTest | 8 PASS | 27 |
| StudentResultVisibilityTest | 7 PASS | 22 |
| TOTAL | 49 PASS | 246 |

- Composer validate/install/check-platform-reqs: PASS.
- PHP lint app/bootstrap/config/database/routes/tests: PASS.
- MySQL identity, empty schema, grant terbatas, Laravel DB config: PASS.
- Required schema dan seluruh tabel InnoDB: PASS.
- Upload profile/logo, tenant scoping, audit log: PASS.
- User list single-role Guru/Siswa, tenant isolation, role restrictions, audit log: PASS.
- Permission rows dan permission_role pivot tetap tidak berubah: PASS.
- Tidak menjalankan seeder, RolePermissionBootstrapper, atau production/VPS migration.

## BATAS YANG MASIH OPEN
- Paket 1 belum selesai: edit/deactivate akun, reset password, import Excel, penugasan Guru, promosi/kelulusan, master Kurmer-KBC versioned, laporan administratif, backup/restore, konfigurasi dan UAT masih perlu.
- Role legacy OPERATOR/ADMIN/PROKTOR dan portal Proktor permanen masih perlu dimigrasikan/dihapus secara fail-closed. Tidak ada permission_role pivot mutation yang disetujui.
- User status aktif/nonaktif belum tersedia.
- Tidak ada UAT/browser visual/performance/backup-restore/concurrency/Proktor tests.
- Tidak ada merge, deployment VPS, atau production DB changes.

## CHECKPOINT / LANGKAH BERIKUTNYA
1. Lanjutkan Stage 4: migrasi role legacy yang fail-closed, tanpa mutasi permission rows/pivot.
2. Selesaikan identitas/role/auth boundary dan portal Admin.
3. Lengkapi UserManagement (edit, status, reset password, import) dan modul administrasi lainnya.
4. Setelah seluruh acceptance Web Admin lulus, lanjut Portal Guru lalu Portal Siswa.
