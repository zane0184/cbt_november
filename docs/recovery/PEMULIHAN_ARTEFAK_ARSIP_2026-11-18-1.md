# PEMULIHAN DAN PENGAMANAN ARTEFAK CBT — 21 NOVEMBER 2026

## Status
Dokumen ini merekam artefak yang berhasil ditemukan pada arsip `CBT_MADRASAH_HANDOVER_AGENT_BARU_2026-11-18(1).zip` dan menyiapkan agent berikutnya. Ini adalah **arsip bukti dan indeks pemulihan**, bukan klaim bahwa seluruh source runtime sudah pulih atau siap produksi.

## AUDIT — identitas arsip
- Nama: `CBT_MADRASAH_HANDOVER_AGENT_BARU_2026-11-18(1).zip`
- Ukuran: 140,767,681 byte
- Entri: 13,299
- CRC: PASS (`testzip() = None`)
- SHA-256: `c4af30179c4c941927f6d5d0c32dc592b22896f26d83a76403402c81d38445c4`
- Dua ZIP bersarang yang diperiksa: ZIP handover 17 Nov revisi (3) dan `Audit Integritas Handover_files.zip`; CRC keduanya PASS.
- Enam nama source runtime yang dicari tidak ditemukan dalam ZIP utama maupun dua ZIP bersarang: `CurriculumIntegrityPreflightCommand.php`, `2026_11_06_200002_enforce_curriculum_integrity_not_null.php`, `RegisteredDevice.php`, `DeviceLease.php`, `DeviceLeaseService.php`, `DeviceLeaseController.php`.

## ARTEFAK SOURCE YANG DITEMUKAN
Semua file di bawah ditemukan sebagai raw source di dalam ZIP utama, di bawah:
`01_WORKSPACE/CBT_MADRASAH_RECONSTRUCTION/01_SOURCE_TENANT/backend-laravel/`

| Path relatif | SHA-256 | Byte |
|---|---|---:|
| app/Http/Controllers/Api/V1/ExamAttemptController.php | `27caa27c6c59dd53a6ce36fb776c41d68a75ecbd065750ab2974b15fbc29f9b4` | 20,450 |
| app/Http/Controllers/Api/V1/ExamAnswerController.php | `fb1b11703ae4b024d88f02f3f08120b591b6567c0a35f7932b0c4b0842ddc291` | 15,077 |
| app/Http/Controllers/Api/V1/MonitoringController.php | `37aff3148a67742c1f3768d72deb0d465c3ad2af8804eb1da72aee283e7103e7` | 11,978 |
| database/migrations/2026_11_05_190001_add_fencing_and_lease_to_exam_attempts_table.php | `ba3236ff4ac7e473c518d04567fbe6f38dc32965cc659ec6d1140093ddf5ba8f` | 2,153 |
| database/migrations/2026_11_05_190002_create_registered_devices_table.php | `d4acb22d9fd5e1d038b9ac91665ac2666fa91b4c75b6f75e82ed67d77dc22c9e` | 1,740 |
| database/migrations/2026_11_05_190003_create_device_leases_table.php | `4640f65441cc3ae7563c2c90de01f490f13fbbadd0889278b368b26f42c5a73f` | 1,615 |
| database/migrations/2026_11_05_190008_create_question_multi_curriculum_mappings_table.php | `fd28beb8b2a77510c202877015df23c48f3c18a456ca6d646335e83304d9df32` | 1,674 |
| database/migrations/2026_11_05_200001_fase1c_multi_curriculum_integrity.php | `f633830fbad94a1310bd63499a3f481821cd9ae921e2c65c81594378b75ed976` | 16,924 |

**Penting:** hash di atas adalah hash raw file yang diekstrak dari ZIP 18 Nov (1). Jangan menyamakan otomatis dengan HEAD GitHub atau manifest 17 Nov. Bandingkan SHA-256 secara eksplisit sebelum mengadopsi file ke source aktif.

## BUKTI HISTORIS DEVICE LEASE
ZIP berisi dokumen:
- `CHECKPOINT_DEVICE_LEASE_FENCING_INTEGRATION_2026-11-17(1).md` — SHA-256 `2803f078e390aad7bd4921f76430ae2c941334a92bb0c745a2ae7b400cd4580e`, 22,098 byte.
- `MANIFEST_SHA256_DEVICE_LEASE_RUNTIME_2026-11-17(1).txt` — SHA-256 `483d50cb77d054dc2e8b1e4da8eec5c6d714bbccb376ddd749ffcee51daf958d`, 33,838 byte.

Dokumen tersebut menyimpan klaim historis dan expected hashes untuk runtime files yang tidak ada sebagai raw file di arsip. Manifest hanya dapat memverifikasi file jika raw file ditemukan kembali; manifest bukan pengganti source.

## STATUS AUDIT DAN BATASAN
- Controller aktif dan backup controller ditemukan. Belum dinyatakan lulus fencing; perlu audit diff dan pencarian validasi lease token/generation pada semua jalur start/resume/answer/submit/heartbeat/release.
- Migration 190001–190003 ditemukan sebagai schema. Keberadaan migration tidak membuktikan runtime service/controller enforcement.
- Migration 190008 ditemukan. Audit sebelumnya menandai `Schema::dropIfExists()` di `up()` sebagai risiko kritis data-loss. Jangan jalankan migration dan jangan patch sebelum preflight/source canonical/disposable MySQL 8 tersedia.
- Migration 200001 ditemukan. File 200002 tidak ditemukan.
- Device Lease/Fencing tetap **HOLD — SCHEMA/HISTORICAL EVIDENCE ONLY — RUNTIME NOT VERIFIED**.
- P0.1 yang disetujui user tetap satu-satunya lingkup implementasi berikutnya. P0.2 dan P0.3 belum disetujui untuk mulai.
- Tidak ada database produksi yang disentuh; tidak ada migration dijalankan dalam proses penyelamatan ini.

## DESAIN — cara menyimpan
1. Pertahankan arsip asli lokal sampai agent berikutnya memverifikasi bahwa file raw yang diperlukan sudah ada di GitHub atau media cadangan lain.
2. GitHub branch ini khusus recovery/docs; jangan merge otomatis ke branch source.
3. Raw file yang belum disimpan di GitHub harus diunggah sebagai file terpisah dengan path dan hash yang sama persis. Jangan salin source dari laporan atau menebak implementasi.
4. Agent baru wajib membandingkan hash source terhadap HEAD, memeriksa backup, dan baru membuat backup kerja sebelum perubahan.

## IMPLEMENTASI
- Branch recovery dibuat dari commit dokumentasi sebelumnya.
- Dokumen ini dan indeks checksum di-commit terpisah dari source aplikasi.
- Belum ada perubahan pada source aktif, database, atau konfigurasi deployment.

## PENGUJIAN
- Pemeriksaan CRC ZIP utama dan dua nested ZIP: PASS.
- Ekstraksi file terpilih dan penghitungan SHA-256: PASS.
- Test PHP, PHPUnit, migration, MySQL, integrasi, dan Android: TIDAK DIJALANKAN.
- Tidak ada klaim runtime/produksi.

## INSTRUKSI WAJIB UNTUK AGENT BERIKUTNYA
1. Baca dokumen ini, checkpoint kontinuitas, dan handover pemulihan P0.1 yang sudah ada di `docs/handover/`.
2. Verifikasi branch/commit dan status PR #5 sebelum mengubah source.
3. Periksa seluruh file GitHub dan arsip yang masih tersedia; gunakan checksum tabel sebagai identitas artefak, bukan asumsi versi.
4. Pulihkan raw file yang masih belum ada di GitHub ke direktori `docs/recovery/source-snapshots/2026-11-18-1/` atau direktori karantina setara; jangan menimpa source aktif.
5. Audit migration 190008, 200001, controllers, dan migrations 190001–190003. Buat diff dengan backup controller dan bandingkan manifest Device Lease.
6. Jangan merekonstruksi `RegisteredDevice`, `DeviceLease`, `DeviceLeaseService`, `DeviceLeaseController`, atau preflight command dari asumsi.
7. Lanjutkan P0.1 hanya setelah source canonical dan runtime PHP 8.3 + Composer + MySQL 8/InnoDB disposable terverifikasi. P0.1 saja; tunggu persetujuan sebelum P0.2/P0.3.
8. Jangan mengaktifkan Device Lease/Fencing, jangan jalankan migration pada produksi, jangan build Android via terminal, dan jangan menyatakan siap produksi.

## PENGINGAT PENGHAPUSAN ZIP
Dokumen ini **tidak menjamin** seluruh isi ZIP sudah tersimpan di GitHub. ZIP jangan dihapus dulu sebelum agent memverifikasi bahwa semua raw source, dokumen historis, backup, dan aset yang memang ingin dipertahankan telah memiliki salinan terverifikasi di GitHub atau media lain. Menghapus ZIP sekarang berisiko menghilangkan source/backup yang tidak termasuk daftar artefak terpilih di atas.


## PEMERIKSAAN LANJUTAN: PERBANDINGAN DENGAN SOURCE GITHUB
Pada 21 November 2026, delapan path yang sama juga ditemukan pada branch recovery di source repository. Semua file dapat di-fetch dari GitHub, tetapi SHA-256 arsip dan Git blob SHA-1 GitHub tidak dapat dibandingkan langsung tanpa menghitung SHA-256 atas konten GitHub. Ukuran teks yang dikembalikan juga berbeda untuk delapan file tersebut, sehingga snapshot arsip tidak boleh dianggap identik dengan source GitHub.

GitHub blob SHA-1 saat pemeriksaan:
- ExamAttemptController.php: `ace6a83989375e067b3a7747bc4f320c21222c69`
- ExamAnswerController.php: `333e37007c072243fe828ab038d738fc98d88e8e`
- MonitoringController.php: `335a32029f4d5ab4788ea31fd81088dfdb5acde2`
- migration 190001: `651a55c6c7e99fce7c355fb8906c0e633fb811c1`
- migration 190002: `456dfdafbcb18bf2f36f9f405354bdf8220c1057`
- migration 190003: `61614a0e1a8b9acf505211621d2948afd770dc2f`
- migration 190008: `d0da49dd973959d05325da1068d18a69a5bcbdc2`
- migration 200001: `d4a3de9169ea08ac99325acdebcae6bae9a97625`

Arsip snapshots memiliki byte-length: 20,450; 15,077; 11,978; 2,153; 1,740; 1,615; 1,674; 16,924. GitHub fetch menghasilkan character-length: 20,746; 15,942; 13,521; 2,103; 1,700; 1,580; 1,638; 13,770. Ini menunjukkan perbedaan versi/representasi dan wajib diaudit sebagai dua snapshot terpisah.

**Keputusan keselamatan:** source aktif GitHub tidak ditimpa. Snapshot arsip belum berhasil diunggah sebagai raw file tersendiri. ZIP tetap diperlukan sampai raw snapshot yang ingin dipertahankan benar-benar disimpan dan diverifikasi.


## ADDENDUM PENTING — ATURAN SUMBER KEBENARAN (DIKONFIRMASI USER)
**GitHub branch source terbaru adalah acuan utama dan wajib dipertahankan.** Arsip ZIP 18 Nov (1) adalah sumber historis/pemulihan, bukan pengganti source terbaru.

Branch source yang diverifikasi:
- `fix/web-portal-usability-rbac-20261120`
- HEAD: `ade5daa6cf853c164c4f51ec6e25ca68ce40b4f2`
- Tree: `f5cc0a523f52d19b0fa9ca7c30fb7576d3ace615`
- Commit: `ci: enable verified web deployment package for PR5 branch`
- URL: https://github.com/zane0184/cbt_november/commit/ade5daa6cf853c164c4f51ec6e25ca68ce40b4f2

### Instruksi wajib
1. Jangan pernah mengganti, menurunkan versi, atau menimpa file branch source terbaru menggunakan file ZIP 18 Nov (1), ZIP 18 Nov (2), ZIP 17 Nov, atau arsip lebih lama.
2. Jika file arsip berbeda dengan HEAD GitHub, pertahankan HEAD GitHub. File arsip hanya boleh disimpan sebagai snapshot historis terpisah dengan nama/path dan checksum asal.
3. Pemulihan hanya untuk artefak yang tidak ada di GitHub, setelah pencarian branch/commit/tree selesai. Jangan duplikasi file yang sudah ada.
4. Untuk file yang sama: GitHub HEAD tetap canonical; file historis masuk karantina `docs/recovery/archive-snapshots/` bila perlu, bukan ke source aktif.
5. Tidak ada force push, reset, rebase destruktif, overwrite, merge, atau pengubahan branch source tanpa instruksi dan persetujuan user.
6. Branch `recovery/source-preservation-20261121` hanya untuk laporan/manifest/snapshot recovery. Branch ini bukan pengganti branch source.
7. Sebelum menghapus ZIP, verifikasi inventaris semua artefak unik yang belum ada di GitHub dan pastikan sudah disimpan terpisah. Jangan hapus arsip yang masih satu-satunya salinan source/backup.
