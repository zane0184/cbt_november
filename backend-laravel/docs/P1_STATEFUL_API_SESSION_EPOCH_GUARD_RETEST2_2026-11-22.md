# P1 — Stateful API epoch guard: retest after stateless-session fix

Tanggal: 22 November 2026

## AUDIT

Run #376 (`44997565595`) mengulang source lama pada commit `cf0cfc215af7` dan gagal pada rangkaian API/Guru-Siswa karena request stateless tidak memiliki session store. Kegagalan tersebut sudah direproduksi pada run #375 dan penyebabnya tercatat sebagai `Session store not set on request`.

## IMPLEMENTASI YANG DIUJI

Source perbaikan berada pada commit `d759cfce51c97c999697fb069dcfa5467ebf1bd5`:
- `EnsureAccountSessionVersion` memeriksa `hasSession()` sebelum membaca guard/session.
- Test mengatur `sanctum.stateful=['cbt.test']`.
- Test memisahkan browser session API dari browser session web agar request pertama tidak menginvalidasi session yang kedua.

## PENGUJIAN

Menunggu workflow baru pada HEAD branch terbaru. Kriteria PASS:
- PHPUnit API auth dan QuestionImport tidak lagi menghasilkan 500.
- Stateful Sanctum cookie lama ditolak sesudah reactivation.
- Fresh stateful session diterima.
- Bearer token stateless tetap bekerja dan tetap dicabut melalui account lifecycle.

Belum ada klaim PASS. Tidak ada migration atau database produksi.
