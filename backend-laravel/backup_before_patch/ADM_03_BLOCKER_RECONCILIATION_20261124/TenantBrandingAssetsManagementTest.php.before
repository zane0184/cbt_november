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

        $otherTenantId = (string) Str::uuid();
        $otherPath = 'tenants/' . $otherTenantId . '/branding/real-logo.png';
        $aliasPath = 'tenants/' . $this->tenant->id . '/branding/aliased-logo.png';
        File::ensureDirectoryExists(dirname(storage_path('app/public/' . $otherPath)));
        File::ensureDirectoryExists(dirname(storage_path('app/public/' . $aliasPath)));
        File::copy(
            UploadedFile::fake()->image('real-logo.png', 120, 120)->getRealPath(),
            storage_path('app/public/' . $otherPath)
        );
        symlink(
            storage_path('app/public/' . $otherPath),
            storage_path('app/public/' . $aliasPath)
        );

        $this->assertNull($branding->localPath($aliasPath), 'A symlink into another tenant branding directory must be rejected.');
        File::deleteDirectory(storage_path('app/public/tenants/' . $otherTenantId));
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
        $appLogoPath = app(TenantBrandingService::class)->storeUpload(
            UploadedFile::fake()->image('tenant-a-logo.png', 120, 120),
            (string) $this->tenant->id,
            'app-logo'
        );
        $this->tenant->forceFill(['logo_path' => $appLogoPath])->save();

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
        // actingAs() swaps the guard user without dispatching the Authenticated
        // event used by the real request lifecycle to synchronize TenantContext.
        app(\App\Contexts\TenantContext::class)->setTenantId($otherTenant->id);

        $branding = app(TenantBrandingService::class);
        $this->assertNull($branding->localPath($appLogoPath));
        $this->assertNull($branding->publicUrl($appLogoPath));
        $this->assertNull(app(ReportHeaderService::class)->getLogoDataUri($this->tenant->fresh()));

        try {
            $branding->storeUpload(
                UploadedFile::fake()->image('cross-install.png', 120, 120),
                (string) $this->tenant->id,
                'app-logo'
            );
            $this->fail('A tenant B account must not write branding under tenant A.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('tidak sesuai', $exception->getMessage());
        }

        Livewire::test(TenantBrandingAssetsManagement::class)
            ->set('appLogoUpload', UploadedFile::fake()->image('other-logo.png', 120, 120))
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame($appLogoPath, $this->tenant->fresh()->logo_path);
        $this->assertFileExists(storage_path('app/public/' . $appLogoPath));
        $this->assertStringStartsWith('tenants/' . $otherTenant->id . '/branding/app-logo-', $otherTenant->fresh()->logo_path);
        $this->assertFileExists(storage_path('app/public/' . $otherTenant->fresh()->logo_path));
    }

    public function test_branding_migration_up_is_idempotent_and_repairs_legacy_partial_schema(): void
    {
        $migration = require database_path('migrations/2026_11_24_230001_add_tenant_branding_asset_paths.php');
        $documentPath = 'tenants/' . $this->tenant->id . '/branding/legacy-document.png';
        $faviconPath = 'tenants/' . $this->tenant->id . '/branding/legacy-favicon.png';
        $this->tenant->forceFill([
            'document_logo_path' => $documentPath,
            'favicon_path' => $faviconPath,
        ])->save();

        // Target schema: both columns already exist; migration must be a no-op.
        $migration->up();
        $this->assertTrue(Schema::hasColumn('tenants', 'document_logo_path'));
        $this->assertTrue(Schema::hasColumn('tenants', 'favicon_path'));
        $this->assertSame($documentPath, $this->tenant->fresh()->document_logo_path);
        $this->assertSame($faviconPath, $this->tenant->fresh()->favicon_path);

        // Legacy schema with only document_logo_path.
        Schema::table('tenants', function ($table): void {
            $table->dropColumn('favicon_path');
        });
        $migration->up();
        $this->assertTrue(Schema::hasColumn('tenants', 'document_logo_path'));
        $this->assertTrue(Schema::hasColumn('tenants', 'favicon_path'));
        $this->assertSame($documentPath, $this->tenant->fresh()->document_logo_path);

        // Legacy schema with only favicon_path.
        Schema::table('tenants', function ($table): void {
            $table->dropColumn('document_logo_path');
        });
        $migration->up();
        $this->assertTrue(Schema::hasColumn('tenants', 'document_logo_path'));
        $this->assertTrue(Schema::hasColumn('tenants', 'favicon_path'));
        $this->assertSame($faviconPath, $this->tenant->fresh()->favicon_path);

        // Empty pre-branding schema: neither column exists.
        Schema::table('tenants', function ($table): void {
            $table->dropColumn(['document_logo_path', 'favicon_path']);
        });
        $migration->up();
        $this->assertTrue(Schema::hasColumn('tenants', 'document_logo_path'));
        $this->assertTrue(Schema::hasColumn('tenants', 'favicon_path'));
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


    public function test_branding_resolution_is_isolated_between_two_disposable_installation_databases(): void
    {
        $databaseA = 'cbt_brand_a_' . strtolower(Str::random(10));
        $databaseB = 'cbt_brand_b_' . strtolower(Str::random(10));
        $tenantAId = (string) Str::uuid();
        $tenantBId = (string) Str::uuid();
        $pathA = 'tenants/' . $tenantAId . '/branding/app-logo-a.png';
        $pathB = 'tenants/' . $tenantBId . '/branding/app-logo-b.png';
        $mysqlConfig = config('database.connections.mysql');

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
            DB::connection('installation_control')->statement(
                'CREATE DATABASE ' . $databaseB . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
            );

            foreach (['installation_a', 'installation_b'] as $connection) {
                Schema::connection($connection)->create('local_installation_identities', function (Blueprint $table): void {
                    $table->id();
                    $table->uuid('tenant_id');
                    $table->timestamps();
                });
                Schema::connection($connection)->create('tenants', function (Blueprint $table): void {
                    $table->uuid('id')->primary();
                    $table->string('name');
                    $table->string('logo_path', 500)->nullable();
                    $table->string('document_logo_path', 500)->nullable();
                    $table->string('favicon_path', 500)->nullable();
                    $table->softDeletes();
                    $table->timestamps();
                });
            }

            DB::connection('installation_a')->table('local_installation_identities')->insert([
                'tenant_id' => $tenantAId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::connection('installation_b')->table('local_installation_identities')->insert([
                'tenant_id' => $tenantBId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::connection('installation_a')->table('tenants')->insert([
                'id' => $tenantAId,
                'name' => 'Instalasi A',
                'logo_path' => $pathA,
                'document_logo_path' => $pathA,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::connection('installation_b')->table('tenants')->insert([
                'id' => $tenantBId,
                'name' => 'Instalasi B',
                'logo_path' => $pathB,
                'document_logo_path' => $pathB,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ([[$pathA, 'logo-a.png'], [$pathB, 'logo-b.png']] as [$path, $filename]) {
                File::ensureDirectoryExists(dirname(storage_path('app/public/' . $path)));
                $image = UploadedFile::fake()->image($filename, 120, 120);
                File::copy($image->getRealPath(), storage_path('app/public/' . $path));
            }

            Auth::logout();
            Auth::forgetGuards();
            config(['database.default' => 'installation_a']);

            $branding = app(TenantBrandingService::class);
            $header = app(ReportHeaderService::class);
            $tenantA = $branding->currentTenant();

            $this->assertNotNull($tenantA);
            $this->assertSame($tenantAId, (string) $tenantA->id);
            $this->assertSame('Instalasi A', $tenantA->name);
            $this->assertNotNull($branding->localPath($pathA));
            $this->assertNull($branding->localPath($pathB));
            $this->assertNotNull($header->getLogoDataUri($tenantA));

            config(['database.default' => 'installation_b']);
            $tenantB = $branding->currentTenant();

            $this->assertNotNull($tenantB);
            $this->assertSame($tenantBId, (string) $tenantB->id);
            $this->assertSame('Instalasi B', $tenantB->name);
            $this->assertNotNull($branding->localPath($pathB));
            $this->assertNull($branding->localPath($pathA));
            $this->assertNull($header->getLogoDataUri($tenantA));
            $this->assertNotNull($header->getLogoDataUri($tenantB));
        } finally {
            config(['database.default' => 'mysql']);
            DB::purge('installation_a');
            DB::purge('installation_b');

            try {
                DB::connection('installation_control')->statement('DROP DATABASE IF EXISTS ' . $databaseA);
                DB::connection('installation_control')->statement('DROP DATABASE IF EXISTS ' . $databaseB);
            } finally {
                DB::purge('installation_control');
                File::deleteDirectory(storage_path('app/public/tenants/' . $tenantAId));
                File::deleteDirectory(storage_path('app/public/tenants/' . $tenantBId));
            }
        }
    }

}
