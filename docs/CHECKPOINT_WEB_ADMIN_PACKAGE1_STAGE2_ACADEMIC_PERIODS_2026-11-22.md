# CHECKPOINT — PAKET 1 WEB ADMIN / STAGE 2 PERIODE AKADEMIK

Tanggal: 22 November 2026
Repository: zane0184/cbt_november
Branch: work/web-admin-package1-implementation-20261122
Commit source yang diuji: 99c2f4ef4ee77f9b05ad85a4ca406425c2dc2720
Run: https://github.com/zane0184/cbt_november/actions/runs/44924891185

## AUDIT
- Komponen Data Akademik sebelumnya belum menyediakan alur pengelolaan tahun ajaran dan semester aktif yang lengkap.
- Data model AcademicYear dan Semester sudah memiliki flag is_active, tetapi belum ada constraint database untuk memastikan maksimal satu record aktif per tenant.
- Aktivasi harus eksplisit oleh Admin; tidak boleh memilih tahun/semester otomatis.
- Perubahan tahun ajaran harus mengakhiri semester aktif lama, lalu meminta Admin memilih semester baru.
- Seluruh pemeriksaan menggunakan database MySQL 8 disposable; tidak ada database produksi/VPS yang disentuh.

## DESAIN
- Tahun ajaran baru yang dapat dibuat: 2026/2027, 2027/2028, 2028/2029, 2029/2030, 2030/2031, 2031/2032.
- Pembuatan tahun ajaran/semester selalu is_active=false.
- Admin memilih satu tahun ajaran aktif melalui aksi eksplisit.
- Pergantian tahun ajaran menonaktifkan semester aktif terdahulu; sistem tidak mengaktifkan semester baru secara otomatis.
- Admin memilih semester Ganjil atau Genap secara eksplisit, hanya untuk tahun ajaran yang sedang aktif.
- Tenant row menjadi mutex transaksi untuk serialisasi perubahan periode. Query record aktif menggunakan lockForUpdate.
- Audit log dibuat untuk create, activation, dan semester deactivation akibat year change.
- Database constraint MySQL 8 memakai unique functional index dengan ekspresi CASE agar maksimal satu tahun ajaran aktif dan maksimal satu semester aktif per tenant. SQLite test runtime memakai partial unique index.
- Migration melakukan preflight dan gagal tertutup jika data lama memiliki lebih dari satu tahun/semester aktif. Migration tidak memilih atau menonaktifkan data lama secara otomatis.

## IMPLEMENTASI
File source:
1. backend-laravel/app/Livewire/Admin/AcademicManagement.php
   - Tab Tahun Ajaran & Semester.
   - Create year/semester, explicit activation, tenant locking, audit log, tenant/role/permission validation.
2. backend-laravel/resources/views/livewire/admin/academic-management.blade.php
   - Daftar tahun ajaran dan semester.
   - Modal pembuatan tahun/semester.
   - Status aktif/riwayat dan instruksi eksplisit.
3. backend-laravel/database/migrations/2026_11_22_000001_enforce_single_active_academic_periods.php
   - Preflight duplicate active rows.
   - MySQL 8 functional unique indexes dan SQLite partial unique indexes.
4. backend-laravel/tests/Feature/AcademicPeriodManagementTest.php
   - 3 test: create/activation/audit/no-auto-activation; batas rentang tahun; database unique constraint.
5. .github/workflows/wa01-alternative-isolated-runtime.yml
   - Runner memasukkan test periode akademik.
   - Static guard memastikan test tidak menulis permission rows/pivot.
   - Post-migration guard memverifikasi tabel schema penting dan InnoDB.

Backup exact sebelum perubahan:
- docs/backup/web-admin-package1-2026-11-22/AcademicManagement.php.before-period-management
  - SHA-256: 2964356889401156bf586f052fa7b9be67ffba7f1a3bfdc548b342bdfe22ea44
  - Git blob: 5afd0780fa642354c11954fce307d8e6f7e4576f
- docs/backup/web-admin-package1-2026-11-22/academic-management.blade.php.before-period-management
  - SHA-256: af866459997774f41173b9081a1c0bd143a2808a32894f7f6e0f78b804f3f1a5
  - Git blob: dd00edc31b554e4200dca1ced858b81d474a629c
- docs/backup/web-admin-package1-2026-11-22/wa01-alternative-runtime.yml.before-period-management
  - SHA-256: 8bb9cd2d67aead78e084e67e7fb9bb9ae2d08a0305e932651332234cb4493c55
  - Git blob: ac52afe4b22c8c8b3ad3b3bd8a991662dc11f9e3

Backup iterasi perbaikan migration/runner:
- academic-period-migration.php.before-runtime-warning-fix — SHA-256 ef9d39cbd3ff6f9bf00ec6199bae79075a58fa85d817142171e2595de93ad1f9; blob 50a972511cc91675b9249f50ff5de4dbbb059492.
- academic-period-migration.php.before-fqcn-slash-fix — SHA-256 27183e00c46efdc57ea1563ea213a314c26b4b58f1b3e6103920236cdabdae81; blob c89b1db3af4b42af86c60c296510dc5eb0df2e91.
- academic-period-migration.php.before-functional-index — SHA-256 9a4e000a94e978e31c2565e1a898f20b1a4d289030c5d739a6f3cd78e3e934ca; blob 260c9da30b176d91b942ffd19f219232c091a073.
- wa01-alternative-runtime.yml.before-period-runner-cleanup — SHA-256 fe08047be3f6fe75317d660b69b89ba5a85d11ca400bc6266fa6c4b168e23e6d; blob e0ce3cdfb0ba0222298c698e2c8ecb3580072620.
All backups are stored under docs/backup/web-admin-package1-2026-11-22/.

SHA-256 source akhir:
- AcademicManagement.php: e717a319b8268c56c716ff377617ff09d64594651edc92d651b9eaf4d729422b
- academic-management.blade.php: f10ba8d6106ec1d187102b60d56263838465587f657d843c3633a30d6bbefec4
- enforce_single_active_academic_periods.php: 793f28c1dcd6005d42bc9d2bbc8104dc9b80b590073f400bac009c27f3666067
- AcademicPeriodManagementTest.php: d68c030d7409e610697f1d634659e804d8ddc4e6829c3bafb9ca52040c6127dd
- wa01-alternative-isolated-runtime.yml: 534bdbea44f9e81a546aac31c705ce877ce87d47a8b8813112c7632dd4e0cb19

## PENGUJIAN
PASS — GitHub Actions run #44924891185, commit 99c2f4ef4ee77f9b05ad85a4ca406425c2dc2720.

Lingkungan:
- Ubuntu 24.04.
- PHP 8.3.37.
- Composer 2.11.2.
- MySQL 8.0.46, InnoDB.
- Hostname MySQL disposable: d3c33f6469f3.
- Database: cbt_wa01_alt_44924891185_1.
- User aplikasi: wa01_alt_44924891185_1@%.
- User aplikasi hanya mendapat GRANT pada schema tersebut; tidak ada global privilege/grant option.
- Schema kosong sebelum migration; production/VPS credentials tidak digunakan.
- 71 base tables setelah migration; semua InnoDB; required schema tables tersedia.
- Cleanup database dan user disposable berhasil.
- Artifact wa01-alternative-runtime-44924891185-1 tersedia 30 hari.

Test:
| Test | Hasil | Assertions |
|---|---:|---:|
| AdminCbtBoundaryTest | 1 PASS | 27 |
| AdminDashboardAccessTest | 3 PASS | 28 |
| AcademicPeriodManagementTest | 3 PASS | 33 |
| AuthTest | 7 PASS | 38 |
| MultiTenantAuthTest | 3 PASS | 11 |
| TenantIsolationTest | 10 PASS | 16 |
| WebAdminAuthTest — login Guru NIP | 1 PASS | 5 |
| StudentWebLoginTest | 8 PASS | 27 |
| StudentResultVisibilityTest | 7 PASS | 22 |
| TOTAL | 43 PASS | 207 |

- Composer validate/install/check-platform-reqs: PASS.
- PHP lint app/bootstrap/config/database/routes/tests: PASS.
- MySQL identity/grant/empty schema: PASS.
- Laravel resolved DB configuration: PASS.
- Migration preflight and functional unique indexes: PASS.
- Academic year/semester explicit activation and audit log: PASS.
- Switching year deactivates old semester and does not auto-select new semester: PASS.
- Database rejects a second active year and a second active semester for one tenant: PASS.
- Permission rows and permission_role pivot counts remain unchanged in selected tests: PASS.
- No seeder, RolePermissionBootstrapper, or production/VPS database operation was executed.

## BATAS YANG MASIH OPEN
- Stage 2 hanya menyelesaikan periode akademik; belum seluruh Web Admin.
- Migration diuji pada schema kosong/disposable. Data historis dengan duplicate active year/semester sengaja membuat migration fail closed dan memerlukan rekonsiliasi eksplisit; tidak ada auto-fix.
- Pengujian tidak mensimulasikan dua koneksi concurrent activation secara paralel. Tenant row lock dan unique functional indexes sudah ada; uji concurrency terpisah masih diperlukan.
- Profil/identitas madrasah, manajemen Guru/Siswa lengkap, assignment Proktor, import Excel, master Kurmer-KBC versioned, laporan, backup/restore, profile/password dan UAT masih OPEN.
- Role legacy OPERATOR/ADMIN/PROKTOR masih harus direkonsiliasi fail-closed.
- Tidak ada merge, deployment VPS, atau perubahan database produksi.

## CHECKPOINT / LANGKAH BERIKUTNYA
1. Lanjutkan Paket 1 Stage 3: identitas/profil madrasah dan data pengguna Guru/Siswa.
2. Pertahankan period service sebagai satu-satunya jalur perubahan status periode.
3. Tambahkan pengujian concurrency dua koneksi sebelum menyatakan aktivasi periode tahan race.
4. Lanjutkan hingga seluruh acceptance Web Admin selesai, kemudian Portal Guru, lalu Portal Siswa.
