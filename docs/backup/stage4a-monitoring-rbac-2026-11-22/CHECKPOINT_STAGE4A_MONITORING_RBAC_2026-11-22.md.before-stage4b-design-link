# CHECKPOINT — STAGE 4A MONITORING RBAC
Tanggal: 22 November 2026
Repository: zane0184/cbt_november
Branch kerja: work/stage4a-monitoring-rbac-20261122
HEAD awal yang diminta: 17c20c66284deb7a18e3a6dde2b3d47f9c966bd9
Source aplikasi terakhir teruji: 88f1510a7d1992f6cd8d4b727daf93fe28a3afd3
CI run: 44952383038 — SUCCESS

## STATUS
- Fixture Admin sebagai pembuat ujian telah diganti menjadi Guru pembuat ujian dengan relasi akademik yang sah.
- Guru pembuat ujian dan Guru Proktor sementara diuji terpisah.
- Admin ditolak pada monitoring URL/API langsung dan endpoint CBT representatif.
- Penugasan Proktor memakai Guru dengan TeacherProfile aktif, status PENDING → CONFIRMED, revoke, interval server, dan conflict check.
- Legacy ACTIVE fail-closed menjadi NEEDS_REVIEW.
- Stage 4A masih PARTIAL: ruang/sesi dan pemetaan peserta ke ruang/sesi belum ada di schema; scope monitoring Proktor belum dapat dibatasi hingga level ruang/sesi.
- Tidak ada PR, merge, migration produksi, deployment VPS, atau akses database produksi.

## AUDIT
- Baseline MonitoringDuplicationTest menggunakan Admin sebagai pembuat Assessment/Exam dan mengharapkan monitoring 200; fixture tidak mewakili RBAC yang benar.
- Service/UI assignment Proktor masih mencari role PROKTOR permanen.
- Status ACTIVE digunakan untuk assignment, bertentangan dengan keputusan kanonis.
- Policy monitoring belum mengakui assignment sementara sebagai capability.
- Endpoint execution/submit, jawaban, dan heartbeat memeriksa device header sebelum otorisasi role.
- Tidak ada model/tabel ruang ujian, sesi ujian, relasi ruang-sesi, atau pemetaan peserta ke ruang-sesi.
- UserManagement, StudentRombelManagement, CurriculumManagement, dan modul operasional Web Admin masih belum lengkap.

## IMPLEMENTASI
File source:
- app/Policies/ExamPolicy.php
- app/Services/ExamProctorAssignmentService.php
- app/Models/ExamProctorAssignment.php
- app/Livewire/Admin/ProctorAssignmentManagement.php
- resources/views/livewire/admin/proctor-assignment-management.blade.php
- database/migrations/2026_11_22_100001_enforce_confirmed_proctor_assignment_capability.php
- app/Http/Controllers/Api/V1/ExamAttemptController.php
- app/Http/Controllers/Api/V1/ExamAnswerController.php
- app/Http/Controllers/Api/V1/MonitoringController.php
- tests/Feature/MonitoringDuplicationTest.php
- tests/Feature/ProctorAssignmentSecurityTest.php
- .github/workflows/wa01-alternative-isolated-runtime.yml

Perubahan kunci:
- Guru author: role GURU + permission exam.monitor + exam.created_by + TeacherAccess.canManageExam.
- Guru Proktor: role GURU tunggal + TeacherProfile aktif tenant-local + assignment CONFIRMED yang cocok tenant/exam/user dan interval server.
- Admin: hanya dapat membuat/mengonfirmasi/mencabut assignment; tidak mendapat monitoring atau akses operasional CBT.
- PENDING dan legacy ACTIVE tidak memberi capability.
- Header device tidak lagi mendahului role check pada execution/submit, answer read/write, dan heartbeat.
- Semua transisi assignment dicatat melalui AuditService.

## BACKUP
- Folder: docs/backup/stage4a-monitoring-rbac-2026-11-22/
- 31 snapshot tersedia pada source aplikasi yang diuji; 2 snapshot tambahan dibuat sebelum pembaruan laporan/checkpoint.
- Snapshot tambahan antar-patch mempertahankan state sebelum koreksi lanjutan tanpa menimpa backup sebelumnya.

## PENGUJIAN
- GitHub Actions run 44952383038: SUCCESS; 30 langkah sukses, 0 gagal.
- MySQL Community Server 8.0.46 disposable; PHP 8.3.
- 109 test / 578 assertions PASS pada suite terpilih.
- MonitoringDuplicationTest: 8 / 42 assertions PASS.
- ProctorAssignmentSecurityTest: 8 / 34 assertions PASS.
- PHP lint: 333 file PASS.
- required_schema_ready=true; missing_required_tables=[].
- Database disposable dibersihkan setelah tes.
- Tidak ada uji produksi, browser manual, UAT madrasah, stress multi-process, atau concurrent deadlock multi-koneksi.

## LANGKAH BERIKUTNYA
1. Stage 4B — ruang ujian, sesi, relasi ruang-sesi, pemetaan rombel/peserta, assignment Proktor pada scope ruang-sesi, dan pembatasan query seluruh endpoint monitoring/attempt/report.
2. Stage 4B — schedule revision, NEEDS_REVIEW, EXPIRED/COMPLETED, conflict lintas assignment, serta uji multi-koneksi.
3. Stage 4C — UI Web Admin penugasan Guru mapel/rombel/tahun/semester.
4. Stage 4D–4E — lifecycle akun Guru/Siswa, histori rombel/kenaikan/kelulusan, dan kurikulum Kurmer/KBC/Panca Cinta/Pembelajaran Mendalam.
5. Stage 4F–4G — laporan administratif, konfigurasi, audit, health, backup/restore, performance, integrasi, dan UAT.
6. Tetap tidak mengerjakan Kamad, Super Admin, portal Proktor permanen, Android, PR/merge, migration produksi, atau deployment VPS.
