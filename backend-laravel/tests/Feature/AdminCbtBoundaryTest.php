<?php

namespace Tests\Feature;

use App\Livewire\Admin\ExamManagement;
use App\Livewire\Admin\QuestionBank;
use App\Models\Assessment;
use App\Models\AcademicYear;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamQuestionScore;
use App\Models\ExamQuestionSnapshot;
use App\Models\ExamParticipant;
use App\Models\Permission;
use App\Models\Question;
use App\Models\QuestionVersion;
use App\Models\Role;
use App\Models\Subject;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class AdminCbtBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_with_stale_guru_role_and_permissions_is_denied_all_cbt_surfaces(): void
    {
        $tenant = Tenant::create(['name' => 'Madrasah Boundary', 'code' => 'BOUNDARY']);
        app(\App\Contexts\TenantContext::class)->setTenantId($tenant->id);
        $adminRole = Role::create(['name' => 'ADMIN_MADRASAH', 'label' => 'Admin Madrasah']);
        $guruRole = Role::create(['name' => 'GURU', 'label' => 'Guru']);
        $studentRole = Role::create(['name' => 'SISWA', 'label' => 'Siswa']);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin Multi Role',
            'email' => 'admin.multi@boundary.test',
            'password' => bcrypt('password'),
        ]);
        $admin->roles()->attach([$adminRole->id, $guruRole->id, $studentRole->id]);

        // Simulate legacy/stale grants only in Eloquent's in-memory relations.
        // This deliberately performs no INSERT/UPDATE/DELETE on permissions or permission_role.
        $permissionRowsBefore = Permission::query()->count();
        $permissionPivotRowsBefore = DB::table('permission_role')->count();

        $stalePermissions = [
            'question.view', 'question.manage',
            'exam.manage', 'exam.assign', 'exam.monitor',
            'exam.close', 'exam.token', 'exam.grade', 'exam.reopen',
            'report.view',
        ];
        $stalePermissionModels = collect($stalePermissions)->map(
            fn (string $name): Permission => new Permission(['name' => $name, 'label' => $name])
        );
        $adminRole->setRelation('permissions', $stalePermissionModels);
        $guruRole->setRelation('permissions', $stalePermissionModels);
        $admin->setRelation('roles', collect([$adminRole, $guruRole, $studentRole]));

        $question = new Question(['tenant_id' => $tenant->id, 'owner_type' => 'MADRASAH']);
        $assessment = new Assessment(['tenant_id' => $tenant->id, 'created_by' => $admin->id]);
        $exam = new Exam(['tenant_id' => $tenant->id, 'assessment_id' => '00000000-0000-4000-8000-000000000001']);
        $attempt = new ExamAttempt(['tenant_id' => $tenant->id, 'exam_id' => $exam->id]);
        $subject = new Subject(['tenant_id' => $tenant->id]);

        $gate = Gate::forUser($admin);

        $this->assertFalse($gate->allows('viewAny', Question::class));
        $this->assertFalse($gate->allows('view', $question));
        $this->assertFalse($gate->allows('create', Question::class));

        $this->assertFalse($gate->allows('viewAny', Assessment::class));
        $this->assertFalse($gate->allows('view', $assessment));
        $this->assertFalse($gate->allows('create', Assessment::class));

        $this->assertFalse($gate->allows('viewAny', Exam::class));
        $this->assertFalse($gate->allows('view', $exam));
        $this->assertFalse($gate->allows('create', Exam::class));
        $this->assertFalse($gate->allows('monitor', $exam));
        $this->assertFalse($gate->allows('manageParticipants', $exam));
        $this->assertFalse($gate->allows('generateToken', $exam));

        $this->assertFalse($gate->allows('view', $attempt));
        $this->assertFalse($gate->allows('viewScore', $attempt));
        $this->assertFalse($gate->allows('score', $attempt));
        $this->assertFalse($gate->allows('finalize', $attempt));
        $this->assertFalse($gate->allows('reopen', $attempt));

        $this->assertFalse($gate->allows('viewExamResults', $exam));
        $this->assertFalse($gate->allows('viewStudentResults', $admin));
        $this->assertFalse($gate->allows('viewSubjectResults', $subject));

        app(\App\Contexts\TenantContext::class)->setTenantId($tenant->id);
        $this->actingAs($admin);
        $this->get('/')->assertRedirect('/admin/dashboard');
        Livewire::test(QuestionBank::class)->assertForbidden();
        Livewire::test(ExamManagement::class)->assertForbidden();
        $this->get('/student/exams')->assertForbidden();

        $this->assertSame($permissionRowsBefore, Permission::query()->count());
        $this->assertSame($permissionPivotRowsBefore, DB::table('permission_role')->count());
    }

    public function test_admin_bootstrap_grants_only_administrative_and_temporary_proctor_assignment_permissions(): void
    {
        $adminRole = Role::create(['name' => 'ADMIN_MADRASAH', 'label' => 'Admin Madrasah']);
        $guruRole = Role::create(['name' => 'GURU', 'label' => 'Guru']);
        Role::create(['name' => 'KAMAD', 'label' => 'Kepala Madrasah']);

        app(\App\Services\RolePermissionBootstrapper::class)->bootstrap();

        $grants = $adminRole->fresh()->permissions()->pluck('name')->all();

        $this->assertEqualsCanonicalizing([
            'curriculum.view',
            'curriculum.manage',
            'academic.manage',
            'user.manage',
            'exam.proctor.assign',
        ], $grants);

        $this->assertFalse($adminRole->fresh()->permissions()->whereIn('name', [
            'question.view', 'question.manage',
            'exam.manage', 'exam.assign', 'exam.monitor',
            'exam.close', 'exam.token', 'exam.grade', 'exam.reopen',
            'report.view',
        ])->exists());

        $this->assertTrue($guruRole->fresh()->permissions()->where('name', 'exam.manage')->exists());
    }
    public function test_admin_with_conflicting_roles_is_denied_direct_http_cbt_endpoints_without_database_mutation(): void
    {
        $tenant = Tenant::create(['name' => 'Madrasah HTTP Boundary', 'code' => 'HTTPBOUNDARY']);
        app(\App\Contexts\TenantContext::class)->setTenantId($tenant->id);

        $adminRole = Role::create(['name' => 'ADMIN_MADRASAH', 'label' => 'Admin Madrasah']);
        $guruRole = Role::create(['name' => 'GURU', 'label' => 'Guru']);
        $studentRole = Role::create(['name' => 'SISWA', 'label' => 'Siswa']);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'HTTP Boundary Admin',
            'email' => 'http-boundary-admin@example.test',
            'password' => bcrypt('password'),
        ]);
        $admin->roles()->attach([$adminRole->id, $guruRole->id, $studentRole->id]);

        // Simulate stale GURU permission objects in memory; no permission table writes.
        $stalePermissions = collect([
            'question.view', 'question.manage', 'exam.manage', 'exam.assign',
            'exam.monitor', 'exam.close', 'exam.token', 'exam.grade',
            'exam.reopen', 'report.view',
        ])->map(fn (string $name) => new Permission(['name' => $name, 'label' => $name]));
        $guruRole->setRelation('permissions', $stalePermissions);
        $adminRole->setRelation('permissions', $stalePermissions);
        $admin->setRelation('roles', collect([$adminRole, $guruRole, $studentRole]));

        $year = AcademicYear::create([
            'tenant_id' => $tenant->id,
            'name' => '2026/2027',
            'is_active' => true,
        ]);
        $assessment = Assessment::create([
            'tenant_id' => $tenant->id,
            'academic_year_id' => $year->id,
            'title' => 'Boundary Assessment',
            'assessment_type' => 'SUMMATIVE',
            'created_by' => $admin->id,
        ]);
        $exam = Exam::create([
            'tenant_id' => $tenant->id,
            'assessment_id' => $assessment->id,
            'title' => 'Boundary Exam',
            'duration_seconds' => 3600,
            'start_at' => now()->subMinute(),
            'end_at' => now()->addHour(),
            'timezone' => 'Asia/Jakarta',
            'status' => 'PUBLISHED',
            'attempt_limit' => 2,
            'require_token' => true,
            'token' => 'ABC123',
            'created_by' => $admin->id,
            'locked_at' => now(),
        ]);
        $participant = ExamParticipant::create([
            'tenant_id' => $tenant->id,
            'exam_id' => $exam->id,
            'user_id' => $admin->id,
            'status' => 'STARTED',
        ]);
        $question = Question::create([
            'tenant_id' => $tenant->id,
            'owner_type' => 'TENANT',
            'owner_user_id' => $admin->id,
            'question_code' => 'BOUNDARY-Q1',
            'created_by' => $admin->id,
        ]);
        $version = QuestionVersion::create([
            'tenant_id' => $tenant->id,
            'question_id' => $question->id,
            'major_version' => 1,
            'minor_version' => 0,
            'question_type' => 'ESSAY',
            'stem' => 'Boundary question',
            'is_current' => true,
            'created_by' => $admin->id,
        ]);
        $snapshot = ExamQuestionSnapshot::create([
            'tenant_id' => $tenant->id,
            'exam_id' => $exam->id,
            'source_question_id' => $question->id,
            'source_question_version_id' => $version->id,
            'sequence' => 1,
            'points' => 10,
            'snapshot_stem' => 'Boundary question',
            'snapshot_options' => [],
            'metadata' => ['question_type' => 'ESSAY'],
        ]);
        $attempt = ExamAttempt::create([
            'tenant_id' => $tenant->id,
            'exam_id' => $exam->id,
            'participant_id' => $participant->id,
            'attempt_number' => 1,
            'status' => 'IN_PROGRESS',
            'device_fingerprint' => 'boundary-device',
            'started_at' => now(),
            'expires_at' => now()->addHour(),
            'question_order' => [(string) $snapshot->id],
        ]);
        $questionScore = ExamQuestionScore::create([
            'tenant_id' => $tenant->id,
            'exam_id' => $exam->id,
            'attempt_id' => $attempt->id,
            'snapshot_id' => $snapshot->id,
            'student_id' => $admin->id,
            'question_type' => 'ESSAY',
            'awarded_score' => 0,
            'max_score' => 10,
            'scoring_status' => 'MANUAL_REVIEW',
            'scoring_rule_version' => 'v1',
            'scoring_version' => 1,
            'evaluated_answer_snapshot' => [],
            'evaluated_at' => now(),
        ]);

        $tables = [
            'questions', 'question_versions', 'question_options', 'question_publications',
            'assessments', 'exams', 'exam_participants', 'exam_attempts',
            'exam_attempt_answers', 'exam_attempt_scores', 'exam_attempt_score_current',
            'exam_question_scores', 'tags', 'audit_logs', 'teacher_subject_assignments',
        ];
        $before = [];
        foreach ($tables as $table) {
            if (\Illuminate\Support\Facades\Schema::hasTable($table)) {
                $before[$table] = DB::table($table)->count();
            }
        }

        $this->actingAs($admin, 'sanctum');

        $requests = [
            ['GET', '/api/v1/questions', []],
            ['POST', '/api/v1/questions', []],
            ['POST', '/api/v1/questions/import', []],
            ['GET', '/api/v1/assessments', []],
            ['POST', '/api/v1/assessments', []],
            ['GET', '/api/v1/exams', []],
            ['POST', '/api/v1/exams', []],
            ['GET', "/api/v1/exams/{$exam->id}", []],
            ['GET', "/api/v1/exams/{$exam->id}/preview", []],
            ['POST', "/api/v1/exams/{$exam->id}/questions", []],
            ['POST', "/api/v1/exams/{$exam->id}/token", []],
            ['POST', "/api/v1/exams/{$exam->id}/lock", []],
            ['POST', "/api/v1/exams/{$exam->id}/close", []],
            ['POST', "/api/v1/exams/{$exam->id}/publish-results", []],
            ['POST', "/api/v1/exams/{$exam->id}/assign-rombel", []],
            ['POST', "/api/v1/exams/{$exam->id}/participants", []],
            ['POST', "/api/v1/exams/{$exam->id}/attempts", []],
            ['GET', "/api/v1/exams/{$exam->id}/execution", []],
            ['GET', "/api/v1/exams/{$exam->id}/attempts/{$attempt->id}/answers", []],
            ['PUT', "/api/v1/exams/{$exam->id}/attempts/{$attempt->id}/answers/{$snapshot->id}", [
                'client_request_id' => 'admin-denied-answer',
                'answer_state' => 'ANSWERED',
                'answer_payload' => ['text' => 'not authorized'],
            ]],
            ['POST', "/api/v1/exams/{$exam->id}/attempts/{$attempt->id}/submit", []],
            ['POST', "/api/v1/exams/{$exam->id}/attempts/{$attempt->id}/score", []],
            ['POST', "/api/v1/exams/{$exam->id}/attempts/{$attempt->id}/recalculate", []],
            ['GET', "/api/v1/exams/{$exam->id}/attempts/{$attempt->id}/score", []],
            ['PUT', "/api/v1/exams/{$exam->id}/attempts/{$attempt->id}/grading/{$questionScore->id}", ['awarded_score' => 10]],
            ['POST', "/api/v1/exams/{$exam->id}/attempts/{$attempt->id}/finalize", []],
            ['POST', "/api/v1/exams/{$exam->id}/attempts/{$attempt->id}/reopen", ['reason' => 'not authorized']],
            ['GET', "/api/v1/exams/{$exam->id}/monitoring", []],
            ['GET', "/api/v1/exams/{$exam->id}/monitoring/participants", []],
            ['GET', "/api/v1/exams/{$exam->id}/monitoring/attempts/{$attempt->id}", []],
            ['GET', "/api/v1/exams/{$exam->id}/monitoring/events", []],
            ['GET', "/api/v1/reports/exams/{$exam->id}/results", []],
            ['GET', "/api/v1/reports/exams/{$exam->id}/export/pdf", []],
            ['GET', "/api/v1/reports/exams/{$exam->id}/summary/export/pdf", []],
            ['GET', "/api/v1/analytics/exams/{$exam->id}", []],
            ['GET', "/api/v1/reports/students/{$admin->id}/results", []],
            ['GET', "/api/v1/analytics/students/{$admin->id}", []],
            ['GET', '/api/v1/question-publications', []],
            ['GET', '/api/v1/tags', []],
            ['POST', '/api/v1/tags', ['name' => 'forbidden']],
            ['GET', '/api/v1/media', []],
            ['GET', '/api/v1/teacher-subject-assignments', []],
            ['POST', '/api/v1/teacher-subject-assignments', []],
        ];

        foreach ($requests as [$method, $uri, $payload]) {
            $response = $this->json($method, $uri, $payload);
            $response->assertForbidden("Expected 403 for {$method} {$uri}; got {$response->status()}");
        }

        $after = [];
        foreach (array_keys($before) as $table) {
            $after[$table] = DB::table($table)->count();
        }

        $this->assertSame($before, $after, 'Denied Admin CBT requests must not mutate database rows.');
    }

}
