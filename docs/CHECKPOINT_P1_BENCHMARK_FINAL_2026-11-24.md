# Checkpoint P1 — Benchmark Final Verification

Tanggal: 24 November 2026  
Branch source: `work/p1-seven-targets-20261124`  
Exact source SHA tested: `dbe99de52dd22f8a2a0852908feeca4200e02278`  
GitHub Actions: https://github.com/zane0184/cbt_november/actions/runs/45382796481  
Conclusion: **SUCCESS**

## AUDIT
- PHP lint PASS.
- Focused login/tenant smoke: 57 passed / 242 assertions.
- Full PHPUnit: 549 passed / 0 failed / 2.353 assertions.
- MySQL 8.0.46 / InnoDB; PHP 8.3.37; Laravel 13.42.0.

## DESAIN & AUTHORIZATION
- Success benchmark calls real `ReportController::exportPdf()` and its `authorize('viewExamResults', $exam)`.
- Guru actor has exact GURU role, official report.view grant, active TeacherSubjectAssignment and matching pinned curriculum registration/context.
- No Policy bypass or permission relaxation.
- Failure regression deliberately omits grant in disposable fixture; real ReportPolicy returns false and export is denied.

## IMPLEMENTASI
- PDF response is persisted to temporary .pdf and validated by HTTP 200, %PDF- signature, full write and filesize.
- Warm-up PDF: 19,693 bytes.
- Measured PDF: 19,693 bytes.
- Report rombel filter closure fix and valid grade-level fixture.
- Database cleanup in finally; post-DROP INFORMATION_SCHEMA absence verification.
- Failure-path test confirms temporary schema exists at Policy denial and is absent after failure.

## PENGUJIAN
| Gate | Result |
|---|---|
| PHP lint | PASS |
| Focused login/tenant | 57 PASS |
| Full PHPUnit | 549 PASS / 0 FAIL |
| Report rombel filter | PASS |
| Failure cleanup via real Policy denial | PASS |
| PDF warm-up | HTTP 200 / 19,693 bytes / valid signature |
| PDF measured run | HTTP 200 / 19,693 bytes / valid signature |
| Batch calculation | 1,732.86 ms / 50.50 MB |
| PDF export | 468.05 ms / 72.50 MB |
| Success schema cleanup | PASS; INFORMATION_SCHEMA absence verified |
| Overall benchmark | PASSED |
| CI exact source SHA | SUCCESS |

One measured run per operation. CV 0.00% is not a repeatability/stability claim. Multi-run load/stability testing remains open.

## STATUS
P1-ID-03A benchmark gate: **CLOSED / PASS**.  
PR #23: OPEN / DRAFT / NOT MERGED.  
Production DB/VPS: untouched.  
Production readiness: NOT CLAIMED.

## NEXT
Continue main P1 backlog:
1. Web Admin account lifecycle + identity DB invariants.
2. Web Siswa login/resume/admission/session-device alignment.
3. IndexedDB recovery, multi-tab, offline sync.
4. Device Lease/Fencing integration and multi-device testing.
5. Web Guru publishing and full role/tenant E2E.
