<?php

namespace Tests\Feature;

use App\Contexts\TenantContext;
use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamParticipant;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class StudentExamPortalTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $student;
    private Exam $exam;
    private ExamParticipant $participant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create(['name' => 'Tenant Student Portal', 'code' => 'TSP1']);
        app(TenantContext::class)->setTenantId($this->tenant->id);

        $this->student = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Student Portal',
            'email' => 'student-portal@example.test',
            'password' => 'password',
        ]);
        $this->student->roles()->attach(
            Role::firstOrCreate(['name' => 'SISWA'], ['label' => 'Siswa'])
        );

        $year = AcademicYear::create(['tenant_id' => $this->tenant->id, 'name' => '2026/2027']);
        $assessment = Assessment::create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $year->id,
            'title' => 'Asesmen Portal',
            'assessment_type' => 'DAILY',
            'created_by' => $this->student->id,
        ]);

        $this->exam = Exam::create([
            'tenant_id' => $this->tenant->id,
            'assessment_id' => $assessment->id,
            'title' => 'Ujian Portal',
            'instructions' => 'Baca petunjuk sebelum memulai.',
            'duration_seconds' => 3600,
            'attempt_limit' => 2,
            'created_by' => $this->student->id,
            'status' => 'PUBLISHED',
            'start_at' => now()->subMinute(),
            'end_at' => now()->addHour(),
            'locked_at' => now()->subMinute(),
            'show_result' => true,
            'require_token' => true,
            'token' => 'ABC123',
        ]);

        $this->participant = ExamParticipant::create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'user_id' => $this->student->id,
            'status' => 'ASSIGNED',
        ]);
    }

    public function test_student_exam_list_contains_only_assigned_exams(): void
    {
        $unassigned = Exam::create([
            'tenant_id' => $this->tenant->id,
            'assessment_id' => $this->exam->assessment_id,
            'title' => 'Ujian Tidak Ditugaskan',
            'duration_seconds' => 1800,
            'created_by' => $this->student->id,
            'status' => 'PUBLISHED',
            'start_at' => now()->subMinute(),
            'end_at' => now()->addHour(),
            'locked_at' => now()->subMinute(),
        ]);

        $this->actingAs($this->student)
            ->get(route('student.exams.index'))
            ->assertOk()
            ->assertSee('Ujian Portal')
            ->assertDontSee($unassigned->title);
    }

    public function test_student_can_open_assigned_exam_and_sees_token_requirement(): void
    {
        $this->actingAs($this->student)
            ->get(route('student.exams.show', $this->exam->id))
            ->assertOk()
            ->assertSee('Ujian Portal')
            ->assertSee('Diperlukan')
            ->assertSee('Mulai Ujian')
            ->assertSee('Baca petunjuk sebelum memulai.');
    }

    public function test_student_exam_without_required_token_does_not_render_token_input(): void
    {
        $this->exam->update(['require_token' => false, 'token' => null]);

        $this->actingAs($this->student)
            ->get(route('student.exams.show', $this->exam->id))
            ->assertOk()
            ->assertSee('Tidak diperlukan')
            ->assertDontSee('id="exam-token"', false)
            ->assertSee('Mulai Ujian');
    }

    public function test_student_can_resume_own_active_attempt(): void
    {
        ExamAttempt::create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'participant_id' => $this->participant->id,
            'attempt_number' => 1,
            'status' => 'IN_PROGRESS',
            'started_at' => now()->subMinutes(5),
            'expires_at' => now()->addMinutes(55),
            'question_order' => [],
            'options_order' => [],
        ]);

        $this->actingAs($this->student)
            ->get(route('student.exams.index'))
            ->assertOk()
            ->assertSee('Sedang dikerjakan')
            ->assertSee('Lanjutkan Ujian');

        $this->actingAs($this->student)
            ->get(route('student.exams.show', $this->exam->id))
            ->assertOk()
            ->assertSee('Lanjutkan Ujian')
            ->assertSee('Attempt aktif ditemukan.');
    }

    public function test_student_cannot_open_unassigned_or_cross_tenant_exam(): void
    {
        $unassigned = Exam::create([
            'tenant_id' => $this->tenant->id,
            'assessment_id' => $this->exam->assessment_id,
            'title' => 'Ujian Privat',
            'duration_seconds' => 1800,
            'created_by' => $this->student->id,
            'status' => 'PUBLISHED',
            'start_at' => now()->subMinute(),
            'end_at' => now()->addHour(),
            'locked_at' => now()->subMinute(),
        ]);

        $otherTenant = Tenant::create(['name' => 'Tenant Other', 'code' => 'TSP2']);
        app(TenantContext::class)->setTenantId($otherTenant->id);
        $otherYear = AcademicYear::create(['tenant_id' => $otherTenant->id, 'name' => '2026/2027']);
        $otherAssessment = Assessment::create([
            'tenant_id' => $otherTenant->id,
            'academic_year_id' => $otherYear->id,
            'title' => 'Asesmen Tenant Other',
            'assessment_type' => 'DAILY',
            'created_by' => $this->student->id,
        ]);
        $crossTenantExam = Exam::create([
            'tenant_id' => $otherTenant->id,
            'assessment_id' => $otherAssessment->id,
            'title' => 'Ujian Tenant Other',
            'duration_seconds' => 1800,
            'created_by' => $this->student->id,
            'status' => 'PUBLISHED',
            'start_at' => now()->subMinute(),
            'end_at' => now()->addHour(),
            'locked_at' => now()->subMinute(),
        ]);
        app(TenantContext::class)->setTenantId($this->tenant->id);

        $this->actingAs($this->student)
            ->get(route('student.exams.show', $unassigned->id))
            ->assertForbidden();

        $this->actingAs($this->student)
            ->get(route('student.exams.show', $crossTenantExam->id))
            ->assertNotFound();
    }

    public function test_student_outside_eligibility_window_cannot_start_exam(): void
    {
        $this->participant->update(['eligible_from' => now()->addHour()]);

        $this->actingAs($this->student)
            ->get(route('student.exams.index'))
            ->assertOk()
            ->assertSee('Di luar waktu akses')
            ->assertSee('Belum dapat dikerjakan');

        $this->actingAs($this->student)
            ->get(route('student.exams.show', $this->exam->id))
            ->assertOk()
            ->assertSee('Waktu akses peserta belum dimulai atau sudah berakhir.')
            ->assertSee('Mulai Ujian');
    }

    public function test_student_exam_schedule_start_and_end_boundaries_are_enforced(): void
    {
        $startAt = now()->addMinutes(10)->startOfSecond();
        $endAt = $startAt->copy()->addHour();
        $this->exam->update(['start_at' => $startAt, 'end_at' => $endAt]);

        try {
            Carbon::setTestNow($startAt->copy()->subSecond());
            $this->actingAs($this->student)
                ->get(route('student.exams.index'))
                ->assertOk()
                ->assertSee('Terjadwal')
                ->assertSee('Belum dapat dikerjakan');

            Carbon::setTestNow($startAt);
            $this->actingAs($this->student)
                ->get(route('student.exams.index'))
                ->assertOk()
                ->assertSee('Siap dikerjakan')
                ->assertSee('Buka Ujian');

            Carbon::setTestNow($endAt->copy()->addSecond());
            $this->actingAs($this->student)
                ->get(route('student.exams.index'))
                ->assertOk()
                ->assertSee('Ditutup')
                ->assertSee('Belum dapat dikerjakan');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_student_cannot_open_exam_after_attempt_limit_is_exhausted(): void
    {
        foreach ([1, 2] as $attemptNumber) {
            ExamAttempt::create([
                'tenant_id' => $this->tenant->id,
                'exam_id' => $this->exam->id,
                'participant_id' => $this->participant->id,
                'attempt_number' => $attemptNumber,
                'status' => 'SUBMITTED',
                'started_at' => now()->subMinutes(20 - $attemptNumber),
                'expires_at' => now()->subMinutes(10 - $attemptNumber),
                'submitted_at' => now()->subMinutes(10 - $attemptNumber),
                'question_order' => [],
                'options_order' => [],
            ]);
        }

        $this->actingAs($this->student)
            ->get(route('student.exams.index'))
            ->assertOk()
            ->assertSee('2 / 2')
            ->assertSee('Selesai')
            ->assertSee('Belum dapat dikerjakan')
            ->assertDontSee('Buka Ujian');

        $this->actingAs($this->student)
            ->get(route('student.exams.show', $this->exam->id))
            ->assertOk()
            ->assertSee('Ujian belum dapat dimulai')
            ->assertSee('disabled', false);
    }

    public function test_user_with_admin_madrasah_role_cannot_enter_student_portal(): void
    {
        $this->student->roles()->attach(
            Role::firstOrCreate(['name' => 'ADMIN_MADRASAH'], ['label' => 'Admin Madrasah'])
        );

        $this->actingAs($this->student)
            ->get(route('student.exams.index'))
            ->assertForbidden();
    }
}
