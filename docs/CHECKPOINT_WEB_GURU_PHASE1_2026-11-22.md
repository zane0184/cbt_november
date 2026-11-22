# CHECKPOINT WEB GURU — PHASE 1
Tanggal: 22 November 2026

## Posisi
- Acuan: `docs/MASTER_SPESIFIKASI_FITUR_WEB_GURU_2026-11-22.md` pada `main`.
- Branch kerja: `work/web-guru-phase1-audit-implementation-20261122`.
- Baseline: Agent 3 `cf9a6ed871314b289505c9ef4cd4319ee7590ffb`.
- Source SHA Phase 4B: `18830228b64e6718291119662a2907e0e2f416b8`.
- Test SHA Phase 4B: `616dc82f006e0c8327f5738857cd33d53ec9f071`.
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

## Phase 4B — Gate soal terpublikasi pada Exam Builder Guru
- Laporan: `docs/WEB_GURU_PHASE4B_PUBLISHED_QUESTION_GATE_2026-11-22.md`.
- `ExamManagement.php`: daftar soal untuk Exam sekarang hanya menampilkan Question `PUBLISHED` dengan current QuestionVersion `PUBLISHED`.
- `saveExam()` memvalidasi ulang status Question dan QuestionVersion di server sebelum membuat/mengubah Exam. Versi DRAFT ditolak sebelum transaksi.
- Regression test ditambahkan: draft QuestionVersion dengan tenant/subject yang benar ditolak; tidak boleh ada Assessment/Exam baru.
- Backup source/test 2/2 byte-exact terverifikasi:
  - `backend-laravel/backup_before_patch/agent3-phase4b-20261122/ExamManagement.php.before-published-question-gate`
  - `backend-laravel/backup_before_patch/agent3-phase4b-20261122/TeacherPracticeWorkflowTest.php.before-published-question-gate`
- Backup checkpoint pra-update: `docs/backup/agent3-phase4b-20261122/CHECKPOINT_WEB_GURU_PHASE1_2026-11-22.md.before-phase4b-gate`, SHA cocok.
- Batas penting: ini hanya gate jalur Livewire Guru. `ExamSnapshotService` masih harus menegakkan invariant status PUBLISHED secara otoritatif untuk semua caller; ownership Agent 2 tidak disentuh.
- CI run #109 (source SHA `1883022...`) dan #110 (test SHA `616dc82...`) completed/failure sebelum steps; jobs `steps=null`, `logs_url=null`. PHP lint/PHPUnit tidak berjalan. Tidak dapat disebut PASS/FAIL kode.
- Tidak ada migration, database, service Agent 2, policy, VPS, merge, deployment atau main yang diubah.

## Tahap berikutnya — Phase 4C
1. Agent 2 mengunci invariant Question/QuestionVersion `PUBLISHED` di `ExamSnapshotService` dan menambahkan service-level regression tests.
2. Pulihkan workflow runner sebelum menambah patch runtime berikutnya; verifikasi exact SHA, PHP lint, PHPUnit, disposable MySQL 8/InnoDB.
3. Setelah backend contract dan CI terbukti, lanjutkan satu subfitur Bank Soal/Exam Builder yang disepakati.
4. Jangan aktifkan Monitoring, Penilaian, Hasil, Analytics, atau Proktor tanpa policy/API dan test gate masing-masing.

Status keseluruhan Web Guru: **PARTIAL — NOT READY FOR OPERATIONAL EXAM**.
