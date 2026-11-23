<?php

use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/health', function () {
        return response()->json([
            'success' => true,
            'message' => 'CBT Madrasah API is running',
            'version' => '1.0.0',
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION,
            'timestamp' => now()->toIso8601String(),
        ]);
    });

    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);
        Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/logout', [AuthController::class, 'logout']);
        });
    });

    Route::middleware(['auth:sanctum', 'tenant', 'license'])->group(function () {
        // Curriculum Detail
        // Legacy curriculum master APIs are reference-only. Official curriculum
        // versions/structure are managed through the audited registry; Guru may
        // author only assignment-scoped TP/ATP through the canonical services.
        // Retiring these generic resource mutations also closes GradeLevel/Element
        // write handlers that had no controller-level authorization.
        Route::apiResource('curricula', \App\Http\Controllers\Api\V1\CurriculumController::class)->only(['index', 'show']);
        Route::apiResource('phases', \App\Http\Controllers\Api\V1\PhaseController::class)->only(['index', 'show']);
        Route::apiResource('grade-levels', \App\Http\Controllers\Api\V1\GradeLevelController::class)->only(['index', 'show']);
        Route::apiResource('elements', \App\Http\Controllers\Api\V1\ElementController::class)->only(['index', 'show']);
        // Official CP is read-only through the legacy API; mutations use the verified curriculum registry.
        // Legacy TP write API disabled; Guru authors TP through assignment-scoped TeacherLearning services.
        // Legacy ATP/LearningPath write API disabled; Guru authors ATP through the canonical curriculum workflow.
        // Legacy material API is read-only; Guru writes use assignment-scoped TeacherMaterialManagement.
        Route::apiResource('materials', \App\Http\Controllers\Api\V1\MaterialController::class)->only(['index', 'show']);

        // Bank Soal
        // Legacy rows/XLSX import is disabled; use the canonical teacher authoring/import workflow.
        Route::apiResource('questions', \App\Http\Controllers\Api\V1\QuestionController::class);
        Route::get('/questions/{question}/versions', [\App\Http\Controllers\Api\V1\QuestionController::class, 'versions']);
        Route::post('/questions/{question}/publish', [\App\Http\Controllers\Api\V1\QuestionController::class, 'publish']);

        Route::apiResource('question-publications', \App\Http\Controllers\Api\V1\QuestionPublicationController::class);
        Route::get('/media', [\App\Http\Controllers\Api\V1\MediaController::class, 'index']);
        Route::post('/media', [\App\Http\Controllers\Api\V1\MediaController::class, 'store']);
        Route::get('/media/{media}', [\App\Http\Controllers\Api\V1\MediaController::class, 'show']);
        Route::delete('/media/{media}', [\App\Http\Controllers\Api\V1\MediaController::class, 'destroy']);
        Route::post('/questions/{question}/versions/{version}/media', [\App\Http\Controllers\Api\V1\MediaController::class, 'attachToQuestionVersion']);
        Route::delete('/questions/{question}/versions/{version}/media/{media}', [\App\Http\Controllers\Api\V1\MediaController::class, 'detachFromQuestionVersion']);
        Route::apiResource('tags', \App\Http\Controllers\Api\V1\TagController::class);

        // Teacher Subject Assignment (tenant-scoped, assignment-based authority)
        Route::apiResource('teacher-subject-assignments', \App\Http\Controllers\Api\V1\TeacherSubjectAssignmentController::class);

        // Assessment & Exam Builder
        Route::apiResource('assessments', \App\Http\Controllers\Api\V1\AssessmentController::class)->only(['index', 'show', 'destroy']);
        Route::apiResource('exams', \App\Http\Controllers\Api\V1\ExamController::class);
        Route::get('/exams/{exam}/monitoring', [\App\Http\Controllers\Api\V1\MonitoringController::class, 'summary']);
        Route::get('/exams/{exam}/monitoring/participants', [\App\Http\Controllers\Api\V1\MonitoringController::class, 'participants']);
        Route::get('/exams/{exam}/monitoring/attempts/{attempt}', [\App\Http\Controllers\Api\V1\MonitoringController::class, 'attempt']);
        Route::get('/exams/{exam}/monitoring/events', [\App\Http\Controllers\Api\V1\MonitoringController::class, 'events']);
        Route::get('/exams/{exam}/preview', [\App\Http\Controllers\Api\V1\ExamController::class, 'preview']);

        Route::post('/exams/{exam}/questions', [\App\Http\Controllers\Api\V1\ExamController::class, 'addQuestions']);
        Route::post('/exams/{exam}/questions/bulk-remove', [\App\Http\Controllers\Api\V1\ExamController::class, 'bulkRemoveQuestions']);
        Route::delete('/exams/{exam}/questions/{snapshot}', [\App\Http\Controllers\Api\V1\ExamController::class, 'removeQuestion']);
        Route::post('/exams/{exam}/questions/reorder', [\App\Http\Controllers\Api\V1\ExamController::class, 'reorderQuestions']);
        Route::post('/exams/{exam}/token', [\App\Http\Controllers\Api\V1\ExamController::class, 'generateToken']);
        Route::post('/exams/{exam}/lock', [\App\Http\Controllers\Api\V1\ExamController::class, 'lock']);
        Route::post('/exams/{exam}/close', [\App\Http\Controllers\Api\V1\ExamController::class, 'close']);
        Route::post('/exams/{exam}/assign-rombel', [\App\Http\Controllers\Api\V1\ExamController::class, 'assignRombel']);
        Route::post('/exams/{exam}/publish-results', [\App\Http\Controllers\Api\V1\ExamController::class, 'publishResults']);

        Route::apiResource('exams.participants', \App\Http\Controllers\Api\V1\ExamParticipantController::class)->only(['index', 'store', 'destroy']);
        Route::get('/exams/{exam}/execution', [\App\Http\Controllers\Api\V1\ExamAttemptController::class, 'execution']);
        Route::apiResource('exams.attempts', \App\Http\Controllers\Api\V1\ExamAttemptController::class)->only(['store', 'show']);
        Route::post('/exams/{exam}/attempts/{attempt}/heartbeat', [\App\Http\Controllers\Api\V1\MonitoringController::class, 'heartbeat'])
            ->middleware('throttle:exam-heartbeat'); // 30/minute per authenticated tenant user
        Route::get('/exams/{exam}/attempts/{attempt}/answers', [\App\Http\Controllers\Api\V1\ExamAnswerController::class, 'index']);
        Route::put('/exams/{exam}/attempts/{attempt}/answers/{snapshot}', [\App\Http\Controllers\Api\V1\ExamAnswerController::class, 'update']);
        Route::post('/exams/{exam}/attempts/{attempt}/submit', [\App\Http\Controllers\Api\V1\ExamAttemptController::class, 'submit']);

        // Scoring & Grading
        Route::post('/exams/{exam}/attempts/{attempt}/score', [\App\Http\Controllers\Api\V1\ScoringController::class, 'score']);
        Route::post('/exams/{exam}/attempts/{attempt}/recalculate', [\App\Http\Controllers\Api\V1\ScoringController::class, 'recalculate']);
        Route::get('/exams/{exam}/attempts/{attempt}/score', [\App\Http\Controllers\Api\V1\ScoringController::class, 'show']);
        Route::put('/exams/{exam}/attempts/{attempt}/grading/{questionScore}', [\App\Http\Controllers\Api\V1\ScoringController::class, 'updateManualScore']);
        Route::post('/exams/{exam}/attempts/{attempt}/finalize', [\App\Http\Controllers\Api\V1\ScoringController::class, 'finalize']);
        Route::post('/exams/{exam}/attempts/{attempt}/reopen', [\App\Http\Controllers\Api\V1\ScoringController::class, 'reopen']);

        // Reporting
        Route::prefix('reports')->group(function () {
            Route::get('/exams/{exam}/results', [\App\Http\Controllers\Api\V1\ReportController::class, 'examResults']);
            Route::get('/exams/{exam}/summary', [\App\Http\Controllers\Api\V1\ReportController::class, 'examSummary']);
            Route::get('/exams/{exam}/questions', [\App\Http\Controllers\Api\V1\ReportController::class, 'questionReport']);
            Route::get('/exams/{exam}/summary/export/pdf', [\App\Http\Controllers\Api\V1\ReportController::class, 'exportSummaryPdf']);
            Route::get('/exams/{exam}/export/excel', [\App\Http\Controllers\Api\V1\ReportController::class, 'exportExcel']);
            Route::get('/exams/{exam}/export/pdf', [\App\Http\Controllers\Api\V1\ReportController::class, 'exportPdf']);
            Route::get('/exams/{exam}/rombels/{rombel}', [\App\Http\Controllers\Api\V1\ReportController::class, 'rombelReport']);
            Route::get('/students/{student}/results', [\App\Http\Controllers\Api\V1\ReportController::class, 'studentResults']);
            Route::get('/subjects/{subject}/results', [\App\Http\Controllers\Api\V1\ReportController::class, 'subjectReport']);
            Route::get('/students/{student}/export/pdf', [\App\Http\Controllers\Api\V1\ReportController::class, 'exportStudentPdf']);
        });

        // Analytics
        Route::prefix('analytics')->group(function () {
            Route::prefix('curriculum')->group(function () {
                Route::get('/students/{student}', [\App\Http\Controllers\Api\V1\CurriculumAnalyticsController::class, 'studentAnalytics']);
                Route::get('/rombels/{rombel}', [\App\Http\Controllers\Api\V1\CurriculumAnalyticsController::class, 'rombelAnalytics']);
                Route::get('/subjects/{subject}', [\App\Http\Controllers\Api\V1\CurriculumAnalyticsController::class, 'subjectAnalytics']);
                Route::get('/tps/{tp}', [\App\Http\Controllers\Api\V1\CurriculumAnalyticsController::class, 'tpAnalytics']);
            });

            Route::get('/exams/{exam}', [\App\Http\Controllers\Api\V1\AnalyticsController::class, 'examSummary']);
            Route::get('/exams/{exam}/distribution', [\App\Http\Controllers\Api\V1\AnalyticsController::class, 'examDistribution']);
            Route::get('/exams/{exam}/items', [\App\Http\Controllers\Api\V1\AnalyticsController::class, 'examItems']);
            Route::get('/rombels/{rombel}/exams/{exam}', [\App\Http\Controllers\Api\V1\AnalyticsController::class, 'rombelExamAnalytics']);
            Route::get('/students/{student}', [\App\Http\Controllers\Api\V1\AnalyticsController::class, 'studentAnalytics']);
            Route::get('/subjects/{subject}', [\App\Http\Controllers\Api\V1\AnalyticsController::class, 'subjectAnalytics']);
        });
    });
});
