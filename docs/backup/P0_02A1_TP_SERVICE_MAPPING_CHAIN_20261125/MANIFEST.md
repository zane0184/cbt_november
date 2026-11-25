# Backup Manifest — P0-02A.1 TP Service Mapping Chain Repair

Tanggal: 2026-11-25

| File | Backup | UTF-8 bytes | Git blob SHA-1 | SHA-256 |
|---|---|---:|---|---|
| `backend-laravel/app/Services/TeacherPortal/TeacherLearningOutcomeService.php` | `backend-laravel/backup_before_patch/P0_02A1_TP_SERVICE_MAPPING_CHAIN_20261125/TeacherLearningOutcomeService.php.before` | 29,572 | `5eef8195052683c186a8e76dd2f2b56972dc0dab` | `c4b0b294fb9859f853229ca676208edeed92f113da2803ca951d40993bb8547b` |
| `backend-laravel/tests/Feature/TeacherLearningOutcomeProvenanceTest.php` | `backend-laravel/backup_before_patch/P0_02A1_TP_SERVICE_MAPPING_CHAIN_20261125/TeacherLearningOutcomeProvenanceTest.php.before` | 17,476 | `fb5e66d7eb455be324bfb1feaff485b68589ed72` | `d3bc0f689d738a74115faf2cf533c83831ac22efe4c0302c5a97e38f99d507dd` |

Backup commits:
- Service: `1247f8b893fbf7064c45b05f3b3a940d50ed05f3`
- Test: `9a3880531f2a0f7c926cc139fb4378924875fd88`

Verifikasi: kedua source dan backup di-fetch ulang dan identik byte-for-byte; ukuran serta Git blob SHA-1 sama. SHA-256 routine lolos test vector `SHA256("test")`.

Backup dibuat sebelum:
1. Meneruskan `pmOlahIds` ke `validateFrameworkNodes()`.
2. Mengikat `teacher_subject_assignment_id` pada setiap mapping TP yang dibuat oleh `persistFrameworkMappings()`.
3. Menambahkan regression test yang membuat TP melalui service, memverifikasi mapping scope, lalu membuat ATP melalui service.
