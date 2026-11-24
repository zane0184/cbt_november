<?php

namespace Tests\Feature;

use App\Livewire\Admin\TenantBrandingAssetsManagement;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantBrandingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
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
}
