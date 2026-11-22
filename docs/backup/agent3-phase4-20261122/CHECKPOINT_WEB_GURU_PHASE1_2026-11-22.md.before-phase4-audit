# CHECKPOINT WEB GURU — PHASE 1
Tanggal: 22 November 2026

## Posisi
- Acuan: `docs/MASTER_SPESIFIKASI_FITUR_WEB_GURU_2026-11-22.md` pada `main`.
- Branch kerja: `work/web-guru-phase1-audit-implementation-20261122`.
- Baseline: Agent 3 `cf9a6ed871314b289505c9ef4cd4319ee7590ffb`.
- Source/test SHA terakhir: `a64caefb2ab683fb910c6fe4bf8ff3b14cc182dc`.
- Source HEAD fitur Penugasan Saya: `8a0d30caa67e431badc342d8a0cf9cabcca6abf0`.
- HEAD branch setelah laporan Phase 3: `d679a2160eae55cd6b3e38db9813f05c2ea364a1` (commit dokumen).
- Branch Agent 3 lama dan `main` tidak diubah.
- Laporan fase: `docs/WEB_GURU_PHASE1_REPORT_2026-11-22.md`.

## Selesai pada fase ini
1. Refresh source Guru: route, TeacherPortalAccess, dashboard, learning/curriculum, QuestionBank, ExamManagement, layout, profile, test dan workflow.
2. Menandai status menu berdasarkan source, tidak menganggap menu sebagai fitur teruji.
3. Memperbaiki roster peserta Exam: tepat satu role persisted SISWA, tenant/profile/user valid, soft-deleted dikecualikan.
4. Menambah regression test multi-role negative + exact SISWA positive.
5. Menyimpan backup pra-patch dan memverifikasi tiga backup utama byte-identical.
6. Menambahkan branch fase ini ke filter CI.

## Belum lulus / blocker
- CI run #94/#95/#96 gagal sebelum runner/steps; steps null dan logs tidak tersedia. PHP lint, PHPUnit, route:list, disposable MySQL 8/InnoDB belum berjalan.
- Monitoring Mode A, Penilaian Essay, Hasil/Laporan, Analytics belum terverifikasi/aktif.
- Proktor Mode B menunggu kontrak direct Exam–Room, placement, assignment lifecycle, overlap, room-scoped monitoring Agent 1 dan exact-role/lock contract Agent 2.
- Import XLSX/DOCX menunggu approval DTO/API/batch Agent 2.
- Media, RTL/harakat, formula editor belum terbukti end-to-end.
- Tidak ada perubahan database, migration, VPS, deployment, merge, atau main.

## Audit Phase 2 yang telah dicatat
- Dokumen: `docs/WEB_GURU_PHASE2_BANK_SOAL_AUDIT_2026-11-22.md`.
- Hasil: Bank Soal ada tetapi parsial; media/RTL/formula/import belum siap; CP/TP pada QuestionBank masih memakai model legacy yang belum terbukti sinkron dengan multi-curriculum registry.
- Snapshot menyimpan field `is_correct` dan explanation; privasi Student API/media wajib diverifikasi oleh Agent 2/4.
- Tidak ada source Bank Soal yang diubah pada audit Phase 2.

## Implementasi Phase 3
- Halaman read-only `/guru/penugasan` sudah dibuat, memakai kembali TeacherPortalDataService.
- Menu desktop/mobile dan CTA Dashboard sudah diarahkan ke halaman khusus.
- Regression test HTTP route ditambahkan.
- Dokumen: `docs/WEB_GURU_PHASE3_PENUGASAN_SAYA_2026-11-22.md`.
- Source/test belum runtime verified karena GitHub Actions gagal sebelum runner/steps.

## Tahap berikutnya
**Phase 4 — Hardening Bank Soal dan Asesmen Guru**
1. Pulihkan CI runner/log dan jalankan test exact SHA.
2. Audit QuestionBank seluruh action/IDOR, owner, versioning, key privacy, type validation, filters.
3. Audit ExamManagement transaction/publish/snapshot/token/roster/update schedule and status.
4. Tambah regression tests hanya setelah source dan schema dipastikan.
5. Backup sebelum setiap patch.
6. Lanjutkan monitoring/penilaian/hasiI/analytics setelah kontrak backend.
7. Update report/checkpoint setelah phase 2.

Status keseluruhan Web Guru: **PARTIAL — NOT READY FOR OPERATIONAL EXAM**.
