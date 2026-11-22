# Agent 1 — Audit dan Desain Transisi Ujian Serentak Tanpa ExamSession
Tanggal: 22 November 2026
Repository: zane0184/cbt_november
Branch: work/stage4b-room-session-20261122
HEAD remote saat pemeriksaan: d4d9d3eb60404c09131f36f037c604055926694f

## Status
PARTIAL — audit source Stage4B inti telah diperbarui dan konflik desain dicatat. Implementasi runtime belum dimulai. Lock order lintas Agent 2, kontrak Attempt Agent 4, dan pemetaan seluruh caller/schema masih harus ditutup sebelum patch aman.

## 1. AUDIT

### 1.1 Git dan CI
- Branch Agent 1 ditemukan dan HEAD remote dibaca dari Git ref: d4d9d3eb60404c09131f36f037c604055926694f.
- Commit HEAD hanya menambah manifest backup dokumentasi Agent 3; bukan source runtime.
- Main saat pemeriksaan: feab77228f7108f7f7a45325d7f1fb878c07310b.
- Combined status pada HEAD Agent 1 kosong; workflow runs untuk exact SHA juga kosong.
- CI lama 44984303003 menguji source lama f1d01ea6fa4b9a100d00680a1b70c2a8d92b9af5, bukan HEAD sekarang dan bukan model tanpa ExamSession. Tidak dapat dipakai sebagai bukti P0 baru.

### 1.2 Source Stage4B yang diverifikasi
- Migration database/migrations/2026_11_22_110001_create_exam_room_session_scope.php — blob 9551b1dd79397bd0432f03eefc970852b675fd25.
- ExamRoomSessionService.php — blob e514fa9f751584e54af1125d48b5673e013f2339.
- ExamProctorAssignmentService.php — blob c968ce68c0decace1c35a0ccb3b2b4449c057656.
- ExamMonitoringScope.php — blob 11b455954f974d1c60ed5c49700368f8d37c09c1.
- ExamSession.php — blob d1d41ad8c7063edbd6b17e094a3b39bdec71664a.
- ExamAttemptController.php — blob 13e8981cca4653ff4e9b9448a50e4d0ec8a74bae.

### 1.3 Konflik langsung terhadap keputusan terbaru
Migration Stage4B membuat exam_sessions dengan session_number dan unique (tenant_id, exam_id, session_number), exam_session_rooms, exam_session_room_rombels, serta participant assignment yang mengacu ke exam_session_room_id. Service menerima session_number dan mengelola lifecycle Session. Ini seluruhnya bertentangan dengan model serentak tanpa sesi.

ExamMonitoringScope saat ini memberi bypass untuk Exam author dan KAMAD sebelum filter assignment/Room. Ini tidak memenuhi aturan Proktor room-scoped. KAMAD bukan portal/otoritas CBT; authoring Guru juga tidak otomatis menjadi capability pengawasan semua ruang.

ExamProctorAssignmentService saat ini memvalidasi Guru exact-one role dan TeacherProfile, tetapi assignment masih terikat SessionRoom dan waktu assignment dibatasi Session. Ini perlu dialihkan ke Exam schedule + Room dan dua mode operasi.

### 1.4 Temuan transaksi/lock
Audit method-level sebelumnya menunjukkan variasi urutan lock pada create/map/release/cancel/reschedule/updateRoom, scheduler, dan Attempt. UpdateRoom memulai dari Room; scheduler mengunci Session lalu Assignment; Attempt start/resume mengunci Exam → Participant → Attempt; assignment service mengunci Exam → Room → SessionRoom → Session → User → Assignment. Tidak boleh menambah urutan lock baru sebelum Agent 2 menetapkan kontrak global.

Bentrok waktu Proktor lintas Exam/Room tidak dapat dijamin oleh unique key biasa. Dibutuhkan serialisasi pada identitas Guru (atau mekanisme setara yang disepakati Agent 2) dan pemeriksaan overlap interval dalam transaksi. Interval yang digunakan adalah [starts_at, ends_at), sehingga end=09:00 dan next start=09:00 tidak overlap.

## 2. DESAIN TARGET — TANPA SESSION

### 2.1 Relasi konseptual
- Exam: satu jendela resmi start_at/end_at, status, token, tenant.
- ExamRoom: ruang ujian yang secara langsung terdaftar pada Exam; membawa kapasitas/status dan identitas ruang yang relevan.
- ExamParticipant: roster resmi peserta Exam.
- ExamParticipantRoomAssignment: penempatan peserta ke ExamRoom; satu penempatan aktif per peserta per Exam.
- ExamRoomRombel: sumber roster kelas untuk penempatan massal yang disahkan Admin; bukan kelas asal Guru Proktor.
- ExamProctorAssignment: Exam + ExamRoom + proctor_user_id (akun GURU) + starts_at/expires_at + status + actor/audit.
- ExamAttempt/Answer/Score tetap terikat ke Exam dan Participant; Room tidak menjadi admission gate.
- Tidak ada ExamSession, SessionRoom, session_number, wave, atau session lifecycle dalam model operasional baru.

Catatan desain: sebelum schema final, audit perlu memastikan apakah ExamRoom merupakan entitas ruang fisik reusable atau alokasi ruang per Exam. Bila reusable, relasi alokasi Exam–Room harus berupa pivot langsung Exam–Room; bila tidak, ExamRoom dapat langsung membawa exam_id. Jangan memilih salah satu tanpa memeriksa model ruang yang sudah ada dan kebutuhan pemakaian ulang ruang.

### 2.2 Mode A — asesmen mandiri Guru
- Capability authoring/monitoring terbatas ke Exam milik Guru dan peserta yang dapat dikelola berdasarkan TeacherAccess/penugasan kelas.
- Roster hanya dari kelas/peserta yang Guru berhak kelola.
- Tidak boleh memakai assignment Admin untuk melewati batas kelas.
- Guru tidak boleh memantau Exam Guru lain atau peserta lintas kelas.
- Aksi reset login hanya jika permission eksplisit, policy server-side, status Attempt memenuhi syarat, dan audit event tercatat.

### 2.3 Mode B — ujian terpusat
- Admin menetapkan Guru dengan exact-one role GURU sebagai Proktor untuk ExamRoom tertentu.
- Cross-class hanya berlaku untuk peserta roster/placement Room yang ditetapkan secara resmi.
- Assignment memuat rentang waktu, status, assigned_by, revoked_by, revoked_at, reason, dan audit trail.
- Proktor hanya memperoleh roster/status kehadiran/status ujian/progres dalam Room tersebut.
- Tidak ada akses jawaban, answer key, nilai, bank soal, konfigurasi Exam, Force Submit, Reset Attempt, assignment lain, atau Room lain kecuali permission operasional khusus disetujui terpisah.

### 2.4 Otorisasi monitoring
- Pisahkan capability pembuat Exam (authoring) dari capability Proktor sementara (assignment).
- Query Proktor wajib mengikat tenant + Exam + Room + assignment aktif + rentang waktu + participant placement aktif.
- Seluruh endpoint/API, controller, policy, Livewire, export, dan query turunan memakai scope yang sama.
- Payload monitoring allowlist hanya identitas minimum, attendance/status/progress. Jangan serialisasi relasi Answer, key, score atau isi jawaban.
- Direct API dan export wajib diuji, bukan hanya UI.

## 3. TRANSISI HISTORI — FORWARD ONLY
- Jangan drop exam_sessions atau tabel turunannya selama ada histori, foreign key, audit, attempt, answer, score, atau referensi lain.
- Jangan auto-pilih Session bila satu Exam memiliki beberapa Session atau data berbeda/ambigu.
- Pertahankan tabel dan baris lama sebagai legacy read-only/history bila sudah terisi.
- Hentikan pembuatan Session/wave baru pada code path baru.
- Pemetaan SessionRoom → ExamRoom dan placement lama hanya boleh dilakukan jika relasi/provenance dapat dibuktikan secara deterministik.
- Data orphan/ambiguous diekspor ke laporan karantina untuk keputusan manual; migration harus fail-closed bila data operasional yang diperlukan tidak dapat dipetakan.
- Siapkan migration forward-only bertahap: preflight counts/references; create direct Exam–Room schema; copy verified mappings preserving IDs/provenance; validate counts/FKs/uniques; switch application reads/writes; mark old tables legacy; drop legacy schema hanya melalui rencana terpisah setelah retensi disetujui.
- Jangan menjalankan migration pada database produksi. Uji pada clone/disposable MySQL 8/InnoDB dari baseline representatif.

## 4. DEPENDENCY LINTAS AGENT
- Agent 2: keputusan lock order global, serialisasi bentrok assignment Guru lintas Exam/Room, Answer update lock, lifecycle Exam, idempotency dan audit.
- Agent 3: UI Admin/Guru harus memakai Exam+Room langsung, tanpa Session UI; Proktor menu di Web Guru memakai backend room-scoped.
- Agent 4: admission memakai Exam schedule/token/server time; Attempt aktif yang masih eligible resume sebagai Attempt yang sama; Room bukan admission gate.
- Agent 1 tidak mengubah routes/web.php (milik integrasi Agent 2), AuthWebController.php/routes/teacher.php/layout/komponen Guru (milik Agent 3), atau Web Siswa (milik Agent 4).

## 5. PENGUJIAN WAJIB (BELUM DIJALANKAN)
1. Mode A own Exam/assigned class PASS; cross-class/other Exam DENY.
2. Mode B Admin assignment to Room cross-class PASS; unassigned Room DENY.
3. Proktor cannot read answer/key/score/config or Force Submit/Reset Attempt.
4. Assignment overlap across Room/Exam denied; [07:00,09:00) + [09:00,11:00) accepted.
5. Concurrent assignment requests for same Guru serialized; no overlapping rows.
6. Participant placement valid and unique; unauthorized move denied.
7. Tenant isolation across Admin/Guru/Siswa.
8. Monitoring endpoint and export strictly room-scoped and payload excludes answers/keys/scores.
9. Exam schedule changes/cancel/status transition versus active Attempts serialized safely.
10. Idempotent replay for assignment/placement/revoke.
11. Legacy migration preserves all references and rejects ambiguous data.
12. No active runtime dependency on ExamSession/session_number/wave.
13. MySQL 8/InnoDB multi-connection tests for FK, lock, race, deadlock and interval overlap.

Environment/testing evidence: no PHPUnit, migration, MySQL restore, concurrency, Browser/Livewire/API E2E or UAT run in this audit. No VPS access attempted.

## 6. STATUS / NEXT
Status: PARTIAL / P0 OPEN.
Next: complete repository-wide inventory of Session references and callers; obtain Agent2 lock contract; obtain Agent3/4 interface confirmations; finalize reusable Room mapping; then backup every source file and implement direct Exam–Room + Mode A/B + monitoring scope with MySQL disposable tests.
