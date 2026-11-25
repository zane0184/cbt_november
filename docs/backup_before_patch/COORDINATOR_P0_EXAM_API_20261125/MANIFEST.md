# Backup Manifest — Coordinator P0 Exam API
Tanggal: 25 November 2026
Source branch: `work/guru-docx-import-e2e-20261125`
Source HEAD: `7c1cdcbe62673d50d05923cef8de9b462cd8aec9`
Implementation branch: `work/coordinator-web-cbt-p0-20261125`

Semua backup berikut dibuat sebelum file source terkait diubah. Isi backup diverifikasi byte-equivalent terhadap file sumber yang diambil dari GitHub; SHA-256 dihitung atas byte UTF-8.

| Source path | Backup path | Bytes | SHA-256 | Source blob SHA-1 | Backup commit |
|---|---|---:|---|---|---|
| `backend-laravel/app/Http/Controllers/Api/V1/ExamController.php` | `backend-laravel/backups/coordinator-web-cbt-p0-20261125/ExamController.php.before` | 26091 | `545531788b2eb516c1a55a7149f37a361c582a9eac006c5c06873c8ae4a02e9a` | `df9a1f2cb29d28950dc82875cb54d7c8dcd0232d` | `8a4cc0d79fb00f77d4b33cc61a34c0202d4fdc0e` |
| `backend-laravel/app/Policies/ExamPolicy.php` | `backend-laravel/backups/coordinator-web-cbt-p0-20261125/ExamPolicy.php.before` | 7016 | `57f689d697bae0d20bb41f9b7ebf32cb5b2cd82a40086abe85dd05dd72e73d05` | `b2be589419b935a2f5e15dfe3985aaa212e55d71` | `3d5876fcfb0c296d24cca60380cd656b5522269e` |
| `backend-laravel/tests/Feature/ExamListContractTest.php` | `backend-laravel/backups/coordinator-web-cbt-p0-20261125/ExamListContractTest.php.before` | 7933 | `c2820118d3ce573e87ff9ba68ef0db3a55dbcc6ea0612741ba2d8753e27cfd41` | `3e9897eaba0d9cecb4c623366008c93b60bf0f8f` | `aa2a8fc18c2721504be4070c71c4278bd550b02a` |
| `.github/workflows/docx-import-parser-ci.yml` | `.github/backups/coordinator-web-cbt-p0-20261125/docx-import-parser-ci.yml.before` | 5883 | `5e499184df178ef4dfbe73b2bf6e561944d2050add8cd1c04de899eb4bb5e4ef` | `d1740f8160b1c8c06041867e8a6da547c2014187` | `07ecc399d8619decac6b13e4b310aa90a5b20ce3` |
| `backend-laravel/tests/Feature/Security/AnswerKeyLeakageTest.php` | `backend-laravel/backups/coordinator-web-cbt-p0-20261125/AnswerKeyLeakageTest.php.before` | 19227 | `3d396bc21b3911865dd6cda49db78ca5152288b67dd7da238876a50a819a6f35` | `0a0ad3559981a3176a58c21b48bc5869e1812c04` | `568f1301dcbea512fe16258e0e8b1abf26374abd` |

Tidak ada backup yang menggantikan source baseline. Backup berada pada branch implementasi dan harus dikecualikan dari ZIP distribusi.
