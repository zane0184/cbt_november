# AUDIT & IMPLEMENTASI — WEB GURU FOUNDATION
Tanggal: 22 November 2026
Repository: zane0184/cbt_november
Branch: work/web-guru-foundation-20261122
Baseline: 81b6c74d81d80bc483530802594f624fb4e0f3db
HEAD saat laporan: 55e8557c30f427db97624b8c4837e8dc3c9d8784

## 1. AUDIT

### Baseline dan isolasi branch
- Branch baru dibuat dari commit 81b6c74d, snapshot terakhir yang memiliki bukti CI sukses pada run 44957719985.
- HEAD Agent Pertama saat audit adalah 42a3ed3a; terdapat perubahan lanjutan Stage 4B/4C yang belum memiliki bukti CI terbaru pada SHA tersebut.
- Perubahan lanjutan yang belum diverifikasi tidak dibawa ke branch ini.
- Branch Web Guru terpisah; tidak ada merge, rebase, pull request, deployment, atau perubahan VPS.

### Komponen existing yang digunakan
- TeacherProfile, TeacherSubjectAssignment, TeacherAccess.
- AcademicYear, Semester, Rombel, GradeLevel, Phase, Subject, StudentProfile.
- MultiCurriculum, MultiCurriculumVersion, MultiCurriculumStructure, MultiLearningOutcome, TenantCurriculumRegistration.
- AuditService.

Tidak dibuat ulang model penugasan Guru dan tidak ditambahkan migration.

### Ketergantungan dan batas data
- Skema akun baseline tidak menyediakan kolom status aktif/nonaktif tersendiri; UI menyatakan sesi terautentikasi dan menjelaskan batas ini.
- Skema CP baseline belum menyimpan metadata provenance sumber resmi. CP tersimpan tidak dinyatakan resmi terverifikasi.
- Struktur mengenal DIMENSI_CINTA dan outcome KBC_CINTA; hubungan rinci ke lima unsur Panca Cinta belum dapat dipastikan dari data.
- Referensi khusus Pembelajaran Mendalam dan delapan dimensi profil lulusan belum ditemukan pada baseline.
- Agenda/jadwal mengajar belum memiliki sumber model yang dapat dipakai; UI menampilkan empty state.
- Menu Bank Soal, Asesmen, Ujian, Proktor, Monitoring, Penilaian, dan Hasil/Laporan belum dihubungkan dari portal baru. Sebelum tahap berikutnya, audit dan gunakan ulang halaman Guru existing bila tersedia; jangan membangun duplikat.

### Risiko konflik dengan Agent Pertama/Kedua
- Hanya dua file bersama yang diubah untuk integrasi: routes/web.php dan AuthWebController.php.
- Kedua file tersebut memiliki backup sebelum perubahan.
- Tidak mengubah resources/views/layouts/app.blade.php, middleware alias bersama, policy bersama, model akademik, migration, atau workflow CI existing.
- Layout Guru dibuat terpisah pada resources/views/layouts/teacher.blade.php.
- Route Guru diisolasi pada routes/teacher.php; file route utama hanya mendaftarkan file tersebut dan mengubah redirect Guru.

## 2. DESAIN

- Prefix portal: /guru.
- Route: teacher.dashboard, teacher.learning, teacher.curriculum, teacher.profile.
- Portal hanya menerima tepat satu role GURU; role Admin atau multi-role ditolak.
- Tenant berasal dari user terautentikasi dan konteks server, bukan parameter browser.
- Profil Guru harus tepat satu dan berada pada tenant user.
- Assignment dibatasi oleh teacher profile, tenant, relasi mapel/rombel/tahun/semester, serta konsistensi tenant dan tahun akademik.
- Assignment ACTIVE tetapi belum mulai atau sudah berakhir tidak dihitung sebagai aktif.
- Semester tidak dipilih otomatis. Jika Admin belum menetapkan semester, UI menampilkan Belum Ditetapkan.
- Guru hanya dapat mengubah nomor kontak pada fondasi ini. Email, nama, NIP/NIK/NUPTK, role, tenant, status, dan assignment read-only.
- Perubahan nomor kontak dicatat melalui AuditService.
- Curriculum context hanya membaca versi yang terdaftar untuk tenant dan memeriksa kesesuaian owner versi/kurikulum.
- UI menyesuaikan preferensi Light/Dark sistem dan menyediakan navigasi desktop/mobile.

## 3. IMPLEMENTASI

### File baru
- backend-laravel/app/Support/Security/TeacherPortalAccess.php
- backend-laravel/app/Http/Middleware/EnsureTeacherPortalAccess.php
- backend-laravel/app/Livewire/Teacher/TeacherPortalComponent.php
- backend-laravel/app/Livewire/Teacher/TeacherDashboard.php
- backend-laravel/app/Livewire/Teacher/TeacherProfilePage.php
- backend-laravel/app/Livewire/Teacher/TeacherLearning.php
- backend-laravel/app/Services/TeacherPortal/TeacherPortalDataService.php
- backend-laravel/app/Services/TeacherPortal/TeacherCurriculumContextService.php
- backend-laravel/routes/teacher.php
- backend-laravel/resources/views/layouts/teacher.blade.php
- backend-laravel/resources/views/livewire/teacher/portal-dashboard.blade.php
- backend-laravel/resources/views/livewire/teacher/portal-profile.blade.php
- backend-laravel/resources/views/livewire/teacher/portal-learning.blade.php
- backend-laravel/tests/Feature/TeacherPortalFoundationTest.php
- .github/workflows/web-guru-foundation-ci.yml

### File existing yang diubah
- backend-laravel/routes/web.php
- backend-laravel/app/Http/Controllers/AuthWebController.php

### Fitur nyata
- Dashboard mengambil identitas, periode akademik, mapel, rombel, siswa, assignment aktif, asesmen/ujian terkait, dan versi kurikulum dari query backend.
- Halaman profil menampilkan identitas Guru dan hanya menyediakan perubahan nomor kontak yang diaudit.
- Halaman pembelajaran menampilkan assignment aktif maupun riwayat dengan status/periode, fase/kelas, mapel, rombel, siswa, dan konteks kurikulum.
- CP/TP/ATP/KBC ditampilkan hanya dari versi yang terdaftar; provenance CP dinyatakan belum terverifikasi.
- Agenda, Pembelajaran Mendalam, dan delapan dimensi tidak diisi data contoh.
- Tidak ada migration atau perubahan schema.
- Tidak ada source Web Admin yang diubah selain integrasi route/auth bersama yang dicatat di atas.

## 4. PENGUJIAN

### CI terisolasi
Workflow khusus menjalankan PHP 8.3, Composer v2, dan MySQL 8.0 disposable. Database dibuat unik per run, diverifikasi kosong dan terisolasi sebelum PHPUnit, serta seluruh tabel hasil migration diperiksa memakai InnoDB.

Run 44982652810 pada SHA af92f58d:
- Composer install dan platform requirements: PASS.
- PHP lint: PASS.
- Route registration /guru: PASS.
- PHPUnit TeacherPortalFoundationTest: step PASS, 7 test cases, 35 assertions, 7 warnings.
- Verifikasi engine InnoDB: PASS.
- Cleanup disposable DB: FAIL pada versi workflow lama karena terminator heredoc tidak sejajar. Ini kegagalan workflow cleanup, bukan kegagalan assertion atau test aplikasi.

Perbaikan heredoc/YAML sudah masuk ke branch. Run 44982777697 pada SHA 20012d216cdef9b80a47dc344e505125755e24f9 selesai dengan seluruh 16 langkah PASS, termasuk cleanup disposable DB. PHPUnit step PASS dengan 7 test cases dan 35 assertions; terdapat 7 warnings yang masih perlu diperiksa. Workflow saat ini menggunakan --display-warnings. Run 44982836419 pada SHA 55e8557c30f427db97624b8c4837e8dc3c9d8784 masih berjalan dan mencakup test tambahan assignment kedaluwarsa. Jangan menyatakan HEAD terbaru lulus sebelum run ini selesai.

### Pengujian yang belum dilakukan
- Browser visual test desktop/tablet/mobile.
- Audit akses seluruh Livewire action dan hydration dengan pengujian adversarial tambahan.
- Audit seluruh halaman Guru existing (Bank Soal/Ujian/Asesmen/Proktor/Hasil) sebelum menghubungkannya ke menu baru.
- Uji manual Light/Dark pada browser nyata.
- Validasi referensi CP resmi, Pembelajaran Mendalam, dan delapan dimensi dari sumber resmi.
- Uji beban dan query profiling dengan data madrasah skala besar.

## 5. BACKUP

Manifest: docs/backup/web-guru-foundation-20261122/MANIFEST.md

Backup:
- docs/backup/web-guru-foundation-20261122/routes-web.php.before
- docs/backup/web-guru-foundation-20261122/AuthWebController.php.before

Manifest mencatat blob SHA sumber, ukuran byte, lokasi backup, dan blob SHA backup. Backup dibuat sebelum kedua file bersama diubah.

## 6. STATUS

WEB GURU FOUNDATION: PARSIAL — implementasi awal tersedia pada branch terisolasi; gate verifikasi akhir masih menunggu hasil CI terbaru dan audit reuse halaman Guru existing.

Tidak siap produksi. Tidak di-merge. Tidak di-deploy. Database produksi tidak disentuh.

Langkah berikutnya:
1. Tunggu dan periksa CI terbaru pada HEAD branch.
2. Tinjau warning PHPUnit dengan output --display-warnings.
3. Jika CI bersih, lakukan audit keamanan lanjutan dan browser visual test.
4. Audit halaman Guru existing dan hubungkan menu ke route existing yang aman, tanpa duplikasi.
5. Perbarui checkpoint setelah hasil tersebut benar-benar tersedia.


## 7. AUDIT LANJUTAN — 22 NOVEMBER 2026

### A. Posisi source dan bukti CI
- HEAD branch aktual sebelum pembaruan audit ini: `7c8be7668bd039b56680f030a2f1778021d75eb1`.
- Commit source test terbaru pada saat checkpoint: `9afc0a0fd489227a67310edec67223457fe42139`; commit setelahnya hanya memperbarui manifest backup.
- Run `44982836419` telah diperiksa dari job log: checkout memakai SHA `55e8557c30f427db97624b8c4837e8dc3c9d8784`; perintah `git log -1` di runner menghasilkan SHA yang sama.
- Run `44982777697` dan `44982836419`: job selesai SUCCESS pada SHA masing-masing. Bukti dipertahankan; tidak berarti siap produksi.
- Run `44983508195` pada SHA `7d6d80cacc72def49fbe63731537dae773fa055f`: job PHPUnit dan cleanup database disposable SUCCESS setelah penghapusan predicate relasi duplikat.
- Run `44983639700` pada SHA `9afc0a0fd489227a67310edec67223457fe42139`: menunggu/queued ketika checkpoint ini ditulis. Run ini mencakup tiga test lifecycle tambahan.
- Run `44983510471` dibatalkan oleh antrean/concurrency ketika commit baru masuk; tidak dihitung sebagai hasil pengujian.
- Tidak ada perubahan pada branch Agent 1 atau Agent 2, merge, rebase, PR, deployment, VPS, Android, Web Siswa, atau database produksi.

### B. Warning PHPUnit dan perbaikan
- Pada run `44982836419`, delapan test menghasilkan warning yang sama: pembacaan `.env` yang tidak ada melalui `vlucas/phpdotenv`, dipanggil saat bootstrap Laravel di `tests/TestCase.php:12`. Jumlahnya delapan karena run terbaru memuat delapan test method.
- Output juga menunjukkan deprecation PHPUnit 11 untuk metadata test dalam doc-comment pada sejumlah test legacy; ini terpisah dari warning `.env` dan belum diperbaiki.
- Workflow isolasi diperbarui agar membuat file `.env` kosong hanya selama langkah PHPUnit dan menghapusnya melalui shell trap. Output warning tetap ditampilkan dengan `--display-warnings`; tidak ada suppression.
- Hasil perbaikan warning harus menunggu run `44983639700`; belum dinyatakan selesai.

### C. Audit query penugasan dan membership
- Predicate `whereHas('rombel.gradeLevel.phase')` duplikat di `TeacherPortalDataService::assignments()` telah dihapus.
- Assignment query tetap memvalidasi tenant pada assignment, teacher profile, subject, rombel, grade level, phase, academic year, dan semester; konsistensi tahun ajaran rombel dan semester terhadap assignment juga diperiksa.
- Skema pivot `rombel_student` tidak memiliki kolom academic-year atau status membership. Tahun ajaran dimiliki oleh `rombels`. Karena itu hitungan siswa dibatasi ke ID rombel yang berasal dari assignment terverifikasi; query juga membatasi tenant pada pivot, profil siswa dan rombel, mengecualikan profil soft-deleted dan rombel nonaktif, serta memakai distinct student profile.
- Hitungan total lintas rombel memakai distinct profile agar siswa yang tercatat pada lebih dari satu rombel terpilih tidak terhitung ganda.
- Pengujian multi-tahun dan edge-case keanggotaan lintas rombel masih perlu diperluas. Tidak ada perubahan schema/migration.

### D. Audit asesmen dan reuse fitur Guru existing
Inventaris dari source branch:

| Fitur | Web / Livewire | API / Controller | Policy / batas akses yang ditemukan | Keputusan menu |
|---|---|---|---|---|
| Bank Soal | `GET /admin/questions`, `App\\Livewire\\Admin\\QuestionBank` | `/api/v1/questions`, QuestionController | QuestionPolicy; permission `question.view` untuk baca, `question.manage` untuk authoring, TeacherAccess untuk mapel/tenant | Existing, tetapi tautan portal tetap ditahan sampai audit strict multi-role selesai |
| Asesmen | Terintegrasi dalam `GET /admin/exams` / ExamManagement; belum ditemukan halaman asesmen web terpisah | `/api/v1/assessments`, AssessmentController | AssessmentPolicy; Guru perlu `exam.manage`, tenant, penugasan aktif; update/delete juga creator | Reuse, bukan modul baru; belum ditautkan |
| Ujian | `GET /admin/exams`, `App\\Livewire\\Admin\\ExamManagement` | `/api/v1/exams`, ExamController | ExamPolicy; permission exam.manage/question.manage/exam.assign/exam.token sesuai aksi, assignment dan peserta harus dalam scope | Reuse, bukan modul baru; belum ditautkan |
| Proktor | `GET /admin/proctor-assignments`, ProctorAssignmentManagement (Admin) | ExamProctorAssignmentService dan API terkait | Admin + `exam.proctor.assign`; Guru dipilih sebagai penerima penugasan sementara | Tidak ada portal Proktor permanen; tidak dibuat menu Guru |
| Monitoring | `GET /exams/{exam}/monitoring`; tautan per ujian pada ExamManagement | `/api/v1/exams/{exam}/monitoring/*`, MonitoringController | ExamPolicy::monitor; permission `exam.monitor`, tenant, penulis ujian atau penugasan Proktor sementara yang memenuhi syarat | Akses per ujian; bukan menu global |
| Penilaian | `/exams/{exam}/results/{attempt}`, ResultDetail | ScoringController: score/recalculate/grading/finalize/reopen | ExamAttemptPolicy; `exam.grade`, tenant, assessment dan cakupan assignment | Existing, akses per attempt; belum ditautkan sebagai menu global |
| Hasil & Laporan | `/exams/{exam}/results`, ResultList | ReportController: results, summary, rombel/student report | ReportPolicy, `report.view`, TeacherAccess, scope subject/year/semester/rombel | Existing, akses per ujian; belum ditautkan |
| Analytics | `/exams/{exam}/analytics`, AnalyticsDashboard; `/analytics/items`, ItemAnalysis | AnalyticsController dan Analytics services | `viewExamResults` / `viewStudentResults` / `viewSubjectResults`, ReportPolicy dan TeacherAccess | Existing, akses per ujian; belum ditautkan |
| Ekspor | Route web export ujian/siswa | ReportController exportExcel/exportPdf/exportStudentPdf/exportSummaryPdf | Policy laporan dan scope tenant/rombel/siswa | Existing, akses per ujian; belum ditautkan |

- Komponen Bank Soal dan ExamManagement berada pada namespace `Admin`, tetapi guard komponennya memang mengizinkan Guru dengan permission yang sesuai.
- Pemeriksaan saat ini menemukan guard legacy tersebut mengecek `hasRole('GURU')` dan menolak `ADMIN_MADRASAH`, tetapi belum secara eksplisit menolak setiap kombinasi multi-role lain seperti GURU+SISWA atau GURU+KAMAD. Policy terkait juga perlu diperiksa untuk konsistensi exact-role.
- Karena itu menu portal Guru saat ini sengaja menampilkan status “Audit akses”, bukan tautan aktif ke route legacy. Ini mencegah integrasi UI sebelum gate kewenangan lolos.
- Tidak dibuat fitur Bank Soal, Asesmen, Ujian, Proktor, Monitoring, Penilaian, Laporan, Analytics, atau Ekspor duplikat.

### E. Lifecycle access dan profil
- Portal baru memakai `TeacherPortalAccess`: role database dibaca ulang melalui query relation, user harus masih ada dan tidak soft-deleted, tenant context harus sama, dan tepat satu TeacherProfile tenant-local wajib ada.
- Test yang telah ada: direct URL Guru, Admin ditolak, multi-role Guru+Admin ditolak, Guru tanpa profil unik ditolak, tenant lain tidak tampil, nomor kontak saja dapat diubah dan audit log tercatat.
- Ditambahkan test untuk role dicabut saat session user object masih dipakai, akun soft-deleted dengan session lama, dan tenant context mismatch pada Livewire. Menunggu run `44983639700`.
- Test manipulasi profil saat ini memastikan NIP/email tidak berubah pada penyimpanan nomor kontak. Belum ada test browser adversarial untuk mencoba payload Livewire di luar properti publik.
- Audit stale permission untuk komponen legacy Bank Soal/Ujian masih terbuka. Menu belum ditautkan karena guard multi-role belum lolos audit.

### F. Kurikulum
- Status CP tetap “belum terverifikasi sumber resmi”; tidak ada klaim provenance baru.
- KBC tetap ditampilkan dari data yang tersedia; mapping rinci Panca Cinta tidak diasumsikan.
- Pembelajaran Mendalam dan delapan dimensi Profil Lulusan tetap dinyatakan belum tersedia/terverifikasi. Tidak ada referensi yang dikarang.

### G. Uji browser yang disiapkan
- Belum ada browser E2E/visual test yang dijalankan dalam tahap ini.
- Matriks uji yang disiapkan: desktop 1440×900 dan 1024×768; tablet 768×1024; mobile 390×844 dan 320×640; masing-masing Light dan Dark.
- Cakupan: sidebar desktop, menu mobile, overflow tabel/kartu, form profil dan validasi, empty state, navigasi, fokus keyboard, kontras, tema sistem, serta akses langsung ke modul existing.
- PHPUnit/CI tidak dianggap sebagai bukti browser E2E.

### H. Implementasi tahap audit
- Duplikasi predicate tenant relation di TeacherPortalDataService dihapus.
- Workflow CI menyiapkan file `.env` kosong hanya selama PHPUnit agar tidak menimbulkan warning file hilang; output warning tetap aktif.
- Test lifecycle role revoke, soft-delete, dan tenant mismatch ditambahkan.
- Menu Guru tidak mengaktifkan link ke modul legacy sebelum audit strict multi-role selesai.
- Backup semua file yang disentuh ditambahkan ke manifest.

## 8. STATUS SETELAH AUDIT LANJUTAN

PARSIAl — reuse fitur telah diinventaris dan query duplikat dibersihkan; gate CI terbaru dan test lifecycle tambahan masih menunggu hasil. Menu modul legacy tetap tidak ditautkan sampai multi-role/stale-permission audit selesai. Tidak siap produksi.
