<?php

namespace Tests\Feature;

use App\Contexts\TenantContext;
use App\Models\StudentProfile;
use App\Models\TeacherProfile;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class LoginIdentityPreflightCommandTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'Madrasah Preflight', 'code' => 'PREFLIGHT']);
        app(TenantContext::class)->setTenantId($this->tenant->id);
    }

    public function test_preflight_passes_for_valid_required_login_identities_without_printing_values(): void
    {
        $pns = User::create(['tenant_id' => $this->tenant->id, 'name' => 'Guru PNS', 'username' => 'guru.pns', 'password' => bcrypt('password123456')]);
        TeacherProfile::create([
            'user_id' => $pns->id, 'full_name' => 'Guru PNS', 'employment_status' => 'PNS',
            'nip' => '198001012006041001', 'employee_identifier' => '198001012006041001',
        ]);

        $honorer = User::create(['tenant_id' => $this->tenant->id, 'name' => 'Guru Honorer', 'username' => 'guru.honorer', 'password' => bcrypt('password123456')]);
        TeacherProfile::create([
            'user_id' => $honorer->id, 'full_name' => 'Guru Honorer', 'employment_status' => 'HONORER',
            'nik' => '3171000000000001', 'employee_identifier' => '3171000000000001',
        ]);

        $student = User::create(['tenant_id' => $this->tenant->id, 'name' => 'Siswa', 'username' => 'siswa.preflight', 'password' => bcrypt('password123456')]);
        StudentProfile::create(['user_id' => $student->id, 'full_name' => 'Siswa', 'nisn' => '0001234567']);

        $exitCode = Artisan::call('login-identity:preflight');
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Tidak ditemukan konflik', $output);
        $this->assertStringNotContainsString('198001012006041001', $output);
        $this->assertStringNotContainsString('3171000000000001', $output);
        $this->assertStringNotContainsString('0001234567', $output);
    }

    public function test_preflight_fails_closed_for_legacy_teacher_without_status_or_login_identity(): void
    {
        $legacy = User::create(['tenant_id' => $this->tenant->id, 'name' => 'Guru Legacy', 'username' => 'guru.legacy', 'password' => bcrypt('password123456')]);
        TeacherProfile::create([
            'user_id' => $legacy->id, 'full_name' => 'Guru Legacy',
            'employment_status' => null, 'nip' => null, 'nik' => null,
        ]);

        $exitCode = Artisan::call('login-identity:preflight');
        $output = Artisan::output();

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Status kepegawaian Guru kosong/tidak dikenal', $output);
        $this->assertStringNotContainsString('guru.legacy', $output);
    }
}
