<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Semester;
use App\Models\TeacherProfile;
use App\Models\TeacherSubjectAssignment;
use App\Models\Curriculum;
use App\Models\Exam;
use App\Models\ExamParticipant;
use App\Models\GradeLevel;
use App\Models\Phase;
use App\Models\Question;
use App\Models\QuestionVersion;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Rombel;
use App\Models\StudentProfile;
use App\Models\Subject;
use App\Models\Tenant;
use App\Models\User;
use App\Livewire\Admin\ExamManagement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use Tests\Concerns\CreatesCanonicalCurriculumContext;

class ExamManagementHardeningTest extends TestCase
{
    use RefreshDatabase;
    use CreatesCanonicalCurriculumContext;

    private $tenant;
    private $teacher;
    private $student1;
    private $student2;
    private $subject;
    private $academicYear;
    private $semester;
    private $rombelA;
    private $rombelB;
    private $questionVersion1;
    private $questionVersion2;
    private $assignment;
    private array $canonicalContext = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create(['name' => 'Madrasah Hardening Test', 'code' => 'HARDEN']);
        app(\App\Contexts\TenantContext::class)->setTenantId($this->tenant->id);

        $teacherRole = Role::create(['name' => 'GURU', 'label' => 'Guru']);
        $studentRole = Role::create(['name' => 'SISWA', 'label' => 'Siswa']);
        $teacherPermissions = collect(['exam.manage', 'exam.assign', 'exam.monitor', 'exam.token', 'exam.grade', 'report.view', 'question.manage'])
            ->map(fn ($name) => \App\Models\Permission::firstOrCreate(['name' => $name], ['label' => $name]));
        $teacherRole->permissions()->sync($teacherPermissions->pluck('id')->all());

        $this->teacher = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Guru Penguji',
            'email' => 'guru.test@harden.sch.id',
            'password' => bcrypt('password123'),
        ]);
        $this->teacher->roles()->attach($teacherRole);

        $this->student1 = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Siswa Rombel A',
            'email' => 'siswa.a@harden.sch.id',
            'password' => bcrypt('password123'),
        ]);
        $this->student1->roles()->attach($studentRole);

        $this->student2 = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Siswa Rombel B',
            'email' => 'siswa.b@harden.sch.id',
            'password' => bcrypt('password123'),
        ]);
        $this->student2->roles()->attach($studentRole);

        $sp1 = StudentProfile::create(['tenant_id' => $this->tenant->id, 'user_id' => $this->student1->id, 'full_name' => 'Siswa A']);
        $sp2 = StudentProfile::create(['tenant_id' => $this->tenant->id, 'user_id' => $this->student2->id, 'full_name' => 'Siswa B']);

        $curriculum = Curriculum::create(['name' => 'K-Merdeka', 'code' => 'KM', 'is_active' => true]);
        $phase = Phase::create(['curriculum_id' => $curriculum->id, 'name' => 'Fase C', 'code' => 'C']);
        $this->academicYear = AcademicYear::create(['name' => '2024/2025', 'is_active' => true]);
        $this->semester = Semester::create(['tenant_id' => $this->tenant->id, 'academic_year_id' => $this->academicYear->id, 'term' => 'GANJIL', 'is_active' => true]);
        $gradeLevel = GradeLevel::create(['phase_id' => $phase->id, 'name' => 'Kelas 6', 'code' => '6', 'level' => 6]);

        $this->subject = Subject::create([
            'tenant_id' => $this->tenant->id,
            'code' => 'IPAS',
            'name' => 'IPAS',
            'short_name' => 'IPA',
            'status' => 'ACTIVE',
        ]);

        $this->rombelA = Rombel::create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $this->academicYear->id,
            'grade_level_id' => $gradeLevel->id,
            'name' => 'Kelas 6-A',
            'status' => 'ACTIVE',
        ]);
        $this->rombelB = Rombel::create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $this->academicYear->id,
            'grade_level_id' => $gradeLevel->id,
            'name' => 'Kelas 6-B',
            'status' => 'ACTIVE',
        ]);
        $teacherProfile = TeacherProfile::create(['tenant_id' => $this->tenant->id, 'user_id' => $this->teacher->id, 'full_name' => 'Guru Penguji']);
        $this->assignment = TeacherSubjectAssignment::create([
            'tenant_id' => $this->tenant->id,
            'teacher_profile_id' => $teacherProfile->id,
            'subject_id' => $this->subject->id,
            'rombel_id' => $this->rombelA->id,
            'academic_year_id' => $this->academicYear->id,
            'semester_id' => $this->semester->id,
            'starts_at' => now()->subDay()->toDateString(),
            'ends_at' => now()->addYears(5)->toDateString(),
            'status' => 'ACTIVE',
            'created_by' => $this->teacher->id,
        ]);

        \Illuminate\Support\Facades\DB::table('rombel_student')->insert([
            ['id' => (string) \Illuminate\Support\Str::uuid(), 'tenant_id' => $this->tenant->id, 'rombel_id' => $this->rombelA->id, 'student_id' => $sp1->id],
            ['id' => (string) \Illuminate\Support\Str::uuid(), 'tenant_id' => $this->tenant->id, 'rombel_id' => $this->rombelB->id, 'student_id' => $sp2->id],
        ]);

        // Question 1
        $q1 = Question::create([
            'tenant_id' => $this->tenant->id,
            'subject_id' => $this->subject->id,
            'question_code' => 'BIO-01',
            'question_type' => 'MULTIPLE_CHOICE',
            'status' => 'PUBLISHED',
            'created_by' => $this->teacher->id,
        ]);
        $this->questionVersion1 = QuestionVersion::create([
            'tenant_id' => $this->tenant->id,
            'question_id' => $q1->id,
            'version_number' => 1,
            'stem' => 'Apa organel sel penghasil energi?',
            'question_type' => 'MULTIPLE_CHOICE',
            'default_points' => 10,
            'status' => 'PUBLISHED',
            'created_by' => $this->teacher->id,
        ]);
        $this->canonicalContext = $this->createCanonicalCurriculumContext($this->assignment, $this->teacher);
        $this->mapQuestionVersionToCanonicalCurriculum($this->questionVersion1, $this->canonicalContext);
        $q1->update(['current_version_id' => $this->questionVersion1->id]);

        // Question 2
        $q2 = Question::create([
            'tenant_id' => $this->tenant->id,
            'subject_id' => $this->subject->id,
            'question_code' => 'BIO-02',
            'question_type' => 'TRUE_FALSE',
            'status' => 'PUBLISHED',
            'created_by' => $this->teacher->id,
        ]);
        $this->questionVersion2 = QuestionVersion::create([
            'tenant_id' => $this->tenant->id,
            'question_id' => $q2->id,
            'version_number' => 1,
            'stem' => 'Fotosintesis menghasilkan oksigen.',
            'question_type' => 'TRUE_FALSE',
            'default_points' => 5,
            'status' => 'PUBLISHED',
            'created_by' => $this->teacher->id,
        ]);
        $this->mapQuestionVersionToCanonicalCurriculum($this->questionVersion2, $this->canonicalContext);
        $q2->update(['current_version_id' => $this->questionVersion2->id]);

        app(\App\Contexts\TenantContext::class)->setTenantId(null);
        $this->bootstrapCbtRolePermissions();
    }

    /** @test */
    public function saving_exam_fails_without_selecting_rombel()
    {
        $this->actingAs($this->teacher);

        $this->examManagementComponent()
            ->set('title', 'Ujian Tanpa Rombel')
            ->set('selectedSubjectId', $this->subject->id)
            ->set('selectedSemesterId', $this->semester->id)
            ->set('selectedRombelId', '')
            ->set('selectedQuestionVersionIds', [$this->questionVersion1->id])
            ->set('startAt', now()->addHour()->format('Y-m-d\TH:i'))
            ->set('endAt', now()->addDays(2)->format('Y-m-d\TH:i'))
            ->call('saveExam')
            ->assertHasErrors(['selectedRombelId']);
    }

    /** @test */
    public function saving_exam_fails_without_selecting_question()
    {
        $this->actingAs($this->teacher);

        $this->examManagementComponent()
            ->set('title', 'Ujian Tanpa Soal')
            ->set('selectedSubjectId', $this->subject->id)
            ->set('selectedSemesterId', $this->semester->id)
            ->set('selectedRombelId', $this->rombelA->id)
            ->set('selectedQuestionVersionIds', [])
            ->set('startAt', now()->addHour()->format('Y-m-d\TH:i'))
            ->set('endAt', now()->addDays(2)->format('Y-m-d\TH:i'))
            ->call('saveExam')
            ->assertHasErrors(['selectedQuestionVersionIds']);
    }

    /** @test */
    public function teacher_cannot_assign_exam_to_all_students_outside_their_exact_assignment_scope()
    {
        $this->actingAs($this->teacher);

        $this->examManagementComponent()
            ->set('title', 'Ujian Global Ditolak')
            ->set('selectedSubjectId', $this->subject->id)
            ->set('selectedSemesterId', $this->semester->id)
            ->set('selectedRombelId', 'ALL')
            ->set('confirmAllStudents', true)
            ->set('selectedQuestionVersionIds', [$this->questionVersion1->id])
            ->set('startAt', now()->addHour()->format('Y-m-d\TH:i'))
            ->set('endAt', now()->addDays(2)->format('Y-m-d\TH:i'))
            ->call('saveExam')
            ->assertForbidden();

        $this->assertDatabaseMissing('exams', ['title' => 'Ujian Global Ditolak']);
    }

    /** @test */
    public function exam_participants_contain_only_selected_rombel_students()
    {
        $this->actingAs($this->teacher);

        $this->examManagementComponent()
            ->set('title', 'Ujian Khusus Rombel A')
            ->set('selectedSubjectId', $this->subject->id)
            ->set('selectedSemesterId', $this->semester->id)
            ->set('selectedRombelId', $this->rombelA->id)
            ->set('selectedQuestionVersionIds', [$this->questionVersion1->id])
            ->set('startAt', now()->addHour()->format('Y-m-d\TH:i'))
            ->set('endAt', now()->addDays(2)->format('Y-m-d\TH:i'))
            ->call('saveExam')
            ->assertHasNoErrors();

        $exam = Exam::where('title', 'Ujian Khusus Rombel A')->first();

        // Student 1 (Rombel A) ASSIGNED, Student 2 (Rombel B) NOT assigned
        $this->assertDatabaseHas('exam_participants', ['exam_id' => $exam->id, 'user_id' => $this->student1->id]);
        $this->assertDatabaseMissing('exam_participants', ['exam_id' => $exam->id, 'user_id' => $this->student2->id]);
    }

    /** @test */
    public function exam_snapshots_contain_only_explicitly_selected_questions()
    {
        $this->actingAs($this->teacher);

        // Only select questionVersion1 (BIO-01), NOT questionVersion2 (BIO-02)
        $this->examManagementComponent()
            ->set('title', 'Ujian Biologi 1 Soal')
            ->set('selectedSubjectId', $this->subject->id)
            ->set('selectedSemesterId', $this->semester->id)
            ->set('selectedRombelId', $this->rombelA->id)
            ->set('selectedQuestionVersionIds', [$this->questionVersion1->id])
            ->set('requireToken', false) // Client tampering must not disable mandatory tokens.
            ->set('startAt', now()->addHour()->format('Y-m-d\TH:i'))
            ->set('endAt', now()->addDays(2)->format('Y-m-d\TH:i'))
            ->call('saveExam')
            ->assertHasNoErrors();

        $exam = Exam::where('title', 'Ujian Biologi 1 Soal')->first();
        $this->assertTrue((bool) $exam->fresh()->require_token);
        $this->assertNull($exam->fresh()->token);
        $this->assertDatabaseHas('audit_logs', ['action' => 'CREATE_EXAM', 'entity_id' => $exam->id]);

        // Even a legacy draft row with require_token=false must be corrected at publish.
        $exam->update(['require_token' => false]);
        $this->examManagementComponent()->call('lockExam', $exam->id);

        $exam->refresh();
        $this->assertTrue((bool) $exam->require_token);
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{6}$/', $exam->token);
        $this->assertDatabaseHas('audit_logs', ['action' => 'PUBLISH_EXAM', 'entity_id' => $exam->id]);
        $this->assertEquals(1, $exam->snapshots()->count());
        $this->assertDatabaseHas('exam_question_snapshots', [
            'exam_id' => $exam->id,
            'source_question_version_id' => $this->questionVersion1->id,
        ]);
        $this->assertDatabaseMissing('exam_question_snapshots', [
            'exam_id' => $exam->id,
            'source_question_version_id' => $this->questionVersion2->id,
        ]);
    }
    /** @test */
    public function teacher_can_generate_token_only_after_published_exam_and_with_permission()
    {
        $this->actingAs($this->teacher);
        $assessment = \App\Models\Assessment::create([
            'tenant_id' => $this->tenant->id,
            'title' => 'Asesmen Token',
            'subject_id' => $this->subject->id,
            'academic_year_id' => $this->academicYear->id,
            'semester_id' => $this->semester->id,
            'assessment_type' => 'SUMMATIVE',
            'status' => 'ACTIVE',
            'weight' => 1,
            'created_by' => $this->teacher->id,
        ]);
        $this->createCanonicalCurriculumContext($this->assignment, $this->teacher, $assessment);
        $exam = Exam::create([
            'tenant_id' => $this->tenant->id,
            'assessment_id' => $assessment->id,
            'title' => 'Ujian Token',
            'status' => 'DRAFT',
            'duration_seconds' => 3600,
            'start_at' => now()->addHour(),
            'end_at' => now()->addDays(2),
            'created_by' => $this->teacher->id,
            'require_token' => true,
        ]);

        // Teacher token access is scoped to exams with an assigned participant roster.
        $exam->participants()->create([
            'user_id' => $this->student1->id,
            'status' => 'ASSIGNED',
        ]);

        $policy = app(\App\Policies\ExamPolicy::class);
        $this->assertFalse($policy->generateToken($this->teacher, $exam));

        $exam->update(['status' => 'PUBLISHED', 'locked_at' => now(), 'published_at' => now()]);
        $exam->refresh();
        $this->assertTrue($policy->generateToken($this->teacher, $exam));

        $guruRole = $this->teacher->roles()->where('roles.name', 'GURU')->firstOrFail();
        $tokenPermission = Permission::query()->where('name', 'exam.token')->firstOrFail();
        $guruRole->permissions()->detach($tokenPermission->id);
        $this->teacher->unsetRelation('roles');
        $this->assertFalse($policy->generateToken($this->teacher, $exam));
    }

    private function examManagementComponent()
    {
        return Livewire::test(ExamManagement::class)
            ->set('selectedAssignmentId', $this->assignment->id)
            ->set('selectedSubjectId', $this->subject->id)
            ->set('selectedSemesterId', $this->semester->id)
            ->set('activityName', 'Asesmen IPAS Kelas 6')
            ->set('assessmentPurpose', 'Asesmen pembelajaran IPAS')
            ->set('selectedAssessmentFunctionOptionId', $this->canonicalContext['taxonomy']['function']->id)
            ->set('selectedAssessmentScopeOptionId', $this->canonicalContext['taxonomy']['scope']->id)
            ->set('selectedInstrumentTypeOptionId', $this->canonicalContext['taxonomy']['instrument']->id)
            ->set('selectedDeliveryModeOptionId', $this->canonicalContext['taxonomy']['delivery']->id)
            ->set('selectedCpIds', [$this->canonicalContext['cp']->id])
            ->set('selectedOutcomeIds', [$this->canonicalContext['tp']->id]);
    }

}
