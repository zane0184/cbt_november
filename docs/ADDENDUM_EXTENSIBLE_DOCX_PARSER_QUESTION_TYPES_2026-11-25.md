# Addendum — Extensible DOCX Parser and Question Bank Contract
Tanggal: 25 November 2026
Repository: `zane0184/cbt_november`
Baseline: `work/p1-seven-targets-20261124`
Work branch: `work/docx-import-parser-20261125`
PR #23: OPEN / DRAFT / NOT MERGED

## Koreksi cakupan dari Owner

Fixture DOCX utama hanya menjadi fixture penerimaan untuk tipe yang benar-benar ada di dalamnya:
- PG
- PG Kompleks
- Benar/Salah

Daftar tipe berikut adalah cakupan extensibility sistem Bank Soal, bukan klaim bahwa seluruh tipe sudah aktif:
1. PG
2. PG Kompleks
3. Benar/Salah
4. Menjodohkan
5. Isian Singkat
6. Essay/Uraian
7. Melengkapi kalimat
8. Mengurutkan
9. Soal berbasis gambar, tabel, grafik, dan stimulus bersama

Setiap tipe wajib melewati acceptance kontrak data, persistence, validasi, runner, answer payload, scoring, dan immutable snapshot. Parser capability tidak otomatis berarti CBT runtime capability.

## AUDIT — source contract saat ini

| Tipe / kemampuan | Source state saat audit | Status penggunaan |
|---|---|---|
| PG / MULTIPLE_CHOICE | payload satu option ID; radio runner; exact option scoring; snapshot option | Existing path; regression test wajib |
| PG Kompleks / MULTIPLE_RESPONSE | payload selected_option_ids; checkbox runner; multi-response scoring; snapshot option | Existing path; regression test wajib |
| Benar/Salah sederhana / TRUE_FALSE | satu selected_option_id; radio runner; multiple-choice scoring | Existing path hanya untuk satu pernyataan |
| B/S kelompok tiga pernyataan | tidak ada grouped response schema, runner, atau scoring | BLOCKED |
| Menjodohkan / MATCHING | matching payload validation dan scoring ada; snapshot menyimpan option/matching keys | Existing path; harus diverifikasi E2E sebelum import diaktifkan |
| Isian Singkat / SHORT_ANSWER | controller menolak; snapshot service menolak; accepted-answer persistence/snapshot/scoring belum lengkap | BLOCKED — pertahankan keputusan Owner |
| Essay/Uraian / ESSAY | text payload dan manual review status tersedia; runner textarea tersedia | Manual grading path; perlu regression E2E sebelum importer mengaktifkan |
| Melengkapi kalimat | tidak ada kontrak khusus yang terverifikasi | NOT IMPLEMENTED |
| Mengurutkan | tidak ada contract/runner/scoring khusus yang terverifikasi | NOT IMPLEMENTED |
| Gambar/tabel/grafik | media snapshot dasar tersedia; struktur tabel/grafik tidak dibuktikan sebagai data semantik | PARTIAL / belum acceptance |
| Stimulus bersama | belum ada first-class group relation atau immutable group snapshot | BLOCKED |

Catatan:
- PGK tetap memakai kontrak existing MULTIPLE_RESPONSE selama test memastikan seluruh opsi dan scoring benar.
- B/S dalam fixture harus dibaca sesuai struktur fixture; jangan dipaksakan ke TRUE_FALSE single-select bila terdapat tiga pernyataan per nomor.
- SHORT_ANSWER tetap BLOCKED sampai accepted answers, persistence, immutable snapshot, dan scoring E2E terbukti.
- ESSAY tetap manual review; jangan dibuat auto-scored dari kunci hasil parser.
- Tidak ada source lama yang dihapus atau diubah.

## DESAIN — extensible type registry

Pisahkan tiga kemampuan yang tidak boleh disamakan:

1. **Parser adapter**: mengenali struktur DOCX dan menghasilkan normalized import AST.
2. **Question type contract**: mendefinisikan schema data, validasi, response schema, scoring strategy, render contract, snapshot contract.
3. **Runtime capability registry**: menyatakan apakah suatu type sudah diaktifkan untuk save, runner, answer persistence, scoring, dan snapshot.

Setiap type memiliki deklarasi eksplisit:
- stable type code dan schema version;
- canonical question content schema;
- response payload schema;
- validation rules;
- renderer identifier;
- scoring strategy identifier (AUTO_EXACT, AUTO_PARTIAL, MANUAL_REVIEW, atau BLOCKED);
- snapshot serializer/version;
- capability flags: parse, preview, draft-save, publish, runner, answer-persistence, scoring, snapshot;
- compatibility aliases untuk type code lama.

Default harus fail-closed: type yang belum memiliki seluruh kontrak runtime tidak dapat dipublish ke ujian. Preview boleh menampilkan hasil parse beserta status UNSUPPORTED/BLOCKED, tetapi tidak boleh mengaktifkan type tersebut.

Jangan menggunakan satu switch besar yang menyamakan parser dan runtime. Tambahkan contract tests per type dan test registry untuk memastikan tidak ada type yang dapat dipublish hanya karena parser mengenalinya.

## DESAIN — normalized import AST

Parser DOCX menghasilkan struktur netral format, tidak langsung membuat Question/QuestionVersion:
- document metadata dan subject identification;
- question locator (part, paragraph/table/cell, sequence);
- subject, source question number, item type;
- stem content blocks (text, image reference, table, chart/data, formula jika dikenali);
- options/statements/items;
- answer key reference dan provenance (table/sheet/section/row/number as applicable);
- stimulus group reference, exact stimulus content, and sequence;
- kisi-kisi/curriculum metadata;
- warnings/errors, confidence, and parse status.

Preserve original order and raw extracted text. Unknown OOXML constructs become explicit warnings and are never silently discarded. Parser output is preview-only until user confirms mapping and every required key is verified.

## DESAIN — type contracts

| Type | Canonical content | Response | Scoring | Gate |
|---|---|---|---|---|
| PG | options with one correct option | selected_option_id | exact one-correct | Existing runtime |
| PG Kompleks | options with one or more correct options | selected_option_ids[] | explicit partial-credit policy | Existing runtime; test |
| B/S simple | one proposition and true/false choices | selected_option_id | exact | Existing single-statement only |
| B/S grouped | stable statement IDs and expected booleans | statement_id => boolean | per-statement proportional, only after Owner approval | BLOCKED |
| Menjodohkan | left/right item sets and canonical pair IDs | matches[] | exact or defined partial policy | Existing runtime; test |
| Isian Singkat | accepted answer set, normalization policy, locale rules | text | deterministic accepted-answer match | BLOCKED |
| Essay/Uraian | prompt and rubric/criteria | text | MANUAL_REVIEW | Manual grading only |
| Melengkapi kalimat | blank IDs, accepted values or selectable choices | blank_id => value/option ID | per-blank defined policy | NOT IMPLEMENTED |
| Mengurutkan | stable item IDs and canonical order | ordered item IDs | exact or partial rank policy | NOT IMPLEMENTED |
| Image/table/chart | typed content blocks and media/data references | inherited from question type | inherited from question type | PARTIAL; content renderer contract needed |
| Shared stimulus | immutable group ID, content blocks, ordering and version | independent per question | inherited from question type | BLOCKED until group snapshot/runner support |

No type may be activated until the entire row contract has tests from import preview through runner and scoring.

## DESAIN — shared stimulus

Use a first-class stimulus group/version concept rather than duplicating text into each question stem:
- group has stable ID, version, content blocks, media references, ordering, and provenance;
- each question version references one stimulus group version when applicable;
- exam snapshot copies immutable stimulus content once (or a canonical immutable reference plus verified content hash) and each snapshot question references that exact group snapshot;
- runner renders the group stimulus for all related questions while keeping independent answer state per question;
- snapshot tests verify 1–5 stay attached to group A and 6–9 to group B, without relying on sequence adjacency alone.

## IMPLEMENTASI — current branch state

- Isolated work branch exists.
- Four source backups (QuestionImportService, QuestionBank, routes/web.php, QuestionImportTest) were fetched from baseline and Git blob SHA verified equal.
- Backup manifest: `backend-laravel/backup_before_patch/DOCX_IMPORT_20261125/BACKUP_MANIFEST.md`.
- No application source file has been patched.
- This addendum records the Owner's scope correction and the type capability design.
- No migration, production DB, deployment, merge, or PR status change.

## PENGUJIAN — acceptance matrix required

For every type, report separately:
1. Parsed correctly from its fixture.
2. Previewed correctly, including content order and source locator.
3. Saved to Question/QuestionVersion draft.
4. Curriculum mapping persisted and validated.
5. Rendered correctly in student runner.
6. Answer payload accepted, validated, persisted, and recovered.
7. Scored correctly (or correctly marked MANUAL_REVIEW/BLOCKED).
8. Included in immutable snapshot, including media/stimulus references.
9. Publish gate rejects unsupported or incomplete types.
10. Legacy question and exam regression tests pass.

Fixture plan:
- Primary owner DOCX fixture: only PG, PGK, and B/S that are actually present.
- Separate synthetic fixture for MATCHING.
- Separate synthetic fixture for SHORT_ANSWER (negative/blocking behavior only; must not activate).
- Separate synthetic fixture for ESSAY (manual review).
- Separate synthetic fixture for sentence completion.
- Separate synthetic fixture for ordering.
- Separate synthetic fixtures for image, table, chart, and shared stimulus.
- Synthetic fixtures are test data, not claims of production readiness.

## NEXT STEPS

1. Build parser adapter and normalized AST for DOCX, starting only with primary fixture item types.
2. Add parser registry and question type capability registry; preserve legacy aliases and default fail-closed.
3. Add preview-only UI and validation diagnostics before persistence.
4. Integrate PG/PGK existing contracts with explicit regression tests; B/S only after its actual fixture semantics are represented without data loss.
5. Design and implement first-class shared stimulus persistence and immutable snapshot contract before claiming grouped question acceptance.
6. Add separate synthetic fixtures for future types; mark unsupported types clearly.
7. Run disposable MySQL 8/InnoDB PHPUnit and actual browser-to-Laravel runner tests. No production migration/deployment without approval.
8. Keep PR #23 Draft.
