<?php

namespace App\Services;

use App\Models\LocalInstallationIdentity;
use App\Models\Tenant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
        if (!$file->isValid()) {
            throw new RuntimeException('File branding tidak valid atau gagal diunggah.');
        }

        if (!in_array($kind, ['app-logo', 'document-logo', 'favicon'], true)) {
            throw new RuntimeException('Jenis aset branding tidak didukung.');
        }

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

    /**
     * Resolve only an existing regular file contained inside the public storage root.
     * Reject absolute paths, traversal segments, backslashes and null bytes.
     */
    public function localPath(?string $relativePath): ?string
    {
        if (
            !$relativePath
            || str_starts_with($relativePath, '/')
            || str_contains($relativePath, '\\')
            || str_contains($relativePath, "\0")
        ) {
            return null;
        }

        $segments = explode('/', $relativePath);
        if (in_array('..', $segments, true) || in_array('.', $segments, true)) {
            return null;
        }

        $root = realpath(storage_path('app/public'));
        if (!$root) {
            return null;
        }

        $candidate = $root . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $segments);
        $target = realpath($candidate);
        if (!$target || !str_starts_with($target, $root . DIRECTORY_SEPARATOR) || !is_file($target)) {
            return null;
        }

        return $target;
    }

    public function publicUrl(?string $relativePath): ?string
    {
        if (!$this->localPath($relativePath)) {
            return null;
        }

        return asset('storage/' . ltrim((string) $relativePath, '/'));
    }

    /**
     * Delete only managed assets that are no longer referenced by any branding
     * column. The tenant row lock serializes this check with branding updates.
     */
    public function deleteManaged(?string $relativePath, string $tenantId): void
    {
        if (!$relativePath || str_contains($relativePath, '..')) {
            return;
        }

        $prefix = 'tenants/' . $tenantId . '/branding/';
        if (!str_starts_with($relativePath, $prefix)) {
            return;
        }

        DB::transaction(function () use ($relativePath, $tenantId): void {
            $tenant = Tenant::withTrashed()
                ->withoutGlobalScopes()
                ->whereKey($tenantId)
                ->lockForUpdate()
                ->first();

            if (!$tenant) {
                return;
            }

            $referenced = Tenant::withTrashed()
                ->withoutGlobalScopes()
                ->where(function ($query) use ($relativePath): void {
                    foreach (['logo_path', 'document_logo_path', 'favicon_path'] as $column) {
                        if (Schema::hasColumn('tenants', $column)) {
                            $query->orWhere($column, $relativePath);
                        }
                    }
                })
                ->exists();

            if ($referenced) {
                return;
            }

            $target = $this->localPath($relativePath);
            if ($target) {
                File::delete($target);
            }
        }, 3);
    }
}
