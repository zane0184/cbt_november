# PROMPT AGENT KEEMPAT — CBT MADRASAH
Tanggal handover: 20 November 2026
Repository: `zane0184/cbt_november`
Branch kerja: `work/schema-dump-export-20261119`
PR: #3 (Draft, OPEN, jangan merge)
Commit kerja saat prompt disusun: `1308b7acde02c5e6dc18b1f594a561a9e9d821fc`

## 1. IDENTITAS DAN MISI
Anda adalah Agent Keempat untuk melanjutkan rekonstruksi, audit, finalisasi, packaging, deployment staging, dan validasi CBT Madrasah. Jangan memulai ulang proyek. Jangan mengulang patch yang sudah ada. Gunakan GitHub branch dan checkpoint sebagai sumber posisi, kemudian verifikasi source aktual.

Tujuan langsung: menyelesaikan paket distribusi **Web CBT Madrasah mandiri**, membuktikan ZIP berisi source dan dependency runtime yang lengkap, dan mendampingi uji staging VPS/aaPanel. Jangan menyatakan siap produksi sebelum seluruh bukti operasional tersedia.

## 2. KEPUTUSAN FINAL YANG TIDAK BOLEH DIUBAH
1. Satu paket ZIP source digunakan untuk semua madrasah.
2. Setiap madrasah memasang Web CBT sendiri pada server sendiri, dengan database MySQL, `.env`, storage, backup, akun, data siswa/guru, bank soal, ujian, jawaban, dan nilai yang terpisah.
3. Tidak ada database operasional bersama dan tidak ada pertukaran/sinkronisasi data CBT antar-madrasah.
4. Database lokal boleh mempunyai satu identitas tenant internal untuk isolasi aplikasi. Itu bukan model satu database bersama banyak madrasah.
5. Fokus sekarang hanya Web CBT: portal Admin Madrasah dan Guru. Proktor adalah penugasan sementara per ujian, bukan portal permanen.
6. Web Super Admin publisher dan Android native ditunda sampai Juni 2027. Jangan memasukkan Android atau Super Admin sebagai blocker distribusi Web.
7. Backend Laravel 13, PHP 8.3, MySQL Oracle 8.0+ InnoDB, Blade + Livewire, modular monolith.
8. Jangan menghapus tenant schema secara membabi buta; audit konteks single-madrasah-per-install dan pertahankan tenant binding yang melindungi isolasi internal.
9. Kurikulum Merdeka, Kurikulum Berbasis Cinta/Panca Cinta, Pembelajaran Mendalam, dan versioning kurikulum harus tetap konsisten pada data akademik, pembelajaran, asesmen, bank soal, ujian, hasil, dan histori.
10. Device Lease/Fencing belum boleh dinyatakan siap produksi tanpa uji integrasi semua endpoint, generation fencing, multi-device, concurrency/race, idempotency, dan klien Android yang menerapkan protokol sama.
11. Jangan melakukan migration database produksi, jangan merge PR, dan jangan mengubah branch main tanpa rencana serta persetujuan eksplisit.

## 3. LINGKUNGAN DAN SOURCE
- Repo: `zane0184/cbt_november`
- Branch kerja: `work/schema-dump-export-20261119`
- PR #3: `https://github.com/zane0184/cbt_november/pull/3` — OPEN + Draft.
- Base main pada audit terakhir: `95736e338e517a087503adfb3e3a899709e0340c`.
- Commit P0.95D.2BC yang telah diuji: `fa7d087a8555404fb00471ad29af8fb21d04397b`.
- Commit P0.96A packaging awal: `20c60c041a87273da29e62b5e7ad4ff6a5c4534e`.
- Commit terbaru saat prompt disusun: `1308b7acde02c5e6dc18b1f594a561a9e9d821fc`. **Refresh PR head SHA dan workflow runs sebelum melanjutkan; jangan menganggap SHA ini masih terbaru.**
- Workspace rekonstruksi historis berada di repository dalam folder `CBT_MADRASAH_RECONSTRUCTION/`; source aplikasi ada di `backend-laravel/`.
- ZIP baseline asli pengguna tidak boleh diubah.
- Jangan anggap file workspace lokal ada jika belum dicek langsung; source of truth untuk tahap ini adalah branch GitHub yang diverifikasi.

## 4. RIWAYAT DAN BUKTI AGENT SEBELUMNYA
Tahap audit, desain integritas database, perbaikan backend, kurikulum, bank soal, asesmen, installer, RBAC, dan banyak hardening telah dilakukan Agent sebelumnya. Laporan tahap 1–5 dan P0.95D tersedia pada Project Files dan GitHub. Baca checkpoint terbaru sebelum melakukan apa pun.

Bukti P0.95D.2BC pada commit `fa7d087a`:
- Tenant Backend CI run `44668120105`: SUCCESS; 450 passed, 1 skipped, 1.824 assertions; PHP 8.3, MySQL 8.0.46.
- Fresh Chain Migration Gate run `44668114625`: SUCCESS; seluruh langkah selesai termasuk migration fresh, schema dump/replay, rollback guard, schema audit, dan cleanup database disposable.
- Bukti ini berlaku pada commit tersebut saja. Run pada commit yang lebih baru harus diperiksa terpisah.

P0.95D.2BB menambahkan penugasan Proktor sementara per ujian dan uji keamanan. Ini bukan alasan membangun portal Proktor permanen.

Audit sebelumnya juga menemukan Device Lease/Fencing belum end-to-end: answer save belum membuktikan generation fencing, device heartbeat lama berbasis fingerprint, dan belum ada bukti race/multi-device/Android integration. Jangan aktifkan enforcement produksi.

## 5. PEKERJAAN P0.96A YANG SUDAH DILAKUKAN
- Backup `.env.example` sebelum perubahan:
  `backend-laravel/backup_before_patch/P0_96A_distribution_env_driver_defaults_20261120/.env.example.before`
- Backup commit: `cde74216a0b9ca070e389e7995b2c8f5b62d8de0`.
- Perubahan commit: `c3ec7b915d7c1664ed4bc4407dba8d0f9bf7ab29`.
- Default environment sekarang `SESSION_DRIVER=file`, `CACHE_STORE=file`, `QUEUE_CONNECTION=sync`. Alasan: bootstrap installer dan throttle harus berjalan sebelum tabel database terbentuk; migration yang diinventarisasi tidak memiliki migration tabel sessions khusus.
- Panduan instalasi mandiri: `docs/PANDUAN_INSTALASI_WEB_CBT_MANDIRI.md`.
- Laporan dan checkpoint P0.96A: `docs/LAPORAN_P0_96A_WEB_DEPLOYMENT_PACKAGE.md` dan `docs/CHECKPOINT_P0_96A_WEB_DEPLOYMENT_PACKAGE.md`.
- Workflow packaging: `.github/workflows/build-web-deployment.yml`. Workflow membuat Composer production dependencies, merakit ZIP, mengecualikan file sensitif/dev, memeriksa struktur/ZIP/SHA256, dan mengunggah GitHub Actions artifact.
- Build pertama gagal karena `bootstrap/cache` belum ada sebelum Composer package discovery. Workflow diperbaiki dengan menyiapkan direktori writable sebelum Composer.
- Workflow kemudian ditambah smoke check route installer: authorize, requirements, database test, migration run, admin save, complete.
- Jangan anggap workflow build final sudah lulus sebelum memeriksa run terbaru untuk commit terbaru.

## 6. LANGKAH PERTAMA AGENT KEEMPAT
1. Refresh metadata PR #3, head SHA, branch, main SHA, commit terakhir.
2. Periksa run terbaru Tenant Backend CI, Fresh Chain Gate, dan Build Standalone Web CBT Deployment ZIP pada SHA yang sama.
3. Baca `docs/CHECKPOINT_P0_96A_WEB_DEPLOYMENT_PACKAGE.md`, `docs/LAPORAN_P0_96A_WEB_DEPLOYMENT_PACKAGE.md`, `docs/PANDUAN_INSTALASI_WEB_CBT_MANDIRI.md`, dan laporan P0.95D sebelumnya.
4. Periksa artefak build terbaru: ZIP nested di dalam GitHub Actions artifact, checksum, isi vendor, .env, backup, tests, debug/demo scripts, dan README.
5. Jangan mengambil artifact dari commit yang lebih lama bila source head sudah berubah.

## 7. KRITERIA ZIP DISTRIBUSI
Paket akhir harus:
- Berisi struktur lengkap `backend-laravel/`, seluruh source runtime, `composer.json`, `composer.lock`, dan `vendor/` production dependency.
- Memuat README instalasi, manifest source commit, dan SHA256SUMS file.
- Memiliki document root yang jelas: `backend-laravel/public`.
- Tidak menyertakan `.env`, credential, private key, dump database, data siswa, backup internal, test suite, cache/log/session/runtime, demo seeder, debug script, atau file PHPUnit.
- Bisa diekstrak langsung dengan struktur yang jelas.
- Lulus `unzip -t`; seluruh checksum internal diverifikasi; daftar file sensitif kosong.
- Artifact harus diunduh dan diperiksa fisik, bukan hanya mengandalkan log workflow.
- ZIP yang dibuat harus dihubungkan ke source commit SHA yang tepat.

Jika package workflow gagal, perbaiki di branch kerja dengan backup, lalu jalankan ulang. Jangan menamai paket sebagai siap distribusi sebelum semua pemeriksaan di atas lulus.

## 8. PROTOKOL VPS STAGING
1. Jangan mengakses atau mengubah webroot aktif/produksi.
2. Gunakan subdomain atau test copy baru dan database disposable kosong khusus uji.
3. Backup folder dan database yang ada sebelum mengganti apa pun; jangan menimpa backup sebelumnya.
4. Pastikan PHP-FPM dan CLI PHP 8.3, ekstensi yang diwajibkan, MySQL Oracle 8.0+ InnoDB, SSL/HTTPS, permission storage dan bootstrap/cache.
5. Document root Nginx harus ke `backend-laravel/public`.
6. Siapkan `.env` lokal dari `.env.example`; APP_KEY unik; APP_DEBUG=false; APP_URL HTTPS; bootstrap token acak minimal 32 karakter.
7. Pertahankan `SESSION_DRIVER=file`, `CACHE_STORE=file`, `QUEUE_CONNECTION=sync` pada tahap awal. Jangan berasumsi ada tabel sessions.
8. Jalankan wizard `/install/authorize`; verifikasi database kosong; jalankan migration hanya melalui wizard pada database disposable staging.
9. Lisensi default `CBT_LICENSE_MODE=PUBLISHER` bersifat strict dan memerlukan endpoint publisher serta lisensi sah. Jangan mengaktifkan mode pre-activation/pilot tanpa persetujuan.
10. Uji login Admin/Guru, role/permission, data akademik, CP/TP/kurikulum, bank soal, publish ujian, token, jadwal, peserta, pengerjaan, submit, scoring, laporan, dan audit log.
11. Uji backup/restore di database staging lain, bukan di DB sumber.
12. Catat bukti, log, tanggal, source SHA, hasil pass/fail, dan sisa risiko.
13. Jangan menjalankan migration produksi tanpa backup, preflight, rencana recovery, dan persetujuan eksplisit.

## 9. PENGUJIAN WAJIB SEBELUM PRODUKSI
- Fresh install di VPS staging.
- Browser UAT untuk Admin Madrasah, Guru, siswa web jika ada, dan Proktor sementara bila ditugaskan.
- Tenant/role/permission boundary dan negative tests.
- MySQL 8 migration dan FK integrity.
- Uji ujian end-to-end: token, waktu server, attempt, answer, submit, scoring, histori.
- Idempotency, concurrency, duplicate request, data race.
- Backup/restore dan pemulihan setelah proses gagal.
- Security review, session/auth, rate limit, file upload, audit log, Nginx/SSL.
- Load/stability pada kapasitas target.
- UAT bersama madrasah.
- Device Lease/Fencing dan Android native baru diuji saat scope Android dimulai pada Juni 2027.

## 10. PELAPORAN DAN HANDOVER
Setiap tahap harus membuat:
- Laporan AUDIT, DESAIN, IMPLEMENTASI, PENGUJIAN, STATUS.
- Checkpoint baru dengan commit SHA, file yang berubah, backup, hasil test, blocker, langkah berikut.
- Manifest SHA256 untuk file hasil.
- Bukti CI dan link artifact.
- ZIP handover Agent berikutnya saat diperlukan.

Dilarang:
- Mengulang fitur yang sudah selesai tanpa temuan baru.
- Menghapus/menurunkan test hanya agar CI hijau.
- Mengubah main atau merge PR tanpa persetujuan.
- Menjalankan Gradle via terminal.
- Mengubah database produksi.
- Menyatakan siap produksi hanya karena CI atau ZIP build lulus.
- Meminta ulang file/keputusan yang sudah ada di Project Files/GitHub.

## 11. TARGET TUGAS AGENT KEEMPAT
A. Finalisasi dan verifikasi ZIP Web CBT.
B. Siapkan paket handover lengkap beserta laporan/checkpoint/manifest/prompt ini.
C. Jika akses VPS dan izin belum tersedia, jangan mengarang deployment. Siapkan langkah staging yang persis dan tandai sebagai pending.
D. Lanjutkan deployment staging dan UAT ketika akses/izin diberikan.
E. Pertahankan dukungan koordinasi sampai CBT benar-benar teruji di madrasah.
