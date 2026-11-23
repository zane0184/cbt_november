<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Semester;
use App\Models\TeacherProfile;
use App\Models\TeacherSubjectAssignment;
use App\Models\Assessment;
use App\Models\Curriculum;
use App\Models\Exam;
use App\Models\GradeLevel;
use App\Models\LearningObjective;
use App\Models\LearningOutcome;
use App\Models\Phase;
use App\Models\Question;
use App\Models\Role;
use App\Models\Rombel;
use App\Models\StudentProfile;
use App\Models\Subject;
use App\Models\Tenant;
use App\Models\User;
use App\Livewire\Admin\QuestionBank;
use App\Livewire\Admin\ExamManagement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use Tests\Concerns\CreatesCanonicalCurriculumContext;

class TeacherPracticeWorkflowTest extends TestCase
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
    private $rombel;
    private $learningOutcome;
    private $learningObjective;
    private $assignment;
    private array $canonicalContext = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create(['name' => 'Madrasah Aliyah Negeri 2', 'code' => 'MAN2']);
        app(\App\Contexts\TenantContext::class)->setTenantId($this->tenant->id);

        $teacherRole = Role::create(['name' => 'GURU', 'label' => 'Guru']);
        $studentRole = Role::create(['name' => 'SISWA', 'label' => 'Siswa']);

        $this->teacher = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Guru Pengajar Fiqih',
            'email' => 'guru.fiqih@man2.sch.id',
            'password' => bcrypt('password123'),
        ]);
        $this->teacher->roles()->attach($teacherRole);

        $this->student1 = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Siswa Rombel A',
            'email' => 'siswa.a@man2.sch.id',
            'password' => bcrypt('password123'),
        ]);
        $this->student1->roles()->attach($studentRole);

        $this->student2 = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Siswa Rombel B',
            'email' => 'siswa.b@man2.sch.id',
            'password' => bcrypt('password123'),
        ]);
        $this->student2->roles()->attach($studentRole);

        $sp1 = StudentProfile::create(['tenant_id' => $this->tenant->id, 'user_id' => $this->student1->id, 'full_name' => 'Siswa Rombel A']);
        $sp2 = StudentProfile::create(['tenant_id' => $this->tenant->id, 'user_id' => $this->student2->id, 'full_name' => 'Siswa Rombel B']);

        $curriculum = Curriculum::create(['name' => 'Kurikulum Merdeka', 'code' => 'K-MERDEKA', 'is_active' => true]);
        $phase = Phase::create(['curriculum_id' => $curriculum->id, 'name' => 'Fase C', 'code' => 'C']);
        $this->academicYear = AcademicYear::create(['name' => '2024/2025', 'is_active' => true]);
        $this->semester = Semester::create(['tenant_id' => $this->tenant->id, 'academic_year_id' => $this->academicYear->id, 'term' => 'GANJIL', 'is_active' => true]);
        $gradeLevel = GradeLevel::create(['phase_id' => $phase->id, 'name' => 'Kelas 6', 'code' => '6', 'level' => 6]);

        $this->subject = Subject::create([
            'tenant_id' => $this->tenant->id,
            'code' => 'FIKIH',
            'name' => 'Fiqih',
            'short_name' => 'FIQ',
            'status' => 'ACTIVE',
        ]);

        $this->rombel = Rombel::create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $this->academicYear->id,
            'grade_level_id' => $gradeLevel->id,
            'name' => 'Kelas 6-A',
            'status' => 'ACTIVE',
        ]);
        $teacherProfile = TeacherProfile::create(['tenant_id' => $this->tenant->id, 'user_id' => $this->teacher->id, 'full_name' => 'Guru Pengajar Fiqih']);
        $this->assignment = TeacherSubjectAssignment::create([
            'tenant_id' => $this->tenant->id,
            'teacher_profile_id' => $teacherProfile->id,
            'subject_id' => $this->subject->id,
            'rombel_id' => $this->rombel->id,
            'academic_year_id' => $this->academicYear->id,
            'semester_id' => $this->semester->id,
            'starts_at' => now()->subDay()->toDateString(),
            'ends_at' => now()->addYears(5)->toDateString(),
            'status' => 'ACTIVE',
            'created_by' => $this->teacher->id,
        ]);

        \Illuminate\Support\Facades\DB::table('rombel_student')->insert([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'rombel_id' => $this->rombel->id,
            'student_id' => $sp1->id,
        ]);

        $this->canonicalContext = $this->createCanonicalCurriculumContext($this->assignment, $this->teacher);

        $this->learningOutcome = LearningOutcome::create([
            'curriculum_id' => $curriculum->id,
            'phase_id' => $phase->id,
            'subject_id' => $this->subject->id,
            'code' => 'CP-FIQ-01',
        ]);

        $this->learningObjective = LearningObjective::create([
            'learning_outcome_id' => $this->learningOutcome->id,
            'code' => 'TP-FIQ-01',
            'title' => 'Memahami Konsep Ibadah Muamalah',
            'description' => 'Deskripsi Tujuan Pembelajaran Fiqih',
            'status' => 'ACTIVE',
        ]);

        app(\App\Contexts\TenantContext::class)->setTenantId(null);
        $this->bootstrapCbtRolePermissions();
    }

    /** @test */
    public function teacher_can_access_question_bank_and_create_mc_true_false_and_essay_questions()
    {
        $this->actingAs($this->teacher);

        // 1. Create MC Question with CP/TP mapping
        $this->questionBankComponent()
            ->set('questionCode', 'FIQ-MC-01')
            ->set('questionType', 'MULTIPLE_CHOICE')
            ->set('selectedSubjectId', $this->subject->id)
        // Semester is not a QuestionBank component property; scope comes from teacher assignment.
            ->set('selectedCpIds', [$this->canonicalContext['cp']->id])
            ->set('selectedOutcomeIds', [$this->canonicalContext['tp']->id])
            ->set('stem', 'Apakah rukun Islam yang pertama?')
            ->set('defaultPoints', 10)
            ->set('optionA', 'Mengucapkan Dua Kalimat Syahadat')
            ->set('optionB', 'Mendirikan Shalat')
            ->set('correctOption', 'A')
            ->call('saveQuestion')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('questions', [
            'tenant_id' => $this->tenant->id,
            'question_code' => 'FIQ-MC-01',
            'status' => 'DRAFT',
        ]);

        // 2. Create TRUE_FALSE Question
        $this->questionBankComponent()
            ->set('questionCode', 'FIQ-TF-01')
            ->set('questionType', 'TRUE_FALSE')
            ->set('selectedSubjectId', $this->subject->id)
        // Semester is not a QuestionBank component property; scope comes from teacher assignment.
            ->set('stem', 'Shalat Subuh berjumlah dua rakaat.')
            ->set('defaultPoints', 5)
            ->set('correctOption', 'A')
            ->call('saveQuestion')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('questions', [
            'question_code' => 'FIQ-TF-01',
        ]);

        // 3. Create ESSAY Question with Rubric
        $this->questionBankComponent()
            ->set('questionCode', 'FIQ-ESSAY-01')
            ->set('questionType', 'ESSAY')
            ->set('selectedSubjectId', $this->subject->id)
        // Semester is not a QuestionBank component property; scope comes from teacher assignment.
            ->set('stem', 'Jelaskan syarat sah shalat Jumat!')
            ->set('explanation', '1. Beragama Islam, 2. Baligh, 3. Berakal, 4. Laki-laki.')
            ->set('defaultPoints', 20)
            ->call('saveQuestion')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('questions', [
            'question_code' => 'FIQ-ESSAY-01',
        ]);
    }

    /** @test */
    public function teacher_can_edit_and_delete_question()
    {
        $this->actingAs($this->teacher);

        // Create initial question
        $component = $this->questionBankComponent()
            ->set('questionCode', 'FIQ-EDIT-01')
            ->set('questionType', 'MULTIPLE_CHOICE')
            ->set('selectedSubjectId', $this->subject->id)
        // Semester is not a QuestionBank component property; scope comes from teacher assignment.
            ->set('stem', 'Pertanyaan Awal')
            ->set('optionA', 'Awal 1')
            ->set('optionB', 'Awal 2')
            ->set('correctOption', 'A')
            ->call('saveQuestion');

        $question = Question::where('question_code', 'FIQ-EDIT-01')->first();
        $this->assertNotNull($question);

        // Edit Question
        $this->questionBankComponent()
            ->call('openEditModal', $question->id)
            ->set('stem', 'Pertanyaan Yang Sudah Direvisi')
            ->set('optionA', 'Revisi 1')
            ->call('saveQuestion')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('question_versions', [
            'question_id' => $question->id,
            'stem' => 'Pertanyaan Yang Sudah Direvisi',
        ]);

        // Delete Question
        $this->questionBankComponent()
            ->call('confirmDeleteQuestion', $question->id)
            ->call('deleteQuestion')
            ->assertHasNoErrors();

        $this->assertSoftDeleted('questions', ['id' => $question->id]);
    }

    /** @test */
    public function teacher_can_build_exam_pick_questions_assign_rombel_and_lock_exam()
    {
        $this->actingAs($this->teacher);

        // Create and publish a question in Bank Soal
        $this->questionBankComponent()
            ->set('questionCode', 'FIQ-EXAM-01')
            ->set('questionType', 'MULTIPLE_CHOICE')
            ->set('selectedSubjectId', $this->subject->id)
        // Semester is not a QuestionBank component property; scope comes from teacher assignment.
            ->set('stem', 'Apakah hukum menuntut ilmu bagi setiap Muslim?')
            ->set('optionA', 'Fardhu Ain')
            ->set('optionB', 'Fardhu Kifayah')
            ->set('correctOption', 'A')
            ->call('saveQuestion');

        $q = Question::where('question_code', 'FIQ-EXAM-01')->first();
        $this->questionBankComponent()->call('publishQuestion', $q->id);

        $version = $q->currentVersion;

        // Build Exam & Pick Question & Assign Rombel
        $startAt = now()->addHour()->format('Y-m-d\TH:i');
        $endAt = now()->addDays(2)->format('Y-m-d\TH:i');

        $this->examManagementComponent()
            ->set('title', 'Ujian Fiqih Rombel IPA 1')
            ->set('selectedSubjectId', $this->subject->id)
            ->set('selectedSemesterId', $this->semester->id)
            ->set('selectedRombelId', $this->rombel->id)
            ->set('selectedQuestionVersionIds', [$version->id])
            ->set('durationMinutes', 90)
            ->set('startAt', $startAt)
            ->set('endAt', $endAt)
            ->call('saveExam')
            ->assertHasNoErrors();

        $exam = Exam::where('title', 'Ujian Fiqih Rombel IPA 1')->first();
        $this->assertNotNull($exam);

        // Lock & Snapshot Exam
        $this->examManagementComponent()->call('lockExam', $exam->id);

        $exam->refresh();
        $this->assertEquals('PUBLISHED', $exam->status);
        $this->assertNotNull($exam->locked_at);

        // Verify Snapshots
        $this->assertDatabaseHas('exam_question_snapshots', [
            'exam_id' => $exam->id,
            'source_question_id' => $q->id,
            'source_question_version_id' => $version->id,
            'snapshot_stem' => 'Apakah hukum menuntut ilmu bagi setiap Muslim?',
        ]);

        // Verify Participant Assignment (Only Student 1 in Rombel A is assigned, NOT Student 2)
        $this->assertDatabaseHas('exam_participants', [
            'exam_id' => $exam->id,
            'user_id' => $this->student1->id,
        ]);
        $this->assertDatabaseMissing('exam_participants', [
            'exam_id' => $exam->id,
            'user_id' => $this->student2->id,
        ]);
    }

    /** @test */
    public function editing_bank_soal_after_exam_locked_does_not_mutate_exam_snapshots()
    {
        $this->actingAs($this->teacher);

        // Create question & Exam
        $this->questionBankComponent()
            ->set('questionCode', 'FIQ-FREEZE-01')
            ->set('questionType', 'MULTIPLE_CHOICE')
            ->set('selectedSubjectId', $this->subject->id)
        // Semester is not a QuestionBank component property; scope comes from teacher assignment.
            ->set('stem', 'Stem Asli Sebelum Diedit')
            ->set('optionA', 'Asli A')
            ->set('optionB', 'Asli B')
            ->set('correctOption', 'A')
            ->call('saveQuestion');

        $q = Question::where('question_code', 'FIQ-FREEZE-01')->first();
        $this->questionBankComponent()->call('publishQuestion', $q->id);

        $version = $q->currentVersion;

        $this->examManagementComponent()
            ->set('title', 'Ujian Terkunci Freeze Test')
            ->set('selectedSubjectId', $this->subject->id)
            ->set('selectedSemesterId', $this->semester->id)
            ->set('selectedRombelId', $this->rombel->id)
            ->set('selectedQuestionVersionIds', [$version->id])
            ->set('startAt', now()->addHour()->format('Y-m-d\TH:i'))
            ->set('endAt', now()->addDays(2)->format('Y-m-d\TH:i'))
            ->call('saveExam');

        $exam = Exam::where('title', 'Ujian Terkunci Freeze Test')->first();
        $this->examManagementComponent()->call('lockExam', $exam->id);

        // Edit Bank Soal AFTER Exam is Locked
        $this->questionBankComponent()
            ->call('openEditModal', $q->id)
            ->set('stem', 'Stem Baru Setelah Diedit Guru')
            ->call('saveQuestion');

        // Verify Snapshot Stem remains the ORIGINAL frozen text
        $this->assertDatabaseHas('exam_question_snapshots', [
            'exam_id' => $exam->id,
            'snapshot_stem' => 'Stem Asli Sebelum Diedit',
        ]);
    }
    private function questionBankComponent()
    {
        return Livewire::test(QuestionBank::class)
            ->set('selectedAssignmentId', $this->assignment->id)
            ->set('selectedSubjectId', $this->subject->id)
            ->set('selectedCpIds', [$this->canonicalContext['cp']->id])
            ->set('selectedOutcomeIds', [$this->canonicalContext['tp']->id]);
    }

    private function examManagementComponent()
    {
        return Livewire::test(ExamManagement::class)
            ->set('selectedAssignmentId', $this->assignment->id)
            ->set('selectedSubjectId', $this->subject->id)
            ->set('selectedSemesterId', $this->semester->id)
            ->set('selectedRombelId', $this->rombel->id)
            ->set('activityName', 'Latihan Fiqih')
            ->set('assessmentPurpose', 'Asesmen pembelajaran Fiqih kelas 6')
            ->set('selectedAssessmentFunctionOptionId', $this->canonicalContext['taxonomy']['function']->id)
            ->set('selectedAssessmentScopeOptionId', $this->canonicalContext['taxonomy']['scope']->id)
            ->set('selectedInstrumentTypeOptionId', $this->canonicalContext['taxonomy']['instrument']->id)
            ->set('selectedDeliveryModeOptionId', $this->canonicalContext['taxonomy']['delivery']->id)
            ->set('selectedCpIds', [$this->canonicalContext['cp']->id])
            ->set('selectedOutcomeIds', [$this->canonicalContext['tp']->id]);
    }

}
