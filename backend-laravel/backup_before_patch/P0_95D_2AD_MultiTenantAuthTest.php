<?php

namespace Tests\Feature;

use App\Models\LocalInstallationIdentity;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Deployment model: one CBT Web installation and one database per madrasah.
 * Multiple tenant/install identity records in one database are invalid and must fail closed.
 */
class MultiTenantAuthTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenantA;
    private User $userA;
    private string $installationIdA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantA = Tenant::create(['name' => 'Madrasah A', 'code' => 'MAD-A']);
        $this->userA = User::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'User A',
            'username' => 'user-a',
            'email' => 'user-a@example.test',
            'password' => Hash::make('password-a'),
        ]);

        $this->installationIdA = (string) Str::uuid();
        $this->createInstallationIdentity($this->tenantA, $this->installationIdA, 'key-a', 'a.cbt.test');
    }

    public function test_login_resolves_identity_inside_one_madrasah_installation(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'identifier' => 'user-a',
            'password' => 'password-a',
            'device_name' => 'test-client',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.tenant_id', $this->tenantA->id)
            ->assertJsonPath('data.installation_id', $this->installationIdA);
    }

    public function test_login_fails_closed_if_multiple_installations_are_present_in_one_database(): void
    {
        $tenantB = Tenant::create(['name' => 'Madrasah B', 'code' => 'MAD-B']);
        $this->createInstallationIdentity($tenantB, (string) Str::uuid(), 'key-b', 'b.cbt.test');

        $response = $this->postJson('/api/v1/auth/login', [
            'identifier' => 'user-a',
            'password' => 'password-a',
            'device_name' => 'test-client',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['identifier']);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_super_admin_identity_is_rejected_by_tenant_login(): void
    {
        $role = Role::firstOrCreate(['name' => 'SUPER_ADMIN'], ['label' => 'Super Admin']);
        $superAdmin = User::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Publisher Operator',
            'username' => 'publisher-operator',
            'email' => 'publisher@example.test',
            'password' => Hash::make('publisher-password'),
        ]);
        $superAdmin->roles()->attach($role);

        $response = $this->postJson('/api/v1/auth/login', [
            'identifier' => 'publisher-operator',
            'password' => 'publisher-password',
            'device_name' => 'test-client',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['identifier']);
    }

    private function createInstallationIdentity(Tenant $tenant, string $installationId, string $keyId, string $domain): void
    {
        LocalInstallationIdentity::create([
            'tenant_id' => $tenant->id,
            'installation_id' => $installationId,
            'key_id' => $keyId,
            'public_key' => 'test-public-key-'.$keyId,
            'encrypted_private_key' => 'test-encrypted-private-key-'.$keyId,
            'key_fingerprint' => hash('sha256', $keyId),
            'registered_domain' => $domain,
            'product_code' => 'CBT_WEB',
            'product_version' => '1.0.0',
        ]);
    }
}
