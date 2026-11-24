<?php

namespace Tests\Feature;

use App\Livewire\Admin\TenantBrandingAssetsManagement;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantBrandingService;
use App\Services\Reporting\ReportHeaderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class TenantBrandingAssetsManagementTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'Madrasah Branding', 'code' => 'BRAND-A']);
        app(\App\Contexts\TenantContext::class)->setTenantId($this->tenant->id);

        $role = Role::firstOrCreate(['name' => 'ADMIN_MADRASAH', 'label' => 'Admin Madrasah']);
        $permission = Permission::firstOrCreate(['name' => 'tenant.manage', 'label' => 'Kelola Data Madrasah']);
        $role->permissions()->syncWithoutDetaching([$permission->id]);

        $this->admin = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Admin Branding',
            'email' => 'admin.branding@example.test',
            'password' => 'password-admin-branding',
        ]);
        $this->admin->roles()->sync([$role->id]);
        $this->actingAs($this->admin);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(storage_path('app/public/tenants/' . $this->tenant->id));
        parent::tearDown();
    }

    public function test_admin_can_upload_tenant_isolated_logo_document_logo_and_square_favicon(): void
    {
        Livewire::test(TenantBrandingAssetsManagement::class)
            ->set('appLogoUpload', UploadedFile::fake()->image('logo.png', 600, 300))
            ->set('documentLogoUpload', UploadedFile::fake()->image('document.png', 700, 250))
            ->set('faviconUpload', UploadedFile::fake()->image('favicon.png', 64, 64))
            ->call('save')
            ->assertHasNoErrors();

        $tenant = $this->tenant->fresh();
        $this->assertStringStartsWith('tenants/' . $tenant->id . '/branding/app-logo-', $tenant->logo_path);
        $this->assertStringStartsWith('tenants/' . $tenant->id . '/branding/document-logo-', $tenant->document_logo_path);
        $this->assertStringStartsWith('tenants/' . $tenant->id . '/branding/favicon-', $tenant->favicon_path);

        foreach ([$tenant->logo_path, $tenant->document_logo_path, $tenant->favicon_path] as $path) {
            $this->assertFileExists(storage_path('app/public/' . $path));
        }

        $this->assertSame(asset('storage/' . $tenant->logo_path), app(TenantBrandingService::class)->publicUrl($tenant->logo_path));
        $this->assertDatabaseHas('audit_logs', ['action' => 'TENANT_BRANDING_ASSETS_UPDATED', 'entity_id' => $tenant->id]);
    }

    public function test_favicon_validation_rejects_non_square_image(): void
    {
        Livewire::test(TenantBrandingAssetsManagement::class)
            ->set('faviconUpload', UploadedFile::fake()->image('favicon-wide.png', 128, 64))
            ->call('save')
            ->assertHasErrors(['faviconUpload']);
    }

    public function test_favicon_png_and_webp_preview_use_content_mime_and_fallback_when_unavailable(): void
    {
        $branding = app(TenantBrandingService::class);
        $tenantId = (string) $this->tenant->id;

        $pngPath = $branding->storeUpload(
            UploadedFile::fake()->image('favicon.png', 64, 64),
            $tenantId,
            'favicon'
        );
        $pngAttributes = $branding->faviconAttributes($pngPath, $tenantId);
        $this->assertSame('image/png', $pngAttributes['mime'] ?? null);
        $this->assertSame(
            'image/png',
            (new \finfo(FILEINFO_MIME_TYPE))->file(storage_path('app/public/' . $pngPath))
        );
        $this->tenant->forceFill(['favicon_path' => $pngPath])->save();
        $this->get('/admin/branding-assets')
            ->assertOk()
            ->assertSee('type="image/png"', false);

        Livewire::test(TenantBrandingAssetsManagement::class)
            ->set('faviconUpload', UploadedFile::fake()->image('favicon-preview.png', 64, 64))
            ->assertSee('Pratinjau favicon');

        $this->assertTrue(function_exists('imagewebp'), 'GD WebP support is required by the branding CI environment.');
        $webpImage = imagecreatetruecolor(64, 64);
        $temporaryPath = tempnam(sys_get_temp_dir(), 'cbt-favicon-');
        $this->assertNotFalse($temporaryPath);
        $webpPath = $temporaryPath . '.webp';
        $this->assertTrue(rename($temporaryPath, $webpPath));

        try {
            $this->assertTrue(imagewebp($webpImage, $webpPath, 80));
            $webpUpload = new UploadedFile($webpPath, 'favicon.webp', 'image/webp', UPLOAD_ERR_OK, true);
            $storedWebpPath = $branding->storeUpload($webpUpload, $tenantId, 'favicon');
            $webpAttributes = $branding->faviconAttributes($storedWebpPath, $tenantId);
            $this->assertSame('image/webp', $webpAttributes['mime'] ?? null);
            $this->assertSame(
                'image/webp',
                (new \finfo(FILEINFO_MIME_TYPE))->file(storage_path('app/public/' . $storedWebpPath))
            );

            $this->tenant->forceFill(['favicon_path' => $storedWebpPath])->save();
            $this->get('/admin/branding-assets')
                ->assertOk()
                ->assertSee('type="image/webp"', false);

            // The selected-file temporary preview branch is exercised above;
            // this asserts that a stored WebP favicon remains previewable in the UI.
            Livewire::test(TenantBrandingAssetsManagement::class)
                ->assertSee($webpAttributes['url']);
        } finally {
            imagedestroy($webpImage);
            @unlink($temporaryPath);
            @unlink($webpPath);
        }

        $this->tenant->forceFill(['favicon_path' => null])->save();
        $this->get('/admin/branding-assets')
            ->assertOk()
            ->assertSee('favicon.svg');
        $this->assertFileExists(public_path('favicon.svg'));

        $corruptPath = 'tenants/' . $tenantId . '/branding/favicon-corrupt.png';
        File::ensureDirectoryExists(dirname(storage_path('app/public/' . $corruptPath)));
        File::put(storage_path('app/public/' . $corruptPath), 'not-an-image');
        $this->tenant->forceFill(['favicon_path' => $corruptPath])->save();
        $this->assertNull($branding->faviconAttributes($corruptPath, $tenantId));
        $this->get('/admin/branding-assets')
            ->assertOk()
            ->assertSee('favicon.svg');

        Auth::guard('web')->forgetUser();
        Auth::guard('sanctum')->forgetUser();
        Auth::shouldUse('web');
        app(\App\Contexts\TenantContext::class)->setTenantId(null);
        $this->get('/login')
            ->assertOk()
            ->assertSee('favicon.svg');
    }


    public function test_admin_can_remove_only_managed_asset_path(): void
    {
        $managedPath = 'tenants/' . $this->tenant->id . '/branding/app-logo-existing.png';
        $legacyPath = 'legacy/logo.png';
        File::ensureDirectoryExists(dirname(storage_path('app/public/' . $managedPath)));
        File::put(storage_path('app/public/' . $managedPath), 'fake-image-bytes');
        File::ensureDirectoryExists(dirname(storage_path('app/public/' . $legacyPath)));
        File::put(storage_path('app/public/' . $legacyPath), 'legacy-bytes');

        $this->tenant->forceFill(['logo_path' => $managedPath, 'document_logo_path' => $legacyPath])->save();

        Livewire::test(TenantBrandingAssetsManagement::class)->call('removeAppLogo')->assertHasNoErrors();

        $this->assertNull($this->tenant->fresh()->logo_path);
        $this->assertFileDoesNotExist(storage_path('app/public/' . $managedPath));
        $this->assertFileExists(storage_path('app/public/' . $legacyPath));
    }

    public function test_non_admin_cannot_manage_branding_assets(): void
    {
        $guruRole = Role::firstOrCreate(['name' => 'GURU', 'label' => 'Guru']);
        $guru = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Guru Branding',
            'email' => 'guru.branding@example.test',
            'password' => 'password-guru-branding',
        ]);
        $guru->roles()->sync([$guruRole->id]);

        $this->actingAs($guru);
        Livewire::test(TenantBrandingAssetsManagement::class)->assertForbidden();
    }

    public function test_replacing_app_logo_keeps_old_file_when_document_logo_still_references_it(): void
    {
        $oldPath = 'tenants/' . $this->tenant->id . '/branding/shared-logo.png';
        File::ensureDirectoryExists(dirname(storage_path('app/public/' . $oldPath)));
        File::put(storage_path('app/public/' . $oldPath), 'shared-logo-bytes');

        $this->tenant->forceFill([
            'logo_path' => $oldPath,
            'document_logo_path' => $oldPath,
        ])->save();

        Livewire::test(TenantBrandingAssetsManagement::class)
            ->set('appLogoUpload', UploadedFile::fake()->image('replacement.png', 300, 200))
            ->call('save')
            ->assertHasNoErrors();

        $fresh = $this->tenant->fresh();
        $this->assertNotSame($oldPath, $fresh->logo_path);
        $this->assertSame($oldPath, $fresh->document_logo_path);
        $this->assertFileExists(storage_path('app/public/' . $oldPath));
    }

    public function test_pdf_report_header_prefers_dedicated_document_logo(): void
    {
        $branding = app(TenantBrandingService::class);
        $appLogoPath = $branding->storeUpload(
            UploadedFile::fake()->image('app-logo.png', 120, 120),
            (string) $this->tenant->id,
            'app-logo'
        );
        $documentLogoPath = $branding->storeUpload(
            UploadedFile::fake()->image('document-logo.png', 180, 90),
            (string) $this->tenant->id,
            'document-logo'
        );

        $this->tenant->forceFill([
            'logo_path' => $appLogoPath,
            'document_logo_path' => $documentLogoPath,
        ])->save();

        $dataUri = app(ReportHeaderService::class)->getLogoDataUri($this->tenant->fresh());
        $this->assertIsString($dataUri);
        $this->assertStringStartsWith('data:image/png;base64,', $dataUri);
        $encoded = substr($dataUri, strlen('data:image/png;base64,'));
        $this->assertSame(
            File::get(storage_path('app/public/' . $documentLogoPath)),
            base64_decode($encoded, true)
        );
    }

    public function test_branding_migration_rollback_is_fail_closed_and_preserves_columns(): void
    {
        $migration = require database_path('migrations/2026_11_24_230001_add_tenant_branding_asset_paths.php');
        $documentPath = 'tenants/' . $this->tenant->id . '/branding/preexisting-document.png';
        $faviconPath = 'tenants/' . $this->tenant->id . '/branding/preexisting-favicon.png';
        $this->tenant->forceFill([
            'document_logo_path' => $documentPath,
            'favicon_path' => $faviconPath,
        ])->save();

        try {
            $migration->down();
            $this->fail('Branding migration rollback must not drop columns without ownership metadata.');
        } catch (\LogicException $exception) {
            $this->assertStringContainsString('forward-only', $exception->getMessage());
        }

        $fresh = $this->tenant->fresh();
        $this->assertTrue(Schema::hasColumn('tenants', 'document_logo_path'));
        $this->assertTrue(Schema::hasColumn('tenants', 'favicon_path'));
        $this->assertSame($documentPath, $fresh->document_logo_path);
        $this->assertSame($faviconPath, $fresh->favicon_path);
    }


    public function test_pdf_report_header_falls_back_to_valid_app_logo_when_document_logo_is_invalid(): void
    {
        $appLogoPath = app(TenantBrandingService::class)->storeUpload(
            UploadedFile::fake()->image('fallback-app.png', 120, 120),
            (string) $this->tenant->id,
            'app-logo'
        );

        $this->tenant->forceFill([
            'logo_path' => $appLogoPath,
            'document_logo_path' => '../outside/document.png',
        ])->save();

        $dataUri = app(ReportHeaderService::class)->getLogoDataUri($this->tenant->fresh());
        $this->assertIsString($dataUri);
        $this->assertStringStartsWith('data:image/png;base64,', $dataUri);
        $this->assertSame(
            File::get(storage_path('app/public/' . $appLogoPath)),
            base64_decode(substr($dataUri, strlen('data:image/png;base64,')), true)
        );
    }

    public function test_pdf_report_header_falls_back_to_app_logo_when_document_logo_is_unreadable(): void
    {
        $branding = app(TenantBrandingService::class);
        $tenantId = (string) $this->tenant->id;
        $appLogoPath = $branding->storeUpload(
            UploadedFile::fake()->image('unreadable-fallback-app.png', 120, 120),
            $tenantId,
            'app-logo'
        );
        $documentLogoPath = $branding->storeUpload(
            UploadedFile::fake()->image('unreadable-document.png', 180, 90),
            $tenantId,
            'document-logo'
        );

        $this->tenant->forceFill([
            'logo_path' => $appLogoPath,
            'document_logo_path' => $documentLogoPath,
        ])->save();

        $documentAbsolutePath = storage_path('app/public/' . $documentLogoPath);
        $originalPermissions = fileperms($documentAbsolutePath) & 0777;
        $this->assertTrue(chmod($documentAbsolutePath, 0000));

        try {
            if (is_readable($documentAbsolutePath)) {
                $this->markTestSkipped('The test runner can still read mode-0000 files; unreadable-file fallback cannot be simulated here.');
            }

            $dataUri = app(ReportHeaderService::class)->getLogoDataUri($this->tenant->fresh());
            $this->assertIsString($dataUri);
            $this->assertStringStartsWith('data:image/png;base64,', $dataUri);
            $this->assertSame(
                File::get(storage_path('app/public/' . $appLogoPath)),
                base64_decode(substr($dataUri, strlen('data:image/png;base64,')), true)
            );
        } finally {
            chmod($documentAbsolutePath, $originalPermissions);
        }
    }

    public function test_branding_local_path_rejects_traversal_absolute_backslash_and_missing_paths(): void
    {
        $branding = app(TenantBrandingService::class);
        $tenantId = (string) $this->tenant->id;
        $tenantBrandingDirectory = storage_path('app/public/tenants/' . $tenantId . '/branding');
        File::ensureDirectoryExists($tenantBrandingDirectory);

        foreach ([
            '../outside.png',
            'tenants/' . $tenantId . '/branding/../../outside.png',
            '/etc/passwd',
            'tenants\\' . $tenantId . '\\branding\\logo.png',
            'tenants/' . $tenantId . '/branding/missing.png',
            'tenants/' . $tenantId . '/branding/nested/file.png',
        ] as $path) {
            $this->assertNull($branding->localPath($path), 'Unsafe path was accepted: ' . $path);
        }

        $internalImage = UploadedFile::fake()->image('internal-target.png', 120, 120);
        $internalTarget = $tenantBrandingDirectory . '/internal-target.png';
        $this->assertTrue(File::copy($internalImage->getRealPath(), $internalTarget));
        $internalLink = $tenantBrandingDirectory . '/internal-link.png';
        $this->assertTrue(symlink($internalTarget, $internalLink));

        $internalLinkPath = 'tenants/' . $tenantId . '/branding/internal-link.png';
        $this->assertSame(realpath($internalTarget), $branding->localPath($internalLinkPath));
        $branding->deleteManaged($internalLinkPath, $tenantId);
        $this->assertFileDoesNotExist($internalLink);
        $this->assertFileExists($internalTarget);

        $otherTenantId = (string) Str::uuid();
        $otherTenantDirectory = storage_path('app/public/tenants/' . $otherTenantId . '/branding');
        File::ensureDirectoryExists($otherTenantDirectory);
        $crossTenantImage = UploadedFile::fake()->image('cross-tenant-target.png', 120, 120);
        $crossTenantTarget = $otherTenantDirectory . '/cross-tenant-target.png';
        $this->assertTrue(File::copy($crossTenantImage->getRealPath(), $crossTenantTarget));

        $outsideDirectory = storage_path('app/private/branding-outside-' . strtolower(Str::random(10)));
        File::ensureDirectoryExists($outsideDirectory);
        $outsideTarget = $outsideDirectory . '/outside-target.bin';
        File::put($outsideTarget, 'outside-installation-target');

        try {
            $crossTenantLink = $tenantBrandingDirectory . '/cross-tenant-link.png';
            $this->assertTrue(symlink($crossTenantTarget, $crossTenantLink));
            $crossTenantLinkPath = 'tenants/' . $tenantId . '/branding/cross-tenant-link.png';
            $this->assertNull($branding->localPath($crossTenantLinkPath));
            $branding->deleteManaged($crossTenantLinkPath, $tenantId);
            $this->assertFileDoesNotExist($crossTenantLink);
            $this->assertFileExists($crossTenantTarget);

            $outsideLink = $tenantBrandingDirectory . '/outside-link.png';
            $this->assertTrue(symlink($outsideTarget, $outsideLink));
            $outsideLinkPath = 'tenants/' . $tenantId . '/branding/outside-link.png';
            $this->assertNull($branding->localPath($outsideLinkPath));
            $branding->deleteManaged($outsideLinkPath, $tenantId);
            $this->assertFileDoesNotExist($outsideLink);
            $this->assertFileExists($outsideTarget);

            $brokenLink = $tenantBrandingDirectory . '/broken-link.png';
            $this->assertTrue(symlink($tenantBrandingDirectory . '/missing-target.png', $brokenLink));
            $brokenLinkPath = 'tenants/' . $tenantId . '/branding/broken-link.png';
            $this->assertNull($branding->localPath($brokenLinkPath));
            $branding->deleteManaged($brokenLinkPath, $tenantId);
            $this->assertFalse(is_link($brokenLink), 'A dangling symlink entry should be removable without touching its target.');

            // The tenant branding directory itself must never be a symlink,
            // even when its target is still somewhere under public storage.
            $brandingRoot = $tenantBrandingDirectory;
            $detachedBrandingRoot = dirname($brandingRoot) . '/branding-real-' . strtolower(Str::random(8));
            $redirectDirectory = storage_path('app/public/shared-branding-target-' . strtolower(Str::random(8)));
            File::ensureDirectoryExists($redirectDirectory);
            $redirectTarget = $redirectDirectory . '/root-link-target.png';
            File::put($redirectTarget, 'do-not-delete-through-directory-symlink');
            $this->assertTrue(rename($brandingRoot, $detachedBrandingRoot));

            try {
                $this->assertTrue(symlink($redirectDirectory, $brandingRoot));
                $rootSymlinkPath = 'tenants/' . $tenantId . '/branding/root-link-target.png';
                $this->assertNull($branding->localPath($rootSymlinkPath));
                $branding->deleteManaged($rootSymlinkPath, $tenantId);
                $this->assertFileExists($redirectTarget);

                // Upload must reject a symlinked branding directory before
                // writing through it into a different public-storage location.
                $redirectEntriesBefore = array_values(array_diff(scandir($redirectDirectory), ['.', '..']));
                try {
                    $branding->storeUpload(
                        UploadedFile::fake()->image('must-not-escape.png', 120, 120),
                        $tenantId,
                        'app-logo'
                    );
                    $this->fail('Upload must reject a symlinked branding directory.');
                } catch (\RuntimeException $exception) {
                    $this->assertStringContainsString('Direktori branding', $exception->getMessage());
                }
                $redirectEntriesAfter = array_values(array_diff(scandir($redirectDirectory), ['.', '..']));
                $this->assertSame($redirectEntriesBefore, $redirectEntriesAfter);

                // The public storage root itself must also be a real directory,
                // not a symlink to another installation or private location.
                $publicRoot = storage_path('app/public');
                $detachedPublicRoot = dirname($publicRoot) . '/public-real-' . strtolower(Str::random(8));
                $outsideEntriesBefore = array_values(array_diff(scandir($outsideDirectory), ['.', '..']));
                $this->assertTrue(rename($publicRoot, $detachedPublicRoot));

                try {
                    $this->assertTrue(symlink($outsideDirectory, $publicRoot));
                    try {
                        $branding->storeUpload(
                            UploadedFile::fake()->image('must-not-escape-root.png', 120, 120),
                            $tenantId,
                            'app-logo'
                        );
                        $this->fail('Upload must reject a symlinked public storage root.');
                    } catch (\RuntimeException $exception) {
                        $this->assertStringContainsString('storage', strtolower($exception->getMessage()));
                    }
                    $this->assertSame(
                        $outsideEntriesBefore,
                        array_values(array_diff(scandir($outsideDirectory), ['.', '..']))
                    );
                    $this->assertFileExists($outsideTarget);
                } finally {
                    if (is_link($publicRoot)) {
                        @unlink($publicRoot);
                    }
                    if (is_dir($detachedPublicRoot)) {
                        rename($detachedPublicRoot, $publicRoot);
                    }
                }
            } finally {
                if (is_link($brandingRoot)) {
                    @unlink($brandingRoot);
                }
                if (is_dir($detachedBrandingRoot)) {
                    rename($detachedBrandingRoot, $brandingRoot);
                }
                File::deleteDirectory($redirectDirectory);
            }
        } finally {
            File::deleteDirectory(storage_path('app/public/tenants/' . $otherTenantId));
            File::deleteDirectory($outsideDirectory);
        }
    }

    public function test_branding_service_rejects_invalid_image_content_and_oversized_uploads(): void
    {
        $branding = app(TenantBrandingService::class);

        try {
            $branding->storeUpload(
                UploadedFile::fake()->createWithContent('spoofed.png', 'not-an-image'),
                (string) $this->tenant->id,
                'app-logo'
            );
            $this->fail('A non-image payload must be rejected.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('gambar raster', $exception->getMessage());
        }

        try {
            $branding->storeUpload(
                UploadedFile::fake()->create('oversized.png', 2049, 'image/png'),
                (string) $this->tenant->id,
                'app-logo'
            );
            $this->fail('An oversized logo must be rejected by the service boundary.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('Ukuran', $exception->getMessage());
        }
    }

    public function test_branding_service_rejects_mismatched_file_extension_and_image_type(): void
    {
        $validPng = UploadedFile::fake()->image('valid-payload.png', 120, 120);
        $file = new UploadedFile($validPng->getRealPath(), 'fake.jpg', 'image/jpeg', null, true);

        try {
            app(TenantBrandingService::class)->storeUpload(
                $file,
                (string) $this->tenant->id,
                'app-logo'
            );
            $this->fail('A PNG payload with a JPG extension must be rejected.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('Ekstensi dan MIME', $exception->getMessage());
        }
    }

    public function test_admin_can_replace_and_remove_document_logo_and_favicon(): void
    {
        $branding = app(TenantBrandingService::class);
        $oldDocument = $branding->storeUpload(UploadedFile::fake()->image('old-document.png', 120, 80), (string) $this->tenant->id, 'document-logo');
        $oldFavicon = $branding->storeUpload(UploadedFile::fake()->image('old-favicon.png', 64, 64), (string) $this->tenant->id, 'favicon');
        $this->tenant->forceFill(['document_logo_path' => $oldDocument, 'favicon_path' => $oldFavicon])->save();

        Livewire::test(TenantBrandingAssetsManagement::class)
            ->set('documentLogoUpload', UploadedFile::fake()->image('new-document.png', 140, 90))
            ->set('faviconUpload', UploadedFile::fake()->image('new-favicon.png', 64, 64))
            ->call('save')
            ->assertHasNoErrors();

        $fresh = $this->tenant->fresh();
        $this->assertNotSame($oldDocument, $fresh->document_logo_path);
        $this->assertNotSame($oldFavicon, $fresh->favicon_path);
        $this->assertFileDoesNotExist(storage_path('app/public/' . $oldDocument));
        $this->assertFileDoesNotExist(storage_path('app/public/' . $oldFavicon));
        $this->assertFileExists(storage_path('app/public/' . $fresh->document_logo_path));
        $this->assertFileExists(storage_path('app/public/' . $fresh->favicon_path));

        Livewire::test(TenantBrandingAssetsManagement::class)->call('removeDocumentLogo')->assertHasNoErrors();
        Livewire::test(TenantBrandingAssetsManagement::class)->call('removeFavicon')->assertHasNoErrors();

        $fresh = $this->tenant->fresh();
        $this->assertNull($fresh->document_logo_path);
        $this->assertNull($fresh->favicon_path);
        $this->assertFileDoesNotExist(storage_path('app/public/' . $oldDocument));
        $this->assertFileDoesNotExist(storage_path('app/public/' . $oldFavicon));
    }

    public function test_branding_upload_is_scoped_to_the_authenticated_installation_tenant(): void
    {
        $branding = app(TenantBrandingService::class);
        $appLogoPath = $branding->storeUpload(
            UploadedFile::fake()->image('tenant-a-logo.png', 120, 120),
            (string) $this->tenant->id,
            'app-logo'
        );
        $this->tenant->forceFill(['logo_path' => $appLogoPath])->save();
        $this->actingAs($this->admin, 'web');
        Auth::shouldUse('web');
        Auth::guard('web')->setUser($this->admin);
        $this->setTenantContext((string) $this->tenant->id);
        $this->assertNotNull($branding->localPath($appLogoPath));

        $otherTenant = Tenant::create(['name' => 'Madrasah Other', 'code' => 'BRAND-B']);
        $role = Role::where('name', 'ADMIN_MADRASAH')->firstOrFail();

        // User creation derives tenant_id from the active installation context;
        // tenant_id is intentionally not mass assignable on User.
        $this->setTenantContext((string) $otherTenant->id);
        $otherAdmin = User::create([
            'name' => 'Admin Other',
            'email' => 'admin.other-branding@example.test',
            'password' => 'password-admin-other',
        ]);
        $this->assertSame((string) $otherTenant->id, (string) $otherAdmin->tenant_id);
        $otherAdmin->roles()->sync([$role->id]);

        // User B + Context B: direct service use (without a Livewire request)
        // may resolve B's own asset but must not resolve A's asset.
        $this->actingAs($otherAdmin, 'web');
        Auth::shouldUse('web');
        Auth::guard('web')->setUser($otherAdmin);
        $this->assertSame($otherAdmin->id, Auth::user()?->id, 'The active web guard must resolve User B after the account switch.');
        $this->setTenantContext((string) $otherTenant->id);
        $otherLogoPath = $branding->storeUpload(
            UploadedFile::fake()->image('tenant-b-logo.png', 120, 120),
            (string) $otherTenant->id,
            'app-logo'
        );
        $otherTenant->forceFill(['logo_path' => $otherLogoPath])->save();

        $this->assertNotNull($branding->localPath($otherLogoPath));
        $this->assertNull($branding->localPath($appLogoPath));
        $this->assertNull($branding->publicUrl($appLogoPath));
        $this->assertNull(app(ReportHeaderService::class)->getLogoDataUri($this->tenant->fresh()));

        // An installation B caller cannot delete an A asset even when the
        // caller supplies A's tenant UUID explicitly.
        $branding->deleteManaged($appLogoPath, (string) $this->tenant->id);
        $this->assertFileExists(storage_path('app/public/' . $appLogoPath));

        // User A + Context B is inconsistent and must fail closed.
        $this->actingAs($this->admin, 'web');
        Auth::shouldUse('web');
        Auth::guard('web')->setUser($this->admin);
        $this->assertSame($this->admin->id, Auth::user()?->id, 'The active web guard must resolve User A after switching back.');
        $this->setTenantContext((string) $otherTenant->id);
        $this->assertNull($branding->localPath($appLogoPath));
        $this->assertNull($branding->localPath($otherLogoPath));

        try {
            $branding->storeUpload(
                UploadedFile::fake()->image('cross-install.png', 120, 120),
                (string) $this->tenant->id,
                'app-logo'
            );
            $this->fail('User A with Context B must not upload into either installation.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('tidak sesuai', $exception->getMessage());
        }

        // User B + Context A is also inconsistent and must fail closed.
        $this->actingAs($otherAdmin, 'web');
        Auth::shouldUse('web');
        Auth::guard('web')->setUser($otherAdmin);
        $this->setTenantContext((string) $this->tenant->id);
        $this->assertNull($branding->localPath($appLogoPath));
        $this->assertNull($branding->localPath($otherLogoPath));

        // Missing context for an authenticated user is not inferred from a
        // path, UUID, cached Tenant model, or previous request.
        $this->setTenantContext(null);
        $this->assertNull($branding->localPath($otherLogoPath));

        // No authenticated user and no single local installation identity:
        // no managed branding path may be returned.
        Auth::shouldUse('web');
        Auth::guard('web')->forgetUser();
        Auth::guard('sanctum')->forgetUser();
        $this->setTenantContext(null);
        if (Schema::hasTable('local_installation_identities')) {
            DB::table('local_installation_identities')->delete();
        }
        $this->assertNull($branding->localPath($appLogoPath));
        $this->assertNull($branding->localPath($otherLogoPath));
    }

    public function test_branding_migration_up_is_idempotent_and_repairs_legacy_partial_schema(): void
    {
        if (
            !app()->environment('testing')
            || DB::connection()->getDriverName() !== 'mysql'
            || !str_starts_with((string) config('database.connections.mysql.database'), 'cbt_ci_')
        ) {
            $this->markTestSkipped('The migration matrix requires the isolated cbt_ci_* MySQL test database.');
        }

        $originalDefault = config('database.default');
        $mysqlConfig = config('database.connections.mysql');
        $database = 'cbt_branding_matrix_' . strtolower(Str::random(10));
        $controlConnection = 'branding_matrix_control';
        $fixtureConnection = 'branding_matrix_fixture';
        $createdDatabase = false;

        config(['database.connections.' . $controlConnection => $mysqlConfig]);
        DB::purge($controlConnection);

        try {
            DB::connection($controlConnection)->statement(
                'CREATE DATABASE ' . $database . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
            );
            $createdDatabase = true;

            config([
                'database.connections.' . $fixtureConnection => array_merge($mysqlConfig, ['database' => $database]),
                'database.default' => $fixtureConnection,
            ]);
            DB::purge($fixtureConnection);
            DB::setDefaultConnection($fixtureConnection);

            $fixtures = [
                ['name' => 'empty', 'document' => false, 'favicon' => false, 'document_index' => false],
                ['name' => 'document_only', 'document' => true, 'favicon' => false, 'document_index' => false],
                ['name' => 'favicon_only', 'document' => false, 'favicon' => true, 'document_index' => false],
                ['name' => 'target_schema', 'document' => true, 'favicon' => true, 'document_index' => false],
                ['name' => 'target_schema_with_index', 'document' => true, 'favicon' => true, 'document_index' => true],
            ];

            foreach ($fixtures as $fixture) {
                Schema::dropIfExists('tenants');
                Schema::create('tenants', function (Blueprint $table) use ($fixture): void {
                    $table->uuid('id')->primary();
                    $table->string('name', 120);
                    $table->string('logo_path', 500)->nullable();

                    if ($fixture['document']) {
                        $table->string('document_logo_path', 500)->nullable();
                    }
                    if ($fixture['favicon']) {
                        $table->string('favicon_path', 500)->nullable();
                    }

                    $table->index('name', 'tenants_name_idx');
                    if ($fixture['document_index']) {
                        $table->index('document_logo_path', 'tenants_document_logo_path_idx');
                    }
                });

                $tenantId = (string) Str::uuid();
                $row = [
                    'id' => $tenantId,
                    'name' => 'preserve-' . $fixture['name'],
                    'logo_path' => 'legacy/' . $fixture['name'] . '-logo.png',
                ];
                if ($fixture['document']) {
                    $row['document_logo_path'] = 'legacy/' . $fixture['name'] . '-document.png';
                }
                if ($fixture['favicon']) {
                    $row['favicon_path'] = 'legacy/' . $fixture['name'] . '-favicon.png';
                }
                DB::table('tenants')->insert($row);

                $tableStatus = DB::selectOne("SHOW TABLE STATUS WHERE Name = 'tenants'");
                $this->assertSame('InnoDB', $tableStatus->Engine, $fixture['name'] . ' fixture must use InnoDB');

                $migration = require database_path('migrations/2026_11_24_230001_add_tenant_branding_asset_paths.php');
                $migration->up();

                $this->assertTrue(Schema::hasColumn('tenants', 'document_logo_path'), $fixture['name']);
                $this->assertTrue(Schema::hasColumn('tenants', 'favicon_path'), $fixture['name']);

                $first = (array) DB::table('tenants')->where('id', $tenantId)->first();
                foreach ($row as $column => $value) {
                    $this->assertSame($value, $first[$column], $fixture['name'] . ' must preserve existing ' . $column);
                }

                // Re-running up against the target schema must be a no-op.
                $migration->up();
                $second = (array) DB::table('tenants')->where('id', $tenantId)->first();
                $this->assertSame($first, $second, $fixture['name'] . ' migration up must be idempotent');

                $indexNames = collect(DB::select('SHOW INDEX FROM tenants'))
                    ->pluck('Key_name')
                    ->unique()
                    ->all();
                $this->assertContains('tenants_name_idx', $indexNames);
                if ($fixture['document_index']) {
                    $this->assertContains('tenants_document_logo_path_idx', $indexNames);
                }

                try {
                    $migration->down();
                    $this->fail($fixture['name'] . ' rollback must fail closed.');
                } catch (\LogicException $exception) {
                    $this->assertStringContainsString('forward-only', $exception->getMessage());
                }

                $this->assertTrue(Schema::hasColumn('tenants', 'document_logo_path'));
                $this->assertTrue(Schema::hasColumn('tenants', 'favicon_path'));
                $afterRollbackAttempt = (array) DB::table('tenants')->where('id', $tenantId)->first();
                $this->assertSame($second, $afterRollbackAttempt, $fixture['name'] . ' rollback must preserve all data');
            }
        } finally {
            DB::setDefaultConnection($originalDefault);
            config(['database.default' => $originalDefault]);
            DB::purge($fixtureConnection);

            if ($createdDatabase) {
                DB::connection($controlConnection)->statement('DROP DATABASE IF EXISTS ' . $database);
            }
            DB::purge($controlConnection);
        }
    }

    public function test_pdf_header_falls_back_when_document_logo_is_missing_or_corrupt_and_returns_null_without_app_logo(): void
    {
        $branding = app(TenantBrandingService::class);
        $appLogoPath = $branding->storeUpload(
            UploadedFile::fake()->image('fallback-app.png', 120, 120),
            (string) $this->tenant->id,
            'app-logo'
        );
        $missingPath = 'tenants/' . $this->tenant->id . '/branding/document-missing.png';
        $this->tenant->forceFill(['logo_path' => $appLogoPath, 'document_logo_path' => $missingPath])->save();

        $dataUri = app(ReportHeaderService::class)->getLogoDataUri($this->tenant->fresh());
        $this->assertIsString($dataUri);
        $this->assertStringStartsWith('data:image/png;base64,', $dataUri);

        $corruptPath = 'tenants/' . $this->tenant->id . '/branding/document-corrupt.png';
        File::ensureDirectoryExists(dirname(storage_path('app/public/' . $corruptPath)));
        File::put(storage_path('app/public/' . $corruptPath), 'not-a-valid-image');
        $this->tenant->forceFill(['document_logo_path' => $corruptPath])->save();

        $dataUri = app(ReportHeaderService::class)->getLogoDataUri($this->tenant->fresh());
        $this->assertIsString($dataUri);
        $this->assertStringStartsWith('data:image/png;base64,', $dataUri);

        $this->tenant->forceFill(['logo_path' => null, 'document_logo_path' => $missingPath])->save();
        $this->assertNull(app(ReportHeaderService::class)->getLogoDataUri($this->tenant->fresh()));
    }

    public function test_admin_can_replace_and_remove_app_logo_through_branding_management(): void
    {
        $branding = app(TenantBrandingService::class);
        $oldPath = $branding->storeUpload(
            UploadedFile::fake()->image('old-app.png', 120, 120),
            (string) $this->tenant->id,
            'app-logo'
        );
        $this->tenant->forceFill(['logo_path' => $oldPath])->save();

        Livewire::test(TenantBrandingAssetsManagement::class)
            ->set('appLogoUpload', UploadedFile::fake()->image('new-app.png', 140, 140))
            ->call('save')
            ->assertHasNoErrors();

        $newPath = $this->tenant->fresh()->logo_path;
        $this->assertNotSame($oldPath, $newPath);
        $this->assertFileDoesNotExist(storage_path('app/public/' . $oldPath));
        $this->assertFileExists(storage_path('app/public/' . $newPath));

        Livewire::test(TenantBrandingAssetsManagement::class)->call('removeAppLogo')->assertHasNoErrors();
        $this->assertNull($this->tenant->fresh()->logo_path);
        $this->assertFileDoesNotExist(storage_path('app/public/' . $newPath));
    }


    public function test_branding_resolution_isolated_between_two_disposable_installations_with_separate_databases_and_storage_roots(): void
    {
        if (
            !app()->environment('testing')
            || DB::connection()->getDriverName() !== 'mysql'
            || !str_starts_with((string) config('database.connections.mysql.database'), 'cbt_ci_')
        ) {
            $this->markTestSkipped('Two-installation acceptance requires isolated cbt_ci_* MySQL databases.');
        }

        $originalDefault = DB::getDefaultConnection();
        $originalStoragePath = app()->storagePath();
        $databaseA = 'cbt_install_a_' . strtolower(Str::random(10));
        $databaseB = 'cbt_install_b_' . strtolower(Str::random(10));
        $storageA = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cbt-install-a-' . strtolower(Str::random(12));
        $storageB = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cbt-install-b-' . strtolower(Str::random(12));
        $tenantAId = (string) Str::uuid();
        $tenantBId = (string) Str::uuid();
        $installationAId = (string) Str::uuid();
        $installationBId = (string) Str::uuid();
        $mysqlConfig = config('database.connections.mysql');
        $createdA = false;
        $createdB = false;
        $branding = app(TenantBrandingService::class);
        $header = app(ReportHeaderService::class);

        config([
            'database.connections.installation_control' => $mysqlConfig,
            'database.connections.installation_a' => array_merge($mysqlConfig, ['database' => $databaseA]),
            'database.connections.installation_b' => array_merge($mysqlConfig, ['database' => $databaseB]),
        ]);
        DB::purge('installation_control');
        DB::purge('installation_a');
        DB::purge('installation_b');

        try {
            DB::connection('installation_control')->statement(
                'CREATE DATABASE ' . $databaseA . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
            );
            $createdA = true;
            DB::connection('installation_control')->statement(
                'CREATE DATABASE ' . $databaseB . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
            );
            $createdB = true;

            foreach (['installation_a', 'installation_b'] as $connection) {
                Schema::connection($connection)->create('local_installation_identities', function (Blueprint $table): void {
                    $table->id();
                    $table->uuid('tenant_id');
                    $table->uuid('installation_id')->unique();
                    $table->string('key_id', 80)->unique();
                    $table->text('public_key');
                    $table->longText('encrypted_private_key');
                    $table->char('key_fingerprint', 64);
                    $table->string('registered_domain', 190);
                    $table->string('product_code', 64);
                    $table->string('product_version', 40);
                    $table->timestamps();
                });
                Schema::connection($connection)->create('tenants', function (Blueprint $table): void {
                    $table->uuid('id')->primary();
                    $table->string('name');
                    $table->string('code')->unique();
                    $table->string('logo_path', 500)->nullable();
                    $table->string('document_logo_path', 500)->nullable();
                    $table->string('favicon_path', 500)->nullable();
                    $table->softDeletes();
                    $table->timestamps();
                });
            }

            DB::connection('installation_a')->table('local_installation_identities')->insert([
                'tenant_id' => $tenantAId,
                'installation_id' => $installationAId,
                'key_id' => 'test-key-a',
                'public_key' => 'public-key-a',
                'encrypted_private_key' => 'encrypted-private-key-a',
                'key_fingerprint' => str_repeat('a', 64),
                'registered_domain' => 'a.example.test',
                'product_code' => 'CBT-MADRASAH',
                'product_version' => 'test',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::connection('installation_b')->table('local_installation_identities')->insert([
                'tenant_id' => $tenantBId,
                'installation_id' => $installationBId,
                'key_id' => 'test-key-b',
                'public_key' => 'public-key-b',
                'encrypted_private_key' => 'encrypted-private-key-b',
                'key_fingerprint' => str_repeat('b', 64),
                'registered_domain' => 'b.example.test',
                'product_code' => 'CBT-MADRASAH',
                'product_version' => 'test',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::connection('installation_a')->table('tenants')->insert([
                'id' => $tenantAId,
                'name' => 'Instalasi A',
                'code' => 'INSTALL-A',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::connection('installation_b')->table('tenants')->insert([
                'id' => $tenantBId,
                'name' => 'Instalasi B',
                'code' => 'INSTALL-B',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Auth::shouldUse('web');
            Auth::guard('web')->forgetUser();
            Auth::guard('sanctum')->forgetUser();

            // Installation A: database A and storage A are selected together.
            config(['database.default' => 'installation_a']);
            DB::setDefaultConnection('installation_a');
            DB::purge('installation_a');
            app()->useStoragePath($storageA);
            $this->setTenantContext($tenantAId);
            $this->assertSame($databaseA, DB::connection()->getDatabaseName());
            $this->assertSame($storageA, app()->storagePath());
            $this->assertSame($installationAId, DB::table('local_installation_identities')->value('installation_id'));

            $tenantA = $branding->currentTenant();
            $this->assertNotNull($tenantA);
            $this->assertSame($tenantAId, (string) $tenantA->id);
            $this->assertSame('Instalasi A', $tenantA->name);

            $pathAOld = $branding->storeUpload(
                UploadedFile::fake()->image('installation-a-old.png', 120, 120),
                $tenantAId,
                'app-logo'
            );
            DB::table('tenants')->where('id', $tenantAId)->update(['logo_path' => $pathAOld]);
            $tenantA->refresh();
            $this->assertFileExists(storage_path('app/public/' . $pathAOld));
            $this->assertNotNull($branding->localPath($pathAOld));
            $this->assertIsString($header->getLogoDataUri($tenantA));

            $pathANew = $branding->storeUpload(
                UploadedFile::fake()->image('installation-a-new.png', 140, 140),
                $tenantAId,
                'app-logo'
            );
            DB::table('tenants')->where('id', $tenantAId)->update(['logo_path' => $pathANew]);
            $branding->deleteManaged($pathAOld, $tenantAId);
            $this->assertFileDoesNotExist(storage_path('app/public/' . $pathAOld));
            $this->assertFileExists(storage_path('app/public/' . $pathANew));

            // Installation B: both the database and storage root change.
            config(['database.default' => 'installation_b']);
            DB::setDefaultConnection('installation_b');
            DB::purge('installation_b');
            app()->useStoragePath($storageB);
            $this->setTenantContext($tenantBId);
            $this->assertSame($databaseB, DB::connection()->getDatabaseName());
            $this->assertSame($storageB, app()->storagePath());
            $this->assertSame($installationBId, DB::table('local_installation_identities')->value('installation_id'));
            $this->assertFalse(DB::table('tenants')->where('id', $tenantAId)->exists());

            $tenantB = $branding->currentTenant();
            $this->assertNotNull($tenantB);
            $this->assertSame($tenantBId, (string) $tenantB->id);
            $this->assertSame('Instalasi B', $tenantB->name);

            $pathBOld = $branding->storeUpload(
                UploadedFile::fake()->image('installation-b-old.png', 120, 120),
                $tenantBId,
                'app-logo'
            );
            DB::table('tenants')->where('id', $tenantBId)->update(['logo_path' => $pathBOld]);
            $tenantB->refresh();
            $this->assertFileExists(storage_path('app/public/' . $pathBOld));
            $this->assertNotNull($branding->localPath($pathBOld));
            $this->assertIsString($header->getLogoDataUri($tenantB));

            // B cannot read, replace, or delete A's asset, even if given A's UUID.
            $this->assertNull($branding->localPath($pathANew, $tenantAId));
            $this->assertNull($branding->publicUrl($pathANew, $tenantAId));
            $branding->deleteManaged($pathANew, $tenantAId);
            $this->assertFileExists($storageA . '/app/public/' . $pathANew);
            $this->assertNull($header->getLogoDataUri($tenantA));

            try {
                $branding->storeUpload(
                    UploadedFile::fake()->image('cross-installation.png', 120, 120),
                    $tenantAId,
                    'app-logo'
                );
                $this->fail('Installation B must not upload into installation A.');
            } catch (\RuntimeException $exception) {
                $this->assertStringContainsString('tidak sesuai', $exception->getMessage());
            }

            $pathBNew = $branding->storeUpload(
                UploadedFile::fake()->image('installation-b-new.png', 140, 140),
                $tenantBId,
                'app-logo'
            );
            DB::table('tenants')->where('id', $tenantBId)->update(['logo_path' => $pathBNew]);
            $branding->deleteManaged($pathBOld, $tenantBId);
            $this->assertFileDoesNotExist($storageB . '/app/public/' . $pathBOld);
            $this->assertFileExists($storageB . '/app/public/' . $pathBNew);

            // Switch back to A and prove the inverse boundary and independent DB.
            config(['database.default' => 'installation_a']);
            DB::setDefaultConnection('installation_a');
            DB::purge('installation_a');
            app()->useStoragePath($storageA);
            $this->setTenantContext($tenantAId);
            $this->assertSame($databaseA, DB::connection()->getDatabaseName());
            $this->assertFalse(DB::table('tenants')->where('id', $tenantBId)->exists());
            $this->assertNotNull($branding->localPath($pathANew, $tenantAId));
            $this->assertNull($branding->localPath($pathBNew, $tenantBId));
            $branding->deleteManaged($pathBNew, $tenantBId);
            $this->assertFileExists($storageB . '/app/public/' . $pathBNew);

            // Delete A's current asset and verify only A's storage is mutated.
            DB::table('tenants')->where('id', $tenantAId)->update(['logo_path' => null]);
            $branding->deleteManaged($pathANew, $tenantAId);
            $this->assertFileDoesNotExist($storageA . '/app/public/' . $pathANew);
            $this->assertFileExists($storageB . '/app/public/' . $pathBNew);
        } finally {
            DB::setDefaultConnection($originalDefault);
            config(['database.default' => $originalDefault]);
            DB::purge('installation_a');
            DB::purge('installation_b');
            DB::purge('installation_control');
            app()->useStoragePath($originalStoragePath);

            File::deleteDirectory($storageA);
            File::deleteDirectory($storageB);

            if ($createdA || $createdB) {
                $control = DB::connection('mysql');
                if ($createdA) {
                    $control->statement('DROP DATABASE IF EXISTS ' . $databaseA);
                }
                if ($createdB) {
                    $control->statement('DROP DATABASE IF EXISTS ' . $databaseB);
                }
            }
        }
    }

}
