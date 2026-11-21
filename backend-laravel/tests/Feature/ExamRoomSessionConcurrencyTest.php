<?php

namespace Tests\Feature;

use App\Contexts\TenantContext;
use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\Exam;
use App\Models\ExamRoom;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ExamRoomSessionService;
use Illuminate\Database\DatabaseMigrations;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ExamRoomSessionConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    private ?string $tenantId = null;
    private User $admin;
    private Exam $examOne;
    private Exam $examTwo;
    private ExamRoom $room;
    private ExamRoomSessionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Concurrency locking test requires MySQL 8/InnoDB.');
        }

        $tenant = Tenant::create(['name' => 'Tenant Concurrency', 'code' => 'CONC1']);
        $this->tenantId = $tenant->id;
        app(TenantContext::class)->setTenantId($tenant->id);

        $adminRole = Role::create(['name' => 'ADMIN_MADRASAH', 'label' => 'Admin Madrasah']);
        $permission = Permission::create([
            'name' => 'exam.proctor.assign',
            'label' => 'Kelola metadata ruang dan sesi',
        ]);
        $adminRole->permissions()->attach($permission->id);

        $this->admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin Concurrent',
            'email' => 'admin-concurrent@example.test',
            'password' => bcrypt('password'),
        ]);
        $this->admin->roles()->attach($adminRole->id);

        $year = AcademicYear::create([
            'tenant_id' => $tenant->id,
            'name' => '2026/2027',
            'is_active' => true,
        ]);
        $semester = Semester::create([
            'tenant_id' => $tenant->id,
            'academic_year_id' => $year->id,
            'term' => 'GANJIL',
            'is_active' => true,
        ]);
        $subject = Subject::create([
            'tenant_id' => $tenant->id,
            'code' => 'CONC-MTK',
            'name' => 'Matematika',
            'status' => 'ACTIVE',
        ]);
        $assessment = Assessment::create([
            'tenant_id' => $tenant->id,
            'academic_year_id' => $year->id,
            'semester_id' => $semester->id,
            'subject_id' => $subject->id,
            'title' => 'Asesmen Konflik Ruang',
            'assessment_type' => 'SUMMATIVE',
            'created_by' => $this->admin->id,
        ]);

        $this->examOne = $this->createExam($assessment, 'Ujian Pertama');
        $this->examTwo = $this->createExam($assessment, 'Ujian Kedua');
        $this->service = app(ExamRoomSessionService::class);
        $this->room = $this->service->createRoom($this->admin, [
            'code' => 'CONC-ROOM',
            'name' => 'Ruang Bersama',
            'location' => 'Gedung A',
            'capacity' => 30,
        ]);
    }

    private function createExam(Assessment $assessment, string $title): Exam
    {
        return Exam::create([
            'tenant_id' => $assessment->tenant_id,
            'assessment_id' => $assessment->id,
            'title' => $title,
            'duration_seconds' => 3600,
            'start_at' => now()->addMinutes(5),
            'end_at' => now()->addMinutes(120),
            'status' => 'PUBLISHED',
            'created_by' => $this->admin->id,
            'locked_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        if ($this->tenantId !== null && DB::getDriverName() === 'mysql') {
            DB::setDefaultConnection('mysql');
            $connection = DB::connection('mysql');
            $connection->statement('SET FOREIGN_KEY_CHECKS = 0');

            // The Stage 4B down migration intentionally refuses to discard
            // placement/schedule history. Clean only this disposable test tenant
            // so DatabaseMigrations can safely roll back its isolated schema.
            if (\Illuminate\Support\Facades\Schema::hasTable('exam_participant_room_assignments')) {
                $connection->table('exam_participant_room_assignments')->where('tenant_id', $this->tenantId)->delete();
            }
            if (\Illuminate\Support\Facades\Schema::hasTable('exam_session_room_rombels')) {
                $connection->table('exam_session_room_rombels')->where('tenant_id', $this->tenantId)->delete();
            }
            if (\Illuminate\Support\Facades\Schema::hasTable('exam_session_rooms')) {
                $connection->table('exam_session_rooms')->where('tenant_id', $this->tenantId)->delete();
            }
            if (\Illuminate\Support\Facades\Schema::hasTable('exam_sessions')) {
                $connection->table('exam_sessions')->where('tenant_id', $this->tenantId)->delete();
            }
            if (\Illuminate\Support\Facades\Schema::hasTable('exam_rooms')) {
                $connection->table('exam_rooms')->where('tenant_id', $this->tenantId)->delete();
            }
            if (\Illuminate\Support\Facades\Schema::hasTable('exam_participants')) {
                $connection->table('exam_participants')->where('tenant_id', $this->tenantId)->update(['rombel_id' => null]);
            }
            if (\Illuminate\Support\Facades\Schema::hasTable('exam_proctor_assignments')) {
                $connection->table('exam_proctor_assignments')->where('tenant_id', $this->tenantId)
                    ->update(['exam_session_room_id' => null, 'schedule_revision' => null]);
            }

            $connection->statement('SET FOREIGN_KEY_CHECKS = 1');
        }

        DB::setDefaultConnection('mysql');
        DB::purge('mysql_concurrent');
        parent::tearDown();
    }

    public function test_concurrent_room_attach_is_serialized_and_second_writer_observes_overlap(): void
    {
        $sessionOne = $this->service->createSession(
            $this->admin,
            $this->examOne->id,
            'Sesi Pertama',
            1,
            now()->addMinutes(5),
            now()->addMinutes(65)
        );
        $sessionTwo = $this->service->createSession(
            $this->admin,
            $this->examTwo->id,
            'Sesi Kedua',
            1,
            now()->addMinutes(10),
            now()->addMinutes(70)
        );

        $primary = DB::connection('mysql');
        $primary->beginTransaction();
        $primary->table('exam_rooms')
            ->where('id', $this->room->id)
            ->lockForUpdate()
            ->first();

        DB::setDefaultConnection('mysql');
        $this->service->attachRoom($this->admin, $this->examOne->id, $sessionOne->id, $this->room->id);

        $secondaryConfig = config('database.connections.mysql');
        config(['database.connections.mysql_concurrent' => $secondaryConfig]);
        DB::purge('mysql_concurrent');
        $secondary = DB::connection('mysql_concurrent');
        $secondary->statement('SET SESSION innodb_lock_wait_timeout = 1');
        DB::setDefaultConnection('mysql_concurrent');

        $lockWaitObserved = false;
        try {
            $this->service->attachRoom($this->admin, $this->examTwo->id, $sessionTwo->id, $this->room->id);
        } catch (QueryException $exception) {
            $lockWaitObserved = str_contains(strtolower($exception->getMessage()), 'lock wait timeout')
                || str_contains(strtolower($exception->getMessage()), '1205');
        }

        $this->assertTrue($lockWaitObserved, 'The second connection must wait on the InnoDB room mutex.');
        $this->assertSame(1, $primary->transactionLevel());

        DB::setDefaultConnection('mysql');
        $primary->commit();

        DB::setDefaultConnection('mysql_concurrent');
        try {
            $this->service->attachRoom($this->admin, $this->examTwo->id, $sessionTwo->id, $this->room->id);
            $this->fail('After the first transaction commits, the second writer must observe the overlapping session.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('room', $exception->errors());
        }

        $this->assertSame(1, $secondary->table('exam_session_rooms')
            ->where('tenant_id', $this->tenantId)
            ->where('exam_room_id', $this->room->id)
            ->where('status', 'ACTIVE')
            ->count());
    }
}
