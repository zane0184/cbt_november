<?php

namespace App\Livewire\Admin;

use App\Models\ImportBatch;
use App\Services\Import\ImportWorkbookService;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use Livewire\Component;
use Livewire\WithFileUploads;

class ImportManagement extends Component
{
    use WithFileUploads;

    public string $type = 'GURU';
    public $upload = null;
    public ?string $batchId = null;
    public string $notice = '';

    private function authorizeImport(): void
    {
        $user = auth()->user();
        abort_unless($user && $user->tenant_id && $user->hasRole('ADMIN_MADRASAH')
            && $user->hasPermission('user.manage'), 403);
    }

    public function mount(): void
    {
        $this->authorizeImport();
    }

    public function previewUpload(): void
    {
        $this->authorizeImport();
        $this->validate([
            'type' => ['required', 'in:GURU,SISWA'],
            'upload' => ['required', 'file', 'max:10240'],
        ]);
        if (strtolower($this->upload->getClientOriginalExtension()) !== 'xlsx') {
            $this->addError('upload', 'Hanya workbook .xlsx yang diterima.');
            return;
        }
        $mime = $this->upload->getMimeType();
        if (!in_array($mime, [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/zip', 'application/x-zip', 'application/octet-stream',
        ], true)) {
            $this->addError('upload', 'Tipe berkas tidak dikenali sebagai workbook XLSX.');
            return;
        }

        $this->batchId = null;
        $this->notice = '';

        try {
            $batch = app(ImportWorkbookService::class)->preview(
                $this->upload,
                $this->type,
                (string) auth()->user()->tenant_id,
                (string) auth()->id(),
            );
            $this->batchId = (string) $batch->id;
            $this->notice = $batch->status === 'PREVIEW'
                ? 'Preview berhasil dibuat. Periksa seluruh baris sebelum konfirmasi.'
                : 'File ini sudah pernah diproses. Lihat status pada riwayat import.';
            $this->resetValidation('upload');
        } catch (\Throwable $exception) {
            $this->addError('upload', $this->safeErrorMessage($exception, true));
        }
    }

    public function confirmImport(): void
    {
        $this->authorizeImport();
        if (!$this->batchId) {
            $this->addError('upload', 'Tidak ada preview yang dapat dikonfirmasi.');
            return;
        }

        try {
            $result = app(ImportWorkbookService::class)->confirm(
                $this->batchId,
                (string) auth()->user()->tenant_id,
                (string) auth()->id(),
            );
            session()->flash('import_activation_links', $result['activation_links']);
            session()->flash('import_notice', 'Proses import selesai. Tautan aktivasi ditampilkan satu kali; salin dan bagikan secara pribadi kepada pemilik akun.');
            $this->upload = null;
            $this->batchId = (string) $result['batch']->id;
            return redirect()->route('admin.import');
        } catch (\Throwable $exception) {
            $this->addError('upload', $this->safeErrorMessage($exception));
        }
    }

    public function regenerateActivation(string $rowId): void
    {
        $this->authorizeImport();
        if (!$this->batchId) {
            $this->addError('upload', 'Pilih riwayat import terlebih dahulu.');
            return;
        }
        try {
            $link = app(ImportWorkbookService::class)->regenerateActivation(
                $this->batchId,
                $rowId,
                (string) auth()->user()->tenant_id,
                (string) auth()->id(),
            );
            session()->flash('import_activation_links', [$link]);
            session()->flash('import_notice', 'Tautan aktivasi baru diterbitkan. Tautan lama telah dinonaktifkan; salin dan bagikan secara pribadi.');
            return redirect()->route('admin.import');
        } catch (\Throwable $exception) {
            $this->addError('upload', $this->safeErrorMessage($exception));
        }
    }

    private function safeErrorMessage(\Throwable $exception, bool $allowValidationMessage = false): string
    {
        if ($allowValidationMessage && $exception instanceof \App\Exceptions\ImportWorkbookValidationException) return $exception->getMessage();
        if ($exception instanceof QueryException) {
            Log::error('Import operation failed at database layer.', [
                'tenant_id' => auth()->user()?->tenant_id,
                'user_id' => auth()->id(),
                'exception_class' => get_class($exception),
            ]);
            return 'Operasi import gagal pada penyimpanan data. Tidak ada perubahan yang dapat dipastikan berhasil; periksa riwayat import atau hubungi dukungan teknis.';
        }

        Log::error('Import operation failed.', ['tenant_id' => auth()->user()?->tenant_id, 'user_id' => auth()->id(), 'exception_class' => get_class($exception)]);
        return 'Operasi import tidak dapat diselesaikan. Silakan periksa kembali data atau hubungi dukungan teknis.';
    }

    public function discardPreview(): void
    {
        $this->authorizeImport();
        if ($this->batchId) {
            app(ImportWorkbookService::class)->discard(
                $this->batchId,
                (string) auth()->user()->tenant_id,
                (string) auth()->id(),
            );
        }
        session()->forget(['import_activation_links', 'import_notice']);
        $this->reset(['upload', 'batchId', 'notice']);
    }

    public function showBatch(string $id): void
    {
        $this->authorizeImport();
        $batch = ImportBatch::query()->where('tenant_id', auth()->user()->tenant_id)->findOrFail($id);
        $this->batchId = (string) $batch->id;
        $this->notice = 'Detail riwayat import: tautan aktivasi tidak disimpan dan tidak dapat ditampilkan ulang.';
    }

    public function render()
    {
        $this->authorizeImport();
        $tenantId = (string) auth()->user()->tenant_id;
        $batch = $this->batchId
            ? ImportBatch::query()->where('tenant_id', $tenantId)->with(['rows' => fn ($query) => $query->orderBy('sheet_name')->orderBy('row_number')])->find($this->batchId)
            : null;
        $history = ImportBatch::query()->where('tenant_id', $tenantId)->latest()->limit(20)->get();

        return view('livewire.admin.import-management', [
            'batch' => $batch,
            'history' => $history,
            'activationLinks' => session()->pull('import_activation_links', []),
            'notice' => session()->pull('import_notice', $this->notice),
        ])->layout('layouts.app');
    }
}
