<?php

namespace Tests\Feature;

use App\Contexts\TenantContext;
use App\Http\Middleware\EnsureLicenseActive;
use App\Models\LocalInstallationIdentity;
use App\Models\Role;
use App\Models\StudentProfile;
use App\Models\TeacherProfile;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
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
            'employment_status' => 'PNS',
            'nip' => '198001012006041001',
            'employee_identifier' => '198001012006041001',
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
            'product_code' => (string) config('license.product_code', 'CBT-MI'),
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
        $response->assertSee('Madrasah Uji');
        $response->assertDontSee('>CBT MADRASAH</h1>', false);
        $response->assertSee('Masuk ke CBT Madrasah');
        $response->assertSee('Username Admin / NIP / NIK / NISN');
        $response->assertSee('Siswa: NISN');
        $response->assertSee('name="identifier"', false);
        $response->assertSee('name="password"', false);
        $response->assertDontSee('name="tenant_code"', false);
    }

    public function test_login_heading_uses_madrasah_short_name_when_configured(): void
    {
        $this->tenant->update(['short_name' => 'MIN 15 Jakarta Selatan']);

        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('MIN 15 Jakarta Selatan');
        $response->assertDontSee('>CBT MADRASAH</h1>', false);
    }

    public function test_login_page_uses_current_installation_logo_and_favicon(): void
    {
        $logoPath = 'tenants/' . $this->tenant->id . '/branding/app-logo-login-test.png';
        $faviconPath = 'tenants/' . $this->tenant->id . '/branding/favicon-login-test.png';
        File::ensureDirectoryExists(dirname(storage_path('app/public/' . $logoPath)));
        $logo = UploadedFile::fake()->image('login-logo.png', 128, 128);
        $favicon = UploadedFile::fake()->image('login-favicon.png', 64, 64);
        File::copy($logo->getRealPath(), storage_path('app/public/' . $logoPath));
        File::copy($favicon->getRealPath(), storage_path('app/public/' . $faviconPath));
        $this->tenant->forceFill([
            'logo_path' => $logoPath,
            'favicon_path' => $faviconPath,
        ])->save();

        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('storage/' . $logoPath);
        $response->assertSee('storage/' . $faviconPath);
        $response->assertSee('type="image/png"', false);
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

    public function test_admin_madrasah_login_redirects_to_admin_madrasah_dashboard(): void
    {
        $response = $this->post('/login', ['identifier' => 'admin.uji', 'password' => $this->password]);
        $response->assertRedirect('/admin/dashboard');
        $this->assertAuthenticatedAs($this->admin);
    }

    public function test_legacy_operator_login_is_rejected(): void
    {
        $response = $this->post('/login', ['identifier' => 'operator.uji', 'password' => $this->password]);
        $response->assertSessionHasErrors('identifier');
        $this->assertGuest();
    }

    public function test_legacy_kamad_login_is_rejected(): void
    {
        $response = $this->post('/login', ['identifier' => 'kamad.uji', 'password' => $this->password]);
        $response->assertSessionHasErrors('identifier');
        $this->assertGuest();
    }

    public function test_kamad_cannot_open_the_admin_dashboard(): void
    {
        $this->withoutMiddleware(EnsureLicenseActive::class);
        $this->actingAs($this->kamad);

        $this->get('/admin/dashboard')->assertForbidden();
    }

    public function test_legacy_permanent_proktor_role_cannot_login_as_a_portal(): void
    {
        $response = $this->post('/login', ['identifier' => 'proktor.uji', 'password' => $this->password]);
        $response->assertSessionHasErrors('identifier');
        $this->assertGuest();
    }

    public function test_admin_account_with_legacy_second_role_is_rejected(): void
    {
        $this->admin->roles()->attach(Role::where('name', 'OPERATOR')->firstOrFail());

        $response = $this->post('/login', [
            'identifier' => 'admin.uji',
            'password' => $this->password,
        ]);

        $response->assertSessionHasErrors('identifier');
        $this->assertGuest();
    }

    public function test_asn_teacher_can_login_using_nip_only(): void
    {
        $response = $this->post('/login', ['identifier' => '198001012006041001', 'password' => $this->password]);
        $response->assertRedirect('/guru/dashboard');
        $this->assertAuthenticatedAs($this->guru);
    }

    public function test_pppk_teacher_can_login_using_18_digit_nip(): void
    {
        $pppkRole = Role::where('name', 'GURU')->firstOrFail();
        app(TenantContext::class)->setTenantId($this->tenant->id);
        $pppk = $this->makeUser('guru.pppk', 'Guru PPPK', $pppkRole);
        TeacherProfile::create([
            'user_id' => $pppk->id,
            'employment_status' => 'PPPK',
            'nip' => '199001012021041001',
            'employee_identifier' => '199001012021041001',
            'full_name' => 'Guru PPPK',
        ]);
        app(TenantContext::class)->setTenantId(null);

        $response = $this->post('/login', [
            'identifier' => '199001012021041001',
            'password' => $this->password,
        ]);
        $response->assertRedirect('/guru/dashboard');
        $this->assertAuthenticatedAs($pppk);
    }

    public function test_guru_username_is_not_an_authentication_fallback(): void
    {
        $response = $this->post('/login', ['identifier' => 'guru.uji', 'password' => $this->password]);
        $response->assertSessionHasErrors('identifier');
        $this->assertGuest();
    }

    public function test_honorer_teacher_can_login_using_nik_only(): void
    {
        $honorerRole = Role::where('name', 'GURU')->firstOrFail();
        app(TenantContext::class)->setTenantId($this->tenant->id);
        $honorer = $this->makeUser('guru.honorer', 'Guru Honorer', $honorerRole);
        TeacherProfile::create([
            'user_id' => $honorer->id,
            'employment_status' => 'HONORER',
            'nik' => '3171000000000001',
            'employee_identifier' => '3171000000000001',
            'full_name' => 'Guru Honorer',
        ]);
        app(TenantContext::class)->setTenantId(null);

        $response = $this->post('/login', ['identifier' => '3171000000000001', 'password' => $this->password]);
        $response->assertRedirect('/guru/dashboard');
        $this->assertAuthenticatedAs($honorer);
    }

    public function test_honorer_teacher_username_is_not_an_authentication_fallback(): void
    {
        $honorerRole = Role::where('name', 'GURU')->firstOrFail();
        app(TenantContext::class)->setTenantId($this->tenant->id);
        $honorer = $this->makeUser('guru.honorer.no.login', 'Guru Honorer', $honorerRole);
        TeacherProfile::create([
            'user_id' => $honorer->id,
            'employment_status' => 'HONORER',
            'nik' => '3171000000000002',
            'employee_identifier' => '3171000000000002',
            'full_name' => 'Guru Honorer',
        ]);
        app(TenantContext::class)->setTenantId(null);

        $response = $this->post('/login', ['identifier' => 'guru.honorer.no.login', 'password' => $this->password]);
        $response->assertSessionHasErrors('identifier');
        $this->assertGuest();
    }

    public function test_nuptk_is_not_an_authentication_fallback(): void
    {
        $guruRole = Role::where('name', 'GURU')->firstOrFail();
        app(TenantContext::class)->setTenantId($this->tenant->id);
        $teacher = $this->makeUser('guru.nuptk', 'Guru NUPTK', $guruRole);
        TeacherProfile::create([
            'user_id' => $teacher->id,
            'employment_status' => 'PNS',
            'nip' => '198001012006041002',
            'employee_identifier' => '198001012006041002',
            'nuptk' => '1234567890123456',
            'full_name' => 'Guru NUPTK',
        ]);
        app(TenantContext::class)->setTenantId(null);

        $response = $this->post('/login', [
            'identifier' => '1234567890123456',
            'password' => $this->password,
        ]);

        $response->assertSessionHasErrors('identifier');
        $this->assertGuest();
    }

    public function test_api_login_uses_nip_for_asn_and_rejects_guru_username(): void
    {
        $success = $this->postJson('/api/v1/auth/login', [
            'identifier' => '198001012006041001', 'password' => $this->password,
        ]);
        $success->assertOk()->assertJsonPath('data.user.id', $this->guru->id);

        $rejected = $this->postJson('/api/v1/auth/login', [
            'identifier' => 'guru.uji', 'password' => $this->password,
        ]);
        $rejected->assertUnprocessable();
    }

    public function test_api_login_uses_nik_for_honorer_and_rejects_honorer_username(): void
    {
        $honorerRole = Role::where('name', 'GURU')->firstOrFail();
        app(TenantContext::class)->setTenantId($this->tenant->id);
        $honorer = $this->makeUser('guru.honorer.api', 'Guru Honorer API', $honorerRole);
        TeacherProfile::create([
            'user_id' => $honorer->id,
            'employment_status' => 'HONORER',
            'nik' => '3171000000000005',
            'employee_identifier' => '3171000000000005',
            'full_name' => 'Guru Honorer API',
        ]);
        app(TenantContext::class)->setTenantId(null);

        $success = $this->postJson('/api/v1/auth/login', [
            'identifier' => '3171000000000005', 'password' => $this->password,
        ]);
        $success->assertOk()->assertJsonPath('data.user.id', $honorer->id);

        $rejected = $this->postJson('/api/v1/auth/login', [
            'identifier' => 'guru.honorer.api', 'password' => $this->password,
        ]);
        $rejected->assertUnprocessable();
    }

    public function test_student_can_login_using_nisn_without_email(): void
    {
        $response = $this->post('/login', ['identifier' => $this->nisn, 'password' => $this->password]);
        $response->assertRedirect('/student/exams');
        $this->assertAuthenticatedAs($this->siswa);
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

    public function test_login_validation_does_not_flash_personal_identifier_into_session(): void
    {
        $response = $this->post('/login', [
            'identifier' => '198001012006041001',
            'password' => '',
        ]);

        $response->assertSessionHasErrors('identifier');
        $this->assertFalse(session()->has('_old_input'));
    }

    public function test_invalid_password_returns_generic_identifier_error(): void
    {
        $response = $this->post('/login', ['identifier' => '198001012006041001', 'password' => 'salah']);
        $response->assertSessionHasErrors('identifier');
        $this->assertGuest();
    }

    public function test_login_rate_limiter_blocks_repeated_failures(): void
    {
        $identifier = '198001012006041001';
        $throttleKey = 'login:web:'.hash('sha256', $identifier.'|127.0.0.1');
        RateLimiter::clear($throttleKey);
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['identifier' => $identifier, 'password' => 'salah']);
        }
        $response = $this->post('/login', ['identifier' => $identifier, 'password' => 'salah']);
        $response->assertSessionHasErrors('identifier');
        $this->assertStringContainsString('Terlalu banyak percobaan login', session('errors')->first('identifier'));
    }

    public function test_authenticated_legacy_kamad_is_logged_out_before_login_form(): void
    {
        $this->actingAs($this->kamad);
        $this->get('/login')->assertOk();
        $this->assertGuest();
    }

    public function test_logout_terminates_session_and_redirects_to_login(): void
    {
        $this->actingAs($this->guru);
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }
}
