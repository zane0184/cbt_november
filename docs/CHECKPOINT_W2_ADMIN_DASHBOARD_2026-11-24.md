# Checkpoint W2 — Admin Dashboard Active Year

Repository: `zane0184/cbt_november`  
Branch: `work/solo-web-cbt-completion-20261123-execution`  
Tanggal: 24 November 2026

## Source
- Dashboard component blob: `e3cfaed3d70c336362d254641f453f6a03f1aa8d`
- Dashboard view blob: `017e95463161bb93628a205ef8acc298c20ce93d`
- AdminUiVerificationTest blob: `df9b50407a1169e4a4ab2cda73d85e23fa25ccbc`

## Backup
- Dashboard.php.before: `0198e7ad2fed9bc18669972805dba1bcd6d89c7a` (byte-exact verified)
- dashboard.blade.php.before: `701b943088e4d112110e3e6fbb2661d7ddbb0bc5` (byte-exact verified)
- AdminUiVerificationTest.php.before: `8dc6580e6be22f4d10907ef957db5a399ca7f644` (byte-exact verified)

## Patch
Dashboard metrics now scope Rombel to current active academic year + ACTIVE status; Subjects to ACTIVE; recent Rombel list to active academic year with actual status and year label. Tests cover previous year, inactive Rombel, inactive Subject and missing active academic year.

## Test
W2 regression tests NOT YET VERIFIED. Prior workflow attempts #1671–#1676 canceled due to branch concurrency and commit sequence; #1676 stopped before checkout/test. Do not read cancellation as a test failure or a pass.

## Next
Run the final focused Admin UI test against the settled source SHA. Continue the Admin W2 shell/dashboard acceptance after evidence.
