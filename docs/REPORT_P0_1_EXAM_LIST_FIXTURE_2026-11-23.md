# Laporan Tahap P0.1 — Perbaikan Fixture ExamListContractTest

Tanggal: 23 November 2026 (WIB)
Repository: zane0184/cbt_november
Branch: work/solo-web-cbt-completion-20261123-execution

## AUDIT
- Handover operasional terbaru menempatkan ExamListContractTest sebagai P0.1: empat test gagal karena variabel $assignment dipakai tetapi belum didefinisikan.
- Source sebelum patch, blob SHA: 78b6f637402ecf994638955d89bfe856b9f4b589.
- Setup membuat TeacherSubjectAssignment::create([...]) tanpa menyimpan model hasil, lalu memanggil bindCanonicalTeacherAssignment($assignment, $assessment).
- Helper Tests\Concerns\CreatesCanonicalTeacherAssignment sudah ada. Helper membuat curriculum version, registration dan assignment context kanonis. Tidak dibuat helper baru dan policy tidak dilonggarkan.
- Android native dan Web Super Admin DEFERRED sampai Juni 2027.

## DESAIN
Patch minimal: simpan instance hasil TeacherSubjectAssignment::create() ke variabel $assignment sehingga helper kanonis menerima assignment persisted yang memang baru dibuat. Tidak mengubah schema, endpoint, role, permission atau security boundary.

## IMPLEMENTASI
Satu file source diubah:
- backend-laravel/tests/Feature/ExamListContractTest.php
  - Backup byte-exact: backend-laravel/docs/backup/solo-web-cbt-execution-2026-11-23/ExamListContractTest.php.before-canonical-assignment-variable-fix
  - Blob backup sama dengan blob source sebelum patch: 78b6f637402ecf994638955d89bfe856b9f4b589.
  - Patch blob: c1e1c2a88593648735a7cc84e2fff9b6843dd33a.
  - Perubahan: TeacherSubjectAssignment::create( menjadi $assignment = TeacherSubjectAssignment::create(.
- Commit patch: 335c5d3dd5bf50d7cb16267daf736e04c6881480.
- Commit backup: ea68c11ac1494bd6a1d68e5197510aebc9e62f51.
- Verifikasi GitHub: branch HEAD = patch commit; source blob = patch blob; backup blob = pre-patch blob.

## PENGUJIAN
- CI run #45180674879 dipicu pada exact patch SHA 335c5d3dd5bf50d7cb16267daf736e04c6881480.
- Saat laporan dibuat, run masih in_progress; job laravel-mysql baru menginisialisasi container. Checkout, PHP setup, focused tests, dan full PHPUnit belum selesai.
- Karena itu empat test P0.1 belum dapat dinyatakan PASS. Yang terverifikasi baru backup byte-exact dan patch source di GitHub.
- Pembanding sebelum patch: run #45170164035 pada docs HEAD 3b7c2611dd6fb86407a73b3f90be18b95cff4997 juga gagal full PHPUnit. Run full source terakhir sebelumnya #45168433247: 92 failed / 426 passed / 1,723 assertions; focused login 27 passed / 90 assertions.

## PEKERJAAN BERIKUTNYA
1. Baca hasil/log run #45180674879 pada exact SHA; pastikan empat test ExamListContractTest benar-benar lulus.
2. Lanjut P0.2 ExamSnapshotTest: audit setup, TeacherAccess, tenant context, canonical registration/version/owner dan snapshot provenance. Gunakan helper yang sudah ada; jangan menurunkan policy.
3. P0.3 cleanup FK device_leases sebelum exam_attempts dalam ExamVerificationTest; diagnosis terpisah untuk response 403.
4. P0.4–P0.7 sesuai handover, jaga Admin tidak mendapat akses hasil ujian dan Proktor tetap fail-closed tanpa canonical central-exam marker.
5. Semua suite harus diuji pada disposable MySQL 8.0.46. Belum ada migration produksi, deployment, merge PR, ZIP distribusi, atau UAT.

STATUS: PATCH SUDAH DICATAT; CI MENUNGGU HASIL. Belum siap produksi/ujian operasional/distribusi.
