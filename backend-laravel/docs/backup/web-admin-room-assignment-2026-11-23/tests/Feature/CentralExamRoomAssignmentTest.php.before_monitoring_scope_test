<?php

namespace Tests\Feature;

use App\Contexts\TenantContext;
use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\Exam;
use App\Models\ExamRoomAssignment;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Room;
use App\Models\TeacherProfile;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ExamRoomAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CentralExamRoomAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $admin;
    private User $guruA;
    private User $guruB;
    private User $guruC;
    private Room $roomA;
    private Room $roomB;
    private Exam $examA;
    private Exam $examB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create(['name' => 'Madrasah Central A', 'code' => 'CENTRA']);
        app(TenantContext::class)->setTenantId($this->tenant->id);

        $adminRole = Role::create(['name' => 'ADMIN_MADRASAH', 'label' => 'Admin Madrasah']);
        $guruRole = Role::create(['name' => 'GURU', 'label' => 'Guru']);
        $permission = Permission::create(['name' => 'exam.proctor.assign', 'label' => 'Penugasan Proktor']);
        $adminRole->permissions()->attach($permission->id);

        $this->admin = User::create(['tenant_id' => $this->tenant->id, 'name' => 'Admin', 'email' => 'admin-central@example.test', 'password' => bcrypt('password')]);
        $this->admin->roles()->attach($adminRole->id);

        $this->guruA = $this->makeGuru($guruRole, 'Guru A', 'guru-a-central@example.test');
        $this->guruB = $this->makeGuru($guruRole, 'Guru B', 'guru-b-central@example.test');
        $this->guruC = $this->makeGuru($guruRole, 'Guru C', 'guru-c-central@example.test');

        $this->roomA = Room::create(['code' => 'R-01', 'name' => 'Ruang 01', 'capacity' => 36, 'is_active' => true]);
        $this->roomB = Room::create(['code' => 'R-02', 'name' => 'Ruang 02', 'capacity' => 30, 'is_active' => true]);

        $this->examA = $this->makeExam('ASTS', 'ASTS Semester Ganjil');
        $this->examB = $this->makeExam('UM', 'Ujian Madrasah');
    }

    private function makeGuru(Role $role, string $name, string $email): User
    {
        $guru = User::create(['tenant_id' => $this->tenant->id, 'name' => $name, 'email' => $email, 'password' => bcrypt('password')]);
        $guru->roles()->attach($role->id);
        TeacherProfile::create(['tenant_id' => $this->tenant->id, 'user_id' => $guru->id, 'full_name' => $name]);
        return $guru;
    }

    private function makeExam(string $category, string $title): Exam
    {
        $year = AcademicYear::create(['name' => $title, 'is_active' => true]);
        $assessment = Assessment::create([
            'academic_year_id' => $year->id,
            'title' => $title,
            'assessment_type' => 'SUMMATIVE',
            'created_by' => $this->admin->id,
        ]);

        return Exam::create([
            'tenant_id' => $this->tenant->id,
            'assessment_id' => $assessment->id,
            'title' => $title,
            'category' => $category,
            'is_centralized' => false, // model must derive this from category
            'duration_seconds' => 7200,
            'start_at' => now()->addMinutes(10),
            'end_at' => now()->addHours(3),
            'status' => 'PUBLISHED',
            'created_by' => $this->admin->id,
            'locked_at' => now(),
        ]);
    }

    public function test_multiple_guru_can_be_assigned_to_one_room_session(): void
    {
        $this->actingAs($this->admin);

        $assignment = app(ExamRoomAssignmentService::class)->create(
            $this->admin,
            $this->examA,
            $this->roomA,
            [$this->guruA->id => 'proctor', $this->guruB->id => 'supervisor'],
            1,
            now()->addMinutes(10),
            now()->addHours(2),
        );

        $this->assertDatabaseCount('exam_room_proctors', 2);
        $this->assertSame(2, $assignment->activeProctors()->count());
        $this->assertTrue($this->examA->fresh()->isCentralized());
    }

    public function test_same_guru_cannot_be_assigned_to_overlapping_room_sessions(): void
    {
        $this->actingAs($this->admin);

        app(ExamRoomAssignmentService::class)->create(
            $this->admin, $this->examA, $this->roomA,
            [$this->guruA->id => 'proctor'], 1,
            now()->addMinutes(10), now()->addHours(2)
        );

        try {
            app(ExamRoomAssignmentService::class)->create(
                $this->admin, $this->examB, $this->roomB,
                [$this->guruA->id => 'supervisor'], 1,
                now()->addMinutes(30), now()->addHours(2)
            );
            $this->fail('Overlapping duty for the same Guru must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('proctors', $exception->errors());
        }

        $this->assertSame(1, ExamRoomAssignment::query()->count());
    }

    public function test_same_room_overlap_is_rejected_without_explicit_mutual_merge(): void
    {
        $this->actingAs($this->admin);

        app(ExamRoomAssignmentService::class)->create(
            $this->admin, $this->examA, $this->roomA,
            [$this->guruA->id => 'proctor'], 1,
            now()->addMinutes(10), now()->addHours(2), false
        );

        try {
            app(ExamRoomAssignmentService::class)->create(
                $this->admin, $this->examB, $this->roomA,
                [$this->guruB->id => 'proctor'], 1,
                now()->addMinutes(30), now()->addHours(2), false
            );
            $this->fail('Overlapping room schedule must be rejected without merge-room approval.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('room', $exception->errors());
        }
    }

    public function test_regular_exam_cannot_be_assigned_by_admin_as_central_room_exam(): void
    {
        $this->actingAs($this->admin);
        $regularExam = $this->makeExam('FORMATIF', 'Kuis Formatif');

        try {
            app(ExamRoomAssignmentService::class)->create(
                $this->admin, $regularExam, $this->roomA,
                [$this->guruC->id => 'proctor'], 1,
                now()->addMinutes(10), now()->addHours(2)
            );
            $this->fail('Regular teacher-managed exam must not enter central Admin room assignment.');
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            $this->assertTrue(true);
        }
    }

    public function test_assigned_guru_can_monitor_room_only_during_session_and_cannot_access_exam_authoring(): void
    {
        $this->actingAs($this->admin);
        $assignment = app(ExamRoomAssignmentService::class)->create(
            $this->admin, $this->examA, $this->roomA,
            [$this->guruA->id => 'proctor'], 1,
            now()->addMinutes(10), now()->addHours(2)
        );

        $this->actingAs($this->guruA);
        $this->assertFalse(\Illuminate\Support\Facades\Gate::allows('monitor', $this->examA));
        $this->assertFalse(\Illuminate\Support\Facades\Gate::allows('viewQuestionBank', $this->examA));
        $this->assertFalse(\Illuminate\Support\Facades\Gate::allows('manageQuestions', $this->examA));
        $this->assertFalse(\Illuminate\Support\Facades\Gate::allows('viewParticipants', $assignment));

        $this->travelTo(now()->addMinutes(15));
        $this->assertTrue(\Illuminate\Support\Facades\Gate::allows('monitor', $this->examA));
        $this->assertTrue(\Illuminate\Support\Facades\Gate::allows('viewParticipants', $assignment));
        $this->assertTrue(\Illuminate\Support\Facades\Gate::allows('releaseToken', $assignment));
        $this->assertTrue(\Illuminate\Support\Facades\Gate::allows('resetLogin', $assignment));
        $this->assertTrue(\Illuminate\Support\Facades\Gate::allows('grantExtraTime', $assignment));
        $this->assertTrue(\Illuminate\Support\Facades\Gate::allows('printAttendance', $assignment));
        $this->assertFalse(\Illuminate\Support\Facades\Gate::allows('viewAnswerKey', $assignment));
    }

}
