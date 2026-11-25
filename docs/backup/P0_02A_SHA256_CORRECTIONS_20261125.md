# SHA-256 Corrections — P0-02A Backup Manifests

Tanggal: 2026-11-25

Nilai SHA-256 yang tercantum pada beberapa manifest awal di bawah ini dihitung dengan implementasi digest yang tidak lengkap. Jangan gunakan nilai lama tersebut. Backup file tidak berubah; ukuran UTF-8 dan Git blob SHA-1 tetap benar. Nilai SHA-256 yang benar dihitung ulang dengan implementasi SHA-256 yang lolos known vector `SHA256("test") = 9f86d081884c7d659a2feaa0c55ad015a3bf4f1b2b0b822cd15d6c15b0f00a08`.

| Backup file | Ukuran UTF-8 | Git blob SHA-1 | SHA-256 yang benar |
|---|---:|---|---|
| `backend-laravel/backup_before_patch/P0_02A_ATP_PENDING_CP_GUARD_20261125/TeacherLearningOutcomeService.php.before` | 19,103 bytes | `62d7dd2176253a9064700aa4058ba52d14871e4e` | `3638a983fb2a9f9a5996379704b9871ed386899926a43888635858eab0c3b579` |
| `backend-laravel/backup_before_patch/P0_02A_ACCEPTANCE_TEST_FIXTURE_20261125/P002ANonCpAcademicAcceptanceTest.php.before` | 28,839 bytes | `90f8b9c25b40d4772f3db6f3d79ed689f1728fb2` | `d8715221f66369ed2e9891c51ead53c88551e5a7194bcacdfd153a8282f15056` |
| `backend-laravel/backup_before_patch/P0_02A_PIVOT_COLUMN_FIX_20261125/P002ANonCpAcademicAcceptanceTest.php.before` | 28,874 bytes | `7c392beeb7b74d88aca750fdd1ee353a72935e0d` | `c04a8c856f943636ba743c8c6c6c06691d6d2853c49bea8cae99536f8014b77f` |
| `backend-laravel/backup_before_patch/P0_02A_FULL_NEGATIVE_COVERAGE_20261125/P002ANonCpAcademicAcceptanceTest.php.before` | 28,866 bytes | `526689866c2615b40774a6877541dc65fb38d092` | `b452754916945b64ec75d85a9b7db6eeccfc22090c348810182ddb8a59be3899` |

Sumber kebenaran untuk digest adalah tabel koreksi ini. File backup tidak ditulis ulang dan perbandingan byte-exact sebelumnya tetap berlaku.
