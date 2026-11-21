<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptAnswer;
use App\Models\ExamQuestionSnapshot;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ExamAnswerController extends Controller
{
    public function __construct(protected AuditService $auditService)
    {
    }

    public function index(Request $request, Exam $exam, ExamAttempt $attempt)
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

        if (!$user->hasRole('SISWA') || $user->hasRole('ADMIN_MADRASAH') || !$user->tenant_id) {
            return response()->json(['success' => false, 'message' => 'Hanya akun Siswa aktif yang dapat mengakses jawaban ujian.'], 403);
        }

        if (!$user->hasRole('SISWA') || $user->hasRole('ADMIN_MADRASAH') || !$user->tenant_id) {
            return response()->json(['success' => false, 'message' => 'Hanya akun Siswa aktif yang dapat mengakses jawaban ujian.'], 403);
        }

        // Security: Attempt must belong to the authenticated student
        if ($user->tenant_id !== $exam->tenant_id
            || $attempt->tenant_id !== $exam->tenant_id
            || $attempt->participant->tenant_id !== $exam->tenant_id
            || $attempt->participant->user_id !== $user->id
            || $attempt->exam_id !== $exam->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access to attempt answers.'], 403);
        }

        if ($attempt->device_fingerprint && $attempt->device_fingerprint !== $deviceId) {
            return response()->json([
                'success' => false,
                'message' => 'Sesi ujian ini sedang aktif di perangkat lain.',
                'code' => 'CONCURRENT_DEVICE_DENIED'
            ], 409);
        }

        // Restore only answers belonging to this attempt's immutable question order.
        // A same-exam snapshot that was not assigned to this attempt must never be
        // exposed through a manipulated attempt/snapshot identifier.
        $assignedSnapshotIds = collect($attempt->question_order ?? [])
            ->map(fn ($id) => (string) $id)
            ->values();

        $answers = ExamAttemptAnswer::query()
            ->where('tenant_id', $exam->tenant_id)
            ->where('exam_id', $exam->id)
            ->where('exam_attempt_id', $attempt->id)
            ->whereIn('exam_question_snapshot_id', $assignedSnapshotIds)
            ->orderBy('created_at')
            ->get([
                'id',
                'exam_question_snapshot_id',
                'answer_payload',
                'answer_state',
                'revision_number',
                'answered_at',
                'saved_at',
            ]);

        return response()->json([
            'success' => true,
            'data' => $answers
        ]);
    }

    public function update(Request $request, Exam $exam, ExamAttempt $attempt, ExamQuestionSnapshot $snapshot)
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

        // 1. Basic Authorization & Integrity Checks
        if ($user->tenant_id !== $exam->tenant_id
            || $attempt->tenant_id !== $exam->tenant_id
            || $attempt->participant->tenant_id !== $exam->tenant_id
            || $snapshot->tenant_id !== $exam->tenant_id
            || $attempt->exam_id !== $exam->id
            || $snapshot->exam_id !== $exam->id) {
            return response()->json(['success' => false, 'message' => 'Invalid attempt or snapshot for this exam.'], 404);
        }

        if ($attempt->participant->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized to save answers for this attempt.'], 403);
        }

        if (!in_array((string) $snapshot->id, array_map('strval', $attempt->question_order ?? []), true)) {
            return response()->json(['success' => false, 'message' => 'Snapshot is not assigned to this attempt.'], 404);
        }

        if ($attempt->status !== 'IN_PROGRESS') {
            return response()->json(['success' => false, 'message' => 'Attempt is not in progress.'], 422);
        }

        if ($attempt->device_fingerprint && $attempt->device_fingerprint !== $deviceId) {
            return response()->json([
                'success' => false,
                'message' => 'Sesi ujian ini sedang aktif di perangkat lain.',
                'code' => 'CONCURRENT_DEVICE_DENIED'
            ], 409);
        }

        if ($attempt->expires_at->isPast()) {
            return response()->json(['success' => false, 'message' => 'Attempt has expired.'], 422);
        }

        // 2. Validation
        $validated = $request->validate([
            'client_request_id' => 'required|string|max:255',
            'base_revision' => 'nullable|integer|min:0',
            'answer_payload' => 'nullable|array',
            'answer_state' => 'required|in:ANSWERED,CLEARED,UNANSWERED',
        ]);

        // 3. Payload Validation based on Question Type (Foundation)
        $this->validatePayload($snapshot, $validated['answer_payload'], $validated['answer_state']);

        // 4. Atomic Save with Conflict Detection (Revision Sequencing)
        return DB::transaction(function () use ($exam, $attempt, $snapshot, $user, $validated, $deviceId) {
            // Always lock the parent attempt before touching an answer row.
            // Submit/reset/administrative closure must use the same lock order.
            $lockedAttempt = ExamAttempt::query()
                ->whereKey($attempt->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedAttempt->exam_id !== $exam->id
                || $lockedAttempt->tenant_id !== $exam->tenant_id
                || $lockedAttempt->participant->tenant_id !== $exam->tenant_id
                || $lockedAttempt->participant->user_id !== $user->id) {
                return response()->json(['success' => false, 'message' => 'Unauthorized attempt.'], 403);
            }

            // Re-check under the attempt row lock to avoid relying only on the
            // pre-transaction model instance for the authoritative assignment.
            if (!in_array((string) $snapshot->id, array_map('strval', $lockedAttempt->question_order ?? []), true)) {
                return response()->json(['success' => false, 'message' => 'Snapshot is not assigned to this attempt.'], 404);
            }

            if ($lockedAttempt->status !== 'IN_PROGRESS') {
                return response()->json([
                    'success' => false,
                    'message' => 'Attempt is not in progress.',
                    'code' => 'ATTEMPT_CLOSED'
                ], 422);
            }

            if ($lockedAttempt->expires_at->isPast()) {
                return response()->json(['success' => false, 'message' => 'Attempt has expired.'], 422);
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
                $lockedAttempt->save();
            }

            $answer = ExamAttemptAnswer::where('exam_attempt_id', $lockedAttempt->id)
                ->where('exam_question_snapshot_id', $snapshot->id)
                ->lockForUpdate()
                ->first();

            $serverNow = now();
            $baseRevision = $validated['base_revision'] ?? 0;

            if ($answer) {
                // ... idempotency check ...
                if ($answer->client_request_id === $validated['client_request_id']) {
                    $lockedAttempt->update(['last_activity_at' => now()]);
                    return response()->json([
                        'success' => true,
                        'data' => [
                            'attempt_id' => $lockedAttempt->id,
                            'snapshot_id' => $snapshot->id,
                            'revision_number' => $answer->revision_number,
                            'answer_state' => $answer->answer_state,
                            'server_now' => $serverNow->toIso8601String(),
                            'saved_at' => $answer->saved_at->toIso8601String(),
                        ],
                        'message' => 'Idempotent request handled.'
                    ]);
                }

                // Optimistic concurrency requires an exact revision match. Reject both
                // stale and impossible future revisions; the idempotency check above
                // still returns the original result for a retried request ID.
                $currentRevision = (int) $answer->revision_number;
                if ((int) $baseRevision !== $currentRevision) {
                    return response()->json([
                        'success' => false,
                        'error' => 'ANSWER_REVISION_CONFLICT',
                        'current_revision' => $currentRevision,
                        'current_answer' => [
                            'answer_payload' => $answer->answer_payload,
                            'answer_state' => $answer->answer_state,
                        ],
                        'server_now' => $serverNow->toIso8601String()
                    ], 409);
                }

                $answer->update([
                    'answer_payload' => $validated['answer_payload'],
                    'answer_state' => $validated['answer_state'],
                    'revision_number' => $answer->revision_number + 1,
                    'client_request_id' => $validated['client_request_id'],
                    'answered_at' => $validated['answer_state'] === 'ANSWERED' ? $serverNow : $answer->answered_at,
                    'saved_at' => $serverNow
                ]);
                $lockedAttempt->update(['last_activity_at' => $serverNow]);
            } else {
                // A new answer starts at revision zero. A client claiming a future
                // revision has stale or corrupted local state and must resynchronize.
                if ((int) $baseRevision !== 0) {
                    return response()->json([
                        'success' => false,
                        'error' => 'ANSWER_REVISION_CONFLICT',
                        'current_revision' => 0,
                        'current_answer' => null,
                        'server_now' => $serverNow->toIso8601String(),
                    ], 409);
                }

                $answer = ExamAttemptAnswer::create([
                    'tenant_id' => $exam->tenant_id,
                    'exam_id' => $exam->id,
                    'exam_attempt_id' => $lockedAttempt->id,
                    'exam_question_snapshot_id' => $snapshot->id,
                    'student_id' => $user->id,
                    'answer_payload' => $validated['answer_payload'],
                    'answer_state' => $validated['answer_state'],
                    'revision_number' => 1,
                    'client_request_id' => $validated['client_request_id'],
                    'answered_at' => $validated['answer_state'] === 'ANSWERED' ? $serverNow : null,
                    'saved_at' => $serverNow
                ]);
                $lockedAttempt->update(['last_activity_at' => $serverNow]);
            }

            $this->auditService->log('SAVE_ANSWER', 'ExamAttemptAnswer', $answer->id, null, [
                'attempt_id' => $lockedAttempt->id,
                'snapshot_id' => $snapshot->id,
                'state' => $validated['answer_state']
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'attempt_id' => $lockedAttempt->id,
                    'snapshot_id' => $snapshot->id,
                    'revision_number' => $answer->revision_number,
                    'answer_state' => $answer->answer_state,
                    'server_now' => $serverNow->toIso8601String(),
                    'saved_at' => $answer->saved_at->toIso8601String(),
                ]
            ]);
        });
    }

    protected function validatePayload(ExamQuestionSnapshot $snapshot, ?array $payload, string $state)
    {
        if ($state === 'CLEARED' || $state === 'UNANSWERED') return;
        if (is_null($payload)) {
            abort(422, 'Answered state requires an answer payload.');
        }

        $metadata = $snapshot->metadata;
        $type = $metadata['question_type'] ?? 'MC';
        $options = $snapshot->snapshot_options;
        $optionIds = collect($options)->pluck('id')->toArray();

        switch ($type) {
            case 'MC':
            case 'MULTIPLE_CHOICE':
            case 'TRUE_FALSE':
                if (!isset($payload['selected_option_id']) || !in_array($payload['selected_option_id'], $optionIds)) {
                    abort(422, 'Invalid option selected.');
                }
                break;
            case 'MULTIPLE_RESPONSE':
                if (!isset($payload['selected_option_ids']) || !is_array($payload['selected_option_ids'])) {
                    abort(422, 'Invalid options format.');
                }
                foreach ($payload['selected_option_ids'] as $id) {
                    if (!in_array($id, $optionIds)) abort(422, "Invalid option ID: $id");
                }
                break;
            case 'MATCHING':
                if (!isset($payload['matches']) || !is_array($payload['matches'])) {
                    abort(422, 'Invalid matching format.');
                }

                $leftIds = collect($options)
                    ->filter(fn ($option) => is_array($option) && !empty($option['option_key']))
                    ->pluck('id')->filter()->map(fn ($id) => (string) $id)->values()->all();
                $rightIds = collect($options)
                    ->filter(fn ($option) => is_array($option) && !empty($option['matching_key']))
                    ->pluck('id')->filter()->map(fn ($id) => (string) $id)->values()->all();

                if (!$leftIds || !$rightIds || count($payload['matches']) > count($leftIds)) {
                    abort(422, 'Matching options are incomplete or exceed the available pairs.');
                }

                $usedLeft = [];
                $usedRight = [];
                foreach ($payload['matches'] as $match) {
                    if (!is_array($match)
                        || !isset($match['left_id'], $match['right_id'])
                        || !is_string($match['left_id'])
                        || !is_string($match['right_id'])
                        || !in_array($match['left_id'], $leftIds, true)
                        || !in_array($match['right_id'], $rightIds, true)
                        || isset($usedLeft[$match['left_id']])
                        || isset($usedRight[$match['right_id']])) {
                        abort(422, 'Invalid or duplicate matching pair.');
                    }

                    $usedLeft[$match['left_id']] = true;
                    $usedRight[$match['right_id']] = true;
                }
                break;
            case 'SHORT_ANSWER':
            case 'ESSAY':
                if (!array_key_exists('text', $payload)) {
                    abort(422, 'Text answer missing.');
                }
                if ($payload['text'] !== null && !is_string($payload['text'])) {
                    abort(422, 'Invalid text answer format.');
                }
                break;
        }
    }
}
