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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class DeviceLeaseSchemaIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenantA;
    private User $userA;
    private ExamAttempt $attemptA;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenantA = Tenant::create(['name' => 'Lease Tenant A', 'code' => 'LEASEA']);
        $context = app(TenantContext::class);
        $context->setTenantId($this->tenantA->id);

        $this->userA = User::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Siswa Lease A',
            'email' => 'lease-a@example.test',
            'password' => bcrypt('password'),
        ]);

        $year = AcademicYear::create(['name' => 'Lease 2026/2027']);
        $assessment = Assessment::create([
            'academic_year_id' => $year->id,
            'title' => 'Asesmen Lease',
            'assessment_type' => 'SUMMATIVE',
            'created_by' => $this->userA->id,
        ]);
        $exam = Exam::create([
            'tenant_id' => $this->tenantA->id,
            'assessment_id' => $assessment->id,
            'title' => 'Ujian Lease',
            'duration_seconds' => 3600,
            'start_at' => now()->subMinute(),
            'end_at' => now()->addHour(),
            'status' => 'PUBLISHED',
            'created_by' => $this->userA->id,
            'locked_at' => now(),
        ]);
        $participant = ExamParticipant::create([
            'tenant_id' => $this->tenantA->id,
            'exam_id' => $exam->id,
            'user_id' => $this->userA->id,
            'status' => 'ASSIGNED',
        ]);
        $this->attemptA = ExamAttempt::create([
            'tenant_id' => $this->tenantA->id,
            'exam_id' => $exam->id,
            'participant_id' => $participant->id,
            'attempt_number' => 1,
            'status' => 'IN_PROGRESS',
            'started_at' => now(),
            'expires_at' => now()->addHour(),
            'fence_generation' => 1,
        ]);

        $context->setTenantId(null);
        TenantContext::resetInstance();
    }

    private function createDevice(Tenant $tenant, User $user, string $suffix): string
    {
        $id = (string) Str::uuid();
        DB::table('registered_devices')->insert([
            'id' => $id,
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'installation_uuid' => 'install-' . $suffix,
            'device_token' => 'token-' . $suffix . '-' . Str::random(20),
            'device_token_hash' => hash('sha256', 'secret-' . $suffix),
            'platform' => 'ANDROID',
            'status' => 'ACTIVE',
            'registered_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }

    private function insertLease(string $tenantId, string $attemptId, string $deviceId, ?string $releasedBy = null): void
    {
        DB::table('device_leases')->insert([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenantId,
            'exam_attempt_id' => $attemptId,
            'registered_device_id' => $deviceId,
            'fence_generation' => 1,
            'status' => $releasedBy ? 'RELEASED_BY_PROCTOR' : 'ACTIVE',
            'leased_at' => now(),
            'expires_at' => now()->addMinutes(2),
            'released_at' => $releasedBy ? now() : null,
            'released_by' => $releasedBy,
            'release_reason' => $releasedBy ? 'Release oleh petugas terotorisasi' : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createTenantB(): array
    {
        $tenant = Tenant::create(['name' => 'Lease Tenant B', 'code' => 'LEASEB']);
        $context = app(TenantContext::class);
        $context->setTenantId($tenant->id);
        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Siswa Lease B',
            'email' => 'lease-b@example.test',
            'password' => bcrypt('password'),
        ]);
        $context->setTenantId(null);
        TenantContext::resetInstance();

        return [$tenant, $user];
    }

    public function test_registered_device_rejects_user_from_another_tenant(): void
    {
        [$tenantB, $userB] = $this->createTenantB();

        $this->expectException(QueryException::class);
        $this->createDevice($this->tenantA, $userB, 'cross-user');
    }

    public function test_device_lease_rejects_attempt_from_another_tenant(): void
    {
        [$tenantB, $userB] = $this->createTenantB();
        $context = app(TenantContext::class);
        $context->setTenantId($tenantB->id);
        $year = AcademicYear::create(['name' => 'Lease B 2026/2027']);
        $assessment = Assessment::create([
            'academic_year_id' => $year->id,
            'title' => 'Asesmen Lease B',
            'assessment_type' => 'SUMMATIVE',
            'created_by' => $userB->id,
        ]);
        $exam = Exam::create([
            'tenant_id' => $tenantB->id,
            'assessment_id' => $assessment->id,
            'title' => 'Ujian Lease B',
            'duration_seconds' => 3600,
            'start_at' => now()->subMinute(),
            'end_at' => now()->addHour(),
            'status' => 'PUBLISHED',
            'created_by' => $userB->id,
            'locked_at' => now(),
        ]);
        $participant = ExamParticipant::create([
            'tenant_id' => $tenantB->id,
            'exam_id' => $exam->id,
            'user_id' => $userB->id,
            'status' => 'ASSIGNED',
        ]);
        $attemptB = ExamAttempt::create([
            'tenant_id' => $tenantB->id,
            'exam_id' => $exam->id,
            'participant_id' => $participant->id,
            'attempt_number' => 1,
            'status' => 'IN_PROGRESS',
            'started_at' => now(),
            'expires_at' => now()->addHour(),
            'fence_generation' => 1,
        ]);
        $context->setTenantId(null);
        TenantContext::resetInstance();

        $deviceA = $this->createDevice($this->tenantA, $this->userA, 'device-a');
        $this->expectException(QueryException::class);
        $this->insertLease($this->tenantA->id, $attemptB->id, $deviceA);
    }

    public function test_device_lease_rejects_registered_device_from_another_tenant(): void
    {
        [$tenantB, $userB] = $this->createTenantB();
        $deviceB = $this->createDevice($tenantB, $userB, 'device-b');

        $this->expectException(QueryException::class);
        $this->insertLease($this->tenantA->id, $this->attemptA->id, $deviceB);
    }

    public function test_device_lease_rejects_release_actor_from_another_tenant(): void
    {
        [$tenantB, $userB] = $this->createTenantB();
        $deviceA = $this->createDevice($this->tenantA, $this->userA, 'device-a');

        $this->expectException(QueryException::class);
        $this->insertLease($this->tenantA->id, $this->attemptA->id, $deviceA, $userB->id);
    }

    public function test_lease_history_restricts_hard_delete_of_device_and_attempt(): void
    {
        $deviceA = $this->createDevice($this->tenantA, $this->userA, 'device-a');
        $this->insertLease($this->tenantA->id, $this->attemptA->id, $deviceA);

        try {
            DB::table('registered_devices')->where('id', $deviceA)->delete();
            $this->fail('Hard delete of a device with lease history must be restricted.');
        } catch (QueryException) {
            $this->assertDatabaseHas('registered_devices', ['id' => $deviceA]);
        }

        try {
            DB::table('exam_attempts')->where('id', $this->attemptA->id)->delete();
            $this->fail('Hard delete of an attempt with lease history must be restricted.');
        } catch (QueryException) {
            $this->assertDatabaseHas('exam_attempts', ['id' => $this->attemptA->id]);
        }
    }
}
