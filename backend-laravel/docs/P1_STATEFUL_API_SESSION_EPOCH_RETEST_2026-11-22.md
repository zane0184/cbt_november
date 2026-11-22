# P1 Retest — Stateful Sanctum API Account Epoch

Tanggal: 22 November 2026

## Temuan CI sebelumnya

Run #375 (`44997533553`, code commit `cf0cfc215af7`) gagal pada rangkaian Guru/Siswa regression sebelum `StudentWebLoginTest` dijalankan.

Akar masalah terverifikasi dari stack trace:
- `EnsureAccountSessionVersion` memanggil `$request->session()` pada API request stateless.
- Pada request tersebut session store memang tidak terpasang.
- Akibatnya endpoint import soal dan request API lain menghasilkan HTTP 500 (`Session store not set on request`).

Ini merupakan bug middleware baru, bukan kegagalan validasi import soal.

## Perbaikan

Commit: `d759cfce51c97c999697fb069dcfa5467ebf1bd5`

- Middleware sekarang melewati pemeriksaan epoch jika `$request->hasSession()` bernilai false.
- Bearer-token/stateless API tetap berjalan tanpa membutuhkan session store.
- Stateful cookie API tetap diperiksa setelah Sanctum memulai session.
- Test menetapkan `cbt.test` sebagai Sanctum stateful domain agar simulasi browser benar-benar masuk ke jalur cookie/session.

File berubah:
- `app/Http/Middleware/EnsureAccountSessionVersion.php`
- `tests/Feature/StudentWebLoginTest.php`

Backup kedua file dan manifest sudah diverifikasi dan dicatat pada manifest backup Stage4D.

## Pengujian

Status saat dokumen dibuat: menunggu CI pada commit perbaikan. Belum ada klaim PASS.

Kriteria:
1. Request API stateless tanpa session store tidak menghasilkan 500.
2. Stateful API session valid sebelum deactivation.
3. Stateful API session lama ditolak setelah reactivation.
4. Stateful API session baru diterima.
5. Bearer token tetap diuji terpisah.

Tidak ada migration atau perubahan database.
