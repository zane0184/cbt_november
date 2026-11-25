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

    public function preview(): void
    {
        $this->authorizeAccess();
        $this->validate([
            'file' => ['required', 'file', 'mimes:docx', 'max:10240'],
            'selectedAssignmentId' => ['required', 'uuid'],
        ]);

        abort_unless($this->selectedAssignmentRow(), 403, 'Penugasan tidak ditemukan atau bukan milik Guru yang sedang masuk.');

        $result = app(DocxQuestionParser::class)->parse($this->file->getRealPath());
        $this->questions = array_map(function (array $question): array {
            $question['included'] = true;
            return $question;
        }, $result['questions']);
        $this->excludedContent = $result['excluded_content'] ?? [];
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
            'file' => ['required', 'file', 'mimes:docx', 'max:10240'],
            'selectedAssignmentId' => ['required', 'uuid'],
            'selectedCpIds' => ['nullable', 'array'],
            'selectedCpIds.*' => ['required', 'uuid', 'distinct'],
            'selectedOutcomeIds' => ['nullable', 'array'],
            'selectedOutcomeIds.*' => ['required', 'uuid', 'distinct'],
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.included' => ['sometimes', 'boolean'],
            'questions.*.question_type' => ['required', 'string', 'in:MULTIPLE_CHOICE,MULTIPLE_RESPONSE,TRUE_FALSE,TRUE_FALSE_GROUP,ESSAY,MATCHING,SHORT_ANSWER,UNSUPPORTED'],
            'questions.*.stem' => ['required', 'string', 'max:12000'],
            'questions.*.points' => ['required', 'numeric', 'min:0.5', 'max:100'],
            'questions.*.difficulty' => ['required', 'in:EASY,MEDIUM,HARD'],
            'questions.*.options' => ['sometimes', 'array', 'max:4'],
            'questions.*.options.*.option_key' => ['required_with:questions.*.options', 'string', 'in:A,B,C,D'],
            'questions.*.options.*.content' => ['required_with:questions.*.options', 'string', 'max:12000'],
            'questions.*.options.*.is_correct' => ['sometimes', 'boolean'],
            'questions.*.statements' => ['sometimes', 'array', 'max:3'],
            'questions.*.statements.*.id' => ['required_with:questions.*.statements', 'string', 'max:40'],
            'questions.*.statements.*.text' => ['required_with:questions.*.statements', 'string', 'max:12000'],
            'questions.*.statements.*.is_true' => ['sometimes', 'boolean'],
            'questions.*.stimulus.content' => ['sometimes', 'nullable', 'string', 'max:30000'],
            'questions.*.mapping_cp_ids' => ['sometimes', 'array'],
            'questions.*.mapping_cp_ids.*' => ['required', 'uuid', 'distinct'],
            'questions.*.mapping_outcome_ids' => ['sometimes', 'array'],
            'questions.*.mapping_outcome_ids.*' => ['required', 'uuid', 'distinct'],
        ]);

        if (!$this->hasPreview || !$this->previewHash) {
            throw ValidationException::withMessages(['docx' => 'Buat preview dokumen terlebih dahulu.']);
        }

        $freshHash = hash_file('sha256', $this->file->getRealPath());
        if (!hash_equals($this->previewHash, $freshHash)) {
            throw ValidationException::withMessages(['docx' => 'File berubah setelah preview. Unggah ulang dan buat preview baru.']);
        }

        $assignmentRow = $this->selectedAssignmentRow();
        abort_unless($assignmentRow, 403, 'Penugasan tidak ditemukan atau bukan milik Guru yang sedang masuk.');

        $result = app(DocxQuestionDraftImporter::class)->import(
            $this->questions,
            $this->previewHash,
            $this->selectedAssignmentId,
            $this->selectedCpIds,
            $this->selectedOutcomeIds
        );

        session()->flash(
            'message',
            $result['imported'] . ' soal berhasil disimpan sebagai DRAFT pada Bank Soal. Soal belum diterbitkan dan belum dimasukkan ke ujian.'
        );

        $this->reset(['file', 'questions', 'excludedContent', 'summary', 'previewHash', 'hasPreview', 'selectedCpIds', 'selectedOutcomeIds']);
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
