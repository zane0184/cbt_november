<?php

namespace App\Livewire\Admin;

use App\Models\Tenant;
use App\Services\AuditService;
use App\Services\TenantBrandingService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

class TenantBrandingAssetsManagement extends Component
{
    use WithFileUploads;

    public $appLogoUpload = null;
    public $documentLogoUpload = null;
    public $faviconUpload = null;

    public function mount(): void
    {
        $this->authorizeBrandingManagement();
    }

    public function save(): void
    {
        $this->authorizeBrandingManagement();
        $this->validate([
            'appLogoUpload' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'documentLogoUpload' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'faviconUpload' => ['nullable', 'image', 'mimes:png,webp', 'max:512'],
        ], [
            'appLogoUpload.max' => 'Logo aplikasi maksimal 2 MB.',
            'documentLogoUpload.max' => 'Logo dokumen maksimal 2 MB.',
            'faviconUpload.max' => 'Favicon maksimal 512 KB.',
        ]);

        $tenantId = (string) auth()->user()->tenant_id;
        $branding = app(TenantBrandingService::class);
        $newPaths = [];
        $oldPaths = [];

        try {
            if ($this->appLogoUpload) {
                $newPaths['logo_path'] = $branding->storeUpload($this->appLogoUpload, $tenantId, 'app-logo');
            }
            if ($this->documentLogoUpload) {
                $newPaths['document_logo_path'] = $branding->storeUpload($this->documentLogoUpload, $tenantId, 'document-logo');
            }
            if ($this->faviconUpload) {
                $newPaths['favicon_path'] = $branding->storeUpload($this->faviconUpload, $tenantId, 'favicon');
            }

            if ($newPaths === []) {
                $this->addError('uploads', 'Pilih setidaknya satu file branding sebelum menyimpan.');
                return;
            }

            DB::transaction(function () use ($tenantId, $newPaths, &$oldPaths): void {
                $tenant = Tenant::query()->whereKey($tenantId)->lockForUpdate()->firstOrFail();
                abort_unless($tenant->id !== Tenant::SYSTEM_TENANT_ID, 403);

                foreach ($newPaths as $column => $path) {
                    $oldPaths[$column] = $tenant->getAttribute($column);
                    $tenant->setAttribute($column, $path);
                }
                $tenant->save();

                app(AuditService::class)->log(
                    'TENANT_BRANDING_ASSETS_UPDATED',
                    'Tenant',
                    $tenant->id,
                    null,
                    ['changed_fields' => array_keys($newPaths)]
                );
            }, 3);
        } catch (Throwable $exception) {
            foreach ($newPaths as $path) {
                $branding->deleteManaged($path, $tenantId);
            }
            throw $exception;
        }

        foreach ($oldPaths as $oldPath) {
            $branding->deleteManaged($oldPath, $tenantId);
        }

        $this->reset('appLogoUpload', 'documentLogoUpload', 'faviconUpload');
        session()->flash('message', 'File branding berhasil diperbarui.');
        $this->dispatch('notify', ['type' => 'success', 'message' => 'File branding berhasil diperbarui.']);
    }

    public function removeAppLogo(): void
    {
        $this->removeAsset('logo_path', 'TENANT_APP_LOGO_REMOVED');
    }

    public function removeDocumentLogo(): void
    {
        $this->removeAsset('document_logo_path', 'TENANT_DOCUMENT_LOGO_REMOVED');
    }

    public function removeFavicon(): void
    {
        $this->removeAsset('favicon_path', 'TENANT_FAVICON_REMOVED');
    }

    private function removeAsset(string $column, string $event): void
    {
        $this->authorizeBrandingManagement();
        $tenantId = (string) auth()->user()->tenant_id;

        $oldPath = DB::transaction(function () use ($tenantId, $column, $event): ?string {
            $tenant = Tenant::query()->whereKey($tenantId)->lockForUpdate()->firstOrFail();
            abort_unless($tenant->id !== Tenant::SYSTEM_TENANT_ID, 403);

            $oldPath = $tenant->getAttribute($column);
            if (!$oldPath) {
                return null;
            }

            $tenant->setAttribute($column, null);
            $tenant->save();
            app(AuditService::class)->log($event, 'Tenant', $tenant->id, null, ['changed_fields' => [$column]]);
            return $oldPath;
        }, 3);

        app(TenantBrandingService::class)->deleteManaged($oldPath, $tenantId);
        session()->flash('message', 'File branding berhasil dihapus dari profil.');
    }

    private function authorizeBrandingManagement(): void
    {
        $user = auth()->user();
        abort_unless($user && $user->hasExactRole('ADMIN_MADRASAH') && $user->hasPermission('tenant.manage'), 403);
    }

    private function currentTenant(): Tenant
    {
        $tenantId = auth()->user()?->tenant_id;
        abort_unless($tenantId, 403);
        return Tenant::query()->whereKey($tenantId)->firstOrFail();
    }

    public function render()
    {
        $this->authorizeBrandingManagement();
        $tenant = $this->currentTenant();
        $branding = app(TenantBrandingService::class);

        $favicon = $branding->faviconAttributes($tenant->favicon_path);

        return view('livewire.admin.tenant-branding-assets-management', [
            'tenant' => $tenant,
            'appLogoUrl' => $branding->publicUrl($tenant->logo_path),
            'documentLogoUrl' => $branding->publicUrl($tenant->document_logo_path),
            'faviconUrl' => $favicon['url'] ?? null,
        ])->layout('layouts.app');
    }
}
