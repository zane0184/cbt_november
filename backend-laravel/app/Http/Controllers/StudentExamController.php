<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamParticipant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class StudentExamController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($this->isSingleStudentIdentity($user), 403);

        $now = now();
        $participants = ExamParticipant::query()
            ->where('tenant_id', $user->tenant_id)
            ->where('user_id', $user->id)
            ->with(['exam', 'attempts'])
            ->get()
            ->filter(fn (ExamParticipant $participant) => $participant->exam !== null)
            ->map(function (ExamParticipant $participant) use ($now) {
                $exam = $participant->exam;
                $attempts = $participant->attempts;
                $eligible = (!$participant->eligible_from || $now->gte($participant->eligible_from))
                    && (!$participant->eligible_until || $now->lt($participant->eligible_until));
                $activeAttempt = $eligible ? $attempts->first(function ($attempt) use ($now) {
                    return $attempt->status === 'IN_PROGRESS'
                        && $attempt->expires_at
                        && $attempt->expires_at->gt($now);
                }) : null;
                $latestAttempt = $attempts->sortByDesc('attempt_number')->first();
                if ($latestAttempt) {
                    $latestAttempt->loadMissing('scoreCurrentPointer.currentScore');
                }
                $currentScore = $latestAttempt?->resolvedCurrentScore();
                $resultAvailable = (bool) ($exam->show_result
                    && $latestAttempt
                    && in_array($latestAttempt->status, ['SUBMITTED', 'EXPIRED'], true)
                    && $currentScore
                    && $currentScore->grading_status === 'FINALIZED');
                $limit = $participant->attempt_limit_override ?? $exam->attempt_limit;
                $limitAvailable = $attempts->count() < $limit;

                if ($activeAttempt) {
                    $statusLabel = 'Sedang dikerjakan';
                } elseif ($latestAttempt && $latestAttempt->status === 'SUBMITTED') {
                    $statusLabel = 'Selesai';
                } elseif (!$exam->isLocked()) {
                    $statusLabel = 'Belum diterbitkan';
                } elseif (!$eligible) {
                    $statusLabel = 'Di luar waktu akses';
                } elseif (!$exam->start_at || !$exam->end_at) {
                    $statusLabel = 'Jadwal belum lengkap';
                } elseif ($now->lt($exam->start_at)) {
                    $statusLabel = 'Terjadwal';
                } elseif ($now->gt($exam->end_at)) {
                    $statusLabel = 'Ditutup';
                } elseif (!$limitAvailable) {
                    $statusLabel = 'Batas percobaan habis';
                } else {
                    $statusLabel = 'Siap dikerjakan';
                }

                return [
                    'participant' => $participant,
                    'exam' => $exam,
                    'activeAttempt' => $activeAttempt,
                    'latestAttempt' => $latestAttempt,
                    'attemptCount' => $attempts->count(),
                    'attemptLimit' => $limit,
                    'eligible' => $eligible,
                    'statusLabel' => $statusLabel,
                    'resultAvailable' => $resultAvailable,
                    'canOpen' => (bool) $activeAttempt || ($exam->canStartAttempt() && $eligible && $limitAvailable),
                ];
            })
            ->sortBy(fn ($row) => $row['exam']->start_at)
            ->values();

        return view('student.exams.index', [
            'student' => $user,
            'examRows' => $participants,
        ]);
    }

    public function show(Request $request, Exam $exam)
    {
        $user = $request->user();
        abort_unless($this->isSingleStudentIdentity($user), 403);
        abort_unless($exam->tenant_id === $user->tenant_id, 404);
        $this->authorize('view', $exam);

        $participant = ExamParticipant::query()
            ->where('tenant_id', $user->tenant_id)
            ->where('exam_id', $exam->id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $now = now();
        $activeAttempt = $participant->attempts()
            ->where('status', 'IN_PROGRESS')
            ->where('expires_at', '>', $now)
            ->latest('started_at')
            ->first();

        // Binding dibuat server-side dan disimpan pada session; refresh pada session yang sama
        // akan memakai identitas yang sama. Tidak pernah menerima binding dari input pengguna.
        $deviceBindingId = $request->session()->get('cbt.student.device_binding_id');
        if (!is_string($deviceBindingId) || !Str::isUuid($deviceBindingId)) {
            $deviceBindingId = (string) Str::uuid();
            $request->session()->put('cbt.student.device_binding_id', $deviceBindingId);
        }

        $eligible = (!$participant->eligible_from || $now->gte($participant->eligible_from))
            && (!$participant->eligible_until || $now->lt($participant->eligible_until));
        if (!$eligible) {
            $activeAttempt = null;
        }
        $attemptLimit = $participant->attempt_limit_override ?? $exam->attempt_limit;
        $attemptCount = $participant->attempts()->count();

        return view('student.exams.show', [
            'student' => $user,
            'exam' => $exam,
            'participant' => $participant,
            'activeAttempt' => $activeAttempt,
            'eligible' => $eligible,
            'attemptLimit' => $attemptLimit,
            'attemptCount' => $attemptCount,
            'canStart' => $exam->canStartAttempt() && $eligible && $attemptCount < $attemptLimit,
            'deviceBindingId' => $deviceBindingId,
        ]);
    }

    /**
     * Display only the student's own published and finalized result.
     * The current-score pointer is authoritative; score history is never exposed here.
     */
    public function result(Request $request, string $attempt)
    {
        $user = $request->user();
        abort_unless($this->isSingleStudentIdentity($user), 404);

        $examAttempt = \App\Models\ExamAttempt::query()
            ->where('tenant_id', $user->tenant_id)
            ->whereKey($attempt)
            ->with([
                'exam.assessment.subject',
                'participant',
                'scoreCurrentPointer.currentScore',
            ])
            ->firstOrFail();

        // Conceal unpublished, unfinished, non-finalized, and non-owned results.
        abort_unless($examAttempt->participant
            && $examAttempt->participant->tenant_id === $user->tenant_id
            && $examAttempt->participant->user_id === $user->id, 404);
        abort_unless($examAttempt->exam
            && $examAttempt->exam->tenant_id === $user->tenant_id
            && $examAttempt->exam->show_result, 404);
        abort_unless(in_array($examAttempt->status, ['SUBMITTED', 'EXPIRED'], true), 404);

        $this->authorize('viewScore', $examAttempt);

        $score = $examAttempt->resolvedCurrentScore();
        abort_unless($score, 404);

        return view('student.results.show', [
            'student' => $user,
            'attempt' => $examAttempt,
            'exam' => $examAttempt->exam,
            'score' => $score,
        ]);
    }
    /**
     * Web Siswa routes require exactly one persisted portal role.
     * Do not trust a previously loaded Eloquent roles relation here.
     */
    private function isSingleStudentIdentity(?\App\Models\User $user): bool
    {
        if (!$user || !$user->tenant_id) {
            return false;
        }

        $roleNames = $user->roles()->pluck('name');

        return $roleNames->count() === 1 && $roleNames->first() === 'SISWA';
    }

}
