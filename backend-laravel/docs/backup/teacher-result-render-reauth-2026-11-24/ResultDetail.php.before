<?php

namespace App\Livewire\Teacher;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamQuestionScore;
use App\Models\ExamAttemptScore;
use App\Services\ScoringService;
use App\Services\Analytics\AnalyticsCacheService;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

class ResultDetail extends Component
{
    public Exam $exam;
    public ExamAttempt $attempt;
    public $editingScoreId = null;
    public $manualScore = 0;
    public $feedbackText = '';
    public $showReopenModal = false;
    public $reopenReason = '';

    public function mount(Exam $exam, ExamAttempt $attempt)
    {
        $this->exam = $exam;
        $this->attempt = $attempt->load(['participant.user', 'score', 'questionScores.snapshot']);

        if ($this->attempt->exam_id !== $this->exam->id) {
            abort(404);
        }

        $this->authorize('view', $this->attempt);
    }

    public function editScore($scoreId)
    {
        $this->authorize('grade', $this->attempt);
        abort_unless($this->attempt->status === 'SUBMITTED', 409, 'Hanya attempt yang sudah dikirim yang dapat dinilai.');

        $pointer = \App\Models\ExamAttemptScoreCurrent::query()
            ->where('attempt_id', $this->attempt->id)
            ->where('tenant_id', $this->attempt->tenant_id)
            ->first();
        abort_unless($pointer, 409, 'Current score pointer tidak tersedia.');

        $qScore = ExamQuestionScore::query()
            ->whereKey($scoreId)
            ->where('attempt_id', $this->attempt->id)
            ->where('scoring_version', $pointer->scoring_version)
            ->first();
        abort_unless($qScore, 404, 'Butir nilai tidak ditemukan pada versi skor aktif.');

        $this->editingScoreId = $scoreId;
        $this->manualScore = (float) $qScore->awarded_score;
        $this->feedbackText = $qScore->feedback;
    }

    public function cancelEdit()
    {
        $this->editingScoreId = null;
    }

    public function saveScore()
    {
        $this->authorize('grade', $this->attempt);

        try {
            DB::transaction(function () {
                // Canonical grading order: Attempt -> current pointer -> score -> question score.
                $lockedAttempt = ExamAttempt::query()
                    ->whereKey($this->attempt->id)
                    ->where('exam_id', $this->exam->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->authorize('grade', $lockedAttempt);

                if ($lockedAttempt->status !== 'SUBMITTED') {
                    throw new \InvalidArgumentException('Only submitted attempts can be graded.');
                }

                $currentPointer = \App\Models\ExamAttemptScoreCurrent::where('attempt_id', $lockedAttempt->id)
                    ->lockForUpdate()
                    ->first();

                if (!$currentPointer) {
                    throw new \InvalidArgumentException('Current score pointer is missing; grading is blocked pending integrity review.');
                }

                $attemptScore = ExamAttemptScore::whereKey($currentPointer->current_score_id)
                    ->where('attempt_id', $lockedAttempt->id)
                    ->where('tenant_id', $lockedAttempt->tenant_id)
                    ->where('scoring_version', $currentPointer->scoring_version)
                    ->lockForUpdate()
                    ->first();

                if (!$attemptScore) {
                    throw new \InvalidArgumentException('Current score pointer target is invalid; grading is blocked pending integrity review.');
                }

                if ($attemptScore->grading_status === 'FINALIZED') {
                    throw new \InvalidArgumentException('Cannot modify a finalized score.');
                }

                $qScore = ExamQuestionScore::where('id', $this->editingScoreId)
                    ->where('attempt_id', $lockedAttempt->id)
                    ->where('scoring_version', $attemptScore->scoring_version)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($this->manualScore < 0 || $this->manualScore > $qScore->max_score) {
                    throw new \InvalidArgumentException('Score must be between 0 and ' . $qScore->max_score . '.');
                }

                $qScore->update([
                    'awarded_score' => $this->manualScore,
                    'feedback' => $this->feedbackText,
                    'scoring_status' => 'GRADED',
                    'grader_id' => auth()->id(),
                    'graded_at' => now()
                ]);

                app(ScoringService::class)->scoreAttempt($lockedAttempt, true);
            });

            $this->editingScoreId = null;
            $this->attempt->refresh();
            $this->dispatch('notify', ['type' => 'success', 'message' => 'Question score updated.']);
        } catch (\Throwable $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function finalize()
    {
        $this->authorize('finalize', $this->attempt);

        try {
            DB::transaction(function () {
                // Re-read and re-authorize after acquiring the Attempt lifecycle lock.
                $lockedAttempt = ExamAttempt::query()
                    ->whereKey($this->attempt->id)
                    ->where('exam_id', $this->exam->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->authorize('finalize', $lockedAttempt);

                if ($lockedAttempt->status !== 'SUBMITTED') {
                    throw new \InvalidArgumentException('Only submitted attempts can be finalized.');
                }

                $currentPointer = \App\Models\ExamAttemptScoreCurrent::where('attempt_id', $lockedAttempt->id)
                    ->lockForUpdate()
                    ->first();

                if (!$currentPointer) {
                    throw new \InvalidArgumentException('Current score pointer is missing; finalization is blocked pending integrity review.');
                }

                $score = ExamAttemptScore::whereKey($currentPointer->current_score_id)
                    ->where('attempt_id', $lockedAttempt->id)
                    ->where('tenant_id', $lockedAttempt->tenant_id)
                    ->where('scoring_version', $currentPointer->scoring_version)
                    ->lockForUpdate()
                    ->first();

                if (!$score) {
                    throw new \InvalidArgumentException('Current score pointer target is invalid; finalization is blocked pending integrity review.');
                }

                if ($score->grading_status === 'FINALIZED') {
                    return;
                }

                $pendingManual = ExamQuestionScore::where('attempt_id', $lockedAttempt->id)
                    ->where('scoring_version', $score->scoring_version)
                    ->where('scoring_status', 'MANUAL_REVIEW')
                    ->count();

                if ($pendingManual > 0) {
                    throw new \InvalidArgumentException("Cannot finalize. $pendingManual questions still require manual review.");
                }

                $score->update([
                    'grading_status' => 'FINALIZED',
                    'finalized_at' => now(),
                    'finalized_by' => auth()->id()
                ]);
            });

            app(AnalyticsCacheService::class)->invalidateExamAnalytics($this->exam, $this->attempt);
            $this->attempt->refresh();
            $this->dispatch('notify', ['type' => 'success', 'message' => 'Result finalized successfully.']);
        } catch (\Throwable $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function openReopenModal()
    {
        $this->authorize('reopen', $this->attempt);
        $this->showReopenModal = true;
        $this->reopenReason = '';
    }

    public function closeReopenModal()
    {
        $this->showReopenModal = false;
        $this->reopenReason = '';
    }

    public function reopen()
    {
        $user = auth()->user();
        if (!$user) {
            abort(403);
        }
        $this->authorize('reopen', $this->attempt);

        $reason = trim($this->reopenReason);
        if ($reason === '') {
            $this->addError('reopenReason', 'Reason for reopening is required.');
            return;
        }

        try {
            app(ScoringService::class)->reopenAttempt($this->attempt, $reason, $user);
            $this->showReopenModal = false;
            $this->reopenReason = '';
            $this->attempt->refresh();
            $this->dispatch('notify', ['type' => 'success', 'message' => 'Result reopened successfully.']);
        } catch (\Exception $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        $currentVersion = $this->attempt->score ? $this->attempt->score->scoring_version : 1;
        $questionScores = $this->attempt->questionScores
            ->where('scoring_version', $currentVersion)
            ->sortBy('snapshot.sequence');

        return view('livewire.teacher.result-detail', [
            'questionScores' => $questionScores
        ]);
    }
}
