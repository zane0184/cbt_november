<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\QuestionVersion;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class QuestionController extends Controller
{
    private const ALLOWED_TYPES = [
        'MULTIPLE_CHOICE', 'MULTIPLE_RESPONSE', 'TRUE_FALSE',
        'MATCHING', 'SHORT_ANSWER', 'ESSAY'
    ];

    public function __construct(protected AuditService $auditService)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $this->authorize('viewAny', Question::class);
        $filters = $request->validate([
            'scope' => ['sometimes', Rule::in(['MY_OWN', 'MY_MADRASAH', 'SHARED'])],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'subject_id' => ['sometimes', 'uuid'],
            'curriculum_id' => ['sometimes', 'uuid'],
            'question_type' => ['sometimes', Rule::in(self::ALLOWED_TYPES)],
            'search' => ['sometimes', 'string', 'max:150'],
        ]);
        $query = Question::with(['currentVersion.options', 'currentVersion.learningOutcome.currentVersion', 'currentVersion.learningObjective', 'tags']);

        // Publication & Ownership Scoping
        $scope = $filters['scope'] ?? 'MY_MADRASAH'; // MY_OWN, MY_MADRASAH, SHARED

        switch ($scope) {
            case 'MY_OWN':
                $query->where('tenant_id', $user->tenant_id)
                    ->where('owner_type', 'PERSONAL')
                    ->where('owner_user_id', $user->id)
                    ->where(function ($q) use ($user) {
                        $q->whereNull('subject_id')
                            ->orWhereIn('subject_id', \App\Support\Security\TeacherAccess::assignedSubjectIds($user));
                    });
                break;
            case 'MY_MADRASAH':
                $query->where('tenant_id', $user->tenant_id)
                    ->where(function ($q) use ($user) {
                        if ($user->hasRole('ADMIN_MADRASAH')) {
                            $q->where('owner_type', 'MADRASAH');
                        } elseif ($user->hasRole('GURU')) {
                            $q->where('owner_type', 'SUBJECT')
                                ->whereIn('subject_id', \App\Support\Security\TeacherAccess::assignedSubjectIds($user));
                        } else {
                            $q->whereRaw('1 = 0');
                        }
                    });
                break;
            case 'SHARED':
                $query->where('tenant_id', $user->tenant_id)
                    ->whereHas('publications', function ($q) use ($user) {
                        $q->where('tenant_id', $user->tenant_id)
                            ->where('status', 'APPROVED')
                            ->where('share_scope', 'MADRASAH');
                    });
                break;
        }

        // Standard Filtering
        if (isset($filters['subject_id'])) {
            $query->where('subject_id', $filters['subject_id']);
        }
        if (isset($filters['curriculum_id'])) {
            $query->where('curriculum_id', $filters['curriculum_id']);
        }
        if (isset($filters['question_type'])) {
            $query->whereHas('currentVersion', function($q) use ($filters) {
                $q->where('question_type', $filters['question_type']);
            });
        }
        if (isset($filters['search'])) {
            $searchTerm = '%' . addcslashes($filters['search'], '%_\\\\') . '%';
            $query->whereHas('currentVersion', function($q) use ($searchTerm) {
                $q->where('stem', 'like', $searchTerm);
            });
        }

        $data = $query->paginate($filters['per_page'] ?? 15);
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function show(Question $question)
    {
        $this->authorize('view', $question);
        return response()->json([
            'success' => true,
            'data' => $question->load(['currentVersion.options', 'currentVersion.learningOutcome.currentVersion', 'currentVersion.learningObjective', 'tags'])
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Question::class);
        $tenantId = $request->user()->tenant_id;
        $validated = $request->validate([
            'question_type' => ['required', Rule::in(self::ALLOWED_TYPES)],
            'stem' => 'required|string',
            'explanation' => 'nullable|string',
            'options' => 'nullable|array',
            'options.*.content' => 'required|string',
            'options.*.is_correct' => 'boolean',
            'options.*.option_key' => 'nullable|string',
            'options.*.matching_key' => 'nullable|string',
            'curriculum_id' => ['nullable', 'uuid', Rule::exists('curricula', 'id')->where('tenant_id', $tenantId)],
            'subject_id' => ['nullable', 'uuid', Rule::exists('subjects', 'id')->where('tenant_id', $tenantId)],
            'learning_outcome_id' => ['nullable', 'uuid', Rule::exists('learning_outcomes', 'id')->where('tenant_id', $tenantId)],
            'learning_objective_id' => ['nullable', 'uuid', Rule::exists('learning_objectives', 'id')->where('tenant_id', $tenantId)],
            'owner_type' => 'required|in:PERSONAL',
            'difficulty' => 'required|in:EASY,MEDIUM,HARD',
            'default_points' => 'required|numeric|min:0',
        ]);
        $this->validateOptionIntegrity($validated['question_type'], $validated['options'] ?? []);

        if (!empty($validated['subject_id'])
            && !\App\Support\Security\TeacherAccess::canManageSubject($request->user(), $validated['subject_id'])) {
            abort(403, 'Guru tidak memiliki penugasan aktif untuk mata pelajaran ini.');
        }

        // CP Validation against Subject & Tenant
        if (!empty($validated['learning_outcome_id']) && !empty($validated['subject_id'])) {
            $cpValid = \App\Models\LearningOutcome::where('id', $validated['learning_outcome_id'])
                ->where('subject_id', $validated['subject_id'])
                ->where('tenant_id', $tenantId)
                ->exists();
            if (!$cpValid) {
                return response()->json([
                    'success' => false,
                    'message' => 'The selected learning_outcome_id is invalid or does not belong to the selected subject or tenant.'
                ], 422);
            }
        }

        // TP Validation against CP & Tenant
        if (!empty($validated['learning_objective_id'])) {
            if (empty($validated['learning_outcome_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'learning_outcome_id is required when learning_objective_id is specified.'
                ], 422);
            }
            $tpValid = \App\Models\LearningObjective::where('id', $validated['learning_objective_id'])
                ->where('learning_outcome_id', $validated['learning_outcome_id'])
                ->where('tenant_id', $tenantId)
                ->exists();
            if (!$tpValid) {
                return response()->json([
                    'success' => false,
                    'message' => 'The selected learning_objective_id is invalid or does not belong to the selected learning_outcome_id or tenant.'
                ], 422);
            }
        }

        return DB::transaction(function () use ($validated, $request) {
            $question = Question::create([
                'owner_type' => $validated['owner_type'],
                'owner_user_id' => $request->user()->id,
                'status' => 'DRAFT',
                'curriculum_id' => $validated['curriculum_id'] ?? null,
                'subject_id' => $validated['subject_id'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            $version = $question->versions()->create([
                'major_version' => 1,
                'minor_version' => 0,
                'question_type' => $validated['question_type'],
                'stem' => $validated['stem'],
                'explanation' => $validated['explanation'] ?? null,
                'difficulty' => $validated['difficulty'],
                'default_points' => $validated['default_points'],
                'learning_outcome_id' => $validated['learning_outcome_id'] ?? null,
                'learning_objective_id' => $validated['learning_objective_id'] ?? null,
                'status' => 'DRAFT',
                'is_current' => true,
                'created_by' => $request->user()->id,
            ]);

            $this->saveOptions($version, $validated['options'] ?? []);

            $this->auditService->log('CREATE_QUESTION', 'Question', $question->id, null, $question->toArray());

            return response()->json(['success' => true, 'data' => $question->load(['currentVersion.options', 'currentVersion.learningOutcome', 'currentVersion.learningObjective'])], 201);
        });
    }

    public function update(Request $request, Question $question)
    {
        $this->authorize('update', $question);
        $tenantId = $request->user()->tenant_id;
        $current = $question->currentVersion;
        if (!$current) {
            return response()->json(['success' => false, 'message' => 'Question has no current version.'], 409);
        }

        // If published, edits MUST create a new version
        $isPublished = ($current->status === 'PUBLISHED');

        $validated = $request->validate([
            'stem' => 'sometimes|required|string',
            'explanation' => 'nullable|string',
            'options' => 'sometimes|required|array',
            'options.*.content' => 'required|string',
            'options.*.is_correct' => 'boolean',
            'options.*.option_key' => 'nullable|string',
            'options.*.matching_key' => 'nullable|string',
            'learning_outcome_id' => ['nullable', 'uuid', Rule::exists('learning_outcomes', 'id')->where('tenant_id', $tenantId)],
            'learning_objective_id' => ['nullable', 'uuid', Rule::exists('learning_objectives', 'id')->where('tenant_id', $tenantId)],
            'difficulty' => 'sometimes|required|in:EASY,MEDIUM,HARD',
            'default_points' => 'sometimes|required|numeric|min:0',
            'create_new_version' => 'boolean'
        ]);
        if (array_key_exists('options', $validated)) {
            $this->validateOptionIntegrity($current->question_type, $validated['options']);
        }

        $outcomeId = array_key_exists('learning_outcome_id', $validated) ? $validated['learning_outcome_id'] : $current->learning_outcome_id;
        $objectiveId = array_key_exists('learning_objective_id', $validated) ? $validated['learning_objective_id'] : $current->learning_objective_id;
        $subjectId = $question->subject_id;

        if ($outcomeId && $subjectId) {
            $cpValid = \App\Models\LearningOutcome::where('id', $outcomeId)
                ->where('subject_id', $subjectId)
                ->where('tenant_id', $tenantId)
                ->exists();
            if (!$cpValid) {
                return response()->json([
                    'success' => false,
                    'message' => 'The selected learning_outcome_id is invalid or does not belong to the question subject or tenant.'
                ], 422);
            }
        }

        if ($objectiveId) {
            if (!$outcomeId) {
                return response()->json([
                    'success' => false,
                    'message' => 'learning_outcome_id is required when learning_objective_id is specified.'
                ], 422);
            }
            $tpValid = \App\Models\LearningObjective::where('id', $objectiveId)
                ->where('learning_outcome_id', $outcomeId)
                ->where('tenant_id', $tenantId)
                ->exists();
            if (!$tpValid) {
                return response()->json([
                    'success' => false,
                    'message' => 'The selected learning_objective_id is invalid or does not belong to the selected learning_outcome_id or tenant.'
                ], 422);
            }
        }

        return DB::transaction(function () use ($question, $validated, $request, $outcomeId, $objectiveId) {
            $lockedQuestion = Question::query()->whereKey($question->id)->lockForUpdate()->firstOrFail();
            $this->authorize('update', $lockedQuestion);
            $current = $lockedQuestion->currentVersion;
            if (!$current) {
                return response()->json(['success' => false, 'message' => 'Question has no current version.'], 409);
            }
            $isPublishedNow = $current->status === 'PUBLISHED';

            if ($isPublishedNow || $request->boolean('create_new_version')) {
                // Immutable update pattern: Create New Version
                $lockedQuestion->versions()->update(['is_current' => false]);

                $newVersion = $lockedQuestion->versions()->create([
                    'major_version' => $current->major_version,
                    'minor_version' => $current->minor_version + 1,
                    'question_type' => $current->question_type,
                    'stem' => $validated['stem'] ?? $current->stem,
                    'explanation' => array_key_exists('explanation', $validated) ? $validated['explanation'] : $current->explanation,
                    'difficulty' => $validated['difficulty'] ?? $current->difficulty,
                    'default_points' => $validated['default_points'] ?? $current->default_points,
                    'learning_outcome_id' => $outcomeId,
                    'learning_objective_id' => $objectiveId,
                    'status' => 'DRAFT',
                    'is_current' => true,
                    'created_by' => $request->user()->id,
                ]);

                $options = $validated['options'] ?? $current->options->toArray();
                $this->saveOptions($newVersion, $options);
                $lockedQuestion->update(['status' => 'DRAFT']);

                $this->auditService->log('CREATE_VERSION', 'Question', $lockedQuestion->id, ['old_v' => $current->id], ['new_v' => $newVersion->id]);
            } else {
                // Mutable update for DRAFT
                $updateData = [];
                if (isset($validated['stem'])) $updateData['stem'] = $validated['stem'];
                if (array_key_exists('explanation', $validated)) $updateData['explanation'] = $validated['explanation'];
                if (array_key_exists('learning_outcome_id', $validated)) $updateData['learning_outcome_id'] = $outcomeId;
                if (array_key_exists('learning_objective_id', $validated)) $updateData['learning_objective_id'] = $objectiveId;
                if (isset($validated['difficulty'])) $updateData['difficulty'] = $validated['difficulty'];
                if (isset($validated['default_points'])) $updateData['default_points'] = $validated['default_points'];

                if (!empty($updateData)) {
                    $current->update($updateData);
                }

                if (isset($validated['options'])) {
                    $current->options()->delete();
                    $this->saveOptions($current, $validated['options']);
                }
                $this->auditService->log('UPDATE_DRAFT', 'Question', $lockedQuestion->id, null, null);
            }

            return response()->json(['success' => true, 'data' => $lockedQuestion->fresh()->load(['currentVersion.options', 'currentVersion.learningOutcome', 'currentVersion.learningObjective'])]);
        });
    }

    public function publish(Question $question)
    {
        $this->authorize('publish', $question);

        return DB::transaction(function () use ($question) {
            $lockedQuestion = Question::query()->whereKey($question->id)->lockForUpdate()->firstOrFail();
            $this->authorize('publish', $lockedQuestion);
            $current = $lockedQuestion->currentVersion;
            if (!$current) {
                return response()->json(['success' => false, 'message' => 'Question has no current version.'], 409);
            }

            if ($current->status === 'PUBLISHED') {
                return response()->json(['success' => false, 'message' => 'Already published'], 422);
            }

            $current->update(['status' => 'PUBLISHED']);
            $lockedQuestion->update(['status' => 'PUBLISHED']);

            $this->auditService->log('PUBLISH_QUESTION', 'Question', $lockedQuestion->id, null, ['status' => 'PUBLISHED']);

            return response()->json(['success' => true]);
        });
    }

    public function import(Request $request, \App\Services\ExcelQuestionImporter $importer)
    {
        $this->authorize('create', Question::class);
        $validated = $request->validate([
            'rows' => 'required|array',
            'rows.*.stem' => 'required|string',
            'rows.*.type' => ['required', Rule::in(self::ALLOWED_TYPES)],
            'rows.*.difficulty' => ['nullable', Rule::in(['EASY', 'MEDIUM', 'HARD'])],
            'rows.*.points' => ['nullable', 'numeric', 'min:0'],
            'rows.*.options' => 'nullable|array',
            'rows.*.options.*.content' => 'required_with:rows.*.options|string',
            'rows.*.options.*.is_correct' => 'sometimes|boolean',
            'rows.*.options.*.option_key' => 'nullable|string|max:100',
            'rows.*.options.*.matching_key' => 'nullable|string|max:100',
            'rows.*.question_id' => 'nullable|uuid',
            'rows.*.subject_id' => 'nullable|uuid',
        ]);

        $results = $importer->import('temp_path', ['rows' => $validated['rows']]);

        $this->auditService->log('IMPORT_QUESTIONS', 'Question', null, null, [
            'success' => $results['success'],
            'imported' => $results['imported'],
            'errors_count' => count($results['errors'])
        ]);

        return response()->json($results, $results['success'] ? 200 : 422);
    }

    public function destroy(Question $question)
    {
        $this->authorize('update', $question);

        return DB::transaction(function () use ($question) {
            $lockedQuestion = Question::query()->whereKey($question->id)->lockForUpdate()->firstOrFail();
            $this->authorize('update', $lockedQuestion);
            $lockedQuestion->update([
                'status' => 'ARCHIVED',
                'updated_by' => auth()->id(),
            ]);
            // Question uses SoftDeletes. Do not hard-delete versions/options:
            // exam snapshots and scoring history still reference source rows.
            $lockedQuestion->delete();
            $this->auditService->log('ARCHIVE_QUESTION', 'Question', $lockedQuestion->id, null, ['status' => 'ARCHIVED']);

            return response()->json([
                'success' => true,
                'message' => 'Question archived; historical exam data retained.',
            ]);
        });
    }

    public function versions(Question $question)
    {
        $this->authorize('view', $question);

        return response()->json([
            'success' => true,
            'data' => $question->versions()
                ->with('options')
                ->orderByDesc('major_version')
                ->orderByDesc('minor_version')
                ->paginate(25),
        ]);
    }

    private function validateOptionIntegrity(string $questionType, array $options): void
    {
        $fail = function (string $message): never {
            throw \Illuminate\Validation\ValidationException::withMessages(['options' => $message]);
        };

        if (in_array($questionType, ['SHORT_ANSWER', 'ESSAY'], true)) {
            if ($options !== []) $fail('Soal jawaban singkat/esai tidak boleh memiliki opsi pilihan.');
            return;
        }

        if (in_array($questionType, ['MULTIPLE_CHOICE', 'MULTIPLE_RESPONSE', 'TRUE_FALSE'], true)) {
            $min = $questionType === 'TRUE_FALSE' ? 2 : 2;
            $max = $questionType === 'TRUE_FALSE' ? 2 : 4;
            if (count($options) < $min || count($options) > $max) {
                $fail('Jumlah opsi tidak sesuai dengan jenis soal.');
            }

            $keys = [];
            $correctCount = 0;
            foreach ($options as $option) {
                $key = strtoupper(trim((string) ($option['option_key'] ?? '')));
                $content = trim((string) ($option['content'] ?? ''));
                if ($key === '' || $content === '' || !in_array($key, ['A', 'B', 'C', 'D'], true)) {
                    $fail('Setiap opsi harus memiliki kunci A-D dan isi yang tidak kosong.');
                }
                if (in_array($key, $keys, true)) $fail('Kunci opsi tidak boleh duplikat.');
                $keys[] = $key;
                if ((bool) ($option['is_correct'] ?? false)) $correctCount++;
            }

            if ($questionType === 'TRUE_FALSE' && array_diff(['A', 'B'], $keys) !== []) {
                $fail('Soal benar/salah wajib memiliki opsi A dan B.');
            }
            if (in_array($questionType, ['MULTIPLE_CHOICE', 'TRUE_FALSE'], true) && $correctCount !== 1) {
                $fail('Soal ini wajib memiliki tepat satu kunci jawaban benar.');
            }
            if ($questionType === 'MULTIPLE_RESPONSE' && $correctCount < 1) {
                $fail('Pilihan ganda kompleks wajib memiliki minimal satu kunci jawaban benar.');
            }
            return;
        }

        if ($questionType === 'MATCHING') {
            $left = [];
            $right = [];
            foreach ($options as $option) {
                $content = trim((string) ($option['content'] ?? ''));
                $leftKey = trim((string) ($option['option_key'] ?? ''));
                $rightKey = trim((string) ($option['matching_key'] ?? ''));
                if ($content === '') $fail('Setiap bagian pasangan menjodohkan wajib memiliki isi.');
                if ($leftKey !== '') $left[$leftKey] = ($left[$leftKey] ?? 0) + 1;
                if ($rightKey !== '') $right[$rightKey] = ($right[$rightKey] ?? 0) + 1;
            }
            if (count($left) < 2 || $left !== $right) {
                $fail('Soal menjodohkan wajib memiliki minimal dua pasangan lengkap dan seimbang.');
            }
        }
    }

    private function saveOptions(QuestionVersion $version, array $options)
    {
        foreach ($options as $index => $opt) {
            $option = new \App\Models\QuestionOption(array_merge($opt, [
                'sequence' => $index
            ]));
            $option->question_version_id = $version->id;
            $option->tenant_id = auth()->user()->tenant_id;
            $option->save();
        }
    }
}
