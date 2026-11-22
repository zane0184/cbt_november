# AGENT 3 — P0 EXACT-ROLE CI CLOSURE
Tanggal: 22 November 2026
Repository: `zane0184/cbt_november`
Branch: `work/web-guru-foundation-20261122`
HEAD Agent 3 pada saat laporan dibuat: `c9d8f7e723be64467d759b921ebc3346fb4dfe46`
Status: **P0 OPEN — EVIDENCE INCOMPLETE**

## AUDIT

### 1. HEAD dan perubahan sejak checkpoint
- Branch Agent 3 diverifikasi dengan compare: base `c9d8f7e723be64467d759b921ebc3346fb4dfe46` terhadap branch menghasilkan `identical`, 0 ahead / 0 behind.
- HEAD sebelum pekerjaan lanjutan ini: `a955a439b1b3fa4c149f0153f283951d2bfb88d2`.
- Compare dari HEAD sebelumnya: 4 commit ahead, 0 behind. Empat commit pada tahap ini terdiri dari backup source/test, registrasi backup, dan test regresi tambahan. Tidak ada perubahan source komponen Agent 3 pada tahap ini.
- Commit test sebelumnya `834a59f11f9b548c8d7f0fcea68c15cf4bbe501f` diperluas; test file sekarang memiliki blob SHA `0a48cede38639093e5bd53e5b99ae0526babd8c9`.

### 2. Source aktual Agent 3 yang diperiksa
| File | Blob SHA | Temuan |
|---|---|---|
| `backend-laravel/app/Livewire/Admin/QuestionBank.php` | `219efc2db8f492452ad95da35ae22eb4849d1e66` | Boundary di boot/mount dan action/query diperiksa |
| `backend-laravel/app/Livewire/Admin/ExamManagement.php` | `d45293ea8cfd01bf6eaeea17ced0579a8d8bc4cc` | Boundary di boot/mount dan action/query diperiksa |
| `backend-laravel/app/Support/Security/TeacherPortalAccess.php` | `8c60fb6db17512d6bbf8845027f69841172e2f60` | Exactly-one role, persisted user, tenant context, satu profil Guru |
| `backend-laravel/app/Support/Security/TeacherAccess.php` | `766289c5163b6cf367d511c0bf35207ccbc97684` | Assignment tenant-scoped, status ACTIVE dan tanggal berlaku |
| `backend-laravel/tests/Feature/TeacherPortalFoundationTest.php` | `0a48cede38639093e5bd53e5b99ae0526babd8c9` | 21 test method |
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
- Issue #8 tetap OPEN, 4 komentar setelah komentar koordinasi Agent 3 #7077281338. Ada permintaan exact-role awal, update regresi, dan temuan Agent 3 bahwa ExamPolicy Agent 2 belum konsisten; belum ada persetujuan kontrak import XLSX/PDF.
- Issue #9 tetap OPEN, 0 komentar. Kontrak DOCX belum dijawab Agent 2.
- Issue #10 tetap OPEN, 4 komentar. Review Agent 2 menyatakan NEEDS DESIGN / COORDINATION; gap lock order, lifecycle session/assignment Proktor, relasi histori masih menunggu koordinasi. Tidak ada approval Agent 1/2/3/4.
- Branch Agent 2 dibanding ref SHA yang disebut pada issue #10 `808a7a917f1addbf8b8b67b4929dd1cbb6992cbd`: 13 commit ahead, 0 behind. File policy yang disebut di atas diambil dari branch ref Agent 2 saat audit, bukan disalin atau diubah.

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
- Blob SHA sesudah: `0a48cede38639093e5bd53e5b99ae0526babd8c9`
- Commit: `c9d8f7e723be64467d759b921ebc3346fb4dfe46`

Tidak ada perubahan pada `QuestionBank.php`, `ExamManagement.php`, helper security, policy, API controller, model, migration, schema, Composer, CBT Engine, Android, atau database.

### 2. Backup
Backup dibuat sebelum test file dan manifest diubah:
- Source test backup: `docs/backup/web-guru-exact-role-20261122/TeacherPortalFoundationTest.php.before-post-mount-regressions`
- Blob SHA backup: `a0e9f03412c64e08e54922c6d5b8b49911915586` — MATCH.
- Manifest backup: `docs/backup/web-guru-exact-role-20261122/MANIFEST.md.before-post-mount-regressions`
- Blob SHA backup: `a8260bb6ab8c4a6834fc2a15938183605f35c69b` — MATCH.
- Manifest terbaru: `docs/backup/web-guru-exact-role-20261122/MANIFEST.md`, blob SHA `c8c30ae7311d55adaed2f2058c3f85c421c61ca8`.
- Seluruh 18 path backup dalam manifest diverifikasi ulang: **18/18 MATCH**.

### 3. Koordinasi
Temuan exact-role Agent 2 pada `ExamPolicy` dikirim sebagai komentar koordinasi Issue #8 (comment ID `7077281338`). Tidak ada source Agent 2 yang diubah.

## PENGUJIAN

| Item | Status | Bukti |
|---|---|---|
| HEAD branch aktual | PASS | Compare `c9d8f7e...` terhadap branch = identical |
| Source blob aktual | PASS | Blob SHA setiap file dicatat pada bagian AUDIT |
| Backup | PASS | 18/18 blob SHA MATCH |
| Jumlah test method | PASS (statis) | 21 method ditemukan pada test file; bukan bukti test dijalankan |
| PHPUnit pada SHA HEAD `c9d8f7e...` | NOT VERIFIED | Combined status kosong; tidak ada workflow run SHA ini yang dapat diakses |
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
- Commit `c9d8f7e...` mengubah path `backend-laravel/tests/Feature/TeacherPortalFoundationTest.php`, yang cocok dengan trigger `push` workflow `.github/workflows/web-guru-foundation-ci.yml`.
- Combined commit status: kosong.
- `fetch_commit_workflow_runs` tidak mengembalikan run untuk SHA ini; fungsi konektor tersebut hanya menampilkan run yang dipicu pull request.
- Tidak tersedia action GitHub connector untuk menjalankan `workflow_dispatch` atau menelusuri seluruh push-triggered workflow run.
- Commit test sudah dipush melalui branch yang trigger-nya dikonfigurasi, tetapi hasil run aktual belum dapat dibuktikan. Tidak ada klaim runner gagal karena tidak ada bukti kegagalan runner.

## CHECKPOINT

### Status
**P0 OPEN — EVIDENCE INCOMPLETE.**

Alasan:
1. Test post-mount terbaru belum terbukti berjalan pada disposable MySQL 8/InnoDB dengan SHA HEAD.
2. Agent 2 `ExamPolicy` masih belum menerapkan exact-role konsisten pada jalur Guru/Siswa.
3. Tidak ada bukti browser/E2E pada environment aktif.

### Selesai
- Refresh branch dan source aktual.
- Audit seluruh action/query kedua komponen.
- Audit helper exact-role dan assignment.
- Enam regresi post-mount ditambahkan.
- Backup 18/18 diverifikasi.
- Issue #8/#9/#10 dan policy Agent 2 diperiksa.
- Temuan policy Agent 2 dikirim untuk koordinasi.

### Tertunda / blocker
1. Verifikasi GitHub Actions pada SHA `c9d8f7e723be64467d759b921ebc3346fb4dfe46`, termasuk checkout SHA, PHP lint, PHPUnit, MySQL 8/InnoDB, dan log.
2. Agent 2 meninjau serta memperbaiki exact-role pada seluruh `ExamPolicy` dan menambahkan regresi multi-role API/policy.
3. Agent 2 menetapkan apakah soft-delete cukup sebagai status aktif atau diperlukan status eksplisit.
4. Setelah P0 terbukti, lakukan browser QA dan uji integrasi pada akun uji non-produksi.

### Guardrails
- Tidak ada merge, PR, rebase, cherry-pick, deployment, migration produksi, atau perubahan database.
- Tidak ada perubahan source Agent 1/2/4.
- Tidak melanjutkan branding dinamis, PDF final, atau import XLSX/DOCX.
- Setelah laporan ini, berhenti pada P0 sampai bukti CI exact-SHA dan review Agent 2 tersedia.
