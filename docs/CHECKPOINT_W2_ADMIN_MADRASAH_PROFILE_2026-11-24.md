# Checkpoint W2 — Admin Madrasah Profile

Tanggal: 24 November 2026
Branch: `work/solo-web-cbt-completion-20261123-execution`

## Source state
- New component: `backend-laravel/app/Livewire/Admin/MadrasahProfile.php`
- New view: `backend-laravel/resources/views/livewire/admin/madrasah-profile.blade.php`
- Updated routes: `backend-laravel/routes/web.php`
- Updated sidebar: `backend-laravel/resources/views/layouts/app.blade.php`
- Updated tests: `backend-laravel/tests/Feature/AdminUiVerificationTest.php`
- Updated CI workflow: `.github/workflows/tenant-backend-ci.yml`

## AUDIT / DESIGN
Basic profile form covers existing tenants columns only: name, NSM, NPSN, address, phone, email. It requires exact Admin Madrasah role + tenant.manage, derives tenant only from authenticated user's tenant_id, rejects System Tenant, locks the selected row, keeps code/status immutable, and writes old/new values to audit log. No migration or database write was executed.

## BACKUP
- Existing layout, route and test backups are byte-exact; details in `REPORT_W2_ADMIN_MADRASAH_PROFILE_2026-11-24.md`.
- The component namespace fix and CI workflow lifecycle correction each have byte-exact pre-change backups.
- No backup is required for newly created view/component files before first creation.

## TEST STATUS
NOT VERIFIED.
- Run #1703/#1706 failed before app tests due CI table-engine probe running before schema creation; workflow reordered identity preflight and InnoDB postflight.
- Run #1702/#1705/#1707/#1708/#1709 were cancelled before checkout/test by workflow concurrency.
- No profile focused tests/full suite have run to completion. Do not call feature production-ready.

## Remaining gaps
- Full profile spec (grade/status negeri-swasta, detailed geographical fields, website, headmaster document metadata, extra details) is not represented in current schema.
- Logo upload/replace/delete, favicon, login branding and private asset delivery not implemented.
- Flexible letterhead/preview and application branding controls not implemented.
- These are not silently considered complete.

## Next
One stable GitHub Actions run on final HEAD. If green, continue missing profile fields/design and secure logo storage; if red, fix only demonstrated test/runtime causes.
