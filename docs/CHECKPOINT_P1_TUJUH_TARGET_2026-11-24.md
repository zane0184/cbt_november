# CHECKPOINT P1 — PENYELESAIAN TUJUH TARGET WEB CBT
Tanggal: 24 November 2026
Repository: `zane0184/cbt_november`
Branch kerja lanjutan: `work/p1-seven-targets-20261124`
Base branch/commit: `work/phpunit-41-root-cause-20261123` / `1c33e5d422175f9bd85d206230271a9fe5a4eecd`
PR P0: #22 (tetap Draft, tidak diubah)

## GARIS BATAS P0 — WAJIB DIPERTAHANKAN
- P0 PHPUnit telah dibuktikan pada CI #45218279043: 518 passed, 0 failed, 2.189 assertions; PHP 8.3 + disposable MySQL 8.0.46/InnoDB.
- Commit P0 yang diuji adalah leluhur/base branch di atas. Tidak ada perubahan yang dilakukan pada commit tersebut.
- Pekerjaan P1 dilakukan pada branch turunan baru. Jangan mengulang patch, mengedit branch P0, atau mengklaim hasil P1 sebagai hasil CI P0.
- Tidak ada merge, migration produksi, deployment produksi, atau ZIP final sebelum acceptance gate dan persetujuan owner.

## AUDIT AWAL P1

### 1. Proktor Mode B (Exam + Room)
- Source yang ditemukan: `ExamProctorAssignment`, migration `2026_11_20_100001_create_exam_proctor_assignments_table.php`, `ExamProctorAssignmentService`, `ProctorAssignmentManagement`.
- Tidak ditemukan model/migration Room atau Room placement pada tree branch base yang diperiksa.
- `ExamProctorAssignmentService::assign()` fail-closed dengan LogicException.
- `ProctorAssignmentManagement::assign()` fail-closed; daftar ujian dan Guru kosong.
- Alasan blocker: owner belum menyetujui marker kanonis kelayakan ujian pusat dan siapa aktor yang menetapkannya. `assessment_origin=EXTERNAL` tidak boleh dianggap otomatis sebagai ujian pusat.
- Batas keamanan: role lokal hanya ADMIN_MADRASAH/GURU/SISWA; Proktor bukan role/portal; assignment tidak otomatis memberi akses monitoring/roster/Attempt/perangkat. Tidak boleh menambahkan ExamSession/SessionRoom/wave sebagai admission gate.

### 2. Web Admin ADM-01–23
- Matriks tersedia di `docs/MATRIKS_ADM_01_23_AUDIT_2026-11-23.md`.
- Semua ADM-01 sampai ADM-23 berstatus NOT VERIFIED pada matriks; tidak ada item boleh dinaikkan menjadi VERIFIED tanpa bukti UI, route/action, policy, service, persistence, audit, dan test.
- Audit lanjutan harus memetakan route dan source per item, kemudian melengkapi gap dalam urutan dependensi.

### 3. Web Guru
- Source portal Guru ada, termasuk QuestionBank, ExamManagement, Teacher Learning dan assignment context.
- DOCX adalah jalur utama import soal sesuai keputusan owner; XLSX question import/template/parser/wizard bukan target produk.
- SHORT_ANSWER tetap BLOCKED; ESSAY manual review.
- Integrasi mapping QuestionVersion dan immutable snapshot/provenance belum boleh diklaim selesai sebelum kontrak dan tes snapshot end-to-end lulus.

### 4. Web Siswa
- Runner client dan recovery tests tersedia pada branch Agent 4/PR #13, tetapi static/browser runner bukan bukti E2E browser-to-real-Laravel.
- Acceptance masih memerlukan server-backed token/schedule, durable answer queue, idempotency/receipt, fencing/recovery, media authorization, payload redaction, serta browser E2E dan UAT.
- Student payload tidak boleh memuat answer key, is_correct, explanation/rubric privat, atau data siswa lain.

### 5. Integrasi Kurikulum Merdeka + KBC
- Migration/model/service kontrak kurikulum sudah ada pada branch base.
- CP resmi tetap read-only; TP/ATP dikembangkan Guru dalam assignment scope.
- Sumber resmi, versi, tanggal berlaku, checksum dan status verifikasi harus dibedakan; jangan seed konten CP/PM/8 dimensi sintetis atau mengklaim verified tanpa artefak sumber.
- Mapping harus melekat pada QuestionVersion dan immutable Exam snapshot; histori laporan harus tetap stabil.

### 6. Instalasi dan deployment VPS
- Installer dan workflow paket deployment telah ada; dokumen P0.96A menyatakan VPS staging, backup/restore, browser operasional, load/security/UAT belum dibuktikan.
- User menggunakan aaPanel/File Manager; alur harus mendukung upload/extract, DB melalui aaPanel, konfigurasi .env oleh owner, document root ke `backend-laravel/public`.
- Tidak meminta SSH/password/token/private key. Pengujian dilakukan pada staging/database disposable; produksi memerlukan approval dan preflight.

### 7. ZIP distribusi final
- Workflow build ZIP deployment tersedia tetapi bukti artefak final yang lolos pemeriksaan source/dependency/struktur/secret-exclusion/README belum dikonfirmasi pada checkpoint ini.
- Final ZIP harus satu paket generik per madrasah, database dan storage terpisah per instalasi, tanpa backup/temp/secrets; Android dan Web Super Admin tetap ditunda sampai Juni 2027.

## DESAIN URUTAN EKSEKUSI
1. Tetapkan kontrak Mode B Exam+Room yang patuh owner: Room/placement bukan admission gate; proctor assignment tidak memperluas hak akses; marker ujian pusat eksplisit dan dapat diaudit. Implementasi assignment tetap tertahan bila marker/aktor penetap belum memiliki keputusan owner yang dapat dirujuk.
2. Audit ADM-01–23 dengan matriks source/route/policy/test aktual, lalu implementasikan per modul dan acceptance test.
3. Tutup Web Guru: authoring, DOCX import, versioning, media, curriculum mapping, exam builder, publish/token, result/scoring.
4. Tutup Web Siswa: real Laravel integration, browser E2E, security/redaction, offline/recovery and submit.
5. Tutup curriculum provenance and immutable snapshots across Admin/Guru/Siswa/reports.
6. Fresh install, upgrade path, backup/restore and staging deployment on aaPanel; no production mutation without approval.
7. Build and verify generic distribution ZIP only after all acceptance gates pass.

## IMPLEMENTASI
- Branch turunan P1 dibuat dari P0 branch head `1c33e5d422175f9bd85d206230271a9fe5a4eecd`.
- Pada checkpoint ini belum ada source aplikasi, schema, migration, atau database yang diubah pada branch P1.
- Tidak ada file pada commit P0 yang diedit.

## PENGUJIAN
- P0 evidence: CI #45218279043, 518/0, 2.189 assertions (PHP 8.3 + MySQL 8.0.46/InnoDB disposable).
- P1 tests: NOT RUN pada checkpoint awal.
- VPS deployment, production migration, ZIP final: NOT RUN.
