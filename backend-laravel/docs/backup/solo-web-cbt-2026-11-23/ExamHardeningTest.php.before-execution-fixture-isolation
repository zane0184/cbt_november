<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Assessment;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamParticipant;
use App\Models\AcademicYear;
use App\Models\Subject;
use App\Models\Question;
use App\Models\QuestionVersion;
use App\Models\QuestionOption;
use App\Models\ExamQuestionSnapshot;
use App\Services\ExamSnapshotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamHardeningTest extends TestCase
{
    use RefreshDatabase;

    private $tenant;
    private $admin;
    private $siswa;
    private $exam;
    private $snapshot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'Madrasah', 'code' => 'M']);

        $context = app(\App\Contexts\TenantContext::class);
        $context->setTenantId($this->tenant->id);

        $this->admin = User::create(['tenant_id' => $this->tenant->id, 'name' => 'Admin', 'email' => 'admin@m.com', 'password' => 'p']);
        $this->admin->roles()->attach(
            \App\Models\Role::firstOrCreate(['name' => 'ADMIN_MADRASAH'], ['label' => 'Admin'])
        );

        $this->siswa = User::create(['tenant_id' => $this->tenant->id, 'name' => 'Siswa', 'email' => 'siswa@m.com', 'password' => 'p']);
        $this->siswa->roles()->attach(
            \App\Models\Role::firstOrCreate(['name' => 'SISWA'], ['label' => 'Siswa'])
        );

        $ay = AcademicYear::create(['tenant_id' => $this->tenant->id, 'name' => '2024']);
        $subject = Subject::create(['tenant_id' => $this->tenant->id, 'code' => 'MAT', 'name' => 'Matematika', 'short_name' => 'MTK', 'status' => 'ACTIVE']);
        $assessment = Assessment::create(['tenant_id' => $this->tenant->id, 'subject_id' => $subject->id, 'academic_year_id' => $ay->id, 'title' => 'Daily', 'assessment_type' => 'DAILY', 'created_by' => $this->admin->id]);

        $this->exam = Exam::create([
            'tenant_id' => $this->tenant->id,
            'assessment_id' => $assessment->id,
            'title' => 'Math',
            'duration_seconds' => 3600,
            'created_by' => $this->admin->id,
            'status' => 'DRAFT',
            'start_at' => now()->subMinutes(10),
            'end_at' => now()->addMinutes(50),
            'require_token' => true,
        ]);

        $question = Question::create([
            'tenant_id' => $this->tenant->id,
            'subject_id' => $subject->id,
            'owner_type' => 'TENANT',
            'owner_user_id' => $this->admin->id,
            'question_code' => 'HARD-01',
            'created_by' => $this->admin->id,
        ]);
        $version = QuestionVersion::create([
            'tenant_id' => $this->tenant->id,
            'question_id' => $question->id,
            'major_version' => 1,
            'minor_version' => 0,
            'question_type' => 'MULTIPLE_CHOICE',
            'stem' => 'Soal uji submit',
            'status' => 'PUBLISHED',
            'is_current' => true,
            'created_by' => $this->admin->id,
        ]);
        QuestionOption::create([
            'tenant_id' => $this->tenant->id,
            'question_version_id' => $version->id,
            'option_key' => 'A',
            'content' => 'Benar',
            'is_correct' => true,
            'sequence' => 1,
        ]);
        QuestionOption::create([
            'tenant_id' => $this->tenant->id,
            'question_version_id' => $version->id,
            'option_key' => 'B',
            'content' => 'Salah',
            'is_correct' => false,
            'sequence' => 2,
        ]);
        app(ExamSnapshotService::class)->createSnapshots($this->exam, [$version->id]);
        $this->snapshot = $this->exam->snapshots()->firstOrFail();
        $this->exam->update([
            'status' => 'PUBLISHED',
            'locked_at' => now(),
            'published_at' => now(),
            'token' => 'ABC123',
            'require_token' => true,
        ]);

        $participant = new ExamParticipant(['user_id' => $this->siswa->id]);
        $participant->exam_id = $this->exam->id;
        $participant->save();

        $context->setTenantId(null);
    }

    public function test_siswa_cannot_update_exam()
    {
        $this->actingAs($this->siswa, 'sanctum');
        $response = $this->putJson("/api/v1/exams/{$this->exam->id}", ['title' => 'Hacked']);
        $response->assertStatus(403);
    }

    public function test_admin_cannot_update_locked_exam()
    {
        $this->actingAs($this->admin, 'sanctum');
        $response = $this->putJson("/api/v1/exams/{$this->exam->id}", ['title' => 'Changed']);
        $response->assertStatus(403); // Per policy update returns false if locked
    }

    public function test_duplicate_submission_prevention()
    {
        $context = app(\App\Contexts\TenantContext::class);
        $context->setTenantId($this->tenant->id);

        $participant = ExamParticipant::where('exam_id', $this->exam->id)->where('user_id', $this->siswa->id)->first();
        $attempt = new ExamAttempt([
            'exam_id' => $this->exam->id,
            'participant_id' => $participant->id,
            'attempt_number' => 1,
            'status' => 'IN_PROGRESS',
            'started_at' => now(),
            'expires_at' => now()->addMinutes(45),
            'device_fingerprint' => 'device-1',
            'question_order' => [$this->snapshot->id],
        ]);
        $attempt->save();

        $context->setTenantId(null);
        $this->actingAs($this->siswa);
        $this->withHeader('X-Device-Binding-ID', 'device-1');

        // First submission
        $response = $this->postJson("/api/v1/exams/{$this->exam->id}/attempts/{$attempt->id}/submit");
        $response->assertStatus(200);

        // Second submission
        $response = $this->postJson("/api/v1/exams/{$this->exam->id}/attempts/{$attempt->id}/submit");
        $response->assertStatus(422);
        $response->assertJson(['message' => 'Attempt is not in progress.']);
    }

    public function test_cross_tenant_attempt_submission_denied()
    {
        $tenantB = Tenant::create(['name' => 'B', 'code' => 'B']);

        $context = app(\App\Contexts\TenantContext::class);
        $context->setTenantId($tenantB->id);

        $userB = User::create(['tenant_id' => $tenantB->id, 'name' => 'B', 'email' => 'b@b.com', 'password' => 'p']);
        $userB->roles()->attach(
            \App\Models\Role::firstOrCreate(['name' => 'SISWA'], ['label' => 'Siswa'])
        );

        $context->setTenantId($this->tenant->id);
        $participant = ExamParticipant::where('exam_id', $this->exam->id)->where('user_id', $this->siswa->id)->first();
        $attempt = new ExamAttempt([
            'exam_id' => $this->exam->id,
            'participant_id' => $participant->id,
            'attempt_number' => 1,
            'status' => 'IN_PROGRESS',
            'started_at' => now()
        ]);
        $attempt->save();

        $context->setTenantId(null);
        $this->actingAs($userB, 'sanctum');
        $this->withHeader('X-Device-Binding-ID', 'device-2');
        $response = $this->postJson("/api/v1/exams/{$this->exam->id}/attempts/{$attempt->id}/submit");

        // Depending on how global scopes work, it might be 404 or 403.
        // IdentifyTenant middleware will set context to Tenant B.
        // Attempt search in controller will fail or find nothing.
        $response->assertStatus(404);
    }
}
