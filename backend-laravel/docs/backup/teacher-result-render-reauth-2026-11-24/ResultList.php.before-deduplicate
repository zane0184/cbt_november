<?php

namespace App\Livewire\Teacher;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamParticipant;
use App\Models\Rombel;
use App\Services\Analytics\AnalyticsCacheService;
use App\Services\AuditService;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ResultList extends Component
{
    use WithPagination;

    public Exam $exam;
    public $search = '';
    public $status = '';
    public $gradingStatus = '';
    public $rombelId = '';
    public $showFinalizeAllModal = false;
    public $showPublishModal = false;
    public $preflight = [];
    public $publishPreflight = [];

    public function mount(Exam $exam)
    {
        $this->authorize('viewExamResults', $exam);
        $this->exam = $exam;
    }

    public function startFinalizeAll()
    {
        $this->authorize('viewExamResults', $this->exam);
        $this->preflight = [
            'total' => ExamAttempt::where('exam_id', $this->exam->id)->count(),
            'eligible' => ExamAttempt::where('exam_id', $this->exam->id)
                ->where('status', 'SUBMITTED')
                ->whereHas('score', function ($q) {
                    $q->where('grading_status', '!=', 'FINALIZED')
                      ->where('grading_status', '!=', 'MANUAL_REVIEW');
                })
                ->count(),
            'pending_review' => ExamAttempt::where('exam_id', $this->exam->id)
                ->whereHas('score', function ($q) {
                    $q->where('grading_status', 'MANUAL_REVIEW');
                })
                ->count(),
            'already_finalized' => ExamAttempt::where('exam_id', $this->exam->id)
                ->whereHas('score', function ($q) {
                    $q->where('grading_status', 'FINALIZED');
                })
                ->count(),
            'in_progress' => ExamAttempt::where('exam_id', $this->exam->id)
                ->where('status', 'IN_PROGRESS')
                ->count(),
        ];
        $this->showFinalizeAllModal = true;
    }

    public function confirmFinalizeAll()
    {
        $this->authorize('viewExamResults', $this->exam);

        $eligibleAttemptIds = ExamAttempt::where('exam_id', $this->exam->id)
            ->where('status', 'SUBMITTED')
            ->whereHas('score', function ($q) {
                $q->whereNotIn('grading_status', ['FINALIZED', 'MANUAL_REVIEW']);
            })
            ->orderBy('id')
            ->pluck('id');

        $count = 0;
        $finalizedAttemptIds = [];
        \Illuminate\Support\Facades\DB::transaction(function () use ($eligibleAttemptIds, &$count, &$finalizedAttemptIds) {
            foreach ($eligibleAttemptIds as $attemptId) {
                // Lock Attempt before any score/pointer row to match API grading and submit.
                $attempt = ExamAttempt::query()
                    ->whereKey($attemptId)
                    ->where('exam_id', $this->exam->id)
                    ->lockForUpdate()
                    ->first();

                if (!$attempt || $attempt->status !== 'SUBMITTED') {
                    continue;
                }

                $this->authorize('finalize', $attempt);

                $pointer = \App\Models\ExamAttemptScoreCurrent::where('attempt_id', $attempt->id)
                    ->lockForUpdate()
                    ->first();

                // A missing pointer is an integrity anomaly. Do not silently
                // promote the highest historical scoring_version to current.
                if (!$pointer) {
                    report(new \RuntimeException(
                        'Current score pointer missing for attempt ' . $attempt->id
                    ));
                    continue;
                }

                $score = \App\Models\ExamAttemptScore::whereKey($pointer->current_score_id)
                    ->where('attempt_id', $attempt->id)
                    ->where('tenant_id', $attempt->tenant_id)
                    ->where('scoring_version', $pointer->scoring_version)
                    ->lockForUpdate()
                    ->first();

                if (!$score) {
                    report(new \RuntimeException(
                        'Current score pointer target is invalid for attempt ' . $attempt->id
                    ));
                    continue;
                }

                if ($score->grading_status === 'FINALIZED') {
                    continue;
                }

                $pendingManual = \App\Models\ExamQuestionScore::where('attempt_id', $attempt->id)
                    ->where('scoring_version', $score->scoring_version)
                    ->where('scoring_status', 'MANUAL_REVIEW')
                    ->count();

                if ($pendingManual === 0) {
                    $score->update([
                        'grading_status' => 'FINALIZED',
                        'finalized_at' => now(),
                        'finalized_by' => auth()->id()
                    ]);
                    $count++;
                    $finalizedAttemptIds[] = $attempt->id;
                }
            }
        });

        if ($count > 0) {
            $cacheService = app(AnalyticsCacheService::class);
            $cacheService->invalidateExamAnalytics($this->exam);
            $finalizedAttempts = ExamAttempt::with('participant.user')
                ->whereIn('id', $finalizedAttemptIds)
                ->get();
            foreach ($finalizedAttempts as $finalizedAttempt) {
                $cacheService->invalidateRombelCacheForAttempt($this->exam, $finalizedAttempt);
            }
        }

        $this->showFinalizeAllModal = false;
        $this->dispatch('notify', ['type' => 'success', 'message' => "$count attempts finalized."]);
    }

    public function startPublishModal()
    {
        $this->authorize('publishResults', $this->exam);

        $total = ExamAttempt::where('exam_id', $this->exam->id)->count();
        $finalized = ExamAttempt::where('exam_id', $this->exam->id)
            ->whereHas('score', function ($q) {
                $q->where('grading_status', 'FINALIZED');
            })
            ->count();
        $pendingReview = ExamAttempt::where('exam_id', $this->exam->id)
            ->whereHas('score', function ($q) {
                $q->where('grading_status', 'MANUAL_REVIEW');
            })
            ->count();
        $submitted = ExamAttempt::where('exam_id', $this->exam->id)
            ->where('status', 'SUBMITTED')
            ->count();

        $this->publishPreflight = [
            'is_published' => (bool) $this->exam->show_result,
            'total' => $total,
            'submitted' => $submitted,
            'finalized' => $finalized,
            'pending_review' => $pendingReview,
            'unfinalized_submitted' => $submitted - $finalized,
        ];

        $this->showPublishModal = true;
    }

    public function toggleResultPublication()
    {
        $this->authorize('publishResults', $this->exam);

        $newStatus = DB::transaction(function () {
            // Serialize publish/unpublish transitions and re-authorize the locked row.
            $lockedExam = Exam::query()->whereKey($this->exam->id)->lockForUpdate()->firstOrFail();
            $this->authorize('publishResults', $lockedExam);
            $previous = (bool) $lockedExam->show_result;
            $next = !$previous;
            $lockedExam->update(['show_result' => $next]);

            app(AuditService::class)->log(
                $next ? 'PUBLISH_EXAM_RESULTS' : 'UNPUBLISH_EXAM_RESULTS',
                'Exam',
                $lockedExam->id,
                ['show_result' => $previous],
                ['show_result' => $next]
            );

            $this->exam = $lockedExam->fresh();
            return $next;
        });

        app(AnalyticsCacheService::class)->invalidateExamAnalytics($this->exam);

        $this->showPublishModal = false;
        $message = $newStatus
            ? 'Hasil ujian berhasil dipublikasikan ke siswa.'
            : 'Publikasi hasil ujian berhasil ditarik kembali.';

        $this->dispatch('notify', ['type' => 'success', 'message' => $message]);
        session()->flash('message', $message);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingGradingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingRombelId(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        // Re-check persisted Guru role, permission and assignment on every Livewire request.
        $this->authorize('viewExamResults', $this->exam);

        // Re-check persisted Guru role, permission and assignment on every Livewire request.
        $this->authorize('viewExamResults', $this->exam);

        $participantUserIds = ExamParticipant::query()
            ->where('exam_id', $this->exam->id)
            ->pluck('user_id');

        $rombels = Rombel::query()
            ->where('academic_year_id', $this->exam->assessment?->academic_year_id)
            ->whereHas('students', function ($studentQuery) use ($participantUserIds) {
                $studentQuery->whereHas('user', function ($userQuery) use ($participantUserIds) {
                    $userQuery->whereIn('id', $participantUserIds);
                });
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $query = ExamAttempt::where('exam_id', $this->exam->id)
            ->with(['participant.user', 'score'])
            ->when($this->search, function ($q) {
                $q->whereHas('participant.user', function ($sq) {
                    $sq->where('name', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->status, function ($q) {
                $q->where('status', $this->status);
            })
            ->when($this->gradingStatus, function ($q) {
                if ($this->gradingStatus === 'NOT_SCORED') {
                    $q->whereDoesntHave('score');
                    return;
                }

                $q->whereHas('score', function ($sq) {
                    $sq->where('grading_status', $this->gradingStatus);
                });
            })
            ->when($this->rombelId, function ($q) {
                $q->whereHas('participant.user.studentProfile.rombels', function ($rombelQuery) {
                    $rombelQuery->where('rombels.id', $this->rombelId)
                        ->where('rombels.tenant_id', $this->exam->tenant_id)
                        ->where('rombels.academic_year_id', $this->exam->assessment?->academic_year_id);
                });
            });

        $attempts = $query->paginate(20);

        return view('livewire.teacher.result-list', [
            'attempts' => $attempts,
            'rombels' => $rombels,
        ]);
    }
}
