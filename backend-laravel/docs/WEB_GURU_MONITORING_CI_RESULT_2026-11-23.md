# Hasil CI — Monitoring Guru dan Penugasan Proktor Sementara
Tanggal: 23 November 2026

## Source yang diuji
- Branch: `work/web-guru-remove-xlsx-20261123`
- Checkout SHA: `051c5d5ee1074d1e6dd3004e552658e83a2f2be7`
- Current branch HEAD: `2a9042e4228518645581fcd0646b0f42bdbf690b`
- GitHub compare menunjukkan satu commit setelah SHA diuji hanya menambah `MANIFEST_MONITORING_TESTS.md`; tidak ada source/test/workflow berubah setelah pengujian.

## Hasil final
GitHub Actions run [45108770776](https://github.com/zane0184/cbt_november/actions/runs/45108770776):
- Status: **SUCCESS**
- PHPUnit: **117 passed, 514 assertions**, durasi 16.20 detik.
- PHP lint dan Laravel route registration: PASS.
- Composer validate, locked dependency install, platform requirements: PASS.
- Disposable MySQL 8: identitas database diverifikasi sebelum test.
- Migration test menghasilkan **80 base tables, seluruhnya InnoDB**.
- Database dan user disposable dibersihkan.

## Coverage yang ditambahkan
`MonitoringTest`:
- Guru mengakses ringkasan monitoring.
- Siswa tidak dapat mengakses monitoring.
- Tenant mismatch ditolak.
- Daftar peserta, pencarian/filter, read-only.
- Detail attempt siswa tidak bocor lintas peserta.
- Snapshot soal tidak diberikan sebelum jadwal ujian.

`MonitoringDuplicationTest`:
- Guru pembuat ujian dapat memonitor ujian dalam kewenangannya.
- Proktor sementara dibatasi ke peserta pada ruang/sesi penugasan.
- Admin tidak dapat memakai monitoring CBT meski menugaskan Proktor.
- Assignment pending belum memberi akses sebelum dikonfirmasi.
- Legacy active status tidak memberi akses.
- Guru yang ditugaskan hanya dapat memonitor ujian dengan assignment terkonfirmasi.
- Guru tanpa otoritas/assignment, assignment dicabut atau kedaluwarsa ditolak.

## Temuan audit yang masih perlu Agent 2
Monitoring page `GET /exams/{exam}/monitoring` hanya memakai middleware `auth/tenant/license` dan mengandalkan `ExamPolicy::monitor`; tidak memakai `EnsureTeacherPortalAccess`. Policy memakai `hasRole('KAMAD')` dan `hasRole('GURU')`, tanpa exactly-one persisted role. Cabang KAMAD dapat mengizinkan user multi-role GURU+KAMAD yang memiliki `exam.monitor`. Ini adalah ketidakkonsistenan role boundary pada source, bukan exploit yang telah diuji. Sudah dicatat ke Issue #8 untuk Agent 2 dan diminta regression test multi-role pada halaman dan API monitoring. Agent Web Guru tidak mengubah policy/service bersama.

## Backup
Workflow CI di-backup sebelum penambahan dua suite monitoring; SHA backup **1/1 MATCH**. Lihat `MANIFEST_MONITORING_TESTS.md`. Total backup tahap ini: **18/18 file** byte-exact MATCH.

## Batas
Belum ada browser E2E/visual QA, concurrency/load test, UAT madrasah, atau deployment VPS. Uji multi-role monitoring yang diidentifikasi di atas masih pending dari Agent 2. Device lease/fencing tetap belum boleh dinyatakan siap produksi.

## Status
**Monitoring dan temporary-proctor regression suite: CI PASS 117/514.**
Role boundary multi-role pada halaman/API monitoring: **NEEDS AGENT 2 REVIEW/TEST**.
Tidak ada merge, deployment, migration produksi, atau perubahan DB produksi.
