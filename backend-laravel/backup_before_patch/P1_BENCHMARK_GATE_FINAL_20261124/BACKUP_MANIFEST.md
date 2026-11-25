# Backup byte-exact — final benchmark verification gate

- Repository: `zane0184/cbt_november`
- Branch: `work/p1-seven-targets-20261124`
- Date: 24 November 2026
- Verified by re-fetching each backup and comparing full content.

- Source: `backend-laravel/app/Console/Commands/Phase7dBenchmarkCommand.php`
  - Source blob SHA: `dabcc05a1f670159cfd39503a0eeeb9fa53d5d3c`
  - Backup: `backend-laravel/backup_before_patch/P1_BENCHMARK_GATE_FINAL_20261124/Phase7dBenchmarkCommand.php.before`
  - Backup blob SHA: `dabcc05a1f670159cfd39503a0eeeb9fa53d5d3c`
  - Backup commit: `38d19698a3cde79e9a4a5a4f98f048892377a97b`
  - Byte-exact: **MATCH**

- Source: `.github/workflows/tenant-backend-ci.yml`
  - Source blob SHA: `0366c2ae89702387b95ba726e486074559ded286`
  - Backup: `backend-laravel/backup_before_patch/P1_BENCHMARK_GATE_FINAL_20261124/tenant-backend-ci.yml.before`
  - Backup blob SHA: `0366c2ae89702387b95ba726e486074559ded286`
  - Backup commit: `6641cf3610ffe08d7b9d67d96b56a309c076e278`
  - Byte-exact: **MATCH**
