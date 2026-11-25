# Backup manifest — canonical active-user status read

- Repository: zane0184/cbt_november
- Branch: work/p1-seven-targets-20261124
- Parent SHA: 8b73566ffaf4dd39769e19abef9df6765cdc9a13
- Source path: backend-laravel/app/Http/Middleware/EnsureActiveUser.php
- Source blob SHA: fc36a0b1b23da74d9858ee92e42722eb2ac2f5aa
- Backup path: backend-laravel/backup_before_patch/ADM_ACTIVE_USER_CANONICAL_STATUS_20261124/EnsureActiveUser.php.before
- Backup blob SHA: fc36a0b1b23da74d9858ee92e42722eb2ac2f5aa
- Byte-exact result: MATCH

The middleware now reads is_active from the database using the authenticated user ID and tenant ID before allowing a protected request.