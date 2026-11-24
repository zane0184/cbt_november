# Laporan Implementasi P0 — ZIP Uji Operasional Terbatas

- Baseline Agent 1 diverifikasi: `a65b4604495e8bcc153bf0e09df12a9efb11543a` pada `work/benchmark-cleanup-proof-20261125`.
- Implementasi di branch terpisah: `work/p0-academic-provisioning-20261125`; source commit diuji: `61de22182c9804df4d8749c901069a11046024dc`.
- CI run `45498672360`, PHP 8.3 / MySQL 8.0 InnoDB: **190 passed (935 assertions)**.

## AUDIT

- Installer sebelumnya membuat Tenant/Admin, tetapi belum menyiapkan katalog akademik lokal untuk Rombel, assignment, dan Siswa.
- Model/tabel AcademicYear, Semester, Curriculum, Phase, GradeLevel sudah ada; tidak diperlukan migration/tabel baru.
- Reserved System Tenant adalah namespace registry internal per database instalasi, bukan madrasah kedua. Guard identitas instalasi kini menghitung tepat satu tenant madrasah lokal.
- Test menemukan dua bug P0 pada assignment Guru: `User::trashed()` dipanggil pada model tanpa trait SoftDeletes, dan komponen/view memanggil relasi `teacherProfile` yang tidak ada pada model assignment.

## DESAIN

- Pakai struktur/model yang ada; tidak membuat tabel baru.
- Provisioning tenant lokal: tahun 2026/2027–2031/2032, semester Ganjil/Genap setiap tahun, kelas MI 1–6 dengan fase A untuk 1–2, B untuk 3–4, C untuk 5–6.
- Tidak ada semester aktif default. Admin memilih semester aktif.
- Provisioning idempotent, transactional, tenant-context scoped, locked, mempertahankan histori, dan fail-closed pada konflik/record terhapus.
- Admin-only dengan `academic.manage`, audit log, tanpa aksi hapus/overwrite histori.
- CP tidak disentuh sampai artefak resmi tersedia.

## IMPLEMENTASI

- `TenantAcademicBootstrapService.php` — katalog akademik tenant lokal.
- `AcademicYearManagementService.php` — create tahun dan aktivasi semester yang tenant-scoped, transactional, audited, Admin-only.
- `AcademicManagement.php` + Blade — UI tahun pelajaran/semester.
- `InstallerService.php` — seeder registry, bootstrap katalog lokal, postcondition checks.
- `InstallationIdentityService.php` — hitung tenant madrasah dengan mengecualikan reserved System Tenant.
- `TeacherSubjectAssignmentManagement.php` + view — validasi akun aktif/deleted_at dan relasi `teacher()` yang benar.
- Test baru: `TenantAcademicBootstrapServiceTest`, `AcademicYearManagementTest`, `TeacherSubjectAssignmentManagementTest`; `InstallerEndToEndTest` diperluas.
- `tenant-backend-ci.yml` — job P0 PHP 8.3/MySQL 8/InnoDB.
- `build-web-deployment.yml` + `DISTRIBUTION_EXCLUSIONS.md` — `backups/` dikecualikan dan diperiksa tidak masuk ZIP.

Backup:
- 13/13 backup pra-patch SHA-256 cocok; lihat `BACKUP_MANIFEST.md`.
- Hash file implementasi: `IMPLEMENTATION_SHA256_MANIFEST.md`.
- Tidak ada migration berubah/ditambahkan, database produksi/VPS/deployment disentuh, atau PR #23/#24 diubah/di-merge. Branch audit Agent 2 tidak diubah.

## PENGUJIAN

- CI `45498672360` pada commit `61de22182c9804df4d8749c901069a11046024dc`: PHP 8.3; MySQL 8.0/InnoDB; Composer validate/install/platform requirements PASS; PHP lint PASS; disposable DB identity check PASS; test P0 **190 passed (935 assertions)**.
- Mencakup provisioning idempotent, pengelolaan tahun/semester, assignment Guru aktif/nonaktif, clean-install E2E, Admin UI, login Admin/Siswa, ujian, Attempt/Answer/Submit/Score, RBAC, tenant isolation, concurrency.
- Clean-install E2E: PASS pada MySQL disposable. Test memakai license pilot fixture terisolasi; Publisher activation nyata belum diuji.
- Import Siswa tidak diulang: run sebelumnya `45482223886` pada baseline `a65b460...`, 20 passed/77 assertions pada PHP 8.3/MySQL 8 dan PHP 8.4/MySQL 8.
- Benchmark tidak diulang; sebelumnya 1 passed/20 assertions. Full suite tidak diulang.

## STATUS P0 / RELEASE GATE

| Item | Status | Catatan |
|---|---|---|
| P0-01 Provisioning akademik/Admin/Guru/Siswa/rombel/assignment | **PASS source/CI** | Browser operasional penuh masih release gate |
| P0-02 CP resmi/checksum | **BLOCKED** | Memerlukan artefak resmi |
| Gate 1 Clean-install E2E | **PASS** | MySQL 8/InnoDB disposable |
| Gate 2 Browser E2E | **BLOCKED** | CP resmi belum tersedia; browser E2E belum diklaim |
| Gate 3 ZIP exact commit | **NOT VERIFIED** | Workflow hardened, tetapi belum dijalankan dan artifact belum diverifikasi |

**Keputusan: ZIP BELUM SIAP** karena P0-02 dan Gate 2/3 belum lulus.
