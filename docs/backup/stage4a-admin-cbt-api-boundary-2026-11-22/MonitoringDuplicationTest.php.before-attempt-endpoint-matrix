<?php

namespace Tests\Feature;

use App\Contexts\TenantContext;
use App\Models\Assessment;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamParticipant;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTeacherExamScope;
use Tests\TestCase;

class MonitoringDuplicationTest extends TestCase
{
    use RefreshDatabase;
    use CreatesTeacherExamScope;

    private Tenant $tenant;
    private User $admin;
    private User $teacher;
    private User $student;
    private Exam $exam;
    private ExamAttempt $attempt1;
    private ExamAttempt $attempt2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create(['name' => 'Monitoring Tenant', 'code' => 'MON1']);
        app(TenantContext::class)->setTenantId($this->tenant->id);

        $adminRole = Role::create(['name' => 'ADMIN_MADRASAH', 'label' => 'Admin Madrasah']);
        $guruRole = Role::create(['name' => 'GURU', 'label' => 'Guru']);
        $studentRole = Role::create(['name' => 'SISWA', 'label' => 'Siswa']);
        $monitorPermission = Permission::create(['name' => 'exam.monitor', 'label' => 'Monitoring ujian sesuai penugasan']);
        $guruRole->permissions()->attach($monitorPermission->id);

        $this->admin = User::create([
            'tenant_id' => $this->tenant->id, 'name' => 'Admin',
            'email' => 'admin-monitor@example.test', 'password' => bcrypt('password'),
        ]);
        $this->admin->roles()->attach($adminRole->id);

        $this->teacher = User::create([
            'tenant_id' => $this->tenant->id, 'name' => 'Guru Monitoring',
            'email' => 'guru-monitor@example.test', 'password' => bcrypt('password'),
        ]);
        $this->teacher->roles()->attach($guruRole->id);

        $this->student = User::create([
            'tenant_id' => $this->tenant->id, 'name' => 'Siswa Monitoring',
            'email' => 'siswa-monitor@example.test', 'password' => bcrypt('password'),
        ]);
        $this->student->roles()->attach($studentRole->id);

        $scope = $this->createTeacherExamScope($this->tenant, $this->teacher, [$this->student], 'MONITOR');
        $assessment = Assessment::create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $scope['academicYear']->id,
            'semester_id' => $scope['semester']->id,
            'subject_id' => $scope['subject']->id,
            'title' => 'Asesmen Monitoring',
            'assessment_type' => 'DAILY',
            'created_by' => $this->teacher->id,
        ]);

        $this->exam = Exam::create([
            'tenant_id' => $this->tenant->id,
            'assessment_id' => $assessment->id,
            'title' => 'Ujian Monitoring',
            'duration_seconds' => 3600,
            'start_at' => now()->subMinutes(5),
            'end_at' => now()->addHours(2),
            'status' => 'PUBLISHED',
            'created_by' => $this->teacher->id,
            'locked_at' => now(),
        ]);

        $participant = ExamParticipant::create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'user_id' => $this->student->id,
        ]);

        $this->attempt1 = ExamAttempt::create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'participant_id' => $participant->id,
            'student_id' => $this->student->id,
            'attempt_number' => 1,
            'status' => 'SUBMITTED',
        ]);
        $this->attempt1->created_at = now()->subHour();
        $this->attempt1->save();

        $this->attempt2 = ExamAttempt::create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'participant_id' => $participant->id,
            'student_id' => $this->student->id,
            'attempt_number' => 2,
            'status' => 'IN_PROGRESS',
        ]);
        $this->attempt2->created_at = now();
        $this->attempt2->save();
    }

    public function test_assigned_guru_can_monitor_and_participant_list_does_not_duplicate(): void
    {
        $this->actingAs($this->teacher, 'sanctum');
        $response = $this->getJson("/api/v1/exams/{$this->exam->id}/monitoring/participants");

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('IN_PROGRESS', $response->json('data.0.attempt.status'));

        $this->getJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt2->id}/answers")
            ->assertForbidden();
        $this->postJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt2->id}/heartbeat")
            ->assertForbidden();
    }

    public function test_admin_is_denied_direct_monitoring_answers_score_and_report_urls_and_apis(): void
    {
        $this->actingAs($this->admin)
            ->get("/exams/{$this->exam->id}/monitoring")
            ->assertForbidden();
        $this->get("/exams/{$this->exam->id}/results")->assertForbidden();
        $this->get("/exams/{$this->exam->id}/results/{$this->attempt2->id}")->assertForbidden();

        $this->actingAs($this->admin, 'sanctum');

        $apiUrls = [
            "/api/v1/exams/{$this->exam->id}/monitoring",
            "/api/v1/exams/{$this->exam->id}/monitoring/participants",
            "/api/v1/exams/{$this->exam->id}/monitoring/attempts/{$this->attempt2->id}",
            "/api/v1/exams/{$this->exam->id}/monitoring/events",
            "/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt2->id}/answers",
            "/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt2->id}/score",
            "/api/v1/reports/exams/{$this->exam->id}/results",
            "/api/v1/reports/exams/{$this->exam->id}/summary",
            "/api/v1/reports/exams/{$this->exam->id}/questions",
            "/api/v1/reports/exams/{$this->exam->id}/export/pdf",
            "/api/v1/reports/exams/{$this->exam->id}/export/excel",
        ];

        foreach ($apiUrls as $url) {
            $response = $this->getJson($url);
            $this->assertSame(403, $response->status(), 'Expected 403 for ' . $url . '; got ' . $response->status() . ': ' . $response->getContent());
        }

        $this->postJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt2->id}/heartbeat")
            ->assertForbidden();
    }
}
