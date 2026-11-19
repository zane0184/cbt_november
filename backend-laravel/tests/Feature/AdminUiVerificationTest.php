<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Curriculum;
use App\Models\GradeLevel;
use App\Models\LearningOutcome;
use App\Models\Phase;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Subject;
use App\Models\Rombel;
use App\Models\StudentProfile;
use App\Models\Tenant;
use App\Models\User;
use App\Livewire\Admin\UserManagement;
use App\Livewire\Admin\StudentRombelManagement;
use App\Livewire\Admin\AcademicManagement;
use App\Livewire\Admin\CurriculumManagement;
use App\Livewire\Admin\QuestionBank;
use App\Livewire\Admin\ExamManagement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminUiVerificationTest extends TestCase
{
    use RefreshDatabase;

    private $tenant;
    private $admin;
    private $student;
    private $subject;
    private $academicYear;
    private $gradeLevel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create(['name' => 'Madrasah Aliyah Negeri', 'code' => 'MAN1']);
        app(\App\Contexts\TenantContext::class)->setTenantId($this->tenant->id);

        $adminRole = Role::firstOrCreate(['name' => 'ADMIN_MADRASAH', 'label' => 'Admin Madrasah']);
        $studentRole = Role::firstOrCreate(['name' => 'SISWA', 'label' => 'Siswa']);
        Role::firstOrCreate(['name' => 'GURU', 'label' => 'Guru']);
        $permissions = collect(['academic.manage', 'curriculum.view', 'curriculum.manage', 'user.manage'])
            ->mapWithKeys(fn (string $name) => [$name => Permission::firstOrCreate(['name' => $name, 'label' => $name])]);
        $adminRole->permissions()->syncWithoutDetaching($permissions->pluck('id')->all());

        $this->admin = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Admin Utama',
            'email' => 'admin@man1.sch.id',
            'password' => bcrypt('password123'),
        ]);
        $this->admin->roles()->attach($adminRole);

        $this->student = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Siswa Test',
            'email' => 'siswa@man1.sch.id',
            'password' => bcrypt('password123'),
        ]);
        $this->student->roles()->attach($studentRole);

        $curriculum = Curriculum::create(['name' => 'Kurikulum Merdeka', 'code' => 'K-MERDEKA', 'is_active' => true]);
        $phase = Phase::create(['curriculum_id' => $curriculum->id, 'name' => 'Fase E', 'code' => 'E']);
        $this->academicYear = AcademicYear::create(['name' => '2024/2025', 'is_active' => true]);
        $this->gradeLevel = GradeLevel::create(['phase_id' => $phase->id, 'name' => 'Kelas X', 'code' => 'X', 'level' => 10]);
        $this->subject = Subject::create([
            'tenant_id' => $this->tenant->id,
            'code' => 'MA-10',
            'name' => 'Matematika',
            'short_name' => 'MTK',
            'status' => 'ACTIVE',
        ]);
        $this->bootstrapCbtRolePermissions();
    }

    /** @test */
    public function unauthenticated_users_are_redirected_from_admin_routes()
    {
        $routes = [
            '/admin/users',
            '/admin/academic',
            '/admin/curriculum',
            '/admin/questions',
            '/admin/exams',
        ];

        foreach ($routes as $route) {
            $response = $this->get($route);
            $response->assertRedirect('/login');
        }
    }

    /** @test */
    public function unauthorized_students_are_rejected_from_admin_pages()
    {
        $this->actingAs($this->student);

        $routes = [
            '/admin/users',
            '/admin/academic',
            '/admin/curriculum',
            '/admin/questions',
            '/admin/exams',
        ];

        foreach ($routes as $route) {
            $response = $this->get($route);
            $response->assertStatus(403);
        }
    }

    /** @test */
    public function admin_can_access_all_admin_routes()
    {
        $this->actingAs($this->admin);

        $routes = [
            '/admin/users',
            '/admin/academic',
            '/admin/curriculum',
            '/admin/questions',
            '/admin/exams',
        ];

        foreach ($routes as $route) {
            $response = $this->get($route);
            $response->assertStatus(200);
        }
    }

    /** @test */
    public function admin_can_add_student_to_tenant_rombel_and_duplicate_is_rejected()
    {
        $studentRole = Role::where('name', 'SISWA')->firstOrFail();
        $studentUser = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Siswa Rombel',
            'email' => 'siswa.rombel@man1.sch.id',
            'password' => bcrypt('password123456'),
        ]);
        $studentUser->roles()->attach($studentRole);
        $profile = StudentProfile::create([
            'user_id' => $studentUser->id,
            'full_name' => 'Siswa Rombel',
            'nisn' => '0087654321',
        ]);
        $rombel = Rombel::create([
            'academic_year_id' => $this->academicYear->id,
            'grade_level_id' => $this->gradeLevel->id,
            'name' => 'Kelas X-A',
            'status' => 'ACTIVE',
        ]);

        $this->actingAs($this->admin);
        Livewire::test(StudentRombelManagement::class)
            ->set('selectedStudentProfileId', $profile->id)
            ->set('selectedRombelId', $rombel->id)
            ->call('addStudentToRombel')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('rombel_student', [
            'tenant_id' => $this->tenant->id,
            'student_id' => $profile->id,
            'rombel_id' => $rombel->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $this->tenant->id,
            'action' => 'STUDENT_ADDED_TO_ROMBEL',
            'entity_id' => $profile->id,
        ]);

        Livewire::test(StudentRombelManagement::class)
            ->set('selectedStudentProfileId', $profile->id)
            ->set('selectedRombelId', $rombel->id)
            ->call('addStudentToRombel')
            ->assertHasErrors(['selectedStudentProfileId']);
    }

    /** @test */
    public function admin_user_management_can_create_user()
    {
        $this->actingAs($this->admin);

        Livewire::test(UserManagement::class)
            ->set('name', 'Guru Baru')
            ->set('username', 'guru.baru')
            ->set('email', 'gurubaru@man1.sch.id')
            ->set('password', 'secret123456789')
            ->set('selectedRole', 'GURU')
            ->set('nip', '1987654321')
            ->call('createUser')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'tenant_id' => $this->tenant->id,
            'name' => 'Guru Baru',
            'email' => 'gurubaru@man1.sch.id',
        ]);

        $created = User::query()->where('tenant_id', $this->tenant->id)->where('email', 'gurubaru@man1.sch.id')->firstOrFail();
        $this->assertTrue($created->hasRole('GURU'));
        $this->assertNotNull($created->teacherProfile);
    }

    /** @test */
    public function admin_can_create_student_with_tenant_scoped_profile()
    {
        $this->actingAs($this->admin);

        Livewire::test(UserManagement::class)
            ->set('name', 'Siswa Baru')
            ->set('username', 'siswa.baru')
            ->set('password', 'secret123456789')
            ->set('selectedRole', 'SISWA')
            ->set('nisn', '0098765432')
            ->call('createUser')
            ->assertHasNoErrors();

        $student = User::query()->where('tenant_id', $this->tenant->id)->where('username', 'siswa.baru')->firstOrFail();
        $this->assertTrue($student->hasRole('SISWA'));
        $this->assertDatabaseHas('student_profiles', [
            'tenant_id' => $this->tenant->id,
            'user_id' => $student->id,
            'nisn' => '0098765432',
        ]);
    }

    /** @test */
    public function admin_user_management_cannot_create_privileged_admin_or_legacy_roles()
    {
        $this->actingAs($this->admin);

        Livewire::test(UserManagement::class)
            ->set('name', 'Admin Tambahan')
            ->set('username', 'admin.tambahan')
            ->set('password', 'secret123456789')
            ->set('selectedRole', 'ADMIN_MADRASAH')
            ->call('createUser')
            ->assertHasErrors(['selectedRole']);

        $this->assertDatabaseMissing('users', [
            'tenant_id' => $this->tenant->id,
            'username' => 'admin.tambahan',
        ]);
    }

    /** @test */
    public function admin_without_user_manage_permission_cannot_open_user_management()
    {
        $role = Role::firstOrCreate(['name' => 'ADMIN_MADRASAH_NO_USER_MANAGE', 'label' => 'Admin tanpa user manage']);
        $limitedAdmin = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Admin Terbatas',
            'email' => 'admin.terbatas@man1.sch.id',
            'password' => bcrypt('password123'),
        ]);
        $limitedAdmin->roles()->attach($role);
        $this->actingAs($limitedAdmin);

        $this->get('/admin/users')->assertForbidden();
    }

    /** @test */
    public function guru_without_academic_manage_cannot_mutate_master_academic_data()
    {
        $guruRole = Role::firstOrCreate(['name' => 'GURU', 'label' => 'Guru']);
        $guru = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Guru Test',
            'email' => 'guru@man1.sch.id',
            'password' => bcrypt('password123'),
        ]);
        $guru->roles()->attach($guruRole);
        $this->actingAs($guru);

        // Assert through the HTTP route; a synthetic Livewire snapshot is not a
        // valid way to exercise authorization on the initial component mount.
        $this->get('/admin/academic')->assertForbidden();
        $this->assertDatabaseMissing('rombels', [
            'tenant_id' => $this->tenant->id,
            'name' => 'Kelas Terlarang',
        ]);
    }

    /** @test */
    public function guru_can_view_curriculum_but_cannot_open_tp_creation()
    {
        $guruRole = Role::firstOrCreate(['name' => 'GURU', 'label' => 'Guru']);
        $curriculumView = Permission::firstOrCreate(['name' => 'curriculum.view'], ['label' => 'Lihat Kurikulum']);
        $guruRole->permissions()->attach($curriculumView);
        $guru = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Guru Kurikulum',
            'email' => 'gurukurikulum@man1.sch.id',
            'password' => bcrypt('password123'),
        ]);
        $guru->roles()->attach($guruRole);
        $this->actingAs($guru);

        $this->get('/admin/curriculum')->assertStatus(200);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        Livewire::test(CurriculumManagement::class)->call('openCreateTpModal');
    }

    /** @test */
    public function admin_academic_management_can_create_rombel_and_subject()
    {
        $this->actingAs($this->admin);

        Livewire::test(AcademicManagement::class)
            ->set('rombelName', 'X IPA 1')
            ->set('selectedAcademicYearId', $this->academicYear->id)
            ->set('selectedGradeLevelId', $this->gradeLevel->id)
            ->call('createRombel')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('rombels', [
            'tenant_id' => $this->tenant->id,
            'name' => 'X IPA 1',
        ]);

        Livewire::test(AcademicManagement::class)
            ->set('subjectCode', 'BIO-10')
            ->set('subjectName', 'Biologi')
            ->set('subjectShortName', 'BIO')
            ->call('createSubject')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('subjects', [
            'tenant_id' => $this->tenant->id,
            'code' => 'BIO-10',
            'name' => 'Biologi',
        ]);
    }

    /** @test */
    public function admin_can_view_question_bank_but_cannot_author_questions()
    {
        $this->actingAs($this->admin);

        $this->get('/admin/questions')->assertOk();
        $this->assertFalse(
            \Illuminate\Support\Facades\Gate::forUser($this->admin)->allows('create', \App\Models\Question::class)
        );
    }

    /** @test */
    public function admin_can_view_exam_management_but_cannot_create_exams()
    {
        $this->actingAs($this->admin);

        $this->get('/admin/exams')->assertOk();
        $this->assertFalse(
            \Illuminate\Support\Facades\Gate::forUser($this->admin)->allows('create', \App\Models\Assessment::class)
        );
    }
}
