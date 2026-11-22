<?php

namespace App\Livewire\Teacher;

use App\Services\AuditService;
use App\Services\ExcelQuestionImporter;
use App\Services\QuestionImport\QuestionImportWorkbookParser;
use Illuminate\Support\Facades\Log;
use Livewire\WithFileUploads;
use Throwable;

final class QuestionImportWizard extends TeacherPortalComponent
{
    use WithFileUploads;

    public $workbook = null;

    public array $previewErrors = [];

    public array $previewRows = [];

    public int $totalRows = 0;

    public int $validRows = 0;

    public bool $previewReady = false;

    public function updatedWorkbook(): void
    {
        $this->resetValidation();
        $this->previewErrors = [];
        $this->previewRows = [];
        $this->totalRows = 0;
        $this->validRows = 0;
        $this->previewReady = false;
    }

    public function previewImport(): void
    {
        $this->validate([
            'workbook' => ['required', 'file', 'mimes:xlsx', 'max:10240'],
        ]);

        try {
            $result = app(QuestionImportWorkbookParser::class)->parse($this->workbook, auth()->user());
            $this->previewErrors = $result['errors'];
            $this->previewRows = array_map(fn (array $row) => [
                'number' => $row['_number'],
                'code' => $row['question_code'] ?? '-',
                'type' => $row['type'],
                'stem' => mb_strimwidth($row['stem'], 0, 90, '…'),
            ], $result['rows']);
            $this->totalRows = $result['total_rows'];
            $this->validRows = $result['valid_rows'];
            $this->previewReady = $result['success'];

            if (!$this->previewReady) {
                $this->addError('workbook', 'Workbook belum dapat diimpor. Perbaiki seluruh kesalahan sebelum konfirmasi.');
            }
        } catch (Throwable $exception) {
            Log::warning('Question workbook preview failed', [
                'tenant_id' => auth()->user()?->tenant_id,
                'user_id' => auth()->id(),
                'exception' => $exception::class,
            ]);
            $this->previewReady = false;
            $this->previewRows = [];
            $this->previewErrors = [['row' => 0, 'field' => 'workbook', 'error' => 'Workbook gagal diperiksa. Periksa file dan coba kembali.']];
            $this->addError('workbook', 'Workbook gagal diperiksa.');
        }
    }

    public function confirmImport()
    {
        $this->validate([
            'workbook' => ['required', 'file', 'mimes:xlsx', 'max:10240'],
        ]);

        // Re-parse on confirmation. Client-side preview state is never trusted.
        $parsed = app(QuestionImportWorkbookParser::class)->parse($this->workbook, auth()->user());
        $this->previewErrors = $parsed['errors'];
        $this->previewRows = array_map(fn (array $row) => [
            'number' => $row['_number'],
            'code' => $row['question_code'] ?? '-',
            'type' => $row['type'],
            'stem' => mb_strimwidth($row['stem'], 0, 90, '…'),
        ], $parsed['rows']);
        $this->totalRows = $parsed['total_rows'];
        $this->validRows = $parsed['valid_rows'];
        $this->previewReady = $parsed['success'];

        if (!$parsed['success']) {
            $this->addError('workbook', 'Validasi ulang gagal. Tidak ada data yang diimpor.');
            return null;
        }

        $result = app(ExcelQuestionImporter::class)->import(
            $this->workbook->getRealPath(),
            ['rows' => $parsed['rows']]
        );

        if (!$result['success']) {
            $this->previewReady = false;
            $this->previewErrors = $result['errors'];
            $this->addError('workbook', 'Import dibatalkan. Seluruh perubahan dalam batch telah di-rollback.');
            return null;
        }

        app(AuditService::class)->log('IMPORT_QUESTIONS_BATCH', 'Question', null, null, [
            'imported' => $result['imported'],
            'total_rows' => $result['total_rows'],
            'file_name' => $this->workbook->getClientOriginalName(),
        ]);

        session()->flash('message', $result['imported'].' soal berhasil diimpor.');
        return redirect()->route('teacher.questions.index');
    }

    public function render()
    {
        return view('livewire.teacher.question-import-wizard')
            ->layout('layouts.teacher');
    }
}
