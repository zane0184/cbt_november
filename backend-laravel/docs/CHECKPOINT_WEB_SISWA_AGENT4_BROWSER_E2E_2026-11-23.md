# CHECKPOINT AGENT 4 — WEB SISWA BROWSER E2E
Tanggal: 23 November 2026
Repo: `zane0184/cbt_november`
Branch: `work/agent4-web-siswa-emergency-20261122`
PR: #13 (OPEN, DRAFT, UNMERGED)
Checkpoint commit basis: `238901093809b120f89a9a85b3c102e25e137631`

## 1. AUDIT

### Cakupan halaman Web Siswa yang ditemukan
- `resources/views/student/exams/index.blade.php`: daftar ujian yang ditugaskan, jadwal, durasi, jumlah percobaan, status, lanjutkan/mulai, dan akses hasil.
- `resources/views/student/exams/show.blade.php`: instruksi, token bila diwajibkan, mulai/lanjutkan Attempt, timer, navigasi, enam tipe soal, media, penyimpanan lokal, sinkronisasi, konflik, submit, dan pemulihan.
- `resources/views/student/results/show.blade.php`: hanya ringkasan hasil final yang telah dipublikasikan; tidak menampilkan rincian jawaban soal.
- `app/Http/Controllers/StudentExamController.php`: validasi role SISWA, tenant, penugasan peserta, rentang akses, batas percobaan, Attempt aktif, dan current score pointer.
- `tests/Feature/StudentResultVisibilityTest.php`: tujuh test method yang mencakup hasil milik siswa sendiri, publikasi hasil, akses siswa lain, current score pointer, DTO minimal, dan score final.\n- `tests/Feature/StudentExamPortalTest.php`: enam test method untuk daftar ujian yang ditugaskan, halaman instruksi/token, Attempt aktif, unassigned/cross-tenant, eligibility window, dan penolakan role Admin Madrasah.

### Tipe soal
Bank Soal saat ini membatasi tipe ke:
- MULTIPLE_CHOICE
- MULTIPLE_RESPONSE
- TRUE_FALSE
- MATCHING
- SHORT_ANSWER
- ESSAY

API execution menyamarkan key pasangan MATCHING untuk siswa menjadi `LEFT` dan `RIGHT`; runner menggunakan bentuk sanitasi tersebut. Tidak ada perubahan source runner untuk asumsi format internal pasangan.

### Temuan integrasi yang belum boleh dianggap selesai
1. **Token opsional — temuan backend:** pada `ExamAttemptController::store`, kondisi saat ini mengembalikan `EXAM_TOKEN_NOT_CONFIGURED` ketika `require_token` bernilai false karena memakai kondisi `!$lockedExam->require_token || !$lockedExam->token`. Ini bertentangan dengan UI yang menyatakan token tidak diperlukan. Tidak diubah oleh Agent 4 karena file controller/API berada di luar scope runner Web Siswa.
2. **Receipt jawaban:** response `ExamAnswerController::update` saat ini mengembalikan attempt_id, snapshot_id, revision_number, answer_state, server_now, dan saved_at; tidak mengembalikan client_request_id/payload_hash receipt yang dapat diverifikasi client. Belum ditemukan endpoint read-only untuk mencari receipt berdasarkan client_request_id. Store `server_receipts` tetap disiapkan tetapi tidak diisi atau diklaim terverifikasi.
3. **Device Lease/fencing:** source API yang menjadi basis PR ini belum memberi fence_generation/lease_expires_at pada start dan belum menyediakan kontrak heartbeat yang dapat diverifikasi oleh runner. Client belum mengarang header, generation, heartbeat, atau status receipt.
4. **Media:** endpoint media membatasi tenant dan memeriksa referensi media pada Attempt aktif milik siswa. Runner memakai URL same-origin pada elemen media; autentikasi tetap berasal dari sesi browser. Pengujian media terhadap server Laravel belum dilakukan.
5. **Asset UI:** halaman Siswa masih memuat Tailwind melalui CDN eksternal. Ini belum diganti karena proyek belum memiliki pipeline Vite/CSS lokal yang dapat dipakai langsung dari source branch ini. Ketergantungan CDN perlu ditinjau sebelum UAT jaringan terbatas.

## 2. DESAIN

- Scope Agent 4 tetap terbatas pada alur Web Siswa: daftar ujian, instruksi/akses, runner, pemulihan, submit, dan tampilan hasil.
- Tidak menambah portal/role, tidak mengubah Web Admin/Guru, backend API, migration, atau database.
- Browser E2E memakai Chromium dan API mock untuk memverifikasi perilaku client. Hasilnya tidak menggantikan uji integrasi Laravel/MySQL.
- Tidak menampilkan klaim receipt/fencing sampai server menyediakan kontrak dan uji integrasi yang sesuai.

## 3. IMPLEMENTASI

- Backup byte-exact workflow statis sebelum diperluas:
  `backend-laravel/docs/backup/agent4-web-siswa-emergency-2026-11-22/agent4-web-siswa-runner-static.yml.before-browser-e2e-2026-11-23`
  Backup commit: `3d930741e9a3bcd9c2ef80d25d8cf1327a7e58a5`.
- Menambahkan `backend-laravel/tests/runner-browser-e2e.mjs`.
- Memperluas `.github/workflows/agent4-web-siswa-runner-static.yml` untuk Node 22, Playwright 1.56.1, Chromium, static audit, dan browser E2E. Runner CI dipatok ke Ubuntu 24.04 karena Playwright 1.56.1 tidak mendukung image Ubuntu 26.04.
- Menambahkan `.github/workflows/agent4-student-results-mysql.yml` untuk menjalankan `StudentResultVisibilityTest.php` menggunakan MySQL 8 disposable di GitHub Actions.
- `show.blade.php`: timer interval dibersihkan saat submit/terminal completion; interval dipasang sebelum tick awal agar auto-submit pada Attempt yang sudah kedaluwarsa tidak meninggalkan timer aktif. IndexedDB capability guard kini memeriksa `window.indexedDB` secara langsung agar browser tanpa API gagal tertutup.\n- Dukungan arah teks `dir=auto` ditambahkan untuk judul/instruksi/pertanyaan, pilihan, matching, jawaban teks, nama siswa, judul ujian, dan mata pelajaran agar konten Arab berharakat dan campuran Arab-Indonesia tetap terbaca.\n- Menambahkan `tests/Feature/StudentExamPortalTest.php` (6 test) dan menggabungkan job MySQL ke workflow Agent 4. Workflow MySQL terpisah dihapus setelah backup agar tidak ada CI ganda.\n- Tidak ada perubahan backend API, migration, Web Admin/Guru, atau DB produksi. Perubahan backend yang belum tersedia dicatat sebagai gate, bukan diakali di client.

## 4. PENGUJIAN

### GitHub Actions — static + Chromium browser E2E
Run `45063309059`, job `137333869682`, commit `238901093809b120f89a9a85b3c102e25e137631`:
- Static audit: PASS.
- Chromium install: PASS.
- Browser E2E: PASS.
- Log skenario:
  - normal answer save + terminal submit: PASS
  - IndexedDB tidak tersedia: fail closed sebelum POST start: PASS
  - token wajib dinormalisasi trim + uppercase: PASS
  - MULTIPLE_CHOICE: PASS
  - MULTIPLE_RESPONSE: PASS
  - TRUE_FALSE: PASS
  - MATCHING (format snapshot sanitasi LEFT/RIGHT): PASS
  - SHORT_ANSWER: PASS
  - ESSAY: PASS
  - Arab berharakat + auto RTL pada stem dan editor jawaban: PASS
  - image media same-origin: PASS
  - IndexedDB draft survives reload + reconnect sync: PASS
  - migrasi IndexedDB v1 pending answer ke v2: PASS
  - conflict restore + submit blocked: PASS
  - server expiry auto-submit sekali + timer dibersihkan: PASS
  - unknown submit recovery tanpa duplicate Attempt/submit POST: PASS
- Output akhir: `Agent 4 Web Siswa browser E2E: ALL SCENARIOS PASSED`.

### GitHub Actions — MySQL 8 feature tests
Run yang sama `45063309059`, job `137333869384`:
- MySQL service: `8.0.46`, disposable di GitHub Actions.
- Composer validate/install: PASS.
- `StudentResultVisibilityTest.php`: **7 passed, 22 assertions**.
- `StudentExamPortalTest.php`: **6 passed, 23 assertions**.
- Tidak ada PHPUnit warning pada run final.
- Kedua job workflow (browser dan MySQL) berstatus SUCCESS.

### Belum dijalankan / belum terverifikasi
- PHPUnit seluruh backend di luar dua file test siswa tersebut, PHP lint seluruh repository, API integration real server untuk seluruh jalur, multi-device/lease/fencing, receipt lookup, media authorization runtime terhadap Laravel, dan UAT madrasah.
- Browser E2E menggunakan API mock; MySQL tests menjalankan route Laravel terpilih. Kombinasi keduanya belum membuktikan keseluruhan backend-to-browser integration.
- Tidak ada klaim siap produksi.

## 5. GATE DAN LANGKAH BERIKUTNYA

1. Verifikasi hasil workflow MySQL 8 untuk tujuh test hasil siswa.
2. Tambahkan uji browser untuk token wajib/opsional, media, ukuran mobile/desktop, timer expiry, dan IndexedDB v1 migration jika hasil audit source mendukung.
3. Lakukan integrasi Laravel terhadap daftar ujian, token, execution, jawaban, media, submit, dan hasil.
4. Tutup temuan kontrak backend token opsional, receipt read-only/idempotensi, dan fencing/heartbeat melalui perubahan terpisah pada pemilik source yang berwenang; Agent 4 tidak mengubah file backend tersebut.
5. Perbarui checkpoint ini berdasarkan log nyata berikutnya. PR #13 tetap Draft dan tidak di-merge.

## 6. STATUS CHECKPOINT

Web Siswa client runner: **browser E2E lulus untuk skenario yang dicakup**.
Web Siswa terintegrasi penuh dengan Laravel/MySQL: **belum diverifikasi**.
Device Lease/fencing, receipt, dan produksi: **belum siap diklaim**.
