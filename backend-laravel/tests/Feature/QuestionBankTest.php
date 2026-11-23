<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\TeacherProfile;
use App\Models\TeacherSubjectAssignment;
use App\Models\AcademicYear;
use App\Models\Semester;
use App\Models\Rombel;
use App\Models\GradeLevel;
use App\Models\Phase;
use App\Models\Subject;
use App\Models\LearningOutcome;
use App\Models\LearningObjective;
use App\Models\Question;
use App\Models\QuestionVersion;
use App\Models\MediaAsset;
use App\Livewire\Admin\QuestionBank;
use App\Services\ExcelQuestionImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;
use Tests\Concerns\CreatesCanonicalCurriculumContext;

class QuestionBankTest extends TestCase
{
    use RefreshDatabase;
    use CreatesCanonicalCurriculumContext;

    private $tenantA;
    private $tenantB;
    private $userA;
    private $userB;
    private $subjectA;
    private $cpA;
    private $tpA;
    private $assignment;
    private array $canonicalContext = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenantA = Tenant::create(['name' => 'Tenant A', 'code' => 'TA']);
        $this->tenantB = Tenant::create(['name' => 'Tenant B', 'code' => 'TB']);

        $roleGuru = Role::firstOrCreate(['name' => 'GURU'], ['label' => 'Guru']);
        $questionView = Permission::firstOrCreate(['name' => 'question.view'], ['label' => 'Lihat Bank Soal']);
        $questionManage = Permission::firstOrCreate(['name' => 'question.manage'], ['label' => 'Kelola Bank Soal']);
        $curriculumManage = Permission::firstOrCreate(['name' => 'curriculum.manage'], ['label' => 'Kelola Kurikulum']);
        $roleGuru->permissions()->syncWithoutDetaching([$questionView->id, $questionManage->id, $curriculumManage->id]);

        app(\App\Contexts\TenantContext::class)->setTenantId($this->tenantA->id);
        $this->userA = User::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Teacher A',
            'email' => 'teacher@a.com',
            'password' => bcrypt('password')
        ]);
        $this->userA->roles()->attach($roleGuru);

        $curriculumA = \App\Models\Curriculum::create(['name' => 'KM A']);
        $phaseA = \App\Models\Phase::create(['curriculum_id' => $curriculumA->id, 'name' => 'Fase A', 'code' => 'A']);
        $this->subjectA = Subject::create(['name' => 'Matematika', 'code' => 'MTK']);
        $teacherProfile = TeacherProfile::create([
            'tenant_id' => $this->tenantA->id,
            'user_id' => $this->userA->id,
            'full_name' => 'Teacher A',
        ]);
        $academicYear = AcademicYear::create(['tenant_id' => $this->tenantA->id, 'name' => '2026/2027', 'is_active' => true]);
        $semester = Semester::create(['tenant_id' => $this->tenantA->id, 'academic_year_id' => $academicYear->id, 'term' => 'GANJIL', 'is_active' => true]);
        $phaseForAssignment = Phase::create(['curriculum_id' => $curriculumA->id, 'name' => 'Fase Assignment', 'code' => 'FA']);
        $gradeLevel = GradeLevel::create(['phase_id' => $phaseForAssignment->id, 'name' => 'Kelas 4', 'code' => '4', 'level' => 4]);
        $rombel = Rombel::create(['academic_year_id' => $academicYear->id, 'grade_level_id' => $gradeLevel->id, 'name' => '4A']);
        $this->assignment = TeacherSubjectAssignment::create([
            'tenant_id' => $this->tenantA->id,
            'teacher_profile_id' => $teacherProfile->id,
            'subject_id' => $this->subjectA->id,
            'rombel_id' => $rombel->id,
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
            'starts_at' => now()->subDay()->toDateString(),
            'ends_at' => now()->addYears(5)->toDateString(),
            'status' => 'ACTIVE',
        ]);
        $this->canonicalContext = $this->createCanonicalCurriculumContext($this->assignment, $this->userA);
        $this->cpA = LearningOutcome::create([
            'curriculum_id' => $curriculumA->id,
            'phase_id' => $phaseA->id,
            'subject_id' => $this->subjectA->id,
            'code' => 'CP-MTK-01'
        ]);
        $this->tpA = LearningObjective::create([
            'learning_outcome_id' => $this->cpA->id,
            'code' => 'TP-MTK-01',
            'title' => 'Mengenal Angka',
            'description' => 'Mengenal Angka 1-10'
        ]);

        app(\App\Contexts\TenantContext::class)->setTenantId($this->tenantB->id);
        $this->userB = User::create([
            'tenant_id' => $this->tenantB->id,
            'name' => 'Teacher B',
            'email' => 'teacher@b.com',
            'password' => bcrypt('password')
        ]);
        $this->userB->roles()->attach($roleGuru);

        app(\App\Contexts\TenantContext::class)->setTenantId($this->tenantA->id);
    }

    public function test_editing_published_question_creates_new_version()
    {
        $this->actingAs($this->userA);

        $question = Question::create([
            'tenant_id' => $this->tenantA->id,
            'owner_type' => 'PERSONAL',
            'owner_user_id' => $this->userA->id,
            'created_by' => $this->userA->id
        ]);

        $v1 = $question->versions()->create([
            'tenant_id' => $this->tenantA->id,
            'major_version' => 1,
            'minor_version' => 0,
            'question_type' => 'MULTIPLE_CHOICE',
            'stem' => 'Old Stem',
            'status' => 'PUBLISHED',
            'is_current' => true,
            'created_by' => $this->userA->id
        ]);

        $response = $this->putJson("/api/v1/questions/{$question->id}", [
            'stem' => 'New Stem'
        ]);

        $response->assertStatus(200);

        $v1->refresh();
        $this->assertEquals('Old Stem', $v1->stem);
        $this->assertFalse((bool) $v1->is_current);

        $this->assertDatabaseHas('question_versions', [
            'question_id' => $question->id,
            'minor_version' => 1,
            'stem' => 'New Stem',
            'is_current' => true
        ]);
        $this->assertSame('DRAFT', $question->fresh()->status);
    }

    public function test_web_editing_published_question_creates_new_immutable_version()
    {
        $this->actingAs($this->userA);

        $question = Question::create([
            'tenant_id' => $this->tenantA->id,
            'owner_type' => 'SUBJECT',
            'subject_id' => $this->subjectA->id,
            'question_code' => 'WEB-VERSION-01',
            'status' => 'PUBLISHED',
            'created_by' => $this->userA->id,
        ]);
        $published = $question->versions()->create([
            'tenant_id' => $this->tenantA->id,
            'major_version' => 1,
            'minor_version' => 0,
            'question_type' => 'ESSAY',
            'stem' => 'Stem lama',
            'status' => 'PUBLISHED',
            'is_current' => true,
            'created_by' => $this->userA->id,
        ]);
        $this->mapQuestionVersionToCanonicalCurriculum($published, $this->canonicalContext);

        $this->questionBankComponent()
            ->call('openEditModal', $question->id)
            ->set('stem', 'Stem versi baru')
            ->call('saveQuestion')
            ->assertHasNoErrors();

        $published->refresh();
        $question->refresh();
        $current = $question->currentVersion;

        $this->assertSame('Stem lama', $published->stem);
        $this->assertFalse((bool) $published->is_current);
        $this->assertSame('DRAFT', $question->status);
        $this->assertSame('DRAFT', $current->status);
        $this->assertSame('Stem versi baru', $current->stem);
        $this->assertSame(1, (int) $current->minor_version);
    }

    public function test_tenant_isolation_on_media()
    {
        Storage::fake('local');

        app(\App\Contexts\TenantContext::class)->setTenantId($this->tenantB->id);
        $mediaB = MediaAsset::create([
            'tenant_id' => $this->tenantB->id,
            'owner_type' => 'QUESTION',
            'file_name' => 'secret.jpg',
            'original_name' => 'secret.jpg',
            'storage_path' => 'private/tenants/B/media/secret.jpg',
            'mime_type' => 'image/jpeg',
            'extension' => 'jpg',
            'file_size' => 100,
            'disk' => 'local',
            'uploaded_by' => $this->userB->id
        ]);

        $this->actingAs($this->userA);

        $response = $this->getJson("/api/v1/media/{$mediaB->id}");

        // Tenant-scoped route binding hides foreign media as not found.
        $response->assertStatus(404);
    }

    public function test_import_rejects_multiple_choice_with_multiple_correct_options()
    {
        $this->actingAs($this->userA);

        $result = app(ExcelQuestionImporter::class)->import('unused', ['rows' => [[
            'stem' => 'Pilih satu jawaban',
            'type' => 'MULTIPLE_CHOICE',
            'subject_id' => $this->subjectA->id,
            'options' => [
                ['option_key' => 'A', 'content' => 'A', 'is_correct' => true],
                ['option_key' => 'B', 'content' => 'B', 'is_correct' => true],
            ],
        ]]]);

        $this->assertFalse($result['success']);
        $this->assertSame(0, $result['imported']);
        $this->assertNotEmpty($result['errors']);
        $this->assertDatabaseCount('questions', 0);
    }

    public function test_api_rejects_multiple_choice_without_exactly_one_correct_option()
    {
        $this->actingAs($this->userA);

        $response = $this->postJson('/api/v1/questions', [
            'question_type' => 'MULTIPLE_CHOICE',
            'stem' => 'Pilih jawaban yang benar',
            'owner_type' => 'PERSONAL',
            'difficulty' => 'MEDIUM',
            'default_points' => 1,
            'subject_id' => $this->subjectA->id,
            'options' => [
                ['option_key' => 'A', 'content' => 'Pilihan A', 'is_correct' => false],
                ['option_key' => 'B', 'content' => 'Pilihan B', 'is_correct' => false],
            ],
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['options']);
        $this->assertDatabaseCount('questions', 0);
    }

    public function test_cannot_create_question_with_other_tenant_curriculum()
    {
        app(\App\Contexts\TenantContext::class)->setTenantId($this->tenantB->id);
        $currB = \App\Models\Curriculum::create(['name' => 'Curr B']);

        $this->actingAs($this->userA);

        $response = $this->postJson('/api/v1/questions', [
            'question_type' => 'MULTIPLE_CHOICE',
            'stem' => 'Is this safe?',
            'owner_type' => 'PERSONAL',
            'difficulty' => 'MEDIUM',
            'default_points' => 1,
            'curriculum_id' => $currB->id
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['curriculum_id']);
    }

    /** Web Admin Livewire Tests **/

    public function test_question_bank_filters_reset_pagination()
    {
        $this->actingAs($this->userA);

        $this->questionBankComponent()
            ->call('gotoPage', 2)
            ->set('subjectFilter', $this->subjectA->id)
            ->assertViewHas('questions', fn ($questions) => $questions->currentPage() === 1)
            ->call('gotoPage', 2)
            ->set('statusFilter', 'PUBLISHED')
            ->assertViewHas('questions', fn ($questions) => $questions->currentPage() === 1);
    }

    public function test_web_admin_create_and_update_multiple_response_question()
    {
        $this->actingAs($this->userA);

        $this->questionBankComponent()
            ->set('questionCode', 'MR-01')
            ->set('questionType', 'MULTIPLE_RESPONSE')
            ->set('selectedSubjectId', $this->subjectA->id)
            ->set('selectedCpIds', [$this->canonicalContext['cp']->id])
            ->set('selectedOutcomeIds', [$this->canonicalContext['tp']->id])
            ->set('stem', 'Pilihlah bilangan genap!')
            ->set('defaultPoints', 10)
            ->set('optionA', '2')
            ->set('optionB', '3')
            ->set('optionC', '4')
            ->set('optionD', '5')
            ->set('correctOptions', ['A', 'C'])
            ->call('saveQuestion')
            ->assertHasNoErrors();

        $question = Question::where('question_code', 'MR-01')->first();
        $this->assertNotNull($question);

        $version = $question->currentVersion;
        $this->assertEquals('MULTIPLE_RESPONSE', $version->question_type);
        $this->assertDatabaseHas('question_version_curriculum_mappings', [
            'tenant_id' => $this->tenantA->id,
            'question_version_id' => $version->id,
            'multi_learning_outcome_id' => $this->canonicalContext['cp']->id,
            'mapping_role' => 'CP',
        ]);
        $this->assertDatabaseHas('question_version_tenant_outcome_mappings', [
            'tenant_id' => $this->tenantA->id,
            'question_version_id' => $version->id,
            'tenant_learning_outcome_id' => $this->canonicalContext['tp']->id,
            'mapping_role' => 'TP',
        ]);

        $correctKeys = $version->options->where('is_correct', true)->pluck('option_key')->toArray();
        $this->assertEquals(['A', 'C'], $correctKeys);

        // Edit question to change correct options
        $this->questionBankComponent()
            ->call('openEditModal', $question->id)
            ->set('correctOptions', ['A', 'B'])
            ->call('saveQuestion')
            ->assertHasNoErrors();

        $version->refresh();
        $newCorrectKeys = $version->options->where('is_correct', true)->pluck('option_key')->toArray();
        $this->assertEquals(['A', 'B'], $newCorrectKeys);
    }

    public function test_web_admin_multiple_choice_rejects_correct_key_for_empty_option()
    {
        $this->actingAs($this->userA);

        $this->questionBankComponent()
            ->set('questionCode', 'MC-EMPTY-KEY')
            ->set('questionType', 'MULTIPLE_CHOICE')
            ->set('selectedSubjectId', $this->subjectA->id)
            ->set('stem', 'Kunci tidak boleh menunjuk opsi kosong')
            ->set('defaultPoints', 1)
            ->set('optionA', 'Pilihan A')
            ->set('optionB', 'Pilihan B')
            ->set('optionC', '')
            ->set('correctOption', 'C')
            ->call('saveQuestion')
            ->assertHasErrors(['correctOption']);

        $this->assertDatabaseMissing('questions', ['question_code' => 'MC-EMPTY-KEY']);
    }

    public function test_web_admin_multiple_response_rejects_key_for_empty_option()
    {
        $this->actingAs($this->userA);

        $this->questionBankComponent()
            ->set('questionCode', 'MR-EMPTY-KEY')
            ->set('questionType', 'MULTIPLE_RESPONSE')
            ->set('selectedSubjectId', $this->subjectA->id)
            ->set('stem', 'Kunci kompleks tidak boleh menunjuk opsi kosong')
            ->set('defaultPoints', 1)
            ->set('optionA', 'Pilihan A')
            ->set('optionB', 'Pilihan B')
            ->set('optionC', '')
            ->set('correctOptions', ['C'])
            ->call('saveQuestion')
            ->assertHasErrors(['correctOptions']);

        $this->assertDatabaseMissing('questions', ['question_code' => 'MR-EMPTY-KEY']);
    }

    public function test_web_admin_multiple_response_validation_fails_when_no_correct_option_selected()
    {
        $this->actingAs($this->userA);

        $this->questionBankComponent()
            ->set('questionCode', 'MR-INVALID')
            ->set('questionType', 'MULTIPLE_RESPONSE')
            ->set('selectedSubjectId', $this->subjectA->id)
            ->set('stem', 'Pilih opsi tanpa kunci')
            ->set('optionA', 'A')
            ->set('optionB', 'B')
            ->set('correctOptions', [])
            ->call('saveQuestion')
            ->assertHasErrors(['correctOptions']);
    }

    public function test_web_admin_create_and_update_matching_question()
    {
        $this->actingAs($this->userA);

        $this->questionBankComponent()
            ->set('questionCode', 'MATCH-01')
            ->set('questionType', 'MATCHING')
            ->set('selectedSubjectId', $this->subjectA->id)
            ->set('stem', 'Jodohkan bahasa Inggris dan Indonesia!')
            ->set('defaultPoints', 10)
            ->set('matchingPairs', [
                ['left' => 'Red', 'right' => 'Merah'],
                ['left' => 'Blue', 'right' => 'Biru']
            ])
            ->call('saveQuestion')
            ->assertHasNoErrors();

        $question = Question::where('question_code', 'MATCH-01')->first();
        $this->assertNotNull($question);

        $version = $question->currentVersion;
        $this->assertEquals('MATCHING', $version->question_type);
        $this->assertCount(4, $version->options); // 2 left, 2 right

        $leftOptions = $version->options->filter(fn($o) => !empty($o->option_key) && empty($o->matching_key));
        $rightOptions = $version->options->filter(fn($o) => !empty($o->matching_key));

        $this->assertCount(2, $leftOptions);
        $this->assertCount(2, $rightOptions);
    }

    public function test_web_admin_cp_and_tp_cross_tenant_rejection()
    {
        $this->actingAs($this->userA);

        app(\App\Contexts\TenantContext::class)->setTenantId($this->tenantB->id);
        $currB = \App\Models\Curriculum::create(['name' => 'KM B']);
        $phaseB = \App\Models\Phase::create(['curriculum_id' => $currB->id, 'name' => 'Fase B', 'code' => 'B']);
        $subjectB = Subject::create(['name' => 'IPA', 'code' => 'IPA']);
        $cpB = LearningOutcome::create(['curriculum_id' => $currB->id, 'phase_id' => $phaseB->id, 'subject_id' => $subjectB->id, 'code' => 'CP-IPA-01']);
        $tpB = LearningObjective::create(['learning_outcome_id' => $cpB->id, 'code' => 'TP-IPA-01', 'title' => 'Sel', 'description' => 'Sel']);

        app(\App\Contexts\TenantContext::class)->setTenantId($this->tenantA->id);

        $this->questionBankComponent()
            ->set('questionCode', 'REJECT-CP')
            ->set('questionType', 'MULTIPLE_CHOICE')
            ->set('selectedSubjectId', $this->subjectA->id)
            ->set('selectedCpIds', [$cpB->id])
            ->set('stem', 'Pengujian CP Lintas Tenant')
            ->set('optionA', 'A')
            ->set('optionB', 'B')
            ->set('correctOption', 'A')
            ->call('saveQuestion')
            ->assertHasErrors(['selectedCpIds']);
    }

    public function test_web_admin_tp_unmatched_cp_rejection()
    {
        $this->actingAs($this->userA);

        $cp2 = LearningOutcome::create(['curriculum_id' => $this->cpA->curriculum_id, 'phase_id' => $this->cpA->phase_id, 'subject_id' => $this->subjectA->id, 'code' => 'CP-2']);
        $tp2 = LearningObjective::create(['learning_outcome_id' => $cp2->id, 'code' => 'TP-2', 'title' => 'Title 2', 'description' => 'Desc 2']);

        $this->questionBankComponent()
            ->set('questionCode', 'REJECT-TP')
            ->set('questionType', 'MULTIPLE_CHOICE')
            ->set('selectedSubjectId', $this->subjectA->id)
            ->set('selectedCpIds', [$this->canonicalContext['cp']->id])
            ->set('selectedOutcomeIds', [$tp2->id]) // tp2 belongs to cp2, not cpA!
            ->set('stem', 'Pengujian TP Tidak Sesuai CP')
            ->set('optionA', 'A')
            ->set('optionB', 'B')
            ->set('correctOption', 'A')
            ->call('saveQuestion')
            ->assertHasErrors(['selectedOutcomeIds']);
    }

    /** REST API Tests **/

    public function test_api_store_and_update_with_cp_tp()
    {
        $this->actingAs($this->userA);

        $storeResponse = $this->postJson('/api/v1/questions', [
            'question_type' => 'MULTIPLE_CHOICE',
            'stem' => 'Berapa 1 + 1?',
            'owner_type' => 'PERSONAL',
            'subject_id' => $this->subjectA->id,
            'learning_outcome_id' => $this->cpA->id,
            'learning_objective_id' => $this->tpA->id,
            'difficulty' => 'EASY',
            'default_points' => 10,
            'options' => [
                ['content' => '2', 'is_correct' => true, 'option_key' => 'A'],
                ['content' => '3', 'is_correct' => false, 'option_key' => 'B']
            ]
        ]);

        $storeResponse->assertStatus(201);
        $questionId = $storeResponse->json('data.id');
        $this->assertEquals($this->cpA->id, $storeResponse->json('data.current_version.learning_outcome_id'));
        $this->assertEquals($this->tpA->id, $storeResponse->json('data.current_version.learning_objective_id'));

        // Update API
        $updateResponse = $this->putJson("/api/v1/questions/{$questionId}", [
            'stem' => 'Berapa 1 + 1 (Update)?'
        ]);

        $updateResponse->assertStatus(200);
        $this->assertEquals('Berapa 1 + 1 (Update)?', $updateResponse->json('data.current_version.stem'));
        $this->assertEquals($this->cpA->id, $updateResponse->json('data.current_version.learning_outcome_id'));
    }

    public function test_api_rejects_invalid_cp_tp_relationships()
    {
        $this->actingAs($this->userA);

        app(\App\Contexts\TenantContext::class)->setTenantId($this->tenantB->id);
        $currB = \App\Models\Curriculum::create(['name' => 'KM B2']);
        $phaseB = \App\Models\Phase::create(['curriculum_id' => $currB->id, 'name' => 'Fase B2', 'code' => 'B2']);
        $subjectB = Subject::create(['name' => 'Subject B', 'code' => 'SB']);
        $cpB = LearningOutcome::create(['curriculum_id' => $currB->id, 'phase_id' => $phaseB->id, 'subject_id' => $subjectB->id, 'code' => 'CP-B']);
        $tpB = LearningObjective::create(['learning_outcome_id' => $cpB->id, 'code' => 'TP-B', 'title' => 'B', 'description' => 'Desc B']);
        app(\App\Contexts\TenantContext::class)->setTenantId($this->tenantA->id);

        $response = $this->postJson('/api/v1/questions', [
            'question_type' => 'MULTIPLE_CHOICE',
            'stem' => 'Cross tenant CP test',
            'owner_type' => 'PERSONAL',
            'subject_id' => $this->subjectA->id,
            'learning_outcome_id' => $cpB->id,
            'difficulty' => 'EASY',
            'default_points' => 10
        ]);

        $response->assertStatus(422);
    }
    private function questionBankComponent()
    {
        return Livewire::test(QuestionBank::class)
            ->set('selectedAssignmentId', $this->assignment->id)
            ->set('selectedSubjectId', $this->subjectA->id)
            ->set('selectedCpIds', [$this->canonicalContext['cp']->id])
            ->set('selectedOutcomeIds', [$this->canonicalContext['tp']->id]);
    }

}

