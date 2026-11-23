# Checkpoint Agent 4 — Penguatan Role Tunggal Portal Siswa
Tanggal: 23 November 2026

## POSISI
- Repo: `zane0184/cbt_november`
- Branch: `work/agent4-web-siswa-emergency-20261122`
- PR: #13 — OPEN, Draft, belum merge.
- Audit baseline branch Agent 4 sebelum patch: commit `787293bb31924dbaa2f7bf7768579d64380ca858`.
- Runner Blade tidak diubah pada tahap ini.
- Perubahan hanya pada controller halaman Web Siswa dan feature test portal Siswa.

## AUDIT
1. `StudentExamController` sebelumnya mengizinkan identitas yang memiliki role `SISWA` selama tidak memiliki role `ADMIN_MADRASAH`.
2. Guard tersebut tidak menolak akun dengan kombinasi role `SISWA + GURU`. Ini tidak sesuai keputusan role lokal yang mewajibkan satu role portal aktif per akun.
3. Pemeriksaan menggunakan relasi `roles` yang dipanggil sebagai properti dapat bergantung pada relasi Eloquent yang sudah dimuat. Guard baru membaca role tersimpan melalui query relasi.
4. User model memiliki `hasExactRole('SISWA')` yang juga mensyaratkan profil akademik aktif. Fixture portal Web Siswa yang ada saat ini tidak membangun student profile; karena itu patch ini secara sengaja membatasi perubahan pada invariant satu role tersimpan `SISWA`, tanpa mengubah persyaratan profil atau data akademik.
5. Audit ulang dependency Agent 2 pada branch `work/agent2-p0-auth-session-fencing-20261123`:
   - `ExamAttemptController.php`, blob `145913e6d014eed258d66ef971e5e548de81168d`: kondisi `!require_token || !token` masih menolak ujian token opsional.
   - `ExamAnswerController.php`, blob `b2b5a24c4544ce571c4c95b22d01f032fcedbb2a`: ledger menyimpan `client_request_id` dan exact replay mengembalikan response tersimpan, tetapi objek `receipt` tidak menyertakan `client_request_id`. Runner akan menahan ACK sampai receipt cocok.
   - `routes/api.php`, blob `4a9faa1e2bb0485570f30c199ccfbea7aa3a26ff`: belum ditemukan endpoint lookup receipt read-only.
   - Media endpoint saat ini mengirim file melalui `response()->file()`; pengujian Range/seek pada media audio/video belum tersedia dalam bukti yang diaudit.
6. Status backend di atas adalah blocker lintas-agent; Agent 4 tidak mengubah source Agent 2.

## DESAIN
- Semua halaman Web Siswa yang dilindungi oleh `StudentExamController` hanya boleh dibuka oleh akun dengan tepat satu role tersimpan, yaitu `SISWA`.
- Pemeriksaan role dilakukan terhadap database melalui `$user->roles()`, bukan relasi yang mungkin sudah termuat di memori.
- Penolakan mempertahankan status HTTP sebelumnya: 403 untuk daftar/detail ujian dan 404 untuk hasil.
- Tidak menambah role, portal, endpoint, migration, atau perubahan schema.
- Tidak mengubah validasi API Attempt/Answer, Device Lease/Fencing, atau aturan admission.

## IMPLEMENTASI
### File berubah
1. `backend-laravel/app/Http/Controllers/StudentExamController.php`
   - Guard index, show, dan result dialihkan ke helper `isSingleStudentIdentity()`.
   - Helper menolak user kosong/tanpa tenant, role kosong, multi-role, atau role tunggal selain `SISWA`.
2. `backend-laravel/tests/Feature/StudentExamPortalTest.php`
   - Menambahkan test akun dengan kombinasi `SISWA + GURU`; daftar dan detail ujian harus mengembalikan 403.

### Backup pra-perubahan
- Controller backup: `backend-laravel/docs/backup/agent4-web-siswa-emergency-2026-11-22/StudentExamController.php.before-single-role-guard-2026-11-23`
  - Blob SHA: `981fd29d24906c158d4dda5889950fdc43f6ec3d`, sama dengan blob source sebelum patch.
- Test backup: `backend-laravel/docs/backup/agent4-web-siswa-emergency-2026-11-22/StudentExamPortalTest.php.before-single-role-guard-2026-11-23`
  - Blob SHA: `5bfe3346a1ee0a247e0eb057146cb7758c1c2aa2`, sama dengan blob test sebelum patch.

### Commit implementasi
- Controller patch commit: `aa7fc1abbfe7d77a485b5bf31a5ad8bb933fe598`
- Test patch commit: `f7bd47c831f33e7b07378d5048c6877a1907945d`
- Controller blob sesudah patch: `9a0acc5fc871e033b09f044172569ec4fc070833`
- Test blob sesudah patch: `66c8eafe5732197c2c8af42ca70c83b09a911206`

## PENGUJIAN
- Backup diperiksa melalui fetch ulang; blob backup sama dengan blob source/test sebelum patch.
- Source dan test sesudah patch diperiksa melalui fetch ulang pada branch Agent 4.
- GitHub Actions run #280, ID `45110168166`, terdeteksi pada commit test patch dan berstatus PENDING saat checkpoint ini ditulis:
  https://github.com/zane0184/cbt_november/actions/runs/45110168166
- Belum ada hasil PHPUnit/CI yang dapat diklaim PASS pada checkpoint ini.
- Tidak ada pengujian lokal PHP/MySQL yang dijalankan dari sesi ini.

## BLOCKER DAN LANGKAH BERIKUTNYA
1. Tunggu hasil run #280 dan perbaiki hanya jika ada kegagalan yang relevan.
2. Minta Agent 2 menyelesaikan token opsional, receipt lengkap dengan `client_request_id`, receipt recovery/read-only lookup atau kontrak exact retry, Attempt authorization/limit + concurrency, enforcement kredensial native Android, dan media MIME/Range/seek.
3. Setelah commit Agent 2 tersedia, audit source exact SHA dan cocokkan kontrak sebelum menambah browser-to-real-Laravel integration test.
4. Lanjutkan Safari iOS nyata dan UAT madrasah setelah instalasi uji serta perangkat tersedia.

PR #13 tetap Draft/unmerged. Tidak ada merge, deployment, migration produksi, perubahan database produksi, atau perubahan source Agent 2/3/Android/VPS. Tidak ada klaim siap produksi.


## PEMBARUAN STATUS CI — 23 November 2026
- GitHub Actions run #280 (ID `45110168166`) pada commit test `f7bd47c831f33e7b07378d5048c6877a1907945d` berubah dari PENDING menjadi **CANCELLED**.
- Fetch run jobs mengembalikan daftar kosong; tidak ada log job atau hasil PHPUnit yang dapat diverifikasi.
- Karena run dibatalkan, hasil pengujian patch role tunggal tetap **BELUM TERVERIFIKASI**. Jangan menafsirkan cancellation sebagai PASS atau FAIL kode.
- Workflow `agent4-web-siswa-runner-static.yml` memiliki trigger push untuk branch Agent 4, pull_request ke base `work/stage4d-account-lifecycle-20261122`, dan workflow_dispatch. Connector yang tersedia pada sesi ini hanya mengembalikan run PR; run push tidak dapat diverifikasi melalui fetch PR-run tersebut.
- Langkah berikutnya: jalankan ulang workflow melalui GitHub Actions UI (workflow_dispatch) atau tunggu run PR baru yang dapat diverifikasi. Periksa kedua job: runner static/browser (Chromium + WebKit) dan feature tests MySQL 8.
