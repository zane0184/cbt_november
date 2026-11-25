# PROMPT EKSEKUSI AGENT PENGGANTI — CBT MADRASAH
Tanggal: 23 November 2026
Repository: `zane0184/cbt_november`

## Instruksi pembuka
Anda agent penerus untuk melanjutkan proyek CBT Madrasah. **Jangan mengulang pekerjaan yang telah selesai.** Baca terlebih dahulu:
1. `docs/AGENT_START_HERE.md`
2. `docs/HANDOVER_AGENT_BARU_2026-11-23_P0_518_PASS.md`
3. Dokumen audit, checkpoint, addendum owner, matriks ADM, dan spesifikasi Web Admin/Guru/Siswa yang ditautkan pada handover.

Gunakan GitHub saja. Jangan membuka ZIP baseline, Project Files, atau Library ChatGPT. Jangan minta owner mengulang keputusan yang sudah tercatat.

## Fakta yang sudah selesai — jangan dikerjakan ulang
- Branch remediasi: `work/phpunit-41-root-cause-20261123`.
- Exact HEAD: `856294037312c186f03422071368bcaa2987516a`.
- CI [#45220055382](https://github.com/zane0184/cbt_november/actions/runs/45220055382): PHP 8.3 + disposable MySQL 8.0.46/InnoDB, smoke 27 passed/90 assertions, full PHPUnit 518 passed/0 failed/2,189 assertions.
- P0 root cause 41 failures sudah diperbaiki. File source/test dan backup rinci tercatat di handover.
- PR #22 Draft dari branch remediasi ke branch `work/solo-web-cbt-completion-20261123-execution`. Belum merged. Review dan persetujuan owner wajib.
- Jangan mengulang triage/patch P0, jangan menghapus test, jangan melemahkan security/policy/FK/fencing.

## Keputusan owner — tidak boleh dilanggar
- Satu instalasi + satu database akademik mandiri per madrasah. Bukan shared multi-tenant SaaS.
- Role lokal hanya ADMIN_MADRASAH, GURU, SISWA. Tidak ada portal Operator/Kamad/Proktor/Super Admin lokal.
- Proktor adalah penugasan sementara pada Guru di Web Guru; Mode B Exam+Room, room-scoped, monitoring-only. Admin hanya assign/revoke.
- Guru mengelola dan menerbitkan ujian sendiri sesuai assignment tanpa approval Admin.
- Tidak ada ExamSession/SessionRoom/session_number/wave/session lifecycle. Room bukan admission gate.
- Android native dan Web Super Admin deferred sampai Juni 2027.
- Kurikulum Merdeka + KBC/Panca Cinta + Pembelajaran Mendalam + 8 dimensi profil lulusan satu ekosistem, versioned dan punya provenance hingga laporan historis.
- DOCX prioritas; konflik XLSX soal wajib direkonsiliasi dengan master spec/PR #19/Issue #16 sebelum coding. XLSX Admin untuk Guru/Siswa tidak dihapus. PDF/OCR deferred. SHORT_ANSWER blocked.
- Jangan jalankan Gradle via terminal.

## Tahap berikutnya — mulai dari sini
1. **P1-A PR #22:** fetch ulang branch refs, PR #22 head/base/diff/review/CI. Lakukan review dan minta persetujuan owner sebelum merge. Setelah merge yang disetujui, pastikan CI lulus pada exact HEAD branch eksekusi.
2. **P1-B Proktor Mode B:** audit source aktual, marker jenis ujian terpusat, assignment Exam+Room+Guru, overlap/concurrency/revoke/audit, UI Web Guru, strict room scope dan negative tests. Jangan bypass fail-closed.
3. **P1-C Web Admin:** audit ADM-01–23 per fitur dengan path/policy/schema/test/evidence. Jangan memberi Admin akses soal/ujian/Attempt/jawaban/nilai/monitoring.
4. **P1-D Web Guru:** audit lifecycle soal/asesmen/ujian/grading/history/analytics/DOCX/media/provenance; jangan implement ulang. Reconcile import format dulu.
5. **P1-E Web Siswa:** integrasi PR #13 ke Laravel/Sanctum/PAT/fencing; browser-to-real-Laravel E2E dan offline/recovery/multi-device tests.
6. **P1-F Kurikulum:** satu provenance/version contract sampai QuestionVersion, immutable Exam Snapshot, score dan laporan historis.
7. **P2 Acceptance/ZIP/VPS:** hanya setelah seluruh tes keamanan, integrasi, recovery, concurrency, fresh install, backup/restore, UAT. Minta approval sebelum deployment, production migration, ZIP final.

## Wajib pada setiap perubahan
- Sebelum mengubah file existing: backup byte-exact, checksum, manifest.
- Catat path backup, file changed, commit SHA.
- Regression tests; focused + full PHPUnit pada disposable MySQL.
- Pisahkan AUDIT, DESAIN, IMPLEMENTASI, PENGUJIAN.
- Jangan klaim PASS tanpa exact SHA, workflow/job, dan log.
- Jangan merge/deploy/migrate production/ZIP final tanpa persetujuan owner.
- Perbarui checkpoint dan handover GitHub setelah tiap tahap.

## Output pertama sebelum coding
Berikan ringkasan:
1. Exact refs/HEAD/PR/CI yang baru diverifikasi.
2. Perbedaan antara source aktual dan laporan lama.
3. Pekerjaan yang sudah terbukti selesai (jangan disentuh lagi).
4. Satu tugas berikut yang akan dikerjakan dan bukti/risiko.
5. Backup plan bila akan patch.

**Mulai dari P1-A; jangan mulai ulang proyek.**
