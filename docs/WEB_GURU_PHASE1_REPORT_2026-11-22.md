# LAPORAN PHASE 1 — AUDIT DAN HARDENING WEB GURU
Tanggal: 22 November 2026
Repository: `zane0184/cbt_november`
Branch kerja: `work/web-guru-phase1-audit-implementation-20261122`
Baseline branch Agent 3: `work/web-guru-foundation-20261122`
Baseline HEAD: `cf9a6ed871314b289505c9ef4cd4319ee7590ffb`
Source runtime terakhir baseline yang dilaporkan: `91d484b34b7e01fa6652138761ccbcab80370349`
HEAD branch kerja pada laporan: `a64caefb2ab683fb910c6fe4bf8ff3b14cc182dc`

Dokumen acuan: [Master Spesifikasi Web Guru](https://github.com/zane0184/cbt_november/blob/main/docs/MASTER_SPESIFIKASI_FITUR_WEB_GURU_2026-11-22.md). Keputusan model ujian serentak dan larangan ExamSession operasional mengikuti master spec serta klarifikasi terbaru pada Issue #10.

## 1. AUDIT

### 1.1 Refresh branch dan ownership
- Branch Agent 3 lama masih ada dan tidak diubah.
- Branch kerja baru dibuat dari baseline HEAD Agent 3 `cf9a6ed...`; tidak membuat perubahan ke `main`.
- Source UI Guru dimiliki Agent 3. File yang dipatch pada fase ini adalah komponen ExamManagement dan test workflow yang memang berada pada ownership UI Agent 3.
- `routes/web.php`, policy backend, Exam/Attempt/Answer service, migration/schema, Room/Proktor service Agent 1, dan source Web Siswa Agent 4 tidak diubah.
- Master spec Web Guru berada pada `main`; spesifikasi itu menjadi acuan produk. Source branch Agent 3 lama tidak memiliki salinan dokumen tersebut, tetapi isi dan tautannya telah dibaca dari `main`.

### 1.2 Matriks fitur saat audit
Status di bawah adalah hasil inspeksi source statis, bukan hasil runtime.

| Modul | Status | Bukti / gap utama |
|---|---|---|
| Login / boundary Guru | EXISTS, NOT RUNTIME VERIFIED | Route middleware `auth`, `tenant`, `license`, `EnsureTeacherPortalAccess`; helper memeriksa user persisted, tepat satu role GURU, tenant context, dan satu TeacherProfile. Exact-SHA PHPUnit belum berjalan. |
| Dashboard | EXISTS, PARTIAL | `TeacherDashboard`, `TeacherPortalDataService::dashboard()`, view `portal-dashboard`. Statistik mapel/rombel/siswa/assignment tersedia. Agenda, jumlah soal, status ujian terjadwal/berlangsung/selesai, essay pending, Proktor hari ini belum terbukti tersedia. |
| Penugasan Saya | EXISTS, PARTIAL | Data assignment dan riwayat status tampil pada `portal-learning`; belum menjadi menu/halaman terpisah dan filter/riwayat rinci belum diverifikasi. |
| Pembelajaran | EXISTS, PARTIAL | `TeacherLearning` memakai data assignment dan konteks kurikulum. Jadwal pembelajaran belum tersedia. |
| Kurikulum / CP-TP-ATP | PARTIAL / NEEDS DATA | Referensi tenant/global yang diregistrasi ditampilkan; provenance resmi CP belum tersedia. Pemetaan Panca Cinta rinci, Pembelajaran Mendalam, dan 8 DPL belum tersedia/terverifikasi. |
| Bank Soal | EXISTS, PARTIAL | Komponen QuestionBank terpasang pada route Guru, tetapi masih namespace legacy Admin. Media upload/preview, editor RTL eksplisit, formula editor, dan alur import XLSX/DOCX belum terhubung dengan kontrak backend final. SHORT_ANSWER tetap BLOCKED; ESSAY manual review. |
| Asesmen & Ujian Mode A | EXISTS, PARTIAL | ExamManagement route Guru, pemeriksaan assignment mapel/rombel, snapshot dan publish/token terlihat di source. Exact-SHA CI, seluruh role/IDOR negatif, serta end-to-end siswa belum terverifikasi. |
| Monitoring Mode A | BLOCKED / NOT EXPOSED | Menu masih disabled; route/service monitoring Guru yang siap dan teruji belum dibuktikan. |
| Proktor Mode B | BLOCKED | Memerlukan direct Exam–Room, placement, assignment lifecycle, overlap serialization, dan monitoring Room-scoped dari Agent 1; global lock/policy contract Agent 2 juga belum terbukti selesai. |
| Penilaian Essay | BLOCKED / NOT EXPOSED | ESSAY di authoring/scoring berstatus manual review, tetapi halaman/workflow penilaian, rubrik, histori koreksi dan finalisasi belum dibuktikan. |
| Hasil & Laporan | BLOCKED / NOT EXPOSED | Menu masih disabled; halaman hasil/rekap/print/export Guru belum diverifikasi. |
| Analytics | BLOCKED / NOT EXPOSED | Menu masih disabled; tidak ada bukti analisis butir/capaian yang siap digunakan. |
| Profil Guru | EXISTS, PARTIAL | Profil ditampilkan; Guru dapat mengubah nomor kontak. Password, notifikasi, preferensi tampilan dan riwayat aktivitas pribadi belum dibuktikan. |
| Pengaturan Guru | MISSING / DEFERRED BY GAP | Menu masih disabled; perlu desain dan implementasi sesuai batas bahwa Guru tidak mengubah konfigurasi global/secret. |

### 1.3 Temuan spesifik roster peserta
Pada source baseline `ExamManagement::saveExam()`, peserta rombel diambil melalui `rombel_student` → `student_profiles` → `user_id`. Jalur ini belum melakukan verifikasi bahwa akun peserta memiliki tepat satu role persisted `SISWA`. Akibatnya, akun multi-role dapat berisiko ikut terdaftar sebagai peserta bila memiliki membership dan profil yang sesuai.

Ini adalah gap pada komponen UI/roster Agent 3, bukan alasan untuk mengubah policy Agent 2.

## 2. DESAIN DAN KEPUTUSAN PATCH

### 2.1 Roster eligibility
Untuk peserta yang berasal dari rombel:
1. Membership harus berasal dari `rombel_student` pada tenant dan rombel yang dipilih.
2. `StudentProfile` harus tenant-local dan belum soft-deleted.
3. `User` harus tenant-local dan belum soft-deleted.
4. Role diambil dari relasi persisted `role_user` + `roles`.
5. Hanya user dengan tepat satu role dan nama role tersebut `SISWA` yang boleh menjadi target peserta.
6. Hasil dikelompokkan per user agar satu user tidak terduplikasi karena relasi join.
7. Role/permission dan tenant pada endpoint lain tetap wajib divalidasi oleh backend owner; patch roster ini bukan pengganti policy API.

Jalur Admin yang menggunakan pilihan `ALL` juga diperketat agar hanya mengambil user persisted, tenant-local, dan tepat satu role SISWA. Guru tetap dilarang memilih ALL.

### 2.2 Batas yang tidak disentuh
- Tidak mengubah schema, migration, model, policy, API, database, atau engine Attempt.
- Tidak mengubah role portal.
- Tidak menambahkan ExamSession/gelombang.
- Tidak mengubah source Agent 1, Agent 2, atau Agent 4.
- Tidak mengaktifkan menu yang belum memiliki backend/permission terverifikasi.

## 3. IMPLEMENTASI

Branch: `work/web-guru-phase1-audit-implementation-20261122`

### File runtime/test yang berubah
1. `backend-laravel/app/Livewire/Admin/ExamManagement.php`
   - Mengganti pemilihan peserta rombel dengan join tenant-local membership/profile/user/role.
   - Mewajibkan tepat satu role SISWA.
   - Mengecualikan profile/user soft-deleted.
   - Jalur Admin ALL juga memakai exact-one SISWA.
   - Blob sebelum patch: `4521a7928318e53d50bd2bf9e792133c0d5f2525`.
   - Blob sesudah patch: `d2304490ccae0eac4a8c30cc1b62e7473efea7b6`.

2. `backend-laravel/tests/Feature/TeacherPracticeWorkflowTest.php`
   - Menambah regression test multi-role SISWA+GURU ditolak.
   - Menambah positive control exact-one SISWA diterima.
   - Koreksi fully-qualified namespace pada test setelah inspeksi statis.
   - Blob sebelum patch: `1c939c70841640c63ca14cd03710a27775145f2a`.
   - Blob terakhir: `815a2008c7663ecef0b6bb7f66bb6626ba01d064`.

3. `.github/workflows/web-guru-foundation-ci.yml`
   - Menambahkan branch kerja fase ini ke push filter agar test disposable MySQL 8 berjalan pada branch isolasi.
   - Blob sebelum patch: `a1422371b6a4a20fc7201221099f99e4fc28cf38`.
   - Blob sesudah patch: `6a683c99d8ae0631095f447f0c791f034d900979`.

### Backup sebelum patch
Semua backup dibandingkan dengan file baseline dan hasilnya **byte-identical / blob SHA MATCH**:
- `backend-laravel/backup_before_patch/agent3-phase1-20261122/ExamManagement.php.before-exact-siswa-roster`
  - Blob backup = blob baseline `4521a7928318e53d50bd2bf9e792133c0d5f2525`.
- `backend-laravel/backup_before_patch/agent3-phase1-20261122/TeacherPracticeWorkflowTest.php.before-exact-siswa-roster`
  - Blob backup = blob baseline `1c939c70841640c63ca14cd03710a27775145f2a`.
- `backend-laravel/backup_before_patch/agent3-phase1-20261122/TeacherPracticeWorkflowTest.php.before-namespace-fix`
  - Backup untuk koreksi test berikutnya.
- `docs/backup/agent3-phase1-20261122/web-guru-foundation-ci.yml.before-phase1-branch-filter`
  - Blob backup = blob baseline `a1422371b6a4a20fc7201221099f99e4fc28cf38`.

### Commit penting
- Backup komponen dan test: `b612ded7ecc6dbad378948fc1d3eaecd5f8920b5`, `7664e34dc0a613a6b4df7e0208f477bff5a4a928`.
- Patch komponen: `dc35307882d4b46c32e5368dc944af25bc684e39`.
- Test regresi awal: `4cdd0d7973f744a23cf684e38993fbbb3a2fa619`.
- Backup workflow dan filter branch: `650076f95cb786458a19f8dd7602d0df35759c8c`, `485df3640a35d28607ce2e51417b670dc922fbd7`.
- Backup sebelum koreksi namespace test: `6c5e79d145d42f54ea935f8fdbc840dbd689e17e`.
- Koreksi namespace test: `a64caefb2ab683fb910c6fe4bf8ff3b14cc182dc`.

## 4. PENGUJIAN

### 4.1 Pemeriksaan statis yang dilakukan
- Fetch ulang source/test/workflow setelah commit: PASS.
- Verifikasi guard query exact-one SISWA pada source terbaru: PASS secara inspeksi teks.
- Verifikasi test memiliki negative assertion multi-role dan positive assertion exact SISWA: PASS secara inspeksi teks.
- Verifikasi backup komponen/test/workflow byte-identical terhadap baseline: PASS.
- Verifikasi branch CI filter memuat branch fase ini: PASS.
- Pemeriksaan PHP lint/PHPUnit lokal: NOT RUN.

### 4.2 GitHub Actions exact-SHA
Workflow: `Web Guru Foundation — Isolated MySQL Verification`.

- Run #94 / ID `45043594517`, SHA workflow `485df3640a35d28607ce2e51417b670dc922fbd7`: attempt 1 dan rerun attempt 2 gagal sebelum step. Job `teacher-portal-tests` tidak memiliki steps (`steps=null`), runner/log tidak tersedia.
- Run #95 / ID `45043743169`, SHA `6c5e79d145d42f54ea935f8fdbc840dbd689e17e`: gagal sebelum step; `steps=null`, `logs_url=null`.
- Run #96 / ID `45043771828`, exact final HEAD `a64caefb2ab683fb910c6fe4bf8ff3b14cc182dc`: attempt 1 dan rerun attempt 2 gagal sebelum step; `steps=null`, `logs_url=null`.

Kesimpulan: **CI BLOCKED / NOT EXECUTED**. Tidak ada bukti PHPUnit, PHP lint, route:list, migration disposable, atau InnoDB check telah berjalan. Kegagalan ini bukan bukti test kode gagal maupun lulus; runner tidak memulai step dan log tidak tersedia.

## 5. DEPENDENCY LINTAS AGENT

- Agent 1 branch `work/stage4b-room-session-20261122`, HEAD terakhir yang diperiksa `0f6921ed7301810689f1d342f5e071ce0851a695`. Direct Exam–Room/placement masih memerlukan verifikasi layanan assignment, overlap serialization, dan monitoring Room-scoped. UI Proktor tetap BLOCKED.
- Agent 2 branch `work/stage4d-account-lifecycle-20261122`, HEAD `7642f8df7d20effe82dc1ba5b086147894332767`. Policy exact-role dan global lock order masih memerlukan konfirmasi serta uji integrasi; jangan menambal file ownership Agent 2 dari branch Guru.
- Agent 4 branch `work/agent4-web-siswa-audit-20261122`, HEAD `69acf08725fc1e4af70531ab8e6e8daaee1cc3bb`. Branch tersebut berisi master spesifikasi/handover Web Siswa; runtime end-to-end belum dapat dianggap siap.
- Issue #8/#9: proposal QuestionDraftDTO dan reuse ImportBatch/ImportBatchRow belum menjadi approval implementasi final. Import XLSX/DOCX UI belum boleh terhubung ke endpoint asumsi.

## 6. STATUS DAN LANGKAH BERIKUTNYA

Status phase 1: **IMPLEMENTED IN BRANCH — NOT RUNTIME VERIFIED**.

Berikutnya:
1. Pulihkan jalur CI agar runner benar-benar menjalankan step; setelah itu buktikan lint, route:list, PHPUnit exact SHA, MySQL 8/InnoDB.
2. Lanjutkan audit dan hardening QuestionBank (ownership, versioning, kunci privat, soft delete, filter/pagination, import contract, media).
3. Lengkapi Penugasan Saya sebagai halaman/menu terpisah hanya bila tidak menduplikasi source dan tanpa mengubah master penugasan Admin.
4. Audit dan harden ExamManagement: update/publish idempotency, roster, jadwal, snapshot, token, status ujian, dan negative IDOR.
5. Implementasi Monitoring Mode A dan Penilaian Essay hanya setelah policy/API dan lifecycle backend dapat dibuktikan.
6. Mode B Proktor menunggu Agent 1/2; jangan membuka menu placeholder.
7. Update checkpoint per fase. Tidak ada merge, migration produksi, akses VPS, atau deployment.

**Kesimpulan:** patch exact-role peserta telah dibuat dan regression test disiapkan. Namun karena workflow tidak pernah memulai step, patch belum diterima sebagai lulus runtime dan Portal Guru tetap belum siap operasional end-to-end.
