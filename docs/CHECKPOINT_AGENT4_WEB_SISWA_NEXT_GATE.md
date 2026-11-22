# CHECKPOINT AGENT 4 — WEB SISWA CONTRACT CLOSURE & OWNERSHIP RECONCILIATION

Tanggal audit: 22 November 2026 (WIB)  
Repository: `zane0184/cbt_november`  
Branch dokumentasi Agent 4: `work/agent4-web-siswa-audit-20261122`  
HEAD dokumentasi saat refresh: `16c7d324ef9d7103bb21f0c5528c3c888ba19568`  
Backup commit sebelum pembaruan checkpoint: `577b1d8e7c8c004c48b00d33a877c94a8d170ff6`  
Status: **AUDIT/DESIGN — CROSS-AGENT CONTRACT PENDING**  
Status Web Siswa: **NOT READY FOR OPERATIONAL EXAM**

## 1. SCOPE DAN KEPUTUSAN PRODUK

Sampai Juni 2027 hanya Web Admin Madrasah, Web Guru dan Web Siswa. Web Super Admin, Android native, Kotlin/Compose/Room/WorkManager/Retrofit, Android API client, APK/AAB, Android Studio dan pipeline distribusi Android ditunda.

Web Siswa berjalan melalui browser Android/iOS dan bukan secure kiosk. QR Code tidak digunakan.

Distribusi: satu ZIP source dipasang terpisah pada banyak madrasah. **Satu instalasi = satu madrasah = satu database akademik sendiri.** Storage, kredensial, konfigurasi, file dan backup terisolasi per instalasi. Tidak ada shared academic database atau sinkronisasi akademik lintas madrasah. Super Admin masa depan hanya lisensi/pembayaran/status layanan dan tidak membuka data akademik.

## 2. AUDIT — REFRESH BRANCH REMOTE

Branch refs di-refresh dari GitHub pada 22 November 2026. Ahead/behind dihitung terhadap `main`. Commit terbaru tiap branch diperiksa secara langsung.

| Peran | Branch | HEAD remote | Commit terbaru | Waktu commit (WIB) | Ahead/behind main |
|---|---|---|---|---|---:|
| Main | `main` | `feab77228f7108f7f7a45325d7f1fb878c07310b` | revert(docs): remove WG-01 DOCX design accidentally added to main | 13:58:45 | — |
| Agent 1 | `work/stage4b-room-session-20261122` | `51571ca54ec922466e6230b4e8137a85336bc024` | docs(stage4b): record decision closure manifest | 18:01:33 | 1216 / 15 |
| Agent 2 | `work/stage4d-account-lifecycle-20261122` | `e90233f79acc67b97c329bc4b33d20abbedd0317` | docs(agent2): record XLSX DOCX import contract status | 18:08:11 | 1351 / 15 |
| Agent 3 | `work/web-guru-foundation-20261122` | `cf4da2c371d4f942bfb9cc8b4eb613431988b531` | docs(backup): record P0 HTTP lifecycle test backups | 18:07:10 | 1173 / 15 |
| Agent 4 docs | `work/agent4-web-siswa-audit-20261122` | `16c7d324ef9d7103bb21f0c5528c3c888ba19568` | docs(agent4): checkpoint Web Siswa next integration gate | 17:10:13 | 12 / 0 |
| Kandidat Agent 4 | `work/import-guru-siswa-implementation-20261122` | `f38ceec7b0653fcf3363eec40f3d4577c323cca8` | fix: preserve nonparticipant 404 while enforcing student role before device checks | 04:00:29 |

Catatan compare:
- Branch Agent 1/2/3/kandidat diverged dari main.
- Compare API mengembalikan maksimum 300 entri file pada branch diverged; angka ahead/behind di atas dicatat, tetapi daftar file compare tidak dianggap inventaris lengkap.
- Commit tip Agent 1 hanya menambah `docs/backup/stage4b-room-session-2026-11-22/MANIFEST_DECISION_CLOSURE_2026-11-22.md`.
- Commit tip Agent 2 hanya menambah `backend-laravel/docs/AGENT2_IMPORT_XLSX_DOCX_CONTRACT_STATUS.md`.
- Commit tip Agent 3 hanya memperbarui `docs/backup/web-guru-exact-role-20261122/MANIFEST.md`.
- Commit tip branch dokumentasi Agent 4 hanya menambah checkpoint pada saat branch HEAD tersebut.
- Commit tip kandidat Agent 4 mengubah `backend-laravel/app/Http/Controllers/Api/V1/ExamAttemptController.php` (17 additions, 15 deletions). Ini tidak membuktikan ownership Agent 4.
- Commit tip main menghapus dokumen WG-01 DOCX dari main.

## 3. OWNERSHIP AGENT 4

**AGENT 4 OWNERSHIP — PENDING CONFIRMATION.**

- Branch dokumentasi Agent 4: `work/agent4-web-siswa-audit-20261122`, dokumentasi saja.
- Kandidat: `work/import-guru-siswa-implementation-20261122` @ `f38ceec7b0653fcf3363eec40f3d4577c323cca8`, belum dikonfirmasi sebagai branch resmi.
- Tidak ada konfirmasi langsung dari owner/koordinator yang menetapkan kandidat sebagai branch implementasi.
- Tidak ada branch implementasi Agent 4 dibuat.
- Kandidat tidak diubah, di-rename, diambil alih, di-merge, di-rebase atau di-cherry-pick.
- Branch dokumentasi berasal dari main dan tidak diperlakukan sebagai basis integrasi source CBT.

Permintaan konfirmasi telah ditambahkan pada Issue #10 melalui komentar **7078273774**:
https://github.com/zane0184/cbt_november/issues/10#issuecomment-7078273774

Komentar tersebut meminta koordinator dan owner menyatakan branch resmi, identitas owner/akun pemberi approval, exact base commit, serta keputusan kontrak Agent 1/2/3. Komentar itu adalah permintaan, bukan approval.

## 4. AUDIT — ISSUE #10 DAN STATUS APPROVAL

Issue #10 tetap OPEN. Delapan komentar yang ada sebelum permintaan Agent 4 terbaru seluruhnya berasal dari akun koordinator `zane0184`; komentar Agent 4 terbaru juga dikirim sebagai permintaan koordinasi melalui akun tersebut. Belum ada jawaban langsung pemilik Agent 1/2/3 yang dapat diverifikasi pada refresh ini.

| Owner | Status yang didukung bukti |
|---|---|
| Agent 1 | PENDING — belum ada keputusan owner atas recovery Proktor/scheduler |
| Agent 2 | NEEDS DESIGN / COORDINATION; approval final PENDING |
| Agent 3 | PENDING — belum ada approval QuestionVersion/snapshot/media |
| Agent 4 ownership | PENDING — branch kandidat belum dikonfirmasi |
| Koordinator | Belum menyetujui basis integrasi atau izin implementasi |

Tidak ada approval agent yang disimpulkan dari komentar koordinator atau commit dokumentasi.

## 5. AUDIT — SOURCE TERKINI DAN BLOB SHA

### 5.1 Agent 2 — backend owner

Branch `work/stage4d-account-lifecycle-20261122` @ `e90233f79acc67b97c329bc4b33d20abbedd0317`.

| Path | Blob SHA |
|---|---|
| `backend-laravel/app/Http/Controllers/Api/V1/ExamController.php` | `c7c62e961f2ff47abee8f3b2468b4e7bc43d1cc9` |
| `backend-laravel/app/Http/Controllers/Api/V1/MediaController.php` | `f852c1b3a7247efdbb0c309fb0413bbe5e3eaa62` |
| `backend-laravel/app/Http/Controllers/Api/V1/ExamAttemptController.php` | `13e8981cca4653ff4e9b9448a50e4d0ec8a74bae` |
| `backend-laravel/app/Http/Controllers/Api/V1/ExamAnswerController.php` | `b4ddec14f6208b3ecde9bfc3e45168a2159e2a9c` |
| `backend-laravel/app/Policies/ExamPolicy.php` | `c05d96ef932fdc81b67336240af4e1726864c3d6` |
| `backend-laravel/routes/api.php` | `2e43c37dd669a26446a44b818c1d2c8420b15aca` |
| `backend-laravel/app/Services/ExamSnapshotService.php` | `d0bfcd70b2777a3884c7f92b7c256f05492645ec` |
| `backend-laravel/app/Models/QuestionVersion.php` | `5f937abf691f96ee62696f7ef01b5adb2d6f0d9a` |
| `backend-laravel/app/Models/ExamQuestionSnapshot.php` | `20a9ef80ed69c5e6281d0fbe592c900178ede2d2` |
| `backend-laravel/app/Services/ScoringService.php` | `1fc74eae97538ada140b9b4c75ccd4388d1a40ad` |
| `backend-laravel/app/Http/Controllers/StudentExamController.php` | `981fd29d24906c158d4dda5889950fdc43f6ec3d` |

**F-01 admission: P0 OPEN**
- `ExamController::show()` memuat `snapshots` jika `$exam->canStartAttempt()`; tidak ada pemeriksaan token yang diterima, Attempt aktif milik peminta, registered device, lease, expiry/revoke dan fencing generation sebagai satu admission gate.
- `ExamPolicy::view()` untuk SISWA memeriksa tenant dan ExamParticipant membership, bukan keseluruhan admission.
- `ExamPolicy::startAttempt()` mendukung resume Attempt `IN_PROGRESS` yang belum expired dan peserta masih eligible setelah Exam close. Attempt baru tetap mengikuti jadwal/status. Kebijakan resume ini harus dipertahankan.
- `ExamAttemptController::store()` mengunci Exam → Participant → Attempt. Jalur Attempt aktif dengan fingerprint sama dikembalikan sebelum validasi token baru; token start baru tidak boleh diasumsikan berlaku sama untuk resume.
- Sanitasi answer-key fields tidak membenarkan snapshot diberikan sebelum admission lengkap.

**F-02 media: P0 OPEN**
- `MediaController::show()` memeriksa tenant, owner, storage disk/path aman, dan `private, no-store`.
- Helper `isReferencedByOwnActiveAttempt()` menghubungkan media snapshot dengan Attempt `IN_PROGRESS`, participant dan user serta window Exam.
- Query helper yang diperiksa tidak membuktikan snapshot media berada di `question_order` Attempt. Tidak ada bukti validasi registered device, lease identity/expiry/revoke dan fencing generation.
- Belum ada bukti bahwa request media paralel yang dimulai sebelum takeover tetapi diproses setelah fencing berubah akan ditolak.

**Answer/revision/receipt**
- Request memuat `client_request_id`, `base_revision`, `answer_payload`, `answer_state`.
- Controller mengunci Attempt → Answer, memeriksa ownership, `question_order`, status, expiry dan fingerprint.
- Same request ID pada answer yang tersimpan memiliki jalur idempotency; stale revision mengembalikan konflik HTTP 409/current answer.
- Tidak ditemukan receipt terpisah yang mengikat request ID, Attempt/snapshot, revision dan bukti verifikasi client. HTTP success belum menjadi kontrak receipt final.
- Lock Attempt → Answer belum direkonsiliasi dengan global parent-first lock order Exam → Participant → Attempt → Answer/Score/Lease.

**Device Lease/Fencing**
- Migration yang ditemukan:
  - `2026_11_05_190001_add_fencing_and_lease_to_exam_attempts_table.php` blob `651a55c6c7e99fce7c355fb8906c0e633fb811c1`.
  - `2026_11_05_190002_create_registered_devices_table.php` blob `456dfdafbcb18bf2f36f9f405354bdf8220c1057`.
  - `2026_11_05_190003_create_device_leases_table.php` blob `61614a0e1a8b9acf505211621d2948afd770dc2f`.
  - `2026_11_20_100002_harden_device_lease_tenant_constraints.php` blob `dcbc51ec6e1b348bc1cdc7267d0ae43a2dbd9807`.
  - `tests/Feature/DeviceLeaseSchemaIntegrityTest.php` blob `2ffc7f6fdcab6112bf9f009aa984fe5a4973ff66`.
- Pada tree branch Agent 2 tidak ditemukan `DeviceLease.php`, `RegisteredDevice.php`, `DeviceLeaseService.php`, atau `EnsureDeviceLease.php`; yang ditemukan adalah migration dan schema integrity test.
- Karena itu lifecycle acquire/heartbeat/release/revoke/takeover/expiry/fencing runtime **NOT VERIFIED**. Schema test bukan bukti runtime.

**Runner Web Siswa**
- `resources/views/student/exams/show.blade.php` blob `7283ca553d787f4505299a306e2bd487884622d3`.
- State runner menggunakan in-memory `Map` untuk answers, revisions, saving, timers dan pending; IndexedDB belum ditemukan.
- Saat HTTP 409, runner menghapus pending item dan dapat mengganti jawaban lokal dengan `current_answer` server tanpa conflict archive aman.
- Submit memanggil flush pending; jalur auto-submit dapat tetap mengirim submit setelah save gagal dengan warning. Ini belum memenuhi submit barrier yang diminta.
- Tidak ada bukti browser E2E untuk reload/crash/offline/conflict.

### 5.2 Agent 3 — QuestionBank/authoring owner

Branch `work/web-guru-foundation-20261122` @ `cf4da2c371d4f942bfb9cc8b4eb613431988b531`.

| Path | Blob SHA |
|---|---|
| `backend-laravel/app/Http/Controllers/Api/V1/ExamAttemptController.php` | `e915bf43118026ef8eace96338fa6cad44ee29e1` |
| `backend-laravel/app/Http/Controllers/Api/V1/QuestionController.php` | `7ded53423dd4d96788123819e8039fd543ce4fba` |
| `backend-laravel/app/Models/QuestionVersion.php` | `5f937abf691f96ee62696f7ef01b5adb2d6f0d9a` |
| `backend-laravel/app/Models/ExamQuestionSnapshot.php` | `20a9ef80ed69c5e6281d0fbe592c900178ede2d2` |
| `backend-laravel/app/Models/MediaAsset.php` | `486a96b525b78257ce46b04f504a74b761daea23` |
| `backend-laravel/app/Models/QuestionOption.php` | `053e303a70d98783ac6fee5d7fe642573c9de381` |
| `backend-laravel/app/Models/QuestionPublication.php` | `eaac380e47eca68dcb328e47a04dd6c10891ec62` |
| `backend-laravel/app/Services/ExamSnapshotService.php` | `d0bfcd70b2777a3884c7f92b7c256f05492645ec` |
| `backend-laravel/app/Services/ScoringService.php` | `1fc74eae97538ada140b9b4c75ccd4388d1a40ad` |
| `backend-laravel/app/Services/ExamMonitoringScope.php` | `11b455954f974d1c60ed5c49700368f8d37c09c1` |

- `QuestionController::ALLOWED_TYPES` berisi tepat enam tipe: MULTIPLE_CHOICE, MULTIPLE_RESPONSE, TRUE_FALSE, MATCHING, SHORT_ANSWER, ESSAY.
- `QuestionVersion` menyimpan identity/version, type, stem, explanation, options dan relasi media. Model yang diperiksa belum membuktikan immutability enforcement pada setiap perubahan.
- `ExamSnapshotService` menyalin stem, explanation, options, media dan metadata question_type/difficulty/cognitive_level.
- Source snapshot yang diperiksa tidak menyalin `accepted_answers` ke metadata, sedangkan `ScoringService::evaluateShortAnswer()` membaca `snapshot.metadata.accepted_answers`.
- Maka SHORT_ANSWER tetap **BLOCKED** sampai accepted answers masuk immutable snapshot (atau referensi versioned immutable yang disetujui) dan test scoring exact-SHA membuktikannya.
- ESSAY tetap manual review.
- `ExamQuestionSnapshot` menyimpan snapshot_options, snapshot_media, metadata; field identity/schema version final belum disetujui.
- Source `ExamAttemptController::show()` Agent 3 yang pernah diaudit hanya authorize monitor Exam dan belum membuktikan room scope melalui `ExamMonitoringScope`. Ini risiko lintas ownership yang perlu Agent 1/3 selesaikan; bukan patch Agent 4.
- `ExamMonitoringScope::participantQuery()` pada blob saat ini mengembalikan semua peserta untuk exam author atau KAMAD; jalur Proktor lain dibatasi confirmed room assignments. KAMAD bypass perlu keputusan owner/koordinator sesuai keputusan portal.

### 5.3 Agent 1 — Session/Room/Proktor owner

Branch `work/stage4b-room-session-20261122` @ `51571ca54ec922466e6230b4e8137a85336bc024`.

| Path | Blob SHA |
|---|---|
| `backend-laravel/app/Services/ExamSessionLifecycleService.php` | `8e34152afd817f30cc2f81b5f25fc450571463ac` |
| `backend-laravel/app/Services/ExamRoomSessionService.php` | `e514fa9f751584e54af1125d48b5673e013f2339` |
| `backend-laravel/app/Services/ExamProctorAssignmentService.php` | `c968ce68c0decace1c35a0ccb3b2b4449c057656` |
| `backend-laravel/app/Services/ExamMonitoringScope.php` | `11b455954f974d1c60ed5c49700368f8d37c09c1` |
| `backend-laravel/app/Models/ExamSession.php` | `d1d41ad8c7063edbd6b17e094a3b39bdec71664a` |
| `backend-laravel/app/Models/ExamRoom.php` | `8f14048505606fce69dfa2511a3472e493f75877` |
| `backend-laravel/app/Models/ExamProctorAssignment.php` | `88a1c2319200342eb2dc2a5993e95a8b6a32dbfc` |
| `backend-laravel/database/migrations/2026_11_22_110001_create_exam_room_session_scope.php` | `9551b1dd79397bd0432f03eefc970852b675fd25` |
| `docs/KONTRAK_INTEGRASI_AGENT1_AGENT2_AGENT3_EXAM_SESSION_2026-11-22.md` | `80bdb8c678965554422fc492e4ebe767e2b73d55` |

Temuan:
- Migration menggunakan unique `(tenant_id, exam_id, session_number)`; belum menegakkan satu session per Exam. Kolom `is_operational`/operational guard tidak ditemukan pada migration ini.
- `ExamSessionLifecycleService::synchronize()` mengunci session, mengubah status ke COMPLETED ketika `ends_at <= now`, lalu assignment terkait dapat diselesaikan/di-expire berdasarkan status session atau waktu. Scheduler ini tidak membaca Attempt IN_PROGRESS sebelum menyelesaikan assignment.
- `ExamRoomSessionService::cancelSession()` melakukan pemeriksaan Attempt aktif sebelum revoke assignment dan release mapping; ini guard parsial untuk cancel, tetapi tidak menutup gap scheduler.
- `ExamProctorAssignmentService::assign()` mengunci scope Exam/SessionRoom/Session kemudian User; lock order global lintas service masih harus disepakati.
- `ExamMonitoringScope` membatasi Proktor ke peserta di confirmed room assignment, tetapi memiliki jalur luas untuk author dan KAMAD seperti disebut di atas.
- Desain satu Exam–satu ExamSession operasional dan recovery Proktor masih belum disetujui lintas owner. Jangan menentukan `recovery_until` di Agent 4.

### 5.4 Kandidat Agent 4

Branch `work/import-guru-siswa-implementation-20261122` @ `f38ceec7b0653fcf3363eec40f3d4577c323cca8`.

Source blob yang diperiksa:
- ExamController `bf373858869b470b235e2256e5be6169fbb1f561`.
- MediaController `f852c1b3a7247efdbb0c309fb0413bbe5e3eaa62`.
- ExamAttemptController `808702092c94b386657fe027f60758a625d5bb93`.
- ExamAnswerController `b4ddec14f6208b3ecde9bfc3e45168a2159e2a9c`.
- ExamPolicy `121fa8871dd958b25709cae567adc1d4e6c549b3`.
- routes/api.php `4a9faa1e2bb0485570f30c199ccfbea7aa3a26ff`.
- StudentExamController `981fd29d24906c158d4dda5889950fdc43f6ec3d`.
- Student runner Blade `7283ca553d787f4505299a306e2bd487884622d3`.

Temuan:
- Candidate `ExamController::show()` masih melepas snapshot melalui `canStartAttempt()`; F-01 tidak tertutup.
- Candidate `ExamAttemptController` memiliki `requireSingleStudent()` pada jalur store, tetapi resume aktif masih dapat dikembalikan sebelum validasi token. Method show mengizinkan exact-one SISWA atau GURU dengan jalur berbeda; ini bukan bukti keseluruhan admission aman.
- Candidate tree tidak memiliki `ExamSessionLifecycleService`, `ExamRoomSessionService`, `ExamMonitoringScope` atau migration Stage 4B yang ditemukan pada Agent 1.
- Tidak ditemukan `routes/student.php` atau Student Livewire class. Yang ada adalah `StudentExamController` dan Blade views.
- Tidak ditemukan model/service/middleware Device Lease; hanya migrations/schema test.
- Tidak ada bukti kandidat ini telah dikonfirmasi oleh owner Agent 4.

## 6. AUDIT — DOKUMEN AGENT 4 DAN BACKUP

Sebelas dokumen Agent 4 pada branch dokumentasi telah di-fetch ulang dan dibaca:
1. `AGENT4_WEB_SISWA_ADMISSION_MEDIA_THREAT_MODEL.md`
2. `AGENT4_BACKEND_CONTRACT_REQUEST_AGENT2.md`
3. `AGENT4_QUESTION_MEDIA_CONTRACT_REQUEST_AGENT3.md`
4. `AGENT4_ROOM_RECOVERY_CONTRACT_REQUEST_AGENT1.md`
5. `AGENT4_INDEXEDDB_TRANSACTIONAL_SCHEMA_SPEC.md`
6. `AGENT4_WEB_SISWA_SUBMIT_BARRIER_SPEC.md`
7. `AGENT4_WEB_SISWA_CONTRACT_RECONCILIATION.md`
8. `AGENT4_INDEXEDDB_OFFLINE_RECOVERY_DESIGN.md`
9. `AGENT4_WEB_SISWA_TEST_MATRIX.md`
10. `CHECKPOINT_AGENT4_WEB_SISWA.md`
11. `CHECKPOINT_AGENT4_WEB_SISWA_NEXT_GATE.md`

Dokumen-dokumen request/desain lama merupakan snapshot audit dan proposal pada HEAD sebelumnya. Blob source utama yang dicantumkan tetap sama pada refresh ini; HEAD branch Agent 1/2/3 bergerak melalui commit dokumentasi/manifest saja. Dokumen tersebut tidak dianggap sebagai approval atau kontrak final.

Pemeriksaan tree branch dokumentasi sebelum backup menemukan tidak ada manifest backup khusus dokumen Agent 4. Ada file backup lain untuk workflow lama, tetapi bukan backup checkpoint Agent 4.

Backup byte-exact sebelum memperbarui checkpoint:
- Original: `docs/CHECKPOINT_AGENT4_WEB_SISWA_NEXT_GATE.md`.
- Original blob SHA: `c6bdd306bbb7cd41e5075ff1095b24d3e1e891e7`.
- Backup: `docs/backup/agent4-contract-closure-2026-11-22/CHECKPOINT_AGENT4_WEB_SISWA_NEXT_GATE.md.before`.
- Backup blob SHA: `c6bdd306bbb7cd41e5075ff1095b24d3e1e891e7`.
- Manifest: `docs/backup/agent4-contract-closure-2026-11-22/MANIFEST.md`.
- Backup commit: `577b1d8e7c8c004c48b00d33a877c94a8d170ff6`.
- Verifikasi: PASS — Git blob SHA original dan backup identik. Backup dibuat sebelum perubahan checkpoint.

## 7. DESAIN — REKONSILIASI KONTRAK

Tidak ada kontrak baru yang memperoleh approval. Maka desain IndexedDB dan Submit Barrier tetap **proposal client-side**, belum dapat direkonsiliasi menjadi implementasi terhadap backend yang disetujui.

### 7.1 Admission F-01
Acceptance criteria tetap:
- authenticated, active, exact-one SISWA;
- installation/database tenant benar;
- ExamParticipant eligible;
- waktu server dan Exam schedule;
- token sah untuk Attempt baru;
- Attempt owner/status/expiry sah;
- resume aktif tidak membuat Attempt baru dan tetap diizinkan setelah close hanya jika IN_PROGRESS, belum expired dan eligible;
- device/lease/registered device/fencing sah;
- snapshot dan question_order canonical.
Sebelum admission hanya metadata aman. Tidak boleh ada snapshot, answer key, accepted answers, rubric, scoring guide, explanation privat atau data peserta lain.

**Belum disetujui:** runtime gate, token behavior untuk resume, response/error, audit event dan payload canonical Agent 2.

### 7.2 Media F-02
Acceptance criteria tetap mengikat media ke installation/tenant, owner, QuestionVersion, snapshot Attempt, question_order, Attempt owner/status, device, lease, expiry/revoke dan fencing generation. Request paralel setelah takeover harus ditolak bila generation stale. Cache policy harus ditetapkan Agent 2; source saat ini memakai `private, no-store`, tetapi belum ada approval kontrak final.

**Belum disetujui:** canonical media authorization contract dan audit/error behavior Agent 2.

### 7.3 QuestionVersion/ExamSnapshot
Tepat enam tipe soal saja. Tidak ada tipe ketujuh atau opsi E. SHORT_ANSWER BLOCKED sampai accepted answers immutable dan terbukti digunakan scoring. ESSAY manual review. Browser tidak boleh menerima accepted answers, key, rubric atau scoring guide.

**Belum disetujui:** QuestionVersion immutability, snapshot identity/schema, canonical order, public/private payload, media role/sequence, RTL/harakat, formula dan sanitization Agent 3.

### 7.4 Room/Proktor/recovery
Satu Exam–satu ExamSession operasional dan banyak room tetap desain yang diminta. Session/room bukan admission gate. Proktor assignment sementara dan room-scoped. Attempt/Answer/Score/history tidak boleh hilang akibat perubahan room/session.

**Belum disetujui:** scheduler, Attempt recovery state, dukungan Proktor setelah session end, assignment expiry/extension, recovery window dan audit Agent 1/2. Agent 4 tidak menetapkan `recovery_until`.

### 7.5 IndexedDB
Enam object store konseptual tetap: `attempt_context`, `answers`, `sync_queue`, `server_receipts`, `conflicts`, `local_meta`. Semua record scoped installation/tenant, user, Exam, Attempt, device dan snapshot.

Write answer + queue harus atomic sebelum network send. Retry memakai request ID dan payload identik. SERVER_ACCEPTED hanya setelah receipt resmi Agent 2 diverifikasi. HTTP 409 harus mengarsipkan local/server/base revision/request ID dan block submit. Quota/transaction failure tidak boleh fallback ke memory atau mengklaim tersimpan. Credential lease tidak disimpan sebelum security review.

Karena receipt/revision/lease contract Agent 2 belum disetujui, nama field dan schema di dokumen IndexedDB tetap konseptual.

### 7.6 Submit Barrier
Submit client harus ditahan bila local write belum commit, queue pending, request in-flight/result unknown, conflict terbuka, receipt belum diverifikasi, Attempt tidak sah atau lease/fencing tidak current. Response submit hilang bukan sukses; gunakan lookup/retry idempotent sesuai kontrak Agent 2. Waktu server adalah sumber resmi.

**Belum disetujui:** submit idempotency, definitive response, lost-response lookup, answer-vs-submit-vs-Force Submit race, expiry dan Force Submit Agent 2.

## 8. OWNERSHIP MAP

| Area | Owner keputusan/implementasi | Status |
|---|---|---|
| Web Siswa UI, runner, IndexedDB, offline/recovery UX, browser E2E | Agent 4 | Ownership branch PENDING |
| Auth, tenant, F-01 admission, Attempt, answer receipt/conflict, media F-02, lease/fencing, submit/Force Submit/expiry/scoring | Agent 2 | NEEDS DESIGN / PENDING |
| QuestionBank, QuestionVersion, snapshot authoring, media authoring, accepted answers, six question types | Agent 3 | PENDING |
| ExamSession, Room, Placement, Proktor assignment/lifecycle/monitoring scope | Agent 1 | PENDING |
| Lock order, close/resume, recovery scheduler/Proktor | Agent 1 + Agent 2 + koordinator | NEEDS DESIGN / PENDING |
| Integration base, ownership, final approval and coding permission | Koordinator | PENDING |

Agent 4 adalah consumer kontrak backend. Tidak membuat API/DTO/HTTP status/enum/lease service atau schema backend sendiri.

## 9. IMPLEMENTASI

**NONE — DOCUMENTATION ONLY.**
- Tidak ada source runtime, Blade runner, JavaScript, route, controller, policy, model, service, middleware, migration, schema, database atau config diubah.
- Tidak ada branch Agent 1/2/3/kandidat yang diubah.
- Tidak ada branch implementasi Agent 4 dibuat.
- Tidak ada merge, rebase, cherry-pick, PR merge atau deployment.
- Tidak ada migration atau database produksi disentuh.
- Satu checkpoint dokumentasi akan diperbarui setelah backup byte-exact; backup manifest dan file `.before` disimpan di branch dokumentasi.

## 10. PENGUJIAN DAN STATUS MATRIX

Audit source dan dokumen dilakukan secara read-only. Test runtime berikut belum dijalankan oleh Agent 4 pada exact source SHA yang sama:

| Test group | Status |
|---|---|
| F-01 admission adversarial | NOT RUN |
| F-02 media authorization/adversarial | NOT RUN |
| Lease acquire/heartbeat/release/revoke/takeover/expiry | BLOCKED / NOT RUN |
| Stale fencing + concurrent takeover | BLOCKED / NOT RUN |
| Answer idempotency/receipt/revision | NOT RUN |
| Same request ID with different payload | NOT RUN |
| HTTP 409 conflict archive | NOT RUN |
| IndexedDB atomic transaction | NOT RUN |
| IndexedDB quota/upgrade/blocked transaction | NOT RUN |
| Multi-tab writer coordination | NOT RUN |
| Reload/crash/offline→online recovery | NOT RUN |
| Submit/expiry/Force Submit race | NOT RUN |
| Browser E2E Android Chrome/iOS Safari/desktop | NOT RUN |
| Room/Proktor recovery integration | NOT RUN |
| Disposable MySQL 8/InnoDB integration | NOT RUN |
| Multi-device integration | BLOCKED / NOT RUN |

Tidak ada hasil PASS yang diklaim. CI yang dicatat di Issue #10 berasal dari commit/source lama atau runner tidak tersedia; tidak dipakai sebagai bukti test exact HEAD saat ini.

## 11. CHECKPOINT — KEPUTUSAN DAN NEXT ACTION

### Keputusan yang didukung source saat ini
- Resume Attempt IN_PROGRESS yang belum expired dan participant masih eligible setelah Exam close didukung `ExamPolicy::startAttempt()`; jangan mengubahnya dari UI.
- Attempt baru setelah close/di luar jadwal ditolak sesuai policy.
- Session/room bukan admission gate; jadwal Exam dan waktu server tetap acuan.
- Media route menggunakan private/no-store, tetapi authorization lease/fencing/question_order belum lengkap.
- QuestionBank saat ini membatasi enam tipe soal.
- Candidate Agent 4 bukan branch resmi sampai owner/koordinator mengonfirmasi.

### NEEDS DESIGN / PENDING
- Agent 2: F-01, F-02, lease/fencing runtime, answer revision/idempotency/receipt/conflict, submit/Force Submit/expiry dan lock order.
- Agent 3: QuestionVersion immutability, ExamSnapshot identity/schema/order, media authoring, accepted answers, Arabic/RTL/formula.
- Agent 1: one-session invariant, scheduler, room/proctor lifecycle dan recovery.
- Agent 1/2: close/resume dan lock order/recovery boundary.
- Koordinator: single-install/single-madrasah/single-database topology, official Agent 4 branch, integration base, coding permission.

### Next action
1. Menunggu jawaban eksplisit pada Issue #10, terutama komentar 7078273774.
2. Catat status setiap jawaban hanya pada butir yang disetujui eksplisit owner.
3. Bila owner memberi kontrak, periksa exact source/blob/CI SHA dan reconcile per bagian; jangan langsung coding.
4. Koordinator menetapkan branch resmi Agent 4 dan exact base commit.
5. Koordinator menyetujui seluruh kontrak dan memberi izin implementasi terpisah.
6. Baru lakukan backup/preflight pada branch resmi dan uji integrasi pada disposable MySQL 8+ serta browser E2E.
7. Jangan mengubah runner sebelum seluruh gate dipenuhi.

## 12. STATUS AKHIR

`AUDIT/DESIGN — CROSS-AGENT CONTRACT PENDING`  
`AGENT 4 OWNERSHIP — PENDING CONFIRMATION`  
`BLOCKED — ADMISSION/MEDIA BACKEND CONTRACT PENDING`  
`BLOCKED — DEVICE LEASE/FENCING CONTRACT PENDING`  
`BLOCKED — ANSWER RECEIPT/CONFLICT/SUBMIT CONTRACT PENDING`  
`BLOCKED — QUESTIONVERSION/SNAPSHOT/MEDIA AUTHORING CONTRACT PENDING`  
`BLOCKED — ROOM/PROCTOR RECOVERY CONTRACT PENDING`

**Web Siswa: NOT READY FOR OPERATIONAL EXAM.**

Tidak ada izin implementasi pada tahap ini.


## 13. FINAL REFRESH — branch, source, owner response (22 November 2026, 18:20 WIB)

This section supersedes earlier branch HEAD tables in this checkpoint where the refs have moved. Remote HEADs at this refresh:

| Branch | Current HEAD | Latest commit | Ahead/behind main |
|---|---|---|---:|
| main | `feab77228f7108f7f7a45325d7f1fb878c07310b` | revert WG-01 DOCX design | — |
| Agent 1 | `51571ca54ec922466e6230b4e8137a85336bc024` | docs(stage4b): record decision closure manifest | 1216 / 15 |
| Agent 2 | `462cadc96a95d091bd89819dbc106b4fbd67e1cd` | docs(agent2): checkpoint web-only next gate | 1352 / 15 |
| Agent 3 | `a1f34b010dd5ad64c59a359f7e8d0f49d491eb82` | backup(web-guru): preserve checkpoint before cross-agent contract addendum | 1176 / 15 |
| Agent 4 docs | `3225119ccf3b62f36d6593f7e537cf58adfe22cd` before this update | docs(agent4): refresh Web Siswa contract closure checkpoint | 14 / 0 |
| Candidate Agent 4 | `f38ceec7b0653fcf3363eec40f3d4577c323cca8` | fix: preserve nonparticipant 404 while enforcing student role before device checks | 930 / 15 |

Exact changed blob paths vs main from Git tree comparison:
- Agent 1: 923 added, 0 modified, 9 removed; source 246, tests 79, migrations 64, workflows 5, docs 65, backups 469, other 4.
- Agent 2: 1004 added, 0 modified, 9 removed; source 247, tests 80, migrations 66, workflows 5, docs 69, backups 542, other 4.
- Agent 3: 922 added, 0 modified, 9 removed; source 257, tests 78, migrations 64, workflows 6, docs 60, backups 462, other 4.
- Agent 4 docs: 13 added, 0 modified, 0 removed; docs 11, backups 2. No runtime source/test/migration/workflow changes.
- Candidate Agent 4: 803 added, 0 modified, 9 removed; source 233, tests 75, migrations 62, workflows 5, docs 40, backups 393, other 4.

The compare endpoint caps returned file lists for diverged branches; counts above were calculated from complete recursive Git trees, not the truncated compare file list. Latest tip commits for Agent 1/2/3 are docs/backup-only; candidate HEAD remains the older source commit. No PR/merge/rebase/cherry-pick occurred.

### Issue #10 response status
- Coordinator topology decision comment **7078261415** confirms one separate installation/database/storage/credential/config/file/backup per madrasah; tenant_id remains defense-in-depth inside the installation; no cross-madrasah academic DB/sync. This is a coordinator decision, not Agent 2 technical approval.
- Agent 4 request comment **7078253282** and refresh request **7078273774** ask owners for branch ownership and explicit contract decisions.
- Comment **7078306561** records a technical review marked **NEEDS DESIGN** for QuestionVersion/snapshot/media. Because it is posted from coordinator account `zane0184`, not a separately verifiable owner identity, it is not treated as final Agent 3 approval.
- No independently verifiable Agent 1 or Agent 2 owner response/approval is present in the latest Issue #10 comments. Agent 2 checkpoint status is BLOCKED — EXACT-ROLE/SECURITY DESIGN PENDING; its source runtime baseline is still `74bf7178...`.
- Agent 4 official branch ownership remains PENDING; candidate remains unconfirmed.

### Backup and documentation update
- Checkpoint before refresh was backed up byte-exact at `docs/backup/agent4-contract-closure-2026-11-22/CHECKPOINT_AGENT4_WEB_SISWA_NEXT_GATE.md.before`, blob `c6bdd306bbb7cd41e5075ff1095b24d3e1e891e7`, manifest `MANIFEST.md`.
- Six contract/design docs were backed up byte-exact at the same directory with suffix `.before`; supplemental manifest `MANIFEST_SUPPLEMENT.md` lists original/backup blob SHA and UTF-8 byte length. All six backup blob SHAs were verified identical before update.
- This update is documentation-only. Source runtime remains untouched.

### Updated approval matrix
| Contract | Owner status |
|---|---|
| Agent 4 official branch | PENDING CONFIRMATION |
| F-01 admission / F-02 media | Agent 2 PENDING |
| Device Lease/Fencing | Agent 2 PENDING / runtime NOT VERIFIED |
| Answer revision/receipt/conflict | Agent 2 PENDING |
| Submit/Force Submit/expiry | Agent 2 PENDING |
| QuestionVersion/snapshot/media | NEEDS DESIGN (technical review comment; final owner approval pending) |
| Room/Proktor/recovery | Agent 1 PENDING; Agent 2 coordination pending |
| Integration base and implementation permission | Koordinator PENDING |

### Test status
No tests were executed by Agent 4 in this refresh. F-01/F-02 adversarial, lease/fencing runtime, Answer receipt/conflict, IndexedDB, multi-tab, offline/reload/crash, browser E2E, submit race, room recovery and disposable MySQL integration remain NOT RUN/BLOCKED. No PASS claim.

Final state: **AUDIT/DESIGN — CROSS-AGENT CONTRACT PENDING**; Web Siswa **NOT READY FOR OPERATIONAL EXAM**. No implementation permission.


## 14. REMOTE REFRESH — OWNERSHIP / ISSUE #10 / CI EXACT-SHA (22 NOVEMBER 2026)

### AUDIT — remote refs

HEAD remote sebelum commit dokumentasi refresh ini:

| Owner/jenis | Branch | HEAD aktual | Ahead/behind `main` |
|---|---|---|---:|
| Agent 1 | `work/stage4b-room-session-20261122` | `51571ca54ec922466e6230b4e8137a85336bc024` | 1216 / 15 |
| Agent 2 | `work/stage4d-account-lifecycle-20261122` | `462cadc96a95d091bd89819dbc106b4fbd67e1cd` | 1352 / 15 |
| Agent 3 | `work/web-guru-foundation-20261122` | `6db04429613537c8219e6d8d873332d25f8c1ced` | 1180 / 15 |
| Agent 4 dokumentasi | `work/agent4-web-siswa-audit-20261122` | `f7166abff090d1cc8cdd49d0ebbea6509d5d0886` | 16 / 0 |
| Kandidat Agent 4 | `work/import-guru-siswa-implementation-20261122` | `f38ceec7b0653fcf3363eec40f3d4577c323cca8` | 930 / 15 |
| Main | `main` | `feab77228f7108f7f7a45325d7f1fb878c07310b` | — |

Branch owner dan kandidat divergen. Cross-compare: Agent1→Agent2 234/98; Agent1→Agent3 207/243; Agent1→Agent4 docs 31/1216; Agent1→candidate 80/366; Agent2→Agent3 207/379; Agent2→Agent4 docs 31/1352; Agent3→Agent4 docs 31/1180 (ahead/behind). Dokumentasi Agent4 berbasis main dan bukan basis runtime. Tip commits yang diperiksa tercatat dari `zane0184`; identitas owner agent terpisah belum diverifikasi.

### AUDIT — Issue #10 dan keputusan owner

Issue #10 OPEN, 14 komentar; semua author login yang dikembalikan adalah `zane0184`. Komentar terbaru `7078306561` menyatakan technical review Agent3 **NEEDS DESIGN** untuk QuestionVersion/snapshot/media, tetapi ditulis oleh akun koordinator dan secara eksplisit bukan approval final. Komentar `7078261415` adalah keputusan koordinator mengenai instalasi/database terpisah per madrasah; bukan approval teknis Agent2. Komentar `7078273774` adalah permintaan konfirmasi owner. Tidak ada jawaban langsung owner yang dapat diverifikasi.

`AGENT 4 OWNERSHIP — PENDING CONFIRMATION`. Kandidat `f38ceec...` tidak dikonfirmasi, tidak diubah dan tidak boleh dijadikan basis implementasi. Tidak ada base commit/izin coding.

### AUDIT — source findings

- Agent1 migration `9551b1dd...`: unique `(tenant_id, exam_id, session_number)`, belum one operational session per Exam. Scheduler `8e34152...` menyelesaikan session/assignment berdasar waktu tanpa guard Attempt IN_PROGRESS. Proktor recovery window belum ditetapkan.
- Agent2 ExamController `c7c62e...`: snapshot masih dilepas berdasar `canStartAttempt()`; F-01 P0 OPEN. MediaController `f852c1...`: `question_order`, lease dan fencing belum lengkap; F-02 P0 OPEN. AttemptController `13e898...`: parent lock Exam→Participant→Attempt; resume Attempt aktif valid setelah close didukung. AnswerController `b4ddec...`: lock Attempt→Answer; durable receipt belum terbukti. Device Lease runtime model/service/middleware tidak ditemukan. ExamPolicy `c05d96...` masih non-exact role/KAMAD.
- Agent3 QuestionController `7ded534...` membatasi enam tipe; QuestionVersion `5f937a...` dan SnapshotService `d0bfcd...` tidak menyimpan/meng-copy accepted_answers; ScoringService `1fc74e...` membacanya dari snapshot metadata. SHORT_ANSWER BLOCKED; ESSAY manual review. AttemptController `e915bf...` belum membuktikan room scope.

### DESAIN — status

| Subkontrak | Status |
|---|---|
| Agent4 ownership/branch/base | PENDING |
| F-01 admission | PENDING — P0 OPEN |
| F-02 media | PENDING — P0 OPEN |
| Device Lease/Fencing | PENDING / NOT VERIFIED |
| Answer receipt/conflict | NEEDS DESIGN / PENDING |
| Submit/expiry/Force Submit | PENDING |
| QuestionVersion/snapshot/media | NEEDS DESIGN / PENDING; koordinator review bukan owner approval |
| SHORT_ANSWER | BLOCKED |
| Room/Proktor/recovery | PENDING |
| Integration base/coding permission | PENDING |

### PENGUJIAN

CI exact-SHA untuk branch heads dan source baseline yang diperiksa tidak dapat diverifikasi melalui connector: combined statuses kosong dan tidak ada PR-triggered workflow run yang dikembalikan. Run historis di checkpoint Agent1/2/3/kandidat bukan PASS untuk exact HEAD saat ini. Semua acceptance test Web Siswa tetap NOT RUN/BLOCKED.

### IMPLEMENTASI

Dokumentasi saja. Tidak ada source runtime, route, API, policy, model, service, middleware, migration, schema, database, konfigurasi, branch Agent1/2/3/kandidat, merge/rebase/cherry-pick, PR atau deployment yang diubah. Backup byte-exact checkpoint dan reconciliation tercatat pada `docs/backup/agent4-contract-closure-2026-11-22/MANIFEST_REMOTE_REFRESH_F7166AB.md`.

### NEXT GATE

Menunggu respons langsung owner Agent1/2/3 dan konfirmasi owner Agent4. Setelah itu verifikasi exact source/blob/CI; koordinator menetapkan branch resmi/base/izin coding; lakukan preflight disposable MySQL8 dan browser E2E hanya setelah semua kontrak disetujui.

Status: `BLOCKED — CROSS-AGENT CONTRACT PENDING`; Web Siswa `NOT READY FOR OPERATIONAL EXAM`.

## 15. KOREKSI HEAD BRANCH DOKUMENTASI AGENT 4 SETELAH REFRESH

Tabel pada Bagian 14 mencatat HEAD Agent 4 dokumentasi `f7166ab...` saat source/Issue #10 pertama kali di-refresh, sebelum rangkaian backup dan update dokumen Agent 4 pada sesi ini. Setelah itu branch dokumentasi bergerak hanya melalui commit dokumentasi berikut:

- Backup manifest remote refresh: `d92b96e53da2e8f8df49aed58e716fba48253d2c`.
- Pembaruan reconciliation: `2eac62da79346386b0032da0a810298b1258c772` (blob `1ccfd2a87d0b4031243d2535bdbbcc27c99b7245`).
- Pembaruan checkpoint Bagian 14: `0fbdaeee11ef29fa1d146efbd84b33234ced62d9` (blob `ea06e3abb87a082ca70cf78c3a3fa42885d29038`).

HEAD sebelum koreksi akhir ini: `0fbdaeee11ef29fa1d146efbd84b33234ced62d9`; branch dokumentasi 21 ahead / 0 behind terhadap `main`. Commit `0fbdaee` hanya mengubah `docs/CHECKPOINT_AGENT4_WEB_SISWA_NEXT_GATE.md`. Tidak ada source runtime/test/migration/schema/database yang berubah. Commit koreksi akhir ini juga dokumentasi saja.

Backup checkpoint sebelum koreksi ini diverifikasi byte-exact: original blob `ea06e3abb87a082ca70cf78c3a3fa42885d29038`; backup `docs/backup/agent4-contract-closure-2026-11-22/CHECKPOINT_AGENT4_WEB_SISWA_NEXT_GATE.md.before-final-head-correction.md` blob `ea06e3abb87a082ca70cf78c3a3fa42885d29038`. Manifest: `docs/backup/agent4-contract-closure-2026-11-22/MANIFEST_FINAL_HEAD_CORRECTION.md` (blob `26c1df1694fdde7a9ab1a776d166b6fb8dd759c5`).

## 16. REFRESH KEPUTUSAN KOORDINATOR — KAMAD FROZEN / PROKTOR AKUN GURU (22 NOVEMBER 2026)

### AUDIT — remote dan checkpoint terkini

HEAD remote yang di-refresh pada tahap ini:

| Komponen | Branch | HEAD aktual | Catatan tip commit |
|---|---|---|---|
| Agent 1 | `work/stage4b-room-session-20261122` | `69f16da92452fd28a85aec2a8239c5d5ba9a10dd` | Manifest backup keputusan koordinator; checkpoint menyebut source runtime baseline `87b1d6210d3d175f23b2f5aa215afa293c348563`. |
| Agent 2 | `work/stage4d-account-lifecycle-20261122` | `09b0ee42555b58c520b6ce3b46243b684e5a9d69` | Checkpoint exact-role/installation/Proktor; runtime baseline tetap `74bf7178f46673e9be90523671490c0749034acd`. |
| Agent 3 | `work/web-guru-foundation-20261122` | `f13e2adaf284e2a1d727ea23b7c9788f793e3ec1` | Checkpoint P0 HTTP/Livewire; test source `6334dc9abd07a1441c8c02f3f7a6cc5c452ead36`. |
| Agent 4 dokumentasi | `work/agent4-web-siswa-audit-20261122` | `edec6c2bddfda5563c907827db7d1ae77775c086` | Dokumentasi saja; bukan basis implementasi. |
| Kandidat Agent 4 | `work/import-guru-siswa-implementation-20261122` | `f38ceec7b0653fcf3363eec40f3d4577c323cca8` | Belum dikonfirmasi owner; tidak disentuh. |
| Main | `main` | `feab77228f7108f7f7a45325d7f1fb878c07310b` | Titik pembanding, bukan basis integrasi. |

Branch Agent 1/2/3 tip terbaru adalah dokumentasi; source runtime baseline yang dirujuk checkpoint owner belum berubah. Compare terhadap main saat refresh: Agent 1 1223 ahead/15 behind; Agent 2 1354/15; Agent 3 1185/15; Agent 4 docs 24/0; kandidat 930/15. Seluruh branch owner/kandidat tetap divergen. Commit tip diperiksa tercatat atas akun `zane0184`; tidak membuktikan owner teknis terpisah.

Checkpoint/source owner yang dibaca: Agent 1 checkpoint blob `9d0a928b8e3b9f3655cf5e91eacf663129777c87`; Agent 2 checkpoint blob `a53fd564ddf7bc48648b7c6c68d330248bad177c`; Agent 2 audit report blob `a503e790f08e9cd684e36e9006d4e3d55bb3c3f3`; Agent 3 checkpoint blob `c75fa3a80a42e6782fe1920578c41466be84bb8d`; Agent 4 checkpoint sebelum addendum blob `ec369bc77e7040729cfac5c3314ab14b0b02dd42`.

### AUDIT — keputusan produk koordinator dan owner response

Issue #10 OPEN dengan 15 komentar. Komentar terbaru yang relevan adalah `7078539953`, author login `zane0184`, berjudul “Keputusan Koordinator — portal aktif, KAMAD frozen, Proktor memakai akun Guru”. Ini keputusan produk final koordinator, bukan approval owner Agent 2/3. Komentar itu meminta konfirmasi teknis langsung dari Agent 2, Agent 3 dan Agent 4. Seluruh komentar Issue #10 yang diperiksa tetap berasal dari `zane0184`; belum ada respons owner terpisah yang dapat diverifikasi.

Keputusan yang wajib dipakai Agent 4:
- Portal aktif hanya ADMIN_MADRASAH, GURU, SISWA.
- KAMAD frozen: tidak ada login, portal, dashboard, route, permission, policy, menu, monitoring, bypass atau approval CBT. Histori jabatan/appointment tetap dipertahankan sebagai data tanpa hak akses.
- Proktor adalah assignment operasional sementara untuk akun tepat satu role GURU. Guru login dengan akun Guru sendiri; menu Proktor berada di Web Guru. Tidak ada akun/login/credential/portal Proktor terpisah dan tidak ada role permanen PROKTOR.
- SISWA hanya peserta ujian dan tidak boleh menjadi identitas Proktor.
- Assignment Proktor harus aktif, time-bounded, revocable, tenant/installation/Exam/Session/Room scoped dan seluruh tindakan mencatat identitas Guru.
- Proktor tidak otomatis mendapat jawaban, nilai, bank soal, konfigurasi Exam, Force Submit atau assignment Guru lain.
- Web Siswa tidak boleh menyediakan fungsi, route, menu, API client surface, monitoring, assignment management, takeover atau Force Submit untuk Proktor. Proktor Guru tidak boleh melewati exact-role SISWA melalui endpoint peserta.

### AUDIT — bukti backend relevan dan blocker

Agent 2 audit report menyebut source runtime baseline `74bf7178...` masih memiliki jalur KAMAD: AuthWebController blob `b8e04102858652c2fe9042f8f680c511fcbccf63`; API AuthController `ed0d7c9f51de4190c7f150b92c27a89e93703b40`; LoginIdentityResolver `c1f56029da5b8fdb5d7f80380269637b7d1c3ebe`; RolePermissionBootstrapper `3eea44756b0fec6e207382a45952579a23731b95`; ExamPolicy `c05d96ef932fdc81b67336240af4e1726864c3d6`; ExamAttemptPolicy `00a3a4119f3983de6d940fd8f352a541336c9e12`; ExamMonitoringScope `11b455954f974d1c60ed5c49700368f8d37c09c1`. Report menyatakan KAMAD masih dapat login Web/API, mendapat bootstrap permission dan dikenali policy/monitoring. Status **P0 NON-COMPLIANT** sampai Agent 2 menutup seluruh jalur dengan bukti.

`StudentExamController.php` blob `981fd29d24906c158d4dda5889950fdc43f6ec3d` memeriksa `hasRole('SISWA')` dan menolak `ADMIN_MADRASAH`, bukan exact-one role SISWA. Mixed role lain (misalnya SISWA+GURU atau SISWA+KAMAD) berpotensi melewati predicate controller bila token/sesi sudah ada. Login resolver exact-one tidak menggantikan validasi exact-role pada setiap API/controller/policy. Ini blocker security Web Siswa yang dimiliki Agent 2.

`ExamMonitoringScope` blob `11b455...` mengembalikan seluruh participant query untuk exam author atau KAMAD; cabang Proktor memakai assignment CONFIRMED room. Agent 2 audit menyebut menu khusus Proktor Guru belum ditemukan pada source UI yang diperiksa; assignment UI yang ditemukan adalah pengelolaan Admin. Agent 3 harus mengonfirmasi menu Web Guru dan semua surface monitoring room-scoped. Agent 4 tidak membuat fitur Proktor.

Proctor assignment service Agent 1 blob `c968ce68c0decace1c35a0ccb3b2b4449c057656` memvalidasi target Guru dengan tepat satu role GURU dan TeacherProfile aktif pada tenant; ini bukti helper assignment tertentu, bukan bukti seluruh route/policy/menu sudah sesuai.

### DESAIN — kontrak dan status

| Area | Status | Owner |
|---|---|---|
| Portal aktif / KAMAD frozen / Proktor akun Guru | Keputusan produk koordinator FINAL; implementasi backend masih PENDING | Koordinator; Agent 2 backend; Agent 3 Web Guru |
| Exact-one SISWA untuk semua endpoint Web Siswa | PENDING — P0 | Agent 2 |
| KAMAD deny login/API/policy/monitoring/Livewire/exports | PENDING — P0 NON-COMPLIANT pada source audit | Agent 2 |
| Proktor exact-one GURU + assignment aktif + room scope | PENDING konfirmasi owner dan test seluruh surface | Agent 2 backend; Agent 1 assignment/session; Agent 3 Web Guru |
| Tidak ada Proktor capability pada Web Siswa | DESAIN DIKUNCI oleh keputusan produk; runtime belum diuji menyeluruh | Agent 4 client; Agent 2 API boundary |
| F-01 admission dan F-02 media | PENDING — P0 OPEN | Agent 2 |
| Answer receipt/conflict, submit/expiry/Force Submit | PENDING | Agent 2 |
| IndexedDB/offline recovery/submit barrier | DESIGN ONLY, NOT IMPLEMENTED | Agent 4 setelah kontrak backend |
| QuestionVersion/snapshot/media | NEEDS DESIGN; komentar review bukan owner approval | Agent 3 authoring; Agent 2 persistence/security |
| Room/session/recovery | PENDING; belum ada owner closure | Agent 1 + Agent 2 |
| Agent 4 ownership/official branch/base/coding permission | PENDING | Koordinator + owner Agent 4 |

### PENGUJIAN — CI dan acceptance

Combined status dan PR-workflow lookup kosong pada exact source baseline Agent 1 `87b1d621...`, Agent 2 `74bf717...`, Agent 3 test source `6334dc9...`, dan Agent 4 docs `edec6c2...`. Ini **NOT VERIFIED**, bukan PASS/FAIL; connector workflow yang tersedia hanya menampilkan PR-triggered runs. Agent 2 checkpoint juga menyatakan tidak menjalankan PHPUnit/PHP lint/MySQL/browser/concurrency pada audit read-only; Agent 3 checkpoint menyatakan P0 tenant/security test incomplete. Agent 4 tidak menjalankan test runtime.

Test baru yang wajib ditambahkan ke matriks: exact-one SISWA; SISWA+GURU, SISWA+KAMAD, GURU-only, KAMAD-only ditolak pada semua Student API; KAMAD tidak bisa login/session/token/route/API/policy/monitoring/Livewire/export; Guru Proktor tidak dapat memakai endpoint peserta; assignment Proktor tidak membuat role atau portal; siswa tidak dapat memanggil route/API Proktor; assignment Proktor tidak memberi hak lihat jawaban/nilai/Force Submit; assignment expired/revoked/wrong room ditolak. Semua tetap NOT RUN/BLOCKED.

### IMPLEMENTASI — dokumentasi saja

Dokumen Agent 4 ditambahkan addendum keputusan koordinator setelah lima dokumen existing dibackup byte-exact. Tidak ada source runtime, route, API, controller, policy, model, service, middleware, migration, schema, database, konfigurasi, test code, branch owner/kandidat, merge/rebase/cherry-pick, PR atau deployment yang diubah. Manifest backup: `docs/backup/agent4-contract-closure-2026-11-22/MANIFEST_COORDINATOR_PORTAL_PROCTOR_ADDENDUM.md`.

### NEXT GATE

1. Agent 2 memberi jawaban langsung per subkontrak exact-role SISWA, KAMAD freeze, Proktor assignment, F-01/F-02, lease/fencing, receipt/conflict, submit dan lock order dengan exact source/test/CI SHA.
2. Agent 3 memberi jawaban langsung atas menu Proktor di Web Guru, tanpa role/portal baru, serta room-scope seluruh monitoring surface; QuestionVersion/snapshot/media tetap NEEDS DESIGN sampai disetujui.
3. Agent 1/2 menyepakati assignment lifecycle, scheduler dan recovery Attempt aktif.
4. Owner Agent 4 dan koordinator mengonfirmasi official branch, base commit dan izin implementasi.
5. Sampai semua gate disetujui, hanya audit/dokumentasi; tidak ada runtime patch.

Status: `BLOCKED — CROSS-AGENT CONTRACT PENDING`; Web Siswa `NOT READY FOR OPERATIONAL EXAM`.