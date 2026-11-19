<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Assessment;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamParticipant;
use App\Models\AcademicYear;
use App\Models\Question;
use App\Models\QuestionVersion;
use App\Models\ExamAttemptAnswer;
use App\Models\ExamAttemptScore;
use App\Models\ExamQuestionScore;
use App\Services\ScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Str;

class ScoringHardeningTest extends TestCase
{
    use RefreshDatabase;

    private $tenant;
    private $teacher;
    private $student;
    private $exam;
    private $attempt;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'Madrasah', 'code' => 'M1']);
        app(\App\Contexts\TenantContext::class)->setTenantId($this->tenant->id);
        $this->teacher = User::create(['tenant_id' => $this->tenant->id, 'name' => 'Guru', 'email' => 'guru@m.com', 'password' => 'p']);
        $this->teacher->roles()->create(['name' => 'GURU', 'label' => 'Guru']);

        $this->student = User::create(['tenant_id' => $this->tenant->id, 'name' => 'Siswa', 'email' => 'siswa@m.com', 'password' => 'p']);
        $this->student->roles()->create(['name' => 'SISWA', 'label' => 'Siswa']);

        $ay = AcademicYear::create(['tenant_id' => $this->tenant->id, 'name' => '2024']);
        $assessment = Assessment::create(['tenant_id' => $this->tenant->id, 'academic_year_id' => $ay->id, 'title' => 'A1', 'assessment_type' => 'DAILY', 'created_by' => $this->teacher->id]);

        $this->exam = Exam::create([
            'tenant_id' => $this->tenant->id,
            'assessment_id' => $assessment->id,
            'title' => 'E1',
            'duration_seconds' => 3600,
            'created_by' => $this->teacher->id,
            'status' => 'DRAFT',
            'start_at' => now()->subHour(),
            'end_at' => now()->addHour(),
            'locked_at' => now()
        ]);

        $participant = $this->exam->participants()->create(['tenant_id' => $this->tenant->id, 'user_id' => $this->student->id]);
        $this->attempt = ExamAttempt::create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'participant_id' => $participant->id,
            'attempt_number' => 1,
            'status' => 'SUBMITTED',
            'started_at' => now()->subMinutes(30),
            'expires_at' => now()->addMinutes(30),
            'submitted_at' => now()
        ]);
        $this->bootstrapCbtRolePermissions();
        $this->grantTeacherAccessToExam($this->teacher, $this->exam);
    }

    /** 1. MULTIPLE RESPONSE DUPLICATE SELECTION **/
    public function test_multiple_response_duplicate_selection_is_safe()
    {
        $snapshot = $this->createSnapshot('MULTIPLE_RESPONSE', [
            ['content' => 'A', 'is_correct' => true],
            ['content' => 'B', 'is_correct' => false]
        ], 10);

        // Student selects A twice
        $this->submitAnswer($snapshot->id, ['selected_option_ids' => [$snapshot->snapshot_options[0]['id'], $snapshot->snapshot_options[0]['id']]]);

        $score = (new ScoringService())->scoreAttempt($this->attempt);
        $this->assertEquals(10, $score->raw_score); // Should not be 20
    }

    public function test_scoring_rejects_malformed_or_duplicate_attempt_question_order(): void
    {
        $snapshot = $this->createSnapshot('MC', [
            ['content' => 'A', 'is_correct' => true],
        ], 10);

        $this->attempt->update(['question_order' => [(string) $snapshot->id, null]]);

        try {
            (new ScoringService())->scoreAttempt($this->attempt->fresh());
            $this->fail('Malformed question_order must not be silently filtered.');
        } catch (\DomainException $exception) {
            $this->assertStringContainsString('malformed snapshot ID', $exception->getMessage());
        }

        $this->attempt->update(['question_order' => [(string) $snapshot->id, (string) $snapshot->id]]);

        try {
            (new ScoringService())->scoreAttempt($this->attempt->fresh());
            $this->fail('Duplicate question_order entries must be rejected.');
        } catch (\DomainException $exception) {
            $this->assertStringContainsString('duplicate snapshot IDs', $exception->getMessage());
        }

        $this->assertSame(0, ExamAttemptScore::where('attempt_id', $this->attempt->id)->count());
    }

    /** 2. MATCHING DUPLICATE SELECTION **/
    public function test_matching_duplicate_left_id_is_safe()
    {
        $q = Question::create(['tenant_id' => $this->tenant->id, 'created_by' => $this->teacher->id]);
        $v = QuestionVersion::create(['tenant_id' => $this->tenant->id, 'question_id' => $q->id, 'major_version' => 1, 'minor_version' => 0, 'question_type' => 'MATCHING', 'stem' => 'S', 'created_by' => $this->teacher->id]);

        $snapshot = $this->createSnapshotRecord([
            'id' => (string) Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'source_question_id' => $q->id,
            'source_question_version_id' => $v->id,
            'snapshot_stem' => 'S',
            'snapshot_options' => [
                ['id' => 'L1', 'content' => 'L1', 'option_key' => 'K1'],
                ['id' => 'R1', 'content' => 'R1', 'matching_key' => 'K1']
            ],
            'points' => 10,
            'metadata' => ['question_type' => 'MATCHING']
        ]);

        // Student sends L1->R1 twice
        $this->submitAnswer($snapshot->id, [
            'matches' => [
                ['left_id' => 'L1', 'right_id' => 'R1'],
                ['left_id' => 'L1', 'right_id' => 'R1']
            ]
        ]);

        $score = (new ScoringService())->scoreAttempt($this->attempt);
        $this->assertEquals(10, $score->raw_score);
    }

    /** 3. FINALIZED IMMUTABILITY **/
    public function test_finalized_score_cannot_be_changed()
    {
        $snapshot = $this->createSnapshot('MC', [['content' => 'A', 'is_correct' => true]], 10);
        $this->submitAnswer($snapshot->id, ['selected_option_id' => $snapshot->snapshot_options[0]['id']]);

        $service = new ScoringService();
        $score = $service->scoreAttempt($this->attempt);
        $this->assertEquals(10, $score->raw_score);

        // Finalize
        $this->actingAs($this->teacher)->postJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/finalize");
        $this->assertEquals('FINALIZED', $score->fresh()->grading_status);

        // Try to update manual score
        $qScore = ExamQuestionScore::first();
        $response = $this->actingAs($this->teacher)->putJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/grading/{$qScore->id}", [
            'awarded_score' => 5
        ]);
        $response->assertStatus(422);
        $this->assertEquals(10, $score->fresh()->raw_score);

        // Try to recalculate
        $response = $this->actingAs($this->teacher)->postJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/recalculate");
        $response->assertStatus(422);
    }

    /** 4. ACTIVE ATTEMPTS CANNOT BE SCORED OR RECALCULATED **/
    public function test_active_attempt_is_rejected_by_score_and_recalculate_endpoints()
    {
        $this->attempt->update(['status' => 'IN_PROGRESS', 'submitted_at' => null]);
        $this->actingAs($this->teacher, 'sanctum');

        $this->postJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/score")
            ->assertStatus(422)
            ->assertJson(['success' => false, 'message' => 'Attempt must be closed before scoring.']);

        $this->postJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/recalculate")
            ->assertStatus(422)
            ->assertJson(['success' => false, 'message' => 'Attempt must be closed before recalculation.']);

        $this->assertSame(0, ExamAttemptScore::where('attempt_id', $this->attempt->id)->count());
    }

    /** 5. DECIMAL PRECISION (2/3 * 10) **/
    public function test_decimal_precision_is_stable()
    {
        $q = Question::create(['tenant_id' => $this->tenant->id, 'created_by' => $this->teacher->id]);
        $v = $q->versions()->create(['tenant_id' => $this->tenant->id, 'major_version' => 1, 'minor_version' => 0, 'question_type' => 'MULTIPLE_RESPONSE', 'stem' => 'S', 'created_by' => $this->teacher->id]);
        $o1 = $v->options()->create(['tenant_id' => $this->tenant->id, 'content' => 'C1', 'is_correct' => true]);
        $o2 = $v->options()->create(['tenant_id' => $this->tenant->id, 'content' => 'C2', 'is_correct' => true]);
        $o3 = $v->options()->create(['tenant_id' => $this->tenant->id, 'content' => 'C3', 'is_correct' => true]);

        $snapshot = $this->createSnapshotRecord([
            'id' => (string) Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'source_question_id' => $q->id,
            'source_question_version_id' => $v->id,
            'snapshot_stem' => 'S',
            'snapshot_options' => [
                ['id' => $o1->id, 'content' => 'C1', 'is_correct' => true],
                ['id' => $o2->id, 'content' => 'C2', 'is_correct' => true],
                ['id' => $o3->id, 'content' => 'C3', 'is_correct' => true]
            ],
            'points' => 10,
            'metadata' => ['question_type' => 'MULTIPLE_RESPONSE']
        ]);

        // Student selects 2 out of 3. Score = (2/3) * 10 = 6.6666...
        $this->submitAnswer($snapshot->id, ['selected_option_ids' => [$o1->id, $o2->id]]);

        $score = (new ScoringService())->scoreAttempt($this->attempt);

        // 6.6667 if rounded to 4 decimals in awarded_score?
        // My percentage is rounded to 2 decimals.
        // raw_score is decimal 12,4.
        $this->assertEquals(6.6667, (float) $score->raw_score);
        $this->assertEquals(66.67, (float) $score->percentage);
    }

    /** 6. ZERO MAX SCORE PROTECTION **/
    public function test_zero_max_score_handled()
    {
        $snapshot = $this->createSnapshot('MC', [['content' => 'A', 'is_correct' => true]], 0);
        $this->submitAnswer($snapshot->id, ['selected_option_id' => $snapshot->snapshot_options[0]['id']]);

        $score = (new ScoringService())->scoreAttempt($this->attempt);
        $this->assertEquals(0, $score->raw_score);
        $this->assertEquals(0, $score->percentage);
    }

    /** 6. TRANSACTION ROLLBACK **/
    public function test_scoring_transaction_rollbacks_on_failure()
    {
        $snapshot = $this->createSnapshot('MC', [['content' => 'A', 'is_correct' => true]], 10);
        $this->submitAnswer($snapshot->id, ['selected_option_id' => $snapshot->snapshot_options[0]['id']]);

        // Inject event listener on ExamQuestionScore saving to simulate DB failure
        \App\Models\ExamQuestionScore::saving(function () {
            throw new \RuntimeException('Simulated DB failure during scoring');
        });

        try {
            (new ScoringService())->scoreAttempt($this->attempt);
            $this->fail('Expected exception was not thrown.');
        } catch (\RuntimeException $e) {
            $this->assertEquals('Simulated DB failure during scoring', $e->getMessage());
        } finally {
            \App\Models\ExamQuestionScore::flushEventListeners();
        }

        // Assert database state was rolled back cleanly (no ExamAttemptScore or ExamQuestionScore persisted)
        $this->assertNull(ExamAttemptScore::where('attempt_id', $this->attempt->id)->first());
        $this->assertEquals(0, ExamQuestionScore::where('attempt_id', $this->attempt->id)->count());
    }

    /** 8. MASS ASSIGNMENT PROTECTION **/
    public function test_client_cannot_inject_score()
    {
        $this->actingAs($this->teacher, 'sanctum');

        // Let's say we have an endpoint that might be misused.
        // We'll test manual grading but trying to inject 'grading_status'
        $snapshot = $this->createSnapshot('ESSAY', [], 10);
        $this->submitAnswer($snapshot->id, ['text' => 'E']);
        (new ScoringService())->scoreAttempt($this->attempt);
        $qScore = ExamQuestionScore::first();

        $response = $this->putJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/grading/{$qScore->id}", [
            'awarded_score' => 5,
            'grading_status' => 'FINALIZED' // Malicious attempt to finalize early
        ]);

        $response->assertStatus(200);
        $this->assertEquals('GRADED', $qScore->fresh()->scoring_status);
        $this->assertEquals('GRADED', ExamAttemptScore::first()->grading_status); // GRADED, not FINALIZED as injected
    }


    /** Reopening a finalized score is restricted to authorized administrators. */
    public function test_reopen_score_is_denied_to_operator_teacher_proctor_and_student()
    {
        $roles = ['SUPER_ADMIN', 'OPERATOR', 'GURU', 'PROKTOR', 'SISWA'];

        foreach ($roles as $roleName) {
            $user = User::create([
                'tenant_id' => $this->tenant->id,
                'name' => $roleName,
                'email' => strtolower($roleName) . '.' . Str::uuid() . '@m.com',
                'password' => 'p',
            ]);
            $role = \App\Models\Role::firstOrCreate(['name' => $roleName], ['label' => $roleName]);
            $user->roles()->attach($role);

            $this->assertFalse(
                app(\App\Policies\ExamAttemptPolicy::class)->reopen($user, $this->attempt),
                "Role {$roleName} must not reopen a finalized score."
            );
        }
    }

    public function test_reopen_score_is_allowed_only_for_admin_roles_in_same_tenant()
    {
        foreach (['ADMIN_MADRASAH'] as $roleName) {
            $user = User::create([
                'tenant_id' => $this->tenant->id,
                'name' => $roleName,
                'email' => strtolower($roleName) . '@m.com',
                'password' => 'p',
            ]);
            $user->roles()->attach(\App\Models\Role::firstOrCreate(['name' => $roleName], ['label' => $roleName]));

            $this->assertTrue(
                app(\App\Policies\ExamAttemptPolicy::class)->reopen($user, $this->attempt),
                "Role {$roleName} should be allowed to reopen a finalized score in its tenant."
            );
        }

        $otherTenant = Tenant::create(['name' => 'Madrasah Lain', 'code' => 'M2']);
        $foreignAdmin = User::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Foreign Admin',
            'email' => 'foreign-admin@m.com',
            'password' => 'p',
        ]);
        $foreignAdmin->roles()->attach(\App\Models\Role::firstOrCreate(['name' => 'ADMIN_MADRASAH'], ['label' => 'Admin']));

        $this->assertFalse(
            app(\App\Policies\ExamAttemptPolicy::class)->reopen($foreignAdmin, $this->attempt),
            'An administrator from another tenant must not reopen this score.'
        );
    }

    /** 9. RECALCULATION PRESERVES MANUAL GRADING **/
    public function test_recalculate_preserves_manual_grading()
    {
        $snapshot = $this->createSnapshot('ESSAY', [], 10);
        $this->submitAnswer($snapshot->id, ['text' => 'E']);

        $service = new ScoringService();
        $service->scoreAttempt($this->attempt);
        $qScore = ExamQuestionScore::first();
        $this->assertEquals('MANUAL_REVIEW', $qScore->scoring_status);

        // Teacher grades it
        $this->actingAs($this->teacher)->putJson("/api/v1/exams/{$this->exam->id}/attempts/{$this->attempt->id}/grading/{$qScore->id}", [
            'awarded_score' => 7,
            'feedback' => 'Good'
        ]);

        $this->assertEquals(7, ExamAttemptScore::first()->raw_score);
        $this->assertEquals('GRADED', $qScore->fresh()->scoring_status);

        // Recalculate (force)
        (new ScoringService())->scoreAttempt($this->attempt, true);

        // Manual grade must be preserved
        $this->assertEquals(7, ExamAttemptScore::first()->raw_score);
        $this->assertEquals('GRADED', $qScore->fresh()->scoring_status);
    }

    private function createSnapshot($type, $options, $points)
    {
        $q = Question::create(['tenant_id' => $this->tenant->id, 'created_by' => $this->teacher->id]);
        $v = QuestionVersion::create([
            'tenant_id' => $this->tenant->id,
            'question_id' => $q->id,
            'stem' => 'Stem',
            'question_type' => $type,
            'default_points' => $points,
            'version_number' => 1,
            'status' => 'PUBLISHED',
            'created_by' => $this->teacher->id
        ]);
        $snapshot = $this->exam->snapshots()->create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'source_question_id' => $q->id,
            'source_question_version_id' => $v->id,
            'snapshot_stem' => 'Stem',
            'snapshot_options' => array_map(fn($o) => array_merge(['id' => (string) Str::uuid()], $o), $options),
            'points' => $points,
            'metadata' => ['question_type' => $type]
        ]);
        $order = $this->attempt->fresh()->question_order ?? [];
        $order[] = (string) $snapshot->id;
        $this->attempt->update(['question_order' => array_values(array_unique($order))]);

        return $snapshot;
    }

    private function submitAnswer($snapshotId, $payload)
    {
        ExamAttemptAnswer::create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'exam_attempt_id' => $this->attempt->id,
            'exam_question_snapshot_id' => $snapshotId,
            'student_id' => $this->student->id,
            'answer_payload' => $payload,
            'answer_state' => 'ANSWERED'
        ]);
    }
    private function createSnapshotRecord(array $attributes)
    {
        $snapshot = $this->exam->snapshots()->create($attributes);
        $order = $this->attempt->fresh()->question_order ?? [];
        $order[] = (string) $snapshot->id;
        $this->attempt->update(['question_order' => array_values(array_unique($order))]);

        return $snapshot;
    }

}
