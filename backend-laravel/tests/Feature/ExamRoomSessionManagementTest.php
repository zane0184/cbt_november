<?php

namespace Tests\Feature;

use App\Contexts\TenantContext;
use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\Curriculum;
use App\Models\Exam;
use App\Models\ExamParticipant;
use App\Models\ExamProctorAssignment;
use App\Models\ExamRoom;
use App\Models\GradeLevel;
use App\Models\Permission;
use App\Models\Phase;
use App\Models\Rombel;
use App\Models\Semester;
use App\Models\StudentProfile;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ExamRoomSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ExamRoomSessionManagementTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $admin;
    private User $proctor;
    private Exam $exam;
    private Rombel $rombel;
    private ExamRoomSessionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create(['name' => 'Tenant Room Session', 'code' => 'ROOM1']);
        app(TenantContext::class)->setTenantId($this->tenant->id);

        $adminRole = \App\Models\Role::create(['name' => 'ADMIN_MADRASAH', 'label' => 'Admin Madrasah']);
        $guruRole = \App\Models\Role::create(['name' => 'GURU', 'label' => 'Guru']);
        $siswaRole = \App\Models\Role::create(['name' => 'SISWA', 'label' => 'Siswa']);
        $permission = Permission::create(['name' => 'exam.proctor.assign', 'label' => 'Kelola penugasan Proktor']);
        $adminRole->permissions()->attach($permission->id);

        $this->admin = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Admin Room',
            'email' => 'admin-room@example.test',
            'password' => bcrypt('password'),
        ]);
        $this->admin->roles()->attach($adminRole->id);

        $this->proctor = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Guru Proktor',
            'email' => 'proctor-room@example.test',
            'password' => bcrypt('password'),
        ]);
        $this->proctor->roles()->attach($guruRole->id);
        TeacherProfile::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->proctor->id,
            'full_name' => 'Guru Proktor',
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
            'code' => 'MTK-ROOM',
            'name' => 'Matematika',
            'status' => 'ACTIVE',
        ]);
        $curriculum = Curriculum::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Kurikulum Merdeka',
            'code' => 'KM-ROOM',
            'status' => 'ACTIVE',
            'is_active' => true,
        ]);
        $phase = Phase::create([
            'tenant_id' => $this->tenant->id,
            'curriculum_id' => $curriculum->id,
            'name' => 'Fase A',
            'code' => 'A-ROOM',
            'status' => 'ACTIVE',
        ]);
        $grade = GradeLevel::create([
            'tenant_id' => $this->tenant->id,
            'phase_id' => $phase->id,
            'name' => 'Kelas 1',
            'code' => '1-ROOM',
            'level' => 1,
            'status' => 'ACTIVE',
        ]);
        $this->rombel = Rombel::create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $year->id,
            'grade_level_id' => $grade->id,
            'name' => '1A',
            'status' => 'ACTIVE',
        ]);

        $student = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Siswa Satu',
            'email' => 'student-room@example.test',
            'password' => bcrypt('password'),
        ]);
        $student->roles()->attach($siswaRole->id);
        $profile = StudentProfile::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $student->id,
            'full_name' => 'Siswa Satu',
        ]);
        $this->rombel->students()->attach($profile->id);

        $assessment = Assessment::create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $year->id,
            'semester_id' => $semester->id,
            'subject_id' => $subject->id,
            'title' => 'Asesmen Room Session',
            'assessment_type' => 'SUMMATIVE',
            'created_by' => $this->proctor->id,
        ]);
        $this->exam = Exam::create([
            'tenant_id' => $this->tenant->id,
            'assessment_id' => $assessment->id,
            'title' => 'Ujian Room Session',
            'duration_seconds' => 3600,
            'start_at' => now()->subMinutes(10),
            'end_at' => now()->addHours(2),
            'status' => 'PUBLISHED',
            'created_by' => $this->proctor->id,
            'locked_at' => now(),
        ]);

        ExamParticipant::create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'user_id' => $student->id,
            'rombel_id' => $this->rombel->id,
            'status' => 'ASSIGNED',
        ]);

        $this->service = app(ExamRoomSessionService::class);
        $this->actingAs($this->admin);
    }

    private function createRoom(string $code, int $capacity = 30): ExamRoom
    {
        return $this->service->createRoom($this->admin, [
            'code' => $code,
            'name' => 'Ruang '.$code,
            'location' => 'Lantai 1',
            'capacity' => $capacity,
        ]);
    }

    private function createSession(Exam $exam, int $number = 1, int $startOffset = 5): \App\Models\ExamSession
    {
        return $this->service->createSession(
            $this->admin,
            $exam->id,
            'Sesi '.$number,
            $number,
            now()->addMinutes($startOffset),
            now()->addMinutes($startOffset + 60)
        );
    }

    public function test_admin_can_create_room_session_and_map_a_rombel_with_participant_snapshot(): void
    {
        $room = $this->createRoom('A1');
        $session = $this->createSession($this->exam);
        $sessionRoom = $this->service->attachRoom($this->admin, $this->exam->id, $session->id, $room->id);
        $mapping = $this->service->mapRombel($this->admin, $sessionRoom->id, $this->rombel->id);

        $this->assertDatabaseHas('exam_rooms', [
            'id' => $room->id,
            'tenant_id' => $this->tenant->id,
            'capacity' => 30,
        ]);
        $this->assertDatabaseHas('exam_sessions', [
            'id' => $session->id,
            'exam_id' => $this->exam->id,
            'schedule_revision' => 3,
        ]);
        $this->assertDatabaseHas('exam_session_room_rombels', [
            'id' => $mapping->id,
            'exam_session_room_id' => $sessionRoom->id,
            'rombel_id' => $this->rombel->id,
            'status' => 'ACTIVE',
        ]);
        $this->assertDatabaseHas('exam_participant_room_assignments', [
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'exam_session_room_id' => $sessionRoom->id,
            'status' => 'ACTIVE',
        ]);
    }

    public function test_mapping_rejects_capacity_overflow_without_partial_placement(): void
    {
        $studentRole = \App\Models\Role::where('name', 'SISWA')->firstOrFail();
        $second = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Siswa Dua',
            'email' => 'student-room-2@example.test',
            'password' => bcrypt('password'),
        ]);
        $second->roles()->attach($studentRole->id);
        $profile = StudentProfile::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $second->id,
            'full_name' => 'Siswa Dua',
        ]);
        $this->rombel->students()->attach($profile->id);
        ExamParticipant::create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'user_id' => $second->id,
            'rombel_id' => $this->rombel->id,
            'status' => 'ASSIGNED',
        ]);

        $room = $this->createRoom('CAP1', 1);
        $session = $this->createSession($this->exam);
        $sessionRoom = $this->service->attachRoom($this->admin, $this->exam->id, $session->id, $room->id);

        try {
            $this->service->mapRombel($this->admin, $sessionRoom->id, $this->rombel->id);
            $this->fail('Capacity overflow must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('capacity', $exception->errors());
        }

        $this->assertDatabaseMissing('exam_session_room_rombels', [
            'exam_id' => $this->exam->id,
            'rombel_id' => $this->rombel->id,
            'status' => 'ACTIVE',
        ]);
        $this->assertDatabaseMissing('exam_participant_room_assignments', [
            'exam_id' => $this->exam->id,
            'status' => 'ACTIVE',
        ]);
    }

    public function test_same_physical_room_cannot_be_used_by_overlapping_sessions(): void
    {
        $room = $this->createRoom('OVERLAP');
        $firstSession = $this->createSession($this->exam, 1, 5);
        $this->service->attachRoom($this->admin, $this->exam->id, $firstSession->id, $room->id);

        $secondExam = Exam::create([
            'tenant_id' => $this->tenant->id,
            'assessment_id' => $this->exam->assessment_id,
            'title' => 'Ujian Kedua',
            'duration_seconds' => 3600,
            'start_at' => now()->subMinutes(10),
            'end_at' => now()->addHours(2),
            'status' => 'PUBLISHED',
            'created_by' => $this->proctor->id,
            'locked_at' => now(),
        ]);
        $secondSession = $this->createSession($secondExam, 1, 10);

        try {
            $this->service->attachRoom($this->admin, $secondExam->id, $secondSession->id, $room->id);
            $this->fail('Overlapping room use must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('room', $exception->errors());
        }

        $this->assertSame(1, \App\Models\ExamSessionRoom::query()->where('exam_room_id', $room->id)->count());
    }

    public function test_reschedule_increments_revision_and_invalidates_confirmed_proctor_scope(): void
    {
        $room = $this->createRoom('REV1');
        $session = $this->createSession($this->exam);
        $sessionRoom = $this->service->attachRoom($this->admin, $this->exam->id, $session->id, $room->id);

        $assignment = ExamProctorAssignment::create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'exam_session_room_id' => $sessionRoom->id,
            'schedule_revision' => 2,
            'proctor_user_id' => $this->proctor->id,
            'assigned_by' => $this->admin->id,
            'starts_at' => now()->addMinutes(5),
            'expires_at' => now()->addMinutes(65),
            'status' => 'CONFIRMED',
            'assignment_reason' => 'Penugasan untuk sesi ujian ruang satu',
        ]);

        $updated = $this->service->rescheduleSession(
            $this->admin,
            $session->id,
            now()->addMinutes(10),
            now()->addMinutes(70),
            'Perubahan jadwal berdasarkan edaran madrasah'
        );

        $this->assertSame(3, $updated->schedule_revision);
        $this->assertDatabaseHas('exam_proctor_assignments', [
            'id' => $assignment->id,
            'status' => 'NEEDS_REVIEW',
            'schedule_revision' => 2,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $this->tenant->id,
            'action' => 'EXAM_SESSION_RESCHEDULE',
            'entity_type' => 'ExamSession',
            'entity_id' => $session->id,
        ]);
    }
}
