<?php

namespace Tests\Feature;

use App\Contexts\TenantContext;
use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptScore;
use App\Models\ExamAttemptScoreCurrent;
use App\Models\ExamParticipant;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentResultVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $student;
    private User $teacher;
    private Exam $exam;
    private ExamAttempt $attempt;
    private ExamAttemptScore $score;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create(['name' => 'Tenant Result', 'code' => 'TR1']);
        app(TenantContext::class)->setTenantId($this->tenant->id);

        $this->student = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Student Result',
            'email' => 'student-result@example.test',
            'password' => 'password',
        ]);
        $this->student->roles()->attach(
            Role::firstOrCreate(['name' => 'SISWA'], ['label' => 'Siswa'])
        );
        $this->teacher = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Teacher Result',
            'email' => 'teacher-result@example.test',
            'password' => 'password',
        ]);
        $this->teacher->roles()->attach(
            Role::firstOrCreate(['name' => 'GURU'], ['label' => 'Guru'])
        );

        $year = AcademicYear::create(['tenant_id' => $this->tenant->id, 'name' => '2026/2027']);
        $assessment = Assessment::create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $year->id,
            'title' => 'Asesmen Result',
            'assessment_type' => 'DAILY',
            'created_by' => $this->student->id,
        ]);

        $this->exam = Exam::create([
            'tenant_id' => $this->tenant->id,
            'assessment_id' => $assessment->id,
            'title' => 'Ujian Result',
            'duration_seconds' => 3600,
            'created_by' => $this->student->id,
            'status' => 'CLOSED',
            'start_at' => now()->subHours(2),
            'end_at' => now()->subHour(),
            'locked_at' => now()->subHours(2),
            'show_result' => true,
        ]);

        $participant = ExamParticipant::create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'user_id' => $this->student->id,
            'status' => 'COMPLETED',
        ]);

        $this->attempt = ExamAttempt::create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'participant_id' => $participant->id,
            'attempt_number' => 1,
            'status' => 'SUBMITTED',
            'started_at' => now()->subHours(2),
            'expires_at' => now()->subHour(),
            'submitted_at' => now()->subHour(),
            'question_order' => [],
            'options_order' => [],
        ]);

        $this->score = ExamAttemptScore::create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'attempt_id' => $this->attempt->id,
            'student_id' => $this->student->id,
            'raw_score' => 80,
            'max_score' => 100,
            'percentage' => 80,
            'scoring_version' => 1,
            'grading_status' => 'FINALIZED',
            'calculated_at' => now()->subMinutes(30),
            'finalized_at' => now()->subMinutes(20),
            'finalized_by' => $this->teacher->id,
        ]);

        ExamAttemptScoreCurrent::create([
            'tenant_id' => $this->tenant->id,
            'attempt_id' => $this->attempt->id,
            'current_score_id' => $this->score->id,
            'scoring_version' => 1,
        ]);
    }

    public function test_student_can_view_own_published_finalized_result(): void
    {
        $response = $this->actingAs($this->student)
            ->get(route('student.results.show', $this->attempt->id));

        $response->assertOk()
            ->assertSee('Hasil Ujian')
            ->assertSee('80,00%');
    }


    public function test_student_score_api_returns_only_published_summary_without_question_details(): void
    {
        $response = $this->actingAs($this->student, 'sanctum')
            ->getJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/score");

        $response->assertOk()
            ->assertJsonPath('data.summary.percentage', '80.00')
            ->assertJsonPath('data.summary.grading_status', 'FINALIZED')
            ->assertJsonPath('data.details', []);

        $summary = $response->json('data.summary');
        $this->assertSame([
            'raw_score',
            'max_score',
            'percentage',
            'grading_status',
            'scoring_version',
            'calculated_at',
            'finalized_at',
        ], array_keys($summary));
    }

    public function test_student_cannot_view_result_before_teacher_publishes_it(): void
    {
        $this->exam->update(['show_result' => false]);

        $this->actingAs($this->student)
            ->get(route('student.results.show', $this->attempt->id))
            ->assertNotFound();
    }

    public function test_student_cannot_view_another_students_result(): void
    {
        $other = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Other Student',
            'email' => 'other-result@example.test',
            'password' => 'password',
        ]);
        $other->roles()->attach(Role::firstOrCreate(['name' => 'SISWA'], ['label' => 'Siswa']));

        $this->actingAs($other)
            ->get(route('student.results.show', $this->attempt->id))
            ->assertNotFound();
    }


    public function test_student_results_api_uses_current_score_pointer_not_historical_finalized_score(): void
    {
        // Add a newer historical finalized score, then point the attempt at a
        // non-finalized current score. The historical row must not leak as visible.
        $historical = ExamAttemptScore::create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'attempt_id' => $this->attempt->id,
            'student_id' => $this->student->id,
            'raw_score' => 95,
            'max_score' => 100,
            'percentage' => 95,
            'scoring_version' => 2,
            'grading_status' => 'FINALIZED',
            'calculated_at' => now()->subMinutes(10),
            'finalized_at' => now()->subMinutes(5),
            'finalized_by' => $this->teacher->id,
        ]);
        $this->score->update(['grading_status' => 'MANUAL_REVIEW']);

        $response = $this->actingAs($this->student, 'sanctum')
            ->getJson('/api/v1/reports/students/'.$this->student->id.'/results');

        $response->assertOk()
            ->assertJsonPath('meta.total', 0);
    }


    public function test_student_results_api_returns_minimal_current_score_dto_without_internal_relations(): void
    {
        $response = $this->actingAs($this->student, 'sanctum')
            ->getJson('/api/v1/reports/students/'.$this->student->id.'/results');

        $response->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.exam.title', 'Ujian Result')
            ->assertJsonPath('data.0.score.grading_status', 'FINALIZED')
            ->assertJsonMissingPath('data.0.tenant_id')
            ->assertJsonMissingPath('data.0.participant_id')
            ->assertJsonMissingPath('data.0.score_current_pointer')
            ->assertJsonMissingPath('data.0.score.finalized_by');

        $this->assertEquals(80.0, (float) $response->json('data.0.score.percentage'));
    }

    public function test_student_cannot_view_non_finalized_current_score(): void
    {
        $this->score->update(['grading_status' => 'MANUAL_REVIEW']);

        $this->actingAs($this->student)
            ->get(route('student.results.show', $this->attempt->id))
            ->assertForbidden();
    }
}
