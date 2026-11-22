# Checkpoint P2 — Media Bank Soal Guru — 23 November 2026

## Source
- Repository: `zane0184/cbt_november`
- Branch: `work/web-guru-phase1-audit-implementation-20261122`
- HEAD yang diuji: `305217f01db0b318295b661e6934031d1c37ded0`
- HEAD branch sama persis dengan commit yang diuji oleh CI.
- Tidak menggunakan ZIP baseline atau Library/Pustaka ChatGPT.
- Tidak ada merge, deployment, atau perubahan database produksi.

## AUDIT
- MediaController API sudah menyediakan store, attach, show, detach, dan delete dengan tenant scoping, QuestionPolicy, storage private, dan pemeriksaan versi.
- Web Guru sebelumnya belum memiliki UI untuk upload/attach media.
- QuestionVersion sudah memiliki relasi media melalui `question_version_media`.
- Attachment hanya boleh pada versi DRAFT; versi terbit harus tetap immutable.

## IMPLEMENTASI
1. Controller baru `TeacherQuestionMediaController` mengorkestrasi upload → attach menggunakan MediaController yang sudah ada sebagai sumber aturan tunggal.
2. Jika attach ditolak, upload yang belum terpasang dibersihkan agar tidak meninggalkan orphan file.
3. Route web Guru ditambahkan untuk upload+attach, detach, dan show media, berada di bawah middleware autentikasi, tenant, license, dan exact TeacherPortalAccess.
4. QuestionBank memuat media dari versi yang sedang diedit.
5. Form upload tersedia hanya ketika versi DRAFT.
6. Media yang terpasang dapat dilihat; pelepasan hanya tersedia pada versi DRAFT.
7. Setelah upload/detach, pengguna kembali ke QuestionBank dan modal soal dibuka kembali melalui `edit_question` query.
8. Versi PUBLISHED menampilkan media read-only dan tidak menampilkan kontrol upload/detach.

## BACKUP
Source sebelum perubahan disalin ke:
`.github/backups/web-guru-p2-media-20261123/`
Manifest mencatat source Git blob SHA dan salinan asli; ketiga file sumber yang diubah diverifikasi cocok dengan backup.

## PENGUJIAN — GitHub Actions
Run: `45063555637`
Commit: `305217f01db0b318295b661e6934031d1c37ded0`
Hasil: **SUCCESS**
- PHPUnit: **71 tests passed, 338 assertions**
- PHP lint: PASS
- Route registration: PASS
- Composer locked dependencies: PASS
- Disposable MySQL 8.0.46 provisioning and identity verification: PASS
- Disposable DB migrations: PASS
- InnoDB: **80/80 base tables**
- Cleanup DB/user and stop containers: PASS

Test media mencakup upload, attach, view, detach, cleanup upload jika versi terbit menolak attachment, serta tampilan kontrol hanya pada versi DRAFT.

## STATUS
**Media UI Guru: PASS pada cakupan regression suite.**
Belum berarti semua fitur Web Guru selesai.

## NEXT
1. XLSX import wizard: parser template version, preview, row-level validation, atomic confirm, tenant/assignment/reference validation, audit log.
2. Curriculum mapping versioned: koordinasi issue GitHub untuk integrasi QuestionVersion dan immutable ExamSnapshot; jangan ubah ExamSnapshotService tanpa persetujuan/koordinasi owner Agent 2.
3. Reference dataset Pembelajaran Mendalam/delapan dimensi/Panca Cinta harus bersumber resmi dan memiliki provenance. Saat ini source tidak menyediakan seeder referensi lengkap; jangan membuat data fiktif.
4. Acceptance test browser/operasional dan seluruh alur Guru.

Tidak ada migration produksi, merge, atau deployment.
