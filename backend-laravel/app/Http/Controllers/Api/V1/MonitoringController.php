<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptAnswer;
use App\Models\ExamParticipant;
use App\Models\ExamRoomAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MonitoringController extends Controller
{
    public function summary(Exam $exam)
    {
        $this->authorize('monitor', $exam);

        $serverNow = now();
        $visibleParticipants = $this->visibleParticipantQuery($exam);
        $participantIds = (clone $visibleParticipants)->select('id');
        $totalParticipants = (clone $visibleParticipants)->count();

        // For centralized exams, every aggregate is restricted to the room/session
        // assignments of the authenticated Guru Proktor, never the whole exam.
        $visibleAttempts = ExamAttempt::query()
            ->where('exam_id', $exam->id)
            ->whereIn('participant_id', $participantIds);

        $stats = (clone $visibleAttempts)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        $notStarted = $totalParticipants - (clone $visibleAttempts)->count();

        $inProgressReal = (clone $visibleAttempts)
            ->where('status', 'IN_PROGRESS')
            ->where('expires_at', '>=', $serverNow)
            ->count();

        $expiredLogical = (clone $visibleAttempts)
            ->where(function($q) use ($serverNow) {
                $q->where('status', 'EXPIRED')
                  ->orWhere(function($sq) use ($serverNow) {
                      $sq->where('status', 'IN_PROGRESS')
                         ->where('expires_at', '<', $serverNow);
                  });
            })
            ->count();

        $requiresAttention = (clone $visibleAttempts)
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

        // Restrict centralized exam monitoring to the authenticated Guru's assigned room(s).
        $visibleParticipantIds = $this->visibleParticipantQuery($exam)->select('id');

        // Subquery to get the latest attempt ID for each visible student.
        $latestAttemptSubquery = DB::table('exam_attempts')
            ->select('participant_id', DB::raw('MAX(created_at) as last_attempt_at'))
            ->where('exam_id', $exam->id)
            ->whereIn('participant_id', $visibleParticipantIds)
            ->groupBy('participant_id');

        $query = ExamParticipant::where('exam_participants.exam_id', $exam->id)
            ->whereIn('exam_participants.id', $visibleParticipantIds)
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
            || ($exam->isCentralized() && !$this->visibleParticipantQuery($exam)->whereKey($participant->id)->exists())
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

        $eventsQuery = AuditLog::where('tenant_id', $exam->tenant_id);

        if ($exam->isCentralized()) {
            $visibleAttemptIds = ExamAttempt::query()
                ->where('exam_id', $exam->id)
                ->whereIn('participant_id', $this->visibleParticipantQuery($exam)->select('id'))
                ->select('id');

            // Room Proctors see only audit events belonging to participants in their room.
            $eventsQuery->where('entity_type', 'ExamAttempt')
                ->whereIn('entity_id', $visibleAttemptIds)
                // Do not expose audit before/after payloads, device metadata, or request fingerprints to Proctors.
                ->select(['id', 'user_id', 'action', 'entity_type', 'entity_id', 'created_at']);
        } else {
            $eventsQuery->where(function($q) use ($exam) {
                $q->where(function($sq) use ($exam) {
                    $sq->where('entity_type', 'Exam')
                       ->where('entity_id', $exam->id);
                })->orWhere(function($sq) use ($exam) {
                    $sq->where('entity_type', 'ExamAttempt')
                       ->whereIn('entity_id', function($query) use ($exam) {
                           $query->select('id')->from('exam_attempts')->where('exam_id', $exam->id);
                       });
                });
            });
        }

        $events = $eventsQuery->latest()->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $events
        ]);
    }

    /**
     * Return only participants visible to the current monitor. Centralized exams
     * are strictly room/session scoped to the Guru's active assignment.
     */
    private function visibleParticipantQuery(Exam $exam)
    {
        $query = ExamParticipant::query()
            ->where('tenant_id', $exam->tenant_id)
            ->where('exam_id', $exam->id);

        if (!$exam->isCentralized()) {
            return $query;
        }

        $user = request()->user();
        if (!$user || $user->tenant_id !== $exam->tenant_id) {
            return $query->whereRaw('1 = 0');
        }

        $assignmentIds = ExamRoomAssignment::query()
            ->where('tenant_id', $exam->tenant_id)
            ->where('exam_id', $exam->id)
            ->where('scheduled_start', '<=', now())
            ->where('scheduled_end', '>', now())
            ->whereHas('activeProctors', fn ($builder) => $builder->where('user_id', $user->id))
            ->select('id');

        return $query->whereIn('exam_room_assignment_id', $assignmentIds);
    }

    public function heartbeat(Request $request, Exam $exam, ExamAttempt $attempt)
    {
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
            || $attempt->participant->user_id !== $request->user()->id) {
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
