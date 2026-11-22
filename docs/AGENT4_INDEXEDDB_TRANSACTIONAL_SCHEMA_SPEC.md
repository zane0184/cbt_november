# Agent 4 — Spesifikasi IndexedDB Transaksional Web Siswa

Tanggal: 22 November 2026  
Repository: `zane0184/cbt_november`  
Branch dokumentasi: `work/agent4-web-siswa-audit-20261122`  
Owner: Agent 4 (browser persistence/queue/recovery); backend receipt, lease dan revision contract milik Agent 2.  
Status: **DESIGN PROPOSAL — NOT IMPLEMENTED — BACKEND CONTRACT PENDING**.

## 1. Batas dan prinsip

IndexedDB hanya penyimpanan lokal durable best-effort. Server tetap sumber kebenaran Attempt, waktu, admission, revision accepted, lease/fencing dan submit. Browser storage dapat dihapus, dibatasi quota, atau gagal; tidak ada jaminan browser tidak ditutup.

Satu instalasi = satu madrasah = satu database akademik sendiri. Data IndexedDB harus terikat pada instalasi/tenant, user, Exam, Attempt, device dan snapshot. Tidak ada sinkronisasi akademik lintas instalasi.

Enam object store konseptual dipertahankan:
1. `attempt_context`
2. `answers`
3. `sync_queue`
4. `server_receipts`
5. `conflicts`
6. `local_meta`

Nama ini hanya schema lokal browser, bukan tabel/schema API backend.

## 2. Scope key dan record identity

Semua record yang relevan harus mencakup scope instalasi/tenant, user, exam, attempt, device dan snapshot/question. Jangan menggunakan Question ID saja sebagai key.

KeyPath compound yang diusulkan untuk database lokal versi pertama:
- `attempt_context`: [installation_key, tenant_id, user_id, exam_id, attempt_id, device_id].
- `answers`: [installation_key, tenant_id, user_id, exam_id, attempt_id, device_id, snapshot_id].
- `sync_queue`: [installation_key, tenant_id, user_id, exam_id, attempt_id, device_id, snapshot_id, client_request_id].
- `server_receipts`: [installation_key, tenant_id, user_id, exam_id, attempt_id, device_id, snapshot_id, client_request_id].
- `conflicts`: [installation_key, tenant_id, user_id, exam_id, attempt_id, device_id, snapshot_id, conflict_id].
- `local_meta`: [installation_key, tenant_id, user_id, exam_id, attempt_id, device_id, meta_key].

Nama `installation_key` di sini adalah identifier instalasi publik/non-secret yang harus disepakati arsitektur; bukan credential atau pemilih tenant dari browser. Jangan mengandalkan nilai client untuk otorisasi server.

## 3. Field konseptual per store

### attempt_context
- scope identity di atas;
- server-observed Attempt status;
- snapshot identity/version;
- question order hash/reference;
- local schema version;
- last server verification timestamp;
- last known server revision/time marker;
- local recovery state.
Jangan menyimpan password, bearer token, refresh token, lease secret atau credential otorisasi mentah.

### answers
- scope identity;
- answer payload dan answer state;
- local_revision monotonic per snapshot;
- last_verified_server_revision;
- active_client_request_id bila ada;
- local_saved_at;
- sync state;
- payload checksum untuk mendeteksi korupsi.
Payload lokal belum berarti diterima server.

### sync_queue
- scope identity;
- client_request_id yang stabil;
- base_revision dari server;
- immutable request payload dan payload hash;
- sequence per Attempt/snapshot;
- created_at, retry_count, next_retry_at;
- delivery state: queued/sending/unknown/blocked;
- last error category yang sudah disanitasi.
Setiap retry menggunakan request ID dan payload byte/logical-identical yang sama.

### server_receipts
- scope identity;
- client_request_id;
- server revision accepted;
- server timestamp;
- receipt/reference/digest sesuai kontrak Agent 2;
- verification result/version.
Jangan menyimpan receipt sebagai verified sebelum verifikasi sesuai mekanisme Agent 2.

### conflicts
- scope identity;
- conflict ID lokal;
- request ID dan base revision;
- immutable local payload/revision;
- server payload/revision yang diterima;
- conflict status;
- resolution actor/time/reason hanya jika backend menyediakan workflow yang sah.
Kedua versi dipertahankan. Tidak ada silent overwrite.

### local_meta
- schema version;
- migration marker;
- tab writer coordination metadata;
- last sync checkpoint;
- quota/error flags;
- retention metadata;
- per-attempt sequence counter.
Jangan menyimpan credential lease.

## 4. IndexedDB versioning dan upgrade transaction

Rancangan awal:
- Database mempunyai nama khusus aplikasi Web Siswa dan instalasi, bukan satu database global lintas madrasah.
- Version 1 membuat enam object store dan index minimal.
- Upgrade dilakukan hanya melalui `versionchange` transaction native IndexedDB.
- Semua upgrade bersifat deterministic, additive dan dapat rollback oleh browser jika upgrade transaction abort.
- Tab lama harus menutup koneksi saat `versionchange`; jika blocked, tampilkan pesan agar tab lain ditutup, jangan memaksa menghapus database.
- Jangan menghapus store atau record yang masih memiliki pending request, receipt belum diverifikasi atau conflict terbuka.
- Catat schema version dan migration marker di `local_meta`.
- Jangan menjalankan migrasi lokal bersamaan dengan writer tab lain.

Perubahan keyPath tidak dapat diperlakukan sebagai perubahan kolom biasa. Bila suatu versi membutuhkan keyPath baru, buat store pengganti sementara dalam upgrade transaction, salin record, validasi jumlah dan checksum, baru alihkan pembacaan; store lama tidak dihapus sebelum validasi berhasil. Store sementara hanya artefak migrasi, bukan store bisnis ketujuh yang dipertahankan.

## 5. Index yang diusulkan

Index minimum:
- `attempt_context`: byAttempt, byUserDevice.
- `answers`: byAttemptSnapshot, bySyncState.
- `sync_queue`: byAttemptSequence, byDeliveryState, byRequestId.
- `server_receipts`: byAttemptSnapshot, byRequestId, byServerRevision.
- `conflicts`: byAttemptStatus, byRequestId.
- `local_meta`: byAttemptKey, bySchemaVersion.

Index harus non-unique kecuali uniqueness benar-benar menjadi invariant lokal. Server tetap memutuskan uniqueness idempotency request.

## 6. Atomic write-before-send

Satu edit jawaban harus dilakukan dalam satu `readwrite` transaction yang mencakup `answers`, `sync_queue` dan `local_meta`:
1. Baca local answer dan server revision terakhir yang telah diverifikasi.
2. Bentuk local revision baru.
3. Bentuk request ID sekali; simpan payload immutable dan hash.
4. Tulis jawaban lokal.
5. Tulis queue entry.
6. Update sequence/meta.
7. Commit transaction.
8. Hanya setelah commit sukses, request network boleh dikirim.

Jika transaksi abort, tidak ada request yang dikirim dan UI menyatakan perubahan terbaru gagal disimpan lokal. Jawaban sebelumnya yang committed tetap dipertahankan. Tidak ada fallback diam-diam ke memory.

Receipt transaction mencakup `server_receipts`, `sync_queue` dan `answers`: simpan receipt setelah diverifikasi, ubah status jawaban menjadi server accepted, lalu hapus/mark completed queue entry dalam transaksi yang sama. Jangan menghapus payload lokal sampai receipt sah tersimpan.

## 7. Queue ordering dan retry

- Queue diurutkan per Attempt dan snapshot berdasarkan sequence/revision.
- Hanya satu writer request aktif per answer key. Jangan mengirim dua update revision yang sama secara paralel.
- Request berbeda snapshot dapat diparalelkan hanya jika Agent 2 menyatakan lock/receipt semantics aman; default awal adalah serial per Attempt.
- Retry menggunakan request ID dan payload yang sama. Tidak membuat request baru hanya karena response hilang.
- Backoff usulan: 1s, 2s, 4s, 8s, 16s, maksimum 30s dengan jitter; saat offline hentikan retry dan lanjut setelah koneksi pulih. Batas ini usulan client, perlu review UX/koordinator dan tidak mengubah backend.
- Tidak ada max retry yang menghapus queue. Setelah error permanen atau kontrak conflict, tandai blocked dan tampilkan tindakan yang sah.
- Duplicate response harus menghasilkan receipt yang sama sesuai kontrak Agent 2.

## 8. Conflict archive

Pada stale revision/HTTP 409:
- jangan menghapus local answer;
- jangan mengganti local answer dengan server payload;
- simpan server payload/revision sebagai sisi conflict;
- simpan local payload/revision, request ID, base revision dan hash;
- ubah queue menjadi blocked/conflict, bukan retry dengan payload baru;
- blok submit;
- tunggu resolusi yang ditentukan Agent 2/authorized actor.
Tidak boleh memilih versi secara otomatis.

## 9. Multi-tab coordination

- Hanya satu tab menjadi writer aktif untuk scope Attempt/device.
- Koordinasi tab dapat menggunakan mekanisme browser yang tersedia (mis. Web Locks/BroadcastChannel) sebagai optimasi, bukan security boundary.
- Fallback harus melakukan compare-and-set dalam transaksi IndexedDB pada metadata lock; tab yang gagal memperoleh writer lease lokal tidak boleh mengirim write.
- Local tab lock bukan Device Lease backend dan tidak menggantikan fencing generation.
- Setelah tab kehilangan koordinasi, request yang sudah dikirim tetap harus dilindungi backend melalui idempotency/revision/fencing.
- Jangan menganggap browser lock menjamin satu tab selamanya.

## 10. Credential separation

- Jangan menaruh password, bearer token, refresh token, lease secret atau signed credential dalam enam store.
- Jangan memasukkan credential ke backup/export atau log.
- Credential transport hanya boleh disimpan sesuai mekanisme autentikasi Agent 2 yang disetujui (misalnya cookie aman/credential runtime); dokumen ini tidak menetapkan mekanismenya.
- Bila lease credential perlu dipakai setelah reload, Agent 2 harus merancang re-auth/rebind/lease recovery; Agent 4 tidak boleh menyimpannya sendiri.
- Simpan hanya non-secret reference/metadata yang disetujui.

## 11. Quota dan storage failure

- Gunakan estimasi quota hanya untuk peringatan; bukan jaminan kapasitas.
- Tangkap `QuotaExceededError`, transaction abort, database unavailable, versionchange blocked dan corruption.
- Tidak ada fallback memory yang diklaim durable.
- Jangan mengirim request jika local write belum commit.
- Pertahankan record committed sebelumnya.
- UI menampilkan `GAGAL DISIMPAN DI PERANGKAT`, bukan `TERSIMPAN`.
- Submit diblokir jika perubahan yang diwajibkan belum tersimpan/sinkron.
- Jangan membersihkan pending/conflict otomatis untuk mengatasi quota.

## 12. Local migration dan cleanup retention

- Migration harus versioned dan diuji dengan fixture database lokal dari versi sebelumnya.
- Simpan backup logical sementara di transaction/store migration hanya jika migrasi mengubah format record; validasi count dan checksum sebelum switch.
- Jika upgrade gagal, rollback transaction dan biarkan versi lama dapat dibuka.
- Jangan menghapus attempt_context, answers, queue, receipt atau conflict ketika Attempt belum terminal atau masih ada item tanpa receipt.
- Cleanup hanya setelah status terminal diverifikasi server, seluruh queue resolved, seluruh receipt terverifikasi, conflict resolved, dan retention policy disetujui.
- Durasi retention dan kebijakan export/delete data siswa harus diputuskan koordinator/Agent 2; belum ditetapkan.
- Tidak ada cleanup yang menghapus histori server.

## 13. Acceptance tests

- IDB-01 create six stores and indexes.
- IDB-02 atomic answer+queue transaction.
- IDB-03 failed transaction sends no network request.
- IDB-04 quota failure preserves previous committed answer.
- IDB-05 retry keeps same request ID and payload.
- IDB-06 receipt verification and queue completion atomic.
- IDB-07 409 stores both versions and blocks submit.
- IDB-08 reload reconstructs scoped data.
- IDB-09 account/device/Attempt mismatch never displays old answers.
- IDB-10 schema upgrade preserves all records.
- IDB-11 blocked upgrade informs user and does not delete data.
- IDB-12 multi-tab writer arbitration and stale tab.
- IDB-13 no credential in IndexedDB/export/log.
- IDB-14 quota exhaustion and storage eviction recovery.

## 14. Approval, risks, test status

- Schema: **DESIGN PROPOSAL — NOT IMPLEMENTED**.
- Backend receipt/idempotency/revision: **PENDING Agent 2**.
- Lease/fencing credential: **PENDING Agent 2**.
- Retention/cleanup: **PENDING koordinator/Agent 2**.
- Multi-tab browser E2E: **NOT RUN**.
- IndexedDB transaction tests: **NOT RUN**.
- Offline/reload/crash recovery: **NOT RUN**.
- F-01/F-02: **P0 OPEN**.
- No source code changed.


## Addendum backend reconciliation — 22 November 2026, 18:20 WIB

The six IndexedDB stores remain conceptual only. Backend contracts are not approved. Current source shows:
- Answer row uniqueness is Attempt+snapshot; client_request_id is stored on the answer row, with no separate durable receipt entity found.
- Same client_request_id retry returns current stored answer metadata without comparing request payload equality; stale base_revision returns 409/current_answer.
- No formal receipt ID, signature/digest or lookup-after-lost-response contract has been approved.
- Existing runner in candidate branch uses in-memory Maps; it has no IndexedDB durability.
- HTTP 409 handling in runner deletes pending local item and can replace local answer with server current_answer; this violates the proposed conflict archive rule.
- Device lease runtime model/service/middleware is absent from Agent 2 branch tree; lease schema alone cannot validate queue replay.

Therefore schema key fields, receipt fields, request IDs, retry state, cleanup retention and credential handling remain provisional. Do not freeze IndexedDB version/keyPath/DTO mappings until Agent 2 signs revision, receipt, conflict, lease/fencing and idempotency semantics. Atomic write-before-send, no memory fallback, conflict archive, scoped keys and visible quota failure remain client acceptance principles, not implementation claims.

Status: **DESIGN ONLY — BACKEND CONTRACT PENDING; INDEXEDDB NOT IMPLEMENTED / NOT TESTED**.
