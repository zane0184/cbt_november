<?php

namespace App\Services\Analytics;

use App\Models\Exam;
use App\Models\ExamAttempt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class AnalyticsCacheService
{
    /**
     * Invalidate all analytics cache for a specific exam within a tenant.
     * Optionally invalidates rombel-specific analytics if an attempt is provided.
     */
    public function invalidateExamAnalytics(Exam $exam, ?ExamAttempt $attempt = null): void
    {
        $tenantId = $exam->tenant_id;

        // Base exam analytics
        $keys = [
            "tenant_{$tenantId}:analytics:exam:{$exam->id}:summary",
            "tenant_{$tenantId}:analytics:exam:{$exam->id}:distribution",
            "tenant_{$tenantId}:analytics:exam:{$exam->id}:items",
        ];

        foreach ($keys as $key) {
            Cache::forget($key);
        }

        // Rombel-specific analytics for this exam
        if ($attempt) {
            $this->invalidateRombelCacheForAttempt($exam, $attempt);
        }
    }

    /**
     * Invalidate rombel-specific analytics for a given exam and attempt.
     */
    public function invalidateRombelCacheForAttempt(Exam $exam, ExamAttempt $attempt): void
    {
        $tenantId = (string) $exam->tenant_id;

        // Do not trust ambient TenantContext or a relation loaded under a different
        // request scope. Resolve every edge using the immutable tenant/exam IDs.
        if ((string) $attempt->tenant_id !== $tenantId || (string) $attempt->exam_id !== (string) $exam->id) {
            return;
        }

        $participant = DB::table('exam_participants')
            ->where('id', $attempt->participant_id)
            ->where('exam_id', $exam->id)
            ->where('tenant_id', $tenantId)
            ->first(['user_id']);

        if (!$participant) {
            return;
        }

        $profile = DB::table('student_profiles')
            ->where('user_id', $participant->user_id)
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->first(['id']);

        if (!$profile) {
            return;
        }

        $rombelIds = DB::table('rombel_student as rs')
            ->join('rombels as r', 'r.id', '=', 'rs.rombel_id')
            ->where('rs.student_id', $profile->id)
            ->where('rs.tenant_id', $tenantId)
            ->where('r.tenant_id', $tenantId)
            ->distinct()
            ->pluck('rs.rombel_id');

        foreach ($rombelIds as $rombelId) {
            Cache::forget("tenant_{$tenantId}:analytics:rombel:{$rombelId}:exam:{$exam->id}");
        }
    }

    /**
     * Invalidate student-specific analytics cache.
     * NOT CACHED in Phase 5B baseline — NO INVALIDATION REQUIRED.
     */
    public function invalidateStudentAnalytics(string $tenantId, string $studentId): void
    {
        // Placeholder if caching is added later
    }

    /**
     * Invalidate subject-specific analytics cache.
     * NOT CACHED in Phase 5B baseline — NO INVALIDATION REQUIRED.
     */
    public function invalidateSubjectAnalytics(string $tenantId, string $subjectId): void
    {
        // Placeholder if caching is added later
    }
}
