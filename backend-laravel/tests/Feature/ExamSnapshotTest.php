<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Assessment;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamParticipant;
use App\Models\Question;
use App\Models\QuestionVersion;
use App\Models\AcademicYear;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\Curriculum;
use App\Models\Phase;
use App\Models\GradeLevel;
use App\Models\Rombel;
use App\Models\StudentProfile;
use App\Support\Security\TeacherAccess;
use App\Models\TeacherProfile;
use App\Models\TeacherSubjectAssignment;
use App\Models\MediaAsset;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Str;

class ExamSnapshotTest extends TestCase
{
    use RefreshDatabase;

    private $tenant;
    private $user;
    private $exam;
    private $context;
    private $rombel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create(['name' => 'Demo', 'code' => 'D']);
        $this->context = app(\App\Contexts\TenantContext::class);
        $this->context->setTenantId($this->tenant->id);

        $this->user = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Guru',
            'email' => 'g@e.com',
            'password' => bcrypt('password')
        ]);
        $role = Role::firstOrCreate(['name' => 'GURU'], ['label' => 'Guru']);
        $this->user->roles()->sync([$role->id]);

        $curriculum = Curriculum::create(['name' => 'Kurikulum Merdeka', 'code' => 'KM', 'is_active' => true]);
        $phase = Phase::create(['curriculum_id' => $curriculum->id, 'name' => 'Fase A', 'code' => 'A']);
        $ay = AcademicYear::create(['tenant_id' => $this->tenant->id, 'name' => '2024', 'is_active' => true]);
        $semester = Semester::create(['tenant_id' => $this->tenant->id, 'academic_year_id' => $ay->id, 'term' => 'GANJIL', 'is_active' => true]);
        $subject = Subject::create(['tenant_id' => $this->tenant->id, 'code' => 'MAT', 'name' => 'Matematika', 'short_name' => 'MTK', 'status' => 'ACTIVE']);
        $gradeLevel = GradeLevel::create(['phase_id' => $phase->id, 'name' => 'Kelas 1', 'code' => '1', 'level' => 1]);
        $rombel = Rombel::create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $ay->id,
            'grade_level_id' => $gradeLevel->id,
            'name' => '1A',
            'status' => 'ACTIVE',
        ]);
        $this->rombel = $rombel;
        $teacherProfile = TeacherProfile::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'full_name' => 'Guru',
        ]);
        TeacherSubjectAssignment::create([
            'tenant_id' => $this->tenant->id,
            'teacher_profile_id' => $teacherProfile->id,
            'subject_id' => $subject->id,
            'rombel_id' => $rombel->id,
            'academic_year_id' => $ay->id,
            'semester_id' => $semester->id,
            'starts_at' => now()->subDay()->toDateString(),
            'status' => 'ACTIVE',
            'created_by' => $this->user->id,
        ]);
        $assessment = Assessment::create([
            'tenant_id' => $this->tenant->id,
            'subject_id' => $subject->id,
            'academic_year_id' => $ay->id,
            'semester_id' => $semester->id,
            'title' => 'Daily',
            'assessment_type' => 'DAILY',
            'created_by' => $this->user->id
        ]);

        $this->exam = Exam::create([
            'tenant_id' => $this->tenant->id,
            'assessment_id' => $assessment->id,
            'title' => 'Math Test',
            'duration_seconds' => 3600,
            'created_by' => $this->user->id,
            'status' => 'DRAFT'
        ]);

        $this->bootstrapCbtRolePermissions();
    }

    public function test_exam_snapshot_remains_immutable_when_bank_question_changes()
    {
        // 1. Create Question V1
        $q = Question::create([
            'owner_type' => 'TENANT',
            'owner_user_id' => $this->user->id,
            'question_code' => 'Q1',
            'created_by' => $this->user->id
        ]);

        $v1 = QuestionVersion::create([
            'question_id' => $q->id,
            'major_version' => 1, 'minor_version' => 0,
            'question_type' => 'ESSAY', 'stem' => 'Old Stem', 'is_current' => true,
            'created_by' => $this->user->id
        ]);

        // 2. Snapshot into Exam
        $response = $this->actingAs($this->user, 'sanctum')->postJson("/api/v1/exams/{$this->exam->id}/questions", [
            'question_version_ids' => [$v1->id]
        ]);
        $response->assertStatus(200);

        $this->assertDatabaseHas('exam_question_snapshots', [
            'exam_id' => $this->exam->id,
            'snapshot_stem' => 'Old Stem'
        ]);

        // 3. Update Bank Question to V2
        $v1->update(['is_current' => false]);
        $v2 = QuestionVersion::create([
            'question_id' => $q->id,
            'major_version' => 2, 'minor_version' => 0,
            'question_type' => 'ESSAY', 'stem' => 'New Stem', 'is_current' => true,
            'created_by' => $this->user->id
        ]);

        // 4. Verify Exam Snapshot still has V1
        $snapshot = $this->exam->snapshots()->first();
        $this->assertEquals('Old Stem', $snapshot->snapshot_stem);
        $this->assertEquals($v1->id, $snapshot->source_question_version_id);
    }

    public function test_exam_snapshot_media_remains_intact_when_original_media_deleted()
    {
        $media = MediaAsset::create([
            'file_name' => 'img.png',
            'original_name' => 'img.png',
            'storage_path' => 'exams/img.png',
            'mime_type' => 'image/png',
            'extension' => 'png',
            'file_size' => 1024,
            'disk' => 'public',
            'owner_type' => 'EXAM',
            'uploaded_by' => $this->user->id
        ]);

        $q = Question::create(['created_by' => $this->user->id]);

        $v = QuestionVersion::create([
            'question_id' => $q->id,
            'major_version' => 1, 'minor_version' => 0, 'question_type' => 'ESSAY', 'stem' => 'S', 'is_current' => true, 'created_by' => $this->user->id
        ]);

        $v->media()->attach($media->id, [
            'id' => (string) Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'role' => 'STEM'
        ]);

        // Snapshot
        $response = $this->actingAs($this->user, 'sanctum')->postJson("/api/v1/exams/{$this->exam->id}/questions", ['question_version_ids' => [$v->id]]);
        $response->assertStatus(200);

        // Delete original media asset
        $media->delete();

        // Verify snapshot still has media path
        $snapshot = $this->exam->snapshots()->first();
        $snapshotMedia = $snapshot->snapshot_media;
        $this->assertCount(1, $snapshotMedia);
        $this->assertEquals('exams/img.png', $snapshotMedia[0]['storage_path']);
    }

    public function test_cannot_modify_locked_exam_questions()
    {
        $this->exam->update(['locked_at' => now()]);

        $this->actingAs($this->user, 'sanctum');

        $response = $this->postJson("/api/v1/exams/{$this->exam->id}/questions", ['question_version_ids' => []]);
        $response->assertStatus(403);
    }

    public function test_lock_integrity_prevents_reorder()
    {
        $this->exam->update(['locked_at' => now()]);

        $this->actingAs($this->user, 'sanctum');
        $response = $this->postJson("/api/v1/exams/{$this->exam->id}/questions/reorder", ['orders' => []]);
        $response->assertStatus(403);
    }

    public function test_lock_integrity_prevents_bulk_remove()
    {
        $this->exam->update(['locked_at' => now()]);

        $this->actingAs($this->user, 'sanctum');
        $response = $this->postJson("/api/v1/exams/{$this->exam->id}/questions/bulk-remove", ['snapshot_ids' => []]);
        $response->assertStatus(403);
    }

    public function test_snapshot_cannot_be_removed_after_an_attempt_exists()
    {
        $snapshot = $this->createSnapshotForMutationTests();
        $this->createAttemptForMutationTests();

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/exams/{$this->exam->id}/questions/{$snapshot->id}");

        $response->assertStatus(409);
        $this->assertDatabaseHas('exam_question_snapshots', ['id' => $snapshot->id]);
    }

    public function test_snapshots_cannot_be_bulk_removed_after_an_attempt_exists()
    {
        $snapshot = $this->createSnapshotForMutationTests();
        $this->createAttemptForMutationTests();

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/exams/{$this->exam->id}/questions/bulk-remove", [
                'snapshot_ids' => [$snapshot->id],
            ]);

        $response->assertStatus(409);
        $this->assertDatabaseHas('exam_question_snapshots', ['id' => $snapshot->id]);
    }

    public function test_snapshot_order_cannot_change_after_an_attempt_exists()
    {
        $snapshot = $this->createSnapshotForMutationTests();
        $this->createAttemptForMutationTests();

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/exams/{$this->exam->id}/questions/reorder", [
                'orders' => [['snapshot_id' => $snapshot->id, 'sequence' => 99]],
            ]);

        $response->assertStatus(409);
        $this->assertDatabaseHas('exam_question_snapshots', [
            'id' => $snapshot->id,
            'sequence' => 0,
        ]);
    }

    public function test_snapshot_sync_is_rejected_after_an_attempt_exists()
    {
        $snapshot = $this->createSnapshotForMutationTests();
        $this->createAttemptForMutationTests();

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/exams/{$this->exam->id}/questions", [
                'question_version_ids' => [$snapshot->source_question_version_id],
                'append' => false,
            ]);

        $response->assertStatus(409);
        $this->assertDatabaseHas('exam_question_snapshots', ['id' => $snapshot->id]);
    }

    private function createSnapshotForMutationTests(): \App\Models\ExamQuestionSnapshot
    {
        $question = Question::create([
            'owner_type' => 'TENANT',
            'owner_user_id' => $this->user->id,
            'question_code' => 'MUTATION-1',
            'created_by' => $this->user->id,
        ]);

        $version = QuestionVersion::create([
            'question_id' => $question->id,
            'major_version' => 1,
            'minor_version' => 0,
            'question_type' => 'ESSAY',
            'stem' => 'Snapshot mutation guard',
            'is_current' => true,
            'created_by' => $this->user->id,
        ]);

        app(\App\Services\ExamSnapshotService::class)->createSnapshots($this->exam, [$version->id]);

        return $this->exam->snapshots()->firstOrFail();
    }

    private function createAttemptForMutationTests(): void
    {
        // Use a real student in the teacher's assigned rombel. The exam
        // policy correctly denies management when an unrelated teacher account
        // is inserted as a participant outside the assignment scope.
        $student = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Siswa Uji',
            'email' => 'student-snapshot@example.test',
            'password' => bcrypt('password'),
        ]);
        $studentRole = Role::firstOrCreate(['name' => 'SISWA'], ['label' => 'Siswa']);
        $student->roles()->sync([$studentRole->id]);

        $studentProfile = StudentProfile::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $student->id,
            'full_name' => 'Siswa Uji',
        ]);
        $this->rombel->students()->attach($studentProfile->id, [
            'tenant_id' => $this->tenant->id,
        ]);

        $this->assertDatabaseHas('rombel_student', [
            'rombel_id' => $this->rombel->id,
            'student_id' => $studentProfile->id,
            'tenant_id' => $this->tenant->id,
        ]);
        $this->assertTrue(
            $student->studentProfile()->exists(),
            'Student user must resolve its tenant-scoped student profile.'
        );
        $this->assertTrue(
            $studentProfile->rombels()->whereKey($this->rombel->id)->exists(),
            'Student profile must resolve the tenant-scoped rombel membership.'
        );
        $this->assertContains(
            $this->rombel->id,
            TeacherAccess::assignedRombelIdsForScope(
                $this->user,
                $this->exam->assessment->subject_id,
                $this->exam->assessment->academic_year_id,
                $this->exam->assessment->semester_id
            ),
            'Teacher must have the exact active assignment for this rombel.'
        );
        $this->assertTrue(
            TeacherAccess::canManageAssessment($this->user, $this->exam->assessment),
            'Teacher fixture must retain assessment management permission.'
        );

        $participant = ExamParticipant::create([
            'exam_id' => $this->exam->id,
            'user_id' => $student->id,
            'status' => 'STARTED',
        ]);

        $this->assertTrue(
            TeacherAccess::canViewExam($this->user, $this->exam->fresh()),
            'Teacher fixture must retain exam visibility after assigning an in-scope student.'
        );

        ExamAttempt::create([
            'exam_id' => $this->exam->id,
            'participant_id' => $participant->id,
            'attempt_number' => 1,
            'status' => 'IN_PROGRESS',
            'started_at' => now(),
        ]);
    }

}
