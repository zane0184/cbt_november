<?php

namespace Tests\Feature;

use App\Livewire\Admin\AdminDashboard;
use App\Models\AcademicYear;
use App\Models\Curriculum;
use App\Models\GradeLevel;
use App\Models\Permission;
use App\Models\Phase;
use App\Models\Role;
use App\Models\Rombel;
use App\Models\Semester;
use App\Models\StudentProfile;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class AdminDashboardAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_is_tenant_scoped_and_contains_no_cbt_operational_data(): void
    {
        $permissionRowsBefore = Permission::query()->count();
        $permissionPivotsBefore = DB::table('permission_role')->count();

        $tenantA = Tenant::create(['name' => 'Madrasah A', 'code' => 'MADA']);
        $tenantB = Tenant::create(['name' => 'Madrasah B', 'code' => 'MADB']);

        $adminRole = Role::firstOrCreate(['name' => 'ADMIN_MADRASAH', 'label' => 'Admin Madrasah']);
        $guruRole = Role::firstOrCreate(['name' => 'GURU', 'label' => 'Guru']);
        $siswaRole = Role::firstOrCreate(['name' => 'SISWA', 'label' => 'Siswa']);

        $this->setTenantContext($tenantA->id);
        $admin = User::create([
            'name' => 'Admin A',
            'email' => 'admin.a@madrasah.test',
            'password' => bcrypt('password123'),
        ]);
        $admin->roles()->attach($adminRole);

        $studentUser = User::create([
            'name' => 'Siswa A',
            'email' => 'siswa.a@madrasah.test',
            'password' => bcrypt('password123'),
        ]);
        $studentUser->roles()->attach($siswaRole);
        $student = StudentProfile::create([
            'user_id' => $studentUser->id,
            'full_name' => 'Siswa A',
            'nisn' => '0012345678',
        ]);

        $teacherUser = User::create([
            'name' => 'Guru A',
            'email' => 'guru.a@madrasah.test',
            'password' => bcrypt('password123'),
        ]);
        $teacherUser->roles()->attach($guruRole);
        TeacherProfile::create([
            'user_id' => $teacherUser->id,
            'full_name' => 'Guru A',
        ]);

        $curriculum = Curriculum::create(['name' => 'Kurikulum Merdeka', 'code' => 'KM', 'is_active' => true]);
        $phase = Phase::create(['curriculum_id' => $curriculum->id, 'name' => 'Fase A', 'code' => 'A']);
        $gradeLevel = GradeLevel::create(['phase_id' => $phase->id, 'name' => 'Kelas 1', 'code' => '1', 'level' => 1]);
        $academicYear = AcademicYear::create(['name' => '2026/2027', 'is_active' => true]);
        Semester::create([
            'academic_year_id' => $academicYear->id,
            'term' => 'GANJIL',
            'is_active' => true,
        ]);
        Subject::create([
            'code' => 'MTK',
            'name' => 'Matematika',
            'short_name' => 'MTK',
            'status' => 'ACTIVE',
        ]);
        $rombel = Rombel::create([
            'academic_year_id' => $academicYear->id,
            'grade_level_id' => $gradeLevel->id,
            'name' => '1A',
            'status' => 'ACTIVE',
        ]);
        DB::table('rombel_student')->insert([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenantA->id,
            'rombel_id' => $rombel->id,
            'student_id' => $student->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->setTenantContext($tenantB->id);
        $studentB = User::create([
            'name' => 'Siswa B',
            'email' => 'siswa.b@madrasah.test',
            'password' => bcrypt('password123'),
        ]);
        $studentB->roles()->attach($siswaRole);
        StudentProfile::create([
            'user_id' => $studentB->id,
            'full_name' => 'Siswa B',
            'nisn' => '0098765432',
        ]);

        $this->setTenantContext($tenantA->id);
        $this->actingAs($admin);

        $this->get('/')->assertRedirect('/admin/dashboard');
        $response = $this->get('/admin/dashboard');
        $response->assertOk()
            ->assertSee('Panel Admin Madrasah')
            ->assertSee('Madrasah A')
            ->assertSee('Dashboard Eksekutif')
            ->assertDontSee('Panel Admin & Guru')
            ->assertDontSee('Dashboard Operator')
            ->assertDontSee('Manajemen Ujian')
            ->assertDontSee('Bank Soal')
            ->assertDontSee('Kunci Jawaban')
            ->assertDontSee('Total Ujian');

        Livewire::test(AdminDashboard::class)
            ->assertViewHas('totalStudents', 1)
            ->assertViewHas('totalTeachers', 1)
            ->assertViewHas('totalRombels', 1)
            ->assertViewHas('totalSubjects', 1)
            ->assertViewHas('activeYearLabel', '2026/2027')
            ->assertViewHas('activeSemesterLabel', 'Ganjil')
            ->assertViewHas('studentDistribution', fn ($rows) => $rows->count() === 1 && $rows->first()['students'] === 1);

        $this->get('/admin/questions')->assertForbidden();
        $this->get('/admin/exams')->assertForbidden();

        $this->assertSame($permissionRowsBefore, Permission::query()->count());
        $this->assertSame($permissionPivotsBefore, DB::table('permission_role')->count());
    }

    public function test_dashboard_does_not_choose_an_active_year_or_semester_automatically(): void
    {
        $tenant = Tenant::create(['name' => 'Madrasah Tanpa Periode', 'code' => 'NOPER']);
        $adminRole = Role::firstOrCreate(['name' => 'ADMIN_MADRASAH', 'label' => 'Admin Madrasah']);

        $this->setTenantContext($tenant->id);
        $admin = User::create([
            'name' => 'Admin Tanpa Periode',
            'email' => 'admin.no.period@madrasah.test',
            'password' => bcrypt('password123'),
        ]);
        $admin->roles()->attach($adminRole);
        $this->actingAs($admin);

        Livewire::test(AdminDashboard::class)
            ->assertViewHas('activeYearLabel', 'Belum Ditetapkan')
            ->assertViewHas('activeSemesterLabel', 'Belum Ditetapkan');

        $this->assertDatabaseMissing('academic_years', [
            'tenant_id' => $tenant->id,
            'is_active' => true,
        ]);
        $this->assertDatabaseMissing('semesters', [
            'tenant_id' => $tenant->id,
            'is_active' => true,
        ]);
    }


    public function test_admin_dashboard_rejects_multi_role_admin_identity(): void
    {
        $tenant = Tenant::create(['name' => 'Madrasah Multi Role', 'code' => 'MULTI']);
        $adminRole = Role::firstOrCreate(['name' => 'ADMIN_MADRASAH', 'label' => 'Admin Madrasah']);
        $guruRole = Role::firstOrCreate(['name' => 'GURU', 'label' => 'Guru']);

        $this->setTenantContext($tenant->id);
        $user = User::create([
            'name' => 'Admin Multi Role',
            'email' => 'admin.multi@madrasah.test',
            'password' => bcrypt('password123'),
        ]);
        $user->roles()->attach([$adminRole->id, $guruRole->id]);

        $this->actingAs($user)->get('/admin/dashboard')->assertForbidden();
    }

    public function test_non_admin_user_cannot_open_admin_dashboard(): void
    {
        $tenant = Tenant::create(['name' => 'Madrasah Siswa', 'code' => 'SISWA']);
        $studentRole = Role::firstOrCreate(['name' => 'SISWA', 'label' => 'Siswa']);

        $this->setTenantContext($tenant->id);
        $student = User::create([
            'name' => 'Siswa',
            'email' => 'siswa.only@madrasah.test',
            'password' => bcrypt('password123'),
        ]);
        $student->roles()->attach($studentRole);

        $this->actingAs($student)->get('/admin/dashboard')->assertForbidden();
    }
}
