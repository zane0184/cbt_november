# Backup Manifest — Agent 2 P0 Attempt/Answer/Scoring

Branch: work/agent2-p0-attempt-answer-scoring-20261122
Baseline: 4e663cd84038b7c3337d776f702c499f20d8f537
Backup folder: backend-laravel/docs/backup/agent2-p0-attempt-answer-scoring-2026-11-22/
Method: GitHub contents API; each backup was re-fetched and its Git blob SHA matched the source blob SHA exactly.
Source files: 19
Restore rule: copy the corresponding .before file back to the original path using a new commit; do not reset the branch.

| Source path | Original Git blob SHA | Backup path | Verified |
|---|---|---|---|
| backend-laravel/app/Http/Controllers/Api/V1/ExamAnswerController.php | b4ddec14f6208b3ecde9bfc3e45168a2159e2a9c | backend-laravel/docs/backup/agent2-p0-attempt-answer-scoring-2026-11-22/ExamAnswerController.php.before | YES |
| backend-laravel/app/Http/Controllers/Api/V1/ExamAttemptController.php | 13e8981cca4653ff4e9b9448a50e4d0ec8a74bae | backend-laravel/docs/backup/agent2-p0-attempt-answer-scoring-2026-11-22/ExamAttemptController.php.before | YES |
| backend-laravel/app/Services/ScoringService.php | 1fc74eae97538ada140b9b4c75ccd4388d1a40ad | backend-laravel/docs/backup/agent2-p0-attempt-answer-scoring-2026-11-22/ScoringService.php.before | YES |
| backend-laravel/app/Http/Controllers/Api/V1/ScoringController.php | 42b96176162a6e69e3661d468a6d3362b05f59c5 | backend-laravel/docs/backup/agent2-p0-attempt-answer-scoring-2026-11-22/ScoringController.php.before | YES |
| backend-laravel/app/Services/ExamSnapshotService.php | d0bfcd70b2777a3884c7f92b7c256f05492645ec | backend-laravel/docs/backup/agent2-p0-attempt-answer-scoring-2026-11-22/ExamSnapshotService.php.before | YES |
| backend-laravel/app/Http/Controllers/Api/V1/QuestionController.php | 7ded53423dd4d96788123819e8039fd543ce4fba | backend-laravel/docs/backup/agent2-p0-attempt-answer-scoring-2026-11-22/QuestionController.php.before | YES |
| backend-laravel/app/Policies/ExamPolicy.php | c05d96ef932fdc81b67336240af4e1726864c3d6 | backend-laravel/docs/backup/agent2-p0-attempt-answer-scoring-2026-11-22/ExamPolicy.php.before | YES |
| backend-laravel/app/Policies/ExamAttemptPolicy.php | 00a3a4119f3983de6d940fd8f352a541336c9e12 | backend-laravel/docs/backup/agent2-p0-attempt-answer-scoring-2026-11-22/ExamAttemptPolicy.php.before | YES |
| backend-laravel/app/Http/Controllers/Api/V1/ExamController.php | c7c62e961f2ff47abee8f3b2468b4e7bc43d1cc9 | backend-laravel/docs/backup/agent2-p0-attempt-answer-scoring-2026-11-22/ExamController.php.before | YES |
| backend-laravel/app/Services/Reporting/ExamSummaryReportService.php | 715c2df7fff94eb1f91bd601e5e82cb8432307e6 | backend-laravel/docs/backup/agent2-p0-attempt-answer-scoring-2026-11-22/ExamSummaryReportService.php.before | YES |
| backend-laravel/app/Policies/AssessmentPolicy.php | 1aea2b90d92139c9c6935c60e94ca43e825abb29 | backend-laravel/docs/backup/agent2-p0-attempt-answer-scoring-2026-11-22/AssessmentPolicy.php.before | YES |
| backend-laravel/app/Policies/QuestionPolicy.php | 81e3cb6c82f1a2f5e15deacf8b8ea50f8a494566 | backend-laravel/docs/backup/agent2-p0-attempt-answer-scoring-2026-11-22/QuestionPolicy.php.before | YES |
| backend-laravel/app/Policies/ReportPolicy.php | 0fbd934bde79ebd7ae3fe4fe08f09cb4dc9e46ce | backend-laravel/docs/backup/agent2-p0-attempt-answer-scoring-2026-11-22/ReportPolicy.php.before | YES |
| backend-laravel/app/Http/Controllers/Api/V1/MediaController.php | f852c1b3a7247efdbb0c309fb0413bbe5e3eaa62 | backend-laravel/docs/backup/agent2-p0-attempt-answer-scoring-2026-11-22/MediaController.php.before | YES |
| backend-laravel/app/Http/Controllers/Api/V1/ExamParticipantController.php | 6720e53c879f9cd2f6f82dc63c0ff37d5f62ab79 | backend-laravel/docs/backup/agent2-p0-attempt-answer-scoring-2026-11-22/ExamParticipantController.php.before | YES |
| backend-laravel/app/Models/User.php | 74dd080f703d7ef3256b406176e53dad5786a937 | backend-laravel/docs/backup/agent2-p0-attempt-answer-scoring-2026-11-22/User.php.before | YES |
| backend-laravel/tests/Feature/AdminCbtBoundaryTest.php | fc8e99c971485a9021bf7c6d7ca765c6426b89ac | backend-laravel/docs/backup/agent2-p0-attempt-answer-scoring-2026-11-22/AdminCbtBoundaryTest.php.before | YES |
| backend-laravel/tests/Feature/ExamAnswerTest.php | c6ac21f3db9591e37ffbdf21228f4736d7bd962a | backend-laravel/docs/backup/agent2-p0-attempt-answer-scoring-2026-11-22/ExamAnswerTest.php.before | YES |
| backend-laravel/tests/Feature/ExamSessionHardeningTest.php | 95f2708b5aaf4948571d21b0a3bb3ae60edb2215 | backend-laravel/docs/backup/agent2-p0-attempt-answer-scoring-2026-11-22/ExamSessionHardeningTest.php.before | YES |
