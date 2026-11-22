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

        if ($request->user()->hasExactRole('SISWA')) {
            $query->whereHas('participants', function($q) use ($request) {
                $q->where('user_id', $request->user()->id);
            });
        }

        if ($request->user()->hasExactRole('GURU')) {
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

        if ($request->user()->hasExactRole('GURU')) {
            $assessment = \App\Models\Assessment::query()->findOrFail($validated['assessment_id']);
            if (!TeacherAccess::canManageAssessment($request->user(), $assessment)
                || $assessment->created_by !== $request->user()->id) {
                return response()->json(['success' => false, 'message' => 'Guru hanya dapat membuat ujian untuk asesmen miliknya pada penugasan aktif.'], 403);
            }
        }

        $exam = Exam::create(array_merge($validated, [
            'tenant_id' => $tenantId, // Explicitly set for safety
            'created_by' => $request->user()->id,
            'status' => 'DRAFT',
            'require_token' => true,
        ]));

        $this->auditService->log('CREATE', 'Exam', $exam->id, null, $exam->toArray());

        return response()->json(['success' => true, 'data' => $exam], 201);
    }

    public function show(Exam $exam)
    {
        $this->authorize('view', $exam);

        $data = $exam->load(['assessment']);

        if (auth()->user()->hasExactRole('SISWA')) {
            // Question snapshots are never released by the metadata endpoint.
            // They are available only through execution after token/eligibility admission.
            $latestAttempt = \App\Models\ExamAttempt::where('exam_id', $exam->id)
                ->whereHas('participant', function($q) {
                    $q->where('user_id', auth()->id());
                })
                ->latest()
                ->first();

            $data = $data->toArray();
            unset($data['snapshots']);

            // Defense in depth: if an upstream relation was preloaded, snapshots
            // are still removed from the student metadata response.
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
        $validated['require_token'] = true;
        $exam->update($validated);

        $this->auditService->log('UPDATE', 'Exam', $exam->id, $oldValues, $exam->toArray());

        return response()->json(['success' => true, 'data' => $exam]);
    }

    public function addQuestions(Request $request, Exam $exam)
    {
        // Fast authorization before validation; the authoritative check is repeated
        // after acquiring the same exam row lock used by publish and attempt creation.
        $this->authorize('manageQuestions', $exam);

        $validated = $request->validate([
            'question_version_ids' => 'required|array',
            'question_version_ids.*' => ['uuid', Rule::exists('question_versions', 'id')->where('tenant_id', $exam->tenant_id)],
            'append' => 'boolean'
        ]);

        return DB::transaction(function () use ($request, $exam, $validated) {
            $lockedExam = Exam::query()
                ->where('tenant_id', $exam->tenant_id)
                ->whereKey($exam->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->authorize('manageQuestions', $lockedExam);
            if (!$lockedExam->canBeEdited()) {
                return response()->json(['success' => false, 'message' => 'Locked exams cannot have questions modified.'], 422);
            }

            $this->snapshotService->createSnapshots(
                $lockedExam,
                $validated['question_version_ids'],
                $request->boolean('append', true)
            );

            $this->auditService->log('ADD_QUESTIONS', 'Exam', $lockedExam->id, null, [
                'count' => count($validated['question_version_ids']),
            ]);

            return response()->json(['success' => true]);
        });
    }

    public function assignRombel(Request $request, Exam $exam)
    {
        $this->authorize('manageParticipants', $exam);

        $validated = $request->validate([
            'rombel_id' => ['required', 'uuid', Rule::exists('rombels', 'id')->where('tenant_id', $exam->tenant_id)]
        ]);

        DB::transaction(function () use ($request, $exam, $validated) {
            // Serialize roster changes with exam lifecycle transitions.
            $lockedExam = Exam::query()
                ->where('tenant_id', $exam->tenant_id)
                ->whereKey($exam->id)
                ->lockForUpdate()
                ->firstOrFail();
            $this->authorize('manageParticipants', $lockedExam);

            $rombel = Rombel::query()
                ->where('tenant_id', $lockedExam->tenant_id)
                ->whereKey($validated['rombel_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $assessment = $lockedExam->assessment;
            if (!$assessment || (string) $rombel->academic_year_id !== (string) $assessment->academic_year_id) {
                abort(422, 'Rombel harus berasal dari tahun ajaran yang sama dengan asesmen ujian.');
            }

            if ($request->user()->hasExactRole('GURU')
                && !TeacherAccess::canAssignRombelForAssessment($request->user(), $rombel, $assessment)) {
                abort(403, 'Rombel tidak tercakup dalam penugasan aktif Guru untuk mapel, tahun ajaran, dan semester asesmen ini.');
            }

            // Reject corrupt/cross-tenant roster membership before creating any participant.
            $validStudents = $rombel->students()
                ->with('user')
                ->where('student_profiles.tenant_id', $lockedExam->tenant_id)
                ->whereHas('user', fn ($query) => $query->where('tenant_id', $lockedExam->tenant_id))
                ->get();
            $allStudentCount = $rombel->students()->count();
            if ($validStudents->count() !== $allStudentCount || $validStudents->contains(
                fn ($student) => !$student->user || !$student->user->hasExactRole('SISWA')
            )) {
                abort(409, 'Data keanggotaan rombel mengandung relasi lintas tenant atau profil yang tidak terhubung ke akun Siswa aktif.');
            }

            foreach ($validStudents as $student) {
                // Add-only and idempotent: never reset STARTED/COMPLETED participant state.
                $participant = ExamParticipant::firstOrCreate(
                    [
                        'tenant_id' => $lockedExam->tenant_id,
                        'exam_id' => $lockedExam->id,
                        'user_id' => $student->user_id,
                    ],
                    ['status' => 'ASSIGNED', 'rombel_id' => $rombel->id]
                );

                // Never infer the source rombel for a legacy participant with NULL
                // snapshot, and never silently move an existing participant.
                if ($participant->rombel_id !== null
                    && (string) $participant->rombel_id !== (string) $rombel->id) {
                    abort(409, 'Peserta sudah terdaftar pada snapshot rombel lain untuk ujian ini.');
                }
            }

            $this->auditService->log('ASSIGN_ROMBEL', 'Exam', $lockedExam->id, null, [
                'rombel_id' => $rombel->id,
                'participant_count' => $validStudents->count(),
                'semantics' => 'ADD_ONLY',
            ]);
        });

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
            $tokenGenerated = !$lockedExam->token;
            $lockedExam->update([
                'status' => 'PUBLISHED',
                'locked_at' => $publishedAt,
                'published_at' => $publishedAt,
                'require_token' => true,
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

        return DB::transaction(function () use ($exam, $snapshot) {
            // Use the same serialization point as publish and attempt creation.
            $lockedExam = Exam::query()
                ->where('tenant_id', $exam->tenant_id)
                ->whereKey($exam->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->authorize('manageQuestions', $lockedExam);
            if (!$lockedExam->canBeEdited()) {
                return response()->json(['success' => false, 'message' => 'Locked exams cannot have questions modified.'], 422);
            }

            $lockedSnapshot = $lockedExam->snapshots()
                ->whereKey($snapshot->id)
                ->lockForUpdate()
                ->first();
            if (!$lockedSnapshot) {
                return response()->json(['success' => false, 'message' => 'Snapshot not found in this exam.'], 404);
            }

            if ($this->examHasSnapshotHistory($lockedExam, [$lockedSnapshot->id])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Snapshot tidak dapat dihapus karena ujian sudah memiliki attempt atau histori jawaban/nilai.',
                ], 409);
            }

            $lockedSnapshot->delete();
            $this->auditService->log('REMOVE_QUESTION', 'Exam', $lockedExam->id, ['snapshot_id' => $lockedSnapshot->id]);

            return response()->json(['success' => true]);
        });
    }

    public function reorderQuestions(Request $request, Exam $exam)
    {
        $this->authorize('manageQuestions', $exam);

        $validated = $request->validate([
            'orders' => 'required|array',
            'orders.*.snapshot_id' => 'required|uuid',
            'orders.*.sequence' => 'required|integer'
        ]);

        return DB::transaction(function () use ($exam, $validated) {
            $lockedExam = Exam::query()
                ->where('tenant_id', $exam->tenant_id)
                ->whereKey($exam->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->authorize('manageQuestions', $lockedExam);
            if (!$lockedExam->canBeEdited()) {
                return response()->json(['success' => false, 'message' => 'Locked exams cannot have questions modified.'], 422);
            }

            $snapshotIds = array_values(array_unique(array_column($validated['orders'], 'snapshot_id')));
            if (count($snapshotIds) !== count($validated['orders'])) {
                return response()->json(['success' => false, 'message' => 'Snapshot tidak boleh muncul lebih dari satu kali dalam urutan.'], 422);
            }
            $ownedCount = $lockedExam->snapshots()->whereIn('id', $snapshotIds)->count();
            if ($ownedCount !== count($snapshotIds)) {
                return response()->json(['success' => false, 'message' => 'Satu atau lebih snapshot tidak ditemukan pada ujian ini.'], 404);
            }

            if ($this->examHasSnapshotHistory($lockedExam, $snapshotIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Urutan snapshot tidak dapat diubah karena ujian sudah memiliki attempt atau histori jawaban/nilai.',
                ], 409);
            }

            foreach ($validated['orders'] as $order) {
                $lockedExam->snapshots()->where('id', $order['snapshot_id'])->update(['sequence' => $order['sequence']]);
            }

            $this->auditService->log('REORDER_QUESTIONS', 'Exam', $lockedExam->id);
            return response()->json(['success' => true]);
        });
    }

    public function bulkRemoveQuestions(Request $request, Exam $exam)
    {
        $this->authorize('manageQuestions', $exam);

        $validated = $request->validate([
            'snapshot_ids' => 'required|array',
            'snapshot_ids.*' => 'uuid'
        ]);

        return DB::transaction(function () use ($exam, $validated) {
            $lockedExam = Exam::query()
                ->where('tenant_id', $exam->tenant_id)
                ->whereKey($exam->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->authorize('manageQuestions', $lockedExam);
            if (!$lockedExam->canBeEdited()) {
                return response()->json(['success' => false, 'message' => 'Locked exams cannot have questions modified.'], 422);
            }

            $snapshotIds = array_values(array_unique($validated['snapshot_ids']));
            $ownedCount = $lockedExam->snapshots()->whereIn('id', $snapshotIds)->count();
            if ($ownedCount !== count($snapshotIds)) {
                return response()->json(['success' => false, 'message' => 'Satu atau lebih snapshot tidak ditemukan pada ujian ini.'], 404);
            }

            if ($this->examHasSnapshotHistory($lockedExam, $snapshotIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Snapshot tidak dapat dihapus karena ujian sudah memiliki attempt atau histori jawaban/nilai.',
                ], 409);
            }

            $lockedExam->snapshots()->whereIn('id', $snapshotIds)->delete();
            $this->auditService->log('BULK_REMOVE_QUESTIONS', 'Exam', $lockedExam->id, null, ['count' => count($snapshotIds)]);

            return response()->json(['success' => true]);
        });
    }

    /**
     * Snapshot identity and order become historical as soon as an attempt exists.
     * Check legacy answer/score rows as a second line of defense even if an attempt
     * row was removed or the data predates the attempt-retention invariant.
     */
    private function examHasSnapshotHistory(Exam $exam, array $snapshotIds): bool
    {
        if ($exam->attempts()->exists()) {
            return true;
        }

        if ($snapshotIds === []) {
            return false;
        }

        $hasAnswers = DB::table('exam_attempt_answers')
            ->where('tenant_id', $exam->tenant_id)
            ->where('exam_id', $exam->id)
            ->whereIn('exam_question_snapshot_id', $snapshotIds)
            ->exists();

        if ($hasAnswers) {
            return true;
        }

        return DB::table('exam_question_scores')
            ->where('tenant_id', $exam->tenant_id)
            ->where('exam_id', $exam->id)
            ->whereIn('snapshot_id', $snapshotIds)
            ->exists();
    }

    public function preview(Exam $exam)
    {
        $this->authorize('preview', $exam);

        $snapshots = $exam->snapshots()
            ->where('tenant_id', $exam->tenant_id)
            ->orderBy('sequence')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'exam' => [
                    'id' => $exam->id,
                    'title' => $exam->title,
                    'instructions' => $exam->instructions,
                    'duration_seconds' => $exam->duration_seconds,
                    'start_at' => $exam->start_at,
                    'end_at' => $exam->end_at,
                    'status' => $exam->status,
                ],
                'questions' => $snapshots->map(function ($question) {
                    return [
                        'id' => $question->id,
                        'sequence' => $question->sequence,
                        'points' => $question->points,
                        'snapshot_stem' => $question->snapshot_stem,
                        'snapshot_options' => $question->snapshot_options,
                        'snapshot_media' => $question->snapshot_media,
                        'metadata' => $question->metadata,
                    ];
                }),
            ],
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
