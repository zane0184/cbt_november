# Agent 4 — Gate Integrasi Web Siswa dan Matriks Acceptance (Revisi 23 November 2026)

## Tujuan dan batas
Dokumen operasional ini mengendalikan pekerjaan Agent 4 pada Portal Web Siswa. Cakupan: login Web Siswa, daftar ujian, detail/instruksi/token, runner, answer draft/sync/recovery, submit, hasil, aset tampilan, aksesibilitas, browser testing, dan integrasi milik client.

Di luar kewenangan Agent 4: backend/API/controller milik Agent 2, route, migration/schema, database server, Web Admin/Guru, Android native, VPS/deployment, dan data akademik lintas-installasi. Setiap madrasah memiliki instalasi dan database independen. Agent 4 tidak membuat peran/portal Operator, Kamad, atau Proktor. Role lokal hanya ADMIN_MADRASAH, GURU, SISWA. Kepala Madrasah hanya metadata. Guru dapat menjadi pengawas melalui assignment sementara, bukan portal Proktor.

PR #13 tetap Draft/unmerged sampai acceptance gate terukur lulus. Mock API tidak sama dengan integrasi backend nyata.

## Status saat revisi
- Branch: `work/agent4-web-siswa-emergency-20261122`
- PR: #13, OPEN / DRAFT / UNMERGED
- PR HEAD terakhir yang diverifikasi: `8a1365b3a24605699593621fda18cae4e0c0ca1d`
- CI patch role tunggal: run #280, ID `45110168166`, status CANCELLED. Tidak ada hasil job yang dapat diverifikasi.
- Run #272 SUCCESS pada source SHA `328c8b45f4e201115d18d309ac1bcb52ca868377`: static, Chromium 27, WebKit 27, PHPUnit MySQL 8: 28 tests/154 assertions. Bukti tersebut berlaku untuk source SHA itu saja, bukan patch role tunggal sesudahnya dan bukan integrasi browser-to-Laravel.

## AUDIT — temuan dan kontrak yang perlu ditutup
### A. Admission/token
Backend Agent 2 pada exact source SHA yang diaudit masih memiliki kondisi `if (!$lockedExam->require_token || !$lockedExam->token)` sehingga `require_token=false` ditolak. UI Agent 4 telah mendukung tanpa token dan tidak mengirim field token jika opsional. Hanya Agent 2 boleh mengubah controller API. Acceptance: token opsional start berhasil tanpa token; token wajib tetap fail-closed ketika kosong/salah/tidak dikonfigurasi dan benar saat valid.

### B. Answer receipt / response loss
Client Agent 4 memverifikasi `receipt.client_request_id`, Attempt, snapshot, revision, payload SHA-256, ID dan committed_at sebelum menghapus antrean IndexedDB. Exact retry immutable didukung secara desain melalui backend ledger, tetapi receipt response yang diaudit belum memuat `client_request_id`. Endpoint read-only lookup juga belum terbukti tersedia pada route backend yang diaudit.
- Jangan ubah client agar menerima receipt tanpa request ID.
- Jangan akui antrean hanya karena HTTP 200 atau answer DTO terlihat sama.
- Jangan buang request NOT_FOUND yang mungkin masih in-flight.
- Exact retry, jika disetujui kontrak final Agent 2, harus memakai request ID + payload + snapshot + base revision yang sama persis.
- Receipt immutable; generation terbaru harus menjadi envelope/metadata terpisah, bukan mengubah receipt lama.

### C. Attempt authorization, session dan fencing
Runner sudah menyimpan generation/lease, mengirim `X-CBT-Fence-Generation` + `X-Device-Binding-ID`, heartbeat 60 detik, dan mengunci runner pada mismatch. Ini belum membuktikan Device Lease/fencing siap produksi. Diperlukan MySQL two-connection concurrency PASS, multi-session/multi-device integration, auth-session yang benar, dan response terminal/recovery.

### D. Platform policy
Peserta Android wajib aplikasi native. Web/PWA peserta diperuntukkan bagi iOS dan browser non-Android yang secara eksplisit disetujui. Jangan memakai User-Agent JavaScript sebagai security boundary. Enforcement wajib melalui credential/session/token capability yang divalidasi server oleh Agent 2. Agent 4 tidak menambahkan bypass client-side.

### E. Media
Runner menggunakan same-origin Fetch/Blob dan header device/fence, MIME allowlist, limit 10 MB, dan revoke Object URL. Backend saat ini mengembalikan file melalui `response()->file()`; range/seek audio-video belum ada bukti runtime yang diaudit. Uji backend Range/seek dan authorization tetap menjadi gate Agent 2; Agent 4 kemudian menguji behavior elemen media terhadap endpoint yang disepakati.

### F. Role portal
Patch Agent 4 pada `StudentExamController` mengharuskan tepat satu persisted role SISWA. Tes SISWA+GURU ditambahkan, tetapi run #280 cancelled. Perubahan tetap unverified sampai rerun CI valid. Perhatikan bahwa guard ini adalah penguatan UI route, bukan pengganti `hasExactRole('SISWA')`/profile enforcement pada backend.

### G. Status hasil
Siswa hanya melihat hasil yang dipublikasikan dan final dari current-score pointer. Tidak boleh menampilkan answer key, scoring internals, histori score lain, atau hasil siswa lain.

## DESAIN — urutan pengujian
1. Static source audit pada exact PR HEAD, termasuk JavaScript parse, Blade local CSS, unique IDs, IndexedDB stores/version, receipt gate, submit barrier, fencing headers, media lifecycle, RTL/accessibility selectors.
2. Chromium E2E terhadap fixture/mock hanya untuk behavior client. Label hasil harus eksplisit mock.
3. WebKit Linux E2E hanya proxy engine; tidak sama dengan Safari iOS.
4. PHPUnit + MySQL 8 disposable untuk route/view authorization dan result visibility. Tidak menggunakan DB produksi.
5. Setelah exact Agent 2 commit tersedia dan seluruh P0 integration preconditions lulus, jalankan browser-to-real-Laravel pada DB disposable:
   - login/identity exact-one SISWA;
   - exams list/assignment/eligibility;
   - optional/required token;
   - start/resume satu Attempt;
   - GET execution, server timer;
   - answer write + receipt;
   - same request ID replay, same-ID/different-payload reject;
   - response lost + read-only receipt status atau retry semantics yang disahkan;
   - offline answer retention, heartbeat/fence advance, exact retry;
   - session mismatch/multi-device fencing;
   - submit, unknown submit, terminal status, expiry;
   - result publish/finalize visibility;
   - media auth/MIME/size/Range/seek.
6. UAT perangkat nyata iOS Safari termasuk keyboard, VoiceOver, IndexedDB, reload/sleep/wake, network loss/reconnect dan media seek. UAT madrasah Admin/Guru/Siswa dijalankan pada instalasi uji tersendiri.

## Acceptance criteria
| Gate | Owner utama | Syarat lulus | Kondisi saat revisi |
|---|---|---|---|
| Local UI CSS/runner | Agent 4 | Static audit + Chromium/WebKit PASS pada exact HEAD | PASS pada SHA lama; patch role terkini belum teruji |
| Portal single role | Agent 4 | PHPUnit menolak akun multi-role dan role bukan SISWA | Patch tersedia; CI cancelled |
| Optional token | Agent 2 | API MySQL 8 positive/negative tests PASS | BLOCKED |
| Receipt + idempotency | Agent 2 | receipt memuat client_request_id; exact replay immutable; mismatch reject | BLOCKED |
| Receipt recovery | Agent 2 | read-only status route atau retry semantics/race policy disahkan dan diuji | BLOCKED / NEEDS DESIGN |
| Attempt auth/limit | Agent 2 | semua auth/limit tests PASS | BLOCKED |
| InnoDB concurrency | Agent 2 | two-connection test benar-benar RUN dan PASS | BLOCKED |
| Native Android vs Web | Agent 2 | server-side credential capability test matrix PASS | BLOCKED |
| Media auth/Range | Agent 2 | auth/fence + MIME/size + Range/seek tests PASS | BLOCKED |
| Browser-to-Laravel | Agent 4 + Agent 2 | browser nyata pada Laravel dan MySQL disposable exact integrated SHA | NOT RUN; menunggu dependency |
| Safari iOS/UAT | Agent 4 + madrasah | perangkat Apple nyata, checklist bukti tersanitasi, issue resolved | NOT RUN |

## IMPLEMENTASI yang sudah tercatat
- Login, daftar ujian, runner, hasil memakai CSS lokal tanpa Tailwind/Google Fonts CDN.
- Runner mobile-first, navigasi adaptif, Arabic/RTL dir=auto, ESSAY aktif, SHORT_ANSWER fail-closed.
- IndexedDB v2: attempt_context, answers, sync_queue, server_receipts, conflicts, local_meta; migrasi queue lokal v1.
- Draft write/queue atomic; request ID/payload/base revision persisten; 409 conflict memisahkan local/server, mengembalikan draft lokal ke editor dan memblokir submit.
- Undurable draft menahan submit; submit barrier menunggu write, flush, lock runner sebelum marker SUBMITTING.
- Unknown submit tidak memicu POST ulang otomatis; GET status memulihkan Attempt yang sama atau terminal.
- Fence generation/lease persistent; heartbeat 60 detik; online recovery menunggu heartbeat sebelum answer queue retry.
- Media melalui Fetch/Blob terautentikasi, MIME/size gate dan Object URL cleanup.
- Receipt verification strict; ACK hanya setelah receipt terverifikasi.
- Controller role single persisted SISWA telah dipatch dan regresi SISWA+GURU ditambahkan; CI cancelled sehingga belum teruji.

## Tindakan sekarang
1. Dapatkan run CI valid untuk PR HEAD `8a1365b3a24605699593621fda18cae4e0c0ca1d`, pastikan test role guard benar-benar ikut teruji.
2. Agent 2 diminta mengirim exact final branch/commit + workflow evidence untuk seluruh blockers A–E. Jangan membuat E2E real terhadap backend yang masih known-broken.
3. Review exact source Agent 2 yang baru; update kontrak ini dengan file/blob SHA dan hasil test baru.
4. Setelah dependency lulus, tambahkan dedicated integration workflow browser-to-Laravel/MySQL disposable di branch Agent 4. Jangan menggabungkan workflow dua owner tanpa koordinasi.
5. Jalankan iOS Safari UAT dan madrasah UAT ketika perangkat/instalasi uji tersedia.

## Bukti dan larangan
Semua perubahan Agent 4 harus didahului backup byte-exact, ditulis pada branch Agent 4, di-verify fetch ulang, dan dilaporkan pada PR #13. Setiap hasil CI harus diikat ke exact source SHA. Jangan menyatakan ready-to-review, production-ready, merge, deploy, migration produksi, atau Device Lease/fencing siap produksi hanya dari mock/browser test.
