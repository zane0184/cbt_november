# Backup Manifest — Explicit Source Scope Replacement
Tanggal: 26 November 2026
Branch: integration/zip-vps-php83-20261126
File: backend-laravel/database/seeders/CurriculumReferenceSourceSeeder.php
Blob SHA sebelum patch: 75edda8712c04469af75590849ebaa456e989236
Backup byte-exact: backend-laravel/backups/curriculum-source-scope-reset-20261126/CurriculumReferenceSourceSeeder.php.before
Alasan: array_replace_recursive tidak menghapus item array lama ketika source metadata baru memberikan verified_scope kosong. Perlu assignment eksplisit agar record BKPDM decision PENDING tidak mewarisi verified_scope dari record lama.
