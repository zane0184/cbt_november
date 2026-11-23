<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Question;
use App\Models\QuestionVersion;
use App\Models\Role;
use App\Models\Permission;
use App\Models\TeacherProfile;
use App\Models\TeacherSubjectAssignment;
use App\Models\AcademicYear;
use App\Models\Semester;
use App\Models\Rombel;
use App\Models\GradeLevel;
use App\Models\Curriculum;
use App\Models\Phase;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\ExcelQuestionImporter;
use Tests\TestCase;

class QuestionImportTest extends TestCase
{
    use RefreshDatabase;

    private $tenant;
    private $teacher;
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'T1', 'code' => 'T1']);
        app(\App\Contexts\TenantContext::class)->setTenantId($this->tenant->id);
        $this->teacher = User::create(['tenant_id' => $this->tenant->id, 'name' => 'Guru', 'email' => 'g@m.com', 'password' => 'p']);
        $guruRole = Role::firstOrCreate(['name' => 'GURU'], ['label' => 'Guru']);
        $this->teacher->roles()->attach($guruRole);
        $questionView = Permission::firstOrCreate(['name' => 'question.view'], ['label' => 'Lihat Bank Soal']);
        $questionManage = Permission::firstOrCreate(['name' => 'question.manage'], ['label' => 'Kelola Bank Soal']);
        $guruRole->permissions()->syncWithoutDetaching([$questionView->id, $questionManage->id]);

        $this->subject = Subject::create(['name' => 'Matematika', 'code' => 'MTK']);
        $profile = TeacherProfile::create(['tenant_id' => $this->tenant->id, 'user_id' => $this->teacher->id, 'full_name' => 'Guru']);
        $year = AcademicYear::create(['tenant_id' => $this->tenant->id, 'name' => '2026/2027', 'is_active' => true]);
        $semester = Semester::create(['tenant_id' => $this->tenant->id, 'academic_year_id' => $year->id, 'term' => 'GANJIL', 'is_active' => true]);
        $curriculum = Curriculum::create(['name' => 'KM']);
        $phase = Phase::create(['curriculum_id' => $curriculum->id, 'name' => 'Fase B', 'code' => 'B']);
        $grade = GradeLevel::create(['phase_id' => $phase->id, 'name' => 'Kelas 4', 'code' => '4', 'level' => 4]);
        $rombel = Rombel::create(['academic_year_id' => $year->id, 'grade_level_id' => $grade->id, 'name' => '4A']);
        TeacherSubjectAssignment::create([
            'tenant_id' => $this->tenant->id, 'teacher_profile_id' => $profile->id,
            'subject_id' => $this->subject->id, 'rombel_id' => $rombel->id,
            'academic_year_id' => $year->id, 'semester_id' => $semester->id, 'status' => 'ACTIVE',
        ]);
    }

    public function test_valid_question_import()
    {
        $this->actingAs($this->teacher);

        $payload = [
            'rows' => [
                [
                    'stem' => 'What is 1+1?',
                    'type' => 'MULTIPLE_CHOICE',
                    'subject_id' => $this->subject->id,
                    'difficulty' => 'EASY',
                    'points' => 5,
                    'options' => [
                        ['content' => '2', 'is_correct' => true],
                        ['content' => '3', 'is_correct' => false]
                    ]
                ]
            ]
        ];

        $response = $this->postJson("/api/v1/questions/import", $payload);

        $response->assertStatus(200);
        $response->assertJsonPath('imported', 1);
        $this->assertEquals(1, Question::count());
        $this->assertEquals('What is 1+1?', QuestionVersion::first()->stem);
    }

    public function test_short_answer_import_is_rejected_until_accepted_answers_are_fully_supported()
    {
        $this->actingAs($this->teacher);

        $response = $this->postJson('/api/v1/questions/import', [
            'rows' => [[
                'stem' => 'Jawaban singkat belum siap',
                'type' => 'SHORT_ANSWER',
                'subject_id' => $this->subject->id,
                'accepted_answers' => ['jawaban'],
            ]],
        ]);

        $response->assertStatus(422);
        $this->assertSame(0, Question::query()->count());
    }

    public function test_row_import_service_rejects_short_answer_directly()
    {
        $this->actingAs($this->teacher);

        $result = app(ExcelQuestionImporter::class)->import('', [
            'rows' => [[
                'stem' => 'Jawaban singkat belum siap',
                'type' => 'SHORT_ANSWER',
                'subject_id' => $this->subject->id,
                'accepted_answers' => ['jawaban'],
            ]],
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame(0, $result['imported']);
        $this->assertSame(0, Question::query()->count());
    }

    public function test_import_validation_required_fields()
    {
        $this->actingAs($this->teacher);

        $payload = [
            'rows' => [
                ['stem' => '', 'type' => 'MULTIPLE_CHOICE'] // Empty stem
            ]
        ];

        $response = $this->postJson("/api/v1/questions/import", $payload);
        $response->assertStatus(422);
    }

    public function test_matching_question_import()
    {
        $this->actingAs($this->teacher);

        $payload = [
            'rows' => [
                [
                    'stem' => 'Match colors',
                    'type' => 'MATCHING',
                    'subject_id' => $this->subject->id,
                    'options' => [
                        ['content' => 'Red', 'option_key' => 'color_red', 'matching_key' => 'color_red'],
                        ['content' => 'Merah', 'option_key' => 'color_red', 'matching_key' => 'color_red']
                    ]
                ]
            ]
        ];

        $response = $this->postJson("/api/v1/questions/import", $payload);
        $response->assertStatus(200);
        $response->assertJsonPath('imported', 1);
    }

    public function test_import_existing_published_question_creates_new_version()
    {
        $this->actingAs($this->teacher);

        // 1. Create a published question
        $q = Question::create(['tenant_id' => $this->tenant->id, 'owner_type' => 'SUBJECT', 'subject_id' => $this->subject->id, 'created_by' => $this->teacher->id]);
        $v1 = $q->versions()->create(['tenant_id' => $this->tenant->id, 'question_type' => 'MULTIPLE_CHOICE', 'stem' => 'V1', 'status' => 'PUBLISHED', 'is_current' => true, 'major_version' => 1, 'minor_version' => 0, 'created_by' => $this->teacher->id]);

        // 2. Import same question ID with new stem
        $payload = [
            'rows' => [
                [
                    'question_id' => $q->id,
                    'stem' => 'V2',
                    'type' => 'MULTIPLE_CHOICE',
                    'options' => [
                        ['content' => '2', 'is_correct' => true],
                        ['content' => '3', 'is_correct' => false]
                    ]
                ]
            ]
        ];

        $response = $this->postJson("/api/v1/questions/import", $payload);
        $response->assertStatus(200);

        $this->assertEquals(2, $q->fresh()->versions()->count());
        $this->assertEquals('PUBLISHED', $v1->fresh()->status);
        $this->assertEquals('DRAFT', $q->fresh()->currentVersion->status);
        $this->assertEquals('V2', $q->fresh()->currentVersion->stem);
    }
}
