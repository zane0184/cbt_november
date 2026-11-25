# DOCX Parser Implementation Gate — 25 November 2026

## AUDIT

Repository: `zane0184/cbt_november`
Baseline branch: `work/p1-seven-targets-20261124`
Isolated work branch: `work/docx-import-parser-20261125`
PR #23 remains OPEN / DRAFT / NOT MERGED.

The official owner fixture was already inspected in the conversation and its SHA-256 matched the uploaded file. This checkpoint does not repeat fixture inspection.

### Verified source contracts

1. `QuestionImportService.php` is an interface only.
2. `ExcelQuestionImporter.php` accepts row arrays and supports `MULTIPLE_CHOICE`, `MULTIPLE_RESPONSE`, and `TRUE_FALSE`. It is not a DOCX parser and will not be revived as an XLSX question-upload path.
3. `QuestionVersion` persists `question_type`, `stem`, `explanation`, difficulty, points, and options/media relations. It has no dedicated shared-stimulus/group relation or general metadata field.
4. `ExamQuestionSnapshot` stores stem, options, media, metadata and curriculum context. Its metadata can preserve question type but there is no explicit shared-stimulus/group model.
5. `ExamAnswerController::validatePayload()` accepts:
   - `MULTIPLE_CHOICE` / `TRUE_FALSE`: one `selected_option_id`;
   - `MULTIPLE_RESPONSE`: `selected_option_ids[]`;
   - no grouped three-statement B/S payload type.
6. Student runner `resources/views/student/exams/show.blade.php` renders `TRUE_FALSE` as radio choices for one question and `MULTIPLE_RESPONSE` as checkboxes. It renders only `snapshot_stem` and snapshot media; no shared stimulus group is rendered.
7. `ScoringService` supports `TRUE_FALSE` as a single multiple-choice evaluation and `MULTIPLE_RESPONSE` through multi-response scoring. It has no three-statement grouped B/S evaluator.
8. `ExamSnapshotService` copies question type, options, media, and curriculum context into immutable snapshot. It currently has no explicit stimulus-group snapshot contract.

### Compatibility blockers

- **B/S three statements:** current engine semantics are one radio-selected option per question. Modeling three independent statements as one `TRUE_FALSE` would lose the three answers; modeling them as three separate questions would violate owner requirements. A dedicated grouped type/answer payload/scoring contract is required.
- **Shared reading stimulus:** no first-class stimulus-group field/relation exists in QuestionVersion and no corresponding runner/snapshot contract. Repeating the reading passage in each stem is not an acceptable substitute for preserving the shared relationship.
- **Fixture binary in CI:** the uploaded fixture is available in this conversation runtime but has not yet been added as a binary test fixture to the repository. Therefore exact fixture-driven PHPUnit/CI cannot be claimed yet.

## BACKUP / SOURCE PROTECTION

Pre-patch backups are in:
`backend-laravel/backup_before_patch/DOCX_IMPORT_20261125/`

Verified Git blob SHA equality (source blob = backup blob):
- `QuestionImportService.php`: `1bdbcec7305b9c4abdfc010492cc7382b42ba352`
- `QuestionBank.php`: `c827e2fafdbec4696f7fbaa6f2cbbce2e3299ee3`
- `web.php`: `1fca336b285415935558a3ad913972297093c4a2`
- `QuestionImportTest.php`: `8f6faee74c1076dd38f3a7ce4b1f19f046febbb1`

Manifest: `backend-laravel/backup_before_patch/DOCX_IMPORT_20261125/BACKUP_MANIFEST.md`

No existing source file has been modified. No existing source was deleted or overwritten.

## DESIGN REQUIRED BEFORE ENGINE PATCH

Recommended non-lossy contract for owner review:
- Keep PGK mapped to existing `MULTIPLE_RESPONSE` and its `selected_option_ids[]` payload/scoring.
- Add a distinct grouped B/S question type (proposed name `TRUE_FALSE_GROUP`) with exactly three statement items, each with its own stable ID/text and expected truth value.
- Answer payload should be a map/list of statement IDs to boolean values; validation must require exactly the supported statement IDs and boolean values.
- Scoring must compare all three statement results using an explicit policy (all-or-nothing or per-statement proportional points). Do not infer this policy from the answer key.
- Add a first-class stimulus group model/relationship or an equally explicit immutable snapshot contract. The same stimulus group ID must be referenced by questions 1–5 and 6–9 respectively. Snapshot must include immutable stimulus content and ordering.
- Parser output must retain source locator (document part/block/table/row), question number, subject, item type, options/statements, key provenance, kisi-kisi metadata, stimulus group, warnings and parse status.
- Save only complete/valid questions as DRAFT; incomplete/unsupported items remain in preview with actionable errors and are never silently skipped.
- No production migration. Any schema migration is forward-only candidate code and must first pass disposable MySQL 8/InnoDB plus SQLite contract tests if SQLite is in the supported suite.

## IMPLEMENTASI

- Created isolated branch `work/docx-import-parser-20261125` from the active P1 source branch.
- Created and re-fetched four pre-patch backups; all four Git blob SHAs match exactly.
- No parser source code or engine source code has been changed yet.
- A temporary branch write probe exists at `docs/DOCX_IMPORT_BRANCH_PROBE_20261125.md`; it is documentation-only and not application source.

## PENGUJIAN

- Source inspection only for engine contracts.
- No PHP lint, PHPUnit, fixture parser run, runner browser test, scoring test, snapshot test, or CI run has been executed for this work branch.
- No production database, migration, deployment, or VPS action occurred.

## CHECKPOINT / NEXT ACTION

Implementation is paused at a genuine compatibility gate, not because the DOCX example is missing. Before enabling save-to-draft and end-to-end acceptance, the owner must approve the data contract for:
1. dedicated grouped three-statement B/S type and its scoring policy;
2. first-class shared stimulus representation carried through QuestionVersion, runner and immutable snapshot.

Once approved, implement parser and the engine extension in small commits, each with pre-patch backups and disposable-database tests. Keep PR #23 Draft; do not merge, deploy, or migrate production.
