# Backup manifest — account lifecycle test fixture and password wording

- Repository: zane0184/cbt_november
- Branch: work/p1-seven-targets-20261124
- Parent SHA: da86298059b5cd080462120d3a8908f319dd02e8

| Source path | Source blob SHA | Backup path | Backup blob SHA | Result |
|---|---|---|---|---|
| `backend-laravel/tests/Feature/UserAccountLifecycleTest.php` | `27e321356d948b505af8fde37b50ef8e41fe4b96` | `backend-laravel/backup_before_patch/ADM_ACCOUNT_LIFECYCLE_TEST_FIXTURE_20261124/UserAccountLifecycleTest.php.before` | `27e321356d948b505af8fde37b50ef8e41fe4b96` | MATCH |
| `backend-laravel/resources/views/livewire/admin/user-management.blade.php` | `c9132343595540ff73bef8611c242f740a0f9e63` | `backend-laravel/backup_before_patch/ADM_ACCOUNT_LIFECYCLE_TEST_FIXTURE_20261124/user-management.blade.php.before` | `c9132343595540ff73bef8611c242f740a0f9e63` | MATCH |

The lifecycle test now creates a local installation identity and verifies both active-user resolution and inactive-user rejection. UI wording no longer implies forced first-login password change.