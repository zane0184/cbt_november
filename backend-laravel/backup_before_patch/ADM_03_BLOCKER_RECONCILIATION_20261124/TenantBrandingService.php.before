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

        if (!Str::isUuid($tenantId) || $this->resolveInstallationTenantId($tenantId) !== $tenantId) {
            throw new RuntimeException('Identitas instalasi branding tidak sesuai dengan akun atau instalasi aktif.');
        }

        $maxBytes = $kind === 'favicon' ? 512 * 1024 : 2048 * 1024;
        $sourcePath = $file->getRealPath();
        $size = $file->getSize();
        if (!$sourcePath || !is_file($sourcePath) || !is_readable($sourcePath) || !$size || $size > $maxBytes) {
            throw new RuntimeException('Ukuran atau sumber file branding tidak valid.');
        }

        $image = @getimagesize($sourcePath);
        if ($image === false) {
            throw new RuntimeException('Konten file branding bukan gambar raster yang valid.');
        }

        $types = [
            IMAGETYPE_PNG => ['png', 'image/png'],
            IMAGETYPE_JPEG => ['jpg', 'image/jpeg'],
            IMAGETYPE_WEBP => ['webp', 'image/webp'],
        ];
        $detected = $types[$image[2]] ?? null;
        $originalExtension = strtolower(pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION));
        $extensionAliases = ['jpeg' => 'jpg'];
        $normalizedOriginalExtension = $extensionAliases[$originalExtension] ?? $originalExtension;

        if (
            !$detected
            || !in_array($originalExtension, ['png', 'jpg', 'jpeg', 'webp'], true)
            || $normalizedOriginalExtension !== $detected[0]
            || (new \finfo(FILEINFO_MIME_TYPE))->file($sourcePath) !== $detected[1]
        ) {
            throw new RuntimeException('Ekstensi dan MIME file branding tidak sesuai dengan konten gambar.');
        }

        [$width, $height] = $image;
        if ($kind === 'favicon') {
            if ($width < 32 || $height < 32 || $width > 256 || $height > 256 || $width !== $height) {
                throw new RuntimeException('Favicon harus persegi dengan ukuran 32–256 piksel.');
            }
        } elseif ($width < 64 || $height < 64 || $width > 2000 || $height > 2000) {
            throw new RuntimeException('Dimensi logo harus berada pada rentang 64–2000 piksel.');
        }

        $extension = $detected[0];
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
    public function localPath(?string $relativePath, ?string $tenantId = null): ?string
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

        // Managed branding paths are installation-scoped. A valid file under
        // another installation's tenant namespace must never be resolved.
        $managedBrandingRoot = null;
        if (($segments[0] ?? null) === 'tenants' && ($segments[2] ?? null) === 'branding') {
            $scopeTenantId = $this->resolveInstallationTenantId($tenantId);
            if (!$scopeTenantId || ($segments[1] ?? null) !== $scopeTenantId) {
                return null;
            }

            $managedBrandingRoot = 'tenants' . DIRECTORY_SEPARATOR . $scopeTenantId . DIRECTORY_SEPARATOR . 'branding';
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

        // realpath() follows symlinks. Keep the resolved target inside this
        // installation's own branding directory, not merely inside public storage.
        if (
            $managedBrandingRoot !== null
            && !str_starts_with($target, $root . DIRECTORY_SEPARATOR . $managedBrandingRoot . DIRECTORY_SEPARATOR)
        ) {
            return null;
        }

        return $target;
    }

    public function publicUrl(?string $relativePath, ?string $tenantId = null): ?string
    {
        if (!$this->localPath($relativePath, $tenantId)) {
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
        if (
            !$relativePath
            || str_contains($relativePath, '..')
            || $this->resolveInstallationTenantId($tenantId) !== $tenantId
        ) {
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

            $target = $this->localPath($relativePath, $tenantId);
            if ($target) {
                File::delete($target);
            }
        }, 3);
    }
    /**
     * Resolve the active installation boundary from the authenticated account
     * or the single local installation identity. Explicit IDs never override
     * an authenticated account or a conflicting installation identity.
     */
    private function resolveInstallationTenantId(?string $requestedTenantId = null): ?string
    {
        $user = Auth::user() ?? Auth::guard('sanctum')->user();
        if ($user?->tenant_id) {
            $authenticatedTenantId = (string) $user->tenant_id;
            return !$requestedTenantId || $requestedTenantId === $authenticatedTenantId
                ? $authenticatedTenantId
                : null;
        }

        if (!Schema::hasTable('local_installation_identities')) {
            return null;
        }

        $identities = LocalInstallationIdentity::withoutGlobalScopes()->limit(2)->get(['tenant_id']);
        if ($identities->count() !== 1) {
            return null;
        }

        $installationTenantId = (string) $identities->first()->tenant_id;
        return !$requestedTenantId || $requestedTenantId === $installationTenantId
            ? $installationTenantId
            : null;
    }

}
