# P1 — Retest after IndependentBrowserClient default-guard reset

Tanggal: 22 November 2026

## Hasil run sebelumnya

Run #379 (`44998131077`, SHA `a1eb70f193e8`) menghasilkan:
- QuestionImportTest: 4/4 PASS, 12 assertions.
- WebAdminAuthTest: 1/1 PASS, 25 assertions.
- StudentWebLoginTest: 10 PASS/1 FAIL.
- Kegagalan tetap HTTP 500 pada login Admin terpisah setelah request stateful API browser kedua.

Run #378 menunjukkan pola kegagalan yang sama. Kedua run tersebut menguji source sebelum perbaikan default guard pada test helper.

## Source perbaikan

Commit: `e91d5b20155a3d660a8a24668a9cf8c535327cc4`

`IndependentBrowserClient` sekarang:
- menghapus instance guard sebelum/sesudah setiap request;
- mengembalikan default guard ke `auth.defaults.guard` (web);
- mempertahankan cookie jar terpisah per browser.

Ini memperbaiki simulasi lifecycle request ketika test berpindah antara web session dan Sanctum stateful API.

## Pengujian

Workflow baru harus menguji commit tersebut. Kriteria:
1. QuestionImportTest tetap 4/4 PASS.
2. Login Admin browser terpisah tidak lagi 500.
3. Stateful API session lama ditolak setelah reactivation.
4. Fresh stateful API session diterima.
5. Bearer token dan remember-me regression tetap lulus.

Belum ada klaim PASS.
