<?php

namespace Tests\Feature\Security;

use App\Models\Tenant;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Assessment;
use App\Models\Exam;
use App\Models\AcademicYear;
use App\Models\ExamParticipant;
use App\Models\StudentProfile;
use App\Models\Rombel;
use App\Models\Curriculum;
use App\Models\Phase;
use App\Models\GradeLevel;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\TeacherSubjectAssignment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Question;
use App\Models\QuestionVersion;
use App\Models\QuestionOption;
use App\Services\ExamSnapshotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnswerKeyLeakageTest extends TestCase
{
    use RefreshDatabase;

    private $tenant;
    private $student;
    private $guru;
    private $exam;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'T1', 'code' => 'T1']);

        $context = app(\App\Contexts\TenantContext::class);
        $context->setTenantId($this->tenant->id);

        $this->student = User::create(['tenant_id' => $this->tenant->id, 'name' => 'Student', 'email' => 'student@test.com', 'password' => bcrypt('password')]);
        $this->student->roles()->attach(\App\Models\Role::firstOrCreate(['name' => 'SISWA'], ['label' => 'Siswa']));

        $this->guru = User::create(['tenant_id' => $this->tenant->id, 'name' => 'Guru', 'email' => 'guru@test.com', 'password' => bcrypt('password')]);
        $guruRole = Role::firstOrCreate(['name' => 'GURU'], ['label' => 'Guru']);
        $this->guru->roles()->attach($guruRole);
        $guruRole->permissions()->syncWithoutDetaching([
            Permission::firstOrCreate(['name' => 'exam.manage'], ['label' => 'Kelola ujian'])->id,
            Permission::firstOrCreate(['name' => 'question.manage'], ['label' => 'Kelola bank soal'])->id,
        ]);

        $ay = AcademicYear::create(['tenant_id' => $this->tenant->id, 'name' => '2024']);
        $semester = Semester::create(['tenant_id' => $this->tenant->id, 'academic_year_id' => $ay->id, 'term' => 'GANJIL', 'is_active' => true]);
        $subject = Subject::create(['tenant_id' => $this->tenant->id, 'name' => 'S1', 'code' => 'S1', 'short_name' => 'S1', 'status' => 'ACTIVE']);
        $curriculum = Curriculum::create(['name' => 'Kurikulum Merdeka', 'code' => 'KM', 'is_active' => true]);
        $phase = Phase::create(['curriculum_id' => $curriculum->id, 'name' => 'Fase A', 'code' => 'A']);
        $gradeLevel = GradeLevel::create(['phase_id' => $phase->id, 'name' => 'Kelas 1', 'code' => '1', 'level' => 1]);
        $rombel = Rombel::create(['tenant_id' => $this->tenant->id, 'academic_year_id' => $ay->id, 'grade_level_id' => $gradeLevel->id, 'name' => '1A', 'status' => 'ACTIVE']);
        $teacherProfile = TeacherProfile::create(['tenant_id' => $this->tenant->id, 'user_id' => $this->guru->id, 'full_name' => 'Guru']);
        TeacherSubjectAssignment::create([
            'tenant_id' => $this->tenant->id,
            'teacher_profile_id' => $teacherProfile->id,
            'subject_id' => $subject->id,
            'rombel_id' => $rombel->id,
            'academic_year_id' => $ay->id,
            'semester_id' => $semester->id,
            'starts_at' => now()->subDay()->toDateString(),
            'status' => 'ACTIVE',
            'created_by' => $this->guru->id,
        ]);
        $studentProfile = StudentProfile::create(['tenant_id' => $this->tenant->id, 'user_id' => $this->student->id, 'full_name' => 'Student']);
        DB::table('rombel_student')->insert([
            'id' => (string) Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'rombel_id' => $rombel->id,
            'student_id' => $studentProfile->id,
        ]);
        $assessment = Assessment::create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $ay->id,
            'semester_id' => $semester->id,
            'subject_id' => $subject->id,
            'title' => 'A1',
            'assessment_type' => 'DAILY',
            'created_by' => $this->guru->id,
        ]);

        $this->exam = Exam::create([
            'tenant_id' => $this->tenant->id,
            'assessment_id' => $assessment->id,
            'title' => 'E1',
            'duration_seconds' => 3600,
            'created_by' => $this->guru->id,
            'status' => 'DRAFT',
            'start_at' => now()->subMinutes(10),
            'end_at' => now()->addMinutes(50),
            'timezone' => 'UTC',
            'require_token' => true,
            'token' => 'ABC123'
        ]);

        // Create a question with correct option
        $question = Question::create(['tenant_id' => $this->tenant->id, 'subject_id' => $subject->id, 'created_by' => $this->guru->id]);
        $version = QuestionVersion::create([
            'tenant_id' => $this->tenant->id,
            'question_id' => $question->id,
            'stem' => 'What is 1+1?',
            'question_type' => 'MC',
            'default_points' => 10,
            'version_number' => 1,
            'status' => 'PUBLISHED',
            'created_by' => $this->guru->id
        ]);
        QuestionOption::create([
            'tenant_id' => $this->tenant->id,
            'question_version_id' => $version->id,
            'content' => '2',
            'is_correct' => true,
            'score_weight' => 1.0
        ]);
        QuestionOption::create([
            'tenant_id' => $this->tenant->id,
            'question_version_id' => $version->id,
            'content' => '3',
            'is_correct' => false,
            'score_weight' => 0.0
        ]);

        app(ExamSnapshotService::class)->createSnapshots($this->exam, [$version->id]);
        $this->exam->update(['locked_at' => now(), 'status' => 'SCHEDULED']);

        ExamParticipant::create(['tenant_id' => $this->tenant->id, 'exam_id' => $this->exam->id, 'user_id' => $this->student->id, 'status' => 'ASSIGNED']);

        $context->setTenantId(null);
    }

    public function test_student_execution_does_not_leak_answer_key()
    {
        $this->actingAs($this->student);

        // Start attempt first
        $this->withHeader('X-Device-Binding-ID', 'dev1')
             ->postJson("/api/v1/exams/{$this->exam->id}/attempts", ['token' => 'ABC123'])
             ->assertStatus(201);

        $response = $this->withHeader('X-Device-Binding-ID', 'dev1')
                         ->getJson("/api/v1/exams/{$this->exam->id}/execution");

        $response->assertStatus(200);

        $questions = $response->json('data.questions');
        $this->assertNotEmpty($questions);

        foreach ($questions as $q) {
            foreach ($q['snapshot_options'] as $option) {
                $this->assertArrayNotHasKey('is_correct', $option, 'Leakage detected: is_correct found in student response');
                $this->assertArrayNotHasKey('score_weight', $option, 'Leakage detected: score_weight found in student response');
            }
        }
    }

    public function test_student_preview_is_forbidden()
    {
        $this->actingAs($this->student);

        $this->getJson("/api/v1/exams/{$this->exam->id}/preview")
            ->assertForbidden();
    }

    public function test_non_teacher_staff_cannot_preview_answer_key()
    {
        $context = app(\App\Contexts\TenantContext::class);
        $context->setTenantId($this->tenant->id);

        try {
            foreach (['ADMIN_MADRASAH', 'OPERATOR', 'KAMAD', 'PROKTOR'] as $roleName) {
                $role = Role::firstOrCreate(['name' => $roleName], ['label' => $roleName]);
                $staff = User::create([
                    'tenant_id' => $this->tenant->id,
                    'name' => $roleName,
                    'email' => strtolower($roleName) . '@test.com',
                    'password' => bcrypt('password'),
                ]);
                $staff->roles()->attach($role);
                $staffUsers[] = $staff;
            }
        } finally {
            $context->setTenantId(null);
        }

        foreach ($staffUsers ?? [] as $staff) {
            $this->actingAs($staff);
            $this->getJson("/api/v1/exams/{$this->exam->id}/preview")
                ->assertForbidden();
        }
    }

    public function test_student_show_does_not_leak_answer_key()
    {
        $this->actingAs($this->student);

        $response = $this->getJson("/api/v1/exams/{$this->exam->id}");
        $response->assertStatus(200);

        $snapshots = $response->json('data.snapshots');
        foreach ($snapshots as $q) {
            foreach ($q['snapshot_options'] as $option) {
                $this->assertArrayNotHasKey('is_correct', $option);
                $this->assertArrayNotHasKey('score_weight', $option);
            }
        }
    }

    public function test_guru_can_see_answer_key_in_preview()
    {
        $this->actingAs($this->guru);

        $response = $this->getJson("/api/v1/exams/{$this->exam->id}/preview");
        $response->assertStatus(200);

        $questions = $response->json('data.questions');
        $foundCorrect = false;
        foreach ($questions as $q) {
            foreach ($q['snapshot_options'] as $option) {
                if (isset($option['is_correct']) && $option['is_correct'] === true) {
                    $foundCorrect = true;
                }
            }
        }
        $this->assertTrue($foundCorrect, 'Guru should be able to see correct answers in preview');
    }

    public function test_student_execution_does_not_leak_matching_pair_keys()
    {
        $context = app(\App\Contexts\TenantContext::class);
        $context->setTenantId($this->tenant->id);

        // Create a matching question
        $subject = \App\Models\Subject::first();
        $qMatching = Question::create(['tenant_id' => $this->tenant->id, 'subject_id' => $subject->id, 'created_by' => $this->guru->id]);
        $vMatching = QuestionVersion::create([
            'tenant_id' => $this->tenant->id,
            'question_id' => $qMatching->id,
            'stem' => 'Match capitals',
            'question_type' => 'MATCHING',
            'default_points' => 10,
            'version_number' => 1,
            'status' => 'PUBLISHED',
            'created_by' => $this->guru->id
        ]);
        QuestionOption::create([
            'tenant_id' => $this->tenant->id,
            'question_version_id' => $vMatching->id,
            'content' => 'Indonesia',
            'option_key' => 'secret_pair_id_100',
            'is_correct' => false,
            'score_weight' => 0
        ]);
        QuestionOption::create([
            'tenant_id' => $this->tenant->id,
            'question_version_id' => $vMatching->id,
            'content' => 'Jakarta',
            'matching_key' => 'secret_pair_id_100',
            'is_correct' => false,
            'score_weight' => 0
        ]);

        $this->exam->update(['status' => 'DRAFT', 'locked_at' => null]);
        app(ExamSnapshotService::class)->createSnapshots($this->exam, [$vMatching->id]);
        $this->exam->update(['locked_at' => now(), 'status' => 'SCHEDULED']);

        $context->setTenantId(null);

        $this->actingAs($this->student);

        $this->withHeader('X-Device-Binding-ID', 'dev1')
             ->postJson("/api/v1/exams/{$this->exam->id}/attempts", ['token' => 'ABC123']);

        $response = $this->withHeader('X-Device-Binding-ID', 'dev1')
                         ->getJson("/api/v1/exams/{$this->exam->id}/execution");

        $response->assertStatus(200);
        $questions = $response->json('data.questions');

        foreach ($questions as $q) {
            foreach ($q['snapshot_options'] as $option) {
                // Secret pair matching keys must not match raw secret key 'secret_pair_id_100'
                if (isset($option['option_key'])) {
                    $this->assertNotEquals('secret_pair_id_100', $option['option_key'], 'Secret pair matching key leaked in option_key');
                }
                if (isset($option['matching_key'])) {
                    $this->assertNotEquals('secret_pair_id_100', $option['matching_key'], 'Secret pair matching key leaked in matching_key');
                }
            }
        }
    }

    public function test_student_execution_does_not_leak_short_answer_and_essay_metadata_keys()
    {
        $context = app(\App\Contexts\TenantContext::class);
        $context->setTenantId($this->tenant->id);

        $subject = \App\Models\Subject::first();
        $qShort = Question::create(['tenant_id' => $this->tenant->id, 'subject_id' => $subject->id, 'created_by' => $this->guru->id]);
        $vShort = QuestionVersion::create([
            'tenant_id' => $this->tenant->id,
            'question_id' => $qShort->id,
            'stem' => 'Capital of France?',
            'question_type' => 'SHORT_ANSWER',
            'default_points' => 10,
            'version_number' => 1,
            'status' => 'PUBLISHED',
            'created_by' => $this->guru->id
        ]);

        $this->exam->update(['status' => 'DRAFT', 'locked_at' => null]);
        app(ExamSnapshotService::class)->createSnapshots($this->exam, [$vShort->id]);
        // Manually add accepted_answers metadata
        $snapshot = $this->exam->snapshots()->first();
        $meta = $snapshot->metadata;
        $meta['accepted_answers'] = ['Paris', 'Paris City'];
        $meta['rubric'] = 'Answer must be Paris';
        $snapshot->update(['metadata' => $meta, 'snapshot_explanation' => 'Paris is the capital of France.']);
        $this->exam->update(['locked_at' => now(), 'status' => 'SCHEDULED']);

        $context->setTenantId(null);

        $this->actingAs($this->student);

        $this->withHeader('X-Device-Binding-ID', 'dev1')
             ->postJson("/api/v1/exams/{$this->exam->id}/attempts", ['token' => 'ABC123']);

        $response = $this->withHeader('X-Device-Binding-ID', 'dev1')
                         ->getJson("/api/v1/exams/{$this->exam->id}/execution");

        $response->assertStatus(200);
        $questions = $response->json('data.questions');

        foreach ($questions as $q) {
            $metadata = $q['metadata'] ?? [];
            $this->assertArrayNotHasKey('accepted_answers', $metadata, 'accepted_answers leaked in metadata to student');
            $this->assertArrayNotHasKey('rubric', $metadata, 'rubric leaked in metadata to student');
            $this->assertArrayNotHasKey('snapshot_explanation', $q, 'snapshot_explanation leaked to student');
        }

        // Test GET /api/v1/exams/{exam}
        $showResponse = $this->getJson("/api/v1/exams/{$this->exam->id}");
        $showResponse->assertStatus(200);
        $showSnapshots = $showResponse->json('data.snapshots');
        foreach ($showSnapshots as $s) {
            $this->assertArrayNotHasKey('snapshot_explanation', $s, 'snapshot_explanation leaked in exam show endpoint');
            if (isset($s['metadata'])) {
                $this->assertArrayNotHasKey('accepted_answers', $s['metadata'], 'accepted_answers leaked in exam show metadata');
                $this->assertArrayNotHasKey('rubric', $s['metadata'], 'rubric leaked in exam show metadata');
            }
        }
    }
}

