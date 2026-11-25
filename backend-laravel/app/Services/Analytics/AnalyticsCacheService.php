<?php

namespace App\Services\Analytics;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\User;
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
        $tenantId = $exam->tenant_id;
        $student = $attempt->participant->user;

        if (!$student) return;

        // Ensure student profile and rombels are loaded
        $student->loadMissing('studentProfile.rombels');

        if ($student->studentProfile) {
            foreach ($student->studentProfile->rombels as $rombel) {
                Cache::forget("tenant_{$tenantId}:analytics:rombel:{$rombel->id}:exam:{$exam->id}");
            }
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
