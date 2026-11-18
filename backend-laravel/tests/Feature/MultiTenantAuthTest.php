<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Contexts\TenantContext;

class MultiTenantAuthTest extends TestCase
{
    use RefreshDatabase;

    private $tenantA;
    private $tenantB;
    private $userA;
    private $userB;
    private $studentRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->studentRole = Role::create(['name' => 'SISWA', 'label' => 'Siswa']);

        $this->tenantA = Tenant::create(['name' => 'Tenant A', 'code' => 'DEMOA']);
        $this->tenantB = Tenant::create(['name' => 'Tenant B', 'code' => 'DEMOB']);

        // User A in Tenant A
        app(TenantContext::class)->setTenantId($this->tenantA->id);
        $this->userA = User::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'User A',
            'email' => 'same@example.com',
            'password' => bcrypt('passwordA')
        ]);
        $this->userA->roles()->attach($this->studentRole->id);

        // User B in Tenant B
        app(TenantContext::class)->setTenantId($this->tenantB->id);
        $this->userB = User::create([
            'tenant_id' => $this->tenantB->id,
            'name' => 'User B',
            'email' => 'same@example.com',
            'password' => bcrypt('passwordB')
        ]);
        $this->userB->roles()->attach($this->studentRole->id);

        app(TenantContext::class)->setTenantId(null);
    }

    public function test_login_success_tenant_a()
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'tenant_code' => 'DEMOA',
            'email' => 'same@example.com',
            'password' => 'passwordA'
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.id', $this->userA->id)
            ->assertJsonPath('data.user.tenant_id', $this->tenantA->id);
    }

    public function test_login_success_tenant_b()
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'tenant_code' => 'DEMOB',
            'email' => 'same@example.com',
            'password' => 'passwordB'
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.id', $this->userB->id)
            ->assertJsonPath('data.user.tenant_id', $this->tenantB->id);
    }

    public function test_login_fail_wrong_tenant_password_a()
    {
        // Tenant A code with User B's password for the same email
        $response = $this->postJson('/api/v1/auth/login', [
            'tenant_code' => 'DEMOA',
            'email' => 'same@example.com',
            'password' => 'passwordB'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_fail_wrong_tenant_password_b()
    {
        // Tenant B code with User A's password for the same email
        $response = $this->postJson('/api/v1/auth/login', [
            'tenant_code' => 'DEMOB',
            'email' => 'same@example.com',
            'password' => 'passwordA'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_fail_invalid_tenant_code()
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'tenant_code' => 'INVALID',
            'email' => 'same@example.com',
            'password' => 'passwordA'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_fail_invalid_email()
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'tenant_code' => 'DEMOA',
            'email' => 'nonexistent@example.com',
            'password' => 'passwordA'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }
    public function test_legacy_platform_super_admin_cannot_receive_tenant_api_token()
    {
        app(TenantContext::class)->setTenantId($this->tenantA->id);
        $role = Role::create(['name' => 'SUPER_ADMIN', 'label' => 'Super Admin']);
        $legacy = User::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Legacy Platform Admin',
            'email' => 'legacy-super@example.com',
            'password' => bcrypt('legacy-password'),
        ]);
        $legacy->roles()->attach($role->id);
        app(TenantContext::class)->setTenantId(null);

        $response = $this->postJson('/api/v1/auth/login', [
            'tenant_code' => 'DEMOA',
            'email' => 'legacy-super@example.com',
            'password' => 'legacy-password',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['email']);
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $legacy->id]);
    }

}
