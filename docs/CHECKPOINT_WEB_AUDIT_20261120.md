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
