# PHASE 4 — AUDIT TERARAH BANK SOAL & ASESMEN WEB GURU
Tanggal: 22 November 2026
Branch kerja: `work/web-guru-phase1-audit-implementation-20261122`
HEAD acuan sebelum audit: `a4c854235978b034504cbcfe4b9c0baeb8131246` (dikonfirmasi compare ref: identik, 0 ahead/behind)
Acuan produk: `docs/MASTER_SPESIFIKASI_FITUR_WEB_GURU_2026-11-22.md`, blob `4d5e0b6f422b0f93005d6bebdabf4925c95f8df1`
Status: **AUDIT SOURCE STATIS — BELUM IMPLEMENTASI / BELUM RUNTIME VERIFIED**

## 1. AUDIT

### 1.1 Bank Soal — source yang diperiksa
- Component `backend-laravel/app/Livewire/Admin/QuestionBank.php`, blob `ad7e6590592ee25c126b2886b739331eed3cc34e`.
- View `backend-laravel/resources/views/livewire/admin/question-bank.blade.php`, blob `360312c228478f4b581481d2a9478b18ae30471d`.
- Component tetap di-route melalui Portal Guru dan layout `layouts.teacher`; namespace/view Admin adalah technical debt, bukan izin akses Admin.
- Boundary component menggunakan `TeacherPortalAccess::profile()`, permission `question.view`, dan assignment mapel aktif. Action utama menggunakan Gate dan `TeacherAccess`.

### 1.2 Bank Soal — status dan gap
**ADA, PARSIAL, BELUM RUNTIME VERIFIED**
- Tipe authoring saat ini: MULTIPLE_CHOICE, MULTIPLE_RESPONSE, TRUE_FALSE, MATCHING, ESSAY. SHORT_ANSWER tidak ditawarkan; tetap BLOCKED.
- List memiliki pencarian kode/stem, filter mapel, filter status, dan pagination.
- Filter yang belum tersedia: kelas/fase, kurikulum/versi, ATP, topik/materi, difficulty, cognitive level, owner, serta rentang waktu.
- Form belum memiliki field kelas/fase, kurikulum/versi, ATP, topik/materi, difficulty, cognitive level, estimated time, media upload/preview, RTL/harakat preview, atau formula editor.
- Pemetaan authoring menggunakan `LearningOutcome`/`LearningObjective` legacy. Halaman Pembelajaran menggunakan struktur multi-curriculum/registration berbeda. Kesatuan mapping, versi, provenance, dan histori belum terbukti.
- Tampilan Guru memperlihatkan opsi dan penanda kunci; ini sesuai kebutuhan authoring Guru. Keamanan payload Siswa berada pada API/backend, bukan dapat disimpulkan dari view Guru.
- Publish soal mengunci Question row dan mencatat audit, tetapi integritas seluruh field/opsi snapshot dan konsistensi dengan scoring belum dibuktikan oleh runtime test.
- Import XLSX/DOCX tidak diimplementasikan. Proposal `QuestionDraftDTO` + reuse `ImportBatch/ImportBatchRow` masih menunggu keputusan eksplisit owner backend pada Issue #8/#9. Tidak dibuat parser, endpoint, dependency, tabel, atau batch service baru.

### 1.3 Exam Builder — temuan source
Source:
- Component `backend-laravel/app/Livewire/Admin/ExamManagement.php`, blob `d2304490ccae0eac4a8c30cc1b62e7473efea7b6`.
- View `backend-laravel/resources/views/livewire/admin/exam-management.blade.php`, blob `302ee33f2136e92d51459e5c1eb9006328571ccb`.
- Snapshot service `backend-laravel/app/Services/ExamSnapshotService.php`, blob `d0bfcd70b2777a3884c7f92b7c256f05492645ec`.

**Temuan yang perlu ditutup sebelum menyatakan Exam Builder aman:**
1. Query `availableQuestions` menampilkan Question tanpa filter status `PUBLISHED`; versi aktif juga tidak difilter berdasarkan status versi.
2. Validasi pilihan versi di `saveExam()` membatasi tenant dan subject, tetapi tidak memeriksa bahwa Question/QuestionVersion berada pada status yang boleh digunakan untuk ujian.
3. `ExamSnapshotService` mengunci Exam dan QuestionVersion, memeriksa tenant/subject, editability, serta riwayat Attempt/Answer/Score sebelum mengganti snapshot. Namun service tidak memeriksa status Question/QuestionVersion sebelum membuat snapshot. Blob service yang sama ditemukan pada branch Agent 3 dan Agent 2.
4. Snapshot menyimpan `snapshot_explanation` serta `is_correct` di `snapshot_options`. Ini dapat diperlukan oleh scoring/private workflow, tetapi public payload Siswa wajib menyaringnya. Sanitasi harus diverifikasi di seluruh API Student dan media delivery oleh owner backend; jangan mengubah struktur snapshot dari UI Guru.
5. Validasi assignment Guru dan rombel dilakukan sebelum transaksi penyimpanan. Di dalam transaksi, Exam dikunci dan `manageQuestions` diotorisasi ulang, tetapi pemeriksaan/serialisasi assignment peserta tidak terlihat diulang dengan lock pada assignment/roster. Ini adalah potensi race/TOCTOU yang perlu direview bersama Agent 2 sebelum patch; belum dibuktikan sebagai exploit.
6. `lockExam()` memeriksa keberadaan snapshot sebelum publish dan mengulang pemeriksaan setelah row lock. Tidak terlihat pemeriksaan jumlah peserta > 0. Master spec meminta peringatan ujian tanpa peserta; apakah kondisi ini harus memblokir publish atau hanya memberi warning perlu diputuskan sesuai aturan operasional.
7. View menampilkan link Hasil dan Analytics tanpa directive capability di sekeliling link. Route hasil/analytics ditemukan pada `routes/web.php`; `AnalyticsDashboard::mount()` melakukan authorization `viewExamResults`. Visibilitas link sebaiknya diselaraskan dengan capability, tetapi route authorization tetap wajib dan tidak boleh diganti oleh penyembunyian link.
8. Tidak ditemukan kebutuhan untuk menambah ExamSession. Model serentak tetap dipertahankan.

### 1.4 Drift policy lintas branch — blocker integrasi
Master spec terbaru menetapkan hanya role portal ADMIN_MADRASAH, GURU, SISWA; KAMAD bukan akun/role CBT; semua aksi perlu exact-one role.

- Branch Agent 2 `work/stage4d-account-lifecycle-20261122`:
  - `ExamPolicy.php` blob `c05d96ef932fdc81b67336240af4e1726864c3d6` masih memuat KAMAD dan beberapa pemeriksaan `hasRole()` tanpa exact-one.
  - `ReportPolicy.php` blob `0fbd934bde79ebd7ae3fe4fe08f09cb4dc9e46ce` masih mengizinkan KAMAD.
  - `QuestionPolicy.php` blob `af2b3be5fdee31b857c56fdf09ef54ffd02ed94e` sudah memiliki helper exact-one GURU.
- Branch koordinator `work/coordinator-web-admin-audit-20261122`:
  - `ExamPolicy.php` blob `d76ed7ecba7f41fbad701b60dee9b843eefb6c84` dan `ReportPolicy.php` blob `1ac4e007224396e3c06163a940731b7151a33625` telah menghapus allowlist KAMAD.
  - Pemeriksaan beberapa aksi masih menggunakan `hasRole()`; exact-one pada setiap aksi belum dibuktikan dari source yang diperiksa.
- Kedua branch tersebut belum otomatis terintegrasi ke branch Guru. Tidak boleh menganggap source branch lain sudah menjadi runtime branch Guru. Policy Agent 2/koordinator tidak diubah pada Phase 4.

### 1.5 CI dan runtime
- Branch Guru saat ini identik dengan HEAD dokumentasi `a4c854235978b034504cbcfe4b9c0baeb8131246` sebelum backup checkpoint Phase 4.
- Riwayat sebelumnya menunjukkan Actions run #94–#106 gagal sebelum runner/steps; `steps=null`, log tidak tersedia.
- Dalam audit ini tidak menjalankan PHP lint, PHPUnit, route:list, MySQL 8/InnoDB, browser E2E, concurrency test, maupun UAT.
- Karena itu semua temuan di atas berstatus inspeksi statis; bukan runtime PASS/FAIL.

## 2. DESAIN — URUTAN PENUTUPAN YANG DIUSULKAN
1. Agent 2 menetapkan kontrak final status Question/QuestionVersion yang eligible untuk Exam, dan menerapkan validasi otoritatif di snapshot/backend service.
2. Agent 2/koordinator menyatukan ExamPolicy dan ReportPolicy dengan master role terbaru: tidak ada KAMAD, exact-one role untuk setiap action Guru/Siswa, dan regresi multi-role.
3. Agent 2 memverifikasi pemisahan snapshot private/public: `is_correct`, explanation, accepted answers, dan metadata privat tidak boleh keluar ke Siswa; media harus melalui authorization tenant/Exam/Attempt.
4. Agent 3 kemudian menyelaraskan daftar pilihan dan validasi UI Exam Builder dengan kontrak backend yang telah disetujui, tanpa membuat aturan UI yang berbeda dari backend.
5. Agent 3 + Agent 2 merancang revalidation/lock order untuk assignment mapel/rombel/roster di dalam transaksi. Jangan menambahkan lock sebelum urutan lock disepakati.
6. Setelah kontrak dan test matrix tersedia, patch dilakukan satu kelompok kecil dengan backup byte-exact dan regression test negatif/positif.
7. Baru setelah itu aktifkan link/capability Hasil, Analytics, Monitoring, Penilaian dan Proktor sesuai policy/API yang benar-benar tersedia.

## 3. IMPLEMENTASI
- Tidak ada source runtime yang diubah pada Phase 4 audit ini.
- Tidak ada migration, schema, API, dependency, database, VPS, deployment, merge, atau perubahan `main`.
- Backup checkpoint dibuat sebelum dokumen checkpoint diperbarui:
  `docs/backup/agent3-phase4-20261122/CHECKPOINT_WEB_GURU_PHASE1_2026-11-22.md.before-phase4-audit`.

## 4. PENGUJIAN
- Fetch source dan blob SHA melalui GitHub: dilakukan.
- Compare branch terhadap HEAD `a4c854...`: identik sebelum audit docs.
- Inspeksi statis query QuestionBank, validasi Exam, snapshot service, routes, dan authorization Analytics: dilakukan.
- Backup checkpoint pra-update: disimpan sebagai file terpisah.
- PHP lint/PHPUnit/route:list/MySQL/browser/concurrency/UAT: **NOT RUN**.

## 5. KEPUTUSAN DAN STATUS
- Status Phase 4: **AUDIT SELESAI — IMPLEMENTASI DITAHAN MENUNGGU KONTRAK BACKEND DAN TEST GATE**.
- Status Web Guru: **PARTIAL — NOT READY FOR OPERATIONAL EXAM**.
- Tidak ada klaim fitur lulus runtime.
- Langkah berikutnya paling aman: minta Agent 2/koordinator mengonfirmasi kontrak eligibility QuestionVersion, private snapshot, exact-role Exam/Report policy, dan lock order assignment; setelah ada jawaban, lanjut patch UI Guru yang sesuai.
