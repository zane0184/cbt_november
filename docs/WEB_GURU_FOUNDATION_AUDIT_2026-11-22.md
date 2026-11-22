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

Perbaikan heredoc/YAML sudah masuk ke branch. Workflow saat ini menggunakan --display-warnings agar rincian warning dapat diperiksa. Run pada HEAD terbaru masih pending/in progress ketika checkpoint ini dibuat; jangan menyatakan seluruh CI terbaru PASS sebelum run tersebut selesai.

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
