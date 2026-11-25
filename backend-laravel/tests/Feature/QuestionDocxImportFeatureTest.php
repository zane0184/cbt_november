<?php

namespace Tests\Feature;

use App\Livewire\Teacher\QuestionDocxImport;
use App\Models\AcademicYear;
use App\Models\Curriculum;
use App\Models\GradeLevel;
use App\Models\Phase;
use App\Models\Role;
use App\Models\Rombel;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\TeacherSubjectAssignment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\Concerns\CreatesCanonicalCurriculumContext;
use Tests\TestCase;
use ZipArchive;

class QuestionDocxImportFeatureTest extends TestCase
{
    use RefreshDatabase;
    use CreatesCanonicalCurriculumContext;

    private Tenant $tenant;
    private User $teacher;
    private TeacherSubjectAssignment $assignment;
    private array $curriculumContext;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create(['name' => 'DOCX Import Feature Test', 'code' => 'DOCXFT']);
        $this->setTenantContext($this->tenant->id);
        $this->bootstrapCbtRolePermissions();

        $guruRole = Role::query()->where('name', 'GURU')->firstOrFail();
        $this->teacher = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Guru DOCX Test',
            'email' => 'guru.docx@example.test',
            'password' => bcrypt('Acceptance-Password-2026'),
        ]);
        $this->teacher->roles()->sync([$guruRole->id]);

        $profile = TeacherProfile::create($this->withSyntheticTeacherIdentity([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->teacher->id,
            'full_name' => 'Guru DOCX Test',
        ]));

        $curriculum = Curriculum::create([
            'name' => 'Kurikulum Merdeka',
            'code' => 'K-MERDEKA-DOCX',
            'is_active' => true,
        ]);
        $phase = Phase::create([
            'curriculum_id' => $curriculum->id,
            'name' => 'Fase C',
            'code' => 'C',
        ]);
        $academicYear = AcademicYear::create([
            'tenant_id' => $this->tenant->id,
            'name' => '2026/2027',
            'is_active' => true,
        ]);
        $semester = Semester::create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $academicYear->id,
            'term' => 'GANJIL',
            'is_active' => true,
        ]);
        $gradeLevel = GradeLevel::create([
            'phase_id' => $phase->id,
            'name' => 'Kelas 6',
            'code' => '6',
            'level' => 6,
        ]);
        $subject = Subject::create([
            'tenant_id' => $this->tenant->id,
            'code' => 'FIKIH',
            'name' => 'Fikih',
            'short_name' => 'FIQ',
            'status' => 'ACTIVE',
        ]);
        $rombel = Rombel::create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $academicYear->id,
            'grade_level_id' => $gradeLevel->id,
            'name' => 'Kelas 6-A',
            'status' => 'ACTIVE',
        ]);

        $this->assignment = TeacherSubjectAssignment::create([
            'tenant_id' => $this->tenant->id,
            'teacher_profile_id' => $profile->id,
            'subject_id' => $subject->id,
            'rombel_id' => $rombel->id,
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
            'starts_at' => now()->subDay()->toDateString(),
            'ends_at' => now()->addYears(5)->toDateString(),
            'status' => 'ACTIVE',
            'created_by' => $this->teacher->id,
        ]);

        $this->curriculumContext = $this->createCanonicalCurriculumContext($this->assignment, $this->teacher);
        $this->actingAs($this->teacher);
    }

    public function test_teacher_can_upload_preview_and_save_docx_question_as_draft_with_curriculum_mapping(): void
    {
        Livewire::test(QuestionDocxImport::class)
            ->set('file', $this->uploadedDocx())
            ->set('selectedAssignmentId', $this->assignment->id)
            ->call('preview')
            ->assertHasNoErrors()
            ->assertSet('hasPreview', true)
            ->assertSet('summary.total', 1)
            ->set('selectedCpIds', [$this->curriculumContext['cp']->id])
            ->set('selectedOutcomeIds', [$this->curriculumContext['tp']->id])
            ->call('importDrafts')
            ->assertHasNoErrors()
            ->assertSee('1 soal berhasil disimpan sebagai DRAFT');

        $this->assertDatabaseHas('questions', [
            'tenant_id' => $this->tenant->id,
            'subject_id' => $this->assignment->subject_id,
            'status' => 'DRAFT',
            'created_by' => $this->teacher->id,
        ]);

        $question = \App\Models\Question::query()
            ->where('tenant_id', $this->tenant->id)
            ->where('created_by', $this->teacher->id)
            ->firstOrFail();
        $version = $question->currentVersion;

        $this->assertSame('MULTIPLE_CHOICE', $version->question_type);
        $this->assertSame('DOCX', $version->metadata['import_source']);
        $this->assertSame('DRAFT', $version->status);
        $this->assertDatabaseCount('question_options', 2);
        $this->assertDatabaseHas('question_docx_import_batches', [
            'tenant_id' => $this->tenant->id,
            'teacher_subject_assignment_id' => $this->assignment->id,
            'imported_count' => 1,
        ]);
        $this->assertDatabaseHas('question_version_curriculum_mappings', [
            'tenant_id' => $this->tenant->id,
            'question_version_id' => $version->id,
            'mapping_role' => 'CP',
        ]);
    }


    public function test_arabic_text_and_harakat_survive_docx_preview_import_and_mysql_round_trip(): void
    {
        $arabicStem = 'بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ';
        $arabicOptionA = 'جَوَابٌ أَوَّلُ';
        $arabicOptionB = 'جَوَابٌ ثَانٍ';
        $bytes = $this->makeDocxBytes(true, false, $arabicStem, $arabicOptionA, $arabicOptionB);

        Livewire::test(QuestionDocxImport::class)
            ->set('file', $this->uploadedDocx($bytes))
            ->set('selectedAssignmentId', $this->assignment->id)
            ->call('preview')
            ->assertHasNoErrors()
            ->assertSet('hasPreview', true)
            ->assertSet('questions.0.stem', $arabicStem)
            ->assertSet('questions.0.options.0.content', $arabicOptionA)
            ->assertSet('questions.0.options.1.content', $arabicOptionB)
            ->set('selectedCpIds', [$this->curriculumContext['cp']->id])
            ->set('selectedOutcomeIds', [$this->curriculumContext['tp']->id])
            ->call('importDrafts')
            ->assertHasNoErrors()
            ->assertSee('1 soal berhasil disimpan sebagai DRAFT');

        $question = \App\Models\Question::query()
            ->where('tenant_id', $this->tenant->id)
            ->where('created_by', $this->teacher->id)
            ->firstOrFail();
        $version = $question->currentVersion()->firstOrFail();
        $options = $version->options()->orderBy('sequence')->get();

        $this->assertSame($arabicStem, $version->fresh()->stem);
        $this->assertSame([$arabicOptionA, $arabicOptionB], $options->pluck('content')->all());
        $this->assertSame($arabicStem, \App\Models\QuestionVersion::query()->findOrFail($version->id)->stem);
    }

    public function test_teacher_can_import_docx_without_answer_key_or_score_weight_as_draft(): void
    {
        $bytes = $this->makeDocxBytes(false);
        Livewire::test(QuestionDocxImport::class)
            ->set('file', $this->uploadedDocx($bytes))
            ->set('selectedAssignmentId', $this->assignment->id)
            ->call('preview')
            ->assertHasNoErrors()
            ->assertSet('summary.total', 1)
            ->assertSet('summary.valid', 1)
            ->set('selectedCpIds', [$this->curriculumContext['cp']->id])
            ->set('selectedOutcomeIds', [$this->curriculumContext['tp']->id])
            ->call('importDrafts')
            ->assertHasNoErrors()
            ->assertSee('1 soal berhasil disimpan sebagai DRAFT');

        $question = \App\Models\Question::query()
            ->where('tenant_id', $this->tenant->id)
            ->where('created_by', $this->teacher->id)
            ->firstOrFail();
        $version = $question->currentVersion;

        $this->assertSame('DRAFT', $version->status);
        $this->assertSame('0.00', number_format((float) $version->default_points, 2, '.', ''));
        $this->assertSame(0, $version->options()->where('is_correct', true)->count());
    }


    public function test_multi_subject_docx_is_previewed_but_blocked_from_single_assignment_import(): void
    {
        Livewire::test(QuestionDocxImport::class)
            ->set('file', $this->uploadedDocx($this->makeDocxBytes(true, true)))
            ->set('selectedAssignmentId', $this->assignment->id)
            ->call('preview')
            ->assertHasNoErrors()
            ->assertSet('requiresSectionSplit', true)
            ->assertSee('Dokumen memuat beberapa bagian mata pelajaran')
            ->call('importDrafts')
            ->assertHasErrors('docx');

        $this->assertDatabaseCount('questions', 0);
        $this->assertDatabaseCount('question_docx_import_batches', 0);
    }

    public function test_same_docx_cannot_be_imported_twice_into_the_same_assignment(): void
    {
        $sameDocxBytes = $this->makeDocxBytes();
        $first = Livewire::test(QuestionDocxImport::class)
            ->set('file', $this->uploadedDocx($sameDocxBytes))
            ->set('selectedAssignmentId', $this->assignment->id)
            ->call('preview')
            ->set('selectedCpIds', [$this->curriculumContext['cp']->id])
            ->set('selectedOutcomeIds', [$this->curriculumContext['tp']->id])
            ->call('importDrafts');
        $first->assertHasNoErrors();

        Livewire::test(QuestionDocxImport::class)
            ->set('file', $this->uploadedDocx($sameDocxBytes))
            ->set('selectedAssignmentId', $this->assignment->id)
            ->call('preview')
            ->set('selectedCpIds', [$this->curriculumContext['cp']->id])
            ->set('selectedOutcomeIds', [$this->curriculumContext['tp']->id])
            ->call('importDrafts')
            ->assertHasErrors('docx');

        $this->assertDatabaseCount('questions', 1);
        $this->assertDatabaseCount('question_docx_import_batches', 1);
    }

    private function uploadedDocx(?string $bytes = null): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('soal.docx', $bytes ?? $this->makeDocxBytes());
    }

    private function makeDocxBytes(bool $includeAnswerKey = true, bool $includeSecondSubject = false, string $stem = 'Ibu kota Indonesia adalah ....', string $optionA = 'Bandung', string $optionB = 'Jakarta'): string
    {
        $path = tempnam(sys_get_temp_dir(), 'cbt-docx-feature-');
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Cannot create DOCX feature fixture.');
        }

        $document = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>'
            . ($includeSecondSubject ? '<w:p><w:r><w:t>MATEMATIKA KELAS 6</w:t></w:r></w:p>' : '')
            . '<w:p><w:r><w:t>SOAL 1. [PG] ' . htmlspecialchars($stem, ENT_XML1 | ENT_COMPAT, 'UTF-8') . '</w:t></w:r></w:p>'
            . '<w:p><w:r><w:t>A. ' . htmlspecialchars($optionA, ENT_XML1 | ENT_COMPAT, 'UTF-8') . '</w:t></w:r></w:p>'
            . '<w:p><w:r><w:t>B. ' . htmlspecialchars($optionB, ENT_XML1 | ENT_COMPAT, 'UTF-8') . '</w:t></w:r></w:p>'
            . ($includeAnswerKey ? '<w:p><w:r><w:t>KUNCI: B</w:t></w:r></w:p>' : '')
            . ($includeSecondSubject
                ? '<w:p><w:r><w:t>BAHASA INDONESIA KELAS 6</w:t></w:r></w:p>'
                    . '<w:p><w:r><w:t>SOAL 1. [PG] Gagasan utama bacaan adalah ....</w:t></w:r></w:p>'
                    . '<w:p><w:r><w:t>A. Membaca buku</w:t></w:r></w:p>'
                    . '<w:p><w:r><w:t>B. Bermain bola</w:t></w:r></w:p>'
                : '')
            . '<w:sectPr/></w:body></w:document>';
        $contentTypes = '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>';
        $relationships = '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>';

        $zip->addFromString('[Content_Types].xml', $contentTypes);
        $zip->addFromString('_rels/.rels', $relationships);
        $zip->addFromString('word/document.xml', $document);
        $zip->close();

        $bytes = file_get_contents($path);
        @unlink($path);

        return $bytes;
    }


}
