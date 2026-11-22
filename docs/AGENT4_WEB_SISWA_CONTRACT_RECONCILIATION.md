# Agent 4 — Rekonsiliasi Kontrak Web Siswa dan Admission Security

Tanggal audit: 22 November 2026  
Repository: `zane0184/cbt_november`  
Branch dokumentasi: `work/agent4-web-siswa-audit-20261122`  
Basis branch dokumentasi: `main` @ `feab77228f7108f7f7a45325d7f1fb878c07310b`  
Status: **AUDIT/DESIGN DOCUMENTATION — IMPLEMENTATION GATED**  
Status operasional Web Siswa: **NOT READY FOR OPERATIONAL EXAM**

## 1. AUDIT — sumber aktual yang diperiksa

### 1.1 Branch dan integrasi

| Peran | Branch | Temuan graph terhadap main |
|---|---|---|
| Main | `main` | HEAD terverifikasi `feab77228f7108f7f7a45325d7f1fb878c07310b` |
| Agent 1 | `work/stage4b-room-session-20261122` | Diverged; 1191 ahead / 15 behind |
| Agent 2 | `work/stage4d-account-lifecycle-20261122` | Diverged; 1342 ahead / 15 behind |
| Agent 3 | `work/web-guru-foundation-20261122` | Diverged; 1141 ahead / 15 behind |
| Kandidat Agent 4 | `work/import-guru-siswa-implementation-20261122` | Diverged; 930 ahead / 15 behind |
| Dokumentasi Agent 4 | `work/agent4-web-siswa-audit-20261122` | Dibuat dari HEAD main terverifikasi; dokumentasi saja |

Catatan:
- Branch kandidat `work/import-guru-siswa-implementation-20261122` memang ada, tetapi Issue #10 menyebutnya **kandidat / ownership belum terkonfirmasi**. Tidak ditemukan branch yang dapat dinyatakan sebagai branch resmi Agent 4.
- SHA `f38ceec7b0653fcf3363eec40f3d4577c323cca8` dicatat Issue #10 sebagai SHA kandidat pada audit terdahulu; bukan bukti bahwa SHA tersebut adalah HEAD branch saat audit ini.
- Compare API yang tersedia memberi status diverged dan jumlah ahead/behind, tetapi tidak memberikan SHA tip untuk setiap branch. Karena itu SHA tip Agent 1/2/3/kandidat tidak diklaim dari hasil ini.
- Struktur source CBT `backend-laravel/` tidak tersedia di `main` pada path yang diperiksa. File-file runtime dibaca dari branch Agent 2 dan kandidat Agent 4. Main tidak boleh diperlakukan sebagai source gabungan.
- Tidak ada merge, rebase, cherry-pick, PR, atau perubahan source agent lain.

### 1.2 Blob SHA source Agent 2

Branch: `work/stage4d-account-lifecycle-20261122`.

| File | Blob SHA |
|---|---|
| `app/Http/Controllers/Api/V1/ExamController.php` | `c7c62e961f2ff47abee8f3b2468b4e7bc43d1cc9` |
| `app/Http/Controllers/Api/V1/MediaController.php` | `f852c1b3a7247efdbb0c309fb0413bbe5e3eaa62` |
| `app/Http/Controllers/Api/V1/ExamAttemptController.php` | `13e8981cca4653ff4e9b9448a50e4d0ec8a74bae` |
| `app/Http/Controllers/Api/V1/ExamAnswerController.php` | `b4ddec14f6208b3ecde9bfc3e45168a2159e2a9c` |
| `routes/api.php` | `2e43c37dd669a26446a44b818c1d2c8420b15aca` |
| `resources/views/student/exams/show.blade.php` | `7283ca553d787f4505299a306e2bd487884622d3` |
| `app/Policies/ExamPolicy.php` | `c05d96ef932fdc81b67336240af4e1726864c3d6` |
| `app/Http/Controllers/StudentExamController.php` | `981fd29d24906c158d4dda5889950fdc43f6ec3d` |

### 1.3 Blob SHA source kandidat Agent 4

Branch: `work/import-guru-siswa-implementation-20261122`.

| File | Blob SHA |
|---|---|
| `app/Http/Controllers/Api/V1/ExamController.php` | `bf373858869b470b235e2256e5be6169fbb1f561` |
| `app/Http/Controllers/Api/V1/MediaController.php` | `f852c1b3a7247efdbb0c309fb0413bbe5e3eaa62` |
| `app/Http/Controllers/Api/V1/ExamAttemptController.php` | `808702092c94b386657fe027f60758a625d5bb93` |
| `app/Http/Controllers/Api/V1/ExamAnswerController.php` | `b4ddec14f6208b3ecde9bfc3e45168a2159e2a9c` |
| `routes/api.php` | `4a9faa1e2bb0485570f30c199ccfbea7aa3a26ff` |
| `resources/views/student/exams/show.blade.php` | `7283ca553d787f4505299a306e2bd487884622d3` |
| `app/Policies/ExamPolicy.php` | `121fa8871dd958b25709cae567adc1d4e6c549b3` |
| `app/Http/Controllers/StudentExamController.php` | `981fd29d24906c158d4dda5889950fdc43f6ec3d` |

### 1.4 F-01 — admission dan release snapshot

Source `ExamController::show()` pada Agent 2 dan kandidat masih memuat snapshot jika `$exam->canStartAttempt()`. Kondisi tersebut tidak mensyaratkan:
- token sudah diterima untuk Attempt yang sedang berjalan;
- Attempt milik user yang meminta;
- Attempt masih aktif dan sah;
- Device Lease/token lease valid;
- fencing generation sesuai.

`ExamPolicy::view()` untuk Siswa membatasi tenant dan keanggotaan ExamParticipant, tetapi bukan admission Attempt.

Pada `ExamAttemptController::store()`, request memakai `X-Device-Binding-ID`. Untuk Attempt baru, token diperiksa. Untuk Attempt aktif yang fingerprint-nya sama, jalur resume mengembalikan Attempt sebelum pemeriksaan token baru. Ini tidak mengubah kesimpulan F-01: endpoint detail Exam masih dapat memuat snapshot berdasarkan jadwal saja.

Test leakage yang ditemukan sebelumnya hanya menguji field answer-key tertentu, bukan memastikan snapshot tidak diberikan sebelum admission.

**Status F-01: P0 OPEN.**

### 1.5 F-02 — akses media

`MediaController::show()` memeriksa tenant, owner, dan apakah media direferensikan oleh snapshot yang terkait dengan Attempt aktif milik siswa. Namun source yang diperiksa belum membuktikan:
- snapshot media berada di dalam `question_order` Attempt tersebut;
- lease token valid;
- registered device valid;
- fencing generation sesuai;
- lease belum expired/revoked.

Validitas ID media saja tidak cukup. Mengganti ID media atau snapshot tidak boleh memberi akses ke aset lain.

**Status F-02: P0 OPEN.**

### 1.6 Device Lease/fencing

Pada route API yang diperiksa, grup authenticated/tenant/license belum menunjukkan middleware validasi lease/fencing. Attempt dan Answer menggunakan device binding ID/fingerprint.

Belum ditemukan bukti runtime yang dapat diverifikasi untuk service/controller/middleware lengkap siklus:
- acquire;
- heartbeat;
- release;
- revoke;
- takeover;
- lease expiry;
- registered device;
- lease token;
- fencing generation;
- stale request rejection.

Schema migration/test keberadaan tabel bukan bukti siklus lease operasional.

**Status: SCHEMA FOUNDATION EXISTS; END-TO-END RUNTIME NOT VERIFIED.**

### 1.7 Answer, revision, idempotency, dan runner

Backend Answer menerima `client_request_id`, `base_revision`, payload, dan answer state. Update melakukan transaksi dan lock Attempt lalu Answer. Belum terbukti urutan global lock Agent 1/2 final untuk seluruh operasi yang juga menyentuh Exam/Participant/Lease.

Runner `show.blade.php` menggunakan in-memory state Map untuk answers/revisions/pending/saving. Tidak ada bukti IndexedDB persistence pada runner yang diperiksa. Respons 409 belum dapat dianggap sebagai conflict archive yang aman. Status UI tidak boleh menyatakan server accepted tanpa receipt yang terverifikasi.

**Status: REVISION/IDEMPOTENCY FOUNDATION EXISTS; DURABLE BROWSER RECOVERY NOT VERIFIED.**

## 2. DESAIN — kontrak frontend yang diusulkan

### 2.1 Admission

Sebelum admission sah, response siswa hanya boleh memuat metadata aman:
- identitas ujian yang boleh dilihat;
- jadwal dan durasi;
- instruksi;
- status token yang aman.

Tidak boleh ada snapshot soal, answer key, accepted answers, rubric, scoring guide, metadata internal, atau informasi peserta lain.

Snapshot baru boleh diterima setelah backend membuktikan User aktif, tenant, Exam, peserta, token, Attempt, jadwal server, perangkat, lease, dan fencing generation sah.

Frontend tidak menetapkan endpoint, nama field, header, status HTTP, atau response baru. Semua menunggu kontrak Agent 2.

### 2.2 Media

Media hanya dapat diminta setelah backend memverifikasi pemilik Attempt, tenant, Exam, status Attempt, snapshot dalam `question_order`, referensi media oleh snapshot tersebut, registered device, lease token, expiry/revoke, dan fencing generation.

### 2.3 Kontrak lintas agent

| Domain | Owner | Keputusan yang diperlukan |
|---|---|---|
| ExamSession | Agent 1 | Satu Exam–satu ExamSession operasional; lifecycle |
| Admission/Attempt | Agent 2 | Token, Attempt, waktu server, close/resume |
| Device Lease/Fencing | Agent 2 | Acquire/heartbeat/release/revoke/takeover, token, generation |
| Answer/Receipt/Conflict | Agent 2 | Revision, idempotency, receipt, konflik |
| Question snapshot/media | Agent 3 | QuestionVersion, media, snapshot, enam tipe |
| Web Siswa | Agent 4 | Admission UI, runner, recovery UX |
| Integrasi | Koordinator | Persetujuan dan urutan integrasi |

### 2.4 Status koordinasi yang didukung bukti

- Agent 1: dokumen kontrak Agent 1–4 tersedia, tetapi menyatakan approval Agent 2/3/4 masih PENDING.
- Agent 2: Issue #10 memuat review **NEEDS DESIGN / COORDINATION**. Source mendukung resume Attempt aktif setelah close selama status/expiry/eligibility valid; lock Attempt creation adalah Exam → Participant → Attempt. Answer update masih lock Attempt → Answer dan perlu rekonsiliasi.
- Agent 3: Issue #8 memiliki update exact-role Livewire dan test regresi. Komentar terakhir yang diperiksa menyebut CI untuk test tambahan masih NOT VERIFIED. Issue #9 DOCX belum memiliki komentar.
- Agent 4: ownership branch kandidat belum dikonfirmasi. Tidak ada approval formal Agent 4 pada Issue #10.

Tidak ada status APPROVED yang diklaim atas nama agent lain.

## 3. IMPLEMENTASI

**NONE — DOCUMENTATION ONLY.** Tidak ada source aplikasi, route, API, policy, model, service, migration, schema, runner, database, atau VPS yang diubah.

## 4. PENGUJIAN

- Audit pembacaan source GitHub: dilakukan.
- Pemeriksaan blob SHA: dilakukan.
- PHPUnit/backend runtime: NOT RUN.
- Browser E2E: NOT RUN.
- IndexedDB: NOT RUN.
- Multi-tab: NOT RUN.
- Multi-device: BLOCKED.
- Recovery/reload/crash: NOT RUN.
- Submit barrier: NOT RUN.
- F-01/F-02 adversarial runtime: NOT RUN.

## 5. BLOCKER DAN LANGKAH BERIKUTNYA

1. Agent 2 menetapkan dan mengimplementasikan F-01/F-02 serta kontrak admission/lease/fencing/answer receipt.
2. Agent 1 dan Agent 2 menyepakati close/resume, lifecycle session, scheduler, serta global lock order.
3. Agent 3 menyepakati identitas QuestionVersion/media snapshot dan kontrak accepted answers.
4. Koordinator mengonfirmasi branch resmi Agent 4 dan basis integrasi.
5. Baru setelah kontrak disetujui, Agent 4 mengimplementasikan runner/recovery dan browser test.

Status akhir: **BLOCKED — CROSS-AGENT CONTRACT PENDING.** Web Siswa tetap **NOT READY FOR OPERATIONAL EXAM**.


## REMOTE REFRESH — 22 NOVEMBER 2026

### AUDIT — HEAD remote dan compare

HEAD aktual sebelum dokumentasi refresh: Agent 1 `51571ca54ec922466e6230b4e8137a85336bc024`; Agent 2 `462cadc96a95d091bd89819dbc106b4fbd67e1cd`; Agent 3 `6db04429613537c8219e6d8d873332d25f8c1ced`; Agent 4 docs `f7166abff090d1cc8cdd49d0ebbea6509d5d0886`; kandidat `f38ceec7b0653fcf3363eec40f3d4577c323cca8`; `main` `feab77228f7108f7f7a45325d7f1fb878c07310b`.

Ahead/behind terhadap main: Agent 1 1216/15; Agent 2 1352/15; Agent 3 1180/15; Agent 4 docs 16/0; kandidat 930/15. Perbandingan lintas owner seluruhnya `diverged`: Agent 1→2 234/98; Agent 1→3 207/243; Agent 1→Agent 4 docs 31/1216; Agent 1→candidate 80/366; Agent 2→3 207/379; Agent 2→Agent 4 docs 31/1352; Agent 3→Agent 4 docs 31/1180. Merge-base Agent 1→2 `0631a3d84d058c8c049dcb71dc54b2e7cbb849a6`; Agent 1→3 `81b6c74d81d80bc483530802594f624fb4e0f3db`; Agent 1→candidate `17c20c66284deb7a18e3a6dde2b3d47f9c966bd9`; branches Agent 4 docs terhadap Agent 1/2/3 bercabang dari main (`95736e338e517a087503adfb3e3a899709e0340c`).

Tip Agent 1/2/3 dan Agent 4 docs yang diperiksa hanya dokumentasi/backup; kandidat tip masih mengubah `backend-laravel/app/Http/Controllers/Api/V1/ExamAttemptController.php`. Semua tip commit tercatat dari akun `zane0184`; itu tidak membuktikan identitas owner agent yang berbeda. Kandidat tidak diubah dan tidak diakui sebagai branch resmi.

### AUDIT — Issue #10 dan approval

Issue #10 OPEN, 14 komentar. Semua komentar yang dikembalikan ber-author login `zane0184`. Komentar terbaru `7078306561` berjudul “Agent 3 — jawaban teknis QuestionVersion / ExamSnapshot / media (status NEEDS DESIGN)”. Isi komentar menyajikan audit teknis yang konsisten dengan source, namun author metadata adalah akun koordinator `zane0184` dan komentar menyatakan bukan approval final lintas-agent. Catat sebagai **technical review koordinator — NEEDS DESIGN**, bukan approval owner Agent 3.

Komentar `7078261415` adalah keputusan koordinator: satu instalasi/database/storage/credential/config/file/backup per madrasah, `tenant_id` defense-in-depth, tanpa shared academic database atau sinkronisasi akademik lintas madrasah. Itu bukan approval teknis Agent 2. Komentar `7078273774` adalah permintaan jawaban owner, bukan approval. Tidak ditemukan jawaban langsung terverifikasi dari owner Agent 1/2/3/4. **AGENT 4 OWNERSHIP — PENDING CONFIRMATION.**

### AUDIT — source path/blob dan temuan

Agent 1: migration `2026_11_22_110001_create_exam_room_session_scope.php` blob `9551b1dd79397bd0432f03eefc970852b675fd25` (unique `(tenant_id, exam_id, session_number)`, belum menegakkan satu operational session per Exam); `ExamSessionLifecycleService.php` `8e34152afd817f30cc2f81b5f25fc450571463ac` (scheduler menyelesaikan session dan assignment berdasar ends_at/status tanpa memeriksa Attempt IN_PROGRESS); `ExamRoomSessionService.php` `e514fa9f751584e54af1125d48b5673e013f2339`; `ExamProctorAssignmentService.php` `c968ce68c0decace1c35a0ccb3b2b4449c057656`; `ExamMonitoringScope.php` `11b455954f974d1c60ed5c49700368f8d37c09c1` (Proktor room assignment scoped, author/KAMAD broad scope masih perlu ditutup).

Agent 2 runtime baseline `74bf7178f46673e9be90523671490c0749034acd` (tip branch `462cadc...` hanya checkpoint): `ExamController.php` `c7c62e961f2ff47abee8f3b2468b4e7bc43d1cc9` — `show()` masih melepas snapshot berdasar `canStartAttempt()` tanpa admission lengkap; **F-01 P0 OPEN**. `MediaController.php` `f852c1b3a7247efdbb0c309fb0413bbe5e3eaa62` — tenant/owner/path/private-no-store ada, tetapi `question_order`, registered device, lease expiry/revoke dan fencing belum dibuktikan; **F-02 P0 OPEN**. `ExamAttemptController.php` `13e8981cca4653ff4e9b9448a50e4d0ec8a74bae` — create/resume lock Exam→Participant→Attempt; resume Attempt IN_PROGRESS, belum expired, peserta eligible setelah close didukung dan harus dipertahankan. `ExamAnswerController.php` `b4ddec14f6208b3ecde9bfc3e45168a2159e2a9c` — Answer update lock Attempt→Answer, belum sejalan dengan parent-first; request ID/revision ada, receipt terpisah belum terbukti. `ExamPolicy.php` `c05d96ef932fdc81b67336240af4e1726864c3d6` masih memakai hasRole non-exact dan jalur KAMAD. DeviceLease/RegisteredDevice model/service/middleware runtime tidak ditemukan pada tree Agent 2; migration/schema bukan bukti runtime.

Agent 3: `QuestionController.php` `7ded53423dd4d96788123819e8039fd543ce4fba` mengizinkan tepat enam tipe tetapi tidak menerima `accepted_answers`; `QuestionVersion.php` `5f937abf691f96ee62696f7ef01b5adb2d6f0d9a` fillable tidak memuat accepted answers; `ExamSnapshotService.php` `d0bfcd70b2777a3884c7f92b7c256f05492645ec` metadata snapshot tidak menyalin accepted answers; `ScoringService.php` `1fc74eae97538ada140b9b4c75ccd4388d1a40ad` membacanya dari metadata snapshot dan fallback ke opsi benar. **SHORT_ANSWER tetap BLOCKED** sampai authoring→immutable snapshot→scoring teruji. ESSAY manual review. `ExamAttemptController.php` `e915bf43118026ef8eace96338fa6cad44ee29e1` `show()` belum terbukti menerapkan room-scope; `MediaAsset.php` `486a96b525b78257ce46b04f504a74b761daea23`.

### DESAIN — status kontrak

| Kontrak | Status |
|---|---|
| Agent 4 official owner/branch/base/coding permission | PENDING |
| F-01 admission/snapshot release | PENDING — P0 OPEN |
| F-02 media authorization | PENDING — P0 OPEN |
| Device Lease/Fencing runtime | PENDING — NOT VERIFIED |
| Answer revision/idempotency/receipt/conflict | NEEDS DESIGN / PENDING |
| Submit/expiry/Force Submit | PENDING |
| QuestionVersion/snapshot/media | NEEDS DESIGN / PENDING (technical review koordinator, bukan owner approval) |
| SHORT_ANSWER | BLOCKED |
| Room/Session/Proktor/recovery | PENDING |
| Integration base and permission | PENDING |

Lock order Attempt-only `Exam → Participant → Attempt → Answer/Score/Lease` masih proposal; Answer update aktual masih `Attempt → Answer`. Agent 4 tidak menetapkan endpoint/DTO/HTTP status/enum/backend lease service/`recovery_until`.

### PENGUJIAN — CI dan acceptance

Combined status dan lookup workflow-run untuk HEAD Agent 1/2/3/4 docs/kandidat serta source baseline Agent 1 `d253279...`, Agent 2 `74bf717...`, Agent 3 test source `4dc407d...` tidak mengembalikan status check atau PR-triggered workflow run. Connector hanya menampilkan PR-triggered workflow runs; karenanya CI exact-SHA **NOT VERIFIED**, bukan bukti push-run tidak pernah ada.

Riwayat CI yang dicatat checkpoint owner: Agent 1 run `44984303003` SUCCESS pada source lama `f1d01ea...`, bukan source current dan belum menguji operational guard; Agent 2 runs `45003796892` dan `45008591113` BLOCKED — RUNNER, PHPUnit tidak berjalan; Agent 3 run `45003191589` pada `834a59f...` FAILURE (18 passed/2 failed/86 assertions), run `45009039557` pada `88b5c135...` job failure/steps kosong/PHPUnit UNKNOWN; kandidat run `44949239676` SUCCESS pada cakupan lama, bukan bukti integrasi lintas-branch.

Agent 4 tidak menjalankan PHPUnit, MySQL 8 integration, F-01/F-02 adversarial, lease/fencing, receipt/conflict, IndexedDB, multi-tab, offline/reload/crash, browser E2E, submit race, room recovery atau multi-device. Status test tetap NOT RUN/BLOCKED.

### IMPLEMENTASI DAN NEXT GATE

Hanya dokumentasi Agent 4 yang diperbarui setelah backup byte-exact. Tidak ada source runtime/test/route/controller/policy/model/service/middleware/migration/schema/database/config, branch Agent 1/2/3/kandidat, atau deployment yang diubah. Tidak ada PR/merge/rebase/cherry-pick.

Backup checkpoint dan reconciliation sebelum refresh: `docs/backup/agent4-contract-closure-2026-11-22/MANIFEST_REMOTE_REFRESH_F7166AB.md`; kedua backup blob SHA diverifikasi identik dengan original. Langkah berikutnya: tunggu owner Agent 1/2/3/4; verifikasi setiap kontrak dan CI exact-SHA; koordinator menetapkan branch/base/izin; baru setelah semua gate lulus lakukan backup/preflight dan test disposable MySQL 8/browser E2E.

Status: **BLOCKED — CROSS-AGENT CONTRACT PENDING**. Web Siswa **NOT READY FOR OPERATIONAL EXAM**.

## ADDENDUM — KOORDINATOR PORTAL/KAMAD/PROKTOR DAN WEB SISWA (22 NOVEMBER 2026)

### Keputusan produk yang menjadi batas Web Siswa

Issue #10 komentar `7078539953` (author login `zane0184`) menetapkan portal aktif ADMIN_MADRASAH, GURU, SISWA; KAMAD frozen dari seluruh akses CBT; Proktor merupakan assignment sementara untuk akun GURU sendiri dengan menu di Web Guru; tidak ada akun/login/portal/role permanen PROKTOR; SISWA hanya peserta dan bukan identitas Proktor. Komentar tersebut adalah keputusan koordinator, bukan approval teknis Agent 2/3.

Implikasi Agent 4:
- Web Siswa hanya menerima exact-one role SISWA. Validasi harus dilakukan backend pada setiap Student route/API/policy/service, bukan hanya login atau menu.
- Guru Proktor tetap exact-one GURU dan tidak boleh mengakses Student runner, Attempt, answer atau submit sebagai SISWA.
- Tidak ada menu, route, API client action atau UI Proktor di Web Siswa. Fungsi Proktor berada dalam Web Guru dan hanya untuk assignment aktif yang valid.
- Tidak ada jalur KAMAD di Web Siswa maupun backend CBT; seluruh login/API/policy/monitoring/Livewire/export KAMAD harus ditolak oleh Agent 2. Histori appointment tetap data tanpa capability.
- Assignment Proktor tidak memberi hak melihat jawaban/nilai, mengelola soal/Exam, Force Submit, atau assignment Guru lain.

### Audit source owner terbaru

Branch HEAD saat refresh: Agent 1 `69f16da92452fd28a85aec2a8239c5d5ba9a10dd` (runtime baseline `87b1d621...`); Agent 2 `09b0ee42555b58c520b6ce3b46243b684e5a9d69` (runtime baseline `74bf7178...`); Agent 3 `f13e2adaf284e2a1d727ea23b7c9788f793e3ec1` (test source `6334dc9...`); Agent 4 docs `edec6c2bddfda5563c907827db7d1ae77775c086`; candidate `f38ceec7...` tetap unconfirmed.

Agent 2 audit report blob `a503e790f08e9cd684e36e9006d4e3d55bb3c3f3` menyatakan KAMAD masih bisa login Web/API, resolver dan RolePermissionBootstrapper masih memberi jalur/permission, serta ExamPolicy/ExamAttemptPolicy/ReportPolicy/ExamMonitoringScope mengenali KAMAD. StudentExamController blob `981fd29d24906c158d4dda5889950fdc43f6ec3d` memakai `hasRole('SISWA')` dan hanya menolak ADMIN_MADRASAH, bukan exact-one SISWA. Ini P0 yang harus ditutup Agent 2; Agent 4 tidak memodifikasi backend.

Agent 2 ExamMonitoringScope blob `11b455954f974d1c60ed5c49700368f8d37c09c1` memberi seluruh participant scope kepada exam author atau KAMAD; Proktor hanya room-scoped melalui assignment CONFIRMED pada cabang lain. Agent 2 audit menyebut menu Proktor Guru belum ditemukan dan assignment UI yang ada adalah Admin. Agent 3 harus memberi keputusan dan source evidence untuk menu Web Guru serta seluruh endpoint monitoring yang room-scoped.

Agent 1 ExamProctorAssignmentService blob `c968ce68c0decace1c35a0ccb3b2b4449c057656` memiliki helper validasi exact-one GURU + TeacherProfile aktif untuk target assignment; belum membuktikan semua route, policy, monitoring, scheduler, expiry/revocation dan UI sudah aman.

### Status kontrak setelah keputusan koordinator

| Subkontrak | Status | Owner |
|---|---|---|
| Portal aktif dan KAMAD frozen | Koordinator APPROVED sebagai keputusan produk; backend enforcement PENDING | Koordinator / Agent 2 |
| Proktor sebagai assignment Guru, bukan role/portal | Koordinator APPROVED sebagai keputusan produk; backend dan Web Guru UI PENDING | Koordinator / Agent 1, 2, 3 |
| Exact-one SISWA di semua Student entrypoint | PENDING — P0 | Agent 2 |
| KAMAD deny all CBT entrypoints | PENDING — P0 NON-COMPLIANT pada source audit | Agent 2 |
| Proktor assignment active/expiry/revoke/room-scope | PENDING owner confirmation + integration tests | Agent 1/2/3 |
| Web Siswa tanpa fungsi Proktor | DESAIN DIKUNCI; runtime acceptance NOT RUN | Agent 4 |
| F-01/F-02/lease/receipt/submit | PENDING — P0/NEEDS DESIGN | Agent 2 |
| QuestionVersion/snapshot/media/SHORT_ANSWER | NEEDS DESIGN; SHORT_ANSWER BLOCKED | Agent 3 + Agent 2 |

### Acceptance criteria baru

1. Student endpoint menerima hanya satu role `SISWA`; setiap role campuran dan GURU/KAMAD/ADMIN/legacy role ditolak.
2. KAMAD tidak dapat login, memperoleh API token, mengakses Web/API/Livewire/monitoring/report/export atau memanfaatkan jalur author/KAMAD bypass.
3. Proktor login menggunakan akun GURU sendiri dan tidak memiliki role PROKTOR; assignment aktif hanya membuka menu/fungsi di Web Guru yang diizinkan.
4. Guru Proktor tidak dapat memanggil Student runner/Attempt/Answer/Submit karena exact-one role SISWA gagal.
5. Siswa tidak dapat memanggil fungsi assignment/monitoring Proktor; tidak ada route/menu Proktor pada Web Siswa.
6. Proktor hanya melihat participant/event pada room assignment aktif; tidak otomatis melihat jawaban, nilai, bank soal, konfigurasi Exam, Force Submit atau assignment lain.
7. Semua penolakan fail closed, tidak mengungkap data lintas tenant/instalasi, dan audit action menyimpan identitas Guru untuk operasi Proktor.

Keputusan produk koordinator tidak menutup F-01/F-02, exact-role backend, QuestionVersion/snapshot/media, lease/fencing, receipt/conflict, submit, scheduler/recovery, owner Agent 4 atau izin coding. Status keseluruhan tetap **BLOCKED — CROSS-AGENT CONTRACT PENDING**.