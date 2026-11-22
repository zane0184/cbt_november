<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamParticipant;
use App\Models\ExamAttemptAnswer;
use App\Models\ExamAttemptScore;
use App\Models\ExamAttemptScoreCurrent;
use App\Models\ExamQuestionScore;
use App\Models\ExamQuestionSnapshot;
use App\Models\User;
use App\Services\Analytics\AnalyticsCacheService;
use Illuminate\Support\Facades\DB;

class ScoringService
{
    protected AuditService $auditService;
    protected AnalyticsCacheService $analyticsCacheService;

    public function __construct(
        ?AuditService $auditService = null,
        ?AnalyticsCacheService $analyticsCacheService = null
    ) {
        $this->auditService = $auditService ?? app(AuditService::class);
        $this->analyticsCacheService = $analyticsCacheService ?? app(AnalyticsCacheService::class);
    }
    /**
     * Return the immutable attempt question assignment without silently repairing it.
     * A malformed, sparse, duplicated, or empty list must never be converted into a
     * smaller valid-looking assignment because that could produce a misleading score.
     *
     * @throws \DomainException
     */
    public function validatedQuestionOrder(ExamAttempt $attempt): array
    {
        $questionOrder = $attempt->question_order;

        if (!is_array($questionOrder) || $questionOrder === [] || !array_is_list($questionOrder)) {
            throw new \DomainException('Scoring blocked: attempt question order is missing or malformed.');
        }

        $seen = [];
        foreach ($questionOrder as $snapshotId) {
            if (!is_string($snapshotId) || $snapshotId === '' || trim($snapshotId) !== $snapshotId) {
                throw new \DomainException('Scoring blocked: attempt question order contains a malformed snapshot ID.');
            }

            if (isset($seen[$snapshotId])) {
                throw new \DomainException('Scoring blocked: attempt question order contains duplicate snapshot IDs.');
            }

            $seen[$snapshotId] = true;
        }

        return $questionOrder;
    }

    public function scoreAttempt(ExamAttempt $attempt, bool $force = false, bool $createNewVersion = false): ExamAttemptScore
    {
        return DB::transaction(function () use ($attempt, $force, $createNewVersion) {
            // Canonical lock order: Exam -> Participant -> Attempt -> ScoreCurrent -> AttemptScore -> QuestionScore.
            $lockedExam = Exam::query()
                ->where('tenant_id', $attempt->tenant_id)
                ->whereKey($attempt->exam_id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedParticipant = ExamParticipant::query()
                ->where('tenant_id', $lockedExam->tenant_id)
                ->where('exam_id', $lockedExam->id)
                ->whereKey($attempt->participant_id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedAttempt = ExamAttempt::query()
                ->where('tenant_id', $lockedExam->tenant_id)
                ->where('exam_id', $lockedExam->id)
                ->where('participant_id', $lockedParticipant->id)
                ->whereKey($attempt->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (!in_array($lockedAttempt->status, ['SUBMITTED', 'EXPIRED'], true)) {
                throw new \DomainException('Scoring blocked: attempt must be terminal before scoring.');
            }

            $lockedAttempt->setRelation('exam', $lockedExam);
            $lockedAttempt->setRelation('participant', $lockedParticipant);
            $attempt = $lockedAttempt;

            $currentPointer = ExamAttemptScoreCurrent::where('attempt_id', $attempt->id)
                ->where('tenant_id', $attempt->tenant_id)
                ->lockForUpdate()
                ->first();

            $existingScore = null;
            if ($currentPointer) {
                $existingScore = ExamAttemptScore::whereKey($currentPointer->current_score_id)
                    ->where('attempt_id', $attempt->id)
                    ->where('tenant_id', $attempt->tenant_id)
                    ->where('scoring_version', $currentPointer->scoring_version)
                    ->lockForUpdate()
                    ->first();

                if (!$existingScore) {
                    throw new \DomainException('Scoring blocked: current score pointer target is invalid.');
                }
            } elseif (ExamAttemptScore::where('attempt_id', $attempt->id)->exists()) {
                throw new \DomainException('Scoring blocked: current score pointer is missing for an attempt with score history.');
            }

            if ($existingScore && $existingScore->grading_status === 'FINALIZED' && !$force && !$createNewVersion) {
                return $existingScore;
            }

            $version = 1;
            $carryForwardFromVersion = null;
            if ($existingScore) {
                if ($createNewVersion) {
                    // Allocate a never-used version across both score histories. MAX is
                    // used only as a sequence allocator, never as the active score source.
                    $latestAttemptVersion = (int) (ExamAttemptScore::where('attempt_id', $attempt->id)
                        ->where('tenant_id', $attempt->tenant_id)
                        ->max('scoring_version') ?? 0);
                    $latestQuestionVersion = (int) (ExamQuestionScore::where('attempt_id', $attempt->id)
                        ->where('tenant_id', $attempt->tenant_id)
                        ->max('scoring_version') ?? 0);
                    $version = max($existingScore->scoring_version, $latestAttemptVersion, $latestQuestionVersion) + 1;

                    // Carry-forward is bound to the validated current pointer target.
                    $carryForwardFromVersion = (int) $existingScore->scoring_version;
                } else {
                    $version = $existingScore->scoring_version;
                }
            }

            $exam = $attempt->exam;
            $questionOrder = $this->validatedQuestionOrder($attempt);

            $snapshots = $exam->snapshots()
                ->where('tenant_id', $attempt->tenant_id)
                ->whereIn('id', $questionOrder)
                ->get();

            if ($snapshots->count() !== count($questionOrder)) {
                throw new \DomainException('Scoring blocked: attempt question order contains an unknown or foreign snapshot.');
            }

            $answers = ExamAttemptAnswer::query()
                ->where('tenant_id', $attempt->tenant_id)
                ->where('exam_id', $attempt->exam_id)
                ->where('exam_attempt_id', $attempt->id)
                ->whereIn('exam_question_snapshot_id', $questionOrder)
                ->get()
                ->keyBy('exam_question_snapshot_id');

            $totalRawScore = 0;
            $totalMaxScore = 0;
            $hasManualReview = false;
            $hasManualGraded = false;

            foreach ($snapshots as $snapshot) {
                $answer = $answers->get($snapshot->id);
                $questionScore = $this->evaluateQuestion(
                    $snapshot,
                    $answer,
                    $attempt,
                    $version,
                    $createNewVersion,
                    $carryForwardFromVersion
                );

                $totalRawScore += $questionScore->awarded_score;
                $totalMaxScore += $questionScore->max_score;

                if ($questionScore->scoring_status === 'MANUAL_REVIEW') {
                    $hasManualReview = true;
                } elseif ($questionScore->scoring_status === 'GRADED') {
                    $hasManualGraded = true;
                }
            }

            $percentage = $totalMaxScore > 0 ? ($totalRawScore / $totalMaxScore) * 100 : 0;

            $gradingStatus = 'AUTO_SCORED';
            if ($hasManualReview) {
                $gradingStatus = 'MANUAL_REVIEW';
            } elseif ($hasManualGraded) {
                $gradingStatus = 'GRADED';
            }

            if ($existingScore && $existingScore->grading_status === 'FINALIZED' && !$createNewVersion) {
                $gradingStatus = 'FINALIZED';
            }

            $attemptScore = ExamAttemptScore::updateOrCreate(
                [
                    'attempt_id' => $attempt->id,
                    'scoring_version' => $version
                ],
                [
                    'tenant_id' => $attempt->tenant_id,
                    'exam_id' => $attempt->exam_id,
                    'student_id' => $attempt->participant->user_id,
                    'raw_score' => $totalRawScore,
                    'max_score' => $totalMaxScore,
                    'percentage' => round($percentage, 2),
                    'grading_status' => $gradingStatus,
                    'calculated_at' => now(),
                    'scoring_version' => $version,
                ]
            );

            ExamAttemptScoreCurrent::updateOrCreate(
                ['attempt_id' => $attempt->id],
                [
                    'tenant_id' => $attempt->tenant_id,
                    'current_score_id' => $attemptScore->id,
                    'scoring_version' => $version,
                ]
            );

            // Score history and its current pointer are audited atomically.
            // A finalized idempotent return above does not create a duplicate event.
            if (!$createNewVersion) {
                $this->auditService->log(
                    $existingScore ? 'RECALCULATE_SCORE' : 'SCORE_ATTEMPT',
                    ExamAttemptScore::class,
                    (string) $attemptScore->id,
                    $existingScore?->only([
                        'attempt_id', 'scoring_version', 'raw_score',
                        'max_score', 'percentage', 'grading_status',
                    ]),
                    [
                        'attempt_id' => $attempt->id,
                        'scoring_version' => $version,
                        'raw_score' => $attemptScore->raw_score,
                        'max_score' => $attemptScore->max_score,
                        'percentage' => $attemptScore->percentage,
                        'grading_status' => $attemptScore->grading_status,
                    ]
                );
            }

            return $attemptScore;
        });
    }

    protected function evaluateQuestion(
        ExamQuestionSnapshot $snapshot,
        ?ExamAttemptAnswer $answer,
        ExamAttempt $attempt,
        int $version = 1,
        bool $createNewVersion = false,
        ?int $carryForwardFromVersion = null
    ): ExamQuestionScore {
        $existingQScore = null;
        if (!$createNewVersion) {
            $existingQScore = ExamQuestionScore::where('attempt_id', $attempt->id)
                ->where('snapshot_id', $snapshot->id)
                ->where('scoring_version', $version)
                ->lockForUpdate()
                ->first();
        } elseif ($carryForwardFromVersion !== null) {
            $previousQScore = ExamQuestionScore::where('attempt_id', $attempt->id)
                ->where('snapshot_id', $snapshot->id)
                ->where('tenant_id', $attempt->tenant_id)
                ->where('scoring_version', $carryForwardFromVersion)
                ->lockForUpdate()
                ->first();

            if ($previousQScore && $previousQScore->scoring_status === 'GRADED') {
                $existingQScore = $previousQScore;
            }
        }

        // Preserve manual grading if GRADED on same version
        if ($existingQScore && $existingQScore->scoring_status === 'GRADED' && !$createNewVersion) {
            return $existingQScore;
        }

        $awardedScore = 0;
        $scoringStatus = 'AUTO_SCORED';
        $rawQuestionType = $snapshot->metadata['question_type'] ?? 'MC';
        $questionType = strtoupper(trim($rawQuestionType));
        $maxScore = (float) $snapshot->points;

        if ($questionType === 'SHORT_ANSWER') {
            throw new \DomainException('Scoring blocked: SHORT_ANSWER is disabled until accepted answers, immutable snapshots, and scoring are implemented consistently.');
        }

        if (!$answer || $answer->answer_state !== 'ANSWERED') {
            // Essay responses, including blank responses, require a Guru decision.
            if ($questionType === 'ESSAY') {
                if ($existingQScore && $existingQScore->scoring_status === 'GRADED') {
                    return $this->persistQuestionScore(
                        $snapshot,
                        $attempt,
                        (float) $existingQScore->awarded_score,
                        'GRADED',
                        $answer,
                        $version
                    );
                }

                return $this->persistQuestionScore($snapshot, $attempt, 0, 'MANUAL_REVIEW', $answer, $version);
            }

            if ($existingQScore && $existingQScore->scoring_status === 'GRADED') {
                return $this->persistQuestionScore(
                    $snapshot,
                    $attempt,
                    (float) $existingQScore->awarded_score,
                    'GRADED',
                    $answer,
                    $version
                );
            }

            return $this->persistQuestionScore($snapshot, $attempt, 0, 'AUTO_SCORED', $answer, $version);
        }

        $payload = $answer->answer_payload ?? [];

        switch ($questionType) {
            case 'MC':
            case 'MULTIPLE_CHOICE':
            case 'TRUE_FALSE':
            case 'TF':
                $awardedScore = $this->evaluateMultipleChoice($snapshot, $payload) ? $maxScore : 0;
                break;

            case 'MULTIPLE_RESPONSE':
                $awardedScore = $this->evaluateMultipleResponse($snapshot, $payload, $maxScore);
                break;

            case 'MATCHING':
                $awardedScore = $this->evaluateMatching($snapshot, $payload, $maxScore);
                break;

            case 'SHORT_ANSWER':
                throw new \DomainException('Scoring blocked: SHORT_ANSWER is disabled until accepted answers, immutable snapshots, and scoring are implemented consistently.');

            case 'ESSAY':
                $awardedScore = $existingQScore ? (float) $existingQScore->awarded_score : 0;
                $scoringStatus = ($existingQScore && $existingQScore->scoring_status === 'GRADED') ? 'GRADED' : 'MANUAL_REVIEW';
                break;

            default:
                throw new \DomainException('Scoring blocked: unsupported question type in immutable exam snapshot.');
        }

        return $this->persistQuestionScore($snapshot, $attempt, $awardedScore, $scoringStatus, $answer, $version);
    }

    protected function evaluateMultipleChoice(ExamQuestionSnapshot $snapshot, array $payload): bool
    {
        $selectedOptionId = $payload['selected_option_id'] ?? null;
        if (!$selectedOptionId) return false;

        $options = $snapshot->snapshot_options ?? [];
        foreach ($options as $option) {
            if ($option['id'] === $selectedOptionId && ($option['is_correct'] ?? false)) {
                return true;
            }
        }

        return false;
    }

    protected function evaluateMultipleResponse(ExamQuestionSnapshot $snapshot, array $payload, float $maxScore): float
    {
        $selectedIds = array_unique($payload['selected_option_ids'] ?? []);
        if (empty($selectedIds)) return 0;

        $options = $snapshot->snapshot_options ?? [];
        $correctIds = [];
        foreach ($options as $option) {
            if ($option['is_correct'] ?? false) {
                $correctIds[] = $option['id'];
            }
        }

        $correctCount = count($correctIds);
        if ($correctCount === 0) return 0;

        $selectedCorrect = array_intersect($selectedIds, $correctIds);
        $selectedIncorrect = array_diff($selectedIds, $correctIds);

        if (!empty($selectedIncorrect)) {
            return 0;
        }

        return (count($selectedCorrect) / $correctCount) * $maxScore;
    }

    protected function evaluateMatching(ExamQuestionSnapshot $snapshot, array $payload, float $maxScore): float
    {
        $matches = $payload['matches'] ?? [];
        if (empty($matches)) return 0;

        $uniqueStudentMatches = [];
        foreach ($matches as $match) {
            $leftId = $match['left_id'] ?? null;
            $rightId = $match['right_id'] ?? null;
            if ($leftId && $rightId) {
                $uniqueStudentMatches[$leftId] = $rightId;
            }
        }

        $options = $snapshot->snapshot_options ?? [];
        $leftToKeyMap = [];
        $keyToRightMap = [];

        foreach ($options as $opt) {
            if (!empty($opt['option_key'])) {
                $leftToKeyMap[$opt['id']] = $opt['option_key'];
            }
            if (!empty($opt['matching_key'])) {
                $keyToRightMap[$opt['matching_key']] = $opt['id'];
            }
        }

        $totalPairs = count($leftToKeyMap);
        if ($totalPairs === 0) return 0;

        $correctCount = 0;
        foreach ($uniqueStudentMatches as $leftId => $rightId) {
            $expectedKey = $leftToKeyMap[$leftId] ?? null;
            if ($expectedKey && ($keyToRightMap[$expectedKey] ?? null) === $rightId) {
                $correctCount++;
            }
        }

        return ($correctCount / $totalPairs) * $maxScore;
    }

    protected function evaluateShortAnswer(ExamQuestionSnapshot $snapshot, array $payload): bool
    {
        $text = $payload['text'] ?? '';
        if (trim($text) === '') return false;

        $normalizedInput = strtolower(preg_replace('/\s+/', ' ', trim($text)));

        $acceptedAnswers = $snapshot->metadata['accepted_answers'] ?? [];

        if (empty($acceptedAnswers)) {
            $options = $snapshot->snapshot_options ?? [];
            foreach ($options as $opt) {
                if ($opt['is_correct'] ?? false) {
                    $acceptedAnswers[] = $opt['content'];
                }
            }
        }

        foreach ($acceptedAnswers as $accepted) {
            $normalizedAccepted = strtolower(preg_replace('/\s+/', ' ', trim($accepted)));
            if ($normalizedInput === $normalizedAccepted) {
                return true;
            }
        }

        return false;
    }

    protected function persistQuestionScore(
        ExamQuestionSnapshot $snapshot,
        ExamAttempt $attempt,
        float $awardedScore,
        string $status,
        ?ExamAttemptAnswer $answer,
        int $version = 1
    ): ExamQuestionScore {
        return ExamQuestionScore::updateOrCreate(
            [
                'attempt_id' => $attempt->id,
                'snapshot_id' => $snapshot->id,
                'scoring_version' => $version,
            ],
            [
                'tenant_id' => $attempt->tenant_id,
                'exam_id' => $attempt->exam_id,
                'student_id' => $attempt->participant->user_id,
                'question_type' => $snapshot->metadata['question_type'] ?? 'MC',
                'awarded_score' => $awardedScore,
                'max_score' => (float) $snapshot->points,
                'scoring_status' => $status,
                'evaluated_answer_snapshot' => $answer ? $answer->answer_payload : null,
                'evaluated_at' => now(),
                'scoring_rule_version' => 1,
                'scoring_version' => $version,
            ]
        );
    }

    public function reopenAttempt(ExamAttempt $attempt, string $reason, ?User $actor = null): ExamAttemptScore
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw new \InvalidArgumentException('Reason for reopening cannot be empty or whitespace-only.');
        }

        return DB::transaction(function () use ($attempt, $reason, $actor) {
            // Reopen follows the same parent-first lifecycle lock order as submit.
            $lockedExam = Exam::query()
                ->where('tenant_id', $attempt->tenant_id)
                ->whereKey($attempt->exam_id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedParticipant = ExamParticipant::query()
                ->where('tenant_id', $lockedExam->tenant_id)
                ->where('exam_id', $lockedExam->id)
                ->whereKey($attempt->participant_id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedAttempt = ExamAttempt::query()
                ->where('tenant_id', $lockedExam->tenant_id)
                ->where('exam_id', $lockedExam->id)
                ->where('participant_id', $lockedParticipant->id)
                ->whereKey($attempt->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (!in_array($lockedAttempt->status, ['SUBMITTED', 'EXPIRED'], true)) {
                throw new \InvalidArgumentException('Only submitted or expired attempts can have a finalized score reopened.');
            }

            $lockedAttempt->setRelation('exam', $lockedExam);
            $lockedAttempt->setRelation('participant', $lockedParticipant);

            $currentPointer = ExamAttemptScoreCurrent::where('attempt_id', $lockedAttempt->id)
                ->where('tenant_id', $lockedAttempt->tenant_id)
                ->lockForUpdate()
                ->first();

            if (!$currentPointer) {
                throw new \InvalidArgumentException('Current score pointer is missing; reopening is blocked pending integrity review.');
            }

            $existingScore = ExamAttemptScore::whereKey($currentPointer->current_score_id)
                ->where('attempt_id', $lockedAttempt->id)
                ->where('tenant_id', $lockedAttempt->tenant_id)
                ->where('scoring_version', $currentPointer->scoring_version)
                ->lockForUpdate()
                ->first();

            if (!$existingScore || $existingScore->grading_status !== 'FINALIZED') {
                throw new \InvalidArgumentException('Only finalized results can be reopened.');
            }

            $oldValues = [
                'score_id' => $existingScore->id,
                'scoring_version' => $existingScore->scoring_version,
                'grading_status' => $existingScore->grading_status,
                'raw_score' => (float) $existingScore->raw_score,
                'max_score' => (float) $existingScore->max_score,
                'percentage' => (float) $existingScore->percentage,
                'finalized_at' => $existingScore->finalized_at?->toIso8601String(),
                'finalized_by' => $existingScore->finalized_by,
            ];

            $newScore = $this->scoreAttempt($lockedAttempt, true, true);

            $this->analyticsCacheService->invalidateExamAnalytics($lockedAttempt->exam, $lockedAttempt);

            $newValues = [
                'score_id' => $newScore->id,
                'scoring_version' => $newScore->scoring_version,
                'grading_status' => $newScore->grading_status,
                'raw_score' => (float) $newScore->raw_score,
                'max_score' => (float) $newScore->max_score,
                'percentage' => (float) $newScore->percentage,
                'reason' => $reason,
            ];

            $this->auditService->log('REOPEN_SCORE', 'ExamAttemptScore', $newScore->id, $oldValues, $newValues);

            return $newScore;
        });
    }
}
