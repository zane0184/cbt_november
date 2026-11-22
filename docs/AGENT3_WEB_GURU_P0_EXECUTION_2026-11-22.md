# Agent 3 — Laporan Eksekusi P0 Web Guru, Bank Soal, dan Asesmen

Tanggal: 22 November 2026
Repository: `zane0184/cbt_november`
Branch: `work/web-guru-foundation-20261122`
Source HEAD saat audit: `91d484b34b7e01fa6652138761ccbcab80370349`
Keputusan model ujian terbaru: Issue #10 comment `7079750527`
Status keseluruhan: **PARTIAL — P0 BELUM SIAP OPERASIONAL END-TO-END**

Laporan ini memperbarui audit/implementasi Agent 3. Source aktual dan keputusan terbaru pemilik menjadi acuan; checkpoint sebelumnya yang menyebut ExamSession tidak lagi berlaku.

## 1. AUDIT

### 1.1 Ruang lingkup dan keputusan model ujian

- Portal aktif: ADMIN_MADRASAH, GURU, SISWA.
- Tidak ada portal/role OPERATOR, KAMAD, atau PROKTOR permanen.
- Proktor adalah assignment sementara kepada akun GURU.
- Satu Exam adalah satu pelaksanaan serentak dengan jendela `exam.start_at`–`exam.end_at` dan waktu server.
- Tidak ada ExamSession, session number, wave, atau lifecycle sesi baru.
- Banyak Room, roster/placement peserta, dan assignment Proktor terhubung langsung ke Exam + Room.
- Mode A: Guru pembuat asesmen mandiri hanya dalam scope penugasan kelas/peserta; tidak boleh lintas kelas.
- Mode B: ujian terpusat; Admin Madrasah menugaskan Guru sebagai Proktor pada Room tertentu. Hanya assignment Admin yang sah yang dapat memberi akses lintas kelas.

Keputusan ini menggantikan desain lama satu Exam–satu ExamSession.

### 1.2 Source Web Guru

| File | Blob terkini | Temuan |
|---|---|---|
| `app/Http/Controllers/AuthWebController.php` | `fcc66f12dcfb4b77acc364f68438e06f6521db63` | Login Web hanya menerima tepat satu role persisted: ADMIN_MADRASAH/GURU/SISWA. Role lain, KAMAD, OPERATOR dan multi-role ditolak. Sesi authenticated lama yang role-nya tidak valid dilogout. |
| `routes/teacher.php` | `63eac1edee6f6b374fd1f4786a0cfc129099dbde` | Route /guru dilindungi auth, tenant, license, EnsureTeacherPortalAccess. Tersedia dashboard, pembelajaran, kurikulum, profil, bank soal, asesmen, template import. |
| `resources/views/layouts/teacher.blade.php` | `20fe7eb4ed0e31c89ff8a8ab5812043cc8967559` | Menu Bank Soal dan Asesmen & Ujian aktif; Monitoring, Penilaian & Hasil, Analytics masih nonaktif/ditahan. Layout menyediakan sidebar desktop dan menu mobile serta warna adaptif berdasarkan preferensi sistem. |
| `app/Livewire/Admin/QuestionBank.php` | `ad7e6590592ee25c126b2886b739331eed3cc34e` | Komponen lama namespace Admin sekarang dipasang di route Guru dan memakai layout Guru. Boundary memeriksa exact GURU, profil Guru, permission dan penugasan mapel. SHORT_ANSWER ditolak di validasi server. |
| `resources/views/livewire/admin/question-bank.blade.php` | `360312c228478f4b581481d2a9478b18ae30471d` | Form Bank Soal menampilkan tipe yang tersedia dan tidak menawarkan SHORT_ANSWER untuk authoring baru. |
| `app/Livewire/Admin/ExamManagement.php` | `4521a7928318e53d50bd2bf9e792133c0d5f2525` | Komponen lama namespace Admin sekarang dipasang pada route Guru dan memakai layout Guru. Scope create/edit/peserta dibatasi subject dan rombel yang ditugaskan. Tidak membuat ExamSession. |
| `resources/views/livewire/admin/exam-management.blade.php` | `302ee33f2136e92d51459e5c1eb9006328571ccb` | Judul dan keterangan disesuaikan menjadi Asesmen & Ujian Guru, bukan UI sesi/gelombang. |
| `app/Services/TeacherPortal/TeacherPortalDataService.php` | `59f8b4affeeb74cd5307a57dd29a2a5aff88ee37` | Dashboard memakai assignment aktif dan menghitung siswa hanya dari profil/user/rombel aktif, tenant yang cocok, akun tidak terhapus, dan tepat satu role persisted SISWA. |
| `tests/Feature/TeacherPortalFoundationTest.php` | `dc82958d8ac9557859b471e551ca456b8a83e774` | 26 metode test menurut pemeriksaan statis, termasuk route Guru, akses direct route, tenant context dan jumlah siswa valid. |
| `tests/Feature/TeacherPracticeWorkflowTest.php` | `1c939c70841640c63ca14cd03710a27775145f2a` | Workflow test memeriksa create soal, create ujian, snapshot, peserta rombel, publish dan token otomatis. |
| `tests/Feature/PortalLoginRoleBoundaryTest.php` | `1c2001d25d3bd878e6dbe7374fe7e13a86ff611f` | 7 metode test menurut pemeriksaan statis untuk tiga role portal, KAMAD/OPERATOR, multi-role dan stale session. |
| `.github/workflows/web-guru-foundation-ci.yml` | `a1422371b6a4a20fc7201221099f99e4fc28cf38` | Filter PHPUnit sudah memasukkan TeacherPortalFoundationTest, TeacherPracticeWorkflowTest, QuestionImportTemplateDownloadTest, PortalLoginRoleBoundaryTest. |

### 1.3 Dashboard dan akademik

- Data kelas/rombel dan mapel berasal dari TeacherSubjectAssignment yang tenant-local dan memiliki relasi akademik valid.
- Statistik siswa tidak lagi sekadar menghitung membership. Query memeriksa profil dan akun aktif, tenant, rombel aktif, dan tepat satu role SISWA.
- Test tambahan memasukkan siswa valid, akun soft-deleted, dan akun multi-role; ekspektasi hanya siswa valid yang dihitung.
- CP/TP dapat dipilih dari data yang ada pada database. Sumber resmi, versi kurikulum, kelengkapan Pembelajaran Mendalam, Panca Cinta, dan dimensi lulusan belum diaudit end-to-end. Tidak boleh mengklaim integrasi kurikulum resmi lengkap.

### 1.4 Bank Soal

- Tipe yang terlihat pada UI: MULTIPLE_CHOICE, MULTIPLE_RESPONSE, TRUE_FALSE, MATCHING, ESSAY.
- ScoringService yang diperiksa memiliki evaluator untuk pilihan ganda, pilihan ganda kompleks, benar/salah, menjodohkan, dan ESSAY dengan status MANUAL_REVIEW.
- SHORT_ANSWER diblokir pada UI dan validasi server karena accepted answers belum terbukti tersalin ke snapshot yang dipakai ScoringService.
- ExamSnapshotService menyalin stem, explanation, options, media metadata dan question_type ke snapshot; snapshot dikunci dari perubahan setelah ujian dipublikasi/ada histori.
- Form Bank Soal yang diperiksa belum memperlihatkan alur upload media, preview khusus, editor formula, atau kontrol RTL eksplisit. Textarea dapat menerima Unicode/Arab/harakat, tetapi dukungan RTL dan renderer rumus Web–Android belum terverifikasi.
- Nama namespace dan view masih legacy `App\\Livewire\\Admin` / `livewire.admin.*`; route dan layout yang digunakan sudah di area Guru. Ini technical debt, bukan alasan membuat portal Admin untuk Guru.

### 1.5 Asesmen mandiri Guru

- Route operasional UI: `/guru/asesmen` (`teacher.exams.index`).
- ExamManagement membuat Assessment/Exam langsung dengan start_at/end_at, memilih soal, membatasi rombel sesuai assignment Guru, menyimpan participant roster, membuat snapshot, dan menerbitkan Exam.
- `lockExam()` mengubah status menjadi PUBLISHED dan membuat token otomatis jika belum ada.
- Mode ini adalah asesmen mandiri Guru (Mode A). UI ini tidak memberikan kewenangan lintas kelas.
- Test workflow telah ditambah assertion token otomatis dan require_token setelah publikasi.
- Mode B (ujian terpusat dengan Room dan Proktor) belum terintegrasi ke komponen ini.

### 1.6 Integrasi Agent 1/2/4

- Agent 1 branch `work/stage4b-room-session-20261122`, HEAD `0f6921ed7301810689f1d342f5e071ce0851a695`. Migration/model direct Exam–Room awal telah ditambahkan, termasuk proctoring_mode default SELF_MANAGED, pivot Exam–Room, placement peserta dan room relation pada assignment. Namun service create/confirm/revoke Proktor, placement UI/callers, dan monitoring consumers belum seluruhnya dialihkan; belum ada MySQL concurrency test.
- Agent 2 branch `work/stage4d-account-lifecycle-20261122`, HEAD `7642f8df7d20effe82dc1ba5b086147894332767`. Belum ada global lock order yang disetujui pada Issue #10. ExamPolicy masih memakai hasRole pada banyak jalur; exact-role dan lock contract lintas Exam/Room/placement/ProctorAssignment/Attempt/Answer/Score/Lease masih memerlukan keputusan/pembuktian.
- Agent 4 branch `work/agent4-web-siswa-audit-20261122`, HEAD `a0425e63c06fe2ac0ddbd942f81dd7db6ca111fd`. Status yang tersedia masih checkpoint audit Web Siswa; admission, Attempt/Answer, submit/resume, recovery, lease/fencing dan UAT belum dinyatakan siap.
- `LoginIdentityResolver` yang menjadi dependency login masih mengenali KAMAD sebagai kandidat. AuthWebController menolak role tersebut sebelum login berhasil, tetapi resolver tetap perlu diselaraskan oleh owner Agent 2 agar allowlist konsisten.

### 1.7 Deployment

- Pemilik menyatakan Web CBT sudah terpasang pada VPS dan dapat dibuka melalui browser.
- Sesuai instruksi, Agent 3 tidak mengakses SSH/VPS dan tidak meminta kredensial.
- Exact deployed commit, schema/migration production, PHP minor, dan kompatibilitas upgrade belum diverifikasi. Tidak ada migration atau perubahan database produksi.

## 2. DESAIN

1. Web Guru memakai route `/guru/*` dengan exact-role/tenant/assignment guard di middleware dan komponen.
2. Bank Soal dan Asesmen mandiri Guru berada di navigasi Guru. Asesmen Mode A hanya mengelola peserta dalam rombel yang ditugaskan.
3. Tidak ada UI ExamSession/wave.
4. Mode B hanya diaktifkan setelah Agent 1 menyelesaikan direct Exam–Room service, room placement, assignment Proktor, overlap serialization dan monitoring room-scoped; Agent 2 harus menyetujui global lock order.
5. Proktor tidak memperoleh jawaban, kunci, nilai, hasil, force submit, reset attempt, atau Room lain hanya karena memiliki assignment.
6. SHORT_ANSWER tetap diblokir sampai accepted answers benar-benar masuk snapshot dan scoring teruji.
7. Media, preview, Arabic RTL/harakat, formula editor, dan kurikulum resmi hanya ditampilkan setelah backend/data/source/versioning dan renderer tersedia.

## 3. IMPLEMENTASI

Perubahan source pada branch Agent 3:

- AuthWebController: exact-one portal role, persisted role read, reject KAMAD/OPERATOR/multi-role, stale session logout.
- routes/teacher.php: route /guru/bank-soal dan /guru/asesmen; duplicate route registration telah dibersihkan.
- layouts/teacher.blade.php: navigasi Bank Soal dan Asesmen & Ujian aktif; menu monitoring/penilaian/analytics tetap ditahan.
- QuestionBank: route Guru, layout Guru, SHORT_ANSWER diblokir di validasi server dan disembunyikan dari pilihan authoring baru.
- ExamManagement: route Guru, layout Guru, UI diberi label Asesmen & Ujian Guru; alur Mode A tetap berdasarkan assignment Guru.
- TeacherPortalDataService: dashboard count membatasi ke siswa valid dan exact-one SISWA.
- Tests dan CI workflow: login role, route Bank Soal/Asesmen, validasi SHORT_ANSWER, dashboard student count, token publication dan TeacherPracticeWorkflow dimasukkan ke filter CI.

File Agent 2 tidak diubah. `routes/web.php` tidak diubah. Tidak ada migration, schema, DB, deployment, merge atau rebase.

## 4. BACKUP

Backup source yang diverifikasi byte-identical sebelum patch tersedia di:

- `backend-laravel/docs/backup/agent3-web-guru-urgent-20261122/AuthWebController.php.before-exact-three-portals` — blob `d2fba9ea8a35ee84cbbe08f1d3e2c24a77f48893`.
- `backend-laravel/docs/backup/agent3-web-guru-urgent-20261122/teacher.php.before-bank-soal-route` — blob `ace47ce471f1c87776dd991632f4c98221027a8b`.
- `backend-laravel/docs/backup/agent3-web-guru-urgent-20261122/teacher.php.before-deduplicate-assessment-route` — blob `655ddbecb16525970754e1ae528bbf73e549cf53`.
- `backend-laravel/docs/backup/agent3-web-guru-urgent-20261122/teacher.blade.php.before-bank-soal-route` — blob `c6b11bab9eb5869ad7c0867b8a444a62f78c7a8c`.
- `backend-laravel/docs/backup/agent3-web-guru-urgent-20261122/app.blade.php.before-bank-soal-route` — blob `8cffb53d3592e6af33f80a5f30c3846eda300949`.
- `backend-laravel/docs/backup/agent3-web-guru-urgent-20261122/QuestionBank.php.before-bank-soal-route` — blob `219efc2db8f492452ad95da35ae22eb4849d1e66`.
- `backend-laravel/docs/backup/agent3-web-guru-urgent-20261122/question-bank.blade.php.before-bank-soal-route` — blob `72feaf1cc1be17253868c91861781dbb223b5aa9`.
- `backend-laravel/docs/backup/agent3-web-guru-urgent-20261122/TeacherPortalFoundationTest.php.before-bank-soal-route` — blob `4f1bb48b390b03b2602df6348fd3b9788581e6c9`.
- `backend-laravel/docs/backup/agent3-web-guru-urgent-20261122/PortalLoginRoleBoundaryTest.php.before-positive-admin-student` — blob `a326e973e140b87ffb4fdabd19e183968c8e1222`.
- `backend-laravel/docs/backup/agent3-web-guru-urgent-20261122/web-guru-foundation-ci.yml.before-login-role-test` — blob `8c0c1f4de31dedf1584d0ee5aeecfff735596eb6`.
- `backend-laravel/docs/backup/agent3-web-guru-urgent-20261122/TeacherPracticeWorkflowTest.php.before-standalone-exam-route` — blob `4806a1913f24b0e180b522b2398b66ef405d071b`.

Backup tambahan untuk file yang diperbarui oleh pekerjaan paralel tercatat dengan nama `*.before-*` pada folder backup yang sama. Tidak ada backup yang ditimpa.

## 5. PENGUJIAN

| Pemeriksaan | Hasil |
|---|---|
| Source/branch/issue refresh | PASS — statis |
| Backup sebelum patch | PASS — blob sumber dan backup cocok untuk backup yang dicatat |
| TeacherPortalFoundationTest | 26 metode terdeteksi statis; BELUM dijalankan pada HEAD ini |
| PortalLoginRoleBoundaryTest | 7 metode terdeteksi statis; BELUM dijalankan pada HEAD ini |
| TeacherPracticeWorkflowTest | 4 test method berbasis doc-comment terdeteksi; BELUM dijalankan pada HEAD ini |
| PHP lint PHP 8.3 | NOT RUN pada HEAD ini |
| route:list | NOT RUN pada HEAD ini |
| PHPUnit exact HEAD | NOT VERIFIED |
| CI lama #44984094200 | PASS pada SHA `e9c6867d40b134538e791bbbba02f5743afec7ea`: 11 tests / 44 assertions; bukan source HEAD saat ini |
| CI #45003191589 | Dua kegagalan ekspektasi test pernah dilaporkan; ekspektasi diperbaiki, tetapi hasil perbaikan pada HEAD saat ini belum terbukti |
| Combined status HEAD `91d484b34b7e01fa6652138761ccbcab80370349` | EMPTY / UNKNOWN |
| MySQL 8/InnoDB multi-connection | NOT RUN untuk perubahan direct Exam–Room |
| Browser/E2E desktop/tablet/mobile | NOT RUN |
| Light/Dark visual QA | NOT RUN |
| Production DB / VPS | NOT TOUCHED |

49 PHPUnit doc-comment metadata deprecation warnings yang tercatat pada CI awal masih perlu diinventarisasi dan dibereskan.

## 6. RISIKO / BLOCKER

### P0
1. Mode B Proktor belum siap: service assignment dan placement belum seluruhnya memakai direct Exam–Room; overlap serialization dan concurrency test belum ada.
2. Agent 2 belum menetapkan global lock order/transaction contract. Jangan klaim race-safe.
3. Agent 2 exact-role pada policy/API belum selesai. UI component exact Guru tidak menggantikan policy enforcement di endpoint lain.
4. Agent 4 belum membuktikan admission, token, waktu server, Attempt/Answer, submit/resume, recovery dan sinkronisasi.
5. Old route `/admin/questions` dan `/admin/exams` masih berada di `routes/web.php`; Agent 3 tidak mengedit file milik Agent 2. Perlu redirect/removal/compatibility decision oleh Agent 2 setelah memastikan semua caller.
6. Browser QA dan exact-SHA CI belum ada. Karena itu, route/fitur yang lolos inspeksi statis belum boleh disebut siap produksi.
7. Exact deployment source/schema VPS belum diketahui; upgrade compatibility belum diverifikasi.

### P1
1. Upload media/preview soal, RTL eksplisit, formula editor dan renderer lintas Web belum terbukti tersedia pada UI Guru.
2. Kurikulum Merdeka/KBC/Panca Cinta/Pembelajaran Mendalam/dimensi lulusan perlu source resmi, versioning dan audit data.
3. Menu Penilaian, Monitoring, Analytics, dan Proktor belum dibuka sebagai capability umum. Akses per ujian harus melalui policy yang diuji.

## 7. HANDOVER

- **Agent 1:** lanjutkan direct Exam–Room: service create/confirm/revoke Proktor, participant placement, monitoring room-scoped semua caller/export, overlap [starts_at, ends_at), audit, dan test MySQL 8/InnoDB multi-connection. Jangan hidupkan Session UI.
- **Agent 2:** tetapkan global lock order; selaraskan exact-role ExamPolicy/QuestionPolicy/LoginIdentityResolver; review route legacy `/admin/*`; berikan kontrak transaksi, reauthorization, idempotency, Answer update, submit/resume, scheduler dan audit.
- **Agent 4:** integrasikan student admission terhadap Exam start/end/token/server time; Attempt/Answer/submit/resume/recovery/sync sesuai kontrak Agent 2; uji end-to-end.
- **Agent 3:** setelah kontrak diterima, bangun menu Proktor di Web Guru dengan Room scope; finalisasi Penilaian/Essay dan hasil; tambah media/preview/RTL/formula hanya setelah backend dan renderer terbukti; lakukan browser QA setelah exact-SHA CI lulus.
- Tidak ada merge/deployment. Tidak ada permintaan SSH/kredensial. Tidak ada migration produksi.

**Kesimpulan:** route Bank Soal dan Asesmen mandiri Guru telah tersedia di source dan tes pendukung telah ditambahkan, tetapi belum ada hasil CI exact-SHA atau browser/E2E untuk HEAD terbaru. Proktor ujian terpusat dan operasi ujian end-to-end tetap BLOCKED. **P0 OPEN — BELUM BOLEH DINYATAKAN SIAP DIGUNAKAN BESOK.**


## ADDENDUM — CI exact-SHA dan deployment compatibility (22 Nov 2026, pembaruan)

### Identitas source dan workflow
- Source terakhir yang mengubah runtime/test: `91d484b34b7e01fa6652138761ccbcab80370349`.
- Branch Agent 3 tetap `work/web-guru-foundation-20261122`; HEAD saat audit dokumen ini: `2b85995403df4fbc47adb5692370bdbbe1d6c180` (commit dokumentasi/backup setelah source SHA di atas).
- Test source blob: `dc82958d8ac9557859b471e551ca456b8a83e774` (26 metode `test_*` statis pada `TeacherPortalFoundationTest`).
- Login role test blob: `1c2001d25d3bd878e6dbe7374fe7e13a86ff611f` (7 metode statis).
- Workflow blob: `a1422371b6a4a20fc7201221099f99e4fc28cf38`; filter memasukkan `TeacherPortalFoundationTest`, `TeacherPracticeWorkflowTest`, `QuestionImportTemplateDownloadTest`, dan `PortalLoginRoleBoundaryTest`.

### Hasil CI exact-SHA
- Run [#45028604234](https://github.com/zane0184/cbt_november/actions/runs/45028604234) dipicu event `push` pada source SHA `91d484b34b7e01fa6652138761ccbcab80370349`.
- Attempt 1 berakhir `failure`; job `teacher-portal-tests` tidak memiliki step, runner belum teralokasi, dan log tidak tersedia.
- Rerun job sekali (attempt 2) juga berakhir `failure`; step tetap kosong, `runner_id` null, dan endpoint log mengembalikan `BlobNotFound`.
- Run sebelumnya #45028590509 pada SHA `915379f7fa0e04c186fa9f604b4b21f007a10a9e` juga gagal sebelum step berjalan.
- Kesimpulan yang dapat dibuktikan: **workflow gagal pada tahap sebelum runner/step dimulai**. Ini bukan bukti PHPUnit gagal atau lulus. Penyebab layanan/runner tidak dipaparkan pada metadata/log yang tersedia; jangan menebak akar masalah.
- PHPUnit, PHP lint, route:list, dan tes MySQL pada source SHA tersebut **belum berjalan**. Status CI acceptance: **BLOCKED / NOT EXECUTED**.

### Deployment aktif / upgrade compatibility
- URL deployment yang diinformasikan pemilik: `https://cbt.min15jakarta.sch.id/login`; Agent 3 tidak memiliki akses SSH dan tidak mengakses VPS.
- Prompt eksekusi terbaru menyebut database aplikasi masih kosong dan akan dibuat baru. Addendum pemilik Issue #10 sebelumnya menyatakan ini revisi aplikasi yang sudah terpasang dan meminta seluruh data/riwayat dipertahankan.
- Karena tidak ada akses read-only ke release marker, manifest deployment, schema database, migration ledger, atau storage, kondisi database aktual **BELUM TERVERIFIKASI**. Kedua pernyataan tersebut tidak boleh diselaraskan dengan tebakan.
- Tidak ada reset, fresh-install, migration, perubahan konfigurasi, atau deployment produksi. Sampai bukti metadata/schema non-secret tersedia, perlakukan produksi secara non-destruktif dan jangan menghapus/mengganti data.

### Status setelah addendum
- Route Bank Soal dan Asesmen mandiri Guru tersedia pada source, dengan tes ditambahkan tetapi belum dieksekusi.
- Statistik siswa kini menyaring akun soft-deleted dan role yang bukan tepat satu `SISWA`; membership dianggap aktif bila pivot tenant-local masih ada pada rombel aktif karena tabel `rombel_student` tidak memiliki kolom status.
- Browser/E2E, light/dark QA, media/RTL/formula, Penilaian ESSAY, Proktor Mode B, dan end-to-end ujian tetap belum diterima.
- **P0 OPEN — BELUM SIAP OPERASIONAL.**
