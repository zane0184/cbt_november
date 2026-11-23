# Laporan W2 — Admin Dashboard Active Academic Year

Tanggal: 24 November 2026  
Repository: `zane0184/cbt_november`  
Branch: `work/solo-web-cbt-completion-20261123-execution`

## AUDIT
Dashboard sebelumnya:
- menghitung semua Rombel tenant tanpa filter tahun ajaran aktif atau status;
- menghitung semua Subject tanpa filter `status=ACTIVE`;
- mengambil tahun ajaran aktif tetapi tidak menggunakannya untuk ruang lingkup angka;
- menampilkan daftar Rombel terbaru lintas tahun ajaran;
- menampilkan teks badge `Aktif` yang di-hardcode;
- mencoba menampilkan `$rombel->code`, sedangkan source model/migration Rombel yang diperiksa tidak menyediakan atribut code.

Komponen sidebar dan struktur Dashboard sudah tersedia; tidak dibuat ulang.

## DESAIN
- Ambil tahun ajaran aktif pada awal render.
- Hitung Rombel hanya jika status `ACTIVE` dan terkait tahun ajaran aktif. Jika belum ada tahun aktif, hasil Rombel 0 dan tampilkan empty state yang eksplisit.
- Hitung mapel hanya dengan status `ACTIVE`.
- Daftar Rombel hanya tahun ajaran aktif dan memuat relasi tahun pelajaran agar view dapat menampilkan label yang benar.
- Status Rombel ditampilkan dari record, bukan teks hard-coded.
- Tidak mengubah role, hak akses, schema/migration, maupun alur Guru/Siswa.

## IMPLEMENTASI
File berubah:
- `backend-laravel/app/Livewire/Admin/Dashboard.php`
- `backend-laravel/resources/views/livewire/admin/dashboard.blade.php`
- `backend-laravel/tests/Feature/AdminUiVerificationTest.php`

Backup byte-exact sebelum patch (konten setiap backup diverifikasi sama dengan blob source awal):
- Dashboard.php: `backend-laravel/docs/backup/admin-dashboard-year-scope-2026-11-24/Dashboard.php.before` — blob SHA `0198e7ad2fed9bc18669972805dba1bcd6d89c7a`.
- dashboard.blade.php: `backend-laravel/docs/backup/admin-dashboard-year-scope-2026-11-24/dashboard.blade.php.before` — blob SHA `701b943088e4d112110e3e6fbb2661d7ddbb0bc5`.
- AdminUiVerificationTest.php: `backend-laravel/docs/backup/admin-dashboard-year-scope-2026-11-24/AdminUiVerificationTest.php.before` — blob SHA `8dc6580e6be22f4d10907ef957db5a399ca7f644`.

Source blobs saat checkpoint:
- Dashboard.php `e3cfaed3d70c336362d254641f453f6a03f1aa8d`.
- dashboard.blade.php `017e95463161bb93628a205ef8acc298c20ce93d`.
- AdminUiVerificationTest.php `df9b50407a1169e4a4ab2cda73d85e23fa25ccbc`.

Regression tests:
- `admin_dashboard_counts_only_active_rombel_in_current_year_and_active_subjects`
- `admin_dashboard_shows_empty_rombel_state_when_no_academic_year_is_active`

## PENGUJIAN
- W1 Actions #1668: [SUCCESS](https://github.com/zane0184/cbt_november/actions/runs/45264938898), exact SHA `b726dcb55d5bcba60adb6bc34529df14ffb6dad9`, 530 passed / 2,199 assertions / 0 failed. Ini membuktikan W1 patch, bukan perubahan W2.
- Actions #1671–#1676 tercatat CANCELLED karena banyak commit backup/source memicu `cancel-in-progress`; run #1676 dibatalkan sebelum checkout/test. CANCELLED bukan PASS dan bukan hasil test yang gagal.
- W2 Dashboard test belum dinyatakan lulus. Jalankan focused AdminUiVerificationTest pada satu exact final commit, lalu gunakan hasil run tersebut.
- Tidak ada database produksi atau VPS yang diakses.

## STATUS BACKLOG
- W2 Dashboard year/status scoping: IMPLEMENTED, TEST PENDING.
- W1 installation context tests: DONE / VERIFIED.
- W1 reauthorization: tetap VERIFIED pada exact SHA dan bukti sebelumnya.
- ADM-01 tetap PARTIAL sampai seluruh metrik/UX acceptance dan browser UAT dinilai; patch ini hanya menutup scoping data Dashboard yang disebut.
- ADM-20 tetap BLOCKED oleh marker kelayakan ujian pusat yang belum ditetapkan owner.

## RISIKO / BATASAN
- Implementasi mengandalkan invariant satu tahun ajaran aktif; invariant DB/form tidak diperbaiki pada tahap ini.
- Total pengguna/Guru/Siswa masih merupakan statistik akun instalasi, bukan statistik per tahun ajaran/rombel, karena akun profil tidak membawa periode aktif.
- Tidak ada klaim seluruh Dashboard, ADM-01–23, Web Guru/Siswa atau ZIP sudah diterima.

## LANGKAH BERIKUTNYA
Tuntaskan focused test Dashboard dan kemudian acceptance dashboard empty/loading/error, data metric provenance, serta responsive/accessibility pada browser nyata.
