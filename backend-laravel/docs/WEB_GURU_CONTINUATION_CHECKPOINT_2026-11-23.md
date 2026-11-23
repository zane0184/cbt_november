# CHECKPOINT LANJUTAN PORTAL WEB GURU — 23 NOVEMBER 2026

## 1. Posisi source
- Repository: `zane0184/cbt_november`
- Branch kerja: `work/web-guru-remove-xlsx-20261123`
- Current HEAD: `050226ad6ef1bf47e4510126f1f9736a07b542d3`
- Baseline source: `work/web-guru-phase1-audit-implementation-20261122`
- Baseline SHA: `5962646bdcf5a2702ef162c43373815483906e5c`
- PR #19: https://github.com/zane0184/cbt_november/pull/19
- PR state: OPEN, DRAFT, UNMERGED.
- Compare current HEAD against last tested source SHA `051c5d5ee1074d1e6dd3004e552658e83a2f2be7`: only 3 documentation/manifest files added; no source/test/workflow changed after test.

## 2. AUDIT
- Portal Guru has tested foundation, assignments, Question Bank, media draft, Exam Builder, result workflows, analytics and monitoring API/page entry.
- Feature gaps remain: official/versioned curriculum data, material/topic relation, curriculum provenance to question/snapshot, DOCX import implementation, full RTL/harakat/math verification, complete Guru settings, browser E2E/visual QA, concurrency/load/UAT.
- Monitoring page is linked from each authorized exam card, not a standalone sidebar route. Sidebar placeholder indicates monitoring is per exam.
- Role-boundary issue reported to Agent 2 in Issue #8: `GET /exams/{exam}/monitoring` does not use `EnsureTeacherPortalAccess`; `ExamPolicy::monitor` checks role membership, not exactly-one persisted role. GURU+KAMAD with `exam.monitor` may enter via KAMAD branch. Code-level inconsistency; no exploit claim. Agent 2 owns policy/shared service.
- Agent 2 DOCX contract approval still pending on Issue #9.

## 3. DESAIN
- Owner decision: DOCX is the main question import format; XLSX/template/parser/wizard are not product target.
- Draft Web Guru UI flow prepared:
  `backend-laravel/docs/WEB_GURU_DOCX_IMPORT_UI_DESIGN_DRAFT_2026-11-23.md`.
- UI flow: private staging → extraction preview/source locator → correction using authoring fields → server-side revalidation → confirmation through existing ImportBatch/ImportBatchRow → DRAFT persistence/media attach → result summary.
- This is draft only. DTO fields, source locator, warning/error schema, security limits, lifecycle/idempotency/media contract remain PENDING Agent 2 approval.
- No DOCX parser/dependency, migration, batch subsystem, or UI implementation added.
- SHORT_ANSWER remains BLOCKED; ESSAY remains manual review.

## 4. IMPLEMENTASI
### XLSX removal
- Deleted XLSX-only template export/controller, workbook parser, Livewire wizard/view, teacher routes, UI links, and tests.
- Preserved `ExcelQuestionImporter` because API `QuestionController::import()` uses row-based JSON `rows[]`; preserved `QuestionImportService`.
- Preserved `maatwebsite/excel`/PhpSpreadsheet because other Admin import/export features depend on them.
- Generic import rollback test moved to `QuestionImportTest`.
### Result/Exam/Monitoring coverage
- Added Livewire ResultDetail ESSAY manual grading + finalization test.
- Added `ResultWorkflowTest`, `ExamBuilderTest`, `MonitoringTest`, and `MonitoringDuplicationTest` to CI filter.
- No production schema, migration, shared policy/service, or DB changes.
### Backups
- Total **18/18 file backups SHA MATCH** byte-exact.
- Manifests in `.github/backups/web-guru-remove-xlsx-20261123/`:
  - `MANIFEST.md`
  - `MANIFEST_TEST_FIXES.md`
  - `MANIFEST_RESULTS_TESTS.md`
  - `MANIFEST_EXAM_BUILDER_TESTS.md`
  - `MANIFEST_MONITORING_TESTS.md`

## 5. PENGUJIAN
Latest GitHub Actions run:
- URL: https://github.com/zane0184/cbt_november/actions/runs/45108770776
- Status: SUCCESS
- Checkout SHA: `051c5d5ee1074d1e6dd3004e552658e83a2f2be7`
- PHPUnit: **117 passed, 514 assertions**, 16.20 sec.
- PHP lint and route registration: PASS.
- Composer validate/locked install/platform requirements: PASS.
- Disposable MySQL 8: schema verified; 80 base tables, all InnoDB.
- Disposable DB/user cleaned by workflow.
- Current branch HEAD after test adds only manifests/docs; compare confirms no source/test/workflow code changed after tested SHA.

## 6. PENDING / NEXT ACTIONS
1. Agent 2: resolve exact-role invariant for Live Monitoring route/policy and add multi-role regression tests.
2. Agent 2: approve/reject/needs-design DOCX DTO, source locator, parser profile, warnings, ZIP/XML security, resource limits, retention, idempotency, retry, audit and media staging.
3. Agent Web Guru: after approval, implement DOCX upload/preview/correction/confirm/result UI against the exact shared contract.
4. Continue curriculum UI integration after Agent 2 finalizes academic year/material/topic/curriculum mapping and snapshot provenance contracts.
5. Add browser E2E/visual QA for desktop/mobile and additional direct URL/Livewire action tests.
6. Keep PR #19 DRAFT. No merge to main, deployment, or production DB migration without owner approval.

## 7. STATUS
- XLSX question import removal: **IMPLEMENTED + CI PASS**.
- Result/Exam Builder/Monitoring selected regression suite: **CI PASS 117/514**.
- DOCX import: **NOT IMPLEMENTED — awaiting Agent 2 approval**.
- Full Portal Web Guru: **NOT COMPLETE / NOT PRODUCTION READY**.
