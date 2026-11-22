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

## Phase 4 — Audit Bank Soal dan Asesmen Guru (22 November 2026)
- Laporan: `docs/WEB_GURU_PHASE4_AUDIT_BANK_SOAL_ASESMEN_2026-11-22.md`.
- Audit statis menemukan Bank Soal belum memiliki filter/field kurikulum lengkap, difficulty/cognitive/estimated time, media, RTL/formula, serta import yang kontraknya belum disetujui.
- Exam Builder saat ini tidak membatasi pilihan Question/QuestionVersion berdasarkan status eligible; `ExamSnapshotService` juga belum memvalidasi status tersebut. Perlu keputusan/patch otoritatif Agent 2 sebelum UI Guru diselaraskan.
- Snapshot menyimpan explanation dan `is_correct`; public payload Siswa harus disanitasi oleh backend owner.
- Validasi assignment/roster terjadi sebelum transaksi; perlu review race/lock order lintas Agent 2 sebelum perubahan.
- Policy lintas branch masih drift: branch Agent 2 lama memiliki KAMAD dan hasRole checks; branch koordinator telah menghapus KAMAD tetapi exact-one semua aksi belum terbukti. Tidak ada policy lintas agent yang diubah.
- Tidak ada source runtime yang diubah dalam Phase 4 audit.
- Backup checkpoint sebelum update: `docs/backup/agent3-phase4-20261122/CHECKPOINT_WEB_GURU_PHASE1_2026-11-22.md.before-phase4-audit`.
- Pengujian hanya inspeksi statis/fetch/compare. PHP lint, PHPUnit, route:list, MySQL 8/InnoDB, browser E2E, concurrency, dan UAT **NOT RUN**.

## Tahap berikutnya — Phase 4B, setelah kontrak backend
1. Minta Agent 2/koordinator menetapkan eligibility QuestionVersion untuk Exam dan menutup sanitasi snapshot private/public.
2. Pastikan ExamPolicy/ReportPolicy sesuai master role dan exact-one pada setiap action; jangan menganggap branch lain telah terintegrasi.
3. Sepakati revalidation/lock order assignment dan roster dalam transaksi.
4. Setelah kontrak disetujui, backup dan patch UI Guru kecil-kecil beserta regression test.
5. Pulihkan CI runner dan jalankan lint/PHPUnit exact SHA pada disposable MySQL 8/InnoDB.
6. Monitoring, Penilaian, Hasil, Analytics dan Proktor tetap tidak diaktifkan sebelum policy/API/capability masing-masing terbukti.

Status keseluruhan Web Guru: **PARTIAL — NOT READY FOR OPERATIONAL EXAM**.
