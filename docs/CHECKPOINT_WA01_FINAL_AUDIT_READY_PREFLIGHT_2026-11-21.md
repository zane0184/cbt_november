# CHECKPOINT AUDIT AKHIR WORKFLOW WA-01 — SIAP PREFLIGHT — 2026-11-21

## Status keputusan
- AUDIT WORKFLOW: selesai.
- AUDIT JALUR TEST TERPILIH: selesai pada source yang diperiksa.
- WORKFLOW: dipertahankan dan diperbaiki hanya pada file workflow terpisah WA-01.
- STATUS EKSEKUSI: BELUM DIJALANKAN.
- MIGRATION / SEEDER / ROLEPERMISSIONBOOTSTRAPPER / PHPUNIT: TIDAK DIJALANKAN.
- Status: SIAP UNTUK TAHAP PREFLIGHT SAJA. Bukan izin otomatis untuk tahap execute.

## AUDIT

### Identitas source dan workflow
Repository: `zane0184/cbt_november`

Implementation branch:
`work/wa01-package0-implementation-20261121`

Implementation branch HEAD setelah audit workflow:
`11f9726498db4be7d7b020560c286594439017d6`

Default branch HEAD setelah sinkronisasi dispatcher:
`7467e70ebf37cd94053e223603c554ee6fce1847`

Final workflow blob SHA pada kedua branch:
`dad05b7f8134dd34d9c94a2e28bc666cdbbf5bfe`

Workflow kedua branch diverifikasi byte-identical.

WA-01 application source baseline yang sebelumnya diverifikasi:
`11ab8e437af66fd6f35af81dfbcdec4fd391567f`

Pinned W0 baseline:
`2b4321ce032fd5e67cf022da8564a5287b8cef6f`

### A. Audit workflow

1. Trigger hanya `workflow_dispatch`, input phase pilihan `preflight` atau `execute`, default `preflight`.
2. Job-level guard mensyaratkan tepat `refs/heads/work/wa01-package0-implementation-20261121`. Trigger dari branch lain boleh terlihat, tetapi job akan skipped.
3. Preflight menjalankan setup PHP/Composer, Composer validate/install tanpa scripts, package discovery dengan koneksi SQLite sementara, pembuatan database disposable kosong, pemeriksaan MySQL identity/grants, dan verifikasi Laravel resolved DB config. Tidak ada migration, seeder, PHPUnit, atau RolePermissionBootstrapper pada phase ini.
4. Execute mengulang pembuatan database dan kedua gerbang identitas (PDO MySQL dan resolved Laravel config) sebelum PHP lint dan PHPUnit.
5. Laravel DB config gate memastikan default connection = mysql, driver/host/port/database/user cocok dengan database run-specific, user bukan root, password cocok dengan kredensial aplikasi sementara, dan menolak DB_URL, config cache, atau installer DB override.
6. Root tidak berada pada job-wide environment. Root hanya tersedia sebagai environment lokal untuk langkah provisioning dan cleanup MySQL service. Health check memakai `mysqladmin ping` tanpa root.
7. Aplikasi menggunakan user unik dengan grant hanya pada schema `$DB_NAME.*`; tidak diberi global grant atau grant option. Preflight memeriksa SHOW GRANTS dan menolak privilege global selain USAGE.
8. Nama database dan user memuat `GITHUB_RUN_ID` dan `GITHUB_RUN_ATTEMPT`; retry mendapat identitas baru.
9. Password aplikasi dibuat acak dan diberi GitHub Actions mask sebelum ditulis ke GITHUB_ENV. Password root service bersifat ephemeral berbasis run ID/attempt dan diberi mask. Tidak ada GitHub Secrets atau kredensial/hostname database produksi/VPS.
10. Cleanup menggunakan `if: always()`, sehingga tetap dicoba setelah job sukses, gagal, atau dibatalkan; cleanup menghapus schema dan user, kemudian service MySQL dihancurkan bersama job.
11. Pemeriksaan InnoDB menggunakan agregasi `information_schema.tables` per ENGINE, berjalan dengan `if: always()` setelah execute jika preflight berhasil. Gagal jika tidak ada tabel atau ada satu pun tabel non-InnoDB.
12. Log preflight hanya menampilkan identitas database/user, versi, engine, dan grants; tidak mencetak password. Log aplikasi juga menerima masking credential.
13. Tidak ada referensi GitHub Secrets, VPS hostname, atau production DB host pada workflow.

Existing workflows tidak diubah:
- `.github/workflows/tenant-backend-ci.yml`
- `.github/workflows/p0-95d-fresh-chain.yml`
- `.github/workflows/build-web-deployment.yml`
- `.github/workflows/w0-source-snapshot-verify.yml`

`p0-95d-fresh-chain.yml` tetap dikecualikan karena menjalankan migration chain, schema dump/replay dan rollback guard.

### B. Audit jalur test sebelum eksekusi

Source yang diperiksa:

- `tests/TestCase.php`: setUp hanya reset TenantContext dan set tenant null; tearDown hanya membersihkan TenantContext. Helper `bootstrapCbtRolePermissions()` memang ada dan memanggil RolePermissionBootstrapper, tetapi tidak dipanggil dari setUp/tearDown.
- `bootstrap/providers.php`: hanya mendaftarkan AppServiceProvider.
- `AppServiceProvider.php`: register TenantContext, listener Authenticated untuk tenant context, dan Gate policy. Tidak memanggil RolePermissionBootstrapper.
- `User.php`: model boot callback hanya menentukan tenant_id dan username unik. Tidak memanggil bootstrapper.
- Model fixture yang diperiksa (Tenant, Role, Permission, Subject, AcademicYear, Curriculum, Assessment, Exam, ExamAttempt, StudentProfile, TeacherProfile, LocalInstallationIdentity) tidak memiliki referensi RolePermissionBootstrapper.
- Selected test classes selain AdminCbtBoundaryTest tidak memiliki referensi langsung ke RolePermissionBootstrapper/helper, seeder, atau factory.
- Selected test classes tidak meng-override tearDown; setUp masing-masing hanya membuat fixture data dan role, tanpa bootstrapper.
- Test tidak memanggil factory callback. Tidak ada seeder yang dipanggil workflow. RefreshDatabase menjalankan migration pada DB disposable; tidak otomatis menjalankan seeder tanpa konfigurasi seeding yang diminta test.
- Tidak ditemukan pemanggilan bootstrapper dari provider atau callback model yang diperiksa.

Pengecualian yang dikunci:
- `AdminCbtBoundaryTest::test_admin_bootstrap_grants_only_administrative_and_temporary_proctor_assignment_permissions` secara eksplisit memanggil RolePermissionBootstrapper; dikecualikan melalui filter method yang tepat.
- `RolePermissionBootstrapperTest` dikecualikan.
- `AdminUiVerificationTest` dikecualikan karena setUp memanggil helper bootstrap.
- `TeacherPracticeWorkflowTest` dikecualikan karena setUp memanggil helper bootstrap.

Test Admin CBT yang dipilih:
`test_admin_with_stale_guru_role_and_permissions_is_denied_all_cbt_surfaces`
Method tersebut tidak memanggil bootstrapper. Ia membuat permission rows dan role-permission pivot sebagai fixture untuk mensimulasikan stale grants, lalu menguji Gate, route dan Livewire denial. Penulisan fixture tersebut hanya terjadi pada schema disposable jika execute kelak dijalankan; bukan pada DB produksi. Karena ini merupakan mutasi tabel permission di database uji, hal tersebut diungkapkan secara eksplisit dan perlu dipastikan termasuk persetujuan tahap execute sebelum PHPUnit dijalankan.

### C. Audit nama file dan method serta cakupan

Nama file dan method yang dipanggil workflow sudah ditemukan pada branch implementasi:
- `AdminCbtBoundaryTest.php`: `test_admin_with_stale_guru_role_and_permissions_is_denied_all_cbt_surfaces`
- `AuthTest.php`
- `MultiTenantAuthTest.php`
- `TenantIsolationTest.php`
- `AssessmentPolicyTeacherCreateTest.php`
- `WebAdminAuthTest.php`: `test_teacher_can_login_using_nip`
- `StudentWebLoginTest.php`
- `StudentResultVisibilityTest.php`

Workflow execute menambahkan static guard untuk keberadaan file/class/method, referensi bootstrapper dalam method Admin yang dipilih, pemanggilan helper dari TestCase setUp/tearDown, provider, seeder dan factory pada jalur test yang dipilih.

Cakupan:
- Admin CBT boundary dan Livewire denial: satu method terpilih.
- API authentication/authorization: AuthTest dan MultiTenantAuthTest.
- Tenant isolation: TenantIsolationTest.
- Guru: AssessmentPolicyTeacherCreateTest dan satu method login NIP.
- Siswa: StudentWebLoginTest dan StudentResultVisibilityTest.
- PHP lint: app, bootstrap, config, database, routes, tests.
- InnoDB: semua base table hasil migration diperiksa via agregasi ENGINE.

Batasan cakupan:
- Tidak menguji seluruh lifecycle Guru, bank soal, atau pembuatan/penerbitan ujian.
- Test Guru bank soal/lifecycle yang bergantung pada helper bootstrapper tetap dikecualikan.
- Jangan menyatakan seluruh lifecycle Guru lulus berdasarkan subset ini.

## DESAIN

Final runner:
- GitHub-hosted `ubuntu-24.04`.
- PHP 8.3.
- Composer v2; `composer.lock` dipakai dengan `composer install`, tanpa update lock.
- Ekstensi PHP disediakan eksplisit; `composer check-platform-reqs` memverifikasi kebutuhan aktual lock.
- MySQL service `mysql:8.0`, default engine diverifikasi InnoDB.
- DB/user unik per run dan attempt.
- Laravel hanya boleh menggunakan user aplikasi terbatas.
- Preflight tidak menjalankan migration atau PHPUnit.
- Execute hanya setelah preflight ditinjau dan persetujuan execute diberikan.
- Artefak log disimpan 30 hari.

## IMPLEMENTASI

File workflow final:
`.github/workflows/wa01-disposable-mysql-test.yml`

Final workflow blob SHA:
`dad05b7f8134dd34d9c94a2e28bc666cdbbf5bfe`

Branch implementation:
- Commit workflow final terakhir: `11f9726498db4be7d7b020560c286594439017d6`
- Branch head sebelum pembaruan referensi checkpoint: `7824fdac9f549c0b79c18c3d1d3b3ebe5f0a55d5`. Commit checkpoint awal: `7824fdac9f549c0b79c18c3d1d3b3ebe5f0a55d5`. Setelah pembaruan referensi ini, branch head akan menunjuk commit dokumentasi berikutnya.

Default branch dispatcher:
- Commit sinkronisasi workflow final: `7467e70ebf37cd94053e223603c554ee6fce1847`
- Workflow di main identik dengan implementation branch.
- Job guard tetap menolak eksekusi pada main; sinkronisasi hanya agar GitHub menyediakan workflow_dispatch UI dari default branch.

Backup sebelum perubahan:
- `docs/backup/wa01-runner-final-audit-prepatch.yml` — blob awal `eaa889065234bab93ffa5e98eb35b1019373a0be`, commit `96b9596567d31c84f93e8d4d02910a4557b5053f`.
- `docs/backup/wa01-runner-laravel-config-prepatch.yml` — blob `afd17e84c751742c652679f8db5755a1a81cb8dc`, commit `b81bcdd2952ad2cbc4d46be30ec0c32b3890db99`.
- `docs/backup/wa01-runner-shell-continuation-prepatch.yml` — blob `32e8a285019b1a7d99a139a8d402237eaff34fb4`, commit `af09deeda7ef5fef6476bf62db49589bb02c1ae2`.
- `docs/backup/wa01-main-workflow-prepatch-2026-11-21.yml` — backup default branch sebelum sinkronisasi, commit `d3594f480a578c5a9cb6b0bf18e2176f2164273f`.

Perubahan hanya pada workflow WA-01 dan dokumentasi backup/checkpoint. Tidak ada source aplikasi, test aplikasi, atau empat workflow lama yang diubah. Tidak ada PR atau deployment.

## PENGUJIAN

Tidak ada workflow yang dijalankan. Tidak ada migration, seeder, PHPUnit, PHP lint, atau test runtime yang dijalankan dalam audit ini.

Yang dilakukan hanya inspeksi source GitHub dan verifikasi statis isi workflow, identitas file, branch guard, kondisi cleanup, sinkronisasi blob kedua branch, serta jalur pemanggilan test dari source.

### Keputusan
WORKFLOW AMAN UNTUK TAHAP PREFLIGHT SAJA.

Jangan pilih phase execute sebelum hasil preflight ditinjau. Sebelum execute, konfirmasikan bahwa fixture permission rows/pivots yang dibuat oleh test Admin CBT di schema disposable termasuk dalam persetujuan pengujian. Persetujuan tersebut tidak memberi izin menjalankan RolePermissionBootstrapper, seeder, atau mutasi permission pada database produksi/VPS.
