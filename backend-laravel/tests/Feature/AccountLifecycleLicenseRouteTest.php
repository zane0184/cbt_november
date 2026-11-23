<?php

namespace Tests\Feature;

use App\Contexts\TenantContext;
use App\Models\LocalInstallationIdentity;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class AccountLifecycleLicenseRouteTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenantA;
    private Tenant $tenantB;
    private User $adminA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantA = Tenant::create(['name' => 'License Tenant A', 'code' => 'LTA01']);
        $this->tenantB = Tenant::create(['name' => 'License Tenant B', 'code' => 'LTB01']);

        // User creation is tenant-guarded; establish the local tenant before
        // creating its Admin identity in this fixture.
        app(TenantContext::class)->setTenantId((string) $this->tenantA->id);
        TenantContext::getInstance()?->setTenantId((string) $this->tenantA->id);

        $adminRole = Role::firstOrCreate(['name' => 'ADMIN_MADRASAH'], ['label' => 'Admin Madrasah']);

        $this->adminA = User::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'License Admin A',
            'username' => 'license.admin.a',
            'email' => 'license.admin.a@test.local',
            'password' => Hash::make('license-admin-password'),
        ]);
        $this->adminA->roles()->attach($adminRole);
    }

    private function createIdentity(Tenant $tenant, string $domain): LocalInstallationIdentity
    {
        return LocalInstallationIdentity::create([
            'tenant_id' => $tenant->id,
            'installation_id' => (string) Str::uuid(),
            'key_id' => 'license-key-'.Str::lower(Str::random(12)),
            'public_key' => 'test-public-key-'.$domain,
            'encrypted_private_key' => 'test-encrypted-private-key',
            'key_fingerprint' => hash('sha256', $domain),
            'registered_domain' => $domain,
            'product_code' => 'CBT-MI',
            'product_version' => '1.0.0',
        ]);
    }

    public function test_disabled_account_cannot_access_license_status_or_refresh(): void
    {
        $this->adminA->forceFill(['is_active' => false, 'auth_version' => 2])->save();
        $this->actingAs($this->adminA)->withSession(['account_auth_version' => 2]);

        $this->get('/license/status')->assertRedirect('/login');
        $this->post('/license/refresh')->assertRedirect('/login');

        $this->assertDatabaseMissing('local_license_events', ['event_type' => 'LICENSE_LEASE_REFRESHED']);
    }

    public function test_old_web_session_cannot_access_license_status_or_refresh(): void
    {
        $this->adminA->forceFill(['is_active' => true, 'auth_version' => 2])->save();
        $this->actingAs($this->adminA)->withSession(['account_auth_version' => 1]);

        $this->get('/license/status')->assertRedirect('/login');
        $this->post('/license/refresh')->assertRedirect('/login');

        $this->assertDatabaseMissing('local_license_events', ['event_type' => 'LICENSE_LEASE_REFRESHED']);
    }

    public function test_license_status_uses_authenticated_tenant_context_and_only_its_identity(): void
    {
        $identityA = $this->createIdentity($this->tenantA, 'tenant-a.example.test');
        $identityB = $this->createIdentity($this->tenantB, 'tenant-b.example.test');

        $this->actingAs($this->adminA)->withSession(['account_auth_version' => 1])
            ->get('/license/status')
            ->assertOk()
            ->assertViewHas('identity', fn ($identity) => $identity?->id === $identityA->id);

        $this->assertSame((string) $this->tenantA->id, app(TenantContext::class)->getTenantId());
        $this->assertNotSame($identityB->id, $identityA->id);
    }
}
