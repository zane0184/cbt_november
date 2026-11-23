# Agent 4 — Gate Integrasi Browser ke Laravel Web Siswa
Tanggal: 23 November 2026
Status: DISIAPKAN — belum dapat dijalankan terhadap kontrak final Agent 2.

## AUDIT
PR #13 berada pada branch `work/agent4-web-siswa-emergency-20261122` dan tetap Draft. Browser E2E yang sudah ada menjalankan fixture HTML runner dengan API mock; tes tersebut bukan bukti browser-to-Laravel integration.

Berdasarkan review koordinasi Agent 2 pada Issue #10, beberapa kontrak backend masih perlu dibuktikan pada satu branch/commit yang menjadi base integrasi:
- `require_token=false` harus mengizinkan start tanpa token; saat ini ada temuan kondisi controller yang membalik semantik opsional.
- Receipt answer harus mengikat `client_request_id`, Attempt, snapshot, revision, payload hash, dan waktu commit; receipt yang dapat diverifikasi harus dikembalikan oleh API.
- Status/receipt read-only atau mekanisme exact-retry immutable harus memiliki semantik response-loss dan race yang jelas.
- Attempt authorization/attempt limit dan concurrency harus lulus MySQL 8/InnoDB.
- Web session harus ditolak untuk Attempt pada Android browser; Android native memakai credential/capability server-issued. Jangan memakai User-Agent sebagai security boundary.
- Media harus terotorisasi dengan session/fence, MIME allowlist, ukuran, dan Range/seek untuk audio/video.

## DESAIN
Integrasi dilakukan hanya setelah Agent 2 menyampaikan exact branch + commit, route, middleware, request/response schema, dan test backend. Tidak cherry-pick seluruh branch Agent 2 ke branch Agent 4 dan tidak mengubah route, controller, migration, atau database milik Agent 2.

### Skenario browser-to-Laravel wajib
1. Login NISN/password melalui browser; sesi Web terikat pada satu instalasi dan satu user SISWA.
2. Daftar ujian hanya menampilkan penugasan siswa sendiri; tenant/installasi lain tidak terlihat.
3. `require_token=false`: halaman tidak menampilkan input token; POST start tanpa token berhasil.
4. `require_token=true`: input wajib; kosong/salah ditolak, token benar dinormalisasi dan diterima.
5. Sebelum `start_at`, start ditolak; tepat pada batas mulai sesuai aturan backend, start diizinkan; setelah `end_at`, start baru ditolak.
6. Attempt limit habis: start baru ditolak, active Attempt yang sah tetap dapat dipulihkan sesuai kontrak.
7. Start mengembalikan Attempt ID + generation + lease expiry; client menyimpan konteks secara durable sebelum request Attempt-scoped berikutnya.
8. Jawaban disimpan ke Laravel; receipt cocok persis pada request ID, Attempt, snapshot, revision, hash payload. Exact retry tidak menggandakan Answer/ledger/audit mutation.
9. Response loss: recovery tidak membuat Attempt baru, tidak mengganti request ID/payload, dan tidak menghapus queue tanpa receipt valid.
10. Fencing: dua browser/session berbeda pada Attempt sama; sesi lama ditolak setelah takeover/lease rotation. Uji concurrency dua koneksi pada DB disposable.
11. Offline: draft IndexedDB bertahan reload; reconnect melakukan heartbeat/recovery dahulu; antrean hanya dikirim setelah generation valid.
12. Media image/audio/video/PDF: authorized Fetch/Blob, MIME/size validation, 401/403/409 fail-closed, audio/video Range/seek, Object URL cleanup.
13. Submit bersamaan dengan answer/expiry: hanya satu terminal transition; tidak ada jawaban hilang atau submit duplikat.
14. Hasil hanya milik siswa sendiri dan hanya ditampilkan jika dipublikasikan serta FINALIZED.
15. Logout menghapus sesi; halaman siswa tidak dapat dibuka kembali dengan sesi yang sudah logout.

## PENGUJIAN
Workflow integrasi yang disiapkan harus menggunakan:
- MySQL 8/InnoDB disposable, bukan database produksi.
- Laravel server nyata dan browser Playwright yang mengakses route/API Laravel nyata.
- Test credentials dan data fixture khusus tenant/installation test.
- Log request/response yang disanitasi; jangan menyimpan password, token ujian, session cookie, atau private key di artefak.
- Test concurrency two-connection terpisah dan wajib berjalan (bukan SKIPPED).

Status saat ini: belum ada klaim integrasi lulus. Mock E2E hanya memvalidasi perilaku client pada kontrak simulasi.
