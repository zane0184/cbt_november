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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
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

        try {
            $migration->down();
            $this->fail('Branding migration rollback must not drop columns without ownership metadata.');
        } catch (\LogicException $exception) {
            $this->assertStringContainsString('forward-only', $exception->getMessage());
        }

        $this->assertTrue(Schema::hasColumn('tenants', 'document_logo_path'));
        $this->assertTrue(Schema::hasColumn('tenants', 'favicon_path'));
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

    public function test_branding_local_path_rejects_traversal_absolute_backslash_and_missing_paths(): void
    {
        $branding = app(TenantBrandingService::class);

        foreach ([
            '../outside.png',
            'tenants/' . $this->tenant->id . '/branding/../../outside.png',
            '/etc/passwd',
            'tenants\\' . $this->tenant->id . '\\branding\\logo.png',
            'tenants/' . $this->tenant->id . '/branding/missing.png',
        ] as $path) {
            $this->assertNull($branding->localPath($path), 'Unsafe path was accepted: ' . $path);
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
        } catch (\\RuntimeException $exception) {
            $this->assertStringContainsString('gambar raster', $exception->getMessage());
        }

        try {
            $branding->storeUpload(
                UploadedFile::fake()->create('oversized.png', 2049, 'image/png'),
                (string) $this->tenant->id,
                'app-logo'
            );
            $this->fail('An oversized logo must be rejected by the service boundary.');
        } catch (\\RuntimeException $exception) {
            $this->assertStringContainsString('Ukuran', $exception->getMessage());
        }
    }

    public function test_branding_service_rejects_mismatched_file_extension_and_image_type(): void
    {
        $file = UploadedFile::fake()->createWithContent('fake.jpg', base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p1sAAAAASUVORK5CYII=',
            true
        ));

        try {
            app(TenantBrandingService::class)->storeUpload(
                $file,
                (string) $this->tenant->id,
                'app-logo'
            );
            $this->fail('A PNG payload with a JPG extension must be rejected.');
        } catch (\\RuntimeException $exception) {
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
        $otherTenant = Tenant::create(['name' => 'Madrasah Other', 'code' => 'BRAND-B']);
        $role = Role::where('name', 'ADMIN_MADRASAH')->firstOrFail();
        $otherAdmin = User::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Admin Other',
            'email' => 'admin.other-branding@example.test',
            'password' => 'password-admin-other',
        ]);
        $otherAdmin->roles()->sync([$role->id]);

        $this->actingAs($otherAdmin);
        Livewire::test(TenantBrandingAssetsManagement::class)
            ->set('appLogoUpload', UploadedFile::fake()->image('other-logo.png', 120, 120))
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull($this->tenant->fresh()->logo_path);
        $this->assertStringStartsWith('tenants/' . $otherTenant->id . '/branding/app-logo-', $otherTenant->fresh()->logo_path);
    }

    public function test_branding_migration_up_is_idempotent_and_repairs_legacy_partial_schema(): void
    {
        $migration = require database_path('migrations/2026_11_24_230001_add_tenant_branding_asset_paths.php');

        $migration->up();
        $this->assertTrue(Schema::hasColumn('tenants', 'document_logo_path'));
        $this->assertTrue(Schema::hasColumn('tenants', 'favicon_path'));

        Schema::table('tenants', function ($table): void {
            $table->dropColumn('favicon_path');
        });
        $migration->up();

        $this->assertTrue(Schema::hasColumn('tenants', 'document_logo_path'));
        $this->assertTrue(Schema::hasColumn('tenants', 'favicon_path'));
    }

}
