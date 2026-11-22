<?php

namespace App\Livewire\Admin;

use App\Models\Tenant;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

class TenantProfileManagement extends Component
{
    use WithFileUploads;

    public string $name = '';
    public string $nsm = '';
    public string $npsn = '';
    public string $address = '';
    public string $phone = '';
    public string $email = '';
    public $logo;

    public function mount(): void
    {
        $this->authorizeProfileManagement();
        $tenant = $this->currentTenant();
        $this->name = (string) $tenant->name;
        $this->nsm = (string) ($tenant->nsm ?? '');
        $this->npsn = (string) ($tenant->npsn ?? '');
        $this->address = (string) ($tenant->address ?? '');
        $this->phone = (string) ($tenant->phone ?? '');
        $this->email = (string) ($tenant->email ?? '');
    }

    private function authorizeProfileManagement(): void
    {
        $user = auth()->user();
        abort_unless(
            $user && $user->tenant_id && $user->hasRole('ADMIN_MADRASAH') && $user->hasPermission('academic.manage'),
            403,
            'Akses ditolak. Diperlukan Admin Madrasah dengan permission academic.manage.'
        );
    }

    private function currentTenant(): Tenant
    {
        return Tenant::query()->whereKey((string) auth()->user()->tenant_id)->firstOrFail();
    }

    protected function rules(): array
    {
        $tenantId = (string) auth()->user()->tenant_id;
        return [
            'name' => ['required', 'string', 'max:255'],
            'nsm' => ['nullable', 'digits:12', Rule::unique('tenants', 'nsm')->ignore($tenantId, 'id')],
            'npsn' => ['nullable', 'digits:8', Rule::unique('tenants', 'npsn')->ignore($tenantId, 'id')],
            'address' => ['nullable', 'string', 'max:2000'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function saveProfile(): void
    {
        $this->authorizeProfileManagement();
        $this->validate();
        $tenantId = (string) auth()->user()->tenant_id;
        $newLogoPath = $this->logo
            ? $this->logo->storePublicly("tenants/{$tenantId}/branding", 'public')
            : null;

        try {
            DB::transaction(function () use ($tenantId, $newLogoPath): void {
                $tenant = Tenant::query()->whereKey($tenantId)->lockForUpdate()->firstOrFail();
                $oldValues = [
                    'name' => $tenant->name,
                    'nsm' => $tenant->nsm,
                    'npsn' => $tenant->npsn,
                    'address' => $tenant->address,
                    'phone' => $tenant->phone,
                    'email' => $tenant->email,
                    'logo_path' => $tenant->logo_path,
                ];
                $newValues = [
                    'name' => trim($this->name),
                    'nsm' => trim($this->nsm) !== '' ? trim($this->nsm) : null,
                    'npsn' => trim($this->npsn) !== '' ? trim($this->npsn) : null,
                    'address' => trim($this->address) !== '' ? trim($this->address) : null,
                    'phone' => trim($this->phone) !== '' ? trim($this->phone) : null,
                    'email' => trim($this->email) !== '' ? strtolower(trim($this->email)) : null,
                ];
                if ($newLogoPath) $newValues['logo_path'] = $newLogoPath;

                $tenant->fill($newValues)->save();
                app(AuditService::class)->log('TENANT_PROFILE_UPDATED', 'Tenant', $tenant->id, $oldValues, $newValues);
            }, 3);
        } catch (Throwable $exception) {
            if ($newLogoPath) Storage::disk('public')->delete($newLogoPath);
            throw $exception;
        }

        $this->logo = null;
        session()->flash('message', 'Profil madrasah berhasil diperbarui.');
    }

    public function render()
    {
        $this->authorizeProfileManagement();
        $tenant = $this->currentTenant();
        return view('livewire.admin.tenant-profile-management', [
            'tenant' => $tenant,
            'logoUrl' => $tenant->logo_path ? Storage::disk('public')->url($tenant->logo_path) : null,
        ])->layout('layouts.app');
    }
}
