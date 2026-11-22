<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\LocalInstallationIdentity;
use App\Models\User;
use App\Models\Role;
use App\Contexts\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private $tenant;
    private $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'Demo', 'code' => 'DEMO']);

        app(TenantContext::class)->setTenantId($this->tenant->id);

        $this->user = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test User',
            'username' => 'test-user',
            'email' => 'test@example.com',
            'password' => bcrypt('password')
        ]);

        // LoginIdentityResolver deliberately rejects users without an authorized
        // madrasah role. Give this fixture the minimum valid staff role.
        $adminRole = Role::firstOrCreate(['name' => 'ADMIN_MADRASAH'], ['label' => 'Admin Madrasah']);
        $this->user->roles()->attach($adminRole);

        LocalInstallationIdentity::create([
            'tenant_id' => $this->tenant->id,
            'installation_id' => (string) \Illuminate\Support\Str::uuid(),
            'key_id' => 'test-key-1',
            'public_key' => 'test-public-key',
            'encrypted_private_key' => 'test-encrypted-private-key',
            'key_fingerprint' => str_repeat('a', 64),
            'registered_domain' => 'cbt.test',
            'product_code' => 'CBT_WEB',
            'product_version' => '1.0.0',
        ]);

        app(TenantContext::class)->setTenantId(null);
    }

    public function test_login_successful()
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'identifier' => 'test-user',
            'password' => 'password',
            'device_name' => 'android'
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => ['token', 'user']
            ]);

        $this->assertNotEmpty($response->json('data.token'));
    }

    public function test_login_failed_wrong_password()
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'identifier' => 'test-user',
            'password' => 'wrong-password'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['identifier']);
    }

    public function test_api_login_is_throttled_after_five_invalid_attempts(): void
    {
        $throttleKey = 'login:api:'.hash('sha256', 'test-user|127.0.0.1');
        RateLimiter::clear($throttleKey);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/v1/auth/login', [
                'identifier' => 'test-user',
                'password' => 'wrong-password',
            ])->assertStatus(422)->assertJsonValidationErrors(['identifier']);
        }

        $this->postJson('/api/v1/auth/login', [
            'identifier' => 'test-user',
            'password' => 'password',
        ])->assertStatus(429)->assertJsonPath('success', false);

        RateLimiter::clear($throttleKey);
    }

    public function test_successful_api_login_clears_failed_attempt_bucket(): void
    {
        $throttleKey = 'login:api:'.hash('sha256', 'test-user|127.0.0.1');
        RateLimiter::clear($throttleKey);

        for ($attempt = 0; $attempt < 4; $attempt++) {
            $this->postJson('/api/v1/auth/login', [
                'identifier' => 'test-user',
                'password' => 'wrong-password',
            ])->assertStatus(422);
        }

        $this->postJson('/api/v1/auth/login', [
            'identifier' => 'test-user',
            'password' => 'password',
        ])->assertOk();

        $this->assertSame(0, RateLimiter::attempts($throttleKey));
    }

    public function test_me_endpoint_requires_auth()
    {
        $response = $this->getJson('/api/v1/auth/me');
        $response->assertStatus(401);
    }

    public function test_me_endpoint_returns_user_data()
    {
        $this->actingAs($this->user, 'sanctum');

        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJsonPath('data.username', 'test-user')
            ->assertJsonPath('data.tenant_id', $this->tenant->id);
    }

    public function test_logout_revokes_token()
    {
        $token = $this->user->createToken('test')->plainTextToken;
        $tokenId = explode('|', $token)[0];

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200);
        $this->assertNull(PersonalAccessToken::find($tokenId));
    }

    public function test_inactive_user_cannot_obtain_api_token(): void
    {
        $this->user->forceFill(['is_active' => false])->save();

        $this->postJson('/api/v1/auth/login', [
            'identifier' => 'test-user',
            'password' => 'password',
            'device_name' => 'android',
        ])->assertStatus(422)->assertJsonValidationErrors(['identifier']);

        $this->assertSame(0, $this->user->tokens()->count());
    }

    public function test_existing_api_token_is_revoked_when_account_becomes_inactive(): void
    {
        $plainTextToken = $this->user->createToken('before-deactivation')->plainTextToken;
        $tokenId = explode('|', $plainTextToken)[0];
        $this->user->forceFill(['is_active' => false])->save();

        $this->withHeaders(['Authorization' => 'Bearer '.$plainTextToken])
            ->getJson('/api/v1/auth/me')
            ->assertForbidden()
            ->assertJsonPath('success', false);

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $tokenId]);
    }

}
