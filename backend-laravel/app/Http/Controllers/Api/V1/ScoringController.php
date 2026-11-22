<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamParticipant;
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
        if ($attempt->exam_id !== $exam->id || $attempt->tenant_id !== $exam->tenant_id) {
            abort(404);
        }

        $this->authorize('score', $attempt);

        return DB::transaction(function () use ($exam, $attempt) {
            [$lockedExam, $participant, $lockedAttempt] = $this->lockAttemptContext($exam, $attempt);
            $this->authorize('score', $lockedAttempt);

            if (!in_array($lockedAttempt->status, ['SUBMITTED', 'EXPIRED'], true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Attempt must be closed before scoring.'
                ], 422);
            }

            try {
                $score = $this->scoringService->scoreAttempt($lockedAttempt);
            } catch (\DomainException $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 409);
            }

            // ScoringService owns the atomic score history/current-pointer audit event.
            return response()->json(['success' => true, 'data' => $score]);
        });
    }

    public function show(Request $request, Exam $exam, ExamAttempt $attempt)
    {
        if ($attempt->exam_id !== $exam->id || $attempt->tenant_id !== $exam->tenant_id) {
            abort(404);
        }

        $this->authorize('viewScore', $attempt);

        $currentPointer = ExamAttemptScoreCurrent::where('attempt_id', $attempt->id)
            ->where('tenant_id', $attempt->tenant_id)
            ->first();
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
        if ($request->user()->hasExactRole('SISWA')) {
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
        if ($attempt->exam_id !== $exam->id
            || $attempt->tenant_id !== $exam->tenant_id
            || $questionScore->attempt_id !== $attempt->id
            || $questionScore->tenant_id !== $attempt->tenant_id
            || $questionScore->exam_id !== $exam->id) {
            abort(404);
        }

        $this->authorize('grade', $attempt);
        $validated = $request->validate([
            'awarded_score' => 'required|numeric|min:0',
            'feedback' => 'nullable|string'
        ]);

        return DB::transaction(function () use ($request, $exam, $attempt, $questionScore, $validated) {
            [$lockedExam, $participant, $lockedAttempt] = $this->lockAttemptContext($exam, $attempt);
            $this->authorize('grade', $lockedAttempt);

            if (!in_array($lockedAttempt->status, ['SUBMITTED', 'EXPIRED'], true)) {
                return response()->json(['success' => false, 'message' => 'Only submitted or expired attempts can be graded.'], 422);
            }

            $currentPointer = ExamAttemptScoreCurrent::query()
                ->where('tenant_id', $lockedAttempt->tenant_id)
                ->where('attempt_id', $lockedAttempt->id)
                ->lockForUpdate()
                ->first();

            if (!$currentPointer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Current score pointer is missing; grading is blocked pending integrity review.'
                ], 409);
            }

            $attemptScore = ExamAttemptScore::query()
                ->whereKey($currentPointer->current_score_id)
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

            $lockedQuestionScore = ExamQuestionScore::query()
                ->where('tenant_id', $lockedAttempt->tenant_id)
                ->where('exam_id', $lockedExam->id)
                ->where('attempt_id', $lockedAttempt->id)
                ->where('scoring_version', $attemptScore->scoring_version)
                ->whereKey($questionScore->id)
                ->lockForUpdate()
                ->first();

            if (!$lockedQuestionScore || $lockedQuestionScore->question_type !== 'ESSAY'
                || !in_array($lockedQuestionScore->scoring_status, ['MANUAL_REVIEW', 'GRADED'], true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Manual grading is available only for ESSAY questions awaiting or receiving Guru review.'
                ], 422);
            }

            if ((float) $validated['awarded_score'] > (float) $lockedQuestionScore->max_score) {
                return response()->json(['success' => false, 'message' => 'Awarded score exceeds the immutable snapshot maximum.'], 422);
            }

            $oldValues = $lockedQuestionScore->toArray();
            $lockedQuestionScore->update([
                'awarded_score' => $validated['awarded_score'],
                'feedback' => $validated['feedback'] ?? null,
                'scoring_status' => 'GRADED',
                'grader_id' => $request->user()->id,
                'graded_at' => now()
            ]);

            $this->auditService->log(
                'UPDATE_MANUAL_SCORE',
                'ExamQuestionScore',
                $lockedQuestionScore->id,
                $oldValues,
                $lockedQuestionScore->toArray()
            );

            $this->scoringService->scoreAttempt($lockedAttempt, true);

            return response()->json(['success' => true, 'data' => $lockedQuestionScore->fresh()]);
        });
    }

    public function finalize(Exam $exam, ExamAttempt $attempt)
    {
        if ($attempt->exam_id !== $exam->id || $attempt->tenant_id !== $exam->tenant_id) {
            abort(404);
        }

        $this->authorize('finalize', $attempt);

        try {
            return DB::transaction(function () use ($exam, $attempt) {
                [$lockedExam, $participant, $lockedAttempt] = $this->lockAttemptContext($exam, $attempt);
                $this->authorize('finalize', $lockedAttempt);

                if (!in_array($lockedAttempt->status, ['SUBMITTED', 'EXPIRED'], true)) {
                    return response()->json(['success' => false, 'message' => 'Only submitted or expired attempts can be finalized.'], 422);
                }

                $currentPointer = ExamAttemptScoreCurrent::query()
                    ->where('tenant_id', $lockedAttempt->tenant_id)
                    ->where('attempt_id', $lockedAttempt->id)
                    ->lockForUpdate()
                    ->first();

                if (!$currentPointer) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Current score pointer is missing; finalization is blocked pending integrity review.'
                    ], 409);
                }

                $score = ExamAttemptScore::query()
                    ->whereKey($currentPointer->current_score_id)
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
                    return response()->json(['success' => true, 'data' => $score, 'message' => 'Already finalized.', 'idempotent' => true]);
                }

                $pendingManual = ExamQuestionScore::query()
                    ->where('tenant_id', $lockedAttempt->tenant_id)
                    ->where('exam_id', $lockedExam->id)
                    ->where('attempt_id', $lockedAttempt->id)
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

                $assessment = $lockedExam->assessment;
                $student = $participant->user;
                if ($assessment && $student) {
                    $subject = $assessment->subject;
                    $academicYear = $assessment->academicYear;
                    $semester = $assessment->semester;
                    $rombel = $student->studentProfile?->rombels()->first();

                    if ($subject && $academicYear && $semester) {
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

                $this->analyticsCacheService->invalidateExamAnalytics($lockedExam, $lockedAttempt);
                $this->auditService->log('FINALIZE_SCORE', 'ExamAttemptScore', $score->id);

                return response()->json(['success' => true, 'data' => $score->fresh()]);
            });
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function recalculate(Exam $exam, ExamAttempt $attempt)
    {
        if ($attempt->exam_id !== $exam->id || $attempt->tenant_id !== $exam->tenant_id) {
            abort(404);
        }

        $this->authorize('score', $attempt);

        return DB::transaction(function () use ($exam, $attempt) {
            [$lockedExam, $participant, $lockedAttempt] = $this->lockAttemptContext($exam, $attempt);
            $this->authorize('score', $lockedAttempt);

            if (!in_array($lockedAttempt->status, ['SUBMITTED', 'EXPIRED'], true)) {
                return response()->json(['success' => false, 'message' => 'Attempt must be submitted or expired before recalculation.'], 422);
            }

            $currentPointer = ExamAttemptScoreCurrent::query()
                ->where('tenant_id', $lockedAttempt->tenant_id)
                ->where('attempt_id', $lockedAttempt->id)
                ->lockForUpdate()
                ->first();

            $existingScore = null;
            if ($currentPointer) {
                $existingScore = ExamAttemptScore::query()
                    ->whereKey($currentPointer->current_score_id)
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
            } elseif (ExamAttemptScore::where('tenant_id', $lockedAttempt->tenant_id)->where('attempt_id', $lockedAttempt->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Current score pointer is missing for an attempt with score history; recalculation is blocked pending integrity review.'
                ], 409);
            }

            if ($existingScore && $existingScore->grading_status === 'FINALIZED') {
                return response()->json(['success' => false, 'message' => 'Cannot recalculate a finalized score.'], 422);
            }

            try {
                $score = $this->scoringService->scoreAttempt($lockedAttempt, true);
            } catch (\DomainException $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 409);
            }

            // ScoringService owns the score history/current-pointer audit event.
            return response()->json(['success' => true, 'data' => $score]);
        });
    }

    public function reopen(Request $request, Exam $exam, ExamAttempt $attempt)
    {
        if ($attempt->exam_id !== $exam->id || $attempt->tenant_id !== $exam->tenant_id) {
            abort(404);
        }

        $this->authorize('reopen', $attempt);
        $validated = $request->validate(['reason' => 'required|string']);
        $reason = trim($validated['reason']);

        if ($reason === '') {
            return response()->json([
                'success' => false,
                'message' => 'Reason for reopening cannot be empty or whitespace-only.'
            ], 422);
        }

        try {
            return DB::transaction(function () use ($request, $exam, $attempt, $reason) {
                [$lockedExam, $participant, $lockedAttempt] = $this->lockAttemptContext($exam, $attempt);
                $this->authorize('reopen', $lockedAttempt);

                $score = $this->scoringService->reopenAttempt($lockedAttempt, $reason, $request->user());

                return response()->json([
                    'success' => true,
                    'message' => 'Score reopened successfully.',
                    'data' => $score
                ]);
            });
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 409);
        }
    }

    /**
     * Acquire lifecycle parent rows in the shared order before any score row:
     * Exam -> Participant -> Attempt -> ScoreCurrent -> AttemptScore -> QuestionScore.
     */
    private function lockAttemptContext(Exam $exam, ExamAttempt $attempt): array
    {
        $lockedExam = Exam::query()
            ->where('tenant_id', $exam->tenant_id)
            ->whereKey($exam->id)
            ->lockForUpdate()
            ->firstOrFail();

        $participant = ExamParticipant::query()
            ->where('tenant_id', $lockedExam->tenant_id)
            ->where('exam_id', $lockedExam->id)
            ->whereKey($attempt->participant_id)
            ->lockForUpdate()
            ->firstOrFail();

        $lockedAttempt = ExamAttempt::query()
            ->where('tenant_id', $lockedExam->tenant_id)
            ->where('exam_id', $lockedExam->id)
            ->where('participant_id', $participant->id)
            ->whereKey($attempt->id)
            ->lockForUpdate()
            ->firstOrFail();

        return [$lockedExam, $participant, $lockedAttempt];
    }
}
