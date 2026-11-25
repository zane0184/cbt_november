# Backup Manifest — P0-02A.1 ATP Mapping and Assignment Relation Fix

Tanggal: 2026-11-25

| File | Backup | UTF-8 bytes | Git blob SHA-1 | SHA-256 |
|---|---|---:|---|---|
| `backend-laravel/app/Models/TeacherSubjectAssignment.php` | `backend-laravel/backup_before_patch/P0_02A1_ATP_MAPPING_AND_RELATION_FIX_20261125/TeacherSubjectAssignment.php.before` | 2,186 | `0abe29f125998fd63b29fb8364a3276130304a85` | `9ddc71599460a4be128db49e37c16448c2efe7c098c7a06cbef523a9ef477277` |
| `backend-laravel/app/Services/TeacherPortal/TeacherLearningOutcomeService.php` | `backend-laravel/backup_before_patch/P0_02A1_ATP_MAPPING_AND_RELATION_FIX_20261125/TeacherLearningOutcomeService.php.before` | 24,360 | `734735c6c31f9b0a7648705e322dda3fba28015f` | `bfa37ae563bf19e14decff9ef62118ebefb2246413870eca9ff04d442735580d` |

Backup commits:
- Model: `6595ef2ad353e4d4abb7b0085bb24b7a27deb73e`
- Service: `995fd5108f4fe6df8e22ea42bfd35e25ea98f250`

Verifikasi fetch ulang: source dan backup identik byte-for-byte; ukuran dan Git blob SHA-1 sama. SHA-256 dihitung dengan implementasi yang lolos test vector standar SHA256("test").

Backup dibuat sebelum memperbaiki alias relasi `teacherProfile()` dan menyertakan `teacher_subject_assignment_id` pada mapping ATP.