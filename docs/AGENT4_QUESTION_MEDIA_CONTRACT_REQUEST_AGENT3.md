# Agent 4 — Permintaan Kontrak QuestionVersion, Snapshot dan Media kepada Agent 3

Tanggal: 22 November 2026  
Repository: `zane0184/cbt_november`  
Branch dokumentasi: `work/agent4-web-siswa-audit-20261122`  
HEAD dokumentasi sebelum dokumen ini: `c4a02a93bb143da7c83df1894f92b2ffa75c8bc6`  
Owner: Agent 3 untuk QuestionBank/Guru/Exam Builder dan authoring contract; Agent 2 untuk backend admission/media authorization/scoring. Agent 4 hanya consumer Web Siswa.

## 1. AUDIT — source yang diperiksa

Branch Agent 3: `work/web-guru-foundation-20261122`, HEAD remote `bb22fbd1ea3181fdec898109a5d9c5110f2a0890`.

| File | Blob SHA | Temuan |
|---|---|---|
| `backend-laravel/app/Models/QuestionVersion.php` | `5f937abf691f96ee62696f7ef01b5adb2d6f0d9a` | Identitas major/minor, type, stem, explanation, difficulty, cognitive level, estimated time, points, element; relasi options/media |
| `backend-laravel/app/Services/ExamSnapshotService.php` | `d0bfcd70b2777a3884c7f92b7c256f05492645ec` | Membuat snapshot dari version, options dan media; metadata yang terlihat menyalin question type/cognitive fields |
| `backend-laravel/app/Http/Controllers/Api/V1/QuestionController.php` | `7ded53423dd4d96788123819e8039fd543ce4fba` | Authoring type, stem, options, CP/TP-related IDs dan version update |

Branch Agent 2 memiliki blob yang sama untuk QuestionVersion dan ExamSnapshotService pada pembacaan saat ini. Kesamaan blob bukan pengganti approval ownership.

Source snapshot yang diperiksa mengisi snapshot stem, explanation, options, media dan metadata. Pada service yang dibaca, metadata mencakup difficulty/cognitive_level/question_type; accepted answers tidak terbukti ikut disalin. Source ScoringService yang dibaca pada Agent 3 menggunakan `snapshot.metadata.accepted_answers` saat evaluate SHORT_ANSWER. Karena itu SHORT_ANSWER tetap **BLOCKED** sampai data tersebut tersimpan pada snapshot dan test scoring membuktikannya.

## 2. Scope dan enam tipe soal

Web Siswa hanya merender enam tipe yang telah ditetapkan:
- `MULTIPLE_CHOICE`
- `MULTIPLE_RESPONSE`
- `TRUE_FALSE`
- `MATCHING`
- `SHORT_ANSWER`
- `ESSAY`

Tidak ada tipe baru dan tidak ada opsi E. ESSAY tetap manual review. SHORT_ANSWER tetap BLOCKED sampai accepted answers masuk immutable snapshot dan digunakan scoring backend.

## 3. Kontrak yang diminta untuk QuestionVersion

Mohon Agent 3 menetapkan:
1. Primary identity QuestionVersion yang immutable dan tenant-scoped.
2. Hubungan Question → QuestionVersion, major/minor, status dan publication lifecycle.
3. Apakah update membuat version baru atau mengubah draft; kapan version tidak boleh berubah.
4. Hubungan versi dengan curriculum/subject/CP/TP/ATP dan versioned curriculum.
5. Struktur stem, explanation, points, difficulty, cognitive level, estimated time dan metadata.
6. Struktur options untuk setiap enam tipe, termasuk identifier stabil, urutan, matching pair, true/false dan multi-response.
7. Bentuk media reference dan role (image/audio/video/document atau role lain yang sudah didukung).
8. Sanitasi konten, batas ukuran, MIME, alt text/caption/transcript dan fallback.
9. Arabic/RTL, harakat, mixed Arabic-Indonesian, Unicode normalization dan bidirectional isolation.
10. Formula/equation representation dan renderer yang dapat dipakai konsisten pada browser.
11. Accessibility dan mobile rendering requirements.
12. Versi schema payload yang menjamin kompatibilitas consumer.

Agent 4 tidak mengusulkan field DTO atau format baru sebelum Agent 3 mengonfirmasi source of truth.

## 4. Kontrak ExamSnapshot

Mohon Agent 3 bersama Agent 2 menetapkan:
- snapshot ID yang immutable;
- source QuestionVersion ID dan version number;
- tenant/install dan Exam ownership;
- snapshot sequence dan question order;
- salinan stem/options/media/points/type/formatting;
- curriculum mapping yang perlu tampil sebagai metadata belajar tanpa membocorkan internal;
- snapshot schema version;
- aturan shuffle question/options dan pemetaan identifier agar jawaban tetap benar;
- integritas snapshot setelah publish/Attempt pertama;
- aturan perubahan snapshot setelah Attempt/Answer/Score history ada.

Question order harus immutable per Attempt. Snapshot yang tidak ada dalam order tidak boleh dikirim atau menjadi sumber media/answer.

## 5. Media contract

Mohon Agent 3 menetapkan authoring contract:
- MediaAsset identity dan tenant ownership;
- QuestionVersion-media pivot, role dan sequence;
- media reference yang disalin ke snapshot;
- media metadata yang boleh dikirim ke browser;
- perubahan draft attach/detach dan larangan mengubah snapshot historis;
- lifecycle file orphan/retention;
- alt text, caption, transcript dan fallback;
- media type dan size limits yang didukung.

Agent 2 tetap menetapkan authorization pada runtime:
- media harus milik instalasi/tenant yang benar;
- media tertaut ke source QuestionVersion dan snapshot Attempt;
- snapshot ada pada question_order Attempt;
- Attempt pemilik user yang meminta;
- Attempt status sah;
- device/lease/fencing valid;
- response file tidak membocorkan storage path atau file lain.

## 6. Accepted answers, rubric dan scoring

Mohon Agent 3 dan Agent 2 menetapkan batas data:
- accepted answers hanya tersedia di backend scoring dan tidak pernah diserialisasi ke siswa;
- rubric/answer key/scoring guide tidak masuk payload browser sebelum publikasi hasil yang sah;
- SHORT_ANSWER accepted answers harus ikut snapshot immutable atau ada referensi versioned immutable yang terbukti;
- normalisasi jawaban (case, whitespace, punctuation, harakat/Unicode) harus ditetapkan dan diuji;
- ESSAY hanya menyimpan jawaban siswa; rubric dan grading hanya untuk role yang sah;
- MULTIPLE_CHOICE/MULTIPLE_RESPONSE/TRUE_FALSE/MATCHING tidak mengekspos correctness flags/weight/answer key;
- perubahan bank soal setelah publish tidak mengubah snapshot ujian yang sudah diterbitkan.

## 7. Test matrix

| ID | Skenario | Acceptance |
|---|---|---|
| QV-01 | QuestionVersion dari tenant lain | Tidak dapat dipasang pada Exam tenant ini |
| QV-02 | Version draft/unpublished | Tidak masuk snapshot published kecuali policy menyatakan sah |
| QV-03 | Version berubah setelah snapshot | Snapshot historis tidak berubah |
| QV-04 | Duplicate source question | Ditolak atau sesuai aturan Agent 3; tidak diam-diam menggandakan |
| QV-05 | CP/TP/subject mismatch | Ditolak sebelum snapshot |
| SNAP-01 | Snapshot ID berubah/manipulasi | Ditolak backend |
| SNAP-02 | Question order kosong/duplikat/foreign | Attempt fail closed |
| SNAP-03 | Shuffle | Jawaban tetap terikat option identity yang benar |
| SNAP-04 | Publish lalu source question diedit | Payload snapshot tetap identik |
| MED-01 | Media dari version lain | Tidak dapat dibaca siswa |
| MED-02 | Media detached dari draft setelah snapshot | Snapshot historis tetap punya referensi yang sah |
| MED-03 | Media file missing/corrupt | Error fallback terlihat; tidak mengganti diam-diam |
| TXT-01 | Arab + harakat | Teks dan tanda baca tidak rusak |
| TXT-02 | Arab dan Indonesia campur | Directionality tetap benar |
| TXT-03 | Persamaan | Konsisten dan fallback terbaca |
| TYPE-01 | Enam tipe soal | Render dan payload sesuai kontrak |
| TYPE-02 | SHORT_ANSWER | Tetap blocked sampai accepted-answer snapshot + scoring PASS |
| TYPE-03 | ESSAY | Status manual review; tidak auto-score sebagai jawaban benar |

## 8. Acceptance criteria

- Agent 3 memberikan kontrak tertulis untuk identity, snapshot, order, media, content, RTL dan enam tipe.
- Agent 2 menyetujui runtime authorization dan sanitasi payload siswa.
- QuestionVersion/snapshot/media exact-SHA tests lulus pada disposable database.
- F-01/F-02 security tests lulus sebelum runner mengonsumsi payload.
- Tidak ada accepted answers/rubric/answer key pada browser response.
- SHORT_ANSWER tidak diaktifkan hanya karena form input tersedia.

## 9. Approval dan status

- Agent 3: **PENDING**.
- Agent 2 untuk runtime media/admission/scoring: **PENDING**.
- Koordinator: **PENDING**.
- Agent 4 tidak mengubah source Agent 3/2.
- Tidak ada endpoint, field, enum atau DTO backend baru yang ditetapkan oleh dokumen ini.
- Tidak ada implementasi atau pengujian runtime yang diklaim.
