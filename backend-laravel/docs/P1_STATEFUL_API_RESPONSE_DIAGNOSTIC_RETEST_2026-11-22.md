# P1 — Diagnostic HTTP 500 response for Admin browser login

Tanggal: 22 November 2026

## AUDIT

Run #382 (`44998949281`, SHA `808a7a917f1addbf8b8b67b4929dd1cbb6992cbd`) repeated:
- QuestionImportTest and WebAdminAuthTest passed.
- API authentication and tenant isolation passed.
- StudentWebLoginTest remained 10 PASS / 1 FAIL.
- Failure: separate Admin browser login returns HTTP 500 instead of 302 after a second browser establishes a stateful Sanctum API session.

The default-guard reset in `IndependentBrowserClient` did not resolve the issue. It is therefore not accepted as the root cause.

## DIAGNOSTIC PATCH

Commit `3fbebcb63b2510cabe31b56289ddd8c2f4b41295` changes the failing assertion to include the Admin login response body in PHPUnit output. No production code was changed by this diagnostic patch.

Backup:
`docs/backup/stage4d-account-lifecycle-2026-11-22/StudentWebLoginTest.php.before-admin-login-response-diagnostic-2026-11-22`.

## NEXT TEST

The next workflow must include commit `3fbebc`. Inspect the returned HTTP 500 body and stack trace before making any additional production-code change.

Potential follow-up after evidence:
- make web authentication explicit with `Auth::guard('web')` in `AuthWebController`;
- use explicit `auth:web` middleware on web-protected route groups;
- only if the response/stack trace supports this change.

No migration or database production operation.
