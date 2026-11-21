# REKONSILIASI DOKUMENTASI W0 — SHA, BRANCH, DAN PROVENANCE

**Tanggal:** 21 November 2026 (WIB)  
**Repository:** `zane0184/cbt_november`  
**Jenis:** Catatan rekonsiliasi dokumentasi, bersifat append-only  
**Status:** Dibuat pada branch dokumentasi terpisah; tidak mengubah source aplikasi.  
**Branch dokumen:** `docs/web-admin-master-spec-20261121`  
**Base:** `2b4321ce032fd5e67cf022da8564a5287b8cef6f` (W0 HEAD saat branch dibuat)

## 1. Tujuan dan batas

Dokumen ini merekam koreksi hasil pemeriksaan GitHub atas laporan/checkpoint W0 dan hubungan branch W0 dengan dokumentasi Kurmer-KBC.

Batas:
- Tidak membuka atau membandingkan ZIP historis 6 November.
- Tidak mengubah source aplikasi, migration, seeder, database, konfigurasi, VPS, atau deployment.
- Tidak menggabungkan branch W0, recovery, atau Kurmer-KBC.
- Tidak menghapus/menimpa dokumen sumber.
- Tidak menutup temuan keamanan dan tidak mengesahkan W0/W1/W6.

## 2. Sumber kebenaran

1. Keputusan proyek terbaru yang disepakati pengguna adalah otoritas desain/persyaratan.
2. GitHub working source adalah otoritas kondisi source aktual.
3. Branch/commit/PR adalah riwayat perubahan.
4. Laporan/checkpoint adalah catatan audit dan handover.
5. Manifest/snapshot adalah bukti integritas dan pemulihan.

ZIP baseline 6 November hanya arsip historis; bukan blocker dan tidak digunakan sebagai sumber implementasi.

## 3. Git refs yang diperiksa

Pemeriksaan langsung GitHub pada 21 November 2026:

| Fungsi | Branch | HEAD |
|---|---|---|
| Working branch Web Admin W0 | `work/web-admin-w0-20261121` | `2b4321ce032fd5e67cf022da8564a5287b8cef6f` |
| Dokumentasi persyaratan Kurmer-KBC | `docs/curriculum-kbc-w6-requirements-20261121` | `4a7f7c3ed56dc2fb10ce477418cebc5a2394a0d4` |
| Source pin/recovery | `recovery/source-preservation-20261121` | `7fc45bf71ac1ebd375eba44fd57c983bf18d66e0` |

Branch dokumentasi induk ini dibuat dari W0 HEAD `2b4321ce032fd5e67cf022da8564a5287b8cef6f`. SHA HEAD final branch ini harus dibaca dari Git ref setelah setiap commit; jangan menulis SHA dirinya sendiri ke file yang ikut membentuk commit tersebut.

## 4. Hasil perbandingan branch

### 4.1 Recovery → W0

- Status GitHub: `ahead`.
- Ahead: 18 commit.
- Behind: 0.
- Merge base: source pin `7fc45bf71ac1ebd375eba44fd57c983bf18d66e0`.
- File berubah: tepat empat path.

1. `.github/workflows/w0-source-snapshot-verify.yml`
2. `docs/CHECKPOINT_W0_WEB_ADMIN_2026-11-21.md`
3. `docs/LAPORAN_AUDIT_W0_WEB_ADMIN_2026-11-21.md`
4. `docs/backup/W0_SOURCE_TREE_MANIFEST_2026-11-21.tsv`

Perbandingan menunjukkan tidak ada perubahan source aplikasi pada rentang Recovery → W0; empat path tambahan adalah workflow dan dokumentasi/manifest W0.

### 4.2 W0 → Kurmer-KBC

- Status: `diverged`.
- Ahead: 4.
- Behind: 4.
- Merge base: `cbb7cda6ebfbd3792435d6dbfebed8b1cbc406af`.

Empat path yang hanya ditambahkan pada sisi branch Kurmer-KBC:

1. `docs/CHECKPOINT_KURIKULUM_KURMER_KBC_W6_2026-11-21.md`
2. `docs/MATRIKS_AUDIT_DAN_PENGUJIAN_MASTER_KURIKULUM_W6.md`
3. `docs/REGISTER_KEPUTUSAN_WEB_ADMIN_KURIKULUM.md`
4. `docs/SUPPLEMENTAL_SPEC_MASTER_KURIKULUM_KURMER_KBC_W6.md`

Dengan demikian, angka lama ahead 4/behind 3 sudah kedaluwarsa. Angka yang benar pada pemeriksaan ini adalah ahead 4/behind 4. Kedua branch tidak disatukan.

## 5. Rekonsiliasi riwayat commit W0

Rantai empat commit W0 setelah merge base:

| Commit | Pesan ringkas | Perubahan |
|---|---|---|
| `cbb7cda6ebfbd3792435d6dbfebed8b1cbc406af` | Merge base bersama | Titik percabangan |
| `9e6e6f0e9693fb728ca8c0040b92463e15f58f0a` | `docs(w0): reconcile GitHub source of truth and historical ZIP status` | Revisi laporan W0 |
| `e62fb14f1b68b99b819d64e774b694e7c64d75d2` | `docs(w0): reconcile checkpoint with GitHub source hierarchy` | Revisi checkpoint |
| `a412b672de6cf7c57fe22008b608fc8298854b31` | `docs(w0): correct Kurmer-KBC checkpoint path` | Koreksi path checkpoint Kurmer-KBC |
| `2b4321ce032fd5e67cf022da8564a5287b8cef6f` | `docs(w0): correct checkpoint parent reference` | Koreksi referensi parent checkpoint; HEAD W0 |

Perbandingan merge base → W0 mengonfirmasi empat commit ahead dan nol behind.

Commit `8073cbd4c281b786e56d0b3d688ae2ce194dd872` adalah revisi laporan lebih lama, bukan revisi laporan W0 terkini. Commit pembaruan laporan terkini yang disebut dalam riwayat branch adalah `9e6e6f0e9693fb728ca8c0040b92463e15f58f0a`.

## 6. Pembedaan jenis SHA

Nilai berikut adalah jenis identitas yang berbeda dan tidak boleh dipertukarkan.

| Objek | Jenis | Nilai |
|---|---|---|
| HEAD W0 | Commit SHA | `2b4321ce032fd5e67cf022da8564a5287b8cef6f` |
| Commit laporan terkini | Commit SHA | `9e6e6f0e9693fb728ca8c0040b92463e15f58f0a` |
| Commit checkpoint terkini | Commit SHA | `2b4321ce032fd5e67cf022da8564a5287b8cef6f` |
| Source pin recovery | Commit SHA | `7fc45bf71ac1ebd375eba44fd57c983bf18d66e0` |
| Tree source pin | Git tree SHA-1 | `144364e4163ccc62f74bc1fbf93b601380ed2723` |
| Blob laporan W0 | Git blob SHA-1 | `1ce38b71c5dab233794fb1f101c422f232a5b73a` |
| Blob checkpoint W0 | Git blob SHA-1 | `e0ea7ee3cadb76b4b76da1497ef2585da623aead` |
| Blob manifest | Git blob SHA-1 | `f74b89d00015a15c04e4e062d46571afdd3074bc` |
| Blob workflow | Git blob SHA-1 | `e059c9834107e88565a3717ee8d624bbf34c82df` |

Riwayat blob checkpoint yang dicatat:

| Versi | Blob SHA-1 |
|---|---|
| Pada merge base `cbb7cda...` | `fe7be8a1fafab26fa8d3b6f8398f2d5d5cfa9d72` |
| Setelah `e62fb14f...` | `184ae6b4743a7ff7ac23a376a609c056c6c78952` |
| Setelah `a412b672...` | `dc86e55994d58b290d51090c0d776108641bc072` |
| Setelah `2b4321ce...` | `e0ea7ee3cadb76b4b76da1497ef2585da623aead` |

### 6.1 Catatan koreksi checkpoint

Checkpoint W0 saat ini masih mencantumkan `8073cbd4...` dengan label “Commit laporan audit revisi”. Karena laporan terkini diperbarui pada `9e6e6f0e...`, referensi lama tersebut perlu diberi penjelasan historis atau addendum koreksi. Jangan menghapus catatan lama.

Checkpoint mencatat `cbb7cda...` sebagai HEAD saat rekonsiliasi dimulai; itu adalah catatan historis yang benar. HEAD akhir W0 adalah `2b4321ce...`. Kedua waktu harus dibedakan, bukan saling menggantikan.

Laporan W0 memiliki catatan historis ZIP yang lebih lama. Addendum terbaru menetapkan ZIP sebagai arsip historis, bukan blocker. Addendum terbaru mengendalikan status saat ini; riwayat lama dipertahankan sebagai sejarah.

## 7. Checksum snapshot terdahulu

Checksum berikut dicatat dari bukti snapshot sebelumnya:

- Manifest SHA-256: `af72d7556d4c96c5ea9e6ec0db226870cdb19df076f4a6828dd7d07a22f45a02`
- Snapshot tar.gz SHA-256: `6fca2435b62f17dbbf339935083725540148fffeab943acd14d2a7db44b3bc46`
- Artifact ZIP SHA-256: `efe94178561b17ccb956f8bcd373d01e28c28a90c42718bfdc2eb4c673cd0bd8`

Bukti workflow terdahulu:
- GitHub Actions run ID: `44870810068`.
- Source pin: `7fc45bf71ac1ebd375eba44fd57c983bf18d66e0`.
- 531 tracked files.
- Restore: 531 dipulihkan, missing 0, extra 0, changed 0.
- Kesimpulan terdahulu: `RESTORE_VERIFICATION=PASS`.

Ini adalah bukti run terdahulu, bukan pengujian ulang pada commit dokumen induk. Checksum artifact bukan Git commit/blob SHA.

## 8. Inventaris delapan dokumen utama

| Path | Branch asal | Blob SHA-1 | Fungsi/status |
|---|---|---|---|
| `.github/workflows/w0-source-snapshot-verify.yml` | W0 | `e059c9834107e88565a3717ee8d624bbf34c82df` | Workflow snapshot/SHA-256/restore; pertahankan |
| `docs/backup/W0_SOURCE_TREE_MANIFEST_2026-11-21.tsv` | W0 | `f74b89d00015a15c04e4e062d46571afdd3074bc` | Manifest source pin 531 file; pertahankan |
| `docs/LAPORAN_AUDIT_W0_WEB_ADMIN_2026-11-21.md` | W0 | `1ce38b71c5dab233794fb1f101c422f232a5b73a` | Audit route/RBAC/tenant dan W0–W10; addendum terbaru berlaku |
| `docs/CHECKPOINT_W0_WEB_ADMIN_2026-11-21.md` | W0 | `e0ea7ee3cadb76b4b76da1497ef2585da623aead` | Checkpoint/handover; referensi commit laporan lama perlu addendum |
| `docs/SUPPLEMENTAL_SPEC_MASTER_KURIKULUM_KURMER_KBC_W6.md` | Kurmer-KBC | `ee4d02fae6d00301dfcb773a2fdfcb93fd192a76` | Persyaratan master Kurmer-KBC; belum implementasi |
| `docs/REGISTER_KEPUTUSAN_WEB_ADMIN_KURIKULUM.md` | Kurmer-KBC | `98159ac46794f62428012897edc7d1525b2f606a` | Register keputusan kurikulum |
| `docs/MATRIKS_AUDIT_DAN_PENGUJIAN_MASTER_KURIKULUM_W6.md` | Kurmer-KBC | `b1109886f40f4a9e9e2d0f74daf4587d36c67b9a` | Matriks audit dan acceptance W6; bukan bukti runtime |
| `docs/CHECKPOINT_KURIKULUM_KURMER_KBC_W6_2026-11-21.md` | Kurmer-KBC | `289a07c088c2d4744eaaf9e159979ecf18533ee2` | Checkpoint dokumentasi W6; implementasi belum dimulai |

Dokumen Kurmer-KBC tetap berada pada branch asalnya dan dirujuk dari spesifikasi induk menggunakan URL yang dipatok ke commit `4a7f7c3ed56dc2fb10ce477418cebc5a2394a0d4`. Tidak ada merge.

## 9. Inventaris dokumen pendukung/historis

Daftar berikut berasal dari inventaris source pin recovery dan dipertahankan sebagai dokumen provenance/riwayat. Dokumen ini bukan pengganti keputusan terbaru atau source aktif.

1. `docs/CHECKPOINT_LANJUTAN_2026-11-20_P0_95D_2BA.md` — checkpoint P0.95D.2BA.
2. `docs/CHECKPOINT_P0_96A_WEB_DEPLOYMENT_PACKAGE.md` — checkpoint paket deployment historis.
3. `docs/CHECKPOINT_P0_96G_INSTALLER_PRIVATE_DB_CONFIG_2026-11-20.md` — checkpoint installer.
4. `docs/CHECKPOINT_WEB_AUDIT_20261120.md` — audit Web CBT terdahulu.
5. `docs/HANDOVER_AGENT_P0_96G_INSTALLER_FIX_2026-11-20.md` — handover perbaikan installer.
6. `docs/HANDOVER_PROMPT_AGENT_BARU_WEB_CBT_20261120.md` — keputusan/handover Web CBT.
7. `docs/handover/CHECKPOINT_KONTINUITAS_DAN_ATURAN_HANDOVER_2026-11-21.md` — aturan kontinuitas dan handover.
8. `docs/handover/HANDOVER_PEMULIHAN_SOURCE_HILANG_DAN_P0_1_2026-11-21.md` — provenance pemulihan source.
9. `docs/handover/PROMPT_AGENT_BARU_P0_1_2026-11-21.md` — instruksi agent historis.
10. `docs/LAPORAN_P0_95D_2BA_CI_GREEN_RBAC_ROOT_FIX_2026-11-20.md` — audit RBAC/portal.
11. `docs/LAPORAN_P0_96A_WEB_DEPLOYMENT_PACKAGE.md` — laporan paket deployment historis.
12. `docs/PANDUAN_AKTIVASI_PRE_ACTIVATION_2026.md` — panduan aktivasi operasional.
13. `docs/PANDUAN_INSTALASI_WEB_CBT_MANDIRI.md` — panduan instalasi.
14. `docs/PROMPT_AGENT_KEEMPAT_CBT_MADRASAH.md` — handover agent sebelumnya.
15. `docs/recovery/PEMULIHAN_ARTEFAK_ARSIP_2026-11-18-1.md` — catatan pemulihan arsip.
16. `docs/recovery/SHA256_INDEX_ARSIP_2026-11-18-1.txt` — indeks checksum arsip.
17. `docs/backup_before_patch/BUILD_WEB_DEPLOYMENT_WORKFLOW_20261120.yml.before` — backup workflow sebelum patch; bukan source aktif.
18. `CBT_MADRASAH_RECONSTRUCTION/docs/backup_before_patch/P0_95D_2BB_markdown_escape_fix_20261120/CHECKPOINT_P0_95D_2BB_2026-11-20.md.before` — backup checkpoint.
19. `CBT_MADRASAH_RECONSTRUCTION/docs/backup_before_patch/P0_95D_2BB_markdown_escape_fix_20261120/LAPORAN_P0_95D_2BB_PENUGASAN_PROKTOR_2026-11-20.md.before` — backup laporan Proktor.
20. `CBT_MADRASAH_RECONSTRUCTION/docs/backup_before_patch/P0_95D_2BB_markdown_escape_fix_20261120/MANIFEST_P0_95D_2BB_SHA256.txt.before` — manifest backup.
21. `CBT_MADRASAH_RECONSTRUCTION/docs/CHECKPOINT_P0_95D_2BB_2026-11-20.md` — checkpoint penugasan Proktor.
22. `CBT_MADRASAH_RECONSTRUCTION/docs/LAPORAN_P0_95D_2BB_PENUGASAN_PROKTOR_2026-11-20.md` — laporan implementasi Proktor sementara.
23. `CBT_MADRASAH_RECONSTRUCTION/docs/MANIFEST_P0_95D_2BB_SHA256.txt` — manifest backup P0.95D.2BB.
24. `README.md` — gambaran umum repository dan batasan.

## 10. Rancangan resolusi dokumentasi

- `docs/WEB_ADMIN_MASTER_SPECIFICATION.md` menjadi entry point normatif W0–W10.
- Dokumen audit W0 tetap menjadi sumber temuan statis dan route/action-permission matrix.
- Checkpoint tetap menjadi catatan kronologis; koreksi dilakukan dengan addendum, bukan penghapusan.
- Empat dokumen Kurmer-KBC tetap menjadi sumber rinci W6 dan ditautkan dengan URL commit-pinned.
- Matriks W6 tetap menjadi acceptance criteria, bukan bukti test PASS.
- Dokumen P0/handover lama tetap menjadi riwayat; keputusan terbaru mengendalikan konflik.
- Tidak ada kebutuhan merge branch untuk membangun satu entry point; referensi commit-pinned menjaga provenance.

## 11. Temuan yang tetap OPEN

- Konsolidasi Admin/Operator dan migrasi data role legacy.
- Alias role `ADMIN`.
- Role/portal Proktor permanen.
- Akses Admin ke isi Bank Soal.
- Akses Admin ke jawaban, detail attempt, score, report/result.
- Hak Admin untuk monitoring, close, finalize, reopen, dan operasi token.
- Konflik interval assignment Proktor lintas ujian/kelas.
- Status `NEEDS_REVIEW` saat jadwal ujian berubah.
- Tenant isolation seluruh jalur query/relasi, terutama model User dan `withoutGlobalScopes`.
- Rekonsiliasi `multi_curriculums` yang memposisikan KBC sejajar dengan MERDEKA.
- Integritas mapping soal, CP/TP legacy, FK cascade, snapshot, dan histori.
- Katalog Panca Cinta/DPL resmi terversi belum terbukti pada source.
- Kode RDM dan CP/TP/ATP resmi belum diverifikasi/di-seed.
- W0 formal closure masih menunggu penilaian pengguna.
- W1 belum disetujui/dimulai; W6 belum dimulai.

Snapshot/CI PASS tidak menutup temuan di atas.

## 12. PENGUJIAN dan batas bukti

Pada rekonsiliasi sumber yang mendasari dokumen ini, pemeriksaan yang dilakukan meliputi:
- pembacaan tiga Git refs;
- compare Recovery → W0;
- compare W0 → Kurmer-KBC;
- verifikasi merge base dan jumlah commit;
- pembacaan file W0 dan empat dokumen Kurmer-KBC;
- pembacaan blob SHA dokumen;
- pembacaan/inventaris dokumen pendukung.

Tidak dilakukan:
- automated test aplikasi;
- migration atau database test;
- E2E/browser runtime;
- MySQL integration test baru;
- deployment atau production operation;
- unduh/ekstrak ulang artifact snapshot pada pekerjaan rekonsiliasi dokumentasi ini.

Hasil snapshot 531/531 adalah bukti terdahulu yang dicatat di laporan/checkpoint W0. Jangan menyebutnya pengujian baru.

## 13. Catatan perubahan branch dokumentasi ini

Branch `docs/web-admin-master-spec-20261121` dibuat dari W0 HEAD `2b4321ce032fd5e67cf022da8564a5287b8cef6f`.

File yang dibuat pada branch ini:
1. `docs/WEB_ADMIN_MASTER_SPECIFICATION.md` — entry point spesifikasi induk.
2. `docs/REKONSILIASI_DOKUMENTASI_W0_2026-11-21.md` — dokumen ini.

Tidak ada dokumen sumber yang dipindahkan, ditimpa, atau dihapus. Tidak ada perubahan source aplikasi, migration, database, branch W0, branch recovery, atau branch Kurmer-KBC. Branch ini belum digabung ke branch lain.

---

**Status akhir:** rekonsiliasi dokumentasi dicatat; temuan keamanan tetap OPEN; W0 belum ditutup secara formal; W1/W6 belum dimulai. Semua implementasi berikutnya tetap menunggu gate dan persetujuan terpisah.


## 14. ADDENDUM REKONSILIASI — STATUS TERBARU DAN PRECEDENCE (21 NOVEMBER 2026)

### 14.1 Branch dan commit yang diverifikasi ulang
- `recovery/source-preservation-20261121`: `7fc45bf71ac1ebd375eba44fd57c983bf18d66e0`.
- `work/web-admin-w0-20261121`: `2b4321ce032fd5e67cf022da8564a5287b8cef6f`.
- `docs/curriculum-kbc-w6-requirements-20261121`: `4a7f7c3ed56dc2fb10ce477418cebc5a2394a0d4`.
- `docs/web-admin-master-spec-20261121`: `8956906c9f0750ba9c6ce845582ae279c9f68e74`.
- `docs/admin-web-spec-20261121`: `3e0b9195c9768c0d892322f926f25a6b9c4fb135`.
- `docs/web-admin-agent-handover-20261121`: `a7bef3dea2b1f57c79d34c775822bd5bde24998f`.
- `docs/web-admin-audit-reconciliation-20261121`: `7617bf07e0e7f83935d5be880652cc65c355566a`.
- `docs/source-canonical-design-closure-20261121`: `59aa76ae60de396216e9754d200cb1b6d806e9e0`.
- `docs/web-admin-w0-recheck-20261121`: `fb058f05264b8d464d687583c53f06fbb7fe54f7`.
- `docs/project-continuity-policy-20261121`: `c3efd541d1f7d321f2d2f6ba1495c346c9992785`.
- `docs/prompt-induk-audit-20261120`: `396b06aaaa0a84b0cef355441fc39b93bd170126`.

Daftar branch repository yang dipaginasi juga memperlihatkan branch backup, source, P0, dan work lain; branch tambahan tidak otomatis menjadi kandidat source Web Admin. Branch aktif ditentukan melalui tujuan, provenance, PR, dan isi commit.

### 14.2 Hubungan antarbranch
- Recovery → W0: W0 ahead 18, behind 0; empat path tambahan, seluruhnya workflow/dokumen/manifest, tidak ada source aplikasi.
- W0 → Kurmer-KBC: diverged, masing-masing ahead 4/behind 4; empat dokumen W6 berada pada branch terpisah.
- Handover → source-canonical: source-canonical ahead 6, behind 0; enam perubahan dokumentasi.
- Source-canonical → W0-recheck: W0-recheck ahead 2, behind 0; menambahkan laporan audit read-only W0 dan checkpoint W0.
- Master-spec → source-canonical: diverged, source-canonical ahead 45 dan behind 27; bukan fast-forward. Jangan merge otomatis.
- Master-spec → W0-recheck juga merupakan riwayat divergen dari common ancestor; konsolidasi dilakukan melalui branch dokumentasi baru dan salinan/backup eksplisit, bukan merge.

### 14.3 PR terkini
Pemeriksaan detail PR menunjukkan:
- PR #1 OPEN, Draft, Unmerged.
- PR #2 CLOSED, Unmerged.
- PR #3 OPEN, Draft, Unmerged.
- PR #4 OPEN, Draft, Unmerged.
- PR #5 OPEN, Draft, Unmerged; head `ade5daa6cf853c164c4f51ec6e25ca68ce40b4f2`, base `158227362eea82fc7ec7e9c232736596015739d2`.
- PR #6 OPEN, Draft, Unmerged; head `3e0b9195c9768c0d892322f926f25a6b9c4fb135`.
- PR #7 OPEN, Draft, Unmerged; head `a7bef3dea2b1f57c79d34c775822bd5bde24998f`.

PR #5/#6/#7 tidak diubah dan tidak digabung. PR #6/#7 merupakan rangkaian dokumentasi lama; branch dokumen yang lebih baru belum otomatis menjadi PR.

### 14.4 Keputusan yang mengikat dan konflik lama
- Role aktif hanya `ADMIN_MADRASAH`; `OPERATOR` tidak aktif dan tidak boleh menjadi alias runtime.
- Admin hanya administrasi, master referensi yang menjadi kewenangannya, dan assignment Proktor. Admin tidak memiliki operasional CBT, termasuk monitoring operasional atau analitik hasil ujian Guru.
- Guru menguasai seluruh operasional CBT.
- Proktor adalah Guru aktif dengan assignment sementara; tidak ada role/portal permanen.
- Perubahan jadwal ujian dilakukan Guru dan wajib menandai assignment terdampak `NEEDS_REVIEW`.
- W0-recheck report/checkpoint yang menyatakan ZIP raw BLOCKED adalah status historis yang telah digantikan oleh keputusan pengguna bahwa ZIP 6 November adalah arsip historis non-blocker. ZIP tidak dibuka.
- Catatan lama mengenai CI #671 tidak boleh dipakai sebagai status terkini. Bukti yang berbeda commit harus tetap diikat ke commit asalnya; tidak ada test baru yang dijalankan dalam audit dokumentasi ini.
- Checkpoint W0 pada branch lama masih menyimpan referensi commit laporan terdahulu `8073cbd4...`; laporan W0 yang lebih baru pada histori dokumentasi adalah commit `9e6e6f0e9693fb728ca8c0040b92463e15f58f0a`. Catatan lama dipertahankan sebagai histori, bukan referensi terkini.

### 14.5 Hierarki dokumen kanonis
1. Keputusan pengguna terbaru.
2. `docs/WEB_ADMIN_MASTER_SPECIFICATION.md` sebagai entry point dan navigasi.
3. `docs/SPECIFIKASI_INDUK_WEB_ADMIN_MADRASAH.md` sebagai spesifikasi rinci; addendum terbaru mengatasi konflik teks lama.
4. `docs/REGISTER_KEPUTUSAN_WEB_ADMIN.md` sebagai register keputusan.
5. `docs/MATRIKS_AUDIT_WEB_ADMIN_TEMPLATE.md` dan `docs/MATRIKS_PENGUJIAN_WEB_ADMIN.md`.
6. `docs/PETA_DOKUMEN_PROVENANCE_CBT.md`, laporan audit, checkpoint, prompt dan paket handover.
7. Dokumen W6 Kurmer-KBC sebagai spesifikasi tambahan kurikulum; tetap branch/commit terpisah sampai salinan kanonisnya tercatat.
8. Laporan/branch historis dengan label HISTORICAL/SUPERSEDED bila bertentangan.

### 14.6 Status pekerjaan
Audit metadata GitHub, branch, PR, dan dokumen dilakukan read-only. Tidak ada source aplikasi, migration, database, .env, konfigurasi VPS, atau deployment yang diubah. Pengujian runtime, browser, concurrency, import/restore, dan UAT tidak dijalankan dalam sesi rekonsiliasi.
