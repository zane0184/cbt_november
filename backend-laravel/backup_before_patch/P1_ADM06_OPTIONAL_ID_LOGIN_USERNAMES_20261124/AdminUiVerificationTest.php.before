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
use App\Models\Semester;
use App\Models\TeacherProfile;
use App\Models\TeacherSubjectAssignment;
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
use Tests\Concerns\CreatesCanonicalCurriculumContext;

class AdminUiVerificationTest extends TestCase
{
    use RefreshDatabase;
    use CreatesCanonicalCurriculumContext;

    private $tenant;
    private $admin;
    private $student;
    private $subject;
    private $academicYear;
    private $semester;
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
        $adminRole->permissions()->attach($permissions->pluck('id')->all());

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
        $phase = Phase::create(['curriculum_id' => $curriculum->id, 'name' => 'Fase C', 'code' => 'C']);
        $this->academicYear = AcademicYear::create(['name' => '2024/2025', 'is_active' => true]);
        $this->semester = Semester::create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $this->academicYear->id,
            'term' => 'GANJIL',
            'is_active' => true,
        ]);
        $this->gradeLevel = GradeLevel::create(['phase_id' => $phase->id, 'name' => 'Kelas 6', 'code' => '6', 'level' => 6]);
        $this->subject = Subject::create([
            'tenant_id' => $this->tenant->id,
            'code' => 'MAT',
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
            '/admin/rombel-students',
            '/admin/teacher-assignments',
            '/admin/curriculum/registry',
            '/admin/proctor-assignments',
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
            '/admin/rombel-students',
            '/admin/teacher-assignments',
            '/admin/curriculum/registry',
            '/admin/proctor-assignments',
        ];

        foreach ($routes as $route) {
            $response = $this->get($route);
            $response->assertStatus(403);
        }
    }

    /** @test */
    public function admin_can_access_administrative_routes_but_not_exam_or_question_operations()
    {
        $this->actingAs($this->admin);

        foreach ([
            '/admin/dashboard',
            '/admin/users',
            '/admin/academic',
            '/admin/rombel-students',
            '/admin/teacher-assignments',
            '/admin/curriculum',
            '/admin/curriculum/registry',
            '/admin/proctor-assignments',
        ] as $route) {
            $this->get($route)->assertOk();
        }

        $this->get('/admin/questions')->assertNotFound();
        $this->get('/admin/exams')->assertNotFound();
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
    public function admin_user_management_filter_does_not_offer_retired_operator_or_kamad_roles()
    {
        $this->actingAs($this->admin);

        Livewire::test(UserManagement::class)
            ->assertSee('ADMIN_MADRASAH')
            ->assertSee('GURU')
            ->assertSee('SISWA')
            ->assertDontSee('value="OPERATOR"', false)
            ->assertDontSee('value="KAMAD"', false);
    }

    /** @test */
    public function legacy_material_api_is_read_only()
    {
        $this->actingAs($this->admin);

        $routes = collect(app('router')->getRoutes()->getRoutes());
        $this->assertSame(1, $routes->filter(
            fn ($route) => $route->uri() === 'api/v1/materials' && in_array('GET', $route->methods(), true)
        )->count());
        $this->assertSame(0, $routes->filter(
            fn ($route) => $route->uri() === 'api/v1/materials' && in_array('POST', $route->methods(), true)
        )->count());
        $this->assertSame(0, $routes->filter(
            fn ($route) => $route->uri() === 'api/v1/materials/{material}'
                && array_intersect(['PUT', 'PATCH', 'DELETE'], $route->methods()) !== []
        )->count());
    }

    /** @test */
    public function admin_cannot_create_teacher_without_employment_status_and_required_identity()
    {
        $this->actingAs($this->admin);

        Livewire::test(UserManagement::class)
            ->set('name', 'Guru Tanpa Identitas')
            ->set('password', 'secret123456789')
            ->set('selectedRole', 'GURU')
            ->call('createUser')
            ->assertHasErrors(['employmentStatus']);

        $this->assertDatabaseMissing('users', ['tenant_id' => $this->tenant->id, 'name' => 'Guru Tanpa Identitas']);
    }

    /** @test */
    public function admin_can_create_pns_teacher_only_with_18_digit_nip()
    {
        $this->actingAs($this->admin);

        Livewire::test(UserManagement::class)
            ->set('name', 'Guru PNS')
            ->set('password', 'secret123456789')
            ->set('selectedRole', 'GURU')
            ->set('employmentStatus', 'PNS')
            ->set('nip', '198001012006041001')
            ->call('createUser')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('teacher_profiles', [
            'tenant_id' => $this->tenant->id, 'employment_status' => 'PNS',
            'nip' => '198001012006041001', 'nik' => null,
        ]);
    }

    /** @test */
    public function admin_can_create_honorer_teacher_only_with_16_digit_nik()
    {
        $this->actingAs($this->admin);

        Livewire::test(UserManagement::class)
            ->set('name', 'Guru Honorer')
            ->set('password', 'secret123456789')
            ->set('selectedRole', 'GURU')
            ->set('employmentStatus', 'HONORER')
            ->set('nik', '3171000000000001')
            ->call('createUser')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('teacher_profiles', [
            'tenant_id' => $this->tenant->id, 'employment_status' => 'HONORER',
            'nip' => null, 'nik' => '3171000000000001',
        ]);
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
            ->set('employmentStatus', 'PNS')
            ->set('nip', '198765432100000001')
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

        // AcademicManagement denies this role during component mount.
        Livewire::test(AcademicManagement::class)->assertStatus(403);
    }

    /** @test */
    public function guru_cannot_open_admin_curriculum_reference_page()
    {
        $guruRole = Role::firstOrCreate(['name' => 'GURU', 'label' => 'Guru']);
        $curriculumView = Permission::firstOrCreate(['name' => 'curriculum.view'], ['label' => 'Lihat Kurikulum']);
        $guruRole->permissions()->syncWithoutDetaching([$curriculumView->id]);
        $guru = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Guru Kurikulum',
            'email' => 'gurukurikulum@man1.sch.id',
            'password' => bcrypt('password123'),
        ]);
        $guru->roles()->attach($guruRole);
        $this->actingAs($guru);

        $this->get('/admin/curriculum')->assertForbidden();
    }

    /** @test */
    public function admin_cannot_create_or_modify_teacher_learning_objectives()
    {
        $this->actingAs($this->admin);

        $this->get('/admin/curriculum')->assertOk();

        Livewire::test(CurriculumManagement::class)
            ->call('openCreateTpModal')
            ->assertStatus(403);

        Livewire::test(CurriculumManagement::class)
            ->call('createTp')
            ->assertStatus(403);
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
    public function guru_can_create_question_for_assigned_subject()
    {
        $guruRole = Role::where('name', 'GURU')->firstOrFail();
        $guru = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Guru Bank Soal',
            'email' => 'guru.banksoal@man1.sch.id',
            'password' => bcrypt('password123'),
        ]);
        $guru->roles()->attach($guruRole);

        $teacherProfile = TeacherProfile::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $guru->id,
            'full_name' => $guru->name,
        ]);
        $rombel = Rombel::create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $this->academicYear->id,
            'grade_level_id' => $this->gradeLevel->id,
            'name' => 'Kelas X-Guru',
            'status' => 'ACTIVE',
        ]);
        $assignment = TeacherSubjectAssignment::create([
            'tenant_id' => $this->tenant->id,
            'teacher_profile_id' => $teacherProfile->id,
            'subject_id' => $this->subject->id,
            'rombel_id' => $rombel->id,
            'academic_year_id' => $this->academicYear->id,
            'semester_id' => $this->semester->id,
            'starts_at' => now()->subDay()->toDateString(),
            'ends_at' => now()->addYears(5)->toDateString(),
            'status' => 'ACTIVE',
            'created_by' => $guru->id,
        ]);
        $canonicalContext = $this->createCanonicalCurriculumContext($assignment, $guru);

        $this->actingAs($guru);

        Livewire::test(QuestionBank::class)
            ->set('selectedAssignmentId', $assignment->id)
            ->set('selectedCpIds', [$canonicalContext['cp']->id])
            ->set('selectedOutcomeIds', [$canonicalContext['tp']->id])
            ->set('selectedSubjectId', $this->subject->id)
            ->set('questionCode', 'MTK-001')
            ->set('questionType', 'MULTIPLE_CHOICE')
            ->set('selectedSubjectId', $this->subject->id)
            ->set('stem', 'Berapa 5 + 5?')
            ->set('defaultPoints', 10)
            ->set('optionA', '10')
            ->set('optionB', '20')
            ->set('correctOption', 'A')
            ->call('saveQuestion')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('questions', [
            'tenant_id' => $this->tenant->id,
            'question_code' => 'MTK-001',
            'status' => 'DRAFT',
        ]);
    }

    /** @test */
    public function admin_cannot_create_or_configure_exams_through_livewire()
    {
        $this->actingAs($this->admin);

        Livewire::test(ExamManagement::class)->assertForbidden();

        $this->assertDatabaseMissing('exams', [
            'tenant_id' => $this->tenant->id,
            'title' => 'Ujian Akhir Semester Matematika',
        ]);
    }
}
