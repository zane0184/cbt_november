<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Assessment;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamParticipant;
use App\Models\StudentProfile;

use App\Models\Permission;

use App\Models\Question;
use App\Models\QuestionVersion;
use App\Models\AcademicYear;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\Curriculum;
use App\Models\Phase;
use App\Models\GradeLevel;
use App\Models\Rombel;
use App\Models\TeacherProfile;
use App\Models\TeacherSubjectAssignment;
use App\Models\MediaAsset;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;


class ExamSnapshotTest extends TestCase
{
    use RefreshDatabase;

    private $tenant;
    private $user;
    private $student;
    private $rombel;
    private $exam;
    private $context;

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
        $role = Role::create(['name' => 'GURU', 'label' => 'Guru']);
        $teacherPermissions = collect(['exam.manage', 'exam.assign', 'exam.monitor', 'exam.token', 'exam.grade', 'report.view', 'question.manage'])
            ->map(fn ($name) => Permission::firstOrCreate(['name' => $name], ['label' => $name]));
        $role->permissions()->sync($teacherPermissions->pluck('id')->all());
        $this->user->roles()->attach($role);
        $this->user->refresh()->load('roles.permissions');
        $this->student = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Siswa Uji',
            'email' => 'student@e.com',
            'password' => bcrypt('password'),
        ]);

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
        $studentProfile = StudentProfile::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->student->id,
            'full_name' => 'Siswa Uji',
        ]);
        $studentProfile->rombels()->attach($rombel->id, ['tenant_id' => $this->tenant->id]);
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

        $this->assertTrue($this->user->hasRole('GURU'), 'Snapshot test fixture must authenticate as GURU.');
        $this->assertTrue($this->user->hasPermission('exam.manage'), 'Snapshot test fixture must grant exam.manage.');
        $this->assertTrue($this->user->hasPermission('question.manage'), 'Snapshot test fixture must grant question.manage.');
        $this->assertTrue(\App\Support\Security\TeacherAccess::canManageExam($this->user, $this->exam), 'Snapshot test fixture must have an active matching teacher assignment.');

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

    public function test_snapshot_from_another_exam_cannot_be_removed_or_reordered()
    {
        $sourceSnapshot = $this->createSnapshotForMutationTests();

        $otherExam = Exam::create([
            'tenant_id' => $this->tenant->id,
            'assessment_id' => $this->exam->assessment_id,
            'title' => 'Other exam',
            'duration_seconds' => 1800,
            'created_by' => $this->user->id,
            'status' => 'DRAFT',
        ]);
        app(\App\Services\ExamSnapshotService::class)->createSnapshots(
            $otherExam,
            [$sourceSnapshot->source_question_version_id]
        );
        $foreignSnapshot = $otherExam->snapshots()->firstOrFail();

        $this->actingAs($this->user, 'sanctum');

        $remove = $this->deleteJson("/api/v1/exams/{$this->exam->id}/questions/{$foreignSnapshot->id}");
        $remove->assertStatus(404);

        $bulkRemove = $this->postJson("/api/v1/exams/{$this->exam->id}/questions/bulk-remove", [
            'snapshot_ids' => [$foreignSnapshot->id],
        ]);
        $bulkRemove->assertStatus(404);

        $reorder = $this->postJson("/api/v1/exams/{$this->exam->id}/questions/reorder", [
            'orders' => [['snapshot_id' => $foreignSnapshot->id, 'sequence' => 99]],
        ]);
        $reorder->assertStatus(404);

        $this->assertDatabaseHas('exam_question_snapshots', [
            'id' => $foreignSnapshot->id,
            'exam_id' => $otherExam->id,
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_snapshot_from_another_tenant_cannot_be_mutated()
    {
        $otherTenant = Tenant::create(['name' => 'Other tenant', 'code' => 'OTHER-TENANT']);
        $this->context->setTenantId($otherTenant->id);

        $otherUser = User::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Other Guru',
            'email' => 'other-guru@example.test',
            'password' => bcrypt('password'),
        ]);
        $otherYear = AcademicYear::create([
            'tenant_id' => $otherTenant->id,
            'name' => '2026/2027',
            'is_active' => true,
        ]);
        $otherSemester = Semester::create([
            'tenant_id' => $otherTenant->id,
            'academic_year_id' => $otherYear->id,
            'term' => 'GANJIL',
            'is_active' => true,
        ]);
        $otherSubject = Subject::create([
            'tenant_id' => $otherTenant->id,
            'code' => 'OTH-MAT',
            'name' => 'Other Mathematics',
            'short_name' => 'OMT',
            'status' => 'ACTIVE',
        ]);
        $otherAssessment = Assessment::create([
            'tenant_id' => $otherTenant->id,
            'subject_id' => $otherSubject->id,
            'academic_year_id' => $otherYear->id,
            'semester_id' => $otherSemester->id,
            'title' => 'Other tenant assessment',
            'assessment_type' => 'DAILY',
            'created_by' => $otherUser->id,
        ]);
        $otherExam = Exam::create([
            'tenant_id' => $otherTenant->id,
            'assessment_id' => $otherAssessment->id,
            'title' => 'Other tenant exam',
            'duration_seconds' => 1800,
            'created_by' => $otherUser->id,
            'status' => 'DRAFT',
        ]);
        $otherQuestion = Question::create([
            'owner_type' => 'TENANT',
            'owner_user_id' => $otherUser->id,
            'question_code' => 'OTHER-TENANT-Q1',
            'created_by' => $otherUser->id,
        ]);
        $otherVersion = QuestionVersion::create([
            'question_id' => $otherQuestion->id,
            'major_version' => 1,
            'minor_version' => 0,
            'question_type' => 'ESSAY',
            'stem' => 'Other tenant snapshot',
            'is_current' => true,
            'created_by' => $otherUser->id,
        ]);
        app(\App\Services\ExamSnapshotService::class)->createSnapshots($otherExam, [$otherVersion->id]);
        $foreignSnapshot = $otherExam->snapshots()->firstOrFail();

        $this->context->setTenantId($this->tenant->id);
        $this->actingAs($this->user, 'sanctum');

        $response = $this->postJson("/api/v1/exams/{$this->exam->id}/questions/bulk-remove", [
            'snapshot_ids' => [$foreignSnapshot->id],
        ]);

        $response->assertStatus(404);
        $this->assertDatabaseHas('exam_question_snapshots', [
            'id' => $foreignSnapshot->id,
            'exam_id' => $otherExam->id,
            'tenant_id' => $otherTenant->id,
        ]);
    }

    public function test_snapshot_mutation_rejection_preserves_answer_and_score_rows()
    {
        $snapshot = $this->createSnapshotForMutationTests();
        $attempt = $this->createAttemptForMutationTests();
        $answerId = (string) Str::uuid();
        $scoreId = (string) Str::uuid();
        $now = now();

        DB::table('exam_attempt_answers')->insert([
            'id' => $answerId,
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'exam_attempt_id' => $attempt->id,
            'exam_question_snapshot_id' => $snapshot->id,
            'student_id' => $this->student->id,
            'answer_payload' => json_encode(['text' => 'local answer']),
            'answer_state' => 'ANSWERED',
            'revision_number' => 1,
            'client_request_id' => 'snapshot-history-answer-1',
            'answered_at' => $now,
            'saved_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('exam_question_scores')->insert([
            'id' => $scoreId,
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'attempt_id' => $attempt->id,
            'snapshot_id' => $snapshot->id,
            'student_id' => $this->user->id,
            'question_type' => 'ESSAY',
            'awarded_score' => 4,
            'max_score' => 5,
            'scoring_status' => 'MANUAL_REVIEW',
            'scoring_rule_version' => 1,
            'evaluated_answer_snapshot' => json_encode(['text' => 'local answer']),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->actingAs($this->user, 'sanctum');

        $remove = $this->deleteJson("/api/v1/exams/{$this->exam->id}/questions/{$snapshot->id}");
        $remove->assertStatus(409);

        $bulkRemove = $this->postJson("/api/v1/exams/{$this->exam->id}/questions/bulk-remove", [
            'snapshot_ids' => [$snapshot->id],
        ]);
        $bulkRemove->assertStatus(409);

        $reorder = $this->postJson("/api/v1/exams/{$this->exam->id}/questions/reorder", [
            'orders' => [['snapshot_id' => $snapshot->id, 'sequence' => 99]],
        ]);
        $reorder->assertStatus(409);

        $this->assertDatabaseHas('exam_question_snapshots', ['id' => $snapshot->id]);
        $this->assertDatabaseHas('exam_attempt_answers', ['id' => $answerId]);
        $this->assertDatabaseHas('exam_question_scores', ['id' => $scoreId]);
    }

    private function createSnapshotForMutationTests()
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

    private function createAttemptForMutationTests(): ExamAttempt
    {
        $participant = ExamParticipant::create([
            'exam_id' => $this->exam->id,
            'user_id' => $this->student->id,
            'status' => 'STARTED',
        ]);
        $this->assertDatabaseHas('student_profiles', [
            'id' => $this->student->studentProfile->id,
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->student->id,
        ]);
        $this->assertDatabaseHas('rombel_student', [
            'tenant_id' => $this->tenant->id,
            'rombel_id' => $this->rombel->id,
            'student_id' => $this->student->studentProfile->id,
        ]);
        $this->assertContains($this->rombel->id, $this->student->studentProfile->rombels()->pluck('rombels.id')->all());
        $this->assertContains($this->rombel->id, \App\Support\Security\TeacherAccess::assignedRombelIdsForScope(
            $this->user,
            $this->exam->assessment->subject_id,
            $this->exam->assessment->academic_year_id,
            $this->exam->assessment->semester_id
        ));
        $this->assertTrue(\App\Support\Security\TeacherAccess::canManageExam($this->user, $this->exam), 'Teacher must retain exact assignment coverage for every participant.');

        return ExamAttempt::create([
            'exam_id' => $this->exam->id,
            'participant_id' => $participant->id,
            'attempt_number' => 1,
            'status' => 'IN_PROGRESS',
            'started_at' => now(),
        ]);
    }

}
