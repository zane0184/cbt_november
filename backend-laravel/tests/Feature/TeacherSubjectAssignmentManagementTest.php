<?php

namespace Tests\Feature;

use App\Contexts\TenantContext;
use App\Livewire\Admin\TeacherSubjectAssignmentManagement;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Curriculum;
use App\Models\GradeLevel;
use App\Models\Phase;
use App\Models\Permission;
use App\Models\Rombel;
use App\Models\Role;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\TeacherSubjectAssignment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class TeacherSubjectAssignmentManagementTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $admin;
    private TeacherProfile $teacher;
    private Subject $subject;
    private AcademicYear $year;
    private Semester $semester;
    private Rombel $rombel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create(['name' => 'Tenant Assignment', 'code' => 'TAS1']);
        app(TenantContext::class)->setTenantId($this->tenant->id);

        $adminRole = Role::create(['name' => 'ADMIN_MADRASAH', 'label' => 'Admin Madrasah']);
        $guruRole = Role::create(['name' => 'GURU', 'label' => 'Guru']);
        $permission = Permission::create(['name' => 'academic.manage', 'label' => 'Kelola akademik']);
        $adminRole->permissions()->attach($permission->id);

        $this->admin = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Admin Assignment',
            'email' => 'admin-assignment@example.test',
            'password' => bcrypt('password'),
        ]);
        $this->admin->roles()->attach($adminRole->id);

        $guru = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Guru Assignment',
            'email' => 'guru-assignment@example.test',
            'password' => bcrypt('password'),
        ]);
        $guru->roles()->attach($guruRole->id);

        $this->teacher = TeacherProfile::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $guru->id,
            'employment_type' => 'ASN',
            'nip' => '198801012026011001',
            'employee_identifier' => '198801012026011001',
            'full_name' => 'Guru Assignment',
        ]);

        $this->year = AcademicYear::create([
            'tenant_id' => $this->tenant->id,
            'name' => '2026/2027',
            'is_active' => true,
        ]);
        $this->semester = Semester::create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $this->year->id,
            'term' => 'GANJIL',
            'is_active' => true,
        ]);
        $this->subject = Subject::create([
            'tenant_id' => $this->tenant->id,
            'code' => 'MTK-TAS',
            'name' => 'Matematika',
            'status' => 'ACTIVE',
        ]);

        $curriculum = Curriculum::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Kurikulum Merdeka',
            'code' => 'KM-TAS',
            'status' => 'ACTIVE',
            'is_active' => true,
        ]);
        $phase = Phase::create([
            'tenant_id' => $this->tenant->id,
            'curriculum_id' => $curriculum->id,
            'name' => 'Fase A',
            'code' => 'A-TAS',
            'status' => 'ACTIVE',
        ]);
        $grade = GradeLevel::create([
            'tenant_id' => $this->tenant->id,
            'phase_id' => $phase->id,
            'name' => 'Kelas 1',
            'code' => '1-TAS',
            'level' => 1,
            'status' => 'ACTIVE',
        ]);
        $this->rombel = Rombel::create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $this->year->id,
            'grade_level_id' => $grade->id,
            'name' => '1A',
            'status' => 'ACTIVE',
        ]);
    }

    private function validPayload(): array
    {
        return [
            'teacherProfileId' => $this->teacher->id,
            'subjectId' => $this->subject->id,
            'rombelId' => $this->rombel->id,
            'academicYearId' => $this->year->id,
            'semesterId' => $this->semester->id,
            'startsAt' => '2026-07-01',
            'endsAt' => '2027-06-30',
            'assignmentStatus' => 'ACTIVE',
        ];
    }

    public function test_admin_can_create_assignment_and_duplicate_submission_is_idempotent(): void
    {
        $component = Livewire::actingAs($this->admin)
            ->test(TeacherSubjectAssignmentManagement::class)
            ->set($this->validPayload())
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('teacher_subject_assignments', 1);

        $component->call('save')->assertHasNoErrors();
        $this->assertDatabaseCount('teacher_subject_assignments', 1);

        $assignment = TeacherSubjectAssignment::query()->firstOrFail();
        $this->assertDatabaseCount('audit_logs', 1);
        $audit = AuditLog::query()->where('action', 'TEACHER_ASSIGNMENT_CREATE')->firstOrFail();
        $this->assertSame($this->tenant->id, $audit->tenant_id);
        $this->assertSame($this->admin->id, $audit->user_id);
        $this->assertSame($assignment->id, $audit->entity_id);
        $this->assertSame('ACTIVE', $audit->new_values['status']);
    }

    public function test_deactivation_preserves_assignment_record_and_history(): void
    {
        $assignment = TeacherSubjectAssignment::create([
            'teacher_profile_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'rombel_id' => $this->rombel->id,
            'academic_year_id' => $this->year->id,
            'semester_id' => $this->semester->id,
            'starts_at' => '2026-07-01',
            'ends_at' => '2027-06-30',
            'status' => 'ACTIVE',
            'created_by' => $this->admin->id,
        ]);

        Livewire::actingAs($this->admin)
            ->test(TeacherSubjectAssignmentManagement::class)
            ->call('deactivate', $assignment->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('teacher_subject_assignments', [
            'id' => $assignment->id,
            'status' => 'INACTIVE',
        ]);
        $this->assertDatabaseCount('teacher_subject_assignments', 1);

        $audit = AuditLog::query()->where('action', 'TEACHER_ASSIGNMENT_DEACTIVATE')->firstOrFail();
        $this->assertSame($assignment->id, $audit->entity_id);
        $this->assertSame('ACTIVE', $audit->old_values['status']);
        $this->assertSame('INACTIVE', $audit->new_values['status']);
    }

    public function test_edit_updates_assignment_and_records_before_after_audit(): void
    {
        $assignment = TeacherSubjectAssignment::create([
            'teacher_profile_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'rombel_id' => $this->rombel->id,
            'academic_year_id' => $this->year->id,
            'semester_id' => $this->semester->id,
            'starts_at' => '2026-07-01',
            'ends_at' => '2027-06-30',
            'status' => 'ACTIVE',
            'created_by' => $this->admin->id,
        ]);

        Livewire::actingAs($this->admin)
            ->test(TeacherSubjectAssignmentManagement::class)
            ->call('edit', $assignment->id)
            ->set('endsAt', '2027-05-31')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('2027-05-31', $assignment->fresh()->ends_at->toDateString());
        $audit = AuditLog::query()->where('action', 'TEACHER_ASSIGNMENT_UPDATE')->firstOrFail();
        $this->assertSame($assignment->id, $audit->entity_id);
        $this->assertSame('2027-06-30', Carbon::parse($audit->old_values['ends_at'])->toDateString());
        $this->assertSame('2027-05-31', Carbon::parse($audit->new_values['ends_at'])->toDateString());
    }

    public function test_assignment_rejects_rombel_and_semester_from_different_academic_year(): void
    {
        $otherYear = AcademicYear::create([
            'tenant_id' => $this->tenant->id,
            'name' => '2027/2028',
            'is_active' => false,
        ]);
        $otherSemester = Semester::create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $otherYear->id,
            'term' => 'GANJIL',
            // Preserve the tenant's one-active-semester invariant in this relationship-mismatch fixture.
            'is_active' => false,
        ]);
        $otherRombel = Rombel::create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $otherYear->id,
            'grade_level_id' => $this->rombel->grade_level_id,
            'name' => '1B',
            'status' => 'ACTIVE',
        ]);

        // Keep the mount-selected academicYearId untouched so the UI's
        // updatedAcademicYearId hook does not clear the intentionally mismatched
        // references before the server-side save guard is exercised.
        $payload = $this->validPayload();
        unset($payload['academicYearId']);
        $payload['rombelId'] = $otherRombel->id;
        $payload['semesterId'] = $otherSemester->id;

        Livewire::actingAs($this->admin)
            ->test(TeacherSubjectAssignmentManagement::class)
            ->set($payload)
            ->call('save')
            ->assertHasErrors(['academicYearId']);

        $this->assertDatabaseCount('teacher_subject_assignments', 0);
    }

    public function test_assignment_rejects_teacher_profile_from_another_tenant(): void
    {
        $otherTenant = Tenant::create(['name' => 'Tenant Foreign', 'code' => 'TAS2']);
        $context = app(TenantContext::class);
        $context->setTenantId($otherTenant->id);

        $guruRole = Role::query()->where('name', 'GURU')->firstOrFail();
        $foreignUser = User::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Guru Foreign',
            'email' => 'guru-foreign-assignment@example.test',
            'password' => bcrypt('password'),
        ]);
        $foreignUser->roles()->attach($guruRole->id);
        $foreignTeacher = TeacherProfile::create([
            'tenant_id' => $otherTenant->id,
            'user_id' => $foreignUser->id,
            'full_name' => 'Guru Foreign',
        ]);

        $context->setTenantId($this->tenant->id);

        Livewire::actingAs($this->admin)
            ->test(TeacherSubjectAssignmentManagement::class)
            ->set(array_merge($this->validPayload(), ['teacherProfileId' => $foreignTeacher->id]))
            ->call('save')
            ->assertHasErrors(['teacherProfileId']);

        $this->assertDatabaseCount('teacher_subject_assignments', 0);
    }

    public function test_same_assignment_key_with_different_dates_is_rejected_as_conflict(): void
    {
        $component = Livewire::actingAs($this->admin)
            ->test(TeacherSubjectAssignmentManagement::class)
            ->set($this->validPayload())
            ->call('save')
            ->assertHasNoErrors();

        $component
            ->set('endsAt', '2027-05-31')
            ->call('save')
            ->assertHasErrors(['teacherProfileId']);

        $this->assertDatabaseCount('teacher_subject_assignments', 1);
        $this->assertDatabaseHas('teacher_subject_assignments', [
            'teacher_profile_id' => $this->teacher->id,
            'ends_at' => '2027-06-30',
        ]);
    }

    public function test_assignment_rejects_end_date_before_start_date(): void
    {
        Livewire::actingAs($this->admin)
            ->test(TeacherSubjectAssignmentManagement::class)
            ->set(array_merge($this->validPayload(), [
                'startsAt' => '2027-01-01',
                'endsAt' => '2026-12-31',
            ]))
            ->call('save')
            ->assertHasErrors(['endsAt']);

        $this->assertDatabaseCount('teacher_subject_assignments', 0);
    }

    public function test_non_admin_guru_and_siswa_cannot_open_assignment_management(): void
    {
        $siswaRole = Role::query()->firstOrCreate(
            ['name' => 'SISWA'],
            ['label' => 'Siswa']
        );

        foreach ([
            ['role' => 'GURU', 'name' => 'Guru Bukan Admin', 'email' => 'guru-denied@example.test'],
            ['role' => 'SISWA', 'name' => 'Siswa Bukan Admin', 'email' => 'siswa-denied@example.test'],
        ] as $fixture) {
            $role = $fixture['role'] === 'SISWA'
                ? $siswaRole
                : Role::query()->where('name', $fixture['role'])->firstOrFail();
            $user = User::create([
                'tenant_id' => $this->tenant->id,
                'name' => $fixture['name'],
                'email' => $fixture['email'],
                'password' => bcrypt('password'),
            ]);
            $user->roles()->attach($role->id);

            Livewire::actingAs($user)
                ->test(TeacherSubjectAssignmentManagement::class)
                ->assertForbidden();
        }
    }

    public function test_admin_without_academic_manage_permission_cannot_open_assignment_management(): void
    {
        $permissionId = Permission::query()->where('name', 'academic.manage')->value('id');
        $this->admin->roles()->firstOrFail()->permissions()->detach($permissionId);

        Livewire::actingAs($this->admin)
            ->test(TeacherSubjectAssignmentManagement::class)
            ->assertForbidden();
    }
}
