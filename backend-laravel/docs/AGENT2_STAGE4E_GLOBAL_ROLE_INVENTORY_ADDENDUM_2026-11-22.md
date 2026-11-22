# Stage 4E — Global Active-Source Role Inventory Addendum

Tanggal: 22 November 2026 (WIB)  
Repository: `zane0184/cbt_november`  
Branch: `work/stage4d-account-lifecycle-20261122`  
Source runtime baseline: `74bf7178f46673e9be90523671490c0749034acd`  
Parent audit: `AGENT2_STAGE4E_EXACT_ROLE_KAMAD_OPERATOR_INSTALLATION_AUDIT_2026-11-22.md`  
Mode: READ-ONLY; no runtime patch, test execution, migration or database mutation.

## 1. Cakupan file-level yang selesai dipindai

Pemeriksaan pola role/permission dilakukan pada seluruh file aktif berikut di tree baseline Agent 2:

| Area | Jumlah file | Status |
|---|---:|---|
| `backend-laravel/app/` | 181 PHP | Dipindai seluruhnya |
| `backend-laravel/database/` | 81 PHP (factory, migration, seeder) | Dipindai seluruhnya |
| `backend-laravel/tests/` | 80 PHP | Dipindai seluruhnya |
| `backend-laravel/resources/` | 39 PHP/Blade | Dipindai seluruhnya |
| `backend-laravel/routes/` | 3 PHP | Dibaca seluruhnya |
| `backend-laravel/bootstrap/` | 2 PHP | Dibaca seluruhnya |
| `backend-laravel/config/` | 4 PHP | Dibaca seluruhnya |
| `backend-laravel/public/index.php` | 1 PHP | Dibaca |
| **Total file aktif** | **391** | **Static file-level scan selesai** |

Tree menunjukkan 42 file PHP tambahan di `backend-laravel/backup_before_patch/`; semuanya merupakan snapshot historis/backup, bukan file aktif runtime. Backup tidak digabungkan sebagai active call-site. Backup relevan tetap harus dipertahankan dan tidak dihapus.

**Batas:** scan file-level dan pemeriksaan entrypoint statis tidak sama dengan pembuktian seluruh call graph dinamis. Pemanggilan melalui dependency injection, event/listener, dynamic component, macro, queued job dan external integration masih harus ditelusuri serta dibuktikan dengan test. Tidak ada runtime test yang dijalankan.

## 2. AUDIT — tambahan temuan migration/seeder

1. `2024_01_01_000004_create_roles_permissions_tables.php` masih memiliki komentar schema yang mencantumkan role tenant `ADMIN_MADRASAH, OPERATOR, KAMAD, GURU, SISWA`. Ini komentar historis yang tidak lagi sesuai keputusan portal.
2. `2026_11_22_000003_migrate_legacy_roles_to_canonical_roles.php` memanggil `LegacyRoleMigrationService::migrate()`. Bila migration dijalankan, ia mengubah `role_user`, `users.role` dan nama role legacy, walaupun membuat `legacy_role_migration_records` untuk pemulihan histori.
3. `DatabaseSeeder` membuat role `ADMIN_MADRASAH, KAMAD, GURU, SISWA` dan memanggil RolePermissionBootstrapper. Ini berarti role KAMAD masih dibuat oleh seeder.
4. `RolePermissionBootstrapper` mewajibkan role KAMAD. Menghapus role KAMAD dari bootstrap aktif harus diselaraskan dengan installer, seeder, test base class dan fixtures; jangan sekadar menghapus row atau permission dari database.
5. `InstallerService` membuat definisi role KAMAD dan memeriksa keberadaan role legacy ADMIN/OPERATOR/PROKTOR. Perlu patch plan agar instalasi baru tidak membuat KAMAD sebagai role akses, dan legacy retirement tidak memaksa transformasi akun tanpa inventaris/approval.
6. Migration Proktor `2026_11_20_100001_create_exam_proctor_assignments_table.php` melindungi histori assignment pada rollback. Ini sesuai prinsip mempertahankan audit/history; jangan menghapus assignment historis.
7. Migration `2026_11_22_100001_enforce_confirmed_proctor_assignment_capability.php` mengubah status legacy ACTIVE menjadi NEEDS_REVIEW. Tidak dijalankan dalam audit ini.

Tidak ada migration dijalankan, tidak ada schema/database yang diubah.

## 3. AUDIT — konflik test/fixture terhadap keputusan portal final

### 3.1 Konflik langsung yang wajib direkonsiliasi

| File test | Ekspektasi/fixture saat ini | Konflik dengan keputusan final |
|---|---|---|
| `tests/Feature/WebAdminAuthTest.php` | Menguji login OPERATOR ke `/operator/dashboard`, KAMAD ke `/`, PROKTOR ke `/proktor/assignments`. | Ketiga login/portal tersebut tidak boleh aktif. Admin kini redirect ke `/admin/dashboard`. |
| `tests/Feature/RoleDashboardVerificationTest.php` | Membuat role OPERATOR/KAMAD/PROKTOR; mengharapkan `/proktor/assignments` untuk PROKTOR dan monitoring KAMAD HTTP 200. | Menghidupkan kembali role/portal Proktor permanen dan akses KAMAD. |
| `tests/Feature/RolePermissionBootstrapperTest.php` | Membuat KAMAD dan optional OPERATOR; menguji grant KAMAD. | Harus diselaraskan ke tiga role aktif; OPERATOR bukan role aktif. |
| `tests/TestCase.php` | Helper bootstrap test membuat role KAMAD sebelum RolePermissionBootstrapper. | Fixture global membuat KAMAD seolah role wajib. |
| `tests/Feature/SeederSafetyTest.php` | Mengharapkan role KAMAD ada setelah seeding. | Bertentangan dengan freeze KAMAD sebagai akun/role akses CBT. |
| `tests/Feature/LegacyRoleMigrationServiceTest.php` | Mengharapkan OPERATOR → ADMIN_MADRASAH dan PROKTOR → GURU melalui migration service. | Tidak boleh menjadi alasan menjalankan transformasi produksi sebelum inventory dan approval. |
| `tests/Feature/Phase7BManualReviewTest.php`, `Phase7CReopenTest.php`, `Phase7DHistoricalRecalculationAndAnalyticsTest.php`, `Phase7DReportingTest.php` | Beberapa fixture membuat role KAMAD/PROKTOR. | Fixture lama perlu diganti dengan skenario role aktif/assignment yang benar. |
| `tests/Feature/ScoringHardeningTest.php` | Menggunakan OPERATOR/PROKTOR sebagai identitas untuk uji deny reopen. | Tujuan deny masih relevan, tetapi fixture harus menjadi skenario role legacy yang tidak dapat login atau role tidak dikenal, bukan spesifikasi portal/role aktif. |

Pada tree aktif, `OperatorDashboard` dan `ProktorDashboard` tidak ditemukan sebagai class aktif; snapshotnya hanya ada di backup historis. `RoleDashboardVerificationTest` masih mengimpor kedua class dan memanggil Livewire::test terhadapnya. Status aktual PHPUnit belum diketahui karena test tidak dijalankan.

### 3.2 Test positif yang mendukung exact-role

- `AdminCbtBoundaryTest`: mencakup Guru+Siswa mixed identity dan Admin dengan role stale Guru/Siswa, mengharapkan deny pada CBT surfaces.
- `StudentWebLoginTest`: memiliki test SISWA+GURU ditolak saat login.
- `AdminDashboardAccessTest`: membuat Admin+Guru dan mengharapkan area CBT Guru ditolak.
- `TeacherPortalFoundationTest` pada branch Agent 3: blob `4f1bb48b390b03b2602df6348fd3b9788581e6c9`, 22 test methods statis; ini belum dibuktikan lulus PHPUnit.
- `QuestionPolicy` dan `ExamProctorAssignmentService` memiliki exact-one Guru helper/eligibility logic.

Test positif tersebut tidak menutup seluruh kombinasi KAMAD+Guru, OPERATOR+Admin, role unknown/empty, session/token lama, API, export/media, scheduler dan Livewire post-hydration. Matriks exact-role lengkap tetap harus dibuat.

### 3.3 Test Proktor dan monitoring yang sudah tersedia

- `ProctorAssignmentSecurityTest`: 8 metode; assign, confirm, revoke, expiry, overlap, target Guru/profile dan tenant reference.
- `MonitoringDuplicationTest`: 15 metode; termasuk Proktor room scope, exam author, assignment pending/confirmed/revoked/expired, tenant boundary, mixed-role Guru, completed session dan soft-deleted TeacherProfile.
- `ExamRoomSessionConcurrencyTest`: 2 metode; lock/concurrency room attach dan Proktor confirmation.

Ini adalah bukti adanya test source, bukan bukti test pass. Tidak ada PHPUnit atau concurrency test yang dijalankan pada Stage 4E ini.

## 4. AUDIT — Blade/menu dan route

1. `resources/views/layouts/app.blade.php` mengidentifikasi Admin/Guru menggunakan `hasRole()`, bukan exact-one. Menu assignment Proktor saat ini berada pada menu Admin melalui permission `exam.proctor.assign`. Tidak ditemukan menu Proktor di dalam bagian Ruang Kerja Guru.
2. `routes/web.php` mendaftarkan `/admin/proctor-assignments` untuk komponen Admin. Tidak ada route `/proktor/*` aktif. Route `/operator/dashboard` merupakan redirect compatibility ke `/admin/dashboard`; tujuan akhirnya tetap harus memastikan exact-one Admin dan permission.
3. Agent 3 branch memiliki `routes/teacher.php` dengan prefix `/guru`, tetapi `bootstrap/app.php` pada branch Agent 3 hanya mendaftarkan web/api/console route file; `routes/teacher.php` belum didaftarkan. Route file itu juga belum memuat menu/route Proktor.
4. `resources/views/livewire/kamad/kamad-dashboard.blade.php` dan class `KamadDashboard` masih ada; route aktifnya tidak ditemukan pada `routes/web.php`. Class guard saat ini menerima ADMIN, ADMIN_MADRASAH dan KAMAD, sehingga tetap harus dinonaktifkan/ditutup pada backend.
5. `resources/views/livewire/teacher/result-list.blade.php` memiliki conditional Admin-or-Guru ditambah permission grading; harus diselaraskan dengan larangan Admin grading dan exact-one Guru.
6. `resources/views/livewire/admin/exam-management.blade.php` memiliki branching hasRole Guru untuk opsi seluruh siswa; role mixed tidak boleh mengubah capability.
7. `resources/views/installer/authorize.blade.php` menggunakan istilah “Operator” untuk token bootstrap operator server. Ini bukan role OPERATOR CBT dan tidak boleh dihapus hanya karena retirement role akun CBT.

## 5. AUDIT — issue dan approval

- Issue #8: 7 komentar, semua author `zane0184`; OPEN.
- Issue #9: 2 komentar, semua author `zane0184`; OPEN.
- Issue #10: 16 komentar, semua author `zane0184`; OPEN.
- Komentar #7078921888 adalah keputusan produk/koordinator terbaru (Web-only sampai Juni 2027; portal Admin/Guru/Siswa; KAMAD/OPERATOR/Proktor permanen tidak aktif; instalasi terpisah).
- Komentar #7078306561 menyatakan QuestionVersion/ExamSnapshot/media NEEDS DESIGN, bukan approval.
- Tidak ada approval teknis lintas-owner Agent 1/2/3/4 yang dapat diverifikasi terpisah dari akun koordinator.
- Agent 4 candidate branch belum dikonfirmasi sebagai branch implementasi resmi.

## 6. DESAIN — perbaikan yang harus diajukan (belum diimplementasikan)

1. Buat central exact-role predicate yang membaca state role otoritatif, tidak bergantung pada cached relation yang stale. Deny role campuran/unknown; KAMAD harus ditolak meski ada role tambahan.
2. KAMAD freeze harus mencakup login, existing session, Sanctum token, policy/Gate, route, API, Livewire, report, media, export, monitoring dan background tasks. Pertahankan User/appointment/history.
3. OPERATOR retirement: tidak ada login/route/permission CBT; fungsi administrasi ke Admin Madrasah. Migration role legacy tetap blocked sampai inventaris dan approval.
4. Proktor: exact-one Guru + assignment valid; menu/route berada di Web Guru. Room scope berlaku pada seluruh data surface. Exam author capability dibuat eksplisit terpisah dari Proktor assignment; tidak ada KAMAD bypass.
5. Reconcile test fixtures lama: hapus ekspektasi login/dashboard/monitoring KAMAD, OPERATOR dan PROKTOR permanen; pertahankan test deny legacy; tambahkan exact-role matrix dan account-freeze session/token tests.
6. Preserve installer bootstrap token sebagai mekanisme keamanan instalasi server; jangan samakan istilah operator installer dengan role OPERATOR CBT.

## 7. PENGUJIAN — status

| Pemeriksaan | Status |
|---|---|
| Active PHP/Blade file-level scan (391 file) | PASS — statis |
| Role/permission inventory dari entrypoint dan file yang teridentifikasi | PASS — statis, indirect call graph masih perlu ditutup |
| PHPUnit pada Agent 2 baseline | NOT RUN |
| PHPUnit pada Agent 3 current exact SHA | NOT RUN / CI UNKNOWN |
| PHP lint / route:list | NOT RUN |
| Browser E2E / Livewire HTTP / session/token freeze | NOT RUN |
| MySQL 8 disposable migration rehearsal | NOT RUN |
| Multi-room monitoring / concurrency runtime | NOT RUN |
| Production DB | NOT TOUCHED |

## 8. Kesimpulan

Inventory file-level aktif telah diperluas menjadi 391 file dan menyingkap konflik test/fixture yang harus diselesaikan sebelum patch. Namun Stage 4E **BELUM SELESAI**: indirect call graph, owner approval, test execution exact-SHA, disposable MySQL, browser E2E, session/token freeze dan dua-install isolation masih terbuka.

Tidak ada source runtime, migration, schema, config, test source, workflow, dependency, database, branch Agent 1/3/4, atau deployment yang diubah.
