# Agent 4 — Matriks Pengujian Web Siswa

Tanggal: 22 November 2026  
Status seluruh pengujian dalam dokumen ini: **NOT RUN**, kecuali dinyatakan lain.  
Tujuan: acceptance matrix untuk implementasi dan integrasi mendatang.

## 1. Admission dan snapshot — P0

| ID | Skenario | Hasil yang diwajibkan | Status |
|---|---|---|---|
| ADM-01 | Token kosong | Ditolak; tidak ada snapshot/metadata sensitif | NOT RUN |
| ADM-02 | Token salah | Ditolak; rate limit/audit sesuai kontrak | NOT RUN |
| ADM-03 | Token benar sebelum jadwal | Metadata aman saja; snapshot tidak tersedia | NOT RUN |
| ADM-04 | Token benar pada jadwal | Admission hanya jika user, tenant, peserta, Attempt, device, lease/fence valid | NOT RUN |
| ADM-05 | Tanpa Attempt | Snapshot tidak diberikan | NOT RUN |
| ADM-06 | Attempt milik siswa lain | Ditolak tanpa kebocoran data | NOT RUN |
| ADM-07 | Attempt lintas tenant | Ditolak tanpa kebocoran data | NOT RUN |
| ADM-08 | Attempt terminal/expired | Tidak dapat resume atau mengambil snapshot baru | NOT RUN |
| ADM-09 | Exam ditutup, Attempt aktif | Resume hanya jika status, expiry, eligibility, waktu server, lease sah | NOT RUN |
| ADM-10 | Close Exam vs Attempt baru | Attempt baru ditolak secara serial | NOT RUN |

## 2. Media — P0

| ID | Skenario | Hasil yang diwajibkan | Status |
|---|---|---|---|
| MED-01 | Media di luar snapshot Attempt | Ditolak | NOT RUN |
| MED-02 | Snapshot bukan di question_order | Ditolak | NOT RUN |
| MED-03 | Media tenant lain | Ditolak | NOT RUN |
| MED-04 | Media milik Attempt siswa lain | Ditolak | NOT RUN |
| MED-05 | Lease tidak ada | Ditolak | BLOCKED |
| MED-06 | Lease expired/revoked | Ditolak | BLOCKED |
| MED-07 | Fencing generation lama | Ditolak sebagai stale request | BLOCKED |
| MED-08 | Manipulasi ID media/snapshot | Tidak mengungkap file lain | NOT RUN |

## 3. Device Lease/Fencing — P0

| ID | Skenario | Hasil yang diwajibkan | Status |
|---|---|---|---|
| LSE-01 | Acquire perangkat pertama | Lease tunggal valid | BLOCKED |
| LSE-02 | Acquire perangkat kedua | Denial atau takeover berotorisasi | BLOCKED |
| LSE-03 | Heartbeat lease aktif | Perpanjangan sesuai kontrak | BLOCKED |
| LSE-04 | Heartbeat lease stale | Ditolak | BLOCKED |
| LSE-05 | Release/revoke | Tidak dapat dipakai lagi | BLOCKED |
| LSE-06 | Lease expiry | Request berikutnya ditolak | BLOCKED |
| LSE-07 | Takeover | Generation naik dan perangkat lama fenced | BLOCKED |
| LSE-08 | Retry idempotent lease operation | Tidak membuat lease ganda | BLOCKED |
| LSE-09 | Concurrent acquire | Satu pemenang; tidak ada dua lease aktif | BLOCKED |

## 4. Answer persistence, revision, dan idempotency

| ID | Skenario | Hasil yang diwajibkan | Status |
|---|---|---|---|
| ANS-01 | Simpan jawaban normal | Revision naik dan receipt dapat diverifikasi | NOT RUN |
| ANS-02 | Simpan payload tidak valid | Ditolak tanpa merusak jawaban sebelumnya | NOT RUN |
| ANS-03 | Base revision stale | Konflik eksplisit; kedua versi dipertahankan | NOT RUN |
| ANS-04 | Retry request ID sama/payload sama | Satu efek server, response konsisten | NOT RUN |
| ANS-05 | Request ID sama/payload berbeda | Ditolak; tidak mengubah jawaban | NOT RUN |
| ANS-06 | Respons hilang setelah server commit | Retry identik dapat menemukan hasil server | NOT RUN |
| ANS-07 | Request stale generation | Ditolak oleh fencing | BLOCKED |
| ANS-08 | Answer vs submit race | Urutan lock dan status konsisten | NOT RUN |
| ANS-09 | Answer vs Force Submit race | Tidak ada jawaban tertimpa setelah terminal | NOT RUN |

## 5. IndexedDB dan recovery

| ID | Skenario | Hasil yang diwajibkan | Status |
|---|---|---|---|
| REC-01 | Transaksi jawaban lokal | Answer + queue tersimpan atomik | NOT RUN |
| REC-02 | IndexedDB gagal | Error terlihat; tidak klaim tersimpan | NOT RUN |
| REC-03 | Quota penuh | Submit diblokir; data lama tidak dihapus | NOT RUN |
| REC-04 | Reload dengan pending | Antrean dipulihkan | NOT RUN |
| REC-05 | Browser crash | Jawaban tanpa receipt tetap tersedia | NOT RUN |
| REC-06 | Offline lalu online | Replay identik dan receipt diverifikasi | NOT RUN |
| REC-07 | HTTP 409 | Jawaban lokal dan server diarsipkan | NOT RUN |
| REC-08 | Dua tab Attempt sama | Writer terkoordinasi; revision conflict terlihat | NOT RUN |
| REC-09 | Akun/perangkat berbeda | Data lokal Attempt lain tidak ditampilkan | NOT RUN |
| REC-10 | Upgrade schema IndexedDB | Migrasi lokal aman, data tidak hilang | NOT RUN |

## 6. Submit, expiry, dan Force Submit

| ID | Skenario | Hasil yang diwajibkan | Status |
|---|---|---|---|
| SUB-01 | Semua receipt valid, queue kosong | Submit dapat diteruskan | NOT RUN |
| SUB-02 | Ada pending | Submit diblokir | NOT RUN |
| SUB-03 | Ada request aktif | Submit menunggu/ditahan | NOT RUN |
| SUB-04 | Ada konflik | Submit diblokir | NOT RUN |
| SUB-05 | Respons submit hilang | Retry idempotent; status server diperiksa | NOT RUN |
| SUB-06 | Submit dipanggil dua kali | Tidak ada double finalization | NOT RUN |
| SUB-07 | Attempt expired | Server menjadi acuan terminal status | NOT RUN |
| SUB-08 | Force Submit | Actor/reason/before-after tercatat; race aman | NOT RUN |
| SUB-09 | Lease stale saat submit | Ditolak atau mengikuti kontrak recovery yang disetujui | BLOCKED |

## 7. Browser dan aksesibilitas

| ID | Lingkungan | Skenario | Status |
|---|---|---|---|
| BRW-01 | Android Chrome | Layout, input, audio/video, reload | NOT RUN |
| BRW-02 | iOS Safari | Layout, input, audio/video, IndexedDB | NOT RUN |
| BRW-03 | Desktop Chrome/Firefox | Layout dan navigasi | NOT RUN |
| BRW-04 | Mobile viewport | Keyboard virtual, scroll, timer | NOT RUN |
| BRW-05 | Arabic/RTL | Harakat, mixed Arabic-Indonesian, bidi | NOT RUN |
| BRW-06 | Formula | Rendering dan fallback yang terbaca | NOT RUN |
| BRW-07 | Aksesibilitas | Keyboard, focus, screen reader labels | NOT RUN |
| BRW-08 | Network transition | Offline banner dan reconnection | NOT RUN |

Browser biasa tidak dapat dijanjikan memblokir Home, screenshot, app switching, atau menjamin kiosk mode. Web Siswa bukan exam browser.

## 8. Enam tipe soal

Harus diuji tepat enam tipe:
- MULTIPLE_CHOICE
- MULTIPLE_RESPONSE
- TRUE_FALSE
- MATCHING
- SHORT_ANSWER
- ESSAY

SHORT_ANSWER tetap BLOCKED sampai Agent 2/3 membuktikan accepted answers tersimpan, masuk ExamSnapshot, dan digunakan scoring. ESSAY tetap melalui penilaian manual. Tidak ada tipe soal baru.

## 9. Tenant, role, dan manipulasi request

- Siswa tenant A tidak boleh mengakses Exam/Attempt/media tenant B.
- User nonaktif atau role campuran tidak boleh lolos boundary.
- Manipulasi attempt_id, snapshot_id, media_id, device ID, revision, dan request ID harus diuji.
- Response error tidak boleh mengungkap data tenant lain.
- Audit log wajib untuk Force Submit, reset, takeover, dan perpanjangan waktu sesuai authorization dan alasan.

## 10. Acceptance gate

Web Siswa tidak siap operasional sebelum seluruh P0/P1 yang relevan lulus pada source SHA yang sama, termasuk F-01, F-02, lease/fencing, recovery, konflik, submit barrier, multi-tab, multi-device, tenant isolation, browser E2E, dan integration test Agent 1/2/3. Semua status di atas tetap NOT RUN/BLOCKED sampai ada bukti.


## 11. KOORDINATOR PORTAL / KAMAD / PROKTOR BOUNDARY — ADDENDUM 22 NOVEMBER 2026

Dasar: Issue #10 komentar `7078539953` oleh koordinator `zane0184`. Keputusan produk: portal aktif ADMIN_MADRASAH/GURU/SISWA; KAMAD frozen; Proktor adalah assignment sementara akun GURU di Web Guru; tidak ada akun/role/login/portal Proktor; SISWA bukan Proktor. Persetujuan teknis Agent 2/3 masih PENDING.

| ID | Skenario | Hasil yang diwajibkan | Status | Owner |
|---|---|---|---|---|
| ROLE-01 | Exact-one SISWA | Hanya user dengan tepat satu role SISWA dapat memakai seluruh Student endpoint | BLOCKED | Agent 2 |
| ROLE-02 | SISWA + GURU | Ditolak pada daftar/detail Exam, admission, Attempt, snapshot, media, answer, submit, result | NOT RUN | Agent 2 |
| ROLE-03 | SISWA + KAMAD | Ditolak; tidak boleh lolos karena hanya ADMIN_MADRASAH yang dikecualikan | NOT RUN | Agent 2 |
| ROLE-04 | GURU saja / Guru Proktor | Ditolak pada seluruh Student endpoint; Guru tetap memakai identitas Guru | NOT RUN | Agent 2 |
| ROLE-05 | ADMIN_MADRASAH / legacy OPERATOR | Tidak dapat mengakses runner peserta | NOT RUN | Agent 2 |
| ROLE-06 | KAMAD login Web/API | Tidak dapat login, memperoleh session/token, atau redirect ke dashboard CBT | BLOCKED | Agent 2 |
| ROLE-07 | KAMAD policy/route/Livewire/export/monitoring | Seluruh jalur capability ditolak; tidak ada bypass author/KAMAD | BLOCKED | Agent 2 |
| ROLE-08 | Role PROKTOR permanen | Tidak ada role/credential/portal/login PROKTOR yang dibuat | NOT RUN | Agent 1/2 |
| ROLE-09 | Assignment Guru Proktor aktif | Assignment hanya membuka menu dan tindakan terbatas di Web Guru | BLOCKED | Agent 1/2/3 |
| ROLE-10 | Assignment Proktor expired/revoked/wrong room/Exam/tenant/schedule revision | Akses monitoring ditolak fail-closed | BLOCKED | Agent 1/2/3 |
| ROLE-11 | Guru Proktor memanggil API peserta | Ditolak oleh exact-one SISWA; tidak dapat start/resume/answer/submit sebagai peserta | NOT RUN | Agent 2 |
| ROLE-12 | Siswa memanggil route/API Proktor | Ditolak; Web Siswa tidak menyediakan menu/route/action Proktor | NOT RUN | Agent 2/4 |
| ROLE-13 | Proktor mencoba melihat jawaban/nilai/answer key | Ditolak kecuali ada permission terpisah yang secara eksplisit disetujui; default deny | BLOCKED | Agent 2/3 |
| ROLE-14 | Proktor mencoba Force Submit/ubah Exam/bank soal/assignment Guru lain | Ditolak | BLOCKED | Agent 2/3 |
| ROLE-15 | Audit tindakan Proktor | Actor adalah user_id akun GURU; tenant, Exam, Room, assignment, action, reason, timestamp tercatat | NOT RUN | Agent 1/2 |

Test di atas harus dijalankan pada exact source SHA yang sama melalui PHPUnit/API integration dan browser E2E. Status kosong/UNKNOWN bukan PASS. Tidak ada Proktor UI/API client di Web Siswa; seluruh menu Proktor berada di Web Guru dan dimiliki Agent 3. Web Siswa tidak mengambil alih assignment atau monitoring.