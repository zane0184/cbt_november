# LAPORAN STAGE 4D — AUDIT DAN DESAIN SEBELUM IMPLEMENTASI

Tanggal pemeriksaan: 22 November 2026  
Repository: `zane0184/cbt_november`  
Branch kerja: `work/stage4d-account-lifecycle-20261122`  
HEAD saat laporan: `0038028fe2fedf0e660b35700fa293e6770da557`  
Checkpoint awal yang diminta: `887acc43c3b1e2955b1d7fee825f111d786e06d8`

## 1. AUDIT

### 1.1 CI — run yang diminta

**Run 44981468061**
- Branch: `work/stage4d-account-lifecycle-20261122`
- SHA checkout: `886f6edf30d1582b639379eb118efa25a0ef28be`
- Event: `push`
- Final run: `completed / failure`
- Job: `safe-runtime-tests` — `completed / failure`
- Failed step: `PHPUnit — Stage 4C teacher subject assignment lifecycle and tenant boundary`
- Langkah sebelumnya yang sukses: setup runner/container, checkout exact SHA, PHP 8.3/Composer, dependency validation, disposable MySQL 8 preflight, static guard, PHP lint, Admin boundary, Admin dashboard, academic period, legacy role migration, tenant profile, user-management scope, import, auth/tenant isolation, Guru/Siswa regression, monitoring duplication, temporary Proktor assignment.
- Langkah dilewati setelah kegagalan: Stage 4C MySQL concurrency, Stage 4B room/session, dan Stage 4B MySQL concurrency.
- Run ini tidak membuktikan Stage 4C atau Stage 4B lulus.

URL: https://github.com/zane0184/cbt_november/actions/runs/44981468061

### 1.2 Akar kegagalan dan perbaikan fixture Stage 4C

Akar kegagalan test yang ditelusuri: `test_non_admin_cannot_open_assignment_management` membuat role `GURU` kedua, padahal `setUp()` telah membuat role yang sama. Ini benturan fixture test, bukan cacat aturan akses aplikasi.

Perbaikan:
- Commit: `0038028fe2fedf0e660b35700fa293e6770da557`
- Fixture menggunakan role yang sudah ada melalui `Role::query()->firstOrCreate(...)`.
- Backup sebelum patch tercatat pada manifest Stage 4D.
- CI run terbaru untuk SHA perbaikan: `44982023649`, saat laporan ini disusun masih `in_progress`. Step Stage 4C belum dijalankan pada saat pemeriksaan terakhir. Karena itu belum ada klaim PASS untuk SHA ini.

### 1.3 CI Stage 4B — branch Agent 1

Branch Agent 1 tidak diubah:
- Branch: `work/stage4b-room-session-20261122`
- HEAD terbaru yang diperiksa: `5d45b8404f225c105016a25976939afe3774abef`
- Run pada HEAD terbaru: `44982090117`, status `pending`, belum memiliki job yang mulai.
- Run sebelumnya: `44981750084`, SHA `42a3ed3a2d5d908cbcfc30340ef06de5b62b5887`, masih `in_progress` ketika diperiksa; belum merupakan bukti untuk HEAD terbaru.
- Run pada SHA lebih lama `0b7fef381c6c36a55f6d57bd4ca2cb6f1ae711be` berakhir failure pada test Stage 4C, sehingga tidak dapat dipakai sebagai bukti kelulusan Stage 4B.

Source yang ditemukan:
- Migration: `backend-laravel/database/migrations/2026_11_22_110001_create_exam_room_session_scope.php`.
- Test fungsional: `backend-laravel/tests/Feature/ExamRoomSessionManagementTest.php`.
- Test concurrency: `backend-laravel/tests/Feature/ExamRoomSessionConcurrencyTest.php`.
- Service: `backend-laravel/app/Services/ExamRoomSessionService.php`.
- Livewire Admin: `backend-laravel/app/Livewire/Admin/ExamRoomSessionManagement.php`.

Concurrency test memang menggunakan dua koneksi MySQL dan memuat dua skenario: attach room overlap dan konfirmasi Proktor overlap. Workflow Stage 4B memiliki step untuk menjalankan test concurrency MySQL 8. Namun hasil pada SHA terbaru belum tersedia.

Migration `down()` memiliki guard yang menolak rollback jika histori ruang/sesi/placement atau kolom scope peserta/Proktor sudah terisi. Pada test/workflow yang ditemukan, belum ditemukan test eksplisit terpisah yang membuktikan kedua jalur rollback: (a) rollback kosong sukses dengan FK/index child-first, dan (b) rollback dengan histori ditolak tanpa kehilangan data. Keberadaan method `down()` atau penggunaan trait migrasi pada test concurrency bukan bukti cukup untuk menyatakan migration rollback PASS. Ini tetap menjadi item verifikasi Agent 1.

### 1.4 Source Stage 4D — akun Guru/Siswa

File aktif yang diperiksa:
- `backend-laravel/app/Livewire/Admin/UserManagement.php` — create dan list akun; belum ada edit, aktivasi/nonaktif, atau reset password.
- `backend-laravel/app/Models/User.php` — **tidak menggunakan trait SoftDeletes**, walaupun tabel `users` memiliki kolom `deleted_at`.
- `backend-laravel/app/Models/TeacherProfile.php` dan `StudentProfile.php` — keduanya memakai SoftDeletes.
- `backend-laravel/app/Services/LoginIdentityResolver.php` — query login secara eksplisit mengecualikan `users.deleted_at`, serta mengecualikan profil Guru/Siswa yang soft-deleted.
- `backend-laravel/app/Http/Controllers/AuthWebController.php` — login web memakai resolver lalu Auth::login.
- `backend-laravel/database/migrations/0001_01_01_000000_create_users_table.php` — users memiliki `softDeletes()`, unique tenant-email, password reset table Laravel berbasis email sebagai primary key.
- `backend-laravel/database/migrations/2026_11_11_000003_add_login_identity_fields.php` — username tenant-unique, email nullable, NIK Guru ditambahkan; rollback menolak bila email NULL.
- `backend-laravel/app/Services/AuditService.php` dan `AuditLog.php` — audit old/new values sudah tersedia dalam JSON.

Temuan keamanan penting:
- Login resolver mengecualikan `deleted_at`, tetapi User model tidak memiliki global SoftDeletes scope. Karena itu belum terbukti bahwa session web yang telah aktif, Sanctum token, route model binding, relasi, atau guard autentikasi lain otomatis menolak akun yang soft-deleted. Harus ada test runtime eksplisit sebelum mengaktifkan deactivation.
- Profile Guru/Siswa sudah SoftDeletes; perubahan akun harus memutus akses tanpa menghapus profil, assignment, attempt, jawaban, nilai, maupun histori akademik.
- Unique tenant-email/username tetap mempertahankan identitas pada akun nonaktif. Jangan membebaskan unique key dengan menghapus data. Proses reaktivasi harus memeriksa benturan identitas yang benar-benar tenant-scoped.
- Tidak boleh memakai password broker Laravel standar: tabel `password_reset_tokens` ber-key email global, sedangkan email opsional dan keunikan identitas per tenant. Desain reset harus memakai token sekali pakai berbasis tenant + user_id, bukan email.

### 1.5 Histori akademik Siswa

Source aktual:
- `StudentRombelManagement.php` hanya menambah siswa ke rombel.
- `RombelStudent.php` adalah pivot historis minimal dengan tenant_id dan timestamps; belum memiliki status membership, tanggal efektif, alasan, atau actor.
- `rombel_student` dibuat oleh `2024_01_01_000002_create_academic_tables.php`, unique pada rombel_id + student_id.
- `Rombel` soft-deletable dan terhubung ke academic_year/grade_level.
- Belum ditemukan event ledger append-only untuk pindah rombel, naik kelas, tinggal kelas, kelulusan, atau perubahan status akademik.

Tidak ada backfill yang dilakukan atau direncanakan berdasarkan asumsi.

### 1.6 Audit Stage 4E–4G berdasarkan source

**Stage 4E — Kurikulum**
- Route Web: `/admin/curriculum`.
- Livewire: `app/Livewire/Admin/CurriculumManagement.php`.
- UI saat ini berfokus pada pembuatan TP dan pembacaan CP/TP; belum ada workflow UI lengkap untuk versi kurikulum, ATP/learning path, dan pemetaan Kurikulum Merdeka, KBC/Panca Cinta, Pembelajaran Mendalam.
- Fondasi API/model learning paths, curriculum versions/structures, outcomes/objectives dan tabel multi-curriculum sudah ada. Jangan membuat model/fungsi duplikat; audit controller, model, migration, policy, permission dan test masing-masing sebelum perluasan UI.
- Permission aktif yang relevan: `curriculum.view` dan `curriculum.manage`.

**Stage 4F — Operasional Admin**
- Sudah ada: AdminDashboard, TenantProfileManagement, AcademicManagement, UserManagement, StudentRombelManagement, ImportManagement, TeacherSubjectAssignmentManagement, ProctorAssignmentManagement, ExamRoomSessionManagement.
- ReportController/API laporan dan analytics sudah ada, tetapi belum ditemukan UI laporan administratif Admin yang lengkap.
- Belum ditemukan UI monitoring sinkronisasi Android, helpdesk/tiket, backup/restore, atau pusat pembaruan sistem pada source yang diperiksa.
- Stage 4C teacher-subject assignment UI sudah ada pada branch Stage 4D; jangan duplikasi.

**Stage 4G — Integrasi, audit, hardening dan gate**
- Belum ditemukan implementasi sebagai satu modul/route terpisah bernama Stage 4G.
- Cakupan harus berupa audit integrasi dan penutupan gap lintas modul, bukan pembuatan dashboard duplikat: tenant/role/permission, audit log, reporting, import, lifecycle, curriculum, sync observability, browser E2E, performance/security dan UAT.
- Spesifikasi final Stage 4G harus diturunkan dari gap aktual setelah Stage 4D–4F, bukan diasumsikan.

### 1.7 Larangan akses Admin terhadap operasional CBT

Source yang diperiksa:
- `RolePermissionBootstrapper.php`: Admin grants terbatas ke curriculum.view/manage, academic.manage, user.manage, exam.proctor.assign.
- `ExamPolicy.php`: explicit deny Admin pada exam viewing/authoring/monitoring, token, lock/close, participants, publish results dan result access.
- `ExamAttemptPolicy.php`: explicit deny Admin untuk attempt, score, grading/finalization.
- `AdminCbtBoundaryTest.php`: policy/Gate matrix, QuestionBank dan ExamManagement Livewire forbidden, student exam route forbidden, termasuk stale mixed roles.
- `MonitoringDuplicationTest.php` dan `ProctorAssignmentSecurityTest.php`: mencakup Admin tidak memperoleh monitoring melalui assignment Proktor dan penolakan route/API monitoring.
- `routes/api.php`: daftar endpoint CBT mencakup question, exam, participants, execution, attempt, answer, scoring, reports, analytics dan monitoring.

Status bukti: **parsial, bukan bukti exhaustif seluruh route/HTTP method/lapisan**. Belum ditemukan satu matriks test Admin yang secara eksplisit mencoba semua endpoint API GET/POST/PUT/DELETE termasuk scoring, token, close, reopen, participant mutation, answer, attempt execution/submit, report export dan analytics. Gate minimum Admin tidak boleh dinyatakan lulus sebelum matriks ini lengkap dan CI lulus pada SHA yang sama.

## 2. DESAIN

### 2.1 Lifecycle akun Guru/Siswa

Ruang lingkup yang direncanakan:
1. Edit data User dan profile sesuai role tunggal yang sah.
2. Aktivasi/nonaktif tanpa hard delete; role yang boleh dikelola hanya tepat satu dari GURU atau SISWA.
3. Semua operasi tenant-scoped, transaction + row lock, revalidasi identitas unik dalam lock, dan audit old/new.
4. Deactivation harus menolak autentikasi baru dan sesi/token lama; tidak menghapus User, Profile, role pivot, teacher assignments, rombel, exam participant, attempt, answer, score atau histori.
5. Reactivation memeriksa konflik unique tenant username/email/NIP/NIK/NISN dan memastikan profil/role masih valid.
6. Uji SoftDeletes harus mencakup web session guard, Sanctum token, login resolver, route model binding, relasi profile, TeacherAccess, import, dan unique key.
7. Tidak mengubah role account menjadi Admin/Kamad/legacy role melalui UI ini.

### 2.2 Reset password tenant-scoped tanpa broker email

Desain awal:
- Tabel token khusus dengan tenant_id, user_id, token_hash, created_by, expires_at, consumed_at, revoked_at, created_at.
- Token acak kriptografis, hanya hash yang disimpan; token mentah hanya ditampilkan sekali kepada Admin untuk disampaikan melalui kanal yang disetujui madrasah.
- Token terikat pada tenant dan user_id, expiry singkat (usulan 15 menit), sekali pakai, satu token aktif per akun; penerbitan baru mencabut token aktif sebelumnya.
- Endpoint validasi dan penyelesaian tidak menerima email sebagai identitas.
- Token tidak boleh masuk audit log, exception, URL analytics atau plaintext DB.
- Setelah reset: hash password baru, consumed_at, rotasi remember_token, revoke Sanctum personal access tokens, invalidasi sesi yang dapat diidentifikasi secara aman, audit completion tanpa secret.
- Rate-limit per tenant/user/IP dan respons generik agar tidak membocorkan keberadaan akun.
- Tidak memakai `Password::broker()` atau tabel `password_reset_tokens` standar.

### 2.3 Histori akademik append-only

Rancangan konseptual sebelum migration:
- Ledger event `student_academic_history_events`: tenant_id, student_profile_id, event_type, from/to rombel, academic year, grade level, effective_at, event_at, reason, actor_user_id, source, idempotency_key, immutable snapshot payload, created_at.
- Event type sekurangnya: BASELINE_CONFIRMED, ENROLLED, TRANSFERRED, PROMOTED, RETAINED, GRADUATED, STATUS_CHANGED, WITHDRAWN.
- Projection membership terpisah untuk status saat ini; maksimal satu membership aktif per siswa per tenant.
- Setiap perpindahan/kenaikan/tinggal kelas/kelulusan menutup projection lama dan membuat projection baru secara atomik, tetapi event ledger hanya INSERT.
- Composite FK tenant-aware untuk siswa, rombel, tahun akademik, kelas/grade dan actor.
- Lock baris StudentProfile untuk serialisasi operasi; unique idempotency key untuk retry; audit actor/reason/effective date.
- Tidak ada update/delete event; koreksi dilakukan dengan event koreksi yang merujuk event salah.
- Jangan mengisi histori baseline otomatis dari pivot lama.

### 2.4 Strategi transisi data histori

1. Backup penuh DB dan lampiran/file terkait; simpan checksum, ukuran, waktu, schema version dan catatan tenant.
2. Restore rehearsal pada DB staging terisolasi; tidak menyentuh produksi.
3. Buat migration schema additive saja, tanpa backfill.
4. Jalankan laporan rekonsiliasi read-only untuk seluruh pivot lama: siswa dengan 0, 1, atau beberapa membership; rombel/tahun yang aktif/nonaktif; akun/profile soft-deleted; tenant mismatch.
5. Semua data lama berstatus belum terverifikasi sampai Admin madrasah mengonfirmasi; tidak menebak rombel/status saat ini dari tahun aktif atau satu-satunya pivot.
6. Catat event BASELINE_CONFIRMED secara eksplisit oleh actor yang berwenang beserta bukti/reason.
7. Cutover pembacaan ke projection baru setelah jumlah siswa dan membership direkonsiliasi serta checksum/count dibandingkan.
8. Pertahankan pivot lama read-only selama periode kompatibilitas; rollback setelah event ditulis harus forward-only, bukan menghapus histori.

## 3. IMPLEMENTASI

- Tidak ada implementasi fitur Stage 4D pada checkpoint ini karena CI baseline untuk SHA terbaru belum final.
- Satu perubahan yang dibuat hanya koreksi fixture test Stage 4C pada commit `0038028fe2fedf0e660b35700fa293e6770da557`.
- Tidak ada migration, perubahan schema, perubahan auth runtime, atau source Web Guru.
- Tidak ada file Agent 1 yang diubah.
- Tidak ada PR, merge, rebase, deployment, VPS, atau perubahan database produksi.

## 4. PENGUJIAN

- Run `44981468061`: FAILURE pada SHA lama `886f6edf...`.
- Run `44982023649`: pada pemeriksaan terakhir masih IN_PROGRESS pada SHA perbaikan `0038028...`; Stage 4C test belum dijalankan pada saat itu.
- Stage 4B run `44981750084`: masih IN_PROGRESS pada SHA `42a3ed3...`, bukan HEAD Agent 1 terbaru.
- Stage 4B run `44982090117`: PENDING pada HEAD Agent 1 terbaru `5d45b840...`.
- Tidak ada hasil test lokal yang diklaim; tidak menjalankan Gradle, migration produksi, atau DB produksi.

## 5. BACKUP

- Backup test Stage 4C: `docs/backup/stage4d-account-lifecycle-2026-11-22/TeacherSubjectAssignmentManagementTest.php.before-nonadmin-fixture-fix`.
- Source blob SHA: `b402b25a88518d426da37d13b54708492b8b7e45`.
- Backup commit: `9c18882e4202c6a43fcf660614b1436c46167f20`.
- Manifest: `docs/backup/stage4d-account-lifecycle-2026-11-22/MANIFEST.md`.
- Source original dan ZIP baseline tidak diubah.

## 6. STATUS DAN LANGKAH LANJUT

**Stage 4C: belum lulus gate pada SHA terbaru sampai run 44982023649 selesai.**  
**Stage 4B: belum lulus gate pada HEAD Agent 1 terbaru sampai run 44982090117 selesai dan bukti rollback/concurrency tersedia.**  
**Stage 4D: audit dan desain selesai; implementasi ditahan.**  
**Histori akademik: desain awal selesai; belum ada migration/backfill.**  
**Admin CBT denial: parsial, belum exhaustive.**  
**Web Guru WG-01: tidak disentuh.**

Urutan berikut:
1. Ambil hasil final run 44982023649 dan log Stage 4C.
2. Jika gagal, telusuri akar dan patch terarah dengan backup; jalankan ulang CI exact SHA.
3. Ambil hasil final run 44982090117 pada HEAD Agent 1; minta Agent 1 melengkapi rollback test eksplisit bila belum ada.
4. Selesaikan matriks Admin denial seluruh endpoint API/route/UI.
5. Setelah baseline CI jelas, implementasikan Stage 4D dengan test SoftDeletes/auth/session/token, tenant isolation, audit, unique constraints, reset token.
6. Selesaikan desain histori akademik dan minta persetujuan sebelum migration.
7. Audit detail Stage 4E–4G dan jangan duplikasi source yang sudah ada.
8. Saat Gate minimum Admin benar-benar lulus pada SHA tertentu, segera laporkan kepada koordinator dan serahkan WG-01 kepada Agent 3 tanpa menunggu backlog minor.

Pembagian kepemilikan: Agent 1 tetap pemilik Stage 4B; branch ini mengelola Stage 4C–4G; Agent 3 pemilik Web Guru WG-01.

---

## ADDENDUM CI — 22 November 2026

### Hasil final run 44982023649 pada SHA 0038028...

- PHPUnit Stage 4C lifecycle/tenant boundary: **7 tests / 26 assertions PASS**.
- PHPUnit Stage 4C concurrency: **1 test FAIL / 6 assertions**.
- Penyebab: test menggunakan `DatabaseMigrations`; teardown mencoba rollback seluruh migration dan terkena migration `2026_11_20_100002_harden_device_lease_tenant_constraints.php` yang memang forward-only. Exception berasal dari `down()` baris 201 dan dipanggil dari `tests/TestCase.php:56`.
- Verifikasi engine: 71 base tables; non-InnoDB = 0; `all_tables_innodb=true`. Step tetap exit 1 karena workflow branch Stage4D mensyaratkan lima tabel Stage4B yang tidak ada pada branch ini.
- Run final: FAILURE; URL https://github.com/zane0184/cbt_november/actions/runs/44982023649.

### Patch perbaikan lanjutan

- `TeacherSubjectAssignmentConcurrencyTest.php` commit `11b927dd422075e4ef3139a511456596e5be1445`: menghapus `DatabaseMigrations`; test memverifikasi schema yang sudah disiapkan dan tidak melakukan rollback migration saat teardown.
- Workflow commit `af4e26ad974e0cded776ca552cafa77bcf4ae4d7`:
  - menyiapkan schema test dengan `migrate:fresh` hanya setelah preflight membuktikan database disposable, APP_ENV testing, host 127.0.0.1, dan nama DB/user cocok pola run;
  - required schema Stage4B hanya diwajibkan pada branch Agent1;
  - test room/session dan MySQL concurrency Stage4B hanya berjalan pada branch Agent1;
  - branch Stage4D tetap menjalankan suite Stage4C tanpa mengimpor source Stage4B.
- Run untuk commit test `11b927d...`: 44982509444 masih berjalan ketika diperiksa.
- Run untuk workflow `af4e26a...`: 44982525201 masih pending ketika diperiksa.
- Tidak ada hasil PASS atas patch lanjutan sampai run exact SHA selesai.

### Stage4B Agent1

- HEAD Agent1 saat pemeriksaan: `5d45b8404f225c105016a25976939afe3774abef`.
- Run exact HEAD: `44982090117`, masih in progress.
- Tidak ada file pada branch Agent1 yang diubah.
- Catatan untuk Agent1: test `ExamRoomSessionConcurrencyTest` juga menggunakan `DatabaseMigrations`; periksa risiko teardown terhadap migration forward-only yang sama. Jangan mengubah migration keamanan untuk membuat teardown lolos. Migration Stage4B `down()` memiliki guard histori, tetapi belum ditemukan test eksplisit yang membuktikan rollback kosong berhasil dan rollback dengan histori ditolak.

