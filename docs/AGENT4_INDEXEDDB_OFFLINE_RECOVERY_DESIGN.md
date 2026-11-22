# Agent 4 — Desain IndexedDB, Offline Recovery, dan Submit Barrier

Tanggal: 22 November 2026  
Status: DESAIN FRONTEND — BELUM DIIMPLEMENTASIKAN  
Backend contract: MENUNGGU APPROVAL AGENT 2

## 1. Prinsip

IndexedDB adalah durable local persistence untuk browser Web Siswa. Ia tidak menggantikan server sebagai sumber kebenaran, tidak menggantikan Device Lease/fencing, dan tidak menjamin browser tidak menutup tab atau kehilangan perangkat.

Jawaban tidak boleh dianggap aman hanya karena berada di memory atau karena request telah dikirim.

## 2. Object store

Pertahankan enam object store konseptual berikut:

| Object store | Fungsi | Identitas/kunci yang wajib |
|---|---|---|
| `attempt_context` | Konteks Attempt, User, Tenant, Exam, perangkat, schema lokal | tenant + user + exam + attempt + device |
| `answers` | Jawaban lokal, local revision, server revision terakhir, status | tenant + user + exam + attempt + device + snapshot |
| `sync_queue` | Antrean request idempotent | scope Attempt + snapshot + request ID |
| `server_receipts` | Bukti penerimaan server yang telah diverifikasi | scope Attempt + snapshot + request ID + server revision |
| `conflicts` | Arsip versi lokal dan server | scope Attempt + snapshot + conflict ID |
| `local_meta` | Versi schema lokal, metadata recovery, waktu sync | scope Attempt + device + key |

Jangan menggunakan ID soal saja sebagai primary key jawaban lokal.

## 3. Model data konseptual

### answers
- tenant_id
- user_id
- exam_id
- attempt_id
- device_id
- snapshot_id
- local_revision
- last_server_revision
- answer_payload
- answer_state
- local_saved_at
- sync_status

### sync_queue
- request_id (stabil untuk retry)
- attempt scope
- snapshot_id
- base_revision
- payload immutable untuk request tersebut
- answer_state
- created_at
- retry_count
- delivery_state

### server_receipts
- request_id
- snapshot_id
- accepted_revision
- accepted_at/server timestamp
- response digest/receipt reference sesuai kontrak Agent 2

### conflicts
- local payload/revision
- server payload/revision
- request_id yang konflik
- conflict status
- resolution actor/time/reason jika ada workflow resolusi

Model di atas adalah desain lokal. Bukan nama tabel, enum, atau schema backend.

## 4. State machine konseptual

- `LOCAL_ONLY`: jawaban tersimpan lokal, belum antre.
- `PENDING_SYNC`: antrean dibuat.
- `SENDING`: request sedang diproses.
- `SERVER_ACCEPTED`: receipt valid diverifikasi.
- `CONFLICT`: versi lokal dan server berbeda.
- `RETRY_REQUIRED`: pengiriman gagal dan dapat dicoba ulang.
- `SUBMIT_BLOCKED`: submit belum memenuhi syarat.

Status tersebut bukan enum backend dan belum menjadi kontrak API.

## 5. Write-before-send dan idempotensi

1. Tulis jawaban dan antrean secara transaksional ke IndexedDB.
2. Baru setelah transaksi lokal sukses, kirim request.
3. Request retry wajib menggunakan ID dan payload yang sama.
4. Jawaban lokal tidak dihapus hanya karena request sudah dikirim.
5. Status SERVER_ACCEPTED hanya setelah receipt server diverifikasi.
6. Jika respons hilang, ulangi request identik dan periksa hasil server.
7. Jangan membuat request baru dengan isi berbeda untuk menutupi request lama yang hasilnya belum diketahui.

Jika IndexedDB gagal, tampilkan kegagalan penyimpanan lokal secara eksplisit dan jangan mengklaim jawaban tersimpan.

## 6. Conflict handling

HTTP 409 tidak boleh menghapus jawaban lokal atau langsung memilih versi server/lokal.

Saat konflik:
- simpan payload lokal;
- simpan response/current version server;
- simpan base revision dan request ID;
- tandai CONFLICT;
- blok submit;
- tampilkan informasi konflik yang dapat dipahami;
- tunggu aturan resolusi yang disetujui Agent 2.

Tidak boleh ada silent overwrite.

## 7. Submit barrier

Submit hanya dapat diteruskan bila seluruh syarat berikut benar:

1. Semua jawaban yang harus disinkronkan memiliki receipt server terverifikasi.
2. Antrean pending kosong.
3. Tidak ada request yang sedang berjalan.
4. Tidak ada konflik terbuka.
5. Attempt masih sah menurut server.
6. Device Lease dan fencing masih valid.
7. Status expiry/terminal Attempt belum menolak submit.
8. Server mengonfirmasi hasil submit/idempotency secara definitif.

Jika salah satu syarat tidak terpenuhi, submit ditahan dan UI menunjukkan alasan.

## 8. Recovery

### Reload/browser crash
- buka IndexedDB;
- validasi scope tenant/user/exam/attempt/device;
- rekonstruksi jawaban dan antrean;
- jangan menghapus jawaban tanpa receipt;
- verifikasi status Attempt dan lease ke server sebelum melanjutkan.

### Offline → online
- pertahankan antrean;
- kirim berurutan per snapshot sesuai revision;
- gunakan request ID/payload sama;
- verifikasi receipt satu per satu.

### Respons server hilang
- jangan menganggap gagal atau sukses;
- retry request identik;
- verifikasi revision dan receipt.

### Dua tab
- gunakan koordinasi lintas tab untuk menghindari dua writer lokal;
- deteksi revision berubah;
- jangan menimpa jawaban tanpa resolusi;
- mekanisme tab bukan pengganti lease/fencing backend.

### Storage quota penuh / IndexedDB tidak tersedia
- hentikan klaim penyimpanan aman;
- tampilkan error yang terlihat;
- jangan diam-diam fallback ke memory;
- submit tetap diblokir bila ada jawaban yang belum dapat disimpan/sinkron.

## 9. Security dan privasi

- Scope semua record dengan tenant/user/exam/attempt/device/snapshot.
- Jangan simpan token rahasia atau lease credential secara sembarangan.
- Hindari menyimpan data identitas yang tidak diperlukan.
- Validasi konteks server sebelum resume.
- Data lokal dari Attempt lama tidak boleh ditampilkan kepada akun/perangkat berbeda.
- Aturan retensi/penghapusan lokal harus mengikuti status terminal dan kebijakan produk; jangan hapus data yang belum memiliki receipt.

## 10. Ketergantungan kontrak Agent 2

Sebelum implementasi, Agent 2 harus menetapkan:
- format request answer;
- client_request_id/idempotency semantics;
- base_revision/current revision;
- receipt response dan cara verifikasi;
- response konflik;
- stale request behavior;
- lease token dan fencing generation;
- expiry/revoke/takeover behavior;
- submit idempotency/result lookup;
- aturan Force Submit dan expiry.

Agent 4 tidak menetapkan endpoint, header, field, HTTP status, atau DTO backend.

## 11. IMPLEMENTASI

**NONE — DOCUMENTATION ONLY.** IndexedDB belum dibuat atau dihubungkan ke runner.

## 12. PENGUJIAN

- IndexedDB schema/transaction: NOT RUN.
- Atomic answer + queue write: NOT RUN.
- Quota exceeded: NOT RUN.
- Reload/crash recovery: NOT RUN.
- Offline-online replay: NOT RUN.
- HTTP 409 preservation: NOT RUN.
- Lost response idempotent retry: NOT RUN.
- Multi-tab writer conflict: NOT RUN.
- Submit barrier: NOT RUN.
- Device lease/fencing: BLOCKED — backend contract/runtime belum tersedia.
