# Audit Web Siswa Agent 4 — pemeliharaan navigasi HP saat resize — 23 November 2026

## AUDIT
- Runner diperiksa pada `backend-laravel/resources/views/student/exams/show.blade.php`.
- Fungsi `syncQuestionNavigationForViewport()` sebelumnya menetapkan `details.open = window.innerWidth >= 1024` pada setiap event resize.
- Pada HP, event resize yang dipicu perubahan viewport/browser chrome/keyboard dapat menutup panel navigasi yang sedang dibuka siswa secara manual, walaupun breakpoint desktop tidak dilewati.
- Pembacaan read-only backend Agent 2:
  - `ExamAttemptController::store()` mengembalikan Attempt aktif yang sama saat fingerprint perangkat sama; fingerprint berbeda ditolak.
  - `StudentExamController::show()` membuat device binding UUID di session server dan menggunakannya kembali pada session yang sama.
  - Ini membantu retry POST start setelah respons hilang selama session dan binding yang sama masih berlaku, tetapi bukan bukti proteksi dual-login antarsesi. Binding session unik dan penegakan endpoint tetap blocker P0 Agent 2.
- `backend-laravel/composer.json` hanya mendeklarasikan PHPUnit; tidak ada `package.json` pada root atau `backend-laravel`, dan belum ada harness browser E2E yang dapat diverifikasi melalui source yang diperiksa.
- `phpunit.xml` mengatur SQLite in-memory untuk suite PHPUnit; ini bukan pengujian MySQL 8/InnoDB.

## DESAIN
- Otomatis buka navigasi saat melewati breakpoint ke desktop (>=1024px).
- Otomatis tutup saat kembali ke mode HP.
- Saat ukuran berubah tetapi tetap dalam mode HP, pertahankan pilihan buka/tutup yang dilakukan siswa.
- Tidak mengubah HTML, API, endpoint, Attempt, IndexedDB, atau backend.

## IMPLEMENTASI
- Backup byte-exact:
  `backend-laravel/docs/backup/agent4-web-siswa-emergency-2026-11-22/show.blade.php.before-mobile-nav-resize-state-fix-2026-11-23`
- Blob source pra-patch dan backup: `0ced4c50b542f5c67aa58866e71dc3653dd544fd`.
- Verifikasi backup identik byte-per-byte: PASS.
- Patch menambahkan state `desktopQuestionNavMode` dan hanya mengubah `panel.open` ketika melintasi breakpoint.
- Commit: `f0379de7ac4ee704621f16d0afd0f45e7f825c68`.
- Runner blob: `9ef6558a84aec1dc8469dc37337fb3ebca38e988`.

## PENGUJIAN
- JavaScript inline parse/compile: PASS.
- Simulasi fungsi navigasi dengan mock viewport:
  - Resize HP ke HP mempertahankan pilihan panel siswa: PASS.
  - Melintasi breakpoint ke desktop membuka panel: PASS.
  - Kembali ke HP menutup panel: PASS.
  - Resize berikutnya di HP tidak mengubah pilihan panel: PASS.
- Duplicate HTML ID: tidak ditemukan.
- CSS ID selector terhadap DOM: tidak ada selector yang tidak cocok (warna hex dikecualikan).
- Selector mobile nav, IndexedDB v2, pemulihan draft konflik, conflict submit gate, unknown-submit no-auto-post: PASS (static assertions).
- Belum dijalankan: browser E2E nyata, perangkat fisik, keyboard virtual di browser nyata, IndexedDB lintas reload, API integration, PHP lint/PHPUnit, MySQL concurrency, multi-device lease/fencing, UAT.

## BATAS / GATE
- Hanya Web Siswa runner dan dokumentasi Agent 4.
- Tidak mengubah Web Guru/Admin atau source backend Agent 2.
- Backend P0 dual-login/auth-session binding dan enforcement per endpoint tetap menunggu Agent 2.
- PR #13 tetap Draft/unmerged. Tidak ada merge, migration, database produksi, VPS, atau deployment.
