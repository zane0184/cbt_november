<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptAnswer;
use App\Models\ExamParticipant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MonitoringController extends Controller
{
    public function summary(Exam $exam)
    {
        $this->authorize('monitor', $exam);

        $serverNow = now();
        $totalParticipants = ExamParticipant::where('exam_id', $exam->id)->count();

        $stats = ExamAttempt::where('exam_id', $exam->id)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        $notStarted = $totalParticipants - ExamAttempt::where('exam_id', $exam->id)->count();

        $inProgressReal = ExamAttempt::where('exam_id', $exam->id)
            ->where('status', 'IN_PROGRESS')
            ->where('expires_at', '>=', $serverNow)
            ->count();

        $expiredLogical = ExamAttempt::where('exam_id', $exam->id)
            ->where(function($q) use ($serverNow) {
                $q->where('status', 'EXPIRED')
                  ->orWhere(function($sq) use ($serverNow) {
                      $sq->where('status', 'IN_PROGRESS')
                         ->where('expires_at', '<', $serverNow);
                  });
            })
            ->count();

        $requiresAttention = ExamAttempt::where('exam_id', $exam->id)
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
                    'requires_attention' => $requiresAttention
                ]
            ]
        ]);
    }

    public function participants(Request $request, Exam $exam)
    {
        $this->authorize('monitor', $exam);

        $serverNow = now();

        // Subquery to get the latest attempt ID for each student in this exam
        $latestAttemptSubquery = DB::table('exam_attempts')
            ->select('participant_id', DB::raw('MAX(created_at) as last_attempt_at'))
            ->where('exam_id', $exam->id)
            ->groupBy('participant_id');

        $query = ExamParticipant::where('exam_participants.exam_id', $exam->id)
            ->with(['user'])
            // Join with subquery to find the latest attempt timestamp
            ->leftJoinSub($latestAttemptSubquery, 'latest_at', function($join) {
                $join->on('exam_participants.id', '=', 'latest_at.participant_id');
            })
            // Join again with exam_attempts using participant_id and the max timestamp to get the specific record
            // This ensures only one (the latest) attempt row per participant
            ->leftJoin('exam_attempts', function($join) use ($exam) {
                $join->on('exam_participants.id', '=', 'exam_attempts.participant_id')
                     ->on('exam_attempts.created_at', '=', 'latest_at.last_attempt_at')
                     ->where('exam_attempts.exam_id', '=', $exam->id);
            })
            ->select(
                'exam_participants.id as participant_id',
                'exam_participants.user_id',
                'exam_attempts.id as attempt_id',
                'exam_attempts.status as attempt_status',
                'exam_attempts.started_at',
                'exam_attempts.expires_at',
                'exam_attempts.submitted_at',
                'exam_attempts.last_activity_at'
            );

        // 1. Filtering
        if ($request->has('status')) {
            if ($request->status === 'NOT_STARTED') {
                $query->whereNull('exam_attempts.id');
            } else if ($request->status === 'EXPIRED') {
                 $query->where(function($q) use ($serverNow) {
                    $q->where('exam_attempts.status', 'EXPIRED')
                      ->orWhere(function($sq) use ($serverNow) {
                          $sq->where('exam_attempts.status', 'IN_PROGRESS')
                             ->where('exam_attempts.expires_at', '<', $serverNow);
                      });
                });
            } else {
                $query->where('exam_attempts.status', $request->status);
            }
        }

        if ($request->has('search')) {
            $searchTerm = '%' . $request->search . '%';
            $query->whereHas('user', function($q) use ($searchTerm) {
                $q->where('name', 'like', $searchTerm);
            });
        }

        if ($request->boolean('requires_attention')) {
            $query->where('exam_attempts.status', 'IN_PROGRESS')
                  ->where('exam_attempts.expires_at', '<', $serverNow);
        }

        // 2. Sorting
        $sortField = $request->get('sort_by', 'student_name');
        $sortOrder = $request->get('sort_order', 'asc');

        if ($sortField === 'student_name') {
            $query->join('users', 'exam_participants.user_id', '=', 'users.id')
                  ->orderBy('users.name', $sortOrder);
        } else {
            $query->orderBy($sortField, $sortOrder);
        }

        // 3. Pagination
        $participants = $query->paginate($request->get('per_page', 50));

        // 4. Data Mapping with Counts (avoiding N+1)
        $attemptIds = collect($participants->items())->pluck('attempt_id')->filter()->toArray();
        $answerStats = ExamAttemptAnswer::whereIn('exam_attempt_id', $attemptIds)
            ->select('exam_attempt_id',
                DB::raw('count(*) as total'),
                DB::raw('sum(case when answer_state = "ANSWERED" then 1 else 0 end) as answered')
            )
            ->groupBy('exam_attempt_id')
            ->get()
            ->keyBy('exam_attempt_id');

        $totalQuestions = $exam->snapshots()->count();

        $data = collect($participants->items())->map(function($p) use ($serverNow, $answerStats, $totalQuestions) {
            $remainingSeconds = 0;
            $requiresAttention = false;
            $syncHealth = 'HEALTHY';

            if ($p->attempt_id) {
                $expiresAt = \Carbon\Carbon::parse($p->expires_at);
                $lastActivity = $p->last_activity_at ? \Carbon\Carbon::parse($p->last_activity_at) : null;

                if ($p->attempt_status === 'IN_PROGRESS') {
                    $remainingSeconds = max(0, $expiresAt->diffInSeconds($serverNow, false) * -1);
                    if ($expiresAt->isPast()) {
                        $requiresAttention = true;
                    }

                    // Simple Sync Health Logic: Warning if no activity for 5 minutes
                    if ($lastActivity && $lastActivity->diffInMinutes($serverNow) > 5) {
                        $syncHealth = 'SYNC_WARNING';
                    }
                }
            }

            $stats = $p->attempt_id ? $answerStats->get($p->attempt_id) : null;

            return [
                'participant_id' => $p->participant_id,
                'student_name' => $p->user->name,
                'attempt' => $p->attempt_id ? [
                    'id' => $p->attempt_id,
                    'status' => $p->attempt_status,
                    'started_at' => $p->started_at,
                    'expires_at' => $p->expires_at,
                    'submitted_at' => $p->submitted_at,
                    'last_activity_at' => $p->last_activity_at,
                    'remaining_seconds' => $remainingSeconds,
                    'answered_count' => $stats ? (int)$stats->answered : 0,
                    'unanswered_count' => $stats ? (int)($totalQuestions - $stats->answered) : $totalQuestions,
                    'sync_health' => $syncHealth
                ] : null,
                'requires_attention' => $requiresAttention
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'current_page' => $participants->currentPage(),
                'last_page' => $participants->lastPage(),
                'total' => $participants->total()
            ]
        ]);
    }

    public function attempt(Exam $exam, ExamAttempt $attempt)
    {
        $this->authorize('monitor', $exam);

        if ($attempt->exam_id !== $exam->id || $attempt->tenant_id !== $exam->tenant_id) abort(404);

        $totalQuestions = $exam->snapshots()
            ->where('tenant_id', $exam->tenant_id)
            ->count();

        $answeredCount = ExamAttemptAnswer::query()
            ->where('tenant_id', $exam->tenant_id)
            ->where('exam_id', $exam->id)
            ->where('exam_attempt_id', $attempt->id)
            ->where('answer_state', 'ANSWERED')
            ->count();

        $attempt->loadMissing('participant.user');
        $participant = $attempt->participant;
        $participantUser = $participant?->user;

        if (!$participant
            || $participant->tenant_id !== $exam->tenant_id
            || $participant->exam_id !== $exam->id
            || !$participantUser
            || $participantUser->tenant_id !== $exam->tenant_id) {
            abort(404);
        }

        // Monitoring receives operational fields only. Device fingerprints,
        // lease/fencing state, server references and answer ordering stay private.
        $attemptData = [
            'id' => $attempt->id,
            'attempt_number' => $attempt->attempt_number,
            'status' => $attempt->status,
            'started_at' => $attempt->started_at?->toIso8601String(),
            'expires_at' => $attempt->expires_at?->toIso8601String(),
            'submitted_at' => $attempt->submitted_at?->toIso8601String(),
            'last_activity_at' => $attempt->last_activity_at?->toIso8601String(),
            'participant' => $participant ? [
                'id' => $participant->id,
                'status' => $participant->status,
                'user' => $participantUser ? [
                    'id' => $participantUser->id,
                    'name' => $participantUser->name,
                ] : null,
            ] : null,
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'attempt' => $attemptData,
                'server_now' => now()->toIso8601String(),
                'stats' => [
                    'total_questions' => $totalQuestions,
                    'answered_count' => $answeredCount,
                    'unanswered_count' => max(0, $totalQuestions - $answeredCount),
                ],
            ],
        ]);
    }

    public function events(Request $request, Exam $exam)
    {
        $this->authorize('monitor', $exam);

        $events = AuditLog::where('tenant_id', $exam->tenant_id)
            ->where(function($q) use ($exam) {
                $q->where(function($sq) use ($exam) {
                    $sq->where('entity_type', 'Exam')
                       ->where('entity_id', $exam->id);
                })->orWhere(function($sq) use ($exam) {
                    $sq->where('entity_type', 'ExamAttempt')
                       ->whereIn('entity_id', function($query) use ($exam) {
                           $query->select('id')->from('exam_attempts')->where('exam_id', $exam->id);
                       });
                });
            })
            ->latest()
            ->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $events
        ]);
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
