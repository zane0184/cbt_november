<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Assessment;
use App\Models\Exam;
use App\Models\Question;
use App\Models\QuestionVersion;
use App\Models\AcademicYear;
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

        $ay = AcademicYear::create(['name' => '2024']);
        $assessment = Assessment::create([
            'academic_year_id' => $ay->id,
            'title' => 'Daily',
            'assessment_type' => 'DAILY',
            'created_by' => $this->user->id
        ]);

        $this->exam = Exam::create([
            'assessment_id' => $assessment->id,
            'title' => 'Math Test',
            'duration_seconds' => 3600,
            'created_by' => $this->user->id,
            'status' => 'DRAFT'
        ]);
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
}
