# P0-02A — Execution Checkpoint (Interim)
Tanggal: 2026-11-25

## Identitas source
- Repository: `zane0184/cbt_november`
- Branch kerja: `work/limited-zip-p0-20261125`
- HEAD saat checkpoint: `995fd5108f4fe6df8e22ea42bfd35e25ea98f250`
- Commit dokumentasi P0-02 sebelumnya tetap berada dalam ancestry: `52f34ee2be7d346c1e1eaba276285badc75dac7f`.
- `main` diverifikasi kembali ke `f1665d0feffe04e6581150d1fd7ff8433a80b83a`. Tidak ada PR/merge atau deployment.

## AUDIT
- Source aktual dibaca ulang dari branch. Source/test code yang diuji di run #155 memiliki blob yang sama dengan HEAD checkpoint ini; commit setelah run #155 hanya menambah backup files, tanpa mengubah source/test code.
- `TeacherSubjectAssignmentManagement`: guard assignment memeriksa tenant, role GURU, `is_active`, dan `deleted_at`; tidak memanggil `User::trashed()` karena model User tidak menggunakan trait SoftDeletes.
- `TeacherLearningOutcomeService`: helper `resolveVerifiedOfficialCp()` dipakai oleh create TP, create ATP, dan publish. Helper menolak sumber bukan `ARTIFACT_CHECKSUM_VERIFIED`, checksum sumber/CP tidak valid atau tidak cocok, CP nonaktif/tidak resmi/dapat diedit, dan mismatch owner/version/struktur.
- Registry Pendis 9941 tetap `PENDING`; checksum tidak diisi. Tidak ada CP resmi yang dibuat atau diimpor.
- Test-only placeholder CP pada acceptance P0-02A terhubung ke sumber Pendis 9941 PENDING, tanpa isi CP resmi; hanya untuk membuktikan fail-closed.
- Browser runner memakai mock melalui `page.route('**/api/v1/**', ...)`; bukan browser-to-Laravel E2E.

## DESAIN
- Alur Admin: Guru/Siswa → Rombel → penempatan siswa → mapel → registrasi metadata → mapping mapel → penugasan Guru.
- Negative acceptance: tenant isolation, permission, identitas NIP/NISN, audit, transaksi rollback, idempotensi/duplikasi, akun Guru inactive/deleted, CP source PENDING.
- Tidak ada perubahan provisioning dasar, migration, seeder, aturan checksum, metadata resmi, atau status CP.

## IMPLEMENTASI
File source:
- `backend-laravel/app/Services/TeacherPortal/TeacherLearningOutcomeService.php`
- `backend-laravel/app/Livewire/Admin/TeacherSubjectAssignmentManagement.php`

Test:
- `backend-laravel/tests/Feature/P002ANonCpAcademicAcceptanceTest.php`
- `backend-laravel/tests/Feature/TeacherLearningOutcomeProvenanceTest.php`

Backup:
- `backend-laravel/backup_before_patch/P0_02A_ATP_PENDING_CP_GUARD_20261125/TeacherLearningOutcomeService.php.before`
- `backend-laravel/backup_before_patch/P0_02A_ACTIVE_USER_GUARD_20261125/TeacherSubjectAssignmentManagement.php.before`
- Acceptance test snapshots pada direktori `P0_02A_ACCEPTANCE_TEST_FIXTURE_20261125`, `P0_02A_PIVOT_COLUMN_FIX_20261125`, `P0_02A_FULL_NEGATIVE_COVERAGE_20261125`, `P0_02A_ACTIVE_USER_GUARD_20261125`, dan `P0_02A_ACTIVE_STATUS_TEST_20261125`.
- Manifest koreksi SHA-256 yang berlaku: `docs/backup/P0_02A_SHA256_CORRECTIONS_20261125.md`, `docs/backup/P0_02A_FULL_NEGATIVE_COVERAGE_20261125/SHA256_CORRECTION.md`, dan manifest active guard/status.
- Backup terbaru source/test provenance alignment juga ada pada `backend-laravel/backup_before_patch/P0_02A1_ATP_MAPPING_AND_RELATION_FIX_20261125/`.

## PENGUJIAN — bukti saat checkpoint
### CI run #155
- Run: `45535045721`
- URL: `https://github.com/zane0184/cbt_november/actions/runs/45535045721`
- Commit yang diuji: `0960e4dd6d830065eb4c8b674d6e3ef57384de9e`
- Matriks: PHP 8.3 dan PHP 8.4; MySQL 8.0/InnoDB; DB CI `cbt_ci_docx`; installer E2E memakai database disposable terpisah.
- PASS: source/database identity preflight (dua matriks).
- PASS: Composer lock, dependency install, Laravel runtime preparation, whitespace check, PHP lint (dua matriks).
- PASS: clean-install Installer E2E (dua matriks).
- PASS: Student Import acceptance (dua matriks).
- PASS: benchmark cleanup denial-path pada PHP 8.4.
- PHP 8.3 benchmark denial-path: IN PROGRESS pada pemeriksaan terakhir.
- PHPUnit lengkap PHP 8.3 dan 8.4: IN PROGRESS pada pemeriksaan terakhir.
- Source/test blob sama dengan HEAD `995fd5108f4fe6df8e22ea42bfd35e25ea98f250`; perubahan setelah commit yang diuji hanya backup files.

### Run sebelumnya
- Run #123 (`45533305836`) FAIL: tiga test gagal karena pemanggilan `User::trashed()` pada model User tanpa trait SoftDeletes. Root cause sudah diperbaiki pada assignment guard; test active/deleted user ditambahkan.
- Run #142 (`45534390278`) FAIL: satu test mengubah status row CP menjadi PENDING sementara sumbernya tetap verified. Test tersebut diselaraskan agar mengubah status sumber CP menjadi PENDING, sesuai kontrak provenance/checksum. Source gate tidak dilonggarkan.
- Tidak ada FAIL yang diubah menjadi SKIPPED.
- Run #155 merupakan regression run untuk source/test yang sudah diperbaiki.

## Status kriteria
- Non-CP academic acceptance: NOT RUN FINAL (PHPUnit masih berjalan).
- CP PENDING rejection (TP/ATP/question create/publish): NOT RUN FINAL (PHPUnit masih berjalan).
- Tenant isolation, permission, identity, audit, idempotency, transaction rollback: NOT RUN FINAL (PHPUnit masih berjalan).
- Real browser-to-Laravel E2E: BLOCKED; current browser runner mocks API.
- Official CP artifact positive-path: BLOCKED; no verified original binary/checksum, and Pendis 9941 remains PENDING.
- Production DB/VPS: NOT RUN; not accessed.

## NEXT
1. Tunggu run #155 sampai PHPUnit kedua matriks selesai.
2. Jika FAIL, baca log, identifikasi root cause, backup byte-exact sebelum patch, perbaiki sekecil mungkin, jalankan regression suite.
3. Setelah run #155 selesai, periksa run terbaru yang menguji HEAD `995fd...` dan verifikasi source/test blob tetap sama.
4. Simpan laporan final dengan hasil PASS/FAIL/BLOCKED/NOT RUN. P0-02A belum boleh dinyatakan selesai sampai acceptance dan integritas transaksi PASS pada MySQL 8/InnoDB.
