# P1 — Stateful Sanctum API Account Epoch

Tanggal: 22 November 2026

## AUDIT

- `bootstrap/app.php` mengaktifkan `statefulApi()`, sehingga API dapat menerima autentikasi berbasis cookie/session browser.
- Route API terlindungi menggunakan `auth:sanctum`, tetapi sebelum patch tidak menerapkan `EnsureAccountSessionVersion`.
- Middleware epoch sebelumnya hanya dipasang pada grup `web`.
- Deactivation/reactivation sudah menaikkan `auth_version`, merotasi remember token, dan mencabut personal access token. Jalur bearer token dan web session memiliki bukti test; stateful API cookie belum diuji.
- Tanpa pemeriksaan epoch pada API stateful, session cookie lama berpotensi kembali mengautentikasi setelah akun diaktifkan kembali.

## DESAIN

- Terapkan `EnsureAccountSessionVersion` sesudah `auth:sanctum` dan `tenant` pada seluruh grup API yang memerlukan autentikasi.
- Middleware hanya memeriksa `Auth::guard('web')`; request bearer-token tanpa web session tetap melewati pemeriksaan epoch.
- Jika epoch session berbeda, logout guard, invalidate session, regenerate CSRF token, dan balas HTTP 401 JSON untuk request API/JSON; route web tetap redirect ke login.
- Test menggunakan browser client terpisah: session API valid sebelum deaktivasi, session lama ditolak setelah reaktivasi, session baru diterima, dan bearer token stateless tetap diuji terpisah.

## IMPLEMENTASI

Commit: `cf0cfc215af7ef37e3f75f74db336f4e866f8510`

File berubah:
- `app/Http/Middleware/EnsureAccountSessionVersion.php`
- `routes/api.php`
- `tests/Feature/StudentWebLoginTest.php`

Backup terverifikasi:
- `docs/backup/stage4d-account-lifecycle-2026-11-22/EnsureAccountSessionVersion.php.before-stateful-api-session-epoch-2026-11-22`
- `docs/backup/stage4d-account-lifecycle-2026-11-22/api.php.before-stateful-api-session-epoch-2026-11-22`
- `docs/backup/stage4d-account-lifecycle-2026-11-22/StudentWebLoginTest.php.before-stateful-api-session-epoch-2026-11-22`

SHA source dan backup dicatat dalam `docs/backup/stage4d-account-lifecycle-2026-11-22/MANIFEST.md`.

## PENGUJIAN

Status saat dokumen dibuat: menunggu CI pada commit setelah `cf0cfc`. Belum ada klaim PASS untuk test stateful API baru. Tidak ada migration atau perubahan database.

## STATUS

IN PROGRESS — menunggu CI dan bukti bahwa cookie-based Sanctum session lama tidak dapat hidup kembali setelah reaktivasi.
