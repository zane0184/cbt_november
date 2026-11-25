# Backup Manifest — P0-02A.1 Provenance Contract Alignment

Tanggal: 2026-11-25

| File | Backup | Ukuran | Git blob SHA-1 | SHA-256 |
|---|---|---:|---|---|
| `backend-laravel/app/Services/TeacherPortal/TeacherLearningOutcomeService.php` | `backend-laravel/backup_before_patch/P0_02A1_PROVENANCE_CONTRACT_ALIGNMENT_20261125/TeacherLearningOutcomeService.php.before` | 24,433 bytes | `3e1a484aec0a212234e866c04bfde3d2789d2aa6` | `16ac6488ff927184ff3271d322493668553e5768038883a0eaef9bc42847eb4f` |
| `backend-laravel/tests/Feature/TeacherLearningOutcomeProvenanceTest.php` | `backend-laravel/backup_before_patch/P0_02A1_PROVENANCE_CONTRACT_ALIGNMENT_20261125/TeacherLearningOutcomeProvenanceTest.php.before` | 12,249 bytes | `3440a0a0690a161d51d71717944df339b5ec4736` | `17b04bebd422406674917473820bea98724e85bdece82297190e7c795a880c06` |

Backup commits:
- Service: `ddae25989ed4a61bfc062e115d28cf543e2db762`
- Test: `c2573aeef27f54ab947830592125f6c2337235af`

Verifikasi fetch ulang: kedua pasangan source/backup identik byte-for-byte dan Git blob SHA-1 sama. SHA-256 menggunakan implementasi yang diverifikasi terhadap test vector standar SHA256("test").

Perubahan berikutnya hanya menghapus syarat tambahan `MultiLearningOutcome.verification_status` yang tidak menjadi bagian dari guard CP sebelumnya; guard otoritatif tetap mensyaratkan CP ACTIVE, resmi/tidak editable, sumber ARTIFACT_CHECKSUM_VERIFIED, checksum sumber 64 hex, checksum CP 64 hex, dan kesamaan `hash_equals`. Test CP PENDING dipindahkan ke status sumber PENDING agar sesuai dengan kontrak provenance sumber yang dipakai oleh createTp dan TeacherCurriculumContextService.