# CHECKPOINT PENGAMBILALIHAN DAN PENYELESAIAN WEB ADMIN CBT
Tanggal pemeriksaan: 22 November 2026
Repository: `zane0184/cbt_november`
Branch kerja terisolasi: `work/web-admin-completion-20261122`
Baseline source: `work/coordinator-web-admin-audit-20261122`
Baseline HEAD: `dc434ea3beb4838a49194089f546a8c6c799161d`
Parent HEAD: `0f5c92dcd55db3321326d9b59b927c7f410692ec`
Acuan produk: `docs/MASTER_SPESIFIKASI_FITUR_WEB_ADMIN_MADRASAH_2026-11-22.md` (main, blob `68150acf7b9abf130776bee27444c99889ce53a5`) dan Issue #12.

## STATUS
PENGAMBILALIHAN SELESAI — AUDIT LANJUTAN BERJALAN.
WEB ADMIN BELUM ACCEPTED. Belum ada source aplikasi/migration/database/VPS yang diubah dalam checkpoint ini. Branch baru dibuat dari exact HEAD koordinator untuk mengisolasi pekerjaan berikutnya.

## AUDIT

### 1. Kondisi repository dan baseline
- Repository default branch: `main`, HEAD `e6af675ebbd5aabc48d11956c9c5476d41f76234`.
- Tree `main` saat pemeriksaan hanya memuat 33 entri dokumentasi dan tidak memuat `backend-laravel/`. Karena itu `main` bukan baseline source aplikasi untuk implementasi Web Admin saat ini.
- Branch source terlengkap yang dipakai agent sebelumnya: `work/coordinator-web-admin-audit-20261122`, HEAD `dc434ea3beb4838a49194089f546a8c6c799161d`, tree 975 entri.
- Branch koordinator berasal dari Paket 1; perubahan agent sebelumnya tetap dipertahankan dan tidak di-reset.
- Branch baru `work/web-admin-completion-20261122` dibuat dari exact SHA koordinator di atas. Tidak ada merge, rebase, cherry-pick atau perubahan branch `main`.

### 2. Keputusan produk yang mengikat
- Scope sekarang WEB CBT. Portal aktif hanya ADMIN_MADRASAH, GURU, SISWA.
- Tidak ada portal/role Operator, Kamad, atau Proktor permanen. Proktor adalah Guru dengan assignment sementara untuk Exam dan Room.
- Satu madrasah satu instalasi dan database akademik terpisah; tenant_id tetap defense-in-depth.
- Ujian serentak berdasarkan Exam schedule/token/server time; tidak ada ExamSession/SessionRoom/session/wave sebagai lifecycle operasional.
- Guru mengelola soal/asesmen/ujian sesuai penugasan tanpa approval Admin.
- Admin mengelola administrasi, master data, ruang/placement, assignment Proktor, monitoring, dukungan, dan laporan; Admin tidak otomatis mengubah soal/kunci/jawaban Guru.
- Android dan Web Super Admin deferred sampai Juni 2027.
- Issue #12 dan master spec tanggal 22-11-2026 mengesampingkan desain historis yang bertentangan.

### 3. Temuan branch dan pekerjaan agent sebelumnya
| Branch | Exact HEAD yang diperiksa | Temuan |
|---|---|---|
| `work/coordinator-web-admin-audit-20261122` | `dc434ea3` | Baseline kerja koordinator; edit/reset akun ada di source, tetapi HEAD belum memiliki bukti CI yang dapat diamati |
| `work/web-admin-package1-implementation-20261122` | `d7e2034e` | Dasar Paket 1; bukti CI hanya berlaku untuk SHA test masing-masing |
| `work/import-guru-siswa-implementation-20261122` | `f38ceec7` | Memiliki import XLSX, layanan batch, template, preview/confirm dan tes; perlu adopsi terpilih serta pemeriksaan exact-SHA |
| `work/stage4a-monitoring-rbac-20261122` | `32bb7663` | Laporan menyebut 109 test/578 assertions PASS pada SHA `88f1510a`; desainnya menolak Admin monitoring, bertentangan dengan master spec yang memberi Admin fitur monitoring administratif. Tidak boleh digabung apa adanya |
| `work/stage4b-room-session-20261122` | `0f6921ed` | Memuat model operasional ruang/sesi; bertentangan dengan keputusan terbaru tanpa ExamSession/SessionRoom operasional. Jangan adopsi migration/komponen session |
| `work/web-guru-foundation-20261122` | `cf9a6ed8` | Pekerjaan portal Guru terpisah; bukan scope implementasi Web Admin saat ini |
| `work/agent4-web-siswa-audit-20261122` | `69acf087` | Dokumentasi Web Siswa; bukan scope implementasi Web Admin saat ini |

Catatan: hasil CI lama hanya berlaku untuk commit SHA yang benar-benar diuji. Tidak boleh memindahkan label PASS ke HEAD branch terbaru.

### 4. Status modul awal (berdasarkan source yang ditemukan, bukan acceptance)
| Modul | Status | Bukti/gap awal |
|---|---|---|
| Dashboard | PARSIAL | Livewire AdminDashboard ada; angka/aktivitas/peringatan perlu audit query dan test |
| Profil Madrasah | PARSIAL | TenantProfileManagement ada; branding, favicon, kop cetak dan preview belum lengkap |
| Guru/Siswa | PARSIAL | UserManagement create/edit/reset ada; status akun aktif/nonaktif dan lifecycle/history belum lengkap |
| Import/Export | PARSIAL | ImportManagement dan branch import terpisah ada; template, idempotensi, batch, error report, export harus direkonsiliasi pada satu baseline |
| Tahun Pelajaran/Semester | PARSIAL | AcademicManagement dan migration active-period ada; concurrency dua koneksi dan seluruh CRUD perlu diuji |
| Kelas/Rombel/Mutasi | PARSIAL | StudentRombelManagement ada; pindah/naik/tinggal kelas/kelulusan/histori belum lengkap |
| Mata Pelajaran | PARSIAL | Fondasi akademik ada; relasi kurikulum dan histori perlu audit |
| Penugasan Guru | PARSIAL/BLOCKED | Model/service ada; UI Livewire Admin belum terbukti pada baseline koordinator |
| Kurikulum | PARSIAL | CurriculumManagement ada; registrasi, versi, struktur, ATP, mapping KBC/Panca Cinta dan Pembelajaran Mendalam belum lengkap |
| Manajemen Ujian Admin | PARSIAL | Komponen ExamManagement tercampur jalur Guru/Admin; batas akses Admin harus diperketat sesuai spesifikasi |
| Ruang & placement | BELUM TERVERIFIKASI | Tidak boleh mengadopsi session/wave; desain langsung Exam–Room–placement harus diaudit |
| Penugasan Proktor | PARSIAL | Komponen dan service ada; perlu verifikasi Guru target, assignment lifecycle, interval, overlap concurrency, dan scope Room |
| Monitoring Admin | BLOCKED UNTUK ACCEPTANCE | Route/policy dan komponen harus diselaraskan dengan spesifikasi: monitoring administratif tanpa jawaban/kunci |
| Laporan Admin | BELUM TERVERIFIKASI | API laporan Guru ada; laporan administratif Admin, PDF/XLSX, kop, permission dan audit export belum dibuktikan |
| Audit & keamanan | PARSIAL | AuditService dan AuditLog ada; UI log, login/access denied dan cakupan event belum lengkap |
| Pengaturan | BELUM TERVERIFIKASI | Tema, preferensi, zona waktu, branding dan operasional perlu audit |
| Backup/restore & health | BELUM TERVERIFIKASI | Tidak boleh dianggap ada hanya dari dokumentasi deployment |

### 5. Keputusan identitas login final
Keputusan terbaru pengguna yang ditemukan pada percakapan sebelumnya:
- Admin Madrasah: login hanya dengan username.
- Guru ASN: login hanya dengan NIP (18 digit).
- Guru Honorer: login hanya dengan NIK (16 digit).
- Siswa: login hanya dengan NISN (10 digit).
- NIP dan NIK bukan alternatif silang. NUPTK bukan identitas login.
- Username tambahan untuk Guru/Siswa boleh disimpan sebagai data akun, tetapi tidak menjadi fallback login.
- Keputusan spesifik dan lebih baru ini menjadi aturan operasional login. Catatan master spec bahwa NIP/NUPTK dapat opsional untuk data umum tidak boleh dipakai untuk membolehkan login silang atau membuat Guru tanpa identitas login sesuai kategorinya.
- Nilai kategori kanonis di source adalah `ASN` dan `HONORER`. Workbook lama yang menulis `PNS` dinormalisasi ke `ASN`; data baru/template menampilkan `ASN`.
- Profil Guru lama yang belum memiliki kategori tidak di-auto-infer dari kolom NIP/NIK. Admin harus mengklasifikasikan dan melengkapi identitas melalui edit akun sebelum login Guru diizinkan. Resolver fail-closed.

### 6. Risiko/ketidaksesuaian yang harus ditangani
1. `routes/web.php` mengelompokkan sejumlah route Admin dan Guru di grup auth/tenant/license umum. Setiap komponen/policy/action harus fail-closed; route grouping belum cukup sebagai bukti RBAC.
2. `QuestionBank` dan `ExamManagement` masih berada di namespace Admin dan menu lama. Harus dipastikan Admin tidak mendapat akses authoring, kunci, jawaban, score, atau token.
3. ImportManagement dari branch import menggunakan hasRole('ADMIN_MADRASAH') dan permission; harus diuji exact-one role/stale permission, tenant, ownership batch, dan semua action.
4. Source/komponen Proktor harus mengikat assignment sementara ke Guru, Exam, Room, interval dan status; jangan menggunakan role PROKTOR permanen.
5. Stage 4A menolak seluruh monitoring Admin, sedangkan master spec mewajibkan monitoring Admin. Implementasi harus membatasi informasi dan tindakan, bukan menutup fitur Admin sepenuhnya.
6. Stage 4B menambah operational session/wave. Semua source/migration tersebut dikecualikan dari baseline baru sampai desain langsung Exam–Room disahkan.
7. CI branch koordinator belum memberikan bukti test runtime pada HEAD `dc434ea3`; check-runs untuk commit ini kosong. Tidak boleh mengklaim source branch lulus.
8. Workspace lokal rekonstruksi `/mnt/data/CBT_MADRASAH_RECONSTRUCTION/` tidak ditemukan pada sesi ini. Repository GitHub adalah sumber source yang dapat diverifikasi; ZIP baseline dan workspace lain tidak tersedia pada filesystem sesi ini.

## DESAIN / URUTAN PENYELESAIAN
Satu owner integrasi: branch `work/web-admin-completion-20261122`. Tidak ada patch paralel pada file Admin yang sama.

1. P0 — tutup kontrak role/auth/session, lifecycle akun, status aktif/nonaktif, exact-one role, dan tenant boundary. Konflik identitas login ditahan; jangan membuat migration tebakan.
2. P1 — rekonsiliasi import/export Guru/Siswa dari branch import secara file/dependency-level, bukan merge branch.
3. P2 — selesaikan penugasan Guru Mapel–Kelas/Rombel–Tahun/Semester beserta histori.
4. P3 — selesaikan lifecycle akademik Siswa: penempatan, mutasi, naik/tinggal kelas, lulus, arsip.
5. P4 — selesaikan master kurikulum/versioning/registrasi/struktur/CP/TP/ATP dan pemetaan Kurmer–KBC/Panca Cinta–Pembelajaran Mendalam. Jangan mengarang dataset resmi.
6. P5 — administrasi ujian: daftar Exam Guru, Room, placement peserta dan assignment Guru Proktor sementara; tanpa ExamSession/SessionRoom.
7. P6 — monitoring Admin dengan data minimum dan tindakan yang diotorisasi/audit; tidak membuka jawaban/kunci tanpa hak.
8. P7 — laporan administratif, export PDF/XLSX, kop/branding.
9. P8 — audit UI, keamanan, pengaturan, backup/restore/health.
10. P9 — MySQL 8/InnoDB, concurrency, browser E2E, responsive, accessibility, UAT.
11. P10 — paket distribusi satu instalasi madrasah dan runbook aaPanel File Manager; tanpa secret/backup/temp. Deployment produksi hanya setelah preflight, backup, rencana rollback dan persetujuan.

## IMPLEMENTASI PADA CHECKPOINT INI
- Branch `work/web-admin-completion-20261122` dibuat dari exact SHA `dc434ea3beb4838a49194089f546a8c6c799161d`.
- Tidak ada file aplikasi, migration, database, workflow, konfigurasi, VPS atau branch `main` yang diubah.
- Dokumen ini menjadi checkpoint awal agent penyelesaian; semua backup source yang sudah ada tetap dipertahankan.

## PENGUJIAN
- Audit GitHub refs/trees/source/docs/issue #12: dilakukan.
- Test runtime oleh agent ini pada exact HEAD: NOT RUN.
- PHP lint/PHPUnit/MySQL 8/InnoDB/concurrency/browser/E2E/UAT: NOT RUN pada sesi ini.
- Stage 4A CI lama: 109 test / 578 assertions PASS hanya pada SHA `88f1510a7d1992f6cd8d4b727daf93fe28a3afd3`, bukan pada baseline branch baru.
- Tidak ada bukti acceptance Web Admin menyeluruh.

## NEXT ACTION
Mulai audit dan implementasi P0 pada branch ini: periksa seluruh file auth/model/migration/session dan tests, desain account active/inactive tanpa soft-delete, backup tiap file sebelum patch, lalu jalankan CI disposable MySQL 8 pada exact SHA. Setelah P0 teruji, lanjut P1 dan seterusnya.


## ADDENDUM P0 — STATUS AKUN AKTIF/NONAKTIF (22 NOV 2026)

### AUDIT
- Baseline `users` tidak memiliki flag status akun eksplisit.
- Kolom `deleted_at` ada pada tabel, tetapi model User tidak menggunakan SoftDeletes; field tersebut tidak boleh dijadikan status aktif/nonaktif.
- Login Web dan API memakai LoginIdentityResolver yang sama; karena itu pemeriksaan status akun diletakkan pada resolver dan middleware request terautentikasi.
- Tabel `sessions.user_id` dibuat sebagai foreignId numerik sedangkan `users.id` UUID. Jangan menghapus sesi dengan membandingkan UUID ke kolom numerik tersebut. Request berikutnya untuk sesi Web ditolak middleware; bearer token dicabut saat deaktivasi.

### DESAIN
- Flag `users.is_active` boolean, default true untuk mempertahankan akses akun lama saat migrasi.
- Akun nonaktif ditolak saat login Web/API.
- Middleware `account.active` dipasang pada semua route Web/API yang membutuhkan autentikasi, termasuk student routes, monitoring, /auth/me, dan logout.
- Deaktivasi hanya tersedia bagi Guru/Siswa single-role pada tenant Admin yang sedang aktif.
- Deaktivasi mewajibkan alasan, mengubah flag dalam transaksi/row lock, merotasi remember_token, mencabut seluruh Sanctum token, dan menulis audit event USER_DEACTIVATED.
- Aktivasi kembali mewajibkan alasan dan menulis USER_REACTIVATED; token/sesi lama tidak dipulihkan.
- Filter daftar akun mendukung semua/aktif/nonaktif. Status tidak mengubah histori akademik dan tidak memakai soft-delete.

### IMPLEMENTASI
Branch: `work/web-admin-completion-20261122`.
Source changes:
- `backend-laravel/database/migrations/2026_11_22_000006_add_is_active_to_users_table.php` — flag aktif default true.
- `backend-laravel/app/Models/User.php` — cast is_active boolean.
- `backend-laravel/app/Services/LoginIdentityResolver.php` — hanya resolve user aktif; profile tables tidak memiliki flag is_active.
- `backend-laravel/app/Http/Middleware/EnsureActiveAccount.php` — menolak request sesi/token untuk akun nonaktif; token aktif yang dipakai pada request ditarik.
- `backend-laravel/bootstrap/app.php` — alias account.active.
- `backend-laravel/routes/web.php` dan `routes/api.php` — middleware aktif dipasang pada seluruh route terautentikasi.
- `backend-laravel/app/Http/Controllers/Api/V1/AuthController.php` — role login API dibatasi ADMIN_MADRASAH/GURU/SISWA; KAMAD dihapus dari allow-list.
- `backend-laravel/app/Livewire/Admin/UserManagement.php` dan view — filter status, modal alasan, aktivasi/deaktivasi, token revocation, audit.
- `tests/Feature/AdminUserManagementScopeTest.php` — lifecycle, audit, token revocation dan tenant denial.
- `tests/Feature/WebAdminAuthTest.php` — login nonaktif dan session denial.
- `tests/Feature/AuthTest.php` — login API nonaktif dan token revocation.
- `.github/workflows/wa01-alternative-isolated-runtime.yml` — workflow ditambahkan untuk branch completion; suite test terkait sudah termasuk dalam workflow.

Backup exact pre-patch dibuat di `docs/backup/web-admin-completion-2026-11-22/`:
- User.php.before-account-active-status
- LoginIdentityResolver.php.before-account-active-status
- AuthController.php.before-account-active-status
- app.php.before-account-active-status
- web.php.before-account-active-status
- api.php.before-account-active-status
- UserManagement.php.before-account-active-status
- user-management.blade.php.before-account-active-status
- WebAdminAuthTest.php.before-account-active-status
- AuthTest.php.before-account-active-status
- wa01-alternative-isolated-runtime.yml.before-account-active-status

Commits utama source: migration `eaddb5ad`; middleware `168747b1`; model `e71b85c6`; resolver final correction `8511d1fe`; API role allow-list `b0df6c85`; bootstrap `482398ad`; Web routes `ad60f508`; API routes `6924bba4`; UserManagement `cea54ccd`; view `4eb6a134`; Web auth tests `c1f01ee5`; API auth tests `bc0c40e1`; UserManagement tests `9f0534dc`; workflow `32444d5a`.

### PENGUJIAN
- GitHub Actions run `45047019233`, job `137283740235`, exact source SHA `8511d1fe7a643f82473cf6cc0c15e4cfe3065cbe`: FAILURE pada level job sebelum steps; GitHub job steps kosong dan log artifact 404 BlobNotFound.
- Klasifikasi: BLOCKED — RUNNER/OBSERVABILITY. Bukan bukti PHPUnit gagal dan bukan PASS.
- PHPUnit, PHP lint, MySQL 8/InnoDB migration, Browser/E2E: NOT VERIFIED. Tidak ada test runtime lokal pada sesi ini.
- Tidak ada database produksi/VPS, branch main, migration production, atau deployment yang diubah.

### NEXT
1. Pulihkan runtime CI yang dapat diamati. Jangan melakukan rerun berulang ketika job tidak memiliki steps.
2. Jalankan exact HEAD di PHP 8.3 + disposable MySQL 8/InnoDB; periksa migration, login Web/API, middleware token/session, Livewire status lifecycle, tenant denial, audit dan regression semua portal.
3. Jika P0 lulus, lanjut P1: adopsi terpilih Import Guru/Siswa dari branch `work/import-guru-siswa-implementation-20261122`; jangan merge branch karena diverged dan berisi perubahan Stage 4A yang memerlukan rekonsiliasi.


## ADDENDUM P0/P1 — IDENTITAS LOGIN DAN IMPORT/EXPORT (22 NOV 2026)

### AUDIT
- Keputusan login dari percakapan pengguna yang lebih baru telah ditemukan; konflik dengan catatan umum spesifikasi terselesaikan.
- Baseline TeacherProfile belum menyimpan klasifikasi ASN/HONORER, sehingga resolver sebelumnya tidak dapat membedakan NIP vs NIK dengan aman.
- Branch import lama telah memiliki workbook, preview/confirm, staging encrypted, activation link, dan test suite; namun belum menyimpan kategori kepegawaian ke TeacherProfile dan belum dapat dipakai bersama resolver strict.
- Branch import diverged dari branch koordinator; merge keseluruhan dilarang karena juga memuat Stage 4A/Proktor/monitoring yang perlu direkonsiliasi.
- ImportWorkbookService memakai tenant row lock; UserManagement manual create/edit sekarang diselaraskan untuk mengunci row Tenant dan mencegah benturan username vs NIP/NIK/NISN pada instalasi yang sama.

### DESAIN
- Login Admin = username saja; Guru ASN = NIP saja; Guru Honorer = NIK saja; Siswa = NISN saja.
- Resolver Web/API sama dan role harus tepat satu dari tiga portal.
- Kategori Guru disimpan pada `teacher_profiles.employment_type` dengan nilai `ASN` atau `HONORER`.
- NIP 18 digit wajib hanya untuk ASN; NIK 16 digit wajib hanya untuk Honorer. Field silang dilarang pada form dan tidak dipakai resolver. NUPTK opsional dan bukan login.
- Profil lama tanpa employment_type tetap null; tidak ada backfill tebakan. Admin wajib melakukan klasifikasi.
- Import template versi 1.1.0 menampilkan ASN/HONORER. Nilai PNS dari workbook lama diterima sebagai alias legacy lalu disimpan sebagai ASN.
- Import Guru/Siswa menggunakan preview, validasi baris, konfirmasi transaksional, staging, idempotensi, error report, activation link satu kali, dan retensi tujuh hari sesuai service branch yang direview.

### IMPLEMENTASI
Branch: `work/web-admin-completion-20261122`.
Identitas:
- `2026_11_22_000007_add_employment_type_to_teacher_profiles.php` — kolom klasifikasi nullable dan index.
- `TeacherProfile.php` — employment_type fillable.
- `LoginIdentityResolver.php` — Guru ASN hanya NIP; Guru Honorer hanya NIK; Guru tidak fallback ke username/employee_identifier.
- `UserManagement.php` dan view — kategori wajib, NIP/NIK conditional, username tambahan opsional, collision checks, tenant lock, audit.
- `WebAdminAuthTest.php` — NIP ASN, NIK Honorer, penolakan NIK ASN/NIP Honorer dan username Guru.
- `AdminUserManagementScopeTest.php` — Guru tanpa kategori/identitas ditolak; create/edit menggunakan kategori.

Import:
- Integrasi terpilih dari branch `work/import-guru-siswa-implementation-20261122`: ImportWorkbookService, ImportManagement, ImportBatch/Row/ActivationToken, exception, template export/data/instruction/reference sheets, activation/template controllers, views, migrations, dan tiga test suite.
- `routes/web.php` — admin import, template download, signed one-time account activation; admin routes tetap memakai account.active.
- `app.blade.php` — menu Import Data di desktop dan mobile dengan permission user.manage.
- `routes/console.php` — jadwal cleanup staging tujuh hari.
- `wa01-alternative-isolated-runtime.yml` — test suite import dan deadlock ditambahkan ke CI completion branch.
- `ImportWorkbookService.php` — kategori ASN/HONORER dinormalisasi dan disimpan; assignment guru memakai identity sesuai kategori.
- `ImportTemplateExport.php`, `ImportInstructionSheet.php`, `ImportTemplateVersion.php` — template 1.1.0 dengan ASN/HONORER.

Backup:
- Sebelum identity patch: `docs/backup/web-admin-completion-2026-11-22/identity-classification/`.
- Sebelum import integration: `docs/backup/web-admin-completion-2026-11-22/import-integration/`.
- Backup P0 account active sebelumnya tetap ada di folder utama `docs/backup/web-admin-completion-2026-11-22/`.
- File baru (migration, middleware, Import* components/services/models/controllers/views/tests) tidak menimpa file lama; file existing yang diubah memiliki backup sebelum perubahan terkait.

### PENGUJIAN
- Import branch terdahulu melaporkan test PASS pada SHA yang dicatat dalam `CHECKPOINT_IMPORT_GURU_SISWA_2026-11-22.md`; hasil itu hanya bukti branch/SHA lama dan tidak dipindahkan menjadi klaim PASS pada branch completion.
- GitHub Actions run `45048216908`, job `137287362762`, exact HEAD `729d25b7b707f0bb861b5b4d19df35a0117f6022`: FAILURE sebelum job steps; API mengembalikan `steps=[]`, tanpa logs_url. Klasifikasi BLOCKED runner/observability, bukan test failure dan bukan PASS.
- PHP lint, PHPUnit, MySQL 8/InnoDB migration, deadlock/concurrency, browser E2E dan UAT branch completion: NOT VERIFIED.
- Import module belum boleh dipakai produksi sampai seluruh test dijalankan pada exact HEAD dan hasilnya terekam.

### NEXT
1. Jalankan PHP lint + seluruh suite pada disposable PHP 8.3/MySQL 8/InnoDB; jika runner GitHub tetap tidak menyediakan steps, siapkan runtime runner yang dapat diamati tanpa mengubah DB produksi.
2. Audit/fix semua test import yang terdampak employment_type dan template 1.1.0.
3. Setelah P0/P1 lulus, lanjut P2 Penugasan Guru, P3 lifecycle akademik siswa, P4 kurikulum, P5 ruang/placement/pengawas, P6 monitoring Admin, P7 laporan, P8 audit/pengaturan, P9 UAT.


## ADDENDUM P2 — PENUGASAN GURU MATA PELAJARAN (22 NOV 2026)

### AUDIT
- Baseline memiliki model/tabel `TeacherSubjectAssignment` dan API controller, tetapi belum ada halaman Livewire Admin pada branch koordinator.
- Branch Stage 4B memiliki komponen dan test penugasan Guru yang dapat dipisahkan dari perubahan Room/Session. Implementasi Stage 4B yang memperkenalkan `ExamRoomSession` dan `SessionRoom` tidak diambil.
- Komponen upstream tidak membatasi Admin ke satu role, dan picker Guru belum mengecualikan akun nonaktif, multi-role, atau profil yang belum memiliki kategori login. Komponen juga belum mewajibkan alasan perubahan.

### DESAIN
- Admin dengan satu role ADMIN_MADRASAH dan permission academic.manage dapat membuat, mengubah, menonaktifkan penugasan Guru.
- Guru yang dapat dipilih harus akun aktif, tepat satu role GURU, profil aktif/non-deleted, dan identitas login valid: ASN+NIP 18 digit atau HONORER+NIK 16 digit.
- Penugasan mengikat Guru, mapel, rombel, tahun ajaran, semester, rentang tanggal, dan status.
- Referensi harus berasal dari tenant yang sama; rombel dan semester harus berada pada tahun ajaran yang sama.
- Tenant row lock + row lock penugasan + unique constraint menjaga race/duplikasi. Penonaktifan tidak menghapus histori.
- Alasan wajib (10–1000 karakter) untuk create/update/deactivate dan disimpan di AuditLog before/after. Tidak ada perubahan operasional ExamSession/SessionRoom.

### IMPLEMENTASI
- `backend-laravel/app/Livewire/Admin/TeacherSubjectAssignmentManagement.php` — komponen dari branch Stage 4B yang direkonsiliasi; role Admin exact-one, filter Guru aktif/single-role/terklasifikasi, tenant checks, duplicate handling, reason, audit, dan lock.
- `backend-laravel/resources/views/livewire/admin/teacher-subject-assignment-management.blade.php` — form create/edit dengan alasan wajib dan modal konfirmasi penonaktifan dengan alasan.
- `backend-laravel/routes/web.php` — route `/admin/teacher-subject-assignments` di group auth/tenant/license/account.active.
- `backend-laravel/resources/views/layouts/app.blade.php` — menu desktop/mobile Penugasan Guru Mapel di bawah academic.manage.
- `TeacherSubjectAssignmentManagementTest.php` — create/idempotency, update, deactivate, year mismatch, tenant denial, permission, stale Admin role, picker filter, audit reason.
- `TeacherSubjectAssignmentConcurrencyTest.php` — dua koneksi MySQL/InnoDB dan tenant mutex; fixture Guru mengikuti employment_type ASN.
- `.github/workflows/wa01-alternative-isolated-runtime.yml` — test management dan MySQL concurrency dimasukkan ke workflow.
- `whereKeyNot` diganti ke kondisi UUID eksplisit; profil soft-deleted tidak masuk picker.
- Tidak ada file/migration/route `ExamRoomSession` atau `SessionRoom` yang diambil dari branch Stage 4B.

Backup:
- Route, layout dan workflow: `docs/backup/web-admin-completion-2026-11-22/teacher-assignment/`.
- File upstream component/view/tests: `docs/backup/web-admin-completion-2026-11-22/teacher-assignment/upstream-*.before-admin-boundary-hardening`.

### PENGUJIAN
- Test suite ditambahkan ke workflow, tetapi belum dapat dieksekusi pada branch completion.
- GitHub Actions runner sebelumnya gagal sebelum steps (job steps kosong). P2 management/concurrency: NOT VERIFIED.
- MySQL 8/InnoDB independent-session test, PHP lint, PHPUnit, browser UI: NOT RUN pada exact HEAD ini.

### NEXT
1. Verifikasi P0/P1/P2 pada runtime PHP 8.3 + disposable MySQL 8/InnoDB yang dapat diamati.
2. Lanjut P3 lifecycle akademik Siswa (mutasi/naik kelas/tinggal kelas/kelulusan/alumni) tanpa menghapus histori.
3. Lanjut P4 kurikulum dan integrasi kurikulum; lalu P5 ruang/placement/pengawas tanpa session model.
