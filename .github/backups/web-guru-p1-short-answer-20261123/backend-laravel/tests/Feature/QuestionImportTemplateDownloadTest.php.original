<?php

namespace Tests\Feature;

use App\Contexts\TenantContext;
use App\Exports\QuestionImport\QuestionImportTemplateExport;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\Support\CreatesTeacherExamScope;
use Tests\TestCase;

final class QuestionImportTemplateDownloadTest extends TestCase
{
    use RefreshDatabase;
    use CreatesTeacherExamScope;

    private Tenant $tenant;
    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Tenant Template Uji',
            'code' => 'TTU01',
            'status' => 'ACTIVE',
        ]);

        app(TenantContext::class)->setTenantId($this->tenant->id);

        $guruRole = Role::create(['name' => 'GURU', 'label' => 'Guru']);
        $this->teacher = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Guru Template Uji',
            'email' => 'guru.template@test.local',
            'password' => bcrypt('password123'),
        ]);
        $this->teacher->roles()->attach($guruRole->id);

        $this->createTeacherExamScope($this->tenant, $this->teacher, [], 'TEMPLATE');
    }

    public function test_exact_role_guru_can_download_question_template(): void
    {
        $response = $this->actingAs($this->teacher)->get(route('teacher.questions.template'));

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->assertHeader('Pragma', 'no-cache');

        $cacheDirectives = array_map('trim', explode(',', strtolower($response->headers->get('Cache-Control', ''))));
        $this->assertEqualsCanonicalizing(['private', 'no-store', 'max-age=0'], $cacheDirectives);

        $this->assertStringContainsString(
            'Template_Import_Soal_CBT-QUESTION-1.xlsx',
            $response->headers->get('Content-Disposition', '')
        );
    }

    public function test_generated_xlsx_opens_with_expected_sheets_headers_and_dropdowns(): void
    {
        $bytes = Excel::raw(new QuestionImportTemplateExport(), \Maatwebsite\Excel\Excel::XLSX);

        $this->assertStringStartsWith('PK', $bytes, 'Generated template must be a ZIP-based XLSX workbook.');

        $temporaryPath = tempnam(sys_get_temp_dir(), 'cbt-question-template-');
        $this->assertNotFalse($temporaryPath);

        try {
            file_put_contents($temporaryPath, $bytes);
            $workbook = IOFactory::load($temporaryPath);

            $this->assertSame([
                'Petunjuk',
                'Data Soal',
                'Detail Opsi',
                'Referensi Jenis Soal',
                'Contoh Soal',
                'Referensi Akademik',
            ], $workbook->getSheetNames());

            $data = $workbook->getSheetByName('Data Soal');
            $this->assertSame('External Row ID', $data->getCell('A1')->getValue());
            $this->assertSame('Jenis Soal', $data->getCell('L1')->getValue());
            $this->assertSame('Pertanyaan', $data->getCell('M1')->getValue());
            $this->assertSame(
                '"MULTIPLE_CHOICE,MULTIPLE_RESPONSE,TRUE_FALSE,MATCHING,SHORT_ANSWER,ESSAY"',
                $data->getCell('L2')->getDataValidation()->getFormula1()
            );
            $this->assertSame('"EASY,MEDIUM,HARD"', $data->getCell('P2')->getDataValidation()->getFormula1());

            $details = $workbook->getSheetByName('Detail Opsi');
            $this->assertSame('Option Key', $details->getCell('D1')->getValue());
            $this->assertSame('"A,B,C,D"', $details->getCell('D2')->getDataValidation()->getFormula1());
            $this->assertSame(
                '"OPTION,MATCH_PAIR,ACCEPTED_ANSWER"',
                $details->getCell('B2')->getDataValidation()->getFormula1()
            );

            $types = $workbook->getSheetByName('Referensi Jenis Soal');
            $this->assertStringContainsString('BLOCKED', (string) $types->getCell('D6')->getValue());
            $this->assertStringContainsString('Opsi E', (string) $types->getCell('C8')->getValue());

            $this->assertSame(
                'NEEDS_BACKEND_CONTRACT',
                $workbook->getSheetByName('Referensi Akademik')->getCell('G2')->getValue()
            );
        } finally {
            if (is_file($temporaryPath)) {
                unlink($temporaryPath);
            }
        }
    }

    public function test_unauthenticated_user_cannot_download_question_template(): void
    {
        $this->get(route('teacher.questions.template'))->assertRedirect();
    }

    public function test_single_admin_or_student_role_is_denied_from_template_download(): void
    {
        foreach ([
            ['name' => 'ADMIN_MADRASAH', 'label' => 'Admin Madrasah', 'email' => 'admin.template@test.local'],
            ['name' => 'SISWA', 'label' => 'Siswa', 'email' => 'siswa.template@test.local'],
        ] as $identity) {
            $role = Role::create([
                'name' => $identity['name'],
                'label' => $identity['label'],
            ]);

            $user = User::create([
                'tenant_id' => $this->tenant->id,
                'name' => $identity['label'].' Template Uji',
                'email' => $identity['email'],
                'password' => bcrypt('password123'),
            ]);
            $user->roles()->attach($role->id);

            $this->actingAs($user)
                ->get(route('teacher.questions.template'))
                ->assertForbidden();
        }
    }

    public function test_multi_role_account_is_denied_from_template_download(): void
    {
        $adminRole = Role::create(['name' => 'ADMIN_MADRASAH', 'label' => 'Admin Madrasah']);
        $this->teacher->roles()->attach($adminRole->id);

        $this->actingAs($this->teacher)
            ->get(route('teacher.questions.template'))
            ->assertForbidden();
    }
}
