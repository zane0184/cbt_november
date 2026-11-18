<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptScore;
use App\Models\ExamAttemptScoreCurrent;
use App\Models\ExamQuestionScore;
use App\Services\AuditService;
use App\Services\ScoringService;
use App\Services\Analytics\AnalyticsCacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ScoringController extends Controller
{
    public function __construct(
        protected ScoringService $scoringService,
        protected AuditService $auditService,
        protected AnalyticsCacheService $analyticsCacheService
    ) {}

    public function score(Exam $exam, ExamAttempt $attempt)
    {
        $this->authorize('score', $attempt);

        if ($attempt->exam_id !== $exam->id) {
            abort(404);
        }

        return DB::transaction(function () use ($attempt) {
            // Keep the same lock order as answer writes and submit: attempt first.
            $lockedAttempt = ExamAttempt::query()
                ->whereKey($attempt->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedAttempt->status === 'IN_PROGRESS') {
                return response()->json([
                    'success' => false,
                    'message' => 'Attempt must be closed before scoring.'
                ], 422);
            }

            try {
                $score = $this->scoringService->scoreAttempt($lockedAttempt);
            } catch (\DomainException $e) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 409);
            }

            $this->auditService->log('SCORE_ATTEMPT', 'ExamAttemptScore', $score->id);

            return response()->json(['success' => true, 'data' => $score]);
        });
    }

    public function show(Request $request, Exam $exam, ExamAttempt $attempt)
    {
        if ($attempt->exam_id !== $exam->id || $attempt->tenant_id !== $exam->tenant_id) {
            abort(404);
        }

        $this->authorize('viewScore', $attempt);

        $currentPointer = ExamAttemptScoreCurrent::where('attempt_id', $attempt->id)->first();
        if (!$currentPointer) {
            return response()->json([
                'success' => false,
                'message' => 'Current score pointer is missing; result is unavailable pending integrity review.'
            ], 409);
        }

        $score = ExamAttemptScore::whereKey($currentPointer->current_score_id)
            ->where('attempt_id', $attempt->id)
            ->where('tenant_id', $attempt->tenant_id)
            ->where('scoring_version', $currentPointer->scoring_version)
            ->first();

        if (!$score) {
            return response()->json([
                'success' => false,
                'message' => 'Current score pointer target is invalid; result is unavailable pending integrity review.'
            ], 409);
        }

        // Students receive only the published summary. Per-question evaluated
        // answers, grading feedback, grader identity, and internal score metadata
        // remain restricted to authorized staff even when show_result is enabled.
        if ($request->user()->hasRole('SISWA')) {
            abort_unless(
                $score->tenant_id === $attempt->tenant_id
                    && $score->attempt_id === $attempt->id
                    && $score->grading_status === 'FINALIZED',
                404
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => [
                        'raw_score' => $score->raw_score,
                        'max_score' => $score->max_score,
                        'percentage' => $score->percentage,
                        'grading_status' => $score->grading_status,
                        'scoring_version' => $score->scoring_version,
                        'calculated_at' => $score->calculated_at?->toIso8601String(),
                        'finalized_at' => $score->finalized_at?->toIso8601String(),
                    ],
                    'details' => [],
                ],
            ]);
        }

        $questionScores = ExamQuestionScore::where('tenant_id', $attempt->tenant_id)
            ->where('exam_id', $exam->id)
            ->where('attempt_id', $attempt->id)
            ->where('scoring_version', $score->scoring_version)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => $score,
                'details' => $questionScores
            ]
        ]);
    }

    public function updateManualScore(Request $request, Exam $exam, ExamAttempt $attempt, ExamQuestionScore $questionScore)
    {
        $this->authorize('grade', $attempt);

        if ($attempt->exam_id !== $exam->id || $questionScore->attempt_id !== $attempt->id) {
            abort(404);
        }

        $validated = $request->validate([
            'awarded_score' => 'required|numeric|min:0|max:' . $questionScore->max_score,
            'feedback' => 'nullable|string'
        ]);

        return DB::transaction(function () use ($request, $attempt, $questionScore, $validated) {
            // Serialize grading with submit/recalculation using the canonical lock order.
            $lockedAttempt = ExamAttempt::query()
                ->whereKey($attempt->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedAttempt->status === 'IN_PROGRESS') {
                return response()->json(['success' => false, 'message' => 'Cannot grade an active attempt.'], 422);
            }

            $currentPointer = ExamAttemptScoreCurrent::where('attempt_id', $lockedAttempt->id)
                ->lockForUpdate()
                ->first();

            if (!$currentPointer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Current score pointer is missing; grading is blocked pending integrity review.'
                ], 409);
            }

            $attemptScore = ExamAttemptScore::whereKey($currentPointer->current_score_id)
                ->where('attempt_id', $lockedAttempt->id)
                ->where('tenant_id', $lockedAttempt->tenant_id)
                ->where('scoring_version', $currentPointer->scoring_version)
                ->lockForUpdate()
                ->first();

            if (!$attemptScore) {
                return response()->json([
                    'success' => false,
                    'message' => 'Current score pointer target is invalid; grading is blocked pending integrity review.'
                ], 409);
            }

            if ($attemptScore->grading_status === 'FINALIZED') {
                return response()->json(['success' => false, 'message' => 'Cannot modify a finalized score.'], 422);
            }

            if ($questionScore->scoring_version !== $attemptScore->scoring_version) {
                return response()->json(['success' => false, 'message' => 'Cannot update historical score version.'], 422);
            }

            $questionScore = ExamQuestionScore::where('id', $questionScore->id)->lockForUpdate()->first();

            $oldValues = $questionScore->toArray();

            $questionScore->update([
                'awarded_score' => $validated['awarded_score'],
                'feedback' => $validated['feedback'] ?? null,
                'scoring_status' => 'GRADED',
                'grader_id' => $request->user()->id,
                'graded_at' => now()
            ]);

            $this->auditService->log('UPDATE_MANUAL_SCORE', 'ExamQuestionScore', $questionScore->id, $oldValues, $questionScore->toArray());

            $updatedScore = $this->scoringService->scoreAttempt($lockedAttempt, true);

            return response()->json(['success' => true, 'data' => $questionScore->fresh()]);
        });
    }

    public function finalize(Exam $exam, ExamAttempt $attempt)
    {
        $this->authorize('finalize', $attempt);

        if ($attempt->exam_id !== $exam->id) {
            abort(404);
        }

        if ($attempt->status !== 'SUBMITTED') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot finalize an unsubmitted or in-progress attempt.'
            ], 422);
        }

        try {
            return DB::transaction(function () use ($exam, $attempt) {
                $lockedAttempt = ExamAttempt::query()
                    ->whereKey($attempt->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lockedAttempt->exam_id !== $exam->id || $lockedAttempt->status !== 'SUBMITTED') {
                    return response()->json(['success' => false, 'message' => 'Only submitted attempts can be finalized.'], 422);
                }

                $currentPointer = ExamAttemptScoreCurrent::where('attempt_id', $lockedAttempt->id)
                    ->lockForUpdate()
                    ->first();

                if (!$currentPointer) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Current score pointer is missing; finalization is blocked pending integrity review.'
                    ], 409);
                }

                $score = ExamAttemptScore::whereKey($currentPointer->current_score_id)
                    ->where('attempt_id', $lockedAttempt->id)
                    ->where('tenant_id', $lockedAttempt->tenant_id)
                    ->where('scoring_version', $currentPointer->scoring_version)
                    ->lockForUpdate()
                    ->first();

                if (!$score) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Current score pointer target is invalid; finalization is blocked pending integrity review.'
                    ], 409);
                }

                if ($score->grading_status === 'FINALIZED') {
                    return response()->json(['success' => true, 'data' => $score, 'message' => 'Already finalized.']);
                }

                $pendingManual = ExamQuestionScore::where('attempt_id', $lockedAttempt->id)
                    ->where('scoring_version', $score->scoring_version)
                    ->where('scoring_status', 'MANUAL_REVIEW')
                    ->count();

                if ($pendingManual > 0) {
                    return response()->json([
                        'success' => false,
                        'message' => "Cannot finalize. $pendingManual questions still require manual review."
                    ], 422);
                }

                $score->update([
                    'grading_status' => 'FINALIZED',
                    'finalized_at' => now(),
                    'finalized_by' => auth()->id()
                ]);

                // Calculate Curriculum Achievements for the finalized attempt
                $assessment = $exam->assessment;
                if ($assessment) {
                    $student = $lockedAttempt->participant->user;
                    $subject = $assessment->subject;
                    $academicYear = $assessment->academicYear;
                    $semester = $assessment->semester;
                    $rombel = $student->studentProfile?->rombels()->first();

                    if ($student && $subject && $academicYear && $semester) {
                        $achievementService = app(\App\Services\CurriculumAchievementService::class);
                        $achievementService->calculateStudentAchievements(
                            $student,
                            $subject,
                            $academicYear,
                            $semester,
                            $rombel,
                            $score->scoring_version
                        );
                    }
                }

                $this->analyticsCacheService->invalidateExamAnalytics($exam, $lockedAttempt);

                $this->auditService->log('FINALIZE_SCORE', 'ExamAttemptScore', $score->id);

                return response()->json(['success' => true, 'data' => $score->fresh()]);
            });
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function recalculate(Exam $exam, ExamAttempt $attempt)
    {
        $this->authorize('score', $attempt);

        if ($attempt->exam_id !== $exam->id) {
            abort(404);
        }

        return DB::transaction(function () use ($exam, $attempt) {
            $lockedAttempt = ExamAttempt::query()
                ->whereKey($attempt->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedAttempt->exam_id !== $exam->id || $lockedAttempt->status === 'IN_PROGRESS') {
                return response()->json(['success' => false, 'message' => 'Attempt must be closed before recalculation.'], 422);
            }

            $currentPointer = ExamAttemptScoreCurrent::where('attempt_id', $lockedAttempt->id)
                ->lockForUpdate()
                ->first();

            $existingScore = null;
            if ($currentPointer) {
                $existingScore = ExamAttemptScore::whereKey($currentPointer->current_score_id)
                    ->where('attempt_id', $lockedAttempt->id)
                    ->where('tenant_id', $lockedAttempt->tenant_id)
                    ->where('scoring_version', $currentPointer->scoring_version)
                    ->lockForUpdate()
                    ->first();

                if (!$existingScore) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Current score pointer target is invalid; recalculation is blocked pending integrity review.'
                    ], 409);
                }
            } elseif (ExamAttemptScore::where('attempt_id', $lockedAttempt->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Current score pointer is missing for an attempt with score history; recalculation is blocked pending integrity review.'
                ], 409);
            }

            if ($existingScore && $existingScore->grading_status === 'FINALIZED') {
                return response()->json(['success' => false, 'message' => 'Cannot recalculate a finalized score.'], 422);
            }

            $score = $this->scoringService->scoreAttempt($lockedAttempt, true);

            $this->auditService->log('RECALCULATE_SCORE', 'ExamAttemptScore', $score->id);

            return response()->json(['success' => true, 'data' => $score]);
        });
    }

    public function reopen(Request $request, Exam $exam, ExamAttempt $attempt)
    {
        $this->authorize('reopen', $attempt);

        if ($attempt->exam_id !== $exam->id) {
            abort(404);
        }

        $validated = $request->validate([
            'reason' => 'required|string',
        ]);

        $reason = trim($validated['reason']);
        if ($reason === '') {
            return response()->json([
                'success' => false,
                'message' => 'Reason for reopening cannot be empty or whitespace-only.'
            ], 422);
        }

        try {
            $score = $this->scoringService->reopenAttempt($attempt, $reason, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Score reopened successfully.',
                'data' => $score
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }
}
