# Checkpoint Koordinator — GitHub Recheck 25 November 2026

Repository: `zane0184/cbt_november`
Branch dokumentasi: `audit/coordinator-scope-reconciliation-20261125`
Laporan: `docs/REPORT_KOORDINATOR_GITHUB_RECHECK_2026-11-25.md`

## Ref terkini yang diverifikasi

| Jalur | Branch | HEAD / SHA | Status |
|---|---|---|---|
| Web execution | `work/solo-web-cbt-completion-20261123-execution` | `2a82331fe7aede1d051af1c4e268af9e772123d0` | W4 execution head, CI #1873 SUCCESS |
| Student Import | `work/benchmark-cleanup-proof-20261125` | `a65b4604495e8bcc153bf0e09df12a9efb11543a` | CI #93 SUCCESS; files NOT in execution |
| DOCX design/gate | `work/docx-import-parser-20261125` | `d7a33b73e32d42064a31f4ef29c01266121d29f0` | Design only, parser NOT IMPLEMENTED |
| Independent QA | `audit/independent-qa-admin-guru-siswa-20261125` | audit checkpoint references `fabe1147b3d2e52626529d3e5c9eb37af4fc80de` | P0 limited ZIP audit available |
| Coordinator audit | `audit/coordinator-scope-reconciliation-20261125` | pre-recheck HEAD `9562bfbad61b43ff197d06e39614f9ec073649d2` | This report/checkpoint added on same doc-only branch |

## Posisi
- User decisions: Web only until June 2027; per-installation DB; 3 roles; Proktor temporary Guru capability; no Kamad/Operator; Kurmer+KBC one context.
- Student Import is implemented/hardened in its branch and must not be rebuilt; selective integration into execution remains open.
- Web Admin, Guru, Siswa source exists but end-to-end browser acceptance remains incomplete.
- DOCX parser not implemented; maintain contract and fail-closed gates.
- CP official data/provenance not verified; source-blocker for end-to-end Guru question/exam lifecycle.
- Academic provisioning missing for a fresh local install per independent QA; source-blocker for limited operational ZIP.
- Clean-install E2E, packaging workflow/artifact/extract, and browser E2E remain release gates.
- PR #21/#23 remain Draft; PR #24 DO NOT MERGE.
- No deployment/production migration/PR merge authorized.

## Next task
P0-A: inspect exact academic schema/migrations/relations and existing academic lifecycle decisions, then design/implement the smallest safe initial provisioning path for new madrasah installs (AcademicYear, Semester, GradeLevel MI 1–6), with idempotency, tenant/installation scoping, audit and tests. Before source patch, byte-exact backup each existing source file in target execution branch and record SHA. If migration changes are required, forward-only candidate plus disposable MySQL 8/InnoDB tests; never production DB.

After P0-A, proceed to CP artifact/provenance blocker; then selective Student Import integration, then release gates.

## Safety
- This checkpoint is documentation-only.
- No source patch, migration, database mutation, VPS access/deployment, workflow run, PR merge or ZIP build was performed this turn.
- Do not claim ZIP readiness or production readiness.

Status: **AUDIT DONE — P0 IMPLEMENTATION NEXT — ZIP FINAL NOT READY**.
