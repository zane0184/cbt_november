# P1 — Addendum Koreksi Identitas Guru, Login, dan Status CI

Tanggal: 24 November 2026
Repository: `zane0184/cbt_november`
Branch: `work/p1-seven-targets-20261124`
HEAD laporan: `40abf8e9499a1e1a9c974f16fa2a441cc1155ff8`
PR: #23 (OPEN / DRAFT; tidak di-merge)

## 1. AUDIT

### Kontradiksi yang direkonsiliasi
Master Web Admin menyatakan NIP/NUPTK opsional bila Guru tidak memilikinya, sedangkan NIK hanya dikumpulkan bila ada kebutuhan sah dan perlindungan. Laporan `REPORT_AUDIT_IDENTITAS_WAJIB_PR23_2026-11-24.md` pernah merekomendasikan sebaliknya, yaitu NIP wajib untuk PNS/PPPK dan NIK wajib untuk Honorer. Rekomendasi tersebut tidak sesuai dengan master spec dan **disupersede oleh addendum ini**. Laporan lama tetap dipertahankan sebagai histori audit, tidak dihapus atau ditimpa.

### Kondisi source HEAD
- `UserManagement::rules()` tidak mewajibkan employment status, NIP, NUPTK atau NIK.
- Bila field identitas diisi, format digit dan uniqueness tenant-local divalidasi di aplikasi. Bila kosong, disimpan NULL.
- `TeacherProfile` dibuat dengan kolom identifier nullable. `employee_identifier` diisi dari identifier yang ada, atau NULL bila semuanya kosong.
- Form UserManagement menampilkan NIP/NUPTK opsional dan NIK hanya bila diperlukan; status kepegawaian opsional.
- `LoginIdentityResolver` mengizinkan Guru login menggunakan username atau identifier profil yang tersedia. NISN tetap identitas Siswa; username Admin tetap.
- Semua lookup Guru/NIP/NUPTK/NIK dibatasi ke `tenant_id` lokal dan `deleted_at IS NULL`; resolver tetap memeriksa exact role dan installation identity.
- Halaman login menjelaskan Admin/Guru menggunakan username, Siswa menggunakan NISN.
- Tidak ada perubahan pada schema/migration untuk koreksi ini.

Batasan audit: database instalasi tidak dibaca atau diubah. Keunikan kolom NIP/NUPTK/NIK di database belum dibuktikan sebagai UNIQUE constraint untuk seluruh jalur tulis. Rule aplikasi saja tidak cukup untuk menutup race dari importer atau writer lain; audit/preflight index dan unique enforcement masih diperlukan sebelum klaim integritas penuh.

## 2. DESAIN

Kontrak autentikasi yang dipakai patch:
- Admin Madrasah: username.
- Guru: username sebagai kredensial utama; NIP/NUPTK/NIK hanya alias opsional jika memang tercatat dan tidak ambigu.
- Siswa: NISN.

NIP/NUPTK tidak boleh menjadi prasyarat akun Guru. NIK bukan prasyarat universal. Identifier yang kosong harus NULL, bukan string kosong. Tidak boleh memutus akun lama tanpa migration upgrade terencana, preflight, backup dan komunikasi perubahan login.

Catatan: master spec memperbolehkan NIP/NUPTK opsional; user management pada source masih memberi label username optional. Perlu menyelaraskan UX dengan password/login policy supaya Guru memperoleh username yang stabil dan dapat diingat. Jangan membuat akun Guru tanpa cara login yang dapat diambil kembali.

## 3. IMPLEMENTASI

Perubahan berada pada:
- `backend-laravel/app/Livewire/Admin/UserManagement.php`
- `backend-laravel/app/Services/LoginIdentityResolver.php`
- `backend-laravel/resources/views/auth/login.blade.php`
- `backend-laravel/resources/views/livewire/admin/user-management.blade.php`
- `backend-laravel/tests/Feature/AdminUiVerificationTest.php`
- `backend-laravel/tests/Feature/WebAdminAuthTest.php`
- `backend-laravel/tests/Feature/StudentWebLoginTest.php`

Backup byte-exact dibuat sebelum patch pada:
`backend-laravel/backup_before_patch/P1_ADM06_OPTIONAL_ID_LOGIN_USERNAMES_20261124/`
Masing-masing file source yang berubah mempunyai salinan `.before`, termasuk component, resolver, Blade, tests dan migration file yang hanya diinspeksi. Konten backup diverifikasi identik dengan source sebelum perubahan.

Tidak ada migration dibuat/dijalankan, trigger, data mutation, deployment, PR merge, atau perubahan P0 base.

## 4. PENGUJIAN

### Hasil yang terbukti pada exact HEAD
- Build Standalone Web CBT Deployment ZIP workflow #45342397839: SUCCESS untuk SHA `40abf8e9499a1e1a9c974f16fa2a441cc1155ff8`. Ini hanya membuktikan pipeline packaging selesai, bukan kelulusan fitur.
- Tenant Backend CI #45342397865 pada SHA yang sama: masih berjalan pada pemeriksaan terakhir. Langkah container MySQL, checkout, PHP 8.3, isolated DB check, Composer validation/install, disposable installer E2E DB, dan focused Web login/tenant smoke test berstatus SUCCESS. Full PHPUnit sedang berjalan; belum ada angka akhir yang dapat diklaim.
- CI sebelumnya #45341915156 pada SHA lama `6fbd9e0...` gagal pada dua assertion teks halaman login yang masih mengharapkan copy lama; itu sudah diselaraskan di commit berikutnya.
- CI #45342320691 pada SHA `d2e6c088...` dibatalkan di saat startup container MySQL, sebelum PHPUnit dijalankan. Itu bukan test failure.
- Tidak ada hasil PHPUnit penuh exact HEAD yang tersedia pada saat laporan ini dibuat.

### Test yang harus lulus sebelum ADM-06 diterima
- Pembuatan Guru dengan username, tanpa status, tanpa NIP/NUPTK/NIK.
- Pembuatan Guru dengan NIP atau NUPTK opsional.
- NIK hanya diisi bila dibutuhkan.
- Username duplikat ditolak; username kosong tidak menghasilkan akun tanpa login identity yang stabil.
- Login Guru username untuk ASN dan Honorer.
- Login Siswa NISN dan Admin username tetap.
- Lookup identifier opsional tidak bisa cross-installation/tenant, ambigu fail-closed, akun nonaktif/soft-deleted ditolak.
- Import/API writer, race unique identity, profile list masking dan audit redaction.

## 5. RISIKO / PEKERJAAN TERBUKA

1. Pastikan username Guru tidak benar-benar opsional pada flow UI/validation bila username merupakan login utama. Auto-generation fallback harus terlihat, unik dan dapat disampaikan kepada Admin/Guru dengan aman; jangan menampilkan identifier rahasia.
2. Unique DB constraint tenant-local untuk identifier optional belum diverifikasi.
3. Tabel/list Guru dan profil masih perlu audit masking NIP/NIK/NUPTK; jangan tampilkan NIK penuh tanpa kebutuhan.
4. Login rate limiter/cache key dan audit log harus tidak merekam identifier sensitif mentah.
5. Migration upgrade lama tidak boleh memaksa backfill NIP/NIK; setiap data legacy harus direkonsiliasi dengan preflight.
6. Hasil full PHPUnit exact HEAD masih menunggu workflow.

## 6. STATUS

ADM-06 identity subtask: **PATCH ADA; CI FOCUSED PASS; FULL PHPUNIT BELUM TERVERIFIKASI; ADM-06 keseluruhan tetap PARTIAL**. Tidak ada klaim siap produksi atau siap distribusi.
