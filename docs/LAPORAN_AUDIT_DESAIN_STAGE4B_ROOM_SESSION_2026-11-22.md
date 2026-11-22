# LAPORAN AUDIT DAN DESAIN LANJUTAN — STAGE 4B

Tanggal pemeriksaan: 22 November 2026, 16:28 WIB  
Repository: zane0184/cbt_november  
Branch kerja Agent 1: work/stage4b-room-session-20261122  
Checkpoint awal: 84fdae954e195bc07b8fa0642eeb1703f77ebdf6  
HEAD setelah backup dokumentasi: 6fb24dbe8d2de48f98c5ca9668286b2c20452c75  
Issue koordinasi: https://github.com/zane0184/cbt_november/issues/10

Status: AUDIT/DESIGN CONTINUATION COMPLETE — IMPLEMENTATION GATED

## 1. Batas dan metode

Audit dilakukan READ-ONLY terhadap source aplikasi, migration, model, controller, policy, service, branch, Issue #10, CI, dan dokumen baseline. Tidak dilakukan merge, rebase, cherry-pick, PR, migration, perubahan schema, mutasi database, deployment, atau akses VPS.

Dokumentasi koordinasi pada branch Agent 1 diperbarui setelah backup byte-exact. Tidak ada source aplikasi yang diubah.

## 2. AUDIT

### 2.1 HEAD remote dan graph branch

HEAD remote yang diperiksa:

| Pemilik | Branch | HEAD | Status ownership |
|---|---|---|---|
| Agent 1 | work/stage4b-room-session-20261122 | 6fb24dbe8d2de48f98c5ca9668286b2c20452c75 | Owner Stage 4B |
| Agent 2 | work/stage4d-account-lifecycle-20261122 | 74bf7178f46673e9be90523671490c0749034acd | Branch Agent 2 |
| Agent 3 | work/web-guru-foundation-20261122 | a955a439b1b3fa4c149f0153f283951d2bfb88d2 | Branch Agent 3 |
| Kandidat Agent 4 | work/import-guru-siswa-implementation-20261122 | f38ceec7b0653fcf3363eec40f3d4577c323cca8 | Belum dikonfirmasi sebagai owner |
| Main | main | feab77228f7108f7f7a45325d7f1fb878c07310b | Bukan basis integrasi |

Perbandingan relatif terhadap HEAD Agent 1 saat ini:

| Branch | Merge-base | Ahead | Behind | Graph |
|---|---|---:|---:|---|
| Agent 2 | 0631a3d84d058c8c049dcb71dc54b2e7cbb849a6 | 224 | 73 | Diverged |
| Agent 3 | 81b6c74d81d80bc483530802594f624fb4e0f3db | 164 | 218 | Diverged |
| Kandidat Agent 4 | 17c20c66284deb7a18e3a6dde2b3d47f9c966bd9 | 80 | 341 | Diverged |
| Main | 95736e338e517a087503adfb3e3a899709e0340c | 15 | 1191 | Diverged |

Tidak ditemukan PR terbuka dari keempat branch tersebut. Tidak ada merge/rebase yang dicoba, sehingga konflik merge aktual berstatus NOT TESTED. File bersama dan perbedaan blob di bawah adalah hotspot integrasi, bukan klaim bahwa Git merge sudah dijalankan.

Perubahan commit terbaru yang diverifikasi:
- Agent 1: commit 84a3d3bf menambah manifest backup; dua commit sesudahnya hanya menambah backup byte-exact kontrak dan checkpoint.
- Agent 2: HEAD 74bf7178 mengubah AdminCbtBoundaryTest.php; tidak mengubah file inti Stage 4B pada commit terakhir.
- Agent 3: HEAD a955a439 hanya menambah manifest backup.
- Kandidat Agent 4: HEAD f38ceec7 mengubah ExamAttemptController.php. Pemilik branch tetap belum terkonfirmasi.

### 2.2 Matriks blob SHA file inti

SHA berikut dibaca dari blob pada branch aktual, bukan disimpulkan dari nama file.

| File | Agent 1 | Agent 2 | Agent 3 | Kandidat Agent 4 |
|---|---|---|---|---|
| ExamAttemptController.php | 13e8981cca4653ff4e9b9448a50e4d0ec8a74bae | 13e8981cca4653ff4e9b9448a50e4d0ec8a74bae | e915bf43118026ef8eace96338fa6cad44ee29e1 | 808702092c94b386657fe027f60758a625d5bb93 |
| ExamAnswerController.php | b4ddec14f6208b3ecde9bfc3e45168a2159e2a9c | b4ddec14f6208b3ecde9bfc3e45168a2159e2a9c | Belum diverifikasi pada snapshot ini | b4ddec14f6208b3ecde9bfc3e45168a2159e2a9c |
| ExamPolicy.php | c05d96ef932fdc81b67336240af4e1726864c3d6 | c05d96ef932fdc81b67336240af4e1726864c3d6 | 839df41c82a5b45706072d60431706ca5861cc6 | 121fa8871dd958b25709cae567adc1d4e6c549b3 |
| ExamMonitoringScope.php | 11b455954f974d1c60ed5c49700368f8d37c09c1 | 11b455954f974d1c60ed5c49700368f8d37c09c1 | 11b455954f974d1c60ed5c49700368f8d37c09c1 | NOT FOUND |
| ExamSessionLifecycleService.php | 8e34152afd817f30cc2f81b5f25fc450571463ac | 8e34152afd817f30cc2f81b5f25fc450571463ac | NOT FOUND | NOT FOUND |
| ExamRoomSessionService.php | e514fa9f751584e54af1125d48b5673e013f2339 | e514fa9f751584e54af1125d48b5673e013f2339 | Berbeda pada snapshot matriks sebelumnya | NOT FOUND |
| ExamProctorAssignmentService.php | c968ce68c0decace1c35a0ccb3b2b4449c057656 | c968ce68c0decace1c35a0ccb3b2b4449c057656 | c968ce68c0decace1c35a0ccb3b2b4449c057656 | Berbeda pada snapshot matriks sebelumnya |
| Migration Stage 4B | 9551b1dd79397bd0432f03eefc970852b675fd25 | Tidak dimiliki Agent 2 | Tidak dimiliki Agent 3 | Tidak dimiliki kandidat |

Implikasi ownership:
- Migration, session lifecycle, room/placement dan Proktor Stage 4B tetap milik Agent 1.
- Attempt, Answer, Policy, scoring dan lifecycle akun memiliki perubahan antarbranch; jangan menyalin model Agent 1 ke Agent 2 atau sebaliknya.
- Candidate Agent 4 tidak boleh diperlakukan sebagai branch resmi tanpa konfirmasi pemiliknya.

### 2.3 Temuan invariant session

Migration aktual:
backend-laravel/database/migrations/2026_11_22_110001_create_exam_room_session_scope.php

Blob SHA: 9551b1dd79397bd0432f03eefc970852b675fd25

Temuan:
1. Unique key saat ini adalah (tenant_id, exam_id, session_number).
2. session_number berbeda masih memungkinkan lebih dari satu session untuk Exam yang sama.
3. Tidak ada is_operational atau operational_guard.
4. Constraint yang ada menjamin keunikan nomor session, bukan satu session operasional per Exam.

Karena itu invariant satu Exam–satu ExamSession operasional belum ditegakkan database.

### 2.4 Temuan lifecycle scheduler

ExamSessionLifecycleService::synchronize() pada blob 8e34152afd817f30cc2f81b5f25fc450571463ac:
- Kandidat dipilih dari starts_at/ends_at milik ExamSession.
- Transaksi mengunci ExamSession terlebih dahulu, tanpa mengunci Exam.
- Session diubah COMPLETED saat ends_at tercapai.
- Assignment Proktor diubah COMPLETED jika session selesai atau EXPIRED jika expires_at assignment tercapai.
- Tidak ditemukan pemeriksaan Attempt IN_PROGRESS atau kebutuhan recovery sebelum status session/assignment diubah.
- Service tidak membaca atau mengubah Attempt/Answer.

Dampak: waktu session dapat mencabut capability monitoring sebelum lifecycle Attempt/recovery selesai. Ini makin berisiko karena session masih dapat dijadwalkan ulang terpisah dari Exam.

### 2.5 Cancel dan reschedule session

ExamRoomSessionService:
- cancelSession mengunci Exam, room, lalu session; memeriksa Attempt aktif pada placement; kemudian dapat membatalkan session SCHEDULED, melepas placement peserta, dan mencabut assignment Proktor.
- Pembatalan tersebut tidak menutup Exam.
- Karena session bukan admission gate, Exam PUBLISHED masih dapat menerima Attempt selama Exam schedule dan token mengizinkan. Hasilnya dapat berupa Attempt baru tanpa placement/monitoring yang semestinya.
- rescheduleSession masih mengubah jadwal session secara independen, padahal jadwal resmi berada pada Exam.

Keputusan desain: reschedule hanya pada Exam; starts_at/ends_at session adalah mirror. cancelSession harus menjadi compatibility guard yang menolak operasi independen, atau hanya tersedia sebagai bagian transaksi penutupan Exam yang sah. Tidak boleh ada Admin action yang meninggalkan Exam terbuka tetapi mencabut capability ruang/monitoring.

### 2.6 Lifecycle Attempt Agent 2

Source Agent 2 yang diverifikasi:
- ExamAttemptController::store mengunci Exam → ExamParticipant → ExamAttempt dan melakukan re-authorize setelah Exam terkunci.
- Submit memakai urutan Exam → Participant → Attempt.
- ExamPolicy::startAttempt mengizinkan resume Attempt IN_PROGRESS yang belum kedaluwarsa selama peserta masih eligible, meskipun Exam sudah ditutup.
- Attempt baru tetap bergantung pada status/jadwal Exam, token, eligibility, dan waktu server.

Kebijakan resume tersebut harus dipertahankan. Close Exam menolak Attempt baru tetapi tidak menghapus, memindahkan, atau membatalkan Attempt aktif yang masih sah.

### 2.7 Answer lock ordering dan operasi Exam

ExamAnswerController::update:
- Transaksi mengunci Attempt terlebih dahulu, lalu Answer.
- Tidak mengunci Exam dan Participant lebih dahulu.
- Validasi awal menggunakan model route-bound sebelum transaksi; validasi di dalam transaksi belum mengikuti urutan global.

Ini belum sesuai dengan urutan Attempt-only yang disepakati sebagai target: Exam → Participant → Attempt → Answer/Score/Lease.

ExamController:
- close mengunci Exam, re-authorize pada row terkunci, mengubah status CLOSED dan mencatat audit.
- archive mengubah status langsung tanpa transaksi + Exam lock + re-authorize di dalam transaksi.
- publishResults mengubah show_result tanpa transaksi + Exam lock + re-authorize di dalam transaksi.
- destroy menghitung Attempt tanpa Exam row lock; dapat berlomba dengan start Attempt. Pemeriksaan juga belum mencakup seluruh histori session/room/placement/assignment.

archive, publishResults dan destroy memerlukan transaksi, Exam row lock, re-authorize setelah lock, dan audit before-after. Destroy harus fail-closed bila ada histori yang direferensikan; gunakan archive sebagai jalur retensi, bukan menghapus histori.

### 2.8 Monitoring Proktor

Agent 1:
- ExamAttemptController::show menerima ExamMonitoringScope.
- Setelah policy monitor Exam-level, controller memanggil allowsParticipant untuk memastikan peserta berada pada room/session yang ditugaskan.
- Blob controller 13e8981cca4653ff4e9b9448a50e4d0ec8a74bae.

Agent 3:
- ExamAttemptController::show hanya memeriksa attempt/exam/tenant dan authorize('monitor', exam).
- Tidak memanggil ExamMonitoringScope::allowsParticipant().
- Blob e915bf43118026ef8eace96338fa6cad44ee29e1.

Kandidat Agent 4:
- ExamAttemptController::show tidak menunjukkan pemanggilan room scope.
- ExamMonitoringScope.php tidak ditemukan pada branch kandidat.
- Policy monitor kandidat tidak menggantikan filter room per peserta.

Risiko: Proktor dengan assignment pada satu ruang dapat lolos otorisasi Exam-level dan membaca Attempt di ruang lain apabila controller tanpa room scope digunakan.

Kriteria:
- Proktor A hanya dapat membaca Attempt, answer, media, dan event peserta pada ruang A.
- Proktor B hanya pada ruang B.
- Guru pemilik Exam mengikuti policy author dan mendapat scope yang sah.
- Admin yang mengelola assignment tidak otomatis memperoleh monitoring atau isi jawaban.
- Assignment revoked/expired tidak memberi capability.
- Semua endpoint monitoring, answer, media dan event wajib menggunakan scope yang sama; audit semua endpoint tersebut masih harus diselesaikan.

### 2.9 Relasi histori akun nonaktif

Perubahan Agent 2 yang harus dipertahankan:
- ExamParticipant.userForHistory() memakai withTrashed().
- ExamProctorAssignment.proctorForHistory(), assigner(), dan revoker() memakai withTrashed().
- ExamParticipantRoomAssignment mempertahankan actor assigner/releaser untuk histori dengan withTrashed().
- ExamAttemptScore.student() memakai withTrashed().
- ExamQuestionScore.student() memakai withTrashed().

Pemisahan:
- Relasi operasional tetap memakai relasi normal dan tidak boleh mengembalikan akun soft-deleted sebagai aktor aktif.
- Relasi histori boleh memakai withTrashed() agar identitas lama tetap terbaca.
- ExamAttemptScore memiliki finalized_by dan ExamQuestionScore memiliki grader_id, tetapi model yang diperiksa belum menyediakan relasi actor histori yang setara dengan withTrashed(). Agent 2 perlu memutuskan relasi finalizer/grader historis dan acceptance test-nya.

### 2.10 Lock order aktual

Kondisi aktual:
- createSession: Exam.
- attachRoom: Exam → Physical Room → ExamSession.
- mapRombel/releaseRombelMapping: Exam → Room → SessionRoom → Session → Rombel/Participant/Placement.
- ExamProctorAssignmentService::lockScope: Exam → Room → SessionRoom → Session.
- assign/revoke Proktor mengunci User sebelum Assignment.
- Scheduler: Session tanpa Exam lock.
- start/resume/submit Attempt: Exam → Participant → Attempt.
- save Answer: Attempt → Answer.

Perbedaan urutan ini harus diselesaikan sebelum integrasi.

## 3. DESAIN

### 3.1 Invariant produk

- Satu Exam.
- Tepat satu ExamSession operasional.
- Banyak ExamSessionRoom dalam session yang sama.
- Banyak placement peserta per room.
- Banyak Proktor temporer per room.
- Satu jadwal resmi: Exam.start_at dan Exam.end_at berdasarkan waktu server.
- Session adalah wadah operasional, bukan admission gate dan bukan pemilik jadwal.
- Identitas session operasional tidak diganti untuk pelaksanaan ulang.
- Tidak ada gelombang/session tambahan.

### 3.2 Operational session guard — perbandingan database

| Aspek | MySQL 8 | SQLite |
|---|---|---|
| Guard yang diusulkan | Generated virtual column operational_guard = CASE WHEN is_operational = 1 THEN 1 ELSE NULL END | Partial unique index pada tenant_id, exam_id WHERE is_operational = 1 |
| Unique | UNIQUE(tenant_id, exam_id, operational_guard) | UNIQUE(tenant_id, exam_id) WHERE is_operational = 1 |
| Perilaku NULL | Unique index mengizinkan banyak NULL, tetapi hanya satu nilai guard 1 | Baris non-operasional dikecualikan dari partial index |
| Candidate key FK | Pertahankan UNIQUE(id, tenant_id, exam_id); jangan gunakan generated column sebagai target FK | Pertahankan candidate key yang sama |
| Kompatibilitas | Harus diuji pada MySQL 8.0.x/InnoDB target | Harus mencatat SELECT sqlite_version() pada runtime test yang sebenarnya |
| Status | DESAIN, belum dibuat/diuji | DESAIN, belum dibuat/diuji |

Unique guard menjamin paling banyak satu session operasional. Syarat paling sedikit satu session harus dipastikan oleh service transaksi saat Exam dibuat/diterbitkan dan oleh preflight; unique index saja tidak menjamin keberadaan baris.

is_operational harus NOT NULL. Session historis diberi status non-operasional hanya setelah rekonsiliasi eksplisit. session_number dapat dipertahankan sebagai atribut histori, tetapi tidak boleh menjadi cara membuat pelaksanaan kedua.

### 3.3 Legacy reconciliation dan idempotensi

1. Exam tanpa session: preflight melaporkan; canonical session dibuat dalam transaksi yang mengunci Exam dan menggunakan jadwal Exam sebagai mirror.
2. Exam dengan satu session: validasi tenant, exam_id, jadwal mirror, room, placement, assignment, dan histori sebelum menetapkannya sebagai canonical.
3. Exam dengan lebih dari satu session: jangan memilih otomatis jika ada Attempt, Answer, Score, placement, assignment, audit, atau referensi historis yang membuat pilihan ambigu.
4. Semua session lama dipertahankan. Session non-canonical tidak dihapus; setelah keputusan manual, tandai non-operasional dan pastikan tidak memiliki capability.
5. Dua transaksi create: keduanya mengunci Exam terlebih dahulu; transaksi kedua membaca canonical yang sudah dibuat. Unique guard menjadi perlindungan database terakhir jika ada writer yang melewati service.
6. Retry setelah duplicate key hanya boleh membaca ulang jika tepat satu canonical ditemukan; selain itu fail-closed.
7. Gagal di tengah transaksi harus rollback seluruh pembuatan session dan relasi baru.
8. Rollback migration harus menolak jika session/room/placement/assignment/histori sudah ada. Gunakan forward-only recovery, bukan penghapusan histori.

### 3.4 Lifecycle scheduler dan monitoring recovery

- Jadwal Exam adalah acuan; session starts_at/ends_at hanya mirror.
- Scheduler wajib mengambil kandidat berdasarkan Exam, lalu mengunci Exam sebelum room/session/placement/assignment/Attempt.
- Session tidak menjadi admission gate.
- Session hanya COMPLETED secara administratif setelah Exam berakhir/ditutup dan tidak ada Attempt yang masih membutuhkan operasi atau recovery yang sah.
- Attempt IN_PROGRESS tidak boleh dihapus atau dipindah. Attempt aktif yang belum expired tetap dapat resume jika eligibility dan policy sah.
- Attempt expired harus melalui lifecycle expiry/submit yang tercatat; scheduler session tidak boleh mengubah assignment sebelum memeriksa hasilnya.

Transisi assignment yang diusulkan:

| Status | Kondisi |
|---|---|
| CONFIRMED | Penugasan aktif, schedule_revision cocok, waktu penugasan berlaku, Guru masih aktif/eligible |
| NEEDS_REVIEW | Jadwal/scope berubah atau waktu assignment berakhir sementara Attempt masih membutuhkan recovery; capability dibatasi dan dibatasi waktu |
| EXPIRED | Masa tugas/recovery selesai dan tidak ada Attempt yang memerlukan monitoring |
| COMPLETED | Exam/session selesai secara administratif dan semua Attempt terkait terminal |
| REVOKED | Pencabutan eksplisit oleh actor berwenang dengan reason, waktu, dan before-after |

NEEDS_REVIEW tidak boleh menjadi akses permanen. Desain memerlukan recovery_until yang eksplisit atau kebijakan waktu recovery yang disetujui. Sampai deadline, capability recovery dibatasi pada room yang sama. Setelah deadline, capability otomatis berakhir; jika Attempt belum terminal, harus ada keputusan actor berwenang untuk reassign, extend dengan audit, atau Force Submit. Nilai grace period belum ditetapkan dan tidak boleh ditebak.

Revoke Proktor saat Attempt aktif harus ditolak kecuali coverage dialihkan ke Proktor pengganti atau Attempt diterminalkan melalui Force Submit yang sah. Admin tidak memperoleh hak monitoring hanya karena mengelola assignment.

### 3.5 Final lock order yang diusulkan

Semua row lock dalam daftar berulang harus diurutkan berdasarkan primary key. Query non-locking untuk menemukan kandidat tidak boleh dipakai sebagai pengganti re-fetch dan lock dalam transaksi.

Urutan global:
1. Exam.
2. Physical ExamRoom, urut primary key.
3. ExamSession.
4. ExamSessionRoom, urut primary key.
5. Rombel, urut primary key.
6. ExamParticipant, urut primary key.
7. Placement/mapping, urut primary key.
8. Assignment Proktor, urut primary key.
9. User/Guru, urut primary key.
10. Attempt.
11. Answer.
12. Score.
13. Device Lease/Fencing.

Matriks operasi:

| Operasi | Urutan lock target |
|---|---|
| Create session | Exam → ExamSession canonical lookup/insert |
| Attach room | Exam → Physical Room(s) → ExamSession → ExamSessionRoom(s) |
| Map rombel | Exam → Room(s) → Session → SessionRoom(s) → Rombel → Participant(s) → Placement(s) |
| Release mapping | Exam → Room(s) → Session → SessionRoom(s) → Rombel → Participant(s) → Placement(s) |
| Assign Proktor | Exam → Room(s) → Session → SessionRoom(s) → Assignment(s) → User(s) |
| Revoke Proktor | Exam → Room(s) → Session → SessionRoom(s) → Participant/Placement bila diperiksa → Assignment(s) → User(s) → Attempt bila perlu |
| Reschedule Exam | Exam → Room(s) → Session → SessionRoom(s) → Placement(s) → Assignment(s) |
| Close Exam | Exam; bila operasi menyentuh Attempt, lanjut Participant(s) → Attempt(s) |
| Scheduler | Exam → Room(s) → Session → SessionRoom(s) → Rombel/Participant(s) → Placement(s) → Assignment(s) → User(s) → Attempt(s) |
| Start Attempt | Exam → Participant → Attempt |
| Resume Attempt | Exam → Participant → Attempt |
| Save Answer | Exam → Participant → Attempt → Answer → Lease/Fencing |
| Submit | Exam → Participant → Attempt → Answer(s) → Score(s) → Lease/Fencing |
| Force Submit | Exam → Participant → Attempt → Answer(s) → Score(s) → Lease/Fencing |
| Takeover device | Exam → Room(s) → Session → SessionRoom(s) → Participant → Placement → Assignment → User → Attempt → Lease/Fencing |

Operasi yang tidak menyentuh suatu jenis row tidak perlu menguncinya. Tidak boleh mengambil lock child lebih dahulu lalu kembali ke parent. Operasi Attempt-only tidak perlu mengunci room jika tidak mengevaluasi scope Proktor.

### 3.6 Contoh interleaving deadlock yang harus dicegah

A. Scheduler versus attach/cancel:
- T1 scheduler mengunci Session lebih dahulu.
- T2 attachRoom mengunci Exam, lalu menunggu Session yang dipegang T1.
- T1 kemudian mencoba mengunci Exam dan menunggu T2.
- Terbentuk siklus Session ↔ Exam.
Pencegahan: scheduler wajib mengunci Exam sebelum Session.

B. Save Answer versus Force Submit:
- T1 save answer mengunci Attempt terlebih dahulu lalu mencoba mengunci Exam/Participant untuk re-authorize.
- T2 Force Submit mengunci Exam → Participant lalu menunggu Attempt.
- T1 menunggu Exam, T2 menunggu Attempt.
Pencegahan: save answer memulai dengan Exam → Participant → Attempt → Answer.

C. Session versus SessionRoom:
- Satu service mengunci SessionRoom sebelum Session, service lain Session sebelum SessionRoom.
- Jika salah satu jalur tidak mengambil Exam mutex lebih dahulu, kedua transaksi dapat membentuk siklus.
Pencegahan: samakan urutan Session sebelum SessionRoom setelah Exam dan physical room.

D. Assignment versus User:
- Satu jalur mengunci User sebelum Assignment; jalur lain dapat mengunci Assignment lalu memeriksa User.
- Transaksi pertama menunggu Assignment dan transaksi kedua menunggu User.
Pencegahan: jika keduanya perlu row lock, Assignment harus dikunci sebelum User.

### 3.7 Archive, publishResults, destroy

- archive: Exam lock + re-authorize; jangan menghapus session/room/placement/Attempt/Answer/Score/audit. Jika Attempt masih membutuhkan recovery, pertahankan jalur recovery yang sah.
- publishResults: Exam lock + re-authorize + audit before-after; tidak mengubah session atau assignment.
- destroy: Exam lock + re-authorize; fail-closed bila ada Attempt atau histori session/room/placement/assignment/audit yang harus dipertahankan. Gunakan archive jika histori sudah ada.
- Close Exam: menolak Attempt baru; tidak mencabut Attempt aktif atau monitoring yang masih sah.

### 3.8 Monitoring acceptance

- Proktor A ditugaskan hanya pada ruang A; Proktor B hanya ruang B.
- A tidak dapat membaca Attempt, answer, media, event, atau detail peserta B.
- Guru pemilik Exam mendapat scope sesuai policy author.
- Admin assignment tidak otomatis dapat memonitor.
- Assignment revoked/expired tidak memberi capability.
- Schedule revision mismatch mengharuskan konfirmasi ulang.
- Test harus mencakup seluruh endpoint monitoring, bukan hanya show Attempt.

## 4. IMPLEMENTASI

Perubahan aktual pada sesi ini:
- Backup byte-exact kontrak dan checkpoint dibuat di docs/backup/stage4b-room-session-2026-11-22/.
- Laporan audit/desain dan pembaruan kontrak/checkpoint dibuat sebagai dokumentasi koordinasi.
- Tidak ada source aplikasi, test, migration, schema, database, workflow, konfigurasi, branch agent lain, atau ZIP baseline yang diubah.
- Tidak ada migration atau database yang dijalankan.
- Tidak ada merge/rebase/cherry-pick/PR/deployment.

## 5. PENGUJIAN DAN BUKTI

### 5.1 CI Agent 1

Run 44984303003 — SUCCESS pada source SHA f1d01ea6fa4b9a100d00680a1b70c2a8d92b9af5. MySQL 8.0.46 dan InnoDB diverifikasi.

Log menunjukkan:
- ExamRoomSessionManagementTest: 12 test / 49 assertions PASS.
- ExamRoomSessionConcurrencyTest: 2 test / 22 assertions PASS.
- MonitoringDuplicationTest: 15 test / 151 assertions PASS.
- ProctorAssignmentSecurityTest: 8 test / 34 assertions PASS.
- Stage 4B child-first rollback: PASS, FK checks tetap aktif, tabel Stage 4B tidak tersisa setelah rollback.

Batas bukti: run ini menguji migration/schema Stage 4B yang sekarang, bukan operational guard satu session per Exam, bukan scheduler dengan Attempt aktif, dan bukan seluruh lock-order matrix.

### 5.2 CI Agent 2

Run 44998669927 pada source helper fac13a049b35f0c42a0e71cb5d24c07113f54238 dan run 44998949281 pada snapshot dokumentasi 808a7a917f1addbf8b8b67b4929dd1cbb6992cbd berakhir FAILURE. Log menunjukkan StudentWebLoginTest.php baris 125 menerima HTTP 500, padahal test mengharapkan 302; 10 test lulus dan 1 gagal pada run yang diperiksa. HEAD Agent 2 sekarang 74bf7178 lebih baru dan belum memiliki bukti CI PASS yang terhubung pada SHA tersebut.

### 5.3 CI Agent 3 dan kandidat Agent 4

- Agent 3 run 44995889398 SUCCESS pada SHA a33867928711b456d2414a675bef1a929861dee8. HEAD terbaru sesudahnya hanya dokumentasi. Run ini tidak membuktikan monitoring room scope pada controller yang berbeda.
- Kandidat Agent 4 run 44949239676 SUCCESS pada HEAD f38ceec7b0653fcf3363eec40f3d4577c323cca8. Branch belum dikonfirmasi sebagai owner dan bukan bukti integrasi Stage 4B lintas agent.

Tidak ada workflow run/status baru yang dikembalikan untuk exact HEAD saat pemeriksaan ini; bukti di atas adalah run terakhir yang relevan, bukan CI exact HEAD terbaru.

### 5.4 Database dan workspace

Ditemukan kembali dump uji:
- cbt_madrasah_test_backup_20261102_083757.sql
- Ukuran 122.380 byte.
- SHA-256 historis yang tercatat: f716921d657baf7096df577fa844602e5cc015364d07d30a0834c4b43471901c.
- Header: MySQL 8.0.46, database sumber cbt_madrasah_test.
- 57 tabel; 57/57 InnoDB; 39 migration tercatat.
- INSERT hanya pada cache, migrations, sessions; tidak ada tenant, user, siswa, guru, rombel, atau membership.
- Dump berasal dari 2 November 2026 dan belum memiliki tabel exam_sessions/room/placement Stage 4B.
- Dump mengandung DROP TABLE IF EXISTS; hanya boleh di-restore ke database disposable baru yang benar-benar kosong.

Dump ditemukan di Library dan juga tercatat sebagai bagian ZIP baseline. SHA/ukuran di atas berasal dari runbook dan manifest audit historis; raw bytes tidak dapat dimaterialisasi pada sesi ini, sehingga tidak dilakukan rehash baru. Dump tidak di-restore dan tidak digunakan untuk migration pada tahap ini.

ZIP reassembled read-only CBT REVISI 6 NOPEMBER 2026_REASSEMBLED_READONLY.zip ditemukan di Library. Register historis mencatat SHA ZIP asli CBT REVISI 6 NOPEMBER 2026.zip sebesar b7017b7d0ccc2a93cc6b522e8a34ccacffe28d1a7e081c68dfded83238785ad0; raw original exact-name ZIP tidak tersedia untuk rehash sesi ini. Tidak ada ZIP yang diubah.

Workspace aktif berikut tidak ditemukan:
- /mnt/data/CBT_MADRASAH_RECONSTRUCTION/
- /mnt/data/cbt_audit_work/
- /mnt/data/cbt_stage5p_work/
- /mnt/data/CBT_DUAL_INSTALLER_RECONSTRUCTION_2026-11-15/

Status database yang benar: DUMP FOUND — RESTORE/RUNTIME NOT RUN — PRODUCTION DB NOT TOUCHED. Klaim lama “dump tidak ditemukan” pada bagian 10.9 kontrak disupersede oleh temuan ini.

### 5.5 Test plan — belum dijalankan

| Area | Acceptance test | Status |
|---|---|---|
| Session guard | Dua transaksi membuat session pada Exam yang sama; tepat satu operational | NOT RUN |
| Session history | Session historis tidak mendapat capability | NOT RUN |
| Legacy reconciliation | Zero/one/multiple session; ambiguity berhenti fail-closed | NOT RUN |
| Retry | Duplicate request tidak membuat session kedua | NOT RUN |
| Close vs start | Close Exam menolak Attempt baru | NOT RUN |
| Close vs resume | Attempt aktif yang sah tetap resume | NOT RUN |
| Scheduler | Attempt IN_PROGRESS mempertahankan monitoring/recovery | NOT RUN |
| Assignment expiry | NEEDS_REVIEW/EXPIRED/COMPLETED mengikuti recovery deadline | NOT RUN |
| Force Submit | Role, room scope, reason, audit, idempotensi | NOT RUN |
| Proktor isolation | Proktor A tidak membaca ruang B | NOT RUN pada exact branch integration |
| Admin boundary | Admin assignment tidak mendapat monitoring | NOT RUN pada exact branch integration |
| Revocation | Assignment revoked tidak memberi capability | NOT RUN pada exact branch integration |
| Save vs submit | Tidak ada deadlock/lost update | NOT RUN |
| Scheduler vs room update | Tidak ada deadlock | NOT RUN |
| Takeover vs heartbeat | Tidak ada stale-generation write | NOT RUN |
| MySQL/SQLite parity | Guard dan rollback diuji pada engine/versi aktual | NOT RUN |

Tidak ada test baru yang dieksekusi oleh audit ini. CI lama tidak boleh digunakan sebagai bukti PASS untuk test plan baru.

## 6. STATUS PERSETUJUAN ISSUE #10

Issue #10 masih OPEN. Empat komentar yang tersedia berasal dari koordinator; tidak ada komentar persetujuan dari Agent 2, Agent 3, atau Agent 4.

| Owner | Status |
|---|---|
| Agent 1 | DESAIN DIAJUKAN; menunggu keputusan koordinator dan owner lain |
| Agent 2 | NEEDS DESIGN / COORDINATION |
| Agent 3 | PENDING |
| Agent 4 | PENDING — branch resmi/owner belum dikonfirmasi |
| Operational guard | NEEDS DESIGN / compatibility test |
| Lock order | NEEDS DESIGN lintas Agent 1/2/3/4 |
| Database restore/runtime | BLOCKED — dump ditemukan, restore belum dijalankan |
| Implementasi source/migration | NOT STARTED |
| Production database/VPS | NOT TOUCHED |

## 7. GATE DAN PEKERJAAN BERIKUTNYA

Sebelum coding Stage 4B:
1. Agent 2 menyetujui kontrak Attempt close/resume, Answer lock, submit/score, archive/publishResults/destroy, scheduler dan relasi histori.
2. Agent 3 menyetujui canonical session, room/placement, assignment, cancel/reschedule guard dan room monitoring.
3. Agent 4 mengonfirmasi ownership branch resmi lalu menyetujui Attempt/recovery/Force Submit/lease/fencing/offline.
4. Koordinator menyetujui database guard dan kebijakan recovery_until.
5. Restore dump hanya ke MySQL 8 disposable baru, setelah raw bytes dan checksum diverifikasi; lakukan preflight schema, bukan data produksi.
6. Setelah semua approval dan izin eksplisit koordinator, siapkan migration forward-only dan acceptance tests. Tidak ada migration sekarang.

Status akhir: AUDIT/DESIGN CONTINUATION COMPLETE — IMPLEMENTATION GATED.


## 8. POST-AUDIT REMOTE REFRESH — UPDATE AGENT 2 DAN CI AGENT 3

Pemeriksaan tambahan dilakukan setelah komentar baru Agent 2 pada Issue #10.

### 8.1 Branch HEAD dan graph terbaru

| Owner | Branch | HEAD | Ahead / behind terhadap Agent 1 |
|---|---|---|---:|
| Agent 1 | work/stage4b-room-session-20261122 | 4754ae0418193cd0f25b29a6296d3632065348f9 sebelum update dokumentasi ini | — |
| Agent 2 | work/stage4d-account-lifecycle-20261122 | 32d383eb1564cdbcf084b8677fe8b750a4b45a11 | 225 / 84 |
| Agent 3 | work/web-guru-foundation-20261122 | 07ad9ed99cbe24492264fb0f5d74c2bc15ed7a23 | 172 / 229 |
| Kandidat Agent 4 | work/import-guru-siswa-implementation-20261122 | f38ceec7b0653fcf3363eec40f3d4577c323cca8 | 80 / 352 |
| main | main | feab77228f7108f7f7a45325d7f1fb878c07310b | 15 / 1202 |

Merge-base tetap:
- Agent 2: 0631a3d84d058c8c049dcb71dc54b2e7cbb849a6.
- Agent 3: 81b6c74d81d80bc483530802594f624fb4e0f3db.
- Kandidat Agent 4: 17c20c66284deb7a18e3a6dde2b3d47f9c966bd9.
- main: 95736e338e517a087503adfb3e3a899709e0340c.

Commit terbaru Agent 2 hanya menambah dokumen audit. Source runtime baseline tetap 74bf7178f46673e9be90523671490c0749034acd. Commit terbaru Agent 3 hanya menambah manifest backup. Candidate Agent 4 belum dikonfirmasi sebagai owner.

### 8.2 Update Agent 2

Agent 2 menambahkan laporan read-only:
backend-laravel/docs/AGENT2_CONTINUATION_AUTH_TENANT_REPORTING_LIFECYCLE_2026-11-22.md

Blob SHA: bd8e80ad324fda10faf4cfc9cb34b4d19b8f299c.
Source baseline HEAD: 74bf7178f46673e9be90523671490c0749034acd.
Dokumentasi HEAD: 32d383eb1564cdbcf084b8677fe8b750a4b45a11.

Poin lintas Stage 4B:
- Exact-role belum konsisten pada sejumlah Exam/Attempt/Report policy dan service entrypoint.
- ExamPolicy::monitor masih memiliki jalur KAMAD; ini bertentangan dengan keputusan tidak ada portal KAMAD.
- Tenant pre-auth resolver belum menyelesaikan topologi shared multi-tenant DB vs per-tenant installation.
- Reporting wajib memakai current score pointer dan FINALIZED; ExamSummaryReportService saat ini mengagregasi seluruh histori score dan berisiko menghitung versi lama/non-final.
- Immutable report issuance/snapshot belum tersedia.
- Scheduler Proktor Agent 1 masih dapat menyelesaikan assignment saat Attempt aktif.
- Device Lease/Fencing belum dibuktikan end-to-end.

Status approval Agent 2 pada Issue #10 tetap PENDING; laporan audit bukan approval kontrak Stage 4B.

### 8.3 CI Agent 2 — koreksi status terbaru

Run #388, ID 45003796892, pada source SHA 74bf7178f46673e9be90523671490c0749034acd: BLOCKED — RUNNER.
Run #389, ID 45008591113, pada dokumentasi SHA 32d383eb1564cdbcf084b8677fe8b750a4b45a11 dengan source runtime tidak berubah: BLOCKED — RUNNER.

Kedua job tidak memiliki steps yang dieksekusi; laporan Agent 2 mencatat runner_id=0 dan attempts 1–3 tanpa runner. PHPUnit tidak berjalan. Status patch explicit web guard bukan PASS dan bukan FAIL; statusnya BLOCKED — RUNNER. Run 44998669927 dan 44998949281 yang lebih lama tetap tercatat sebagai failure HTTP 500 vs expected 302, tetapi bukan bukti untuk run terbaru.

### 8.4 CI Agent 3 — run terbaru menggantikan success lama

Run 45003191589 pada source SHA 834a59f11f9b548c8d7f0fcea68c15cf4bbe501f — FAILURE.
- PHP lint dan route registration: PASS.
- PHPUnit TeacherPortalFoundationTest: 18 passed, 2 failed, 86 assertions.
- Kegagalan 1: tenant-context mismatch mengharapkan HTTP 403 tetapi menerima 200.
- Kegagalan 2: Livewire action untuk ID Question lintas tenant melempar ModelNotFoundException, bukan respons Not Found yang diharapkan.
- Source test commit 834a59f hanya mengubah TeacherPortalFoundationTest.php.
- Branch Agent 3 saat refresh berada di 07ad9ed99cbe24492264fb0f5d74c2bc15ed7a23; commit terbaru sesudah test hanya dokumentasi backup.

Run SUCCESS Agent 3 sebelumnya 44995889398 pada SHA a338679... tidak lagi menjadi CI terbaru. Dua kegagalan baru ini harus diselesaikan Agent 3 sebelum integrasi Web Guru.

Controller Agent 3 tetap pada blob e915bf43118026ef8eace96338fa6cad44ee29e1 dan method show() tidak memanggil ExamMonitoringScope::allowsParticipant(). Kegagalan CI lintas tenant tersebut menambah blocker security, tetapi tidak menggantikan acceptance test Proktor A/B.

### 8.5 Keputusan yang tidak berubah

- Issue #10 tetap OPEN.
- Komentar Agent 2 menyatakan approval Agent 2/3/4 tetap PENDING.
- Belum ada approval teknis Agent 3 atau Agent 4.
- Branch Agent 4 tetap kandidat.
- Tidak ada source, migration, schema, database, workflow, atau branch agent lain yang diubah dalam refresh ini.
- Status Stage 4B tetap AUDIT/DESIGN CONTINUATION COMPLETE — IMPLEMENTATION GATED.
