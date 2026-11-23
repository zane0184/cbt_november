<?php

namespace Tests\Feature;

use App\Contexts\TenantContext;
use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptScore;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Reporting\ReportHeaderService;
use App\Services\RolePermissionBootstrapper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class Phase7DReportingTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenantA;
    private Tenant $tenantB;
    private User $adminA;
    private User $guruA;
    private User $studentA;
    private User $adminB;
    private Exam $examA;
    private Exam $examB;
    private ExamAttempt $attemptA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantA = Tenant::create([
            'name' => 'MI Al-Hidayah Jakarta',
            'code' => 'TEN_HIDAYAH',
            'nsm' => '111231710001',
            'npsn' => '60700001',
            'address' => 'Jl. Kebon Jeruk No. 12, Jakarta Barat',
            'phone' => '021-5551234',
            'email' => 'info@alhidayah.sch.id',
            'logo_path' => null,
            'status' => 'ACTIVE'
        ]);

        $this->tenantB = Tenant::create([
            'name' => 'MI Nurul Iman Bandung',
            'code' => 'TEN_NURUL',
            'nsm' => '111231710002',
            'npsn' => '60700002',
            'address' => 'Jl. Soekarno Hatta No. 45, Bandung',
            'phone' => '022-7771234',
            'email' => 'info@nuruliman.sch.id',
            'logo_path' => null,
            'status' => 'ACTIVE'
        ]);

        // Setup Tenant A resources
        app(TenantContext::class)->setTenantId($this->tenantA->id);

        $adminRoleA = Role::firstOrCreate(['name' => 'ADMIN_MADRASAH'], ['label' => 'Admin Madrasah']);
        $guruRoleA = Role::firstOrCreate(['name' => 'GURU'], ['label' => 'Guru']);
        $siswaRoleA = Role::firstOrCreate(['name' => 'SISWA'], ['label' => 'Siswa']);        app(RolePermissionBootstrapper::class)->bootstrap();

        $this->adminA = User::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Admin Al-Hidayah',
            'email' => 'admin@alhidayah.sch.id',
            'password' => bcrypt('password'),
        ]);
        $this->adminA->roles()->attach($adminRoleA);
        $this->adminA->load('roles');

        $this->guruA = User::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Guru Matematika',
            'email' => 'guru@alhidayah.sch.id',
            'password' => bcrypt('password'),
        ]);
        $this->guruA->roles()->attach($guruRoleA);
        $this->guruA->load('roles');

        $this->studentA = User::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Ahmad Student',
            'email' => 'ahmad@alhidayah.sch.id',
            'password' => bcrypt('password'),
        ]);
        $this->studentA->roles()->attach($siswaRoleA);
        $this->studentA->load('roles');

        $ayA = AcademicYear::create([
            'tenant_id' => $this->tenantA->id,
            'name' => '2024/2025',
            'status' => 'ACTIVE'
        ]);

        $assessmentA = Assessment::create([
            'tenant_id' => $this->tenantA->id,
            'academic_year_id' => $ayA->id,
            'title' => 'Penilaian Harian 1',
            'assessment_type' => 'FORMATIVE',
            'created_by' => $this->guruA->id
        ]);

        $this->examA = Exam::create([
            'tenant_id' => $this->tenantA->id,
            'assessment_id' => $assessmentA->id,
            'title' => 'Ujian Matematika Bab 1',
            'duration_seconds' => 3600,
            'created_by' => $this->guruA->id,
            'status' => 'SCHEDULED',
            'start_at' => now()->subHour(),
            'end_at' => now()->addHour(),
            'locked_at' => now(),
            'show_result' => true
        ]);

        $participantA = $this->examA->participants()->create([
            'tenant_id' => $this->tenantA->id,
            'user_id' => $this->studentA->id
        ]);

        $this->attemptA = ExamAttempt::create([
            'tenant_id' => $this->tenantA->id,
            'exam_id' => $this->examA->id,
            'participant_id' => $participantA->id,
            'attempt_number' => 1,
            'status' => 'SUBMITTED',
            'started_at' => now()->subMinutes(30),
            'expires_at' => now()->addMinutes(30),
            'submitted_at' => now()
        ]);

        ExamAttemptScore::create([
            'tenant_id' => $this->tenantA->id,
            'exam_id' => $this->examA->id,
            'attempt_id' => $this->attemptA->id,
            'student_id' => $this->studentA->id,
            'scoring_version' => 1,
            'raw_score' => 85.0,
            'max_score' => 100.0,
            'percentage' => 85.0,
            'grading_status' => 'FINALIZED',
            'finalized_at' => now()
        ]);

        // Setup Tenant B resources
        app(TenantContext::class)->setTenantId($this->tenantB->id);

        $adminRoleB = Role::firstOrCreate(['name' => 'ADMIN_MADRASAH'], ['label' => 'Admin Madrasah']);

        $this->adminB = User::create([
            'tenant_id' => $this->tenantB->id,
            'name' => 'Admin Nurul Iman',
            'email' => 'admin@nuruliman.sch.id',
            'password' => bcrypt('password'),
        ]);
        $this->adminB->roles()->attach($adminRoleB);
        $this->adminB->refresh();

        $ayB = AcademicYear::create([
            'tenant_id' => $this->tenantB->id,
            'name' => '2024/2025',
            'status' => 'ACTIVE'
        ]);

        $assessmentB = Assessment::create([
            'tenant_id' => $this->tenantB->id,
            'academic_year_id' => $ayB->id,
            'title' => 'Penilaian Harian 1 B',
            'assessment_type' => 'FORMATIVE',
            'created_by' => $this->adminB->id
        ]);

        $this->examB = Exam::create([
            'tenant_id' => $this->tenantB->id,
            'assessment_id' => $assessmentB->id,
            'title' => 'Ujian Bahasa Indonesia Tenant B',
            'duration_seconds' => 3600,
            'created_by' => $this->adminB->id,
            'status' => 'SCHEDULED',
            'start_at' => now()->subHour(),
            'end_at' => now()->addHour(),
            'locked_at' => now(),
            'show_result' => true
        ]);

        app(TenantContext::class)->setTenantId(null);
    }

    private function authAs(User $user)
    {
        app(TenantContext::class)->setTenantId(null);
        return $this->actingAs($user, 'sanctum');
    }

    public function test_admin_madrasah_cannot_export_exam_results_pdf()
    {
        $response = $this->authAs($this->adminA)
            ->get("/api/v1/reports/exams/{$this->examA->id}/export/pdf");

        $response->assertForbidden();    }

    public function test_pdf_report_renders_logo_when_valid_image_exists()
    {
        $logoDir = storage_path('app/public/logos');
        if (!File::exists($logoDir)) {
            File::makeDirectory($logoDir, 0755, true);
        }

        $logoPath = $logoDir . '/tenant_a_logo.png';
        $pngData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        File::put($logoPath, $pngData);

        $this->tenantA->update(['logo_path' => 'logos/tenant_a_logo.png']);

        $service = new ReportHeaderService();
        $dataUri = $service->getLogoDataUri($this->tenantA);

        $this->assertNotNull($dataUri);
        $this->assertStringStartsWith('data:image/png;base64,', $dataUri);

        $view = view('reports.pdf.exam-results', [
            'exam' => $this->examA,
            'results' => [$this->attemptA],
            'tenant' => $this->tenantA,
            'logoDataUri' => $dataUri
        ])->render();

        $this->assertStringContainsString('KEMENTERIAN AGAMA REPUBLIK INDONESIA', $view);
        $this->assertStringContainsString('MI Al-Hidayah Jakarta', $view);
        $this->assertStringContainsString('data:image/png;base64,', $view);

        if (File::exists($logoPath)) {
            File::delete($logoPath);
        }
    }

    public function test_pdf_report_falls_back_to_text_kop_when_logo_is_null()
    {
        $this->tenantA->update(['logo_path' => null]);

        $service = new ReportHeaderService();
        $dataUri = $service->getLogoDataUri($this->tenantA);

        $this->assertNull($dataUri);

        $view = view('reports.pdf.exam-results', [
            'exam' => $this->examA,
            'results' => [$this->attemptA],
            'tenant' => $this->tenantA,
            'logoDataUri' => null
        ])->render();

        $this->assertStringContainsString('KEMENTERIAN AGAMA REPUBLIK INDONESIA', $view);
        $this->assertStringContainsString('MI Al-Hidayah Jakarta', $view);
        $this->assertStringContainsString('Jl. Kebon Jeruk No. 12, Jakarta Barat', $view);
        $this->assertStringContainsString('NSM: 111231710001', $view);
        $this->assertStringContainsString('NPSN: 60700001', $view);
        $this->assertStringNotContainsString('<img src=', $view);

        $response = $this->authAs($this->adminA)
            ->get("/api/v1/reports/exams/{$this->examA->id}/export/pdf");

        $response->assertForbidden();    }

    public function test_pdf_report_falls_back_to_text_kop_when_logo_file_is_missing()
    {
        $this->tenantA->update(['logo_path' => 'logos/non_existent_logo.png']);

        $service = new ReportHeaderService();
        $dataUri = $service->getLogoDataUri($this->tenantA);

        $this->assertNull($dataUri);

        $view = view('reports.pdf.exam-results', [
            'exam' => $this->examA,
            'results' => [$this->attemptA],
            'tenant' => $this->tenantA,
            'logoDataUri' => null
        ])->render();

        $this->assertStringContainsString('KEMENTERIAN AGAMA REPUBLIK INDONESIA', $view);
        $this->assertStringContainsString('MI Al-Hidayah Jakarta', $view);

        $response = $this->authAs($this->adminA)
            ->get("/api/v1/reports/exams/{$this->examA->id}/export/pdf");

        $response->assertForbidden();    }

    public function test_admin_madrasah_cannot_export_student_results_pdf()
    {
        $response = $this->authAs($this->adminA)
            ->get("/api/v1/reports/students/{$this->studentA->id}/export/pdf");

        $response->assertForbidden();
        $service = new ReportHeaderService();
        $dataUri = $service->getLogoDataUri($this->tenantA);

        $view = view('reports.pdf.student-results', [
            'student' => $this->studentA->load('studentProfile.rombels'),
            'results' => [$this->attemptA],
            'tenant' => $this->tenantA,
            'logoDataUri' => $dataUri
        ])->render();

        $this->assertStringContainsString('KEMENTERIAN AGAMA REPUBLIK INDONESIA', $view);
        $this->assertStringContainsString('MI Al-Hidayah Jakarta', $view);
        $this->assertStringContainsString('Ahmad Student', $view);
    }

    public function test_admin_madrasah_cannot_export_exam_summary_pdf()
    {
        $response = $this->authAs($this->adminA)
            ->get("/api/v1/reports/exams/{$this->examA->id}/summary/export/pdf");

        $response->assertForbidden();
        $view = view('reports.pdf.exam-summary', [
            'summary' => [
                'exam_title' => 'Ujian Matematika Bab 1',
                'participant_count' => 1,
                'attempt_count' => 1,
                'total_scored' => 1,
                'finalized_count' => 1,
                'pending_manual_count' => 0,
                'average_percentage' => 85,
                'highest_percentage' => 85,
                'lowest_percentage' => 85,
                'average_score' => 85,
                'highest_score' => 85,
                'lowest_score' => 85,
            ],
            'tenant' => $this->tenantA,
            'logoDataUri' => null
        ])->render();

        $this->assertStringContainsString('KEMENTERIAN AGAMA REPUBLIK INDONESIA', $view);
        $this->assertStringContainsString('MI Al-Hidayah Jakarta', $view);
        $this->assertStringContainsString('RINGKASAN HASIL UJIAN', $view);
    }

    public function test_admin_madrasah_cannot_export_exam_or_student_results(): void
    {
        app(TenantContext::class)->setTenantId($this->tenantA->id);

        $adminRole = Role::where('name', 'ADMIN_MADRASAH')->firstOrFail();
        $admin = User::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Admin Read Only',
            'email' => 'admin.readonly@alhidayah.sch.id',
            'password' => bcrypt('password'),
        ]);
        $admin->roles()->attach($adminRole);

        $this->authAs($admin)
            ->get("/api/v1/reports/exams/{$this->examA->id}/export/pdf")
            ->assertForbidden();

        $this->authAs($admin)
            ->get("/api/v1/reports/students/{$this->studentA->id}/export/pdf")
            ->assertForbidden();
    }

    public function test_multi_tenant_isolation_prevents_exporting_other_tenant_pdf()
    {
        $response = $this->authAs($this->adminA)
            ->get("/api/v1/reports/exams/{$this->examB->id}/export/pdf");

        $this->assertTrue(in_array($response->status(), [403, 404]));

        $responseSummary = $this->authAs($this->adminA)
            ->get("/api/v1/reports/exams/{$this->examB->id}/summary/export/pdf");

        $this->assertTrue(in_array($responseSummary->status(), [403, 404]));
    }

    public function test_unauthenticated_and_unauthorized_pdf_export_blocked()
    {
        app(TenantContext::class)->setTenantId(null);
        $this->getJson("/api/v1/reports/exams/{$this->examA->id}/export/pdf")
            ->assertStatus(401);

        $this->authAs($this->studentA)
            ->getJson("/api/v1/reports/exams/{$this->examA->id}/summary/export/pdf")
            ->assertStatus(403);
    }

    public function test_pdf_generation_does_not_mutate_academic_data()
    {
        app(TenantContext::class)->setTenantId($this->tenantA->id);
        $scoreBefore = ExamAttemptScore::where('attempt_id', $this->attemptA->id)->first();

        $this->authAs($this->adminA)
            ->get("/api/v1/reports/exams/{$this->examA->id}/export/pdf")
            ->assertForbidden();

        app(TenantContext::class)->setTenantId($this->tenantA->id);
        $scoreAfter = ExamAttemptScore::where('attempt_id', $this->attemptA->id)->first();

        $this->assertEquals($scoreBefore->raw_score, $scoreAfter->raw_score);
        $this->assertEquals($scoreBefore->percentage, $scoreAfter->percentage);
        $this->assertEquals($scoreBefore->grading_status, $scoreAfter->grading_status);
        $this->assertEquals($this->attemptA->status, $this->attemptA->fresh()->status);
    }
}
