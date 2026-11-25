# CHECKPOINT P0-02A — Non-CP Academic Acceptance
Tanggal: 2026-11-25

## 1. Identitas
- Repository: `zane0184/cbt_november`
- Branch: `work/limited-zip-p0-20261125`
- HEAD saat checkpoint: `2d1f28775b4768bffb05f75a8bbfa617d4ed25d7`
- Parent chain mencakup checkpoint P0-02 `52f34ee2be7d346c1e1eaba276285badc75dac7f`.
- `main` tidak diubah; tidak ada merge, deployment, VPS, migration produksi, atau DB produksi disentuh.

## 2. AUDIT
- P0-02 checkpoint dibaca ulang; provisioning dasar dan gate CP tetap dipertahankan.
- Runtime lokal: `git` dan PHP 8.4 tersedia, tetapi Composer, MySQL/MariaDB client/server, Docker, dan Podman tidak tersedia; `pdo_mysql` juga tidak terpasang. Tidak ada workspace rekonstruksi lokal yang ditemukan pada `/mnt/data/CBT_MADRASAH_RECONSTRUCTION/`.
- Repository memiliki workflow `.github/workflows/docx-import-parser-ci.yml` yang terpicu pada branch ini. Workflow menjalankan service `mysql:8.0`, memverifikasi `APP_ENV=testing`, `DB_CONNECTION=mysql`, `DB_DATABASE=cbt_ci_docx`, MySQL major version 8 dan `InnoDB`; matriks PHP 8.3/8.4; menjalankan lint, installer E2E pada database disposable terpisah, Student Import acceptance, benchmark denial-path, dan PHPUnit lengkap.
- Dengan demikian CI ini dapat menjadi database MySQL 8/InnoDB disposable untuk acceptance test; tidak perlu menyentuh VPS atau produksi.
- Browser runner `backend-laravel/tests/runner-browser-e2e.mjs` memasang `page.route('**/api/v1/**', ...)`; runner tersebut menggunakan mock API dan belum merupakan browser-to-real-Laravel E2E.

## 3. DESAIN
- Acceptance berurutan: Admin membuat Guru/Siswa → Rombel → placement Siswa → mapel → registrasi versi metadata → mapping mapel → assignment Guru.
- Acceptance negatif: tenant isolation, permission, identitas, audit, transaksi/rollback, idempotensi, duplikasi, CP PENDING.
- CP PENDING diuji dengan placeholder fixture yang terikat ke row sumber Pendis 9941 yang statusnya benar-benar PENDING; fixture tidak memuat konten CP dan bukan bukti CP resmi.
- Tidak mengubah provisioning dasar, seeder CP, status PENDING, checksum, atau aturan CP lainnya.

## 4. IMPLEMENTASI
### Backup sebelum perubahan
1. `backend-laravel/app/Services/TeacherPortal/TeacherLearningOutcomeService.php`
   - Backup: `backend-laravel/backup_before_patch/P0_02A_ATP_PENDING_CP_GUARD_20261125/TeacherLearningOutcomeService.php.before`
   - Ukuran 19.103 byte; Git blob SHA-1 `62d7dd2176253a9064700aa4058ba52d14871e4e`; SHA-256 `9820ca933d625a257b854fd330975e0c24e97f8a50b5af7148a0c22de740cec9`.
   - Manifest: `docs/backup/P0_02A_ATP_PENDING_CP_GUARD_20261125/MANIFEST.md`.
2. `backend-laravel/tests/Feature/P002ANonCpAcademicAcceptanceTest.php` (file baru; backup dibuat sebelum koreksi fixture)
   - Backup: `backend-laravel/backup_before_patch/P0_02A_ACCEPTANCE_TEST_FIXTURE_20261125/P002ANonCpAcademicAcceptanceTest.php.before`
   - Ukuran 28.839 byte; Git blob SHA-1 `90f8b9c25b40d4772f3db6f3d79ed689f1728fb2`; SHA-256 `a8c0eed105b1c217b1dba3f233d8194d924bc659ada48cd5ce5c693ac47e1fde`.
   - Manifest: `docs/backup/P0_02A_ACCEPTANCE_TEST_FIXTURE_20261125/MANIFEST.md`.
- Kedua backup diverifikasi dengan fetch ulang; konten byte-for-byte identik dengan source sebelum perubahan.

### File berubah
- `backend-laravel/app/Services/TeacherPortal/TeacherLearningOutcomeService.php`: satu perubahan minimum pada `createAtp()` agar CP induk harus resmi, immutable, sumber `ARTIFACT_CHECKSUM_VERIFIED`, checksum sumber tersedia, checksum CP tersedia, dan keduanya cocok.
- `backend-laravel/tests/Feature/P002ANonCpAcademicAcceptanceTest.php`: test baru untuk alur Admin, duplicate/idempotency, tenant/permission, rollback audit, serta penolakan CP PENDING. Fixture test dikoreksi agar aksi service dijalankan sebagai Guru.
- Tidak ada migration, seeder, provisioning, test lama, browser runner, atau konfigurasi PHPUnit yang diubah.

## 5. PENGUJIAN — status saat checkpoint
- Branch/HEAD dan checkpoint P0-02: PASS (read/compare GitHub source).
- Backup byte-exact: PASS (source/backup content identical; ukuran dan blob SHA identik; SHA-256 tercatat).
- MySQL 8/InnoDB workflow environment: PASS pada preflight run #116 (commit `36b6a2b02e262a61238b83345c1c032e46555943`); kedua matriks melewati pemeriksaan DB identity, lint, Installer E2E, Student Import acceptance, benchmark denial-path. Full PHPUnit run #116 masih berjalan pada pemeriksaan terakhir dan bukan run acceptance P0-02A.
- Acceptance P0-02A test baru: NOT RUN pada saat checkpoint ini; menunggu workflow run untuk commit yang memuat test.
- Browser-to-real-Laravel E2E: BLOCKED; runner saat ini mock API.
- Tidak ada hasil FAIL yang diubah menjadi SKIPPED. Belum ada klaim alur akademik PASS.

## 6. BLOCKER / NEXT
1. Tunggu workflow CI pada exact commit yang memuat test terbaru; ambil job/step/log PHP 8.3 dan 8.4, klasifikasikan PASS/FAIL/BLOCKED/NOT RUN.
2. Jika ada kegagalan, identifikasi root cause, backup file sebelum koreksi, lakukan patch minimum, lalu ulangi test relevan dan regression suite.
3. Tambahkan uji ATP publication bila belum tercakup; pastikan penolakan CP PENDING pada create TP, create ATP, publish TP/ATP, create question, dan publish question.
4. Browser-to-real-Laravel tetap gap terpisah; jangan klaim E2E browser backend.
5. Jangan merge ke main, deployment, atau akses DB/VPS produksi.

Status: **P0-02A IMPLEMENTASI AWAL ADA; ACCEPTANCE MENUNGGU HASIL CI.**
