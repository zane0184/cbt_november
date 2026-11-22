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
