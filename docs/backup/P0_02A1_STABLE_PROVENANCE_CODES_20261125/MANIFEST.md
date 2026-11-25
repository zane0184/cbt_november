# Backup Manifest — P0-02A.1 Stable Provenance Rejection Codes

Tanggal: 2026-11-25

| File | Backup | UTF-8 bytes | Git blob SHA-1 | SHA-256 |
|---|---|---:|---|---|
| `backend-laravel/app/Services/TeacherPortal/TeacherLearningOutcomeService.php` | `backend-laravel/backup_before_patch/P0_02A1_STABLE_PROVENANCE_CODES_20261125/TeacherLearningOutcomeService.php.before` | 24,449 | `23cd9f01fc9a250d2fa84023502355d8956f0a11` | `296c66773cbaf00ca14c7468898bd84e8776cc589b68b8a93685e8d51cedbd4a` |
| `backend-laravel/tests/Feature/TeacherLearningOutcomeProvenanceTest.php` | `backend-laravel/backup_before_patch/P0_02A1_STABLE_PROVENANCE_CODES_20261125/TeacherLearningOutcomeProvenanceTest.php.before` | 12,257 | `31ee25a475ff20a6080fb544449f2f1ddcec5dfc` | `6b2505780e34e7365461796c070d06c46a87212b6da9a284b89fd77a6d1457d8` |

Backup commits:
- Service: `2ffce22a1365d72755f3af6fa06be9f499f0e372`
- Test: `e416a9456e58406944d19f3b0ad749da00525bf2`

Fetch ulang membuktikan kedua backup identik byte-for-byte dengan source pada baseline ref `a62fac21f58aa5dc452444915ae15bfbbd3b2126`; ukuran dan Git blob SHA-1 cocok. SHA-256 dihitung atas byte UTF-8 dan fungsi digest diverifikasi menggunakan test vector standar `SHA256("test") = 9f86d081884c7d659a2feaa0c55ad015a3bf4f1b2b0b822cd15d6c15b0f00a08`.

Backup dibuat sebelum patch kode penolakan stabil, konsistensi permission, locking, dan regression assertions.