# PETA DOKUMEN DAN PROVENANCE — CBT MADRASAH

## 1. Prinsip

Agent baru wajib memeriksa seluruh sumber yang sudah tersedia sebelum meminta file tambahan atau menyatakan source tidak tersedia. Periksa:
1. repository GitHub seluruh branch/commit/PR yang relevan;
2. seluruh folder `docs/` secara rekursif;
3. workspace rekonstruksi jika tersedia;
4. ZIP baseline asli dan seluruh subfolder;
5. backup, manifest, hash, laporan, checkpoint;
6. Project Files/Library/attachment percakapan yang tersedia.

Jangan menganggap filename atau laporan sebagai bukti bahwa source cocok. Bandingkan commit, SHA-256, ukuran, tanggal, isi, dan kegunaan.

## 2. Dokumen Web Admin yang menjadi acuan

- `docs/PROMPT_INDUK_AGENT_BARU_WEB_ADMIN.md`
- `docs/SPECIFIKASI_INDUK_WEB_ADMIN_MADRASAH.md`
- `docs/CHECKPOINT_WEB_ADMIN_2026-11-21.md`
- `docs/HANDOVER_WEB_ADMIN_AGENT_BARU.md`
- `docs/00_PAKET_HANDOVER_WEB_ADMIN_AGENT_BARU.md`
- `docs/REGISTER_KEPUTUSAN_WEB_ADMIN.md`
- `docs/MATRIKS_AUDIT_WEB_ADMIN_TEMPLATE.md`
- `docs/MATRIKS_PENGUJIAN_WEB_ADMIN.md`

Dokumen lain di repository tetap harus ditemukan dan dibaca jika berkaitan dengan IAM, tenant, RBAC, akademik, kurikulum, import/export, backup, audit, atau source Web.

## 3. Laporan historis yang telah tersedia dalam konteks proyek

File-file berikut pernah diberikan sebagai laporan/checkpoint. Gunakan untuk memahami sejarah dan risiko, tetapi selalu verifikasi terhadap source aktual:
- `CBT_Audit_Tahap_1.md`
- `CBT_Audit_Tahap_2_Fase1B.md`
- `CBT_Rancangan_Integritas_Database_Tahap_3.md`
- `CBT_Implementasi_Tahap_4A.md`
- `CBT_Implementasi_Tahap_4B.md`
- `CBT_Implementasi_Tahap_4C.md`
- `CBT_Implementasi_Tahap_4D.md`
- `CBT_Audit_Tahap_5A_Device_Fencing.md`
- `CBT_Implementasi_Tahap_5B.md`
- `CBT_Implementasi_Tahap_5C.md`
- `CBT_Implementasi_Tahap_5D.md`
- `CBT_Implementasi_Tahap_5E.md`
- `CBT_Implementasi_Tahap_5F.md`
- `CBT_Implementasi_Tahap_5G.md`
- `CBT_Implementasi_Tahap_5H.md`
- `CBT_Implementasi_Tahap_5I.md`
- `CBT_Implementasi_Tahap_5J.md`
- `CBT_Implementasi_Tahap_5K.md`
- `CBT_Implementasi_Tahap_5L.md`
- `CBT_Implementasi_Tahap_5M_FINAL.md`
- `CBT_Implementasi_Tahap_5N.md`
- `CBT_Audit_Tahap_5O_Fencing_Recovery.md`
- `CBT_Implementasi_Tahap_5P1.md`
- `CBT_Implementasi_Tahap_5P_Backend_Android.md`
- `Lanjutkan Eksekusi Sampai Selesai.txt`

Nama di atas menunjukkan file pernah disediakan dalam konteks percakapan; tidak otomatis berarti file tersebut sudah berada di GitHub. Agent harus mencari setiap file di Project Files/Library/attachments/workspace/repository dan mencatat lokasi nyata. Jangan mengarang path.

## 4. Dokumen risiko terpisah

### P0 database
Ada audit dan rencana remediasi P0 terpisah. Jangan masukkan patch P0 ke Web Admin. Catat referensi dokumen aktual dan statusnya, verifikasi source migration terbaru sebelum menyimpulkan.

Migration historis yang pernah ditandai berisiko:
- `2026_11_05_190008_create_question_multi_curriculum_mappings_table.php` — `Schema::dropIfExists()` di `up()`.
- `2026_11_05_200001_fase1c_multi_curriculum_integrity.php` — backfill/provenance/composite FK.

Ini bukan instruksi untuk mengubah migration.

### Device Lease/Fencing
Audit provenance terdahulu menemukan ketidaksesuaian klaim laporan dengan source kandidat yang diperiksa. Status HOLD, enforcement OFF. Jangan restore/recreate source berdasarkan laporan atau manifest tanpa pembuktian.

## 5. Provenance minimum setiap klaim

Untuk setiap klaim implementasi:
- path source;
- branch dan commit SHA;
- blob SHA atau SHA-256;
- bukti jalur eksekusi;
- test yang benar-benar menguji perilaku;
- command/exit code/log;
- environment dan versi;
- status PASS/FAIL/BLOCKED/NOT RUN.

Jika salah satu tidak tersedia, tulis batas bukti secara eksplisit.
