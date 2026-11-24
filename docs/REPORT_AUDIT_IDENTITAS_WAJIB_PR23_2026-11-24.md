# Laporan Audit Identitas Wajib — PR #23

Tanggal audit: 24 November 2026  
Repository: `zane0184/cbt_november`  
PR: [#23 — P1 Web CBT completion](https://github.com/zane0184/cbt_november/pull/23)  
Branch audit: `work/p1-seven-targets-20261124`  
Exact source HEAD audit: `b78dd65c228f56b44eab7b75f0b017a89259491d`  
Base PR: `1c33e5d422175f9bd85d206230271a9fe5a4eecd`  
Status PR saat audit: OPEN, DRAFT, belum merge.  
Status dokumen: AUDIT DAN DESAIN; menunggu persetujuan owner sebelum implementasi.

> Keputusan owner yang menjadi kontrak audit:
> - Guru PNS/PPPK (ASN): NIP wajib 18 digit.
> - Guru Honorer: NIK wajib 16 digit.
> - Siswa: NISN wajib 10 digit.
> - Identitas unik dalam lingkup tenant.
> - Login masing-masing kategori harus memakai identitas tersebut.
> - Data identitas pribadi tidak boleh bocor melalui halaman, log, response API, atau pesan kesalahan.
> - Identitas Admin tetap memakai username; tidak diubah dalam pekerjaan ini.

---

## 1. Ringkasan eksekutif

**Status kontrak identitas pada source HEAD audit: NOT COMPLIANT.**

Source saat ini masih mengizinkan:
1. Pembuatan Guru tanpa status kepegawaian.
2. Pembuatan Guru ASN tanpa NIP.
3. Pembuatan Guru Honorer tanpa NIK.
4. Login Guru menggunakan username, bukan hanya identitas wajib berdasarkan kategorinya.
5. Penyimpanan identitas duplikat di database karena index NIP/NIK/NISN belum unique.

Test pada source saat ini bahkan secara eksplisit mengharapkan Guru tanpa NIP/NUPTK/NIK berhasil dibuat. Test login juga mengharapkan Guru ASN dan Honorer dapat masuk dengan username tanpa identitas wajib. Perilaku ini berlawanan dengan keputusan owner dan bukan sekadar kekurangan dokumentasi.

Temuan tambahan:
- Form UserManagement memiliki binding NUPTK, tetapi pada source sebelum commit audit terakhir properti publik `$nuptk` belum dideklarasikan. Commit terbaru `b78dd65` sudah menambahkan deklarasi properti itu; temuan ini dicatat sebagai perbaikan yang sudah masuk source, bukan pekerjaan terbuka.
- Alur import Guru/Siswa, perubahan identitas, dan pemulihan akun belum ditemukan pada route/component/service aktif yang diperiksa.
- Nomor identitas ditampilkan utuh pada halaman Manajemen Pengguna dan Profil Guru.
- CI backend pada HEAD sebelumnya `6fbd9e0` gagal pada dua assertion copy halaman login; CI untuk HEAD audit `b78dd65` masih berjalan saat laporan ini disusun. Build ZIP untuk HEAD `6fbd9e0` sukses, tetapi bukan bukti kelulusan backend atau kesiapan produksi.

Tidak ada source, migration, database, konfigurasi, atau deployment yang diubah dalam audit ini.

---

## 2. AUDIT

### 2.1 Pembuatan akun dan validasi

**Guru ASN**
- `UserManagement::rules()` hanya menerapkan `digits:18` pada NIP jika nilai diisi.
- NIP menggunakan `nullable/sometimes`, bukan wajib untuk PNS/PPPK.
- `employmentStatus` juga nullable/sometimes.
- `createUser()` menyimpan status, NIP, NIK, NUPTK sebagai nullable.
- Dengan demikian, Guru dapat dibuat tanpa status dan tanpa NIP.

**Guru Honorer**
- NIK hanya divalidasi `digits:16` jika diisi.
- Tidak ada `required_if:employmentStatus,HONORER`.
- Status Honorer tidak mewajibkan NIK pada source saat ini.

**Siswa**
- NISN sudah diwajibkan di Livewire melalui `required|digits:10`.
- Ini hanya terbukti pada jalur pembuatan akun Admin yang diperiksa. Belum ada bukti kontrak yang sama pada jalur import atau jalur tulis lain.

**Test yang bertentangan dengan owner**
- `AdminUiVerificationTest::admin_can_create_teacher_without_optional_nip_nuptk_or_nik()` mengharapkan Guru tanpa status/NIP/NIK berhasil dibuat.
- `WebAdminAuthTest::test_teacher_can_login_using_username_without_nip()` mengharapkan Guru ASN tanpa NIP dapat login menggunakan username.
- `WebAdminAuthTest::test_honorer_teacher_can_login_using_username_without_nik()` mengharapkan Guru Honorer tanpa NIK dapat login menggunakan username.

Temuan ini adalah **P0 — pelanggaran langsung terhadap kontrak identitas owner**.

### 2.2 Login Web, API, dan LoginIdentityResolver

Web dan API sama-sama menggunakan `LoginIdentityResolver`, sehingga resolver adalah batas autentikasi bersama yang tepat untuk diaudit.

Source resolver saat ini:
- Mengumpulkan kandidat dari username, NIP/NUPTK/NIK, dan NISN.
- Guru diterima bila cocok dengan username **atau** salah satu identitas profil.
- Pencarian NIP/NUPTK/NIK tidak memeriksa kecocokan `employment_status`.
- Akibatnya, Guru tanpa NIP/NIK tetap dapat login menggunakan username. Guru juga tidak dibatasi agar ASN hanya memakai NIP dan Honorer hanya memakai NIK.
- Siswa hanya diterima bila cocok dengan NISN.
- Admin Madrasah hanya diterima melalui username; aturan identitas Admin tidak diubah.

**Status:** autentikasi siswa dengan NISN sudah memiliki jalur dan regression test positif. Autentikasi Guru belum sesuai kontrak owner.

Resolver juga menggabungkan kandidat dari beberapa jenis identitas. Jika nilai username dan identitas kategori lain bertabrakan, resolver menolak ketika ada lebih dari satu kandidat. Ini fail-closed, tetapi belum menggantikan aturan validasi lintas-identitas saat pembuatan/pembaruan akun.

### 2.3 Database, migration, unique index, dan constraint

Migration yang diperiksa:

1. `2024_01_01_000001_create_profiles_tables.php`
   - `teacher_profiles.nip` nullable.
   - `teacher_profiles.nuptk` nullable.
   - `student_profiles.nisn` nullable.
   - Index `(tenant_id, employee_identifier)`, `(tenant_id, nis)`, dan `(tenant_id, nisn)` adalah index biasa, bukan UNIQUE.

2. `2026_11_11_000003_add_login_identity_fields.php`
   - Menambahkan NIK nullable.
   - Menambahkan index `(tenant_id, nik)`, bukan UNIQUE.
   - Unique tenant-scoped yang dibuat di migration ini adalah untuk `users(tenant_id, username)`, bukan NIP/NIK/NISN.

3. `2026_11_24_160001_add_teacher_employment_status.php`
   - Menambahkan `employment_status VARCHAR(16) NULL`.
   - Index `(tenant_id, employment_status)` bukan UNIQUE.
   - Tidak ada backfill status ke data Guru lama.
   - Tidak ada CHECK/constraint yang mengharuskan status ASN memiliki NIP atau status Honorer memiliki NIK.
   - Penambahan index berada di dalam kondisi `if (! hasColumn)`; jika kolom sudah ada tetapi index belum ada, migration tidak memperbaiki index yang hilang.
   - `down()` menghapus kolom status beserta datanya; rollback setelah klasifikasi status terisi berisiko menghilangkan data tersebut.

**Kesimpulan database:** keunikan NIP/NIK/NISN belum ditegakkan di database. Rule::unique pada Livewire tidak mencegah race condition dari dua request bersamaan atau jalur tulis lain. Database saat ini dapat menyimpan NISN duplikat; test duplikasi siswa membuktikan dua profile dengan NISN sama dapat dibuat, lalu resolver menolak login karena kandidat ambigu. Itu bukan pengganti unique constraint.

### 2.4 Tenant dan duplikasi lintas instalasi

- Validasi Livewire membatasi pencarian duplikat ke `tenant_id`.
- Resolver mengikat login ke `LocalInstallationIdentity` dan menolak lebih dari satu tenant madrasah lokal dalam satu database.
- Ini sesuai model operasional satu instalasi/database per madrasah dengan `tenant_id` sebagai defense-in-depth.
- Nilai identitas yang sama pada dua madrasah berbeda seharusnya tetap boleh karena berada pada database instalasi yang berbeda; tidak boleh dibuat unique global lintas instalasi.
- Test lintas madrasah perlu menggunakan dua database/instalasi terisolasi. Test dua tenant dalam satu database tidak tepat untuk membuktikan keberterimaan karena source resolver memang menolak konfigurasi multi-tenant lokal dalam satu DB.

Belum ditemukan test yang membuktikan identitas sama dapat dipakai pada dua database instalasi yang berbeda.

### 2.5 Import, update, pemulihan akun, dan perubahan identitas

Audit tree dan source aktif:
- Route Admin memuat halaman `UserManagement`, tetapi component yang diperiksa hanya memiliki pembuatan akun dan daftar pengguna; belum ada edit/update identitas, nonaktif/restore, atau reset password.
- `TeacherProfilePage` hanya mengizinkan Guru memperbarui nomor telepon. Identitas kepegawaian ditampilkan read-only.
- Route autentikasi Web dan API yang diperiksa berisi login/logout serta endpoint `me`; tidak ditemukan route reset password/pemulihan akun.
- Tidak ditemukan service/controller/import Guru atau Siswa. Importer aktif yang ditemukan adalah importer bank soal (`ExcelQuestionImporter` dan `QuestionImportService`), bukan import akun Guru/Siswa.
- Tidak ditemukan alur perubahan NIP/NIK/NISN yang terotorisasi dan diaudit.

**Status:** MISSING/BLOCKED; belum dapat dinilai berfungsi.

### 2.6 Isolasi tenant dan konsistensi akun

- UserManagement memeriksa role tepat satu `ADMIN_MADRASAH` dan permission `user.manage`.
- Query daftar pengguna dibatasi tenant pengguna login.
- Rule::unique identitas di Livewire menggunakan tenant aktif.
- Resolver mengikat pencarian identitas ke tenant yang terdaftar pada instalasi.
- Namun database belum memiliki unique constraint tenant-scoped untuk identitas. Perlindungan race dan jalur tulis lain belum memadai.
- Tidak ada test dua koneksi MySQL yang mencoba menyimpan identitas sama secara bersamaan.

### 2.7 Privasi dan data pribadi

Temuan:
- Halaman `livewire/admin/user-management.blade.php` menampilkan NIP, NIK, dan NISN dalam bentuk penuh.
- Halaman `livewire/teacher/portal-profile.blade.php` menampilkan Employee Identifier, NIP, NIK, dan NUPTK dalam bentuk penuh.
- Halaman login menggunakan `old('identifier')`; controller mengembalikan input identifier ke session setelah login gagal. Jika identifier adalah NIP/NIK/NISN, nilai lengkap dapat muncul kembali pada halaman.
- Web rate limiter memakai identifier mentah dalam key `login:web:{identifier}|IP`; API menggunakan hash untuk key. Key cache mentah berpotensi terekspos melalui inspeksi/cache diagnostics.
- Response sukses API login dan endpoint `me` yang diperiksa tidak mengembalikan NIP/NIK/NISN. Error autentikasi umumnya generik.
- `AuditService` menerima `old_values/new_values` bebas. Belum ada guard untuk mencegah caller memasukkan NIP/NIK/NISN mentah.
- Pembuatan akun di UserManagement belum memanggil AuditService. Tidak ada audit khusus perubahan identitas karena alur perubahan identitas belum tersedia.

**Risiko:** HIGH untuk tampilan identitas mentah dan penyimpanan identifier mentah pada cache key Web; MEDIUM untuk audit log generik tanpa redaction guard. Tidak ditemukan bukti bahwa API login saat ini mengirim identitas penuh pada response sukses.

### 2.8 Data lama dan kompatibilitas instalasi

- Kolom `employment_status` nullable dan tidak di-backfill.
- Tidak ada bukti source migration yang mengklasifikasikan data lama berdasarkan verifikasi madrasah.
- Kolom identitas lama nullable dan belum memiliki unique constraint.
- Belum ada preflight data aktual untuk menghitung NIP/NIK/NISN kosong, format salah, duplikat, atau status tidak cocok.
- Tidak ada database produksi/representatif yang dibaca dalam audit ini; kondisi data aktual tidak boleh diasumsikan.

Upgrade harus dilakukan bertahap setelah preflight. Jangan otomatis menganggap Guru yang memiliki NIP/NIK tertentu sebagai status ASN/Honorer tanpa verifikasi data madrasah.

### 2.9 Regression test dan CI

Test yang sudah ada:
- Pembuatan Guru PNS dengan NIP valid.
- Pembuatan Guru Honorer dengan NIK valid.
- Pembuatan Siswa dengan NISN valid.
- Login Siswa dengan NISN.
- Penolakan login Siswa memakai username.
- Penolakan login jika NISN duplikat (resolver fail-closed).
- Penolakan role ganda dan tenant/install ambiguity.

Test yang belum ditemukan:
- Penolakan ASN tanpa NIP.
- Penolakan ASN dengan NIP bukan 18 digit.
- Penolakan Honorer tanpa NIK.
- Penolakan Honorer dengan NIK bukan 16 digit.
- Penolakan Siswa tanpa NISN atau NISN bukan 10 digit pada jalur pembuatan akun.
- Penolakan duplikasi NIP/NIK/NISN saat create/update/import oleh database.
- Race test dua koneksi MySQL.
- Identitas sama pada dua database instalasi berbeda.
- Login ASN hanya dengan NIP; Honorer hanya dengan NIK; Siswa hanya dengan NISN; Guru dengan username harus ditolak.
- Pemulihan akun/perubahan identitas, audit redaction, dan preservation histori Attempt/Answer/Score.
- Uji bahwa identitas tidak tampil utuh di halaman, session old input, cache key, log, error, atau response API.

**Bukti CI yang tersedia:**
- HEAD `6fbd9e0174bfa7ea37dc6415ba3fe2cff4d6dc5e`: Tenant Backend CI run [45341915156](https://github.com/zane0184/cbt_november/actions/runs/45341915156) FAILED. Fokus login: 2 failed, 26 passed, 89 assertions; dua kegagalan adalah assertion copy lama pada halaman login di `StudentWebLoginTest` dan `WebAdminAuthTest`. Full PHPUnit suite dilewati karena smoke step gagal.
- HEAD yang diaudit `b78dd65c228f56b44eab7b75f0b017a89259491d`: Tenant Backend CI run [45342213250](https://github.com/zane0184/cbt_november/actions/runs/45342213250) masih IN_PROGRESS ketika laporan ini dibuat.
- ZIP workflow untuk HEAD `6fbd9e0` run [45341915166](https://github.com/zane0184/cbt_november/actions/runs/45341915166) SUCCESS. Ini hanya bukti build arsip, bukan kelulusan test backend atau UAT.
- Tidak ada test yang dijalankan langsung oleh audit ini. Hasil di atas adalah bukti GitHub Actions pada exact SHA yang disebutkan.

---

## 3. DESAIN PERBAIKAN

### 3.1 Kontrak validasi tunggal

Buat satu policy/validator domain yang dipakai oleh UI Livewire, API, import, service, dan jalur perubahan identitas. Jangan menduplikasi aturan di masing-masing controller.

| Role | Kategori | Field wajib | Aturan |
|---|---|---|---|
| GURU | PNS | employment_status=PNS | NIP 18 digit |
| GURU | PPPK | employment_status=PPPK | NIP 18 digit |
| GURU | HONORER | employment_status=HONORER | NIK 16 digit |
| SISWA | — | — | NISN 10 digit |
| ADMIN_MADRASAH | — | — | Tetap username; tidak diubah |

NUPTK tetap field tambahan sesuai data yang dimiliki, bukan pengganti NIP/NIK dan bukan login identifier berdasarkan keputusan ini.

Untuk kategori Guru, status kepegawaian wajib dipilih dan harus konsisten dengan identitas:
- PNS/PPPK: NIP wajib; NIK tidak menjadi pengganti.
- Honorer: NIK wajib; NIP tidak menjadi pengganti.
- Siswa: NISN wajib.

### 3.2 Kontrak database

- Tambahkan unique constraint tenant-scoped untuk NIP, NIK, dan NISN.
- Pastikan unique index bekerja untuk semua jalur tulis dan request concurrent; jangan hanya mengandalkan validasi aplikasi.
- Tambahkan CHECK constraint/jenis kolom yang menegakkan format dan pasangan status-identitas sesuai versi MySQL yang didukung.
- NIP/NIK yang tidak berlaku pada kategori lain disimpan NULL, bukan string kosong.
- Pertahankan identitas sebagai string agar leading zero tidak hilang.
- Identitas yang sudah dipakai tetap terlindungi dari duplikasi pada data soft-deleted/historis sesuai kebijakan retensi.
- Jangan membuat unique global lintas instalasi. Unique berlaku pada tenant/database madrasah masing-masing.
- Evaluasi enkripsi field-level untuk nilai identitas dan keyed lookup digest/HMAC untuk pencarian serta unique index, agar nilai mentah tidak harus tersimpan terbuka. Desain key rotation harus ditetapkan sebelum implementasi.

### 3.3 Migrasi data lama

Sebelum constraint wajib diberlakukan pada instalasi existing:
1. Jalankan preflight read-only per database.
2. Laporkan jumlah Guru tanpa status, tanpa NIP/NIK, format salah, NIP/NIK ganda, dan Siswa tanpa NISN/format salah/duplikat.
3. Jangan menebak status ASN/Honorer dari nama atau field yang ambigu.
4. Sediakan proses koreksi/konfirmasi Admin yang berotorisasi.
5. Simpan checkpoint hasil preflight dan backup sebelum migration.
6. Terapkan constraint setelah konflik selesai.
7. Uji fresh install dan upgrade existing pada database disposable MySQL 8/InnoDB.

Tidak boleh migration produksi pada tahap audit/desain ini.

### 3.4 Login

- Admin Madrasah tetap login dengan username.
- Guru ASN hanya di-resolve dari NIP 18 digit dan status PNS/PPPK yang cocok.
- Guru Honorer hanya di-resolve dari NIK 16 digit dan status HONORER yang cocok.
- Siswa hanya di-resolve dari NISN 10 digit.
- Username Guru tidak menjadi fallback.
- Web dan API menggunakan resolver yang sama.
- Pesan kegagalan tetap generik; jangan mengungkap apakah NIP/NIK/NISN terdaftar atau kategori akun cocok.
- Konflik antar-identitas harus fail-closed dan ditangani saat create/update, bukan baru diketahui saat login.
- Jangan mengubah username atau mekanisme login Admin.

### 3.5 Import dan UI

- Import Guru/Siswa belum tersedia pada source yang diperiksa. Saat dibuat, importer harus memanggil validator yang sama, melakukan preview, menolak duplikat, transaksional, idempotent, dan menghasilkan error report tanpa nilai identitas lengkap.
- Form create/update menampilkan field wajib sesuai kategori.
- NIP/NIK/NISN tidak ditampilkan utuh pada tabel/list; gunakan masking. Nilai penuh hanya ditampilkan pada alur yang benar-benar berwenang dan dibutuhkan.
- Jangan mengembalikan identifier melalui `old()` setelah login gagal.
- Jangan menyimpan identifier mentah dalam cache key/log/error/analytics.
- Response API tidak mengirim NIP/NIK/NISN kecuali kontrak endpoint secara eksplisit mengharuskannya dan memiliki authorization khusus.

### 3.6 Perubahan identitas, pemulihan akun, dan histori

- Perubahan identitas hanya oleh Admin berizin melalui flow khusus dengan alasan dan verifikasi.
- Gunakan transaksi, row lock, validasi unique tenant, serta audit event.
- Audit mencatat actor, target user/profile, tipe field, waktu, alasan, hasil, dan masked/fingerprint; jangan simpan nilai mentah pada old/new JSON.
- Pertahankan user_id/profile_id dan seluruh relasi akademik. Jangan membuat ulang user untuk mengganti NIP/NIK/NISN.
- Riwayat identitas lama perlu disimpan sebagai histori perubahan yang tidak menghapus referensi lama.
- Reset password/pemulihan akun harus terotorisasi dan tercatat, tanpa mengungkap identitas penuh atau credential.
- Jangan mengubah Attempt, Answer, Score, exam participant, atau histori akademik ketika identitas diperbarui.

---

## 4. IMPLEMENTASI

**NOT AUTHORIZED — BELUM DILAKUKAN.**

Tidak ada file aplikasi, migration, database, konfigurasi, workflow, atau deployment yang diubah oleh audit ini. Tidak ada commit implementasi, tidak ada merge PR, dan tidak ada migration produksi.

Implementasi baru boleh dimulai setelah owner menyetujui desain dan urutan preflight/migrasi.

---

## 5. PENGUJIAN

### 5.1 Rencana test minimum

1. Guru PNS dengan NIP 18 digit valid berhasil dibuat.
2. Guru PNS tanpa NIP ditolak.
3. Guru PNS dengan NIP 17/19 digit, huruf, spasi, atau format salah ditolak.
4. Guru PPPK dengan NIP valid berhasil dibuat; tanpa NIP ditolak.
5. Guru Honorer dengan NIK 16 digit valid berhasil dibuat.
6. Guru Honorer tanpa NIK ditolak.
7. Guru Honorer dengan NIK bukan 16 digit ditolak.
8. Siswa dengan NISN 10 digit valid berhasil dibuat.
9. Siswa tanpa NISN atau NISN bukan 10 digit ditolak.
10. Duplikasi NIP/NIK/NISN pada tenant yang sama ditolak oleh aplikasi dan database.
11. Dua request bersamaan yang menyimpan identitas sama: hanya satu yang commit.
12. Identitas sama pada dua instalasi/database madrasah berbeda diperbolehkan.
13. Guru ASN login hanya dengan NIP; username/NIK/NUPTK tidak dapat menjadi fallback.
14. Guru Honorer login hanya dengan NIK; username/NIP/NUPTK tidak dapat menjadi fallback.
15. Siswa login hanya dengan NISN; username ditolak.
16. Admin tetap login dengan username tanpa perubahan.
17. Web login dan API login memberi hasil identik.
18. Import Guru/Siswa memakai validator yang sama, preview konflik, idempotensi, rollback batch, dan error report redacted.
19. Pemulihan akun dan perubahan identitas diuji untuk authorization, audit, idempotensi, dan rate limit.
20. Perubahan NIP/NIK/NISN tidak mengubah user_id/profile_id dan tidak menghilangkan histori ujian, jawaban, nilai, serta akademik.
21. Halaman, HTML lama input, cache, log, exception, dan response API tidak memuat identitas penuh tanpa otorisasi.
22. Fresh install dan upgrade existing diuji pada MySQL 8/InnoDB, termasuk CHECK, unique constraint, soft-delete, dan rollback plan.

### 5.2 Status bukti

- Test langsung oleh auditor: NOT RUN.
- CI pada SHA `b78dd65c228f56b44eab7b75f0b017a89259491d`: IN PROGRESS pada saat laporan dibuat.
- CI sebelumnya pada SHA `6fbd9e0174bfa7ea37dc6415ba3fe2cff4d6dc5e`: FAIL, 2 failed / 26 passed / 89 assertions pada focused login smoke; full suite skipped.
- ZIP build pada SHA `6fbd9e0`: SUCCESS, tetapi bukan bukti feature acceptance.
- Database produksi/representatif: NOT INSPECTED; tidak ada SQL yang dijalankan.

Tidak ada status PASS yang diberikan untuk skenario yang belum benar-benar diuji.

---

## 6. Daftar file/source yang diperiksa

SHA di bawah adalah blob SHA pada branch/commit audit, bukan SHA file lokal.

| File | Blob SHA |
|---|---|
| `backend-laravel/app/Livewire/Admin/UserManagement.php` | `899b011fe40492c570c9611dbde90554ebffe1a6` |
| `backend-laravel/app/Models/TeacherProfile.php` | `a5f232895d9e0702ad81dd939344780fe5e86331` |
| `backend-laravel/app/Models/StudentProfile.php` | `4237e764cf722817cd1ce27b4ffa134ad2d776c7` |
| `backend-laravel/app/Models/User.php` | `dec82ba26e785708945bdea1bc0e34a1ed20458d` |
| `backend-laravel/app/Services/LoginIdentityResolver.php` | `1edf12db91b2e6d751844153de457104aa3001b9` |
| `backend-laravel/app/Services/AuditService.php` | `e968cc401cdd8b04ab1322171d5f475332a55372` |
| `backend-laravel/app/Models/AuditLog.php` | `c8789bc5b78b915dfbb8ade7cfd031ddf72c4a4c` |
| `backend-laravel/app/Http/Controllers/AuthWebController.php` | `d9512b27463b1b732ff49950243553ae750dc3a1` |
| `backend-laravel/app/Http/Controllers/Api/V1/AuthController.php` | `7680c93bc3f88c43ab47cb1a4c24a603947f79de` |
| `backend-laravel/app/Livewire/Teacher/TeacherProfilePage.php` | `e5bc6d51c740b83d9ec0dd72b0fbb3f2929ff5b1` |
| `backend-laravel/resources/views/auth/login.blade.php` | `8e02e832b42993c87dca490c0a81d42b8055cd44` |
| `backend-laravel/resources/views/livewire/admin/user-management.blade.php` | `4287ec71afc292b40fbaff036363e1ec36ffd141` |
| `backend-laravel/resources/views/livewire/teacher/portal-profile.blade.php` | `97001a504f11c19c686d35bd83fda824c4590203` |
| `backend-laravel/database/migrations/2024_01_01_000001_create_profiles_tables.php` | `6731605480aeb104d3e12db6cfef5903bff54262` |
| `backend-laravel/database/migrations/2026_11_11_000003_add_login_identity_fields.php` | `03c8a08046357fd2138b2c0135fe8812315bcaf6` |
| `backend-laravel/database/migrations/2026_11_24_160001_add_teacher_employment_status.php` | `3284e6b050f2678fa455d91bcb40e43ea2947736` |
| `backend-laravel/routes/web.php` | `f6df667627c7b134ce7ed6f90f916a9bab2730a9` |
| `backend-laravel/routes/api.php` | `b35d35704483217061730aeff2281d9033aeb621` |
| `backend-laravel/routes/teacher.php` | `a1679492a6fded0c0c58a4ac790f8654e143b6e0` |
| `backend-laravel/tests/Feature/AdminUiVerificationTest.php` | `1fd857c73ef6ad9336fd5cd2d308aaa1af26aeec` |
| `backend-laravel/tests/Feature/WebAdminAuthTest.php` | `1762d3972f956f6387287e8f22e491bae9cfde5d` |
| `backend-laravel/tests/Feature/StudentWebLoginTest.php` | `b92743a1001ee224d903795f06bf273d3f9cc495` |
| `backend-laravel/tests/Feature/AuthTest.php` | `c3d5020445e3c0e49538a207bfac929f41e01093` |
| `backend-laravel/tests/Feature/MultiTenantAuthTest.php` | `448cd1327e3169b6d5d4a6d741593026feb1d7b2` |

Additional pemeriksaan:
- `backend-laravel/app/Http/Controllers/InstallerController.php`
- `backend-laravel/app/Services/InstallerService.php`
- `backend-laravel/app/Livewire/Admin/StudentRombelManagement.php`
- `backend-laravel/app/Livewire/Admin/TeacherSubjectAssignmentManagement.php`
- `backend-laravel/app/Services/QuestionImportService.php`
- `backend-laravel/app/Services/ExcelQuestionImporter.php`
- `docs/MASTER_SPESIFIKASI_FITUR_WEB_ADMIN_MADRASAH_2026-11-22.md`
- PR #23 metadata, changed-file list, diff, comments, commit history, branch HEAD, dan GitHub Actions.

---

## 7. Risiko prioritas dan pekerjaan belum selesai

| Prioritas | Temuan | Dampak |
|---|---|---|
| P0 | Guru dapat dibuat tanpa NIP/NIK dan status | Akun tidak memenuhi identitas wajib |
| P0 | Guru login memakai username | Login tidak sesuai kategori identitas |
| P0 | NIP/NIK/NISN belum unique di database | Duplikasi dan race condition |
| P1 | Identitas lama belum dipreflight/backfill | Upgrade berisiko memblokir akun lama atau salah klasifikasi |
| P1 | Import Guru/Siswa belum ditemukan | Jalur import belum memenuhi kontrak identitas |
| P1 | Perubahan identitas/pemulihan akun belum tersedia | Operasional akun dan histori belum terjamin |
| P1 | Identitas penuh tampil di halaman | Risiko paparan data pribadi |
| P1 | Audit identity create/change belum tersedia | Perubahan tidak dapat ditelusuri |
| P2 | CI pada SHA audit belum selesai saat laporan dibuat | Belum ada bukti test lengkap pada exact HEAD |
| P2 | Test concurrency, cross-install, privacy, recovery belum ada | Acceptance gate belum terpenuhi |

### Urutan tindak lanjut setelah persetujuan owner

1. Kunci kontrak identitas dan shared validator.
2. Susun preflight read-only untuk data existing per instalasi.
3. Desain migration additive/upgrade dengan unique constraints dan CHECK.
4. Perbaiki UserManagement UI/Livewire dan resolver Web/API.
5. Tambahkan importer Guru/Siswa dan audit perubahan identitas.
6. Tambahkan alur recovery/reset dan identity change yang menjaga histori.
7. Tambahkan privacy redaction/masking di halaman, cache, log, response, dan error.
8. Jalankan test MySQL 8/InnoDB pada exact commit; baru setelah semua PASS lanjut ke staging/UAT.

**Gerbang berikutnya:** menunggu persetujuan owner atas desain ini. Tidak ada implementasi yang diizinkan oleh laporan ini.
