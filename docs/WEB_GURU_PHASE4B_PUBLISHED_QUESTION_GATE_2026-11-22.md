# PHASE 4B — GATE SOAL TERPUBLIKASI PADA EXAM BUILDER GURU
Tanggal: 22 November 2026
Branch: `work/web-guru-phase1-audit-implementation-20261122`
Source commit: `18830228b64e6718291119662a2907e0e2f416b8`
Test commit: `616dc82f006e0c8327f5738857cd33d53ec9f071`
Status: **PATCH UI/SERVER ACTION SELESAI — RUNTIME TEST BLOCKED**

## 1. AUDIT
Audit Phase 4 menemukan Exam Builder menampilkan Question tanpa memfilter status publikasi dan `saveExam()` hanya memeriksa tenant/subject pada QuestionVersion. Sementara itu, alur Bank Soal yang ada menetapkan Question dan current QuestionVersion menjadi `PUBLISHED` secara bersamaan pada `publishQuestion()`.

Status ini berasal langsung dari source:
- `QuestionBank::publishQuestion()` mengunci Question, mengambil currentVersion, lalu mengubah kedua status menjadi `PUBLISHED`.
- Soal baru dan hasil edit dibuat sebagai `DRAFT`.
- `Question::currentVersion()` mengacu pada versi dengan `is_current = true`.

## 2. DESAIN
Untuk UI/Livewire Guru, hanya Question dengan status `PUBLISHED` dan current QuestionVersion dengan status `PUBLISHED` yang boleh ditawarkan atau dipilih ke Exam Builder.
- Query daftar pilihan harus menampilkan soal terbit saja.
- `saveExam()` harus mengulangi pemeriksaan status server-side, sehingga manipulasi properti Livewire dengan ID versi DRAFT tidak diterima.
- Status tenant/subject tetap divalidasi lebih dahulu dan kegagalan lingkup tetap 403.
- Validasi ini adalah perlindungan pada jalur Web Guru, bukan pengganti validasi otoritatif di `ExamSnapshotService`. Service tersebut masih milik Agent 2 dan perlu patch yang sama untuk semua caller.

## 3. IMPLEMENTASI
File runtime diubah:
1. `backend-laravel/app/Livewire/Admin/ExamManagement.php`
   - Daftar `availableQuestions` sekarang hanya mengambil Question `PUBLISHED` dengan current QuestionVersion `PUBLISHED`.
   - `saveExam()` mempertahankan validasi tenant/subject, kemudian memvalidasi status Question dan QuestionVersion. Versi DRAFT ditolak dengan validation error sebelum transaksi membuat Assessment/Exam.
   - Blob sebelum: `d2304490ccae0eac4a8c30cc1b62e7473efea7b6`.
   - Blob sesudah: `453879ef8ad42a7dbea9cd6a78c5eb7c8face219`.

2. `backend-laravel/tests/Feature/TeacherPracticeWorkflowTest.php`
   - Ditambah regresi: draft QuestionVersion yang tenant/subject-nya benar tetap ditolak oleh Exam Builder.
   - Test memastikan tidak ada row Exam maupun Assessment dibuat ketika pilihan soal masih DRAFT.
   - Blob sebelum: `815a2008c7663ecef0b6bb7f66bb6626ba01d064`.
   - Blob sesudah: `0271a7836c5fefe76b97e9bca70ded23ae35619e`.

Backup pra-patch:
- `backend-laravel/backup_before_patch/agent3-phase4b-20261122/ExamManagement.php.before-published-question-gate`
  - SHA backup = SHA source sebelum patch: `d2304490ccae0eac4a8c30cc1b62e7473efea7b6`.
- `backend-laravel/backup_before_patch/agent3-phase4b-20261122/TeacherPracticeWorkflowTest.php.before-published-question-gate`
  - SHA backup = SHA source sebelum patch: `815a2008c7663ecef0b6bb7f66bb6626ba01d064`.

Backup checkpoint pra-update:
- `docs/backup/agent3-phase4b-20261122/CHECKPOINT_WEB_GURU_PHASE1_2026-11-22.md.before-phase4b-gate`
- SHA backup cocok dengan checkpoint sebelum perubahan: `789cf48066aba6bb216d923d2531e8701655209f`.

Tidak ada perubahan pada ExamSnapshotService, QuestionPolicy, ExamPolicy, ReportPolicy, schema, migration, dependency, database, VPS, deployment, merge, atau main.

## 4. PENGUJIAN
**Yang berhasil diverifikasi dari repository:**
- Backup source dan test byte-exact berdasarkan blob SHA: 2/2 cocok.
- Backup checkpoint byte-exact: cocok.
- Fetch ulang source menunjukkan filter `PUBLISHED` dan server-side gate tersedia.
- Fetch ulang test menunjukkan regresi draft-question tersedia.
- Existing positive workflow test `teacher_can_build_exam_pick_questions_assign_rombel_and_lock_exam` tetap ada dan mempublikasikan soal sebelum dipakai.

**CI exact SHA — gagal sebelum steps:**
- Run #109, ID `45047046983`, source commit `18830228b64e6718291119662a2907e0e2f416b8`: job `teacher-portal-tests` completed/failure, `steps=null`, `logs_url=null`.
- Run #110, ID `45047116468`, test commit `616dc82f006e0c8327f5738857cd33d53ec9f071`: job `teacher-portal-tests` completed/failure, `steps=null`, `logs_url=null`.
- Combined commit status untuk test SHA tidak memiliki status checks.
- Karena runner tidak memulai steps, PHP lint dan PHPUnit **belum dijalankan**. Ini bukan bukti test lulus ataupun bukti kode gagal.

Belum dilakukan: route:list, disposable MySQL 8/InnoDB, browser E2E, concurrency, API integration, dan UAT.

## 5. BATAS DAN BLOCKER
1. Gate ini hanya menutup jalur Livewire Guru. `ExamSnapshotService` belum menegakkan status `PUBLISHED` secara otoritatif; Agent 2 harus menutup invariant ini di service/backend dengan lock order yang disepakati.
2. Belum ada respons keputusan final Agent 2/koordinator pada Issue #10 mengenai private/public snapshot, exact-role Exam/Report, dan lock order assignment/roster.
3. Jangan menganggap Monitoring, Essay, Results, Analytics, atau Proktor siap/aktif dari patch ini.
4. Status Web Guru tetap **PARTIAL — NOT READY FOR OPERATIONAL EXAM**.

## 6. LANGKAH BERIKUTNYA
- Minta Agent 2 mengunci invariant status publikasi pada `ExamSnapshotService` dan menambah test service langsung.
- Pulihkan penyebab workflow runner gagal sebelum menambah patch berikutnya; gunakan exact SHA dan disposable MySQL 8.
- Setelah service invariant dan CI terbukti, lanjutkan satu subfitur berikutnya berdasarkan master spec. Jangan menambah import/media/curriculum UI sebelum kontrak backend disetujui.
