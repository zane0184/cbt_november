<?php

namespace App\Livewire\Admin;

use App\Models\ImportBatch;
use App\Models\ImportBatchRow;
use App\Services\Import\ImportWorkbookService;
use Illuminate\Support\Facades\URL;
use Livewire\Component;
use Livewire\WithFileUploads;

class ImportManagement extends Component
{
    use WithFileUploads;

    public string $type = 'GURU';
    public $upload = null;
    public ?string $batchId = null;
    public array $activationLinks = [];
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
            'upload' => ['required', 'file', 'mimes:xlsx', 'max:10240'],
        ]);
        if (strtolower($this->upload->getClientOriginalExtension()) !== 'xlsx') {
            $this->addError('upload', 'Hanya workbook .xlsx yang diterima.');
            return;
        }

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
            $this->addError('upload', $exception->getMessage());
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
            $this->activationLinks = $result['activation_links'];
            $this->notice = 'Proses import selesai. Tautan aktivasi ditampilkan satu kali; salin dan bagikan secara pribadi kepada pemilik akun.';
            $this->upload = null;
            $this->batchId = (string) $result['batch']->id;
        } catch (\Throwable $exception) {
            $this->addError('upload', $exception->getMessage());
        }
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
        $this->reset(['upload', 'batchId', 'activationLinks', 'notice']);
    }

    public function showBatch(string $id): void
    {
        $this->authorizeImport();
        $batch = ImportBatch::query()->where('tenant_id', auth()->user()->tenant_id)->findOrFail($id);
        $this->batchId = (string) $batch->id;
        $this->activationLinks = [];
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
        ])->layout('layouts.app');
    }
}
