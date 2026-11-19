<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Assessment;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamParticipant;
use App\Models\AcademicYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitoringDuplicationTest extends TestCase
{
    use RefreshDatabase;

    private $tenant;
    private $admin;
    private $student;
    private $exam;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'T1', 'code' => 'T1']);
        app(\App\Contexts\TenantContext::class)->setTenantId($this->tenant->id);
        $this->admin = User::create(['tenant_id' => $this->tenant->id, 'name' => 'Admin', 'email' => 'admin@e.com', 'password' => 'p']);
        $adminRole = \App\Models\Role::firstOrCreate(['name' => 'ADMIN_MADRASAH'], ['label' => 'Admin Madrasah']);
        $this->admin->roles()->attach($adminRole);
        $this->student = User::create(['tenant_id' => $this->tenant->id, 'name' => 'S1', 'email' => 's@e.com', 'password' => 'p']);
        $this->student->roles()->create(['name' => 'SISWA', 'label' => 'Siswa']);

        $ay = AcademicYear::create(['tenant_id' => $this->tenant->id, 'name' => '2024']);
        $assessment = Assessment::create(['tenant_id' => $this->tenant->id, 'academic_year_id' => $ay->id, 'title' => 'A1', 'assessment_type' => 'DAILY', 'created_by' => $this->admin->id]);

        $this->exam = Exam::create(['tenant_id' => $this->tenant->id, 'assessment_id' => $assessment->id, 'title' => 'E1', 'duration_seconds' => 60, 'created_by' => $this->teacher->id]);
        $this->exam->participants()->create(['tenant_id' => $this->tenant->id, 'user_id' => $this->student->id]);
        $this->bootstrapCbtRolePermissions();
    }

    public function test_participants_list_does_not_duplicate_when_multiple_attempts_exist()
    {
        $participant = ExamParticipant::where('exam_id', $this->exam->id)->first();

        // 1. Create first attempt (e.g., finished)
        $attempt1 = ExamAttempt::create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'participant_id' => $participant->id,
            'student_id' => $this->student->id,
            'attempt_number' => 1,
            'status' => 'SUBMITTED',
        ]);
        $attempt1->created_at = now()->subHour();
        $attempt1->save();

        // 2. Create second attempt (latest)
        $attempt2 = ExamAttempt::create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'participant_id' => $participant->id,
            'student_id' => $this->student->id,
            'attempt_number' => 2,
            'status' => 'IN_PROGRESS',
        ]);
        $attempt2->created_at = now();
        $attempt2->save();

        $this->actingAs($this->admin, 'sanctum');
        $response = $this->getJson("/api/v1/exams/{$this->exam->id}/monitoring/participants");

        $response->assertStatus(200);
        // Should only have 1 student row despite 2 attempts
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('IN_PROGRESS', $response->json('data.0.attempt.status'));
    }
}
