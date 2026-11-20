# Checkpoint Web CBT — Audit & Perbaikan Lanjutan
Tanggal: 2026-11-20
Branch: `fix/web-portal-usability-rbac-20261120`
PR: #5 (Draft)

## AUDIT
- Memeriksa komponen UserManagement, StudentRombelManagement, ProctorAssignmentManagement, ResultList, QuestionBank, ExamManagement, AcademicManagement, CurriculumManagement; model tenant terkait; TenantScoped; IdentifyTenant; ExamPolicy; ReportPolicy; QuestionPolicy; TeacherAccess; routes/web.php; layout navigasi.
- StudentProfile, Rombel, Subject, LearningOutcome, LearningObjective memakai TenantScoped. StudentRombelManagement juga membatasi operasi mutasi dengan tenant_id, mengunci baris terkait, dan menulis audit log; query render mengikuti global scope fail-closed. Belum ada bukti runtime khusus untuk seluruh skenario ini.
- Ditemukan ketidaksesuaian navigasi Data Akademik & Rombel: link ditampilkan untuk OPERATOR, GURU, KAMAD, sedangkan komponen hanya mengizinkan ADMIN_MADRASAH/ADMIN dengan permission academic.manage. Ini menyebabkan menu terlihat tetapi halaman ditolak untuk beberapa role.
- ExamManagement menampilkan akses untuk ADMIN_MADRASAH/GURU; operasi mutasi tetap dilindungi Gate/policy dan TeacherAccess. Guru dibatasi penugasan mapel/rombel; Admin tidak otomatis mendapat kewenangan menerbitkan ujian.
- QuestionBank memeriksa question.view saat masuk; operasi tulis melalui QuestionPolicy dan penugasan Guru. Hak Admin untuk membuat soal tidak diberikan otomatis.
- ProctorAssignmentManagement membatasi Admin Madrasah + exam.proctor.assign dan memeriksa tenant pada query target/service.
- UserManagement membatasi Admin Madrasah + user.manage; pembuatan akun hanya GURU/SISWA, konsisten dengan pemisahan akun operasional yang tidak boleh dibuat sembarang dari layar ini.
- Hasil pemeriksaan source belum menggantikan pengujian integrasi/multi-tenant runtime.

## DESAIN
- Navigasi harus mengikuti otorisasi aktual komponen. Menyembunyikan link yang pasti ditolak bukan pengganti pemeriksaan server-side.
- Tidak memperluas role/permission hanya demi membuat menu tampak aktif.
- Tidak mengubah skema DB atau menjalankan migration.

## IMPLEMENTASI
- Mempertahankan tiga perbaikan terdahulu pada CurriculumManagement, AcademicManagement search, dan visibilitas menu User Management.
- Perubahan baru: kondisi menu Data Akademik & Rombel kini hanya tampil bagi ADMIN_MADRASAH dengan permission academic.manage.
- Commit terbaru: `58cf081e36b0c018feda8de024e1d010b58e6208`.

## PENGUJIAN
- Pemeriksaan source dan konsistensi kondisi menu vs authorize() dilakukan.
- Belum ada workflow run GitHub Actions yang terdeteksi untuk commit terbaru (fetch_commit_workflow_runs mengembalikan daftar kosong).
- Belum menjalankan PHPUnit, browser test, atau runtime test pada perubahan terbaru. Status: BELUM LULUS UJI.

## TERTUNDA
- Audit mendalam seluruh view/form/tombol dan controller ekspor/hasil, termasuk pagination/filter, empty state, error state, serta permission per aksi.
- Tambah regression tests untuk mismatch menu/authorization dan skenario tenant.
- Jalankan CI dan verifikasi hasil.
- Deployment menunggu review/merge PR dan backup VPS yang disetujui secara eksplisit. Jangan timpa .env, DB, storage privat, atau installer state.


## Audit lanjutan — visibilitas aksi dan navigasi hasil
- Ditemukan tombol Publikasikan/Tarik Publikasi pada halaman hasil tampil tanpa memperhatikan policy `publishResults`, padahal ExamPolicy hanya mengizinkan Guru pemilik asesmen yang memiliki permission dan penugasan aktif. Admin Madrasah/Kamad dapat melihat halaman hasil tetapi tombol tersebut akan ditolak server.
- Ditemukan tombol Finalisasi Masal juga tampil untuk semua pembaca hasil, meskipun kebijakan finalisasi hanya mengizinkan Admin Madrasah atau Guru dengan permission `exam.grade` dan batas penugasan terkait.
- Link Bank Soal dan Kurikulum sebelumnya hanya memeriksa role, padahal mount komponen memerlukan permission `question.view` dan `curriculum.view`.
- Implementasi: navigasi Bank Soal dan Kurikulum kini memeriksa permission view; tombol publikasi memakai Blade policy `@can('publishResults', $exam)`; tombol finalisasi hanya ditampilkan untuk Admin Madrasah/Guru dengan `exam.grade`. Server-side authorization yang ada tetap menjadi otoritas.
- Commit: `93e1af2357b1ec4fdd3db50373f79c0fe122a03c` (layout) dan `899e5d442d20ab571ef8a3ccec800d2e63d56aa6` (hasil ujian).
- Tes otomatis/runtime untuk perubahan ini belum dijalankan; status tetap BELUM LULUS UJI.


## Audit Bank Soal — validasi kunci jawaban
- Ditemukan validasi Pilihan Ganda yang menerima `correctOption=C/D` walaupun opsi C/D kosong. Untuk Pilihan Ganda Kompleks, daftar kunci dapat menunjuk ke opsi yang kosong atau berisi key tidak dikenal/duplikat.
- Dampak: soal dapat tersimpan dengan kunci yang tidak punya opsi pasangan, sehingga jawaban siswa berpotensi tidak dapat dipilih atau dinilai sesuai kunci.
- Implementasi: validasi sekarang memastikan kunci PG menunjuk opsi terisi; PG Kompleks memvalidasi array, nilai hanya A-D, elemen unik, dan setiap kunci menunjuk opsi terisi.
- Commit: `cbb727191879734fa6207acb3abe81ad133593a5`.
- Belum ada PHPUnit/Livewire runtime test untuk kasus valid/invalid ini; status BELUM LULUS UJI.


## Audit integritas snapshot ujian — 2026-11-20
### AUDIT
- `ExamSnapshotService::createSnapshots()` mengunci Exam dan QuestionVersion serta menolak perubahan snapshot setelah attempt/answer/score history ada.
- Ditemukan celah validasi pada service: pemanggil Livewire memvalidasi tenant dan subject sebelum transaksi, tetapi service sendiri hanya memastikan `QuestionVersion.tenant_id` sama dengan tenant ujian. Service dapat dipanggil dari jalur lain atau menerima perubahan kondisi di antara validasi awal dan transaksi.
- Karena snapshot menjadi sumber soal ujian yang immutable, pemeriksaan subject harus dilakukan kembali di dalam transaksi setelah Exam dikunci.

### DESAIN
- Subject resmi ujian diambil dari Assessment yang terhubung ke Exam yang terkunci.
- Setiap QuestionVersion yang akan disnapshot harus berasal dari tenant yang sama dan Question dengan subject_id yang sama dengan Assessment.
- Gagal tertutup jika Assessment tidak memiliki subject; tidak mengubah skema database.

### IMPLEMENTASI
- `ExamSnapshotService.php`: menambahkan validasi subject di dalam transaksi dan query QuestionVersion dengan `whereHas('question')` yang mensyaratkan tenant serta subject ujian.
- Commit: `0ff72ab11fff907d9e70e4b0077e486a26f9212d`.

### PENGUJIAN
- Source diff diperiksa setelah perubahan.
- PHPUnit/Livewire/DB concurrency test belum dijalankan. CI run untuk commit baru belum diverifikasi.
- Status: BELUM LULUS UJI; jangan merge/deploy berdasarkan patch ini saja.

### TERTUNDA
- Tambahkan regression test: versi soal dari subject berbeda pada tenant yang sama harus ditolak; versi soal dari tenant berbeda harus ditolak; subject yang sesuai harus berhasil; kegagalan tidak boleh meninggalkan snapshot parsial.
- Uji race antara snapshot edit dan publish/lock.
- Verifikasi constraint unik DB dan urutan sequence snapshot dari migration aktual.


## Audit lanjutan — semantik peserta saat edit ujian (2026-11-20)

### AUDIT
- Pemeriksaan ulang `ExamManagement::openEditModal()`, `saveExam()`, model `ExamParticipant`, migration `create_exam_participants_table`, dan form Blade menemukan bahwa saat edit draf, `selectedRombelId` sengaja dikosongkan.
- Jika operator memilih rombel pada mode edit, implementasi menggunakan `firstOrCreate` untuk menambah peserta yang belum terdaftar. Peserta lama tidak dihapus atau diganti.
- Ini adalah perilaku additive yang aman terhadap data peserta, tetapi label form sebelumnya masih berbunyi “Rombel Target Peserta”, sehingga berpotensi disalahartikan sebagai penggantian seluruh daftar peserta.
- Tidak ada kolom sumber rombel pada `exam_participants`; karena itu sistem saat ini tidak dapat merekonstruksi satu rombel asal dari seluruh peserta ujian secara andal. Tidak dilakukan sinkronisasi/detach otomatis.

### DESAIN
- Pertahankan perilaku additive yang tidak menghapus peserta lama.
- Bedakan label pemilihan rombel pada mode buat dan mode edit.
- Jelaskan secara eksplisit bahwa edit hanya menambahkan peserta yang belum ada; tidak mengganti/menghapus roster yang sudah tersimpan.
- Pengelolaan penghapusan/pergantian peserta perlu desain terpisah dengan guard attempt/answer/score dan audit log, bukan disisipkan ke alur edit umum.

### IMPLEMENTASI
- Backup view sebelum perubahan dibuat di:
  `backend-laravel/backup_before_patch/WEB_PARTICIPANT_EDIT_SEMANTICS_20261120/exam-management.blade.php.before`
- View `exam-management.blade.php` diperbarui dengan label “Tambahkan Peserta dari Rombel” dan keterangan semantik additive saat edit; mode buat tetap menampilkan label target rombel.
- Backup commit: `1d3343b2d082ba2db70d65ff435eb0482c616f8c`.
- Patch commit: `fca6d222ac7f42b75923ab705a0054aa01273dca`.

### PENGUJIAN
- Source diff diperiksa pada saat patch dibuat.
- PHPUnit/Livewire/browser test belum dijalankan; CI commit baru belum diverifikasi.
- Status: BELUM LULUS UJI.

### TERTUNDA
- Tambahkan regression test UI untuk memastikan pesan additive muncul saat edit dan label target muncul saat create.
- Lanjutkan audit seluruh fitur dan aksi per role, termasuk filter rombel pada hasil ujian dan root landing Kamad (tanpa membuat portal permanen yang bertentangan dengan keputusan arsitektur).
- Uji permission, tenant isolation, dan alur peserta dengan runtime disposable sebelum PR dapat dinaikkan dari Draft.


## Audit & implementasi — filter rombel pada hasil ujian (2026-11-20)

### AUDIT
- `ResultList` sudah memiliki state `rombelId`, tetapi tidak ada selector pada Blade dan query hasil belum memakai state tersebut.
- Relasi data yang sah tersedia melalui `ExamAttempt -> participant -> user -> studentProfile -> rombels`, dengan pivot `rombel_student` yang membawa `tenant_id`.
- Model `StudentProfile` dan `Rombel` menggunakan `TenantScoped`; kedua arah relasi rombel juga menambahkan filter pivot tenant dan gagal tertutup jika tenant context tidak konsisten.
- Filter harus menampilkan hanya rombel yang benar-benar memiliki siswa peserta ujian terkait, bukan semua rombel tenant atau rombel lintas tenant.

### DESAIN
- Opsi rombel diturunkan dari peserta ujian yang sudah ada, melalui relasi model resmi.
- Query attempt difilter melalui relasi student profile ke rombels; tidak memakai raw join yang mengabaikan tenant.
- Perubahan filter mengembalikan pagination ke halaman pertama.
- Tidak mengubah data peserta, schema, migration, atau policy akses halaman hasil.

### IMPLEMENTASI
- Backup sebelum perubahan:
  - `backend-laravel/backup_before_patch/RESULT_ROMBEL_FILTER_20261120/ResultList.php.before`
  - `backend-laravel/backup_before_patch/RESULT_ROMBEL_FILTER_20261120/result-list.blade.php.before`
- `ResultList.php`: opsi rombel dibatasi pada rombel yang memiliki peserta ujian; query attempt sekarang memakai filter `participant.user.studentProfile.rombels`; perubahan search/status/grading status/rombel reset pagination.
- `result-list.blade.php`: menambahkan selector “Semua Rombel” beserta opsi rombel terkait ujian.
- Commit implementasi:
  - PHP: `310cb65dbe9a451aa58e87a5a4d63e3b3f49dafb`
  - Blade: `4b46e15140a3da002ee27ad4c7b019ea81ae587c`

### PENGUJIAN
- Relasi model dan tenant guard diperiksa dari source; patch disimpan ke branch GitHub.
- PHPUnit/Livewire runtime test belum dijalankan.
- Belum ada bukti CI hijau untuk commit ini.
- Status: BELUM LULUS UJI.

### TERTUNDA
- Tambahkan regression tests: filter rombel hanya menampilkan attempt siswa dari rombel terpilih; siswa multi-rombel tampil pada masing-masing filter yang sesuai; rombel tenant lain tidak dapat dipilih/digunakan; filter kosong menampilkan semua peserta ujian; pagination reset.
- Jalankan CI pada branch PR dan perbaiki kegagalan yang ditemukan.
- Audit root landing Kamad dan seluruh halaman analitik/ekspor per role; jangan menambah portal Proktor permanen atau memperluas permission tanpa dasar.


## Regression test tambahan — integritas subject snapshot dan kunci opsi
Tanggal: 2026-11-20

### AUDIT
- Pengetatan ExamSnapshotService pada commit 0ff72ab11fff907d9e70e4b0077e486a26f9212d mengharuskan setiap QuestionVersion berasal dari tenant dan subject yang sama dengan Assessment ujian.
- Pemeriksaan ulang fixture ExamSnapshotTest.php menemukan beberapa fixture lama membuat Question tanpa subject_id. Fixture tersebut tidak lagi mewakili data valid setelah invariant subject diterapkan dan berpotensi membuat tes snapshot lama gagal bukan karena regresi layanan.
- Tes QuestionBankTest.php sebelumnya mencakup PG Kompleks tanpa kunci, tetapi belum menguji kunci yang menunjuk opsi kosong untuk PG maupun PG Kompleks.
- Audit ekspor: ReportController::exportExcel() dan exportPdf() memanggil policy viewExamResults; filter rombel Guru diperiksa terhadap penugasan aktif. Ekspor PDF hasil siswa memakai viewStudentResults dan validasi lingkup penugasan Guru. AnalyticsDashboard dan ItemAnalysis juga memanggil viewExamResults pada mount.
- KamadDashboard memang ada sebagai komponen, tetapi tidak memiliki route aktif. Test RoleDashboardVerificationTest secara eksplisit mengunci keputusan bahwa Kamad tidak mempunyai dashboard permanen terpisah (/kamad/dashboard harus 404); tidak diaktifkan tanpa dasar keputusan yang lebih baru.

### DESAIN
- Fixture snapshot harus selalu menyertakan tenant dan subject yang sesuai dengan Assessment.
- Tambahkan regresi untuk menolak soal satu tenant tetapi subject berbeda, dan pastikan tidak ada snapshot parsial yang tersimpan.
- Tambahkan regresi validasi PG dan PG Kompleks yang menolak kunci pada opsi kosong.
- Tidak mengubah skema database, route portal, atau keputusan role.

### IMPLEMENTASI
- Backup sebelum perubahan:
  - backend-laravel/backup_before_patch/WEB_SNAPSHOT_SUBJECT_SCOPE_20261120/ExamSnapshotTest.php.before
  - backend-laravel/backup_before_patch/WEB_QUESTION_OPTION_VALIDATION_20261120/QuestionBankTest.php.before
- ExamSnapshotTest.php: menyimpan subject ujian pada fixture; menambahkan test penolakan soal same-tenant dengan subject berbeda; memperbaiki fixture soal lama termasuk fixture tenant lain dan helper mutasi agar memenuhi invariant subject.
- QuestionBankTest.php: menambahkan test PG dan PG Kompleks untuk menolak correct key yang menunjuk opsi kosong.
- Commit test snapshot: 7e782d484a5a54393175534ef95996d09d439d8a dan 71f63a9b4679c5767e6d18e895b7d0b570f4acee.
- Commit test opsi: 28697bca9d7ec235ec7acac8246e0cfb5677adb9.
- Skrip .github/workflows/build-web-deployment.yml mengecualikan backup_before_patch/ dari ZIP deployment. Backup tetap ada di branch untuk audit, tetapi tidak masuk paket deploy.

### PENGUJIAN
- Diff dan fixture test diperiksa melalui GitHub.
- Belum menjalankan PHPUnit atau workflow; tidak menyatakan test lulus.
- Tidak ada workflow run atau status check terdeteksi pada head PR terakhir saat checkpoint ini disusun.
- Workflow tenant-backend-ci.yml saat ini memicu pull_request hanya untuk base branch main, sedangkan PR #5 menggunakan base fix/p0-96g-installer-private-db-config-20261120. Workflow juga menyediakan workflow_dispatch, tetapi pemicu manual belum dijalankan. Ini menjelaskan mengapa tidak muncul CI otomatis pada PR #5; bukan bukti bahwa kode lulus.

### STATUS PR DAN PEKERJAAN TERTUNDA
- PR #5 tetap Draft, belum merge. Pemeriksaan terbaru GitHub melaporkan mergeable=true, merged=false.
- Lanjutkan audit seluruh aksi UI, alur edit/publish/participant, filter hasil, analytics, export, dan keamanan tenant.
- Jalankan PHPUnit pada MySQL 8 disposable melalui workflow yang tepat; review hasil lengkap termasuk test yang sudah ada, bukan hanya test baru.
- Setelah seluruh audit dan tes lulus, review diff akhir, perbarui checkpoint, dan siapkan keputusan merge secara terpisah.
- Deployment VPS tetap belum dilakukan. Tidak ada perubahan database, .env, storage privat, installer state, atau vhost.


## Audit lanjutan hasil, rombel, dan ruang lingkup akademik
Tanggal: 2026-11-20

### AUDIT
- Ditemukan opsi filter status penilaian NOT_SCORED pada halaman hasil, tetapi query ResultList sebelumnya selalu memakai whereHas('score'). Attempt yang belum memiliki record skor tidak mungkin lolos filter NOT_SCORED.
- Dropdown rombel ResultList sebelumnya tidak membatasi tahun ajaran dan query filter rombel juga belum menyatakan tenant/tahun ajaran secara eksplisit, berbeda dengan ExamResultReportService yang membatasi tenant dan tahun ajaran asesmen.
- ExamManagement sebelumnya memvalidasi selectedSubjectId hanya dengan exists:subjects,id; pemeriksaan semester sudah tenant-scoped. Untuk Admin Madrasah, validasi eksplisit subject tenant tidak terlihat pada jalur itu.
- Pemeriksaan target rombel di saveExam hanya memastikan rombel ada pada tenant, belum memastikan rombel berasal dari tahun ajaran aktif. Dropdown rombel juga mengambil seluruh tahun pada tenant.

### DESAIN
- NOT_SCORED harus berarti attempt tanpa current score relation, sehingga query memakai whereDoesntHave('score').
- Filter dan opsi rombel harus konsisten dengan tenant dan tahun ajaran yang terkait pada Assessment ujian.
- Pembuatan/pengubahan ujian harus menolak subject dari tenant lain dan rombel dari tahun ajaran selain tahun aktif; pilihan UI harus mengikuti batasan yang sama.
- Tidak mengubah schema atau migrasi.

### IMPLEMENTASI
- Backup ResultList.php: backend-laravel/backup_before_patch/WEB_RESULT_FILTER_CONSISTENCY_20261120/ResultList.php.before
- ResultList.php: status NOT_SCORED sekarang menggunakan whereDoesntHave('score'); pilihan rombel dan filter rombel dibatasi tenant serta tahun ajaran Assessment.
- Backup ExamManagement.php: backend-laravel/backup_before_patch/WEB_EXAM_ACADEMIC_SCOPE_20261120/ExamManagement.php.before
- ExamManagement.php: subject harus ditemukan pada tenant aktif; target rombel harus berasal dari tahun ajaran aktif; dropdown rombel dibatasi tahun ajaran aktif.
- Backup ResultWorkflowTest.php: backend-laravel/backup_before_patch/WEB_RESULT_NOT_SCORED_TEST_20261120/ResultWorkflowTest.php.before
- ResultWorkflowTest.php: menambah regression test attempt tanpa skor harus muncul pada filter NOT_SCORED.
- Commit implementasi ResultList: 8d29f26c3a854a7c185614bbfcd8cdc94c389061.
- Commit implementasi ExamManagement: e267fcac1593673613094801a0b1bbe4a8b02a7d.
- Commit test NOT_SCORED: f5140ba85f737c43a3b1645997ed952bbc88d494.

### PENGUJIAN
- Pemeriksaan source/diff dilakukan setelah perubahan.
- PHPUnit/Livewire belum dijalankan; status belum lulus uji.
- Head PR saat diperiksa: mergeable=true, draft=true, merged=false. Tidak ada status check pada commit implementasi terakhir.

### TERTUNDA
- Tambahkan/selesaikan regression coverage untuk batas tenant subject dan tahun ajaran rombel pada ExamManagement.
- Jalankan test suite lengkap pada MySQL 8 disposable. Pastikan fixture ExamSnapshotTest dan test baru lolos.
- Lanjutkan audit seluruh UI aksi, role/permission, tenant isolation, laporan, analytics, dan sinkronisasi sebelum menyatakan Web selesai.


## Handover untuk agent penerus
- Prompt handover lengkap telah dibuat di docs/HANDOVER_PROMPT_AGENT_BARU_WEB_CBT_20261120.md.
- Dokumen memuat arsitektur/keputusan yang tidak boleh berubah, batas keselamatan, status PR/CI, temuan audit, file yang harus dibaca, pekerjaan tertunda, status VPS, instruksi Android Studio, dan deliverables.
- Commit prompt handover: dcaedd1a176675192d525db6a8aedb2b87762445.
- Head branch setelah pembuatan dokumen perlu diverifikasi ulang sebelum handover final.


## Audit pagination Manajemen Ujian
### AUDIT
- ExamManagement memiliki filter judul dan status. Hook updatingSearch sebelumnya mereset pagination, tetapi perubahan statusFilter tidak mereset halaman. Jika pengguna berada di halaman yang tinggi lalu memilih status dengan jumlah halaman lebih sedikit, tampilan dapat kosong meskipun data tersedia.
### DESAIN
- Reset pagination saat statusFilter berubah, mengikuti pola filter lain.
### IMPLEMENTASI
- Backup: backend-laravel/backup_before_patch/WEB_EXAM_STATUS_PAGINATION_20261120/ExamManagement.php.before
- ExamManagement.php: menambahkan updatingStatusFilter(): void yang memanggil resetPage().
- Commit: 5a46c0914533589706653b3b2f3aaad245b2d535.
### PENGUJIAN
- Source diff diperiksa; PHPUnit/Livewire belum dijalankan.


## Verifikasi metadata akhir sesi 20 November 2026
- PR #5: OPEN, Draft, merged=false, mergeable=true pada pemeriksaan GitHub terakhir.
- Head branch terverifikasi: 099b839de3dcb4c46c0751e70ab17626650c1343.
- PR berisi 39 commits dan 23 changed files pada pemeriksaan terakhir.
- Tidak ada workflow run pull_request dan tidak ada combined status checks pada head terakhir. Status tes tetap BELUM LULUS / BELUM DIJALANKAN.
- Prompt handover agent baru tersedia di docs/HANDOVER_PROMPT_AGENT_BARU_WEB_CBT_20261120.md dan sudah ditambahkan addendum pagination.
- Deployment VPS, ekstraksi ZIP, migration, dan perubahan database tetap BELUM dilakukan.


## CI branch trigger diperbaiki dan run pertama
### AUDIT
- tenant-backend-ci.yml sebelumnya hanya memicu pull_request ke main dan push ke dua branch lama. PR #5 memakai base lain, sehingga event pull_request tidak memicu workflow.
### DESAIN
- Tambahkan branch audit Web pada daftar push trigger agar setiap perubahan branch menjalankan workflow yang memakai MySQL 8 disposable, validasi identitas DB fail-closed, smoke login/tenant, dan seluruh PHPUnit suite.
### IMPLEMENTASI
- Backup workflow: backend-laravel/backup_before_patch/WEB_CI_TRIGGER_20261120/tenant-backend-ci.yml.before
- Workflow .github/workflows/tenant-backend-ci.yml menambahkan fix/web-portal-usability-rbac-20261120 ke daftar push branches.
- Commit backup: edbb229ebf479c40345c36a10528757f14b1d947.
- Commit workflow: 7d9e0fbe92a8305daea56fc7c633705ea8d01a5b.
### PENGUJIAN
- GitHub Actions run #620, ID 44732146599, event push, head 7d9e0fbe92a8305daea56fc7c633705ea8d01a5b.
- Saat checkpoint ini diperbarui: run masih in_progress pada job laravel-mysql; container MySQL sedang inisialisasi. Belum ada hasil PHPUnit. Pantau run sampai conclusion terminal dan periksa job steps/log.
- Link: https://github.com/zane0184/cbt_november/actions/runs/44732146599
- Workflow DB bersifat disposable (MySQL 8 container) dan tidak mengarah ke VPS.

## Status PR terkini
- PR #5 tetap OPEN, Draft, unmerged, mergeable=true.
- Head commit sebelum checkpoint update ini: 7d9e0fbe92a8305daea56fc7c633705ea8d01a5b.
- CI workflow trigger sekarang diperbaiki; setiap push berikutnya pada branch audit memicu tes. Pull_request trigger masih terbatas main, sehingga PR check status dapat berbeda dengan push run.
- VPS, DB produksi, .env, storage privat, installer state, vhost belum disentuh.


---

# ADDENDUM AUDIT ROLE & PERMISSION — 20 NOVEMBER 2026 (LANJUTAN)

## AUDIT AKTUAL
- Branch kerja: `fix/web-portal-usability-rbac-20261120`.
- Commit terakhir yang sudah diverifikasi dari GitHub: `de222d2e0fb986cde189233b7b0feadaaf0c70e7` (penghapusan hook pagination duplikat; hook existing tetap dipertahankan).
- PR #5 tetap OPEN, DRAFT, UNMERGED, UNDEPLOYED.
- CI run #676 / run ID `44745729601`, commit `641b87d67712b432e2c3cf2ff2a5f1922b2ffc79`, gagal sebelum PHPUnit berjalan karena patch menambahkan `updatingSearch()` duplikat. Patch duplikat telah dihapus pada commit setelahnya.
- CI run terbaru untuk commit `de222d2e0fb986cde189233b7b0feadaaf0c70e7`, run ID `44745926681`, job ID `136321086285`, status terakhir saat addendum dibuat: IN_PROGRESS. Hasil terminal wajib diambil sebelum perubahan source selanjutnya.
- Dua tes participant sebelumnya gagal karena konteks tenant dibersihkan sebelum User Siswa dibuat. Tes telah diperbaiki dengan mengatur TenantContext secara eksplisit sebelum pembuatan user dan membersihkannya sebelum request.
- Tes pagination sebelumnya menguji properti `p` yang tidak tersedia. Assertion diubah ke `page`; wajib diverifikasi hasil CI, jangan anggap benar sebelum tes lulus.
- Koreksi audit: komponen QuestionBank SEBENARNYA sudah memiliki hook `updatingSearch`, `updatingSubjectFilter`, dan `updatingStatusFilter` pada bagian bawah file. Patch percobaan yang menambahkan duplikat telah dihapus. Jangan mengulang patch hook tersebut.
- RolePermissionBootstrapper saat ini memberi grant least-privilege secara additive dan mencabut beberapa stale grants secara selektif. Audit menemukan risiko permission legacy lain yang tidak termasuk daftar revoked tetap tertinggal. Ini belum diperbaiki. Evaluasi dan uji semua built-in permissions untuk Admin Madrasah, Kamad, Guru, Operator, dan Proktor; jangan melakukan revoke tanpa matriks yang benar.
- Policy yang sudah diperiksa: QuestionPolicy, ExamPolicy, ReportPolicy, ExamAttemptPolicy. Temuan: QuestionPolicy viewAny bergantung pada tenant_id + question.view; komponen QuestionBank mount menambahkan role Admin/Guru. ExamAttemptPolicy view membatasi detail hasil kepada Guru assigned + report.view atau Admin/Kamad + report.view; Operator/Siswa ditolak. Siswa harus melalui hasil pribadi dengan current score pointer dan DTO terbatas.
- RolePermissionBootstrapper grants: Admin Madrasah (administrasi, monitoring, close, proctor assignment, token/grade/reopen sesuai policy; tidak question.manage), Kamad (curriculum.view, exam.monitor, report.view), Guru (question/exam authoring sesuai assignment, grading, report), Operator (curriculum.view, academic.manage, exam.monitor, report.view), Proktor bukan role portal permanen dan akses monitoring bergantung penugasan aktif.
- RoleDashboardVerificationTest dan RolePermissionBootstrapperTest tersedia. RBACEnforcementTest, StudentResultVisibilityTest, ProctorAssignmentSecurityTest juga tersedia. Namun coverage halaman/aksi detail hasil per matriks role/permission masih belum lengkap.

## IMPLEMENTASI TERAKHIR DAN BACKUP
- Backup dibuat sebelum patch:
  - `backend-laravel/backup_before_patch/20261120_RBAC_DEEP_AUDIT/tests/Feature/ExamBuilderTest.php.before`
  - `backend-laravel/backup_before_patch/20261120_RBAC_DEEP_AUDIT/tests/Feature/QuestionBankTest.php.before`
  - `backend-laravel/backup_before_patch/20261120_RBAC_DEEP_AUDIT/app/Livewire/Admin/QuestionBank.php.before`
- Backup dokumentasi:
  - `docs/backup_before_patch/20261120_RBAC_DEEP_AUDIT/CHECKPOINT_WEB_AUDIT_20261120.md.before`
  - `docs/backup_before_patch/20261120_RBAC_DEEP_AUDIT/HANDOVER_PROMPT_AGENT_BARU_WEB_CBT_20261120.md.before`
- Commit perubahan tes konteks tenant: `c46f10d39a160f1a7ff41d183214df9253c2c391`.
- Commit assertion pagination: `641b87d67712b432e2c3cf2ff2a5f1922b2ffc79`.
- Commit hapus hook duplikat: `de222d2e0fb986cde189233b7b0feadaaf0c70e7`.
- Commit backup sumber dan docs dicatat pada riwayat branch; jangan hapus folder backup.

## PENGUJIAN
- Run #673 sebelumnya: 477 passed, 3 failed; dua fixture tanpa tenant context, satu assertion pagination salah. Ini hasil sebelum patch lanjutan.
- Run #676 gagal pada package discovery karena duplicate method; bukan hasil PHPUnit. Patch duplicate telah dihapus.
- Run #677 / ID `44745926681`: status IN_PROGRESS saat dokumen ini disusun. Belum boleh dinyatakan lulus.
- Semua test harus berjalan di MySQL 8 disposable CI; tidak ada pengujian diarahkan ke DB VPS.

## PEKERJAAN WAJIB AGENT PENERUS
1. Poll run #677 sampai terminal, ambil log lengkap dan hitung hasil PHPUnit. Jangan push saat run aktif karena concurrency cancel-in-progress.
2. Jika CI gagal, klasifikasikan kegagalan dan backup file sebelum patch. Jangan melemahkan assertion tenant/RBAC untuk membuat CI hijau.
3. Audit dan perbaiki RolePermissionBootstrapper agar stale built-in permissions tidak tertinggal; buat tes dengan pre-existing grants untuk seluruh role. Pertahankan custom permission di luar daftar built-in jika memang desainnya demikian. Cocokkan dengan role matrix resmi sebelum commit.
4. Tambahkan matriks regression untuk ResultDetail: Admin tanpa/dengan report.view, Kamad tanpa/dengan report.view, Guru tanpa/dengan assignment, Operator, Siswa, tenant lain. Uji URL langsung dan aksi finalize/reopen/grade.
5. Audit route/controller/policy/component/view untuk Question Bank, Exam Builder, result, report, analytics, export, monitoring, participant assignment, proctor assignment, user/academic/curriculum management. Periksa setiap GET/POST/API dan aksi Livewire.
6. Audit stale permission dan multi-role accounts; tentukan apakah satu akun boleh memiliki lebih dari satu role. Jangan biarkan gabungan role memperluas hak secara tidak disengaja.
7. Periksa Super Admin secara terpisah: platform-level access tidak boleh masuk ke tenant CBT tanpa jalur otorisasi eksplisit.
8. Lanjutkan tenant isolation tests dan cross-tenant direct route/API tests; pastikan query/export/cache tidak bocor lintas tenant.
9. Setelah test focused dan full suite lulus, jalankan browser/runtime smoke pada staging disposable, bukan production. Audit visual menu/landing role dan URL langsung.
10. Update checkpoint dan handover setelah setiap tahap, dengan SHA aktual, file berubah, backup, CI run, hasil test, dan sisa gap.

## BATAS LINGKUP DAN KESELAMATAN
- Fokus saat ini HANYA Web CBT dan backend Laravel.
- Pengembangan/pengujian aplikasi Android ditunda sampai JUNI 2027. Jangan meminta persiapan perangkat Android saat fase Web/backend.
- PR #5 tetap Draft/unmerged/undeployed sampai semua gerbang lulus.
- Tidak ada deployment VPS, migration produksi, perubahan DB/.env/storage/vhost, atau ZIP distribusi final tanpa prosedur dan persetujuan yang diwajibkan.
- Jangan menyatakan sistem aman/siap ujian sebelum role matrix, tenant isolation, alur ujian, penilaian, pemulihan, dan validasi madrasah memiliki bukti pengujian.


---

# CHECKPOINT LANJUTAN — 20 NOVEMBER 2026 — CI #684 BERHASIL

## AUDIT
- Branch: `fix/web-portal-usability-rbac-20261120`.
- Head commit terverifikasi: `9e48a719668eecd80e03682d33fa2ba978231d37` — `test: verify question paginator resets through rendered paginator`.
- PR #5: OPEN, DRAFT, UNMERGED, UNDEPLOYED; mergeable=true. Base `fix/p0-96g-installer-private-db-config-20261120`.
- Rincian RolePermissionBootstrapper diperiksa ulang pada head ini. SHA sumber `fac82573de84c52b52867afbbf7e1637164835b4`; test SHA `cfacac702f967667c8e1e4f6dff18000fc105f68`.
- Temuan belum selesai: bootstrap grants secara additive dengan `syncWithoutDetaching` dan hanya revoke daftar tertentu. Test saat ini menguji daftar permission role yang diharapkan setelah memberi beberapa stale grant, tetapi tidak membuktikan seluruh permission legacy/built-in yang mungkin sudah ada telah ditangani. Jangan mengubah detach/revoke sebelum inventaris permission dari migrations/seeders/pemakaian kode dan matriks role disusun. Custom permission perlu dipertahankan kecuali kebijakan eksplisit menyatakan sebaliknya.
- Test RolePermissionBootstrapperTest membuat role ADMIN_MADRASAH, KAMAD, GURU, optional OPERATOR; belum menguji PROKTOR sebagai role existing dengan stale grant. Revoke PROKTOR saat ini hanya mencakup tujuh nama permission.
- Detail ResultDetail role/permission regression matrix masih belum lengkap. Audit route/controller/policy/UI dan kurikulum lintas alur juga belum tuntas.

## DESAIN
- Pagination filter Question Bank diuji melalui paginator yang dirender (currentPage), bukan property Livewire internal `page` yang dapat null. Pertahankan assertion yang menguji perilaku tampilan, jangan kembali mengasumsikan property internal.
- PR tetap draft hingga seluruh audit role/action/tenant dan runtime staging selesai. Tidak ada perubahan desain produk/portal.

## IMPLEMENTASI
- Patch terakhir hanya mengubah `backend-laravel/tests/Feature/QuestionBankTest.php` agar memeriksa current page dari paginator hasil render setelah filter.
- Backup sebelum patch: `backend-laravel/backup_before_patch/WEB_QUESTION_BANK_PAGINATION_VIEW_ASSERTION_20261120/QuestionBankTest.php.before`.
- Commit patch: `9e48a719668eecd80e03682d33fa2ba978231d37`.
- Tidak ada patch source aplikasi pada commit ini; tidak ada perubahan VPS/DB produksi.

## PENGUJIAN
- GitHub Actions run #684, ID `44748706279`, job `laravel-mysql`, job ID `136330660780`: COMPLETED / SUCCESS.
- Semua langkah penting sukses: identitas DB disposable fail-closed, Composer lock/platform validation, dependency install, Laravel package discovery, pembuatan disposable installer E2E DB, focused Web login/tenant identity smoke tests, full PHPUnit pada MySQL 8 disposable.
- Log full PHPUnit: **480 passed, 1.942 assertions, 89,88 detik**. Tidak ada test gagal.
- MySQL container CI tercatat versi 8.0.46. Ini bukan DB VPS.
- Link run: https://github.com/zane0184/cbt_november/actions/runs/44748706279
- Catatan: workflow run ini adalah push event pada branch audit. PR #5 tetap Draft dan belum merge.

## PEKERJAAN TERTUNDA / URUTAN AGENT BERIKUTNYA
1. Inventaris seluruh permission dari migrations, seeders, RolePermissionBootstrapper, Gate/Policy, middleware, route, komponen Livewire, controller, Blade, dan test. Klasifikasikan built-in vs custom. Buat matriks permission × role resmi sebelum mengubah bootstrapper.
2. Tambah test bootstrap dengan permission stale pre-existing untuk semua role termasuk PROKTOR; pastikan stale built-in dicabut, grant resmi tetap, custom permission ditangani sesuai kebijakan yang diputuskan. Jangan lakukan blanket detach.
3. Lengkapi ResultDetail/ExamAttemptPolicy matrix: Admin/Kamad tanpa-dengan report.view; Guru tanpa assignment/dengan assignment; Operator; Proktor hanya penugasan yang relevan; Siswa hanya hasil sendiri melalui DTO; cross-tenant; uji URL langsung dan tombol grade/finalize/reopen.
4. Audit Exam Builder lifecycle, participant assignment/removal, token, schedule/server time, close/publish, snapshot, tenant/year/subject, race/idempotency dan audit log.
5. Audit Question Bank enam tipe soal, media, CP/TP, import/export, versioning/immutability, validasi opsi/kunci, kebocoran jawaban.
6. Audit laporan/analytics/export/cache, role, tenant, rombel, tahun ajaran, penugasan Guru, finalized/current score pointer.
7. Audit seluruh halaman dan aksi Web (User/Academic/Curriculum/Question/Exam/Results/Monitoring/Proctor), serta integrasi Kurikulum Merdeka, Panca Cinta, Pembelajaran Mendalam di seluruh alur/role.
8. Jalankan focused tests dan full CI tiap patch; jangan push selama workflow aktif karena concurrency dapat membatalkan run. Backup sebelum perubahan source.
9. Setelah audit source dan tests memadai, jalankan browser/runtime smoke di staging disposable, bukan VPS produksi.
10. Perbarui checkpoint/handover lagi dengan hasil dan SHA aktual.

## BATAS KESELAMATAN
- Fokus hanya Web CBT/backend Laravel. Android ditunda sampai Juni 2027.
- PR #5 tetap Draft/open/unmerged/undeployed.
- Dilarang deployment VPS, migration produksi, ekstraksi ZIP, overwrite DB/.env/storage/vhost tanpa rencana, backup, preflight dan persetujuan eksplisit.
- Jangan nyatakan production-ready atau siap ujian berdasarkan CI saja.


---

# ADDENDUM AUDIT LANJUTAN — 20 NOVEMBER 2026 — CI #686

## AUDIT
- CI #686 / run ID 44749489282: COMPLETED SUCCESS. Job laravel-mysql ID 136333362885; full suite 480 passed, 1.942 assertions, 61,09 detik pada MySQL 8.0.46 disposable. https://github.com/zane0184/cbt_november/actions/runs/44749489282
- Migration role_user many-to-many (migration SHA 18602aca8a15e4d75edec415fd012ea16f8289dd) memungkinkan satu user memiliki lebih dari satu role.
- User.php SHA 74dd080f703d7ef3256b406176e53dad5786a937: hasRole mengecek koleksi role; hasPermission menggabungkan permission semua role (roles->flatMap->permissions). Akun multi-role menggabungkan hak seluruh role.
- AuthWebController.php SHA 1f76179dbbf304eb11271b74fbe1d1d38c74a401: login mengizinkan jika setidaknya satu role allowlist; redirectForUser memprioritaskan ADMIN/OPERATOR, KAMAD, PROKTOR, GURU, SISWA. Tidak ada pemeriksaan tepat satu role.
- UserManagement.php SHA 5f90e3873cadeb77a1bcd5a6aa2134953e60beed membuat role Guru/Siswa dengan sync tunggal, tetapi tidak membuktikan jalur lain/akun existing bebas multi-role.
- DatabaseSeeder.php SHA bb9a55dd16ac7b7e56b79dc8f5ccaca689416868 menciptakan tenant.view, tenant.manage, user.view, executive.dashboard selain permission lain. RolePermissionBootstrapper tidak memasukkan empat permission ini ke map reconcile. Telusuri semua referensi sebelum menyimpulkan unused; jangan hapus/revoke spekulatif.
- RolePermissionBootstrapper SHA fac82573de84c52b52867afbbf7e1637164835b4 tetap additive syncWithoutDetaching + selective detach. Test SHA cfacac702f967667c8e1e4f6dff18000fc105f68 belum mencakup semua stale grants dan belum membuat role PROKTOR.
- Migration mendefinisikan role tenant permanen ADMIN_MADRASAH, OPERATOR, KAMAD, GURU, SISWA; PROKTOR tidak tercantum sebagai role tenant permanen.
- AssessmentPolicy SHA 79a0367c8c83723ae3cc9e581fb8dda8a6e2d02a dan ExamPolicy SHA 350c1ddc6bed8738d1af17cacd6c95bc367b7c29: viewAny beberapa role tidak mensyaratkan permission eksplisit; pastikan list query tetap tenant/assignment scoped.
- ReportPolicy SHA e53be3994c52d067c1e31fe37edc450c24a0f4b6: Guru butuh report.view + assignment untuk hasil ujian; Admin/Kamad report.view; self-view student diperbolehkan. Subject aggregate dibatasi Admin/Kamad. Controller query/export dan ResultDetail matrix belum tuntas.
- routes/web.php SHA c22fed7d54871bf4971acf626514fc02c4a68ce9: beberapa route memakai auth+tenant+license dan mengandalkan authorization di mount/method Livewire. Audit seluruh mutation methods.

## DESAIN
- Multi-role berpotensi privilege aggregation. Keputusan proyek memisahkan portal; Proktor penugasan sementara. Belum ada keputusan eksplisit mengizinkan akun multi-role permanen. Susun cardinality matrix sebelum mengubah model/pivot; pertimbangkan role utama tunggal + penugasan Proktor terpisah dan Super Admin tetap platform-level. Jangan patch sebelum impact analysis installer/seeder/auth/proctor.

## IMPLEMENTASI
- Tidak ada perubahan source aplikasi, migration, atau DB produksi pada audit ini.

## PENGUJIAN
- CI #686: 480 passed / 1.942 assertions, disposable MySQL 8.0.46. CI hijau tidak membuktikan seluruh matrix role tercakup.

## TUGAS AGENT PENERUS
1. Code-reference inventory tenant.view, tenant.manage, user.view, executive.dashboard dan semua permission; klasifikasikan active/legacy/reserved.
2. Audit multi-role: login, redirect precedence, permission union, create/edit, installer, seeder, proctor assignment. Terapkan invariant hanya setelah matriks dan regression tests.
3. Isolasi Super Admin dari tenant portal; Proktor hanya monitoring exam dengan assignment aktif, tanpa hak global/authoring.
4. Lengkapi stale permission tests seluruh role termasuk PROKTOR, tanpa blanket detach custom grants.
5. Lengkapi ResultDetail/report/export/analytics role matrix dan query scope; audit viewAny/list endpoints.
6. Backup sebelum patch; CI disposable; update checkpoint/handover. Tidak ada deployment/migration produksi.

## BATASAN
- PR #5 tetap Draft/open/unmerged/undeployed. Fokus Web/backend; Android ditunda sampai Juni 2027. Tidak ada perubahan VPS/DB/.env/storage/installer-state/vhost.
