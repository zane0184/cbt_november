# Addendum Audit Agent 4 — tindak lanjut receipt dan fencing (23 November 2026)

## Ringkasan status
**NEEDS DESIGN — belum ada konfirmasi Agent 2 yang dapat diverifikasi.**

Pemeriksaan ulang langsung pada Issue #10 menemukan:
- Agent 2 contract: komentar #7083116647.
- Agent 4 review: komentar #7084085527.
- Tindak lanjut koordinator: komentar #7084160817.
- Tidak ada komentar Issue #10 dengan ID setelah #7084160817 pada saat audit ini dilakukan.
- Issue #10 tetap OPEN.

Koordinator telah menetapkan bahwa receipt wajib dapat diikat secara deterministik ke request yang dikirim client. Opsi A adalah menambahkan `client_request_id` ke request, ledger idempotensi, dan receipt. Opsi B hanya dapat diterima jika identifier ekuivalen immutable, unik per operasi, diketahui client sebelum request, disimpan server, dikembalikan pada receipt, dan diverifikasi tanpa tebakan atau pencocokan berdasar timestamp/revision saja.

## AUDIT — source aktual yang dibandingkan

Repository: `zane0184/cbt_november`

Branch Agent 4: `work/agent4-web-siswa-emergency-20261122`

Branch backend base PR #13: `work/stage4d-account-lifecycle-20261122`

PR #13:
- Head SHA: `9de231bcbc311292989694352459d1c7f392d6cb`.
- Base SHA: `7642f8df7d20effe82dc1ba5b086147894332767`.
- Draft: true.
- Merged: false.
- Changed files: 45.
- Compare base-to-head: 96 commits ahead, 0 behind.
- Runner file blob: `2d1f868bdc877188d2bcd4925c1be3dcec153bc9`.

File backend yang diperiksa di branch base PR:
| File | Blob SHA |
|---|---|
| `ExamAttemptController.php` | `13e8981cca4653ff4e9b9448a50e4d0ec8a74bae` |
| `ExamAnswerController.php` | `b4ddec14f6208b3ecde9bfc3e45168a2159e2a9c` |
| `MonitoringController.php` | `90fcfea595c10918e2c3866363356f688452a0a4` |
| `MediaController.php` | `f852c1b3a7247efdbb0c309fb0413bbe5e3eaa62` |
| `routes/api.php` | `2e43c37dd669a26446a44b818c1d2c8420b15aca` |

Temuan statis:
1. Source answer controller sudah memakai `client_request_id` dalam request dan menyimpan pada record jawaban untuk idempotensi yang ada.
2. Source yang diperiksa belum menunjukkan immutable receipt berisi `client_request_id` atau identifier ekuivalen.
3. Source yang diperiksa belum menunjukkan `payload_hash`/ledger immutable sesuai kontrak baru.
4. Source yang diperiksa belum menunjukkan header `X-CBT-Fence-Generation`, enforcement `ATTEMPT_FENCE_MISMATCH`, atau respons `lease_expires_at`.
5. Route heartbeat ada, tetapi controller yang diperiksa masih memakai `X-Device-Binding-ID`/device fingerprint dan belum menunjukkan auth-session lease/fence generation.
6. Media controller/route yang diperiksa belum menunjukkan enforcement generation.
7. Runner Agent 4 masih belum menyimpan fence generation/lease expiry, tidak mengirim header generation, belum memiliki heartbeat Web, belum memverifikasi receipt, dan memuat media dengan elemen browser native tanpa mekanisme header fencing.

Penting: keberadaan `client_request_id` pada jawaban bukan bukti bahwa receipt memiliki binding yang diminta koordinator. Harus diverifikasi bahwa nilai yang sama dipersistenkan secara immutable pada ledger operasi dan dikembalikan oleh receipt.

## DESAIN — keputusan yang tetap berlaku

- `fence_generation` dan `lease_expires_at` disimpan pada record `attempt_context` per Attempt.
- Generation hanya diperbarui dari respons server yang sah dan diotorisasi oleh auth-session yang sama.
- Generation harus dipersistenkan ke IndexedDB sebelum request Attempt-scoped berikutnya berjalan.
- Jangan menaikkan generation lokal dari HTTP 409.
- Queue lama dengan request ID, payload, dan base revision immutable ditahan saat generation berubah. Tidak boleh auto-replay atau auto-rebase saat status commit belum pasti.
- Receipt diverifikasi paling sedikit terhadap request identifier, Attempt ID, snapshot ID, revision number, dan payload hash. Receipt terverifikasi disimpan immutable pada `server_receipts`.
- HTTP 200 atau revision number saja bukan bukti receipt terverifikasi.
- `X-Device-Binding-ID` dipertahankan sebagai identitas tambahan selama backend masih memerlukannya; bukan pengganti auth-session binding/fencing.
- Mekanisme media harus kompatibel dengan otorisasi fencing. Jangan menambahkan bearer token buatan sebagai query parameter pada client.
- Heartbeat cadence awal 60 detik adalah usulan client, bukan keputusan final sampai Agent 2 mengonfirmasi pembaruan lease, response schema, dan batas server.
- Status desain receipt tetap **NEEDS DESIGN**.

## IMPLEMENTASI
- Tidak ada source runtime yang diubah dalam audit ini.
- Tidak ada backend Agent 2/3, route, migration, schema, database, Web Admin/Guru, VPS, atau ZIP baseline yang diubah.
- Addendum ini hanya dokumentasi audit/checkpoint pada branch Agent 4.
- Tidak ada merge/rebase/cherry-pick/deployment.

## PENGUJIAN
Dilakukan:
- Fetch Issue #10 dan seluruh komentar yang tersedia; konfirmasi komentar #7084160817 merupakan komentar terbaru yang ditemukan.
- Fetch PR #13; memastikan Draft/open/unmerged.
- Compare branch Agent 4 dengan base PR; 96 ahead, 0 behind.
- Fetch dan pemeriksaan statis terhadap lima file backend serta runner; catat exact blob SHA di atas.

Belum dijalankan:
- PHPUnit/PHP lint pada source integration HEAD.
- Uji ledger/receipt/idempotency pada MySQL 8.
- Test ID sama + payload sama mengembalikan receipt asli.
- Test ID sama + payload berbeda ditolak `409 IDEMPOTENCY_KEY_REUSED`.
- Pemeriksaan status receipt tanpa mengirim ulang payload.
- Fencing/auth-session integration, heartbeat/lease expiry/recovery.
- Media access dengan generation.
- Browser E2E/IndexedDB runtime, multi-tab, multi-device, jaringan putus/pulih, expiry-submit race, dan UAT.

## Pekerjaan berikutnya
1. Tunggu konfirmasi Agent 2 beserta branch, commit, file/class, exact schema, dan test hasil nyata.
2. Cocokkan opsi receipt A/B dengan ledger immutable dan endpoint pemeriksaan status receipt.
3. Jangan mengubah runner untuk mengklaim receipt terverifikasi sebelum kontrak tersedia.
4. Setelah kontrak backend tersedia, siapkan patch client dengan backup byte-exact per file, lalu uji API dan browser runtime.
5. Pertahankan PR #13 Draft sampai semua blocker backend dan browser E2E ditutup.

## Gate
Web Siswa belum dinyatakan siap untuk ujian resmi/produksi. PR #13 tidak boleh di-merge atau di-deploy berdasarkan hasil audit ini.
