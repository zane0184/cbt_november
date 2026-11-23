# Laporan W3 — Audit event untuk pembuatan data Admin

Tanggal: 24 November 2026 (WIB)  
Branch: `work/solo-web-cbt-completion-20261123-execution`  
HEAD laporan: `f4397b8999ddf31a96d4d04b15bcfac4473b5203`

## AUDIT
- Issue #12 mensyaratkan audit aktor/waktu/hasil untuk CRUD Guru/Siswa, perubahan master akademik, import/export, dan operasi administrasi.
- `AcademicManagement::createRombel()` dan `createSubject()` sebelumnya menyimpan data tanpa menulis AuditLog.
- `UserManagement::createUser()` sebelumnya membuat User, role, dan profil Guru/Siswa dalam satu transaksi, tetapi tanpa AuditLog.
- Infrastruktur AuditLog/AuditService dan tabel `audit_logs` sudah tersedia; tidak diperlukan migration.
- Reauthorization Admin dan tenant scope tetap dipertahankan; tidak mengubah role atau permission.

## DESAIN
- Event audit dibuat dalam transaksi yang sama dengan mutasi data agar rollback mencakup record dan audit.
- Event master: `CREATE_ACADEMIC_ROMBEL`, `CREATE_ACADEMIC_SUBJECT`.
- Event akun: `CREATE_LOCAL_USER`.
- Payload dibatasi pada metadata non-rahasia. Tidak mencatat password, NIP, NIK, NISN, token, maupun secret.
- Tidak mengubah schema, routes, migration, atau batas peran.

## IMPLEMENTASI
Perubahan source:
1. `backend-laravel/app/Livewire/Admin/AcademicManagement.php`
   - Backup byte-exact: `backend-laravel/docs/backup/admin-academic-audit-2026-11-24/AcademicManagement.php.before`
   - Backup blob SHA: `779fdf04f9067ee0b00f791014de3d803302f453`
   - Source baru blob SHA: `2fae40c5e08bd392ff726a1330f8432d7612301b`
   - Commit source: `512d3b79f6ed6bb1850e7a79277b15221d72918c`

2. `backend-laravel/app/Livewire/Admin/UserManagement.php`
   - Backup byte-exact: `backend-laravel/docs/backup/admin-user-audit-2026-11-24/UserManagement.php.before`
   - Backup blob SHA: `b0868c101f5b98581263d6cfd3cec687eb9219b8`
   - Source baru blob SHA: `dbbf9334a27ca6de58a14609cb0a626c56f1f8a1`
   - Commit source: `60df4c5b1ae34887204fd68527ab83b0f5b51796`

3. `backend-laravel/tests/Feature/AdminUiVerificationTest.php`
   - Backup pra-test: `backend-laravel/docs/backup/admin-academic-audit-2026-11-24/AdminUiVerificationTest.php.before`
   - Backup blob SHA: `bbc71d9d3e12cfb8681264776f41892379802b43`
   - Backup pra-koreksi sintaks: `backend-laravel/docs/backup/admin-account-audit-2026-11-24/AdminUiVerificationTest.php.before-fully-qualified-name-fix`
   - Backup blob SHA: `5c09dc2412ff38e298b09137e2e5b6ebe2d46828`
   - Test source akhir blob SHA: `17be4815c31ca5c4fc8c7629c80302ce64895f46`
   - Test assertion audit akun Guru/Siswa dan rombel/mapel.
   - Commit test final: `f4397b8999ddf31a96d4d04b15bcfac4473b5203`

Seluruh backup di atas telah di-fetch ulang; blob SHA setiap backup sama dengan source tepat sebelum patch terkait.

## PENGUJIAN
- CI #1729 pada SHA `1ea5f43921fabfb39318835000a2db6046d7c424`: workflow FAILED saat PHPUnit memuat test suite karena syntax error di `AdminUiVerificationTest.php:619` (referensi class AuditLog memiliki backslash ganda). PHPUnit belum menjalankan test apa pun. Ini bukan failure assertion.
- Koreksi sintaks dibackup byte-exact lalu diperbaiki pada commit `f4397b8999ddf31a96d4d04b15bcfac4473b5203`.
- CI #1734 pada exact SHA `f4397b8999ddf31a96d4d04b15bcfac4473b5203`: run akhirnya `cancelled` sebelum focused test dieksekusi. Setup PHP 8.3/MySQL disposable dan Composer/package discovery selesai; focused Web login/tenant ditandai cancelled, sedangkan Admin/Guru/full PHPUnit dan InnoDB check dilewati. Tidak ada test assertion yang berjalan pada run ini; hasil bukan FAIL test maupun PASS.
- CI #1725 pada SHA sebelumnya `df3ec4d13f6f38f61584aa0a8e661c4644c95140` SUCCESS; itu tidak menguji patch source sekarang.
- Dengan demikian implementasi source telah dilakukan dan backup diverifikasi, tetapi acceptance test masih PENDING.

## STATUS BACKLOG
- Audit event create rombel/mapel: IMPLEMENTED — TEST PENDING.
- Audit event create Guru/Siswa: IMPLEMENTED — TEST PENDING.
- Lifecycle edit/nonaktif/arsip Guru/Siswa: NOT COMPLETE.
- Tahun ajaran/semester lifecycle dan historis rombel: NOT COMPLETE.
- Import/export Guru/Siswa: NOT COMPLETE.
- Profil lanjutan/logo/branding/kop surat: NOT COMPLETE.
- Kurikulum + KBC provenance/QuestionVersion/snapshot: BLOCKED menunggu kontrak backend tunggal.
- DOCX import: BLOCKED menunggu kontrak final parser/DTO/staging/ImportBatch.
- Assignment Proktor pusat ADM-20: BLOCKED menunggu canonical eligibility marker.
- Web Guru/Siswa integration E2E dan UAT: NOT VERIFIED.
- ZIP distribusi final: NOT READY.

## Batas keselamatan
Tidak ada migration, perubahan database produksi, akses VPS, deployment, merge PR, atau aktivasi Android/Web Super Admin. Tidak ada perubahan schema atau route. Tidak ada keputusan owner yang diubah.

## Langkah berikutnya
1. Verifikasi CI #1734 pada exact SHA hingga selesai.
2. Jika PASS, lanjut audit event untuk alur administrasi lain dan fitur lifecycle yang memiliki kontrak jelas; jika FAIL, tangani akar masalah dari log.
3. Lanjutkan fitur Admin dan integrasi Web Guru/Siswa dengan backend kanonis; jangan mengarang schema atau marker.
4. Jangan membuat ZIP final sebelum seluruh acceptance gates lulus.


## PENGUJIAN UPDATE — W3 CI RETRY
Commit dokumentasi koreksi status run sebelumnya: `d925b7ba41fd623cb621e1c0342d1bbd7e1326eb`. Source aplikasi tidak berubah pada commit dokumentasi ini. CI untuk source aplikasi masih memerlukan run yang menyelesaikan focused Admin/Guru dan full PHPUnit pada PHP 8.3 + disposable MySQL 8/InnoDB. Jangan ubah status test menjadi PASS sebelum hasil tersebut tersedia.
