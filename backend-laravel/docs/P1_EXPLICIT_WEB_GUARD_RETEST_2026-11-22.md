# P1 — Explicit Web Guard Regression Fix

Tanggal: 22 November 2026

## Root cause terkonfirmasi

Run #383 (`44999353477`) menyertakan body HTTP 500 dari StudentWebLoginTest:

`BadMethodCallException: Method Illuminate\\Auth\\RequestGuard::login does not exist.`

Stack trace:
- `AuthWebController.php:52`
- `Auth::login($user, ...)`
- guard aktif saat itu adalah `Illuminate\\Auth\\RequestGuard`, bukan session guard `web`.

Dengan demikian, default guard contamination dari Sanctum stateful API terbukti sebagai akar masalah. Reset helper saja tidak cukup karena controller web masih memanggil facade tanpa guard eksplisit.

## Implementasi

Commit: `302960a1adf7b8dfb694744e04d9ebc246a41005`.

1. `AuthWebController` sekarang menggunakan `Auth::guard('web')` untuk check, login, dan logout.
2. Redirect portal memvalidasi tepat satu role dan tidak memilih portal dari akun multi-role.
3. Route root memakai session guard `web`.
4. Monitoring web dan seluruh route terlindungi memakai middleware `auth:web`.
5. Tidak mengubah Sanctum API guard atau ekspektasi 401/302 pada test.

Backup sebelum patch:
- `docs/backup/stage4d-account-lifecycle-2026-11-22/AuthWebController.php.before-explicit-web-guard-2026-11-22`
- `docs/backup/stage4d-account-lifecycle-2026-11-22/web.php.before-explicit-auth-web-guard-2026-11-22`

## Acceptance test

- Student web login tetap berhasil.
- Sanctum stateful API cookie sebelum deactivation diterima.
- Admin browser terpisah dapat login setelah stateful API request.
- Session web/API stateful lama ditolak setelah deactivation/reactivation.
- Remember-me lama ditolak.
- Fresh login/session diterima.
- Bearer token lama ditolak tanpa cookie/session.

Belum ada klaim PASS sebelum workflow berikutnya selesai.
