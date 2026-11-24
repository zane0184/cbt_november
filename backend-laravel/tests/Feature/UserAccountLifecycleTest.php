<?php

namespace Tests\Feature;

use App\Livewire\Admin\UserManagement;
use App\Models\LocalInstallationIdentity;
use App\Models\Permission;
use App\Models\Role;
use App\Models\StudentProfile;
use App\Models\TeacherProfile;
use App\Models\Tenant;
use App\Models\User;
use App\Services\LoginIdentityResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class UserAccountLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'Madrasah Lifecycle', 'code' => 'LIFE']);
        app(\App\Contexts\TenantContext::class)->setTenantId($this->tenant->id);

        $adminRole = Role::firstOrCreate(['name' => 'ADMIN_MADRASAH', 'label' => 'Admin Madrasah']);
        $permission = Permission::firstOrCreate(['name' => 'user.manage', 'label' => 'Kelola Pengguna']);
        $adminRole->permissions()->syncWithoutDetaching([$permission->id]);
        Role::firstOrCreate(['name' => 'GURU', 'label' => 'Guru']);
        Role::firstOrCreate(['name' => 'SISWA', 'label' => 'Siswa']);

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

        $this->admin = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Admin Lifecycle',
            'email' => 'admin.lifecycle@example.test',
            'password' => 'password-admin-lifecycle',
        ]);
        $this->admin->roles()->sync([$adminRole->id]);
    }

    private function createTeacher(string $suffix = 'one'): User
    {
        $user = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Guru ' . $suffix,
            'email' => 'guru.' . $suffix . '@example.test',
            'password' => 'password-guru-lifecycle',
        ]);
        $user->roles()->sync([Role::where('name', 'GURU')->value('id')]);
        $nip = '19900101202604' . str_pad((string) (100 + strlen($suffix)), 4, '0', STR_PAD_LEFT);
        TeacherProfile::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $user->id,
            'full_name' => $user->name,
            'employment_status' => 'PNS',
            'nip' => $nip,
            'employee_identifier' => $nip,
        ]);
        return $user;
    }

    private function createStudent(): User
    {
        $user = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Siswa Lifecycle',
            'email' => 'siswa.lifecycle@example.test',
            'password' => 'password-siswa-lifecycle',
        ]);
        $user->roles()->sync([Role::where('name', 'SISWA')->value('id')]);
        StudentProfile::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $user->id,
            'full_name' => $user->name,
            'nisn' => '9000000101',
        ]);
        return $user;
    }

    public function test_admin_deactivation_revokes_api_tokens_and_web_sessions(): void
    {
        $teacher = $this->createTeacher();
        $token = $teacher->createToken('lifecycle-device');
        DB::table('sessions')->insert([
            'id' => 'lifecycle-session-' . $teacher->id,
            'user_id' => $teacher->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'lifecycle-test',
            'payload' => 'test-payload',
            'last_activity' => time(),
        ]);

        $this->actingAs($this->admin);
        Livewire::test(UserManagement::class)->call('deactivateUser', $teacher->id)->assertHasNoErrors();

        $this->assertDatabaseHas('users', ['id' => $teacher->id, 'is_active' => 0]);
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->accessToken->id]);
        $this->assertDatabaseMissing('sessions', ['user_id' => $teacher->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'USER_DEACTIVATED', 'entity_id' => $teacher->id]);
    }

    public function test_admin_can_reactivate_student_account(): void
    {
        $student = $this->createStudent();
        $student->is_active = false;
        $student->save();

        $this->actingAs($this->admin);
        Livewire::test(UserManagement::class)->call('activateUser', $student->id)->assertHasNoErrors();

        $this->assertDatabaseHas('users', ['id' => $student->id, 'is_active' => 1]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'USER_ACTIVATED', 'entity_id' => $student->id]);
    }

    public function test_password_reset_returns_one_time_password_and_revokes_api_tokens(): void
    {
        $teacher = $this->createTeacher();
        $token = $teacher->createToken('before-reset');

        $this->actingAs($this->admin);
        $component = Livewire::test(UserManagement::class)
            ->call('resetUserPassword', $teacher->id)
            ->assertSet('showOneTimePasswordModal', true)
            ->assertSet('oneTimePasswordFor', $teacher->name);

        $temporaryPassword = $component->get('oneTimePassword');
        $this->assertIsString($temporaryPassword);
        $this->assertSame(24, strlen($temporaryPassword));
        $this->assertTrue(Hash::check($temporaryPassword, $teacher->fresh()->password));
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->accessToken->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'USER_PASSWORD_RESET', 'entity_id' => $teacher->id]);

        $audit = \App\Models\AuditLog::where('action', 'USER_PASSWORD_RESET')->where('entity_id', $teacher->id)->firstOrFail();
        $this->assertStringNotContainsString($temporaryPassword, json_encode($audit->new_values));
    }

    public function test_inactive_user_cannot_resolve_login_identity_or_use_existing_api_token(): void
    {
        $activeTeacher = $this->createTeacher('active');
        $this->assertNotNull(app(LoginIdentityResolver::class)->resolve($activeTeacher->teacherProfile->nip));

        $teacher = $this->createTeacher('inactive');
        $token = $teacher->createToken('stale-token');
        $teacher->is_active = false;
        $teacher->save();

        $this->assertNull(app(LoginIdentityResolver::class)->resolve($teacher->teacherProfile->nip));

        $this->withToken($token->plainTextToken)
            ->getJson('/api/v1/auth/me')
            ->assertForbidden()
            ->assertJsonPath('code', 'ACCOUNT_INACTIVE');

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->accessToken->id]);
    }

    public function test_inactive_web_session_is_logged_out(): void
    {
        $teacher = $this->createTeacher('webinactive');
        $teacher->is_active = false;
        $teacher->save();

        $this->actingAs($teacher)->get('/guru/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }
}
