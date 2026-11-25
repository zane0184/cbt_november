# Backup Manifest — P1 MATCHING Gate + Media Early-Close
Tanggal: 25 November 2026
Repository: `zane0184/cbt_november`
Branch: `work/coordinator-web-cbt-p0-20261125`
Source HEAD sebelum patch: `d0ed25cc07b58abcaddcbddd363dae42396018ce`

Semua backup dibuat dari source aktual pada HEAD di atas, sebelum perubahan file. SHA-256 dihitung atas byte UTF-8; Git blob SHA sumber dan backup harus identik.

| Source path | Backup path | Bytes | SHA-256 | Source blob SHA-1 | Backup blob SHA-1 |
|---|---|---:|---|---|---|
| `backend-laravel/app/Livewire/Admin/QuestionBank.php` | `backend-laravel/backups/coordinator-p1-matching-media-20261125/QuestionBank.php.before` | 41395 | `3aca3c2dc6c63731312e645791ff199a036a083b24b732e344e6a54b393a180e` | `b0de64e9acb6c2bc27b2ce3cf384a9273ea457d1` | `b0de64e9acb6c2bc27b2ce3cf384a9273ea457d1` |
| `backend-laravel/app/Services/ExamSnapshotService.php` | `backend-laravel/backups/coordinator-p1-matching-media-20261125/ExamSnapshotService.php.before` | 26885 | `9115880cb642e2b3addf5f5dca5c63984455be78dc0a4160dd5c8c20a079492a` | `6095149414c4c1d485730c19d456fa7cc7ebf4bb` | `6095149414c4c1d485730c19d456fa7cc7ebf4bb` |
| `backend-laravel/tests/Feature/QuestionBankTest.php` | `backend-laravel/backups/coordinator-p1-matching-media-20261125/QuestionBankTest.php.before` | 29588 | `d6b4e73cc797519d68023f3c4cc7898970e7e7e4c7aa4df6fa021debeacf4d9f` | `579a35da947f9409887f42e5d1673d6675d725f8` | `579a35da947f9409887f42e5d1673d6675d725f8` |
| `backend-laravel/tests/Feature/ExamSnapshotTest.php` | `backend-laravel/backups/coordinator-p1-matching-media-20261125/ExamSnapshotTest.php.before` | 42127 | `1e36fc3fa6da9c3f67dfe653ab1dfe72695501847b453deead8651e5a1eb6e09` | `decfa652997ef70c2670af9a25d4b1b1eb39e307` | `decfa652997ef70c2670af9a25d4b1b1eb39e307` |
| `backend-laravel/app/Http/Controllers/Api/V1/MediaController.php` | `backend-laravel/backups/coordinator-p1-matching-media-20261125/MediaController.php.before` | 18956 | `225438f9de60020fa0473a419f7f2d61fd4f00aff37e8f4351c96de2ec66d5a4` | `850034595f901e063119a831ffabea1b574d13d9` | `850034595f901e063119a831ffabea1b574d13d9` |
| `backend-laravel/tests/Feature/ExamSessionHardeningTest.php` | `backend-laravel/backups/coordinator-p1-matching-media-20261125/ExamSessionHardeningTest.php.before` | 15233 | `fd289ef19b1b8624ded9acbe43fec50efd61fbe0bace2e529ba0a07ae800e617` | `eb86364f927fadc9aa1ebfb268e4711a3e5ba34b` | `eb86364f927fadc9aa1ebfb268e4711a3e5ba34b` |

Backup disimpan di branch implementasi. Folder backup wajib dikecualikan dari ZIP distribusi.
