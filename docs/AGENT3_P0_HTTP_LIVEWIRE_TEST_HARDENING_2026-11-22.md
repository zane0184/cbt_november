# Laporan Agent 3 — P0 HTTP Livewire Lifecycle & Tenant Boundary Hardening

Tanggal: 22 November 2026  
Repository: `zane0184/cbt_november`  
Branch: `work/web-guru-foundation-20261122`  
HEAD sebelum laporan/checkpoint: `cf4da2c371d4f942bfb9cc8b4eb613431988b531`  
Test source commit terakhir: `4dc407d091fc6cf786260052cfd86d836edc5908`  
Test blob terakhir: `0b273618677c791d82645fa373be57aa2d961643`

Status: **P0 OPEN — TENANT/SECURITY TEST INCOMPLETE**

## 1. AUDIT

### 1.1 Branch dan perubahan lintas-agent

HEAD remote terverifikasi melalui GitHub refs:
| Owner | Branch | HEAD saat audit | Perubahan sejak checkpoint sebelumnya |
|---|---|---|---|
| Agent 1 | `work/stage4b-room-session-20261122` | `51571ca54ec922466e6230b4e8137a85336bc024` | 10 commit sejak `d253279...`; commit terakhir hanya manifest dokumentasi. Source Stage 4B yang dirujuk tetap baseline `d253279...`. |
| Agent 2 | `work/stage4d-account-lifecycle-20261122` | `462cadc96a95d091bd89819dbc106b4fbd67e1cd` | 8 commit sejak `a1dc570...`; commit terakhir checkpoint dokumentasi Web-only. Source runtime yang dinyatakan Agent 2 tetap `74bf7178f46673e9be90523671490c0749034acd`. |
| Agent 3 | `work/web-guru-foundation-20261122` | `cf4da2c371d4f942bfb9cc8b4eb613431988b531` | 7 commit sejak checkpoint `fa658202...`, termasuk backup, hardening test dan manifest. |
| Dokumentasi Agent 4 | `work/agent4-web-siswa-audit-20261122` | `16c7d324ef9d7103bb21f0c5528c3c888ba19568` | Dokumentasi saja; bukan branch implementasi. |
| Kandidat Agent 4 | `work/import-guru-siswa-implementation-20261122` | `f38ceec7b0653fcf3363eec40f3d4577c323cca8` | Tidak berubah pada refresh ini; owner belum mengonfirmasi branch resmi. |

Commit terakhir Agent 1 `51571ca...` hanya menambahkan manifest keputusan. Commit terakhir Agent 2 `462cadc...` hanya menambahkan checkpoint Web-only. Tidak ada perubahan source runtime Agent 1/2 yang disimpulkan dari commit dokumentasi tersebut.

Branch kandidat Agent 4 tetap belum dikonfirmasi owner. Branch dokumentasi Agent 4 menyatakan Web Siswa **NOT READY FOR OPERATIONAL EXAM**, dan mengidentifikasi F-01 admission, F-02 media, IndexedDB/offline recovery, Device Lease/Fencing, answer receipt/conflict, serta submit barrier sebagai pekerjaan yang belum terbukti selesai.

### 1.2 Boundary Web Guru Agent 3

| Path | Blob SHA | Temuan |
|---|---|---|
| `backend-laravel/app/Livewire/Admin/QuestionBank.php` | `219efc2db8f492452ad95da35ae22eb4849d1e66` | `boot/mount` memanggil exact Guru boundary, permission, profile dan active assignment. |
| `backend-laravel/app/Livewire/Admin/ExamManagement.php` | `d45293ea8cfd01bf6eaeea17ced0579a8d8bc4cc` | Pola boundary sama untuk exam.manage dan assignment. |
| `backend-laravel/app/Support/Security/TeacherPortalAccess.php` | `8c60fb6db17512d6bbf8845027f69841172e2f60` | Tepat satu role GURU, user persisted/non-soft-deleted, tenant context, tepat satu TeacherProfile tenant-local. |
| `backend-laravel/app/Support/Security/TeacherAccess.php` | `766289c5163b6cf367d511c0bf35207ccbc97684` | Assignment tenant-local, ACTIVE dan date window. |
| `backend-laravel/app/Http/Middleware/IdentifyTenant.php` | `376a4b2771c3d9a6b128ad847c150d47ac2977db` | Mengosongkan context lama dan membangun kembali dari tenant authenticated user; header override diabaikan. |
| `backend-laravel/bootstrap/app.php` | `d1137fce9e10bc490a044f950170008d55cab914` | Alias middleware tenant mengarah ke IdentifyTenant. |
| `backend-laravel/routes/web.php` | `0cf0c73d28a1f8ab948e7594b6a588c1ca864d6a` | Route /admin/questions dan /admin/exams berada di auth/tenant/license group; komponen sendiri tetap menegakkan Guru boundary. |

Definisi aktif mengikuti schema aktual: User belum soft-deleted, TeacherProfile belum soft-deleted, assignment ACTIVE dan tanggal berlaku. Tidak ada kolom status/is_active baru yang ditambahkan.

### 1.3 Backend Agent 2 — gap exact-role lintas domain

Source Agent 2 yang diperiksa:
- `ExamPolicy.php` blob `c05d96ef932fdc81b67336240af4e1726864c3d6`.
- `QuestionPolicy.php` blob `af2b3be5fdee31b857c56fdf09ef54ffd02ed94e`.

QuestionPolicy pada branch Agent 2 menggunakan helper `hasExactGuruPermission()`. Namun QuestionPolicy pada branch Agent 3 sendiri masih blob lama `81e3cb6c82f1a2f5e15deacf8b8ea50f8a494566` dengan `hasRole('GURU')`. Ini perbedaan branch yang harus direkonsiliasi saat integrasi; tidak diubah oleh Agent 3.

ExamPolicy Agent 2 masih menggunakan `hasRole()` biasa pada `viewAny`, `view`, `preview`, `create`, `update`, `delete`, `manageQuestions`, `manageParticipants`, `generateToken`, `lock`, `close`, dan `publishResults`. `startAttempt` memeriksa keberadaan role SISWA, bukan tepat satu role SISWA. `viewAny/view/monitor` masih memiliki jalur KAMAD, padahal KAMAD bukan portal CBT. Ini ownership Agent 2 dan tetap blocker; Agent 3 tidak mengubahnya.

Agent 2 checkpoint Web-only menyatakan status **BLOCKED — EXACT-ROLE/SECURITY DESIGN PENDING**, source runtime baseline `74bf7178...`, dan implementasi runtime gated. CI Agent 2 Run #388 dan #389 dilaporkan BLOCKED — RUNNER (runner_id 0, tanpa steps), bukan PHPUnit PASS.

### 1.4 Room/session dan Web Siswa

Agent 1 mempertahankan desain satu Exam = satu ExamSession operasional dengan banyak room, tetapi approval lintas-agent dan acceptance tests masih pending. Migration Stage 4B yang diaudit sebelumnya belum membuktikan operational guard tunggal dan scheduler belum membuktikan pemeriksaan Attempt IN_PROGRESS sebelum menutup session/assignment.

Kandidat Agent 4 `ExamAttemptController.php` blob `808702092c94b386657fe027f60758a625d5bb93` pada method `show()` memanggil authorize view/monitor pada Exam tetapi tidak memanggil room-scope helper. Dokumentasi Agent 4 juga menyatakan Web Siswa belum siap ujian operasional. Tidak ada source Agent 1/2/4 yang diubah oleh Agent 3.

### 1.5 Issue dan approval

- Issue #8 — OPEN; 7 komentar. Proposal DTO/batch Agent 2 masih memerlukan keputusan eksplisit field/type/endpoint/error/security/lifecycle.
- Issue #9 — OPEN; 2 komentar. DOCX adapter ke DTO/lifecycle bersama masih proposal; parser dependency belum disetujui.
- Issue #10 — OPEN; 11 komentar setelah refresh. Tidak ada approval owner lintas-agent yang dapat diverifikasi. Komentar #7078152288 menegaskan komentar koordinator bukan approval Agent 3/4 yang terpisah; ownership Agent 4 belum dikonfirmasi.
- Koordinator menambahkan keputusan distribusi pada komentar #7078261415: satu instalasi per madrasah, database akademik/storage/credential/config/backup sendiri, tanpa shared academic database. tenant_id tetap defense-in-depth di dalam instalasi. Ini arah topologi yang harus dikonfirmasi Agent 2 pada resolver/installation identity; tidak ada source diubah.

## 2. DESAIN — skenario test hardening

1. **HTTP mount**: GET `/admin/questions` dan `/admin/exams` sebagai Guru harus melewati auth, tenant, license dan mount komponen; tenant context setelah request harus sama dengan tenant authenticated user.
2. **Livewire update lifecycle**: setelah komponen di-mount, context dipaksa ke Tenant B sebelum update; request update harus membangun ulang context dari authenticated user Tenant A, bukan mempercayai context/header klien.
3. **Persisted identity change**: setelah mount, tenant_id User di database diubah ke Tenant B sementara sesi masih memegang identitas Tenant A; request update berikutnya harus ditolak oleh persisted identity check.
4. **Foreign tenant fixture**: simpan Question, QuestionVersion, QuestionOption (termasuk correct option), Assessment dan Exam pada Tenant B; assert keberadaan seluruh fixture sebelum request Guru A.
5. **Anti-enumeration/no leakage**: openEditModal untuk ID foreign harus gagal dengan ModelNotFoundException model yang tepat; komponen awal tidak boleh menampilkan stem/kunci atau Exam title/instructions foreign.
6. **No mutation**: snapshot raw database untuk questions, question_versions, question_options, assessments dan exams Tenant A dibandingkan sebelum/sesudah setiap post-mount denial.
7. **Exact role**: ADMIN_MADRASAH, SISWA dan KAMAD diberi permission yang sama dengan komponen Guru tetapi tetap ditolak; GURU + role tambahan juga ditolak.
8. Enam regresi post-mount sebelumnya dipertahankan: permission revoke, assignment inactive, TeacherProfile soft-delete, User soft-delete, TenantContext mismatch, role GURU revoke.

API yang dipakai tetap mekanisme test Livewire yang sudah ada pada suite; tidak menambah API Livewire atau mengubah middleware.

## 3. IMPLEMENTASI

Satu file test milik Agent 3 diubah:
- `backend-laravel/tests/Feature/TeacherPortalFoundationTest.php`
- Test source commit: `4dc407d091fc6cf786260052cfd86d836edc5908`
- Blob: `0b273618677c791d82645fa373be57aa2d961643`
- Method: 22 (sebelumnya 21).
- Commit pertama hardening HTTP/foreign tenant: `d07ac15e5b39146a5811c2371785949541797ae1`.
- Commit berikutnya menambahkan exact-role KAMAD dan snapshot no-mutation: `4dc407d091fc6cf786260052cfd86d836edc5908`.

Backup:
- Manifest terbaru: `docs/backup/web-guru-exact-role-20261122/MANIFEST.md`, blob `93a9f2b3edd5053eb53444354f01236c4314b722`.
- 32/32 path backup pada manifest di-fetch ulang; semua blob SHA cocok dengan manifest.
- Backup baru sebelum patch: `TeacherPortalFoundationTest.php.before-http-lifecycle-tenant-hardening` (blob sama dengan source awal `747989...`) dan `TeacherPortalFoundationTest.php.before-post-http-lifecycle-exact-role` (blob `3ef6e350...`).
- Checkpoint sebelum update: `CHECKPOINT_AGENT3_WEB_ONLY_NEXT_GATE.md.before-p0-test-hardening` (blob `ec171795...`).
- Backup manifest sebelum update: `MANIFEST.md.before-http-lifecycle-tenant-hardening` (blob `3fb900cb...`).
- Dua respons create_file pertama tidak mengembalikan commit SHA yang dapat dicatat; blob backup diverifikasi identik. Commit backup lain tercatat pada manifest.
- Tidak ada source komponen/helper/policy/controller/middleware/model, migration/schema, Composer, workflow, database, atau file Agent 1/2/4 yang diubah.

Dokumen ini dan checkpoint merupakan dokumentasi baru/terbarui. Tidak ada merge, PR, rebase, cherry-pick, deployment, atau migration produksi.

## 4. PENGUJIAN

| Pemeriksaan | Status |
|---|---|
| Branch refs dan commit metadata | PASS — diverifikasi read-only |
| Source/blob komponen dan helper | PASS — blob dicatat |
| Backup manifest | PASS — 32/32 blob cocok |
| Test method count | PASS statis — 22 method |
| Test logic / fixture / assertion review | REVIEWED statis |
| PHP lint pada exact SHA terbaru | NOT RUN / NOT VERIFIED |
| PHPUnit pada exact SHA terbaru | NOT RUN / NOT VERIFIED |
| MySQL 8/InnoDB disposable pada exact SHA terbaru | NOT RUN / NOT VERIFIED |
| Browser QA | BLOCKED — belum ada CI/P0 backend pass dan environment browser/akun uji terverifikasi |
| Database produksi | NOT TOUCHED |

### CI Run #41 exact SHA lama

Run `45009039557`, source SHA `88b5c1354971bfb7b03d715014b40b9026f1fbed`, job `137167012186`:
- job status completed, conclusion failure;
- steps kosong;
- logs_url null; download log mengembalikan BlobNotFound;
- artifacts kosong;
- combined status kosong;
- runner ID/name, start/completion timestamps dan run attempt tidak tersedia melalui endpoint yang dapat diakses;
- PHPUnit execution **UNKNOWN**. Tidak boleh disebut PHPUnit gagal atau lulus.

Run tersebut menguji SHA lama, bukan test source `4dc407d...` atau branch HEAD `cf4da2c...`. Combined status commit `4dc407d...` juga kosong. Endpoint workflow-runs konektor yang tersedia hanya menampilkan PR-triggered runs, bukan push runs. Web API langsung tidak dapat diakses melalui alat ini. Workflow YAML memiliki trigger push dan workflow_dispatch, tetapi tidak ada aksi dispatch yang tersedia pada konektor. Tidak ada rerun dilakukan karena instruksi mensyaratkan izin koordinator untuk menjalankan ulang; belum ada izin rerun eksplisit.

## 5. CHECKPOINT / NEXT GATE

**P0 OPEN — TENANT/SECURITY TEST INCOMPLETE.**

Langkah berikut:
1. Koordinator/repository owner memberikan izin eksplisit untuk menjalankan workflow exact SHA terbaru atau memeriksa GitHub Actions runner/metadata melalui UI/API pemilik.
2. Verifikasi PHP lint, route registration, PHPUnit seluruhnya PASS, disposable MySQL 8/InnoDB dan cleanup pada exact SHA.
3. Agent 2 menutup exact-role ExamPolicy dan seluruh policy/API/service entrypoint, termasuk penolakan KAMAD dan multi-role SISWA/GURU; kirim source SHA dan regression.
4. Agent 2 mengonfirmasi tenant resolver untuk satu instalasi per madrasah dan installation identity; jangan mengubah source sebelum keputusan.
5. Agent 1/2/4 menyelesaikan approval canonical session, Attempt close/resume/recovery, room-scoped monitoring dan lock order.
6. Setelah gate backend dan CI, lakukan browser QA Web Guru pada environment non-produksi dengan URL, browser version, viewport, akun/role uji dan bukti tiap skenario.
7. Kontrak Import XLSX/DOCX tetap menunggu approval eksplisit Agent 2. Tidak ada parser/dependency/upload/persistence UI.
8. Jangan menyatakan Web Guru atau Web Siswa siap operasional/produksi.

Tidak ada klaim bahwa test telah berjalan atau lulus. Tidak ada browser QA, PHP lint, PHPUnit, build, migration, atau deployment yang diklaim selesai.
