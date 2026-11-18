<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\Rombel;
use App\Models\ExamParticipant;
use App\Models\ExamQuestionSnapshot;
use App\Services\AuditService;
use App\Services\ExamSnapshotService;
use App\Support\Security\TeacherAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ExamController extends Controller
{
    public function __construct(
        protected ExamSnapshotService $snapshotService,
        protected AuditService $auditService
    ) {
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Exam::class);
        $query = Exam::with(['assessment']);

        if ($request->user()->hasRole('SISWA')) {
            $query->whereHas('participants', function($q) use ($request) {
                $q->where('user_id', $request->user()->id);
            });
        }

        if ($request->user()->hasRole('GURU')) {
            $query = TeacherAccess::constrainExamQuery($query, $request->user());
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $paginator = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem()
            ]
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Exam::class);
        $tenantId = $request->user()->tenant_id;
        $validated = $request->validate([
            'assessment_id' => ['required', 'uuid', Rule::exists('assessments', 'id')->where('tenant_id', $tenantId)],
            'title' => 'required|string|max:255',
            'instructions' => 'nullable|string',
            'duration_seconds' => 'required|integer|min:60',
            'start_at' => 'required|date',
            'end_at' => 'required|date|after:start_at',
            'timezone' => 'required|string',
            'attempt_limit' => 'integer|min:1',
            'shuffle_questions' => 'boolean',
            'shuffle_options' => 'boolean',
        ]);

        if ($request->user()->hasRole('GURU')) {
            $assessment = \App\Models\Assessment::query()->findOrFail($validated['assessment_id']);
            if (!TeacherAccess::canManageAssessment($request->user(), $assessment)
                || $assessment->created_by !== $request->user()->id) {
                return response()->json(['success' => false, 'message' => 'Guru hanya dapat membuat ujian untuk asesmen miliknya pada penugasan aktif.'], 403);
            }
        }

        $exam = Exam::create(array_merge($validated, [
            'tenant_id' => $tenantId, // Explicitly set for safety
            'created_by' => $request->user()->id,
            'status' => 'DRAFT'
        ]));

        $this->auditService->log('CREATE', 'Exam', $exam->id, null, $exam->toArray());

        return response()->json(['success' => true, 'data' => $exam], 201);
    }

    public function show(Exam $exam)
    {
        $this->authorize('view', $exam);

        $data = $exam->load(['assessment']);

        if (auth()->user()->hasRole('SISWA')) {
            // Students may see exam metadata before the schedule, but question
            // snapshots are released only while the server says the exam is ongoing.
            if ($exam->canStartAttempt()) {
                $data->load('snapshots');
            }

            $latestAttempt = \App\Models\ExamAttempt::where('exam_id', $exam->id)
                ->whereHas('participant', function($q) {
                    $q->where('user_id', auth()->id());
                })
                ->latest()
                ->first();

            $data = $data->toArray();

            // Security: Sanitize snapshots in relationships
            if (isset($data['snapshots'])) {
                foreach ($data['snapshots'] as &$snapshot) {
                    unset($snapshot['snapshot_explanation']);

                    if (isset($snapshot['metadata']) && is_array($snapshot['metadata'])) {
                        unset($snapshot['metadata']['accepted_answers']);
                        unset($snapshot['metadata']['rubric']);
                        unset($snapshot['metadata']['answer_key']);
                        unset($snapshot['metadata']['scoring_guide']);
                    }

                    $qType = $snapshot['metadata']['question_type'] ?? null;
                    if (in_array($qType, ['SHORT_ANSWER', 'ESSAY'])) {
                        $snapshot['snapshot_options'] = [];
                    } elseif (isset($snapshot['snapshot_options']) && is_array($snapshot['snapshot_options'])) {
                        $snapshot['snapshot_options'] = array_map(function($opt) {
                            unset($opt['is_correct']);
                            unset($opt['score_weight']);
                            if (isset($opt['option_key']) && $opt['option_key'] !== null && $opt['option_key'] !== '') {
                                $opt['option_key'] = 'LEFT';
                            }
                            if (isset($opt['matching_key']) && $opt['matching_key'] !== null && $opt['matching_key'] !== '') {
                                $opt['matching_key'] = 'RIGHT';
                            }
                            return $opt;
                        }, $snapshot['snapshot_options']);
                    }
                }
            }

            $data['my_latest_attempt_id'] = $latestAttempt ? $latestAttempt->id : null;
            $data['my_latest_attempt_status'] = $latestAttempt ? $latestAttempt->status : null;

            return response()->json(['success' => true, 'data' => $data]);
        }

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function update(Request $request, Exam $exam)
    {
        $this->authorize('update', $exam);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'instructions' => 'nullable|string',
            'duration_seconds' => 'sometimes|required|integer|min:60',
            'start_at' => 'sometimes|required|date',
            'end_at' => 'sometimes|required|date|after:start_at',
            'timezone' => 'sometimes|string',
            'attempt_limit' => 'sometimes|integer|min:1',
            'shuffle_questions' => 'sometimes|boolean',
            'shuffle_options' => 'sometimes|boolean',
        ]);

        $oldValues = $exam->toArray();
        $exam->update($validated);

        $this->auditService->log('UPDATE', 'Exam', $exam->id, $oldValues, $exam->toArray());

        return response()->json(['success' => true, 'data' => $exam]);
    }

    public function addQuestions(Request $request, Exam $exam)
    {
        $this->authorize('manageQuestions', $exam);

        if (!$exam->canBeEdited()) {
            return response()->json(['success' => false, 'message' => 'Locked exams cannot have questions modified.'], 422);
        }

        $validated = $request->validate([
            'question_version_ids' => 'required|array',
            'question_version_ids.*' => ['uuid', Rule::exists('question_versions', 'id')->where('tenant_id', $exam->tenant_id)],
            'append' => 'boolean'
        ]);

        $this->snapshotService->createSnapshots($exam, $validated['question_version_ids'], $request->boolean('append', true));

        $this->auditService->log('ADD_QUESTIONS', 'Exam', $exam->id, null, ['count' => count($validated['question_version_ids'])]);

        return response()->json(['success' => true]);
    }

    public function assignRombel(Request $request, Exam $exam)
    {
        $this->authorize('manageParticipants', $exam);

        $validated = $request->validate([
            'rombel_id' => ['required', 'uuid', Rule::exists('rombels', 'id')->where('tenant_id', $exam->tenant_id)]
        ]);

        $rombel = Rombel::query()->findOrFail($validated['rombel_id']);

        if ($request->user()->hasRole('GURU')) {
            $assessment = $exam->assessment;
            if (!$assessment || !TeacherAccess::canAssignRombelForAssessment($request->user(), $rombel, $assessment)) {
                return response()->json(['success' => false, 'message' => 'Rombel tidak tercakup dalam satu penugasan aktif Guru yang cocok dengan mapel, tahun ajaran, dan semester asesmen ini.'], 403);
            }
        }

        // Migration 2026_11_09_230003 adds tenant-aware pivot FKs; retain a runtime guard
        // so legacy/corrupt roster data is rejected rather than partially assigned.
        $validStudents = $rombel->students()
            ->with('user')
            ->where('student_profiles.tenant_id', $exam->tenant_id)
            ->whereHas('user', fn ($query) => $query->where('tenant_id', $exam->tenant_id))
            ->get();
        $allStudentCount = $rombel->students()->count();
        if ($validStudents->count() !== $allStudentCount || $validStudents->contains(
            fn ($student) => !$student->user || !$student->user->hasRole('SISWA')
        )) {
            return response()->json([
                'success' => false,
                'message' => 'Data keanggotaan rombel mengandung relasi lintas tenant atau profil yang tidak terhubung ke akun Siswa aktif. Perlu koreksi data akademik oleh Admin.',
            ], 409);
        }

        DB::transaction(function () use ($exam, $validStudents) {
            foreach ($validStudents as $student) {
                ExamParticipant::updateOrCreate(
                    ['exam_id' => $exam->id, 'user_id' => $student->user_id],
                    ['tenant_id' => $exam->tenant_id, 'status' => 'ASSIGNED']
                );
            }
        });

        $this->auditService->log('ASSIGN_ROMBEL', 'Exam', $exam->id, null, ['rombel_id' => $rombel->id]);

        return response()->json(['success' => true, 'message' => 'Rombel students assigned.']);
    }

    public function generateToken(Exam $exam)
    {
        $this->authorize('generateToken', $exam);

        $token = DB::transaction(function () use ($exam) {
            $lockedExam = Exam::query()
                ->where('tenant_id', $exam->tenant_id)
                ->whereKey($exam->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->authorize('generateToken', $lockedExam);
            if ($lockedExam->status !== 'PUBLISHED' || !$lockedExam->locked_at) {
                abort(409, 'Token hanya tersedia setelah ujian diterbitkan dan dikunci.');
            }

            $previousTokenPresent = filled($lockedExam->token);
            $newToken = strtoupper(Str::random(6));
            $lockedExam->update(['token' => $newToken, 'require_token' => true]);

            $this->auditService->log(
                'ROTATE_EXAM_TOKEN',
                'Exam',
                $lockedExam->id,
                ['token_present' => $previousTokenPresent],
                ['token_present' => true]
            );

            return $newToken;
        });

        return response()->json(['success' => true, 'token' => $token]);
    }

    public function lock(Exam $exam)
    {
        $this->authorize('lock', $exam);

        if ($exam->snapshots()->count() === 0) {
            return response()->json(['success' => false, 'message' => 'Cannot lock exam without questions.'], 422);
        }

        $publishedExam = DB::transaction(function () use ($exam) {
            $lockedExam = Exam::query()->whereKey($exam->id)->lockForUpdate()->firstOrFail();
            $this->authorize('lock', $lockedExam);

            if ($lockedExam->status !== 'DRAFT' || $lockedExam->locked_at) {
                abort(409, 'Ujian sudah diterbitkan atau tidak lagi berstatus draf.');
            }
            if ($lockedExam->snapshots()->count() === 0) {
                abort(422, 'Cannot lock exam without questions.');
            }

            $publishedAt = now();
            $tokenGenerated = (bool) ($lockedExam->require_token && !$lockedExam->token);
            $lockedExam->update([
                'status' => 'PUBLISHED',
                'locked_at' => $publishedAt,
                'published_at' => $publishedAt,
                'token' => $tokenGenerated ? strtoupper(Str::random(6)) : $lockedExam->token,
            ]);

            $this->auditService->log('PUBLISH_EXAM', 'Exam', $lockedExam->id, ['status' => 'DRAFT'], [
                'status' => 'PUBLISHED',
                'published_at' => $publishedAt->toISOString(),
                'token_generated' => $tokenGenerated,
            ]);

            return $lockedExam->fresh();
        });

        return response()->json(['success' => true, 'data' => $publishedExam]);
    }

    public function removeQuestion(Exam $exam, ExamQuestionSnapshot $snapshot)
    {
        $this->authorize('manageQuestions', $exam);

        if (!$exam->canBeEdited()) {
            return response()->json(['success' => false, 'message' => 'Locked exams cannot have questions modified.'], 422);
        }

        if ($snapshot->exam_id !== $exam->id) {
            return response()->json(['success' => false, 'message' => 'Snapshot not found in this exam.'], 404);
        }

        $snapshot->delete();
        $this->auditService->log('REMOVE_QUESTION', 'Exam', $exam->id, ['snapshot_id' => $snapshot->id]);
        return response()->json(['success' => true]);
    }

    public function reorderQuestions(Request $request, Exam $exam)
    {
        $this->authorize('manageQuestions', $exam);

        if (!$exam->canBeEdited()) {
            return response()->json(['success' => false, 'message' => 'Locked exams cannot have questions modified.'], 422);
        }

        $validated = $request->validate([
            'orders' => 'required|array',
            'orders.*.snapshot_id' => 'required|uuid|exists:exam_question_snapshots,id',
            'orders.*.sequence' => 'required|integer'
        ]);

        DB::transaction(function () use ($exam, $validated) {
            foreach ($validated['orders'] as $order) {
                $exam->snapshots()->where('id', $order['snapshot_id'])->update(['sequence' => $order['sequence']]);
            }
        });

        $this->auditService->log('REORDER_QUESTIONS', 'Exam', $exam->id);
        return response()->json(['success' => true]);
    }

    public function bulkRemoveQuestions(Request $request, Exam $exam)
    {
        $this->authorize('manageQuestions', $exam);

        if (!$exam->canBeEdited()) {
            return response()->json(['success' => false, 'message' => 'Locked exams cannot have questions modified.'], 422);
        }

        $validated = $request->validate([
            'snapshot_ids' => 'required|array',
            'snapshot_ids.*' => 'uuid|exists:exam_question_snapshots,id'
        ]);

        DB::transaction(function () use ($exam, $validated) {
            $exam->snapshots()->whereIn('id', $validated['snapshot_ids'])->delete();
        });

        $this->auditService->log('BULK_REMOVE_QUESTIONS', 'Exam', $exam->id, null, ['count' => count($validated['snapshot_ids'])]);

        return response()->json(['success' => true]);
    }

    public function preview(Exam $exam)
    {
        abort_unless(!auth()->user()->hasRole('SISWA'), 403, 'Preview soal hanya tersedia untuk petugas yang berwenang.');
        $this->authorize('view', $exam);

        $snapshots = $exam->snapshots()->orderBy('sequence')->get();
        $isStudent = auth()->user()->hasRole('SISWA');

        return response()->json([
            'success' => true,
            'data' => [
                'exam' => $exam,
                'questions' => $snapshots->map(function ($q) use ($isStudent) {
                    $options = $q->snapshot_options;
                    $metadata = $q->metadata ?? [];

                    if ($isStudent) {
                        unset($metadata['accepted_answers']);
                        unset($metadata['rubric']);
                        unset($metadata['answer_key']);
                        unset($metadata['scoring_guide']);

                        $qType = $metadata['question_type'] ?? null;
                        if (in_array($qType, ['SHORT_ANSWER', 'ESSAY'])) {
                            $options = [];
                        } elseif (is_array($options)) {
                            $options = array_map(function($opt) {
                                unset($opt['is_correct']);
                                unset($opt['score_weight']);
                                if (isset($opt['option_key']) && $opt['option_key'] !== null && $opt['option_key'] !== '') {
                                    $opt['option_key'] = 'LEFT';
                                }
                                if (isset($opt['matching_key']) && $opt['matching_key'] !== null && $opt['matching_key'] !== '') {
                                    $opt['matching_key'] = 'RIGHT';
                                }
                                return $opt;
                            }, $options);
                        }
                    }

                    return [
                        'id' => $q->id,
                        'sequence' => $q->sequence,
                        'points' => $q->points,
                        'snapshot_stem' => $q->snapshot_stem,
                        'snapshot_options' => $options,
                        'snapshot_media' => $q->snapshot_media,
                        'metadata' => $metadata,
                    ];
                })
            ]
        ]);
    }

    public function close(Exam $exam)
    {
        $this->authorize('close', $exam);

        $closedExam = DB::transaction(function () use ($exam) {
            $lockedExam = Exam::query()
                ->where('tenant_id', $exam->tenant_id)
                ->whereKey($exam->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->authorize('close', $lockedExam);

            if ($lockedExam->status === 'CLOSED') {
                return $lockedExam;
            }

            if ($lockedExam->status !== 'PUBLISHED' || !$lockedExam->locked_at) {
                abort(409, 'Hanya ujian yang sudah diterbitkan dan dikunci yang dapat ditutup.');
            }

            $lockedExam->update(['status' => 'CLOSED']);
            $this->auditService->log(
                'CLOSE_EXAM',
                'Exam',
                $lockedExam->id,
                ['status' => 'PUBLISHED'],
                ['status' => 'CLOSED']
            );

            return $lockedExam->fresh();
        });

        return response()->json(['success' => true, 'data' => $closedExam]);
    }

    public function archive(Exam $exam)
    {
        $this->authorize('delete', $exam);
        $exam->update(['status' => 'ARCHIVED']);
        $this->auditService->log('ARCHIVE', 'Exam', $exam->id);
        return response()->json(['success' => true]);
    }

    public function publishResults(Request $request, Exam $exam)
    {
        $this->authorize('publishResults', $exam);

        $validated = $request->validate([
            'show_result' => 'required|boolean'
        ]);

        $showResult = $validated['show_result'];
        $oldValue = $exam->show_result;

        $exam->update(['show_result' => $showResult]);

        $this->auditService->log(
            $showResult ? 'PUBLISH_EXAM_RESULTS' : 'UNPUBLISH_EXAM_RESULTS',
            'Exam',
            $exam->id,
            ['show_result' => $oldValue],
            ['show_result' => $showResult]
        );

        app(\App\Services\Analytics\AnalyticsCacheService::class)->invalidateExamAnalytics($exam);

        return response()->json([
            'success' => true,
            'message' => $showResult ? 'Hasil ujian berhasil dipublikasikan.' : 'Publikasi hasil ujian berhasil ditarik.',
            'data' => $exam->fresh()
        ]);
    }

    public function destroy(Exam $exam)
    {
        $this->authorize('delete', $exam);
        if ($exam->attempts()->count() > 0) {
            return response()->json(['success' => false, 'message' => 'Cannot delete exam with existing attempts. Use archive instead.'], 422);
        }

        $this->auditService->log('DELETE', 'Exam', $exam->id, $exam->toArray(), null);
        $exam->delete();

        return response()->json(['success' => true]);
    }
}
