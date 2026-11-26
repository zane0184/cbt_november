<?php

namespace App\Livewire\Admin;

use App\Models\Material;
use App\Models\MaterialTopic;
use App\Models\QuestionVersionCurriculumMapping;
use App\Models\QuestionVersionStructureMapping;
use App\Models\QuestionVersionTenantOutcomeMapping;
use App\Models\TeacherSubjectAssignment;
use App\Models\TenantLearningOutcome;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\QuestionVersion;
use App\Models\Subject;
use App\Support\Security\TeacherAccess;
use App\Support\Security\TeacherPortalAccess;
use App\Services\AuditService;
use App\Services\TeacherPortal\QuestionVersionCurriculumMappingService;
use App\Services\TeacherPortal\TeacherPortalDataService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class QuestionBank extends Component
{
    use WithPagination;

    public $search = '';
    public $subjectFilter = '';
    public $statusFilter = '';

    public function boot(): void
    {
        $this->assertCanAccess();
    }

    public function mount(): void
    {
        $this->assertCanAccess();

        $questionId = request()->query('edit_question');
        if (is_string($questionId) && $questionId !== '') {
            $this->openEditModal($questionId);
        }
    }

    private function assertCanAccess(): void
    {
        $user = auth()->user();
        abort_unless($user && $user->tenant_id, 403, 'Bank soal hanya dapat diakses Guru sesuai penugasan.');

        // Do not trust a previously loaded role relation after role changes.
        $user->unsetRelation('roles');
        abort_unless($user->hasPermission('question.view'), 403, 'Bank soal hanya dapat diakses Guru sesuai penugasan.');

        // Reuse the canonical Portal Guru boundary: exactly one GURU role,
        // persisted tenant identity, and exactly one tenant-local TeacherProfile.
        app(TeacherPortalAccess::class)->profile($user);

        // A valid profile without an active academic assignment is not enough
        // to enter the subject-scoped Guru management screens.
        abort_unless(
            TeacherAccess::assignedSubjectIds($user) !== [],
            403,
            'Bank soal hanya dapat diakses Guru sesuai penugasan.'
        );
    }

    // Modal Form State
    public $showModal = false;
    public $editingQuestionId = null;
    public $editingQuestionVersionId = null;
    public $editingQuestionVersionStatus = null;

    public $questionCode = '';
    public $questionType = 'MULTIPLE_CHOICE'; // MULTIPLE_CHOICE, MULTIPLE_RESPONSE, TRUE_FALSE, TRUE_FALSE_GROUP, MATCHING, ESSAY
    public array $groupStatements = [];
    public $selectedSubjectId = '';
    public $selectedAssignmentId = '';
    public array $selectedCpIds = [];
    public array $selectedOutcomeIds = [];
    public $selectedMaterialId = '';
    public $selectedMaterialTopicId = '';
    public $stem = '';
    public $explanation = ''; // Pedoman Penskoran / Rubrik / Pembahasan
    public $defaultPoints = 10.0;

    // Options for MC, MULTIPLE_RESPONSE & TRUE_FALSE
    public $optionA = '';
    public $optionB = '';
    public $optionC = '';
    public $optionD = '';
    public $correctOption = 'A';
    public $correctOptions = []; // Array of keys for MULTIPLE_RESPONSE e.g. ['A', 'C']

    // Options for MATCHING pairs [['left' => '', 'right' => ''], ...]
    public $matchingPairs = [];

    // Delete Confirmation State
    public $showDeleteModal = false;
    public $deletingQuestionId = null;
    public $deletingQuestionCode = '';

    public function openCreateModal()
    {
        Gate::authorize('create', Question::class);
        $this->reset(['editingQuestionId', 'editingQuestionVersionId', 'editingQuestionVersionStatus', 'questionCode', 'stem', 'explanation', 'optionA', 'optionB', 'optionC', 'optionD', 'correctOptions', 'matchingPairs', 'groupStatements', 'selectedSubjectId', 'selectedAssignmentId', 'selectedCpIds', 'selectedOutcomeIds', 'selectedMaterialId', 'selectedMaterialTopicId']);
        $this->questionType = 'MULTIPLE_CHOICE';
        $this->correctOption = 'A';
        $this->correctOptions = [];
        $this->matchingPairs = [
            ['left' => '', 'right' => ''],
            ['left' => '', 'right' => ''],
        ];
        $this->groupStatements = [
            ['id' => 'sa', 'text' => '', 'is_true' => false],
            ['id' => 'sb', 'text' => '', 'is_true' => false],
            ['id' => 'sc', 'text' => '', 'is_true' => false],
        ];
        $this->defaultPoints = 10.0;
        $this->resetValidation();
        $this->showModal = true;
    }

    public function openEditModal($questionId)
    {
        $question = Question::with(['currentVersion.options', 'currentVersion.curriculumMappings.learningOutcome', 'currentVersion.tenantOutcomeMappings.learningOutcome', 'currentVersion.structureMappings.structure'])->findOrFail($questionId);
        Gate::authorize('update', $question);
        $this->authorizeTeacherQuestionAccess($question->subject_id);
        $version = $question->currentVersion;

        $this->editingQuestionId = $question->id;
        $this->editingQuestionVersionId = $version?->id;
        $this->editingQuestionVersionStatus = $version?->status;
        $this->questionCode = $question->question_code;
        $this->selectedSubjectId = $question->subject_id;

        if ($version) {
            $this->questionType = $version->question_type ?? 'MULTIPLE_CHOICE';
            $this->stem = $version->stem ?? '';
            $this->explanation = $version->explanation ?? '';
            $this->defaultPoints = $version->default_points ?? 10.0;
            $firstAssignmentMapping = $version->curriculumMappings->first() ?? $version->tenantOutcomeMappings->first();
            $mappingMetadata = $version->metadata['curriculum_mapping'] ?? [];
            $this->selectedAssignmentId = $firstAssignmentMapping?->teacher_subject_assignment_id
                ?? ($mappingMetadata['teacher_subject_assignment_id'] ?? '');
            $this->selectedCpIds = $version->curriculumMappings
                ->where('teacher_subject_assignment_id', $this->selectedAssignmentId)
                ->pluck('multi_learning_outcome_id')->all();
            $this->selectedOutcomeIds = $version->tenantOutcomeMappings
                ->where('teacher_subject_assignment_id', $this->selectedAssignmentId)
                ->pluck('tenant_learning_outcome_id')->all();
            $firstMaterialMapping = $version->curriculumMappings
                ->firstWhere('teacher_subject_assignment_id', $this->selectedAssignmentId);
            $this->selectedMaterialId = $firstMaterialMapping?->material_id ?? '';
            $this->selectedMaterialTopicId = $firstMaterialMapping?->material_topic_id ?? '';

            if (in_array($this->questionType, ['MULTIPLE_CHOICE', 'MULTIPLE_RESPONSE', 'TRUE_FALSE'])) {
                $opts = $version->options->keyBy('option_key');
                $this->optionA = isset($opts['A']) ? $opts['A']->content : ($this->questionType === 'TRUE_FALSE' ? 'BENAR' : '');
                $this->optionB = isset($opts['B']) ? $opts['B']->content : ($this->questionType === 'TRUE_FALSE' ? 'SALAH' : '');
                $this->optionC = isset($opts['C']) ? $opts['C']->content : '';
                $this->optionD = isset($opts['D']) ? $opts['D']->content : '';

                if ($this->questionType === 'MULTIPLE_RESPONSE') {
                    $this->correctOptions = $version->options
                        ->where('is_correct', true)
                        ->pluck('option_key')
                        ->toArray();
                } else {
                    $correct = $version->options->firstWhere('is_correct', true);
                    $this->correctOption = $correct ? $correct->option_key : '';
                }
            } elseif ($this->questionType === 'MATCHING') {
                $leftOptions = $version->options->filter(fn($o) => !empty($o->option_key) && empty($o->matching_key));
                $rightOptions = $version->options->filter(fn($o) => !empty($o->matching_key))->keyBy('matching_key');

                $pairs = [];
                foreach ($leftOptions as $left) {
                    $right = $rightOptions->get($left->option_key);
                    $pairs[] = [
                        'left' => $left->content,
                        'right' => $right ? $right->content : '',
                    ];
                }

                if (count($pairs) < 2) {
                    while (count($pairs) < 2) {
                        $pairs[] = ['left' => '', 'right' => ''];
                    }
                }
                $this->matchingPairs = $pairs;
            }

            if ($this->questionType === 'TRUE_FALSE_GROUP') {
                $this->groupStatements = array_values($version->metadata['statement_items'] ?? []);
                while (count($this->groupStatements) < 3) {
                    $index = count($this->groupStatements);
                    $this->groupStatements[] = [
                        'id' => 's' . ($index + 1),
                        'text' => '',
                        'is_true' => null,
                    ];
                }
                $this->groupStatements = array_slice($this->groupStatements, 0, 3);
            }
        }

        $this->resetValidation();
        $this->showModal = true;
    }

    public function addMatchingPair()
    {
        $this->matchingPairs[] = ['left' => '', 'right' => ''];
    }

    public function removeMatchingPair($index)
    {
        if (isset($this->matchingPairs[$index])) {
            unset($this->matchingPairs[$index]);
            $this->matchingPairs = array_values($this->matchingPairs);
        }
    }

    public function updatedSelectedAssignmentId($value): void
    {
        $this->selectedCpIds = [];
        $this->selectedOutcomeIds = [];
        $this->selectedMaterialId = '';
        $this->selectedMaterialTopicId = '';

        $user = auth()->user();
        $profile = app(TeacherPortalAccess::class)->profile($user);
        $assignment = TeacherSubjectAssignment::query()
            ->where('tenant_id', $user->tenant_id)
            ->where('teacher_profile_id', $profile->id)
            ->whereKey($value)
            ->where('status', 'ACTIVE')
            ->first();

        $this->selectedSubjectId = $assignment?->subject_id ?? '';
    }

    public function updatedQuestionType($value)
    {
        if ($value === 'TRUE_FALSE') {
            $this->optionA = 'BENAR';
            $this->optionB = 'SALAH';
            $this->optionC = '';
            $this->optionD = '';
            if (!in_array($this->correctOption, ['A', 'B'])) {
                $this->correctOption = 'A';
            }
        } elseif ($value === 'MULTIPLE_RESPONSE') {
            if (empty($this->correctOptions)) {
                $this->correctOptions = ['A'];
            }
        } elseif ($value === 'TRUE_FALSE_GROUP') {
            $this->groupStatements = [
                ['id' => 'sa', 'text' => '', 'is_true' => null],
                ['id' => 'sb', 'text' => '', 'is_true' => null],
                ['id' => 'sc', 'text' => '', 'is_true' => null],
            ];
        } elseif ($value === 'MATCHING') {
            if (count($this->matchingPairs) < 2) {
                $this->matchingPairs = [
                    ['left' => '', 'right' => ''],
                    ['left' => '', 'right' => ''],
                ];
            }
        }
    }

    public function createQuestion()
    {
        return $this->saveQuestion();
    }

    private function currentQuestionMetadata(array $existingMetadata = []): array
    {
        if ($this->questionType === 'TRUE_FALSE_GROUP') {
            $existingMetadata['statement_items'] = array_map(
                fn (array $statement): array => [
                    'id' => trim((string) $statement['id']),
                    'text' => trim((string) $statement['text']),
                    'is_true' => (bool) $statement['is_true'],
                ],
                array_values($this->groupStatements)
            );
            $existingMetadata['statement_scoring'] = 'PROPORTIONAL_PER_STATEMENT_V1';
        } else {
            unset($existingMetadata['statement_items'], $existingMetadata['statement_scoring']);
        }

        $richContent = $existingMetadata['rich_content'] ?? [];
        if (!empty($richContent['mathml_fragments']) || !empty($richContent['table_structure'])) {
            $richContent['requires_manual_review'] = true;
            unset($richContent['reviewed_at'], $richContent['reviewed_by']);
            $existingMetadata['rich_content'] = $richContent;
        }

        return $existingMetadata;
    }

    public function saveQuestion()
    {
        $this->validate([
            'questionCode' => 'required|string|max:50',
            'questionType' => 'required|string|in:MULTIPLE_CHOICE,MULTIPLE_RESPONSE,TRUE_FALSE,TRUE_FALSE_GROUP,MATCHING,ESSAY',
            'selectedAssignmentId' => 'required|uuid',
            'selectedSubjectId' => 'required|exists:subjects,id',
            'selectedCpIds' => 'nullable|array',
            'selectedCpIds.*' => 'required|uuid|distinct',
            'selectedOutcomeIds' => 'nullable|array',
            'selectedOutcomeIds.*' => 'required|uuid|distinct',
            'selectedMaterialId' => 'nullable|uuid',
            'selectedMaterialTopicId' => 'nullable|uuid',
            'stem' => 'required|string',
            'defaultPoints' => 'required|numeric|min:0.5',
        ]);

        if ($this->questionType === 'TRUE_FALSE_GROUP') {
            $this->validate([
                'groupStatements' => 'required|array|size:3',
                'groupStatements.*.id' => 'required|string|max:40|distinct',
                'groupStatements.*.text' => 'required|string|max:12000',
                'groupStatements.*.is_true' => 'required|boolean',
            ]);
        }

        $user = auth()->user();
        $tenantId = $user->tenant_id ?? null;

        if ($this->editingQuestionId) {
            $existing = Question::findOrFail($this->editingQuestionId);
            Gate::authorize('update', $existing);
            $this->authorizeTeacherQuestionAccess($existing->subject_id);
        } else {
            Gate::authorize('create', Question::class);
        }
        $this->authorizeTeacherQuestionAccess($this->selectedSubjectId);

        if ($this->questionType === 'MULTIPLE_CHOICE') {
            $this->validate([
                'optionA' => 'required|string',
                'optionB' => 'required|string',
                'correctOption' => 'required|in:A,B,C,D',
            ]);

            $availableOptionKeys = [
                'A' => trim((string) $this->optionA) !== '',
                'B' => trim((string) $this->optionB) !== '',
                'C' => trim((string) $this->optionC) !== '',
                'D' => trim((string) $this->optionD) !== '',
            ];
            if (!($availableOptionKeys[$this->correctOption] ?? false)) {
                $this->addError('correctOption', 'Kunci jawaban harus menunjuk pada opsi yang benar-benar diisi.');
                return;
            }
        } elseif ($this->questionType === 'MULTIPLE_RESPONSE') {
            $this->validate([
                'optionA' => 'required|string',
                'optionB' => 'required|string',
                'correctOptions' => 'required|array|min:1',
                'correctOptions.*' => 'required|string|distinct|in:A,B,C,D',
            ]);
            if (empty($this->correctOptions) || !is_array($this->correctOptions) || count($this->correctOptions) < 1) {
                $this->addError('correctOptions', 'Pilih setidaknya satu kunci jawaban benar untuk Pilihan Ganda Kompleks.');
                return;
            }

            $availableOptionKeys = [
                'A' => trim((string) $this->optionA) !== '',
                'B' => trim((string) $this->optionB) !== '',
                'C' => trim((string) $this->optionC) !== '',
                'D' => trim((string) $this->optionD) !== '',
            ];
            foreach ($this->correctOptions as $key) {
                if (!($availableOptionKeys[$key] ?? false)) {
                    $this->addError('correctOptions', 'Setiap kunci jawaban harus menunjuk pada opsi yang benar-benar diisi.');
                    return;
                }
            }
        } elseif ($this->questionType === 'TRUE_FALSE') {
            $this->validate([
                'correctOption' => 'required|in:A,B',
            ]);
            $this->optionA = 'BENAR';
            $this->optionB = 'SALAH';
        } elseif ($this->questionType === 'MATCHING') {
            if (empty($this->matchingPairs) || count($this->matchingPairs) < 2) {
                $this->addError('matchingPairs', 'Soal Menjodohkan membutuhkan setidaknya 2 pasangan.');
                return;
            }
            foreach ($this->matchingPairs as $idx => $pair) {
                if (empty(trim($pair['left'] ?? '')) || empty(trim($pair['right'] ?? ''))) {
                    $this->addError('matchingPairs', 'Setiap item premis dan jawaban pada pasangan ' . ($idx + 1) . ' wajib diisi.');
                    return;
                }
            }
        }

        DB::transaction(function () {
            $oldAuditValues = null;
            $auditAction = 'CREATE_QUESTION';

            if ($this->editingQuestionId) {
                // Update Existing Question
                $question = Question::query()->whereKey($this->editingQuestionId)->lockForUpdate()->firstOrFail();
                Gate::authorize('update', $question);
                $oldVersion = $question->currentVersion;
                $oldAuditValues = [
                    'question_code' => $question->question_code,
                    'subject_id' => $question->subject_id,
                    'status' => $question->status,
                    'question_version_id' => $oldVersion?->id,
                    'question_type' => $oldVersion?->question_type,
                ];
                $auditAction = 'UPDATE_QUESTION';
                $question->update([
                    'question_code' => strtoupper($this->questionCode),
                    'subject_id' => $this->selectedSubjectId,
                    'updated_by' => auth()->id(),
                ]);

                $version = $question->currentVersion;
                if ($version && $version->status === 'PUBLISHED') {
                    // Published versions are immutable. Editing from Web creates
                    // a new draft version and moves the question identity back to
                    // DRAFT so the teacher can review and publish the new version.
                    $previousVersion = $version;
                    $question->versions()->update(['is_current' => false]);
                    $version = $question->versions()->create([
                        'major_version' => $previousVersion->major_version,
                        'minor_version' => $previousVersion->minor_version + 1,
                        'question_type' => $this->questionType,
                        'stem' => $this->stem,
                        'explanation' => $this->explanation,
                        'default_points' => $this->defaultPoints,
                        'difficulty' => $previousVersion->difficulty,
                        'metadata' => $this->currentQuestionMetadata($previousVersion->metadata ?? []),
                        'learning_outcome_id' => null,
                        'learning_objective_id' => null,
                        'status' => 'DRAFT',
                        'is_current' => true,
                        'created_by' => auth()->id(),
                    ]);
                    $question->update(['status' => 'DRAFT']);
                } elseif ($version) {
                    $version->update([
                        'question_type' => $this->questionType,
                        'stem' => $this->stem,
                        'explanation' => $this->explanation,
                        'default_points' => $this->defaultPoints,
                        'metadata' => $this->currentQuestionMetadata($version->metadata ?? []),
                        'learning_outcome_id' => null,
                        'learning_objective_id' => null,
                    ]);

                    // Sync Options only for a mutable DRAFT version.
                    $version->options()->delete();
                } else {
                    $version = QuestionVersion::create([
                        'question_id' => $question->id,
                        'major_version' => 1,
                        'minor_version' => 0,
                        'question_type' => $this->questionType,
                        'stem' => $this->stem,
                        'explanation' => $this->explanation,
                        'default_points' => $this->defaultPoints,
                        'metadata' => $this->currentQuestionMetadata(),
                        'learning_outcome_id' => null,
                        'learning_objective_id' => null,
                        'is_current' => true,
                        'status' => $question->status,
                        'created_by' => auth()->id(),
                    ]);
                }
            } else {
                // Create New Question
                $question = Question::create([
                    'owner_type' => 'SUBJECT',
                    'question_code' => strtoupper($this->questionCode),
                    'status' => 'DRAFT',
                    'subject_id' => $this->selectedSubjectId,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);

                $version = QuestionVersion::create([
                    'question_id' => $question->id,
                    'major_version' => 1,
                    'minor_version' => 0,
                    'question_type' => $this->questionType,
                    'stem' => $this->stem,
                    'explanation' => $this->explanation,
                    'default_points' => $this->defaultPoints,
                    'metadata' => $this->currentQuestionMetadata(),
                    'learning_outcome_id' => null,
                    'learning_objective_id' => null,
                    'is_current' => true,
                    'status' => 'DRAFT',
                    'created_by' => auth()->id(),
                ]);
            }

            // Options handling
            if ($this->questionType === 'MULTIPLE_CHOICE') {
                $optionsData = [
                    ['key' => 'A', 'text' => $this->optionA],
                    ['key' => 'B', 'text' => $this->optionB],
                ];
                if (!empty($this->optionC)) $optionsData[] = ['key' => 'C', 'text' => $this->optionC];
                if (!empty($this->optionD)) $optionsData[] = ['key' => 'D', 'text' => $this->optionD];

                foreach ($optionsData as $idx => $opt) {
                    QuestionOption::create([
                        'question_version_id' => $version->id,
                        'option_key' => $opt['key'],
                        'content' => $opt['text'],
                        'is_correct' => ($opt['key'] === $this->correctOption),
                        'sequence' => $idx + 1,
                    ]);
                }
            } elseif ($this->questionType === 'MULTIPLE_RESPONSE') {
                $optionsData = [
                    ['key' => 'A', 'text' => $this->optionA],
                    ['key' => 'B', 'text' => $this->optionB],
                ];
                if (!empty($this->optionC)) $optionsData[] = ['key' => 'C', 'text' => $this->optionC];
                if (!empty($this->optionD)) $optionsData[] = ['key' => 'D', 'text' => $this->optionD];

                foreach ($optionsData as $idx => $opt) {
                    QuestionOption::create([
                        'question_version_id' => $version->id,
                        'option_key' => $opt['key'],
                        'content' => $opt['text'],
                        'is_correct' => in_array($opt['key'], $this->correctOptions, true),
                        'sequence' => $idx + 1,
                    ]);
                }
            } elseif ($this->questionType === 'TRUE_FALSE') {
                $tfData = [
                    ['key' => 'A', 'text' => 'BENAR'],
                    ['key' => 'B', 'text' => 'SALAH'],
                ];
                foreach ($tfData as $idx => $opt) {
                    QuestionOption::create([
                        'question_version_id' => $version->id,
                        'option_key' => $opt['key'],
                        'content' => $opt['text'],
                        'is_correct' => ($opt['key'] === $this->correctOption),
                        'sequence' => $idx + 1,
                    ]);
                }
            } elseif ($this->questionType === 'MATCHING') {
                foreach ($this->matchingPairs as $idx => $pair) {
                    $pairKey = 'pair_' . ($idx + 1);
                    // Left Option (Premis)
                    QuestionOption::create([
                        'question_version_id' => $version->id,
                        'option_key' => $pairKey,
                        'matching_key' => null,
                        'content' => $pair['left'],
                        'is_correct' => false,
                        'sequence' => ($idx * 2) + 1,
                    ]);
                    // Right Option (Jawaban/Respon)
                    QuestionOption::create([
                        'question_version_id' => $version->id,
                        'option_key' => null,
                        'matching_key' => $pairKey,
                        'content' => $pair['right'],
                        'is_correct' => false,
                        'sequence' => ($idx * 2) + 2,
                    ]);
                }
            }

            app(QuestionVersionCurriculumMappingService::class)->sync(
                $version,
                $this->selectedAssignmentId,
                $this->selectedCpIds,
                $this->selectedOutcomeIds,
                $this->selectedMaterialId ?: null,
                $this->selectedMaterialTopicId ?: null
            );

            // Persist the explicit mapping mode after the mapping service has
            // validated either a complete official mapping or a general question.
            $versionMetadata = $version->metadata ?? [];
            $isMapped = $this->selectedCpIds !== [] && $this->selectedOutcomeIds !== [];
            $versionMetadata['curriculum_mapping'] = [
                'status' => $isMapped ? 'MAPPED' : 'NOT_MAPPED',
                'teacher_subject_assignment_id' => (string) $this->selectedAssignmentId,
                'requested_cp_ids' => array_values($this->selectedCpIds),
                'requested_outcome_ids' => array_values($this->selectedOutcomeIds),
                'message' => null,
            ];
            $version->metadata = $versionMetadata;
            $version->save();

            // Never write answer keys or full question content to the audit trail.
            $question->refresh();
            $version->refresh();
            app(AuditService::class)->log(
                $auditAction,
                Question::class,
                (string) $question->id,
                $oldAuditValues,
                [
                    'question_code' => $question->question_code,
                    'subject_id' => $question->subject_id,
                    'status' => $question->status,
                    'question_version_id' => $version->id,
                    'major_version' => $version->major_version,
                    'minor_version' => $version->minor_version,
                    'question_type' => $version->question_type,
                    'teacher_subject_assignment_id' => $this->selectedAssignmentId,
                    'cp_mapping_count' => count($this->selectedCpIds),
                    'outcome_mapping_count' => count($this->selectedOutcomeIds),
                    'option_count' => $version->options()->count(),
                ]
            );
        });

        $this->showModal = false;
        session()->flash('message', "Soal {$this->questionCode} berhasil disimpan.");
    }

    public function acknowledgeImportedRichContentReview(string $questionId): void
    {
        $this->assertCanAccess();

        $question = Question::query()->whereKey($questionId)->firstOrFail();
        Gate::authorize('update', $question);
        $this->authorizeTeacherQuestionAccess($question->subject_id);

        DB::transaction(function () use ($question): void {
            $lockedQuestion = Question::query()->whereKey($question->id)->lockForUpdate()->firstOrFail();
            Gate::authorize('update', $lockedQuestion);
            $version = $lockedQuestion->currentVersion;
            abort_unless($version, 409, 'Soal tidak memiliki versi aktif.');

            $metadata = $version->metadata ?? [];
            $richContent = $metadata['rich_content'] ?? [];
            abort_unless(
                ($richContent['requires_manual_review'] ?? false) === true,
                409,
                'Soal ini tidak memiliki peninjauan konten kompleks yang tertunda.'
            );

            $richContent['requires_manual_review'] = false;
            $richContent['reviewed_at'] = now()->toIso8601String();
            $richContent['reviewed_by'] = (string) auth()->id();
            $metadata['rich_content'] = $richContent;
            $version->metadata = $metadata;
            $version->save();

            app(AuditService::class)->log(
                'QUESTION_DOCX_RICH_CONTENT_REVIEWED',
                QuestionVersion::class,
                (string) $version->id,
                ['requires_manual_review' => true],
                [
                    'requires_manual_review' => false,
                    'reviewed_by' => (string) auth()->id(),
                    'reviewed_at' => $richContent['reviewed_at'],
                ]
            );
        });

        session()->flash('message', 'Peninjauan konten kompleks DOCX dicatat. Soal dapat diterbitkan setelah seluruh validasi Bank Soal terpenuhi.');
    }

    public function publishQuestion($questionId)
    {
        $question = Question::where('id', $questionId)->firstOrFail();
        Gate::authorize('publish', $question);
        $this->authorizeTeacherQuestionAccess($question->subject_id);
        DB::transaction(function () use ($question) {
            $lockedQuestion = Question::query()->whereKey($question->id)->lockForUpdate()->firstOrFail();
            Gate::authorize('publish', $lockedQuestion);
            $version = $lockedQuestion->currentVersion;
            abort_unless($version, 409, 'Soal tidak memiliki versi aktif.');
            abort_if(
                ($version->metadata['rich_content']['requires_manual_review'] ?? false) === true,
                422,
                'Konten DOCX kompleks belum ditinjau. Periksa pratinjau rumus/tabel, lalu catat peninjauan sebelum menerbitkan.'
            );
            abort_if((float) $version->default_points <= 0, 422, 'Bobot nilai belum ditentukan. Lengkapi bobot pada editor Bank Soal sebelum menerbitkan.');
            if (in_array($version->question_type, ['MULTIPLE_CHOICE', 'MULTIPLE_RESPONSE', 'TRUE_FALSE'], true)) {
                $options = $version->options;
                $optionCount = $options->count();
                $correctCount = $options->filter(fn ($option) => (bool) $option->is_correct)->count();
                $validKeys = match ($version->question_type) {
                    'MULTIPLE_CHOICE' => $optionCount >= 2 && $optionCount <= 4 && $correctCount === 1,
                    'MULTIPLE_RESPONSE' => $optionCount >= 2 && $optionCount <= 4 && $correctCount >= 1,
                    'TRUE_FALSE' => $optionCount === 2 && $correctCount === 1,
                    default => false,
                };
                abort_if(!$validKeys, 422, 'Kunci jawaban belum lengkap atau tidak sesuai jenis soal. Lengkapi melalui editor Bank Soal.');
            }
            if ($version->question_type === 'TRUE_FALSE_GROUP') {
                $items = $version->metadata['statement_items'] ?? [];
                $ids = array_map(fn ($item) => (string) ($item['id'] ?? ''), $items);
                $validItems = count($items) === 3
                    && count(array_unique($ids)) === 3
                    && !in_array('', $ids, true)
                    && collect($items)->every(fn ($item) => is_array($item)
                        && trim((string) ($item['text'] ?? '')) !== ''
                        && is_bool($item['is_true'] ?? null));
                abort_if(!$validItems, 422, 'Soal Benar/Salah kelompok wajib memiliki tepat tiga pernyataan valid sebelum diterbitkan.');
            }

            $cpMappings = QuestionVersionCurriculumMapping::query()
                ->where('tenant_id', auth()->user()->tenant_id)
                ->where('question_version_id', $version->id)
                ->with('learningOutcome.source')
                ->get();
            $outcomeMappings = QuestionVersionTenantOutcomeMapping::query()
                ->where('tenant_id', auth()->user()->tenant_id)
                ->where('question_version_id', $version->id)
                ->with('learningOutcome')
                ->get();
            $structureMappings = QuestionVersionStructureMapping::query()
                ->where('tenant_id', auth()->user()->tenant_id)
                ->where('question_version_id', $version->id)
                ->get();

            $hasAnyCurriculumMapping = $cpMappings->isNotEmpty()
                || $outcomeMappings->isNotEmpty()
                || $structureMappings->isNotEmpty();

            // Questions without curriculum mapping may be published for any assigned subject.
            // If any mapping is supplied, the complete official CP/TP/ATP/framework
            // contract remains mandatory and is validated exactly as before.
            if ($hasAnyCurriculumMapping) {
                $assignmentIds = $cpMappings->pluck('teacher_subject_assignment_id')->unique()->values();
                $outcomeAssignmentIds = $outcomeMappings->pluck('teacher_subject_assignment_id')->unique()->values();
                abort_unless($assignmentIds->isNotEmpty(), 409, 'Pemetaan kurikulum parsial tidak valid: CP resmi dan penugasan Guru wajib lengkap.');
                abort_unless($assignmentIds->sort()->values()->all() === $outcomeAssignmentIds->sort()->values()->all(), 409, 'Pemetaan CP dan TP/ATP harus konsisten untuk setiap penugasan.');
                foreach ($cpMappings as $cpMapping) {
                    $cp = $cpMapping->learningOutcome;
                    abort_unless($cp && $cp->is_official && !$cp->is_editable
                        && $cp->source
                        && $cp->source->verification_status === 'ARTIFACT_CHECKSUM_VERIFIED'
                        && $cp->source->checksum_sha256
                        && $cp->source_checksum
                        && hash_equals((string) $cp->source->checksum_sha256, (string) $cp->source_checksum),
                        409,
                        'CP resmi pada pemetaan soal tidak lagi memenuhi verifikasi sumber/checksum.');
                }
                foreach ($outcomeMappings as $outcomeMapping) {
                    abort_unless($outcomeMapping->learningOutcome
                        && $outcomeMapping->learningOutcome->status === 'PUBLISHED'
                        && $outcomeMapping->learningOutcome->is_current,
                        409,
                        'TP/ATP pada pemetaan soal harus tetap berstatus PUBLISHED dan versi terkini.');
                }
                $profile = app(TeacherPortalAccess::class)->profile(auth()->user());

                foreach ($assignmentIds as $assignmentId) {
                    $assignment = TeacherSubjectAssignment::query()
                        ->where('tenant_id', auth()->user()->tenant_id)
                        ->where('teacher_profile_id', $profile->id)
                        ->whereKey($assignmentId)
                        ->where('status', 'ACTIVE')
                        ->whereDate('starts_at', '<=', now()->toDateString())
                        ->whereDate('ends_at', '>=', now()->toDateString())
                        ->first();
                    abort_unless($assignment, 403, 'Pemetaan versi soal memuat penugasan yang tidak lagi menjadi kewenangan Guru ini.');

                    abort_unless(
                        $outcomeMappings->where('teacher_subject_assignment_id', $assignmentId)->isNotEmpty(),
                        409,
                        'Setiap penugasan soal wajib memiliki pemetaan TP/ATP terpublikasi.'
                    );

                    $roles = $structureMappings
                        ->where('teacher_subject_assignment_id', $assignmentId)
                        ->pluck('mapping_role')
                        ->unique()
                        ->all();
                    foreach (['PANCA_CINTA', 'PM_PRINCIPLE', 'DIMENSI_PROFIL_LULUSAN'] as $requiredRole) {
                        abort_unless(in_array($requiredRole, $roles, true), 409, 'Pemetaan Panca Cinta, Pembelajaran Mendalam, dan dimensi profil lulusan wajib lengkap sebelum publikasi soal.');
                    }
                }
            }

            $versionMetadata = $version->metadata ?? [];
            $mappingMetadata = $versionMetadata['curriculum_mapping'] ?? [];
            $versionMetadata['curriculum_mapping'] = array_merge(
                is_array($mappingMetadata) ? $mappingMetadata : [],
                [
                    'status' => $hasAnyCurriculumMapping ? 'MAPPED' : 'NOT_MAPPED',
                    'resolved_at' => now()->toISOString(),
                ]
            );
            $version->update(['metadata' => $versionMetadata]);

            $oldValues = [
                'question_status' => $lockedQuestion->status,
                'version_status' => $version->status,
                'question_version_id' => $version->id,
            ];
            $lockedQuestion->update(['status' => 'PUBLISHED']);
            $version->update(['status' => 'PUBLISHED']);

            app(AuditService::class)->log(
                'PUBLISH_QUESTION',
                Question::class,
                (string) $lockedQuestion->id,
                $oldValues,
                [
                    'question_status' => 'PUBLISHED',
                    'version_status' => 'PUBLISHED',
                    'question_version_id' => $version->id,
                    'curriculum_mapping_status' => $hasAnyCurriculumMapping ? 'MAPPED' : 'NOT_MAPPED',
                ]
            );
        });

        session()->flash('message', "Soal {$question->question_code} berhasil dipublikasikan.");
    }

    public function confirmDeleteQuestion($questionId)
    {
        $question = Question::findOrFail($questionId);
        Gate::authorize('update', $question);
        $this->authorizeTeacherQuestionAccess($question->subject_id);
        $this->deletingQuestionId = $question->id;
        $this->deletingQuestionCode = $question->question_code;
        $this->showDeleteModal = true;
    }

    public function deleteQuestion()
    {
        if ($this->deletingQuestionId) {
            $question = Question::findOrFail($this->deletingQuestionId);
            Gate::authorize('update', $question);
            $this->authorizeTeacherQuestionAccess($question->subject_id);
            DB::transaction(function () use ($question) {
                $locked = Question::query()->whereKey($question->id)->lockForUpdate()->firstOrFail();
                Gate::authorize('update', $locked);
                // Archive + soft-delete the question identity only. Keep all
                // versions/options because exam snapshots and scoring history
                // retain source references to these rows.
                $oldValues = [
                    'question_code' => $locked->question_code,
                    'status' => $locked->status,
                    'current_version_id' => $locked->currentVersion?->id,
                ];
                $locked->update(['status' => 'ARCHIVED', 'updated_by' => auth()->id()]);
                $locked->delete();

                app(AuditService::class)->log(
                    'ARCHIVE_QUESTION',
                    Question::class,
                    (string) $locked->id,
                    $oldValues,
                    ['status' => 'ARCHIVED', 'soft_deleted' => true]
                );
            });

            session()->flash('message', "Soal {$this->deletingQuestionCode} berhasil diarsipkan. Histori ujian dipertahankan.");
        }
        $this->showDeleteModal = false;
        $this->deletingQuestionId = null;
    }

    private function authorizeTeacherQuestionAccess(string $subjectId): void
    {
        $user = auth()->user();
        if (!$user || $user->roles()->count() !== 1 || !$user->hasRole('GURU') || !$user->hasPermission('question.manage')
            || !$subjectId || !TeacherAccess::canManageSubject($user, $subjectId)) {
            abort(403, 'Hanya Guru dengan permission dan penugasan aktif yang dapat mengelola soal mata pelajaran ini.');
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingSubjectFilter()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function render()
    {
        $user = auth()->user();
        app(TeacherPortalAccess::class)->profile($user);
        $teacherSubjectIds = TeacherAccess::assignedSubjectIds($user);

        $assignmentRows = app(TeacherPortalDataService::class)->assignmentOptions($user);
        if ($this->selectedAssignmentId === '' && $this->showModal && $assignmentRows !== []) {
            $preferredRow = collect($assignmentRows)->first(
                fn (array $row) => (string) $row['assignment']->subject_id === (string) $this->selectedSubjectId
            ) ?? $assignmentRows[0];
            $this->selectedAssignmentId = (string) $preferredRow['assignment']->id;
            $this->selectedSubjectId = (string) $preferredRow['assignment']->subject_id;
        }

        $selectedAssignmentRow = collect($assignmentRows)->first(
            fn (array $row) => (string) $row['assignment']->id === (string) $this->selectedAssignmentId
        );
        $selectedAssignment = $selectedAssignmentRow['assignment'] ?? null;
        $curriculumContext = $selectedAssignmentRow['curriculum_context'] ?? [];

        $cpOptions = collect($curriculumContext['cp_options'] ?? [])
            ->filter(fn (array $cp) => ($cp['selectable'] ?? false) === true)
            ->values();

        $outcomes = collect($curriculumContext['teacher_outcomes'] ?? [])
            ->filter(fn (array $outcome) => ($outcome['status'] ?? null) === 'PUBLISHED'
                && ($outcome['is_current'] ?? false) === true
                && in_array($outcome['outcome_type'] ?? null, ['TP', 'ATP'], true))
            ->values();

        $materials = $selectedAssignment
            ? Material::query()
                ->where('tenant_id', $user->tenant_id)
                ->where('teacher_subject_assignment_id', $selectedAssignment->id)
                ->where('status', 'PUBLISHED')
                ->orderBy('material_code')
                ->get()
            : collect();

        $topics = $this->selectedMaterialId !== ''
            ? MaterialTopic::query()
                ->where('tenant_id', $user->tenant_id)
                ->where('material_id', $this->selectedMaterialId)
                ->where('status', 'PUBLISHED')
                ->orderBy('code')
                ->get()
            : collect();

        $questions = Question::with([
                'subject',
                'currentVersion.options',
                'currentVersion.media',
                'currentVersion.curriculumMappings.learningOutcome.source',
                'currentVersion.tenantOutcomeMappings.learningOutcome',
                'currentVersion.structureMappings.structure',
            ])
            ->where(function ($q) use ($user, $teacherSubjectIds) {
                $q->where(function ($personal) use ($user, $teacherSubjectIds) {
                    $personal->where('owner_type', 'PERSONAL')
                        ->where('owner_user_id', $user->id)
                        ->where(function ($subjectScope) use ($teacherSubjectIds) {
                            $subjectScope->whereNull('subject_id')
                                ->orWhereIn('subject_id', $teacherSubjectIds ?? []);
                        });
                })->orWhere(function ($subject) use ($teacherSubjectIds) {
                    $subject->where('owner_type', 'SUBJECT')
                        ->whereIn('subject_id', $teacherSubjectIds ?? []);
                });
            })
            ->when($this->subjectFilter, fn ($q) => $q->where('subject_id', $this->subjectFilter))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->search, function ($q) {
                $q->where(function ($searchQuery) {
                    $searchQuery->where('question_code', 'like', '%' . $this->search . '%')
                        ->orWhereHas('currentVersion', function ($sq) {
                            $sq->where('stem', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->latest()
            ->paginate(12);

        $subjects = Subject::query()
            ->where('tenant_id', $user->tenant_id)
            ->whereIn('id', $teacherSubjectIds)
            ->orderBy('name')
            ->get();

        $editingVersionMedia = collect();
        if ($this->editingQuestionId && $this->editingQuestionVersionId) {
            $editingVersion = QuestionVersion::query()
                ->whereKey($this->editingQuestionVersionId)
                ->where('question_id', $this->editingQuestionId)
                ->where('tenant_id', $user->tenant_id)
                ->with('media')
                ->first();

            $editingVersionMedia = $editingVersion?->media ?? collect();
        }

        return view('livewire.admin.question-bank', [
            'questions' => $questions,
            'subjects' => $subjects,
            'assignmentRows' => $assignmentRows,
            'selectedAssignment' => $selectedAssignment,
            'curriculumContext' => $curriculumContext,
            'cpOptions' => $cpOptions,
            'outcomes' => $outcomes,
            'materials' => $materials,
            'topics' => $topics,
            'editingVersionMedia' => $editingVersionMedia,
        ])->layout('layouts.teacher');
    }
}
