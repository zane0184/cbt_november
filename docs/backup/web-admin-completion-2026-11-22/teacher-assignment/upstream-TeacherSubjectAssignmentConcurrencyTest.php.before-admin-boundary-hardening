<?php

namespace Tests\Feature;

use App\Contexts\TenantContext;
use App\Livewire\Admin\TeacherSubjectAssignmentManagement;
use App\Models\AcademicYear;
use App\Models\Curriculum;
use App\Models\GradeLevel;
use App\Models\Permission;
use App\Models\Phase;
use App\Models\Rombel;
use App\Models\Role;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\TeacherSubjectAssignment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class TeacherSubjectAssignmentConcurrencyTest extends TestCase
{
    private Tenant $tenant;
    private User $admin;
    private array $payload;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Tenant mutex concurrency test requires MySQL 8/InnoDB.');
        }

        // Rebuild only the disposable test schema; do not roll back the full batch,
        // which intentionally contains a separate forward-only Device Lease migration.
        $this->artisan('migrate:fresh', ['--force' => true]);
        $this->assertSame(1, (int) DB::selectOne('SELECT @@FOREIGN_KEY_CHECKS AS enabled')->enabled);

        $version = (string) DB::selectOne('SELECT VERSION() AS version')->version;
        $this->assertGreaterThanOrEqual(8, (int) explode('.', $version)[0]);

        $this->tenant = Tenant::create(['name' => 'Tenant Assignment Race', 'code' => 'TAR1']);
        app(TenantContext::class)->setTenantId($this->tenant->id);

        $adminRole = Role::create(['name' => 'ADMIN_MADRASAH', 'label' => 'Admin Madrasah']);
        $guruRole = Role::query()->firstOrCreate(
            ['name' => 'GURU'],
            ['label' => 'Guru']
        );
        $permission = Permission::create(['name' => 'academic.manage', 'label' => 'Kelola akademik']);
        $adminRole->permissions()->attach($permission->id);

        $this->admin = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Admin Assignment Race',
            'email' => 'admin-assignment-race@example.test',
            'password' => bcrypt('password'),
        ]);
        $this->admin->roles()->attach($adminRole->id);

        $guru = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Guru Assignment Race',
            'email' => 'guru-assignment-race@example.test',
            'password' => bcrypt('password'),
        ]);
        $guru->roles()->attach($guruRole->id);
        $teacher = TeacherProfile::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $guru->id,
            'full_name' => 'Guru Assignment Race',
        ]);

        $year = AcademicYear::create([
            'tenant_id' => $this->tenant->id,
            'name' => '2026/2027',
            'is_active' => true,
        ]);
        $semester = Semester::create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $year->id,
            'term' => 'GANJIL',
            'is_active' => true,
        ]);
        $subject = Subject::create([
            'tenant_id' => $this->tenant->id,
            'code' => 'MTK-RACE',
            'name' => 'Matematika',
            'status' => 'ACTIVE',
        ]);
        $curriculum = Curriculum::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Kurikulum Merdeka',
            'code' => 'KM-RACE',
            'status' => 'ACTIVE',
            'is_active' => true,
        ]);
        $phase = Phase::create([
            'tenant_id' => $this->tenant->id,
            'curriculum_id' => $curriculum->id,
            'name' => 'Fase A',
            'code' => 'A-RACE',
            'status' => 'ACTIVE',
        ]);
        $grade = GradeLevel::create([
            'tenant_id' => $this->tenant->id,
            'phase_id' => $phase->id,
            'name' => 'Kelas 1',
            'code' => '1-RACE',
            'level' => 1,
            'status' => 'ACTIVE',
        ]);
        $rombel = Rombel::create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $year->id,
            'grade_level_id' => $grade->id,
            'name' => '1A-RACE',
            'status' => 'ACTIVE',
        ]);

        $this->payload = [
            'teacherProfileId' => $teacher->id,
            'subjectId' => $subject->id,
            'rombelId' => $rombel->id,
            'academicYearId' => $year->id,
            'semesterId' => $semester->id,
            'startsAt' => '2026-07-01',
            'endsAt' => '2027-06-30',
            'assignmentStatus' => 'ACTIVE',
        ];
    }

    public function test_second_writer_waits_for_tenant_mutex_then_observes_existing_assignment(): void
    {
        $engine = DB::selectOne(
            "SELECT ENGINE AS engine FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'teacher_subject_assignments'"
        );
        $this->assertSame('InnoDB', (string) $engine->engine);

        $primary = DB::connection('mysql');
        $primary->beginTransaction();
        $primary->table('tenants')
            ->where('id', $this->tenant->id)
            ->lockForUpdate()
            ->first();

        TeacherSubjectAssignment::create([
            'teacher_profile_id' => $this->payload['teacherProfileId'],
            'subject_id' => $this->payload['subjectId'],
            'rombel_id' => $this->payload['rombelId'],
            'academic_year_id' => $this->payload['academicYearId'],
            'semester_id' => $this->payload['semesterId'],
            'starts_at' => $this->payload['startsAt'],
            'ends_at' => $this->payload['endsAt'],
            'status' => 'ACTIVE',
            'created_by' => $this->admin->id,
        ]);

        $secondaryConfig = config('database.connections.mysql');
        config(['database.connections.mysql_concurrent' => $secondaryConfig]);
        DB::purge('mysql_concurrent');
        $secondary = DB::connection('mysql_concurrent');
        $primaryConnectionId = (int) $primary->selectOne('SELECT CONNECTION_ID() AS id')->id;
        $secondaryConnectionId = (int) $secondary->selectOne('SELECT CONNECTION_ID() AS id')->id;
        $this->assertNotSame($primaryConnectionId, $secondaryConnectionId, 'The race test must use two independent MySQL sessions.');
        $secondary->statement('SET SESSION innodb_lock_wait_timeout = 1');

        $lockWaitObserved = false;
        DB::setDefaultConnection('mysql_concurrent');

        try {
            Livewire::actingAs($this->admin)
                ->test(TeacherSubjectAssignmentManagement::class)
                ->set($this->payload)
                ->call('save')
                ->assertHasNoErrors();
        } catch (QueryException $exception) {
            $message = strtolower($exception->getMessage());
            $lockWaitObserved = str_contains($message, 'lock wait timeout')
                || str_contains($message, '1205');
        } finally {
            DB::setDefaultConnection('mysql');
            $primary->commit();
        }

        $this->assertTrue($lockWaitObserved, 'The second writer must wait for the tenant row mutex.');

        DB::setDefaultConnection('mysql_concurrent');
        Livewire::actingAs($this->admin)
            ->test(TeacherSubjectAssignmentManagement::class)
            ->set($this->payload)
            ->call('save')
            ->assertHasNoErrors();

        DB::setDefaultConnection('mysql');
        $this->assertSame(1, DB::table('teacher_subject_assignments')
            ->where('tenant_id', $this->tenant->id)
            ->count());
    }

    protected function tearDown(): void
    {
        DB::setDefaultConnection('mysql');
        DB::purge('mysql_concurrent');
        parent::tearDown();
    }
}
