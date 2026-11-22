<?php

namespace Tests\Feature;

use App\Contexts\TenantContext;
use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\AuditLog;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\DeviceLease;
use App\Models\ExamAttemptScore;
use App\Models\ExamAttemptScoreCurrent;
use App\Models\ExamParticipant;
use App\Models\ExamQuestionSnapshot;
use App\Models\Question;
use App\Models\QuestionVersion;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ExpireExamAttemptsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_expired_attempt_is_closed_scored_and_not_processed_twice(): void
    {
        $tenant = Tenant::create(['name' => 'Expiry Tenant', 'code' => 'EXP1']);
        app(TenantContext::class)->setTenantId($tenant->id);
        $student = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Expiry Student',
            'email' => 'expiry.student@example.test',
            'password' => bcrypt('password'),
        ]);
        $student->roles()->attach(Role::firstOrCreate(['name' => 'SISWA'], ['label' => 'Siswa']));

        $year = AcademicYear::create(['tenant_id' => $tenant->id, 'name' => '2026/2027']);
        $assessment = Assessment::create([
            'tenant_id' => $tenant->id,
            'academic_year_id' => $year->id,
            'title' => 'Expiry Assessment',
            'assessment_type' => 'DAILY',
            'created_by' => $student->id,
        ]);
        $exam = Exam::create([
            'tenant_id' => $tenant->id,
            'assessment_id' => $assessment->id,
            'title' => 'Expiry Exam',
            'duration_seconds' => 600,
            'created_by' => $student->id,
            'status' => 'PUBLISHED',
            'start_at' => now()->subHour(),
            'end_at' => now()->addHour(),
            'locked_at' => now(),
            'require_token' => false,
        ]);
        $participant = ExamParticipant::create([
            'tenant_id' => $tenant->id,
            'exam_id' => $exam->id,
            'user_id' => $student->id,
            'status' => 'STARTED',
        ]);
        $question = Question::create(['tenant_id' => $tenant->id, 'created_by' => $student->id]);
        $version = QuestionVersion::create([
            'tenant_id' => $tenant->id,
            'question_id' => $question->id,
            'major_version' => 1,
            'minor_version' => 0,
            'question_type' => 'MC',
            'stem' => '2 + 2?',
            'created_by' => $student->id,
        ]);
        $snapshot = ExamQuestionSnapshot::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenant->id,
            'exam_id' => $exam->id,
            'source_question_id' => $question->id,
            'source_question_version_id' => $version->id,
            'snapshot_stem' => '2 + 2?',
            'snapshot_options' => [],
            'metadata' => ['question_type' => 'MC'],
            'points' => 5,
        ]);
        $attempt = ExamAttempt::create([
            'tenant_id' => $tenant->id,
            'exam_id' => $exam->id,
            'participant_id' => $participant->id,
            'attempt_number' => 1,
            'status' => 'IN_PROGRESS',
            'started_at' => now()->subMinutes(20),
            'expires_at' => now()->subMinute(),
            'question_order' => [$snapshot->id],
            'device_fingerprint' => 'web-session-expiry',
        ]);
        DeviceLease::create([
            'tenant_id' => $tenant->id,
            'exam_attempt_id' => $attempt->id,
            'registered_device_id' => null,
            'auth_session_hash' => hash('sha256', 'expiry-session-digest'),
            'fence_generation' => 1,
            'status' => 'ACTIVE',
            'leased_at' => now(),
            'expires_at' => now()->addMinutes(5),
        ]);

        $this->artisan('cbt:expire-attempts')->assertExitCode(0);

        $this->assertSame('EXPIRED', $attempt->fresh()->status);
        $this->assertNull($attempt->fresh()->device_lease_expires_at);
        $this->assertDatabaseHas('device_leases', [
            'exam_attempt_id' => $attempt->id,
            'fence_generation' => 1,
            'status' => 'RELEASED',
            'release_reason' => 'SERVER_EXPIRY',
        ]);
        $this->assertSame('COMPLETED', $participant->fresh()->status);
        $score = ExamAttemptScore::where('attempt_id', $attempt->id)->firstOrFail();
        $this->assertSame('AUTO_SCORED', $score->grading_status);
        $this->assertEquals(5, (float) $score->max_score);
        $this->assertSame($score->id, ExamAttemptScoreCurrent::where('attempt_id', $attempt->id)->value('current_score_id'));
        $this->assertSame(1, AuditLog::where('entity_id', $attempt->id)->where('action', 'EXPIRE_ATTEMPT')->count());

        $this->artisan('cbt:expire-attempts')->assertExitCode(0);
        $this->assertSame(1, ExamAttemptScore::where('attempt_id', $attempt->id)->count());
        $this->assertSame(1, AuditLog::where('entity_id', $attempt->id)->where('action', 'EXPIRE_ATTEMPT')->count());
    }

    public function test_expired_attempt_with_missing_question_order_closes_without_fabricating_score(): void
    {
        $tenant = Tenant::create(['name' => 'Expiry Tenant 2', 'code' => 'EXP2']);
        app(TenantContext::class)->setTenantId($tenant->id);
        $student = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Expiry Student 2',
            'email' => 'expiry.student2@example.test',
            'password' => bcrypt('password'),
        ]);
        $year = AcademicYear::create(['tenant_id' => $tenant->id, 'name' => '2026/2027']);
        $assessment = Assessment::create([
            'tenant_id' => $tenant->id,
            'academic_year_id' => $year->id,
            'title' => 'Assessment 2',
            'assessment_type' => 'DAILY',
            'created_by' => $student->id,
        ]);
        $exam = Exam::create([
            'tenant_id' => $tenant->id,
            'assessment_id' => $assessment->id,
            'title' => 'Exam 2',
            'duration_seconds' => 600,
            'created_by' => $student->id,
            'status' => 'PUBLISHED',
            'start_at' => now()->subHour(),
            'end_at' => now()->addHour(),
            'locked_at' => now(),
            'require_token' => false,
        ]);
        $participant = ExamParticipant::create([
            'tenant_id' => $tenant->id,
            'exam_id' => $exam->id,
            'user_id' => $student->id,
            'status' => 'STARTED',
        ]);
        $attempt = ExamAttempt::create([
            'tenant_id' => $tenant->id,
            'exam_id' => $exam->id,
            'participant_id' => $participant->id,
            'attempt_number' => 1,
            'status' => 'IN_PROGRESS',
            'started_at' => now()->subMinutes(20),
            'expires_at' => now()->subMinute(),
            'question_order' => [],
        ]);

        $this->artisan('cbt:expire-attempts')->assertExitCode(0);

        $this->assertSame('EXPIRED', $attempt->fresh()->status);
        $this->assertDatabaseMissing('exam_attempt_scores', ['attempt_id' => $attempt->id]);
        $log = AuditLog::where('entity_id', $attempt->id)->where('action', 'EXPIRE_ATTEMPT')->firstOrFail();
        $this->assertSame('MISSING_OR_INVALID_QUESTION_ORDER', $log->new_values['scoring_skipped_reason']);
    }
}
