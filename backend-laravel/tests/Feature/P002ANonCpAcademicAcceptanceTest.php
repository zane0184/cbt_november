<?php

namespace Tests\Feature;

use App\Contexts\TenantContext;
use App\Livewire\Admin\AcademicManagement;
use App\Livewire\Admin\CurriculumRegistryManagement;
use App\Livewire\Admin\QuestionBank;
use App\Livewire\Admin\StudentRombelManagement;
use App\Livewire\Admin\TeacherSubjectAssignmentManagement;
use App\Livewire\Admin\UserManagement;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\CurriculumReferenceSource;
use App\Models\GradeLevel;
use App\Models\MultiCurriculumStructure;
use App\Models\MultiCurriculumVersion;
use App\Models\MultiLearningOutcome;
use App\Models\Permission;
use App\Models\Question;
use App\Models\QuestionVersion;
use App\Models\QuestionVersionCurriculumMapping;
use App\Models\QuestionVersionTenantOutcomeMapping;
use App\Models\Role;
use App\Models\Rombel;
use App\Models\Semester;
use App\Models\StudentProfile;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\TeacherSubjectAssignment;
use App\Models\Tenant;
use App\Models\TenantCurriculumRegistration;
use App\Models\TenantCurriculumSubjectMapping;
use App\Models\TenantLearningOutcome;
use App\Models\User;
use App\Services\AuditService;
use App\Services\TenantAcademicBootstrapper;
use App\Services\TeacherPortal\TeacherLearningOutcomeService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class P002ANonCpAcademicAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $this->tenant = Tenant::create([
            'name' => 'Madrasah Acceptance P0-02A',
            'code' => 'P002A01',
        ]);
        $this->setTenantContext($this->tenant->id);
        app(TenantAcademicBootstrapper::class)->bootstrap($this->tenant->id);

        $adminRole = Role::query()->where('name', 'ADMIN_MADRASAH')->firstOrFail();
        foreach (['user.manage', 'academic.manage', 'curriculum.registry.manage'] as $permissionName) {
            $permission = Permission::firstOrCreate(
                ['name' => $permissionName],
                ['label' => $permissionName]
            );
            $adminRole->permissions()->syncWithoutDetaching([$permission->id]);
        }

        $this->admin = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Admin Acceptance',
            'username' => 'admin.p002a',
            'email' => 'admin.p002a@example.test',
            'password' => bcrypt('Acceptance-Password-2026'),
        ]);
        $this->admin->roles()->sync([$adminRole->id]);
        $this->actingAs($this->admin);
    }

    /** @test */
    public function admin_completes_non_cp_academic_chain_and_duplicate_operations_are_idempotent(): void
    {
        $chain = $this->createAcademicChain(true);

        $this->assertDatabaseHas('users', [
            'id' => $chain['teacher']->id,
            'tenant_id' => $this->tenant->id,
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $chain['student']->id,
            'tenant_id' => $this->tenant->id,
        ]);
        $this->assertDatabaseHas('rombels', [
            'id' => $chain['rombel']->id,
            'tenant_id' => $this->tenant->id,
        ]);
        $this->assertTrue($chain['studentProfile']->rombels()->whereKey($chain['rombel']->id)->exists());
        $this->assertDatabaseHas('subjects', [
            'id' => $chain['subject']->id,
            'tenant_id' => $this->tenant->id,
            'code' => 'MAT',
        ]);
        $this->assertDatabaseHas('tenant_curriculum_registrations', [
            'id' => $chain['registration']->id,
            'tenant_id' => $this->tenant->id,
            'multi_curriculum_version_id' => $chain['version']->id,
            'status' => 'ACTIVE',
        ]);
        $this->assertDatabaseHas('tenant_curriculum_subject_mappings', [
            'id' => $chain['mapping']->id,
            'tenant_id' => $this->tenant->id,
            'subject_id' => $chain['subject']->id,
            'multi_curriculum_structure_id' => $chain['subjectStructure']->id,
            'status' => 'ACTIVE',
        ]);
        $this->assertDatabaseHas('teacher_subject_assignments', [
            'id' => $chain['assignment']->id,
            'tenant_id' => $this->tenant->id,
            'teacher_profile_id' => $chain['teacherProfile']->id,
            'subject_id' => $chain['subject']->id,
            'rombel_id' => $chain['rombel']->id,
        ]);

        $this->assertSame(1, TenantCurriculumRegistration::query()
            ->where('tenant_id', $this->tenant->id)
            ->where('multi_curriculum_version_id', $chain['version']->id)
            ->count());
        $this->assertSame(1, TenantCurriculumSubjectMapping::query()
            ->where('tenant_id', $this->tenant->id)
            ->where('subject_id', $chain['subject']->id)
            ->where('multi_curriculum_structure_id', $chain['subjectStructure']->id)
            ->count());
        $this->assertSame(1, TeacherSubjectAssignment::query()
            ->where('tenant_id', $this->tenant->id)
            ->where('teacher_profile_id', $chain['teacherProfile']->id)
            ->where('subject_id', $chain['subject']->id)
            ->where('rombel_id', $chain['rombel']->id)
            ->count());

        foreach ([
            'TEACHER_PROFILE_CREATED',
            'STUDENT_PROFILE_CREATED',
            'STUDENT_ADDED_TO_ROMBEL',
            'USER_CREATED',
            'CURRICULUM_VERSION_REGISTERED',
            'CURRICULUM_SUBJECT_MAPPED',
            'TEACHER_SUBJECT_ASSIGNMENT_CREATED',
        ] as $action) {
            $this->assertDatabaseHas('audit_logs', [
                'tenant_id' => $this->tenant->id,
                'action' => $action,
            ]);
        }

        Livewire::test(StudentRombelManagement::class)
            ->set('selectedStudentProfileId', $chain['studentProfile']->id)
            ->set('selectedRombelId', $chain['rombel']->id)
            ->call('addStudentToRombel')
            ->assertHasErrors();

        Livewire::test(TeacherSubjectAssignmentManagement::class)
            ->set('selectedTeacherProfileId', $chain['teacherProfile']->id)
            ->set('selectedSubjectId', $chain['subject']->id)
            ->set('selectedRombelId', $chain['rombel']->id)
            ->set('selectedSemesterId', $chain['semester']->id)
            ->set('selectedRegistrationId', $chain['registration']->id)
            ->set('startsAt', now()->subDay()->toDateString())
            ->set('endsAt', now()->addMonths(6)->toDateString())
            ->call('createAssignment')
            ->assertHasErrors();

        $this->assertSame(1, TeacherSubjectAssignment::query()
            ->where('tenant_id', $this->tenant->id)
            ->where('teacher_profile_id', $chain['teacherProfile']->id)
            ->where('subject_id', $chain['subject']->id)
            ->where('rombel_id', $chain['rombel']->id)
            ->count());

        $chain['teacher']->forceFill(['is_active' => false])->save();
        Livewire::test(TeacherSubjectAssignmentManagement::class)
            ->set('selectedTeacherProfileId', $chain['teacherProfile']->id)
            ->set('selectedSubjectId', $chain['subject']->id)
            ->set('selectedRombelId', $chain['rombel']->id)
            ->set('selectedSemesterId', $chain['semester']->id)
            ->set('selectedRegistrationId', $chain['registration']->id)
            ->set('startsAt', now()->subDay()->toDateString())
            ->set('endsAt', now()->addMonths(6)->toDateString())
            ->call('createAssignment')
            ->assertHasErrors(['selectedTeacherProfileId']);
        $chain['teacher']->forceFill(['is_active' => true, 'deleted_at' => now()])->save();
        Livewire::test(TeacherSubjectAssignmentManagement::class)
            ->set('selectedTeacherProfileId', $chain['teacherProfile']->id)
            ->set('selectedSubjectId', $chain['subject']->id)
            ->set('selectedRombelId', $chain['rombel']->id)
            ->set('selectedSemesterId', $chain['semester']->id)
            ->set('selectedRegistrationId', $chain['registration']->id)
            ->set('startsAt', now()->subDay()->toDateString())
            ->set('endsAt', now()->addMonths(6)->toDateString())
            ->call('createAssignment')
            ->assertHasErrors(['selectedTeacherProfileId']);
        $chain['teacher']->forceFill(['deleted_at' => null])->save();

        Livewire::test(UserManagement::class)
            ->set('name', 'Guru Duplikat NIP')
            ->set('username', 'guru.duplicate.nip')
            ->set('password', 'Acceptance-Password-2026')
            ->set('selectedRole', 'GURU')
            ->set('employmentStatus', 'PNS')
            ->set('nip', '198001012026110001')
            ->call('createUser')
            ->assertHasErrors(['nip']);

        $this->assertDatabaseMissing('users', [
            'tenant_id' => $this->tenant->id,
            'username' => 'guru.duplicate.nip',
        ]);

        Livewire::test(UserManagement::class)
            ->set('name', 'Siswa Duplikat NISN')
            ->set('username', 'siswa.duplicate.nisn')
            ->set('password', 'Acceptance-Password-2026')
            ->set('selectedRole', 'SISWA')
            ->set('nisn', '0001234567')
            ->call('createUser')
            ->assertHasErrors(['nisn']);

        $this->assertDatabaseMissing('users', [
            'tenant_id' => $this->tenant->id,
            'username' => 'siswa.duplicate.nisn',
        ]);
    }

    /** @test */
    public function tenant_isolation_and_role_permission_boundaries_reject_cross_tenant_mutations(): void
    {
        $chain = $this->createAcademicChain();

        $foreignTenant = Tenant::create([
            'name' => 'Madrasah Tenant B P0-02A',
            'code' => 'P002A02',
        ]);
        $this->setTenantContext($foreignTenant->id);
        $studentRole = Role::query()->where('name', 'SISWA')->firstOrFail();
        $foreignUser = User::create([
            'tenant_id' => $foreignTenant->id,
            'name' => 'Siswa Tenant B',
            'username' => 'siswa.tenantb',
            'password' => bcrypt('Acceptance-Password-2026'),
        ]);
        $foreignUser->roles()->sync([$studentRole->id]);
        $foreignProfile = StudentProfile::create([
            'user_id' => $foreignUser->id,
            'full_name' => 'Siswa Tenant B',
            'nisn' => '0098765432',
        ]);

        $this->actingAs($this->admin);
        Livewire::test(StudentRombelManagement::class)
            ->set('selectedStudentProfileId', $foreignProfile->id)
            ->set('selectedRombelId', $chain['rombel']->id)
            ->call('addStudentToRombel')
            ->assertHasErrors(['selectedStudentProfileId']);

        $this->assertFalse($foreignProfile->rombels()->whereKey($chain['rombel']->id)->exists());
        $this->assertDatabaseMissing('rombel_student', [
            'tenant_id' => $this->tenant->id,
            'student_id' => $foreignProfile->id,
            'rombel_id' => $chain['rombel']->id,
        ]);

        $adminRole = Role::query()->where('name', 'ADMIN_MADRASAH')->firstOrFail();
        $registryPermission = Permission::query()->where('name', 'curriculum.registry.manage')->firstOrFail();
        $academicPermission = Permission::query()->where('name', 'academic.manage')->firstOrFail();

        $adminRole->permissions()->detach($registryPermission->id);
        $this->admin->unsetRelation('roles');
        $this->actingAs($this->admin);
        Livewire::test(CurriculumRegistryManagement::class)->assertForbidden();
        $adminRole->permissions()->syncWithoutDetaching([$registryPermission->id]);

        $adminRole->permissions()->detach($academicPermission->id);
        $this->admin->unsetRelation('roles');
        $this->actingAs($this->admin);
        Livewire::test(TeacherSubjectAssignmentManagement::class)->assertForbidden();
        $adminRole->permissions()->syncWithoutDetaching([$academicPermission->id]);
    }

    /** @test */
    public function account_creation_rolls_back_user_and_profile_when_audit_fails_mid_transaction(): void
    {
        $this->actingAs($this->admin);

        $audit = Mockery::mock(AuditService::class);
        $audit->shouldReceive('log')->once()->andThrow(new RuntimeException('forced P0-02A audit failure'));
        $this->app->instance(AuditService::class, $audit);

        try {
            Livewire::test(UserManagement::class)
                ->set('name', 'Guru Rollback')
                ->set('username', 'guru.rollback.p002a')
                ->set('password', 'Acceptance-Password-2026')
                ->set('selectedRole', 'GURU')
                ->set('employmentStatus', 'PNS')
                ->set('nip', '198001012026110099')
                ->call('createUser');

            $this->fail('Expected the injected audit failure to escape the Livewire action.');
        } catch (RuntimeException $exception) {
            $this->assertSame('forced P0-02A audit failure', $exception->getMessage());
        }

        $this->assertDatabaseMissing('users', [
            'tenant_id' => $this->tenant->id,
            'username' => 'guru.rollback.p002a',
        ]);
        $this->assertDatabaseMissing('teacher_profiles', [
            'tenant_id' => $this->tenant->id,
            'full_name' => 'Guru Rollback',
        ]);
    }

    /** @test */
    public function pending_cp_blocks_tp_atp_question_creation_and_publication_without_partial_rows(): void
    {
        $chain = $this->createAcademicChain();
        $teacher = $chain['teacher'];
        $assignment = $chain['assignment'];
        $subject = $chain['subject'];

        $pendingSource = CurriculumReferenceSource::query()
            ->where('tenant_id', Tenant::SYSTEM_TENANT_ID)
            ->where('document_number', 'Kepdirjen Pendis 9941 Tahun 2025')
            ->firstOrFail();
        $this->assertSame('PENDING', $pendingSource->verification_status);
        $this->assertNull($pendingSource->checksum_sha256);

        // Test-only provenance fixture: a placeholder CP row linked to the real
        // seeded PENDING source. It contains no official CP content and is never
        // used as evidence of an official CP artifact.
        $pendingCp = MultiLearningOutcome::create([
            'tenant_id' => Tenant::SYSTEM_TENANT_ID,
            'multi_curriculum_structure_id' => $chain['subjectStructure']->id,
            'multi_curriculum_version_id' => $chain['version']->id,
            'code' => 'TEST_ONLY_PENDING_CP_GUARD',
            'outcome_type' => 'CP',
            'description' => 'Test fixture only; no official CP content.',
            'status' => 'ACTIVE',
            'source_id' => $pendingSource->id,
            'source_locator' => 'P0-02A test-only pending provenance fixture',
            'verification_status' => 'PENDING',
            'source_checksum' => null,
            'is_official' => true,
            'is_editable' => false,
            'metadata' => ['test_fixture_only' => true, 'not_official_cp_content' => true],
        ]);

        $parentTp = $this->createPendingParentTp($chain, $pendingCp);
        $this->actingAs($teacher);
        $beforeOutcomeCount = TenantLearningOutcome::query()->where('tenant_id', $this->tenant->id)->count();

        try {
            app(TeacherLearningOutcomeService::class)->createTp(
                $assignment->id,
                $pendingCp->id,
                'TP-PENDING-01',
                'TP tidak boleh dibuat',
                'Test only',
                [],
                [],
                [],
                [],
                []
            );
            $this->fail('TP creation must reject a PENDING CP.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('checksum', strtolower(implode(' ', $exception->errors()['parentCpId'] ?? [])));
        }

        $this->assertSame($beforeOutcomeCount, TenantLearningOutcome::query()
            ->where('tenant_id', $this->tenant->id)->count());

        try {
            app(TeacherLearningOutcomeService::class)->createAtp(
                $assignment->id,
                $parentTp->id,
                'ATP-PENDING-01',
                'ATP tidak boleh dibuat',
                'Test only',
                1
            );
            $this->fail('ATP creation must reject a TP whose parent CP is PENDING.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('checksum', strtolower(implode(' ', $exception->errors()['parentTpId'] ?? [])));
        }

        $this->assertSame($beforeOutcomeCount, TenantLearningOutcome::query()
            ->where('tenant_id', $this->tenant->id)->count());

        try {
            app(TeacherLearningOutcomeService::class)->publish($parentTp->id);
            $this->fail('TP publication must reject a PENDING CP.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('provenance checksum', strtolower(implode(' ', $exception->errors()['outcome'] ?? [])));
        }

        $this->assertDatabaseHas('tenant_learning_outcomes', [
            'id' => $parentTp->id,
            'status' => 'DRAFT',
        ]);

        $parentAtp = TenantLearningOutcome::create([
            'tenant_id' => $this->tenant->id,
            'tenant_curriculum_registration_id' => $chain['registration']->id,
            'multi_curriculum_version_id' => $chain['version']->id,
            'curriculum_owner_id' => Tenant::SYSTEM_TENANT_ID,
            'subject_id' => $chain['subject']->id,
            'multi_curriculum_structure_id' => $chain['subjectStructure']->id,
            'teacher_subject_assignment_id' => $assignment->id,
            'academic_year_id' => $assignment->academic_year_id,
            'semester_id' => $assignment->semester_id,
            'parent_cp_id' => $pendingCp->id,
            'parent_tp_id' => $parentTp->id,
            'outcome_type' => 'ATP',
            'code' => 'ATP-TEST-PENDING',
            'title' => 'ATP fixture untuk CP PENDING',
            'description' => 'Test-only ATP; bukan konten akademik resmi.',
            'sequence_number' => 1,
            'version_code' => '1',
            'is_current' => true,
            'status' => 'DRAFT',
            'author_user_id' => $teacher->id,
            'metadata' => ['test_fixture_only' => true],
        ]);

        try {
            app(TeacherLearningOutcomeService::class)->publish($parentAtp->id);
            $this->fail('ATP publication must reject a PENDING CP.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('provenance checksum', strtolower(implode(' ', $exception->errors()['outcome'] ?? [])));
        }

        $this->assertDatabaseHas('tenant_learning_outcomes', [
            'id' => $parentAtp->id,
            'status' => 'DRAFT',
        ]);

        $this->actingAs($teacher);
        $questionCountBefore = Question::query()->where('tenant_id', $this->tenant->id)->count();
        $versionCountBefore = QuestionVersion::query()->where('tenant_id', $this->tenant->id)->count();

        Livewire::test(QuestionBank::class)
            ->set('questionCode', 'QUESTION-PENDING-CP')
            ->set('questionType', 'ESSAY')
            ->set('selectedAssignmentId', $assignment->id)
            ->set('selectedSubjectId', $subject->id)
            ->set('selectedCpIds', [$pendingCp->id])
            ->set('selectedOutcomeIds', [(string) \Illuminate\Support\Str::uuid()])
            ->set('stem', 'Soal uji provenance CP PENDING')
            ->set('defaultPoints', 10)
            ->call('saveQuestion')
            ->assertHasErrors(['selectedCpIds']);

        $this->assertSame($questionCountBefore, Question::query()->where('tenant_id', $this->tenant->id)->count());
        $this->assertSame($versionCountBefore, QuestionVersion::query()->where('tenant_id', $this->tenant->id)->count());

        $question = Question::create([
            'owner_type' => 'SUBJECT',
            'question_code' => 'QUESTION-PENDING-PUBLISH',
            'status' => 'DRAFT',
            'subject_id' => $subject->id,
            'created_by' => $teacher->id,
            'updated_by' => $teacher->id,
        ]);
        $questionVersion = QuestionVersion::create([
            'question_id' => $question->id,
            'major_version' => 1,
            'minor_version' => 0,
            'question_type' => 'ESSAY',
            'stem' => 'Test-only draft for publication gate',
            'default_points' => 10,
            'status' => 'DRAFT',
            'is_current' => true,
            'created_by' => $teacher->id,
        ]);
        QuestionVersionCurriculumMapping::create([
            'tenant_id' => $this->tenant->id,
            'question_version_id' => $questionVersion->id,
            'teacher_subject_assignment_id' => $assignment->id,
            'tenant_curriculum_registration_id' => $assignment->tenant_curriculum_registration_id,
            'multi_curriculum_version_id' => $assignment->multi_curriculum_version_id,
            'curriculum_owner_id' => $assignment->curriculum_owner_id,
            'multi_learning_outcome_id' => $pendingCp->id,
            'academic_year_id' => $assignment->academic_year_id,
            'semester_id' => $assignment->semester_id,
            'mapping_role' => 'CP',
            'weight' => 1,
            'is_primary' => true,
            'created_by' => $teacher->id,
            'metadata' => ['test_fixture_only' => true],
        ]);
        QuestionVersionTenantOutcomeMapping::create([
            'tenant_id' => $this->tenant->id,
            'question_version_id' => $questionVersion->id,
            'teacher_subject_assignment_id' => $assignment->id,
            'tenant_curriculum_registration_id' => $assignment->tenant_curriculum_registration_id,
            'multi_curriculum_version_id' => $assignment->multi_curriculum_version_id,
            'curriculum_owner_id' => $assignment->curriculum_owner_id,
            'tenant_learning_outcome_id' => $parentTp->id,
            'academic_year_id' => $assignment->academic_year_id,
            'semester_id' => $assignment->semester_id,
            'mapping_role' => 'TP',
            'weight' => 1,
            'is_primary' => true,
            'created_by' => $teacher->id,
            'metadata' => ['test_fixture_only' => true],
        ]);

        try {
            (new QuestionBank())->publishQuestion($question->id);
            $this->fail('Question publication must reject a PENDING CP.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertSame(409, $exception->getStatusCode());
        }

        $this->assertDatabaseHas('questions', [
            'id' => $question->id,
            'status' => 'DRAFT',
        ]);
        $this->assertDatabaseHas('question_versions', [
            'id' => $questionVersion->id,
            'status' => 'DRAFT',
        ]);
        $this->assertDatabaseMissing('audit_logs', [
            'tenant_id' => $this->tenant->id,
            'action' => 'PUBLISH_QUESTION',
            'entity_id' => $question->id,
        ]);
    }

    private function createAcademicChain(bool $forceAssignmentAuditFailure = false): array
    {
        $this->actingAs($this->admin);

        Livewire::test(UserManagement::class)
            ->set('name', 'Guru P0-02A')
            ->set('username', 'guru.p002a')
            ->set('email', 'guru.p002a@example.test')
            ->set('password', 'Acceptance-Password-2026')
            ->set('selectedRole', 'GURU')
            ->set('employmentStatus', 'PNS')
            ->set('nip', '198001012026110001')
            ->call('createUser')
            ->assertHasNoErrors();

        Livewire::test(UserManagement::class)
            ->set('name', 'Siswa P0-02A')
            ->set('username', 'siswa.p002a')
            ->set('password', 'Acceptance-Password-2026')
            ->set('selectedRole', 'SISWA')
            ->set('nisn', '0001234567')
            ->call('createUser')
            ->assertHasNoErrors();

        $teacher = User::query()->where('tenant_id', $this->tenant->id)->where('username', 'guru.p002a')->firstOrFail();
        $student = User::query()->where('tenant_id', $this->tenant->id)->where('username', 'siswa.p002a')->firstOrFail();
        $teacherProfile = TeacherProfile::query()->where('tenant_id', $this->tenant->id)->where('user_id', $teacher->id)->firstOrFail();
        $studentProfile = StudentProfile::query()->where('tenant_id', $this->tenant->id)->where('user_id', $student->id)->firstOrFail();

        $guruRole = Role::query()->where('name', 'GURU')->firstOrFail();
        foreach (['curriculum.manage', 'question.view', 'question.manage'] as $permissionName) {
            $permission = Permission::firstOrCreate(['name' => $permissionName], ['label' => $permissionName]);
            $guruRole->permissions()->syncWithoutDetaching([$permission->id]);
        }

        $academicYear = AcademicYear::query()
            ->where('tenant_id', $this->tenant->id)
            ->where('is_active', true)
            ->firstOrFail();
        $gradeLevel = GradeLevel::query()
            ->where('tenant_id', $this->tenant->id)
            ->where('code', 'MI_1')
            ->firstOrFail();
        $semester = Semester::query()
            ->where('tenant_id', $this->tenant->id)
            ->where('academic_year_id', $academicYear->id)
            ->where('is_active', true)
            ->firstOrFail();

        Livewire::test(AcademicManagement::class)
            ->set('rombelName', 'Kelas 1 P0-02A')
            ->set('selectedAcademicYearId', $academicYear->id)
            ->set('selectedGradeLevelId', $gradeLevel->id)
            ->call('createRombel')
            ->assertHasNoErrors();
        $rombel = Rombel::query()->where('tenant_id', $this->tenant->id)->where('name', 'Kelas 1 P0-02A')->firstOrFail();

        Livewire::test(StudentRombelManagement::class)
            ->set('selectedStudentProfileId', $studentProfile->id)
            ->set('selectedRombelId', $rombel->id)
            ->call('addStudentToRombel')
            ->assertHasNoErrors();

        Livewire::test(AcademicManagement::class)
            ->set('subjectCode', 'MAT')
            ->set('subjectName', 'Matematika')
            ->set('subjectShortName', 'MTK')
            ->call('createSubject')
            ->assertHasNoErrors();
        $subject = Subject::query()->where('tenant_id', $this->tenant->id)->where('code', 'MAT')->firstOrFail();

        $version = MultiCurriculumVersion::query()
            ->where('tenant_id', Tenant::SYSTEM_TENANT_ID)
            ->where('version_code', 'KMA-1503-2025')
            ->where('status', 'ACTIVE')
            ->firstOrFail();
        $subjectStructure = MultiCurriculumStructure::query()
            ->where('tenant_id', Tenant::SYSTEM_TENANT_ID)
            ->where('multi_curriculum_version_id', $version->id)
            ->where('node_type', 'SUBJECT')
            ->where('code', 'FASE_A_MATEMATIKA')
            ->firstOrFail();

        Livewire::test(CurriculumRegistryManagement::class)
            ->call('registerVersion', $version->id)
            ->assertHasNoErrors();
        Livewire::test(CurriculumRegistryManagement::class)
            ->call('registerVersion', $version->id)
            ->assertHasNoErrors();
        $registration = TenantCurriculumRegistration::query()
            ->where('tenant_id', $this->tenant->id)
            ->where('multi_curriculum_version_id', $version->id)
            ->where('status', 'ACTIVE')
            ->firstOrFail();

        Livewire::test(CurriculumRegistryManagement::class)
            ->set('selectedRegistrationId', $registration->id)
            ->set('selectedSubjectId', $subject->id)
            ->set('selectedStructureId', $subjectStructure->id)
            ->call('mapSubject')
            ->assertHasNoErrors();
        Livewire::test(CurriculumRegistryManagement::class)
            ->set('selectedRegistrationId', $registration->id)
            ->set('selectedSubjectId', $subject->id)
            ->set('selectedStructureId', $subjectStructure->id)
            ->call('mapSubject')
            ->assertHasNoErrors();
        $mapping = TenantCurriculumSubjectMapping::query()
            ->where('tenant_id', $this->tenant->id)
            ->where('subject_id', $subject->id)
            ->where('multi_curriculum_structure_id', $subjectStructure->id)
            ->firstOrFail();

        $assignmentData = [
            'selectedTeacherProfileId' => $teacherProfile->id,
            'selectedSubjectId' => $subject->id,
            'selectedRombelId' => $rombel->id,
            'selectedSemesterId' => $semester->id,
            'selectedRegistrationId' => $registration->id,
            'startsAt' => now()->subDay()->toDateString(),
            'endsAt' => now()->addMonths(6)->toDateString(),
        ];

        if ($forceAssignmentAuditFailure) {
            $audit = Mockery::mock(AuditService::class);
            $audit->shouldReceive('log')->once()->andThrow(new RuntimeException('forced assignment audit failure'));
            $this->app->instance(AuditService::class, $audit);

            try {
                Livewire::test(TeacherSubjectAssignmentManagement::class)
                    ->set('selectedTeacherProfileId', $assignmentData['selectedTeacherProfileId'])
                    ->set('selectedSubjectId', $assignmentData['selectedSubjectId'])
                    ->set('selectedRombelId', $assignmentData['selectedRombelId'])
                    ->set('selectedSemesterId', $assignmentData['selectedSemesterId'])
                    ->set('selectedRegistrationId', $assignmentData['selectedRegistrationId'])
                    ->set('startsAt', $assignmentData['startsAt'])
                    ->set('endsAt', $assignmentData['endsAt'])
                    ->call('createAssignment');

                $this->fail('Expected assignment audit failure to escape the Livewire action.');
            } catch (RuntimeException $exception) {
                $this->assertSame('forced assignment audit failure', $exception->getMessage());
            }

            $this->assertSame(0, TeacherSubjectAssignment::query()
                ->where('tenant_id', $this->tenant->id)
                ->where('teacher_profile_id', $teacherProfile->id)
                ->count());
            $this->app->forgetInstance(AuditService::class);
        }

        Livewire::test(TeacherSubjectAssignmentManagement::class)
            ->set('selectedTeacherProfileId', $assignmentData['selectedTeacherProfileId'])
            ->set('selectedSubjectId', $assignmentData['selectedSubjectId'])
            ->set('selectedRombelId', $assignmentData['selectedRombelId'])
            ->set('selectedSemesterId', $assignmentData['selectedSemesterId'])
            ->set('selectedRegistrationId', $assignmentData['selectedRegistrationId'])
            ->set('startsAt', $assignmentData['startsAt'])
            ->set('endsAt', $assignmentData['endsAt'])
            ->call('createAssignment')
            ->assertHasNoErrors();

        $assignment = TeacherSubjectAssignment::query()
            ->where('tenant_id', $this->tenant->id)
            ->where('teacher_profile_id', $teacherProfile->id)
            ->where('subject_id', $subject->id)
            ->where('rombel_id', $rombel->id)
            ->firstOrFail();

        return compact(
            'teacher', 'student', 'teacherProfile', 'studentProfile',
            'academicYear', 'gradeLevel', 'semester', 'rombel', 'subject',
            'version', 'subjectStructure', 'registration', 'mapping', 'assignment'
        );
    }

    private function createPendingParentTp(array $chain, MultiLearningOutcome $pendingCp): TenantLearningOutcome
    {
        return TenantLearningOutcome::create([
            'tenant_id' => $this->tenant->id,
            'tenant_curriculum_registration_id' => $chain['registration']->id,
            'multi_curriculum_version_id' => $chain['version']->id,
            'curriculum_owner_id' => Tenant::SYSTEM_TENANT_ID,
            'subject_id' => $chain['subject']->id,
            'multi_curriculum_structure_id' => $chain['subjectStructure']->id,
            'teacher_subject_assignment_id' => $chain['assignment']->id,
            'academic_year_id' => $chain['assignment']->academic_year_id,
            'semester_id' => $chain['assignment']->semester_id,
            'parent_cp_id' => $pendingCp->id,
            'parent_tp_id' => null,
            'outcome_type' => 'TP',
            'code' => 'TP-TEST-PENDING',
            'title' => 'TP fixture untuk CP PENDING',
            'description' => 'Test-only parent TP; bukan konten akademik resmi.',
            'version_code' => '1',
            'is_current' => true,
            'status' => 'DRAFT',
            'author_user_id' => $chain['teacher']->id,
            'metadata' => ['test_fixture_only' => true],
        ]);
    }
}
