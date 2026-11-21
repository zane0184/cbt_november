# CHECKPOINT W0 — WEB ADMIN MADRASAH
**Tanggal:** 21 November 2026 (WIB)  
**Status:** AUDIT STATIS DAN SHA-256/RESTORE PASS; ZIP 6 NOVEMBER ARSIP HISTORIS (BUKAN BLOCKER W0); W0 MENUNGGU PENILAIAN PENGGUNA; W1 BELUM DIMULAI

## Posisi Git dan klarifikasi commit head

| Item | Nilai |
|---|---|
| Repository | `zane0184/cbt_november` |
| Working source disetujui | `recovery/source-preservation-20261121` |
| Source commit pin | `7fc45bf71ac1ebd375eba44fd57c983bf18d66e0` |
| Source tree SHA-1 | `144364e4163ccc62f74bc1fbf93b601380ed2723` |
| Branch kerja | `work/web-admin-w0-20261121` |
| **Branch kerja HEAD saat rekonsiliasi dimulai (ref dibaca langsung)** | `cbb7cda6ebfbd3792435d6dbfebed8b1cbc406af` |
| Commit laporan rekonsiliasi W0 | `9e6e6f0e9693fb728ca8c0040b92463e15f58f0a` |
| HEAD branch dokumentasi Kurmer-KBC saat rekonsiliasi dimulai | `4a7f7c3ed56dc2fb10ce477418cebc5a2394a0d4` |
| Parent commit checkpoint ini | `9e6e6f0e9693fb728ca8c0040b92463e15f58f0a` |
| Commit laporan audit revisi | `8073cbd4c281b786e56d0b3d688ae2ce194dd872` |
| Branch recovery HEAD (tidak berubah) | `7fc45bf71ac1ebd375eba44fd57c983bf18d66e0` |

Urutan commit branch kerja setelah source pin:
1. `de2d82244150863e330c17e36345bee73c3907ac` — parent `7fc45bf...`; menambahkan manifest Git tree.
2. `641aff0ad871d442395d0d5b6784f3ddfcac7f52` — parent `de2d822...`; menambahkan laporan audit statis W0.
3. `c708e9781515ec5ffe9b16ab96dc967e31863052` — parent `641aff0...`; menambahkan checkpoint W0 awal.
4. `9c6ce35a6db3edac5c2e57e4025d999e11422122` — parent `c708e978...`; menambahkan workflow verifikasi snapshot. Percobaan workflow pertama gagal karena escape expression checkout.
5. `a42cdbebec90dc9279160e5e062a4fbf98d85df1` — parent `9c6ce35...`; memperbaiki expression workflow. Run kedua berhasil.

Klarifikasi: `641aff0...` adalah commit laporan audit awal dan parent dari checkpoint awal `c708e978...`; bukan HEAD terkini. `c708e978...` adalah checkpoint awal dengan parent `641aff0...`. `a42cdb...` adalah commit perbaikan workflow yang menjalankan snapshot/restore dengan sukses. Setelah itu laporan dan checkpoint direvisi hanya untuk memperbarui bukti, status, dan riwayat commit. HEAD branch W0 saat rekonsiliasi dimulai adalah `cbb7cda...`. Laporan rekonsiliasi kemudian dikomit sebagai `9e6e6f...`, dan checkpoint ini dibuat langsung di atas commit laporan tersebut. SHA commit yang memuat checkpoint dibaca dari Git ref setelah commit dan dicatat pada handover; SHA tersebut tidak dapat ditanamkan ke dalam isi commit itu sendiri karena akan mengubah hash commit. Tidak ada source aplikasi yang diubah.

## AUDIT

- Source pin tetap `7fc45bf...`; recursive tree `144364e...` berisi 768 entries: 531 file blobs (mode 100644) dan 237 directory entries. Tidak ada symlink atau submodule pada tree tersebut.
- Branch kerja dibuat dari source pin. Branch recovery asli tetap pada SHA `7fc45bf...`.
- Perbandingan recovery → working branch menunjukkan hanya empat path tambahan: manifest Git, laporan, checkpoint, serta workflow verifikasi SHA/restore. Tiga dokumen W0 yang telah diterima tetap dipertahankan.
- Route/action-permission static review tetap seperti temuan laporan W0; belum ada patch source.
- ZIP 6 November adalah arsip historis saja sesuai keputusan terbaru; tidak perlu dibuka/dicocokkan dan bukan blocker W0. GitHub working source menjadi sumber kebenaran aktual.

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

## Status ZIP 6 November — arsip historis

- ZIP 6 November bukan lagi acuan utama, blocker W0, atau syarat verifikasi source.
- Tidak perlu membuka, mencocokkan, atau menghitung ulang raw-byte/CRC/SHA ZIP tersebut dalam pekerjaan pengembangan berikutnya.
- GitHub working source dan keputusan proyek terbaru menjadi sumber kebenaran.
- Perbedaan source GitHub dengan ZIP historis bukan defect dan tidak boleh digunakan untuk menurunkan/menimpa source terbaru.

## Gate saat ini

| Item | Status |
|---|---|
| Klarifikasi HEAD, parent, urutan commit | PASS |
| Branch recovery tidak berubah | PASS |
| Manifest Git tree | PASS |
| SHA-256 seluruh 531 tracked files pada source pin | PASS |
| Snapshot arsip dari source pin | PASS |
| Restore snapshot disposable dan per-file compare | PASS |
| ZIP 6 November sebagai blocker W0 | CLOSED BY DECISION — arsip historis saja |
| Source aplikasi diubah | TIDAK |
| W1 | NOT APPROVED / NOT STARTED |

**Instruksi agent berikutnya:** berhenti setelah rekonsiliasi read-only dan rencana dokumentasi. Tunggu pemeriksaan pengguna. W0 belum ditutup dan W1 belum disetujui/dimulai. Pertahankan seluruh temuan RBAC, Operator, Proktor, bank soal, jawaban/hasil, close/finalize/reopen, konflik jadwal, tenant isolation, dan gap Kurmer-KBC sebagai OPEN sampai implementasi dan tes membuktikan perbaikannya. Jangan melakukan patch aplikasi, migration, database, merge/rebase, deployment, atau perubahan branch source tanpa persetujuan tersendiri.


## Addendum rekonsiliasi dokumentasi — 21 November 2026

### Sumber kebenaran

- Keputusan proyek terbaru: sumber kebenaran desain/persyaratan.
- GitHub working source: sumber kebenaran kondisi source aktual.
- Branch/commit/PR: riwayat perubahan.
- Checkpoint/laporan GitHub: progres dan handover.
- Backup/manifest/snapshot Actions: bukti integritas/pemulihan.

### HEAD refs yang diperiksa langsung sebelum rekonsiliasi

- `work/web-admin-w0-20261121`: `cbb7cda6ebfbd3792435d6dbfebed8b1cbc406af`.
- `docs/curriculum-kbc-w6-requirements-20261121`: `4a7f7c3ed56dc2fb10ce477418cebc5a2394a0d4`.
- `recovery/source-preservation-20261121`: `7fc45bf71ac1ebd375eba44fd57c983bf18d66e0`.

Setelah update laporan dan checkpoint, SHA HEAD branch W0 yang aktual diverifikasi lagi dari Git ref dan dicantumkan pada handover akhir. Commit SHA tidak dapat menyebut dirinya sendiri di dalam file yang ikut membentuk hash tersebut; gunakan branch ref sebagai nilai final yang otoritatif.

### Temuan keamanan yang tetap OPEN

Tidak ada temuan berikut yang ditutup hanya karena backup/restore PASS:
- role/permission Admin-Operator dan alias ADMIN;
- role/portal Proktor permanen dan pemilihan target assignment;
- akses Admin ke isi Bank Soal;
- akses Admin ke jawaban, detail attempt, score, report/result;
- close, finalize, dan reopen oleh Admin;
- konflik jadwal Proktor dan review saat reschedule;
- isolasi tenant dan jalur query User;
- integritas multi_curriculums, registration, mapping soal, dan histori.

### Audit read-only multi_curriculums/mapping

- Migration `190004` mendefinisikan `MERDEKA`, `KBC`, `K13`, `CUSTOM` sebagai contoh code sejajar. Ini tidak sesuai keputusan Kurmer+KBC satu kesatuan.
- Migration `190006` memiliki node generik `DIMENSI_CINTA` dan `PETA_KASIH`, bukan bukti katalog Panca Cinta/DPL resmi.
- Migration `190007` memiliki tipe outcome generik CP/TP/ATP/KKTP/KBC_CINTA; `200001` menambah version link dan owner registration.
- Migration `190008` dan `200001` menghubungkan question, tenant, curriculum version, outcome, dan owner. Beberapa foreign key mapping menggunakan cascade; perlindungan histori perlu desain ulang setelah audit data/provenance.
- Model Question yang diperiksa belum menunjukkan relasi langsung ke CP/TP legacy atau mapping multi-curriculum.
- Tidak ada query data runtime/database pada pekerjaan ini; temuan adalah audit struktur source/migration statis.

### Rencana satu spesifikasi kerja Web Admin — belum diintegrasikan

Path yang dipertahankan:
1. `.github/workflows/w0-source-snapshot-verify.yml`
2. `docs/backup/W0_SOURCE_TREE_MANIFEST_2026-11-21.tsv`
3. `docs/LAPORAN_AUDIT_W0_WEB_ADMIN_2026-11-21.md`
4. `docs/CHECKPOINT_W0_WEB_ADMIN_2026-11-21.md`
5. `docs/SUPPLEMENTAL_SPEC_MASTER_KURIKULUM_KURMER_KBC_W6.md`
6. `docs/REGISTER_KEPUTUSAN_WEB_ADMIN_KURIKULUM.md`
7. `docs/MATRIKS_AUDIT_DAN_PENGUJIAN_MASTER_KURIKULUM_W6.md`
8. `docs/CHECKPOINT_KURIKULUM_KURMER_KBC_W6_2026-11-21.md`

Rencana: buat entry point `docs/WEB_ADMIN_MASTER_SPECIFICATION.md` pada branch dokumentasi integrasi terpisah; tautkan spesifikasi induk, keputusan, matriks W0, addendum Kurmer-KBC, register, dan acceptance tests W6. Semua file sumber dipertahankan; tidak ada overwrite/delete. Branch tidak digabung otomatis. Sebelum integrasi, tampilkan path, asal branch, konflik, dan resolusi yang diusulkan untuk ditinjau.

### Status akhir rekonsiliasi

- ZIP 6 November: arsip historis; bukan blocker W0.
- SHA-256 dan restore snapshot: PASS.
- W0 formal closure: menunggu penilaian pengguna.
- W1: NOT APPROVED / NOT STARTED.
- W6: NOT STARTED.
- Source aplikasi, migration, seeder, database, produksi, deployment, recovery branch, dan branch Kurmer-KBC: tidak diubah.
