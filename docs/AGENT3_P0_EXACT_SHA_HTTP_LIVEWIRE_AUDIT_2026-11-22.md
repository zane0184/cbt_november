# Laporan Audit Agent 3 — P0 Exact-SHA CI & HTTP Livewire Security Gate

Tanggal audit: 22 November 2026
Repository: `zane0184/cbt_november`
Branch: `work/web-guru-foundation-20261122`
HEAD saat audit dimulai: `f13e2adaf284e2a1d727ea23b7c9788f793e3ec1`
Target source test: `6334dc9abd07a1441c8c02f3f7a6cc5c452ead36`
File test: `backend-laravel/tests/Feature/TeacherPortalFoundationTest.php`
Blob test: `4f1bb48b390b03b2602df6348fd3b9788581e6c9`
Status: **P0 OPEN — EXACT-SHA CI / REAL HTTP SECURITY PROOF INCOMPLETE**

## A. AUDIT

### A.1 Branch Agent 3 dan target source

- Refresh branch berhasil: HEAD `f13e2adaf284e2a1d727ea23b7c9788f793e3ec1`.
- Target commit `6334dc9abd07a1441c8c02f3f7a6cc5c452ead36` reachable dari branch.
- Compare target → HEAD: `ahead_by=2`, `behind_by=0`; dua commit tambahan hanya menambahkan laporan dan checkpoint, tidak mengubah source test/runtime.
- Blob test aktual tetap `4f1bb48b390b03b2602df6348fd3b9788581e6c9`.
- Statis terverifikasi: 22 test methods.
- Branch tidak di-merge, rebase, cherry-pick, atau dipindahkan.

### A.2 Backup

- Backup: `docs/backup/web-guru-exact-role-20261122/TeacherPortalFoundationTest.php.before-real-http-livewire-20261122`.
- Blob backup: `0b273618677c791d82645fa373be57aa2d961643`.
- Source sebelum patch pada commit `4dc407d091fc6cf786260052cfd86d836edc5908` memiliki blob yang sama.
- Fetch ulang source pre-patch dan backup menghasilkan konten byte-identical, masing-masing 31.772 byte.
- Addendum backup `REAL_HTTP_LIVEWIRE_BACKUP_ADDENDUM.md`, blob `e6a462bca7ace8cabccd7e46960377d5c46c15a1`, mencatat SHA dan alasan backup.
- Pemeriksaan sebelumnya mencatat manifest backup lama 34/34 cocok. Pemeriksaan sesi ini mengonfirmasi kembali backup khusus test terhadap source pre-patch.
- Backup bukan salinan source terbaru; ia memang salinan byte-exact source SEBELUM patch. Karena itu blob backup berbeda dari blob test terbaru.

### A.3 Refresh branch lintas-agent

| Owner | Branch | HEAD aktual saat audit | Delta dari HEAD yang tercatat laporan Agent 3 sebelumnya |
|---|---|---|---|
| Agent 1 | `work/stage4b-room-session-20261122` | `69f16da92452fd28a85aec2a8239c5d5ba9a10dd` | 7 commit, dokumentasi/manifest saja |
| Agent 2 | `work/stage4d-account-lifecycle-20261122` | `09b0ee42555b58c520b6ce3b46243b684e5a9d69` | 2 commit, audit/checkpoint dokumentasi saja |
| Agent 3 | `work/web-guru-foundation-20261122` | `f13e2adaf284e2a1d727ea23b7c9788f793e3ec1` | 2 commit setelah target, laporan/checkpoint saja |
| Agent 4 dokumentasi | `work/agent4-web-siswa-audit-20261122` | `05447d93da90063389b822ce2468a459c5bbac11` | 13 commit, dokumentasi dan backup saja |
| Kandidat Agent 4 | `work/import-guru-siswa-implementation-20261122` | `f38ceec7b0653fcf3363eec40f3d4577c323cca8` | kandidat belum dikonfirmasi sebagai branch resmi |

HEAD Agent 1/2/4 yang tercantum pada laporan Agent 3 sebelumnya sudah usang. Compare branch menunjukkan delta setelah checkpoint yang disebut di laporan hanya berupa dokumen; tidak ditemukan perubahan source runtime pada delta tersebut. Kandidat Agent 4 tetap tidak dianggap branch resmi.

### A.4 Issue dan approval

- Issue #8: OPEN, 7 komentar.
- Issue #9: OPEN, 2 komentar.
- Issue #10: OPEN, 15 komentar.
- Seluruh komentar yang diperiksa pada Issue #10 berasal dari akun `zane0184`; belum ada jawaban owner teknis terpisah yang dapat diverifikasi sebagai approval Agent 1/2/3/4.
- Keputusan koordinator pada komentar #7078539953 final untuk portal aktif ADMIN_MADRASAH/GURU/SISWA, KAMAD frozen, dan Proktor sebagai assignment sementara akun GURU. Keputusan produk ini bukan approval implementasi lintas-owner.
- Issue #8/#9 masih meminta persetujuan field-level kontrak import; import XLSX/DOCX tetap desain.
- Issue #10 tetap menunggu konfirmasi owner untuk lifecycle Exam–ExamSession, Attempt/recovery, room/Proktor scope, dan integrasi Web Siswa.

### A.5 Temuan source test dan HTTP Livewire

Composer lock blob `6369f2a85c8b7110d6a3fe36d06599df6f380045` mengunci Livewire `v3.8.11`, source reference `faee62c6a55e87befd24231bbdadca2aeca1cae5`.

Test terbaru memiliki helper:
- `extractLivewireSnapshot()`: mengambil tepat satu atribut `wire:snapshot` dari HTML halaman, decode HTML entity, lalu memvalidasi JSON.
- `postLivewireUpdate()`: POST JSON ke `app('livewire')->getUpdateUri()`, header `X-Livewire: true`, payload `components[0].snapshot/calls/updates`.
- `tenantQuestionExamSnapshot()`: snapshot integritas terbatas pada tabel questions, question_versions, question_options, assessments, exams.

Test `test_http_livewire_update_rebuilds_tenant_context_and_rejects_stale_tenant_identity`:
1. GET halaman aktual `/admin/questions` dan `/admin/exams`.
2. Mengambil snapshot dari kedua response.
3. Mengosongkan TenantContext sebelum HTTP update POST.
4. Memastikan update sah membangun kembali tenant context dari authenticated user.
5. Mengubah tenant_id user secara persisted di database.
6. Mengirim update HTTP berikutnya dengan snapshot hasil update terakhir; kedua komponen harus ditolak 403.
7. Memastikan tenant_id user telah berubah sesuai fixture dan snapshot lima tabel domain tetap sama.

Batas pembuktian yang tepat:
- GET awal berjalan melalui route group `auth`, `tenant`, `license`; route didefinisikan pada `backend-laravel/routes/web.php` blob `0cf0c73d28a1f8ab948e7594b6a588c1ca864d6a`.
- POST update memang memakai HTTP request ke URI Livewire update, bukan `Livewire::test()->set()`.
- Ini membuktikan request-level HTTP update dan component boundary pada dua komponen yang diuji; bukan bukti bahwa semua middleware route awal otomatis berjalan kembali pada update.
- `IdentifyTenant` tidak didaftarkan sebagai Livewire persistent middleware pada `AppServiceProvider` blob `9cb0fa0b9e9db7a338741e14a7c0798d635e9bc6`. Source bootstrap mendaftarkan alias tenant untuk route biasa, tetapi tidak mendaftarkan persistent middleware Livewire.
- Pada update, `TeacherPortalAccess::profile()` blob `8c60fb6db17512d6bbf8845027f69841172e2f60` mengisi TenantContext hanya dari tenant authenticated user, memeriksa user persisted/non-deleted, tepat satu role GURU, dan tepat satu TeacherProfile tenant-lokal. QuestionBank dan ExamManagement memanggil boundary ini pada boot/mount dan memeriksa assignment aktif.
- Test tidak membuktikan `IdentifyTenant` middleware berjalan pada endpoint update. Test membuktikan component boundary membangun context ketika kosong dan menolak tenant identity persisted yang stale.
- Request memakai Laravel feature-test application yang sama; ini bukan uji multi-worker/process atau browser session production.
- CSRF tidak dibuktikan: request test tidak menyertakan token CSRF, dan environment testing Laravel tidak merepresentasikan validasi CSRF production.
- Snapshot berasal dari halaman aktual dan snapshot hasil update dipakai untuk request berikutnya. Tidak ada test eksplisit untuk snapshot tampering, checksum mismatch, replay lintas-session, atau session cookie lintas instalasi.
- Pemeriksaan data tidak berubah hanya mencakup lima tabel yang dikembalikan `tenantQuestionExamSnapshot()`; tidak mencakup seluruh tabel, audit log, cache, queue, atau efek samping eksternal.

Enam regresi post-mount yang dipertahankan adalah permission revoke, assignment inactive/revoked, TeacherProfile soft-delete, User soft-delete, tenant-context mismatch, dan role GURU revoke. Namun test-test regresi tersebut masih menggunakan `Livewire::test()` atau pemanggilan `boot()` langsung. Livewire test harness menonaktifkan middleware pada initial/subsequent request. Jadi keenamnya bukan bukti bahwa pencabutan tersebut sudah diuji melalui HTTP update endpoint production-equivalent. HTTP POST baru secara langsung mencakup stale tenant identity pada satu test.

### A.6 Web Guru boundary dan lintas-agent

- Route `/admin/questions` dan `/admin/exams` berada dalam route group `auth`, `tenant`, `license`; component `QuestionBank` dan `ExamManagement` menggunakan `TeacherPortalAccess` serta pemeriksaan assignment aktif.
- Route group `/guru` pada `routes/teacher.php` menggunakan `EnsureTeacherPortalAccess`, tetapi saat ini berisi dashboard, pembelajaran, kurikulum, profil, dan template bank soal; QuestionBank/ExamManagement belum berada pada prefix `/guru`. Ini ketidaksesuaian route/ownership UI yang perlu diputuskan, bukan bukti bypass otorisasi dengan sendirinya.
- Agent 2 ExamPolicy blob `c05d96ef932fdc81b67336240af4e1726864c3d6` masih memiliki banyak `hasRole()` non-exact, mengandung jalur KAMAD, dan `startAttempt` memeriksa keberadaan SISWA alih-alih tepat satu role SISWA.
- Agent 2 QuestionPolicy blob `af2b3be5fdee31b857c56fdf09ef54ffd02ed94e` menggunakan helper exact-GURU; QuestionPolicy Agent 3 blob `81e3cb6c82f1a2f5e15deacf8b8ea50f8a494566` masih menggunakan `hasRole('GURU')`. Perbedaan perlu rekonsiliasi oleh owner; tidak diubah oleh Agent 3.
- Audit Agent 2 terbaru menyatakan KAMAD masih dikenali pada login/API/policy/monitoring di source runtime baseline; menu khusus Proktor pada Web Guru belum ditemukan; Proktor assignment UI yang ada masih Admin. Status exact-role/KAMAD/Proktor backend tetap blocker.
- Agent 1 checkpoint terbaru masih menyatakan kontrak lifecycle/session/recovery dan approval lintas-owner pending.
- Agent 4 checkpoint terbaru menyatakan Web Siswa NOT READY FOR OPERATIONAL EXAM; F-01/F-02, admission/media, receipt/conflict, submit/recovery, lease/fencing, dan approval owner masih pending.

## B. DESAIN

### B.1 Strategi pengujian berikutnya

1. Dapatkan CI run yang `head_sha`-nya persis `6334dc9abd07a1441c8c02f3f7a6cc5c452ead36`; periksa run metadata, attempt, job, steps, logs, artifacts dan cleanup.
2. Jalankan PHP lint, route registration dan PHPUnit hanya pada environment proyek dengan dependency terkunci dan database disposable.
3. Perluas HTTP Livewire test agar permission revoke, assignment revoke/expiry, role revoke, TeacherProfile soft-delete dan User soft-delete benar-benar diuji pada request update baru, dengan snapshot valid hasil mount/update.
4. Uji session/auth identity, checksum/snapshot rejection, CSRF pada environment non-test, dan pastikan tidak ada perubahan domain/audit data setelah denial.
5. Uji MySQL 8/InnoDB disposable, constraint, transaksi, tidak ada partial write, serta cleanup yang hanya menyentuh database job.
6. Tunggu Agent 2 menutup exact-role/KAMAD dan menyetujui rekonsiliasi QuestionPolicy; tunggu kontrak Agent 1/2/4 yang diperlukan.
7. Browser QA hanya setelah semua gate di atas lulus dan pada non-production.

### B.2 Kriteria lulus

- CI exact SHA dengan log dan job steps yang menunjukkan PHP lint, route registration, PHPUnit, MySQL 8/InnoDB dan cleanup sukses.
- Semua post-mount revocation diuji melalui HTTP update baru dan ditolak.
- Tenant A/B tidak bocor; snapshot dan seluruh data domain/audit relevan tidak berubah setelah penolakan.
- Exact-role backend hanya menerima role tunggal yang sesuai; KAMAD tidak memiliki jalur akses CBT.
- Proktor hanya assignment sementara akun GURU dan room-scoped.
- Browser QA memiliki bukti akun uji, viewport, langkah, expected/actual, screenshot dan log.

## C. IMPLEMENTASI

- Tidak ada source runtime, test, route, middleware, policy, controller, service, migration, schema, Composer, workflow, konfigurasi, atau database yang diubah dalam audit ini.
- Tidak ada merge, rebase, cherry-pick, deployment, atau workflow rerun.
- Laporan dan checkpoint audit baru dibuat sebagai dokumentasi saja; tidak menimpa file sebelumnya.
- Source test tetap blob `4f1bb48b390b03b2602df6348fd3b9788581e6c9`; backup pre-patch tetap blob `0b273618677c791d82645fa373be57aa2d961643`.

## D. PENGUJIAN

| Pemeriksaan | Status | Bukti / batas |
|---|---|---|
| Refresh branch Agent 3 | PASS | HEAD `f13e2ada...` |
| Target exact source reachable | PASS | Compare target → HEAD ahead 2, behind 0 |
| Source test blob | PASS | `4f1bb48...` |
| Test method count | PASS (statis) | 22; bukan hasil eksekusi |
| Backup pre-patch byte-exact | PASS | Source pre-patch dan backup sama-sama blob `0b273618...`, 31.772 byte |
| Backup manifest lama | PASS (audit terdahulu) | 34/34 blob cocok pada pemeriksaan sebelumnya |
| PHP lint | NOT RUN | Source tidak tersedia di workspace lokal; Composer tidak tersedia |
| PHP runtime kompatibel proyek | BLOCKED | CLI tersedia PHP 8.4.24; target proyek PHP 8.3 |
| Composer dependency install/version | NOT RUN | Composer tidak tersedia di environment |
| Route registration runtime | NOT RUN | Source route statis diperiksa; `route:list` tidak dijalankan |
| PHPUnit | NOT RUN | PHPUnit/vendor dan project checkout tidak tersedia |
| MySQL 8/InnoDB disposable | NOT RUN | mysql/mariadb client dan database disposable tidak tersedia |
| CI exact-SHA | UNKNOWN | Workflow-run helper mengembalikan daftar kosong dan hanya memfilter PR-triggered runs; combined status kosong. Ini bukan bukti tidak ada push run |
| Run #41 lama | NOT ACCEPTANCE EVIDENCE | SHA `88b5c135...`, bukan target `6334dc9...`; steps/log tidak tersedia |
| HTTP Livewire update | PASS (cakupan terbatas, statis) | Source test membentuk POST ke update URI; PHPUnit belum dijalankan |
| Permission/assignment/role/profile/user revocation via HTTP | NOT RUN | Regresi yang ada memakai Livewire test harness/direct boot |
| Tenant A/B full integration | NOT RUN | Ada test lintas-tenant statis; PHPUnit/MySQL belum dijalankan |
| Post-denial data integrity | PARTIAL / NOT RUN | Test membandingkan lima tabel; eksekusi belum dilakukan |
| Browser QA | BLOCKED | CI, PHP/PHPUnit, MySQL, exact-role dan kontrak lintas-agent belum lulus |
| Database produksi | PASS — NOT TOUCHED | Tidak ada koneksi atau operasi produksi |

Environment audit lokal: direktori `/mnt/data/CBT_MADRASAH_RECONSTRUCTION` tidak tersedia; `composer`, `mysql`, `mariadb`, dan `phpunit` tidak ditemukan. PHP CLI yang tersedia adalah 8.4.24, bukan PHP 8.3 target proyek. Tidak ada test runtime yang diklaim berhasil.

## E. CHECKPOINT

**Status: P0 OPEN — EXACT-SHA CI / REAL HTTP SECURITY PROOF INCOMPLETE.**

Posisi:
- Agent 3 branch HEAD saat audit dimulai: `f13e2adaf284e2a1d727ea23b7c9788f793e3ec1`.
- Target test source: `6334dc9abd07a1441c8c02f3f7a6cc5c452ead36`.
- Test blob: `4f1bb48b390b03b2602df6348fd3b9788581e6c9`.
- Backup pre-patch blob: `0b273618677c791d82645fa373be57aa2d961643`, byte-exact dengan source pre-patch pada `4dc407d...`.
- Tidak ada source runtime/test berubah pada audit ini.

Blocker:
1. CI exact-SHA belum dapat diverifikasi; konektor workflow yang tersedia hanya menampilkan PR-triggered runs.
2. PHP lint, route:list, PHPUnit, MySQL 8/InnoDB dan cleanup belum dijalankan karena environment tidak tersedia.
3. HTTP update test baru membuktikan tenant context rebuild dan stale tenant reassignment, bukan seluruh post-mount revocation.
4. CSRF, snapshot tampering/replay, full-session production equivalence dan semua data side effect belum diuji.
5. Agent 2 ExamPolicy masih non-exact dan masih mengenali KAMAD; QuestionPolicy Agent 2/3 berbeda.
6. Approval owner lintas-agent belum terverifikasi; Issue #8/#9/#10 masih OPEN.
7. Browser QA tetap BLOCKED.

Urutan berikutnya:
1. Owner/repository maintainer menyediakan bukti CI exact-SHA atau menjalankan workflow untuk exact SHA sesuai otorisasi.
2. Siapkan checkout dan dependency sesuai lockfile pada PHP 8.3, serta MySQL 8/InnoDB disposable.
3. Jalankan PHP lint, route registration dan PHPUnit; simpan log/artifact dan bukti cleanup.
4. Tambah/eksekusi HTTP revocation matrix tanpa mengubah middleware produksi hanya untuk mengakali test.
5. Minta Agent 2 menyelesaikan exact-role/KAMAD dan approval rekonsiliasi policy.
6. Dapatkan keputusan owner Agent 1/2/4 untuk kontrak room/session/recovery/Proktor/Web Siswa.
7. Setelah gate backend/CI lulus, jalankan Browser QA non-production.

Tidak ada izin untuk merge/rebase/cherry-pick, migration/deployment produksi, perubahan source milik Agent lain, atau klaim siap produksi. Database produksi tidak disentuh.
