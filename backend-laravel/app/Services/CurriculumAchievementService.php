<?php

namespace App\Services;

use App\Contexts\TenantContext;
use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\AtpAchievement;
use App\Models\CpAchievement;
use App\Models\ExamAttemptScore;
use App\Models\KktpVersion;
use App\Models\LearningObjective;
use App\Models\LearningOutcome;
use App\Models\LearningPath;
use App\Models\Rombel;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\TpAchievement;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CurriculumAchievementService
{
    protected array $assessmentCache = [];
    protected array $assessmentExamIdsCache = [];
    protected array $mappedAssessmentsCache = [];
    protected array $kktpVersionCache = [];
    protected array $atpChildTpsCache = [];
    protected array $cpChildAtpsCache = [];
    protected array $tpsBySubjectCache = [];
    protected array $cpsBySubjectCache = [];
    protected array $studentScoresCache = [];
    protected array $previousTpAchievementsCache = [];
    protected array $existingTpAchievementsCache = [];
    protected array $existingAtpAchievementsCache = [];
    protected array $existingCpAchievementsCache = [];
    protected array $batchPreloadedKeys = [];

    public function clearCache(): void
    {
        $this->assessmentCache = [];
        $this->assessmentExamIdsCache = [];
        $this->mappedAssessmentsCache = [];
        $this->kktpVersionCache = [];
        $this->atpChildTpsCache = [];
        $this->cpChildAtpsCache = [];
        $this->tpsBySubjectCache = [];
        $this->cpsBySubjectCache = [];
        $this->studentScoresCache = [];
        $this->previousTpAchievementsCache = [];
        $this->existingTpAchievementsCache = [];
        $this->existingAtpAchievementsCache = [];
        $this->existingCpAchievementsCache = [];
        $this->batchPreloadedKeys = [];
    }

    protected function getAssessmentById(string $assessmentId, string $tenantId): ?Assessment
    {
        $key = $tenantId . ':' . $assessmentId;
        if (isset($this->assessmentCache[$key])) {
            return $this->assessmentCache[$key];
        }

        $assessment = Assessment::where('id', $assessmentId)
            ->where('tenant_id', $tenantId)
            ->first();

        $this->assessmentCache[$key] = $assessment;
        return $assessment;
    }

    protected function getExamIdsForAssessment(Assessment $assessment): array
    {
        $key = $assessment->tenant_id . ':' . $assessment->id;
        if (isset($this->assessmentExamIdsCache[$key])) {
            return $this->assessmentExamIdsCache[$key];
        }

        $examIds = $assessment->exams()->pluck('id')->toArray();
        $this->assessmentExamIdsCache[$key] = $examIds;
        return $examIds;
    }

    protected function getMappedAssessmentsForTp(LearningObjective $tp, string $tenantId): array
    {
        $key = $tenantId . ':' . $tp->id;
        if (isset($this->mappedAssessmentsCache[$key])) {
            return $this->mappedAssessmentsCache[$key];
        }

        $mappedAssessments = $tp->assessments()
            ->where('assessments.tenant_id', $tenantId)
            ->whereNull('assessments.deleted_at')
            ->get();

        $result = [];
        foreach ($mappedAssessments as $assessment) {
            $examIds = $this->getExamIdsForAssessment($assessment);
            $result[] = [
                'model' => $assessment,
                'id' => $assessment->id,
                'weight' => $assessment->weight,
                'exam_ids' => $examIds,
            ];
        }

        $this->mappedAssessmentsCache[$key] = $result;
        return $result;
    }

    protected function getKktpForTp(string $tpId, string $tenantId): array
    {
        $key = $tenantId . ':' . $tpId;
        if (isset($this->kktpVersionCache[$key])) {
            return $this->kktpVersionCache[$key];
        }

        $kktpVersion = KktpVersion::where('learning_objective_id', $tpId)
            ->where('tenant_id', $tenantId)
            ->orderBy('version', 'desc')
            ->first();

        $kktpThreshold = $kktpVersion ? (float)$kktpVersion->threshold : 70.00;

        $res = [
            'id' => $kktpVersion?->id,
            'threshold' => $kktpThreshold,
        ];

        $this->kktpVersionCache[$key] = $res;
        return $res;
    }

    protected function getAtpChildTps(LearningPath $atp, string $tenantId): array
    {
        $key = $tenantId . ':' . $atp->id;
        if (isset($this->atpChildTpsCache[$key])) {
            return $this->atpChildTpsCache[$key];
        }

        $lpVersion = $atp->currentVersion;
        $childTps = $lpVersion ? $lpVersion->objectives : collect();

        $res = [
            'lp_version_id' => $lpVersion?->id,
            'objectives' => $childTps,
        ];

        $this->atpChildTpsCache[$key] = $res;
        return $res;
    }

    protected function getCpChildAtps(Subject $subject, string $tenantId): Collection
    {
        $key = $tenantId . ':' . $subject->id;
        if (isset($this->cpChildAtpsCache[$key])) {
            return $this->cpChildAtpsCache[$key];
        }

        $childAtps = LearningPath::where('subject_id', $subject->id)
            ->where('tenant_id', $tenantId)
            ->get();

        $this->cpChildAtpsCache[$key] = $childAtps;
        return $childAtps;
    }

    protected function getTpsForSubject(Subject $subject, string $tenantId): Collection
    {
        $key = $tenantId . ':' . $subject->id;
        if (isset($this->tpsBySubjectCache[$key])) {
            return $this->tpsBySubjectCache[$key];
        }

        $tps = LearningObjective::whereHas('outcome', function ($q) use ($subject, $tenantId) {
            $q->where('subject_id', $subject->id)->where('tenant_id', $tenantId);
        })->where('tenant_id', $tenantId)->get();

        $this->tpsBySubjectCache[$key] = $tps;
        return $tps;
    }

    protected function getCpsForSubject(Subject $subject, string $tenantId): Collection
    {
        $key = $tenantId . ':' . $subject->id;
        if (isset($this->cpsBySubjectCache[$key])) {
            return $this->cpsBySubjectCache[$key];
        }

        $cps = LearningOutcome::where('subject_id', $subject->id)
            ->where('tenant_id', $tenantId)
            ->get();

        $this->cpsBySubjectCache[$key] = $cps;
        return $cps;
    }

    protected function getStudentCurrentScores(string $studentId, string $tenantId): array
    {
        $key = $tenantId . ':' . $studentId;
        if (isset($this->studentScoresCache[$key])) {
            return $this->studentScoresCache[$key];
        }

        $allCurrentScores = ExamAttemptScore::join('exam_attempt_score_current', function ($join) use ($tenantId) {
                $join->on('exam_attempt_scores.id', '=', 'exam_attempt_score_current.current_score_id')
                    ->on('exam_attempt_scores.attempt_id', '=', 'exam_attempt_score_current.attempt_id')
                    ->on('exam_attempt_scores.tenant_id', '=', 'exam_attempt_score_current.tenant_id')
                    ->on('exam_attempt_scores.scoring_version', '=', 'exam_attempt_score_current.scoring_version')
                    ->where('exam_attempt_score_current.tenant_id', '=', $tenantId);
            })
            ->join('exam_attempts', function ($join) {
                $join->on('exam_attempts.id', '=', 'exam_attempt_score_current.attempt_id')
                    ->on('exam_attempts.tenant_id', '=', 'exam_attempt_score_current.tenant_id');
            })
            ->where('exam_attempt_scores.tenant_id', $tenantId)
            ->select('exam_attempt_scores.*')
            ->get();

        $grouped = [];
        foreach ($allCurrentScores as $score) {
            $sKey = $tenantId . ':' . $score->student_id;
            $grouped[$sKey][$score->exam_id] = $score;
        }

        foreach ($grouped as $sKey => $scoresMap) {
            $this->studentScoresCache[$sKey] = $scoresMap;
        }

        if (!isset($this->studentScoresCache[$key])) {
            $this->studentScoresCache[$key] = [];
        }

        return $this->studentScoresCache[$key];
    }

    protected function findCurrentAttemptScoreForExams(string $studentId, array $examIds, string $tenantId): ?ExamAttemptScore
    {
        if (empty($examIds)) {
            return null;
        }

        $studentScores = $this->getStudentCurrentScores($studentId, $tenantId);

        foreach ($examIds as $examId) {
            if (isset($studentScores[$examId])) {
                return $studentScores[$examId];
            }
        }

        return null;
    }

    protected function preloadAllStudentAchievements(AcademicYear $academicYear, Semester $semester, int $calculationVersion, string $tenantId): void
    {
        $batchKey = $tenantId . ':' . $academicYear->id . ':' . $semester->id . ':' . $calculationVersion;
        if (isset($this->batchPreloadedKeys[$batchKey])) {
            return;
        }

        // 1. Fetch all version 1 TP achievements with contributions
        $allPrevTps = TpAchievement::with('contributions')
            ->where('academic_year_id', $academicYear->id)
            ->where('semester_id', $semester->id)
            ->where('tenant_id', $tenantId)
            ->where('calculation_version', 1)
            ->get();

        foreach ($allPrevTps as $tpAch) {
            $pKey = $tenantId . ':' . $tpAch->student_id . ':' . $academicYear->id . ':' . $semester->id;
            $this->previousTpAchievementsCache[$pKey][$tpAch->learning_objective_id] = $tpAch;
        }

        // 2. Fetch existing TP achievements for target calculation version
        if ($calculationVersion === 1) {
            foreach ($this->previousTpAchievementsCache as $pKey => $tps) {
                $eKey = $pKey . ':1';
                $this->existingTpAchievementsCache[$eKey] = $tps;
            }
        } else {
            $allExistTps = TpAchievement::where('academic_year_id', $academicYear->id)
                ->where('semester_id', $semester->id)
                ->where('tenant_id', $tenantId)
                ->where('calculation_version', $calculationVersion)
                ->get();

            foreach ($allExistTps as $tpAch) {
                $eKey = $tenantId . ':' . $tpAch->student_id . ':' . $academicYear->id . ':' . $semester->id . ':' . $calculationVersion;
                $this->existingTpAchievementsCache[$eKey][$tpAch->learning_objective_id] = $tpAch;
            }
        }

        // 3. Fetch existing ATP achievements
        $allExistAtps = AtpAchievement::where('academic_year_id', $academicYear->id)
            ->where('semester_id', $semester->id)
            ->where('tenant_id', $tenantId)
            ->where('calculation_version', $calculationVersion)
            ->get();

        foreach ($allExistAtps as $atpAch) {
            $eKey = $tenantId . ':' . $atpAch->student_id . ':' . $academicYear->id . ':' . $semester->id . ':' . $calculationVersion;
            $this->existingAtpAchievementsCache[$eKey][$atpAch->learning_path_id] = $atpAch;
        }

        // 4. Fetch existing CP achievements
        $allExistCps = CpAchievement::where('academic_year_id', $academicYear->id)
            ->where('semester_id', $semester->id)
            ->where('tenant_id', $tenantId)
            ->where('calculation_version', $calculationVersion)
            ->get();

        foreach ($allExistCps as $cpAch) {
            $eKey = $tenantId . ':' . $cpAch->student_id . ':' . $academicYear->id . ':' . $semester->id . ':' . $calculationVersion;
            $this->existingCpAchievementsCache[$eKey][$cpAch->learning_outcome_id] = $cpAch;
        }

        $this->batchPreloadedKeys[$batchKey] = true;
    }

    protected function getPreviousTpAchievement(
        User $student,
        LearningObjective $tp,
        AcademicYear $academicYear,
        Semester $semester,
        string $tenantId
    ): ?TpAchievement {
        $key = $tenantId . ':' . $student->id . ':' . $academicYear->id . ':' . $semester->id;
        if (!isset($this->previousTpAchievementsCache[$key])) {
            $this->preloadAllStudentAchievements($academicYear, $semester, 1, $tenantId);
        }

        return $this->previousTpAchievementsCache[$key][$tp->id] ?? null;
    }

    protected function getExistingTpAchievement(
        User $student,
        LearningObjective $tp,
        AcademicYear $academicYear,
        Semester $semester,
        int $calculationVersion,
        string $tenantId
    ): ?TpAchievement {
        $key = $tenantId . ':' . $student->id . ':' . $academicYear->id . ':' . $semester->id . ':' . $calculationVersion;
        if (!isset($this->existingTpAchievementsCache[$key])) {
            $this->preloadAllStudentAchievements($academicYear, $semester, $calculationVersion, $tenantId);
        }

        return $this->existingTpAchievementsCache[$key][$tp->id] ?? null;
    }

    protected function getExistingAtpAchievement(
        User $student,
        LearningPath $atp,
        AcademicYear $academicYear,
        Semester $semester,
        int $calculationVersion,
        string $tenantId
    ): ?AtpAchievement {
        $key = $tenantId . ':' . $student->id . ':' . $academicYear->id . ':' . $semester->id . ':' . $calculationVersion;
        if (!isset($this->existingAtpAchievementsCache[$key])) {
            $this->preloadAllStudentAchievements($academicYear, $semester, $calculationVersion, $tenantId);
        }

        return $this->existingAtpAchievementsCache[$key][$atp->id] ?? null;
    }

    protected function getExistingCpAchievement(
        User $student,
        LearningOutcome $cp,
        AcademicYear $academicYear,
        Semester $semester,
        int $calculationVersion,
        string $tenantId
    ): ?CpAchievement {
        $key = $tenantId . ':' . $student->id . ':' . $academicYear->id . ':' . $semester->id . ':' . $calculationVersion;
        if (!isset($this->existingCpAchievementsCache[$key])) {
            $this->preloadAllStudentAchievements($academicYear, $semester, $calculationVersion, $tenantId);
        }

        return $this->existingCpAchievementsCache[$key][$cp->id] ?? null;
    }

    /**
     * Calculate TP Achievement for a student.
     */
    public function calculateTpAchievement(
        User $student,
        LearningObjective $tp,
        AcademicYear $academicYear,
        Semester $semester,
        Subject $subject,
        ?Rombel $rombel = null,
        int $calculationVersion = 1
    ): TpAchievement {
        $tenantId = $student->tenant_id ?? TenantContext::getTenantId();

        // Option A Historical Snapshot Check: If previous calculation (e.g., version 1) exists for this TP & student,
        // re-use its historical mapping snapshot (contributions & KKTP threshold) instead of current master mapping.
        $previousAchievement = $this->getPreviousTpAchievement($student, $tp, $academicYear, $semester, $tenantId);

        if ($previousAchievement && $previousAchievement->contributions->count() > 0) {
            $historicalContribs = $previousAchievement->contributions;

            $contributions = [];
            $totalWeightedScore = 0.0;
            $totalWeight = 0.0;
            $allFinalized = true;

            foreach ($historicalContribs as $hContrib) {
                $assessmentId = $hContrib->assessment_id;
                $weight = (float)$hContrib->weight_snapshot;

                $assessment = $this->getAssessmentById($assessmentId, $tenantId);

                if (!$assessment) {
                    $allFinalized = false;
                    continue;
                }

                $examIds = $this->getExamIdsForAssessment($assessment);

                $attemptScore = $this->findCurrentAttemptScoreForExams($student->id, $examIds, $tenantId);

                if (!$attemptScore || $attemptScore->grading_status !== 'FINALIZED') {
                    $allFinalized = false;
                    $contributions[] = [
                        'assessment_id' => $assessmentId,
                        'attempt_score_id' => $attemptScore?->id,
                        'assessment_score_snapshot' => $attemptScore ? (float)$attemptScore->percentage : null,
                        'weight_snapshot' => $weight,
                        'weighted_contribution' => null,
                    ];
                } else {
                    $percentage = (float)$attemptScore->percentage;
                    $weightedVal = $percentage * $weight;

                    $totalWeightedScore += $weightedVal;
                    $totalWeight += $weight;

                    $contributions[] = [
                        'assessment_id' => $assessmentId,
                        'attempt_score_id' => $attemptScore->id,
                        'assessment_score_snapshot' => $percentage,
                        'weight_snapshot' => $weight,
                        'weighted_contribution' => $weightedVal,
                    ];
                }
            }

            $kktpVersionId = $previousAchievement->kktp_version_id;
            $kktpThreshold = $previousAchievement->kktp_threshold_snapshot !== null ? (float)$previousAchievement->kktp_threshold_snapshot : 70.00;

            if (!$allFinalized || $totalWeight <= 0) {
                return $this->persistTpAchievement(
                    $student, $tp, $academicYear, $semester, $subject, $rombel,
                    $kktpVersionId, $kktpThreshold, 'BELUM_DINILAI', null, $contributions, $calculationVersion, $tenantId
                );
            }

            $rawScore = $totalWeightedScore / $totalWeight;
            $roundedScore = round($rawScore, 2);

            $status = ($roundedScore >= $kktpThreshold) ? 'TERCAPAI' : 'BELUM_TERCAPAI';

            return $this->persistTpAchievement(
                $student, $tp, $academicYear, $semester, $subject, $rombel,
                $kktpVersionId, $kktpThreshold, $status, $roundedScore, $contributions, $calculationVersion, $tenantId
            );
        }

        // Initial finalization: Mapped assessments from master mapping
        $mappedItems = $this->getMappedAssessmentsForTp($tp, $tenantId);

        if (empty($mappedItems)) {
            return $this->persistTpAchievement(
                $student, $tp, $academicYear, $semester, $subject, $rombel,
                null, null, 'BELUM_DINILAI', null, [], $calculationVersion, $tenantId
            );
        }

        // Validate Assessment Weights (AC-7D.2)
        foreach ($mappedItems as $item) {
            $weight = $item['weight'];
            if ($weight === null || !is_numeric($weight) || (float)$weight <= 0) {
                throw new InvalidArgumentException("Assessment [{$item['id']}] has invalid or unconfigured weight.");
            }
        }

        $contributions = [];
        $totalWeightedScore = 0.0;
        $totalWeight = 0.0;
        $allFinalized = true;

        foreach ($mappedItems as $item) {
            $assessmentId = $item['id'];
            $examIds = $item['exam_ids'];
            $weight = (float)$item['weight'];

            $attemptScore = $this->findCurrentAttemptScoreForExams($student->id, $examIds, $tenantId);

            if (!$attemptScore || $attemptScore->grading_status !== 'FINALIZED') {
                $allFinalized = false;
                $contributions[] = [
                    'assessment_id' => $assessmentId,
                    'attempt_score_id' => $attemptScore?->id,
                    'assessment_score_snapshot' => $attemptScore ? (float)$attemptScore->percentage : null,
                    'weight_snapshot' => $weight,
                    'weighted_contribution' => null,
                ];
            } else {
                $percentage = (float)$attemptScore->percentage;
                $weightedVal = $percentage * $weight;

                $totalWeightedScore += $weightedVal;
                $totalWeight += $weight;

                $contributions[] = [
                    'assessment_id' => $assessmentId,
                    'attempt_score_id' => $attemptScore->id,
                    'assessment_score_snapshot' => $percentage,
                    'weight_snapshot' => $weight,
                    'weighted_contribution' => $weightedVal,
                ];
            }
        }

        // Completeness Gate (AC-7D.3)
        if (!$allFinalized || $totalWeight <= 0) {
            return $this->persistTpAchievement(
                $student, $tp, $academicYear, $semester, $subject, $rombel,
                null, null, 'BELUM_DINILAI', null, $contributions, $calculationVersion, $tenantId
            );
        }

        // Weighted Average Formula (AC-7D.1)
        $rawScore = $totalWeightedScore / $totalWeight;
        $roundedScore = round($rawScore, 2);

        // Fetch active KKTP Version (AC-7D.3)
        $kktp = $this->getKktpForTp($tp->id, $tenantId);
        $kktpVersionId = $kktp['id'];
        $kktpThreshold = $kktp['threshold'];

        $status = ($roundedScore >= $kktpThreshold) ? 'TERCAPAI' : 'BELUM_TERCAPAI';

        return $this->persistTpAchievement(
            $student, $tp, $academicYear, $semester, $subject, $rombel,
            $kktpVersionId, $kktpThreshold, $status, $roundedScore, $contributions, $calculationVersion, $tenantId
        );
    }

    protected function persistTpAchievement(
        User $student,
        LearningObjective $tp,
        AcademicYear $academicYear,
        Semester $semester,
        Subject $subject,
        ?Rombel $rombel,
        ?string $kktpVersionId,
        ?float $kktpThreshold,
        string $status,
        ?float $score,
        array $contributions,
        int $calculationVersion,
        string $tenantId
    ): TpAchievement {
        $existing = $this->getExistingTpAchievement($student, $tp, $academicYear, $semester, $calculationVersion, $tenantId);

        $action = function () use (
            $student, $tp, $academicYear, $semester, $subject, $rombel,
            $kktpVersionId, $kktpThreshold, $status, $score, $contributions, $calculationVersion, $tenantId, $existing
        ) {
            if ($calculationVersion > 1) {
                TpAchievement::where('student_id', $student->id)
                    ->where('learning_objective_id', $tp->id)
                    ->where('academic_year_id', $academicYear->id)
                    ->where('semester_id', $semester->id)
                    ->where('tenant_id', $tenantId)
                    ->where('calculation_version', '!=', $calculationVersion)
                    ->where('is_current', true)
                    ->update(['is_current' => false]);
            }

            $nowStr = date('Y-m-d H:i:s');

            if ($existing) {
                $existing->update([
                    'tenant_id' => $tenantId,
                    'subject_id' => $subject->id,
                    'rombel_id' => $rombel?->id,
                    'kktp_version_id' => $kktpVersionId,
                    'kktp_threshold_snapshot' => $kktpThreshold,
                    'score' => $score,
                    'status' => $status,
                    'is_current' => true,
                    'calculated_at' => $nowStr,
                ]);
                $tpAchievement = $existing;

                DB::table('tp_assessment_contributions')
                    ->where('tp_achievement_id', $tpAchievement->id)
                    ->where('tenant_id', $tenantId)
                    ->delete();
            } else {
                $id = (string)Str::uuid();
                $data = [
                    'id' => $id,
                    'tenant_id' => $tenantId,
                    'student_id' => $student->id,
                    'learning_objective_id' => $tp->id,
                    'academic_year_id' => $academicYear->id,
                    'semester_id' => $semester->id,
                    'subject_id' => $subject->id,
                    'rombel_id' => $rombel?->id,
                    'calculation_version' => $calculationVersion,
                    'kktp_version_id' => $kktpVersionId,
                    'kktp_threshold_snapshot' => $kktpThreshold,
                    'score' => $score,
                    'status' => $status,
                    'is_current' => true,
                    'calculated_at' => $nowStr,
                    'created_at' => $nowStr,
                    'updated_at' => $nowStr,
                ];

                DB::table('tp_achievements')->insert($data);

                $tpAchievement = new TpAchievement();
                $tpAchievement->forceFill($data);
                $tpAchievement->exists = true;

                $key = $tenantId . ':' . $student->id . ':' . $academicYear->id . ':' . $semester->id . ':' . $calculationVersion;
                $this->existingTpAchievementsCache[$key][$tp->id] = $tpAchievement;
            }

            $rows = [];
            foreach ($contributions as $contrib) {
                $rows[] = [
                    'id' => (string)Str::uuid(),
                    'tenant_id' => $tenantId,
                    'tp_achievement_id' => $tpAchievement->id,
                    'assessment_id' => $contrib['assessment_id'],
                    'attempt_score_id' => $contrib['attempt_score_id'],
                    'assessment_score_snapshot' => $contrib['assessment_score_snapshot'],
                    'weight_snapshot' => $contrib['weight_snapshot'],
                    'weighted_contribution' => $contrib['weighted_contribution'],
                    'created_at' => $nowStr,
                ];
            }

            if (!empty($rows)) {
                DB::table('tp_assessment_contributions')->insert($rows);
            }

            return $tpAchievement;
        };

        return DB::transactionLevel() > 0 ? $action() : DB::transaction($action);
    }

    /**
     * Calculate ATP Achievement for a student.
     */
    public function calculateAtpAchievement(
        User $student,
        LearningPath $atp,
        AcademicYear $academicYear,
        Semester $semester,
        Subject $subject,
        ?Rombel $rombel = null,
        int $calculationVersion = 1
    ): AtpAchievement {
        return $this->calculateAtpAchievementInternal($student, $atp, $academicYear, $semester, $subject, $rombel, $calculationVersion, null);
    }

    protected function calculateAtpAchievementInternal(
        User $student,
        LearningPath $atp,
        AcademicYear $academicYear,
        Semester $semester,
        Subject $subject,
        ?Rombel $rombel,
        int $calculationVersion,
        ?array $tpResultsByTpId
    ): AtpAchievement {
        $tenantId = $student->tenant_id ?? TenantContext::getTenantId();

        $atpInfo = $this->getAtpChildTps($atp, $tenantId);
        $lpVersionId = $atpInfo['lp_version_id'];
        $childTps = $atpInfo['objectives'];

        if ($childTps->isEmpty()) {
            return $this->persistAtpAchievement(
                $student, $atp, $lpVersionId, $academicYear, $semester, $subject, $rombel,
                null, 'BELUM_DINILAI', [], $calculationVersion, $tenantId
            );
        }

        $tpSources = [];
        $totalScore = 0.0;
        $tpCount = 0;
        $allComplete = true;
        $hasBelumTercapai = false;

        foreach ($childTps as $tp) {
            $tpAch = $tpResultsByTpId[$tp->id] ?? null;

            if (!$tpAch) {
                $tpAch = $this->getExistingTpAchievement($student, $tp, $academicYear, $semester, $calculationVersion, $tenantId);
            }

            if (!$tpAch || $tpAch->status === 'BELUM_DINILAI' || $tpAch->score === null) {
                $allComplete = false;
                $tpSources[] = [
                    'tp_achievement_id' => $tpAch?->id,
                    'tp_score_snapshot' => $tpAch?->score,
                    'tp_status_snapshot' => $tpAch?->status ?? 'BELUM_DINILAI',
                    'tp_calculation_version_snapshot' => $tpAch?->calculation_version,
                ];
            } else {
                $totalScore += (float)$tpAch->score;
                $tpCount++;

                if ($tpAch->status === 'BELUM_TERCAPAI') {
                    $hasBelumTercapai = true;
                }

                $tpSources[] = [
                    'tp_achievement_id' => $tpAch->id,
                    'tp_score_snapshot' => (float)$tpAch->score,
                    'tp_status_snapshot' => $tpAch->status,
                    'tp_calculation_version_snapshot' => $tpAch->calculation_version,
                ];
            }
        }

        // Completeness Gate (AC-7D.3)
        if (!$allComplete || $tpCount === 0) {
            return $this->persistAtpAchievement(
                $student, $atp, $lpVersionId, $academicYear, $semester, $subject, $rombel,
                null, 'BELUM_DINILAI', $tpSources, $calculationVersion, $tenantId
            );
        }

        // Equal Arithmetic Mean (AC-7D.4)
        $rawScore = $totalScore / $tpCount;
        $roundedScore = round($rawScore, 2);

        $status = $hasBelumTercapai ? 'BELUM_TERCAPAI' : 'TERCAPAI';

        return $this->persistAtpAchievement(
            $student, $atp, $lpVersionId, $academicYear, $semester, $subject, $rombel,
            $roundedScore, $status, $tpSources, $calculationVersion, $tenantId
        );
    }

    protected function persistAtpAchievement(
        User $student,
        LearningPath $atp,
        ?string $lpVersionId,
        AcademicYear $academicYear,
        Semester $semester,
        Subject $subject,
        ?Rombel $rombel,
        ?float $score,
        string $status,
        array $tpSources,
        int $calculationVersion,
        string $tenantId
    ): AtpAchievement {
        $existing = $this->getExistingAtpAchievement($student, $atp, $academicYear, $semester, $calculationVersion, $tenantId);

        $action = function () use (
            $student, $atp, $lpVersionId, $academicYear, $semester, $subject, $rombel,
            $score, $status, $tpSources, $calculationVersion, $tenantId, $existing
        ) {
            if ($calculationVersion > 1) {
                AtpAchievement::where('student_id', $student->id)
                    ->where('learning_path_id', $atp->id)
                    ->where('academic_year_id', $academicYear->id)
                    ->where('semester_id', $semester->id)
                    ->where('tenant_id', $tenantId)
                    ->where('calculation_version', '!=', $calculationVersion)
                    ->where('is_current', true)
                    ->update(['is_current' => false]);
            }

            $nowStr = date('Y-m-d H:i:s');

            if ($existing) {
                $existing->update([
                    'tenant_id' => $tenantId,
                    'subject_id' => $subject->id,
                    'rombel_id' => $rombel?->id,
                    'learning_path_version_id' => $lpVersionId,
                    'score' => $score,
                    'status' => $status,
                    'is_current' => true,
                    'calculated_at' => $nowStr,
                ]);
                $atpAchievement = $existing;

                DB::table('atp_tp_sources')
                    ->where('atp_achievement_id', $atpAchievement->id)
                    ->where('tenant_id', $tenantId)
                    ->delete();
            } else {
                $id = (string)Str::uuid();
                $data = [
                    'id' => $id,
                    'tenant_id' => $tenantId,
                    'student_id' => $student->id,
                    'learning_path_id' => $atp->id,
                    'academic_year_id' => $academicYear->id,
                    'semester_id' => $semester->id,
                    'subject_id' => $subject->id,
                    'rombel_id' => $rombel?->id,
                    'calculation_version' => $calculationVersion,
                    'learning_path_version_id' => $lpVersionId,
                    'score' => $score,
                    'status' => $status,
                    'is_current' => true,
                    'calculated_at' => $nowStr,
                    'created_at' => $nowStr,
                    'updated_at' => $nowStr,
                ];

                DB::table('atp_achievements')->insert($data);

                $atpAchievement = new AtpAchievement();
                $atpAchievement->forceFill($data);
                $atpAchievement->exists = true;

                $key = $tenantId . ':' . $student->id . ':' . $academicYear->id . ':' . $semester->id . ':' . $calculationVersion;
                $this->existingAtpAchievementsCache[$key][$atp->id] = $atpAchievement;
            }

            $rows = [];
            foreach ($tpSources as $src) {
                if (!empty($src['tp_achievement_id'])) {
                    $rows[] = [
                        'id' => (string)Str::uuid(),
                        'tenant_id' => $tenantId,
                        'atp_achievement_id' => $atpAchievement->id,
                        'tp_achievement_id' => $src['tp_achievement_id'],
                        'tp_score_snapshot' => $src['tp_score_snapshot'],
                        'tp_status_snapshot' => $src['tp_status_snapshot'],
                        'tp_calculation_version_snapshot' => $src['tp_calculation_version_snapshot'],
                        'created_at' => $nowStr,
                    ];
                }
            }

            if (!empty($rows)) {
                DB::table('atp_tp_sources')->insert($rows);
            }

            return $atpAchievement;
        };

        return DB::transactionLevel() > 0 ? $action() : DB::transaction($action);
    }

    /**
     * Calculate CP Achievement for a student.
     */
    public function calculateCpAchievement(
        User $student,
        LearningOutcome $cp,
        AcademicYear $academicYear,
        Semester $semester,
        Subject $subject,
        ?Rombel $rombel = null,
        int $calculationVersion = 1
    ): CpAchievement {
        return $this->calculateCpAchievementInternal($student, $cp, $academicYear, $semester, $subject, $rombel, $calculationVersion, null);
    }

    protected function calculateCpAchievementInternal(
        User $student,
        LearningOutcome $cp,
        AcademicYear $academicYear,
        Semester $semester,
        Subject $subject,
        ?Rombel $rombel,
        int $calculationVersion,
        ?array $atpResultsByAtpId
    ): CpAchievement {
        $tenantId = $student->tenant_id ?? TenantContext::getTenantId();

        $loVersion = $cp->currentVersion;

        $childAtps = $this->getCpChildAtps($subject, $tenantId);

        if ($childAtps->isEmpty()) {
            return $this->persistCpAchievement(
                $student, $cp, $loVersion?->id, $academicYear, $semester, $subject, $rombel,
                null, 'BELUM_DINILAI', [], $calculationVersion, $tenantId
            );
        }

        $atpSources = [];
        $totalScore = 0.0;
        $atpCount = 0;
        $allComplete = true;
        $hasBelumTercapai = false;

        foreach ($childAtps as $atp) {
            $atpAch = $atpResultsByAtpId[$atp->id] ?? null;

            if (!$atpAch) {
                $atpAch = $this->getExistingAtpAchievement($student, $atp, $academicYear, $semester, $calculationVersion, $tenantId);
            }

            if (!$atpAch || $atpAch->status === 'BELUM_DINILAI' || $atpAch->score === null) {
                $allComplete = false;
                $atpSources[] = [
                    'atp_achievement_id' => $atpAch?->id,
                    'atp_score_snapshot' => $atpAch?->score,
                    'atp_status_snapshot' => $atpAch?->status ?? 'BELUM_DINILAI',
                    'atp_calculation_version_snapshot' => $atpAch?->calculation_version,
                ];
            } else {
                $totalScore += (float)$atpAch->score;
                $atpCount++;

                if ($atpAch->status === 'BELUM_TERCAPAI') {
                    $hasBelumTercapai = true;
                }

                $atpSources[] = [
                    'atp_achievement_id' => $atpAch->id,
                    'atp_score_snapshot' => (float)$atpAch->score,
                    'atp_status_snapshot' => $atpAch->status,
                    'atp_calculation_version_snapshot' => $atpAch->calculation_version,
                ];
            }
        }

        // Completeness Gate (AC-7D.3)
        if (!$allComplete || $atpCount === 0) {
            return $this->persistCpAchievement(
                $student, $cp, $loVersion?->id, $academicYear, $semester, $subject, $rombel,
                null, 'BELUM_DINILAI', $atpSources, $calculationVersion, $tenantId
            );
        }

        // Equal Arithmetic Mean (AC-7D.4)
        $rawScore = $totalScore / $atpCount;
        $roundedScore = round($rawScore, 2);

        $status = $hasBelumTercapai ? 'BELUM_TERCAPAI' : 'TERCAPAI';

        return $this->persistCpAchievement(
            $student, $cp, $loVersion?->id, $academicYear, $semester, $subject, $rombel,
            $roundedScore, $status, $atpSources, $calculationVersion, $tenantId
        );
    }

    protected function persistCpAchievement(
        User $student,
        LearningOutcome $cp,
        ?string $loVersionId,
        AcademicYear $academicYear,
        Semester $semester,
        Subject $subject,
        ?Rombel $rombel,
        ?float $score,
        string $status,
        array $atpSources,
        int $calculationVersion,
        string $tenantId
    ): CpAchievement {
        $existing = $this->getExistingCpAchievement($student, $cp, $academicYear, $semester, $calculationVersion, $tenantId);

        $action = function () use (
            $student, $cp, $loVersionId, $academicYear, $semester, $subject, $rombel,
            $score, $status, $atpSources, $calculationVersion, $tenantId, $existing
        ) {
            if ($calculationVersion > 1) {
                CpAchievement::where('student_id', $student->id)
                    ->where('learning_outcome_id', $cp->id)
                    ->where('academic_year_id', $academicYear->id)
                    ->where('semester_id', $semester->id)
                    ->where('tenant_id', $tenantId)
                    ->where('calculation_version', '!=', $calculationVersion)
                    ->where('is_current', true)
                    ->update(['is_current' => false]);
            }

            $nowStr = date('Y-m-d H:i:s');

            if ($existing) {
                $existing->update([
                    'tenant_id' => $tenantId,
                    'subject_id' => $subject->id,
                    'rombel_id' => $rombel?->id,
                    'learning_outcome_version_id' => $loVersionId,
                    'score' => $score,
                    'status' => $status,
                    'is_current' => true,
                    'calculated_at' => $nowStr,
                ]);
                $cpAchievement = $existing;

                DB::table('cp_atp_sources')
                    ->where('cp_achievement_id', $cpAchievement->id)
                    ->where('tenant_id', $tenantId)
                    ->delete();
            } else {
                $id = (string)Str::uuid();
                $data = [
                    'id' => $id,
                    'tenant_id' => $tenantId,
                    'student_id' => $student->id,
                    'learning_outcome_id' => $cp->id,
                    'academic_year_id' => $academicYear->id,
                    'semester_id' => $semester->id,
                    'subject_id' => $subject->id,
                    'rombel_id' => $rombel?->id,
                    'calculation_version' => $calculationVersion,
                    'learning_outcome_version_id' => $loVersionId,
                    'score' => $score,
                    'status' => $status,
                    'is_current' => true,
                    'calculated_at' => $nowStr,
                    'created_at' => $nowStr,
                    'updated_at' => $nowStr,
                ];

                DB::table('cp_achievements')->insert($data);

                $cpAchievement = new CpAchievement();
                $cpAchievement->forceFill($data);
                $cpAchievement->exists = true;

                $key = $tenantId . ':' . $student->id . ':' . $academicYear->id . ':' . $semester->id . ':' . $calculationVersion;
                $this->existingCpAchievementsCache[$key][$cp->id] = $cpAchievement;
            }

            $rows = [];
            foreach ($atpSources as $src) {
                if (!empty($src['atp_achievement_id'])) {
                    $rows[] = [
                        'id' => (string)Str::uuid(),
                        'tenant_id' => $tenantId,
                        'cp_achievement_id' => $cpAchievement->id,
                        'atp_achievement_id' => $src['atp_achievement_id'],
                        'atp_score_snapshot' => $src['atp_score_snapshot'],
                        'atp_status_snapshot' => $src['atp_status_snapshot'],
                        'atp_calculation_version_snapshot' => $src['atp_calculation_version_snapshot'],
                        'created_at' => $nowStr,
                    ];
                }
            }

            if (!empty($rows)) {
                DB::table('cp_atp_sources')->insert($rows);
            }

            return $cpAchievement;
        };

        return DB::transactionLevel() > 0 ? $action() : DB::transaction($action);
    }

    /**
     * Complete Student Achievements Calculation pipeline for a subject.
     */
    public function calculateStudentAchievements(
        User $student,
        Subject $subject,
        AcademicYear $academicYear,
        Semester $semester,
        ?Rombel $rombel = null,
        int $calculationVersion = 1
    ): array {
        $tenantId = $student->tenant_id ?? TenantContext::getTenantId();

        $this->preloadAllStudentAchievements($academicYear, $semester, $calculationVersion, $tenantId);

        // 1. Calculate TPs
        $tps = $this->getTpsForSubject($subject, $tenantId);

        $tpResults = [];
        $tpResultsByTpId = [];
        foreach ($tps as $tp) {
            $tpAch = $this->calculateTpAchievement($student, $tp, $academicYear, $semester, $subject, $rombel, $calculationVersion);
            $tpResults[] = $tpAch;
            $tpResultsByTpId[$tp->id] = $tpAch;
        }

        // 2. Calculate ATPs
        $atps = $this->getCpChildAtps($subject, $tenantId);

        $atpResults = [];
        $atpResultsByAtpId = [];
        foreach ($atps as $atp) {
            $atpAch = $this->calculateAtpAchievementInternal($student, $atp, $academicYear, $semester, $subject, $rombel, $calculationVersion, $tpResultsByTpId);
            $atpResults[] = $atpAch;
            $atpResultsByAtpId[$atp->id] = $atpAch;
        }

        // 3. Calculate CPs
        $cps = $this->getCpsForSubject($subject, $tenantId);

        $cpResults = [];
        foreach ($cps as $cp) {
            $cpAch = $this->calculateCpAchievementInternal($student, $cp, $academicYear, $semester, $subject, $rombel, $calculationVersion, $atpResultsByAtpId);
            $cpResults[] = $cpAch;
        }

        return [
            'tp_achievements' => $tpResults,
            'atp_achievements' => $atpResults,
            'cp_achievements' => $cpResults,
        ];
    }
}
