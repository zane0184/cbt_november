# P1 Retest — Browser test client resets default authentication guard

Tanggal: 22 November 2026

## AUDIT

Run #378 (`44997980279`) membuktikan:
- QuestionImportTest: 4/4 PASS, 12 assertions.
- WebAdminAuthTest: 1/1 PASS, 25 assertions.
- StudentWebLoginTest: 10 PASS, 1 FAIL.

Kegagalan terjadi saat browser Admin login setelah browser kedua melakukan request stateful Sanctum. Respons Admin login adalah HTTP 500 pada assertion status 302. Stateful API precondition sebelumnya sudah melewati assertion HTTP 200.

## ANALISIS DAN PERBAIKAN

Pemeriksaan `IndependentBrowserClient` menunjukkan helper menghapus instance guard, tetapi tidak mengembalikan default guard setelah request Sanctum mengubah guard aktif. Karena semua browser simulasi memakai satu container aplikasi di dalam test process, helper harus meniru lifecycle request server secara lengkap.

Commit perbaikan: `e91d5b20155a3d660a8a24668a9cf8c535327cc4`.

- Reset auth context kini memanggil `Auth::forgetGuards()`.
- Default guard dikembalikan ke `config('auth.defaults.guard')` (web) sebelum dan sesudah setiap request.
- Cookie jar tiap browser tetap terpisah.
- Tidak ada perubahan pada middleware produksi atau ekspektasi status keamanan pada tahap ini.

Backup helper terverifikasi:
`docs/backup/stage4d-account-lifecycle-2026-11-22/IndependentBrowserClient.php.before-reset-default-guard-2026-11-22`.

## PENGUJIAN

Menunggu workflow ulang. Kriteria:
1. Browser API stateful valid sebelum deaktivasi.
2. Login Admin pada browser terpisah tidak menghasilkan 500.
3. Stateful API session lama ditolak sesudah reaktivasi.
4. Stateful API session baru diterima.
5. Seluruh regresi bearer token, web session, dan remember-me tetap lulus.

Belum ada klaim PASS.
