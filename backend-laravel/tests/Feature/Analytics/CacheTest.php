<?php

namespace Tests\Feature\Analytics;

use Tests\TestCase;
use App\Models\User;
use App\Models\Exam;
use App\Models\Tenant;
use App\Models\Rombel;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptScore;
use App\Models\ExamParticipant;
use App\Models\StudentProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;

class CacheTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function finalization_invalidates_exam_and_rombel_analytics_cache()
    {
        $tenant = Tenant::factory()->create();
        $this->setTenantContext($tenant->id);

        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->roles()->attach(
            \App\Models\Role::firstOrCreate(['name' => 'ADMIN_MADRASAH'], ['label' => 'Admin'])
        );
        $exam = Exam::factory()->create(['tenant_id' => $tenant->id]);
        $student = User::factory()->create(['tenant_id' => $tenant->id]);
        $profile = StudentProfile::create([
            'user_id' => $student->id,
            'nis' => '12345',
            'full_name' => 'Student Name'
        ]);
        $rombel = Rombel::factory()->create(['tenant_id' => $tenant->id]);
        \Illuminate\Support\Facades\DB::table('rombel_student')->insert([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'rombel_id' => $rombel->id,
            'student_id' => $profile->id,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $participant = ExamParticipant::factory()->create([
            'exam_id' => $exam->id,
            'user_id' => $student->id,
            'tenant_id' => $tenant->id
        ]);

        $attempt = ExamAttempt::factory()->create([
            'exam_id' => $exam->id,
            'participant_id' => $participant->id,
            'tenant_id' => $tenant->id,
            'status' => 'SUBMITTED'
        ]);

        $score = ExamAttemptScore::factory()->create([
            'attempt_id' => $attempt->id,
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'tenant_id' => $tenant->id,
            'grading_status' => 'AUTO_SCORED'
        ]);

        // Put stale cache
        $examKey = "tenant_{$tenant->id}:analytics:exam:{$exam->id}:summary";
        $rombelKey = "tenant_{$tenant->id}:analytics:rombel:{$rombel->id}:exam:{$exam->id}";

        Cache::put($examKey, ['stale' => true]);
        Cache::put($rombelKey, ['stale' => true]);

        $this->actingAs($admin);

        // Finalize via API
        $response = $this->postJson("/api/v1/exams/{$exam->id}/attempts/{$attempt->id}/finalize");
        $response->assertStatus(200);

        // Verify cache is invalidated
        $this->assertFalse(Cache::has($examKey));
        $this->assertFalse(Cache::has($rombelKey));
    }

    /** @test */
    public function tenant_a_invalidation_does_not_affect_tenant_b()
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $this->setTenantContext($tenantA->id);
        $examA = Exam::factory()->create(['tenant_id' => $tenantA->id]);

        $this->setTenantContext($tenantB->id);
        $examB = Exam::factory()->create(['tenant_id' => $tenantB->id]);

        $keyA = "tenant_{$tenantA->id}:analytics:exam:{$examA->id}:summary";
        $keyB = "tenant_{$tenantB->id}:analytics:exam:{$examB->id}:summary";

        Cache::put($keyA, ['data' => 'A']);
        Cache::put($keyB, ['data' => 'B']);

        // Invalidate A manually via service to test isolation
        app(\App\Services\Analytics\AnalyticsCacheService::class)->invalidateExamAnalytics($examA);

        $this->assertFalse(Cache::has($keyA));
        $this->assertTrue(Cache::has($keyB));
    }
}
