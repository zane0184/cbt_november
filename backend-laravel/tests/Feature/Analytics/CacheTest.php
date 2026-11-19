<?php

namespace Tests\Feature\Analytics;

use Tests\TestCase;
use App\Models\User;
use App\Models\Exam;
use App\Models\Tenant;
use App\Models\Rombel;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptScore;
use App\Models\ExamAttemptScoreCurrent;
use App\Models\ExamParticipant;
use App\Models\StudentProfile;
use App\Models\AcademicYear;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\Role;
use App\Models\TeacherProfile;
use App\Models\TeacherSubjectAssignment;
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
            Role::firstOrCreate(['name' => 'ADMIN_MADRASAH'], ['label' => 'Admin'])
        );
        $teacher = User::factory()->create(['tenant_id' => $tenant->id]);
        $teacher->roles()->attach(Role::firstOrCreate(['name' => 'GURU'], ['label' => 'Guru']));
        $exam = Exam::factory()->create(['tenant_id' => $tenant->id]);
        $student = User::factory()->create(['tenant_id' => $tenant->id]);
        $profile = StudentProfile::create([
            'user_id' => $student->id,
            'nis' => '12345',
            'full_name' => 'Student Name'
        ]);
        $rombel = Rombel::factory()->create(['tenant_id' => $tenant->id]);
        $academicYear = AcademicYear::query()->whereKey($rombel->academic_year_id)->firstOrFail();
        $semester = Semester::create([
            'tenant_id' => $tenant->id,
            'academic_year_id' => $academicYear->id,
            'term' => 'GANJIL',
            'is_active' => true,
        ]);
        $subject = Subject::create([
            'tenant_id' => $tenant->id,
            'code' => 'MAT',
            'name' => 'Matematika',
            'short_name' => 'MTK',
            'status' => 'ACTIVE',
        ]);
        $exam->assessment()->update([
            'subject_id' => $subject->id,
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
            'created_by' => $teacher->id,
        ]);
        $teacherProfile = TeacherProfile::create([
            'tenant_id' => $tenant->id,
            'user_id' => $teacher->id,
            'full_name' => $teacher->name,
        ]);
        TeacherSubjectAssignment::create([
            'tenant_id' => $tenant->id,
            'teacher_profile_id' => $teacherProfile->id,
            'subject_id' => $subject->id,
            'rombel_id' => $rombel->id,
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
            'starts_at' => now()->subDay()->toDateString(),
            'status' => 'ACTIVE',
            'created_by' => $teacher->id,
        ]);
        $this->bootstrapCbtRolePermissions();
        \Illuminate\Support\Facades\DB::table('rombel_student')->insert([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'tenant_id' => $tenant->id,
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
        ExamAttemptScoreCurrent::create([
            'tenant_id' => $tenant->id,
            'attempt_id' => $attempt->id,
            'current_score_id' => $score->id,
            'scoring_version' => $score->scoring_version,
        ]);

        // Put stale cache
        $examKey = "tenant_{$tenant->id}:analytics:exam:{$exam->id}:summary";
        $rombelKey = "tenant_{$tenant->id}:analytics:rombel:{$rombel->id}:exam:{$exam->id}";

        Cache::put($examKey, ['stale' => true]);
        Cache::put($rombelKey, ['stale' => true]);

        $this->actingAs($teacher, 'sanctum');

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
