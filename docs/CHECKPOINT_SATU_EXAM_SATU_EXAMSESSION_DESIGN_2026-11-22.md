# CHECKPOINT — AUDIT DESAIN SATU EXAM, SATU EXAMSESSION OPERASIONAL
Tanggal: 22 November 2026

## Status
AUDIT: PASS untuk pemeriksaan source/arsitektur yang dapat dibaca pada branch Agent 1.
DESAIN: DOKUMENTED — menunggu persetujuan sebelum implementasi.
IMPLEMENTASI: NOT RUN — tidak ada source atau migration diubah.
PENGUJIAN: NOT RUN untuk desain baru. CI Stage 4B–4C lama tetap PASS pada source SHA yang telah diuji.

## Git
- Repository: zane0184/cbt_november
- Branch: work/stage4b-room-session-20261122
- HEAD aktual sebelum audit: 6815148248cb963b4083430fbea5e21056676a45
- Commit dokumen audit: 140cd00a65ec96e8bd78221b9beee7173d5077a2
- Source SHA CI lama: f1d01ea6fa4b9a100d00680a1b70c2a8d92b9af5
- Compare SHA CI ke HEAD aktual: 4 commit docs-only; tidak ada source/migration/schema/workflow berubah.
- Working tree lokal tidak dapat diverifikasi melalui pemeriksaan GitHub remote.

## CI Stage 4B–4C sebelumnya
- Run: https://github.com/zane0184/cbt_november/actions/runs/44984303003
- Job safe-runtime-tests: SUCCESS; 35/35 step sukses, 0 failure, 0 skipped.
- Empat suite target: 24 test / 120 assertions.
- Database: disposable MySQL 8.0.46/InnoDB.
- Tidak diulang pada checkpoint ini.

## Temuan inti
1. createSession masih dapat membuat beberapa session berbeda untuk satu Exam.
2. Unique constraint sekarang hanya (tenant_id, exam_id, session_number), bukan satu session per Exam.
3. rescheduleSession masih mengubah waktu session secara independen.
4. UI Admin masih menyediakan nomor, jadwal, dan reschedule session.
5. Scheduler/lifecycle bergantung pada waktu session.
6. Attempt start tetap menggunakan jadwal global Exam dan waktu server.
7. Assignment Proktor dan monitoring tetap scoped per session-room; assignment yang terdampak scope revision menjadi NEEDS_REVIEW.
8. Data lama/duplikat belum diinventarisasi pada database produksi; migrasi langsung unique exam_id tidak aman tanpa preflight dan rekonsiliasi.

## Rencana yang menunggu persetujuan
- Tambah penanda satu session operasional dan unique conditional index yang kompatibel MySQL 8+/SQLite.
- Pertahankan session lama sebagai historical non-operational records; jangan hard delete.
- Buat satu session operasional dengan waktu persis Exam.start_at/end_at.
- Hilangkan input waktu/nomor dan reschedule Admin; jadwal hanya milik Guru melalui lifecycle Exam.
- Scheduler mengikuti waktu Exam dan hanya memproses session operasional.
- Assignment Proktor tetap per ruang dan boleh mempunyai masa tugas lebih pendek dalam rentang Exam.
- Rekonsiliasi data lama harus preflight, canonical selection per Exam, preserve room/placement/proctor/audit history, dan menunda Exam yang memiliki Attempt IN_PROGRESS.
- Tidak ada coding sebelum persetujuan.

## Backup
Tidak ada source yang diubah, sehingga tidak ada source backup baru. Manifest Stage 4B/4C lama tetap berlaku. Dokumen audit/checkpoint baru adalah file baru, tidak menimpa dokumen lama. ZIP baseline tidak disentuh.

## Handover
- Agent 1 tetap hanya Stage 4B–4C.
- Agent 2 tetap pemilik Stage 4D; branch tidak diubah.
- Agent 3 tetap pemilik Web Guru; branch tidak diubah.
- Langkah berikutnya: tunggu persetujuan desain; setelah itu backup tiap source, implementasi additive di disposable MySQL, tes integrasi/concurrency/tenant/history, lalu checkpoint.
- Tidak ada merge, rebase, PR, deployment, atau DB produksi.
