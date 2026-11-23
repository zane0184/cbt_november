# Laporan W1 — Hardening Reauthorization Livewire Admin dan Guru

Tanggal: 24 November 2026 (WIB)  
Repository: `zane0184/cbt_november`  
Branch: `work/solo-web-cbt-completion-20261123-execution`  
Source HEAD ketika laporan disusun: `413fc1f017e4d515a9f373fa99683a521cf31f17`

## Ringkasan

Tahap ini melanjutkan hardening batas otorisasi Livewire yang belum konsisten. Fokusnya bukan mengulang remediasi Full PHPUnit yang sudah ditutup sebelumnya, melainkan menutup reauthorization pada komponen Admin dan halaman hasil/analitik Guru yang mengandalkan otorisasi mount tanpa re-check yang konsisten pada setiap render/request.

Tidak ada migration, perubahan database proyek, akses VPS, deployment, PR merge, Android build, atau perubahan Web Super Admin.

## AUDIT

### Keputusan arsitektur yang dipertahankan

- Fokus sampai Juni 2027: Backend + Web Admin Madrasah + Web Guru + Web Siswa.
- Android dan Web Super Admin deferred.
- Satu instalasi dan satu database akademik per madrasah. `tenant_id` tetap defense-in-depth di dalam instalasi.
- Role aktif hanya `ADMIN_MADRASAH`, `GURU`, `SISWA`. Kamad/Operator bukan portal atau jalur otorisasi aktif. Proktor bukan role permanen.
- Guru dapat mengelola dan menerbitkan asesmen/ujian sesuai assignment resmi.
- Admin tidak mengelola ujian reguler, soal, peserta, token, Attempt, jawaban, monitoring peserta, atau nilai. Admin tidak memperoleh kewenangan Guru lewat komponen/menu yang secara historis ada di namespace Admin.
- Kurikulum Merdeka + KBC/Panca Cinta satu konteks terintegrasi; Pembelajaran Mendalam dan 8 dimensi hanya memakai referensi/provenance resmi yang tersedia.

### Temuan Admin

Dari source branch execution, komponen berikut belum mengulang otorisasi pada entrypoint render/Livewire berikutnya secara konsisten:
- `Admin/Dashboard.php`: awalnya hanya mount authorization.
- `Admin/UserManagement.php`: perlu re-check di render.
- `Admin/AcademicManagement.php`: perlu re-check role, tenant dan permission di render.
- `Admin/CurriculumManagement.php`: perlu re-check role, tenant dan permission di render.
- `Admin/ProctorAssignmentManagement.php`: callback `updatedSelectedExamId()` adalah entrypoint Livewire dan harus memeriksa hak akses kembali.

Komponen Admin lain yang sudah memiliki pemeriksaan berulang tidak diubah.

### Temuan Guru

Empat halaman yang menampilkan hasil atau analitik memiliki authorization pada mount tetapi tidak mengulang policy pada render:
- `Teacher/ResultList.php`
- `Teacher/ResultDetail.php`
- `Teacher/AnalyticsDashboard.php`
- `Teacher/ItemAnalysis.php`

Ini berisiko setelah persisted role/permission/assignment dicabut sementara halaman masih terbuka.

### Web Siswa / media

Pemeriksaan source terkini menunjukkan runner telah memakai IndexedDB stores `attempt_context`, `answers`, `sync_queue`, `server_receipts`, `conflicts`, `local_meta`; receipt diverifikasi terhadap request ID, Attempt, snapshot, revision, payload hash dan waktu server. Submit memiliki barrier untuk pending/conflict dan recovery status Attempt yang tidak pasti. Ini bukti implementasi source, bukan browser E2E.

MediaController memeriksa tenant, Attempt aktif milik peserta, auth-session binding, current lease/fence serta keterikatan snapshot media pada `question_order`. Uji browser dan runtime lintas browser masih belum dilakukan.

### Blocker produk yang tetap berlaku

- Penugasan Proktor pusat tetap fail-closed sampai canonical central-exam eligibility marker ditetapkan. Jangan menganggap `assessment_origin=EXTERNAL` sebagai ujian pusat.
- Menu/capability Proktor Guru dan monitoring Room-scoped belum selesai; jangan membuka akses monitoring/room/roster/Attempt berdasarkan assignment saja.
- DOCX merupakan jalur utama import soal setelah kontrak backend disahkan. XLSX question import lama tetap disabled; jangan menghidupkannya kembali.
- SHORT_ANSWER tetap BLOCKED sampai accepted answers mengalir benar dari authoring → QuestionVersion → immutable ExamSnapshot → ScoringService dengan tes E2E. ESSAY tetap manual review.
- Dataset resmi Pembelajaran Mendalam dan 8 dimensi profil lulusan belum dipastikan; jangan membuat nilai/label resmi fiktif.
- PR #18 tetap HOLD karena desain room/session/seat/roster/monitoring tidak sesuai keputusan model ujian serentak dan batas kewenangan yang baru. Jangan merge/migration.

## DESAIN

Prinsip patch:
1. Authorization dilakukan ulang pada render setiap request Livewire, bukan hanya saat mount.
2. Gunakan policy/permission yang telah berlaku pada komponen; tidak membuat role atau permission baru.
3. Callback property hook yang masuk sebagai request Livewire wajib menjalankan gate lagi.
4. Query, business rules, scoring, dan data akademik tidak diubah.
5. Regression tests mencabut persisted role/permission sesudah komponen dimount, lalu memastikan request Livewire berikutnya ditolak.
6. Tidak menghapus source legacy Kamad/Operator/Proktor; hanya portal aktif dan permissions yang dibatasi sesuai keputusan. Penghapusan file sejarah tidak dibutuhkan untuk patch ini.

## IMPLEMENTASI

### Admin — patch reauthorization

File source:
- `backend-laravel/app/Livewire/Admin/Dashboard.php`
- `backend-laravel/app/Livewire/Admin/UserManagement.php`
- `backend-laravel/app/Livewire/Admin/AcademicManagement.php`
- `backend-laravel/app/Livewire/Admin/CurriculumManagement.php`
- `backend-laravel/app/Livewire/Admin/ProctorAssignmentManagement.php`

Perubahan:
- Dashboard memakai helper otorisasi yang dipanggil dari mount dan render.
- UserManagement memanggil `authorizeUserManagement()` pada render.
- AcademicManagement memvalidasi lagi role tepat, tenant dan permission pada render.
- CurriculumManagement memakai helper `authorizeCurriculumView()` dari mount dan render.
- Callback `updatedSelectedExamId()` pada komponen Proktor Admin memanggil `assertCanManage()` sebelum membaca Exam tenant-local.

Regression:
- `backend-laravel/tests/Feature/AdminLivewireReauthorizationTest.php` — 5 tests.

Backup byte-exact:
- `backend-laravel/docs/backup/livewire-admin-render-reauth-2026-11-24/Dashboard.php.before` — source blob `43624f70f78675e899f2b122318ed8e6382ffa74`.
- `.../UserManagement.php.before` — `29f31e68f2272b1ea870415f3635b0ad8bc88b8e`.
- `.../AcademicManagement.php.before` — `63652d47345962c1cd49e08420956453765e9373`.
- `.../CurriculumManagement.php.before` — `992bb7c96981b781bf89240ca68d0440f06b32bc`.
- `.../ProctorAssignmentManagement.php.before` — `107e8bac028a161f8c4f85862b5670b7ec32da22`.
- `.../tenant-backend-ci.yml.before` — `4bc80531b4ee4a4bda4c47a053deefa1e8ae1a27`.
- Backup paths were verified content-identical to source sebelum patch.

### Guru — patch reauthorization

File source:
- `backend-laravel/app/Livewire/Teacher/ResultList.php`: `authorize('viewExamResults', $this->exam)` saat render.
- `backend-laravel/app/Livewire/Teacher/ResultDetail.php`: `authorize('view', $this->attempt)` saat render.
- `backend-laravel/app/Livewire/Teacher/AnalyticsDashboard.php`: `authorize('viewExamResults', $this->exam)` saat render.
- `backend-laravel/app/Livewire/Teacher/ItemAnalysis.php`: `authorize('viewExamResults', $this->exam)` saat render.
- Regression: `backend-laravel/tests/Feature/TeacherResultLivewireReauthorizationTest.php` — 4 tests.

Backup byte-exact:
- `backend-laravel/docs/backup/teacher-result-render-reauth-2026-11-24/ResultList.php.before` — `fd60539cb33bd33d0c183b980a84f567681a4d68`.
- `.../ResultDetail.php.before` — `6f959b61eb04ca9d0ee8005009a5e82b3b98a016`.
- `.../AnalyticsDashboard.php.before` — `90cabf8c0b61775ac79ede8a6d2d6e5417dfa336`.
- `.../ItemAnalysis.php.before` — `b82649219c0c32310b1ce4ea84a4a21d1fb071b9`.
- Semua backup source diverifikasi identik.

### Fix Blade yang ditemukan saat focused regression

Test Guru menemukan syntax error pada view existing:
`backend-laravel/resources/views/livewire/teacher/result-detail.blade.php`

Directive `@error('manualScore')` memiliki penutup `@error` yang salah; diperbaiki menjadi `@enderror`.

Backup:
- `backend-laravel/docs/backup/teacher-result-render-reauth-2026-11-24/result-detail.blade.php.before` — original blob `c72c6b95703a105225a084ffab8344a83f9ec6dc`.
- Backup diverifikasi identik dengan source sebelum edit.

## PENGUJIAN

### Full PHPUnit sebelumnya yang sudah tertutup

Run CI #45251932629, exact SHA `b4592f8a93fd4d306cdab5105c244ba9c6a37eb0`:
- Focused login/tenant smoke: SUCCESS.
- Focused Admin reauthorization: **5 passed (5 assertions)**.
- Full PHPUnit disposable MySQL 8/InnoDB: **523 passed (2,182 assertions), 0 failed**.
- Run: https://github.com/zane0184/cbt_november/actions/runs/45251932629

Ini bukti Admin patch pada SHA tersebut, bukan otomatis bukti source Guru/Blade berikutnya.

### CI Guru sebelum fix Blade

Run #45254047440 pada SHA `1c0b72260eb0c19d36fb48fddd092bc58a649e53`:
- Focused login/tenant: SUCCESS.
- Focused Admin: SUCCESS (5/5).
- Focused Teacher: 2 passed, 2 failed karena Blade compile error pada `result-detail.blade.php`.
- Full suite dilewati otomatis karena focused step gagal.
- Run: https://github.com/zane0184/cbt_november/actions/runs/45254047440
- Ini run sebelum fix Blade, tidak digunakan sebagai PASS.

### Source final + CI terkini

- Source dengan fix Blade: SHA `cbce0df331784ecfbef50c4da29c3e781a03d7d7`.
- Matriks ADM diperbarui pada commit `413fc1f017e4d515a9f373fa99683a521cf31f17`.
- CI #45254396061 untuk SHA cbce0df dibatalkan karena workflow `cancel-in-progress` setelah dokumentasi matriks berubah; test belum dijalankan pada SHA itu.
- CI terbaru #45254547688 pada exact HEAD `413fc1f017e4d515a9f373fa99683a521cf31f17` masih **QUEUED / IN PROGRESS** saat laporan disusun. Tahap focused tests 12–15 belum dimulai. Jangan mengklaim patch Guru telah lulus sampai run ini selesai.
- Link: https://github.com/zane0184/cbt_november/actions/runs/45254547688

## Kesimpulan status

- Admin Livewire reauthorization: **EXISTS & VERIFIED** pada SHA b4592f8 dengan focused tests 5/5 dan full suite 523/2,182; perlu revalidation pada HEAD baru bila test pada run #1646 gagal.
- Guru result/analytics reauthorization: **IMPLEMENTED — NOT YET VERIFIED** pada source final menunggu CI #45254547688. Run sebelumnya menemukan Blade compile bug, yang sudah diperbaiki; jangan anggap test lulus sampai run final membuktikannya.
- ADM-01..ADM-23: tetap NOT VERIFIED secara end-to-end.
- ADM-20 / Proktor central assignment: BLOCKED sampai canonical central exam marker dan otorisasi terkait disetujui.
- Web Siswa: implementasi source relatif lebih lengkap (IndexedDB/receipt/submit barrier/media authorization) tetapi browser E2E, multi-tab/reload/offline QA dan UAT belum dibuktikan.
- Project overall: **IN PROGRESS — NOT READY FOR DISTRIBUTION / NOT READY FOR PRODUCTION**.

## NEXT

1. Periksa status run #45254547688 dan ambil log test; jika focused Teacher lulus, pastikan full suite lulus pada SHA yang sama.
2. Lanjutkan backlog Web Admin W2–W8 berdasarkan ADM matrix, tanpa membuka kewenangan exam admin.
3. Lengkapi Web Guru acceptance; jangan memberi capability monitoring Proktor tanpa kontrak canonical central Exam/Room yang disetujui.
4. Jalankan Web Siswa Browser E2E (mobile/desktop), termasuk IndexedDB/reload/offline/reconnect/receipt/conflict/expiry/submit/media.
5. Integrasi KM+KBC/PM/8 dimensi dengan provenance resmi dan snapshot historis; no fabricated references.
6. DOCX question import hanya setelah kontrak backend batch/DTO selesai; jangan mengaktifkan XLSX question import.
7. Fresh install, upgrade, backup/restore, security, stability, UAT madrasah sebelum distribusi ZIP generik.
8. Tidak ada migration produksi, deployment VPS, PR merge, atau distribusi ZIP tanpa approval dan bukti acceptance lengkap.
