<?php

namespace Tests\Feature;

use App\Contexts\TenantContext;
use App\Models\LocalInstallationIdentity;
use App\Models\Role;
use App\Models\StudentProfile;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class StudentWebLoginTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $student;
    private string $nisn = '0001234567';

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create(['name' => 'Madrasah Uji', 'code' => 'MADUJI']);
        app(TenantContext::class)->setTenantId($this->tenant->id);

        $studentRole = Role::firstOrCreate(['name' => 'SISWA'], ['label' => 'Siswa']);
        $this->student = User::create([
            'name' => 'Siswa Uji',
            'username' => 'siswa.uji',
            // Email sengaja tidak diisi: NISN adalah identifier login Siswa.
            'password' => bcrypt('SandiUji#2026'),
        ]);
        $this->student->roles()->attach($studentRole);

        StudentProfile::create([
            'user_id' => $this->student->id,
            'nisn' => $this->nisn,
            'full_name' => 'Siswa Uji',
        ]);

        LocalInstallationIdentity::create([
            'tenant_id' => $this->tenant->id,
            'installation_id' => (string) \Illuminate\Support\Str::uuid(),
            'key_id' => 'test-installation-key',
            'public_key' => 'test-public-key',
            'encrypted_private_key' => 'test-encrypted-private-key',
            'key_fingerprint' => str_repeat('a', 64),
            'registered_domain' => 'cbt.test',
            'product_code' => 'CBT_MADRASAH',
            'product_version' => 'test',
        ]);

        app(TenantContext::class)->setTenantId(null);
    }

    public function test_student_browser_cookie_is_invalidated_by_admin_deactivation_and_reactivation(): void
    {
        $studentApiToken = $this->student->createToken('before-account-deactivation')->plainTextToken;
        $studentBrowser = new \Tests\Support\IndependentBrowserClient();
        $adminBrowser = new \Tests\Support\IndependentBrowserClient();
        $sessionCookieName = (string) config('session.cookie');

        // Browser A: student login and a persistent, independent cookie jar.
        $studentLogin = $studentBrowser->request('POST', '/login', [
            'identifier' => $this->nisn,
            'password' => 'SandiUji#2026',
            'remember' => '1',
        ]);
        $this->assertSame(302, $studentLogin->getStatusCode());
        $this->assertStringEndsWith('/student/exams', (string) $studentLogin->headers->get('Location'));
        $studentSessionCookie = $studentBrowser->cookie($sessionCookieName);
        $this->assertNotNull($studentSessionCookie);
        $studentRememberCookieName = collect(array_keys($studentBrowser->cookies()))
            ->first(fn (string $name): bool => str_starts_with($name, 'remember_web_'));
        $this->assertNotNull($studentRememberCookieName);
        $studentRememberCookie = $studentBrowser->cookie($studentRememberCookieName);
        $this->assertNotNull($studentRememberCookie);

        // Create an Admin with only the account-management permission.
        app(TenantContext::class)->setTenantId($this->tenant->id);
        $adminRole = Role::firstOrCreate(['name' => 'ADMIN_MADRASAH'], ['label' => 'Admin Madrasah']);
        $permission = \App\Models\Permission::firstOrCreate(
            ['name' => 'user.manage'],
            ['label' => 'Kelola Pengguna']
        );
        $adminRole->permissions()->syncWithoutDetaching([$permission->id]);
        $admin = User::create([
            'name' => 'Admin Uji',
            'username' => 'admin.session.test',
            'password' => bcrypt('SandiAdmin#2026'),
        ]);
        $admin->roles()->attach($adminRole);
        app(TenantContext::class)->setTenantId(null);

        // Browser B: a separate Admin login and cookie jar.
        $adminLogin = $adminBrowser->request('POST', '/login', [
            'identifier' => 'admin.session.test',
            'password' => 'SandiAdmin#2026',
        ]);
        $this->assertSame(302, $adminLogin->getStatusCode());
        $this->assertStringEndsWith('/admin/dashboard', (string) $adminLogin->headers->get('Location'));
        $adminSessionCookie = $adminBrowser->cookie($sessionCookieName);
        $this->assertNotNull($adminSessionCookie);
        $this->assertNotSame($studentSessionCookie, $adminSessionCookie);
        $this->assertSame(200, $adminBrowser->request('GET', '/admin/dashboard')->getStatusCode());

        // Admin performs the official UserManagement action.
        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\UserManagement::class)
            ->call('deactivateUser', $this->student->id)
            ->assertHasNoErrors();
        $this->assertSoftDeleted('users', ['id' => $this->student->id]);

        // Browser A replays the original session cookie while the account is inactive.
        $studentBrowser->setCookie($sessionCookieName, $studentSessionCookie);
        $inactiveResponse = $studentBrowser->request('GET', '/student/exams');
        $this->assertSame(302, $inactiveResponse->getStatusCode());
        $this->assertStringEndsWith('/login', (string) $inactiveResponse->headers->get('Location'));

        // Browser B remains authenticated and restores the same account identity.
        $this->assertSame(200, $adminBrowser->request('GET', '/admin/dashboard')->getStatusCode());
        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\UserManagement::class)
            ->set('statusFilter', 'INACTIVE')
            ->call('restoreUser', $this->student->id)
            ->assertHasNoErrors();
        $this->assertDatabaseHas('users', ['id' => $this->student->id, 'deleted_at' => null]);

        // Replaying Browser A's exact pre-deactivation session after restore must fail.
        $studentBrowser->setCookie($sessionCookieName, $studentSessionCookie);
        $reactivatedResponse = $studentBrowser->request('GET', '/student/exams');
        $this->assertSame(302, $reactivatedResponse->getStatusCode());
        $this->assertStringEndsWith('/login', (string) $reactivatedResponse->headers->get('Location'));

        // A separate client with only the old remember-me cookie must also fail.
        $rememberBrowser = new \Tests\Support\IndependentBrowserClient();
        $rememberBrowser->setCookie($studentRememberCookieName, $studentRememberCookie);
        $rememberResponse = $rememberBrowser->request('GET', '/student/exams');
        $this->assertSame(302, $rememberResponse->getStatusCode());
        $this->assertStringEndsWith('/login', (string) $rememberResponse->headers->get('Location'));

        // A stateless client proves the old Sanctum bearer token is revoked after reactivation.
        $studentTokenId = explode('|', $studentApiToken, 2)[0];
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $studentTokenId]);
        $apiBrowser = new \Tests\Support\IndependentBrowserClient();
        $apiResponse = $apiBrowser->request('GET', '/api/v1/auth/me', [], [
            'Authorization' => 'Bearer ' . $studentApiToken,
            'Accept' => 'application/json',
        ]);
        $this->assertSame(401, $apiResponse->getStatusCode());
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $studentTokenId]);

        // A new browser login is required to obtain a fresh authenticated session.
        $freshBrowser = new \Tests\Support\IndependentBrowserClient();
        $freshLogin = $freshBrowser->request('POST', '/login', [
            'identifier' => $this->nisn,
            'password' => 'SandiUji#2026',
        ]);
        $this->assertSame(302, $freshLogin->getStatusCode());
        $this->assertStringEndsWith('/student/exams', (string) $freshLogin->headers->get('Location'));
        $this->assertSame(200, $freshBrowser->request('GET', '/student/exams')->getStatusCode());
    }

    public function test_valid_remember_me_cookie_can_establish_a_new_epoch_bound_session(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\EnsureLicenseActive::class);
        $login = $this->post('/login', [
            'identifier' => $this->nisn,
            'password' => 'SandiUji#2026',
            'remember' => '1',
        ]);
        $login->assertRedirect('/student/exams');

        $sessionCookieName = config('session.cookie');
        $rememberCookieName = collect($login->headers->getCookies())
            ->first(fn ($cookie) => str_starts_with($cookie->getName(), 'remember_web_'))?->getName();
        $this->assertNotNull($rememberCookieName);
        $rememberCookie = $login->getCookie($rememberCookieName);
        $this->assertNotNull($rememberCookie);

        // Simulate a new browser session after the session cookie expired while
        // retaining the valid remember-me recaller issued by the same account.
        $this->withCookie($sessionCookieName, '')
            ->withCookie($rememberCookie->getName(), $rememberCookie->getValue());
        $this->app['auth']->forgetGuards();

        $response = $this->get('/student/exams');
        $response->assertOk();
        $this->assertAuthenticatedAs($this->student);
        $this->assertNotNull($response->getCookie($sessionCookieName));
    }

    public function test_student_can_login_on_web_using_nisn_and_password_without_email(): void
    {
        $response = $this->post('/login', [
            'identifier' => $this->nisn,
            'password' => 'SandiUji#2026',
        ]);

        $response->assertRedirect('/student/exams');
        $this->assertAuthenticatedAs($this->student);
    }

    public function test_student_login_page_explains_nisn_identifier(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('Username / NIP / NIK / NISN');
        $response->assertSee('Siswa: NISN');
        $response->assertSee('name="identifier"', false);
    }

    public function test_student_cannot_login_using_username_instead_of_nisn(): void
    {
        $response = $this->post('/login', [
            'identifier' => 'siswa.uji',
            'password' => 'SandiUji#2026',
        ]);

        $response->assertSessionHasErrors('identifier');
        $this->assertGuest();
    }

    public function test_invalid_student_password_returns_generic_authentication_error(): void
    {
        $response = $this->post('/login', [
            'identifier' => $this->nisn,
            'password' => 'password-salah',
        ]);

        $response->assertSessionHasErrors('identifier');
        $this->assertGuest();
    }

    public function test_same_nisn_in_different_tenants_fails_closed_without_tenant_selection(): void
    {
        $otherTenant = Tenant::create(['name' => 'Madrasah Kedua', 'code' => 'MADUJI2']);
        app(TenantContext::class)->setTenantId($otherTenant->id);
        $studentRole = Role::where('name', 'SISWA')->firstOrFail();
        $second = User::create([
            'name' => 'Siswa Tenant Kedua',
            'username' => 'siswa.tenant.kedua',
            'password' => bcrypt('SandiUji#2026'),
        ]);
        $second->roles()->attach($studentRole);
        StudentProfile::create([
            'user_id' => $second->id,
            'nisn' => $this->nisn,
            'full_name' => 'Siswa Tenant Kedua',
        ]);
        app(TenantContext::class)->setTenantId(null);

        $response = $this->post('/login', [
            'identifier' => $this->nisn,
            'password' => 'SandiUji#2026',
        ]);

        $response->assertSessionHasErrors('identifier');
        $this->assertGuest();
        $this->assertDatabaseCount('student_profiles', 2);
    }

    public function test_student_role_combined_with_another_role_is_rejected(): void
    {
        $teacherRole = Role::firstOrCreate(['name' => 'GURU'], ['label' => 'Guru']);
        $this->student->roles()->attach($teacherRole);

        $response = $this->post('/login', [
            'identifier' => $this->nisn,
            'password' => 'SandiUji#2026',
        ]);

        $response->assertSessionHasErrors('identifier');
        $this->assertGuest();
    }

    public function test_student_login_fails_closed_without_local_installation_identity(): void
    {
        LocalInstallationIdentity::query()->delete();

        $response = $this->post('/login', [
            'identifier' => $this->nisn,
            'password' => 'SandiUji#2026',
        ]);

        $response->assertSessionHasErrors('identifier');
        $this->assertGuest();
    }

    public function test_student_login_fails_closed_if_installation_contains_multiple_tenants(): void
    {
        Tenant::create(['name' => 'Madrasah Lain', 'code' => 'MADLAIN']);

        $response = $this->post('/login', [
            'identifier' => $this->nisn,
            'password' => 'SandiUji#2026',
        ]);

        $response->assertSessionHasErrors('identifier');
        $this->assertGuest();
    }
    public function test_existing_student_web_session_is_rejected_while_account_is_deactivated(): void
    {
        $this->post('/login', [
            'identifier' => $this->nisn,
            'password' => 'SandiUji#2026',
        ])->assertRedirect('/student/exams');
        $this->assertAuthenticatedAs($this->student);

        $this->student->delete();
        $this->app['auth']->forgetGuards();

        $this->get('/student/exams')->assertRedirect('/login');
        $this->assertGuest();
        $this->assertSoftDeleted('users', ['id' => $this->student->id]);
    }

}
