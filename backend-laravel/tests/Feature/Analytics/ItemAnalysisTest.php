<?php

namespace Tests\Feature\Analytics;

use Tests\TestCase;
use App\Models\User;
use App\Models\Exam;
use App\Models\Tenant;
use App\Models\Question;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptScore;
use App\Models\ExamQuestionSnapshot;
use App\Models\ExamQuestionScore;
use App\Models\ExamParticipant;
use App\Services\Analytics\ItemAnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ItemAnalysisTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function discrimination_index_requires_minimum_20_finalized_participants()
    {
        $tenant = Tenant::factory()->create();
        app(\App\Contexts\TenantContext::class)->setTenantId($tenant->id);
        $teacher = User::factory()->create(['tenant_id' => $tenant->id]);
        $exam = Exam::factory()->create(['tenant_id' => $tenant->id]);

        $question = Question::create([
            'tenant_id' => $tenant->id,
            'owner_type' => 'MADRASAH',
            'created_by' => $teacher->id,
        ]);
        $questionVersion = $question->versions()->create([
            'tenant_id' => $tenant->id,
            'created_by' => $teacher->id,
            'question_type' => 'MULTIPLE_CHOICE',
            'stem' => 'Question 1',
            'status' => 'PUBLISHED',
            'is_current' => true,
            'major_version' => 1,
            'minor_version' => 0
        ]);

        $snapshot = ExamQuestionSnapshot::create([
            'exam_id' => $exam->id,
            'source_question_id' => $question->id,
            'source_question_version_id' => $questionVersion->id,
            'tenant_id' => $tenant->id,
            'sequence' => 1,
            'points' => 10,
            'snapshot_stem' => 'Question 1',
            'snapshot_explanation' => '',
            'snapshot_options' => [],
            'snapshot_media' => []
        ]);

        // Create only 19 participants
        for ($i = 0; $i < 19; $i++) {
            $student = User::factory()->create(['tenant_id' => $tenant->id]);
            $participant = ExamParticipant::factory()->create(['exam_id' => $exam->id, 'user_id' => $student->id, 'tenant_id' => $tenant->id]);
            $attempt = ExamAttempt::factory()->create(['exam_id' => $exam->id, 'participant_id' => $participant->id, 'tenant_id' => $tenant->id, 'status' => 'SUBMITTED']);
            ExamAttemptScore::factory()->create(['attempt_id' => $attempt->id, 'exam_id' => $exam->id, 'student_id' => $student->id, 'grading_status' => 'FINALIZED', 'tenant_id' => $tenant->id]);
        }

        $service = new ItemAnalysisService();
        $analysis = $service->getAnalysis($exam);

        $this->assertEquals('insufficient_sample', $analysis[0]['discrimination_status']);
        $this->assertNull($analysis[0]['discrimination_index']);
    }

    /** @test */
    public function discrimination_index_uses_top_and_bottom_27_percent_with_deterministic_tie_break()
    {
        $tenant = Tenant::factory()->create();
        app(\App\Contexts\TenantContext::class)->setTenantId($tenant->id);
        $teacher = User::factory()->create(['tenant_id' => $tenant->id]);
        $exam = Exam::factory()->create(['tenant_id' => $tenant->id]);

        $question = Question::create([
            'tenant_id' => $tenant->id,
            'owner_type' => 'MADRASAH',
            'created_by' => $teacher->id,
        ]);
        $questionVersion = $question->versions()->create([
            'tenant_id' => $tenant->id,
            'created_by' => $teacher->id,
            'question_type' => 'MULTIPLE_CHOICE',
            'stem' => 'Question 1',
            'status' => 'PUBLISHED',
            'is_current' => true,
            'major_version' => 1,
            'minor_version' => 0
        ]);

        $snapshot = ExamQuestionSnapshot::create([
            'exam_id' => $exam->id,
            'source_question_id' => $question->id,
            'source_question_version_id' => $questionVersion->id,
            'tenant_id' => $tenant->id,
            'sequence' => 1,
            'points' => 10,
            'snapshot_stem' => 'Question 1',
            'snapshot_explanation' => '',
            'snapshot_options' => [],
            'snapshot_media' => []
        ]);

        // Create 100 participants to make 27% exactly 27 students
        for ($i = 0; $i < 100; $i++) {
            $student = User::factory()->create(['tenant_id' => $tenant->id]);
            $participant = ExamParticipant::factory()->create(['exam_id' => $exam->id, 'user_id' => $student->id, 'tenant_id' => $tenant->id]);
            $attempt = ExamAttempt::factory()->create(['exam_id' => $exam->id, 'participant_id' => $participant->id, 'tenant_id' => $tenant->id, 'status' => 'SUBMITTED']);

            // Percentage: 100 for first 27, 0 for last 27, 50 for middle
            $percentage = ($i < 27) ? 100 : (($i >= 73) ? 0 : 50);

            ExamAttemptScore::factory()->create([
                'attempt_id' => $attempt->id,
                'exam_id' => $exam->id,
                'student_id' => $student->id,
                'percentage' => $percentage,
                'grading_status' => 'FINALIZED',
                'tenant_id' => $tenant->id
            ]);

            // Item scores: Top group gets 10, Bottom group gets 0
            $awarded = ($i < 27) ? 10 : (($i >= 73) ? 0 : 5);
            ExamQuestionScore::create([
                'attempt_id' => $attempt->id,
                'snapshot_id' => $snapshot->id,
                'exam_id' => $exam->id,
                'student_id' => $student->id,
                'question_type' => 'MULTIPLE_CHOICE',
                'awarded_score' => $awarded,
                'max_score' => 10,
                'tenant_id' => $tenant->id
            ]);
        }

        $service = new ItemAnalysisService();
        $analysis = $service->getAnalysis($exam);

        // P_top = 10/10 = 1.0, P_bottom = 0/10 = 0.0 -> D = 1.0
        $this->assertEquals(1.0, $analysis[0]['discrimination_index']);
        $this->assertEquals('success', $analysis[0]['discrimination_status']);
    }
}
