<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptAnswer;
use App\Models\ExamParticipant;
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
        $user = $request->user();
        if (!$user || !$user->hasExactRole('SISWA')) {
            return response()->json(['success' => false, 'message' => 'Hanya akun Siswa aktif dengan satu role yang dapat mengakses jawaban ujian.'], 403);
        }

        $deviceId = $request->header('X-Device-Binding-ID');
        if (!$deviceId) {
            return response()->json([
                'success' => false,
                'message' => 'Device binding identifier missing.',
                'code' => 'MISSING_DEVICE_BINDING'
            ], 400);
        }

        return DB::transaction(function () use ($exam, $attempt, $user, $deviceId) {
            // Canonical read/write lock order: Exam -> Participant -> Attempt -> Answer.
            $lockedExam = Exam::query()
                ->where('tenant_id', $user->tenant_id)
                ->whereKey($exam->id)
                ->lockForUpdate()
                ->firstOrFail();

            $participant = ExamParticipant::query()
                ->where('tenant_id', $lockedExam->tenant_id)
                ->where('exam_id', $lockedExam->id)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (!$participant) {
                return response()->json(['success' => false, 'message' => 'Participant not found for this exam.'], 404);
            }

            $lockedAttempt = ExamAttempt::query()
                ->where('tenant_id', $lockedExam->tenant_id)
                ->where('exam_id', $lockedExam->id)
                ->where('participant_id', $participant->id)
                ->whereKey($attempt->id)
                ->lockForUpdate()
                ->first();

            if (!$lockedAttempt) {
                return response()->json(['success' => false, 'message' => 'Attempt not found for this participant.'], 404);
            }

            if ($lockedAttempt->status !== 'IN_PROGRESS'
                || !$lockedAttempt->expires_at
                || $lockedAttempt->expires_at->lte(now())) {
                return response()->json([
                    'success' => false,
                    'message' => 'Attempt is no longer resumable.',
                    'code' => 'ATTEMPT_NOT_RESUMABLE'
                ], 409);
            }

            $activeAttemptCount = ExamAttempt::query()
                ->where('tenant_id', $lockedExam->tenant_id)
                ->where('exam_id', $lockedExam->id)
                ->where('participant_id', $participant->id)
                ->where('status', 'IN_PROGRESS')
                ->where('expires_at', '>', now())
                ->count();

            if ($activeAttemptCount !== 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Jumlah Attempt aktif peserta tidak konsisten; pembacaan jawaban ditahan.',
                    'code' => 'ATTEMPT_INTEGRITY_CONFLICT'
                ], 409);
            }

            if (!$lockedAttempt->device_fingerprint
                || !hash_equals((string) $lockedAttempt->device_fingerprint, (string) $deviceId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sesi ujian ini tidak terikat pada perangkat yang mengajukan permintaan.',
                    'code' => 'CONCURRENT_DEVICE_DENIED'
                ], 409);
            }

            $assignedSnapshotIds = is_array($lockedAttempt->question_order)
                ? array_map('strval', $lockedAttempt->question_order)
                : [];

            $answers = ExamAttemptAnswer::query()
                ->where('tenant_id', $lockedExam->tenant_id)
                ->where('exam_id', $lockedExam->id)
                ->where('exam_attempt_id', $lockedAttempt->id)
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

            return response()->json(['success' => true, 'data' => $answers]);
        });
    }

    public function update(Request $request, Exam $exam, ExamAttempt $attempt, ExamQuestionSnapshot $snapshot)
    {
        $user = $request->user();
        if (!$user || !$user->hasExactRole('SISWA')) {
            return response()->json(['success' => false, 'message' => 'Hanya akun Siswa aktif dengan satu role yang dapat menyimpan jawaban ujian.'], 403);
        }

        $deviceId = $request->header('X-Device-Binding-ID');
        if (!$deviceId) {
            return response()->json([
                'success' => false,
                'message' => 'Device binding identifier missing.',
                'code' => 'MISSING_DEVICE_BINDING'
            ], 400);
        }

        $validated = $request->validate([
            'client_request_id' => 'required|string|max:255',
            'base_revision' => 'nullable|integer|min:0',
            'answer_payload' => 'nullable|array',
            'answer_state' => 'required|in:ANSWERED,CLEARED,UNANSWERED',
        ]);

        return DB::transaction(function () use ($exam, $attempt, $snapshot, $user, $validated, $deviceId) {
            // Every writer takes the same parent locks before touching an Answer.
            $lockedExam = Exam::query()
                ->where('tenant_id', $user->tenant_id)
                ->whereKey($exam->id)
                ->lockForUpdate()
                ->firstOrFail();

            $participant = ExamParticipant::query()
                ->where('tenant_id', $lockedExam->tenant_id)
                ->where('exam_id', $lockedExam->id)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (!$participant) {
                return response()->json(['success' => false, 'message' => 'Unauthorized participant.'], 403);
            }

            $lockedAttempt = ExamAttempt::query()
                ->where('tenant_id', $lockedExam->tenant_id)
                ->where('exam_id', $lockedExam->id)
                ->where('participant_id', $participant->id)
                ->whereKey($attempt->id)
                ->lockForUpdate()
                ->first();

            if (!$lockedAttempt) {
                return response()->json(['success' => false, 'message' => 'Unauthorized attempt.'], 404);
            }

            if ($lockedAttempt->status !== 'IN_PROGRESS') {
                return response()->json([
                    'success' => false,
                    'message' => 'Attempt is not in progress.',
                    'code' => 'ATTEMPT_CLOSED'
                ], 422);
            }

            $serverNow = now();
            if (!$lockedAttempt->expires_at || $lockedAttempt->expires_at->lte($serverNow)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Attempt has expired.',
                    'code' => 'ATTEMPT_EXPIRED'
                ], 422);
            }

            if (!$lockedAttempt->device_fingerprint
                || !hash_equals((string) $lockedAttempt->device_fingerprint, (string) $deviceId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sesi ujian ini tidak terikat pada perangkat yang mengajukan permintaan.',
                    'code' => 'CONCURRENT_DEVICE_DENIED'
                ], 409);
            }

            $activeAttemptCount = ExamAttempt::query()
                ->where('tenant_id', $lockedExam->tenant_id)
                ->where('exam_id', $lockedExam->id)
                ->where('participant_id', $participant->id)
                ->where('status', 'IN_PROGRESS')
                ->where('expires_at', '>', $serverNow)
                ->count();

            if ($activeAttemptCount !== 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Jumlah Attempt aktif peserta tidak konsisten; penyimpanan jawaban ditahan.',
                    'code' => 'ATTEMPT_INTEGRITY_CONFLICT'
                ], 409);
            }

            $lockedSnapshot = ExamQuestionSnapshot::query()
                ->where('tenant_id', $lockedExam->tenant_id)
                ->where('exam_id', $lockedExam->id)
                ->whereKey($snapshot->id)
                ->firstOrFail();

            $assignedSnapshotIds = is_array($lockedAttempt->question_order)
                ? array_map('strval', $lockedAttempt->question_order)
                : [];

            if (!in_array((string) $lockedSnapshot->id, $assignedSnapshotIds, true)) {
                return response()->json(['success' => false, 'message' => 'Snapshot is not assigned to this attempt.'], 404);
            }

            // Validate against the immutable snapshot loaded under the same attempt lock.
            $this->validatePayload($lockedSnapshot, $validated['answer_payload'], $validated['answer_state']);

            // Idempotency keys are scoped to the attempt, not merely one question row.
            // Reusing a key for a different payload/snapshot is a conflict, never a second mutation.
            $requestReplay = ExamAttemptAnswer::query()
                ->where('tenant_id', $lockedExam->tenant_id)
                ->where('exam_id', $lockedExam->id)
                ->where('exam_attempt_id', $lockedAttempt->id)
                ->where('client_request_id', $validated['client_request_id'])
                ->lockForUpdate()
                ->first();

            if ($requestReplay) {
                $sameRequest = (string) $requestReplay->exam_question_snapshot_id === (string) $lockedSnapshot->id
                    && $requestReplay->answer_state === $validated['answer_state']
                    && $requestReplay->answer_payload == $validated['answer_payload'];

                if (!$sameRequest) {
                    return response()->json([
                        'success' => false,
                        'code' => 'IDEMPOTENCY_KEY_REUSED',
                        'message' => 'client_request_id sudah digunakan untuk mutasi jawaban yang berbeda.'
                    ], 409);
                }

                return response()->json([
                    'success' => true,
                    'data' => [
                        'attempt_id' => $lockedAttempt->id,
                        'snapshot_id' => $lockedSnapshot->id,
                        'revision_number' => $requestReplay->revision_number,
                        'answer_state' => $requestReplay->answer_state,
                        'server_now' => $serverNow->toIso8601String(),
                        'saved_at' => $requestReplay->saved_at?->toIso8601String(),
                    ],
                    'idempotent' => true,
                    'message' => 'Request yang sama telah diterima sebelumnya.'
                ]);
            }

            $answer = ExamAttemptAnswer::query()
                ->where('tenant_id', $lockedExam->tenant_id)
                ->where('exam_id', $lockedExam->id)
                ->where('exam_attempt_id', $lockedAttempt->id)
                ->where('exam_question_snapshot_id', $lockedSnapshot->id)
                ->lockForUpdate()
                ->first();

            $baseRevision = (int) ($validated['base_revision'] ?? 0);

            if ($answer) {
                $currentRevision = (int) $answer->revision_number;
                if ($baseRevision !== $currentRevision) {
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
                    'revision_number' => $currentRevision + 1,
                    'client_request_id' => $validated['client_request_id'],
                    'answered_at' => $validated['answer_state'] === 'ANSWERED' ? $serverNow : $answer->answered_at,
                    'saved_at' => $serverNow,
                ]);
            } else {
                if ($baseRevision !== 0) {
                    return response()->json([
                        'success' => false,
                        'error' => 'ANSWER_REVISION_CONFLICT',
                        'current_revision' => 0,
                        'current_answer' => null,
                        'server_now' => $serverNow->toIso8601String(),
                    ], 409);
                }

                $answer = ExamAttemptAnswer::create([
                    'tenant_id' => $lockedExam->tenant_id,
                    'exam_id' => $lockedExam->id,
                    'exam_attempt_id' => $lockedAttempt->id,
                    'exam_question_snapshot_id' => $lockedSnapshot->id,
                    'student_id' => $user->id,
                    'answer_payload' => $validated['answer_payload'],
                    'answer_state' => $validated['answer_state'],
                    'revision_number' => 1,
                    'client_request_id' => $validated['client_request_id'],
                    'answered_at' => $validated['answer_state'] === 'ANSWERED' ? $serverNow : null,
                    'saved_at' => $serverNow,
                ]);
            }

            $lockedAttempt->update(['last_activity_at' => $serverNow]);

            $this->auditService->log('SAVE_ANSWER', 'ExamAttemptAnswer', $answer->id, null, [
                'attempt_id' => $lockedAttempt->id,
                'snapshot_id' => $lockedSnapshot->id,
                'state' => $validated['answer_state'],
                'revision_number' => $answer->revision_number,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'attempt_id' => $lockedAttempt->id,
                    'snapshot_id' => $lockedSnapshot->id,
                    'revision_number' => $answer->revision_number,
                    'answer_state' => $answer->answer_state,
                    'server_now' => $serverNow->toIso8601String(),
                    'saved_at' => $answer->saved_at?->toIso8601String(),
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
                abort(422, 'SHORT_ANSWER belum diaktifkan karena accepted answers, snapshot immutable, dan scoring belum konsisten.');
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
