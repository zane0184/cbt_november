<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamParticipant;
use App\Models\User;
use App\Services\AuditService;
use App\Services\AttemptAuthSessionBinding;
use App\Services\AttemptDeviceLeaseService;
use App\Services\ExamMonitoringScope;
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
        $user = $this->requireSingleStudent($request);
        $this->authorize('startAttempt', $exam);

        $authIdentity = app(AttemptAuthSessionBinding::class)->identity($request);
        if (!$authIdentity) {
            return response()->json(['success' => false, 'code' => 'AUTH_SESSION_REQUIRED', 'message' => 'Sesi autentikasi server tidak dapat diidentifikasi.'], 401);
        }

        $deviceId = $request->header('X-Device-Binding-ID');
        if (!$deviceId) {
            return response()->json([
                'success' => false,
                'message' => 'Device binding identifier missing.',
                'code' => 'MISSING_DEVICE_BINDING'
            ], 400);
        }

        return DB::transaction(function () use ($exam, $user, $request, $deviceId, $authIdentity) {
            // Global lifecycle lock order: Exam -> Participant -> Attempt.
            $lockedExam = Exam::query()
                ->where('tenant_id', $user->tenant_id)
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

            // An expired/malformed IN_PROGRESS row must be closed by the server
            // expiry lifecycle before a new attempt can be created. Never create a
            // second IN_PROGRESS attempt while stale lifecycle state remains.
            $expiryPending = $participant->attempts()
                ->where('status', 'IN_PROGRESS')
                ->where(function ($query) {
                    $query->whereNull('expires_at')
                        ->orWhere('expires_at', '<=', now());
                })
                ->lockForUpdate()
                ->exists();

            if ($expiryPending) {
                return response()->json([
                    'success' => false,
                    'message' => 'Attempt sebelumnya telah melewati batas waktu dan sedang menunggu penutupan server. Silakan coba kembali setelah proses penutupan.',
                    'code' => 'ATTEMPT_EXPIRY_PENDING'
                ], 409);
            }

            // Lock all valid active attempts after their parent rows. More than one
            // active attempt is legacy corruption; fail closed instead of choosing one.
            $activeAttempts = $participant->attempts()
                ->where('status', 'IN_PROGRESS')
                ->where('expires_at', '>', now())
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($activeAttempts->count() > 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ditemukan lebih dari satu Attempt aktif untuk peserta ini. Pemeriksaan integritas diperlukan.',
                    'code' => 'ATTEMPT_INTEGRITY_CONFLICT'
                ], 409);
            }

            $existingAttempt = $activeAttempts->first();

            if ($existingAttempt) {
                if (!app(AttemptAuthSessionBinding::class)->matches($existingAttempt, $request)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Attempt terikat pada sesi autentikasi lain. Login kedua tidak dapat mengambil alih ujian.',
                        'code' => 'ATTEMPT_SESSION_MISMATCH'
                    ], 409);
                }

                if (!$existingAttempt->device_fingerprint
                    || !hash_equals((string) $existingAttempt->device_fingerprint, (string) $deviceId)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Perangkat belum terikat atau berbeda. Pergantian perangkat memerlukan prosedur pelepasan yang berotorisasi.',
                        'code' => 'CONCURRENT_DEVICE_DENIED'
                    ], 409);
                }

                if (!app(AttemptDeviceLeaseService::class)->assertAndRenew($existingAttempt, $request)) {
                    $fenceMatches = app(AttemptDeviceLeaseService::class)->requestFenceMatches($existingAttempt, $request);
                    return response()->json([
                        'success' => false,
                        'message' => $fenceMatches ? 'Lease Attempt tidak aktif atau tidak konsisten.' : 'Fencing generation tidak cocok atau belum dikirim.',
                        'code' => $fenceMatches ? 'ATTEMPT_LEASE_INVALID' : 'ATTEMPT_FENCE_MISMATCH',
                        'current_fence_generation' => (int) $existingAttempt->fence_generation,
                    ], 409);
                }

                return response()->json([
                    'success' => true,
                    'data' => $this->attemptResponseData($existingAttempt),
                    'message' => 'Resuming existing attempt.'
                ], 200);
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

            if (!$lockedExam->require_token || !$lockedExam->token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token ujian belum dikonfigurasi. Hubungi Admin/Guru untuk pemeriksaan.',
                    'code' => 'EXAM_TOKEN_NOT_CONFIGURED',
                ], 409);
            }

            $throttleKey = 'exam-token:' . $user->id . ':' . $lockedExam->id;
            if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
                $seconds = RateLimiter::availableIn($throttleKey);
                return response()->json([
                    'success' => false,
                    'message' => "Too many invalid token attempts. Try again in $seconds seconds."
                ], 429);
            }

            $request->validate(['token' => 'required|string|size:6']);
            $providedToken = strtoupper(trim((string) $request->input('token')));
            if (!hash_equals((string) $lockedExam->token, $providedToken)) {
                RateLimiter::hit($throttleKey, 60);
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid exam token.'
                ], 422);
            }

            RateLimiter::clear($throttleKey);

            $attemptsCount = $participant->attempts()->count();
            if ($attemptsCount >= ($participant->attempt_limit_override ?? $lockedExam->attempt_limit)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Attempt limit reached.'
                ], 422);
            }

            // Serialize Attempt creation with Web/API login checks on the User row.
            $lockedUser = User::query()
                ->where('tenant_id', $lockedExam->tenant_id)
                ->whereKey($user->id)
                ->lockForUpdate()
                ->first();
            if (!$lockedUser || !$lockedUser->hasExactRole('SISWA')) {
                return response()->json(['success' => false, 'message' => 'Identitas Siswa tidak lagi aktif atau valid.'], 403);
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
                'auth_session_hash' => $authIdentity['hash'],
                'auth_session_type' => $authIdentity['type'],
                'auth_session_bound_at' => $startedAt,
                'started_at' => $startedAt,
                'expires_at' => $expiresAt,
                'server_start_reference' => $startedAt->toIso8601String(),
                'question_order' => $questionIds,
                'options_order' => $optionsOrder,
                'last_activity_at' => $startedAt
            ]);

            if (!app(AttemptDeviceLeaseService::class)->createForAttempt($attempt, $request)) {
                throw new \RuntimeException('Attempt auth-session lease could not be created.');
            }

            $participant->update(['status' => 'STARTED']);

            $this->auditService->log('START_ATTEMPT', 'ExamAttempt', $attempt->id, null, [
                'tenant_id' => $lockedExam->tenant_id,
                'exam_id' => $lockedExam->id,
                'participant_id' => $participant->id,
                'attempt_number' => $attempt->attempt_number,
                'status' => 'IN_PROGRESS',
                'started_at' => $startedAt->toIso8601String(),
                'expires_at' => $expiresAt->toIso8601String(),
            ]);
            $this->auditService->log('START_ATTEMPT', 'Exam', $lockedExam->id, null, [
                'attempt_id' => $attempt->id
            ]);

            return response()->json(['success' => true, 'data' => $this->attemptResponseData($attempt)], 201);
        });
    }


    public function execution(Request $request, Exam $exam)
    {
        $user = $this->requireSingleStudent($request);
        $authIdentity = app(AttemptAuthSessionBinding::class)->identity($request);
        if (!$authIdentity) {
            return response()->json(['success' => false, 'code' => 'AUTH_SESSION_REQUIRED', 'message' => 'Sesi autentikasi server tidak dapat diidentifikasi.'], 401);
        }

        $deviceId = $request->header('X-Device-Binding-ID');
        if (!$deviceId) {
            return response()->json([
                'success' => false,
                'message' => 'Device binding identifier missing.',
                'code' => 'MISSING_DEVICE_BINDING'
            ], 400);
        }

        $lockedState = DB::transaction(function () use ($exam, $user, $deviceId, $request) {
            // Execution heartbeat/binding mutation follows lifecycle parent-first locks.
            $lockedExam = Exam::query()
                ->where('tenant_id', $user->tenant_id)
                ->whereKey($exam->id)
                ->lockForUpdate()
                ->firstOrFail();

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

            $this->authorize('view', $lockedExam);

            $activeAttempts = $participant->attempts()
                ->where('status', 'IN_PROGRESS')
                ->where('expires_at', '>', now())
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($activeAttempts->count() > 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ditemukan lebih dari satu Attempt aktif untuk peserta ini. Pemeriksaan integritas diperlukan.',
                    'code' => 'ATTEMPT_INTEGRITY_CONFLICT'
                ], 409);
            }

            $lockedAttempt = $activeAttempts->first();

            if (!$lockedAttempt) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active attempt found. Start an attempt first.'
                ], 404);
            }

            if (!app(AttemptAuthSessionBinding::class)->matches($lockedAttempt, $request)) {
                return response()->json(['success' => false, 'code' => 'ATTEMPT_SESSION_MISMATCH', 'message' => 'Attempt terikat pada sesi autentikasi lain.'], 409);
            }

            if (!$lockedAttempt->device_fingerprint
                || !hash_equals((string) $lockedAttempt->device_fingerprint, (string) $deviceId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Perangkat belum terikat atau berbeda. Pergantian perangkat memerlukan prosedur pelepasan yang berotorisasi.',
                    'code' => 'CONCURRENT_DEVICE_DENIED'
                ], 409);
            }

            if (!app(AttemptDeviceLeaseService::class)->assertAndRenew($lockedAttempt, $request)) {
                $fenceMatches = app(AttemptDeviceLeaseService::class)->requestFenceMatches($lockedAttempt, $request);
                return response()->json([
                    'success' => false,
                    'code' => $fenceMatches ? 'ATTEMPT_LEASE_INVALID' : 'ATTEMPT_FENCE_MISMATCH',
                    'current_fence_generation' => (int) $lockedAttempt->fence_generation,
                    'message' => $fenceMatches ? 'Lease Attempt tidak aktif atau tidak konsisten.' : 'Fencing generation tidak cocok atau belum dikirim.',
                ], 409);
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
            if ($user->hasExactRole('SISWA')) {
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
            if ($user->hasExactRole('SISWA')) {
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
                    'fence_generation' => (int) $attempt->fence_generation,
                    'lease_expires_at' => $attempt->device_lease_expires_at?->toIso8601String(),
                ],
                'questions' => $orderedQuestions->values()
            ]
        ]);
    }



        public function submit(Request $request, Exam $exam, ExamAttempt $attempt)
    {
        $user = $this->requireSingleStudent($request);
        $authIdentity = app(AttemptAuthSessionBinding::class)->identity($request);
        if (!$authIdentity) {
            return response()->json(['success' => false, 'code' => 'AUTH_SESSION_REQUIRED', 'message' => 'Sesi autentikasi server tidak dapat diidentifikasi.'], 401);
        }

        $deviceId = $request->header('X-Device-Binding-ID');
        if (!$deviceId) {
            return response()->json([
                'success' => false,
                'message' => 'Device binding identifier missing.',
                'code' => 'MISSING_DEVICE_BINDING'
            ], 400);
        }

        return DB::transaction(function () use ($exam, $attempt, $user, $deviceId, $request) {
            // Match Start: Exam -> Participant -> Attempt.
            $lockedExam = Exam::query()
                ->where('tenant_id', $user->tenant_id)
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

            if (!app(AttemptAuthSessionBinding::class)->matches($lockedAttempt, $request)) {
                return response()->json(['success' => false, 'code' => 'ATTEMPT_SESSION_MISMATCH', 'message' => 'Attempt terikat pada sesi autentikasi lain.'], 409);
            }

            if (!app(AttemptDeviceLeaseService::class)->requestFenceMatches($lockedAttempt, $request)) {
                return response()->json([
                    'success' => false,
                    'code' => 'ATTEMPT_FENCE_MISMATCH',
                    'current_fence_generation' => (int) $lockedAttempt->fence_generation,
                    'message' => 'Fencing generation tidak cocok atau belum dikirim.',
                ], 409);
            }

            if (!$lockedAttempt->device_fingerprint
                || !hash_equals((string) $lockedAttempt->device_fingerprint, (string) $deviceId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Perangkat belum terikat atau berbeda. Pergantian perangkat memerlukan prosedur pelepasan yang berotorisasi.',
                    'code' => 'CONCURRENT_DEVICE_DENIED'
                ], 409);
            }

            // Scheduler may win the expiry race. An already-expired attempt from
            // the same authenticated participant/device is an idempotent success.
            if (in_array($lockedAttempt->status, ['SUBMITTED', 'EXPIRED'], true)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Attempt already closed.',
                    'data' => [
                        'status' => $lockedAttempt->status,
                        'submitted_at' => $lockedAttempt->submitted_at?->toIso8601String(),
                        'fence_generation' => (int) $lockedAttempt->fence_generation,
                    ],
                    'idempotent' => true,
                ]);
            }

            if (!app(AttemptDeviceLeaseService::class)->assertAndRenew($lockedAttempt, $request)
                && $lockedAttempt->status === 'IN_PROGRESS') {
                return response()->json(['success' => false, 'code' => 'ATTEMPT_LEASE_INVALID', 'message' => 'Lease Attempt tidak aktif atau tidak konsisten.'], 409);
            }

            if ($lockedAttempt->status !== 'IN_PROGRESS') {
                return response()->json([
                    'success' => false,
                    'message' => 'Attempt is not in progress.',
                    'code' => 'ATTEMPT_ALREADY_CLOSED'
                ], 422);
            }

            if (!$lockedAttempt->expires_at) {
                return response()->json([
                    'success' => false,
                    'message' => 'Attempt tidak memiliki batas waktu server yang valid.',
                    'code' => 'ATTEMPT_INTEGRITY_CONFLICT'
                ], 409);
            }

            // Count all IN_PROGRESS rows, including one whose deadline has just
            // elapsed; that row must be terminalized as EXPIRED by this submit.
            $activeAttemptCount = ExamAttempt::query()
                ->where('tenant_id', $lockedExam->tenant_id)
                ->where('exam_id', $lockedExam->id)
                ->where('participant_id', $participant->id)
                ->where('status', 'IN_PROGRESS')
                ->count();

            if ($activeAttemptCount !== 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Jumlah Attempt aktif peserta tidak konsisten; submit ditahan untuk pemeriksaan integritas.',
                    'code' => 'ATTEMPT_INTEGRITY_CONFLICT'
                ], 409);
            }

            $serverNow = now();
            $expiredByDeadline = $lockedAttempt->expires_at !== null
                && $lockedAttempt->expires_at->lte($serverNow);
            $terminalStatus = $expiredByDeadline ? 'EXPIRED' : 'SUBMITTED';

            $oldStatus = $lockedAttempt->status;
            $lockedAttempt->update([
                'status' => $terminalStatus,
                'submitted_at' => $serverNow,
                'last_activity_at' => $serverNow
            ]);

            app(AttemptDeviceLeaseService::class)->release($lockedAttempt, $expiredByDeadline ? 'SERVER_DEADLINE_REACHED' : 'STUDENT_SUBMITTED');

            $participant->update(['status' => 'COMPLETED']);

            $this->auditService->log(
                $expiredByDeadline ? 'EXPIRE_ATTEMPT' : 'SUBMIT_ATTEMPT',
                'ExamAttempt',
                $lockedAttempt->id,
                ['status' => $oldStatus],
                [
                    'status' => $terminalStatus,
                    'exam_id' => $lockedExam->id,
                    'participant_id' => $participant->id,
                    'device_fingerprint' => $deviceId,
                    'closed_at' => $serverNow->toIso8601String(),
                    'close_reason' => $expiredByDeadline ? 'SERVER_DEADLINE_REACHED' : 'STUDENT_SUBMITTED',
                ]
            );

            // A scoring blocker must not roll back the student's terminal submit.
            // The attempt remains closed and is explicitly flagged for Guru review.
            try {
                app(\App\Services\ScoringService::class)->scoreAttempt($lockedAttempt);
            } catch (\DomainException|\InvalidArgumentException $exception) {
                $this->auditService->log('SCORING_BLOCKED', 'ExamAttempt', $lockedAttempt->id, null, [
                    'status' => $terminalStatus,
                    'reason' => $exception->getMessage(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Attempt berhasil ditutup, tetapi penilaian ditahan untuk pemeriksaan Guru.',
                    'code' => 'SCORING_BLOCKED',
                    'data' => ['status' => $terminalStatus, 'grading_status' => 'BLOCKED'],
                ], 202);
            }

            return response()->json([
                'success' => true,
                'message' => $expiredByDeadline ? 'Attempt expired and closed.' : 'Attempt submitted.',
                'data' => [
                    'status' => $terminalStatus,
                    'fence_generation' => (int) $lockedAttempt->fence_generation,
                    'lease_expires_at' => null,
                ],
            ]);
        });
    }


    public function show(
        Request $request,
        Exam $exam,
        ExamAttempt $attempt,
        ExamMonitoringScope $scope
    ) {
        if ($attempt->exam_id !== $exam->id || $attempt->tenant_id !== $exam->tenant_id) {
            abort(404);
        }

        $participant = $attempt->participant;
        if (!$participant
            || $participant->tenant_id !== $exam->tenant_id
            || $participant->exam_id !== $exam->id
            || !$participant->user
            || $participant->user->tenant_id !== $exam->tenant_id) {
            abort(404);
        }

        if ($request->user()->hasExactRole('SISWA')) {
            abort_unless($participant->user_id === $request->user()->id, 404);
            if (!app(AttemptAuthSessionBinding::class)->matches($attempt, $request)) {
                return response()->json(['success' => false, 'code' => 'ATTEMPT_SESSION_MISMATCH'], 409);
            }
            if (!app(AttemptDeviceLeaseService::class)->requestFenceMatches($attempt, $request)) {
                return response()->json([
                    'success' => false,
                    'code' => 'ATTEMPT_FENCE_MISMATCH',
                    'current_fence_generation' => (int) $attempt->fence_generation,
                ], 409);
            }
            if ($attempt->status === 'IN_PROGRESS'
                && !app(AttemptDeviceLeaseService::class)->isCurrent($attempt, $request)) {
                return response()->json(['success' => false, 'code' => 'ATTEMPT_LEASE_INVALID'], 409);
            }
            $this->authorize('view', $exam);
        } else {
            $this->authorize('monitor', $exam);

            // Exam-level monitor permission is only the first gate. A temporary
            // Proktor must also be authorized for this exact participant's
            // room/session; exam authors retain their complete exam scope.
            abort_unless(
                $scope->allowsParticipant($request->user(), $exam, (string) $participant->id),
                404
            );
        }

        return response()->json(['success' => true, 'data' => $this->attemptResponseData($attempt)]);
    }

    private function requireSingleStudent(Request $request): User
    {
        $user = $request->user();
        abort_unless(
            $user && $user->hasExactRole('SISWA'),
            403,
            'Endpoint eksekusi ujian hanya tersedia untuk akun Siswa aktif dengan satu role.'
        );

        return $user;
    }

    /**
     * Public API representation for an attempt. Never serialize the Eloquent model:
     * it contains internal assignment order, raw device/session identity and
     * other fields that must remain server-side. Only the opaque fence generation
     * and lease expiry are exposed to the bound client.
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
            'fence_generation' => (int) $attempt->fence_generation,
            'lease_expires_at' => $attempt->device_lease_expires_at?->toIso8601String(),
        ];
    }
}
