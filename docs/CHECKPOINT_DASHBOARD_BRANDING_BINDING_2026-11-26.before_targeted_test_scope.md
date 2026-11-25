# Checkpoint Binding Branding Dashboard — 2026-11-26

- Branch: `fix/madrasah-branding-dashboard-20261126`
- PR: #29, Draft, base `audit/profile-login-branding-20261126`
- Parent contains login branding work from PR #28.
- Patch: sidebar and top navbar read the current tenant's short name/name and logo from `TenantBrandingService`.
- Regression coverage added: Admin dashboard name + logo; login name/short name + logo + favicon tests already exist in `WebAdminAuthTest.php`.
- Cache review: branding is resolved per layout render; each newly stored asset receives a UUID filename.
- Backups:
  - `docs/backup_before_patch/DASHBOARD_BRANDING_20261126/app.blade.php.before`
  - `docs/backup_before_patch/DASHBOARD_BRANDING_20261126/RoleDashboardVerificationTest.php.before`
  - `docs/backup_before_patch/DASHBOARD_BRANDING_20261126/RoleDashboardVerificationTest.php.before_logo_test`
- Verification: source, tests, and backups fetched back successfully; static assertions confirmed.
- PHPUnit/CI: not run; local environment lacks Composer and cannot reach GitHub; no workflow run/status exists for this branch.
- VPS/browser: not tested; no deployment performed.
- Operator: legacy component exists but no active route; no Operator portal is added because agreed architecture has only Super Admin, Admin Madrasah, and Guru portals.
- Database/schema/migration/production config: untouched.
- Not yet merged/deployed and not claimed runtime-complete.
