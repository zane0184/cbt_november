<?php

namespace App\Livewire\Admin;

use App\Models\Tenant;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class TenantProfileManagement extends Component
{
    public string $name = '';
    public string $shortName = '';
    public string $nsm = '';
    public string $npsn = '';
    public string $level = '';
    public string $ownershipStatus = '';
    public string $address = '';
    public string $village = '';
    public string $district = '';
    public string $city = '';
    public string $province = '';
    public string $postalCode = '';
    public string $phone = '';
    public string $email = '';
    public string $website = '';
    public string $principalName = '';
    public string $additionalInfo = '';

    public function mount(): void
    {
        $this->authorizeProfileManagement();
        $tenant = $this->currentTenant();

        $this->name = $tenant->name;
        $this->shortName = $tenant->short_name ?? '';
        $this->nsm = $tenant->nsm ?? '';
        $this->npsn = $tenant->npsn ?? '';
        $this->level = $tenant->level ?? '';
        $this->ownershipStatus = $tenant->ownership_status ?? '';
        $this->address = $tenant->address ?? '';
        $this->village = $tenant->village ?? '';
        $this->district = $tenant->district ?? '';
        $this->city = $tenant->city ?? '';
        $this->province = $tenant->province ?? '';
        $this->postalCode = $tenant->postal_code ?? '';
        $this->phone = $tenant->phone ?? '';
        $this->email = $tenant->email ?? '';
        $this->website = $tenant->website ?? '';
        $this->principalName = $tenant->principal_name ?? '';
        $this->additionalInfo = $tenant->additional_info ?? '';
    }

    public function save(): void
    {
        $this->authorizeProfileManagement();
        $tenantId = (string) auth()->user()->tenant_id;

        $data = $this->validate([
            'name' => 'required|string|max:255',
            'shortName' => 'nullable|string|max:120',
            'nsm' => ['nullable', 'digits:12', Rule::unique('tenants', 'nsm')->ignore($tenantId)],
            'npsn' => ['nullable', 'digits:8', Rule::unique('tenants', 'npsn')->ignore($tenantId)],
            'level' => ['required', Rule::in(['MI', 'MTs', 'MA', 'RA'])],
            'ownershipStatus' => ['required', Rule::in(['NEGERI', 'SWASTA'])],
            'address' => 'required|string|max:2000',
            'village' => 'nullable|string|max:120',
            'district' => 'nullable|string|max:120',
            'city' => 'nullable|string|max:120',
            'province' => 'nullable|string|max:120',
            'postalCode' => 'nullable|digits:5',
            'phone' => 'nullable|string|max:25',
            'email' => 'nullable|email:rfc|max:255',
            'website' => 'nullable|url|max:255',
            'principalName' => 'nullable|string|max:180',
            'additionalInfo' => 'nullable|string|max:5000',
        ], [
            'nsm.digits' => 'NSM harus tepat 12 digit.',
            'npsn.digits' => 'NPSN harus tepat 8 digit.',
            'postalCode.digits' => 'Kode pos harus tepat 5 digit.',
            'level.in' => 'Pilih jenjang madrasah yang tersedia.',
            'ownershipStatus.in' => 'Pilih status negeri atau swasta.',
        ]);

        $attributes = [
            'name' => $data['name'],
            'short_name' => $data['shortName'] ?: null,
            'nsm' => $data['nsm'] ?: null,
            'npsn' => $data['npsn'] ?: null,
            'level' => $data['level'],
            'ownership_status' => $data['ownershipStatus'],
            'address' => $data['address'],
            'village' => $data['village'] ?: null,
            'district' => $data['district'] ?: null,
            'city' => $data['city'] ?: null,
            'province' => $data['province'] ?: null,
            'postal_code' => $data['postalCode'] ?: null,
            'phone' => $data['phone'] ?: null,
            'email' => $data['email'] ?: null,
            'website' => $data['website'] ?: null,
            'principal_name' => $data['principalName'] ?: null,
            'additional_info' => $data['additionalInfo'] ?: null,
        ];

        DB::transaction(function () use ($tenantId, $attributes): void {
            $tenant = Tenant::query()->whereKey($tenantId)->lockForUpdate()->firstOrFail();
            abort_unless($tenant->id !== Tenant::SYSTEM_TENANT_ID, 403, 'System Tenant tidak dapat diedit melalui portal madrasah.');

            $changedFields = [];
            foreach ($attributes as $column => $value) {
                if ($tenant->getAttribute($column) !== $value) {
                    $changedFields[] = $column;
                }
            }

            $tenant->fill($attributes);
            $tenant->save();

            if ($changedFields !== []) {
                app(AuditService::class)->log(
                    'TENANT_PROFILE_UPDATED',
                    'Tenant',
                    $tenant->id,
                    null,
                    ['changed_fields' => $changedFields]
                );
            }
        }, 3);

        session()->flash('message', 'Profil madrasah berhasil diperbarui.');
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Profil madrasah berhasil diperbarui.']);
    }

    private function authorizeProfileManagement(): void
    {
        $user = auth()->user();
        abort_unless(
            $user && $user->hasExactRole('ADMIN_MADRASAH') && $user->hasPermission('tenant.manage'),
            403,
            'Hanya Admin Madrasah dengan permission tenant.manage yang dapat mengubah profil madrasah.'
        );
    }

    private function currentTenant(): Tenant
    {
        $tenantId = auth()->user()?->tenant_id;
        abort_unless($tenantId, 403, 'Akun tidak terikat pada instalasi madrasah.');
        return Tenant::query()->whereKey($tenantId)->firstOrFail();
    }

    public function render()
    {
        return view('livewire.admin.tenant-profile-management')->layout('layouts.app');
    }
}
