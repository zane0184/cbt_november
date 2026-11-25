# LAPORAN REKONSILIASI AWAL WEB ADMIN, GURU, SISWA, DAN BACKEND

Tanggal: 23 November 2026  
Repository: `zane0184/cbt_november`  
Branch integrasi: `work/solo-web-cbt-completion-20261123`  
PR integrasi: #21  
Status: **AUDIT AWAL / REKONSILIASI — BELUM SIAP PRODUKSI**

Dokumen ini dibuat setelah instruksi owner untuk memperbaiki keseluruhan proyek. Ini adalah rekonsiliasi awal antar-branch dan bukti yang tersedia; bukan klaim bahwa setiap fitur dari semua portal telah diaudit sampai tuntas.

## 1. AUDIT — kondisi GitHub aktual yang berhasil diverifikasi

### 1.1 PR dan branch
| Area | PR | Branch | HEAD PR yang diverifikasi | Status |
|---|---:|---|---|---|
| Integrasi Web CBT | #21 | `work/solo-web-cbt-completion-20261123` | `44c2b7e3f84f5fef4ff749add13a1fd75369fa6d` | OPEN / DRAFT / UNMERGED |
| Web Siswa Agent 4 | #13 | `work/agent4-web-siswa-emergency-20261122` | `9ef375c407b1faf606c5a268f00b24424d93be9e` | OPEN / DRAFT / UNMERGED |
| Web Guru | #19 | `work/web-guru-remove-xlsx-20261123` | `71296ca6e7c48b7295455a26d38bda11b901eb1d` | OPEN / DRAFT / UNMERGED |
| Web Admin legacy room/proctor | #18 | `work/web-admin-remediation-20261123` | `42a0de2c1f86b8e76e34e1b74ab0feaca90def3a` | OPEN / DRAFT / UNMERGED |
| Backend Agent 2 auth/fencing | branch independen | `work/agent2-p0-auth-session-fencing-20261123` | `b00cf4312b12e1158c609ca91af3dcecec7a2553` | Tidak diperlakukan sebagai branch integrasi |

PR #21 memiliki 255 file berubah. PR #13 126 file, PR #19 49 file, PR #18 58 file. Jumlah file bukan ukuran kelengkapan atau bukti lulus.

### 1.2 Temuan integrasi paling penting
- PR #21 saat ini belum memuat runner Web Siswa Agent 4 terbaru. Source runner PR #21 yang diperiksa masih blob `7283ca553d787f4505299a306e2bd487884622d3`; runner Agent 4 pada branch #13 sudah blob `0c539c3ac1a327fb53fc18b885172db7cd95b88d`.
- Karena itu, audit anti-copy/paste lama pada PR #21 tidak boleh dianggap menggambarkan runner terbaru PR #13. Agent 4 telah mengimplementasikan deterrent copy/cut/selection/context menu/drag/shortcut, mobile-first satu kolom di HP, drawer navigasi, touch target 44–48px, IndexedDB recovery, fence/heartbeat, dan receipt verifier pada client.
- PR #13 checkpoint final menyebut 26 skenario Chromium E2E PASS dan 13 Laravel/MySQL route tests PASS pada run #45069223927, tetapi bukti tersebut untuk SHA lama yang disebut dalam checkpoint; branch PR #13 sekarang HEAD `9ef375c...`. Verifikasi CI exact HEAD terbaru masih wajib.
- PR #21 belum mengintegrasikan backend auth-session/lease/fencing Agent 2. Backend branch Agent 2 memiliki service/model/migration khusus, tetapi tidak aman untuk merge keseluruhan karena branch tersebut sangat divergen dan memuat banyak pekerjaan historis/room-session yang bertentangan dengan keputusan arsitektur terbaru. Integrasi harus selektif dan berdasarkan kontrak.
- PR #18 room/session/multi-Proktor adalah pekerjaan historis yang tidak boleh otomatis diintegrasikan. Keputusan terbaru melarang ExamSession/wave/session_number sebagai lifecycle admission dan Proktor bukan portal/role permanen; tugas pengawasan hanya penugasan administratif sementara dan tidak membuka monitoring/Attempt/operasi CBT. Periksa Issue #20 dan checkpoint integrasi.
- PR #19 Web Guru mempunyai hasil CI historis 118 passed / 520 assertions pada run #45109872421, source SHA `91d8f6989c29c40ef836b244e0d0850bc22f4a99`. Checkpoint cabang menyatakan perubahan setelah SHA uji hanya dokumentasi, tetapi PR HEAD yang saat ini terdaftar adalah `71296ca...`; perlu verifikasi compare/CI exact PR HEAD sebelum menganggap bukti itu berlaku.
- Web Guru belum selesai: DOCX belum diimplementasikan; kurikulum assignment-specific, materi/topik, QuestionVersion → immutable snapshot provenance, Pembelajaran Mendalam, delapan dimensi profil lulusan, dan finalisasi kontrak bersama masih pending.
- Web Admin ADM-01–ADM-23 belum memiliki matriks bukti final yang lengkap pada branch integrasi. PR #18 tidak boleh dipakai sebagai shortcut karena memiliki konflik desain Proktor/room/session.

## 2. AUDIT BACKEND P0 — Auth Session, Attempt, Lease, Fencing

Branch Agent 2 yang diperiksa: `work/agent2-p0-auth-session-fencing-20261123`, HEAD `b00cf4312b12e1158c609ca91af3dcecec7a2553`.

### Implementasi yang sudah tersedia pada branch Agent 2
- HMAC digest identitas Laravel Web session / Sanctum token; tidak menyimpan raw session ID atau bearer token.
- Guard login Web/API yang memblokir login baru saat Attempt SISWA masih IN_PROGRESS.
- Binding Attempt ke auth session, selain device fingerprint.
- Device Lease 5 menit, fence generation, heartbeat/renew, expiry/revoke, dan stale fence rejection.
- Immutable answer request ledger dengan uniqueness tenant + Attempt + client_request_id, payload hash, response JSON.
- Attempt/Answer/Monitoring/Media/Expiry controllers menggunakan binding/fencing.
- Dua migration baru: auth-session binding dan answer request ledger.
- Regression/integration tests dan workflow MySQL 8 disposable.

### Bukti CI aktual
Run Agent 2 #45066851753 pada exact HEAD `b00cf4312b12e1158c609ca91af3dcecec7a2553`:
- Fresh full migration chain pada MySQL 8: PASS.
- PHP syntax validation: PASS.
- Auth/Attempt/Answer/Lease/Media/Expiry suite: FAILURE — 4 failed, 78 passed, 293 assertions.
- Two-connection InnoDB lock race test: SKIPPED karena suite gagal.
- Disposable DB cleanup: PASS.
- Empat kegagalan ada pada `ParticipantAuthorizationAndAttemptLimitTest`, baris 287, 314, 336, 382. Log menunjukkan respons 409 ketika test lama mengharapkan 200/404 dan Attempt tetap IN_PROGRESS karena submit tidak berhasil. Jangan mengubah ekspektasi secara membabi buta; periksa response JSON dan penyebab 409 sebelum patch.
- Workflow run: https://github.com/zane0184/cbt_november/actions/runs/45066851753

### Dua blocker kontrak yang sudah terkonfirmasi
1. `ExamAttemptController::store()` memakai kondisi `if (!$lockedExam->require_token || !$lockedExam->token)` yang menolak ujian ketika `require_token=false`. Ini membalik semantik token opsional dan harus diperbaiki beserta regression test.
2. Receipt pada `ExamAnswerController` menyimpan `client_request_id` di ledger, tetapi objek receipt response tidak memasukkannya. Runner Agent 4 memerlukan field itu untuk memverifikasi receipt sebelum menghapus queue. Tambahkan field pada response dan response replay dengan test exact contract.

### Platform restriction belum terbukti
`StudentExamController.php` Agent 4 saat ini hanya memeriksa exact-one SISWA dan tenant; belum terlihat enforcement server-side yang menolak browser Web untuk peserta Android yang wajib memakai native Android. Jangan gunakan User-Agent JavaScript sebagai security boundary. Kontrak credential/session capability perlu disepakati dan diuji server-side.

### Catatan integrasi
Branch Agent 2 tidak boleh di-merge seluruhnya ke PR #21. Compare branch menunjukkan divergensi besar (ratusan commit dan banyak file lintas Admin/room-session/import/role). Pilih hanya perubahan P0 yang relevan, audit dependensi, siapkan backup di branch integrasi, lalu test exact SHA. Jangan membawa room/session/proctor legacy yang bertentangan dengan keputusan owner.

## 3. WEB SISWA — status yang benar setelah rekonsiliasi

### Sudah dikerjakan di PR #13 (client)
- IndexedDB v2 untuk attempt context, answers, sync queue, receipts, conflicts dan local metadata.
- Atomic answer + queue persistence; immutable request ID/payload/base revision.
- Conflict persistence dan draft restore tanpa overwrite server state; conflict memblokir submit.
- Submit barrier dan UNKNOWN response recovery tanpa POST ganda.
- Fence generation/lease expiry durable, heartbeat, resume Attempt sama setelah reload.
- Media melalui authenticated Fetch/Blob, MIME/size checks, object URL cleanup.
- Receipt verifier memeriksa request ID, Attempt, snapshot, revision, payload hash.
- Mobile-first HP: satu kolom, desktop dua kolom mulai 1024px, navigasi ringkas, touch target 44–48px.
- Browser deterrent copy/cut/selection/context menu/drag/shortcut pada area soal dengan pengecualian input/media/link.
- RTL/Arabic direction dan timer cleanup.

### Belum terbukti / blocker integrasi
- Browser E2E Agent 4 menggunakan API mock; belum browser-to-real-Laravel E2E untuk start, heartbeat, execution, answer, receipt, media, submit, expiry dan result dalam satu sesi.
- Backend Agent 2 belum lulus test suite exact HEAD (4 failure); concurrency test SKIPPED.
- Token opsional salah kondisi.
- Receipt response tidak mengembalikan client_request_id.
- Media Range/seek pada response Laravel belum diuji.
- Platform enforcement Android native vs Web belum ada.
- Uji iOS Safari/WebKit dan UAT perangkat madrasah belum menjadi bukti lulus final.
- Browser deterrent tidak mencegah screenshot OS, screen recording, kamera/perangkat kedua, OCR atau pengetikan ulang; jangan klaim anti-cheat mutlak.

## 4. WEB GURU — status yang benar

Bukti branch PR #19:
- Penghapusan XLSX question import telah dilakukan dan CI historis lulus.
- Selected feature regression (Bank Soal, Exam Builder, result, monitoring) dan global outcome display cap dilaporkan PASS 118 tests / 520 assertions pada run #45109872421.
- XLSX tetap boleh dipakai untuk import data Guru/Siswa Admin atau ekspor yang sudah ada; larangan hanya jalur XLSX import soal.
- DOCX import belum diimplementasikan; desain UI masih draft dan menunggu kontrak Agent 2.
- QuestionVersion belum memiliki relasi/versioned curriculum material/topic yang diperlukan.
- Mapping kurikulum masih question-level, bukan version-level.
- Exam snapshot belum membawa provenance akademik immutable lengkap.
- Pembelajaran Mendalam dan delapan dimensi profil lulusan belum tersedia pada service yang diaudit; UI mempertahankan status belum tersedia, tidak mengarang.
- Monitoring route dan ExamPolicy exact-one role masih menjadi dependency backend/Issue #8.
- Profil Guru hanya mengubah nomor telepon; status kepegawaian aktif/nonaktif tidak tersedia pada schema yang diperiksa.

## 5. WEB ADMIN — status dan konflik yang wajib dijaga

Issue #20 memuat scope ADM-01 s.d. ADM-23. Admin harus berfokus pada administrasi, master data, sinkronisasi, dukungan teknis, laporan administratif, backup/restore, dan penugasan Guru pengawas sementara sesuai batas owner.

Admin tidak boleh:
- membuat/mengubah/menerbitkan ujian Guru;
- mengakses bank soal, token, roster peserta, Attempt, jawaban, monitoring peserta, nilai;
- melakukan reset Attempt, force submit, atau perpanjangan waktu;
- mendapatkan portal Proktor atau kewenangan CBT melalui penugasan pengawas.

PR #18 mempunyai implementasi room/session/multi-Proktor dan kategori ujian terpusat yang belum lengkap pengujiannya, tetapi konsep itu tidak boleh digabung otomatis ke PR #21. Cocokkan semua perubahan dengan Issue #20 dan keputusan owner terbaru. Jangan membuat session/wave/room sebagai lifecycle admission.

Pekerjaan berikutnya: inventaris ADM-01–ADM-23 terhadap route, middleware, Livewire mount/action/hydration, controller, service, model, policy, migration, tests, dan UI mobile/desktop.

## 6. DESAIN — urutan perbaikan

### P0-A — Backend auth/session/role dan contract
1. Perbaiki token optional/required semantics.
2. Perbaiki receipt contract agar menyertakan client_request_id pada fresh response dan replay.
3. Diagnosis empat failure Agent 2 berdasarkan response code/body; pertahankan exact-one role dan session binding.
4. Jalankan kembali suite; wajib sampai two-connection concurrency step PASS, bukan SKIPPED.
5. Audit exact role SISWA/GURU/ADMIN_MADRASAH di semua route/API/policy/service entry points.
6. Selesaikan server-side platform capability enforcement untuk Web vs Android native.
7. Integrasikan backend P0 secara selektif ke branch PR #21 setelah backup dan contract tests.

### P0-B — Runner Web Siswa real API integration
1. Reconcile latest PR #13 runner ke integrasi; jangan menyalin versi lama PR #21.
2. Playwright browser-to-real-Laravel E2E pada disposable MySQL 8.
3. Test start, resume, token optional/required, heartbeat, stale generation, answer exact retry, mismatch receipt, offline/reconnect, media auth/Range/seek, submit/expiry race, response loss.
4. Mobile QA Android Chrome dan iOS Safari; virtual keyboard, focus, rotation, scroll, timer, drawer, touch.
5. UAT siswa pada perangkat HP nyata.

### P1 — Integritas Attempt/Answer/Score dan snapshot
- Lock order konsisten Exam → Participant → Attempt → Answer/Score/Lease.
- Race create/start vs close/archive/destroy; save vs submit/expiry.
- Idempotency, duplicate retry, revision, answer receipt.
- No answer-key/media leakage.
- Snapshot immutable termasuk curriculum provenance.

### P2 — Web Admin dan Web Guru
- Inventaris semua ADM-01–23.
- Lengkapi route/action authorization dan UI Admin.
- Lengkapi Guru assignment-specific curriculum/material/topic dan QuestionVersion mapping setelah kontrak bersama disetujui.
- Implementasi DOCX baru setelah DTO, parser profile, security, staging, ImportBatch lifecycle dan media contract final disetujui.
- SHORT_ANSWER tetap BLOCKED sampai accepted answer tersimpan, snapshot, dan scoring terbukti; ESSAY manual review.

### P3 — Academic, quality, acceptance
- Kurmer + Panca Cinta + Pembelajaran Mendalam + 8 DPL dengan sumber resmi/provenance/version/effective date.
- Browser E2E tiga portal, accessibility, mobile-first.
- Fresh install/legacy upgrade pada disposable MySQL 8, repeat seeder, backup/restore, concurrency, security, load/recovery.
- UAT Admin/Guru/Siswa.

### P4 — Deployment dan distribusi
- Rencana backup/preflight VPS dan persetujuan owner.
- ZIP generik hanya setelah semua gate lulus.
- Android native dan Web Super Admin tetap ditunda sampai Juni 2027.

## 7. IMPLEMENTASI pada tahap rekonsiliasi ini
Tidak ada source runtime, migration, database produksi, VPS, atau deployment yang diubah. Ini audit/rekonsiliasi read-only dan penulisan laporan. Tidak ada PR yang di-merge.

## 8. NEXT — tindakan pertama setelah laporan
1. Backup byte-exact target source pada branch integrasi.
2. Buat patch terisolasi untuk dua kontrak backend (optional token + receipt request ID) pada branch kerja yang tepat; jangan patch test sebelum memahami failure response.
3. Tambahkan test regression untuk token required/optional dan receipt replay.
4. Diagnosis empat failure lama dengan log response body dan session/token identity; perbaiki implementasi atau fixture sesuai root cause.
5. Re-run Agent 2 workflow sampai concurrency test PASS.
6. Setelah backend contract lulus, adaptasikan runner Agent 4 dan lakukan browser-to-Laravel integration.
7. Perbarui laporan dan checkpoint setelah setiap tahap dengan exact source SHA, backup hash, workflow run, dan hasil.

**Batas:** PR #21 tetap Draft/unmerged. Tidak ada migration produksi, perubahan DB produksi, deployment, atau klaim siap ujian/produksi/distribusi.


## 9. PENGUJIAN — refresh exact HEAD PR #13 dan PR #19 (23 November 2026)

### Web Siswa Agent 4 — PR #13
- Exact PR HEAD: `9ef375c407b1faf606c5a268f00b24424d93be9e`.
- Workflow run: [#45110617970](https://github.com/zane0184/cbt_november/actions/runs/45110617970) — SUCCESS pada exact HEAD.
- Job `runner-static-audit`: SUCCESS. Static checks meliputi local CSS/no CDN, JS parse, unique IDs, IndexedDB v2, accessibility semantics, durability/conflict/submit barriers, auth-session fencing/heartbeat/media, receipt verification, timer, mobile-first, copy deterrent.
- Chromium browser E2E: semua skenario pada runner lulus, mencakup mobile/desktop layout, touch target/drawer, answer save/submit, exact retry/receipt mismatch, fail-closed IndexedDB/fencing, token normalization, question types, Arabic/RTL, heartbeat, media MIME/size/auth, reconnect ordering, IndexedDB reload/migration, conflict recovery, expiry, dan unknown submit.
- WebKit engine compatibility E2E (Safari-engine proxy): SUCCESS. Ini bukan pengujian iPhone/iPad fisik atau iOS Safari operasional.
- Job `student-results-feature`: SUCCESS pada disposable MySQL 8. Hasil:
  - StudentResultVisibilityTest: 7 passed / 22 assertions.
  - StudentExamPortalTest: 10 passed / 47 assertions.
  - StudentWebLoginTest: 12 passed / 88 assertions.
  - Total ketiga suite: 29 passed / 157 assertions.
- Batas: Browser E2E tetap memodelkan API dengan mock. Browser-to-real-Laravel integration untuk semua endpoint Attempt/Answer/Lease/Media/Submit belum terbukti.

### Web Guru — PR #19
- Exact PR HEAD: `71296ca6e7c48b7295455a26d38bda11b901eb1d`.
- Workflow run: [#45112459424](https://github.com/zane0184/cbt_november/actions/runs/45112459424) — SUCCESS pada exact HEAD.
- PHPUnit: 118 passed / 520 assertions.
- PHP lint, route registration, Composer validation/locked install/platform checks, disposable MySQL 8, dan pemeriksaan tabel InnoDB: PASS.
- Bukti ini menutup selected Web Guru foundation suite pada exact PR HEAD, bukan keseluruhan acceptance portal. DOCX, provenance QuestionVersion→snapshot, materi/topik, Pembelajaran Mendalam/8 DPL, browser visual QA, concurrency/load dan UAT tetap terbuka.
