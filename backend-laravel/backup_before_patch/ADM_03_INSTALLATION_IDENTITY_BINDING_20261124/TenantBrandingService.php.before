<?php

namespace App\Services;

use App\Contexts\TenantContext;
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
        $tenantId = $this->resolveInstallationTenantId();

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
        $root = $this->preparePublicStorageRoot();
        if (!$root) {
            throw new RuntimeException('Akar storage publik instalasi tidak aman.');
        }

        $tenantsDirectory = $root . DIRECTORY_SEPARATOR . 'tenants';
        $tenantDirectory = $tenantsDirectory . DIRECTORY_SEPARATOR . $tenantId;
        $brandingDirectory = $tenantDirectory . DIRECTORY_SEPARATOR . 'branding';

        if (
            !$this->ensureManagedDirectory($tenantsDirectory, $root)
            || !$this->ensureManagedDirectory($tenantDirectory, $tenantsDirectory)
            || !$this->ensureManagedDirectory($brandingDirectory, $tenantDirectory)
        ) {
            throw new RuntimeException('Direktori branding instalasi tidak aman.');
        }

        $managedRoot = $this->resolveManagedBrandingRoot($root, $tenantId);
        if (!$managedRoot) {
            throw new RuntimeException('Direktori branding instalasi tidak dapat diverifikasi.');
        }

        $filename = sprintf('%s-%s.%s', $kind, (string) Str::uuid(), $extension);
        $relativePath = sprintf('tenants/%s/branding/%s', $tenantId, $filename);
        $absolutePath = $managedRoot . DIRECTORY_SEPARATOR . $filename;

        // Exclusive destination checks prevent following a pre-existing final
        // symlink or overwriting an existing managed asset.
        if (is_link($absolutePath) || file_exists($absolutePath)) {
            throw new RuntimeException('Tujuan file branding sudah ada atau tidak aman.');
        }

        if (!File::copy($file->getRealPath(), $absolutePath)) {
            throw new RuntimeException('File branding gagal disimpan ke storage lokal.');
        }

        $resolvedTarget = realpath($absolutePath);
        if (
            is_link($absolutePath)
            || !$resolvedTarget
            || dirname($resolvedTarget) !== $managedRoot
            || !str_starts_with($resolvedTarget, $managedRoot . DIRECTORY_SEPARATOR)
        ) {
            if (is_file($absolutePath) && !is_link($absolutePath)) {
                File::delete($absolutePath);
            }
            throw new RuntimeException('File branding keluar dari direktori instalasi yang diizinkan.');
        }

        return $relativePath;
    }

    /**
     * Resolve the installation's public storage root without following a
     * symlinked storage, app, or public directory.
     */
    private function existingPublicStorageRoot(): ?string
    {
        $storagePath = app()->storagePath();
        $appPath = $storagePath . DIRECTORY_SEPARATOR . 'app';
        $publicPath = $appPath . DIRECTORY_SEPARATOR . 'public';

        if (
            is_link($storagePath)
            || is_link($appPath)
            || is_link($publicPath)
            || realpath(dirname($storagePath)) !== dirname($storagePath)
        ) {
            return null;
        }

        $resolvedStorage = realpath($storagePath);
        $resolvedApp = realpath($appPath);
        $resolvedPublic = realpath($publicPath);

        if (
            !$resolvedStorage
            || !$resolvedApp
            || !$resolvedPublic
            || $resolvedStorage !== $storagePath
            || $resolvedApp !== $appPath
            || $resolvedPublic !== $publicPath
            || !is_dir($resolvedPublic)
        ) {
            return null;
        }

        return $resolvedPublic;
    }

    /**
     * Create the local public storage root only after verifying that no path
     * component is a symlink or resolves outside this installation.
     */
    private function preparePublicStorageRoot(): ?string
    {
        $storagePath = app()->storagePath();
        $storageParent = dirname($storagePath);

        if (
            is_link($storagePath)
            || realpath($storageParent) !== $storageParent
        ) {
            return null;
        }

        if (!is_dir($storagePath)) {
            if (file_exists($storagePath) || !@mkdir($storagePath, 0750)) {
                return null;
            }
        }

        $resolvedStorage = realpath($storagePath);
        if (!$resolvedStorage || $resolvedStorage !== $storagePath || !is_dir($resolvedStorage)) {
            return null;
        }

        $appPath = $resolvedStorage . DIRECTORY_SEPARATOR . 'app';
        if (is_link($appPath)) {
            return null;
        }
        if (!is_dir($appPath) && !@mkdir($appPath, 0750)) {
            return null;
        }

        $resolvedApp = realpath($appPath);
        if (!$resolvedApp || $resolvedApp !== $appPath || !is_dir($resolvedApp)) {
            return null;
        }

        $publicPath = $resolvedApp . DIRECTORY_SEPARATOR . 'public';
        if (is_link($publicPath)) {
            return null;
        }
        if (!is_dir($publicPath) && !@mkdir($publicPath, 0750)) {
            return null;
        }

        $resolvedPublic = realpath($publicPath);
        if (!$resolvedPublic || $resolvedPublic !== $publicPath || !is_dir($resolvedPublic)) {
            return null;
        }

        return $resolvedPublic;
    }

    /**
     * Create a managed directory only when its direct parent is canonical and
     * neither the directory entry nor any existing parent is a symlink.
     */
    private function ensureManagedDirectory(string $directory, string $parent): bool
    {
        if (is_link($directory) || realpath($parent) !== $parent) {
            return false;
        }

        if (!is_dir($directory)) {
            if (file_exists($directory) || !@mkdir($directory, 0750)) {
                return false;
            }
        }

        $resolved = realpath($directory);

        return $resolved !== false
            && $resolved === $directory
            && dirname($resolved) === $parent
            && is_dir($resolved);
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

        $isTenantBrandingPath = ($segments[0] ?? null) === 'tenants';
        $managedTenantId = null;
        if ($isTenantBrandingPath) {
            if (
                count($segments) !== 4
                || ($segments[2] ?? null) !== 'branding'
                || !Str::isUuid((string) ($segments[1] ?? ''))
            ) {
                return null;
            }

            $scopeTenantId = $this->resolveInstallationTenantId($tenantId);
            if (!$scopeTenantId || ($segments[1] ?? null) !== $scopeTenantId) {
                return null;
            }
            $managedTenantId = $scopeTenantId;
        }

        $root = $this->existingPublicStorageRoot();
        if (!$root) {
            return null;
        }

        $candidate = $root . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $segments);
        $target = realpath($candidate);
        if (!$target || !str_starts_with($target, $root . DIRECTORY_SEPARATOR) || !is_file($target)) {
            return null;
        }

        // A managed path must remain inside its own canonical branding directory,
        // even when its final file entry is a symlink to a path elsewhere in public storage.
        if ($managedTenantId !== null) {
            $managedRoot = $this->resolveManagedBrandingRoot($root, $managedTenantId);
            if (!$managedRoot || !str_starts_with($target, $managedRoot . DIRECTORY_SEPARATOR)) {
                return null;
            }
        }

        // Check the resolved namespace too: a legacy path must not become a
        // symlink-based route into another installation's managed branding.
        $resolvedRelative = substr($target, strlen($root) + 1);
        $resolvedSegments = explode(DIRECTORY_SEPARATOR, $resolvedRelative);
        if (($resolvedSegments[0] ?? null) === 'tenants') {
            if (
                count($resolvedSegments) !== 4
                || ($resolvedSegments[2] ?? null) !== 'branding'
                || !Str::isUuid((string) ($resolvedSegments[1] ?? ''))
            ) {
                return null;
            }

            $scopeTenantId = $this->resolveInstallationTenantId($tenantId);
            if (!$scopeTenantId || ($resolvedSegments[1] ?? null) !== $scopeTenantId) {
                return null;
            }

            $managedRoot = $this->resolveManagedBrandingRoot($root, $scopeTenantId);
            if (!$managedRoot || !str_starts_with($target, $managedRoot . DIRECTORY_SEPARATOR)) {
                return null;
            }
        }

        return $target;
    }

    /**
     * Return a favicon URL only when the stored bytes, detected MIME, and
     * extension agree on a supported PNG or WebP image.
     *
     * @return array{url: string, mime: string}|null
     */
    public function faviconAttributes(?string $relativePath, ?string $tenantId = null): ?array
    {
        $absolutePath = $this->localPath($relativePath, $tenantId);
        if (!$absolutePath || !is_readable($absolutePath)) {
            return null;
        }

        $image = @getimagesize($absolutePath);
        if ($image === false) {
            return null;
        }

        $extension = strtolower(pathinfo((string) $relativePath, PATHINFO_EXTENSION));
        $expected = match ($extension) {
            'png' => ['type' => IMAGETYPE_PNG, 'mime' => 'image/png'],
            'webp' => ['type' => IMAGETYPE_WEBP, 'mime' => 'image/webp'],
            default => null,
        };

        if (!$expected || ($image[2] ?? null) !== $expected['type']) {
            return null;
        }

        $detectedMime = (new \finfo(FILEINFO_MIME_TYPE))->file($absolutePath);
        if ($detectedMime !== $expected['mime']) {
            return null;
        }

        return [
            'url' => asset('storage/' . ltrim((string) $relativePath, '/')),
            'mime' => $expected['mime'],
        ];
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
            || !Str::isUuid($tenantId)
            || $this->resolveInstallationTenantId($tenantId) !== $tenantId
        ) {
            return;
        }

        $segments = explode('/', $relativePath);
        if (
            count($segments) !== 4
            || ($segments[0] ?? null) !== 'tenants'
            || ($segments[1] ?? null) !== $tenantId
            || ($segments[2] ?? null) !== 'branding'
            || in_array('..', $segments, true)
            || in_array('.', $segments, true)
            || str_contains($relativePath, '\\')
            || str_contains($relativePath, "\0")
        ) {
            return;
        }

        $root = $this->existingPublicStorageRoot();
        if (!$root) {
            return;
        }

        $brandingRoot = $this->resolveManagedBrandingRoot($root, $tenantId);
        $entryPath = $root . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $segments);
        $parent = realpath(dirname($entryPath));

        if (
            !$brandingRoot
            || !$parent
            || ($parent !== $brandingRoot && !str_starts_with($parent, $brandingRoot . DIRECTORY_SEPARATOR))
        ) {
            return;
        }

        DB::transaction(function () use ($relativePath, $tenantId, $entryPath): void {
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

            // unlink() removes a symlink entry, never the file it targets.
            // This also safely removes dangling links after validating the parent.
            if (is_link($entryPath)) {
                @unlink($entryPath);
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
    private function resolveManagedBrandingRoot(string $storageRoot, string $tenantId): ?string
    {
        $tenantsDirectory = $storageRoot . DIRECTORY_SEPARATOR . 'tenants';
        $tenantDirectory = $tenantsDirectory . DIRECTORY_SEPARATOR . $tenantId;
        $brandingDirectory = $tenantDirectory . DIRECTORY_SEPARATOR . 'branding';

        // Never follow symlinked namespace directories. Only a final asset file
        // symlink may be resolved, and localPath still confines its target here.
        if (is_link($tenantsDirectory) || is_link($tenantDirectory) || is_link($brandingDirectory)) {
            return null;
        }

        $resolved = realpath($brandingDirectory);
        if (!$resolved || $resolved !== $brandingDirectory || !is_dir($resolved)) {
            return null;
        }

        return $resolved;
    }

    private function resolveInstallationTenantId(?string $requestedTenantId = null): ?string
    {
        // Read the currently selected authentication guard first. The Request
        // user resolver may be cached across test/request lifecycle switches.
        $user = Auth::user() ?? Auth::guard('sanctum')->user();
        $contextTenantId = app(TenantContext::class)->getTenantId();

        if ($user) {
            $authenticatedTenantId = (string) ($user->tenant_id ?? '');
            if (
                $authenticatedTenantId === ''
                || !$contextTenantId
                || (string) $contextTenantId !== $authenticatedTenantId
            ) {
                return null;
            }

            return !$requestedTenantId || $requestedTenantId === $authenticatedTenantId
                ? $authenticatedTenantId
                : null;
        }

        // Public/login rendering may resolve the sole installation identity,
        // but it cannot override a non-empty context from another request.
        if (!Schema::hasTable('local_installation_identities')) {
            return null;
        }

        $identities = LocalInstallationIdentity::withoutGlobalScopes()->limit(2)->get(['tenant_id']);
        if ($identities->count() !== 1) {
            return null;
        }

        $installationTenantId = (string) $identities->first()->tenant_id;
        if ($contextTenantId !== null && (string) $contextTenantId !== $installationTenantId) {
            return null;
        }

        return !$requestedTenantId || $requestedTenantId === $installationTenantId
            ? $installationTenantId
            : null;
    }

}
