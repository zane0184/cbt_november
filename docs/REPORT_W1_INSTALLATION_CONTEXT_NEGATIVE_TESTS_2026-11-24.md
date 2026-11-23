# Laporan W1 — Web Login Installation Context Negative Tests

Tanggal: 24 November 2026  
Repository: `zane0184/cbt_november`  
Branch: `work/solo-web-cbt-completion-20261123-execution`  
Source commit: `b726dcb55d5bcba60adb6bc34529df14ffb6dad9`

## AUDIT
`LoginIdentityResolver` memang sudah menolak login jika:
- tidak ada tepat satu `LocalInstallationIdentity`;
- installation identity tidak menunjuk satu-satunya tenant madrasah lokal;
- database berisi lebih dari satu tenant madrasah.
Sebelum patch, `WebAdminAuthTest` belum menguji tiga kondisi tersebut secara langsung pada endpoint Web Login. API tests yang ada tidak menggantikan bukti pada alur session Web.

## DESAIN
Menambahkan regression tests negatif saja, tanpa mengubah perilaku login, model, migration, konfigurasi, atau database aplikasi. Kondisi System Tenant diuji sebagai identity yang tidak sah untuk akun lokal.

## IMPLEMENTASI
File berubah: `backend-laravel/tests/Feature/WebAdminAuthTest.php`
Test baru:
- `test_web_login_fails_closed_when_local_installation_identity_is_missing`
- `test_web_login_fails_closed_when_installation_identity_is_bound_to_system_tenant`
- `test_web_login_fails_closed_when_database_contains_multiple_madrasah_tenants`

Backup pra-patch pertama (byte-exact, diverifikasi dengan fetch ulang):
- `backend-laravel/docs/backup/web-auth-installation-context-2026-11-24/WebAdminAuthTest.php.before`
- Blob SHA: `51910eeaa51da163374b9fd39951f4fa59cdfb41`

Backup pra-koreksi import seeder kedua (byte-exact, diverifikasi dengan fetch ulang):
- `backend-laravel/docs/backup/web-auth-installation-context-2026-11-24/WebAdminAuthTest.php.before-system-seeder-import-fix`
- Blob SHA: `c6546802befa25cd27254f74e1acd2d291730b65`

Commits:
- Test patch awal: `b770ed0ad42b95b528ac3058b5e6815acf5eb30e`
- Koreksi import seeder: `b726dcb55d5bcba60adb6bc34529df14ffb6dad9`

Tidak ada source aplikasi, migration, atau database yang diubah.

## PENGUJIAN
GitHub Actions [#1668](https://github.com/zane0184/cbt_november/actions/runs/45264938898), exact SHA `b726dcb55d5bcba60adb6bc34529df14ffb6dad9`:
- PHP 8.3.
- MySQL 8 / InnoDB disposable database; identity verification step passed.
- Focused Web Login + tenant identity: **30 passed / 99 assertions**.
- Admin Livewire reauthorization regression: **5 passed / 5 assertions**.
- Teacher result Livewire reauthorization: **4 passed / 8 assertions**.
- Full PHPUnit: **530 passed / 2,199 assertions / 0 failed**.
- Checkout dan container cleanup: SUCCESS.
- Tiga test baru tampil PASS pada `WebAdminAuthTest`.
- Tidak ada production database access, production migration, atau VPS deployment.

## STATUS BACKLOG
- W1 installation-context negative Web Login tests: DONE / VERIFIED pada exact SHA dan run di atas.
- W1 secara keseluruhan: belum dinyatakan selesai; gap W1 lain harus diuji/ditutup satu per satu.
- W1 Livewire reauthorization tests yang lebih dahulu hijau tidak diulang sebagai patch baru.

## RISIKO DAN BATASAN
- Bukti ini hanya mencakup Web Login installation identity, bukan seluruh direct-route/API/IDOR/export/download acceptance.
- Satu database per madrasah tetap keputusan produk; tenant guard saat ini adalah defense-in-depth/legacy compatibility, bukan desain shared academic database.
- Tidak ada klaim bahwa seluruh ADM-01–23, Guru, Siswa atau deployment sudah diterima.

## LANGKAH BERIKUTNYA
Lanjut W2 Admin Dashboard: filter metrik Rombel pada tahun pelajaran aktif dan status ACTIVE, serta hanya menghitung mapel ACTIVE; tampilkan status/tahun rombel secara dinamis dan tambahkan regression test terhadap data tahun sebelumnya dan data nonaktif.
