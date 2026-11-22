# CHECKPOINT FINAL — DESAIN SATU EXAM, SATU EXAMSESSION + PREFLIGHT
Tanggal: 22 November 2026
Repository: zane0184/cbt_november
Branch: work/stage4b-room-session-20261122

## Status ringkas
- AUDIT: PASS untuk pemeriksaan source remote yang tercantum di dokumen audit; working tree lokal NOT VERIFIED.
- DESAIN: FINALIZED FOR REVIEW — menunggu persetujuan eksplisit.
- IMPLEMENTASI: NOT RUN.
- PENGUJIAN desain baru: NOT RUN.
- PREFLIGHT database: NOT RUN karena tidak ada dump/disposable DB terverifikasi yang dapat diakses.
- Produksi: SKIPPED / tidak disentuh.

## Git dan perubahan
- HEAD remote sebelum dokumen final: a1d5063efa3cb0cabcda7af1b54381c7386567e7.
- HEAD remote setelah tiga dokumen: 30d425a32111dafb6ebde94c8119ee64af81119a.
- Compare a1d5063...30d425a: 3 file baru, seluruhnya docs; 0 source/migration/schema/workflow berubah.
- Commit audit final: bd40e4b2cd2b72cfc0bf50cd01ea5747ec8805e0.
- Commit kontrak Agent 1/2/3: e423e9d83f127c5111eaf95d9d5a34351a43c628.
- Commit rencana preflight read-only: 30d425a32111dafb6ebde94c8119ee64af81119a.
- Working tree lokal tidak dapat diperiksa melalui GitHub remote; tidak dinyatakan bersih.
- Tidak ada merge, rebase, PR, deployment, atau perubahan database.

## Dokumen baru
1. docs/AUDIT_DESAIN_FINAL_SATU_EXAM_EXAMSESSION_2026-11-22.md
2. docs/KONTRAK_INTEGRASI_AGENT1_AGENT2_AGENT3_EXAM_SESSION_2026-11-22.md
3. docs/PREFLIGHT_READ_ONLY_EXAM_SESSION_2026-11-22.md

## Temuan penting yang harus ditangani saat implementasi disetujui
1. createSession masih mengizinkan beberapa session per Exam dan menerima jadwal dari Admin.
2. rescheduleSession masih mengubah waktu session independen.
3. ExamController::update memiliki race update-vs-publish karena tidak lock/re-authorize di transaksi.
4. Admin cancelSession dapat membatalkan session tanpa menutup Exam; Attempt start tetap memakai Exam global sehingga peserta masih dapat start.
5. ExamController::close mengubah Exam CLOSED tanpa menyelaraskan session/assignment.
6. Scheduler saat ini mengunci session saja dan menggunakan waktu session.
7. Dua test lama membuat beberapa session untuk Exam yang sama dan harus diubah menjadi satu session + beberapa room.
8. DB legacy belum diinventarisasi; tidak boleh membuat unique index sebelum preflight/reconciliation.

## Keputusan desain yang direkomendasikan
- Satu ExamSession canonical dengan is_operational=1; histori lama is_operational=0.
- MySQL 8+: generated virtual operational_guard + unique (tenant_id, exam_id, operational_guard).
- SQLite: partial unique index (tenant_id, exam_id) WHERE is_operational=1.
- Service mengunci Exam lebih dahulu; unique DB melindungi race; duplicate-key menjadi conflict.
- Session mengambil Exam.start_at/end_at; jadwal independen ditolak.
- Exam update DRAFT harus lock/re-authorize agar tidak race dengan publish.
- Scheduler memakai jadwal Exam, lock Exam -> session, dan hanya memproses session operational.
- Admin tidak membatalkan session operasional independen; penutupan melalui lifecycle Guru pada Exam dan harus aman terhadap Attempt IN_PROGRESS.
- Proktor tetap per ruang, temporer, dengan masa tugas berbeda; monitoring tetap terbatas pada ruang yang ditugaskan.
- Histori session/room/mapping/placement/proktor/Attempt/Answer/Score tidak dihapus atau dipindahkan.

## CI lama
- Source SHA: f1d01ea6fa4b9a100d00680a1b70c2a8d92b9af5.
- Run: https://github.com/zane0184/cbt_november/actions/runs/44984303003
- safe-runtime-tests SUCCESS; 35/35 step, 0 failure, 0 skipped.
- 24 test / 120 assertions pada disposable MySQL 8.0.46/InnoDB.
- Tidak diulang karena tidak ada source change.

## Preflight dan file/database
- Artifact CI 14739069544 diunduh dan diperiksa; hanya log/report, tidak ada DB dump.
- Project/Conversation files yang tersedia tidak memuat SQL dump, SQLite DB, atau ZIP baseline CBT.
- Workspace lokal /mnt/data/CBT_MADRASAH_RECONSTRUCTION tidak ada di runtime.
- Tidak ada akses DB produksi; tidak ada DB yang disentuh.
- SQL read-only preflight disiapkan di dokumen terpisah, tetapi belum dieksekusi.

## Backup
- Manifest Stage 4B: docs/backup/stage4b-room-session-2026-11-22/MANIFEST.md.
- Manifest Stage 4C: docs/backup/stage4c-teacher-subject-assignment-2026-11-22/MANIFEST_SUPPLEMENT.md.
- Tidak ada source diubah sehingga tidak ada backup source baru.
- ZIP baseline tidak disentuh.

## Gate dan pekerjaan berikutnya
1. Pengguna meninjau audit, kontrak, dan preflight.
2. Putuskan kebijakan close Exam saat Attempt IN_PROGRESS dan kebijakan canonical bila beberapa session memiliki Attempt aktif.
3. Sediakan dump/backup terverifikasi untuk disposable DB agar preflight read-only dapat dijalankan.
4. Tunggu persetujuan eksplisit sebelum coding/migration.
5. Setelah disetujui: backup setiap source, maintenance gate untuk session writes/scheduler, migration additive pada disposable MySQL/SQLite, implementasi, test race/concurrency/tenant/history, laporan dan checkpoint baru.
6. Koordinasikan kontrak Agent 2 dan Agent 3; jangan mengubah branch mereka.

Agent 1 berhenti pada tahap desain/preflight. Tidak ada klaim siap produksi.
