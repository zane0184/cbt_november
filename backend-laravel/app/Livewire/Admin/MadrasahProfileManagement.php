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

class MadrasahProfileManagement extends Component
{
    use WithFileUploads;

    public string $name = '';
    public string $nsm = '';
    public string $npsn = '';
    public string $address = '';
    public string $phone = '';
    public string $email = '';
    public $logo = null;
    public ?string $currentLogoPath = null;

    public function mount(): void
    {
        $tenant = $this->authorizedTenant();
        $this->name = (string) $tenant->name;
        $this->nsm = (string) ($tenant->nsm ?? '');
        $this->npsn = (string) ($tenant->npsn ?? '');
        $this->address = (string) ($tenant->address ?? '');
        $this->phone = (string) ($tenant->phone ?? '');
        $this->email = (string) ($tenant->email ?? '');
        $this->currentLogoPath = $tenant->logo_path;
    }

    private function authorizedTenant(): Tenant
    {
        $user = auth()->user();
        abort_unless(
            $user && $user->tenant_id
                && $user->roles()->whereIn('name', ['ADMIN_MADRASAH', 'OPERATOR'])->exists()
                && $user->hasPermission('tenant.manage'),
            403,
            'Hanya Admin Madrasah dengan permission tenant.manage yang dapat mengubah identitas madrasah.'
        );

        return Tenant::query()->whereKey($user->tenant_id)->firstOrFail();
    }

    public function save(AuditService $auditService): void
    {
        $tenant = $this->authorizedTenant();
        $tenantId = (string) $tenant->id;

        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'nsm' => ['nullable', 'string', 'digits:12', Rule::unique('tenants', 'nsm')->ignore($tenantId, 'id')],
            'npsn' => ['nullable', 'string', 'digits:8', Rule::unique('tenants', 'npsn')->ignore($tenantId, 'id')],
            'address' => ['nullable', 'string', 'max:2000'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $newLogoPath = null;
        try {
            if ($this->logo) {
                $newLogoPath = $this->logo->storePublicly("tenants/{$tenantId}/branding", 'public');
                if (!$newLogoPath) {
                    throw new \RuntimeException('Logo madrasah gagal disimpan.');
                }
            }

            DB::transaction(function () use ($tenantId, $data, $newLogoPath, $auditService): void {
                $lockedTenant = Tenant::query()->whereKey($tenantId)->lockForUpdate()->firstOrFail();
                $oldValues = $lockedTenant->only(['name', 'nsm', 'npsn', 'address', 'phone', 'email', 'logo_path']);

                $lockedTenant->update([
                    'name' => trim($data['name']),
                    'nsm' => $data['nsm'] ?: null,
                    'npsn' => $data['npsn'] ?: null,
                    'address' => $data['address'] ?: null,
                    'phone' => $data['phone'] ?: null,
                    'email' => $data['email'] ?: null,
                    'logo_path' => $newLogoPath ?: $lockedTenant->logo_path,
                ]);

                $auditService->log(
                    'TENANT_PROFILE_UPDATED',
                    'Tenant',
                    $lockedTenant->id,
                    $oldValues,
                    $lockedTenant->fresh()->only(['name', 'nsm', 'npsn', 'address', 'phone', 'email', 'logo_path'])
                );
            });
        } catch (Throwable $exception) {
            if ($newLogoPath) {
                Storage::disk('public')->delete($newLogoPath);
            }
            throw $exception;
        }

        $this->currentLogoPath = $newLogoPath ?: $this->currentLogoPath;
        $this->reset('logo');
        session()->flash('madrasahProfileMessage', 'Identitas madrasah berhasil diperbarui dan dicatat pada audit log.');
    }

    public function render()
    {
        $tenant = $this->authorizedTenant();

        return view('livewire.admin.madrasah-profile-management', [
            'tenant' => $tenant,
            'logoUrl' => $this->currentLogoPath
                ? Storage::disk('public')->url($this->currentLogoPath)
                : null,
        ])->layout('layouts.app');
    }
}
