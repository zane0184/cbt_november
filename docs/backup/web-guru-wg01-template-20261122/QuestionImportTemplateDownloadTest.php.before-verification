<?php

namespace Tests\Feature;

use App\Contexts\TenantContext;
use App\Exports\QuestionImport\QuestionImportTemplateExport;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
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
        Excel::fake();

        $response = $this->actingAs($this->teacher)->get(route('teacher.questions.template'));

        $response->assertOk();
        Excel::assertDownloaded(
            'Template_Import_Soal_CBT-QUESTION-1.xlsx',
            function (QuestionImportTemplateExport $export): bool {
                $sheets = $export->sheets();
                $titles = array_map(fn ($sheet) => $sheet->title(), $sheets);

                $this->assertSame([
                    'Petunjuk',
                    'Data Soal',
                    'Detail Opsi',
                    'Referensi Jenis Soal',
                    'Contoh Soal',
                    'Referensi Akademik',
                ], $titles);

                $typeRows = $sheets[3]->array();
                $this->assertContains('SHORT_ANSWER', array_column($typeRows, 0));
                $this->assertContains('ESSAY', array_column($typeRows, 0));
                $this->assertStringContainsString('BLOCKED', implode(' ', $typeRows[5]));
                $this->assertStringContainsString('Opsi E', implode(' ', $typeRows[7]));

                return true;
            }
        );
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
