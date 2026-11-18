<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Contexts\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
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
            'email' => 'test@example.com',
            'password' => bcrypt('password')
        ]);

        app(TenantContext::class)->setTenantId(null);
    }

    public function test_login_successful()
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'tenant_code' => 'DEMO',
            'email' => 'test@example.com',
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
            'tenant_code' => 'DEMO',
            'email' => 'test@example.com',
            'password' => 'wrong-password'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_me_endpoint_requires_auth()
    {
        $response = $this->getJson('/api/v1/auth/me');
        $response->assertStatus(401);
    }

    public function test_me_endpoint_returns_user_data()
    {
        $this->actingAs($this->user);

        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJsonPath('data.email', 'test@example.com')
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
}
