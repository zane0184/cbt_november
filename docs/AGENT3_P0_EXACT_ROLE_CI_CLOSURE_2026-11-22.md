# AGENT 3 — P0 EXACT-ROLE CI CLOSURE
Tanggal: 22 November 2026
Repository: `zane0184/cbt_november`
Branch: `work/web-guru-foundation-20261122`
HEAD Agent 3 saat test fix dibuat: `88b5c1354971bfb7b03d715014b40b9026f1fbed`
Status: **P0 OPEN — EVIDENCE INCOMPLETE**

## AUDIT

### 1. HEAD dan perubahan sejak checkpoint
- Branch Agent 3 diverifikasi dengan compare: base `88b5c1354971bfb7b03d715014b40b9026f1fbed` terhadap branch menghasilkan `identical`, 0 ahead / 0 behind.
- HEAD saat refresh sebelum koreksi dua kegagalan CI: `c9d8f7e723be64467d759b921ebc3346fb4dfe46`.
- Perubahan setelah refresh ini hanya test file dan dokumentasi/backup. Tidak ada perubahan source komponen Agent 3.
- Test file terbaru memiliki blob SHA `747989ed491dc1ead67b8a990434837df47c8af1` pada commit `88b5c1354971bfb7b03d715014b40b9026f1fbed`.

### 2. Source aktual Agent 3 yang diperiksa
| File | Blob SHA | Temuan |
|---|---|---|
| `backend-laravel/app/Livewire/Admin/QuestionBank.php` | `219efc2db8f492452ad95da35ae22eb4849d1e66` | Boundary di boot/mount dan action/query diperiksa |
| `backend-laravel/app/Livewire/Admin/ExamManagement.php` | `d45293ea8cfd01bf6eaeea17ced0579a8d8bc4cc` | Boundary di boot/mount dan action/query diperiksa |
| `backend-laravel/app/Support/Security/TeacherPortalAccess.php` | `8c60fb6db17512d6bbf8845027f69841172e2f60` | Exactly-one role, persisted user, tenant context, satu profil Guru |
| `backend-laravel/app/Support/Security/TeacherAccess.php` | `766289c5163b6cf367d511c0bf35207ccbc97684` | Assignment tenant-scoped, status ACTIVE dan tanggal berlaku |
| `backend-laravel/tests/Feature/TeacherPortalFoundationTest.php` | `747989ed491dc1ead67b8a990434837df47c8af1` | 21 test method |
| `backend-laravel/app/Models/User.php` | `74dd080f703d7ef3256b406176e53dad5786a937` | Model tidak memakai trait SoftDeletes dan tidak mendefinisikan status aktif |
| `backend-laravel/app/Models/TeacherProfile.php` | `437ed33366007c26a071a08fb0b8a9e165dbdbc6` | SoftDeletes; tidak ada atribut status aktif |
| `backend-laravel/app/Models/Question.php` | `737b7cefa945f448aeea3a236c4015fab539e4fb` | TenantScoped + SoftDeletes |
| `backend-laravel/app/Models/Exam.php` | `b679bf6d6a0f68074ef1a000c0345f0248f696f3` | TenantScoped + SoftDeletes |
| `backend-laravel/app/Traits/TenantScoped.php` | `cf192a8ccb6ca530122cb105ed3086adb09a3c5a` | Fail-closed saat tenant context tidak tersedia |

### 3. Boundary exact-role pada komponen

**QuestionBank**
- `boot()` dan `mount()` memanggil `assertCanAccess()`.
- `assertCanAccess()` menghapus cache relation `roles`, memeriksa permission `question.view`, memanggil `TeacherPortalAccess::profile()`, lalu mewajibkan assignment mata pelajaran aktif.
- `openEditModal()` mengambil Question melalui model tenant-scoped, menjalankan Gate dan assignment check sebelum mengisi state form dengan stem, opsi, dan kunci.
- `saveQuestion()`, `publishQuestion()`, `confirmDeleteQuestion()`, dan `deleteQuestion()` memakai Gate/policy dan pemeriksaan assignment. Mutasi persistent dilakukan dalam transaksi; publish/delete/update memakai row lock pada Question.
- `render()` memuat konten setelah boot authorization, dengan pembatasan owner PERSONAL, owner_user_id, subject assignment, dan tenant scope model.
- `addMatchingPair()`, `removeMatchingPair()`, dan hook perubahan form hanya mengubah state form; tidak menulis data soal ke database.

**ExamManagement**
- `boot()` dan `mount()` memanggil `assertCanAccess()`.
- `assertCanAccess()` memeriksa permission `exam.manage`, exact-role melalui `TeacherPortalAccess::profile()`, dan assignment aktif.
- `openEditModal()` memakai tenant filter dan Gate sebelum mengisi state ujian/snapshot.
- `saveExam()` memvalidasi subject, semester, assignment mapel/rombel, peserta, dan QuestionVersion tenant-scoped; transaksi menyimpan Exam/Assessment/snapshot/peserta serta audit.
- `generateToken()` dan `lockExam()` melakukan Gate, row lock, dan re-authorize sebelum rotasi token/publish.
- `render()` membatasi Exam melalui `TeacherAccess::constrainExamQuery()`, subject/rombel assignment, dan available Question tenant-scoped.
- Komponen ini tidak memiliki action `closeExam` atau UI monitoring Proktor pada daftar method yang diperiksa; lifecycle close/monitor berada pada permukaan lain dan tidak boleh dianggap telah diuji oleh komponen ini.
- `toggleQuestionSelection()` hanya mengubah state pemilihan form, bukan persistence.

### 4. Identitas aktif pada schema
- Migration `0001_01_01_000000_create_users_table.php` blob `cd4b3c6931fde3859394e035058f51dc290e8c9d` hanya menyediakan `deleted_at` melalui softDeletes; tidak ada kolom `status`/`is_active`.
- `TeacherPortalAccess` membuktikan User masih tersimpan di tenant dan `deleted_at IS NULL`. Karena schema tidak memiliki status aktif tersendiri, makna aktif yang dapat dibuktikan saat ini adalah belum soft-deleted.
- `TeacherProfile` memakai SoftDeletes dan query helper tidak mengikutkan profil terhapus; tidak ada kolom status aktif tersendiri pada model yang diperiksa.
- `TeacherSubjectAssignment` memiliki status `ACTIVE` dan scope tanggal mulai/akhir.
- Jangan menambah field status atau migration dari Agent 3; perlu keputusan Agent 2 jika madrasah memerlukan status penonaktifan eksplisit.

### 5. Negative test yang tersedia
Test file berisi 21 method. Kasus yang diuji sebelum tahap ini meliputi:
- GURU tunggal yang sah;
- ADMIN_MADRASAH saja dan SISWA saja;
- GURU+SISWA dan ADMIN+GURU;
- permission hilang;
- tanpa TeacherProfile;
- tanpa assignment aktif;
- tenant context mismatch;
- manipulasi ID Question/Exam tenant lain;
- role berubah setelah mount/hydration/direct action;
- soft-deleted User pada portal Guru;
- assignment kedaluwarsa.

Enam test method baru pada commit tahap ini:
1. Permission `question.view`/`exam.manage` dicabut setelah kedua komponen di-mount; direct action harus 403.
2. Assignment diubah menjadi INACTIVE setelah mount; hydration/direct action harus ditolak.
3. TeacherProfile di-soft-delete setelah mount; direct action harus ditolak.
4. User di-soft-delete setelah mount; direct action harus ditolak.
5. TenantContext diubah setelah mount; hydration/direct action harus ditolak.
6. Role GURU dicabut setelah mount; direct action harus ditolak.

### 6. Temuan policy Agent 2 — read-only
Source diperiksa pada branch `work/stage4d-account-lifecycle-20261122`:
- `QuestionPolicy.php` blob `af2b3be5fdee31b857c56fdf09ef54ffd02ed94e` memakai `hasExactGuruPermission()`, memuat role/permission dari database, dan mensyaratkan tepat satu role GURU.
- `ExamPolicy.php` blob `c05d96ef932fdc81b67336240af4e1726864c3d6` belum konsisten:
  - `viewAny`, `view`, `preview`, `create`, `update`, `delete`, `manageQuestions`, `manageParticipants`, `generateToken`, `lock`, `close`, dan `publishResults` memakai `hasRole('GURU')`/penolakan ADMIN, bukan pemeriksaan exactly-one role.
  - `startAttempt` mensyaratkan `hasRole('SISWA')`; akun GURU+SISWA dapat masuk jalur peserta bila eligibility lain terpenuhi.
  - `monitor` sudah memeriksa tepat satu role GURU.
- Ini adalah ownership Agent 2. Agent 3 tidak mengubah policy/controller/model/service Agent 2.
- Dampak: Livewire Guru sudah menolak multi-role, tetapi boundary policy/API Exam belum dapat dinyatakan konsisten untuk seluruh platform. Ini blocker P0 lintas portal yang harus ditangani Agent 2.

### 7. Issue dan kontrak lintas agent
- Issue #8 tetap OPEN, 6 komentar. Agent 2 telah mengusulkan kontrak import: adapter XLSX/DOCX terpisah → satu `QuestionDraftDTO` → reuse `ImportBatch/ImportBatchRow` dan satu lifecycle staging. Ini **usulan**, bukan approval implementasi.
- Issue #9 tetap OPEN, 1 komentar Agent 2: DOCX adalah adapter kedua menuju `QuestionDraftDTO` dan lifecycle batch yang sama; tidak ada dependency/parser/schema kedua yang disetujui.
- Issue #10 tetap OPEN, 7 komentar. Agent 2 berstatus NEEDS DESIGN / COORDINATION; Agent 1 menambahkan rekonsiliasi Stage 4B. Approval Agent 2/3/4 tetap PENDING.
- Branch Agent 2 pada refresh: HEAD dokumentasi `32d383eb1564cdbcf084b8677fe8b750a4b45a11`, source runtime baseline `74bf7178f46673e9be90523671490c0749034acd`. Tidak ada source Agent 2 yang disalin atau diubah.

## DESAIN

### 1. Boundary Portal Guru
Untuk kedua komponen, urutan akses adalah:
1. User terautentikasi dengan tenant_id.
2. Role dibaca ulang dari database dan tepat satu role GURU.
3. User harus masih tersimpan pada tenant yang sama dan belum soft-deleted.
4. TenantContext yang sudah terisi harus sama dengan tenant User; jika kosong, helper membangunnya hanya dari tenant User yang terautentikasi.
5. Tepat satu TeacherProfile tenant-lokal yang belum soft-deleted.
6. Permission komponen (`question.view` atau `exam.manage`).
7. Sedikitnya satu assignment mata pelajaran berstatus ACTIVE dan berlaku pada tanggal saat ini.
8. Gate/policy dan tenant/assignment scope tetap diterapkan pada setiap action persistent.

### 2. Hydration dan direct action
Livewire `boot()` berjalan pada request komponen dan mengulang pemeriksaan pada hydration/action. Test harus memodifikasi data role/permission/profile/assignment/User/TenantContext setelah instance awal dibuat, kemudian memanggil request berikutnya. Enam test baru menutup skenario post-mount yang sebelumnya belum dibuktikan.

### 3. Kontrak backend yang masih diperlukan
- Agent 2 harus menyelaraskan exact-role pada seluruh `ExamPolicy` method, termasuk pemisahan jalur Guru dan Siswa.
- Keputusan apakah soft-delete sudah cukup untuk menandai akun/profile nonaktif atau perlu status eksplisit harus berasal dari Agent 2.
- Tidak boleh membuat helper exact-role kedua di Agent 3.
- Identitas Kamad, branding login, kop PDF final, snapshot laporan, dan import dinamis tetap menunggu kontrak Agent 2.

## IMPLEMENTASI

### 1. Perubahan source pada tahap ini
Hanya file test yang diubah:
- `backend-laravel/tests/Feature/TeacherPortalFoundationTest.php`
- Blob SHA sebelum: `a0e9f03412c64e08e54922c6d5b8b49911915586`
- Blob SHA sesudah: `747989ed491dc1ead67b8a990434837df47c8af1`
- Commit: `88b5c1354971bfb7b03d715014b40b9026f1fbed`

Tidak ada perubahan pada `QuestionBank.php`, `ExamManagement.php`, helper security, policy, API controller, model, migration, schema, Composer, CBT Engine, Android, atau database.

### 2. Backup
Backup dibuat sebelum test file dan manifest diubah:
- Source test backup: `docs/backup/web-guru-exact-role-20261122/TeacherPortalFoundationTest.php.before-post-mount-regressions`
- Blob SHA backup: `a0e9f03412c64e08e54922c6d5b8b49911915586` — MATCH.
- Manifest backup: `docs/backup/web-guru-exact-role-20261122/MANIFEST.md.before-post-mount-regressions`
- Blob SHA backup: `a8260bb6ab8c4a6834fc2a15938183605f35c69b` — MATCH.
- Manifest terbaru: `docs/backup/web-guru-exact-role-20261122/MANIFEST.md`, blob SHA `c8c30ae7311d55adaed2f2058c3f85c421c61ca8`.
- Manifest terbaru memuat 28 path backup. Seluruhnya diverifikasi ulang pada akhir tahap: **28/28 MATCH**.

### 3. Koordinasi
Temuan exact-role Agent 2 dan koreksi dua kegagalan CI dikirim sebagai komentar Issue #8 (comment ID `7077352886`). Tidak ada source Agent 2 yang diubah.

## PENGUJIAN

| Item | Status | Bukti |
|---|---|---|
| HEAD branch saat test fix | PASS | Compare `88b5c135...` terhadap branch = identical |
| Source blob aktual | PASS | Blob SHA setiap file dicatat pada bagian AUDIT |
| Backup | PASS | 28/28 blob SHA MATCH |
| Jumlah test method | PASS (statis) | 21 method ditemukan pada test file; bukan bukti test dijalankan |
| PHPUnit pada SHA `88b5c135...` | NOT VERIFIED | Combined status kosong; belum ada workflow run exact SHA yang dapat diakses |
| PHP lint pada SHA HEAD | NOT VERIFIED | Tidak dijalankan lokal; CI yang tersedia menggunakan SHA lama |
| Browser/E2E | NOT RUN | Tidak ada browser QA aktif dan akun uji |
| Database produksi | NOT TOUCHED | Tidak ada akses/mutasi/migration produksi |

### CI lama — tidak memenuhi exact-SHA gate
- Workflow run: `44995889398`
- Job: `teacher-portal-tests`, job ID `137126954522`
- Kesimpulan job: SUCCESS.
- Log checkout eksplisit menunjukkan SHA: `a33867928711b456d2414a675bef1a929861dee8`.
- Environment: PHP 8.3, Composer v2, disposable MySQL 8.
- Step PHP lint dan route registration: SUCCESS.
- PHPUnit: **16 passed, 69 assertions**.
- InnoDB: 80 base tables, semuanya InnoDB.
- Cleanup database/user disposable: SUCCESS.
- Artifact: tidak ada.
- Run ini bukan pada `834a59f...` dan bukan pada HEAD `c9d8f7e...`; karena source/test berubah setelah SHA `a338679...`, hasil lama tidak dapat menutup P0 sekarang.

### CI HEAD saat ini
- Commit `88b5c135...` mengubah path `backend-laravel/tests/Feature/TeacherPortalFoundationTest.php`, yang cocok dengan trigger `push` workflow `.github/workflows/web-guru-foundation-ci.yml`.
- Combined commit status: kosong.
- `fetch_commit_workflow_runs` tidak mengembalikan run untuk SHA ini; fungsi konektor tersebut hanya menampilkan run yang dipicu pull request.
- Tidak tersedia action GitHub connector untuk menjalankan `workflow_dispatch` atau menelusuri seluruh push-triggered workflow run.
- Commit test fix sudah dipush melalui branch yang trigger-nya dikonfigurasi, tetapi hasil run aktual belum dapat dibuktikan. Tidak ada klaim runner gagal karena tidak ada bukti kegagalan runner.

## CHECKPOINT

### Status
**P0 OPEN — EVIDENCE INCOMPLETE.**

Alasan:
1. Test post-mount terbaru dan dua koreksi ekspektasi CI belum terbukti berjalan pada disposable MySQL 8/InnoDB dengan SHA `88b5c135...`.
2. Agent 2 `ExamPolicy` masih belum menerapkan exact-role konsisten pada jalur Guru/Siswa.
3. Tidak ada bukti browser/E2E pada environment aktif.

### Selesai
- Refresh branch dan source aktual.
- Audit seluruh action/query kedua komponen.
- Audit helper exact-role dan assignment.
- Enam regresi post-mount ditambahkan.
- Backup 28/28 diverifikasi.
- Issue #8/#9/#10 dan policy Agent 2 diperiksa.
- Temuan policy Agent 2 dikirim untuk koordinasi.

### Tertunda / blocker
1. Verifikasi GitHub Actions pada SHA `88b5c1354971bfb7b03d715014b40b9026f1fbed`, termasuk checkout SHA, PHP lint, PHPUnit, MySQL 8/InnoDB, dan log.
2. Agent 2 meninjau serta memperbaiki exact-role pada seluruh `ExamPolicy` dan menambahkan regresi multi-role API/policy.
3. Agent 2 menetapkan apakah soft-delete cukup sebagai status aktif atau diperlukan status eksplisit.
4. Setelah P0 terbukti, lakukan browser QA dan uji integrasi pada akun uji non-produksi.

### Guardrails
- Tidak ada merge, PR, rebase, cherry-pick, deployment, migration produksi, atau perubahan database.
- Tidak ada perubahan source Agent 1/2/4.
- Tidak melanjutkan branding dinamis, PDF final, atau import XLSX/DOCX.
- Setelah laporan ini, berhenti pada P0 sampai bukti CI exact-SHA dan review Agent 2 tersedia.


## ADDENDUM — review Agent 2 dan koreksi kegagalan CI

### Bukti kegagalan CI pada source sebelumnya
- Run: `45003191589`; job: `137148981065`; SHA checkout `834a59f11f9b548c8d7f0fcea68c15cf4bbe501f`.
- Environment: PHP 8.3 dan disposable MySQL 8.0.46.
- PHP lint dan route registration: PASS.
- PHPUnit: **18 passed, 2 failed, 86 assertions**.
- Migrated-table InnoDB check dilewati karena PHPUnit gagal; cleanup disposable DB berhasil.

### Root cause dan koreksi test
1. Tenant context mismatch:
   - Test lama mengubah context ke Tenant B lalu mengirim Livewire HTTP request dan mengharapkan 403.
   - `IdentifyTenant` mereset context pada setiap request dan mengambil tenant dari User terautentikasi; header override memang diabaikan. Karena itu request kembali ke tenant User yang sah dan menghasilkan 200.
   - Test dikoreksi: setelah komponen di-mount, context server-side diubah ke Tenant B dan `boot()` pada instance komponen yang sama dipanggil langsung. Boundary harus melempar HTTP 403. Test helper mismatch yang lama tetap ada.
   - Tidak ada perubahan middleware, helper, atau authorization source.

2. Manipulasi ID lintas tenant:
   - Tenant-scoped `Question::findOrFail()` menghasilkan `ModelNotFoundException`; Livewire test harness melempar exception sebelum assertion `assertNotFound()` dijalankan.
   - Test sekarang menangkap exception dan memverifikasi model yang tidak ditemukan adalah Question atau Exam yang diminta.
   - Ini mempertahankan denial dan tenant isolation; tidak mengubah query agar melonggarkan akses.

### Test fix
- File: `backend-laravel/tests/Feature/TeacherPortalFoundationTest.php`
- Blob sebelum: `0a48cede38639093e5bd53e5b99ae0526babd8c9`
- Blob sesudah: `747989ed491dc1ead67b8a990434837df47c8af1`
- Commit: `88b5c1354971bfb7b03d715014b40b9026f1fbed`
- Jumlah test method tetap 21; enam post-mount test tetap dipertahankan.
- Backup byte-exact tercatat dalam manifest; backup test sebelum koreksi: `TeacherPortalFoundationTest.php.before-ci-failure-fixes`, blob `0a48cede38639093e5bd53e5b99ae0526babd8c9`.

### Usulan kontrak import Agent 2 — belum disetujui
- XLSX dan DOCX memakai adapter berbeda, satu `QuestionDraftDTO`.
- Reuse `ImportBatch/ImportBatchRow` yang sudah ada; jangan membuat batch table/service kedua.
- Lifecycle: upload → security validation → extract → stage → preview/correction → revalidate → confirm → persist → audit.
- Tidak ada auto-publish. SHORT_ANSWER tetap BLOCKED sampai accepted answers masuk snapshot dan scoring; ESSAY manual review.
- Tidak ada dependency DOCX yang disetujui. Usulan ini belum menjadi izin implementasi; Issue #8/#9 tetap OPEN.

### Status koordinasi
- Issue #8: OPEN, 6 komentar.
- Issue #9: OPEN, 1 komentar Agent 2.
- Issue #10: OPEN, 7 komentar. Agent 2 NEEDS DESIGN / COORDINATION; approval Agent 2/3/4 PENDING.
- Laporan Agent 2 mengaudit 99 file PHP dan menyatakan exact-role belum konsisten pada Assessment/Exam/Attempt/Report policy, beberapa analytics, media/student endpoints, Admin service entrypoints, dan KAMAD monitoring. Agent 3 tidak mengubah source Agent 2.

### CI setelah koreksi
- Combined status SHA `88b5c135...`: kosong.
- Workflow run exact SHA belum dapat diakses melalui konektor; fungsi run yang tersedia tidak menampilkan push-triggered runs dan tidak tersedia action manual workflow_dispatch.
- Commit test fix sudah dikirim pada branch yang memiliki push trigger untuk `backend-laravel/**`; hasil run tetap NOT VERIFIED.
- Status P0 tetap OPEN sampai workflow membuktikan checkout SHA yang benar dan seluruh test lulus.
