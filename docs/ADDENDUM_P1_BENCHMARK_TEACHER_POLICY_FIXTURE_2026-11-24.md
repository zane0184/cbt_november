# Addendum P1 — Benchmark Fixture Mengikuti Policy Guru

Tanggal: 24 November 2026  
Repository: `zane0184/cbt_november`  
Branch: `work/p1-seven-targets-20261124`  
Source code commit: `f005cfc5fc23a4d8403a809f93128912e9817496`  
PR #23: OPEN / DRAFT / NOT MERGED

## A. AUDIT

CI pada SHA `872cb...`:
- PHPUnit: 547 PASS / 0 FAIL / 2.344 assertions.
- Disposable MySQL schema dibuat, migration dan workload berhasil, lalu database dihapus dan ketiadaannya diverifikasi.
- PDF export gagal dengan `AuthorizationException` pada `ReportController`.

Penyebab: fixture benchmark memakai Admin Madrasah untuk export hasil ujian. `ReportPolicy` memang membatasi akses hasil ujian kepada Guru yang memiliki permission `report.view` dan assignment aktif yang mencakup assessment serta seluruh peserta. Policy tidak boleh dilemahkan.

## B. DESAIN

Benchmark harus melewati authorization aplikasi yang sebenarnya:
- Guru dengan role tepat satu `GURU`.
- Permission `report.view` diberikan melalui `RolePermissionBootstrapper`.
- TeacherSubjectAssignment aktif untuk subject, rombel, academic year, semester.
- Assessment mem-pin curriculum version, owner, dan tenant curriculum registration yang sama dengan assignment.
- Seluruh peserta berada pada rombel assignment.
- Export tetap menggunakan `ReportController` dan policy asli.

## C. IMPLEMENTASI

- Menjalankan `CurriculumReferenceSourceSeeder` dan `CurriculumRegistryBootstrapSeeder` pada database benchmark sementara.
- Membuat registrasi tenant untuk versi resmi `KMA-1503-2025` dengan owner System Tenant.
- Membuat `TeacherSubjectAssignment` aktif dengan curriculum context snapshot.
- Menghubungkan setiap Assessment ke assignment, version, owner, dan registration yang sama.
- Menjalankan `RolePermissionBootstrapper` untuk grant resmi `report.view`.
- PDF benchmark sekarang dijalankan sebagai Guru, bukan Admin.
- Policy dan authorization tidak di-bypass.

Backup byte-exact:

`backend-laravel/backup_before_patch/P1_BENCHMARK_POLICY_FIXTURE_20261124/BACKUP_MANIFEST.md`

## D. PENGUJIAN

- PHPUnit sebelumnya: 547 PASS / 0 FAIL / 2.344 assertions.
- Benchmark PDF dengan assignment dan grant yang benar: menunggu CI exact source SHA `f005cfc5...`.
- Temporary MySQL database cleanup: menunggu verifikasi CI.
- Tidak ada database produksi atau VPS yang disentuh.

## E. STATUS

Benchmark MySQL disposable: IMPLEMENTASI DIPERBAIKI, NOT VERIFIED.  
P1-ID-03A: PARTIAL.  
P1-ID-03B: BLOCKED.  
PR #23 tetap Draft.

## F. NEXT ACTION

Jalankan CI exact HEAD. Jika gagal, periksa policy/assignment/FK pada fixture; jangan membuat bypass authorization. Setelah benchmark lulus, lanjutkan port account lifecycle secara selektif dari branch W1.
