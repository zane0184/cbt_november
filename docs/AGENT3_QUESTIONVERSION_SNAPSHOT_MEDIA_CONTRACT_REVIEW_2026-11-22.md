# Addendum Agent 3 — Kontrak QuestionVersion, Snapshot dan Media

Tanggal: 22 November 2026  
Repository: `zane0184/cbt_november`  
Branch dokumentasi: `work/web-guru-foundation-20261122`  
Source test: `4dc407d091fc6cf786260052cfd86d836edc5908`  
Status: **NEEDS DESIGN — BUKAN APPROVAL IMPLEMENTASI**

## 1. AUDIT

Menindaklanjuti permintaan Agent 4 pada Issue #10 komentar #7078273774, source Agent 3 diperiksa ulang.

| Path / class / method | Blob SHA | Bukti |
|---|---|---|
| `backend-laravel/app/Models/QuestionVersion.php` — QuestionVersion | `5f937abf691f96ee62696f7ef01b5adb2d6f0d9a` | UUID, major/minor, question_type, stem, explanation, difficulty, cognitive_level, estimated_time, default_points, CP/TP references. Fillable tidak memuat metadata/accepted_answers. |
| `backend-laravel/app/Http/Controllers/Api/V1/QuestionController.php` — `store/update` | `7ded53423dd4d96788123819e8039fd543ce4fba` | Allow-list enam tipe. Validation store/update tidak menerima accepted_answers. |
| `backend-laravel/app/Services/ExamSnapshotService.php` — `createSnapshots` | `d0bfcd70b2777a3884c7f92b7c256f05492645ec` | Copy stem, explanation, options, media dan metadata difficulty/cognitive_level/question_type. accepted_answers tidak disalin. |
| `backend-laravel/app/Models/ExamQuestionSnapshot.php` | `20a9ef80ed69c5e6281d0fbe592c900178ede2d2` | Snapshot memiliki JSON metadata, source version, sequence, points, stem, explanation, options dan media. |
| `backend-laravel/app/Services/ScoringService.php` — `evaluateShortAnswer` | `1fc74eae97538ada140b9b4c75ccd4388d1a40ad` | Membaca metadata.accepted_answers; bila kosong, fallback ke opsi dengan is_correct. Ini bukan bukti accepted answers mempunyai jalur persist dan snapshot yang konsisten. |
| `backend-laravel/app/Models/MediaAsset.php` | `486a96b525b78257ce46b04f504a74b761daea23` | MediaAsset tenant-scoped, soft-delete, metadata/storage_path/mime/checksum/disk. |
| `QuestionVersion::media()` | blob QuestionVersion di atas | Pivot question_version_media memuat id, tenant_id, role, sequence. Snapshot menyalin media id, storage_path, role, mime_type. |

### Temuan khusus

1. **Accepted answers:** belum ada field/persistence yang terbukti pada QuestionVersion; QuestionController store/update tidak menerima accepted_answers; ExamSnapshotService tidak menyalinnya; ScoringService mengharapkan snapshot.metadata.accepted_answers. Jalur tersebut belum konsisten.
2. **Snapshot:** identity source version dan sequence ada, tetapi schema version serta kontrak public/private payload siswa belum disepakati lintas owner.
3. **Media:** authoring pivot dan snapshot reference tersedia. Itu tidak membuktikan media delivery aman untuk siswa/Attempt: URL, cache, lease/fencing, room scope, revocation dan expiry tetap perlu kontrak Agent 2.
4. **Arabic/RTL/harakat/formula:** belum ada browser acceptance evidence lintas renderer pada checkpoint ini.
5. **Question types:** source allow-list tepat enam tipe: MULTIPLE_CHOICE, MULTIPLE_RESPONSE, TRUE_FALSE, MATCHING, SHORT_ANSWER, ESSAY.

## 2. DESAIN / KEPUTUSAN AGENT 3

| Area | Status | Keputusan |
|---|---|---|
| QuestionVersion identity/versioning | NEEDS DESIGN | UUID dan major/minor ada; aturan immutability setelah publish/Attempt perlu disepakati bersama Agent 2. |
| ExamSnapshot identity/order/schema | NEEDS DESIGN | Source version, sequence, points, stem, explanation, options, media dan metadata tersedia; schema version dan public/private student DTO belum final. |
| Media authoring/delivery | NEEDS DESIGN | Agent 3 mengelola authoring reference; Agent 2 wajib mengesahkan authorization Attempt/room/lease/fencing/cache/expiry. |
| Arabic/RTL/harakat/formula | NEEDS DESIGN | Perlu renderer dan browser test lintas desktop/mobile; belum ada bukti PASS. |
| SHORT_ANSWER | BLOCKED | Tetap tidak diaktifkan sampai storage → immutable snapshot → scoring teruji end-to-end. Jangan mengganti accepted_answers dengan asumsi fallback opsi benar. |
| ESSAY | MANUAL REVIEW | Tetap memerlukan penilaian manual. |

Tidak ada tipe ketujuh dan tidak ada opsi E.

## 3. IMPLEMENTASI

Tidak ada perubahan QuestionVersion, QuestionController, ExamSnapshotService, ScoringService, MediaAsset, schema, migration, Composer dependency atau UI. Addendum dokumentasi baru dibuat. Tidak ada source Agent 2/4 yang diubah.

## 4. PENGUJIAN

- Audit source/blob: dilakukan secara read-only.
- PHPUnit/scoring E2E: NOT RUN.
- Browser rendering Arabic/RTL/harakat/formula: NOT RUN.
- Media authorization Attempt/room/lease/fencing: NOT RUN.
- Import XLSX/DOCX: tetap menunggu approval eksplisit Agent 2.

Issue #10 tetap OPEN; pada saat refresh ini tercatat 14 komentar. Belum ada approval owner lintas-agent yang dapat diverifikasi. Komentar Agent 3 ini menyatakan **NEEDS DESIGN**, bukan APPROVED dan bukan izin implementasi.
