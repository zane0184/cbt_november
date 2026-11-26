<?php

namespace App\Livewire\Teacher;

use App\Services\Questions\DocxQuestionDraftImporter;
use App\Services\Questions\DocxQuestionParser;
use App\Services\Questions\DocxQuestionTemplate;
use App\Services\TeacherPortal\TeacherPortalDataService;
use App\Support\Security\TeacherPortalAccess;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

class QuestionDocxImport extends Component
{
    use WithFileUploads;

    public $file;
    public string $selectedAssignmentId = '';
    public array $selectedCpIds = [];
    public array $selectedOutcomeIds = [];
    public array $questions = [];
    public array $excludedContent = [];
    public array $summary = [];
    public array $sections = [];
    public bool $requiresSectionSplit = false;
    public ?string $selectedSection = null;
    #[Locked]
    public ?string $previewHash = null;
    #[Locked]
    public ?string $previewAssignmentId = null;
    public bool $hasPreview = false;

    public function mount(): void
    {
        $this->authorizeAccess();
        $rows = $this->assignmentRows();
        $this->selectedAssignmentId = (string) ($rows[0]['assignment']->id ?? '');
    }

    private function authorizeAccess(): void
    {
        $user = auth()->user();
        abort_unless(
            $user
            && $user->tenant_id
            && $user->hasExactRole('GURU')
            && $user->hasPermission('question.view')
            && $user->hasPermission('question.manage'),
            403,
            'Import soal DOCX memerlukan akun Guru, penugasan aktif, serta kewenangan melihat dan mengelola Bank Soal.'
        );

        app(TeacherPortalAccess::class)->profile($user);
    }

    private function assignmentRows(): array
    {
        return app(TeacherPortalDataService::class)->assignmentOptions(auth()->user());
    }

    private function selectedAssignmentRow(): ?array
    {
        return collect($this->assignmentRows())->first(
            fn (array $row): bool => (string) $row['assignment']->id === $this->selectedAssignmentId
        );
    }

    public function updatedSelectedAssignmentId(): void
    {
        $this->selectedCpIds = [];
        $this->selectedOutcomeIds = [];

        // Multi-section DOCX is previewed before its assignment is chosen.
        if ($this->hasPreview && $this->previewHash && $this->requiresSectionSplit
            && $this->previewAssignmentId === null && $this->selectedAssignmentId !== '') {
            $this->previewAssignmentId = $this->selectedAssignmentId;
            return;
        }

        if ($this->previewAssignmentId !== null
            && !hash_equals($this->previewAssignmentId, $this->selectedAssignmentId)) {
            $this->invalidatePreview();
        }
    }

    public function updatedSelectedSection(): void
    {
        $this->selectedAssignmentId = '';
        $this->selectedCpIds = [];
        $this->selectedOutcomeIds = [];
        $this->previewAssignmentId = null;
    }

    private function invalidatePreview(): void
    {
        $this->questions = [];
        $this->excludedContent = [];
        $this->summary = [];
        $this->sections = [];
        $this->requiresSectionSplit = false;
        $this->previewHash = null;
        $this->previewAssignmentId = null;
        $this->hasPreview = false;
    }

    public function updated(string $propertyName, mixed $value): void
    {
        if (!preg_match('/^questions\.(\d+)\.question_type$/', $propertyName, $match)) {
            return;
        }

        $index = (int) $match[1];
        if (!isset($this->questions[$index])) {
            return;
        }

        if (in_array($value, ['ESSAY', 'TRUE_FALSE_GROUP'], true)) {
            $this->questions[$index]['options'] = [];
        }

        if ($value === 'TRUE_FALSE_GROUP') {
            $statements = array_values($this->questions[$index]['statements'] ?? []);
            while (count($statements) < 3) {
                $next = count($statements) + 1;
                $statements[] = ['id' => 's' . $next, 'text' => '', 'is_true' => ''];
            }
            $this->questions[$index]['statements'] = array_slice($statements, 0, 3);
        }

        if (in_array($value, ['MULTIPLE_CHOICE', 'MULTIPLE_RESPONSE'], true) && empty($this->questions[$index]['options'])) {
            $this->questions[$index]['options'] = [
                ['option_key' => 'A', 'content' => '', 'is_correct' => false],
                ['option_key' => 'B', 'content' => '', 'is_correct' => false],
            ];
        }

        if ($value === 'TRUE_FALSE' && empty($this->questions[$index]['options'])) {
            $this->questions[$index]['options'] = [
                ['option_key' => 'A', 'content' => 'BENAR', 'is_correct' => false],
                ['option_key' => 'B', 'content' => 'SALAH', 'is_correct' => false],
            ];
        }
    }

    public function addGroupedStatement(int $questionIndex): void
    {
        if (!isset($this->questions[$questionIndex]) || count($this->questions[$questionIndex]['statements'] ?? []) >= 3) {
            return;
        }
        $next = count($this->questions[$questionIndex]['statements'] ?? []) + 1;
        $this->questions[$questionIndex]['statements'][] = [
            'id' => 's' . $next,
            'text' => '',
            'is_true' => '',
        ];
    }

    public function removeGroupedStatement(int $questionIndex, int $statementIndex): void
    {
        if (!isset($this->questions[$questionIndex]['statements'])
            || count($this->questions[$questionIndex]['statements']) <= 3
            || !isset($this->questions[$questionIndex]['statements'][$statementIndex])) {
            return;
        }
        unset($this->questions[$questionIndex]['statements'][$statementIndex]);
        $this->questions[$questionIndex]['statements'] = array_values($this->questions[$questionIndex]['statements']);
    }

    public function preview(): void
    {
        $this->authorizeAccess();
        $this->validate([
            'file' => ['required', 'file', 'extensions:docx', 'max:10240'],
            'selectedAssignmentId' => ['nullable', 'uuid'],
        ]);

        // Clear old preview before parsing a replacement upload.
        $this->invalidatePreview();

        $result = app(DocxQuestionParser::class)->parse($this->file->getRealPath());
        $this->questions = array_map(function (array $question): array {
            $question['included'] = true;
            $question['difficulty'] = $question['difficulty'] ?? 'MEDIUM';
            $question['source_fragments'] = array_map(function (array $fragment): array {
                unset($fragment['xml'], $fragment['structure']);
                return $fragment;
            }, $question['source_fragments'] ?? []);
            if (isset($question['stimulus']['source_fragments'])) {
                $question['stimulus']['source_fragments'] = array_map(function (array $fragment): array {
                    unset($fragment['xml']);
                    return $fragment;
                }, $question['stimulus']['source_fragments']);
            }
            if (($question['question_type'] ?? null) === 'TRUE_FALSE_GROUP') {
                $question['statements'] = array_map(function (array $statement): array {
                    $statement['is_true'] = is_bool($statement['is_true'] ?? null)
                        ? ($statement['is_true'] ? '1' : '0')
                        : '';
                    return $statement;
                }, $question['statements'] ?? []);
            }
            return $question;
        }, $result['questions']);
        $this->excludedContent = $result['excluded_content'] ?? [];
        $this->sections = $result['sections'] ?? [];
        $this->requiresSectionSplit = (bool) ($result['requires_section_split'] ?? false);
        $this->selectedSection = $this->sections[0]['title'] ?? null;
        if ($this->requiresSectionSplit) {
            $this->selectedAssignmentId = '';
            $this->selectedCpIds = [];
            $this->selectedOutcomeIds = [];
            $this->previewAssignmentId = null;
        } else {
            abort_unless($this->selectedAssignmentRow(), 403, 'Penugasan tidak ditemukan atau bukan milik Guru yang sedang masuk.');
            $this->previewAssignmentId = $this->selectedAssignmentId;
        }
        $this->summary = [
            'total' => $result['total'],
            'valid' => $result['valid'],
            'invalid' => $result['invalid'],
            'excluded' => count($this->excludedContent),
        ];
        $this->previewHash = $result['file_sha256'];
        $this->hasPreview = true;
    }

    public function importDrafts(): void
    {
        $this->authorizeAccess();
        $this->validate([
            'file' => ['required', 'file', 'extensions:docx', 'max:10240'],
            'selectedAssignmentId' => ['required', 'uuid'],
            'selectedCpIds' => ['nullable', 'array'],
            'selectedCpIds.*' => ['required', 'uuid', 'distinct'],
            'selectedOutcomeIds' => ['nullable', 'array'],
            'selectedOutcomeIds.*' => ['required', 'uuid', 'distinct'],
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.included' => ['sometimes', 'boolean'],
        ]);

        if (!$this->hasPreview || !$this->previewHash || !$this->previewAssignmentId) {
            throw ValidationException::withMessages(['docx' => 'Pilih penugasan yang sesuai dan buat preview dokumen terlebih dahulu.']);
        }

        if (!hash_equals($this->previewAssignmentId, $this->selectedAssignmentId)) {
            throw ValidationException::withMessages(['docx' => 'Penugasan berubah setelah preview. Buat preview ulang sebelum menyimpan.']);
        }

        $freshHash = hash_file('sha256', $this->file->getRealPath());
        if (!hash_equals($this->previewHash, $freshHash)) {
            throw ValidationException::withMessages(['docx' => 'File berubah setelah preview. Unggah ulang dan buat preview baru.']);
        }

        // Re-parse the server-held upload at confirmation time. Unsupported
        // embedded content must never disappear silently during draft save.
        $freshParse = app(DocxQuestionParser::class)->parse($this->file->getRealPath());
        $freshSections = collect($freshParse['sections'] ?? []);
        $requiresSectionSelection = (bool) ($freshParse['requires_section_split'] ?? false);

        if ($requiresSectionSelection
            && !$freshSections->contains(fn (array $section): bool => ($section['title'] ?? null) === $this->selectedSection)) {
            throw ValidationException::withMessages([
                'selectedSection' => 'Pilih satu bagian mata pelajaran yang benar-benar terdeteksi pada dokumen.',
            ]);
        }

        // The parser output is the authority for source identity and unsupported
        // features. Livewire state is used only for explicit teacher edits and
        // include/exclude choices; its READY/NEEDS_REVIEW status is never trusted.
        $freshQuestions = collect($freshParse['questions'] ?? []);
        $previewBySequence = collect($this->questions)->keyBy(
            fn (array $question): int => (int) ($question['source_locator']['sequence'] ?? 0)
        );

        $questionsForImport = [];
        $selectedSequences = [];
        foreach ($freshQuestions as $sourceRow) {
            if ($requiresSectionSelection
                && ($sourceRow['source_section'] ?? null) !== $this->selectedSection) {
                continue;
            }

            $sequence = (int) ($sourceRow['source_locator']['sequence'] ?? 0);
            $previewRow = $previewBySequence->get($sequence);
            if (!$previewRow
                || (int) ($previewRow['source_number'] ?? 0) !== (int) ($sourceRow['source_number'] ?? 0)) {
                throw ValidationException::withMessages([
                    'docx' => 'Identitas soal pada preview tidak cocok dengan hasil pembacaan ulang dokumen. Buat preview baru.',
                ]);
            }

            if (!(bool) ($previewRow['included'] ?? true)) {
                continue;
            }

            // Only teacher-editable fields are accepted from the browser. Source
            // locators, media references, OOXML fragments, blocked features and
            // parser review reasons always come from the fresh server parse.
            $questionRow = $sourceRow;
            foreach (['question_type', 'stem', 'difficulty', 'options', 'statements', 'table_rows', 'mapping_cp_ids', 'mapping_outcome_ids'] as $editableField) {
                if (array_key_exists($editableField, $previewRow)) {
                    $questionRow[$editableField] = $previewRow[$editableField];
                }
            }
            if (isset($sourceRow['stimulus']) && is_array($sourceRow['stimulus'])) {
                $questionRow['stimulus'] = $sourceRow['stimulus'];
                if (isset($previewRow['stimulus']['content'])) {
                    $questionRow['stimulus']['content'] = $previewRow['stimulus']['content'];
                }
            }

            $questionRow['included'] = true;
            $questionRow['source_review_required'] = !empty($sourceRow['errors']);
            $questionRow['source_review_reasons'] = array_values($sourceRow['errors'] ?? []);
            $questionsForImport[] = $questionRow;
            $selectedSequences[] = $sequence;
        }

        if ($questionsForImport === []) {
            throw ValidationException::withMessages([
                'docx' => 'Tidak ada soal yang dipilih untuk disimpan. Centang minimal satu kartu soal pada bagian dokumen yang dipilih.',
            ]);
        }

        validator(['questions' => $questionsForImport], [
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.source_number' => ['required', 'integer', 'min:1'],
            'questions.*.question_type' => ['required', 'string', 'in:MULTIPLE_CHOICE,MULTIPLE_RESPONSE,TRUE_FALSE,TRUE_FALSE_GROUP,ESSAY'],
            'questions.*.stem' => ['required', 'string', 'max:12000'],
            'questions.*.difficulty' => ['required', 'in:EASY,MEDIUM,HARD'],
            'questions.*.options' => ['sometimes', 'array', 'max:4'],
            'questions.*.options.*.option_key' => ['required_with:questions.*.options', 'string', 'in:A,B,C,D'],
            'questions.*.options.*.content' => ['required_with:questions.*.options', 'string', 'max:12000'],
            'questions.*.statements' => [
                'sometimes',
                'array',
                'max:3',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (!is_array($value)) {
                        return;
                    }

                    // Statement identifiers are scoped to one grouped question.
                    // The parser intentionally reuses sa/sb/sc across different questions.
                    $ids = array_map(
                        static fn ($statement) => is_array($statement) ? ($statement['id'] ?? null) : null,
                        $value
                    );
                    $ids = array_values(array_filter(
                        $ids,
                        static fn ($id): bool => is_string($id) && $id !== ''
                    ));

                    if (count($ids) !== count(array_unique($ids))) {
                        $fail('ID pernyataan dalam satu kelompok soal harus unik.');
                    }
                },
            ],
            'questions.*.statements.*.id' => ['required_with:questions.*.statements', 'string', 'max:40'],
            'questions.*.statements.*.text' => ['required_with:questions.*.statements', 'string', 'max:12000'],
            'questions.*.stimulus.content' => ['sometimes', 'nullable', 'string', 'max:30000'],
            'questions.*.source_section' => ['sometimes', 'nullable', 'string', 'max:120'],
            'questions.*.table_rows' => ['sometimes', 'array', 'max:100'],
            'questions.*.table_rows.*' => ['array', 'max:20'],
            'questions.*.table_rows.*.*' => ['nullable', 'string', 'max:12000'],
            'questions.*.mapping_cp_ids' => ['sometimes', 'array'],
            'questions.*.mapping_cp_ids.*' => ['required', 'uuid', 'distinct'],
            'questions.*.mapping_outcome_ids' => ['sometimes', 'array'],
            'questions.*.mapping_outcome_ids.*' => ['required', 'uuid', 'distinct'],
        ])->validate();

        $blockedSequences = $freshQuestions
            ->filter(fn (array $question): bool => ($question['blocked_features'] ?? []) !== [])
            ->map(fn (array $question): int => (int) ($question['source_locator']['sequence'] ?? 0))
            ->all();
        if (array_intersect($blockedSequences, $selectedSequences) !== []) {
            throw ValidationException::withMessages([
                'docx' => 'Ada soal terpilih dengan relasi gambar yang rusak atau format media yang tidak didukung. Perbaiki gambar sumber sebelum impor.',
            ]);
        }



        $assignmentRow = $this->selectedAssignmentRow();
        abort_unless($assignmentRow, 403, 'Penugasan tidak ditemukan atau bukan milik Guru yang sedang masuk.');

        $result = app(DocxQuestionDraftImporter::class)->import(
            $questionsForImport,
            $this->previewHash,
            $this->selectedAssignmentId,
            $this->selectedCpIds,
            $this->selectedOutcomeIds,
            null,
            null,
            $this->file->getRealPath()
        );

        $message = $result['imported'] . ' soal berhasil disimpan sebagai DRAFT pada Bank Soal. Soal belum diterbitkan dan belum dimasukkan ke ujian.';
        $pendingMappingCount = (int) ($result['pending_mapping_count'] ?? 0);
        if ($pendingMappingCount > 0) {
            $message .= ' ' . $pendingMappingCount . ' soal masih menunggu pemetaan CP dan TP/ATP resmi; lengkapi pemetaannya melalui editor Bank Soal sebelum menerbitkan.';
        }

        session()->flash('message', $message);

        $this->reset(['file', 'questions', 'excludedContent', 'summary', 'sections', 'requiresSectionSplit', 'selectedSection', 'previewHash', 'previewAssignmentId', 'hasPreview', 'selectedCpIds', 'selectedOutcomeIds']);
    }

    public function downloadTemplate()
    {
        $this->authorizeAccess();
        return app(DocxQuestionTemplate::class)->download();
    }

    public function render()
    {
        $this->authorizeAccess();
        $assignmentRows = $this->assignmentRows();
        $selectedRow = collect($assignmentRows)->first(
            fn (array $row): bool => (string) $row['assignment']->id === $this->selectedAssignmentId
        );
        $curriculumContext = $selectedRow['curriculum_context'] ?? [];
        $cpOptions = collect($curriculumContext['cp_options'] ?? [])
            ->filter(fn (array $cp): bool => ($cp['selectable'] ?? false) === true)
            ->values();
        $outcomes = collect($curriculumContext['teacher_outcomes'] ?? [])
            ->filter(fn (array $outcome): bool => ($outcome['status'] ?? null) === 'PUBLISHED'
                && ($outcome['is_current'] ?? false) === true
                && in_array($outcome['outcome_type'] ?? null, ['TP', 'ATP'], true))
            ->values();

        return view('livewire.teacher.question-docx-import', [
            'assignmentRows' => $assignmentRows,
            'selectedAssignment' => $selectedRow['assignment'] ?? null,
            'curriculumContext' => $curriculumContext,
            'cpOptions' => $cpOptions,
            'outcomes' => $outcomes,
        ])->layout('layouts.teacher');
    }
}
