# Laporan Implementasi Darurat Agent 4 — Web Siswa
Tanggal: 22 November 2026
Repository: zane0184/cbt_november
Branch: `work/agent4-web-siswa-emergency-20261122`
Base: `7642f8df7d20effe82dc1ba5b086147894332767` (work/stage4d-account-lifecycle-20261122)
HEAD setelah implementasi: `66fc7352e88fa664d9d12119849eea4e184808ca`

## AUDIT
- Acuan produk: `docs/MASTER_SPESIFIKASI_FITUR_WEB_SISWA_CBT_MADRASAH_2026-11-22.md`.
- Source runner sebelum patch: `backend-laravel/resources/views/student/exams/show.blade.php`, blob `7283ca553d787f4505299a306e2bd487884622d3`.
- Temuan awal tetap berlaku: runner menyimpan jawaban pending hanya di Map/memori browser; refresh/crash dapat menghilangkan jawaban yang belum diterima server; konflik 409 sebelumnya menimpa jawaban lokal dengan jawaban server; submit dapat diteruskan walau flush gagal.
- Blocker backend yang tidak dimiliki Agent 4 tetap terbuka: exact-one SISWA di seluruh entrypoint, KAMAD frozen, F-01 admission/snapshot release, F-02 media authorization, lifecycle Device Lease/Fencing, kontrak receipt/idempotency yang kuat, dan verifikasi expiry/submit server.
- Kandidat branch historis `work/import-guru-siswa-implementation-20261122` tidak dipakai. Branch ini dibuat baru dari HEAD Agent 2 yang teridentifikasi.

## DESAIN
- IndexedDB menjadi draft queue lokal per Attempt + snapshot soal.
- Draft lokal ditulis sebelum request jaringan dijadwalkan.
- Draft hanya dihapus setelah server mengembalikan sukses untuk save yang sama.
- Konflik 409 mempertahankan draft lokal; tidak lagi menimpa dengan jawaban server secara otomatis.
- Submit manual/otomatis wajib melewati flush; jika masih ada pending/conflict, UI menahan submit dan tidak mengklaim ujian terkirim.
- IndexedDB harus dapat dibuka sebelum request membuat/resume Attempt. Jika tidak tersedia, proses start ditolak sebelum membuat Attempt baru.
- Browser warning ditampilkan saat pengguna mencoba menutup halaman ketika masih ada draft/pending/conflict.
- IndexedDB adalah perlindungan terhadap reload/crash biasa, bukan enkripsi atau secure kiosk. Perangkat/browser tetap harus dipercaya dan tidak boleh dibersihkan selama ujian.

## IMPLEMENTASI
File runtime diubah:
- `backend-laravel/resources/views/student/exams/show.blade.php`

Backup:
- `backend-laravel/docs/backup/agent4-web-siswa-emergency-2026-11-22/show.blade.php.before`
- Backup blob SHA: `7283ca553d787f4505299a306e2bd487884622d3`
- Baseline blob SHA: `7283ca553d787f4505299a306e2bd487884622d3`
- Pemeriksaan byte-exact: PASS (konten backup sama persis dengan file sebelum perubahan).

Perubahan:
1. Menambahkan IndexedDB store `pending-answers`.
2. Menyimpan draft per Attempt dan snapshot sebelum autosave jaringan.
3. Memulihkan draft lokal setelah jawaban server dimuat; draft pending lokal diprioritaskan di layar agar tidak hilang.
4. Menghapus draft lokal hanya setelah respons save sukses.
5. Menahan dan mengarsipkan konflik 409 dalam state sesi tanpa mengganti draft lokal.
6. Menambahkan submit barrier untuk pending/conflict yang belum terkonfirmasi.
7. Menolak mulai/resume jika IndexedDB tidak tersedia.
8. Menambahkan peringatan sebelum halaman ditutup ketika masih ada pekerjaan yang belum tersinkron.

Commit:
- Backup: `ffb21ae6195a6c4bbd91274a0a0e817d62b29663`
- IndexedDB + submit barrier: `61abd0dc6c6a3fab4a139ae0ffdcc488ccddfd9f`
- Start gate + conflict safety: `66fc7352e88fa664d9d12119849eea4e184808ca`

## PENGUJIAN
Yang benar-benar dilakukan:
- Fetch ulang file baseline, backup, dan file hasil.
- Verifikasi backup byte-exact terhadap baseline: PASS.
- GitHub compare base → HEAD: branch ahead 3, behind 0; perubahan hanya 1 file runner dan 1 file backup.
- Verifikasi blob SHA file hasil: `3a2037b7ac88c07cbcba96a13971e90873e5f57a`.

Belum dilakukan / belum terbukti:
- JavaScript runtime/browser E2E.
- PHPUnit, PHP lint, CI exact-SHA.
- IndexedDB reload/crash/quota/offline/reconnect/multi-tab tests.
- MySQL 8 concurrency, integration API, device lease/fencing.
- UAT guru/siswa di perangkat madrasah.
Tidak ada hasil PASS yang diklaim untuk pengujian tersebut.

## STATUS DAN GATE
**Status: PATCH CLIENT DARURAT TERSEDIA UNTUK REVIEW — BUKAN SIAP UJIAN OPERASIONAL.**

Patch ini memperbaiki ketahanan draft di browser, tetapi tidak menutup blocker backend/security yang sudah ditemukan. Jangan deploy ke server sekolah atau gunakan untuk ujian bernilai tinggi sebelum:
1. Backend Agent 2 menutup exact-role/KAMAD/F-01/F-02 dan memberi kontrak admission/media yang benar.
2. Device lease/fencing atau kontrol perangkat yang disepakati diuji end-to-end; jangan menyebut fingerprint saja sebagai fencing.
3. Browser E2E membuktikan save, reload recovery, offline, 409, submit barrier, expiry, dan multi-tab.
4. CI/PHPUnit + disposable MySQL 8 lulus pada SHA integrasi yang sama.
5. Guru dan siswa menjalankan simulasi ujian non-nilai di perangkat target.

Tidak ada migration, perubahan database, deployment VPS, merge, atau perubahan ZIP baseline.
