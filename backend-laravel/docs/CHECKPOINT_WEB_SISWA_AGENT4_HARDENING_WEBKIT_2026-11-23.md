# Checkpoint Agent 4 — Web Siswa Hardening, Browser E2E, dan WebKit
Tanggal: 23 November 2026

## POSISI
- Repo: `zane0184/cbt_november`
- Branch: `work/agent4-web-siswa-emergency-20261122`
- PR: #13 — OPEN, Draft, belum merge.
- Source implementation commit yang diuji: `328c8b45f4e201115d18d309ac1bcb52ca868377`
- Base PR: `work/stage4d-account-lifecycle-20261122`, SHA `7642f8df7d20effe82dc1ba5b086147894332767`.
- Agent 4 tetap hanya bertanggung jawab pada Web Siswa. Tidak mengubah source Agent 2, route API, migration, database, Web Admin/Guru, Android, atau VPS.

## AUDIT
1. Empat halaman Web Siswa yang diaudit: login, daftar ujian, runner, dan hasil.
2. Sebelum hardening, halaman menggunakan Tailwind CDN dan Google Fonts CDN. Sekarang tidak ada URL eksternal pada keempat halaman dan semua merujuk stylesheet lokal.
3. Runner sebelumnya mengaktifkan editor `SHORT_ANSWER`; hal ini bertentangan dengan keputusan pemilik proyek. Sekarang tipe itu tidak merender editor, dan Attempt yang snapshot-nya memuat tipe tersebut diblokir dari submit. `ESSAY` tetap aktif.
4. Halaman exam menunjukkan token hanya bila diperlukan; client tidak mengirim field token pada mode opsional dan memvalidasi token wajib sebelum POST start.
5. Coverage test yang tersedia sebelumnya belum mencakup logout, batas jadwal terperinci, dan attempt limit pada tampilan.
6. Agent 2 current branch yang diperiksa: `work/agent2-p0-auth-session-fencing-20261123`, HEAD `b00cf4312b12e1158c609ca91af3dcecec7a2553`.
   - `ExamAttemptController.php` blob `145913e6d014eed258d66ef971e5e548de81168d` masih menolak ketika `require_token=false`.
   - `ExamAnswerController.php` blob `b2b5a24c4544ce571c4c95b22d01f032fcedbb2a` menerima `client_request_id`, tetapi receipt yang diaudit belum membuktikan field tersebut dikembalikan.
   - `routes/api.php` blob `4a9faa1e2bb0485570f30c199ccfbea7aa3a26ff` belum memiliki read-only lookup untuk status request ID.
   - Attempt authorization/limit dan two-connection concurrency belum ditunjukkan lulus pada branch tersebut; run Agent 2 #89 sebelumnya tercatat 78 passed, 4 failed dan concurrency SKIPPED.
   - Media Range/seek dan enforcement credential native Android belum dibuktikan lulus.
7. Karena kontrak backend belum lengkap, browser-to-Laravel dan integrasi Attempt/Answer/Score/fencing lintas-agent belum aman untuk dijalankan atau dinyatakan lulus.

## DESAIN
- Pertahankan pemisahan Agent 4 dan Agent 2.
- Jangan memakai User-Agent sebagai security boundary.
- Jangan menandai queue accepted atau mengizinkan submit tanpa receipt immutable yang terikat pada request.
- Jangan mengaktifkan SHORT_ANSWER sebelum accepted-answer, snapshot, scoring, kontrak API dan integrasi lulus.
- Untuk CSS lokal, seluruh class utility statis dan className dinamis pada empat view diperiksa terhadap selector CSS lokal.
- Tambahkan Playwright WebKit sebagai pemeriksaan kompatibilitas engine Safari; hasil WebKit bukan pengganti pengujian Safari iOS nyata.

## IMPLEMENTASI
### Source yang berubah
- `backend-laravel/resources/views/student/exams/show.blade.php`
  - SHORT_ANSWER fail-closed, ESSAY saja yang menggunakan textarea.
  - Token wajib diperiksa sebelum POST start; token opsional tidak dikirim.
- `backend-laravel/resources/views/auth/login.blade.php`
- `backend-laravel/resources/views/student/exams/index.blade.php`
- `backend-laravel/resources/views/student/results/show.blade.php`
  - Semua menghapus CDN eksternal dan memuat stylesheet lokal.
- `backend-laravel/public/css/student-portal.css`
  - Utility subset lokal untuk kelas statis dan dinamis, termasuk responsive dan state variants.
- `backend-laravel/tests/runner-static-audit.mjs`
  - Pemeriksaan no-CDN, local CSS reference, cakupan class CSS, JavaScript parse, ID unik, IndexedDB, fencing, receipt, timer, aksesibilitas dan layout.
- `backend-laravel/tests/runner-browser-e2e.mjs`
  - Token wajib/opsional, SHORT_ANSWER disabled, local CSS/responsive, IndexedDB/recovery, receipt, fencing simulasi, media dan submit.
  - Mendukung Chromium dan Playwright WebKit.
- `backend-laravel/tests/Feature/StudentExamPortalTest.php`
  - Tambahan test token opsional view, batas jadwal, dan attempt limit.
- `backend-laravel/tests/Feature/StudentWebLoginTest.php`
  - Tambahan test logout dan session tidak dapat membuka kembali portal.
- `.github/workflows/agent4-web-siswa-runner-static.yml`
  - Menjalankan static audit, Chromium E2E, WebKit E2E, MySQL 8 feature tests, login/logout.

### Backup
Semua backup berikut dibuat sebelum perubahan file masing-masing dan dibandingkan byte-exact saat dibuat:
- `backend-laravel/docs/backup/agent4-web-siswa-emergency-2026-11-22/show.blade.php.before-hardening-2026-11-23`
- `backend-laravel/docs/backup/agent4-web-siswa-emergency-2026-11-22/index.blade.php.before-local-assets-2026-11-23`
- `backend-laravel/docs/backup/agent4-web-siswa-emergency-2026-11-22/results-show.blade.php.before-local-assets-2026-11-23`
- `backend-laravel/docs/backup/agent4-web-siswa-emergency-2026-11-22/login.blade.php.before-local-assets-2026-11-23`
- `backend-laravel/docs/backup/agent4-web-siswa-emergency-2026-11-22/runner-static-audit.mjs.before-hardening-2026-11-23`
- `backend-laravel/docs/backup/agent4-web-siswa-emergency-2026-11-22/runner-browser-e2e.mjs.before-hardening-2026-11-23`
- `backend-laravel/docs/backup/agent4-web-siswa-emergency-2026-11-22/StudentExamPortalTest.php.before-coverage-2026-11-23`
- `backend-laravel/docs/backup/agent4-web-siswa-emergency-2026-11-22/StudentWebLoginTest.php.before-coverage-2026-11-23`
- `backend-laravel/docs/backup/agent4-web-siswa-emergency-2026-11-22/agent4-web-siswa-runner-static.yml.before-hardening-2026-11-23`
- `backend-laravel/docs/backup/agent4-web-siswa-emergency-2026-11-22/student-portal.css.before-escape-fix-2026-11-23`
- `backend-laravel/docs/backup/agent4-web-siswa-emergency-2026-11-22/student-portal.css.before-dynamic-utility-coverage-2026-11-23`
- `backend-laravel/docs/backup/agent4-web-siswa-emergency-2026-11-22/runner-browser-e2e.mjs.before-webkit-smoke-2026-11-23`
- `backend-laravel/docs/backup/agent4-web-siswa-emergency-2026-11-22/agent4-web-siswa-runner-static.yml.before-webkit-smoke-2026-11-23`

## PENGUJIAN
### GitHub Actions run #272
URL: https://github.com/zane0184/cbt_november/actions/runs/45106592859
Source SHA: `328c8b45f4e201115d18d309ac1bcb52ca868377`
Status: **SUCCESS**, kedua job sukses.

- Static audit: PASS; seluruh selector class utility statis/dinamis ditemukan pada stylesheet lokal; 27 HTML IDs unik; JavaScript inline valid.
- Chromium browser E2E: 27 skenario PASS.
- Playwright WebKit browser E2E: 27 skenario PASS. Ini hanya pemeriksaan WebKit engine di Linux, bukan Safari iOS pada perangkat Apple.
- MySQL 8.0.46 disposable:
  - `StudentResultVisibilityTest`: 7 passed / 22 assertions.
  - `StudentExamPortalTest`: 9 passed / 44 assertions.
  - `StudentWebLoginTest`: 12 passed / 88 assertions.
- Kedua workflow job: SUCCESS.
- Tidak ada migration atau mutasi database produksi.

### Riwayat koreksi
- Run #247 gagal karena assertion statis salah mengharuskan selector runner inline ada di file CSS lokal, serta ekspektasi label attempt limit tidak sesuai status view yang aktual.
- Keduanya diperbaiki; run #252 kemudian SUCCESS pada commit `de202c5cf5168a73a4bf6ac1736179ade0a4c92e`.
- Run #264 SUCCESS pada commit `0725a2072038bf8d97597c40f82536584b9f81ea`, termasuk validasi CSS lokal/responsif.
- Run #272 SUCCESS menambahkan full WebKit E2E.

### Batas bukti
- Chromium/WebKit E2E menggunakan fixture HTML dan API mock. Ini bukan browser-to-real-Laravel integration.
- PHPUnit menguji halaman Laravel dan data fixture pada MySQL disposable; tidak menguji full lifecycle Attempt/Answer/Score/fencing antar perangkat.
- WebKit Linux bukan perangkat iPhone/iPad dan bukan bukti Safari iOS/VoiceOver.
- Media E2E mock membuktikan client MIME/size/fail-closed/Object URL, bukan Range/seek backend.
- Tidak ada UAT nyata bersama Admin, Guru, dan Siswa madrasah.

## DOKUMENTASI GATE LANJUTAN
- Browser-to-Laravel integration: `backend-laravel/docs/AGENT4_WEB_SISWA_BROWSER_LARAVEL_INTEGRATION_GATE_2026-11-23.md`
- iOS Safari + madrasah UAT: `backend-laravel/docs/AGENT4_WEB_SISWA_IOS_SAFARI_UAT_CHECKLIST_2026-11-23.md`

## PEKERJAAN TERTUNDA / LANGKAH BERIKUTNYA
1. Tunggu Agent 2 mengirim branch/commit final yang membuktikan token opsional, receipt `client_request_id`, read-only receipt recovery atau semantik exact retry, attempt authorization/limit/concurrency, platform credential enforcement, dan media Range/seek.
2. Audit source exact commit Agent 2 dan cocokkan request/response contract sebelum menulis integration test.
3. Jalankan browser-to-real-Laravel pada MySQL 8 disposable: login, token, schedule, attempt, answer receipt, response loss, offline recovery, fencing multi-session, submit/expiry, score/result, media dan platform boundary.
4. Lakukan UAT Safari iOS pada iPhone/iPad nyata, termasuk VoiceOver, jaringan madrasah, sleep/wake, IndexedDB, media seek dan recovery.
5. Jalankan UAT Admin/Guru/Siswa di instalasi uji; catat bukti tersanitasi dan tindak lanjut.
6. PR #13 tetap Draft/unmerged sampai semua gate yang relevan lulus.

Tidak ada klaim siap produksi. Tidak ada merge, deployment, migration produksi, atau perubahan source Agent 2.
