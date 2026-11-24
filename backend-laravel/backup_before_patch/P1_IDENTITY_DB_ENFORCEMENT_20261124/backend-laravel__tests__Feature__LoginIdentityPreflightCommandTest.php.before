<?php

namespace Tests\Feature;

use App\Contexts\TenantContext;
use App\Models\StudentProfile;
use App\Models\TeacherProfile;
use App\Models\Tenant;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Database\QueryException;
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
        $pns = $this->createUserWithRole('Guru PNS', 'guru.pns', 'GURU');
        TeacherProfile::create([
            'user_id' => $pns->id, 'full_name' => 'Guru PNS', 'employment_status' => 'PNS',
            'nip' => '198001012006041001', 'employee_identifier' => '198001012006041001',
        ]);

        $honorer = $this->createUserWithRole('Guru Honorer', 'guru.honorer', 'GURU');
        TeacherProfile::create([
            'user_id' => $honorer->id, 'full_name' => 'Guru Honorer', 'employment_status' => 'HONORER',
            'nik' => '3171000000000001', 'employee_identifier' => '3171000000000001',
        ]);

        $student = $this->createUserWithRole('Siswa', 'siswa.preflight', 'SISWA');
        StudentProfile::create(['user_id' => $student->id, 'full_name' => 'Siswa', 'nisn' => '0001234567']);
        $this->createUserWithRole('Admin Madrasah', 'admin.preflight', 'ADMIN_MADRASAH');

        $before = $this->snapshotIdentityTables();
        $exitCode = Artisan::call('login-identity:preflight');
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Tidak ditemukan konflik', $output);
        $this->assertStringNotContainsString('198001012006041001', $output);
        $this->assertStringNotContainsString('3171000000000001', $output);
        $this->assertStringNotContainsString('0001234567', $output);
        $this->assertSame($before, $this->snapshotIdentityTables());
    }

    public function test_preflight_detects_whitespace_padded_identity_without_echoing_value(): void
    {
        $teacher = $this->createUserWithRole('Guru Spasi', 'guru.spasi', 'GURU');
        TeacherProfile::create([
            'user_id' => $teacher->id,
            'full_name' => 'Guru Spasi',
            'employment_status' => 'PNS',
            'nip' => ' 19800101200604100',
            'employee_identifier' => ' 19800101200604100',
        ]);

        $exitCode = Artisan::call('login-identity:preflight');
        $output = Artisan::output();

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Field identitas Guru memiliki spasi di tepi', $output);
        $this->assertStringNotContainsString('19800101200604100', $output);
    }

    public function test_preflight_fails_closed_for_legacy_teacher_without_status_or_login_identity(): void
    {
        $legacy = $this->createUserWithRole('Guru Legacy', 'guru.legacy', 'GURU');
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

    public function test_preflight_detects_wrong_identity_category_and_invalid_nuptk_read_only(): void
    {
        $pns = $this->createUserWithRole('Guru PNS Salah Kategori', 'guru.pns.kategori', 'GURU');
        TeacherProfile::create([
            'user_id' => $pns->id,
            'full_name' => 'Guru PNS Salah Kategori',
            'employment_status' => 'PNS',
            'nip' => '198001012006041001',
            'nik' => '3171000000000002',
            'nuptk' => '12345',
            'employee_identifier' => '198001012006041001',
        ]);

        $honorer = $this->createUserWithRole('Guru Honorer Salah Kategori', 'guru.honorer.kategori', 'GURU');
        TeacherProfile::create([
            'user_id' => $honorer->id,
            'full_name' => 'Guru Honorer Salah Kategori',
            'employment_status' => 'HONORER',
            'nip' => '199001012026041001',
            'nik' => '3171000000000003',
            'employee_identifier' => '3171000000000003',
        ]);

        $student = $this->createUserWithRole('Siswa NISN Invalid', 'siswa.nisn.invalid', 'SISWA');
        StudentProfile::create([
            'user_id' => $student->id,
            'full_name' => 'Siswa NISN Invalid',
            'nisn' => 'BAD',
        ]);

        $before = $this->snapshotIdentityTables();
        $exitCode = Artisan::call('login-identity:preflight');
        $output = Artisan::output();

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Guru PNS/PPPK mengisi NIK yang tidak diperbolehkan', $output);
        $this->assertStringContainsString('Guru Honorer mengisi NIP yang tidak diperbolehkan', $output);
        $this->assertStringContainsString('NUPTK terisi dengan format tidak valid', $output);
        $this->assertStringContainsString('Siswa tanpa NISN valid 10 digit', $output);
        $this->assertStringNotContainsString('198001012006041001', $output);
        $this->assertStringNotContainsString('3171000000000002', $output);
        $this->assertStringNotContainsString('BAD', $output);
        $this->assertSame($before, $this->snapshotIdentityTables());
    }

    public function test_preflight_detects_profile_role_and_cardinality_anomalies_read_only(): void
    {
        $teacher = $this->createUserWithRole('Guru Relasi', 'guru.relasi', 'GURU');
        $student = $this->createUserWithRole('Siswa Relasi', 'siswa.relasi', 'SISWA');
        $missingTeacher = $this->createUserWithRole('Guru Tanpa Profil', 'guru.tanpa.profil', 'GURU');
        $missingStudent = $this->createUserWithRole('Siswa Tanpa Profil', 'siswa.tanpa.profil', 'SISWA');
        $multiRole = $this->createUserWithRole('Akun Multi Role', 'akun.multi.role', 'GURU');
        $multiRole->roles()->attach(Role::firstOrCreate(['name' => 'SISWA'], ['label' => 'Siswa']));

        DB::table('teacher_profiles')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $teacher->id,
            'tenant_id' => $this->tenant->id,
            'full_name' => 'Guru Relasi Tenant Salah',
            'employment_status' => 'PNS',
            'nip' => '198001012006041001',
            'employee_identifier' => '198001012006041001',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('student_profiles')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $teacher->id,
            'tenant_id' => $this->tenant->id,
            'full_name' => 'Profil Siswa Pada Akun Guru',
            'nisn' => '0001234567',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $before = $this->snapshotIdentityTables();
        $exitCode = Artisan::call('login-identity:preflight');
        $output = Artisan::output();

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Profil Siswa tidak memiliki tepat satu role SISWA', $output);
        $this->assertStringContainsString('User memiliki profil Guru dan Siswa sekaligus', $output);
        $this->assertStringContainsString('Akun memiliki jumlah role bukan satu', $output);
        $this->assertStringContainsString('Akun Guru tanpa profil aktif', $output);
        $this->assertStringContainsString('Akun Siswa tanpa profil aktif', $output);
        $this->assertStringNotContainsString('198001012006041001', $output);
        $this->assertStringNotContainsString('0001234567', $output);
        $this->assertSame($before, $this->snapshotIdentityTables());
    }

    public function test_preflight_detects_missing_and_whitespace_padded_admin_username_without_mutation(): void
    {
        $blankAdmin = $this->createUserWithRole('Admin Tanpa Username', 'admin.blank', 'ADMIN_MADRASAH');
        $paddedAdmin = $this->createUserWithRole('Admin Username Spasi', 'admin.padded', 'ADMIN_MADRASAH');

        DB::table('users')->where('id', $blankAdmin->id)->update(['username' => '']);
        DB::table('users')->where('id', $paddedAdmin->id)->update(['username' => ' admin.padded ']);

        $before = $this->snapshotIdentityTables();
        $exitCode = Artisan::call('login-identity:preflight');
        $output = Artisan::output();

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Admin tanpa username valid', $output);
        $this->assertStringContainsString('Username Admin memiliki spasi di tepi', $output);
        $this->assertStringNotContainsString('admin.padded', $output);
        $this->assertSame($before, $this->snapshotIdentityTables());
    }

    public function test_database_rejects_cross_tenant_teacher_profile_relation(): void
    {
        $secondTenant = Tenant::create(['name' => 'Madrasah Kedua', 'code' => 'PREFLIGHT2']);
        $teacher = $this->createUserWithRole('Guru Tenant', 'guru.tenant', 'GURU');

        $this->expectException(QueryException::class);

        DB::table('teacher_profiles')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $teacher->id,
            'tenant_id' => $secondTenant->id,
            'full_name' => 'Guru Tenant Mismatch',
            'employment_status' => 'PNS',
            'nip' => '198001012006041001',
            'employee_identifier' => '198001012006041001',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_preflight_fails_closed_when_required_schema_column_is_missing(): void
    {
        Schema::shouldReceive('hasTable')
            ->once()
            ->with('teacher_profiles')
            ->andReturn(true);
        Schema::shouldReceive('getColumnListing')
            ->once()
            ->with('teacher_profiles')
            ->andReturn(['id', 'user_id', 'tenant_id']);

        try {
            $exitCode = Artisan::call('login-identity:preflight');
            $output = Artisan::output();
            $this->assertSame(1, $exitCode);
            $this->assertStringContainsString('kolom wajib pada tabel teacher_profiles belum lengkap', $output);
        } finally {
            Schema::clearResolvedInstance('db.schema');
        }
    }

    private function createUserWithRole(string $name, string $username, string $roleName): User
    {
        $user = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => $name,
            'username' => $username,
            'password' => bcrypt('password123456'),
        ]);

        $role = Role::firstOrCreate(['name' => $roleName], ['label' => $roleName]);
        $user->roles()->attach($role);

        return $user;
    }

    private function snapshotIdentityTables(): array
    {
        $ordering = [
            'users' => ['id'],
            'role_user' => ['user_id', 'role_id'],
            'roles' => ['id'],
            'teacher_profiles' => ['id'],
            'student_profiles' => ['id'],
        ];

        $snapshot = [];
        foreach ($ordering as $table => $columns) {
            $query = DB::table($table);
            foreach ($columns as $column) {
                $query->orderBy($column);
            }
            $rows = $query->get()->map(fn ($row) => (array) $row)->all();
            $snapshot[$table] = hash('sha256', json_encode($rows, JSON_THROW_ON_ERROR));
        }

        return $snapshot;
    }
}
