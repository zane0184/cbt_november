# AUDIT KEAMANAN ANTI-CURANG WEB SISWA & MOBILE-FIRST
Tanggal: 23 November 2026
Repository: zane0184/cbt_november
Branch audit integrasi: `work/solo-web-cbt-completion-20261123`
Source HEAD saat audit: `4d505fed8d79d81ab0af2fbc41b6ad082203dcff`
PR #21: OPEN / DRAFT / UNMERGED.
Referensi spesifikasi: `work/agent4-web-siswa-audit-20261122/docs/MASTER_SPESIFIKASI_FITUR_WEB_SISWA_CBT_MADRASAH_2026-11-22.md`.

## Kesimpulan
**ANTI-CHEAT WEB SISWA BELUM BERES. WEB SISWA NOT READY FOR OPERATIONAL EXAM.**
Yang ada baru pembatasan parsial terhadap satu Attempt aktif pada device-binding ID berbeda. Tidak ditemukan anti-copy/paste/context-menu/shortcut enforcement pada runner. Device-binding ID saat ini adalah UUID server-side yang disimpan di Laravel session dan dikirim sebagai header; ini bukan registered-device lease, lease token, heartbeat/revoke/takeover/fencing. Login sesi paralel tidak terbukti dicegah pada level autentikasi. Tidak ada browser E2E/multi-tab/multi-device/concurrency/UAT PASS pada exact SHA audit ini.

## AUDIT

### 1. Anti copy/paste dan interaksi browser
File diperiksa:
- `backend-laravel/resources/views/student/exams/show.blade.php` — blob `7283ca553d787f4505299a306e2bd487884622d3`.
- Runner script di file tersebut diperiksa untuk event listener clipboard, copy, cut, paste, contextmenu, selectstart, keydown shortcut, visibilitychange, blur/focus, fullscreen.

Temuan:
- Tidak ditemukan handler untuk memblokir copy/cut/paste, context menu, shortcut copy/paste, atau seleksi teks.
- Tidak ditemukan mekanisme deteksi tab tersembunyi/perpindahan fokus sebagai event pelanggaran.
- Tidak ada fullscreen enforcement.
- Bahkan jika pembatasan copy/paste ditambahkan di browser, hal itu hanya deterrent UX; tidak mencegah foto layar, screenshot OS, screen recording, perangkat kedua, atau inspeksi melalui sistem operasi. Browser Web Siswa bukan secure kiosk/exam browser.

Status: **BELUM ADA pada runner source yang diaudit** (untuk anti-copy/paste/shortcut/contextmenu). Tidak boleh diklaim anti-cheat lengkap.

### 2. Dual login / concurrent device
File diperiksa:
- `backend-laravel/app/Http/Controllers/StudentExamController.php` — session key `cbt.student.device_binding_id` dibuat sebagai UUID di server dan disimpan dalam session.
- `backend-laravel/app/Http/Controllers/Api/V1/ExamAttemptController.php` — blob `ace6a83989375e067b3a7747bc4f320c21222c69`.
- `backend-laravel/app/Http/Controllers/Api/V1/ExamAnswerController.php` — blob `333e37007c072243fe828ab038d738fc98d88e8e`.
- `backend-laravel/app/Models/ExamAttempt.php` — blob `341c8d00337ad8761dca94602da7acfbdc769fb0`.

Temuan:
- Saat POST start, backend mencari Attempt IN_PROGRESS yang belum expired dan mengembalikan HTTP 409 `CONCURRENT_DEVICE_DENIED` jika `device_fingerprint` berbeda dari header `X-Device-Binding-ID`.
- Execution dan Answer API juga membandingkan fingerprint tersebut.
- Start menggunakan transaksi dan row lock Exam -> Participant -> Attempt; ini membantu mencegah pembuatan Attempt paralel.
- Tetapi login web kedua tidak diblokir secara eksplisit. Konflik baru terlihat saat mencoba start/resume atau mengakses execution/answer.
- Device binding saat ini adalah session-bound UUID, bukan identitas perangkat terdaftar yang kuat. Dua tab pada session/browser yang sama memakai binding sama; koordinasi multi-tab writer belum dibuktikan.
- Tidak ditemukan bukti end-to-end registered device, lease token, heartbeat, expiry/revoke, authorized takeover, atau fencing generation pada source yang diperiksa.
- Header device binding dari client bukan pengganti lease backend yang otoritatif.

Status: **PARSIAL / BLOCKED**. Ada konflik Attempt lintas binding yang berbeda, tetapi belum layak disebut dual-login protection siap produksi.

### 3. Payload, Attempt dan data jawaban
- Spesifikasi Web Siswa menyatakan F-01 admission/snapshot release masih BLOCKED; sanitasi answer key saja tidak cukup.
- F-02 media authorization belum membuktikan snapshot berada dalam question_order plus lease/fencing.
- Runner masih menyimpan state jawaban dan pending request di JavaScript memory Map. IndexedDB durable recovery belum ada.
- Pada HTTP 409, runner saat ini dapat mengganti jawaban lokal dengan jawaban server dan menghapus pending lokal; ini berisiko kehilangan jawaban.
- Submit barrier belum aman: jalur auto-expiry tetap mencoba submit meskipun flush jawaban gagal.
- Tidak ada browser E2E, multi-tab, multi-device, offline/reconnect, response-lost, concurrency atau UAT PASS pada source SHA ini.

### 4. Mobile-first
Keputusan produk pada master spec:
- Wajib mobile-first untuk HP, tablet, desktop.
- Input harus mudah disentuh; virtual keyboard tidak boleh menutupi jawaban.
- Scroll/focus harus terjaga saat pindah soal.
- Timer sticky tidak boleh menghalangi konten.
- Navigasi, status simpan, konflik, dan tombol submit harus usable pada layar kecil.

Source:
- `index.blade.php` dan `show.blade.php` menggunakan meta viewport.
- Runner menggunakan `grid gap-4 lg:grid-cols-[1fr_220px]`; default mobile satu kolom, desktop dua kolom.
- Ini menunjukkan responsive styling dasar, bukan bukti mobile-first QA.
- Belum ada hasil visual/browser test Android Chrome, iOS Safari, virtual keyboard, portrait/landscape, viewport kecil, touch target, sticky timer/navigation, scroll preservation, atau mobile network throttling.
- Pada mobile, panel navigasi dan tombol submit berada setelah area soal dalam satu kolom; perlu QA agar tombol/soal/timer tidak mengganggu dan mudah dijangkau.

Status: **RESPONSIVE DASAR ADA — MOBILE-FIRST BELUM TERVERIFIKASI**.

## DESAIN / ACCEPTANCE GATE YANG WAJIB
1. Tetapkan anti-copy/paste sebagai deterrent opsional: copy/cut/paste/context menu/shortcut pada area soal dan jawaban dapat dicegah secara best-effort, tetapi jangan klaim mencegah screenshot, kamera perangkat kedua, OS task switch, atau screen recording.
2. Visibility/focus event hanya menjadi sinyal audit/indikator; jangan auto-submit atau menghukum siswa hanya karena browser kehilangan fokus (notifikasi, panggilan, virtual keyboard, accessibility).
3. Dual login harus ditegakkan backend dengan registered device + lease token + lease expiry/revoke + fencing generation, bukan hanya UUID dari session/header.
4. Start/resume/execution/answer/media/submit harus memvalidasi lease dan fencing yang sama; device stale ditolak.
5. Multi-tab satu browser harus memakai koordinasi writer dan tetap dilindungi server-side idempotency/revision/fencing.
6. Tidak boleh takeover otomatis dari browser; takeover hanya lewat otorisasi Admin/Guru sesuai keputusan produk dan audit trail.
7. Mobile-first QA wajib di Android Chrome dan iOS Safari (untuk web phase), viewport kecil, keyboard virtual, rotasi, touch target, sticky timer, scroll, offline/reconnect, tab switching, accessibility.
8. Seluruh test harus lulus pada exact source SHA yang sama dengan disposable MySQL 8/InnoDB; sertakan browser E2E.

## IMPLEMENTASI
Tidak ada perubahan source runtime dalam audit ini. Hanya laporan audit yang ditambahkan.

## PENGUJIAN
- Dilakukan inspeksi source GitHub pada SHA yang disebutkan.
- Tidak dijalankan browser, PHP lint, PHPUnit, Playwright, multi-tab, multi-device, concurrency, mobile visual QA, offline/recovery, atau UAT untuk audit ini.
- Karena itu tidak ada klaim PASS untuk fitur anti-cheat atau mobile-first.

## NEXT
1. Koordinator membuka gate implementasi Agent Web Siswa dan menetapkan ownership file.
2. Backend owner menyelesaikan lease/fencing/dual-device contract.
3. Agent Web Siswa mengerjakan deterrent browser dan runner mobile-first tanpa mengambil alih otorisasi backend.
4. Jalankan acceptance suite dan E2E pada exact SHA.
5. Jangan nyatakan Web Siswa siap ujian sebelum P0 security, persistence, recovery, mobile QA, browser QA dan UAT lulus.
