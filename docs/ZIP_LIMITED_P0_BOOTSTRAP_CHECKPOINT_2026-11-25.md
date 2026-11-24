# Checkpoint — Limited ZIP P0 bootstrap (2026-11-25)

## AUDIT
- Repository: `zane0184/cbt_november`
- Branch: `work/limited-zip-p0-20261125`
- Source base: `aea96fcd3fabba7f82ba66a4b941025e80d9fbc7` (Agent 1 CSV row-limit branch; exact CI run #99 PASS).
- Current implementation head before this checkpoint: `fb265f7f98774a1cfffc06f448d0f20ce7ee279c`.
- Agent 2 P0 audit: `docs/ZIP_LIMITED_P0_AUDIT_2026-11-25.md` on `audit/independent-qa-admin-guru-siswa-20261125`.
- P0-01 confirmed: clean install had no tenant-local academic year, semesters, or GradeLevel provisioning; Admin UI only creates Rombel/subjects and requires those records.
- P0-02 confirmed: source registry metadata explicitly records `artifact_checksum_verified=false` and `cp_content_imported=false`; Bank Soal/Exam Builder require verified CP/TP mappings.
- Agent 1 Import Siswa hardening and CSV row-limit correction are already present in base and CI run #99 passed: 22 import tests / 87 assertions; full suite 621 passed, 1 skipped / 3120 assertions on PHP 8.3 and 8.4 with MySQL 8.0 InnoDB.
- No production database, VPS, deployment, or default branch was accessed or modified.

## DESAIN
- Run the production-safe `DatabaseSeeder` during installer schema bootstrap so the reserved System Tenant, official reference metadata, curriculum registry, and assessment taxonomy exist on clean installs. This seeder does not provide CP content and does not create demo madrasahs/users.
- Provision tenant-local academic prerequisites from the existing KMA-1503-2025 System Tenant phase/grade registry: Kurikulum Merdeka local curriculum identity, Fase A/B/C, MI classes 1–6, current academic year, and Ganjil/Genap semesters.
- Determine the current academic year using Indonesian July–June convention and the configured app timezone; active semester follows current month. Do not overwrite conflicting existing records.
- Keep CP data fail-closed until the official artifact and checksum/provenance are verified.

## IMPLEMENTASI
- Branch created from Agent 1 exact head `aea96fcd3fabba7f82ba66a4b941025e80d9fbc7`.
- Pre-change backups (byte-exact) and SHA-256 manifest:
  - `backend-laravel/backups/limited-zip-p0-20261125/InstallerService.php.before`
  - `backend-laravel/backups/limited-zip-p0-20261125/InstallerEndToEndTest.php.before`
  - `backend-laravel/backups/limited-zip-p0-20261125/docx-import-parser-ci.yml.before`
  - `backend-laravel/backups/limited-zip-p0-20261125/BACKUP_MANIFEST.md`
- Source changes:
  - `backend-laravel/app/Services/InstallerService.php`: seed reference catalogs after successful migrations; provision tenant academic catalog during new tenant/Admin transaction.
  - `backend-laravel/app/Services/TenantAcademicBootstrapper.php`: idempotent, fail-closed provisioning based on existing System Tenant phase/grade registry; no CP/TP content fabricated.
  - `backend-laravel/tests/Feature/InstallerEndToEndTest.php`: asserts academic prerequisites and confirms no CP rows are fabricated.
  - `.github/workflows/docx-import-parser-ci.yml`: adds dedicated disposable MySQL database and opt-in clean-install E2E step for this branch.
- Implementation commit before test assertions: `7380167dd26c8fb7774a253310193537b22c7ebf`.
- E2E assertion commit: `fb265f7f98774a1cfffc06f448d0f20ce7ee279c`.

## PENGUJIAN
- Not yet run on this new head. The workflow is configured to run PHP lint, clean-install E2E on a separate `cbt_installer_test_*` MySQL database, Student Import acceptance, benchmark denial-path, and full PHPUnit.
- Status: **NOT VERIFIED pending exact-head CI**.
- Installer E2E is isolated from the main CI database; no production DB is targeted.

## BLOCKERS / RELEASE GATES
- P0-01 implementation is present but remains NOT VERIFIED until exact-head CI passes.
- P0-02 remains BLOCKED: no official CP content is inserted. Existing source registry must remain `PENDING`/unverified until signed official artifacts, complete subject/phase coverage, provenance, and SHA-256 are verified.
- P0-04/P0-05/P0-06 browser E2E, clean ZIP extraction smoke test, and package artifact are still NOT VERIFIED.
- No ZIP has been created or declared ready.

## NEXT
1. Verify exact-head CI and fix only evidenced failures with a fresh byte-exact backup.
2. Inspect official CP artifacts and provenance for BSKAP/BKPDM 2026 updates and Kepdirjen Pendis 9941/2025; do not import third-party copies as verified without signature/provenance checks.
3. If CP artifact remains unverified, retain BLOCKED status and report the exact required artifact.
4. Run packaging workflow on a final candidate SHA, download and inspect artifact, extract into a clean directory, then run installer + browser E2E.
