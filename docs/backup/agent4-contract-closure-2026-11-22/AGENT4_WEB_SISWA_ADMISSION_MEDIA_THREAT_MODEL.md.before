# Agent 4 — Threat Model Admission dan Media Web Siswa

Tanggal: 22 November 2026  
Repository: `zane0184/cbt_november`  
Branch dokumentasi: `work/agent4-web-siswa-audit-20261122`  
HEAD branch dokumentasi sebelum dokumen ini: `c4a02a93bb143da7c83df1894f92b2ffa75c8bc6`  
Basis dokumentasi: `main` @ `feab77228f7108f7f7a45325d7f1fb878c07310b`  
Owner dokumen: Agent 4 (Web Siswa); keputusan backend tetap milik Agent 2.  
Status approval: **PENDING** — tidak ada approval Agent 2/koordinator untuk kontrak baru.

## 1. Scope dan batas arsitektur

Sampai Juni 2027 scope hanya Web Admin Madrasah, Web Guru, dan Web Siswa. Android native dan Super Admin ditunda. Web Siswa berjalan di browser Android/iOS; bukan secure kiosk browser.

Distribusi akhir: satu ZIP source dipasang terpisah pada banyak madrasah; **satu instalasi = satu madrasah = satu database akademik sendiri**. Database, storage, kredensial, konfigurasi, file dan backup tiap instalasi terpisah. Tidak ada sinkronisasi akademik lintas instalasi. Super Admin masa depan hanya menangani lisensi, pembayaran, status layanan, bukan data akademik. Rancangan ini tidak mengasumsikan shared academic database.

## 2. AUDIT — sumber dan bukti

Remote HEAD saat refresh:
- Agent 1 `work/stage4b-room-session-20261122`: `d2532792471966f1d0a9d9cbdd4d65e7c84547f0`.
- Agent 2 `work/stage4d-account-lifecycle-20261122`: `7d63ea3b70b5191530c2c92c5c86bf92d7324db6`.
- Agent 3 `work/web-guru-foundation-20261122`: `bb22fbd1ea3181fdec898109a5d9c5110f2a0890`.
- Kandidat Agent 4 `work/import-guru-siswa-implementation-20261122`: `f38ceec7b0653fcf3363eec40f3d4577c323cca8`; ownership belum dikonfirmasi.
- Dokumentasi Agent 4: `work/agent4-web-siswa-audit-20261122` @ `c4a02a93bb143da7c83df1894f92b2ffa75c8bc6`.
- `main`: `feab77228f7108f7f7a45325d7f1fb878c07310b`; bukan basis integrasi source CBT yang sedang direkonsiliasi.

Issue #10: https://github.com/zane0184/cbt_november/issues/10 — tetap OPEN. Delapan komentar yang diperiksa seluruhnya berasal dari koordinator `zane0184`; tidak ada konfirmasi ownership Agent 4 atau approval Agent 2/3/4. Agent 2 masih NEEDS DESIGN/COORDINATION; Agent 3 dan Agent 4 PENDING.

Source Agent 2 yang diperiksa:
| Path | Blob SHA |
|---|---|
| `backend-laravel/app/Http/Controllers/Api/V1/ExamController.php` | `c7c62e961f2ff47abee8f3b2468b4e7bc43d1cc9` |
| `backend-laravel/app/Http/Controllers/Api/V1/MediaController.php` | `f852c1b3a7247efdbb0c309fb0413bbe5e3eaa62` |
| `backend-laravel/app/Http/Controllers/Api/V1/ExamAttemptController.php` | `13e8981cca4653ff4e9b9448a50e4d0ec8a74bae` |
| `backend-laravel/app/Http/Controllers/Api/V1/ExamAnswerController.php` | `b4ddec14f6208b3ecde9bfc3e45168a2159e2a9c` |
| `backend-laravel/routes/api.php` | `2e43c37dd669a26446a44b818c1d2c8420b15aca` |
| `backend-laravel/app/Policies/ExamPolicy.php` | `c05d96ef932fdc81b67336240af4e1726864c3d6` |
| `backend-laravel/app/Services/ExamSnapshotService.php` | `d0bfcd70b2777a3884c7f92b7c256f05492645ec` |
| `backend-laravel/app/Models/QuestionVersion.php` | `5f937abf691f96ee62696f7ef01b5adb2d6f0d9a` |
| `backend-laravel/app/Models/ExamQuestionSnapshot.php` | `20a9ef80ed69c5e6281d0fbe592c900178ede2d2` |

Source Agent 3 yang dibandingkan:
- `ExamAttemptController.php`: `e915bf43118026ef8eace96338fa6cad44ee29e1`.
- `QuestionVersion.php`: `5f937abf691f96ee62696f7ef01b5adb2d6f0d9a`.
- `ExamSnapshotService.php`: `d0bfcd70b2777a3884c7f92b7c256f05492645ec`.
- `QuestionController.php`: `7ded53423dd4d96788123819e8039fd543ce4fba`.

Temuan F-01:
- `ExamController::show()` melepas snapshot berdasarkan `canStartAttempt()`; belum memvalidasi token yang diterima, Attempt aktif milik peminta, status/expiry Attempt, lease, registered device, expiry/revoke lease, dan fencing generation sebagai satu admission gate.
- `ExamPolicy::view()` untuk Siswa membatasi tenant dan membership ExamParticipant, bukan admission Attempt penuh.
- `ExamAttemptController::store()` memakai header `X-Device-Binding-ID`; Attempt baru memeriksa token, tetapi jalur resume Attempt IN_PROGRESS dengan fingerprint sama dapat mengembalikan Attempt sebelum token diperiksa lagi.
- Sanitasi field answer-key tertentu tidak menggantikan larangan mengirim snapshot sebelum admission.

Temuan F-02:
- `MediaController::show()` memeriksa tenant/owner dan helper `isReferencedByOwnActiveAttempt()` menghubungkan media dengan snapshot, Attempt IN_PROGRESS, participant dan user.
- Pemeriksaan yang dibaca belum membuktikan media snapshot berada dalam `question_order` Attempt serta validasi registered device, lease token, lease expiry/revoke dan fencing generation.
- Route media berada dalam grup authenticated/tenant/license; belum ada bukti middleware lease/fencing pada route tersebut.
- Siklus lease/fencing end-to-end belum terbukti. Keberadaan schema bukan bukti runtime.

Temuan jawaban:
- Request update menerima `client_request_id`, `base_revision`, `answer_payload`, `answer_state`; current revision dan payload server dikembalikan pada jalur konflik.
- Controller mengunci Attempt lalu Answer; urutan global dengan Exam/Participant/Lease masih perlu persetujuan.
- Belum ada bukti receipt server terpisah yang dapat diverifikasi client sebagai bukti `SERVER_ACCEPTED`.

## 3. DESAIN — threat model

Aset yang dilindungi:
1. Snapshot soal dan question order.
2. Answer key, accepted answers, rubric, scoring guide dan explanation privat.
3. Media privat milik tenant dan media yang tertaut pada snapshot Attempt.
4. Jawaban, revision, Attempt, identitas siswa dan perangkat.
5. Lease/fencing credential dan status admission.

Trust boundary:
- Browser siswa tidak dipercaya sebagai sumber status Attempt, waktu resmi, token valid, lease, revision server atau hasil submit.
- IndexedDB adalah penyimpanan lokal yang dapat hilang/dimodifikasi oleh browser/pengguna; bukan bukti server accepted.
- Tenant boundary adalah batas instalasi/database. Jangan memakai tenant selector/header dari client sebagai otoritas.
- Hanya backend Agent 2 yang memutuskan admission, authorization media, lease/fencing, answer acceptance dan submit.

Ancaman:
- Akses snapshot hanya dengan jadwal tanpa token/admission.
- Attempt ID milik siswa lain atau tenant lain.
- Token salah/lama atau reuse jalur resume.
- Snapshot ID dan question order dimanipulasi.
- Media ID valid tetapi milik tenant/question/snapshot lain.
- Lease expired/revoked, device mismatch, stale fencing generation.
- Request paralel saat takeover.
- Browser menyatakan jawaban tersimpan server sebelum receipt.
- Error response membocorkan metadata internal atau data peserta lain.

## 4. Acceptance test F-01 — keputusan yang diminta

Semua kasus berikut wajib fail closed: tidak ada snapshot, answer key, accepted answers, rubric, scoring guide, metadata internal, atau data peserta lain pada response yang tidak lolos admission. Status HTTP dan nama audit event **ditetapkan Agent 2**, tidak ditentukan dokumen ini.

| ID | Skenario | Hasil keamanan | Tindakan |
|---|---|---|---|
| ADM-01 | Token tidak dikirim | Tolak admission; metadata aman saja | Hentikan |
| ADM-02 | Token salah | Tolak; tidak ada snapshot | Hentikan |
| ADM-03 | Token lama/dirotasi | Tolak sesuai state token server | Hentikan |
| ADM-04 | User bukan tepat satu role SISWA/akun tidak aktif | Tolak | Hentikan |
| ADM-05 | Peserta bukan ExamParticipant | Tolak tanpa mengungkap membership lain | Hentikan |
| ADM-06 | Tenant instalasi/resource tidak sama | Tolak; tidak ada data lintas tenant | Hentikan |
| ADM-07 | Attempt milik siswa lain | Tolak; jangan mengembalikan detail Attempt | Hentikan |
| ADM-08 | Attempt expired | Tolak resume/snapshot sesuai lifecycle server | Hentikan |
| ADM-09 | Attempt terminal | Tolak operasi yang tidak diizinkan status terminal | Hentikan |
| ADM-10 | Exam belum mulai | Metadata aman; tidak ada snapshot/Attempt baru | Hentikan start |
| ADM-11 | Exam closed, tidak ada Attempt aktif | Attempt baru ditolak | Hentikan |
| ADM-12 | Exam closed, Attempt IN_PROGRESS belum expired dan eligible | Resume Attempt yang sama dapat diizinkan; tidak membuat Attempt baru | Boleh resume jika seluruh gate lain sah |
| ADM-13 | Lease tidak ada/invalid | Tolak akses snapshot/Attempt sesuai kontrak | Hentikan |
| ADM-14 | Fencing generation lama | Tolak stale writer/device | Hentikan |
| ADM-15 | Device mismatch | Tolak; tidak auto-takeover | Hentikan |
| ADM-16 | Snapshot ID dimanipulasi | Tolak snapshot yang tidak terikat pada Exam/Attempt | Hentikan |
| ADM-17 | Question order dimanipulasi/kosong/duplikat | Tolak; jangan memperbaiki diam-diam | Hentikan |

Audit event per kasus: Agent 2 harus menetapkan event, actor, tenant, exam, attempt (jika sah), device reference yang aman, reason code dan correlation ID. Jangan log token/credential mentah. Tidak ada nama event baru yang ditetapkan di sini.

## 5. Acceptance test F-02 — media authorization

| ID | Skenario | Hasil keamanan |
|---|---|---|
| MED-01 | Media ID dari instalasi/tenant lain | Tolak; tidak ada byte/file leak |
| MED-02 | Media milik QuestionVersion lain | Tolak kecuali versi tersebut sumber snapshot sah |
| MED-03 | Snapshot ID palsu | Tolak |
| MED-04 | Snapshot tidak ada dalam question_order Attempt | Tolak |
| MED-05 | Attempt milik user lain | Tolak |
| MED-06 | Attempt expired/terminal | Tolak sesuai status yang diizinkan |
| MED-07 | Lease expired | Tolak |
| MED-08 | Lease revoked | Tolak |
| MED-09 | Device mismatch | Tolak |
| MED-10 | Stale fencing generation | Tolak |
| MED-11 | Request media paralel sesudah takeover | Request dari generation lama ditolak walaupun dimulai sebelum takeover |
| MED-12 | Media reference dimanipulasi | Tidak boleh mengakses file lain, path traversal atau storage key lain |

Untuk semua MED: respons tidak boleh mengungkap storage path, metadata pemilik lain, atau keberadaan media tenant lain secara berlebihan. Audit event, HTTP status, dan error contract ditetapkan Agent 2.

## 6. Invariant dan acceptance criteria

1. Sebelum admission, hanya metadata aman.
2. Snapshot dirilis hanya untuk Attempt sah yang dimiliki user aktif dengan role portal tepat satu SISWA, tenant instalasi benar, jadwal/token server sah, device/lease/fencing sah dan question order valid.
3. Resume setelah close hanya untuk Attempt IN_PROGRESS yang belum expired dan masih eligible; tidak membuat Attempt baru.
4. Session/room/assignment bukan admission gate.
5. Media harus tertaut pada snapshot yang termasuk question order Attempt yang sama.
6. Fencing memblokir request lama setelah takeover.
7. Tidak ada data akademik lintas instalasi/database.
8. Audit log mencatat keputusan tanpa credential rahasia.
9. Semua test dijalankan pada source SHA yang sama dan disposable database; bukan database produksi.

## 7. Regression tests

- Uji metadata aman sebelum jadwal.
- Uji snapshot tidak pernah muncul pada response deny.
- Uji close vs create Attempt dan close vs resume Attempt.
- Uji tenant/user/participant ownership.
- Uji snapshot/question order integrity.
- Uji media tenant/owner/question/snapshot/Attempt scope.
- Uji lease expiry/revoke/device mismatch/stale generation.
- Uji race request media dengan takeover.
- Uji tidak ada answer key/accepted answers/rubric pada payload siswa.
- Uji audit event dan redaction credential.

## 8. Risiko terbuka dan approval

- F-01: **P0 OPEN**.
- F-02: **P0 OPEN**.
- Device Lease/Fencing: **BLOCKED — backend contract/runtime pending**.
- Branch resmi Agent 4: **BLOCKED — ownership pending**.
- Kontrak admission/media Agent 2: **PENDING**.
- Persetujuan koordinator: **PENDING**.

Dokumen ini hanya threat model dan acceptance request; bukan spesifikasi API final dan bukan izin coding.
