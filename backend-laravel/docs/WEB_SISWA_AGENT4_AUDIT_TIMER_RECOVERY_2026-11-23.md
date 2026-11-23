# Audit Web Siswa Agent 4 — lifecycle timer dan pesan recovery — 23 November 2026

## AUDIT
- Source aktif: `backend-laravel/resources/views/student/exams/show.blade.php`.
- `loadExecution()` memasang `window.setInterval()` setiap kali execution Attempt dimuat. Jalur pemulihan submit UNKNOWN dapat memanggil `loadExecution()` pada runner yang sebelumnya sudah memiliki timer, sehingga interval lama tidak dibersihkan.
- Jika pemulihan diulang (misalnya setelah penyimpanan marker RESUMED gagal), interval tambahan juga dapat tertinggal.
- Pada jalur status server `IN_PROGRESS`, UI sebelumnya menampilkan `fatal-error` untuk status submit yang belum pasti. Setelah execution yang sama berhasil dipulihkan, pesan lama tetap tampil walaupun runner sudah kembali aktif. Pesan konflik baru dari `loadExecution()` harus tetap dapat ditampilkan.
- Tidak ada perubahan pada sumber waktu: timer tetap menghitung berdasarkan `server_now` dan `expires_at` dari server.

## DESAIN
- Simpan satu ID interval pada state runner.
- Sebelum memasang timer baru, bersihkan interval sebelumnya.
- Ketika recovery mengonfirmasi Attempt yang sama masih `IN_PROGRESS`, bersihkan peringatan unknown-submit lama sebelum `loadExecution()`. Jika `loadExecution()` menemukan konflik, pesan konflik baru tetap muncul; jika pemulihan gagal, catch menampilkan pesan kegagalan baru.
- Tidak mengubah admission, jadwal, status Attempt, API, IndexedDB schema, backend, atau aturan submit barrier.

## IMPLEMENTASI
- File: `backend-laravel/resources/views/student/exams/show.blade.php`.
- Backup sebelum patch timer:
  `backend-laravel/docs/backup/agent4-web-siswa-emergency-2026-11-22/show.blade.php.before-timer-interval-deduplicate-2026-11-23`
  Blob pra-patch `db04dea4d9d804b0ab2df8f3bc5619f029b697a2`; byte-exact diverifikasi saat dibuat.
- Backup sebelum patch pesan:
  `backend-laravel/docs/backup/agent4-web-siswa-emergency-2026-11-22/show.blade.php.before-clear-stale-recovery-alert-2026-11-23`
  Blob pra-patch `b5d192aacfed406fe01075f121bd8dda864e4303`; byte-exact diverifikasi saat dibuat.
- State runner menambahkan `timerIntervalId`; `loadExecution()` menghapus interval sebelumnya sebelum memasang interval baru.
- Recovery IN_PROGRESS membersihkan pesan unknown-submit lama sebelum `loadExecution()`, sehingga warning konflik yang dibuat oleh `loadExecution()` tidak tertutup.
- Commit timer: `bb04afe0e5c2c2451c1568f83d34513abd6f83fb`.
- Commit pesan recovery: `5d61c6c76b621d387e0f1d98ee97ebadf6a9b9ad`.
- Runner blob terbaru: `fbdeafaf1ec9f5469d06675c16e7d68b632a8ede`.

## PENGUJIAN
- JavaScript inline parse/compile: PASS.
- Simulasi interval dengan mock window selama tiga kali load: hanya satu interval aktif; dua interval sebelumnya dibersihkan — PASS.
- Timer masih memakai `data.attempt.expires_at` dari server — PASS (static assertion).
- Recovery IN_PROGRESS sukses: pesan unknown-submit lama hilang dan tombol submit kembali aktif — PASS.
- Jika loadExecution menampilkan konflik baru: warning konflik tetap terlihat — PASS.
- Jika loadExecution gagal: pesan error baru tampil, submit tetap terkunci, status unknown tetap aktif — PASS.
- Duplicate HTML IDs: tidak ditemukan.
- CSS ID selector terhadap DOM: seluruhnya cocok (warna hex dikecualikan).
- Submit conflict/pending barrier tetap ada — PASS (static assertion).
- Belum dijalankan: browser E2E, IndexedDB browser runtime, device/keyboard nyata, API integration, PHP lint/PHPUnit, MySQL concurrency, multi-device/fencing, UAT.

## BATAS / GATE
- Hanya Web Siswa runner dan dokumentasi Agent 4.
- Tidak mengubah Web Guru/Admin, backend Agent 2, API, route, database, atau migration.
- P0 dual-login/auth-session binding dan endpoint enforcement tetap dependency Agent 2.
- PR #13 tetap Draft/unmerged. Tidak ada merge/deployment.
