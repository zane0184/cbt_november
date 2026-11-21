# CHECKPOINT W0 — WEB ADMIN MADRASAH
**Tanggal:** 21 November 2026 (WIB)  
**Status:** AUDIT STATIS DITERIMA; SHA-256 DAN RESTORE PASS; PROVENANCE ZIP BASELINE TETAP BLOCKED; W1 BELUM DIMULAI

## Posisi Git dan klarifikasi commit head

| Item | Nilai |
|---|---|
| Repository | `zane0184/cbt_november` |
| Working source disetujui | `recovery/source-preservation-20261121` |
| Source commit pin | `7fc45bf71ac1ebd375eba44fd57c983bf18d66e0` |
| Source tree SHA-1 | `144364e4163ccc62f74bc1fbf93b601380ed2723` |
| Branch kerja | `work/web-admin-w0-20261121` |
| **Branch kerja HEAD sebelum pembaruan checkpoint ini** | `f0610e309c009789a3be50ff49c6f7dd7c5a1afd` |
| Parent commit checkpoint ini | `f0610e309c009789a3be50ff49c6f7dd7c5a1afd` |
| Commit laporan audit revisi | `8073cbd4c281b786e56d0b3d688ae2ce194dd872` |
| Branch recovery HEAD (tidak berubah) | `7fc45bf71ac1ebd375eba44fd57c983bf18d66e0` |

Urutan commit branch kerja setelah source pin:
1. `de2d82244150863e330c17e36345bee73c3907ac` — parent `7fc45bf...`; menambahkan manifest Git tree.
2. `641aff0ad871d442395d0d5b6784f3ddfcac7f52` — parent `de2d822...`; menambahkan laporan audit statis W0.
3. `c708e9781515ec5ffe9b16ab96dc967e31863052` — parent `641aff0...`; menambahkan checkpoint W0 awal.
4. `9c6ce35a6db3edac5c2e57e4025d999e11422122` — parent `c708e978...`; menambahkan workflow verifikasi snapshot. Percobaan workflow pertama gagal karena escape expression checkout.
5. `a42cdbebec90dc9279160e5e062a4fbf98d85df1` — parent `9c6ce35...`; memperbaiki expression workflow. Run kedua berhasil.

Klarifikasi: `641aff0...` adalah commit laporan audit awal dan parent dari checkpoint awal `c708e978...`; bukan HEAD terkini. `c708e978...` adalah checkpoint awal dengan parent `641aff0...`. `a42cdb...` adalah commit perbaikan workflow yang menjalankan snapshot/restore dengan sukses. Setelah itu laporan dan checkpoint direvisi hanya untuk memperbarui bukti, status, dan riwayat commit. HEAD sebelum pembaruan checkpoint ini adalah `f0610e...`; checkpoint yang sekarang disimpan merupakan commit langsung di atasnya. Tidak ada source aplikasi yang diubah.

## AUDIT

- Source pin tetap `7fc45bf...`; recursive tree `144364e...` berisi 768 entries: 531 file blobs (mode 100644) dan 237 directory entries. Tidak ada symlink atau submodule pada tree tersebut.
- Branch kerja dibuat dari source pin. Branch recovery asli tetap pada SHA `7fc45bf...`.
- Perbandingan recovery → working branch menunjukkan hanya empat path tambahan: manifest Git, laporan, checkpoint, serta workflow verifikasi SHA/restore. Tiga dokumen W0 yang telah diterima tetap dipertahankan.
- Route/action-permission static review tetap seperti temuan laporan W0; belum ada patch source.
- ZIP baseline 6 November belum diperiksa raw bytes. Status tetap BLOCKED. Tidak ada arsip recovered yang dipakai untuk menggantikan baseline.

## DESAIN — keputusan yang tidak berubah

- ADMIN_MADRASAH adalah satu-satunya role aktif Admin.
- Admin Madrasah dan Operator adalah satu dan sama: satu akun per pengguna, satu role, satu portal, satu dashboard, satu sidebar, satu permission profile, satu kewenangan. Tidak boleh ada role OPERATOR aktif atau percabangan Admin/Operator.
- Guru memegang kewenangan operasional CBT.
- Proktor adalah penugasan sementara kepada Guru aktif, bukan role/portal permanen.
- Admin hanya membaca metadata ujian dan mengelola penugasan Proktor.
- Satu madrasah = satu instalasi = satu database.

## IMPLEMENTASI

- Tiga dokumen W0 tetap berada pada branch kerja: manifest Git, laporan audit, checkpoint.
- Ditambahkan workflow audit-only `.github/workflows/w0-source-snapshot-verify.yml` untuk checkout source commit pin, membuat manifest SHA-256, membuat arsip snapshot, restore ke direktori disposable, dan membandingkan setiap path/ukuran/hash.
- Tidak ada source aplikasi, migration, route, policy, seeder, model, view, konfigurasi aplikasi, database, .env/secret, PR merge, VPS, atau deployment yang diubah.
- Branch recovery tidak diubah.

## PENGUJIAN — SHA-256 dan restore

GitHub Actions run **W0 Source Snapshot SHA256 and Restore Verification #2**:
- Run ID: `44870810068`
- Workflow commit: `a42cdbebec90dc9279160e5e062a4fbf98d85df1`
- Source yang di-checkout dan diarsipkan: `7fc45bf71ac1ebd375eba44fd57c983bf18d66e0`
- Runner: Ubuntu 24.04 disposable.
- SHA-256 dihitung dari byte isi setiap tracked file.
- `TRACKED_FILES=531`
- Restore test: `EXPECTED_FILES=531`, `RESTORED_FILES=531`, `MISSING=[]`, `EXTRA=[]`, `CHANGED=[]`.
- `RESTORE_VERIFICATION=PASS`.
- Artifact snapshot dan manifest berhasil diunggah; artifact ID `14693289226`, ukuran 808.845 byte, digest ZIP artifact `efe94178561b17ccb956f8bcd373d01e28c28a90c42718bfdc2eb4c673cd0bd8`, retensi sampai 19 Februari 2027.
- Manifest SHA-256 TSV SHA-256: `af72d7556d4c96c5ea9e6ec0db226870cdb19df076f4a6828dd7d07a22f45a02`.
- Snapshot tar.gz SHA-256: `6fca2435b62f17dbbf339935083725540148fffeab943acd14d2a7db44b3bc46`.
- Artifact ZIP SHA-256: `efe94178561b17ccb956f8bcd373d01e28c28a90c42718bfdc2eb4c673cd0bd8`.
- Snapshot tar.gz SHA-256: `6fca2435b62f17dbbf339935083725540148fffeab943acd14d2a7db44b3bc46`.
- Artifact ZIP SHA-256 diverifikasi ulang secara independen di runtime container. Snapshot tar.gz diekstrak lagi ke direktori terpisah; hasil independen: 531 file, missing 0, extra 0, changed 0, PASS.
- Tidak ada database yang digunakan dalam uji restore.

Run #1 `44870798226` gagal di checkout karena expression workflow pertama tersimpan dengan escape yang keliru. Kesalahan workflow diperbaiki pada commit `a42cdb...`; run #2 yang menjadi bukti final berhasil.

## Provenance ZIP baseline

- Baseline ZIP asli 6 November tetap belum raw-byte verified.
- Tidak ada klaim kesamaan byte, CRC, SHA-256, atau identitas source Git dengan ZIP baseline.
- Arsip recovered tidak dipakai untuk mengganti baseline. Pekerjaan provenance tetap BLOCKED sampai raw ZIP asli dapat diperiksa.

## Gate saat ini

| Item | Status |
|---|---|
| Klarifikasi HEAD, parent, urutan commit | PASS |
| Branch recovery tidak berubah | PASS |
| Manifest Git tree | PASS |
| SHA-256 seluruh 531 tracked files pada source pin | PASS |
| Snapshot arsip dari source pin | PASS |
| Restore snapshot disposable dan per-file compare | PASS |
| ZIP baseline raw bytes / CRC / SHA | BLOCKED (sesuai instruksi) |
| Source aplikasi diubah | TIDAK |
| W1 | NOT APPROVED / NOT STARTED |

**Instruksi agent berikutnya:** tunggu pemeriksaan akhir dan keputusan eksplisit pengguna untuk W1. Pertahankan source pin, branch recovery, serta batas role/permission. Jangan melakukan patch aplikasi, migration produksi, merge PR, database produksi, atau deployment tanpa persetujuan tersendiri.
