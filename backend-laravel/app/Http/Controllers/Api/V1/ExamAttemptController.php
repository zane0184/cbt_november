<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamParticipant;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

class ExamAttemptController extends Controller
{
    public function __construct(protected AuditService $auditService)
    {
    }

    public function store(Request $request, Exam $exam)
    {
        $this->authorize('startAttempt', $exam);

        $deviceId = $request->header('X-Device-Binding-ID');
        if (!$deviceId) {
            return response()->json([
                'success' => false,
                'message' => 'Device binding identifier missing.',
                'code' => 'MISSING_DEVICE_BINDING'
            ], 400);
        }

        $user = $request->user();

        return DB::transaction(function () use ($exam, $user, $request, $deviceId) {
            // Global lifecycle lock order: Exam -> Participant -> Attempt.
            $lockedExam = Exam::query()
                ->whereKey($exam->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Re-authorize against the current, locked Exam state.
            $this->authorize('startAttempt', $lockedExam);

            $participant = ExamParticipant::query()
                ->where('exam_id', $lockedExam->id)
                ->where('user_id', $user->id)
                ->where('tenant_id', $lockedExam->tenant_id)
                ->lockForUpdate()
                ->first();

            if (!$participant) {
                return response()->json([
                    'success' => false,
                    'message' => 'User is not assigned to this exam.'
                ], 403);
            }

            $eligibilityNow = now();
            if (($participant->eligible_from && $eligibilityNow->lt($participant->eligible_from))
                || ($participant->eligible_until && $eligibilityNow->gte($participant->eligible_until))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Peserta belum atau sudah tidak berada dalam rentang waktu yang diizinkan.',
                    'code' => 'PARTICIPANT_NOT_ELIGIBLE'
                ], 403);
            }

            // Lock the active child attempt only after its parent rows.
            $existingAttempt = $participant->attempts()
                ->where('status', 'IN_PROGRESS')
                ->where('expires_at', '>', now())
                ->lockForUpdate()
                ->first();

            if ($existingAttempt) {
                if ($existingAttempt->device_fingerprint && $existingAttempt->device_fingerprint !== $deviceId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Sesi ujian ini sedang aktif di perangkat lain.',
                        'code' => 'CONCURRENT_DEVICE_DENIED'
                    ], 409);
                }

                if (!$existingAttempt->device_fingerprint) {
                    $existingAttempt->update(['device_fingerprint' => $deviceId]);
                }

                return response()->json([
                    'success' => true,
                    'data' => $this->attemptResponseData($existingAttempt),
                    'message' => 'Resuming existing attempt.'
                ], 200);
            }

            if ($lockedExam->require_token) {
                $throttleKey = 'exam-token:' . $user->id . ':' . $lockedExam->id;

                if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
                    $seconds = RateLimiter::availableIn($throttleKey);
                    return response()->json([
                        'success' => false,
                        'message' => "Too many invalid token attempts. Try again in $seconds seconds."
                    ], 429);
                }

                $request->validate(['token' => 'required|string']);
                if ($request->token !== $lockedExam->token) {
                    RateLimiter::hit($throttleKey, 60);
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid exam token.'
                    ], 422);
                }

                RateLimiter::clear($throttleKey);
            }

            $attemptsCount = $participant->attempts()->count();
            if ($attemptsCount >= ($participant->attempt_limit_override ?? $lockedExam->attempt_limit)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Attempt limit reached.'
                ], 422);
            }

            $startedAt = now();
            $expiresAt = $startedAt->copy()->addSeconds($lockedExam->duration_seconds);
            if ($lockedExam->end_at && $expiresAt->gt($lockedExam->end_at)) {
                $expiresAt = $lockedExam->end_at;
            }
            if ($participant->eligible_until && $expiresAt->gt($participant->eligible_until)) {
                $expiresAt = $participant->eligible_until;
            }
            if ($expiresAt->lte($startedAt)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Waktu akses peserta sudah berakhir.',
                    'code' => 'PARTICIPANT_NOT_ELIGIBLE'
                ], 403);
            }

            $snapshots = $lockedExam->snapshots()->select('id', 'snapshot_options')->get();
            $questionIds = $snapshots->pluck('id')->toArray();
            if ($lockedExam->shuffle_questions) {
                shuffle($questionIds);
            }

            $optionsOrder = [];
            foreach ($snapshots as $snapshot) {
                $options = $snapshot->snapshot_options;
                if (is_array($options)) {
                    $optionIds = array_column($options, 'id');
                    if ($lockedExam->shuffle_options) {
                        shuffle($optionIds);
                    }
                    $optionsOrder[$snapshot->id] = $optionIds;
                }
            }

            $attempt = ExamAttempt::create([
                'tenant_id' => $lockedExam->tenant_id,
                'exam_id' => $lockedExam->id,
                'participant_id' => $participant->id,
                'attempt_number' => $attemptsCount + 1,
                'status' => 'IN_PROGRESS',
                'device_fingerprint' => $deviceId,
                'started_at' => $startedAt,
                'expires_at' => $expiresAt,
                'server_start_reference' => $startedAt->toIso8601String(),
                'question_order' => $questionIds,
                'options_order' => $optionsOrder,
                'last_activity_at' => $startedAt
            ]);

            $participant->update(['status' => 'STARTED']);
            $this->auditService->log('START_ATTEMPT', 'Exam', $lockedExam->id, null, [
                'attempt_id' => $attempt->id
            ]);

            return response()->json(['success' => true, 'data' => $this->attemptResponseData($attempt)], 201);
        });
    }


    public function execution(Request $request, Exam $exam)
    {
        $deviceId = $request->header('X-Device-Binding-ID');
        if (!$deviceId) {
            return response()->json([
                'success' => false,
                'message' => 'Device binding identifier missing.',
                'code' => 'MISSING_DEVICE_BINDING'
            ], 400);
        }

        $user = $request->user();
        $this->authorize('view', $exam);

        $lockedState = DB::transaction(function () use ($exam, $user, $deviceId) {
            // Execution heartbeat/binding mutation follows lifecycle parent-first locks.
            $lockedExam = Exam::query()
                ->whereKey($exam->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->authorize('view', $lockedExam);

            $participant = ExamParticipant::query()
                ->where('exam_id', $lockedExam->id)
                ->where('user_id', $user->id)
                ->where('tenant_id', $lockedExam->tenant_id)
                ->lockForUpdate()
                ->first();

            if (!$participant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Participant not found for this exam.'
                ], 404);
            }

            $eligibilityNow = now();
            if (($participant->eligible_from && $eligibilityNow->lt($participant->eligible_from))
                || ($participant->eligible_until && $eligibilityNow->gte($participant->eligible_until))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Rentang waktu akses peserta telah berakhir atau belum dimulai.',
                    'code' => 'PARTICIPANT_NOT_ELIGIBLE'
                ], 403);
            }

            $lockedAttempt = $participant->attempts()
                ->where('status', 'IN_PROGRESS')
                ->where('expires_at', '>', now())
                ->lockForUpdate()
                ->first();

            if (!$lockedAttempt) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active attempt found. Start an attempt first.'
                ], 404);
            }

            if ($lockedAttempt->device_fingerprint && $lockedAttempt->device_fingerprint !== $deviceId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sesi ujian ini sedang aktif di perangkat lain.',
                    'code' => 'CONCURRENT_DEVICE_DENIED'
                ], 409);
            }

            if (!$lockedAttempt->device_fingerprint) {
                $lockedAttempt->device_fingerprint = $deviceId;
            }
            $lockedAttempt->last_activity_at = now();
            $lockedAttempt->save();

            return [$lockedExam, $lockedAttempt];
        });

        if ($lockedState instanceof \Illuminate\Http\JsonResponse) {
            return $lockedState;
        }

        [$lockedExam, $attempt] = $lockedState;

        // Map snapshots based on saved order
        $questionOrder = $attempt->question_order ?? [];
        $optionsOrderMap = $attempt->options_order ?? [];

        $snapshots = $lockedExam->snapshots()->whereIn('id', $questionOrder)->get()->keyBy('id');

        $orderedQuestions = collect($questionOrder)->map(function ($snapshotId) use ($snapshots, $optionsOrderMap, $user) {
            $q = $snapshots->get($snapshotId);
            if (!$q) return null;

            $options = $q->snapshot_options;
            $optionOrder = $optionsOrderMap[$snapshotId] ?? null;

            if ($optionOrder && is_array($options)) {
                $optionsById = collect($options)->keyBy('id');
                $options = collect($optionOrder)->map(fn($id) => $optionsById->get($id))->filter()->values()->toArray();
            }

            $metadata = $q->metadata ?? [];

            // Security: Sanitize options & metadata for students
            if ($user->hasRole('SISWA')) {
                unset($metadata['accepted_answers']);
                unset($metadata['rubric']);
                unset($metadata['answer_key']);
                unset($metadata['scoring_guide']);

                $qType = $metadata['question_type'] ?? null;
                if (in_array($qType, ['SHORT_ANSWER', 'ESSAY'])) {
                    $options = [];
                } else {
                    $options = collect($options)->map(function ($opt) {
                        unset($opt['is_correct']);
                        unset($opt['score_weight']);
                        if (isset($opt['option_key']) && $opt['option_key'] !== null && $opt['option_key'] !== '') {
                            $opt['option_key'] = 'LEFT';
                        }
                        if (isset($opt['matching_key']) && $opt['matching_key'] !== null && $opt['matching_key'] !== '') {
                            $opt['matching_key'] = 'RIGHT';
                        }
                        return $opt;
                    })->toArray();
                }
            }

            $snapshotMedia = $q->snapshot_media ?? [];
            if ($user->hasRole('SISWA')) {
                // Jangan kirim storage_path internal ke browser; file diakses lewat
                // endpoint media yang memverifikasi tenant dan active attempt peserta.
                $snapshotMedia = collect($snapshotMedia)->map(function ($media) {
                    return [
                        'id' => $media['id'] ?? null,
                        'role' => $media['role'] ?? null,
                        'mime_type' => $media['mime_type'] ?? null,
                    ];
                })->filter(fn ($media) => !empty($media['id']))->values()->all();
            }

            return [
                'id' => $q->id,
                'sequence' => $q->sequence,
                'points' => $q->points,
                'snapshot_stem' => $q->snapshot_stem,
                'snapshot_options' => $options,
                'snapshot_media' => $snapshotMedia,
                'metadata' => $metadata,
            ];
        })->filter();

        return response()->json([
            'success' => true,
            'data' => [
                'exam' => [
                    'id' => $exam->id,
                    'title' => $lockedExam->title,
                    'instructions' => $lockedExam->instructions,
                    'duration_seconds' => $lockedExam->duration_seconds,
                    'timezone' => $lockedExam->timezone,
                    'start_at' => $lockedExam->start_at,
                    'end_at' => $lockedExam->end_at,
                    'server_now' => now()->toIso8601String(),
                ],
                'attempt' => [
                    'id' => $attempt->id,
                    'status' => $attempt->status,
                    'attempt_number' => $attempt->attempt_number,
                    'started_at' => $attempt->started_at,
                    'expires_at' => $attempt->expires_at,
                ],
                'questions' => $orderedQuestions->values()
            ]
        ]);
    }



        public function submit(Request $request, Exam $exam, ExamAttempt $attempt)
    {
        $deviceId = $request->header('X-Device-Binding-ID');
        if (!$deviceId) {
            return response()->json([
                'success' => false,
                'message' => 'Device binding identifier missing.',
                'code' => 'MISSING_DEVICE_BINDING'
            ], 400);
        }

        $user = $request->user();

        return DB::transaction(function () use ($exam, $attempt, $user, $deviceId) {
            // Match Start: Exam -> Participant -> Attempt.
            $lockedExam = Exam::query()
                ->whereKey($exam->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->authorize('view', $lockedExam);

            $participant = ExamParticipant::query()
                ->where('exam_id', $lockedExam->id)
                ->where('user_id', $user->id)
                ->where('tenant_id', $lockedExam->tenant_id)
                ->lockForUpdate()
                ->first();

            if (!$participant || $participant->id !== $attempt->participant_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized submission.'
                ], 403);
            }

            $lockedAttempt = ExamAttempt::query()
                ->whereKey($attempt->id)
                ->where('exam_id', $lockedExam->id)
                ->where('participant_id', $participant->id)
                ->where('tenant_id', $lockedExam->tenant_id)
                ->lockForUpdate()
                ->first();

            if (!$lockedAttempt) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid attempt for this exam.'
                ], 404);
            }

            if ($lockedAttempt->device_fingerprint && $lockedAttempt->device_fingerprint !== $deviceId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sesi ujian ini sedang aktif di perangkat lain.',
                    'code' => 'CONCURRENT_DEVICE_DENIED'
                ], 409);
            }

            // Scheduler may win the expiry race. An already-expired attempt from
            // the same authenticated participant/device is an idempotent success.
            if ($lockedAttempt->status === 'EXPIRED') {
                return response()->json([
                    'success' => true,
                    'message' => 'Attempt already expired and closed.',
                    'data' => ['status' => 'EXPIRED']
                ]);
            }

            if ($lockedAttempt->status !== 'IN_PROGRESS') {
                return response()->json([
                    'success' => false,
                    'message' => 'Attempt is not in progress.',
                    'code' => 'ATTEMPT_ALREADY_CLOSED'
                ], 422);
            }

            if (!$lockedAttempt->device_fingerprint) {
                $lockedAttempt->device_fingerprint = $deviceId;
            }

            $serverNow = now();
            $expiredByDeadline = $lockedAttempt->expires_at !== null
                && $lockedAttempt->expires_at->lte($serverNow);
            $terminalStatus = $expiredByDeadline ? 'EXPIRED' : 'SUBMITTED';

            $lockedAttempt->update([
                'status' => $terminalStatus,
                'submitted_at' => $serverNow,
                'last_activity_at' => $serverNow
            ]);

            $participant->update(['status' => 'COMPLETED']);

            $this->auditService->log($expiredByDeadline ? 'EXPIRE_ATTEMPT' : 'SUBMIT_ATTEMPT', 'Exam', $lockedExam->id, null, [
                'attempt_id' => $lockedAttempt->id,
                'device_fingerprint' => $deviceId,
                'status' => $terminalStatus,
                'close_reason' => $expiredByDeadline ? 'SERVER_DEADLINE_REACHED' : 'STUDENT_SUBMITTED',
            ]);

            // Scoring executes while the parent and Attempt locks remain held.
            app(\App\Services\ScoringService::class)->scoreAttempt($lockedAttempt);

            return response()->json([
                'success' => true,
                'message' => $expiredByDeadline ? 'Attempt expired and closed.' : 'Attempt submitted.',
                'data' => ['status' => $terminalStatus],
            ]);
        });
    }


    public function show(Request $request, Exam $exam, ExamAttempt $attempt)
    {
        if ($attempt->exam_id !== $exam->id || $attempt->tenant_id !== $exam->tenant_id) {
            abort(404);
        }

        if ($request->user()->hasRole('SISWA')) {
            $participant = $attempt->participant;
            abort_unless(
                $participant
                    && $participant->tenant_id === $exam->tenant_id
                    && $participant->user_id === $request->user()->id,
                404
            );
            $this->authorize('view', $exam);
        } else {
            $this->authorize('monitor', $exam);
        }

        return response()->json(['success' => true, 'data' => $this->attemptResponseData($attempt)]);
    }

    /**
     * Public API representation for an attempt. Never serialize the Eloquent model:
     * it contains internal assignment order, device identity, lease/fencing state,
     * and other fields that must remain server-side.
     */
    private function attemptResponseData(ExamAttempt $attempt): array
    {
        return [
            'id' => $attempt->id,
            'status' => $attempt->status,
            'attempt_number' => $attempt->attempt_number,
            'started_at' => $attempt->started_at?->toIso8601String(),
            'expires_at' => $attempt->expires_at?->toIso8601String(),
            'submitted_at' => $attempt->submitted_at?->toIso8601String(),
        ];
    }
}
