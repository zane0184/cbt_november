<?php

namespace Tests\Feature\Analytics;

use App\Contexts\TenantContext;
use App\Models\Assessment;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptScore;
use App\Models\ExamAttemptScoreCurrent;
use App\Models\ExamParticipant;
use App\Models\ExamQuestionScore;
use App\Models\ExamQuestionSnapshot;
use App\Models\Question;
use App\Models\Subject;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Analytics\ExamAnalyticsService;
use App\Services\Analytics\ItemAnalysisService;
use App\Services\Analytics\StudentAnalyticsService;
use App\Services\Analytics\SubjectAnalyticsService;
use App\Services\Reporting\ExamSummaryReportService;
use App\Services\Reporting\QuestionReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrentScoreAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        app(TenantContext::class)->setTenantId($this->tenant->id);
    }

    public function test_only_the_current_pointer_score_is_included_in_exam_and_student_analytics(): void
    {
        $exam = Exam::factory()->create([
            'tenant_id' => $this->tenant->id,
            'show_result' => true,
        ]);
        [$student, $attempt, $historicalScore, $currentScore] = $this->createScoredAttempt(
            $exam,
            35.0,
            95.0
        );

        $currentRows = ExamAttemptScore::query()
            ->where('attempt_id', $attempt->id)
            ->current()
            ->get();

        $this->assertCount(1, $currentRows);
        $this->assertSame($currentScore->id, $currentRows->first()->id);
        $this->assertNotSame($historicalScore->id, $currentRows->first()->id);

        $summary = app(ExamAnalyticsService::class)->getSummary($exam);
        $this->assertSame(1, $summary['participant_count']);
        $this->assertSame(95.0, $summary['average_percentage']);

        $distribution = app(ExamAnalyticsService::class)->getDistribution($exam);
        $this->assertSame(1, array_sum(array_column($distribution, 'count')));
        $this->assertSame(1, $distribution[9]['count']);

        $report = app(ExamSummaryReportService::class)->getSummary($exam);
        $this->assertSame(1, (int) $report['total_scored']);
        $this->assertSame(1, $report['finalized_count']);
        $this->assertSame(95.0, $report['average_percentage']);

        $studentAnalytics = app(StudentAnalyticsService::class)->getStudentSummary($student);
        $this->assertSame(1, $studentAnalytics['total_exams']);
        $this->assertSame(95.0, $studentAnalytics['average_percentage']);
    }

    public function test_item_analysis_and_question_report_use_only_active_attempt_scoring_version(): void
    {
        $exam = Exam::factory()->create(['tenant_id' => $this->tenant->id]);
        $teacher = User::factory()->create(['tenant_id' => $this->tenant->id]);
        [, $attempt] = $this->createScoredAttempt($exam, 10.0, 90.0);

        $question = Question::create([
            'tenant_id' => $this->tenant->id,
            'owner_type' => 'MADRASAH',
            'created_by' => $teacher->id,
        ]);
        $questionVersion = $question->versions()->create([
            'tenant_id' => $this->tenant->id,
            'created_by' => $teacher->id,
            'question_type' => 'MULTIPLE_CHOICE',
            'stem' => 'Question current pointer regression',
            'status' => 'PUBLISHED',
            'is_current' => true,
            'major_version' => 1,
            'minor_version' => 0,
        ]);
        $snapshot = ExamQuestionSnapshot::create([
            'exam_id' => $exam->id,
            'source_question_id' => $question->id,
            'source_question_version_id' => $questionVersion->id,
            'tenant_id' => $this->tenant->id,
            'sequence' => 1,
            'points' => 10,
            'snapshot_stem' => 'Question current pointer regression',
            'snapshot_explanation' => '',
            'snapshot_options' => [],
            'snapshot_media' => [],
            'metadata' => ['question_type' => 'MULTIPLE_CHOICE'],
        ]);

        foreach ([1 => 0.0, 2 => 10.0] as $version => $awarded) {
            ExamQuestionScore::create([
                'tenant_id' => $this->tenant->id,
                'exam_id' => $exam->id,
                'attempt_id' => $attempt->id,
                'snapshot_id' => $snapshot->id,
                'student_id' => $attempt->participant->user_id,
                'question_type' => 'MULTIPLE_CHOICE',
                'awarded_score' => $awarded,
                'max_score' => 10,
                'scoring_version' => $version,
            ]);
        }

        $analysis = app(ItemAnalysisService::class)->getAnalysis($exam);
        $this->assertCount(1, $analysis);
        $this->assertSame(1.0, $analysis[0]['difficulty_index']);

        $questionReport = app(QuestionReportService::class)->getQuestionReport($exam);
        $this->assertCount(1, $questionReport);
        $this->assertSame(1, $questionReport[0]['total_responses']);
        $this->assertSame(1, $questionReport[0]['correct_count']);
        $this->assertSame(10.0, $questionReport[0]['average_score']);
    }

    public function test_subject_analytics_executes_and_uses_only_current_score_rows(): void
    {
        $subject = Subject::factory()->create(['tenant_id' => $this->tenant->id]);
        $assessment = Assessment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'subject_id' => $subject->id,
        ]);
        $exam = Exam::factory()->create([
            'tenant_id' => $this->tenant->id,
            'assessment_id' => $assessment->id,
            'show_result' => true,
        ]);
        $this->createScoredAttempt($exam, 20.0, 88.0);

        $analytics = app(SubjectAnalyticsService::class)->getSubjectAnalytics($subject);

        $this->assertSame(1, $analytics['exam_count']);
        $this->assertSame(88.0, $analytics['exams'][0]['average_percentage']);
        $this->assertSame(1, $analytics['exams'][0]['participant_count']);
    }

    public function test_score_without_a_valid_current_pointer_is_excluded(): void
    {
        $exam = Exam::factory()->create(['tenant_id' => $this->tenant->id]);
        $student = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $participant = ExamParticipant::factory()->create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $exam->id,
            'user_id' => $student->id,
        ]);
        $attempt = ExamAttempt::factory()->create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $exam->id,
            'participant_id' => $participant->id,
            'status' => 'SUBMITTED',
        ]);
        ExamAttemptScore::factory()->create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $exam->id,
            'attempt_id' => $attempt->id,
            'student_id' => $student->id,
            'scoring_version' => 1,
            'grading_status' => 'FINALIZED',
        ]);

        $this->assertSame(0, ExamAttemptScore::query()->where('attempt_id', $attempt->id)->current()->count());
        $this->assertSame(0, app(ExamAnalyticsService::class)->getSummary($exam)['participant_count']);
    }

    /**
     * @return array{0: User, 1: ExamAttempt, 2: ExamAttemptScore, 3: ExamAttemptScore}
     */
    private function createScoredAttempt(Exam $exam, float $historicalPercentage, float $currentPercentage): array
    {
        $student = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $participant = ExamParticipant::factory()->create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $exam->id,
            'user_id' => $student->id,
        ]);
        $attempt = ExamAttempt::factory()->create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $exam->id,
            'participant_id' => $participant->id,
            'status' => 'SUBMITTED',
        ]);

        $historicalScore = ExamAttemptScore::factory()->create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $exam->id,
            'attempt_id' => $attempt->id,
            'student_id' => $student->id,
            'percentage' => $historicalPercentage,
            'raw_score' => $historicalPercentage,
            'scoring_version' => 1,
            'grading_status' => 'FINALIZED',
        ]);
        $currentScore = ExamAttemptScore::factory()->create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $exam->id,
            'attempt_id' => $attempt->id,
            'student_id' => $student->id,
            'percentage' => $currentPercentage,
            'raw_score' => $currentPercentage,
            'scoring_version' => 2,
            'grading_status' => 'FINALIZED',
        ]);

        ExamAttemptScoreCurrent::create([
            'tenant_id' => $this->tenant->id,
            'attempt_id' => $attempt->id,
            'current_score_id' => $currentScore->id,
            'scoring_version' => 2,
        ]);

        return [$student, $attempt, $historicalScore, $currentScore];
    }
}
