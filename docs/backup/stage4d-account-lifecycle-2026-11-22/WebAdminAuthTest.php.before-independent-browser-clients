<?php

namespace Tests\Feature;

use App\Contexts\TenantContext;
use App\Http\Middleware\EnsureLicenseActive;
use App\Models\LocalInstallationIdentity;
use App\Models\Role;
use App\Models\Permission;
use App\Models\StudentProfile;
use App\Models\TeacherProfile;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Tests\TestCase;

class WebAdminAuthTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $superAdmin;
    private User $admin;
    private User $operator;
    private User $kamad;
    private User $proktor;
    private User $guru;
    private User $siswa;
    private string $password = 'SandiUji#2026';
    private string $nisn = '0001234567';

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create(['name' => 'Madrasah Uji', 'code' => 'MADUJI']);
        app(TenantContext::class)->setTenantId($this->tenant->id);

        $roles = [];
        foreach ([
            'SUPER_ADMIN' => 'Super Admin',
            'ADMIN_MADRASAH' => 'Admin Madrasah',
            'OPERATOR' => 'Operator',
            'KAMAD' => 'Kepala Madrasah',
            'PROKTOR' => 'Proktor',
            'GURU' => 'Guru',
            'SISWA' => 'Siswa',
        ] as $name => $label) {
            $roles[$name] = Role::firstOrCreate(['name' => $name], ['label' => $label]);
        }

        $this->superAdmin = $this->makeUser('super.admin', 'Super Admin', $roles['SUPER_ADMIN']);
        $this->admin = $this->makeUser('admin.uji', 'Admin Madrasah', $roles['ADMIN_MADRASAH']);
        $this->operator = $this->makeUser('operator.uji', 'Operator', $roles['OPERATOR']);
        $this->kamad = $this->makeUser('kamad.uji', 'Kepala Madrasah', $roles['KAMAD']);
        $this->proktor = $this->makeUser('proktor.uji', 'Proktor', $roles['PROKTOR']);
        $this->guru = $this->makeUser('guru.uji', 'Guru Uji', $roles['GURU']);
        $this->siswa = $this->makeUser('siswa.uji', 'Siswa Uji', $roles['SISWA']);

        TeacherProfile::create([
            'user_id' => $this->guru->id,
            'nip' => '198001012006041001',
            'nik' => '3171000000000001',
            'full_name' => 'Guru Uji',
        ]);
        StudentProfile::create([
            'user_id' => $this->siswa->id,
            'nisn' => $this->nisn,
            'full_name' => 'Siswa Uji',
        ]);

        LocalInstallationIdentity::create([
            'tenant_id' => $this->tenant->id,
            'installation_id' => (string) Str::uuid(),
            'key_id' => 'test-installation-key',
            'public_key' => 'test-public-key',
            'encrypted_private_key' => 'test-encrypted-private-key',
            'key_fingerprint' => str_repeat('b', 64),
            'registered_domain' => 'cbt.test',
            'product_code' => 'CBT_MADRASAH',
            'product_version' => 'test',
        ]);

        app(TenantContext::class)->setTenantId(null);
    }

    private function makeUser(string $username, string $name, Role $role): User
    {
        $user = User::create([
            'name' => $name,
            'username' => $username,
            'password' => bcrypt($this->password),
        ]);
        $user->roles()->attach($role);
        return $user;
    }

    public function test_login_page_renders_shared_identifier_and_password_form(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('CBT MADRASAH');
        $response->assertSee('Masuk ke CBT Madrasah');
        $response->assertSee('Username / NIP / NIK / NISN');
        $response->assertSee('Siswa: NISN');
        $response->assertSee('name="identifier"', false);
        $response->assertSee('name="password"', false);
        $response->assertDontSee('name="tenant_code"', false);
    }

    public function test_legacy_platform_super_admin_login_is_rejected_from_tenant_cbt(): void
    {
        $response = $this->post('/login', [
            'identifier' => 'super.admin',
            'password' => $this->password,
        ]);

        $response->assertSessionHasErrors('identifier');
        $this->assertGuest();
    }

    public function test_admin_madrasah_login_redirects_to_operator_dashboard(): void
    {
        $response = $this->post('/login', ['identifier' => 'admin.uji', 'password' => $this->password]);
        $response->assertRedirect('/operator/dashboard');
        $this->assertAuthenticatedAs($this->admin);
    }

    public function test_operator_login_redirects_to_operator_dashboard(): void
    {
        $response = $this->post('/login', ['identifier' => 'operator.uji', 'password' => $this->password]);
        $response->assertRedirect('/operator/dashboard');
        $this->assertAuthenticatedAs($this->operator);
    }

    public function test_kamad_login_redirects_to_welcome_landing(): void
    {
        $response = $this->post('/login', ['identifier' => 'kamad.uji', 'password' => $this->password]);
        $response->assertRedirect('/');
        $this->assertAuthenticatedAs($this->kamad);
    }

    public function test_kamad_cannot_open_the_operator_dashboard(): void
    {
        $this->withoutMiddleware(EnsureLicenseActive::class);
        $this->actingAs($this->kamad);

        $this->get('/operator/dashboard')->assertForbidden();
    }

    public function test_proktor_assignment_user_login_redirects_to_assignments(): void
    {
        $response = $this->post('/login', ['identifier' => 'proktor.uji', 'password' => $this->password]);
        $response->assertRedirect('/proktor/assignments');
        $this->assertAuthenticatedAs($this->proktor);
    }

    public function test_teacher_can_login_using_nip(): void
    {
        $adminRole = $this->admin->roles()->firstOrFail();
        $permission = Permission::firstOrCreate(
            ['name' => 'user.manage'],
            ['label' => 'Kelola Pengguna']
        );
        $adminRole->permissions()->syncWithoutDetaching([$permission->id]);

        // Client A: Guru login and retains its original browser session cookie.
        $guruLogin = $this->post('/login', [
            'identifier' => '198001012006041001',
            'password' => $this->password,
            'remember' => '1',
        ]);
        $guruLogin->assertRedirect('/admin/exams');
        $sessionCookieName = config('session.cookie');
        $guruSessionCookie = $guruLogin->getCookie($sessionCookieName);
        $this->assertNotNull($guruSessionCookie);
        $guruSessionCookieValue = $guruSessionCookie->getValue();
        $rememberCookieName = collect($guruLogin->headers->getCookies())
            ->first(fn ($cookie) => str_starts_with($cookie->getName(), 'remember_web_'))?->getName();
        $this->assertNotNull($rememberCookieName);
        $rememberCookie = $guruLogin->getCookie($rememberCookieName);
        $this->assertNotNull($rememberCookie);

        // Client B: a distinct Admin browser session authenticates independently.
        $this->withCookie($sessionCookieName, '');
        $adminLogin = $this->post('/login', [
            'identifier' => 'admin.uji',
            'password' => $this->password,
        ]);
        $adminLogin->assertRedirect('/admin/dashboard');
        $adminSessionCookie = $adminLogin->getCookie($sessionCookieName);
        $this->assertNotNull($adminSessionCookie);
        $this->assertNotSame($guruSessionCookieValue, $adminSessionCookie->getValue());
        $this->assertAuthenticatedAs($this->admin);

        // The official UserManagement action deactivates the Guru account.
        \Livewire\Livewire::test(\App\Livewire\Admin\UserManagement::class)
            ->call('deactivateUser', $this->guru->id)
            ->assertHasNoErrors();
        $this->assertSoftDeleted('users', ['id' => $this->guru->id]);

        // Client A's original cookie is denied while the account is inactive.
        $this->withCookie($sessionCookieName, '')
            ->withCookie($sessionCookieName, $guruSessionCookieValue);
        $this->app['auth']->forgetGuards();
        $this->get('/login')->assertOk()->assertSee('Masuk ke CBT Madrasah');
        $this->assertGuest();

        // Client B restores the same Guru identity using the official Admin action.
        $this->actingAs($this->admin);
        app(\App\Livewire\Admin\UserManagement::class)->restoreUser($this->guru->id);
        $this->assertDatabaseHas('users', ['id' => $this->guru->id, 'deleted_at' => null]);

        // Client A cannot revive its pre-deactivation session after reactivation.
        $this->withCookie($sessionCookieName, '')
            ->withCookie($sessionCookieName, $guruSessionCookieValue);
        $this->app['auth']->forgetGuards();
        $this->get('/login')->assertRedirect('/login');
        $this->assertGuest();

        // The old remember-me cookie was invalidated by remember_token rotation.
        $this->withCookie($sessionCookieName, '')
            ->withCookie($rememberCookie->getName(), $rememberCookie->getValue());
        $this->app['auth']->forgetGuards();
        $this->get('/login')->assertOk()->assertSee('Masuk ke CBT Madrasah');
        $this->assertGuest();

        // Fresh credentials establish a new browser session.
        $this->withCookie($rememberCookie->getName(), '');
        $freshLogin = $this->post('/login', [
            'identifier' => '198001012006041001',
            'password' => $this->password,
        ]);
        $freshLogin->assertRedirect('/admin/exams');
        $this->assertAuthenticatedAs($this->guru->fresh());
    }

    public function test_student_can_login_using_nisn_without_email(): void
    {
        $response = $this->post('/login', ['identifier' => $this->nisn, 'password' => $this->password]);
        $response->assertRedirect('/student/exams');
        $this->assertAuthenticatedAs($this->siswa);
    }


    public function test_soft_deleted_student_cannot_login_with_existing_nisn(): void
    {
        $this->siswa->delete();

        $response = $this->post('/login', [
            'identifier' => $this->nisn,
            'password' => $this->password,
        ]);

        $response->assertSessionHasErrors('identifier');
        $this->assertGuest();
        $this->assertSoftDeleted('users', ['id' => $this->siswa->id]);
    }

    public function test_student_dashboard_is_available_only_to_student_role(): void
    {
        $this->withoutMiddleware(EnsureLicenseActive::class);
        $this->actingAs($this->siswa);
        $response = $this->get('/student/exams');
        $response->assertOk();
        $response->assertSee('Ujian Saya');
    }

    public function test_non_student_cannot_open_student_exam_portal(): void
    {
        $this->withoutMiddleware(EnsureLicenseActive::class);
        $this->actingAs($this->guru);
        $this->get('/student/exams')->assertForbidden();
    }

    public function test_invalid_password_returns_generic_identifier_error(): void
    {
        $response = $this->post('/login', ['identifier' => 'guru.uji', 'password' => 'salah']);
        $response->assertSessionHasErrors('identifier');
        $this->assertGuest();
    }

    public function test_login_rate_limiter_blocks_repeated_failures(): void
    {
        RateLimiter::clear('login:web:guru.uji|127.0.0.1');
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['identifier' => 'guru.uji', 'password' => 'salah']);
        }
        $response = $this->post('/login', ['identifier' => 'guru.uji', 'password' => 'salah']);
        $response->assertSessionHasErrors('identifier');
        $this->assertStringContainsString('Terlalu banyak percobaan login', session('errors')->first('identifier'));
    }

    public function test_authenticated_kamad_visiting_login_is_redirected_to_welcome_landing(): void
    {
        $this->actingAs($this->kamad);
        $this->get('/login')->assertRedirect('/');
    }

    public function test_logout_terminates_session_and_redirects_to_login(): void
    {
        $this->actingAs($this->guru);
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

}
