# KONTRAK INTEGRASI AGENT 1 — AGENT 2 — AGENT 3 — AGENT 4
Tanggal: 22 November 2026
Repository: zane0184/cbt_november
Branch dokumen: work/stage4b-room-session-20261122
Status: KONTRAK DESAIN READ-ONLY — TIDAK MENGUBAH BRANCH AGENT LAIN

## 1. Invariant bersama
1. Satu Exam memiliki tepat satu ExamSession operasional.
2. Satu ExamSession operasional menampung seluruh ExamSessionRoom untuk semua ruang fisik.
3. Tidak ada Session 2/3 atau jadwal gelombang peserta dalam satu Exam.
4. Jadwal peserta hanya Exam.start_at/end_at dengan waktu server.
5. ExamSession.starts_at/ends_at adalah mirror jadwal Exam, bukan sumber jadwal.
6. ExamSession/session-room/placement bukan admission gate peserta.
7. Token tetap melekat pada Exam dan diterbitkan saat Guru mengunci/menerbitkan Exam.
8. Assignment Proktor tetap temporer dan scoped ke Exam + exam_session_room_id.
9. Proktor hanya memonitor peserta/attempt pada room yang ditugaskan.
10. Tenant_id, exam_id, session_id, room_id dan assignment scope harus divalidasi bersama; tidak ada akses lintas tenant.
11. Semua histori room, mapping, placement, Proktor, Attempt, Answer, Score dan audit dipertahankan.

## 2. Kontrak data dan relasi
- Exam: pemilik jadwal resmi, status lifecycle, token, durasi, attempt policy.
- ExamSession: satu wadah operasional canonical untuk Exam; identitasnya tidak diganti setelah dibuat.
- ExamSessionRoom: satu hubungan session dengan ruang fisik; banyak row room dapat berada di bawah satu session.
- ExamSessionRoomRombel: mapping rombel ke room-session.
- ExamParticipantRoomAssignment: penempatan peserta ke room-session dengan status ACTIVE/RELEASED dan histori.
- ExamProctorAssignment: assignment Guru temporer yang mengikat tenant_id + exam_id + exam_session_room_id + schedule_revision + window tugas.
- ExamAttempt/ExamAttemptAnswer/ExamAttemptScore/ExamAttemptScoreCurrent/ExamQuestionScore: tetap terikat pada Exam/Participant/Attempt; tidak dipindah saat room/session direkonsiliasi.

Penanda is_operational:
- 1 = canonical session untuk Exam.
- 0 = session lama/historis non-operasional.
- Nilai 1 tidak berubah menjadi 0 untuk membuat session pengganti.
- Semua query operasional (scheduler, assignment baru, monitoring, management) harus memfilter is_operational=1.
- Histori non-operasional tetap dapat dibaca untuk audit, tetapi tidak boleh memberi capability.

## 3. Tanggung jawab Agent 1 — Stage 4B
- Menjaga kontrak satu Exam/satu session dan banyak room.
- Mengelola room master, session operasional tunggal, mapping rombel/placement, assignment Proktor dan lifecycle room-session.
- Menegakkan invariant pada service + database, bukan UI saja.
- Menyediakan scope monitoring per room untuk Proktor.
- Tidak mengambil alih implementasi Device Lease/fencing Agent 2 atau Web Guru Agent 3.
- Tidak mengubah branch Agent 2/3 tanpa koordinasi dan persetujuan.

## 4. Kontrak untuk Agent 2 — Stage 4D / Device Lease / Fencing
Agent 2 wajib:
- Memakai Exam.start_at/end_at dan waktu server sebagai admission/timing authority.
- Tidak memakai ExamSession.starts_at/ends_at, session_number, atau room assignment sebagai waktu mulai Attempt.
- Tidak membuat session/gelombang tambahan untuk device lease, recovery, takeover, atau fencing.
- Lease/fencing tetap mengikat identitas peserta/Attempt/Exam/device sesuai desain Stage 4D; bila menyimpan session/room reference untuk audit atau konteks operasional, referensi tersebut tidak boleh menjadi gate jadwal.
- Attempt aktif yang mengalami recovery/takeover tidak boleh dipindah room/session secara otomatis. Perubahan placement harus mengikuti workflow Stage 4B dan tetap mempertahankan histori.
- Lock order lintas service harus menghindari siklus: Exam -> Participant -> Attempt -> Device Lease/Fencing, dan ketika operasi perlu room/session, ikuti Exam -> Room -> Session -> SessionRoom sebelum operasi Attempt/Lease yang relevan; detail final harus disepakati kedua agent sebelum implementasi.
- Tidak mengubah FK session-room, menghapus history, atau memperluas Proktor monitoring.
- Menambahkan integration test bahwa peserta dapat start sesuai Exam window walaupun session/room assignment berbeda masa tugas Proktor; session tidak menjadi admission gate.
- Melaporkan kebutuhan perubahan schema bersama sebelum mengubahnya.

## 5. Kontrak untuk Agent 3 — Web Guru
Agent 3 wajib:
- Guru membuat/mengatur jadwal Exam pada Exam.start_at/end_at selama DRAFT dan sesuai policy.
- Setelah Exam dikunci/published, jadwal menjadi immutable pada jalur update biasa.
- Tidak menyediakan input Session 1/2/3, gelombang, jadwal session, atau reschedule session kepada Guru.
- Token tetap dikelola pada Exam dan ditampilkan sesuai hak akses; token dapat dilihat sebelum jadwal, tetapi peserta hanya dapat mulai pada Exam window.
- UI Guru dapat menampilkan informasi ruang secara read-only bila dibutuhkan, tetapi Admin tetap mengelola master ruang dan penempatan operasional sesuai permission.
- Race update-vs-publish wajib ditutup pada backend, bukan hanya disabled state di UI.
- Tidak mengubah model ExamSession, room, placement, atau assignment tanpa koordinasi Agent 1.
- Menambahkan test bahwa update jadwal setelah lock ditolak, dan bahwa race publish-vs-update tidak dapat menulis jadwal setelah locked.

## 6. Kontrak Proktor dan monitoring
- Proktor bukan role/portal permanen; Proktor adalah Guru yang mendapat assignment temporer dan confirmed.
- Satu Proktor dapat diberi tugas pada room yang ditugaskan; banyak Proktor dapat bertugas pada room berbeda dalam satu Exam.
- Assignment window boleh lebih pendek daripada Exam window tetapi selalu berada di dalamnya.
- Perubahan scope room/placement menaikkan schedule_revision dan membuat assignment terdampak NEEDS_REVIEW; konfirmasi ulang diperlukan.
- Monitoring hanya memuat peserta/attempt/event dari room yang ditugaskan. Tidak boleh memperluas akses ke room lain.
- Admin Madrasah mengelola administrasi/penugasan tetapi tidak memperoleh hak monitoring CBT hanya karena menugaskan Guru.
- Guru pemilik Exam dan Kamad mengikuti policy terpisah; assignment Proktor tidak mengubah role permanen.

## 7. Kontrak lifecycle dan close
- ExamController::update harus lock Exam dan re-authorize di dalam transaksi sebelum update jadwal.
- ExamController::lock menetapkan publish/locked dan token pada Exam.
- Close Exam oleh Guru harus menyelaraskan session/assignment secara atomik tanpa merusak Attempt aktif.
- Admin tidak dapat cancel session sebagai jalan alternatif untuk mengubah jadwal/akses peserta.
- Attempt IN_PROGRESS yang sah tetap dapat resume sesuai policy; Proktor monitoring tidak dicabut prematur selama masih dibutuhkan pemulihan.
- Session status scheduler tidak mengubah Exam schedule dan tidak menjadi start gate.

## 8. Integrasi, koordinasi, dan gate
Sebelum perubahan source lintas agent:
1. Agent yang membutuhkan perubahan mengajukan daftar file/schema/API dan alasan.
2. Pemilik branch menilai dampak dan menyepakati kontrak.
3. Buat backup sebelum file yang dimiliki agent diubah.
4. Tidak mengedit branch Agent lain secara sepihak.
5. Jalankan test integrasi lintas branch pada disposable MySQL 8+; SQLite hanya sebagai test runtime tambahan yang versi dan perilakunya dibuktikan.
6. Catat SHA, file, backup, hasil test dan checkpoint.
7. Tidak ada merge/rebase/PR/deployment/DB produksi tanpa persetujuan.

## 9. Status pemeriksaan
- Agent 2 branch yang ditemukan sebelumnya: work/stage4d-account-lifecycle-20261122; source/model terkait dibaca read-only pada audit sebelumnya. Branch tidak diubah.
- Agent 3 branch yang ditemukan sebelumnya: work/web-guru-foundation-20261122; source/model terkait dibaca read-only pada audit sebelumnya. Branch tidak diubah.
- Branch HEAD masing-masing Agent 2/3 tidak menjadi bagian dari pekerjaan ini dan tidak diubah.
- Kontrak ini adalah dokumen koordinasi; penerimaan formal Agent 2/3 belum diperoleh: NOT VERIFIED.


---

## 10. ADDENDUM REKONSILIASI AGENT 1–4 — AUDIT READ-ONLY 22 NOVEMBER 2026

Status addendum: AUDIT + DESAIN KOORDINASI. Tidak memberi izin coding, migration, schema change, database mutation, merge, rebase, cherry-pick, deployment, atau perubahan branch agent lain.

### 10.1 HEAD dan graph branch saat audit

| Pemilik | Branch yang ditemukan | HEAD remote | Merge-base terhadap Agent 1 | Ahead / behind terhadap Agent 1 |
|---|---|---|---|---:|
| Agent 1 | work/stage4b-room-session-20261122 | 338fd45d02a019bbe44cc70aa846b5e76f611323 | — | — |
| Agent 2 | work/stage4d-account-lifecycle-20261122 | a1eb70f193e875ecffa64cc7dcc7114a8e60c631 | 0631a3d84d058c8c049dcb71dc54b2e7cbb849a6 | 204 / 65 |
| Agent 3 | work/web-guru-foundation-20261122 | 650a7b45e1e310c8840e6fc0366d0c96577cf314 | 81b6c74d81d80bc483530802594f624fb4e0f3db | 113 / 210 |
| Kandidat Agent 4 — belum terkonfirmasi | work/import-guru-siswa-implementation-20261122 | f38ceec7b0653fcf3363eec40f3d4577c323cca8 | 17c20c66284deb7a18e3a6dde2b3d47f9c966bd9 | 80 / 333 |
| Main | main | feab77228f7108f7f7a45325d7f1fb878c07310b | 95736e338e517a087503adfb3e3a899709e0340c | Agent 1 15 ahead / 1.183 behind |

Branch kerja diverged. Main tidak memuat path source CBT yang sedang diaudit pada struktur backend-laravel yang digunakan branch Agent 1. Jangan memakai main sebagai pengganti integrasi empat branch ini.

Belum ditemukan branch yang secara eksplisit bernama atau ditandai sebagai Agent 4. Branch import-guru-siswa adalah kandidat karena commit terakhir mengubah ExamAttemptController, tetapi pemiliknya belum dapat dipastikan. Status ownership Agent 4: PENDING CONFIRMATION.

### 10.2 Status PR dan koordinasi

Pemeriksaan daftar seluruh PR repository tidak menemukan PR integrasi Agent 1, Agent 2, Agent 3, atau branch kandidat Agent 4. PR terbuka yang terlihat berkaitan dengan Web Admin, installer, dan audit P0.95D, bukan integrasi satu Exam–satu ExamSession. Tidak ada merge antar-branch yang dibuktikan oleh pemeriksaan ini.

Persetujuan teknis:
- Agent 2 — PENDING. Belum ada persetujuan tertulis untuk kontrak Exam close/resume, session lifecycle, scheduler, dan lock ordering.
- Agent 3 — PENDING. Belum ada persetujuan tertulis untuk canonical session, room/placement, Proktor, serta monitoring scope.
- Agent 4 — PENDING / OWNER UNCONFIRMED. Belum ada branch resmi atau persetujuan tertulis yang dapat diatribusikan secara pasti.
- Agent 1 — bertindak sebagai pemilik rancangan koordinasi, bukan pemberi persetujuan bagi agent lain.

### 10.3 Matriks blob SHA file inti

Nilai MISSING berarti path tidak tersedia pada branch tersebut; bukan bukti bahwa fungsinya tidak ada pada path lain.

| File | Agent 1 | Agent 2 | Agent 3 | Kandidat Agent 4 |
|---|---|---|---|---|
| ExamController.php | c7c62e96 | c7c62e96 | c7c62e96 | bf373858 |
| ExamAttemptController.php | 13e8981c | 13e8981c | e915bf43 | 80870209 |
| ExamSessionLifecycleService.php | 8e34152a | 8e34152a | MISSING | MISSING |
| ExamRoomSessionService.php | e514fa9f | e514fa9f | 9da24429 | MISSING |
| ExamProctorAssignmentService.php | c968ce68 | c968ce68 | c968ce68 | 34443cb8 |
| ExamMonitoringScope.php | 11b45595 | 11b45595 | 11b45595 | MISSING |
| ExamSession.php | d1d41ad8 | d1d41ad8 | d1d41ad8 | MISSING |
| ExamSessionRoom.php | d55c16e8 | d55c16e8 | d55c16e8 | MISSING |
| ExamSessionRoomRombel.php | 78d6673e | 78d6673e | 78d6673e | MISSING |
| ExamParticipant.php | ac252d6b | feaab347 | ac252d6b | ff9cbfba |
| ExamParticipantRoomAssignment.php | 743f8ede | 9a482684 | 743f8ede | MISSING |
| ExamProctorAssignment.php | 88a1c231 | 0c05092c | 88a1c231 | dde7511e |
| ExamAttempt.php | 341c8d00 | 341c8d00 | 341c8d00 | 341c8d00 |
| ExamAttemptAnswer.php | 9765f275 | 9765f275 | 9765f275 | 9765f275 |
| ExamAttemptScore.php | 2fdceaa2 | b374cb57 | 2fdceaa2 | 2fdceaa2 |
| ExamAttemptScoreCurrent.php | d9d0841d | d9d0841d | d9d0841d | d9d0841d |
| ExamQuestionScore.php | 34f9802f | 6257ed2c | 34f9802f | 34f9802f |
| ExamPolicy.php | c05d96ef | c05d96ef | 839df41c | 121fa887 |
| ExamAttemptPolicy.php | 00a3a411 | 00a3a411 | 00a3a411 | 00a3a411 |
| SyncExamSessionLifecycle.php | 9663b571 | 9663b571 | MISSING | MISSING |

Blob SHA dipendekkan untuk keterbacaan; full SHA telah dibaca dari GitHub pada audit ini.

### 10.4 Konsekuensi konflik lintas agent

1. Agent 2 mengubah relasi historis pada ExamParticipant, ExamParticipantRoomAssignment, ExamProctorAssignment, ExamAttemptScore, dan ExamQuestionScore dengan relasi `withTrashed()`. Perubahan ini menjaga identitas actor/siswa yang akunnya dinonaktifkan agar histori tetap dapat dibaca. Perubahan tersebut harus dipertahankan ketika source digabungkan; jangan menyalin model Agent 1 secara membabi buta.
2. Agent 3 memakai ExamRoomSessionService dan ExamAttemptController yang berbeda dari Agent 1. Pada ExamAttemptController Agent 3, endpoint show mengotorisasi monitor pada level Exam tetapi tidak memanggil ExamMonitoringScope::allowsParticipant(). Policy monitor Agent 3 juga memeriksa assignment pada level Exam. Ini belum memenuhi kontrak Proktor hanya boleh melihat peserta di room yang ditugaskan. Integrasi wajib mempertahankan pemeriksaan peserta/room yang ada pada Agent 1 atau memberikan desain pengganti yang ekuivalen dan teruji.
3. ExamRoomSessionService Agent 3 tidak identik dengan Agent 1 dan tidak memuat seluruh method yang ada pada Agent 1; versi yang diperiksa tidak menyediakan updateRoom/cancelSession dan tidak memiliki ExamSessionLifecycleService pada tree branch tersebut. Perlu rekonsiliasi API service dan UI, bukan penggantian file langsung.
4. Kandidat Agent 4 mengubah urutan pemeriksaan peserta dan otorisasi pada ExamAttemptController, tetapi branch itu belum memuat migration/model/service Stage 4B room-session. Perubahan auth-order harus direview dan diuji bersama scope monitoring room; jangan cherry-pick tanpa regression test.
5. ExamController sama pada Agent 1/2/3, tetapi berbeda pada kandidat Agent 4. ExamPolicy sama pada Agent 1/2, berbeda pada Agent 3 dan kandidat Agent 4. ExamAttemptPolicy dan model inti Attempt/Answer/ScoreCurrent sama pada empat branch yang dibandingkan, sedangkan relasi historis Score/QuestionScore berbeda pada Agent 2.

### 10.5 Verifikasi temuan lifecycle Agent 1

Temuan yang masih ada pada HEAD Agent 1:
- createSession masih menerima nama, nomor, starts_at, dan ends_at dari Admin; constraint saat ini hanya unik per nomor session, sehingga nomor berbeda masih memungkinkan session kedua.
- rescheduleSession masih mengubah jadwal session secara independen.
- cancelSession masih membolehkan Admin membatalkan session SCHEDULED dan mencabut placement/assignment tanpa menutup Exam.
- ExamController::update masih melakukan authorize lalu update langsung tanpa transaksi, lockForUpdate, dan authorize ulang pada row terkunci.
- ExamController::close mengunci Exam dan mencatat audit, tetapi belum menyelaraskan ExamSession dan assignment Proktor.
- ExamSessionLifecycleService memilih kandidat berdasarkan starts_at/ends_at session, mengunci session tanpa mengunci Exam terlebih dahulu, dan tidak memfilter is_operational karena kolom tersebut belum ada.
- Scheduler dapat menyelesaikan session/assignment berdasarkan status/waktu session tanpa memeriksa Attempt IN_PROGRESS yang masih memerlukan dukungan monitoring.
- ExamAttemptController::store mengunci Exam lalu Participant lalu Attempt. Policy mengizinkan resume Attempt IN_PROGRESS yang belum kedaluwarsa dan masih eligible setelah Exam ditutup; Attempt baru tetap bergantung pada Exam.canStartAttempt().
- cancelSession memeriksa Attempt aktif pada placement session sebelum pembatalan, tetapi pembatalan session tetap tidak mengubah status Exam. Karena session bukan admission gate, pembatalan ini berpotensi meninggalkan Exam PUBLISHED yang masih dapat menerima Attempt baru sementara scope ruang/monitoring telah dicabut.

### 10.6 Lock ordering — kondisi aktual dan rancangan koordinasi

Kondisi aktual:
- createSession: Exam.
- attachRoom: Exam -> Physical Room -> ExamSession.
- mapRombel/releaseRombelMapping: membaca SessionRoom, lalu lock Exam -> Room -> SessionRoom -> ExamSession -> Rombel/Participant/Placement.
- ExamProctorAssignmentService::lockScope: Exam -> Room -> SessionRoom -> ExamSession; kemudian User/Assignment.
- ExamSessionLifecycleService scheduler: Session saja; belum lock Exam.
- ExamAttemptController::store dan ExpireExamAttemptsCommand: Exam -> Participant -> Attempt.
- ExamController::lock/close: Exam row lock; update belum menggunakan pola yang sama.

Urutan canonical yang harus disepakati:
1. Exam.
2. Physical ExamRoom, sorted by primary key.
3. ExamSession.
4. ExamSessionRoom, sorted by primary key.
5. Rombel, sorted by primary key.
6. ExamParticipant, sorted by primary key.
7. Participant placement/mapping, sorted by primary key.
8. Proctor assignment, sorted by primary key.
9. User/Guru, sorted by primary key.
10. Attempt, Answer, Score, Device Lease/Fencing sesuai kebutuhan operasi.

Operasi Attempt-only tetap menggunakan Exam -> Participant -> Attempt -> Answer/Score/Lease. Jika transaksi menyentuh room/placement sekaligus Attempt, transaksi harus mengikuti urutan global yang disepakati dan tidak boleh mengambil lock child lebih dahulu. Scheduler wajib mengunci Exam sebelum session dan assignment. Urutan aktual mapRombel/lockScope (SessionRoom sebelum Session) harus direkonsiliasi agar sesuai urutan canonical.

### 10.7 Kontrak Agent 4 — Attempt dan recovery

Agent 4 diminta meninjau dan menyatakan APPROVED / REJECTED / NEEDS DESIGN / PENDING atas:
- Attempt baru hanya dapat dibuat pada Exam window berdasarkan waktu server dan token Exam.
- Resume Attempt IN_PROGRESS yang sah setelah close hanya jika Attempt belum terminal/expired dan eligibility masih berlaku.
- Close Exam menolak Attempt baru, tetapi tidak menghapus/memindahkan Attempt aktif.
- Monitoring/assignment Proktor tidak dicabut selama masih dibutuhkan untuk pemulihan Attempt aktif.
- Submit normal, expiry, dan Force Submit harus konsisten, berotorisasi, tercatat, dan aman terhadap pengulangan.
- Answer persistence/sync harus mempertahankan idempotensi, urutan/fencing, dan histori.
- Device lease/takeover tidak membuat session baru dan tidak memindahkan placement secara otomatis.
- Force Submit, takeover, reset, atau perpanjangan waktu harus memiliki audit actor/reason/before-after.
- Device Lease/fencing tidak boleh disebut siap produksi sebelum integration test dan multi-device test lulus.

File/class yang harus dinyatakan owner-nya oleh Agent 4: ExamAttemptController, ExamAttempt, ExamAttemptAnswer, scoring/current score services, lease/fencing services, Force Submit workflow, StudentExamController/Web Siswa, dan tests terkait. Daftar final menunggu audit branch resmi Agent 4.

### 10.8 Database dan compatibility gate

Migration Stage 4B saat ini:
- exam_sessions memiliki unique (tenant_id, exam_id, session_number), bukan unique satu session operasional per Exam.
- Belum ada is_operational atau operational_guard.
- MySQL memakai unique expression index untuk active rombel mapping dan active participant placement; SQLite memakai partial unique index.
- Migration down memiliki guard histori dan rollback child-first; bukti CI lama bukan bukti bahwa migration constraint baru sudah kompatibel.

Bukti runtime yang tersedia:
- Artifact CI Stage 4B mencatat MySQL 8.0.46 dan InnoDB.
- Artifact berisi 37 file log/JSON, bukan SQL dump/database snapshot.
- Tidak ada versi SQLite aktual yang tercatat dalam artifact tersebut.
- Generated virtual column/unique guard untuk satu session operasional belum diimplementasikan atau diuji pada MySQL.
- SQLite partial unique index untuk invariant baru juga belum dijalankan dengan versi SQLite yang dicatat.

Gate database tetap BLOCKED sampai disposable dump dan compatibility test tersedia. Jangan jalankan migration.

### 10.9 Database/backup inventory terbaru

Pemeriksaan dilakukan terhadap /mnt/data, workspace rekonstruksi, Project/Conversation files, Library, artifact CI, dan Git trees branch terkait:
- /mnt/data/CBT_MADRASAH_RECONSTRUCTION tidak ditemukan.
- ZIP baseline CBT REVISI 6 NOPEMBER 2026.zip tidak ditemukan.
- Tidak ditemukan .sql, .sql.gz, .dump, .sqlite, atau .db.
- stage4b-ci-artifact.zip adalah log CI 20.145 byte, bukan database.
- Project/Conversation inventory berisi laporan/checkpoint dan prompt teks; tidak ada dump DB atau ZIP baseline.
- Library inventory tidak menampilkan file database/ZIP yang relevan.
- Git tree branch schema-dump-export dan branch agent terkait tidak menampilkan dump database.
- Tidak ada koneksi ke database produksi.

Preflight data lama: NOT RUN. Tidak ada data sintetis yang menggantikan data lama.

### 10.10 Status CI saat audit

| Branch | Run | SHA yang diuji | Status | Batas bukti |
|---|---:|---|---|---|
| Agent 1 | 44984303003 | f1d01ea6fa4b9a100d00680a1b70c2a8d92b9af5 | SUCCESS | 24 test / 120 assertions; MySQL 8.0.46. HEAD sekarang hanya menambah dokumen; desain satu session belum diuji. |
| Agent 2 | 44997980279 | c18acf591255b5a9199a0b9388d0adcee9ab7ea6 | FAILURE | Gagal pada PHPUnit Guru/Siswa regression; langkah setelahnya dilewati. |
| Agent 2 | 44998131077 | a1eb70f193e875ecffa64cc7dcc7114a8e60c631 | IN PROGRESS saat pemeriksaan | Menunggu hasil exact HEAD; belum PASS. |
| Agent 3 | 44995889398 | a33867928711b456d2414a675bef1a929861dee8 | SUCCESS | HEAD sekarang hanya menambah dokumen; tidak menguji kontrak satu session atau integrasi Agent 1/2/4. |
| Kandidat Agent 4 | 44949239676 | f38ceec7b0653fcf3363eec40f3d4577c323cca8 | SUCCESS | Run exact HEAD kandidat, tetapi branch belum memuat Stage 4B room-session; bukan bukti integrasi Attempt/room. |

CI Stage 4B lama tidak boleh diklaim sebagai pengujian desain satu Exam–satu ExamSession karena source desain tersebut belum diimplementasikan.

### 10.11 Test matrix dan acceptance criteria sebelum implementasi

A. Invariant/database/concurrency:
- Dua transaksi membuat session pada Exam sama: tepat satu berhasil; request lain mendapat conflict; tidak ada duplicate.
- Dua Exam berbeda dalam tenant sama dan tenant berbeda tidak saling memblokir secara keliru.
- Histori session non-operasional dapat tetap dibaca tanpa memperoleh capability operasional.
- Unique constraint dan rollback diuji pada MySQL 8.0.46 dan versi SQLite aktual yang dicatat.
- Migration rollback kosong berhasil; rollback dengan histori yang dilindungi ditolak tanpa kehilangan data.

B. Jadwal dan Exam lifecycle:
- DRAFT update valid.
- Publish versus update race: setelah publish, jadwal tidak dapat berubah.
- Update versus close race: hasil serializable dan audit before/after tepat.
- Jadwal session selalu identik dengan Exam; tidak ada jalur reschedule independen.
- Close Exam tidak membuat scheduler mengaktifkan session yang seharusnya sudah ditutup.
- Token tetap melekat pada Exam dan bukan session.

C. Attempt/recovery:
- Close versus start Attempt bersamaan.
- Attempt baru ditolak setelah close.
- Attempt IN_PROGRESS yang sah tetap dapat resume.
- Attempt expired/terminal tidak dapat resume.
- Submit berulang aman/idempotent.
- Expiry tidak membuat score palsu jika snapshot/order rusak.
- Force Submit hanya oleh role/permission sah, alasan dan actor tercatat.
- Lease/fencing, takeover, offline answer sync, dan multi-device diuji integrasi; unit test/schema saja tidak cukup.

D. Room/placement/Proktor:
- Banyak room dalam satu ExamSession.
- Peserta hanya memiliki satu placement aktif untuk Exam.
- Reassignment menyimpan row RELEASED lama dan row ACTIVE baru.
- Mapping rombel tidak menggandakan peserta.
- Assignment Proktor per room dan masa tugas berbeda.
- Proktor tidak dapat membaca peserta/Attempt di luar room yang dikonfirmasi.
- Admin tidak memperoleh monitoring atau data jawaban karena melakukan assignment.
- Perubahan scope menaikkan schedule_revision dan assignment terdampak NEEDS_REVIEW.

E. Scheduler:
- Exam dikunci sebelum session.
- Hanya session operasional yang diproses.
- Tidak membuat session.
- Tidak menyelesaikan assignment saat Attempt aktif masih membutuhkan monitoring.
- Idempotent pada eksekusi berulang.
- Tenant scope dan audit event tervalidasi.
- Race scheduler dengan update/publish/close/Attempt submit tidak menyebabkan deadlock atau kehilangan histori.

Acceptance criteria keseluruhan: seluruh test di atas dijalankan pada disposable environment dengan bukti exact SHA, tanpa data produksi; counts/hash Attempt, Answer, Score dan current-score pointer sebelum/sesudah rekonsiliasi harus identik.

### 10.12 Status persetujuan dan gate

| Keputusan | Status |
|---|---|
| Invariant satu Exam–satu ExamSession | DESIGN PROPOSED; menunggu persetujuan pengguna |
| is_operational dan unique guard | NEEDS DESIGN / compatibility test |
| Close Exam dan resume Attempt | PENDING Agent 2 + Agent 4 |
| Scheduler dan lock order | NEEDS DESIGN lintas Agent 1/2/3/4 |
| Room/placement/Proktor dan scope monitoring | PENDING Agent 3 |
| Branch resmi Agent 4 | PENDING IDENTIFICATION |
| Preflight database lama | BLOCKED — dump disposable tidak tersedia |
| Implementasi source/migration | NOT STARTED |
| Production database/deployment | NOT TOUCHED |

Sampai semua owner memberikan respons tertulis dan pengguna memberikan persetujuan eksplisit, tidak ada coding, migration, schema change, database mutation, merge/rebase/cherry-pick, deployment, atau klaim siap produksi.


## 10.13 FINAL REMOTE HEAD REFRESH — SNAPSHOT AKHIR AUDIT 22 NOVEMBER 2026

Branch bergerak selama audit. Nilai pada tabel 10.1 adalah snapshot awal addendum; berikut pembaruan remote terakhir yang diperiksa sebelum checkpoint akhir:

| Pemilik | Branch | HEAD remote terakhir | Ahead / behind terhadap Agent 1 |
|---|---|---|---:|
| Agent 1 | work/stage4b-room-session-20261122 | 84fdae954e195bc07b8fa0642eeb1703f77ebdf6 | — |
| Agent 2 | work/stage4d-account-lifecycle-20261122 | 808a7a917f1addbf8b8b67b4929dd1cbb6992cbd | 211 / 68 |
| Agent 3 | work/web-guru-foundation-20261122 | 4e58be81ab9ff821756de823d5faac8cba89e893 | 124 / 213 |
| Kandidat Agent 4 — belum terkonfirmasi | work/import-guru-siswa-implementation-20261122 | f38ceec7b0653fcf3363eec40f3d4577c323cca8 | 80 / 336 |
| main | main | feab77228f7108f7f7a45325d7f1fb878c07310b | Agent 1 15 ahead / 1.183 behind |

Perubahan setelah snapshot sebelumnya pada HEAD Agent 2 dan Agent 3 yang diperiksa adalah dokumentasi/checkpoint; tidak ada source CBT room/Attempt baru yang mengubah matriks blob inti pada pemeriksaan terakhir. Agent 2 source patch terakhir yang menunggu CI adalah helper browser/stateful API pada commit e91d5b20155a3d660a8a24668a9cf8c535327cc4. Agent 3 source terakhir tetap pada basis CI a33867928711b456d2414a675bef1a929861dee8; tambahan setelahnya adalah dokumen WG-01.

### Status CI paling akhir saat refresh

- Agent 1: run 44984303003 SUCCESS pada source SHA f1d01ea6fa4b9a100d00680a1b70c2a8d92b9af5. Tiga commit dokumentasi Agent 1 setelah HEAD 338fd45 tidak mengubah source aplikasi.
- Agent 2: run 44997980279 dan 44998131077 FAILURE pada StudentWebLoginTest (10 PASS/1 FAIL pada run 449979; HTTP 500 pada login Admin terpisah setelah stateful API browser). Source helper diperbaiki pada e91d5b; run 44998669927 pada SHA fac13a049b35f0c42a0e71cb5d24c07113f54238 masih IN_PROGRESS dan run 44998949281 pada HEAD dokumentasi 808a7a917f1addbf8b8b67b4929dd1cbb6992cbd masih PENDING. Belum ada PASS untuk source helper terbaru.
- Agent 3: run 44995889398 SUCCESS pada SHA a33867928711b456d2414a675bef1a929861dee8. HEAD sesudahnya hanya berisi dokumentasi; tidak membuktikan integrasi satu session/room atau monitoring lintas branch.
- Kandidat Agent 4: run 44949239676 SUCCESS pada SHA f38ceec7b0653fcf3363eec40f3d4577c323cca8, tetapi source branch tidak memuat Stage 4B room-session. Tidak dapat dipakai sebagai bukti integrasi Attempt dengan room/placement.

### Status permintaan persetujuan

Issue review lintas agent dibuat: https://github.com/zane0184/cbt_november/issues/10

Pada pemeriksaan komentar terakhir, belum ada respons persetujuan dari Agent 2, Agent 3, atau Agent 4; komentar yang tersedia hanya catatan koordinator. Semua status tetap PENDING. Issue duplikat #11 ditutup sebagai duplicate.

Snapshot ini adalah bukti kondisi remote saat audit, bukan jaminan branch tidak akan bergerak setelah checkpoint. Sebelum implementasi, HEAD wajib diperiksa ulang.


## 10.14 FINAL RECONCILIATION — REMOTE HEAD, SOURCE, DATABASE, DAN APPROVAL (22 NOVEMBER 2026)

Laporan rinci: docs/LAPORAN_AUDIT_DESAIN_STAGE4B_ROOM_SESSION_2026-11-22.md

### HEAD terbaru setelah backup dokumentasi

| Owner | Branch | HEAD remote | Ahead / behind terhadap Agent 1 |
|---|---|---|---:|
| Agent 1 | work/stage4b-room-session-20261122 | 6fb24dbe8d2de48f98c5ca9668286b2c20452c75 | — |
| Agent 2 | work/stage4d-account-lifecycle-20261122 | 74bf7178f46673e9be90523671490c0749034acd | 224 / 73 |
| Agent 3 | work/web-guru-foundation-20261122 | a955a439b1b3fa4c149f0153f283951d2bfb88d2 | 164 / 218 |
| Kandidat Agent 4 — owner belum terkonfirmasi | work/import-guru-siswa-implementation-20261122 | f38ceec7b0653fcf3363eec40f3d4577c323cca8 | 80 / 341 |
| main | main | feab77228f7108f7f7a45325d7f1fb878c07310b | 15 / 1191 |

Merge-base Agent 2: 0631a3d84d058c8c049dcb71dc54b2e7cbb849a6.
Merge-base Agent 3: 81b6c74d81d80bc483530802594f624fb4e0f3db.
Merge-base kandidat Agent 4: 17c20c66284deb7a18e3a6dde2b3d47f9c966bd9.
Merge-base main: 95736e338e517a087503adfb3e3a899709e0340c.

Semua branch diverged. Tidak ada PR terbuka dari branch tersebut. Tidak ada merge/rebase yang dicoba; konflik merge aktual NOT TESTED. Branch Agent 4 tetap kandidat dan tidak boleh dianggap resmi.

### Koreksi database inventory — supersedes bagian 10.9 sebelumnya

Dump uji ditemukan kembali di Project Library:
- cbt_madrasah_test_backup_20261102_083757.sql
- 122.380 byte; SHA-256 historis f716921d657baf7096df577fa844602e5cc015364d07d30a0834c4b43471901c.
- MySQL 8.0.46; 57 tabel; seluruhnya InnoDB; 39 migration tercatat.
- Data INSERT hanya cache, migrations, sessions; tidak ada data tenant/user/siswa/guru/rombel/membership.
- Dump bertanggal 2 November dan belum memiliki tabel Stage 4B exam_sessions/room/placement.
- Mengandung DROP TABLE IF EXISTS; hanya boleh dipulihkan ke database disposable baru yang benar-benar kosong.

Dump belum di-restore, raw bytes tidak dapat dimaterialisasi pada sesi ini, dan SHA belum dihitung ulang saat audit ini. Database produksi tidak disentuh. Status yang benar: DUMP FOUND — RESTORE/RUNTIME NOT RUN — PRODUCTION DB NOT TOUCHED. Dump ini tidak membuktikan kondisi data produksi atau legacy Stage 4B.

Workspace aktif /mnt/data/CBT_MADRASAH_RECONSTRUCTION/, /mnt/data/cbt_audit_work/, /mnt/data/cbt_stage5p_work/ dan /mnt/data/CBT_DUAL_INSTALLER_RECONSTRUCTION_2026-11-15/ tidak ditemukan.

### Temuan source final

1. Migration Stage 4B blob 9551b1dd79397bd0432f03eefc970852b675fd25 masih memakai unique (tenant_id, exam_id, session_number); is_operational/operational_guard belum ada.
2. ExamSessionLifecycleService blob 8e34152afd817f30cc2f81b5f25fc450571463ac mengunci Session tanpa Exam, menyelesaikan session berdasar ends_at, menyelesaikan/expire assignment tanpa memeriksa Attempt IN_PROGRESS.
3. cancelSession dapat melepas placement dan revoke Proktor tanpa menutup Exam; Exam PUBLISHED dapat tetap menerima Attempt karena session bukan admission gate.
4. rescheduleSession masih mengubah jadwal session secara independen; jadwal resmi wajib tetap pada Exam.
5. Agent 2 start/resume/submit mengunci Exam → Participant → Attempt; resume Attempt IN_PROGRESS yang belum expired dan masih eligible setelah close harus dipertahankan.
6. Agent 2 ExamAnswerController masih Attempt → Answer; belum sesuai global parent-first lock order.
7. archive, publishResults, destroy belum memakai transaksi + Exam row lock + re-authorize. Destroy memiliki race dengan create Attempt.
8. Agent 1 ExamAttemptController::show memakai ExamMonitoringScope::allowsParticipant. Agent 3 controller blob e915bf43118026ef8eace96338fa6cad44ee29e1 dan kandidat Agent 4 controller blob 808702092c94b386657fe027f60758a625d5bb93 tidak memakai scope tersebut.
9. Relasi histori withTrashed Agent 2 untuk participant, Proktor, assigner/revoker, placement actor, dan student score harus dipertahankan. Relasi actor grader/finalizer pada model score masih perlu keputusan.

### Status CI

- Agent 1 run 44984303003 SUCCESS pada source f1d01ea6fa4b9a100d00680a1b70c2a8d92b9af5; MySQL 8.0.46/InnoDB. Room management 12/49, room concurrency 2/22, monitoring 15/151, Proktor assignment 8/34; child-first rollback PASS. Tidak menguji operational guard satu session.
- Agent 2 run 44998669927 dan 44998949281 FAILURE pada StudentWebLoginTest.php:125 (HTTP 500, expected 302); belum ada PASS untuk exact HEAD 74bf7178.
- Agent 3 run 44995889398 SUCCESS pada source a33867928711b456d2414a675bef1a929861dee8; tidak membuktikan integrasi Stage 4B.
- Kandidat Agent 4 run 44949239676 SUCCESS pada f38ceec7; tidak membuktikan integrasi Stage 4B lintas agent.

### Status approval

Issue #10 tetap OPEN. Empat komentar yang tersedia berasal dari koordinator; belum ada approval teknis Agent 2, Agent 3, atau Agent 4. Status:
- Agent 2: NEEDS DESIGN / COORDINATION.
- Agent 3: PENDING.
- Agent 4: PENDING — OWNER UNCONFIRMED.
- Operational guard: NEEDS DESIGN / compatibility test.
- Lock order: NEEDS DESIGN lintas agent.
- Database: DUMP FOUND, RESTORE NOT RUN.
- Source/migration implementation: NOT STARTED.

Lihat laporan Stage 4B untuk desain canonical session, MySQL generated guard vs SQLite partial unique index, lifecycle recovery, tabel lock order, contoh deadlock, dan acceptance test plan.



## 10.15 POST-BACKUP REMOTE REFRESH — 22 NOVEMBER 2026

Source HEAD Agent 1 tepat sebelum pembaruan dokumentasi koreksi akhir: 87b1d6210d3d175f23b2f5aa215afa293c348563. Dua commit sebelumnya hanya menambahkan backup byte-exact kontrak dan checkpoint.

Perbandingan terbaru terhadap source HEAD tersebut:

| Owner | Branch | HEAD | Ahead / behind | Merge-base |
|---|---|---|---:|---|
| Agent 2 | work/stage4d-account-lifecycle-20261122 | 74bf7178f46673e9be90523671490c0749034acd | 224 / 78 | 0631a3d84d058c8c049dcb71dc54b2e7cbb849a6 |
| Agent 3 | work/web-guru-foundation-20261122 | a955a439b1b3fa4c149f0153f283951d2bfb88d2 | 164 / 223 | 81b6c74d81d80bc483530802594f624fb4e0f3db |
| Kandidat Agent 4 | work/import-guru-siswa-implementation-20261122 | f38ceec7b0653fcf3363eec40f3d4577c323cca8 | 80 / 346 | 17c20c66284deb7a18e3a6dde2b3d47f9c966bd9 |
| main | main | feab77228f7108f7f7a45325d7f1fb878c07310b | 15 / 1196 | 95736e338e517a087503adfb3e3a899709e0340c |

Seluruh branch tetap diverged. Tidak ada PR terbuka dari branch Agent 1–4. Konflik merge aktual tidak diuji karena merge/rebase dilarang.

Dokumen ini diperbarui hanya untuk mencatat hasil audit, koreksi database inventory, backup dan status koordinasi. Source aplikasi, migration, schema, workflow dan database tidak berubah. HEAD final setelah commit dokumentasi terakhir dicatat pada manifest final reconciliation.


## 10.16 POST-AGENT2 UPDATE — LATEST BRANCH DAN CI (22 NOVEMBER 2026)

Agent 2 menambahkan audit read-only pada:
backend-laravel/docs/AGENT2_CONTINUATION_AUTH_TENANT_REPORTING_LIFECYCLE_2026-11-22.md
Blob: bd8e80ad324fda10faf4cfc9cb34b4d19b8f299c.
Source runtime baseline Agent 2 tetap 74bf7178f46673e9be90523671490c0749034acd; HEAD dokumentasi 32d383eb1564cdbcf084b8677fe8b750a4b45a11.

### HEAD dan graph terbaru

| Owner | Branch | HEAD | Ahead / behind terhadap Agent 1 |
|---|---|---|---:|
| Agent 1 | work/stage4b-room-session-20261122 | 4754ae0418193cd0f25b29a6296d3632065348f9 sebelum update dokumentasi ini | — |
| Agent 2 | work/stage4d-account-lifecycle-20261122 | 32d383eb1564cdbcf084b8677fe8b750a4b45a11 | 225 / 84 |
| Agent 3 | work/web-guru-foundation-20261122 | 07ad9ed99cbe24492264fb0f5d74c2bc15ed7a23 | 172 / 229 |
| Kandidat Agent 4 | work/import-guru-siswa-implementation-20261122 | f38ceec7b0653fcf3363eec40f3d4577c323cca8 | 80 / 352 |
| main | main | feab77228f7108f7f7a45325d7f1fb878c07310b | 15 / 1202 |

Merge-base Agent 2: 0631a3d84d058c8c049dcb71dc54b2e7cbb849a6.
Merge-base Agent 3: 81b6c74d81d80bc483530802594f624fb4e0f3db.
Merge-base kandidat Agent 4: 17c20c66284deb7a18e3a6dde2b3d47f9c966bd9.
Merge-base main: 95736e338e517a087503adfb3e3a899709e0340c.

### CI Agent 2 — latest

- Run 45003796892 pada SHA 74bf7178: BLOCKED — RUNNER; job tidak memiliki steps.
- Run 45008591113 pada SHA dokumentasi 32d383e: BLOCKED — RUNNER; source runtime tidak berubah, job tidak memiliki steps.
- Agent 2 melaporkan runner_id=0 dan attempts 1–3 tanpa runner. PHPUnit tidak berjalan.
- Run lama 44998669927/44998949281 tetap failure HTTP 500 vs expected 302, tetapi bukan bukti terhadap runner-blocked attempts terbaru.

### CI Agent 3 — latest

Run 45003191589 pada SHA 834a59f11f9b548c8d7f0fcea68c15cf4bbe501f: FAILURE, 18 passed / 2 failed / 86 assertions.
- Tenant-context mismatch: expected 403, received 200.
- Cross-tenant Livewire Question ID: ModelNotFoundException, bukan Not Found response.
Run SUCCESS 44995889398 pada SHA a338679... adalah run lama dan telah disupersede.

Agent 3 branch HEAD 07ad9ed99cbe24492264fb0f5d74c2bc15ed7a23; commit terakhir hanya manifest backup. Controller show tetap tidak memanggil ExamMonitoringScope::allowsParticipant().

### Dampak lintas-agent

Laporan Agent 2 juga menandai exact-role gaps, KAMAD monitoring path, topologi tenant resolver, current-score aggregation, dan immutable report issuance. Untuk Stage 4B, isu yang wajib dibawa ke koordinasi adalah KAMAD tidak boleh mendapat monitoring, Attempt/Answer lock order, scheduler Proktor, dan penggunaan current score pada operasi submit/report.

Status approval tidak berubah:
- Agent 2: NEEDS DESIGN / COORDINATION; approval tetap PENDING.
- Agent 3: PENDING dan CI terbaru FAILURE.
- Agent 4: PENDING; owner belum terkonfirmasi.
- Operational guard dan lock order: NEEDS DESIGN.
- Implementasi source/migration: NOT STARTED.
