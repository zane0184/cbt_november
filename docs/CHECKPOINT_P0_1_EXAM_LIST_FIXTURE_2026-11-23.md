# Checkpoint CBT Madrasah — 23 November 2026

## Posisi branch
- Repo: zane0184/cbt_november
- Branch eksekusi: work/solo-web-cbt-completion-20261123-execution
- Source patch P0.1: 335c5d3dd5bf50d7cb16267daf736e04c6881480
- Source terakhir yang telah menjalani full PHPUnit sebelum patch: b31d7a2713383ddcc1282317cdb295d6992d5dce
- CI untuk patch P0.1: #45180674879 — masih in progress pada waktu checkpoint dibuat.

## AUDIT
- Full PHPUnit terakhir sebelum patch: 92 failed / 426 passed / 1,723 assertions, disposable MySQL 8.0.46, run #45168433247.
- HEAD docs-only sebelumnya 3b7c2611dd6fb86407a73b3f90be18b95cff4997 juga menghasilkan full suite fail di run #45170164035.
- Empat failure ExamListContractTest berasal dari $assignment undefined di fixture.
- Role aktif ADMIN_MADRASAH/GURU/SISWA. Satu instalasi/database per madrasah. Kurmer+KBC+PM+8 DPL satu ekosistem. Android native dan Web Super Admin deferred sampai Juni 2027.
- Semua ADM-01..ADM-23 tetap NOT VERIFIED hingga bukti end-to-end tersedia.

## DESAIN
- Perbaiki fixture dengan menyimpan hasil TeacherSubjectAssignment.
- Jangan memberi hak Admin untuk author soal/ujian, peserta, Attempt, monitoring, scoring atau hasil ujian demi membuat tes lama lulus.
- Jangan membuka Proktor assignment/monitoring sampai eligibility ujian pusat diberi marker kanonis oleh owner.
- Pertahankan helper canonical assignment dan provenance sampai immutable snapshot/report.

## IMPLEMENTASI
- File: backend-laravel/tests/Feature/ExamListContractTest.php.
- Satu baris: $assignment = TeacherSubjectAssignment::create([...]).
- Backup: backend-laravel/docs/backup/solo-web-cbt-execution-2026-11-23/ExamListContractTest.php.before-canonical-assignment-variable-fix.
- Blob backup 78b6f637402ecf994638955d89bfe856b9f4b589; patched blob c1e1c2a88593648735a7cc84e2fff9b6843dd33a.
- Commit backup ea68c11ac1494bd6a1d68e5197510aebc9e62f51; commit source 335c5d3dd5bf50d7cb16267daf736e04c6881480.
- Laporan tahap: docs/REPORT_P0_1_EXAM_LIST_FIXTURE_2026-11-23.md.

## PENGUJIAN
- Backup diverifikasi byte-exact lewat Git blob SHA.
- Patch commit dan source blob diverifikasi pada branch aktif.
- CI #45180674879 masih in progress ketika checkpoint ini ditulis. Belum ada klaim PHPUnit lulus.
- Tidak ada database produksi, migration, VPS, Android build atau merge yang dijalankan.

## NEXT
1. Verifikasi hasil exact CI #45180674879.
2. Lanjut P0.2 ExamSnapshotTest.
3. P0.3 ExamVerificationTest FK cleanup.
4. P0.4 scoring/reopen permission+fixture audit.
5. P0.5 stale Livewire contract Admin vs Guru.
6. P0.6 auth/lease/fencing tests.
7. P0.7 Proktor fail-closed eligibility.
8. Setelah itu integrasi Admin/Guru/Siswa, curriculum historical report E2E, fresh-install/upgrade/restore, security/concurrency/UAT.
