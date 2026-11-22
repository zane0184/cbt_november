<?php

namespace Tests\Feature;

use App\Contexts\TenantContext;
use App\Livewire\Teacher\TeacherDashboard;
use App\Livewire\Admin\ExamManagement;
use App\Models\Assessment;
use App\Models\Exam;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\QuestionVersion;
use App\Livewire\Admin\QuestionBank;
use App\Models\Permission;
use App\Livewire\Teacher\TeacherLearning;
use App\Livewire\Teacher\TeacherProfilePage;
use App\Models\MultiCurriculum;
use App\Models\MultiCurriculumStructure;
use App\Models\MultiCurriculumVersion;
use App\Models\MultiLearningOutcome;
use App\Models\Role;
use App\Models\TeacherProfile;
use App\Models\TeacherSubjectAssignment;
use App\Models\Tenant;
use App\Models\TenantCurriculumRegistration;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\CreatesTeacherExamScope;
use Tests\TestCase;

class TeacherPortalFoundationTest extends TestCase
{
    use RefreshDatabase;
    use CreatesTeacherExamScope;

    private Tenant $tenant;
    private User $teacher;
    private Role $guruRole;
    private array $scope;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Madrasah Uji Guru',
            'code' => 'MUG01',
            'status' => 'ACTIVE',
        ]);

        app(TenantContext::class)->setTenantId($this->tenant->id);

        $this->guruRole = Role::create(['name' => 'GURU', 'label' => 'Guru']);
        $guruPermissions = collect(['question.view', 'exam.manage'])
            ->map(fn (string $name) => Permission::firstOrCreate(['name' => $name], ['label' => $name]));
        $this->guruRole->permissions()->sync($guruPermissions->pluck('id')->all());
        $this->teacher = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Guru Portal Uji',
            'email' => 'guru.portal@mug.test',
            'password' => bcrypt('password123'),
        ]);
        $this->teacher->roles()->attach($this->guruRole->id);

        $this->scope = $this->createTeacherExamScope($this->tenant, $this->teacher, [], 'PORTAL');
        $this->scope['academicYear']->update(['is_active' => true]);
        $this->scope['semester']->update(['is_active' => true]);

        TeacherProfile::query()
            ->where('tenant_id', $this->tenant->id)
            ->where('user_id', $this->teacher->id)
            ->firstOrFail()
            ->update([
                'nip' => '198001012006041001',
                'phone' => '081200000001',
            ]);
    }

    public function test_guru_can_open_dashboard_with_real_assignment_and_student_counts(): void
    {
        $studentRole = Role::create(['name' => 'SISWA', 'label' => 'Siswa']);
        $student = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Siswa Portal Uji',
            'email' => 'siswa.portal@mug.test',
            'password' => bcrypt('password123'),
        ]);
        $student->roles()->attach($studentRole->id);
        $this->attachStudentToRombel($this->tenant, $student, $this->scope['rombel']);

        $response = $this->actingAs($this->teacher)->get('/guru/dashboard');

        $response->assertOk()
            ->assertSee('Dashboard Guru')
            ->assertSee('Guru Portal Uji')
            ->assertSee('Mata Pelajaran PORTAL')
            ->assertSee('Rombel PORTAL')
            ->assertSee($this->scope['academicYear']->name)
            ->assertSee('GANJIL')
            ->assertSee('Belum ada agenda terhubung')
            ->assertSee('Kurikulum Merdeka Terintegrasi KBC');
    }

    public function test_semester_is_not_guessed_when_admin_has_not_activated_it(): void
    {
        $this->scope['semester']->update(['is_active' => false]);

        $response = $this->actingAs($this->teacher)->get('/guru/dashboard');

        $response->assertOk()
            ->assertSee('Belum Ditetapkan')
            ->assertSee($this->scope['academicYear']->name);
    }

    public function test_admin_and_multi_role_users_are_denied_from_guru_portal_url_and_livewire(): void
    {
        $adminRole = Role::create(['name' => 'ADMIN_MADRASAH', 'label' => 'Admin Madrasah']);
        $admin = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Admin Uji',
            'email' => 'admin.portal@mug.test',
            'password' => bcrypt('password123'),
        ]);
        $admin->roles()->attach($adminRole->id);

        $this->actingAs($admin)->get('/guru/dashboard')->assertForbidden();
        Livewire::actingAs($admin)->test(TeacherDashboard::class)->assertForbidden();

        $this->teacher->roles()->attach($adminRole->id);
        $this->actingAs($this->teacher)->get('/guru/dashboard')->assertForbidden();
    }

    public function test_question_and_exam_livewire_screens_allow_only_single_role_guru_with_permission_and_assignment(): void
    {
        Livewire::actingAs($this->teacher)->test(QuestionBank::class)->assertOk();
        Livewire::actingAs($this->teacher)->test(ExamManagement::class)->assertOk();

        $adminRole = Role::create(['name' => 'ADMIN_MADRASAH', 'label' => 'Admin Madrasah']);
        $studentRole = Role::create(['name' => 'SISWA', 'label' => 'Siswa']);
        $kamadRole = Role::create(['name' => 'KAMAD', 'label' => 'Kepala Madrasah']);
        $portalPermissionIds = $this->guruRole->permissions()->pluck('permissions.id')->all();

        foreach ([
            [$adminRole, 'admin.component@mug.test'],
            [$studentRole, 'siswa.component@mug.test'],
            [$kamadRole, 'kamad.component@mug.test'],
        ] as [$role, $email]) {
            // Give denied roles the same component permissions so denial proves
            // the exact-one-GURU boundary, not merely a missing permission.
            $role->permissions()->sync($portalPermissionIds);
            $user = User::create([
                'tenant_id' => $this->tenant->id,
                'name' => 'Akun Ditolak',
                'email' => $email,
                'password' => bcrypt('password123'),
            ]);
            $user->roles()->attach($role->id);

            Livewire::actingAs($user)->test(QuestionBank::class)->assertForbidden();
            Livewire::actingAs($user)->test(ExamManagement::class)->assertForbidden();
        }

        foreach ([$studentRole, $adminRole, $kamadRole] as $additionalRole) {
            $this->teacher->roles()->attach($additionalRole->id);
            $this->teacher->unsetRelation('roles');

            Livewire::actingAs($this->teacher)->test(QuestionBank::class)->assertForbidden();
            Livewire::actingAs($this->teacher)->test(ExamManagement::class)->assertForbidden();

            $this->teacher->roles()->detach($additionalRole->id);
            $this->teacher->unsetRelation('roles');
        }
    }
    public function test_question_and_exam_livewire_screens_reject_missing_permission_and_assignment(): void
    {
        $questionView = Permission::query()->where('name', 'question.view')->firstOrFail();
        $examManage = Permission::query()->where('name', 'exam.manage')->firstOrFail();

        $this->guruRole->permissions()->detach($questionView->id);
        $this->teacher->unsetRelation('roles');
        Livewire::actingAs($this->teacher)->test(QuestionBank::class)->assertForbidden();

        $this->guruRole->permissions()->detach($examManage->id);
        $this->teacher->unsetRelation('roles');
        Livewire::actingAs($this->teacher)->test(ExamManagement::class)->assertForbidden();

        $this->guruRole->permissions()->sync([$questionView->id, $examManage->id]);
        $this->teacher->unsetRelation('roles');

        TeacherSubjectAssignment::query()
            ->where('tenant_id', $this->tenant->id)
            ->whereIn('teacher_profile_id', TeacherProfile::query()
                ->where('tenant_id', $this->tenant->id)
                ->where('user_id', $this->teacher->id)
                ->select('id'))
            ->delete();

        // A matching tenant context with no active subject assignment is also denied.
        Livewire::actingAs($this->teacher)->test(QuestionBank::class)->assertForbidden();
        Livewire::actingAs($this->teacher)->test(ExamManagement::class)->assertForbidden();
    }

    public function test_question_and_exam_livewire_recheck_role_during_hydration_and_direct_actions(): void
    {
        $questionHydration = Livewire::actingAs($this->teacher)->test(QuestionBank::class);
        $questionAction = Livewire::actingAs($this->teacher)->test(QuestionBank::class);
        $examHydration = Livewire::actingAs($this->teacher)->test(ExamManagement::class);
        $examAction = Livewire::actingAs($this->teacher)->test(ExamManagement::class);
        $before = $this->tenantQuestionExamSnapshot();

        $studentRole = Role::create(['name' => 'SISWA', 'label' => 'Siswa']);
        $this->teacher->roles()->attach($studentRole->id);
        $this->teacher->unsetRelation('roles');

        $questionHydration->set('search', 'blocked')->assertForbidden();
        $questionAction->call('addMatchingPair')->assertForbidden();
        $examHydration->set('search', 'blocked')->assertForbidden();
        $examAction->call('createExam')->assertForbidden();
        $this->assertSame($before, $this->tenantQuestionExamSnapshot());
    }

    public function test_guru_cannot_open_question_or_exam_ids_from_another_tenant(): void
    {
        $tenantB = Tenant::create([
            'name' => 'Madrasah Foreign',
            'code' => 'MUG03',
            'status' => 'ACTIVE',
        ]);
        app(TenantContext::class)->setTenantId($tenantB->id);

        $teacherB = User::create([
            'tenant_id' => $tenantB->id,
            'name' => 'Guru Tenant Foreign',
            'email' => 'guru.foreign@mug.test',
            'password' => bcrypt('password123'),
        ]);
        $teacherB->roles()->attach($this->guruRole->id);
        $foreignScope = $this->createTeacherExamScope($tenantB, $teacherB, [], 'FOREIGN');

        $foreignQuestion = Question::create([
            'owner_type' => 'SUBJECT',
            'question_code' => 'FOREIGN-QUESTION',
            'status' => 'DRAFT',
            'subject_id' => $foreignScope['subject']->id,
            'created_by' => $teacherB->id,
            'updated_by' => $teacherB->id,
        ]);
        $foreignVersion = QuestionVersion::create([
            'question_id' => $foreignQuestion->id,
            'major_version' => 1,
            'minor_version' => 0,
            'question_type' => 'MULTIPLE_CHOICE',
            'stem' => 'SECRET_STEM_TENANT_B',
            'explanation' => 'SECRET_EXPLANATION_TENANT_B',
            'default_points' => 10,
            'status' => 'DRAFT',
            'is_current' => true,
            'created_by' => $teacherB->id,
        ]);
        $foreignOption = QuestionOption::create([
            'question_version_id' => $foreignVersion->id,
            'option_key' => 'A',
            'content' => 'SECRET_CORRECT_OPTION_TENANT_B',
            'sequence' => 1,
            'is_correct' => true,
            'score_weight' => 1,
        ]);

        $foreignAssessment = Assessment::create([
            'academic_year_id' => $foreignScope['academicYear']->id,
            'semester_id' => $foreignScope['semester']->id,
            'subject_id' => $foreignScope['subject']->id,
            'title' => 'SECRET_ASSESSMENT_TENANT_B',
            'assessment_type' => 'SUMMATIVE',
            'status' => 'ACTIVE',
            'weight' => 1.0,
            'created_by' => $teacherB->id,
        ]);
        $foreignExam = Exam::create([
            'assessment_id' => $foreignAssessment->id,
            'title' => 'SECRET_EXAM_TENANT_B',
            'instructions' => 'SECRET_EXAM_INSTRUCTIONS_TENANT_B',
            'status' => 'DRAFT',
            'duration_seconds' => 3600,
            'start_at' => now()->addDay(),
            'end_at' => now()->addDays(2),
            'attempt_limit' => 1,
            'shuffle_questions' => true,
            'shuffle_options' => true,
            'require_token' => true,
            'created_by' => $teacherB->id,
        ]);

        // Prove all foreign-tenant fixtures were persisted before testing denial.
        $this->assertDatabaseHas('questions', [
            'id' => $foreignQuestion->id,
            'tenant_id' => $tenantB->id,
            'question_code' => 'FOREIGN-QUESTION',
        ]);
        $this->assertDatabaseHas('question_versions', [
            'id' => $foreignVersion->id,
            'tenant_id' => $tenantB->id,
            'question_id' => $foreignQuestion->id,
            'stem' => 'SECRET_STEM_TENANT_B',
            'explanation' => 'SECRET_EXPLANATION_TENANT_B',
            'is_current' => true,
        ]);
        $this->assertDatabaseHas('question_options', [
            'id' => $foreignOption->id,
            'tenant_id' => $tenantB->id,
            'question_version_id' => $foreignVersion->id,
            'content' => 'SECRET_CORRECT_OPTION_TENANT_B',
            'is_correct' => true,
        ]);
        $this->assertDatabaseHas('assessments', [
            'id' => $foreignAssessment->id,
            'tenant_id' => $tenantB->id,
            'title' => 'SECRET_ASSESSMENT_TENANT_B',
        ]);
        $this->assertDatabaseHas('exams', [
            'id' => $foreignExam->id,
            'tenant_id' => $tenantB->id,
            'assessment_id' => $foreignAssessment->id,
            'title' => 'SECRET_EXAM_TENANT_B',
            'instructions' => 'SECRET_EXAM_INSTRUCTIONS_TENANT_B',
        ]);

        app(TenantContext::class)->setTenantId($this->tenant->id);
        $questionComponent = Livewire::actingAs($this->teacher)
            ->test(QuestionBank::class)
            ->assertOk()
            ->assertDontSee('SECRET_STEM_TENANT_B')
            ->assertDontSee('SECRET_CORRECT_OPTION_TENANT_B');
        $examComponent = Livewire::actingAs($this->teacher)
            ->test(ExamManagement::class)
            ->assertOk()
            ->assertDontSee('SECRET_EXAM_TENANT_B')
            ->assertDontSee('SECRET_EXAM_INSTRUCTIONS_TENANT_B');

        try {
            $questionComponent->call('openEditModal', $foreignQuestion->id);

            $this->fail('Question tenant lain tidak boleh ditemukan oleh Guru tenant ini.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $exception) {
            $this->assertSame(Question::class, $exception->getModel());
        }

        try {
            $examComponent->call('openEditModal', $foreignExam->id);

            $this->fail('Exam tenant lain tidak boleh ditemukan oleh Guru tenant ini.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $exception) {
            $this->assertSame(Exam::class, $exception->getModel());
        }

        // Denied requests must not mutate any foreign-tenant record.
        $this->assertDatabaseHas('questions', [
            'id' => $foreignQuestion->id,
            'tenant_id' => $tenantB->id,
            'question_code' => 'FOREIGN-QUESTION',
        ]);
        $this->assertDatabaseHas('question_versions', [
            'id' => $foreignVersion->id,
            'tenant_id' => $tenantB->id,
            'stem' => 'SECRET_STEM_TENANT_B',
            'explanation' => 'SECRET_EXPLANATION_TENANT_B',
        ]);
        $this->assertDatabaseHas('question_options', [
            'id' => $foreignOption->id,
            'tenant_id' => $tenantB->id,
            'content' => 'SECRET_CORRECT_OPTION_TENANT_B',
            'is_correct' => true,
        ]);
        $this->assertDatabaseHas('exams', [
            'id' => $foreignExam->id,
            'tenant_id' => $tenantB->id,
            'title' => 'SECRET_EXAM_TENANT_B',
            'instructions' => 'SECRET_EXAM_INSTRUCTIONS_TENANT_B',
        ]);
    }

    public function test_question_and_exam_reject_revoked_permissions_after_mount(): void
    {
        $question = Livewire::actingAs($this->teacher)->test(QuestionBank::class);
        $exam = Livewire::actingAs($this->teacher)->test(ExamManagement::class);
        $before = $this->tenantQuestionExamSnapshot();

        $questionView = Permission::query()->where('name', 'question.view')->firstOrFail();
        $examManage = Permission::query()->where('name', 'exam.manage')->firstOrFail();
        $this->guruRole->permissions()->detach($questionView->id);
        $this->guruRole->permissions()->detach($examManage->id);
        $this->teacher->unsetRelation('roles');

        $question->call('openCreateModal')->assertForbidden();
        $exam->call('createExam')->assertForbidden();
        $this->assertSame($before, $this->tenantQuestionExamSnapshot());
    }

    public function test_question_and_exam_reject_revoked_assignment_after_mount(): void
    {
        $question = Livewire::actingAs($this->teacher)->test(QuestionBank::class);
        $exam = Livewire::actingAs($this->teacher)->test(ExamManagement::class);
        $before = $this->tenantQuestionExamSnapshot();

        $profile = TeacherProfile::query()
            ->where('tenant_id', $this->tenant->id)
            ->where('user_id', $this->teacher->id)
            ->firstOrFail();
        TeacherSubjectAssignment::query()
            ->where('tenant_id', $this->tenant->id)
            ->where('teacher_profile_id', $profile->id)
            ->update(['status' => 'INACTIVE']);

        $question->set('search', 'must-be-denied')->assertForbidden();
        $exam->call('createExam')->assertForbidden();
        $this->assertSame($before, $this->tenantQuestionExamSnapshot());
    }

    public function test_question_and_exam_reject_deleted_teacher_profile_after_mount(): void
    {
        $question = Livewire::actingAs($this->teacher)->test(QuestionBank::class);
        $exam = Livewire::actingAs($this->teacher)->test(ExamManagement::class);
        $before = $this->tenantQuestionExamSnapshot();

        TeacherProfile::query()
            ->where('tenant_id', $this->tenant->id)
            ->where('user_id', $this->teacher->id)
            ->firstOrFail()
            ->delete();

        $question->call('openCreateModal')->assertForbidden();
        $exam->call('createExam')->assertForbidden();
        $this->assertSame($before, $this->tenantQuestionExamSnapshot());
    }

    public function test_question_and_exam_reject_soft_deleted_user_after_mount(): void
    {
        $question = Livewire::actingAs($this->teacher)->test(QuestionBank::class);
        $exam = Livewire::actingAs($this->teacher)->test(ExamManagement::class);
        $before = $this->tenantQuestionExamSnapshot();

        DB::table('users')
            ->where('id', $this->teacher->id)
            ->where('tenant_id', $this->tenant->id)
            ->update(['deleted_at' => now()]);

        $question->call('openCreateModal')->assertForbidden();
        $exam->call('createExam')->assertForbidden();
        $this->assertSame($before, $this->tenantQuestionExamSnapshot());
    }

    public function test_question_and_exam_component_boot_rejects_tenant_context_change_after_mount(): void
    {
        $question = Livewire::actingAs($this->teacher)->test(QuestionBank::class);
        $exam = Livewire::actingAs($this->teacher)->test(ExamManagement::class);
        $before = $this->tenantQuestionExamSnapshot();

        $tenantB = Tenant::create([
            'name' => 'Tenant Context Changed',
            'code' => 'MUG04',
            'status' => 'ACTIVE',
        ]);
        app(TenantContext::class)->setTenantId($tenantB->id);

        // Invoke the same boot boundary on the already-mounted component
        // instances. A real Livewire HTTP request runs IdentifyTenant first,
        // which deliberately re-establishes the context from the authenticated
        // user's tenant and ignores client-controlled tenant overrides.
        foreach ([$question->instance(), $exam->instance()] as $component) {
            try {
                $component->boot();
                $this->fail('Component Guru harus menolak tenant context yang berbeda.');
            } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
                $this->assertSame(403, $exception->getStatusCode());
            }
        }
        $this->assertSame($before, $this->tenantQuestionExamSnapshot());
    }

    public function test_http_livewire_update_rebuilds_tenant_context_and_rejects_stale_tenant_identity(): void
    {
        $this->actingAs($this->teacher);

        // These real page requests exercise the auth, tenant and license middleware
        // used for the initial full-page Livewire component mount.
        $questionPage = $this->get('/admin/questions')->assertOk();
        $questionSnapshot = $this->extractLivewireSnapshot($questionPage);
        $this->assertSame(
            (string) $this->tenant->id,
            (string) app(TenantContext::class)->getTenantId()
        );

        $examPage = $this->get('/admin/exams')->assertOk();
        $examSnapshot = $this->extractLivewireSnapshot($examPage);
        $this->assertSame(
            (string) $this->tenant->id,
            (string) app(TenantContext::class)->getTenantId()
        );

        $before = $this->tenantQuestionExamSnapshot();
        $tenantB = Tenant::create([
            'name' => 'Tenant Reassignment Target',
            'code' => 'MUG05',
            'status' => 'ACTIVE',
        ]);

        // A new HTTP request starts with no request-local tenant context.
        // The component boundary must derive it from the authenticated user.
        app(TenantContext::class)->setTenantId(null);
        $questionUpdate = $this->postLivewireUpdate(
            $questionSnapshot,
            ['search' => 'http-lifecycle-question']
        )->assertOk();
        $questionSnapshot = $questionUpdate->json('components.0.snapshot');
        $this->assertIsString($questionSnapshot);
        $this->assertSame(
            (string) $this->tenant->id,
            (string) app(TenantContext::class)->getTenantId()
        );

        app(TenantContext::class)->setTenantId(null);
        $examUpdate = $this->postLivewireUpdate(
            $examSnapshot,
            ['search' => 'http-lifecycle-exam']
        )->assertOk();
        $examSnapshot = $examUpdate->json('components.0.snapshot');
        $this->assertIsString($examSnapshot);
        $this->assertSame(
            (string) $this->tenant->id,
            (string) app(TenantContext::class)->getTenantId()
        );

        // Persisted tenant reassignment invalidates the original authenticated
        // identity. Subsequent real Livewire HTTP updates must fail closed.
        DB::table('users')
            ->where('id', $this->teacher->id)
            ->where('tenant_id', $this->tenant->id)
            ->update(['tenant_id' => $tenantB->id]);

        app(TenantContext::class)->setTenantId(null);
        $this->postLivewireUpdate(
            $questionSnapshot,
            ['search' => 'must-be-denied-after-tenant-reassignment']
        )->assertForbidden();

        app(TenantContext::class)->setTenantId(null);
        $this->postLivewireUpdate(
            $examSnapshot,
            ['search' => 'must-be-denied-after-tenant-reassignment']
        )->assertForbidden();

        $this->assertDatabaseHas('users', [
            'id' => $this->teacher->id,
            'tenant_id' => $tenantB->id,
        ]);
        $this->assertSame($before, $this->tenantQuestionExamSnapshot());
    }

    public function test_question_and_exam_reject_revoked_guru_role_after_mount(): void
    {
        $question = Livewire::actingAs($this->teacher)->test(QuestionBank::class);
        $exam = Livewire::actingAs($this->teacher)->test(ExamManagement::class);
        $before = $this->tenantQuestionExamSnapshot();

        $this->teacher->roles()->detach($this->guruRole->id);
        $this->teacher->unsetRelation('roles');

        $question->call('openCreateModal')->assertForbidden();
        $exam->call('createExam')->assertForbidden();
        $this->assertSame($before, $this->tenantQuestionExamSnapshot());
    }

    public function test_guru_without_exactly_one_tenant_local_teacher_profile_is_denied(): void
    {
        $unassigned = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Guru Tanpa Profil',
            'email' => 'guru.tanpa.profil@mug.test',
            'password' => bcrypt('password123'),
        ]);
        $unassigned->roles()->attach($this->guruRole->id);

        $this->actingAs($unassigned)->get('/guru/dashboard')->assertForbidden();
    }

    public function test_dashboard_does_not_expose_assignments_from_another_tenant(): void
    {
        $tenantB = Tenant::create([
            'name' => 'Madrasah Tenant B',
            'code' => 'MTB01',
            'status' => 'ACTIVE',
        ]);

        app(TenantContext::class)->setTenantId($tenantB->id);
        $teacherB = User::create([
            'tenant_id' => $tenantB->id,
            'name' => 'Guru Tenant B',
            'email' => 'guru.tenantb@mug.test',
            'password' => bcrypt('password123'),
        ]);
        $teacherB->roles()->attach($this->guruRole->id);
        $this->createTeacherExamScope($tenantB, $teacherB, [], 'FOREIGN');

        app(TenantContext::class)->setTenantId($this->tenant->id);

        $response = $this->actingAs($this->teacher)->get('/guru/dashboard');

        $response->assertOk()
            ->assertSee('Mata Pelajaran PORTAL')
            ->assertDontSee('Mata Pelajaran FOREIGN')
            ->assertDontSee('Guru Tenant B');
    }

    public function test_teacher_can_update_only_contact_phone_and_change_is_audited(): void
    {
        $profile = TeacherProfile::query()
            ->where('tenant_id', $this->tenant->id)
            ->where('user_id', $this->teacher->id)
            ->firstOrFail();

        $this->actingAs($this->teacher);

        Livewire::test(TeacherProfilePage::class)
            ->set('phone', '081299991111')
            ->call('savePhone')
            ->assertHasNoErrors()
            ->assertSee('Nomor kontak berhasil diperbarui.');

        $this->assertDatabaseHas('teacher_profiles', [
            'id' => $profile->id,
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->teacher->id,
            'phone' => '081299991111',
            'nip' => '198001012006041001',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->teacher->id,
            'tenant_id' => $this->tenant->id,
            'email' => 'guru.portal@mug.test',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->teacher->id,
            'action' => 'TEACHER_PROFILE_PHONE_UPDATED',
            'entity_type' => 'TeacherProfile',
            'entity_id' => $profile->id,
        ]);
    }

    public function test_expired_assignment_is_not_counted_as_active(): void
    {
        $profile = TeacherProfile::query()
            ->where('tenant_id', $this->tenant->id)
            ->where('user_id', $this->teacher->id)
            ->firstOrFail();

        TeacherSubjectAssignment::query()
            ->where('tenant_id', $this->tenant->id)
            ->where('teacher_profile_id', $profile->id)
            ->firstOrFail()
            ->update(['ends_at' => now()->subDay()->toDateString()]);

        $this->actingAs($this->teacher);

        Livewire::test(TeacherLearning::class)
            ->assertSee('Masa Berlaku Berakhir')
            ->assertSee('0 penugasan aktif');

        $this->get('/guru/dashboard')
            ->assertOk()
            ->assertSee('Belum ada penugasan aktif');
    }

    public function test_role_revocation_is_rechecked_for_an_existing_authenticated_session(): void
    {
        $this->actingAs($this->teacher);

        // Keep the already-authenticated User instance while revoking its DB role.
        $this->teacher->roles()->detach($this->guruRole->id);

        $this->get('/guru/dashboard')->assertForbidden();
        Livewire::actingAs($this->teacher)->test(TeacherDashboard::class)->assertForbidden();
    }

    public function test_soft_deleted_teacher_account_is_denied_even_if_session_user_object_remains(): void
    {
        $this->actingAs($this->teacher);
        DB::table('users')->where('id', $this->teacher->id)->update(['deleted_at' => now()]);

        $this->get('/guru/dashboard')->assertForbidden();
        Livewire::actingAs($this->teacher)->test(TeacherDashboard::class)->assertForbidden();
    }

    public function test_teacher_portal_access_rejects_mismatched_tenant_context(): void
    {
        $tenantB = Tenant::create([
            'name' => 'Madrasah Context B',
            'code' => 'MCB01',
            'status' => 'ACTIVE',
        ]);

        app(TenantContext::class)->setTenantId($tenantB->id);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        app(\App\Support\Security\TeacherPortalAccess::class)->profile($this->teacher);
    }

    public function test_curriculum_context_uses_only_registered_tenant_owned_versions_and_shows_unverified_source_state(): void
    {
        $curriculum = MultiCurriculum::create([
            'tenant_id' => $this->tenant->id,
            'code' => 'MERDEKA',
            'name' => 'Kurikulum Merdeka Terintegrasi KBC',
            'status' => 'ACTIVE',
        ]);

        $version = MultiCurriculumVersion::create([
            'tenant_id' => $this->tenant->id,
            'multi_curriculum_id' => $curriculum->id,
            'version_code' => 'KURMER-2026',
            'title' => 'Kurikulum Merdeka 2026',
            'effective_year' => 2026,
            'status' => 'ACTIVE',
        ]);

        $structure = MultiCurriculumStructure::create([
            'tenant_id' => $this->tenant->id,
            'multi_curriculum_version_id' => $version->id,
            'node_type' => 'FASE',
            'code' => 'FASE-A',
            'title' => 'Fase A',
            'status' => 'ACTIVE',
        ]);

        foreach ([
            ['CP', 'CP-UJI-01', 'Capaian Pembelajaran uji'],
            ['TP', 'TP-UJI-01', 'Tujuan Pembelajaran uji'],
            ['ATP', 'ATP-UJI-01', 'Alur Tujuan Pembelajaran uji'],
            ['KBC_CINTA', 'KBC-UJI-01', 'Pemetaan KBC uji'],
        ] as [$type, $code, $description]) {
            MultiLearningOutcome::create([
                'tenant_id' => $this->tenant->id,
                'multi_curriculum_structure_id' => $structure->id,
                'multi_curriculum_version_id' => $version->id,
                'code' => $code,
                'outcome_type' => $type,
                'description' => $description,
                'status' => 'ACTIVE',
            ]);
        }

        TenantCurriculumRegistration::create([
            'tenant_id' => $this->tenant->id,
            'multi_curriculum_version_id' => $version->id,
            'curriculum_owner_id' => $this->tenant->id,
            'status' => 'ACTIVE',
            'registered_at' => now(),
        ]);

        $this->actingAs($this->teacher);

        Livewire::test(TeacherLearning::class)
            ->assertSee('Kurikulum Merdeka Terintegrasi KBC')
            ->assertSee('KURMER-2026')
            ->assertSee('CP-UJI-01')
            ->assertSee('TP-UJI-01')
            ->assertSee('ATP-UJI-01')
            ->assertSee('KBC-UJI-01')
            ->assertSee('Cinta Allah dan Rasul-Nya')
            ->assertSee('Pembelajaran Mendalam')
            ->assertSee('Delapan Dimensi Profil Lulusan')
            ->assertSee('belum terverifikasi sumber resminya');
    }
    private function extractLivewireSnapshot($response): string
    {
        $this->assertSame(
            1,
            preg_match('/wire:snapshot="([^"]+)"/', $response->getContent(), $matches),
            'Full-page Livewire response must contain a component snapshot.'
        );

        $snapshot = html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $this->assertIsArray(json_decode($snapshot, true));

        return $snapshot;
    }

    private function postLivewireUpdate(string $snapshot, array $updates = [])
    {
        return $this->withHeaders(['X-Livewire' => 'true'])->postJson(
            app('livewire')->getUpdateUri(),
            [
                'components' => [
                    [
                        'snapshot' => $snapshot,
                        'calls' => [],
                        'updates' => $updates,
                    ],
                ],
            ]
        );
    }

    private function tenantQuestionExamSnapshot(): array
    {
        $tables = ['questions', 'question_versions', 'question_options', 'assessments', 'exams'];
        $snapshot = [];

        foreach ($tables as $table) {
            $snapshot[$table] = DB::table($table)
                ->where('tenant_id', $this->tenant->id)
                ->orderBy('id')
                ->get()
                ->map(fn ($row) => (array) $row)
                ->all();
        }

        return $snapshot;
    }

}
