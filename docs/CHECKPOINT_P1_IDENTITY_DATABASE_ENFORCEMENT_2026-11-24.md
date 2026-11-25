# Checkpoint P1 — Identity DB Enforcement Audit

Tanggal: 24 November 2026  
Branch: `work/p1-seven-targets-20261124`

## Posisi terakhir yang terverifikasi

- Login identity unique indexes: sudah ada, termasuk fail-closed preflight.
- Format/category enforcement untuk raw SQL: belum ada.
- UI Admin create validation: ada.
- Read-only preflight: ada, tetapi tidak mencegah write selanjutnya.
- Account create audit events: ada pada source aktual.
- Audit sanitizer: email/phone/identity values disamarkan.
- Profile identity update/import flow: belum ditemukan pada inventory app path yang diperiksa.
- Banyak test fixtures lama belum memakai identitas sesuai owner contract; beberapa test memang sengaja membuat legacy/malformed records.

## Next implementation gate

1. Desain final CHECK MySQL 8 + trigger SQLite.
2. Backup dan update test fixtures normal ke synthetic identities yang valid.
3. Isolasi legacy malformed preflight tests.
4. Implement migration fail-closed: seluruh preflight sebelum DDL.
5. Test raw SQL INSERT/UPDATE invalid category/format ditolak.
6. Jalankan migration/test hanya pada disposable MySQL 8 dan SQLite test DB.
7. Audit update/import/recovery sebelum klaim identity acceptance.

## Safety

- Source/migration belum diubah pada workstream ini.
- No production DB/VPS touched.
- No migration production.
- No merge/deployment.
- P1-ID-03B remains BLOCKED.
