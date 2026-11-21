# CHECKPOINT WA-01 — RUNNER ALTERNATIF MYSQL DISPOSABLE

Tanggal: 21 November 2026 (WIB)  
Repository: `zane0184/cbt_november`  
Branch implementasi alternatif: `work/wa01-safe-mysql-ci-20261121`  
Baseline branch: `work/wa01-package0-implementation-20261121` @ `b86ba805388b0913f20858cf3e13aace3a6f393f`

## AUDIT

- HEAD branch implementasi WA-01 yang diperiksa sebelum membuat alternatif: `b86ba805388b0913f20858cf3e13aace3a6f393f`.
- Workflow WA-01 lama dipertahankan; tidak diubah dan tidak diulang.
- Lingkungan lokal coding agent diperiksa: Debian 13, PHP CLI 8.4.24; PHP 8.3, Composer, MySQL client/server, Docker/Podman tidak tersedia. Tidak ada DB produksi/VPS yang diakses.
- Test lama `AdminCbtBoundaryTest::test_admin_with_stale_guru_role_and_permissions_is_denied_all_cbt_surfaces` memang membuat 10 permission rows melalui `Permission::firstOrCreate` dan menulis pivot `permission_role` untuk role Admin dan Guru. Test ini tetap dikecualikan dari runner alternatif karena pengguna belum menyetujui mutasi fixture tersebut.
- Test lama yang memanggil `RolePermissionBootstrapper` atau helper bootstrap tetap dikecualikan.

## DESAIN

- Alternatif memakai GitHub-hosted runner `ubuntu-24.04`, PHP 8.3, Composer 2, MySQL 8.0/InnoDB service disposable.
- Trigger otomatis: push ke branch kerja alternatif saat file backend atau workflow terkait berubah. Tidak perlu pengguna memulai workflow manual.
- Nama database dan user dibuat unik berdasarkan run ID dan attempt.
- User aplikasi hanya memperoleh grant pada schema disposable milik run tersebut; tidak memakai GitHub Secrets, kredensial VPS, atau database produksi.
- Gerbang memeriksa versi PHP/MySQL, hostname, nama DB/user, engine, schema kosong, grants, dan konfigurasi DB Laravel sebelum test.
- Laravel migration hanya dipicu oleh `RefreshDatabase` dalam test di schema disposable. Tidak ada seeder.
- Test tambahan khusus Admin tidak membuat Permission row dan tidak menulis pivot `permission_role`; test memeriksa kedua tabel tetap kosong sebelum dan sesudah.
- Cleanup selalu mencoba DROP schema dan user, lalu service MySQL dihancurkan bersama job.

## IMPLEMENTASI

Branch baru dibuat dari baseline WA-01; tidak ada file baseline yang ditimpa.

1. `backend-laravel/tests/Feature/AdminCbtBoundaryWithoutPermissionFixturesTest.php`
   - Commit terakhir file: `550e127a30e2915af30e6ded6f07354bae081bda`.
   - Test mencakup akun multi-role ADMIN_MADRASAH + GURU + SISWA, penolakan Gate untuk Question/Assessment/Exam/Attempt/Report, Livewire QuestionBank dan ExamManagement, serta endpoint portal siswa.
   - Fixture hanya membuat Tenant, Role, User dan role-user membership. Tidak membuat Permission row atau permission_role pivot.
2. `.github/workflows/wa01-safe-mysql-ci.yml`
   - Commit terakhir workflow: `3380292c0060ae69de3166486f4be6b79e265fb7`.
   - Blob workflow: `be3fc7052aeffdf63f621f9678edaaf395df459f`.
3. HEAD branch implementasi alternatif: `3380292c0060ae69de3166486f4be6b79e265fb7`.

## PENGUJIAN

- GitHub Actions terpicu otomatis pada push; run terbaru yang terdeteksi: [WA-01 Safe Disposable MySQL Test](https://github.com/zane0184/cbt_november/actions/runs/44925197989).
- Saat checkpoint ini dibuat, run terbaru masih pending/menunggu runner; belum ada hasil lint/PHPUnit yang dapat dinyatakan PASS.
- Run awal pada commit sebelum koreksi namespace tidak dipakai sebagai bukti hasil final.
- Belum ada migration produksi, seeder, RolePermissionBootstrapper, atau mutasi permission fixture yang dijalankan.
- Hasil akhir wajib diperbarui setelah log, jobs dan artifact run terbaru diperiksa.

## STATUS

**IMPLEMENTASI RUNNER: DONE. PENGUJIAN: PENDING. WA-01: BELUM PASS.**

## Gerbang selanjutnya

1. Periksa hasil run terbaru dan artifact.
2. Jika test baru gagal, perbaiki hanya berdasarkan log aktual, backup/catat perubahan, lalu push agar CI otomatis berjalan ulang.
3. Setelah test Admin tanpa permission fixture lulus, perluas subset ke test auth/tenant/Guru/Siswa yang tidak memanggil bootstrapper atau mutasi permission fixture.
4. Test stale-permission fixture yang lama tetap menunggu persetujuan khusus pengguna.
5. Setelah bukti WA-01 memadai, lanjut Paket 1 Web Admin sesuai urutan dan checkpoint; jangan kembali mengulang audit statis yang sama.

Tidak ada perubahan pada baseline, branch dokumentasi sebelumnya, PR existing, database produksi, .env, secret, VPS, atau deployment.


## ADDENDUM STATUS EKSEKUSI — PEMBARUAN

- Test tambahan diperkuat untuk mensimulasikan 10 capability/grant CBT lama melalui test-double `StalePermissionSimulationUser::hasPermission()` yang mengembalikan true untuk nama permission CBT yang diuji.
- Simulasi ini dilakukan di memori; tidak membuat row pada tabel `permissions` dan tidak menulis pivot `permission_role`. Test juga memeriksa kedua tabel tetap kosong sebelum dan sesudah pemeriksaan.
- Karena itu, pengujian stale grant tidak memerlukan persetujuan mutasi permission fixture. Batasnya: ini menguji prioritas deny pada authorization ketika hasil pemeriksaan permission bernilai true, bukan integrasi persistensi permission-role DB.
- Run #1–#3: kegagalan awal saat package discovery karena `bootstrap/cache` belum tersedia/writable. Runner diperbaiki dengan membuat direktori runtime.
- Run #4: preflight menemukan format `CURRENT_USER()` berisi user@host; pemeriksaan identitas diperbaiki dengan mengambil username saja.
- Run #5–#6: log menunjukkan parse error pada ekspresi SQL string PHP untuk pemisah `@`; diperbaiki memakai `CHAR(64)`.
- Run #7: gerbang identitas MySQL PASS, tetapi bootstrap Laravel config gagal karena FQCN Kernel masih memiliki separator namespace ganda. Workflow diperbaiki dengan FQCN eksplisit `\\Illuminate\\Contracts\\Console\\Kernel`.
- Run terbaru setelah perbaikan FQCN: [WA-01 Safe Disposable MySQL Test #8](https://github.com/zane0184/cbt_november/actions/runs/44925731947), status QUEUED pada saat addendum ditulis.
- Bukti awal run #7: PHP 8.3.37, Composer 2.11.2, MySQL 8.0.46; disposable DB berhasil dibuat, gate MySQL identity/schema/grants PASS, dan cleanup disposable DB/user PASS. Laravel config gate belum PASS; lint dan PHPUnit belum dijalankan.
- HEAD branch alternatif saat addendum: `6835741059b2d887909cdbed17a79fbf18649063`.
- File test terakhir: `backend-laravel/tests/Feature/AdminCbtBoundaryWithoutPermissionFixturesTest.php`, blob `35ff266fe125aa3ef656222beb5e26e008a15af9`.
- Workflow terakhir: `.github/workflows/wa01-safe-mysql-ci.yml`, blob `f3d6f99154f60b1bef27c76c9931363c3d0273e6`.

**Status saat addendum:** runner alternatif sudah berjalan sampai MySQL preflight pada run #7, tetapi WA-01 tetap NOT PASS karena bootstrap Laravel config gate belum lulus. Belum ada migration/test yang berjalan. Run #8 harus menjadi bukti untuk gerbang Laravel config, lint dan PHPUnit. Semua database yang dibuat pada run gagal telah dihapus oleh cleanup step; MySQL service runner dihentikan.
