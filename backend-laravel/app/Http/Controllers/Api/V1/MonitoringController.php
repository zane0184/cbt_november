<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptAnswer;
use App\Models\ExamParticipant;
use App\Services\ExamMonitoringScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MonitoringController extends Controller
{
    public function summary(Request $request, Exam $exam, ExamMonitoringScope $scope)
    {
        $this->authorize('monitor', $exam);

        $serverNow = now();
        $participantQuery = $scope->participantQuery($request->user(), $exam);
        $participantIds = (clone $participantQuery)->select('id');

        $totalParticipants = (clone $participantQuery)->count();
        $attempts = ExamAttempt::query()
            ->where('tenant_id', $exam->tenant_id)
            ->where('exam_id', $exam->id)
            ->whereIn('participant_id', $participantIds);

        $stats = (clone $attempts)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        $attemptCount = (clone $attempts)->count();
        $notStarted = max(0, $totalParticipants - $attemptCount);
        $inProgressReal = (clone $attempts)
            ->where('status', 'IN_PROGRESS')
            ->where('expires_at', '>=', $serverNow)
            ->count();

        $expiredLogical = (clone $attempts)
            ->where(function ($query) use ($serverNow) {
                $query->where('status', 'EXPIRED')
                    ->orWhere(function ($inner) use ($serverNow) {
                        $inner->where('status', 'IN_PROGRESS')
                            ->where('expires_at', '<', $serverNow);
                    });
            })
            ->count();

        $requiresAttention = (clone $attempts)
            ->where('status', 'IN_PROGRESS')
            ->where('expires_at', '<', $serverNow)
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'exam_id' => $exam->id,
                'title' => $exam->title,
                'status' => $exam->status,
                'server_now' => $serverNow->toIso8601String(),
                'summary' => [
                    'total_participants' => $totalParticipants,
                    'not_started' => $notStarted,
                    'in_progress' => $inProgressReal,
                    'submitted' => $stats->get('SUBMITTED', 0),
                    'expired' => $expiredLogical,
                    'requires_attention' => $requiresAttention,
                ],
            ],
        ]);
    }

    public function participants(Request $request, Exam $exam, ExamMonitoringScope $scope)
    {
        $this->authorize('monitor', $exam);

        $serverNow = now();
        $participantQuery = $scope->participantQuery($request->user(), $exam);
        $participantIds = (clone $participantQuery)->select('id');

        $latestAttemptSubquery = DB::table('exam_attempts')
            ->select('participant_id', DB::raw('MAX(created_at) as last_attempt_at'))
            ->where('tenant_id', $exam->tenant_id)
            ->where('exam_id', $exam->id)
            ->whereIn('participant_id', $participantIds)
            ->groupBy('participant_id');

        $query = ExamParticipant::query()
            ->where('exam_participants.tenant_id', $exam->tenant_id)
            ->where('exam_participants.exam_id', $exam->id)
            ->whereIn('exam_participants.id', $participantIds)
            ->join('users', function ($join) use ($exam) {
                $join->on('exam_participants.user_id', '=', 'users.id')
                    ->where('users.tenant_id', '=', $exam->tenant_id);
            })
            ->leftJoinSub($latestAttemptSubquery, 'latest_at', function ($join) {
                $join->on('exam_participants.id', '=', 'latest_at.participant_id');
            })
            ->leftJoin('exam_attempts', function ($join) use ($exam) {
                $join->on('exam_participants.id', '=', 'exam_attempts.participant_id')
                    ->on('exam_attempts.created_at', '=', 'latest_at.last_attempt_at')
                    ->where('exam_attempts.exam_id', '=', $exam->id)
                    ->where('exam_attempts.tenant_id', '=', $exam->tenant_id);
            })
            ->select(
                'exam_participants.id as participant_id',
                'exam_participants.user_id',
                'users.name as student_name',
                'exam_attempts.id as attempt_id',
                'exam_attempts.status as attempt_status',
                'exam_attempts.started_at',
                'exam_attempts.expires_at',
                'exam_attempts.submitted_at',
                'exam_attempts.last_activity_at'
            );

        if ($request->filled('status')) {
            $status = (string) $request->query('status');
            if ($status === 'NOT_STARTED') {
                $query->whereNull('exam_attempts.id');
            } elseif ($status === 'EXPIRED') {
                $query->where(function ($inner) use ($serverNow) {
                    $inner->where('exam_attempts.status', 'EXPIRED')
                        ->orWhere(function ($nested) use ($serverNow) {
                            $nested->where('exam_attempts.status', 'IN_PROGRESS')
                                ->where('exam_attempts.expires_at', '<', $serverNow);
                        });
                });
            } elseif (in_array($status, ['IN_PROGRESS', 'SUBMITTED'], true)) {
                $query->where('exam_attempts.status', $status);
            }
        }

        if ($request->filled('search')) {
            $searchTerm = '%' . mb_substr((string) $request->query('search'), 0, 100) . '%';
            $query->where('users.name', 'like', $searchTerm);
        }

        if ($request->boolean('requires_attention')) {
            $query->where('exam_attempts.status', 'IN_PROGRESS')
                ->where('exam_attempts.expires_at', '<', $serverNow);
        }

        $sortMap = [
            'student_name' => 'users.name',
            'status' => 'exam_attempts.status',
            'started_at' => 'exam_attempts.started_at',
            'expires_at' => 'exam_attempts.expires_at',
            'submitted_at' => 'exam_attempts.submitted_at',
            'last_activity_at' => 'exam_attempts.last_activity_at',
        ];
        $sortField = $sortMap[$request->query('sort_by', 'student_name')] ?? 'users.name';
        $sortOrder = strtolower((string) $request->query('sort_order', 'asc')) === 'desc' ? 'desc' : 'asc';
        $perPage = min(100, max(1, (int) $request->query('per_page', 50)));

        $participants = $query->orderBy($sortField, $sortOrder)
            ->orderBy('exam_participants.id')
            ->paginate($perPage);

        $attemptIds = collect($participants->items())->pluck('attempt_id')->filter()->values()->all();
        $answerStats = ExamAttemptAnswer::query()
            ->where('tenant_id', $exam->tenant_id)
            ->where('exam_id', $exam->id)
            ->whereIn('exam_attempt_id', $attemptIds)
            ->select(
                'exam_attempt_id',
                DB::raw('count(*) as total'),
                DB::raw('sum(case when answer_state = "ANSWERED" then 1 else 0 end) as answered')
            )
            ->groupBy('exam_attempt_id')
            ->get()
            ->keyBy('exam_attempt_id');

        $totalQuestions = $exam->snapshots()->where('tenant_id', $exam->tenant_id)->count();

        $data = collect($participants->items())->map(function ($participant) use ($serverNow, $answerStats, $totalQuestions) {
            $remainingSeconds = 0;
            $requiresAttention = false;
            $syncHealth = 'HEALTHY';

            if ($participant->attempt_id && $participant->attempt_status === 'IN_PROGRESS') {
                $expiresAt = \Carbon\Carbon::parse($participant->expires_at);
                $lastActivity = $participant->last_activity_at
                    ? \Carbon\Carbon::parse($participant->last_activity_at)
                    : null;

                $remainingSeconds = max(0, (int) $serverNow->diffInSeconds($expiresAt, false));
                $requiresAttention = $expiresAt->isPast();
                if ($lastActivity && $lastActivity->diffInMinutes($serverNow) > 5) {
                    $syncHealth = 'SYNC_WARNING';
                }
            }

            $stats = $participant->attempt_id ? $answerStats->get($participant->attempt_id) : null;
            $answered = $stats ? (int) $stats->answered : 0;

            return [
                'participant_id' => $participant->participant_id,
                'student_name' => $participant->student_name,
                'attempt' => $participant->attempt_id ? [
                    'id' => $participant->attempt_id,
                    'status' => $participant->attempt_status,
                    'started_at' => $participant->started_at,
                    'expires_at' => $participant->expires_at,
                    'submitted_at' => $participant->submitted_at,
                    'last_activity_at' => $participant->last_activity_at,
                    'remaining_seconds' => $remainingSeconds,
                    'answered_count' => $answered,
                    'unanswered_count' => max(0, $totalQuestions - $answered),
                    'sync_health' => $syncHealth,
                ] : null,
                'requires_attention' => $requiresAttention,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'current_page' => $participants->currentPage(),
                'last_page' => $participants->lastPage(),
                'total' => $participants->total(),
            ],
        ]);
    }

    public function attempt(Request $request, Exam $exam, ExamAttempt $attempt, ExamMonitoringScope $scope)
    {
        $this->authorize('monitor', $exam);

        if ($attempt->exam_id !== $exam->id || $attempt->tenant_id !== $exam->tenant_id) {
            abort(404);
        }

        $attempt->loadMissing('participant.user');
        $participant = $attempt->participant;
        $participantUser = $participant?->user;

        if (!$participant
            || $participant->tenant_id !== $exam->tenant_id
            || $participant->exam_id !== $exam->id
            || !$participantUser
            || $participantUser->tenant_id !== $exam->tenant_id
            || !$scope->allowsParticipant($request->user(), $exam, (string) $participant->id)) {
            abort(404);
        }

        $totalQuestions = $exam->snapshots()
            ->where('tenant_id', $exam->tenant_id)
            ->count();

        $answeredCount = ExamAttemptAnswer::query()
            ->where('tenant_id', $exam->tenant_id)
            ->where('exam_id', $exam->id)
            ->where('exam_attempt_id', $attempt->id)
            ->where('answer_state', 'ANSWERED')
            ->count();

        // Monitoring returns operational fields only; no answer payload,
        // answer ordering, token, device fingerprint, or lease secret.
        return response()->json([
            'success' => true,
            'data' => [
                'attempt' => [
                    'id' => $attempt->id,
                    'attempt_number' => $attempt->attempt_number,
                    'status' => $attempt->status,
                    'started_at' => $attempt->started_at?->toIso8601String(),
                    'expires_at' => $attempt->expires_at?->toIso8601String(),
                    'submitted_at' => $attempt->submitted_at?->toIso8601String(),
                    'last_activity_at' => $attempt->last_activity_at?->toIso8601String(),
                    'participant' => [
                        'id' => $participant->id,
                        'status' => $participant->status,
                        'user' => ['id' => $participantUser->id, 'name' => $participantUser->name],
                    ],
                ],
                'server_now' => now()->toIso8601String(),
                'stats' => [
                    'total_questions' => $totalQuestions,
                    'answered_count' => $answeredCount,
                    'unanswered_count' => max(0, $totalQuestions - $answeredCount),
                ],
            ],
        ]);
    }

    public function events(Request $request, Exam $exam, ExamMonitoringScope $scope)
    {
        $this->authorize('monitor', $exam);

        $events = AuditLog::query()
            ->where('tenant_id', $exam->tenant_id)
            ->where('entity_type', 'ExamAttempt')
            ->whereIn('entity_id', $scope->attemptIdsQuery($request->user(), $exam))
            ->latest()
            ->paginate(50);

        if ($scope->isExamAuthor($request->user(), $exam)) {
            $events = AuditLog::query()
                ->where('tenant_id', $exam->tenant_id)
                ->where(function ($query) use ($exam) {
                    $query->where(function ($inner) use ($exam) {
                        $inner->where('entity_type', 'Exam')
                            ->where('entity_id', $exam->id);
                    })->orWhere(function ($inner) use ($exam) {
                        $inner->where('entity_type', 'ExamAttempt')
                            ->whereIn('entity_id', function ($subquery) use ($exam) {
                                $subquery->select('id')
                                    ->from('exam_attempts')
                                    ->where('tenant_id', $exam->tenant_id)
                                    ->where('exam_id', $exam->id);
                            });
                    });
                })
                ->latest()
                ->paginate(50);
        }

        return response()->json(['success' => true, 'data' => $events]);
    }

    public function heartbeat(Request $request, Exam $exam, ExamAttempt $attempt)
    {
        $user = $request->user();
        $roleNames = $user ? $user->roles()->pluck('name')->all() : [];

        abort_unless(
            $user && $user->tenant_id && count($roleNames) === 1 && $roleNames[0] === 'SISWA',
            403,
            'Heartbeat ujian hanya tersedia untuk akun Siswa.'
        );

        $deviceId = $request->header('X-Device-Binding-ID');
        if (!$deviceId) {
            return response()->json([
                'success' => false,
                'message' => 'Device binding identifier missing.',
                'code' => 'MISSING_DEVICE_BINDING'
            ], 400);
        }

        // Student heartbeat
        if ($attempt->exam_id !== $exam->id
            || $attempt->tenant_id !== $exam->tenant_id
            || $attempt->participant->tenant_id !== $exam->tenant_id
            || $attempt->participant->user_id !== $user->id) {
            abort(403);
        }

        if ($attempt->device_fingerprint && $attempt->device_fingerprint !== $deviceId) {
            return response()->json([
                'success' => false,
                'message' => 'Sesi ujian ini sedang aktif di perangkat lain.',
                'code' => 'CONCURRENT_DEVICE_DENIED'
            ], 409);
        }

        if (!$attempt->device_fingerprint) {
            $attempt->update(['device_fingerprint' => $deviceId]);
        }

        $attempt->update(['last_activity_at' => now()]);

        return response()->json([
            'success' => true,
            'server_now' => now()->toIso8601String(),
            'expires_at' => $attempt->expires_at->toIso8601String(),
            'status' => $attempt->status
        ]);
    }
}
