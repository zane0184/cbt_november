<?php

namespace Tests\Feature;

use App\Livewire\Admin\StudentRombelManagement;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\GradeLevel;
use App\Models\Permission;
use App\Models\Rombel;
use App\Models\Role;
use App\Models\StudentAcademicEvent;
use App\Models\StudentProfile;
use App\Models\Tenant;
use App\Models\User;
use App\Services\LoginIdentityResolver;
use App\Services\StudentLifecycleService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class StudentLifecycleServiceTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $admin;
    private Role $studentRole;
    private AcademicYear $oldYear;
    private AcademicYear $activeYear;
    private GradeLevel $grade1;
    private GradeLevel $grade2;
    private GradeLevel $grade6;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create(['name' => 'Madrasah Lifecycle', 'code' => 'LIFECYCLE']);
        $this->setTenantContext($this->tenant->id);

        $adminRole = Role::create(['name' => 'ADMIN_MADRASAH', 'label' => 'Admin Madrasah']);
        $permission = Permission::firstOrCreate(['name' => 'academic.manage'], ['label' => 'Kelola Akademik']);
        $adminRole->permissions()->attach($permission->id);

        $this->admin = User::create([
            'name' => 'Admin Lifecycle',
            'username' => 'admin.lifecycle',
            'email' => 'admin.lifecycle@madrasah.test',
            'password' => bcrypt('StrongPassword123!'),
        ]);
        $this->admin->roles()->attach($adminRole->id);
        $this->actingAs($this->admin);

        $this->studentRole = Role::create(['name' => 'SISWA', 'label' => 'Siswa']);
        $this->oldYear = AcademicYear::create(['name' => '2025/2026', 'is_active' => false]);
        $this->activeYear = AcademicYear::create(['name' => '2026/2027', 'is_active' => true]);

        $this->grade1 = GradeLevel::create(['name' => 'Kelas 1', 'code' => '1', 'level' => 1, 'status' => 'ACTIVE']);
        $this->grade2 = GradeLevel::create(['name' => 'Kelas 2', 'code' => '2', 'level' => 2, 'status' => 'ACTIVE']);
        $this->grade6 = GradeLevel::create(['name' => 'Kelas 6', 'code' => '6', 'level' => 6, 'status' => 'ACTIVE']);
    }

    private function createStudent(string $nisn = '1234567890', string $name = 'Siswa Lifecycle'): StudentProfile
    {
        $user = User::create([
            'name' => $name,
            'username' => $nisn,
            'password' => bcrypt('StudentPassword123!'),
        ]);
        $user->roles()->attach($this->studentRole->id);

        return StudentProfile::create([
            'user_id' => $user->id,
            'full_name' => $name,
            'nisn' => $nisn,
            'academic_status' => 'ACTIVE',
        ]);
    }

    private function createRombel(AcademicYear $year, GradeLevel $grade, string $name, string $status = 'ACTIVE'): Rombel
    {
        return Rombel::create([
            'academic_year_id' => $year->id,
            'grade_level_id' => $grade->id,
            'name' => $name,
            'status' => $status,
        ]);
    }

    public function test_enrollment_sets_entry_year_and_creates_append_only_history(): void
    {
        $student = $this->createStudent();
        $rombel = $this->createRombel($this->activeYear, $this->grade1, '1A');

        $event = app(StudentLifecycleService::class)->enroll(
            $this->tenant->id, $this->admin->id, $student->id, $rombel->id,
            '2026-07-01', 'Penempatan awal tahun pelajaran.'
        );

        $this->assertSame('ENROLLED', $event->event_type);
        $this->assertSame($this->activeYear->id, $student->fresh()->entry_academic_year_id);
        $this->assertDatabaseHas('rombel_student', [
            'tenant_id' => $this->tenant->id,
            'student_id' => $student->id,
            'rombel_id' => $rombel->id,
            'academic_year_id' => $this->activeYear->id,
        ]);
        $this->assertDatabaseHas('student_academic_events', [
            'tenant_id' => $this->tenant->id,
            'student_id' => $student->id,
            'event_type' => 'ENROLLED',
            'reason' => 'Penempatan awal tahun pelajaran.',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $this->tenant->id,
            'action' => 'STUDENT_ACADEMIC_EVENT',
            'entity_id' => $event->id,
        ]);

        $this->expectException(ValidationException::class);
        app(StudentLifecycleService::class)->enroll(
            $this->tenant->id, $this->admin->id, $student->id,
            $this->createRombel($this->activeYear, $this->grade1, '1B')->id,
            '2026-07-02', 'Penempatan kedua tidak boleh.'
        );
    }

    public function test_transfer_replaces_only_same_year_placement_and_records_from_to(): void
    {
        $student = $this->createStudent();
        $from = $this->createRombel($this->activeYear, $this->grade1, '1A');
        $to = $this->createRombel($this->activeYear, $this->grade1, '1B');

        app(StudentLifecycleService::class)->enroll(
            $this->tenant->id, $this->admin->id, $student->id, $from->id,
            '2026-07-01', 'Penempatan awal siswa.'
        );
        $event = app(StudentLifecycleService::class)->transfer(
            $this->tenant->id, $this->admin->id, $student->id, $from->id, $to->id,
            '2026-08-01', 'Mutasi rombel atas penyesuaian kapasitas.'
        );

        $this->assertSame('TRANSFERRED', $event->event_type);
        $this->assertSame($from->id, $event->from_rombel_id);
        $this->assertSame($to->id, $event->to_rombel_id);
        $this->assertDatabaseMissing('rombel_student', ['student_id' => $student->id, 'rombel_id' => $from->id]);
        $this->assertDatabaseHas('rombel_student', [
            'tenant_id' => $this->tenant->id,
            'student_id' => $student->id,
            'rombel_id' => $to->id,
            'academic_year_id' => $this->activeYear->id,
        ]);
    }

    public function test_promotion_and_retention_preserve_prior_year_membership(): void
    {
        $promoted = $this->createStudent('1234567891', 'Siswa Naik');
        $retained = $this->createStudent('1234567892', 'Siswa Tinggal');
        $oldRombel = $this->createRombel($this->oldYear, $this->grade1, '1A', 'INACTIVE');
        $promoteTarget = $this->createRombel($this->activeYear, $this->grade2, '2A');
        $retainTarget = $this->createRombel($this->activeYear, $this->grade1, '1B');

        $oldRombel->students()->attach($promoted->id, ['tenant_id' => $this->tenant->id, 'academic_year_id' => $this->oldYear->id]);
        $oldRombel->students()->attach($retained->id, ['tenant_id' => $this->tenant->id, 'academic_year_id' => $this->oldYear->id]);

        $promotion = app(StudentLifecycleService::class)->promote(
            $this->tenant->id, $this->admin->id, $promoted->id, $oldRombel->id, $promoteTarget->id,
            '2026-07-01', 'Naik kelas sesuai hasil evaluasi.'
        );
        $retention = app(StudentLifecycleService::class)->retain(
            $this->tenant->id, $this->admin->id, $retained->id, $oldRombel->id, $retainTarget->id,
            '2026-07-01', 'Tinggal kelas berdasarkan hasil rapat dewan guru.'
        );

        $this->assertSame('PROMOTED', $promotion->event_type);
        $this->assertSame('RETAINED', $retention->event_type);
        $this->assertDatabaseHas('rombel_student', ['student_id' => $promoted->id, 'rombel_id' => $oldRombel->id, 'academic_year_id' => $this->oldYear->id]);
        $this->assertDatabaseHas('rombel_student', ['student_id' => $promoted->id, 'rombel_id' => $promoteTarget->id, 'academic_year_id' => $this->activeYear->id]);
        $this->assertDatabaseHas('rombel_student', ['student_id' => $retained->id, 'rombel_id' => $oldRombel->id, 'academic_year_id' => $this->oldYear->id]);
        $this->assertDatabaseHas('rombel_student', ['student_id' => $retained->id, 'rombel_id' => $retainTarget->id, 'academic_year_id' => $this->activeYear->id]);
    }

    public function test_graduation_and_alumni_status_block_student_login(): void
    {
        $student = $this->createStudent('1234567893', 'Siswa Kelas Enam');
        $classSix = $this->createRombel($this->activeYear, $this->grade6, '6A');
        $classSix->students()->attach($student->id, ['tenant_id' => $this->tenant->id, 'academic_year_id' => $this->activeYear->id]);

        $event = app(StudentLifecycleService::class)->graduate(
            $this->tenant->id, $this->admin->id, $student->id, $classSix->id,
            '2027-06-30', 'Dinyatakan lulus berdasarkan rapat kelulusan.'
        );
        $this->assertSame('GRADUATED', $student->fresh()->academic_status);
        $this->assertSame('GRADUATED', $event->event_type);
        $this->assertNull(app(LoginIdentityResolver::class)->resolve('1234567893'));

        $alumni = app(StudentLifecycleService::class)->changeStatus(
            $this->tenant->id, $this->admin->id, $student->id, 'ALUMNI',
            '2027-07-01', 'Data dipindahkan ke arsip alumni.'
        );
        $this->assertSame('ALUMNI', $student->fresh()->academic_status);
        $this->assertSame('ALUMNI', $alumni->event_type);
        $this->assertNull(app(LoginIdentityResolver::class)->resolve('1234567893'));
    }

    public function test_student_status_can_be_deactivated_and_reactivated_with_history(): void
    {
        $student = $this->createStudent('1234567894', 'Siswa Status');
        $this->assertNotNull(app(LoginIdentityResolver::class)->resolve('1234567894'));

        app(StudentLifecycleService::class)->changeStatus(
            $this->tenant->id, $this->admin->id, $student->id, 'INACTIVE',
            '2026-09-01', 'Siswa mengundurkan diri sementara.'
        );
        $this->assertSame('INACTIVE', $student->fresh()->academic_status);
        $this->assertNull(app(LoginIdentityResolver::class)->resolve('1234567894'));

        app(StudentLifecycleService::class)->changeStatus(
            $this->tenant->id, $this->admin->id, $student->id, 'ACTIVE',
            '2026-10-01', 'Siswa kembali aktif berdasarkan surat orang tua.'
        );
        $this->assertSame('ACTIVE', $student->fresh()->academic_status);
        $this->assertNotNull(app(LoginIdentityResolver::class)->resolve('1234567894'));
        $this->assertSame(2, StudentAcademicEvent::query()->where('student_id', $student->id)->count());
    }

    public function test_database_unique_index_rejects_two_placements_for_same_student_year(): void
    {
        $student = $this->createStudent('1234567895', 'Siswa Unique');
        $first = $this->createRombel($this->activeYear, $this->grade1, '1A');
        $second = $this->createRombel($this->activeYear, $this->grade1, '1B');
        app(StudentLifecycleService::class)->enroll(
            $this->tenant->id, $this->admin->id, $student->id, $first->id,
            '2026-07-01', 'Penempatan awal tahun berjalan.'
        );

        $this->expectException(QueryException::class);
        DB::table('rombel_student')->insert([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'rombel_id' => $second->id,
            'student_id' => $student->id,
            'academic_year_id' => $this->activeYear->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_livewire_admin_can_enroll_student_and_records_history(): void
    {
        $student = $this->createStudent('1234567896', 'Siswa Livewire');
        $rombel = $this->createRombel($this->activeYear, $this->grade1, '1A');

        Livewire::actingAs($this->admin)->test(StudentRombelManagement::class)
            ->set('selectedStudentId', $student->id)
            ->set('selectedRombelId', $rombel->id)
            ->set('enrollmentEffectiveDate', '2026-07-01')
            ->set('enrollmentReason', 'Penempatan awal melalui panel Admin.')
            ->call('addStudentToRombel')
            ->assertHasNoErrors()
            ->assertSee('Histori Akademik Siswa');

        $this->assertDatabaseHas('rombel_student', [
            'student_id' => $student->id,
            'rombel_id' => $rombel->id,
            'academic_year_id' => $this->activeYear->id,
        ]);
    }

    public function test_service_rejects_admin_with_multiple_roles(): void
    {
        $guruRole = Role::create(['name' => 'GURU', 'label' => 'Guru']);
        $this->admin->roles()->attach($guruRole->id);
        $student = $this->createStudent('1234567897', 'Siswa Role');
        $rombel = $this->createRombel($this->activeYear, $this->grade1, '1A');

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        app(StudentLifecycleService::class)->enroll(
            $this->tenant->id, $this->admin->id, $student->id, $rombel->id,
            '2026-07-01', 'Penempatan harus ditolak.'
        );
    }
}
