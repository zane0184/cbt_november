<?php

namespace App\\Livewire\\Admin;

use App\\Models\\Tenant;
use App\\Services\\AuditService;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Validation\\Rule;
use Livewire\\Component;

class MadrasahProfile extends Component
{
    public string $name = '';
    public string $nsm = '';
    public string $npsn = '';
    public string $address = '';
    public string $phone = '';
    public string $email = '';

    public function mount(): void
    {
        $this->authorizeTenantManagement();
        $this->fillProfile($this->currentTenant());
    }

    private function authorizeTenantManagement(): void
    {
        $user = auth()->user();

        abort_unless(
            $user
            && $user->tenant_id
            && (string) $user->tenant_id !== Tenant::SYSTEM_TENANT_ID
            && $user->roles()->count() === 1
            && $user->roles()->where('name', 'ADMIN_MADRASAH')->exists()
            && $user->hasPermission('tenant.manage'),
            403,
            'Akses ditolak. Diperlukan role Admin Madrasah dan permission tenant.manage.'
        );
    }

    private function currentTenant(): Tenant
    {
        $this->authorizeTenantManagement();

        return Tenant::query()
            ->whereKey(auth()->user()->tenant_id)
            ->where('status', 'ACTIVE')
            ->firstOrFail();
    }

    private function fillProfile(Tenant $tenant): void
    {
        $this->name = $tenant->name;
        $this->nsm = $tenant->nsm ?? '';
        $this->npsn = $tenant->npsn ?? '';
        $this->address = $tenant->address ?? '';
        $this->phone = $tenant->phone ?? '';
        $this->email = $tenant->email ?? '';
    }

    protected function rules(): array
    {
        $tenant = $this->currentTenant();

        return [
            'name' => ['required', 'string', 'max:255'],
            'nsm' => ['nullable', 'string', 'max:12', Rule::unique('tenants', 'nsm')->ignore($tenant->id)],
            'npsn' => ['nullable', 'digits:8', Rule::unique('tenants', 'npsn')->ignore($tenant->id)],
            'address' => ['nullable', 'string', 'max:5000'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
        ];
    }

    public function save(): void
    {
        $this->authorizeTenantManagement();
        $validated = $this->validate();

        DB::transaction(function () use ($validated): void {
            $tenant = Tenant::query()
                ->whereKey(auth()->user()->tenant_id)
                ->where('status', 'ACTIVE')
                ->lockForUpdate()
                ->firstOrFail();

            $oldValues = [
                'name' => $tenant->name,
                'nsm' => $tenant->nsm,
                'npsn' => $tenant->npsn,
                'address' => $tenant->address,
                'phone' => $tenant->phone,
                'email' => $tenant->email,
            ];

            $normalise = static function ($value): ?string {
                $value = trim((string) ($value ?? ''));

                return $value === '' ? null : $value;
            };

            $tenant->fill([
                'name' => trim($validated['name']),
                'nsm' => $normalise($validated['nsm'] ?? null),
                'npsn' => $normalise($validated['npsn'] ?? null),
                'address' => $normalise($validated['address'] ?? null),
                'phone' => $normalise($validated['phone'] ?? null),
                'email' => ($email = $normalise($validated['email'] ?? null)) !== null ? strtolower($email) : null,
            ]);
            $tenant->save();

            $newValues = [
                'name' => $tenant->name,
                'nsm' => $tenant->nsm,
                'npsn' => $tenant->npsn,
                'address' => $tenant->address,
                'phone' => $tenant->phone,
                'email' => $tenant->email,
            ];

            if ($oldValues !== $newValues) {
                app(AuditService::class)->log(
                    'UPDATE_MADRASAH_PROFILE',
                    'Tenant',
                    (string) $tenant->id,
                    $oldValues,
                    $newValues
                );
            }
        });

        $this->fillProfile($this->currentTenant());
        session()->flash('message', 'Profil dasar madrasah berhasil disimpan.');
    }

    public function render()
    {
        $this->authorizeTenantManagement();

        return view('livewire.admin.madrasah-profile', [
            'tenant' => $this->currentTenant(),
        ])->layout('layouts.app');
    }
}
