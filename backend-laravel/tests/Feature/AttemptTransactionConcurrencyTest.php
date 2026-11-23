<?php

namespace Tests\Feature;

use App\Contexts\TenantContext;
use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamParticipant;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AttemptTransactionConcurrencyTest extends TestCase
{
    /**
     * MySQL 8/InnoDB only. This test deliberately uses two independent
     * connections and no RefreshDatabase transaction so both can observe
     * committed fixtures and real row-lock contention.
     */
    public function test_answer_submit_and_expiry_paths_serialize_on_exam_participant_attempt_locks(): void
    {
        $this->assertSame('mysql', DB::connection()->getDriverName());
        $tenant = Tenant::create(['name' => 'Agent2 Concurrency', 'code' => 'A2C']);
        app(TenantContext::class)->setTenantId($tenant->id);

        $student = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Concurrency Student',
            'username' => '2088112233',
            'password' => bcrypt('test-password'),
        ]);
        $year = AcademicYear::create(['tenant_id' => $tenant->id, 'name' => '2026/2027']);
        $assessment = Assessment::create([
            'tenant_id' => $tenant->id,
            'academic_year_id' => $year->id,
            'title' => 'Concurrency Assessment',
            'assessment_type' => 'DAILY',
            'created_by' => $student->id,
        ]);
        $exam = Exam::create([
            'tenant_id' => $tenant->id,
            'assessment_id' => $assessment->id,
            'title' => 'Concurrency Exam',
            'duration_seconds' => 3600,
            'start_at' => now()->subMinute(),
            'end_at' => now()->addHour(),
            'status' => 'PUBLISHED',
            'created_by' => $student->id,
            'locked_at' => now(),
        ]);
        $participant = ExamParticipant::create([
            'tenant_id' => $tenant->id,
            'exam_id' => $exam->id,
            'user_id' => $student->id,
            'status' => 'STARTED',
        ]);
        $submitAttempt = ExamAttempt::create([
            'tenant_id' => $tenant->id,
            'exam_id' => $exam->id,
            'participant_id' => $participant->id,
            'attempt_number' => 1,
            'status' => 'IN_PROGRESS',
            'started_at' => now(),
            'expires_at' => now()->addHour(),
            'fence_generation' => 1,
        ]);
        $expiryAttempt = ExamAttempt::create([
            'tenant_id' => $tenant->id,
            'exam_id' => $exam->id,
            'participant_id' => $participant->id,
            'attempt_number' => 2,
            'status' => 'IN_PROGRESS',
            'started_at' => now(),
            'expires_at' => now()->addHour(),
            'fence_generation' => 1,
        ]);
        app(TenantContext::class)->setTenantId(null);

        config(['database.connections.agent2_secondary' => config('database.connections.mysql')]);
        DB::purge('agent2_secondary');
        $writer = DB::connection('mysql');
        $contender = DB::connection('agent2_secondary');
        $contender->statement('SET SESSION innodb_lock_wait_timeout = 1');

        // Submit wins the canonical lock order: Exam -> Participant -> Attempt.
        $writer->beginTransaction();
        try {
            $writer->table('exams')->where('id', $exam->id)->where('tenant_id', $tenant->id)->lockForUpdate()->first();
            $writer->table('exam_participants')->where('id', $participant->id)->where('tenant_id', $tenant->id)->lockForUpdate()->first();
            $writer->table('exam_attempts')->where('id', $submitAttempt->id)->where('tenant_id', $tenant->id)->lockForUpdate()->first();

            $contender->beginTransaction();
            try {
                $contender->table('exam_attempts')->where('id', $submitAttempt->id)->where('tenant_id', $tenant->id)->lockForUpdate()->first();
                $this->fail('A competing answer writer must wait while submit owns the Attempt lock.');
            } catch (QueryException $exception) {
                $this->assertStringContainsString('Lock wait timeout', $exception->getMessage());
                $contender->rollBack();
            }

            $writer->table('exam_attempts')->where('id', $submitAttempt->id)->update([
                'status' => 'SUBMITTED',
                'submitted_at' => now(),
            ]);
            $writer->commit();
        } catch (\Throwable $exception) {
            if ($writer->transactionLevel() > 0) $writer->rollBack();
            throw $exception;
        }

        $submitState = $contender->table('exam_attempts')->where('id', $submitAttempt->id)->first();
        $this->assertSame('SUBMITTED', $submitState->status);

        // Expiry wins the same lock order; a waiting answer writer sees EXPIRED after commit.
        $writer->beginTransaction();
        try {
            $writer->table('exams')->where('id', $exam->id)->where('tenant_id', $tenant->id)->lockForUpdate()->first();
            $writer->table('exam_participants')->where('id', $participant->id)->where('tenant_id', $tenant->id)->lockForUpdate()->first();
            $writer->table('exam_attempts')->where('id', $expiryAttempt->id)->where('tenant_id', $tenant->id)->lockForUpdate()->first();

            $contender->beginTransaction();
            try {
                $contender->table('exam_attempts')->where('id', $expiryAttempt->id)->where('tenant_id', $tenant->id)->lockForUpdate()->first();
                $this->fail('A competing answer writer must wait while expiry owns the Attempt lock.');
            } catch (QueryException $exception) {
                $this->assertStringContainsString('Lock wait timeout', $exception->getMessage());
                $contender->rollBack();
            }

            $writer->table('exam_attempts')->where('id', $expiryAttempt->id)->update(['status' => 'EXPIRED']);
            $writer->commit();
        } catch (\Throwable $exception) {
            if ($writer->transactionLevel() > 0) $writer->rollBack();
            throw $exception;
        }

        $expiryState = $contender->table('exam_attempts')->where('id', $expiryAttempt->id)->first();
        $this->assertSame('EXPIRED', $expiryState->status);

        DB::purge('agent2_secondary');
    }
}
