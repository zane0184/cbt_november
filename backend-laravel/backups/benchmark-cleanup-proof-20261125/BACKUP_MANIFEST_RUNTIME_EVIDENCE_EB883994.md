# Backup manifest — benchmark runtime evidence assertions

Pre-patch branch HEAD: `eb883994e9f710f7bd3fa5096905d9a6ddb807f8`

| Source file | Backup file | Source Git blob SHA | Backup Git blob SHA | SHA-256 |
|---|---|---|---|---|
| backend-laravel/tests/Feature/Phase7dBenchmarkCommandSafetyTest.php | backend-laravel/backups/benchmark-cleanup-proof-20261125/Phase7dBenchmarkCommandSafetyTest.php.before_evidence_assertions_eb883994 | a71c5bfb2e282303a289a9fa2588f02b531494ee | a71c5bfb2e282303a289a9fa2588f02b531494ee | 926cc3986fafc91b7aa065d8119c44dfd4baa1225af8e23ec356017204610329 |

## Verification
- Backup was re-fetched from GitHub after creation.
- Source and backup Git blob SHA are identical.
- SHA-256 was recalculated using the complete SHA-256 round constants and verified against known test vectors.
- The previous manifest recorded an incorrect SHA-256 value; the byte-identical backup and Git blob SHA were unaffected.
- Only the test file was changed in the associated source patch; application command and policy source remain untouched.

## Supersession
This corrected manifest supersedes the earlier checksum value. The prior manifest was preserved byte-exact at `backend-laravel/backups/benchmark-cleanup-proof-20261125/BACKUP_MANIFEST_RUNTIME_EVIDENCE_EB883994.md.before_sha256_correction`.
