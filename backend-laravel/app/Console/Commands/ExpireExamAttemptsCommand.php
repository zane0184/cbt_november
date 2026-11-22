<?php

namespace App\Console\Commands;

use App\Contexts\TenantContext;
use App\Models\AuditLog;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamParticipant;
use App\Models\ExamQuestionSnapshot;
use App\Services\ScoringService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class ExpireExamAttemptsCommand extends Command
{
    protected $signature = 'cbt:expire-attempts {--limit=200 : Maximum attempts to process per run}';

    protected $description = 'Close expired in-progress CBT attempts and create their score snapshots safely.';

    public function handle(ScoringService $scoringService): int
    {
        $limit = max(1, min(1000, (int) $this->option('limit')));
        $now = now();

        // Use the query builder because tenant-scoped Eloquent models fail closed
        // without an HTTP tenant context. Each row is revalidated under locks below.
        $candidateIds = DB::table('exam_attempts')
            ->where('status', 'IN_PROGRESS')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', $now)
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id');

        $expired = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($candidateIds as $attemptId) {
            try {
                $result = $this->expireOne((string) $attemptId, $scoringService);
                if ($result === 'expired') {
                    $expired++;
                } elseif ($result === 'skipped') {
                    $skipped++;
                }
            } catch (Throwable $exception) {
                $failed++;
                report($exception);
                $this->error("Attempt {$attemptId} gagal diproses: {$exception->getMessage()}");
            }
        }

        $this->info("Selesai. Expired: {$expired}; skipped: {$skipped}; failed: {$failed}.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function expireOne(string $attemptId, ScoringService $scoringService): string
    {
        $candidate = DB::table('exam_attempts')->where('id', $attemptId)->first();
        if (!$candidate) {
            return 'skipped';
        }

        $tenantContext = app(TenantContext::class);
        $previousTenantId = $tenantContext->getTenantId();
        $tenantContext->setTenantId((string) $candidate->tenant_id);

        try {
            return DB::transaction(function () use ($candidate, $attemptId, $scoringService) {
                // Canonical lifecycle lock order: Exam -> Participant -> Attempt.
                $exam = Exam::withoutGlobalScope('tenant')
                    ->whereKey($candidate->exam_id)
                    ->where('tenant_id', $candidate->tenant_id)
                    ->lockForUpdate()
                    ->first();

                if (!$exam) {
                    return 'skipped';
                }

                $participant = ExamParticipant::withoutGlobalScope('tenant')
                    ->whereKey($candidate->participant_id)
                    ->where('exam_id', $exam->id)
                    ->where('tenant_id', $exam->tenant_id)
                    ->lockForUpdate()
                    ->first();

                if (!$participant) {
                    return 'skipped';
                }

                $attempt = ExamAttempt::withoutGlobalScope('tenant')
                    ->whereKey($attemptId)
                    ->where('exam_id', $exam->id)
                    ->where('participant_id', $participant->id)
                    ->where('tenant_id', $exam->tenant_id)
                    ->where('status', 'IN_PROGRESS')
                    ->whereNotNull('expires_at')
                    ->where('expires_at', '<=', now())
                    ->lockForUpdate()
                    ->first();

                if (!$attempt) {
                    return 'skipped';
                }

                try {
                    // Strict validation is shared with ScoringService. Do not silently
                    // discard malformed/duplicate entries before deciding whether to score.
                    $questionOrder = $scoringService->validatedQuestionOrder($attempt);
                } catch (\DomainException) {
                    $questionOrder = [];
                }

                $validSnapshotCount = $questionOrder === [] ? 0 : ExamQuestionSnapshot::withoutGlobalScope('tenant')
                    ->where('tenant_id', $attempt->tenant_id)
                    ->where('exam_id', $attempt->exam_id)
                    ->whereIn('id', $questionOrder)
                    ->count();

                $invalidQuestionOrder = $questionOrder === [] || $validSnapshotCount !== count($questionOrder);
                $closedAt = now();
                $attempt->update([
                    'status' => 'EXPIRED',
                    // submitted_at is the terminal close timestamp used by result/report views;
                    // record the authoritative deadline, while processed_at stays in the audit.
                    'submitted_at' => $attempt->expires_at,
                    'last_activity_at' => $closedAt,
                ]);

                app(\App\Services\AttemptDeviceLeaseService::class)->release($attempt, 'SERVER_EXPIRY');

                $hasOtherActiveAttempt = ExamAttempt::withoutGlobalScope('tenant')
                    ->where('tenant_id', $attempt->tenant_id)
                    ->where('participant_id', $participant->id)
                    ->where('status', 'IN_PROGRESS')
                    ->exists();

                $participant->update(['status' => $hasOtherActiveAttempt ? 'STARTED' : 'COMPLETED']);

                $score = null;
                $skipReason = null;
                if ($invalidQuestionOrder) {
                    // Close the expired session, but do not fabricate a zero score
                    // when the immutable attempt question assignment is missing/corrupt.
                    $skipReason = 'MISSING_OR_INVALID_QUESTION_ORDER';
                } else {
                    $attempt->setRelation('exam', $exam);
                    $attempt->setRelation('participant', $participant);

                    try {
                        $score = $scoringService->scoreAttempt($attempt);
                    } catch (\DomainException|\InvalidArgumentException $exception) {
                        // Keep the authoritative expiry transition, but never fabricate
                        // a score when the immutable snapshot/scoring contract is blocked.
                        $skipReason = 'SCORING_BLOCKED: ' . $exception->getMessage();
                    }
                }

                AuditLog::create([
                    'tenant_id' => $attempt->tenant_id,
                    'user_id' => null,
                    'action' => 'EXPIRE_ATTEMPT',
                    'entity_type' => 'ExamAttempt',
                    'entity_id' => $attempt->id,
                    'old_values' => [
                        'status' => 'IN_PROGRESS',
                        'expires_at' => $attempt->expires_at?->toIso8601String(),
                    ],
                    'new_values' => [
                        'status' => 'EXPIRED',
                        'processed_at' => $closedAt->toIso8601String(),
                        'score_id' => $score?->id,
                        'scoring_skipped_reason' => $skipReason,
                    ],
                    'ip_address' => null,
                    'user_agent' => 'cbt:expire-attempts',
                    'created_at' => $closedAt,
                ]);

                return 'expired';
            });
        } finally {
            $tenantContext->setTenantId($previousTenantId);
        }
    }
}
