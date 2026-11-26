<?php

namespace App\Livewire\Teacher;

use App\Services\Questions\DocxQuestionDraftImporter;
use App\Services\Questions\DocxQuestionParser;
use App\Services\Questions\DocxQuestionTemplate;
use App\Services\TeacherPortal\TeacherPortalDataService;
use App\Support\Security\TeacherPortalAccess;
use Illuminate\Validation\ValidationException;
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
    public ?string $previewHash = null;
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
            && $user->hasPermission('question.manage')
            && $user->hasPermission('curriculum.manage'),
            403,
            'Import soal DOCX memerlukan akun Guru, penugasan aktif, serta kewenangan Bank Soal dan Kurikulum.'
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
    }

    public function updatedSelectedSection(): void
    {
        $this->selectedAssignmentId = '';
        $this->selectedCpIds = [];
        $this->selectedOutcomeIds = [];
    }

    public function updated(string $propertyName, mixed $value): void
    {
        if (!preg_match('/^questions\\.(\\d+)\\.question_type$/', $propertyName, $match)) {
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

        $result = app(DocxQuestionParser::class)->parse($this->file->getRealPath());
        $this->questions = array_map(function (array $question): array {
            $question['included'] = true;
            $question['difficulty'] = $question['difficulty'] ?? 'MEDIUM';
            $question['source_fragments'] = array_map(function (array $fragment): array {
                unset($fragment['xml']);
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

        if (!$this->hasPreview || !$this->previewHash) {
            throw ValidationException::withMessages(['docx' => 'Buat preview dokumen terlebih dahulu.']);
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

        $allowedSequences = collect($freshParse['questions'] ?? [])
            ->filter(fn (array $question): bool => !$requiresSectionSelection
                || ($question['source_section'] ?? null) === $this->selectedSection)
            ->map(fn (array $question): ?int => isset($question['source_locator']['sequence'])
                ? (int) $question['source_locator']['sequence']
                : null)
            ->filter(fn (?int $sequence): bool => $sequence !== null)
            ->values()
            ->all();

        $questionsForImport = array_values(array_filter(
            $this->questions,
            fn (array $question): bool => in_array(
                (int) ($question['source_locator']['sequence'] ?? 0),
                $allowedSequences,
                true
            ) && (bool) ($question['included'] ?? true)
        ));

        if ($questionsForImport === []) {
            throw ValidationException::withMessages([
                'docx' => 'Pilih minimal satu soal pada bagian dokumen yang dipilih.',
            ]);
        }

        validator(['questions' => $questionsForImport], [
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.source_number' => ['required', 'integer', 'min:1'],
            'questions.*.question_type' => ['required', 'string', 'in:MULTIPLE_CHOICE,MULTIPLE_RESPONSE,TRUE_FALSE,TRUE_FALSE_GROUP,ESSAY,MATCHING,SHORT_ANSWER,UNSUPPORTED'],
            'questions.*.stem' => ['required', 'string', 'max:12000'],
            'questions.*.difficulty' => ['required', 'in:EASY,MEDIUM,HARD'],
            'questions.*.options' => ['sometimes', 'array', 'max:4'],
            'questions.*.options.*.option_key' => ['required_with:questions.*.options', 'string', 'in:A,B,C,D'],
            'questions.*.options.*.content' => ['required_with:questions.*.options', 'string', 'max:12000'],
            'questions.*.statements' => ['sometimes', 'array', 'max:3'],
            'questions.*.statements.*.id' => ['required_with:questions.*.statements', 'string', 'max:40', 'distinct'],
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

        $selectedSequences = array_map(
            fn (array $question): int => (int) ($question['source_locator']['sequence'] ?? 0),
            $questionsForImport
        );
        if ($selectedSequences === []) {
            throw ValidationException::withMessages([
                'docx' => 'Pilih minimal satu soal pada bagian dokumen yang dipilih.',
            ]);
        }

        $freshBySequence = collect($freshParse['questions'] ?? [])
            ->keyBy(fn (array $question): int => (int) ($question['source_locator']['sequence'] ?? 0));
        $blockedSequences = collect($freshParse['questions'] ?? [])
            ->filter(fn (array $question): bool => ($question['blocked_features'] ?? []) !== [])
            ->map(fn (array $question): int => (int) ($question['source_locator']['sequence'] ?? 0))
            ->all();
        if (array_intersect($blockedSequences, $selectedSequences) !== []) {
            throw ValidationException::withMessages([
                'docx' => 'Ada soal terpilih dengan relasi gambar yang rusak atau format media yang tidak didukung. Perbaiki gambar sumber sebelum impor.',
            ]);
        }

        foreach ($questionsForImport as &$questionRow) {
            $sequence = (int) ($questionRow['source_locator']['sequence'] ?? 0);
            $sourceRow = $freshBySequence->get($sequence);
            if (!$sourceRow
                || (int) ($sourceRow['source_number'] ?? 0) !== (int) ($questionRow['source_number'] ?? 0)) {
                throw ValidationException::withMessages([
                    'docx' => 'Identitas blok soal pada preview tidak cocok dengan hasil pembacaan ulang dokumen. Buat preview baru.',
                ]);
            }
            $questionRow['source_number'] = (int) $sourceRow['source_number'];
            $questionRow['source_section'] = $sourceRow['source_section'] ?? null;
            $questionRow['source_locator'] = $sourceRow['source_locator'];
            $questionRow['source_fragments'] = $sourceRow['source_fragments'] ?? [];
            $questionRow['blocked_features'] = $sourceRow['blocked_features'] ?? [];
            $questionRow['text_direction'] = $sourceRow['text_direction'] ?? 'auto';
            $questionRow['media_count'] = $sourceRow['media_count'] ?? 0;
            $questionRow['math_count'] = $sourceRow['math_count'] ?? 0;
            $questionRow['nested_table_count'] = $sourceRow['nested_table_count'] ?? 0;
            $questionRow['rich_paragraphs'] = $sourceRow['rich_paragraphs'] ?? [];
            $questionRow['mathml_fragments'] = $sourceRow['mathml_fragments'] ?? [];
        }
        unset($questionRow);

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

        session()->flash(
            'message',
            $result['imported'] . ' soal berhasil disimpan sebagai DRAFT pada Bank Soal. Soal belum diterbitkan dan belum dimasukkan ke ujian.'
        );

        $this->reset(['file', 'questions', 'excludedContent', 'summary', 'sections', 'requiresSectionSplit', 'selectedSection', 'previewHash', 'hasPreview', 'selectedCpIds', 'selectedOutcomeIds']);
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
