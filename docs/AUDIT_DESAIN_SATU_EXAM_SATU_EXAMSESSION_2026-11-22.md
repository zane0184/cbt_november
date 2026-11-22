# AUDIT DAN DESAIN TEKNIS — SATU EXAM, SATU EXAMSESSION OPERASIONAL
Tanggal: 22 November 2026
Branch: work/stage4b-room-session-20261122
Repository: zane0184/cbt_november

## Keputusan desain yang menjadi dasar
Satu Exam memiliki tepat satu ExamSession operasional. Semua ExamRoom fisik, penempatan rombel/peserta, dan assignment Proktor untuk Exam tersebut berada di bawah session yang sama. Session bukan admission gate peserta. Jadwal resmi hanya Exam.start_at/end_at dan waktu server. ExamSession.starts_at/ends_at wajib identik dengan jadwal Exam. Assignment Proktor boleh memiliki starts_at/expires_at lebih sempit sesuai kebutuhan, tetapi tidak mengubah jadwal peserta.

## 1. AUDIT

### 1.1 Git, checkpoint, dan CI
- Branch ditemukan: work/stage4b-room-session-20261122.
- Checkpoint penutupan yang dibaca: docs/CHECKPOINT_PENUTUPAN_STAGE4B_STAGE4C_DESIGN_AUDIT_2026-11-22.md.
- HEAD yang dinyatakan checkpoint dan diverifikasi melalui compare: 5d4d9dc7350fbab49a38842ce1f8964bed42cd88.
- Source SHA CI: f1d01ea6fa4b9a100d00680a1b70c2a8d92b9af5.
- Compare source CI ke HEAD: 3 commit, seluruhnya hanya menambah dokumen audit/laporan/checkpoint; tidak ada file source, migration, schema, atau workflow berubah.
- Working tree lokal: NOT VERIFIED. Pemeriksaan GitHub remote tidak dapat melihat perubahan lokal yang belum di-commit.
- CI run 44984303003, job safe-runtime-tests: SUCCESS. 35/35 step sukses, 0 failure, 0 skipped.
- Empat suite target: 24 test / 120 assertions. Runtime MySQL 8.0.46 InnoDB pada disposable database.
- Artifact: wa01-alternative-runtime-44984303003-1; ID 14739069544; SHA-256 62856a1a0b94b77db9e096c711f24985cca4c7c0dfbaf354362ba9ab44034904.
- Tidak ada test baru dijalankan pada audit desain ini; tidak mengulang CI yang sudah lulus.

### 1.2 Jalur pembuatan dan perubahan session
- Satu jalur aplikasi yang ditemukan: Livewire Admin ExamRoomSessionManagement::createSession() memvalidasi selectedExamId, nama, nomor, startsAt, endsAt lalu memanggil ExamRoomSessionService::createSession().
- Service createSession() mengunci baris Exam, memeriksa status PUBLISHED/locked dan validasi rentang, tetapi hanya menolak nomor sesi yang sama. Nomor berbeda masih dapat membuat beberapa ExamSession pada Exam yang sama.
- Migration 2026_11_22_110001_create_exam_room_session_scope.php mempunyai unique (tenant_id, exam_id, session_number), bukan unique (tenant_id, exam_id). Karena itu database juga masih mengizinkan beberapa session per Exam.
- Jalur perubahan jadwal: Livewire selectSessionForReschedule()/rescheduleSession() memanggil ExamRoomSessionService::rescheduleSession(). Service mengubah ExamSession.starts_at/ends_at, menaikkan schedule_revision dan membuat assignment Proktor PENDING/CONFIRMED terdampak menjadi NEEDS_REVIEW. Jadwal Exam tidak diubah oleh jalur ini.
- Validasi validateSessionWindow() hanya memastikan window berada di dalam jadwal Exam; tidak mewajibkan equality.
- UI Blade masih menampilkan nomor session, input waktu session, tombol Buat Sesi, dan Ubah Jadwal.

### 1.3 Constraint dan relasi
- exam_sessions: unique (id, tenant_id, exam_id), unique (tenant_id, exam_id, session_number), index exam window, FK (exam_id, tenant_id) -> exams.
- exam_session_rooms: unique (id, tenant_id, exam_id), unique (tenant_id, exam_session_id, exam_room_id), FK composite ke exam_sessions dan exam_rooms.
- exam_session_room_rombels dan exam_participant_room_assignments memiliki FK ke exam_session_rooms; assignment peserta dan histori room harus dipertahankan.
- ExamProctorAssignment juga menunjuk exam_session_room_id; schedule_revision menjadi guard terhadap scope yang berubah.
- Unique nomor session saat ini tidak mencegah session kedua dengan nomor berbeda. Menambah unique langsung (tenant_id, exam_id) berisiko gagal pada database yang sudah memiliki duplikat.

### 1.4 Lifecycle, Proktor, monitoring, dan peserta
- ExamSessionLifecycleService::synchronize() mencari SCHEDULED/ACTIVE berdasarkan starts_at/ends_at session, lalu mengubah status ACTIVE/COMPLETED dan menyelesaikan/meng-expire assignment Proktor. Scheduler tidak mengubah Attempt/Answer.
- ExamProctorAssignmentService::assign() mengunci Exam, ruang fisik, session-room, session, lalu Guru; assignment terikat pada Exam + session-room. Window assignment divalidasi berada di dalam window session.
- ExamProctorAssignment::confirmedNow() memerlukan assignment CONFIRMED, belum dicabut, window assignment aktif, room ACTIVE, session SCHEDULED/ACTIVE, revision sama, dan window assignment berada di dalam window session.
- ExamMonitoringScope::participantQuery() hanya mengembalikan peserta dengan placement ACTIVE pada session-room yang memiliki assignment Proktor confirmedNow. Proktor tidak otomatis dapat memonitor semua ruang.
- ExamPolicy::startAttempt(), Exam::canStartAttempt()/isOngoing(), dan ExamAttemptController::store() menggunakan jadwal global Exam dan waktu server; jalur start tidak membaca ExamSession, ExamSessionRoom, atau placement peserta. Field eligible_from/until tetap dapat menjadi batas individual jika diisi proses lain, tetapi bukan akibat room/session mapping.
- ExamController::lock() menerbitkan Exam dan membuat token otomatis bila belum ada; token tetap melekat pada Exam.

### 1.5 Histori dan data lama
- Database produksi tidak diakses dan jumlah Exam yang memiliki lebih dari satu ExamSession belum diketahui: NOT RUN / NOT VERIFIED.
- Migrasi langsung menambahkan unique (tenant_id, exam_id) belum aman sebelum inventaris data duplikat.
- Child history mencakup session-room, mapping rombel, participant-room assignment, assignment Proktor, audit log, dan Attempt/Answer/Score yang terikat pada peserta/Exam. Tidak boleh dihapus untuk merapikan session.
- Exam yang sedang memiliki Attempt IN_PROGRESS tidak boleh dipindahkan penempatan aktifnya secara otomatis. Rekonsiliasi session ganda untuk Exam tersebut harus ditunda sampai aman, atau disusun prosedur khusus tanpa mengubah Attempt/Answer/Score.

### 1.6 Dampak Agent 2 dan Agent 3
- Branch Agent 2 ditemukan: work/stage4d-account-lifecycle-20261122. Pemeriksaan read-only pada ExamSession, ExamParticipantRoomAssignment, ExamProctorAssignment, dan Exam menunjukkan model/scope yang sama dan tetap bergantung pada identitas session-room. Jangan mengubah branch Agent 2. Koordinasi diperlukan agar Device Lease/fencing tidak menjadikan waktu session sebagai gate mulai Attempt dan tidak mengubah FK session-room/history.
- Branch Agent 3 yang relevan ditemukan: work/web-guru-foundation-20261122. Model Exam/ExamSession/room-assignment yang diperiksa masih memakai kontrak yang sama. Jangan mengubah branch Agent 3. Kontrak Web Guru harus tetap mengelola jadwal resmi di Exam; tidak boleh menambahkan pilihan jadwal session atau gelombang.
- Branch Agent 2/3 tidak diubah.

## 2. DESAIN TEKNIS YANG DIREKOMENDASIKAN — BELUM DIIMPLEMENTASIKAN

### 2.1 Satu session operasional
- Pertahankan tabel ExamSession dan seluruh tabel room/placement/assignment.
- Tambahkan penanda operasional pada ExamSession, misalnya is_operational, agar satu row ditetapkan sebagai session operasional dan row lama lain dapat dipertahankan sebagai histori non-operasional.
- Tegakkan maksimal satu row operasional per (tenant_id, exam_id) dengan unique conditional index: MySQL 8 functional/generated expression index; SQLite test runtime menggunakan partial unique index. DDL final harus diverifikasi kompatibilitas pada versi MySQL 8+ yang ditargetkan.
- Service createSession() tetap mengunci Exam terlebih dahulu, menolak jika session operasional sudah ada, dan membuat satu session dengan session_number=1 serta nama tetap/terstandar. Unique conditional index menjadi perlindungan race di tingkat database.
- Admin tetap membuat wadah operasional satu kali bila belum ada, lalu menambahkan banyak ruang ke session yang sama. Tidak ada Session 2, Session 3, dan seterusnya.

### 2.2 Jadwal session selalu mengikuti Exam
- Saat session operasional dibuat, starts_at = Exam.start_at dan ends_at = Exam.end_at; input waktu session dihapus dari UI dan tidak diterima sebagai sumber nilai service.
- Aksi reschedule session dihapus dari UI. Service rescheduleSession() sebaiknya dipertahankan sementara sebagai compatibility guard yang selalu menolak perubahan waktu, bukan diam-diam mengubah jadwal.
- Bila lifecycle Guru di masa depan mengizinkan perubahan jadwal Exam setelah session dibuat, perubahan hanya melalui jalur otorisasi/lifecycle Exam milik Guru dan harus menyelaraskan session dalam transaksi yang sama serta menambah schedule_revision/NEEDS_REVIEW untuk assignment yang terdampak. Jangan memberi Admin jalur alternatif.
- Assignment Proktor tetap boleh memiliki rentang waktu yang lebih pendek daripada Exam, sepanjang starts_at < expires_at dan keduanya berada di dalam window Exam/session.

### 2.3 Scheduler dan monitoring
- Scheduler hanya memproses session operasional. Waktu aktivasi/penyelesaian harus bersumber dari jadwal Exam (session mirror harus sama); lock order harus konsisten Exam -> session agar tidak menambah deadlock terhadap service ruang/proktor.
- confirmedNow tetap memvalidasi assignment terhadap session-room dan revision, dengan invariant session operational serta waktu session sama dengan Exam.
- Monitoring tetap scoped ke session-room yang ditugaskan. Satu session tidak berarti satu Proktor memonitor semua ruang.
- Jangan menjadikan session-room atau placement sebagai admission gate peserta. Exam global tetap satu-satunya jadwal mulai peserta, di luar eligible_from/until yang sah.

### 2.4 Rekonsiliasi data lama
Tahapan aman yang disarankan:
1. Jalankan preflight read-only pada salinan database terisolasi/backup yang sudah diverifikasi; keluarkan daftar Exam tanpa session, satu session, dan lebih dari satu session; sertakan status session, jumlah room, mapping rombel, placement ACTIVE/RELEASED, assignment Proktor per status, serta jumlah Attempt IN_PROGRESS.
2. Tidak memilih canonical session otomatis untuk Exam yang memiliki beberapa session. Buat manifest keputusan per Exam; pemilihan canonical harus mempertimbangkan ruang yang terpakai, placement, assignment Proktor, status lifecycle, dan Attempt aktif.
3. Untuk Exam tanpa Attempt IN_PROGRESS, pilih satu canonical session, set window-nya sama dengan Exam, buat/pastikan satu session-room per ruang fisik yang dipakai, pindahkan mapping aktif dengan pola release historis + create active baru, dan jangan menghapus row lama.
4. Assignment Proktor lama pada scope yang dipindah harus menjadi REVOKED/NEEDS_REVIEW sesuai keadaan; assignment baru dibuat pada session-room canonical dan dikonfirmasi ulang. Jangan mempertahankan capability Proktor dari scope lama secara implisit.
5. Session lama ditandai non-operasional dan terminal (misalnya CANCELLED dengan audit reason rekonsiliasi); row session, session-room, mapping, assignment, dan audit tetap disimpan. Jangan hard delete.
6. Exam yang memiliki Attempt IN_PROGRESS ditunda dari cutover penempatan sampai ujian aman selesai. Attempt, Answer, Score tidak dipindah, di-reset, atau diubah.
7. Setelah preflight membuktikan tepat satu operational session untuk setiap Exam yang membutuhkan room management, aktifkan unique conditional index dan guard aplikasi.
8. Uji proses rekonsiliasi pada disposable MySQL 8+ dengan salinan fixture realistis sebelum rencana produksi. Tidak ada migration produksi dalam tahap ini.

### 2.5 FK dan histori
- Pertahankan semua FK composite tenant/exam/session-room.
- Hindari mengganti ID ExamSessionRoom yang masih direferensikan oleh placement atau Proktor history.
- Untuk perpindahan aktif, pertahankan row lama sebagai RELEASED/REVOKED dan buat row aktif baru pada canonical session-room; catat audit before/after dan alasan.
- Jangan menghapus ExamSession lama sebelum semua dependensi dan histori dibuktikan aman. Rekomendasi utama adalah menyimpan session lama sebagai non-operational historical records.

### 2.6 File yang kemungkinan perlu diubah setelah persetujuan
1. backend-laravel/database/migrations — migration additive untuk is_operational dan unique conditional index; tanpa drop tabel/kolom/FK lama.
2. backend-laravel/app/Models/ExamSession.php — cast/guard is_operational dan relasi/query operasional.
3. backend-laravel/app/Services/ExamRoomSessionService.php — one-session guard, jadwal dari Exam, reschedule fail-closed, scope/tenant/locking.
4. backend-laravel/app/Services/ExamSessionLifecycleService.php — filter operational dan jadwal Exam.
5. backend-laravel/app/Services/ExamProctorAssignmentService.php — pastikan hanya session operasional yang menerima assignment baru; tetap per ruang.
6. backend-laravel/app/Models/ExamProctorAssignment.php dan ExamMonitoringScope.php — pertahankan scope per room, validasi invariant operational.
7. backend-laravel/app/Livewire/Admin/ExamRoomSessionManagement.php — hilangkan input session number/time dan aksi reschedule; tampilkan satu session operasional.
8. backend-laravel/resources/views/livewire/admin/exam-room-session-management.blade.php — UI satu wadah dan banyak ruang.
9. backend-laravel/tests/Feature/ExamRoomSessionManagementTest.php, ExamRoomSessionConcurrencyTest.php, MonitoringDuplicationTest.php, ProctorAssignmentSecurityTest.php serta test lifecycle scheduler yang benar-benar ada — tambah regression coverage.
10. docs — migration preflight, backup manifest, laporan, checkpoint, dan handover.

## 3. IMPLEMENTASI
- Tidak ada perubahan source aplikasi.
- Tidak ada migration dibuat/dijalankan.
- Tidak ada perubahan database.
- Tidak ada perubahan Agent 2/Agent 3.
- Dokumen audit ini adalah dokumentasi desain saja.

## 4. PENGUJIAN
- CI Stage 4B–4C lama: PASS pada SHA f1d01ea6fa4b9a100d00680a1b70c2a8d92b9af5, run 44984303003.
- Test baru untuk keputusan satu session: NOT RUN.
- Uji disposable MySQL untuk legacy duplicate-session reconciliation: NOT RUN.
- Working tree lokal: NOT VERIFIED.
- Produksi: NOT RUN / tidak disentuh.

## 5. BACKUP
- Tidak ada source file yang diubah sehingga tidak ada source backup baru.
- Backup manifest lama tetap tersedia:
  - docs/backup/stage4b-room-session-2026-11-22/MANIFEST.md
  - docs/backup/stage4c-teacher-subject-assignment-2026-11-22/MANIFEST_SUPPLEMENT.md
- Penambahan dokumen audit/checkpoint baru tidak menimpa file lama.
- ZIP baseline tidak disentuh.

## 6. CHECKPOINT DAN HANDOVER
- Branch Agent 1: work/stage4b-room-session-20261122.
- HEAD dasar audit: 5d4d9dc7350fbab49a38842ce1f8964bed42cd88.
- Source CI tetap: f1d01ea6fa4b9a100d00680a1b70c2a8d92b9af5.
- Status: menunggu persetujuan desain teknis sebelum coding.
- Langkah berikutnya setelah disetujui: buat backup snapshot untuk setiap file source yang akan diubah, lakukan migration additive di disposable MySQL, implementasi guard service/database/UI/scheduler, jalankan seluruh pengujian baru, lalu buat laporan dan checkpoint.
- Jangan merge/rebase/PR/deploy atau mengubah DB produksi tanpa persetujuan.
- Agent 2 tetap pemilik Stage 4D; Agent 3 tetap pemilik Web Guru. Koordinasi kontrak saja.
