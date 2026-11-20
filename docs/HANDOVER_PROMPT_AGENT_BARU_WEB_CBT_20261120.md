# PROMPT HANDOVER AGENT BARU — CBT MADRASAH NOVEMBER 2026
Tanggal: 20 November 2026
Repository: https://github.com/zane0184/cbt_november
PR aktif: https://github.com/zane0184/cbt_november/pull/5
Branch kerja: fix/web-portal-usability-rbac-20261120
Base PR: fix/p0-96g-installer-private-db-config-20261120
Head commit ketika dokumen disusun: 65b7400f42a2a3b19c1e1e456c373087180590e4
Status PR terakhir: OPEN, DRAFT, UNMERGED, mergeable=true.
CI PR: BELUM TERVERIFIKASI; tidak ada workflow run/status check yang terdeteksi.

---

## PROMPT UNTUK AGENT PENERUS

Anda adalah coding agent penerus proyek CBT MADRASAH NOVEMBER 2026. Lanjutkan dari source GitHub aktual dan checkpoint. Jangan mulai ulang proyek. Jangan menganggap laporan lama sebagai bukti; verifikasi branch, commit, source, test, dan hasil CI aktual.

### 1. Tujuan
Selesaikan audit dan perbaikan seluruh fungsi Web CBT yang sudah ada terlebih dahulu. Setelah fungsi eksisting benar dan teruji, baru susun gap list fitur wajib yang belum ada. Pisahkan perbaikan eksisting dan penambahan fitur baru.

Setiap laporan wajib memisahkan:
- AUDIT: fakta dari source, konfigurasi, test, atau runtime.
- DESAIN: keputusan/rancangan.
- IMPLEMENTASI: file dan commit yang benar-benar diubah.
- PENGUJIAN: tes yang benar-benar dijalankan, hasil, dan bukti.

Jangan menyatakan PASS, siap produksi, siap deploy, atau selesai tanpa bukti. Source review bukan runtime verification. Test yang belum dijalankan harus ditulis BELUM DIUJI.

### 2. Aturan keselamatan
- ZIP baseline asli CBT REVISI 6 NOPEMBER 2026.zip tidak boleh diubah.
- Workspace rekonstruksi: /mnt/data/CBT_MADRASAH_RECONSTRUCTION/; backend: backend-laravel/; dokumen: docs/.
- Backup utuh setiap file sebelum patch; catat path, ukuran/hash jika tersedia, dan hasil verifikasi.
- Backup source di backend-laravel/backup_before_patch/ jangan dihapus dan jangan dimasukkan ke ZIP deployment. Workflow packaging saat ini mengecualikannya; verifikasi lagi jika workflow berubah.
- Jangan jalankan migration eksperimental di produksi. Jangan drop/reset database, migrate:fresh, db:wipe, seeder demo, atau destructive SQL di produksi.
- Jangan timpa .env, storage privat, installer state, DB, vhost, atau konfigurasi global VPS tanpa rencana, backup, preflight, dan persetujuan spesifik.
- Pengguna meminta pekerjaan dilanjutkan tanpa konfirmasi berulang, tetapi belum memberi persetujuan deployment/backup VPS yang spesifik. Kerjakan audit dan coding GitHub yang aman tanpa bertanya ulang.
- Jangan mengubah PR #4 atau base branch PR #5 tanpa dasar/otorisasi baru. PR #5 tetap Draft sampai audit dan pengujian lengkap; mergeable=true bukan bukti lulus.
- Jangan menjalankan Gradle via terminal. Build Android wajib lewat Android Studio.
- Jangan mengubah layanan/domain lain, PHP 7.4, konfigurasi global VPS, chmod massal, atau chmod 777.
- Jangan menampilkan secret, password, private key, token, isi .env, atau data privat.
- Selalu Bahasa Indonesia.

### 3. Arsitektur dan keputusan produk
- Backend Laravel 13, PHP 8.3, MySQL 8+, Blade + Livewire, modular monolith multi-tenant.
- Android native Kotlin, Jetpack Compose, Room, WorkManager, Retrofit. Android peserta wajib native, bukan browser/PWA. iOS sementara dapat memakai Web.
- Tenant isolation wajib pada query, route binding, service, policy, foreign key, dan laporan. Tidak boleh akses lintas tenant tanpa otorisasi.
- Portal/role: Super Admin, Admin Madrasah, Guru. Proktor hanya penugasan operasional sementara, bukan portal permanen. Kamad punya hak monitoring/laporan sesuai permission; test saat ini secara eksplisit menyatakan route /kamad/dashboard harus 404. Jangan membuat dashboard permanen tanpa keputusan baru.
- Guru mengelola bank soal, membuat/mengatur/menerbitkan ujian sesuai penugasan tanpa approval Admin. Admin Madrasah menangani administrasi, master data, sinkronisasi, monitoring, dukungan teknis.
- Kurikulum Merdeka, Kurikulum Berbasis Cinta/Panca Cinta, Pembelajaran Mendalam harus menyatu dengan akademik, pembelajaran, asesmen, bank soal, ujian, hasil, laporan, dan seluruh role. Pertahankan versi dan histori akademik.
- Token otomatis dibuat saat Guru menerbitkan/mengunci ujian. Token boleh dilihat sebelum jadwal; ujian hanya mulai sesuai jadwal. Waktu server adalah acuan.
- Attempt, Answer, Score, Device Lease, fencing harus konsisten. Offline sync/recovery aman dan idempoten. Device Lease/fencing belum boleh disebut siap produksi sebelum uji integrasi dan multi-device.
- Kamera bukan syarat CBT standar.
- Reset Attempt, Force Submit, takeover perangkat, perpanjangan waktu wajib berotorisasi dan tercatat audit log.
- Jaga transaksi, idempotensi, kompatibilitas, isolasi tenant, dan histori akademik.

### 4. Status GitHub dan PR
PR #5 berjudul [DRAFT] Web CBT — perbaikan alur UI, filter, dan navigasi berbasis permission.
- Head branch: fix/web-portal-usability-rbac-20261120.
- Base: fix/p0-96g-installer-private-db-config-20261120.
- Draft, belum merge, belum deploy.
- Pemeriksaan terakhir: mergeable=true, merged=false.
- Tidak ada status check/workflow run yang terdeteksi.
- Jangan ubah/merge PR #4.

Perbaikan yang telah dibuat pada PR #5:
1. Grouping OR pencarian CP/TP dan tujuan pembelajaran agar tidak melewati filter subject/tenant; reset pagination saat subject filter berubah.
2. Grouping OR pencarian subject di AcademicManagement.
3. Sidebar User Management, Data Akademik & Rombel, Bank Soal, Kurikulum diselaraskan dengan role/permission komponen.
4. Tombol publikasi hasil menggunakan policy publishResults; Finalisasi Masal dibatasi tampilan role/permission; server-side authorization tetap otoritatif.
5. Validasi kunci PG dan PG Kompleks menolak key kosong/tidak dikenal/duplikat.
6. ExamSnapshotService mengunci Exam lalu memeriksa ulang tenant dan subject QuestionVersion terhadap Assessment di dalam transaksi.
7. Edit ujian menjelaskan bahwa pemilihan rombel menambahkan peserta, bukan menghapus peserta lama.
8. Filter rombel pada daftar hasil ditambahkan.
9. Filter NOT_SCORED diperbaiki memakai whereDoesntHave(score), agar attempt tanpa score muncul.
10. Daftar/filter rombel hasil dibatasi tenant dan tahun ajaran Assessment.
11. ExamManagement memeriksa subject milik tenant dan membatasi target rombel/dropdown ke tahun ajaran aktif.
12. Regression tests ditambahkan untuk subject snapshot berbeda, kunci PG/PG Kompleks yang menunjuk opsi kosong, dan filter NOT_SCORED. Fixture ExamSnapshotTest diperbarui agar sesuai invariant subject.

Backup patch ada di backend-laravel/backup_before_patch/. Jangan hapus. Workflow ZIP mengecualikan folder ini; periksa ulang sebelum build ZIP.

### 5. File/checkpoint wajib dibaca dari source aktual
- docs/CHECKPOINT_WEB_AUDIT_20261120.md
- docs/HANDOVER_PROMPT_AGENT_BARU_WEB_CBT_20261120.md
- backend-laravel/app/Livewire/Admin/ExamManagement.php
- backend-laravel/app/Livewire/Admin/QuestionBank.php
- backend-laravel/app/Livewire/Admin/AcademicManagement.php
- backend-laravel/app/Livewire/Admin/CurriculumManagement.php
- backend-laravel/app/Livewire/Teacher/ResultList.php
- backend-laravel/app/Services/ExamSnapshotService.php
- backend-laravel/app/Http/Controllers/Api/V1/ReportController.php
- backend-laravel/app/Services/Reporting/ExamResultReportService.php
- backend-laravel/app/Policies/ReportPolicy.php
- backend-laravel/app/Policies/ExamPolicy.php
- backend-laravel/routes/web.php
- backend-laravel/resources/views/layouts/app.blade.php
- backend-laravel/tests/Feature/ExamSnapshotTest.php
- backend-laravel/tests/Feature/QuestionBankTest.php
- backend-laravel/tests/Feature/ResultWorkflowTest.php
- backend-laravel/tests/Feature/RoleDashboardVerificationTest.php
- .github/workflows/tenant-backend-ci.yml
- .github/workflows/build-web-deployment.yml

Jangan mengandalkan ringkasan ini sebagai pengganti pembacaan source aktual.

### 6. Status pengujian
- Source/diff telah diperiksa melalui GitHub.
- Regression tests telah ditambahkan, tetapi BELUM dijalankan.
- PHPUnit/Livewire/browser test untuk patch PR #5 BELUM dinyatakan lulus.
- tenant-backend-ci.yml saat ini memicu pull_request hanya untuk base main, sedangkan PR #5 memakai base fix/p0-96g-installer-private-db-config-20261120. Workflow menyediakan workflow_dispatch, tetapi belum dipicu melalui koneksi GitHub yang tersedia pada sesi sebelumnya.
- Tidak ada status check pada head terakhir saat checkpoint disusun.
- Jalankan test hanya pada MySQL 8 disposable/test environment dengan identitas DB diverifikasi fail-closed. Jangan arahkan test ke DB VPS.
- Fokus tes minimal: ExamSnapshotTest, QuestionBankTest, ResultWorkflowTest, RBACEnforcementTest, RoleDashboardVerificationTest, TenantIsolationTest, RombelStudentPivotTest, ProctorAssignmentSecurityTest, Analytics Security/ItemAnalysis, kemudian full PHPUnit suite.
- Periksa fixture yang terdampak invariant subject/tenant. Catat kegagalan existing terpisah dari regresi patch.
- Jangan mengarang workflow run ID, status, atau hasil tes.

### 7. Audit yang belum selesai
- Selesaikan audit semua UI dan aksi: daftar/detail/create/edit/delete/archive/publish/unpublish/lock/token/participant assignment/proctor assignment/grading/finalize/reopen/analytics/export/filter/pagination/empty state/error state/permission.
- Audit ExamManagement: edit peserta harus add-only dan tidak menghapus peserta lama; verifikasi transaksi, snapshot, dan audit log.
- Audit ResultList/ResultDetail: policy setiap aksi server-side, current score pointer, NOT_SCORED, rombel, pagination, ekspor, manual review, finalize/reopen.
- Audit QuestionBank untuk semua tipe: MULTIPLE_CHOICE, MULTIPLE_RESPONSE, TRUE_FALSE, MATCHING, SHORT_ANSWER, ESSAY; media, CP/TP, tenant, subject, versioning, published immutability, import/export, answer-key leakage.
- Audit analytics/report: tenant, rombel, tahun ajaran, penugasan Guru, finalisasi, data kosong, cache invalidation, Excel/PDF.
- Audit seluruh role/permission; jangan mengubah kebijakan produk hanya agar menu tampak aktif.
- Verifikasi route/controller/service/policy/UI link selaras.
- Tambahkan regression tests untuk subject lintas tenant dan rombel beda tahun ajaran di ExamManagement.
- Periksa konsistensi filter rombel di daftar hasil dan export, termasuk histori tahun ajaran.
- Periksa alur Kamad: login mengarah ke / dan welcome view; test mengunci /kamad/dashboard sebagai 404. Jangan menciptakan portal baru.
- Pastikan kurikulum terintegrasi pada semua alur, bukan hanya Bank Soal.
- Setelah Web lama stabil, susun gap list fitur wajib yang belum ada dan klasifikasikan P0/P1/P2. Jangan mulai fitur baru sebelum audit fungsi eksisting selesai.

### 8. CI dan ZIP deployment
- Periksa workflow trigger dan base PR. Jangan ubah PR #4/base branch diam-diam.
- Jika tidak dapat memicu workflow, jelaskan keterbatasan; jangan mengarang hasil.
- Workflow packaging melakukan composer validate/install, installer route check, membangun clean deployment tree, mengecualikan .env, backup_before_patch, tests, docs, private storage, debug artifacts, lalu membuat SHA256SUMS.
- ZIP baru harus dibangun dari commit final yang telah diaudit/diuji. Verifikasi unzip -t, checksum, file sensitif, struktur, composer.lock, vendor, artisan, .env.example, README, manifest.
- ZIP lama P0.96G dibuat dari commit 158227362eea82fc7ec7e9c232736596015739d2, SHA-256 90fa052d47564ecc95d590059a8e28db49411c6bf09fc0e2ad100ee09c703052, ukuran 18,590,890 bytes. ZIP tersebut dibuat sebelum patch PR #5 dan bukan paket final.
- PR #4 tetap Draft/Open/Unmerged; jangan merge/ubah tanpa review terpisah.

### 9. Status VPS — jangan deploy otomatis
Domain: https://cbt.min15jakarta.sch.id
App path: /www/wwwroot/cbt.min15jakarta.sch.id/backend-laravel
Document root: /www/wwwroot/cbt.min15jakarta.sch.id/backend-laravel/public
Vhost: /www/server/panel/vhost/nginx/cbt.min15jakarta.sch.id.conf
DB yang terbaca dari konfigurasi Laravel: cbt_zane_min15jakarta. Inspeksi read-only sebelumnya menunjukkan 0 tabel dan migration table belum ada. Ini bukan bukti bahwa DB boleh ditimpa.
Runtime: MySQL CLI 8.0.45, PHP CLI 8.3.36, Laravel 13.42, Composer 2.11.2.
App/public root root:root 755; storage dan bootstrap/cache www:www 755; .env root:www 640. Jangan tampilkan isinya.
Storage private memuat installer-state.lock dan dua backup .env privat; pertahankan.
VPS ZIP staging: /tmp/CBT_MADRASAH_WEB_DEPLOYMENT_P0_96G_158227362eea.zip; SHA tercantum di atas. ZIP belum diekstrak/diinstal.
Disk 120G total, 82G free pada audit terakhir.
Belum ada backup pre-deploy baru. Proposal /www/backup/CBT_P096G_PREDEPLOY_20261120/ belum dieksekusi.
Belum ada migration, extraction, deployment, atau overwrite DB/.env/storage/vhost.
Deployment kelak harus dimulai dengan rencana, backup + manifest/hash, preflight, verifikasi DB target/installer state, maintenance plan, lalu tindakan terkontrol. Jangan migration produksi sebelum persetujuan eksplisit dan bukti backup dapat dipulihkan.

### 10. Android dan koordinasi agent
Setelah Web stabil, lanjutkan Android Studio 2025 Panda. Stack: Kotlin 2.1.0, KSP 2.1.0-1.0.29, Room 2.6.1, AGP 8.8, JVM 17, Compose, MVVM, Room, WorkManager, Retrofit. Build wajib via Android Studio, bukan terminal. Ketika butuh device runtime/ADB, ingatkan pengguna membawa kembali perangkat Cubot dan menghubungkannya.
Buat prompt Android terpisah setelah source/checkpoint Android diverifikasi. Jangan buat ulang source dari asumsi.

### 11. Deliverables
1. Checkpoint terbaru AUDIT/DESAIN/IMPLEMENTASI/PENGUJIAN.
2. Daftar file berubah dan commit SHA.
3. Daftar backup/path.
4. Matriks role × halaman × aksi × permission.
5. Matriks fitur Web eksisting × status audit source × test × hasil.
6. Log CI/test aktual.
7. Daftar bug tersisa berdasarkan severity.
8. Prompt agent berikutnya dengan konteks/instruksi spesifik.
9. Jangan nyatakan siap deploy/produksi tanpa bukti wajib.

### 12. Instruksi mulai kerja
Mulai dengan membaca branch/PR/checkpoint terbaru dan periksa source aktual. Verifikasi head commit sudah berubah atau tidak. Jangan meminta pengguna mengulang keputusan atau mengirim file yang sudah tersedia. Lanjutkan audit tertunda pada bagian 7, buat backup sebelum setiap patch, perbaiki dan tambahkan regression tests, lalu jalankan CI di DB disposable. PR #5 tetap Draft sampai audit dan tes selesai. Jangan melakukan deployment VPS atau migration produksi pada tahap ini.


### Addendum setelah prompt awal disusun
- Audit lanjutan menemukan filter status ExamManagement tidak mereset pagination; ditambahkan updatingStatusFilter() dengan backup di backend-laravel/backup_before_patch/WEB_EXAM_STATUS_PAGINATION_20261120/ExamManagement.php.before.
- Commit perbaikan pagination: 5a46c0914533589706653b3b2f3aaad245b2d535.
- Periksa checkpoint docs/CHECKPOINT_WEB_AUDIT_20261120.md untuk commit terbaru dan status PR terkini. Prompt ini diperbarui sebelum handover final bila ada perubahan tambahan.


## Addendum 20 November 2026 — CI branch trigger
- Temuan: workflow tenant-backend-ci.yml pull_request hanya target main, tidak mencakup base PR #5. Perbaikan aman: branch kerja fix/web-portal-usability-rbac-20261120 ditambahkan pada push trigger.
- Backup: backend-laravel/backup_before_patch/WEB_CI_TRIGGER_20261120/tenant-backend-ci.yml.before (backup commit edbb229ebf479c40345c36a10528757f14b1d947).
- Workflow commit: 7d9e0fbe92a8305daea56fc7c633705ea8d01a5b.
- Run #620 / ID 44732146599 mulai pada event push untuk commit tersebut. Status terakhir ketika addendum disusun masih in_progress; jangan anggap lulus. Periksa https://github.com/zane0184/cbt_november/actions/runs/44732146599 dan log lengkap.
- Tes berjalan di disposable MySQL 8 GitHub Actions; bukan database VPS.
- PR #5 tetap Draft dan belum merge/deploy.


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

# HANDOVER ADDENDUM — 20 NOVEMBER 2026 — HEAD 9e48a719

Agent penerus WAJIB menganggap addendum ini sebagai status yang lebih baru daripada catatan status CI lama di bagian atas dokumen.

## Status terkini terverifikasi
- Branch: `fix/web-portal-usability-rbac-20261120`.
- Head: `9e48a719668eecd80e03682d33fa2ba978231d37`.
- PR #5: OPEN, DRAFT, UNMERGED, UNDEPLOYED, mergeable=true. Jangan merge atau deploy.
- CI #684 / ID `44748706279`: COMPLETED SUCCESS. Job ID `136330660780`; semua steps sukses termasuk focused login/tenant smoke dan full PHPUnit pada disposable MySQL 8.0.46. Hasil: **480 passed (1.942 assertions), 89,88s**. URL: https://github.com/zane0184/cbt_november/actions/runs/44748706279.
- Patch commit #684 hanya mengubah test pagination QuestionBank; backup: `backend-laravel/backup_before_patch/WEB_QUESTION_BANK_PAGINATION_VIEW_ASSERTION_20261120/QuestionBankTest.php.before`.
- Tidak ada perubahan VPS, DB produksi, .env, private storage, installer-state, vhost, atau migration produksi.

## Koreksi status lama
Setiap keterangan lama di dokumen ini yang menyatakan run #684 masih berjalan, belum ada hasil PHPUnit, atau test terakhir belum lulus sudah kedaluwarsa. Gunakan hasil CI di atas. Run lama yang gagal/batal tetap dicatat sebagai riwayat, bukan status terkini.

## Source yang diperiksa ulang
- `RolePermissionBootstrapper.php` SHA `fac82573de84c52b52867afbbf7e1637164835b4`.
- `RolePermissionBootstrapperTest.php` SHA `cfacac702f967667c8e1e4f6dff18000fc105f68`.
- Bootstrapper menggunakan grant additive `syncWithoutDetaching` dan revoke selektif. Coverage seluruh stale built-in permissions belum terbukti. Inventaris semua permissions dan pemetaan pemakaiannya sebelum patch; jangan blanket detach dan jangan hapus custom grants tanpa kebijakan eksplisit.
- Test bootstrap belum membuat PROKTOR role untuk menguji stale grants; perlu regression coverage.
- ResultDetail/ExamAttemptPolicy role-permission-assignment/cross-tenant matrix belum lengkap.

## Instruksi kerja berikutnya
1. Audit semua permission names dari migrations/seeders/code references/routes/policies/Gates/Livewire/controllers/views. Bentuk matriks role × permission yang selaras dengan keputusan produk. Perhatikan akun multi-role dan Super Admin.
2. Perbaiki bootstrapper hanya setelah inventaris/matriks valid. Tambah test stale grants untuk Admin, Kamad, Guru, Operator, Proktor; pertahankan custom permissions sesuai desain.
3. Tambahkan regression matrix detail hasil/grade/finalize/reopen untuk Admin/Kamad/Guru/Operator/Proktor/Siswa dan tenant lain; akses langsung URL wajib dites.
4. Teruskan audit lengkap Exam Builder, Question Bank enam tipe, hasil/penilaian, laporan/analytics/export, user/academic/curriculum, monitoring/proctor assignment.
5. Pastikan Kurikulum Merdeka, Panca Cinta, Pembelajaran Mendalam menyatu ke seluruh alur akademik dan semua role.
6. Backup source sebelum setiap patch; commit backup lebih dahulu. Jangan push saat CI aktif. Jalankan focused tests lalu full CI di disposable MySQL.
7. Jalankan runtime/browser smoke hanya di staging disposable setelah source audit memadai.
8. Update checkpoint/handover setiap tahap. PR tetap Draft sampai review dan semua gerbang lulus.

## Guardrails tetap berlaku
- Web/backend saja sekarang; Android ditunda sampai Juni 2027.
- Jangan deployment/migration/ubah DB/.env/storage/vhost/installer state produksi tanpa persetujuan eksplisit, backup terverifikasi, dan preflight.
- Jangan menyebut siap produksi/siap ujian hanya karena CI lulus.
