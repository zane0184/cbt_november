# Laporan Agent 3 — P0 Real HTTP Livewire Lifecycle & Boundary Refresh

Tanggal: 22 November 2026
Repository: `zane0184/cbt_november`
Branch: `work/web-guru-foundation-20261122`
Source commit: `6334dc9abd07a1441c8c02f3f7a6cc5c452ead36`
Test blob: `4f1bb48b390b03b2602df6348fd3b9788581e6c9`
HEAD branch sebelum dokumen laporan: `6334dc9abd07a1441c8c02f3f7a6cc5c452ead36`

Status: **P0 OPEN — TENANT/SECURITY TEST INCOMPLETE**

## 1. AUDIT

### 1.1 Branch dan source aktual

| Owner | Branch | HEAD aktual | Catatan |
|---|---|---|---|
| Agent 1 | `work/stage4b-room-session-20261122` | `51571ca54ec922466e6230b4e8137a85336bc024` | Commit terakhir dokumentasi/manifest; source runtime tidak diubah pada commit tersebut. |
| Agent 2 | `work/stage4d-account-lifecycle-20261122` | `462cadc96a95d091bd89819dbc106b4fbd67e1cd` | Commit terakhir checkpoint dokumentasi; source policy diperiksa terpisah. |
| Agent 3 | `work/web-guru-foundation-20261122` | `6334dc9abd07a1441c8c02f3f7a6cc5c452ead36` | Test hardening + backup. |
| Agent 4 dokumentasi | `work/agent4-web-siswa-audit-20261122` | `edec6c2bddfda5563c907827db7d1ae77775c086` | Dokumentasi saja. |
| Kandidat Agent 4 | `work/import-guru-siswa-implementation-20261122` | `f38ceec7b0653fcf3363eec40f3d4577c323cca8` | Owner belum mengonfirmasi sebagai branch resmi. |

Test file memiliki **22 test methods**. Enam regresi post-mount tetap ada: permission revoke, assignment inactive, TeacherProfile soft-delete, User soft-delete, tenant-context mismatch, dan role GURU revoke.

Source Web Guru yang diperiksa:
- QuestionBank: blob `219efc2db8f492452ad95da35ae22eb4849d1e66`.
- ExamManagement: blob `d45293ea8cfd01bf6eaeea17ced0579a8d8bc4cc`.
- TeacherPortalAccess: blob `8c60fb6db17512d6bbf8845027f69841172e2f60`.
- TeacherAccess: blob `766289c5163b6cf367d511c0bf35207ccbc97684`.
- IdentifyTenant: blob `376a4b2771c3d9a6b128ad847c150d47ac2977db`.
- bootstrap/app.php: blob `d1137fce9e10bc490a044f950170008d55cab914`.
- routes/web.php: blob `0cf0c73d28a1f8ab948e7594b6a588c1ca864d6a`.

Route `/admin/questions` dan `/admin/exams` berada dalam group `auth`, `tenant`, dan `license`. TeacherPortalAccess mensyaratkan tepat satu role GURU, user persisted/non-soft-deleted, tenant context yang cocok, tepat satu TeacherProfile tenant-lokal, dan assignment aktif.

### 1.2 Temuan penting Livewire HTTP lifecycle

Composer lock mengunci Livewire `v3.8.11`, composer.lock blob `6369f2a85c8b7110d6a3fe36d06599df6f380045`.

Source dependency yang diperiksa pada commit Livewire terkunci:
- Testable: blob `a7bd2b065bb6797119b25b8a97a28eed9242d33d`.
- RequestBroker: blob `bee4594d3cee1175c645d82d7819306b88a4a6d0`.
- InitialRender: blob `52184b2c59963164e2b2082604ef750195100ff1`.
- SubsequentRender: blob `925a6277b07e339fd0a7a27cee4d3933a2e74ae9`.
- PersistentMiddleware: blob `e09af9fc9d53a06e165c300fa66a1f0557e7b19a`.
- HandleRequests: blob `03bbe6b0536df8fc804666e220c972038c6065a4`.

Temuan:
1. Livewire `Livewire::test()` menggunakan RequestBroker yang memanggil `withoutMiddleware()` untuk initial dan subsequent test request.
2. Dengan demikian, GET halaman asli yang terpisah dari instance `Livewire::test()` tidak membuktikan middleware route dijalankan pada update komponen tersebut.
3. PersistentMiddleware bawaan memuat Authenticate/Authorize dan middleware tertentu, tetapi tidak memuat custom `IdentifyTenant`.
4. AppServiceProvider branch Agent 3 tidak mendaftarkan `IdentifyTenant` melalui `Livewire::addPersistentMiddleware()`.
5. TeacherPortalAccess membangun tenant context dari user authenticated ketika context kosong dan menolak context non-null yang berbeda. Ini merupakan boundary fail-closed pada component lifecycle.

### 1.3 Backend Agent 2 — blocker exact-role

ExamPolicy Agent 2:
- Branch: `work/stage4d-account-lifecycle-20261122`.
- Blob: `c05d96ef932fdc81b67336240af4e1726864c3d6`.
- Masih memakai `hasRole()` non-exact pada beberapa capability, termasuk viewAny/view/preview/create/update/delete/manageQuestions/manageParticipants/generateToken/lock/close/publishResults.
- startAttempt masih memeriksa keberadaan role SISWA, bukan tepat satu role SISWA.
- Jalur KAMAD masih ada.

Keputusan koordinator terbaru pada Issue #10 membekukan KAMAD dari seluruh portal/access CBT. KAMAD tidak boleh memiliki login, dashboard, route, permission, policy, menu, monitoring atau bypass CBT. Proktor adalah assignment sementara untuk akun GURU.

QuestionPolicy Agent 2 blob `af2b3be5fdee31b857c56fdf09ef54ffd02ed94e` sudah memakai exact-GURU helper. Namun QuestionPolicy pada branch Agent 3 masih blob `81e3cb6c82f1a2f5e15deacf8b8ea50f8a494566` dan menggunakan `hasRole('GURU')`. Divergensi ini harus direkonsiliasi oleh owner saat integrasi; Agent 3 tidak mengubah policy Agent 2.

### 1.4 Issue dan approval

- Issue #8: OPEN, 7 komentar.
- Issue #9: OPEN, 2 komentar.
- Issue #10: OPEN, 15 komentar.
- Komentar yang diperiksa tercatat dari akun koordinator `zane0184`; belum ada approval owner lintas-agent terpisah yang dapat diverifikasi.
- Kontrak import XLSX/DOCX tetap proposal; belum ada approval field-level DTO, endpoint, error, security, media, idempotency dan lifecycle.
- Kontrak satu Exam–satu ExamSession/room monitoring tetap menunggu approval owner.

### 1.5 CI exact-SHA

Run #41:
- Run ID `45009039557`, source SHA `88b5c1354971bfb7b03d715014b40b9026f1fbed`.
- Job `teacher-portal-tests`, ID `137167012186`.
- Job completed/failure.
- Steps kosong; logs URL null; download log menghasilkan BlobNotFound; artifacts kosong.
- PHPUnit execution **UNKNOWN**. Tidak ada bukti bahwa PHPUnit gagal atau berhasil.

Combined commit statuses yang diperiksa:
- SHA `88b5c135...`: kosong.
- SHA `4dc407d...`: kosong.
- SHA terbaru `6334dc9...`: kosong.

Helper workflow-runs yang tersedia hanya mengembalikan run PR-triggered; hasil kosong bukan bukti bahwa push run tidak pernah ada. Tidak ada run exact-SHA baru yang dapat diverifikasi melalui konektor ini. Workflow tidak diubah dan rerun tidak dilakukan tanpa izin eksplisit koordinator.

## 2. DESAIN

Test lifecycle diperkuat agar:
1. GET halaman QuestionBank dan ExamManagement memakai route asli dan menjalankan auth/tenant/license middleware.
2. Snapshot komponen diambil dari HTML halaman tersebut.
3. Update dilakukan melalui HTTP POST ke URI Livewire update yang dikonfigurasi aplikasi, dengan header `X-Livewire` dan payload snapshot/calls/updates.
4. TenantContext dikosongkan sebelum update untuk mensimulasikan request baru.
5. Component boundary harus membangun context dari authenticated user.
6. Setelah tenant user berubah di database, update komponen QuestionBank dan ExamManagement harus ditolak.
7. Snapshot database sebelum/sesudah denial harus sama.

Tidak ada perubahan pada middleware produksi. Test membuktikan component boundary pada real HTTP update; test ini tidak mengklaim custom IdentifyTenant otomatis menjadi persistent middleware Livewire.

## 3. IMPLEMENTASI

File test yang diubah:
- `backend-laravel/tests/Feature/TeacherPortalFoundationTest.php`
- Source blob sebelum patch: `0b273618677c791d82645fa373be57aa2d961643`.
- Source blob sesudah patch: `4f1bb48b390b03b2602df6348fd3b9788581e6c9`.
- Commit: `6334dc9abd07a1441c8c02f3f7a6cc5c452ead36`.
- Jumlah test methods: 22.

Backup byte-exact:
- `docs/backup/web-guru-exact-role-20261122/TeacherPortalFoundationTest.php.before-real-http-livewire-20261122`.
- Backup blob SHA `0b273618677c791d82645fa373be57aa2d961643`, identik dengan source sebelum patch.
- Backup commit: `45f2048c085bafae5247109d16de190648a71491`.
- Addendum manifest: `docs/backup/web-guru-exact-role-20261122/REAL_HTTP_LIVEWIRE_BACKUP_ADDENDUM.md`, blob `e6a462bca7ace8cabccd7e46960377d5c46c15a1`.

Manifest backup lama berisi 34 snapshot; seluruh 34 backup path telah di-fetch ulang dan SHA cocok 34/34. Backup tambahan test ini diverifikasi terpisah byte-exact.

Tidak ada file runtime, policy, middleware, controller, service, migration, schema, Composer, workflow, database, atau source Agent 1/2/4 yang diubah.

## 4. PENGUJIAN

| Pemeriksaan | Hasil |
|---|---|
| Branch/commit/blob refresh | PASS — read-only audit |
| Backup manifest lama | PASS — 34/34 blob match |
| Backup test terbaru | PASS — source/backup blob identik |
| Test count | 22 — statis |
| Diff scope | Satu file test di commit implementasi |
| PHP lint | NOT RUN |
| PHPUnit | NOT RUN |
| MySQL 8/InnoDB disposable | NOT RUN |
| CI exact-SHA terbaru | UNKNOWN / belum ada bukti |
| Browser QA | BLOCKED |
| Database produksi | NOT TOUCHED |

Tidak ada klaim PHP lint atau PHPUnit PASS. Tidak ada pengujian runtime yang dijalankan dalam sesi ini.

## 5. CHECKPOINT

**P0 OPEN — TENANT/SECURITY TEST INCOMPLETE.**

Selesai:
- Refresh branch, checkpoint, issue dan source aktual.
- Audit Livewire test harness versi terkunci.
- Verifikasi backup lama 34/34 dan backup test baru.
- Memperkuat HTTP Livewire update test pada file test Agent 3.
- Mempertahankan 22 test methods dan enam regresi post-mount.

Tertunda:
1. Izin eksplisit koordinator/repository owner untuk menjalankan workflow pada exact SHA terbaru `6334dc9...`, atau bukti dari UI/API GitHub pemilik.
2. PHPUnit, PHP lint, route registration, disposable MySQL/InnoDB, dan cleanup harus terbukti pada exact SHA.
3. Agent 2 menutup exact-role ExamPolicy/API/service, termasuk SISWA exact-one dan penghapusan seluruh capability KAMAD.
4. Rekonsiliasi QuestionPolicy Agent 3 dengan source exact-role Agent 2 pada tahap integrasi resmi.
5. Browser QA setelah CI dan backend security gate lulus.
6. Import UI XLSX/DOCX tetap desain saja sampai approval eksplisit Agent 2.
7. Approval lintas-agent Issue #10 tetap pending.

Tidak ada merge, rebase, cherry-pick, deployment, migration, atau database mutation. Tidak ada pekerjaan Android/Super Admin yang dimulai.
