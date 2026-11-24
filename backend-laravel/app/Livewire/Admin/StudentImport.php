<?php

namespace App\Livewire\Admin;

use App\Models\Rombel;
use App\Services\StudentImportService;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;

class StudentImport extends Component
{
    use WithFileUploads;

    public $file;
    public string $selectedRombelId = '';
    public array $previewRows = [];
    public array $summary = [];
    public ?string $previewHash = null;

    public function mount(): void
    {
        $this->authorizeImport();
    }

    private function authorizeImport(): void
    {
        $user = auth()->user();
        abort_unless(
            $user && $user->tenant_id
            && $user->hasExactRole('ADMIN_MADRASAH')
            && $user->hasPermission('user.manage'),
            403,
            'Akses ditolak. Diperlukan Admin Madrasah dan permission user.manage.'
        );
    }

    private function validationRules(): array
    {
        $tenantId = auth()->user()->tenant_id;

        return [
            'file' => ['required', 'file', 'mimes:xlsx,csv,txt', 'max:10240'],
            'selectedRombelId' => [
                'required',
                'uuid',
                Rule::exists('rombels', 'id')->where(fn ($query) => $query
                    ->where('tenant_id', $tenantId)
                    ->where('status', 'ACTIVE')),
            ],
        ];
    }

    public function preview(): void
    {
        $this->authorizeImport();
        $this->validate($this->validationRules());

        $tenantId = auth()->user()->tenant_id;
        $path = $this->file->getRealPath();
        $extension = strtolower($this->file->getClientOriginalExtension());

        $result = app(StudentImportService::class)->preview(
            $path,
            $extension,
            $tenantId,
            $this->selectedRombelId
        );

        $this->previewRows = $result['rows'];
        $this->summary = [
            'total' => $result['total'],
            'valid' => $result['valid'],
            'invalid' => $result['invalid'],
            'rombel_name' => $result['rombel_name'],
        ];
        $this->previewHash = $result['file_sha256'];
    }

    public function import(): void
    {
        $this->authorizeImport();
        $this->validate($this->validationRules());

        $tenantId = auth()->user()->tenant_id;
        $path = $this->file->getRealPath();
        $extension = strtolower($this->file->getClientOriginalExtension());
        $result = app(StudentImportService::class)->preview(
            $path,
            $extension,
            $tenantId,
            $this->selectedRombelId
        );

        $this->previewRows = $result['rows'];
        $this->summary = [
            'total' => $result['total'],
            'valid' => $result['valid'],
            'invalid' => $result['invalid'],
            'rombel_name' => $result['rombel_name'],
        ];
        $this->previewHash = $result['file_sha256'];

        if ($result['invalid'] > 0 || $result['total'] < 1) {
            throw ValidationException::withMessages([
                'file' => 'Import belum dapat diproses. Perbaiki seluruh error pada preview.',
            ]);
        }

        $imported = app(StudentImportService::class)->import(
            $result,
            $tenantId,
            auth()->user()->id,
            $this->selectedRombelId
        );

        $this->reset(['file', 'selectedRombelId', 'previewRows', 'summary', 'previewHash']);
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => $imported['imported'] . ' akun Siswa berhasil diimpor.',
        ]);
        session()->flash('message', $imported['imported'] . ' akun Siswa berhasil diimpor. Password awal dibuat acak; gunakan fitur Reset Password untuk menerbitkan kredensial kepada Siswa.');
    }

    public function downloadCsvTemplate()
    {
        $this->authorizeImport();

        $csv = "nama,nisn,jenis_kelamin,tempat_lahir,tanggal_lahir,nama_orang_tua,telepon,alamat\n";
        $csv .= "Contoh Nama,0001234567,L,Jakarta,2015-01-31,Nama Orang Tua,081234567890,Alamat Siswa\n";

        return response()->streamDownload(
            fn () => print($csv),
            'template-import-siswa.csv',
            ['Content-Type' => 'text/csv; charset=UTF-8']
        );
    }

    public function render()
    {
        $this->authorizeImport();
        $tenantId = auth()->user()->tenant_id;
        $rombels = Rombel::query()
            ->with(['academicYear', 'gradeLevel'])
            ->where('tenant_id', $tenantId)
            ->where('status', 'ACTIVE')
            ->orderBy('name')
            ->get();

        return view('livewire.admin.student-import', compact('rombels'))
            ->layout('layouts.app');
    }
}
