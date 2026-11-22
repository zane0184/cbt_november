<?php

namespace Tests\Feature;

use App\Contexts\TenantContext;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\LoginIdentityResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Tests\TestCase;

class PortalLoginRoleBoundaryTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Madrasah Login Role Test',
            'code' => 'MLRT01',
            'status' => 'ACTIVE',
        ]);

        app(TenantContext::class)->setTenantId($this->tenant->id);
    }

    private function userWithRoles(array $roleNames): User
    {
        $user = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Pengguna Uji',
            'email' => 'portal-role-' . uniqid() . '@example.test',
            'username' => 'portal-role-' . uniqid(),
            'password' => Hash::make('password123'),
        ]);

        $roleIds = collect($roleNames)->map(function (string $name): int {
            $role = Role::firstOrCreate(
                ['name' => $name],
                ['label' => $name]
            );

            return (int) $role->id;
        })->all();

        $user->roles()->sync($roleIds);

        return $user->fresh('roles');
    }

    private function resolveTo(User $user): void
    {
        $resolver = Mockery::mock(LoginIdentityResolver::class);
        $resolver->shouldReceive('resolve')
            ->once()
            ->with('portal-test')
            ->andReturn($user);

        $this->instance(LoginIdentityResolver::class, $resolver);
    }

    public function test_exact_guru_role_is_redirected_to_teacher_portal(): void
    {
        $user = $this->userWithRoles(['GURU']);
        $this->resolveTo($user);

        $this->post('/login', [
            'identifier' => 'portal-test',
            'password' => 'password123',
        ])->assertRedirect('/guru/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_exact_admin_madrasah_role_is_redirected_to_admin_portal(): void
    {
        $user = $this->userWithRoles(['ADMIN_MADRASAH']);
        $this->resolveTo($user);

        $this->post('/login', [
            'identifier' => 'portal-test',
            'password' => 'password123',
        ])->assertRedirect('/admin/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_exact_siswa_role_is_redirected_to_student_portal(): void
    {
        $user = $this->userWithRoles(['SISWA']);
        $this->resolveTo($user);

        $this->post('/login', [
            'identifier' => 'portal-test',
            'password' => 'password123',
        ])->assertRedirect('/student/exams');

        $this->assertAuthenticatedAs($user);
    }

    public function test_kamad_role_is_not_a_login_portal(): void
    {
        $user = $this->userWithRoles(['KAMAD']);
        $this->resolveTo($user);

        $this->post('/login', [
            'identifier' => 'portal-test',
            'password' => 'password123',
        ])->assertSessionHasErrors('identifier');

        $this->assertGuest();
    }

    public function test_operator_role_is_not_a_login_portal(): void
    {
        $user = $this->userWithRoles(['OPERATOR']);
        $this->resolveTo($user);

        $this->post('/login', [
            'identifier' => 'portal-test',
            'password' => 'password123',
        ])->assertSessionHasErrors('identifier');

        $this->assertGuest();
    }

    public function test_multi_role_identity_is_rejected_even_when_one_role_is_guru(): void
    {
        $user = $this->userWithRoles(['GURU', 'SISWA']);
        $this->resolveTo($user);

        $this->post('/login', [
            'identifier' => 'portal-test',
            'password' => 'password123',
        ])->assertSessionHasErrors('identifier');

        $this->assertGuest();
    }

    public function test_authenticated_legacy_or_multi_role_session_is_logged_out_on_login_page(): void
    {
        $user = $this->userWithRoles(['KAMAD']);
        $this->actingAs($user);

        $this->get('/login')->assertRedirect('/login')->assertSessionHasErrors('identifier');

        $this->assertGuest();
    }
}
