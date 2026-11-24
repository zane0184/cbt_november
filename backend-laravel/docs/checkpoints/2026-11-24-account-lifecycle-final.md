# Penutupan Lifecycle Akun — 24 November 2026

## Status

**Lifecycle Akun: ACCEPTANCE CI PASS pada source SHA `0250dc257cc7292d960cacf2c20eda782ee8a25d`.**

Status ini berarti acceptance backend yang tercantum di bawah telah lulus pada CI disposable PHP 8.3/MySQL 8.0.46. Ini **bukan** deklarasi siap produksi. Tidak ada migration produksi, deployment, merge, atau perubahan konfigurasi produksi yang dilakukan.

- Repository: `zane0184/cbt_november`
- Branch: `work/solo-web-cbt-completion-20261123-execution`
- HEAD: `0250dc257cc7292d960cacf2c20eda782ee8a25d`
- Commit: https://github.com/zane0184/cbt_november/commit/0250dc257cc7292d960cacf2c20eda782ee8a25d
- CI: https://github.com/zane0184/cbt_november/actions/runs/45299765024
- Workflow run: `45299765024`
- Job: `laravel-mysql` (`138129050960`) — SUCCESS

## AUDIT

### Bukti CI yang diverifikasi ulang

Job CI memiliki seluruh 21 langkah berstatus SUCCESS, termasuk focused test, full PHPUnit, pemeriksaan InnoDB, dan cleanup. Log mencatat:

| Pemeriksaan | Hasil |
|---|---:|
| PHP | 8.3.37 |
| MySQL | 8.0.46 |
| Web login & tenant smoke | 30 passed / 99 assertions |
| Admin Livewire reauthorization | 5 passed / 5 assertions |
| Teacher result Livewire reauthorization | 4 passed / 8 assertions |
| Lifecycle akun + lisensi + concurrency | 25 passed / 271 assertions |
| Web/API authentication + tenant | 30 passed / 121 assertions |
| PHPUnit penuh | 565 passed / 2.544 assertions |
| Engine tabel disposable | `tenants ENGINE=InnoDB` |

Tidak ada test lokal yang dipakai sebagai pengganti bukti CI tersebut.

### Pemeriksaan source lifecycle

- Migration baru menambah `is_active` (default true), `must_change_password` (default false), dan `auth_version` (default 1) ke `users`.
- Model `User` memiliki casts dan default atribut yang sesuai.
- Web login memeriksa akun aktif, mengunci ulang user dalam transaksi sebelum autentikasi final, menyimpan `account_auth_version`, dan mengarahkan akun yang diwajibkan mengganti password ke halaman khusus.
- API login memeriksa status akun dan menerbitkan token Sanctum dengan ability marker `account-version:<n>`.
- Middleware tenant memeriksa status aktif, versi sesi/token, dan kewajiban ganti password pada jalur terlindungi; sesi lama ditolak setelah versi berubah.
- Reset dan deaktivasi dilakukan melalui Livewire Admin dengan verifikasi actor role tunggal `ADMIN_MADRASAH`, permission `user.manage`, target role tunggal `GURU` atau `SISWA`, serta tenant yang sama.
- Reset menghasilkan password sementara acak 48 karakter, hanya dikembalikan untuk tampilan satu kali, menyimpan hash, memaksa pergantian password, menaikkan `auth_version`, dan mencabut token/sesi target.
- Deaktivasi menaikkan `auth_version`, mencabut token/sesi target, dan mempertahankan data akademik. Aktivasi menaikkan versi lagi dan tidak memulihkan kredensial lama.
- Attempt start memeriksa status akun dan `must_change_password` setelah mengunci baris User.
- Reset/deaktivasi menolak jika terdapat Attempt resmi `IN_PROGRESS`; test memeriksa Attempt, Answer, Score, Device Lease, dan fencing tidak berubah pada penolakan.
- Audit event yang digunakan meliputi `RESET_LOCAL_USER_PASSWORD`, `DEACTIVATE_LOCAL_USER`, `ACTIVATE_LOCAL_USER`, dan `FORCE_CHANGE_PASSWORD`. Password plaintext tidak ditulis ke payload audit.

### Route dan middleware

- Route Web portal dan license status/refresh berada di bawah middleware `tenant`; route perubahan password berada di bawah `auth` dan `tenant`.
- Route API terlindungi menggunakan `auth:sanctum` dan `tenant`; endpoint lisensi dan CBT juga berada dalam kelompok `license`.
- Login Web/API memeriksa akun aktif pada controllernya. Logout sengaja tetap tersedia sebagai jalur keluar.
- Test license-route membuktikan akun nonaktif dan sesi Web dengan auth_version lama tidak dapat mengakses status/refresh lisensi.

### Migration dan kompatibilitas instalasi

Migration adalah migration tambahan pada tabel `users`, tidak mengubah atau menghapus data lama secara eksplisit. Kolom memiliki default yang membuat akun lama tetap aktif, tidak dipaksa mengganti password, dan memulai auth version 1.

CI menjalankan rangkaian migration pada database MySQL disposable. Ini membuktikan kompatibilitas terhadap rangkaian schema yang dibangun dari migration repository pada CI, bukan uji upgrade langsung terhadap salinan database produksi madrasah. Migration tidak dijalankan pada database produksi.

### Backup

Backup byte-exact untuk semua file sumber yang telah ada sebelum implementasi utama diverifikasi dengan membandingkan Git blob SHA backup terhadap Git blob SHA file pada baseline sebelum lifecycle. Sepuluh pasangan utama semuanya MATCH; rinciannya ada di manifest terpisah.

File baru (controller password, migration lifecycle, view pergantian password, dan test lifecycle baru) tidak mempunyai file lama untuk dibackup. Test yang kemudian dikembangkan memiliki snapshot bertahap dalam direktori backup.

## DESAIN yang diterapkan

1. Admin Madrasah dapat reset password akun Guru/Siswa dalam tenant sendiri.
2. Password sementara hanya ditampilkan sekali; user wajib menggantinya sebelum mengakses fitur lain.
3. Seluruh sesi/token target dicabut; sesi/token pengguna lain tidak disentuh.
4. Reset ditolak ketika siswa memiliki Attempt `IN_PROGRESS`.
5. Deaktivasi menolak Attempt `IN_PROGRESS`; akun nonaktif tidak dapat login atau memakai sesi/token lama.
6. Aktivasi kembali tidak menghidupkan sesi/token terdahulu.
7. Data akademik dan histori tidak dihapus oleh deaktivasi.

## IMPLEMENTASI — file yang berubah pada rentang baseline hingga HEAD

| File | Status | Blob SHA saat HEAD | Blob SHA baseline |
|---|---|---|---|
| `.github/workflows/tenant-backend-ci.yml` | modified | `abbc441d20f1e7c760c869c620af15f062206576` | `e21c6be218a374bafc19813fda63621fe3884d6a` |
| `backend-laravel/app/Http/Controllers/AccountPasswordController.php` | new | `246dc95f6636bbe3013274e67cdf17a811576738` | `—` |
| `backend-laravel/app/Http/Controllers/Api/V1/AuthController.php` | modified | `ddf5fdea6a8de0f36dd2e4dfd1f9d0845cc8cee0` | `7680c93bc3f88c43ab47cb1a4c24a603947f79de` |
| `backend-laravel/app/Http/Controllers/Api/V1/ExamAttemptController.php` | modified | `528eeca32dbec51c6cb2c8e21601184d02793e13` | `4668ff26bd0e897f9ab9d4976491ed9665e1ae37` |
| `backend-laravel/app/Http/Controllers/AuthWebController.php` | modified | `777235500b54514434a657a9ce3e65b03cccbabd` | `d9512b27463b1b732ff49950243553ae750dc3a1` |
| `backend-laravel/app/Http/Middleware/IdentifyTenant.php` | modified | `e98825088a90f51a285ea0e29e31a9328732540a` | `376a4b2771c3d9a6b128ad847c150d47ac2977db` |
| `backend-laravel/app/Livewire/Admin/UserManagement.php` | modified | `175ead53752302ccdefc1d113f3da81457539aeb` | `b3da526b4c3845f724e3cf2ba9d625f1b9ef5a2b` |
| `backend-laravel/app/Models/User.php` | modified | `e00787d979e2eaeec47db809eb3ce5d9faa51f24` | `dec82ba26e785708945bdea1bc0e34a1ed20458d` |
| `backend-laravel/database/migrations/2026_11_24_000001_add_account_lifecycle_fields_to_users_table.php` | new | `6dadb8bd60403594489e712f3f3042efc3c4c7c5` | `—` |
| `backend-laravel/resources/views/auth/force-password-change.blade.php` | new | `168bd32abc8b0ab8b08f1f4d6dfbd4f8cef53112` | `—` |
| `backend-laravel/resources/views/livewire/admin/user-management.blade.php` | modified | `2865192ac5eb3668d47ac0c8d48110cc8bdf828d` | `0f01fad6b6f933004a350fbeb6fb1be4f0a2365d` |
| `backend-laravel/routes/api.php` | modified | `3d65531f9b36b16a382e17a99dc0fd85cda501ee` | `cee10d9dd15996294ba700cdf0565ac3889d239e` |
| `backend-laravel/routes/web.php` | modified | `3d9e9e4275a5bb8c2ac6bca17aac5db13fb87647` | `a2fb97e8324f28e6f1e52dc8fa4737faf87dd675` |
| `backend-laravel/tests/Feature/AccountLifecycleLicenseRouteTest.php` | new | `48fa3c95efe99f28e78ceb41d433a87f108e1259` | `—` |
| `backend-laravel/tests/Feature/AccountResetAttemptConcurrencyTest.php` | new | `0d14986af97c6fbc4db698c8e4b03bca26c38a2e` | `—` |
| `backend-laravel/tests/Feature/AdminUserAccountLifecycleTest.php` | new | `c0dce39bbe85fc9926ea8d701afe9bb86a0f5199` | `—` |

Baseline perbandingan: `37087d8862f6c7725b4619e3ca096da9e47cae20`. Daftar ini mencakup semua file kode, migration, route, view, test, dan workflow yang berubah pada rentang tersebut. Direktori backup juga ditambahkan sebagai artefak pemulihan.

## PENGUJIAN

Acceptance tambahan yang dibuktikan oleh suite lifecycle/auth pada CI:
- Replay token Sanctum lama setelah deaktivasi ditolak oleh API.
- Batas role/tenant untuk deaktivasi dan reset.
- Race reset dan deaktivasi versus Attempt start dengan koneksi MySQL terpisah.
- Persisted Web session dengan driver file dan database; session pengguna lain tetap valid.
- Aktivasi ulang tidak menghidupkan cookie/sesi lama.
- Lock timeout dan rollback; deadlock InnoDB dan rollback transaksi pesaing.
- Pengujian autentikasi Web/API, tenant, dan route lisensi.
- Tidak ada mutasi Attempt/Answer/Score/Device Lease/fencing ketika operasi ditolak karena Attempt aktif.

## Batasan yang masih terbuka

1. Belum ada validasi runtime terhadap deployment VPS/aaPanel dan konfigurasi session driver produksi.
2. Belum ada uji upgrade terhadap salinan database produksi yang representatif; hanya migration chain pada database disposable CI.
3. Belum ada uji beban/stress multi-worker panjang, failover database, atau observabilitas produksi.
4. Cakupan lock timeout dan deadlock dibuktikan melalui skenario terarah yang ada pada test suite; bukan eksplorasi seluruh kemungkinan interleaving transaksi.
5. Uji integrasi operasional Admin/Guru/Siswa di madrasah belum dilakukan.
6. Device Lease/fencing tetap belum boleh dinyatakan siap produksi tanpa uji integrasi multi-device terpisah.

## Keputusan tahap

**Lifecycle Akun ditutup pada level acceptance CI.** Tidak ada tindakan produksi yang dilakukan. Tahap berikutnya adalah audit dan desain lifecycle Tahun Ajaran/Semester; tidak membuat ulang model `AcademicYear` atau `Semester`.
