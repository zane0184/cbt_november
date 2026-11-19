<?php

namespace Tests\Feature;

use App\Contexts\TenantContext;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\InstallerService;
use App\Services\InstallerStateStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class InstallerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        if (File::exists(storage_path('installed.lock'))) {
            File::delete(storage_path('installed.lock'));
        }
        app(InstallerStateStore::class)->resetForTesting();
        config(['app.installed' => false, 'installer.bootstrap_token' => str_repeat('T', 64)]);
        $this->withSession(['installer.bootstrap_authorized_at' => now()->timestamp]);
    }

    protected function tearDown(): void
    {
        if (File::exists(storage_path('installed.lock'))) {
            File::delete(storage_path('installed.lock'));
        }
        app(InstallerStateStore::class)->resetForTesting();
        config(['app.installed' => false]);
        parent::tearDown();
    }

    public function test_installer_routes_require_operator_bootstrap_authorization(): void
    {
        $this->flushSession();

        $response = $this->get('/install/requirements');
        $response->assertRedirect(route('installer.authorize.form'));

        $jsonResponse = $this->getJson('/install/requirements');
        $jsonResponse->assertStatus(403);
        $jsonResponse->assertJsonPath('success', false);
    }

    public function test_bootstrap_gate_blocks_admin_creation_post_without_operator_authorization(): void
    {
        $this->flushSession();

        $response = $this->post('/install/admin/save', [
            'madrasah_name' => 'MIN Tidak Sah',
            'madrasah_code' => 'MINTIDAKSAH',
            'admin_name' => 'Attempted Admin',
            'admin_email' => 'blocked@min.test',
            'admin_password' => 'SecurePassword123!',
            'admin_password_confirmation' => 'SecurePassword123!',
        ]);

        $response->assertRedirect(route('installer.authorize.form'));
        $this->assertDatabaseMissing('tenants', ['code' => 'MINTIDAKSAH']);
        $this->assertDatabaseMissing('users', ['email' => 'blocked@min.test']);
    }

    public function test_bootstrap_authorization_page_is_available_without_authorized_session_and_not_cacheable(): void
    {
        $this->flushSession();

        $response = $this->get('/install/authorize');
        $response->assertOk();
        $response->assertHeader('Cache-Control', 'no-store, private');
        $response->assertSee('Otorisasi Operator');
    }

    public function test_bootstrap_authorization_rejects_a_wrong_token_when_server_token_is_configured(): void
    {
        $this->flushSession();
        config(['installer.bootstrap_token' => str_repeat('C', 64)]);

        $response = $this->post('/install/authorize', ['bootstrap_token' => str_repeat('X', 64)]);
        $response->assertSessionHasErrors(['bootstrap_token']);
        $this->assertFalse(session()->has('installer.bootstrap_authorized_at'));
    }

    public function test_bootstrap_authorization_rejects_missing_or_short_server_token(): void
    {
        $this->flushSession();
        config(['installer.bootstrap_token' => 'too-short']);

        $response = $this->post('/install/authorize', ['bootstrap_token' => 'too-short']);
        $response->assertSessionHasErrors(['bootstrap_token']);
        $this->assertFalse(session()->has('installer.bootstrap_authorized_at'));
    }

    public function test_bootstrap_authorization_accepts_configured_token_and_opens_wizard(): void
    {
        $this->flushSession();
        $token = str_repeat('S', 64);
        config(['installer.bootstrap_token' => $token]);

        $response = $this->post('/install/authorize', ['bootstrap_token' => $token]);
        $response->assertRedirect(route('installer.requirements'));
        $response->assertSessionHas('installer.bootstrap_authorized_at');

        $this->get('/install/requirements')->assertOk();
    }

    public function test_bootstrap_authorization_expires_after_sixty_minutes(): void
    {
        $this->withSession(['installer.bootstrap_authorized_at' => now()->subMinutes(61)->timestamp]);

        $response = $this->get('/install/requirements');
        $response->assertRedirect(route('installer.authorize.form'));
    }

    public function test_installer_requirements_page_loads(): void
    {
        $response = $this->get('/install/requirements');
        $response->assertStatus(200);
        $response->assertSee('Persyaratan Sistem');
        $response->assertSee('Versi PHP Server');
    }

    public function test_invalid_database_credentials_return_sanitized_error(): void
    {
        $response = $this->postJson('/install/database/test', [
            'host' => '127.0.0.1',
            'port' => 3306,
            'database' => 'non_existent_cbt_db_9999',
            'username' => 'wrong_user',
            'password' => 'secret_db_password_123',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $this->assertStringNotContainsString('secret_db_password_123', $response->getContent());
    }

    public function test_migration_requires_a_verified_empty_database_state(): void
    {
        $result = app(InstallerService::class)->runMigrations();
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('persyaratan server', strtolower($result['message']));
    }

    public function test_database_inspection_fails_closed_when_target_cannot_be_verified(): void
    {
        $unsafe = app(InstallerService::class)->checkExistingTables([
            'host' => "invalid\nhost",
            'port' => 3306,
            'database' => 'cbt_test',
            'username' => 'cbt_test',
            'password' => '',
        ]);

        $this->assertTrue($unsafe);
    }

    public function test_admin_creation_requires_schema_ready_state(): void
    {
        $result = app(InstallerService::class)->createInitialDataAndAdmin([
            'madrasah_name' => 'MIN Test',
            'madrasah_code' => 'MINTEST',
            'admin_name' => 'Admin Test',
            'admin_username' => 'admin_test',
            'admin_email' => 'admin@min.test',
            'admin_password' => 'SecurePassword123!',
        ]);

        $this->assertFalse($result['success']);
        $this->assertDatabaseMissing('tenants', ['code' => 'MINTEST']);
    }

    public function test_admin_creation_assigns_only_admin_madrasah_role(): void
    {
        $mysql = config('database.connections.mysql');
        $fingerprint = hash('sha256', json_encode([
            'host' => trim((string) ($mysql['host'] ?? '127.0.0.1')),
            'port' => (int) ($mysql['port'] ?? 3306),
            'database' => trim((string) ($mysql['database'] ?? 'forge')),
            'username' => (string) ($mysql['username'] ?? 'forge'),
        ], JSON_THROW_ON_ERROR));

        app(InstallerStateStore::class)->synchronized(function () use ($fingerprint): void {
            app(InstallerStateStore::class)->write([
                'state' => 'SCHEMA_READY',
                'db_fingerprint' => $fingerprint,
                'migration_count' => 1,
            ]);
        });

        $result = app(InstallerService::class)->createInitialDataAndAdmin([
            'madrasah_name' => 'MIN Test',
            'madrasah_code' => 'MINTEST',
            'admin_name' => 'Admin Test',
            'admin_username' => 'admin_test',
            'admin_email' => 'admin@min.test',
            'admin_password' => 'SecurePassword123!',
        ]);

        $this->assertTrue($result['success'], $result['message']);
        $admin = User::withoutGlobalScopes()->with('roles.permissions')->findOrFail($result['admin_id']);
        $this->assertTrue($admin->hasRole('ADMIN_MADRASAH'));
        $this->assertFalse($admin->hasRole('SUPER_ADMIN'));
        $this->assertSame(['ADMIN_MADRASAH'], $admin->roles->pluck('name')->sort()->values()->all());
        $this->assertTrue($admin->hasPermission('curriculum.view'));
        $this->assertTrue($admin->hasPermission('curriculum.manage'));
    }

    public function test_admin_creation_recovers_a_committed_pair_when_state_checkpoint_was_lost(): void
    {
        $tenant = Tenant::create(['name' => 'MIN Recovery', 'code' => 'MINRECOVERY']);
        $role = Role::create(['name' => 'ADMIN_MADRASAH', 'label' => 'Admin Madrasah']);
        app(TenantContext::class)->setTenantId($tenant->id);
        $admin = User::forceCreate([
            'tenant_id' => $tenant->id,
            'name' => 'Admin Recovery',
            'email' => 'recovery@min.test',
            'password' => bcrypt('RecoverySecurePassword123!'),
        ]);
        $admin->roles()->sync([$role->id]);
        app(TenantContext::class)->setTenantId(null);

        $mysql = config('database.connections.mysql');
        $fingerprint = hash('sha256', json_encode([
            'host' => trim((string) ($mysql['host'] ?? '127.0.0.1')),
            'port' => (int) ($mysql['port'] ?? 3306),
            'database' => trim((string) ($mysql['database'] ?? 'forge')),
            'username' => (string) ($mysql['username'] ?? 'forge'),
        ], JSON_THROW_ON_ERROR));

        app(InstallerStateStore::class)->synchronized(function () use ($fingerprint): void {
            app(InstallerStateStore::class)->write([
                'state' => 'SCHEMA_READY',
                'db_fingerprint' => $fingerprint,
                'migration_count' => 1,
            ]);
        });

        $result = app(InstallerService::class)->createInitialDataAndAdmin([
            'madrasah_name' => 'MIN Recovery',
            'madrasah_code' => 'MINRECOVERY',
            'admin_name' => 'Admin Recovery',
            'admin_username' => 'recovery_admin',
            'admin_email' => 'recovery@min.test',
            'admin_password' => 'RecoverySecurePassword123!',
        ]);

        $this->assertTrue($result['success'], $result['message']);
        $this->assertTrue($result['recovered']);
        $this->assertSame('ADMIN_CREATED', app(InstallerService::class)->currentState());
        $this->assertSame($tenant->id, $result['tenant_id']);
        $this->assertSame($admin->id, $result['admin_id']);
        $this->assertSame(1, Tenant::withoutGlobalScopes()->where('code', 'MINRECOVERY')->count());
        $this->assertSame(1, User::withoutGlobalScopes()->where('email', 'recovery@min.test')->count());
    }

    public function test_legacy_tenant_created_checkpoint_reconciles_exact_committed_admin_pair(): void
    {
        $tenant = Tenant::create(['name' => 'MIN Legacy Pair', 'code' => 'MINLEGACYPAIR']);
        $role = Role::create(['name' => 'ADMIN_MADRASAH', 'label' => 'Admin Madrasah']);
        app(TenantContext::class)->setTenantId($tenant->id);
        $admin = User::forceCreate([
            'tenant_id' => $tenant->id,
            'name' => 'Legacy Admin',
            'email' => 'legacy-admin@min.test',
            'password' => bcrypt('LegacySecurePassword123!'),
        ]);
        $admin->roles()->sync([$role->id]);
        app(TenantContext::class)->setTenantId(null);

        $mysql = config('database.connections.mysql');
        $fingerprint = hash('sha256', json_encode([
            'host' => trim((string) ($mysql['host'] ?? '127.0.0.1')),
            'port' => (int) ($mysql['port'] ?? 3306),
            'database' => trim((string) ($mysql['database'] ?? 'forge')),
            'username' => (string) ($mysql['username'] ?? 'forge'),
        ], JSON_THROW_ON_ERROR));
        app(InstallerStateStore::class)->synchronized(function () use ($fingerprint, $tenant): void {
            app(InstallerStateStore::class)->write([
                'state' => 'TENANT_CREATED',
                'db_fingerprint' => $fingerprint,
                'tenant_id' => $tenant->id,
                'tenant_code' => $tenant->code,
            ]);
        });

        $result = app(InstallerService::class)->createInitialDataAndAdmin([
            'madrasah_name' => 'MIN Legacy Pair',
            'madrasah_code' => 'MINLEGACYPAIR',
            'admin_name' => 'Legacy Admin',
            'admin_username' => 'legacy_admin',
            'admin_email' => 'legacy-admin@min.test',
            'admin_password' => 'LegacySecurePassword123!',
        ]);

        $this->assertTrue($result['success'], $result['message']);
        $this->assertTrue($result['recovered']);
        $this->assertSame('ADMIN_CREATED', app(InstallerService::class)->currentState());
        $this->assertSame($admin->id, $result['admin_id']);
    }

    public function test_admin_recovery_fails_closed_for_a_partial_tenant_admin_pair(): void
    {
        $tenant = Tenant::create(['name' => 'MIN Partial', 'code' => 'MINPARTIAL']);
        $mysql = config('database.connections.mysql');
        $fingerprint = hash('sha256', json_encode([
            'host' => trim((string) ($mysql['host'] ?? '127.0.0.1')),
            'port' => (int) ($mysql['port'] ?? 3306),
            'database' => trim((string) ($mysql['database'] ?? 'forge')),
            'username' => (string) ($mysql['username'] ?? 'forge'),
        ], JSON_THROW_ON_ERROR));
        app(InstallerStateStore::class)->synchronized(function () use ($fingerprint): void {
            app(InstallerStateStore::class)->write([
                'state' => 'SCHEMA_READY',
                'db_fingerprint' => $fingerprint,
                'migration_count' => 1,
            ]);
        });

        $result = app(InstallerService::class)->createInitialDataAndAdmin([
            'madrasah_name' => 'MIN Partial',
            'madrasah_code' => 'MINPARTIAL',
            'admin_name' => 'Attempted Admin',
            'admin_username' => 'partial_admin',
            'admin_email' => 'partial@min.test',
            'admin_password' => 'PartialSecurePassword123!',
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('MIN Partial', $tenant->fresh()->name);
        $this->assertDatabaseMissing('users', ['email' => 'partial@min.test']);
        $this->assertSame('SCHEMA_READY', app(InstallerService::class)->currentState());
    }

    public function test_legacy_tenant_created_checkpoint_can_resume_admin_step(): void
    {
        $tenant = Tenant::create(['name' => 'MIN Legacy Recovery', 'code' => 'MINLEGACY']);
        app(InstallerStateStore::class)->synchronized(function () use ($tenant): void {
            app(InstallerStateStore::class)->write([
                'state' => 'TENANT_CREATED',
                'tenant_id' => $tenant->id,
                'tenant_code' => $tenant->code,
            ]);
        });

        $response = $this->get('/install/migrations');
        $response->assertRedirect(route('installer.admin'));

        $adminResponse = $this->get('/install/admin');
        $adminResponse->assertOk();
        $adminResponse->assertSee('Admin');
    }

    public function test_installer_does_not_take_over_existing_admin_email(): void
    {
        $tenant = Tenant::create(['name' => 'Existing Tenant', 'code' => 'EXISTING']);
        app(TenantContext::class)->setTenantId($tenant->id);
        $existing = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Existing Owner',
            'email' => 'owner@existing.test',
            'password' => bcrypt('OldSecurePassword123!'),
        ]);
        app(TenantContext::class)->setTenantId(null);

        $mysql = config('database.connections.mysql');
        $fingerprint = hash('sha256', json_encode([
            'host' => trim((string) ($mysql['host'] ?? '127.0.0.1')),
            'port' => (int) ($mysql['port'] ?? 3306),
            'database' => trim((string) ($mysql['database'] ?? 'forge')),
            'username' => (string) ($mysql['username'] ?? 'forge'),
        ], JSON_THROW_ON_ERROR));
        app(InstallerStateStore::class)->synchronized(function () use ($fingerprint): void {
            app(InstallerStateStore::class)->write(['state' => 'SCHEMA_READY', 'db_fingerprint' => $fingerprint]);
        });

        $result = app(InstallerService::class)->createInitialDataAndAdmin([
            'madrasah_name' => 'Another MIN',
            'madrasah_code' => 'ANOTHERMIN',
            'admin_name' => 'Attempted Takeover',
            'admin_username' => 'takeover_admin',
            'admin_email' => 'owner@existing.test',
            'admin_password' => 'NewSecurePassword123!',
        ]);

        $this->assertFalse($result['success']);
        $this->assertDatabaseMissing('tenants', ['code' => 'ANOTHERMIN']);
        $this->assertSame('Existing Owner', $existing->fresh()->name);
        $this->assertTrue(password_verify('OldSecurePassword123!', $existing->fresh()->password));
    }

    public function test_get_complete_page_never_locks_installer(): void
    {
        $response = $this->get('/install/complete');
        $response->assertRedirect(route('installer.license'));
        $this->assertFalse(File::exists(storage_path('installed.lock')));
    }

    public function test_complete_page_requires_explicit_post_finalization(): void
    {
        $this->mock(InstallerService::class, function ($mock): void {
            $mock->shouldReceive('isInstalled')->andReturn(false);
            $mock->shouldReceive('currentState')->andReturn('ADMIN_CREATED');
            $mock->shouldReceive('completionStatus')->once()->andReturn([
                'state' => 'ADMIN_CREATED',
                'can_finalize' => true,
                'message' => 'Ready for final verification.',
            ]);
            $mock->shouldNotReceive('lockInstaller');
        });

        $response = $this->get('/install/complete');
        $response->assertStatus(200);
        $response->assertSee(route('installer.finalize'));
        $this->assertFalse(File::exists(storage_path('installed.lock')));
    }

    public function test_admin_creation_validation_failure_prevents_installer_locking(): void
    {
        $response = $this->post('/install/admin/save', [
            'madrasah_name' => 'MTs Test',
            'madrasah_code' => 'MTSTEST',
            'admin_name' => 'Admin Name',
            'admin_email' => 'admin@test.com',
            'admin_password' => 'SecurePassword123!',
            'admin_password_confirmation' => 'MismatchPassword!',
        ]);

        $response->assertSessionHasErrors(['admin_password']);
        $this->assertFalse(File::exists(storage_path('installed.lock')));
    }

    public function test_reaccess_protection_blocks_all_installer_routes_after_locking(): void
    {
        File::put(storage_path('installed.lock'), json_encode(['installed_at' => now()->toIso8601String()]));
        config(['app.installed' => true]);

        $routes = [
            ['GET', '/install'], ['GET', '/install/requirements'], ['GET', '/install/database'],
            ['POST', '/install/database/test'], ['POST', '/install/database/save'],
            ['GET', '/install/migrations'], ['POST', '/install/migrations/run'],
            ['GET', '/install/admin'], ['POST', '/install/admin/save'],
            ['GET', '/install/complete'], ['POST', '/install/finalize'],
        ];

        foreach ($routes as [$method, $uri]) {
            $response = $method === 'GET' ? $this->get($uri) : $this->post($uri, []);
            $response->assertRedirect('/login');

            $jsonResponse = $method === 'GET' ? $this->getJson($uri) : $this->postJson($uri, []);
            $jsonResponse->assertStatus(403);
            $jsonResponse->assertJsonPath('success', false);
        }
    }
}
