<?php

namespace App\Services;

use App\Models\LocalInstallationIdentity;
use App\Models\Tenant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use RuntimeException;

class TenantBrandingService
{
    public function currentTenant(): ?Tenant
    {
        $user = Auth::user() ?? Auth::guard('sanctum')->user();
        $tenantId = $user?->tenant_id;

        // The unauthenticated login page resolves only the sole local
        // installation identity; it never trusts stale static tenant context.
        if (!$tenantId && Schema::hasTable('local_installation_identities')) {
            $identities = LocalInstallationIdentity::withoutGlobalScopes()->limit(2)->get(['tenant_id']);
            if ($identities->count() === 1) {
                $tenantId = $identities->first()->tenant_id;
            }
        }

        return $tenantId ? Tenant::query()->whereKey($tenantId)->first() : null;
    }

    public function storeUpload(TemporaryUploadedFile|UploadedFile $file, string $tenantId, string $kind): string
    {
        $extension = strtolower((string) $file->guessExtension());
        if (!in_array($extension, ['png', 'jpg', 'jpeg', 'webp'], true)) {
            throw new RuntimeException('Format file branding tidak didukung.');
        }

        $relativePath = sprintf('tenants/%s/branding/%s-%s.%s', $tenantId, $kind, (string) Str::uuid(), $extension);
        $root = storage_path('app/public');
        File::ensureDirectoryExists($root);
        $absolutePath = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        File::ensureDirectoryExists(dirname($absolutePath));

        if (!File::copy($file->getRealPath(), $absolutePath)) {
            throw new RuntimeException('File branding gagal disimpan ke storage lokal.');
        }

        return $relativePath;
    }

    public function publicUrl(?string $relativePath): ?string
    {
        if (!$relativePath || str_contains($relativePath, '..') || str_starts_with($relativePath, '/')) {
            return null;
        }

        $root = realpath(storage_path('app/public'));
        if (!$root) {
            return null;
        }

        $target = realpath($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath));
        if (!$target || !str_starts_with($target, $root . DIRECTORY_SEPARATOR) || !is_file($target)) {
            return null;
        }

        return asset('storage/' . ltrim($relativePath, '/'));
    }

    public function deleteManaged(?string $relativePath, string $tenantId): void
    {
        if (!$relativePath || str_contains($relativePath, '..')) {
            return;
        }

        $prefix = 'tenants/' . $tenantId . '/branding/';
        if (!str_starts_with($relativePath, $prefix)) {
            return;
        }

        $root = realpath(storage_path('app/public'));
        if (!$root) {
            return;
        }

        $target = realpath($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath));
        if ($target && str_starts_with($target, $root . DIRECTORY_SEPARATOR) && is_file($target)) {
            File::delete($target);
        }
    }
}
