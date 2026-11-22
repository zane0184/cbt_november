# Agent 4 — Spesifikasi Submit Barrier Web Siswa

Tanggal: 22 November 2026  
Repository: `zane0184/cbt_november`  
Branch dokumentasi: `work/agent4-web-siswa-audit-20261122`  
Owner client barrier: Agent 4. Owner submit, Force Submit, expiry, idempotency dan scoring: Agent 2.  
Status: **DESIGN PROPOSAL — NOT IMPLEMENTED — BACKEND CONTRACT PENDING**.

## 1. Tujuan dan batas

Submit barrier mencegah browser mengirim submit ketika jawaban lokal belum diketahui hasil server, antrean masih pending, request aktif, konflik terbuka atau Attempt/lease tidak sah.

Server tetap satu-satunya sumber kebenaran status Attempt, accepted answer, expiry, lease/fencing dan hasil submit. Client tidak dapat menjamin submit selesai jika browser/network terputus.

Satu instalasi = satu madrasah = satu database akademik sendiri. Tidak ada sinkronisasi akademik antar instalasi. Sampai Juni 2027 scope hanya Web Admin Madrasah, Web Guru dan Web Siswa; Android native/Super Admin ditunda.

## 2. AUDIT — basis sumber

Branch Agent 2 HEAD saat refresh: `7d63ea3b70b5191530c2c92c5c86bf92d7324db6`.

Source yang relevan:
- Attempt controller blob `13e8981cca4653ff4e9b9448a50e4d0ec8a74bae`.
- Answer controller blob `b4ddec14f6208b3ecde9bfc3e45168a2159e2a9c`.
- Exam controller blob `c7c62e961f2ff47abee8f3b2468b4e7bc43d1cc9`.
- Routes blob `2e43c37dd669a26446a44b818c1d2c8420b15aca`.

Attempt start/resume/submit source mengunci Exam → Participant → Attempt. Submit melakukan pemeriksaan ownership peserta dan device fingerprint, mengubah Attempt ke status terminal dan menulis audit event `SUBMIT_ATTEMPT` atau `EXPIRE_ATTEMPT` sesuai jalur. Answer update menerima `client_request_id`, `base_revision`, payload dan state; lock yang terlihat adalah Attempt → Answer. Belum terbukti kontrak receipt terpisah yang dapat diverifikasi browser. Belum ada keputusan final lock order global.

Tidak ada source yang diubah.

## 3. Client state machine

State client konseptual:
- `LOCAL_ONLY`: jawaban committed di IndexedDB, belum masuk queue.
- `PENDING_SYNC`: request immutable masuk queue.
- `SENDING`: request sedang dikirim.
- `OUTCOME_UNKNOWN`: koneksi putus/response hilang; jangan menyimpulkan gagal/sukses.
- `SERVER_ACCEPTED`: hanya setelah receipt Agent 2 diverifikasi.
- `CONFLICT`: kedua versi diarsipkan; submit blocked.
- `LOCAL_SAVE_FAILED`: IndexedDB write gagal; perubahan terakhir tidak tersimpan.
- `SUBMIT_READY`: semua gate lokal dan server terverifikasi.
- `SUBMITTING`: submit request telah dikirim.
- `SUBMIT_OUTCOME_UNKNOWN`: response submit hilang; lakukan lookup/retry sesuai kontrak Agent 2.
- `SUBMITTED`: hanya setelah hasil definitif server diverifikasi.
- `SUBMIT_BLOCKED`: satu atau lebih syarat tidak terpenuhi.

Status di atas hanya state UI/client; bukan enum backend.

## 4. Barrier sebelum submit

Client hanya boleh membuka tindakan submit ketika seluruh kondisi berikut terpenuhi:

1. Semua perubahan jawaban yang harus disinkronkan telah berhasil ditulis ke IndexedDB.
2. Setiap request answer yang relevan memiliki receipt server terverifikasi.
3. Tidak ada item pending, queued, sending atau outcome unknown.
4. Tidak ada request network aktif yang dapat mengubah jawaban.
5. Tidak ada conflict terbuka.
6. Server telah memverifikasi Attempt masih sah dan belum terminal/expired.
7. Registered device sesuai.
8. Device Lease masih valid dan belum revoked/expired.
9. Fencing generation masih current.
10. Tidak ada status backend yang menolak submit.
11. Question order/snapshot context yang digunakan cocok dengan Attempt.
12. Tidak ada local storage failure yang menyebabkan jawaban terbaru hilang.

Client tidak menentukan sendiri apakah semua pertanyaan wajib dijawab; validasi kelengkapan jawaban tetap server-side. Client hanya memastikan seluruh perubahan lokal yang dibuat telah diketahui hasil server.

## 5. Urutan submit client

1. Freeze input sementara dan tampilkan proses sinkronisasi.
2. Ambil snapshot lokal dari seluruh answer/queue dalam scope Attempt.
3. Tunggu seluruh request aktif selesai atau masuk OUTCOME_UNKNOWN.
4. Flush queue secara berurutan menggunakan request ID dan payload yang sama.
5. Verifikasi setiap receipt sesuai kontrak Agent 2.
6. Jika ada conflict, local persistence failure, pending atau response unknown, batalkan barrier dan buka kembali hanya tindakan yang aman.
7. Revalidate Attempt, device, lease dan fencing melalui mekanisme Agent 2.
8. Minta kontrak submit server; gunakan idempotency key yang ditetapkan Agent 2.
9. Kirim submit hanya setelah barrier lolos.
10. Verifikasi response definitif atau lookup hasil jika response hilang.
11. Tampilkan SUBMITTED hanya setelah bukti server diverifikasi.
12. Bila server menyatakan status terminal lain, tampilkan status persis sesuai kontrak; jangan memaksa label submitted.

Tidak boleh mengirim submit sambil ada answer write yang hasilnya belum diketahui.

## 6. Response hilang dan recovery

Jika request submit terkirim tetapi response hilang:
- status client menjadi SUBMIT_OUTCOME_UNKNOWN;
- jangan mengirim key baru;
- jangan membuat Attempt baru;
- jangan menganggap submit gagal atau berhasil;
- panggil lookup/retry hanya dengan cara yang ditetapkan Agent 2;
- bila browser reload, baca local state dan query server status sebelum tindakan berikutnya;
- pertahankan jawaban/receipt lokal sampai status server definitif.

Jika server sudah commit tetapi client tidak menerima response, retry identik harus mengembalikan hasil idempotent yang sama sesuai kontrak Agent 2.

## 7. Force Submit dan expiry

Force Submit adalah tindakan backend terotorisasi oleh actor yang memiliki permission, bukan tindakan client siswa. Agent 2 menetapkan:
- actor/role/permission;
- alasan wajib;
- before/after;
- audit event;
- locking dengan answer update dan submit;
- perilaku request yang tiba setelah force submit;
- status terminal;
- idempotency dan recovery response.

Expiry otomatis menggunakan waktu server. Scheduler harus serial terhadap answer write dan submit. Browser tidak boleh menyelesaikan Attempt hanya karena timer lokal habis. Timer lokal hanya indikator perkiraan dan harus sinkron dengan waktu/status server.

## 8. Kontrak backend yang harus dijawab Agent 2

1. Submit idempotency key: format, scope uniqueness, lifetime.
2. Request shape dan endpoint existing yang harus dipakai.
3. Response sukses definitif dan cara verifikasi.
4. Status terminal yang mungkin dikembalikan.
5. Lookup result ketika response hilang.
6. Duplicate submit dengan key sama/berbeda.
7. Answer update vs submit race dan global lock order.
8. Answer update vs Force Submit race.
9. Expiry vs answer request yang sedang berjalan.
10. Submit setelah lease expiry/revoke/takeover.
11. Submit dengan stale fencing generation.
12. Submit saat konflik server-side.
13. Force Submit actor, permission, reason, audit.
14. Auto-expiry dan score generation transaction.
15. Retensi idempotency key dan submit receipt.
16. Cara memastikan answer writes yang commit sebelum submit masuk scoring.
17. Perilaku deadlock/retry dan concurrency test.

Agent 4 tidak menentukan endpoint, header, field, HTTP status, enum, idempotency key atau DTO.

## 9. UX status yang jujur

Label UI harus membedakan:
- Tersimpan di perangkat.
- Menunggu sinkronisasi.
- Sedang dikirim.
- Diterima server (receipt terverifikasi).
- Konflik — perlu penyelesaian.
- Gagal disimpan di perangkat.
- Hasil submit belum diketahui — memeriksa status.
- Submit ditahan beserta alasan.

Jangan memakai kata "tersimpan" tanpa menjelaskan apakah lokal atau server. Jangan memberi indikator hijau server accepted hanya karena fetch/request resolve.

## 10. Acceptance tests

| ID | Skenario | Hasil |
|---|---|---|
| SUB-01 | Semua answer receipts verified, queue kosong | Barrier dapat dibuka |
| SUB-02 | Satu pending queue | Submit ditahan |
| SUB-03 | Request answer sedang berjalan | Submit menunggu/ditahan |
| SUB-04 | Response answer hilang | Submit ditahan; retry identik |
| SUB-05 | Conflict terbuka | Submit ditahan; dua versi dipertahankan |
| SUB-06 | IndexedDB write gagal | Tidak ada network answer/submit; error terlihat |
| SUB-07 | Lease expired/revoked | Submit ditahan; tidak auto takeover |
| SUB-08 | Stale fencing generation | Submit ditolak |
| SUB-09 | Attempt expired/terminal | Server status menang; client tidak submit |
| SUB-10 | Answer vs submit race | Tidak ada answer commit setelah terminal yang mengubah hasil |
| SUB-11 | Force Submit vs answer race | Serial dan audit lengkap |
| SUB-12 | Double submit | Satu efek final; replay idempotent |
| SUB-13 | Submit response hilang | Lookup/retry mengembalikan hasil yang sama |
| SUB-14 | Auto-expiry vs pending queue | Status definitif server; local pending tidak dibuang |
| SUB-15 | Question order invalid | Submit/scoring fail closed |

## 11. Approval dan status

- Agent 2 submit/Force Submit/expiry: **PENDING**.
- Agent 2 receipt/revision/lease/fencing: **PENDING**.
- Agent 1 scheduler/Proktor recovery: **PENDING**.
- Koordinator integrasi: **PENDING**.
- Submit barrier client: **NOT IMPLEMENTED**.
- Tests: **NOT RUN**.
- Web Siswa: **NOT READY FOR OPERATIONAL EXAM**.

Tidak ada source, API, route, migration, schema, database atau konfigurasi yang diubah.


## Addendum backend reconciliation — 22 November 2026, 18:20 WIB

The current backend source does not yet provide the approved submit contract required by this barrier:
- no client submit idempotency key was found in ExamAttemptController::submit;
- no definitive submit-result lookup after a lost response was found;
- answer update lock order Attempt→Answer differs from submit Exam→Participant→Attempt;
- no independently verifiable Answer Receipt exists for the client to check;
- Force Submit/expiry/answer race behavior has not been approved as a cross-agent contract.

The existing candidate runner uses in-memory pending/saving Maps. Its 409 branch deletes the pending item and replaces the local answer with current_answer. The automatic expiry/submit path has not been proven to wait for all durable local writes and verified server receipts. This is not a safe submit barrier.

Keep the client barrier as design only. Do not infer HTTP success as receipt verification, do not send a new request ID to hide an unknown result, and do not claim submit success without a definitive server result. Status: **BLOCKED — ANSWER RECEIPT/CONFLICT/SUBMIT CONTRACT PENDING**.
