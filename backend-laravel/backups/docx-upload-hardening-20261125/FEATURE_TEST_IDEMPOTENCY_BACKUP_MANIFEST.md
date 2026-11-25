# Backup manifest — DOCX feature-test idempotency fixture

Branch: `work/guru-docx-import-e2e-20261125`
Pre-patch HEAD: `31d13bde90b8cab6865cac7c0ba5164692dddd13`
Source: `backend-laravel/tests/Feature/QuestionDocxImportFeatureTest.php`
Source blob SHA before patch: `b48458edc24a3f64041f71f8922259faf0d0ff70`
Backup path: `backend-laravel/backups/docx-upload-hardening-20261125/QuestionDocxImportFeatureTest.php.before-idempotency-fixture-fix`
Backup blob SHA: `b48458edc24a3f64041f71f8922259faf0d0ff70`

Test-only correction: both duplicate-import attempts now use identical DOCX bytes, including ZIP metadata, so the SHA-256 idempotency assertion tests the intended contract.