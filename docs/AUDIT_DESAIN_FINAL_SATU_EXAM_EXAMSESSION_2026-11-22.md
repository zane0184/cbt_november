# AUDIT DAN DESAIN TEKNIS FINAL — SATU EXAM = SATU EXAMSESSION OPERASIONAL
Tanggal: 22 November 2026
Repository: zane0184/cbt_november
Branch: work/stage4b-room-session-20261122
Status: DESAIN FINAL UNTUK PERSETUJUAN — TANPA IMPLEMENTASI

## Keputusan yang tidak berubah
SATU EXAM = SATU EXAMSESSION OPERASIONAL = SATU PELAKSANAAN UJIAN SERENTAK.
Satu session menampung seluruh ruang fisik, placement peserta, dan assignment Proktor. Jadwal resmi peserta hanya Exam.start_at/end_at dengan waktu server. Session tidak menjadi admission gate. Proktor hanya memonitor ruang yang ditugaskan. Token tetap melekat pada Exam. Seluruh histori dipertahankan.

## A. AUDIT

### A1. Branch, HEAD, working tree, checkpoint, dan CI
- Branch remote ditemukan: work/stage4b-room-session-20261122.
- Perbandingan ref branch dengan checkpoint HEAD terakhir a1d5063efa3cb0cabcda7af1b54381c7386567e7: IDENTICAL (ahead 0, behind 0). HEAD remote saat audit: a1d5063efa3cb0cabcda7af1b54381c7386567e7.
- Source SHA CI Stage 4B–4C: f1d01ea6fa4b9a100d00680a1b70c2a8d92b9af5.
- Compare source SHA CI ke HEAD branch: docs-only; tidak ada source aplikasi, migration, schema, atau workflow berubah.
- Working tree lokal: NOT VERIFIED. Tidak ada clone/workspace lokal /mnt/data/CBT_MADRASAH_RECONSTRUCTION pada runtime ini; pemeriksaan GitHub remote tidak dapat melihat uncommitted local changes. Tidak dinyatakan bersih.
- Checkpoint sebelumnya dibaca: docs/CHECKPOINT_SATU_EXAM_SATU_EXAMSESSION_DESIGN_2026-11-22.md dan docs/CHECKPOINT_PENUTUPAN_STAGE4B_STAGE4C_DESIGN_AUDIT_2026-11-22.md.
- CI run 44984303003: job safe-runtime-tests SUCCESS; 35/35 steps sukses, 0 failure, 0 skipped; empat suite target 24 tests / 120 assertions; disposable MySQL 8.0.46/InnoDB. Artifact 14739069544, SHA-256 62856a1a0b94b77db9e096c711f24985cca4c7c0dfbaf354362ba9ab44034904.
- Artifact CI diunduh dan diperiksa: hanya log/preflight JSON/runtime reports, tidak berisi SQL dump atau database snapshot. Tidak dipakai sebagai database untuk preflight.
- Tidak ada test dijalankan ulang.

### A2. File source dan test yang benar-benar diperiksa
Source:
- backend-laravel/app/Services/ExamRoomSessionService.php — blob e514fa9f751584e54af1125d48b5673e013f2339
- backend-laravel/app/Services/ExamSessionLifecycleService.php — blob 8e34152afd817f30cc2f81b5f25fc450571463ac
- backend-laravel/app/Services/ExamProctorAssignmentService.php — blob c968ce68c0decace1c35a0ccb3b2b4449c057656
- backend-laravel/app/Services/ExamMonitoringScope.php — blob 11b455954f974d1c60ed5c49700368f8d37c09c1
- backend-laravel/app/Livewire/Admin/ExamRoomSessionManagement.php — blob fde9c04383386058bf2e0c6ef9f651dc7a04ab8f
- backend-laravel/resources/views/livewire/admin/exam-room-session-management.blade.php — diperiksa
- backend-laravel/app/Http/Controllers/Api/V1/ExamController.php — blob c7c62e961f2ff47abee8f3b2468b4e7bc43d1cc9
- backend-laravel/app/Http/Controllers/Api/V1/ExamAttemptController.php — blob 13e8981cca4653ff4e9b9448a50e4d0ec8a74bae
- backend-laravel/app/Policies/ExamPolicy.php — diperiksa
- backend-laravel/app/Models/Exam.php — blob b679bf6d6a0f68074ef1a000c0345f0248f696f3
- backend-laravel/app/Models/ExamSession.php — diperiksa
- backend-laravel/app/Models/ExamSessionRoom.php — diperiksa
- backend-laravel/app/Models/ExamSessionRoomRombel.php — diperiksa
- backend-laravel/app/Models/ExamParticipant.php — diperiksa
- backend-laravel/app/Models/ExamParticipantRoomAssignment.php — diperiksa
- backend-laravel/app/Models/ExamProctorAssignment.php — diperiksa
- backend-laravel/app/Models/ExamAttempt.php — diperiksa
- backend-laravel/app/Models/ExamAttemptAnswer.php — diperiksa
- backend-laravel/app/Models/ExamAttemptScore.php — diperiksa
- backend-laravel/app/Models/ExamAttemptScoreCurrent.php — diperiksa
- backend-laravel/app/Models/ExamQuestionScore.php — diperiksa
- backend-laravel/app/Console/Commands/SyncExamSessionLifecycle.php — diperiksa
- backend-laravel/routes/console.php — diperiksa
- backend-laravel/database/migrations/2026_11_22_110001_create_exam_room_session_scope.php — blob 9551b1dd79397bd0432f03eefc970852b675fd25

Test:
- backend-laravel/tests/Feature/ExamRoomSessionManagementTest.php — blob 69ef2e60601da87036d65393eba9e8f9c080cee4
- backend-laravel/tests/Feature/ExamRoomSessionConcurrencyTest.php — blob e18800c1f4c5f80d4d355a0dbadfd72eadfbc8ec
- backend-laravel/tests/Feature/MonitoringDuplicationTest.php — blob 36062f3d7fde042fe014dd61a64ebb8ac86669d7
- backend-laravel/tests/Feature/ProctorAssignmentSecurityTest.php — blob 38d016f570e1449e020159d93371befd7d1dd495

Backup:
- docs/backup/stage4b-room-session-2026-11-22/MANIFEST.md
- docs/backup/stage4c-teacher-subject-assignment-2026-11-22/MANIFEST_SUPPLEMENT.md

### A3. Temuan source yang diperbarui/diperdalam

1. Pembuatan session
- ExamRoomSessionService::createSession() mengunci Exam terlebih dahulu, tetapi masih menerima name, session_number, starts_at, ends_at dari Admin.
- Validasi hanya menolak nomor sesi yang sama. Nomor berbeda tetap dapat membuat session lain.
- Tidak ada invariant database satu session per Exam. Migration hanya unique (tenant_id, exam_id, session_number).
- UI Admin menampilkan input nama, nomor, waktu mulai/selesai, dan tombol Buat Sesi.

2. Perubahan jadwal session
- rescheduleSession() mengubah starts_at/ends_at session secara independen, memeriksa overlap ruang, menolak bila ada Attempt IN_PROGRESS pada placement aktif, menaikkan schedule_revision, dan membuat assignment Proktor terdampak NEEDS_REVIEW.
- Ini bertentangan dengan jadwal serentak. Metode tersebut harus menjadi compatibility guard yang menolak semua perubahan jadwal independen; bukan sekadar disembunyikan dari UI.

3. Jalur jadwal Exam
- ExamController::store() membuat Exam DRAFT dengan start_at/end_at.
- ExamController::update() mengizinkan update jadwal selama Exam belum locked melalui ExamPolicy::update().
- ExamPolicy::update() menolak Exam yang sudah locked.
- ExamController::lock() mengunci Exam row, mengubah status menjadi PUBLISHED, menetapkan locked_at/published_at, dan membuat token otomatis bila belum ada. Jadwal tidak diubah saat lock.
- Tidak ditemukan jalur sah reschedule Exam setelah publish yang sah. Setelah locked, jadwal seharusnya immutable.
- TEMUAN RACE BARU: ExamController::update() melakukan authorize terhadap model yang belum dikunci lalu langsung update tanpa transaction/lockForUpdate dan tanpa re-authorize setelah lock. Request update yang lolos policy saat DRAFT dapat berlomba dengan lock() dan menulis start_at/end_at sesudah Exam dipublikasikan. Desain harus menutup race ini: update wajib transaction, lock Exam, re-authorize terhadap row terkunci, baru update.
- Karena session hanya dibuat untuk Exam PUBLISHED/locked, perubahan jadwal yang sah saat ini terjadi sebelum session ada. Konsistensi session saat ini dicapai dengan mengambil nilai jadwal Exam ketika session dibuat. Perubahan jadwal setelah publish tidak boleh ditambahkan tanpa jalur Guru khusus yang dirancang dan diuji.

4. Scheduler/lifecycle
- SyncExamSessionLifecycle::synchronize() saat ini memilih kandidat berdasarkan ExamSession.starts_at/ends_at.
- Scheduler mengunci session saja, bukan Exam terlebih dahulu, dan tidak memeriksa apakah session merupakan satu-satunya session operasional.
- Saat due, scheduler mengubah SCHEDULED -> ACTIVE atau -> COMPLETED dan mengubah assignment Proktor sesuai expires_at/status.
- Scheduler tidak membaca atau mengubah Attempt/Answer.
- Desain harus membuat jadwal Exam sumber kebenaran scheduler, mengunci Exam -> session, dan hanya memproses session operasional.

5. Pembatalan session dan Exam — temuan penting
- Admin saat ini dapat memanggil cancelSession(); metode tersebut membatalkan session, melepaskan placement aktif, mencabut assignment Proktor, tetapi tidak mengubah Exam.status.
- ExamAttemptController::store() tidak membaca ExamSession/session-room/placement; ia mengizinkan peserta memulai berdasarkan Exam global, token, eligibility, dan waktu server.
- Karena itu Admin dapat membatalkan session sementara Exam tetap PUBLISHED dan peserta masih dapat memulai ujian. Ini menciptakan ujian yang terbuka tetapi ruang/monitoring operasionalnya dibatalkan.
- ExamController::close() mengubah Exam menjadi CLOSED tetapi tidak menyelaraskan status session atau assignment. ExamPolicy::startAttempt() masih mengizinkan resume Attempt IN_PROGRESS yang sah setelah jadwal mulai berakhir/Exam ditutup.
- Rancangan final: Admin tidak boleh membatalkan wadah session operasional secara independen. Penutupan ujian harus melalui lifecycle Guru pada Exam. Ketika Exam ditutup, session dan assignment diselaraskan secara atomik, tetapi Attempt IN_PROGRESS tetap boleh diselesaikan/resume sesuai aturan yang sudah ada; session/monitoring tidak boleh dicabut sebelum attempt aktif aman. Detail transisi harus diuji.
- Session operasional adalah identitas canonical dan tetap ditandai operational sepanjang lifecycle, termasuk COMPLETED/CANCELLED sebagai tombstone; tidak boleh membuat session pengganti untuk Exam yang sama. Untuk pelaksanaan ulang, buat Exam baru.

6. Room, placement, Proktor, monitoring
- attachRoom() mengunci Exam -> ExamRoom -> ExamSession. Satu session dapat memiliki banyak ExamSessionRoom; constraint unik tenant/session/physical room mencegah pasangan ruang duplikat pada session yang sama.
- mapRombel() mengunci Exam, room, session-room, session, rombel, dan peserta. Mapping dan placement menyimpan histori RELEASED; perpindahan dilakukan melalui release lalu penempatan baru.
- releaseRombelMapping() hanya mengizinkan perubahan sebelum session mulai; placement aktif terkait juga dilepas dan histori dipertahankan.
- Proctor assignment terikat pada Exam + exam_session_room_id; masa tugas harus berada di dalam rentang session. Guru yang ditugaskan harus role GURU tunggal dengan profil Guru aktif.
- ExamMonitoringScope memfilter peserta dari placement ACTIVE di room yang memiliki assignment CONFIRMED aktif. Proktor tidak otomatis memonitor semua ruang.
- NEEDS_REVIEW dan schedule_revision harus dipertahankan untuk perubahan scope. Perubahan penempatan tidak boleh memperluas scope atau mengubah jadwal Exam.

7. Histori Attempt/Answer/Score
- ExamAttempt mengacu pada Exam dan ExamParticipant; ExamAttemptAnswer mengacu pada exam_attempt_id dan Exam; ExamAttemptScore, ExamAttemptScoreCurrent, dan ExamQuestionScore mengacu pada Attempt/Exam.
- Tidak ada FK Attempt/Answer/Score yang bergantung pada ExamSessionRoom pada model yang diperiksa. Rekonsiliasi room tidak boleh mengubah relasi tersebut.
- Jumlah Attempt, Answer, Score, current-score pointer harus sama sebelum/sesudah rekonsiliasi. Jangan melakukan update/delete pada tabel-tabel ini.

8. Test lama yang bertentangan dengan keputusan baru
- ExamRoomSessionManagementTest::test_participant_cannot_be_placed_twice_and_reassignment_preserves_history membuat Session 21 dan Session 22 untuk Exam yang sama. Test harus ditulis ulang menggunakan satu session dan dua room di bawah session tersebut.
- MonitoringDuplicationTest::test_proctor_monitoring_is_limited_to_participants_in_the_assigned_room_session membuat session kedua untuk Exam yang sama. Test harus memakai satu session dengan dua room dan dua assignment Proktor terpisah.
- Helper createSession pada test management masih menerima session number dan start offset, dan beberapa test menggunakan jadwal session berbeda dari Exam. Semua harus diselaraskan dengan jadwal Exam.
- Test concurrency yang membuat session untuk Exam berbeda demi menguji overlap ruang masih valid. Test Proktor untuk Exam berbeda juga masih valid.
- Test reschedule independen dan cancelSession Admin harus diganti dengan penolakan jadwal independen serta lifecycle penutupan Exam yang aman.

### A4. Perbedaan dari audit sebelumnya
- Konfirmasi baru: ExamController::update() memiliki race terhadap lock/publish karena update tidak mengunci/re-authorize Exam dalam transaksi.
- Konfirmasi baru: Admin cancelSession() tidak menutup Exam, sementara admission Attempt tidak membaca session; peserta masih bisa start.
- Konfirmasi baru: ExamController::close() tidak menyelaraskan session/assignment.
- Test lama secara eksplisit mengandalkan beberapa session dalam satu Exam; dua suite harus diubah, bukan hanya menambah test baru.
- Branch tidak berubah sejak checkpoint sebelumnya; temuan berasal dari pembacaan source yang lebih mendalam.

## B. DESAIN FINAL

### B1. Penanda operasional dan status
Rekomendasi: gunakan is_operational BOOLEAN/TINYINT/INTEGER NOT NULL DEFAULT 0 pada exam_sessions. Nilai 1 menandai canonical session untuk Exam; 0 mempertahankan session lama sebagai histori non-operasional.

is_operational adalah identitas canonical, bukan status lifecycle:
- 1 tetap 1 saat status SCHEDULED -> ACTIVE -> COMPLETED.
- 1 tetap 1 bila ditutup/dibatalkan melalui lifecycle Exam dan status menjadi COMPLETED/CANCELLED.
- Tidak boleh mengubah 1 kembali menjadi 0 untuk membuat session pengganti.
- Session lama yang bukan canonical tetap 0; row, ID, FK, status historis, timestamps, room, mapping, dan assignment historis dipertahankan.
- Tidak ada session kedua operational untuk tenant+Exam.
- Exam yang ditutup tidak dapat dibuka kembali dengan membuat session baru; pelaksanaan ulang dibuat sebagai Exam baru.

### B2. Unique enforcement konkret lintas MySQL 8+ dan SQLite
Jangan memakai UNIQUE(tenant_id, exam_id, is_operational) secara langsung: nilai 0 akan membatasi histori menjadi satu row saja.

MySQL 8+ / InnoDB:
- Tambahkan is_operational TINYINT(1) NOT NULL DEFAULT 0.
- Tambahkan generated virtual guard:
  operational_guard TINYINT GENERATED ALWAYS AS (CASE WHEN is_operational = 1 THEN 1 ELSE NULL END) VIRTUAL
- Buat:
  CREATE UNIQUE INDEX exam_sessions_one_operational_per_exam_unique ON exam_sessions (tenant_id, exam_id, operational_guard);
- InnoDB mendukung secondary UNIQUE index pada virtual generated column. UNIQUE mengizinkan banyak NULL; hanya row is_operational=1 menghasilkan guard=1 sehingga satu tenant+Exam hanya dapat memiliki satu row operational.

SQLite test runtime:
- Tambahkan is_operational INTEGER NOT NULL DEFAULT 0.
- Buat:
  CREATE UNIQUE INDEX exam_sessions_one_operational_per_exam_unique ON exam_sessions (tenant_id, exam_id) WHERE is_operational = 1;
- Ini adalah partial unique index; row dengan is_operational=0 tidak masuk index, sedangkan row bernilai 1 harus unik.
- SQLite partial indexes didukung sejak 3.8.0. Jika memilih generated-column fallback, generated columns baru didukung sejak SQLite 3.31.0. CI wajib mencatat SELECT sqlite_version() dan menjalankan test migrasi/index pada versi aktual.

Referensi resmi:
- MySQL generated columns: https://dev.mysql.com/doc/refman/8.0/en/create-table-generated-columns.html
- MySQL InnoDB unique index on virtual generated column: https://dev.mysql.com/doc/refman/8.0/en/create-table-secondary-indexes.html
- MySQL UNIQUE permits multiple NULL: https://dev.mysql.com/doc/refman/8.0/en/create-index.html
- SQLite partial unique index: https://www.sqlite.org/partialindex.html

### B3. Transaction, concurrency, idempotensi, dan lock order
Lock order yang disarankan untuk seluruh lifecycle:
1. Exam row (tenant-scoped, lockForUpdate).
2. Physical ExamRoom rows, sorted by primary key, bila operasi menyentuh ruang.
3. ExamSession row.
4. ExamSessionRoom rows, sorted by ID.
5. Rombel rows, sorted by ID.
6. ExamParticipant rows, sorted by ID.
7. ExamParticipantRoomAssignment / ExamSessionRoomRombel rows, sorted by ID.
8. Proctor assignment rows, sorted by ID.
9. Guru/User rows sesuai operasi assignment.

Scheduler harus mengubah lock order saat ini dari Session-only menjadi Exam -> Session. Semua service yang menyentuh satu Exam harus mengunci Exam lebih dahulu. Baca snapshot ID sebelum transaction boleh dilakukan hanya sebagai petunjuk; semua scope wajib di-resolve ulang di dalam transaction dengan tenant/exam constraints.

Pseudo-code:
~~~php
return DB::transaction(function () use ($actor, $examId) {
    $exam = Exam::query()
        ->where('tenant_id', $actor->tenant_id)
        ->whereKey($examId)
        ->lockForUpdate()
        ->firstOrFail();

    assertSchedulablePublishedExam($exam);

    $existing = ExamSession::query()
        ->where('tenant_id', $exam->tenant_id)
        ->where('exam_id', $exam->id)
        ->where('is_operational', true)
        ->lockForUpdate()
        ->first();

    if ($existing) {
        throw Conflict('Exam sudah mempunyai ExamSession operasional.');
    }

    $session = ExamSession::create([
        'tenant_id' => $exam->tenant_id,
        'exam_id' => $exam->id,
        'name' => 'Pelaksanaan Ujian',
        'session_number' => 1,
        'starts_at' => $exam->start_at,
        'ends_at' => $exam->end_at,
        'schedule_revision' => 1,
        'status' => 'SCHEDULED',
        'is_operational' => true,
    ]);

    writeAuditInSameTransaction($session);
    return $session;
}, 3);
~~~

- Dua request MySQL mengunci Exam row yang sama; request kedua menunggu, kemudian melihat session sudah ada dan ditolak. Unique index adalah pertahanan terakhir bila ada jalur yang lupa lock atau ada writer lain.
- SQLite tidak menerapkan row-level FOR UPDATE seperti InnoDB; penulisan diserialisasi oleh locking database dan unique partial index menjadi penegak akhir.
- Duplicate-key akibat unique index ditangani sebagai konflik domain (HTTP 409/ValidationException), bukan retry membabi-buta.
- Deadlock/serialization failure dapat di-retry terbatas melalui transaction retry (misalnya 3 kali) setelah memastikan closure tidak mengirim side effect eksternal sebelum commit.
- Bila commit sukses tetapi respons hilang, request ulang tidak membuat session kedua: service menemukan existing operational session dan mengembalikan konflik yang aman. Untuk hasil idempotent 200 diperlukan idempotency key persisten; itu belum ada dalam kontrak saat ini dan tidak boleh diasumsikan.
- Audit log harus berada di transaksi yang sama agar rollback tidak meninggalkan audit sukses palsu.

### B4. Jadwal Exam dan Session
- Saat session dibuat, starts_at dan ends_at diambil langsung dari row Exam yang sudah dikunci. Parameter jadwal tidak lagi diterima dari Admin/service.
- UI Admin tidak menampilkan field jadwal atau nomor session. Jadwal Exam ditampilkan read-only.
- rescheduleSession() dipertahankan sementara sebagai compatibility guard yang selalu menolak; endpoint/UI reschedule dihapus.
- ExamController::update() harus transaction + lockForUpdate + re-authorize setelah lock untuk menutup race update-versus-publish. Jadwal hanya dapat diubah selama Exam masih DRAFT.
- Saat ini tidak ada jalur sah mengubah jadwal setelah locked. Jika kelak diperlukan, buat aksi Guru khusus di service Exam; lock Exam -> session, pastikan belum mulai dan tidak ada Attempt, update Exam dan session dalam transaksi yang sama, naikkan schedule_revision, set assignment Proktor PENDING/NEEDS_REVIEW, dan audit before/after. Admin tidak boleh mengubah jadwal.
- Tidak mengubah jadwal bila now >= start_at atau ada Attempt (termasuk completed) sampai ada kebijakan baru yang eksplisit; ini mencegah histori ujian berubah.
- Perbandingan waktu harus memakai instant server yang sama dan presisi kolom yang sama. Hindari membulatkan start/end pada UI; simpan timestamp persis dari Exam. Format lokal hanya untuk tampilan.
- Scheduler menggunakan Exam.start_at/end_at sebagai sumber kebenaran dan memverifikasi session mirror sama. Tidak ada jadwal gelombang.

### B5. Lifecycle close/cancel
- Admin tidak dapat membatalkan session operasional secara independen.
- Penutupan ujian dilakukan oleh Guru pemilik Exam melalui Exam lifecycle.
- Close harus lock Exam lalu session dalam satu transaksi. Tidak boleh membuat session baru sebagai pengganti.
- Jika Exam ditutup saat belum ada Attempt IN_PROGRESS, session dapat menjadi CANCELLED (bila ditutup sebelum start) atau COMPLETED (bila pelaksanaan telah berakhir); assignment capability ditutup dan placement historis dipertahankan.
- Jika masih ada Attempt IN_PROGRESS, jangan melepaskan scope monitoring/assignment yang diperlukan untuk pemulihan attempt. Exam CLOSED mencegah Attempt baru; Attempt yang sedang berjalan tetap dapat resume sesuai policy. Session tetap ACTIVE sampai attempt aman selesai, lalu scheduler menyelesaikan session dan assignment.
- Aturan final apakah close dini harus menolak saat Attempt IN_PROGRESS atau menunggu penyelesaian harus diselaraskan dengan kebijakan Force Submit Agent 2. Jangan menutup monitoring secara prematur.
- cancelSession() Admin menjadi compatibility guard yang menolak pembatalan wadah operasional; jika dibutuhkan, Admin hanya mengelola perubahan ruang/placement sebelum ujian dimulai, tanpa mengubah Exam window.

### B6. Room, placement, Proktor
- Satu session operasional mempunyai banyak ExamSessionRoom; tidak ada sesi lain dalam Exam.
- Setiap room dapat memiliki rombel/peserta dan assignment Proktor berbeda.
- Assignment Proktor tetap exam_id + exam_session_room_id; Proktor role GURU tunggal dan profil aktif; assignment bersifat temporer, bukan portal/role permanen.
- Masa tugas Proktor boleh lebih pendek daripada Exam, tetapi harus berada dalam Exam window dan tidak mempengaruhi start/end peserta.
- confirmedNow() dan ExamMonitoringScope harus memastikan session is_operational=1, status session SCHEDULED/ACTIVE, revision cocok, assignment CONFIRMED, waktu assignment aktif, room ACTIVE, tenant/exam scope cocok.
- Proktor hanya melihat participant/attempt/event pada room yang ditugaskan. Exam author dan Kamad mengikuti policy masing-masing; Admin Madrasah tetap tidak mendapat akses monitoring CBT.
- Perubahan room/placement menaikkan schedule_revision dan assignment yang terdampak menjadi NEEDS_REVIEW. Tidak ada implicit expansion of scope.
- Pemindahan peserta sebelum ujian: release assignment/mapping lama, buat placement baru pada room lain dalam session yang sama, pertahankan row lama. Saat Exam ACTIVE atau Attempt peserta IN_PROGRESS, pemindahan ditolak kecuali ada workflow takeover/force yang diotorisasi dan diaudit oleh pemilik tahap terkait.

### B7. Rekonsiliasi data lama
- Jangan memilih canonical otomatis tanpa manifest.
- Preflight read-only menghasilkan per Exam: session count/status/window, room count, mapping aktif/historis, placement aktif/historis, Proktor assignment per status, Attempt IN_PROGRESS, answer count, score/version/current pointer count, tenant/FK anomalies.
- Canonical: jika satu session, pertahankan ID itu. Jika beberapa, buat keputusan per Exam; prioritas pertimbangan: session dengan Attempt IN_PROGRESS (cutover ditunda), placement ACTIVE, mapping ACTIVE, room ACTIVE, assignment Proktor aktif/confirmed, lifecycle, created_at/ID sebagai tie-breaker saja. Semua keputusan ditinjau manusia.
- Jika lebih dari satu session memiliki Attempt IN_PROGRESS, jangan konsolidasi sampai semua attempt selesai. Bekukan Exam dari perubahan room/session dan jadwalkan cutover setelah Attempt selesai; jangan menghilangkan monitoring berjalan.
- Session canonical mempertahankan ID. Session lama lainnya is_operational=0, tetap tersimpan. Jangan hard-delete session/session-room/mapping/placement/assignment/audit.
- Untuk room yang belum ada di canonical, buat session-room baru di canonical. Bila row canonical untuk physical room sudah ada, gunakan row itu setelah meninjau status/histori. Jangan mengubah FK placement/proctor lama secara in-place.
- Mapping/placement aktif pada session noncanonical: release row lama (reason RECONCILIATION) dan buat row aktif baru di canonical. Assignment Proktor lama dicabut atau NEEDS_REVIEW; buat assignment baru di canonical room dan minta konfirmasi ulang. Tidak ada capability diwariskan.
- Session noncanonical dikeluarkan dari scheduler, policy, assignment service, dan monitoring melalui filter is_operational=1; status historis tidak harus dipalsukan. Session lama tetap dapat ditelusuri dalam audit/history read-only.
- Attempt, Answer, Score, score-current pointer dan question score tidak disentuh. Hitung hash/count sebelum-sesudah; jika tidak sama, rollback.
- Rollback per Exam harus memakai transaction dan reconciliation journal before/after. Bila satu batch gagal sebelum commit, transaction rollback. Jika sudah commit, reverse hanya dengan rencana kompensasi yang mengembalikan status/placement dan FK dari journal; jangan menghapus history baru atau menulis ulang Attempt/Answer/Score.
- Migration/index tidak boleh dijalankan sebelum semua duplikasi yang berkonflik dengan unique index dipetakan dan data aktif aman.

### B8. File yang akan berubah bila kelak disetujui
- Migration additive baru: kolom operational flag/guard/index, tanpa drop tabel/FK/histori.
- ExamSession model.
- ExamRoomSessionService.
- ExamSessionLifecycleService.
- ExamProctorAssignmentService.
- ExamProctorAssignment model dan ExamMonitoringScope.
- ExamRoomSessionManagement Livewire dan Blade view.
- ExamController update/close transaction flow dan kemungkinan Exam schedule service.
- ExamPolicy jika aturan close perlu diperketat.
- Tests: ExamRoomSessionManagementTest, ExamRoomSessionConcurrencyTest, MonitoringDuplicationTest, ProctorAssignmentSecurityTest, Exam lifecycle/update race tests.
- Dokumen preflight, manifest, laporan, checkpoint.

## C. IMPLEMENTASI
- Source aplikasi: NOT RUN.
- Migration/schema: NOT RUN.
- Database: NOT RUN.
- Rekonsiliasi: NOT RUN.
- Agent 2/3: NOT RUN — tidak ada branch/file mereka diubah.
- Tidak ada backup source baru karena tidak ada source diubah.

## D. PENGUJIAN
- CI lama pada SHA f1d01ea6fa4b9a100d00680a1b70c2a8d92b9af5: PASS; run 44984303003; 24 test/120 assertions pada MySQL 8.0.46 disposable.
- Test desain baru: NOT RUN.
- Preflight DB: NOT RUN — tidak ada dump/disposable DB yang dapat diakses.
- Working tree lokal: NOT VERIFIED.
- Produksi: SKIPPED / tidak disentuh.

## E. BACKUP
- Manifest Stage 4B: docs/backup/stage4b-room-session-2026-11-22/MANIFEST.md.
- Manifest tambahan Stage 4C: docs/backup/stage4c-teacher-subject-assignment-2026-11-22/MANIFEST_SUPPLEMENT.md.
- ZIP baseline tidak ditemukan pada daftar file Project/Conversation yang tersedia di runtime ini; tidak disentuh. Tidak ada klaim ZIP baseline sudah diperiksa pada sesi audit ini.
- Tidak ada source backup baru.

## F. RISIKO DAN KEPUTUSAN YANG MASIH DIPERLUKAN
1. Persetujuan terhadap penanda is_operational + strategi index MySQL generated-column/SQLite partial unique.
2. Kebijakan penutupan Exam saat Attempt IN_PROGRESS: apakah close ditolak sampai Attempt selesai atau close mencegah Attempt baru tetapi mengizinkan resume hingga scheduler menyelesaikan session. Rekomendasi aman: tetap izinkan resume Attempt aktif dan jangan cabut monitoring sampai selesai; sinkronkan dengan Force Submit Agent 2.
3. Kebijakan canonical untuk Exam duplikat yang mempunyai Attempt aktif di lebih dari satu session: tunda seluruh cutover sampai semua selesai; jangan konsolidasi live.
4. Versi SQLite minimum pada CI harus diverifikasi dengan SELECT sqlite_version(); partial unique index mensyaratkan SQLite >=3.8.0.
5. Deployment migration harus memakai maintenance gate pada endpoint room/session/proctor + scheduler sampai migration dan semua node aplikasi baru aktif; jangan ada old writer yang membuat session tanpa is_operational=1.
6. Working tree lokal dan database dump tidak tersedia untuk pemeriksaan langsung; perlu environment/backup disposable yang terverifikasi sebelum preflight dijalankan.
7. Race update-vs-publish harus diperbaiki dan diuji sebelum invariant jadwal dianggap aman.

## G. CHECKPOINT DAN HANDOVER
- Branch: work/stage4b-room-session-20261122.
- HEAD remote: a1d5063efa3cb0cabcda7af1b54381c7386567e7.
- Perbandingan HEAD remote terhadap checkpoint terakhir: identical.
- Source CI SHA: f1d01ea6fa4b9a100d00680a1b70c2a8d92b9af5.
- Tidak ada source/migration/schema/database berubah.
- Agent 1 tetap Stage 4B; Agent 2 tetap Stage 4D; Agent 3 tetap Web Guru.
- Berhenti pada gate persetujuan. Tidak ada coding/migration sampai persetujuan eksplisit.
