<?php

namespace App\Livewire\Admin;

use App\Models\LearningObjective;
use App\Models\LearningOutcome;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\QuestionVersion;
use App\Models\Subject;
use App\Support\Security\TeacherAccess;
use App\Services\AuditService;
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

    public function mount()
    {
        $user = auth()->user();
        if (!$user || !$user->hasPermission('question.view')
            || !$user->roles()->whereIn('name', ['ADMIN_MADRASAH', 'GURU'])->exists()) {
            abort(403, 'Akses ditolak. Anda tidak memiliki izin untuk melihat bank soal.');
        }
    }

    // Modal Form State
    public $showModal = false;
    public $editingQuestionId = null;

    public $questionCode = '';
    public $questionType = 'MULTIPLE_CHOICE'; // MULTIPLE_CHOICE, MULTIPLE_RESPONSE, TRUE_FALSE, MATCHING, SHORT_ANSWER, ESSAY
    public $selectedSubjectId = '';
    public $selectedOutcomeId = ''; // CP
    public $selectedObjectiveId = ''; // TP
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
        $this->reset(['editingQuestionId', 'questionCode', 'stem', 'explanation', 'optionA', 'optionB', 'optionC', 'optionD', 'correctOptions', 'matchingPairs', 'selectedSubjectId', 'selectedOutcomeId', 'selectedObjectiveId']);
        $this->questionType = 'MULTIPLE_CHOICE';
        $this->correctOption = 'A';
        $this->correctOptions = [];
        $this->matchingPairs = [
            ['left' => '', 'right' => ''],
            ['left' => '', 'right' => ''],
        ];
        $this->defaultPoints = 10.0;
        $this->resetValidation();
        $this->showModal = true;
    }

    public function openEditModal($questionId)
    {
        $question = Question::with(['currentVersion.options'])->findOrFail($questionId);
        Gate::authorize('update', $question);
        $this->authorizeTeacherQuestionAccess($question->subject_id);
        $version = $question->currentVersion;

        $this->editingQuestionId = $question->id;
        $this->questionCode = $question->question_code;
        $this->selectedSubjectId = $question->subject_id;

        if ($version) {
            $this->questionType = $version->question_type ?? 'MULTIPLE_CHOICE';
            $this->stem = $version->stem ?? '';
            $this->explanation = $version->explanation ?? '';
            $this->defaultPoints = $version->default_points ?? 10.0;
            $this->selectedOutcomeId = $version->learning_outcome_id ?? '';
            $this->selectedObjectiveId = $version->learning_objective_id ?? '';

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
                    $this->correctOption = $correct ? $correct->option_key : 'A';
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

    public function updatedSelectedSubjectId($value)
    {
        $this->selectedOutcomeId = '';
        $this->selectedObjectiveId = '';
    }

    public function updatedSelectedOutcomeId($value)
    {
        $this->selectedObjectiveId = '';
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

    public function saveQuestion()
    {
        $this->validate([
            'questionCode' => 'required|string|max:50',
            'questionType' => 'required|string|in:MULTIPLE_CHOICE,MULTIPLE_RESPONSE,TRUE_FALSE,MATCHING,SHORT_ANSWER,ESSAY',
            'selectedSubjectId' => 'required|exists:subjects,id',
            'stem' => 'required|string',
            'defaultPoints' => 'required|numeric|min:0.5',
            'selectedOutcomeId' => 'nullable|exists:learning_outcomes,id',
            'selectedObjectiveId' => 'nullable|exists:learning_objectives,id',
        ]);

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

        // CP Validation against Subject & Tenant
        if (!empty($this->selectedOutcomeId)) {
            $cpValid = LearningOutcome::where('id', $this->selectedOutcomeId)
                ->where('subject_id', $this->selectedSubjectId)
                ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
                ->exists();
            if (!$cpValid) {
                $this->addError('selectedOutcomeId', 'CP yang dipilih tidak sesuai dengan mata pelajaran atau tenant.');
                return;
            }
        }

        // TP Validation against CP & Tenant
        if (!empty($this->selectedObjectiveId)) {
            if (empty($this->selectedOutcomeId)) {
                $this->addError('selectedObjectiveId', 'Pilih Capaian Pembelajaran (CP) terlebih dahulu.');
                return;
            }
            $tpValid = LearningObjective::where('id', $this->selectedObjectiveId)
                ->where('learning_outcome_id', $this->selectedOutcomeId)
                ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
                ->exists();
            if (!$tpValid) {
                $this->addError('selectedObjectiveId', 'TP yang dipilih tidak sesuai dengan CP atau tenant.');
                return;
            }
        }

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
                if ($version) {
                    if ($version->status === 'PUBLISHED') {
                        abort(409, 'Versi terbit tidak boleh diubah. Buat versi baru untuk melakukan perubahan.');
                    }
                    $version->update([
                        'question_type' => $this->questionType,
                        'stem' => $this->stem,
                        'explanation' => $this->explanation,
                        'default_points' => $this->defaultPoints,
                        'learning_outcome_id' => $this->selectedOutcomeId ?: null,
                        'learning_objective_id' => $this->selectedObjectiveId ?: null,
                    ]);

                    // Sync Options
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
                        'learning_outcome_id' => $this->selectedOutcomeId ?: null,
                        'learning_objective_id' => $this->selectedObjectiveId ?: null,
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
                    'learning_outcome_id' => $this->selectedOutcomeId ?: null,
                    'learning_objective_id' => $this->selectedObjectiveId ?: null,
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
                    'learning_outcome_id' => $version->learning_outcome_id,
                    'learning_objective_id' => $version->learning_objective_id,
                    'option_count' => $version->options()->count(),
                ]
            );
        });

        $this->showModal = false;
        session()->flash('message', "Soal {$this->questionCode} berhasil disimpan.");
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
        if (!$user || !$user->hasRole('GURU') || !$user->hasPermission('question.manage')
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
        $teacherSubjectIds = $user && $user->hasRole('GURU') ? TeacherAccess::assignedSubjectIds($user) : null;

        $questions = Question::with(['subject', 'currentVersion.options', 'currentVersion.learningOutcome.currentVersion', 'currentVersion.learningObjective'])
            ->when($user && $user->hasRole('GURU'), function ($q) use ($user, $teacherSubjectIds) {
                $q->where(function ($scope) use ($user, $teacherSubjectIds) {
                    $scope->where(function ($personal) use ($user, $teacherSubjectIds) {
                        $personal->where('owner_type', 'PERSONAL')
                            ->where('owner_user_id', $user->id)
                            ->where(function ($subjectScope) use ($teacherSubjectIds) {
                                $subjectScope->whereNull('subject_id')
                                    ->orWhereIn('subject_id', $teacherSubjectIds ?? []);
                            });
                    })->orWhere(function ($subject) use ($teacherSubjectIds) {
                        $subject->where('owner_type', 'SUBJECT')->whereIn('subject_id', $teacherSubjectIds ?? []);
                    });
                });
            })
            ->when($user && $user->hasRole('ADMIN_MADRASAH'), fn ($q) => $q->where('owner_type', 'MADRASAH'))
            ->when($this->subjectFilter, fn($q) => $q->where('subject_id', $this->subjectFilter))
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
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
            ->when($teacherSubjectIds !== null, fn($q) => $q->whereIn('id', $teacherSubjectIds))
            ->get();

        $outcomes = !empty($this->selectedSubjectId)
            ? LearningOutcome::with('currentVersion')->where('subject_id', $this->selectedSubjectId)
                ->when($teacherSubjectIds !== null, fn($q) => $q->whereIn('subject_id', $teacherSubjectIds))->get()
            : LearningOutcome::with('currentVersion')
                ->when($teacherSubjectIds !== null, fn($q) => $q->whereIn('subject_id', $teacherSubjectIds))->get();

        $objectives = !empty($this->selectedOutcomeId)
            ? LearningObjective::where('learning_outcome_id', $this->selectedOutcomeId)->get()
            : collect();

        return view('livewire.admin.question-bank', [
            'questions' => $questions,
            'subjects' => $subjects,
            'outcomes' => $outcomes,
            'objectives' => $objectives,
        ])->layout('layouts.app');
    }
}
