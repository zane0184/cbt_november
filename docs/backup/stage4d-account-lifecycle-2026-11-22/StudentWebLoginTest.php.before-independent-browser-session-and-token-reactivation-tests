<?php

namespace Tests\Feature;

use App\Contexts\TenantContext;
use App\Models\LocalInstallationIdentity;
use App\Models\Role;
use App\Models\StudentProfile;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
