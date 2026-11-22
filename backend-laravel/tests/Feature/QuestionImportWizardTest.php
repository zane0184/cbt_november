<?php

namespace Tests\Feature;

use App\Contexts\TenantContext;
use App\Exports\QuestionImport\QuestionImportTemplateExport;
use App\Livewire\Teacher\QuestionImportWizard;
use App\Models\Curriculum;
use App\Models\GradeLevel;
use App\Models\Permission;
use App\Models\Phase;
use App\Models\Question;
use App\Models\Role;
use App\Models\Subject;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ExcelQuestionImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\Support\CreatesTeacherExamScope;
use Tests\TestCase;

final class QuestionImportWizardTest extends TestCase
{
    use RefreshDatabase;
    use CreatesTeacherExamScope;

    private Tenant $tenant;
    private User $teacher;
    private Subject $subject;
    private GradeLevel $gradeLevel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Tenant Import Wizard',
            'code' => 'TIW01',
            'status' => 'ACTIVE',
        ]);
        app(TenantContext::class)->setTenantId($this->tenant->id);

        $role = Role::create(['name' => 'GURU', 'label' => 'Guru']);
        $permissions = collect(['question.view', 'question.manage'])
            ->map(fn (string $name) => Permission::firstOrCreate(['name' => $name], ['label' => $name]));
        $role->permissions()->sync($permissions->pluck('id')->all());

        $this->teacher = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Guru Import Wizard',
            'email' => 'guru.importwizard@test.local',
            'password' => bcrypt('password123'),
        ]);
        $this->teacher->roles()->attach($role->id);

        $scope = $this->createTeacherExamScope($this->tenant, $this->teacher, [], 'IMPORTWIZ');
        $this->subject = $scope['subject'];
        $this->gradeLevel = $scope['rombel']->gradeLevel;

        $this->gradeLevel->update(['status' => 'ACTIVE']);
        $phase = Phase::query()->findOrFail($this->gradeLevel->phase_id);
        $phase->update(['status' => 'ACTIVE']);
        Curriculum::query()->whereKey($phase->curriculum_id)->update(['status' => 'ACTIVE']);
    }

    public function test_guru_can_preview_and_atomically_import_supported_xlsx_questions(): void
    {
        $file = $this->makeWorkbook(
            [[
                'EXT-001', '1', 'IMP-001', $this->subject->id, $this->gradeLevel->id,
                '', '', '', '', '', '', 'MULTIPLE_CHOICE', 'Berapakah 2 + 2?',
                'Pembahasan: hasilnya empat.', 5, 'EASY', '', '', '',
            ]],
            [
                ['1', 'OPTION', 1, 'A', '3', '', 'FALSE', '', ''],
                ['1', 'OPTION', 2, 'B', '4', '', 'TRUE', '', ''],
            ]
        );

        $component = Livewire::actingAs($this->teacher)
            ->test(QuestionImportWizard::class)
            ->set('workbook', $file)
            ->call('previewImport')
            ->assertSet('previewReady', true)
            ->assertSet('totalRows', 1)
            ->assertSet('validRows', 1)
            ->assertSee('Berapakah 2 + 2?', false);

        $component->call('confirmImport')
            ->assertRedirect(route('teacher.questions.index'));

        $question = Question::query()->where('question_code', 'IMP-001')->firstOrFail();
        $this->assertSame($this->subject->id, $question->subject_id);
        $this->assertSame($this->gradeLevel->id, $question->grade_level_id);
        $this->assertSame($this->gradeLevel->phase_id, $question->phase_id);

        $version = $question->currentVersion;
        $this->assertNotNull($version);
        $this->assertSame('MULTIPLE_CHOICE', $version->question_type);
        $this->assertSame('Berapakah 2 + 2?', $version->stem);
        $this->assertSame('Pembahasan: hasilnya empat.', $version->explanation);
        $this->assertSame(5.0, (float) $version->default_points);
        $this->assertCount(2, $version->options);
        $this->assertSame('B', $version->options->firstWhere('is_correct', true)?->option_key);
    }

    public function test_import_preview_rejects_kbc_reference_until_versioned_snapshot_contract_is_ready(): void
    {
        $row = [
            'EXT-002', '2', 'IMP-002', $this->subject->id, $this->gradeLevel->id,
            '', '', '', '', '018f0000-0000-7000-8000-000000000001', '', 'ESSAY',
            'Jelaskan proses daur air.', 'Rubrik manual.', 5, 'MEDIUM', '', '', '',
        ];

        Livewire::actingAs($this->teacher)
            ->test(QuestionImportWizard::class)
            ->set('workbook', $this->makeWorkbook([$row], []))
            ->call('previewImport')
            ->assertSet('previewReady', false)
            ->assertSee('Pemetaan KBC menunggu kontrak mapping versi soal dan snapshot');

        $this->assertSame(0, Question::query()->count());
    }

    public function test_import_preview_rejects_unsupported_short_answer_type(): void
    {
        $row = [
            'EXT-003', '3', 'IMP-003', $this->subject->id, $this->gradeLevel->id,
            '', '', '', '', '', '', 'SHORT_ANSWER', 'Jawab singkat.', '', 1, 'EASY', '', '', '',
        ];

        Livewire::actingAs($this->teacher)
            ->test(QuestionImportWizard::class)
            ->set('workbook', $this->makeWorkbook([$row], []))
            ->call('previewImport')
            ->assertSet('previewReady', false)
            ->assertSee('Jenis soal tidak didukung atau masih berstatus BLOCKED');

        $this->assertSame(0, Question::query()->count());
    }

    public function test_importer_rolls_back_all_rows_and_reports_zero_imported_on_batch_failure(): void
    {
        $rows = [
            [
                'stem' => 'Soal valid',
                'type' => 'MULTIPLE_CHOICE',
                'subject_id' => $this->subject->id,
                'question_code' => 'BATCH-VALID',
                'grade_level_id' => $this->gradeLevel->id,
                'phase_id' => $this->gradeLevel->phase_id,
                'curriculum_id' => $this->gradeLevel->phase->curriculum_id,
                'options' => [
                    ['option_key' => 'A', 'content' => 'Salah', 'is_correct' => false],
                    ['option_key' => 'B', 'content' => 'Benar', 'is_correct' => true],
                ],
            ],
            [
                'stem' => 'Soal tidak didukung',
                'type' => 'SHORT_ANSWER',
                'subject_id' => $this->subject->id,
                'options' => [],
            ],
        ];

        $result = app(ExcelQuestionImporter::class)->import('not-used', ['rows' => $rows]);

        $this->assertFalse($result['success']);
        $this->assertSame(0, $result['imported']);
        $this->assertSame(2, $result['total_rows']);
        $this->assertSame(0, Question::query()->count());
    }

    private function makeWorkbook(array $dataRows, array $detailRows): UploadedFile
    {
        $bytes = Excel::raw(new QuestionImportTemplateExport(), \Maatwebsite\Excel\Excel::XLSX);
        $source = tempnam(sys_get_temp_dir(), 'cbt-question-import-source-');
        $target = tempnam(sys_get_temp_dir(), 'cbt-question-import-target-');

        try {
            file_put_contents($source, $bytes);
            $workbook = IOFactory::load($source);
            if ($dataRows !== []) {
                $workbook->getSheetByName('Data Soal')->fromArray($dataRows, null, 'A2');
            }
            if ($detailRows !== []) {
                $workbook->getSheetByName('Detail Opsi')->fromArray($detailRows, null, 'A2');
            }

            IOFactory::createWriter($workbook, 'Xlsx')->save($target);
            $workbook->disconnectWorksheets();

            return UploadedFile::fake()->createWithContent('questions.xlsx', file_get_contents($target));
        } finally {
            if (is_file($source)) unlink($source);
            if (is_file($target)) unlink($target);
        }
    }
}
