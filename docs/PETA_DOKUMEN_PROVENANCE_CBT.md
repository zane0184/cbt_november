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


## 6. Addendum provenance — konsolidasi 21 November 2026

### 6.1 Branch HEAD yang diverifikasi
| Branch | HEAD SHA | Peran |
|---|---|---|
| recovery/source-preservation-20261121 | 7fc45bf71ac1ebd375eba44fd57c983bf18d66e0 | Snapshot preservasi source kandidat |
| work/web-admin-w0-20261121 | 2b4321ce032fd5e67cf022da8564a5287b8cef6f | W0 dokumentasi/snapshot |
| docs/curriculum-kbc-w6-requirements-20261121 | 4a7f7c3ed56dc2fb10ce477418cebc5a2394a0d4 | Paket spesifikasi W6 Kurmer-KBC |
| docs/web-admin-master-spec-20261121 | 8956906c9f0750ba9c6ce845582ae279c9f68e74 | Entry point master 360 baris |
| docs/admin-web-spec-20261121 | 3e0b9195c9768c0d892322f926f25a6b9c4fb135 | Versi spesifikasi awal |
| docs/web-admin-agent-handover-20261121 | a7bef3dea2b1f57c79d34c775822bd5bde24998f | Paket handover |
| docs/web-admin-audit-reconciliation-20261121 | 7617bf07e0e7f83935d5be880652cc65c355566a | Rekonsiliasi audit |
| docs/source-canonical-design-closure-20261121 | 59aa76ae60de396216e9754d200cb1b6d806e9e0 | Spesifikasi rinci + addendum 32 |
| docs/web-admin-w0-recheck-20261121 | fb058f05264b8d464d687583c53f06fbb7fe54f7 | Addendum dua dokumen W0 recheck |
| docs/project-continuity-policy-20261121 | c3efd541d1f7d321f2d2f6ba1495c346c9992785 | Kebijakan kontinuitas |
| docs/prompt-induk-audit-20261120 | 396b06aaaa0a84b0cef355441fc39b93bd170126 | Prompt audit historis |

### 6.2 Dokumen kanonis dan sumber branch
- Entry point: `docs/WEB_ADMIN_MASTER_SPECIFICATION.md`.
- Spesifikasi rinci: `docs/SPECIFIKASI_INDUK_WEB_ADMIN_MADRASAH.md` (Addendum 33 menjadi precedence terbaru).
- Register: `docs/REGISTER_KEPUTUSAN_WEB_ADMIN.md`.
- Audit: `docs/MATRIKS_AUDIT_WEB_ADMIN_TEMPLATE.md` (23 modul dipertahankan).
- Test: `docs/MATRIKS_PENGUJIAN_WEB_ADMIN.md` (T-001–T-132 dipertahankan; T-133–T-168 ditambahkan).
- Kurikulum: empat dokumen W6 dari branch `docs/curriculum-kbc-w6-requirements-20261121` pada SHA di atas. Jangan menyatakan telah merge; branch tetap terpisah dan referensi ini commit-pinned.
- W0 report/checkpoint lama dipertahankan sebagai histori dan diberi status superseded jika bertentangan.

### 6.3 Label status dokumen
- ACTIVE: keputusan yang berlaku dan telah ditetapkan pengguna.
- HISTORICAL: bukti/catatan yang benar untuk waktu pemeriksaan sebelumnya.
- SUPERSEDED: ketentuan lama yang digantikan oleh keputusan lebih baru.
- PENDING VERIFICATION: fakta yang belum dapat dibuktikan ulang dari source/commit atau sumber primer.

### 6.4 Batas bukti
Branch/commit/PR dibaca melalui GitHub. Tidak ada local worktree yang diperiksa. SHA-256 backup dihitung atas teks UTF-8 hasil fetch dan salinan backup dibuat di branch dokumentasi baru; ini bukan backup lokal. Tidak ada ZIP baseline dibuka. Tidak ada CI, runtime, browser, MySQL concurrency, import/restore, atau UAT baru dijalankan.


## 7. Addendum provenance — finalisasi dokumentasi dan keputusan Proktor (21-11-2026)

### 7.1 Branch konsolidasi aktual
- Branch konsolidasi: `docs/canonical-reconciliation-admin-guru-20261121`.
- HEAD pada awal pemeriksaan ulang: `55cfe325aef13abd4dae97c7d46e127a0ff47bbb`. Commit dokumentasi finalisasi setelah SHA ini tercatat pada riwayat branch; checkpoint final mencatat HEAD sesudah rangkaian perubahan.
- Branch source pin: `recovery/source-preservation-20261121` @ `7fc45bf71ac1ebd375eba44fd57c983bf18d66e0`.
- Branch W0: `work/web-admin-w0-20261121` @ `2b4321ce032fd5e67cf022da8564a5287b8cef6f`.
- Branch W6: `docs/curriculum-kbc-w6-requirements-20261121` @ `4a7f7c3ed56dc2fb10ce477418cebc5a2394a0d4`.
- Branch main @ `91dae257b2210ef89c90534185a3cbdd8979ffe0`; bukan otomatis working source.
- Branch recovery, W0, W6, main, dan PR head tidak diubah.

### 7.2 Status PR pada verifikasi ulang
| PR | Status | Draft | Head | Base | Tindakan |
|---|---|---|---|---|---|
| #1 | OPEN | Ya | hotfix/fase1c-mysql8-fk-name-20261119 | p0-95d-migration-gate-20261118 | Tidak diubah |
| #2 | CLOSED, unmerged | Ya | work/schema-dump-export-20261119 | main | Tidak diubah |
| #3 | OPEN | Ya | work/schema-dump-export-20261119 | main | Tidak diubah |
| #4 | OPEN | Ya | fix/p0-96g-installer-private-db-config-20261120 | work/schema-dump-export-20261119 | Tidak diubah |
| #5 | OPEN | Ya | fix/web-portal-usability-rbac-20261120 | fix/p0-96g-installer-private-db-config-20261120 | Tidak diubah |
| #6 | OPEN | Ya | docs/admin-web-spec-20261121 | fix/web-portal-usability-rbac-20261120 | Tidak diubah |
| #7 | OPEN | Ya | docs/web-admin-agent-handover-20261121 | docs/admin-web-spec-20261121 | Tidak diubah |

### 7.3 Rekonsiliasi jumlah path
Compare dari base W0-recheck `fb058f05264b8d464d687583c53f06fbb7fe54f7` ke branch pada HEAD awal `55cfe325aef13abd4dae97c7d46e127a0ff47bbb`: 28 path berubah/ditambahkan, 0 deleted, semuanya `docs/`, tidak ada source aplikasi. Rincian: 12 dokumen kerja (6 added, 6 modified) dan 16 backup/manifest. Angka 18 dan 24 dalam laporan/checkpoint versi sebelumnya adalah hasil compare parsial sebelum backup finalisasi lengkap.

### 7.4 Backup finalisasi
- Folder: `docs/backup/reconciliation-2026-11-21/finalization/`.
- Manifest: `MANIFEST.tsv`.
- Sebanyak 16 file dokumentasi sebelum perubahan disalin dan diverifikasi blob SHA backup identik dengan blob sumber.
- Backup dibuat di branch GitHub, bukan backup lokal. Backup historis sebelumnya tetap dipertahankan.

### 7.5 Keputusan final Proktor
DEC-WA-026 / Addendum 35 berstatus ACTIVE:
- PENDING: DENY.
- CONFIRMED: satu-satunya status assignment yang dapat memberikan capability setelah seluruh validasi Guru aktif, TeacherProfile, tenant, exam, rombel, schedule revision, permission/scope, revoke/conflict, dan waktu server.
- ACTIVE bukan status assignment.
- NEEDS_REVIEW, REVOKED, EXPIRED, COMPLETED: DENY.
- Capability hanya pada `starts_at <= serverNow < expires_at`, diverifikasi pada setiap request.
- Nota OPEN/PROPOSED lama dan Addendum 34 SUPERSEDED.

### 7.6 Source dan test provenance
Pada pemeriksaan ulang, 14 dari 15 path source kunci yang dipilih berhasil di-fetch pada recovery branch; path `backend-laravel/app/Services/ExamScheduleChangeService.php` tidak ditemukan pada ref tersebut. Source blob yang ditemukan tetap sesuai audit sebelumnya, termasuk seeder, role bootstrapper, resolver login, question bank, policy ujian/attempt/question, API ExamController, Livewire ExamManagement, ProctorAssignmentManagement, User, TenantScoped, IdentifyTenant dan ExamProctorAssignmentService. Ini pemeriksaan statis saja. Tidak ada PHPUnit/runtime/HTTP/MySQL concurrency/browser/UAT dijalankan.

Matriks audit 23 modul dipertahankan; test T-001–T-168 dipertahankan dan T-169–T-173 ditambahkan. Seluruh test yang belum dijalankan tetap NOT RUN.
