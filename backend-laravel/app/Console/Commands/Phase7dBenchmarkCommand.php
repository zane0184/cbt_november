<?php

namespace App\Console\Commands;

use App\Contexts\TenantContext;
use App\Http\Controllers\Api\V1\ReportController;
use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\Curriculum;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptScore;
use App\Models\ExamAttemptScoreCurrent;
use App\Models\ExamParticipant;
use App\Models\ExamQuestionSnapshot;
use App\Models\GradeLevel;
use App\Models\KktpVersion;
use App\Models\LearningObjective;
use App\Models\LearningOutcome;
use App\Models\LearningPath;
use App\Models\LearningPathVersion;
use App\Models\Phase;
use App\Models\Question;
use App\Models\QuestionVersion;
use App\Models\Role;
use App\Models\Rombel;
use App\Models\Semester;
use App\Models\StudentProfile;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\Tenant;
use App\Models\User;
use App\Services\CurriculumAchievementService;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Phase7dBenchmarkCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'benchmark:phase7d {--runs=5 : Number of iterations} {--json : Output JSON report}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Phase 7D.5 local benchmark harness for Curriculum Achievement & Report PDF Export';

    public function handle(): int
    {
        $runs = (int)$this->option('runs');
        if ($runs < 1) {
            $runs = 5;
        }

        $this->info("=================================================");
        $this->info("Phase 7D.5 Local Benchmark Harness");
        $this->info("Runs per operation: {$runs}");
        $this->info("=================================================");

        // Configure isolated SQLite database for benchmark
        $dbPath = database_path('benchmark.sqlite');
        if (!file_exists($dbPath)) {
            touch($dbPath);
        }

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => $dbPath,
        ]);

        DB::purge('sqlite');

        $this->info("Running migrations on isolated benchmark database...");
        Artisan::call('migrate:fresh', ['--database' => 'sqlite', '--force' => true]);

        $this->info("Seeding representative workload...");
        $startTime = microtime(true);
        $workload = $this->seedWorkload();
        $seedDuration = round((microtime(true) - $startTime) * 1000, 2);
        $this->info("Workload seeded in {$seedDuration} ms.");

        // Operation 1: Batch Achievement Calculation
        $this->info("\n--- Operation 1: Batch Achievement Calculation (100 Students) ---");
        $op1Results = $this->runBatchAchievementBenchmark($workload, $runs);

        // Operation 2: Combined 100-student PDF Report Export
        $this->info("\n--- Operation 2: Combined 100-student PDF Report Export ---");
        $op2Results = $this->runPdfExportBenchmark($workload, $runs);

        // Cleanup isolated DB file
        DB::disconnect('sqlite');
        if (file_exists($dbPath)) {
            @unlink($dbPath);
        }

        // Summary report & governance threshold evaluation
        $summary = $this->buildSummaryReport($op1Results, $op2Results, $runs);

        if ($this->option('json')) {
            $this->line(json_encode($summary, JSON_PRETTY_PRINT));
        } else {
            $this->printSummaryTable($summary);
        }

        // Save report to storage
        $reportDir = storage_path('reports');
        if (!file_exists($reportDir)) {
            mkdir($reportDir, 0755, true);
        }
        file_put_contents($reportDir . '/phase7d_benchmark_report.json', json_encode($summary, JSON_PRETTY_PRINT));
        $this->info("\nBenchmark report saved to: " . $reportDir . '/phase7d_benchmark_report.json');

        return 0;
    }

    private function seedWorkload(): array
    {
        return DB::transaction(function () {
            $tenant = Tenant::create([
                'name' => 'MI Benchmark Tenant',
                'code' => 'MIN_BENCH',
                'nsm' => '111231710999',
                'npsn' => '60700999',
                'address' => 'Jl. Benchmark No. 100, Jakarta',
                'phone' => '021-9999999',
                'email' => 'info@bench.sch.id',
                'logo_path' => null,
                'status' => 'ACTIVE'
            ]);

            app(TenantContext::class)->setTenantId($tenant->id);

            $adminRole = Role::firstOrCreate(['name' => 'ADMIN_MADRASAH'], ['label' => 'Admin Madrasah']);
            $guruRole = Role::firstOrCreate(['name' => 'GURU'], ['label' => 'Guru']);
            $siswaRole = Role::firstOrCreate(['name' => 'SISWA'], ['label' => 'Siswa']);

            $adminUser = User::create([
                'tenant_id' => $tenant->id,
                'name' => 'Admin Benchmark',
                'email' => 'admin@bench.sch.id',
                'password' => bcrypt('password'),
            ]);
            $adminUser->roles()->attach($adminRole);
            $adminUser->load('roles');

            $teacherUser = User::create([
                'tenant_id' => $tenant->id,
                'name' => 'Guru Benchmark',
                'email' => 'guru@bench.sch.id',
                'password' => bcrypt('password'),
            ]);
            $teacherUser->roles()->attach($guruRole);
            $teacherUser->load('roles');

            $academicYear = AcademicYear::create([
                'tenant_id' => $tenant->id,
                'name' => '2025/2026',
                'start_date' => '2025-07-01',
                'end_date' => '2026-06-30',
                'status' => 'ACTIVE'
            ]);

            $semester = Semester::create([
                'tenant_id' => $tenant->id,
                'academic_year_id' => $academicYear->id,
                'term' => 'GANJIL',
                'is_active' => true
            ]);

            $subject = Subject::create([
                'tenant_id' => $tenant->id,
                'code' => 'MATH_BENCH',
                'name' => 'Matematika'
            ]);

            $curriculum = Curriculum::create([
                'tenant_id' => $tenant->id,
                'name' => 'Kurikulum Merdeka',
                'code' => 'KM'
            ]);

            $phase = Phase::create([
                'tenant_id' => $tenant->id,
                'curriculum_id' => $curriculum->id,
                'name' => 'Fase C',
                'code' => 'C'
            ]);

            $gradeLevel = GradeLevel::create([
                'tenant_id' => $tenant->id,
                'phase_id' => $phase->id,
                'name' => 'Kelas 5',
                'code' => 'K5',
                'level' => 5,
                'status' => 'ACTIVE'
            ]);

            $teacherProfile = TeacherProfile::create([
                'tenant_id' => $tenant->id,
                'user_id' => $teacherUser->id,
                'full_name' => $teacherUser->name,
            ]);

            $rombel = Rombel::create([
                'tenant_id' => $tenant->id,
                'academic_year_id' => $academicYear->id,
                'grade_level_id' => $gradeLevel->id,
                'name' => 'Kelas 5 Bench',
                'teacher_id' => $teacherProfile->id,
                'status' => 'ACTIVE'
            ]);

            // 1 CP
            $cp = LearningOutcome::create([
                'tenant_id' => $tenant->id,
                'curriculum_id' => $curriculum->id,
                'phase_id' => $phase->id,
                'subject_id' => $subject->id,
                'code' => 'CP_BENCH_1',
                'name' => 'Capaian Pembelajaran Matematika'
            ]);

            // 2 ATPs
            $atp1 = LearningPath::create([
                'tenant_id' => $tenant->id,
                'curriculum_id' => $curriculum->id,
                'subject_id' => $subject->id,
                'phase_id' => $phase->id,
                'code' => 'ATP_BENCH_1',
                'name' => 'Alur Bilangan'
            ]);
            $lpVer1 = LearningPathVersion::create([
                'tenant_id' => $tenant->id,
                'learning_path_id' => $atp1->id,
                'major_version' => 1,
                'minor_version' => 0,
                'is_current' => true
            ]);

            $atp2 = LearningPath::create([
                'tenant_id' => $tenant->id,
                'curriculum_id' => $curriculum->id,
                'subject_id' => $subject->id,
                'phase_id' => $phase->id,
                'code' => 'ATP_BENCH_2',
                'name' => 'Alur Geometri'
            ]);
            $lpVer2 = LearningPathVersion::create([
                'tenant_id' => $tenant->id,
                'learning_path_id' => $atp2->id,
                'major_version' => 1,
                'minor_version' => 0,
                'is_current' => true
            ]);

            // 5 TPs
            $tps = [];
            for ($i = 1; $i <= 5; $i++) {
                $tp = LearningObjective::create([
                    'tenant_id' => $tenant->id,
                    'learning_outcome_id' => $cp->id,
                    'code' => "TP_BENCH_{$i}",
                    'title' => "Tujuan Pembelajaran {$i}",
                    'description' => "Deskripsi TP {$i}"
                ]);
                $tps[] = $tp;

                KktpVersion::create([
                    'tenant_id' => $tenant->id,
                    'learning_objective_id' => $tp->id,
                    'version' => 1,
                    'threshold' => 70.00
                ]);
            }

            // Attach TP1, TP2, TP3 to ATP1; TP4, TP5 to ATP2
            $lpVer1->objectives()->attach([
                $tps[0]->id => ['tenant_id' => $tenant->id, 'id' => (string)Str::uuid(), 'order_index' => 1],
                $tps[1]->id => ['tenant_id' => $tenant->id, 'id' => (string)Str::uuid(), 'order_index' => 2],
                $tps[2]->id => ['tenant_id' => $tenant->id, 'id' => (string)Str::uuid(), 'order_index' => 3],
            ]);
            $lpVer2->objectives()->attach([
                $tps[3]->id => ['tenant_id' => $tenant->id, 'id' => (string)Str::uuid(), 'order_index' => 1],
                $tps[4]->id => ['tenant_id' => $tenant->id, 'id' => (string)Str::uuid(), 'order_index' => 2],
            ]);

            // Create 50 Question Bank items
            $bankQuestions = [];
            for ($q = 1; $q <= 50; $q++) {
                $qModel = Question::create([
                    'tenant_id' => $tenant->id,
                    'owner_type' => 'TENANT',
                    'question_code' => "Q_BENCH_{$q}",
                    'status' => 'PUBLISHED',
                    'visibility' => 'PUBLIC',
                    'curriculum_id' => $curriculum->id,
                    'subject_id' => $subject->id,
                    'phase_id' => $phase->id,
                    'grade_level_id' => $gradeLevel->id,
                    'created_by' => $teacherUser->id,
                ]);

                $qVer = QuestionVersion::create([
                    'tenant_id' => $tenant->id,
                    'question_id' => $qModel->id,
                    'major_version' => 1,
                    'minor_version' => 0,
                    'question_type' => 'MC',
                    'stem' => "Pertanyaan Bank #{$q}",
                    'explanation' => "Pembahasan Bank #{$q}",
                    'default_points' => 2.00,
                    'status' => 'PUBLISHED',
                    'is_current' => true,
                    'created_by' => $teacherUser->id,
                ]);

                $bankQuestions[] = ['question' => $qModel, 'version' => $qVer];
            }

            // 10 Assessments (2 mapped to each TP)
            $assessments = [];
            $exams = [];
            for ($i = 1; $i <= 10; $i++) {
                $tpIndex = (int)(($i - 1) / 2);
                $tp = $tps[$tpIndex];

                $ass = Assessment::create([
                    'tenant_id' => $tenant->id,
                    'academic_year_id' => $academicYear->id,
                    'semester_id' => $semester->id,
                    'curriculum_id' => $curriculum->id,
                    'subject_id' => $subject->id,
                    'title' => "Penilaian {$i}",
                    'assessment_type' => ($i % 2 == 1) ? 'FORMATIVE' : 'SUMMATIVE',
                    'status' => 'PUBLISHED',
                    'weight' => 2.00,
                    'created_by' => $teacherUser->id
                ]);
                $ass->learningObjectives()->attach($tp->id, ['tenant_id' => $tenant->id, 'id' => (string)Str::uuid()]);
                $assessments[] = $ass;

                $exam = Exam::create([
                    'tenant_id' => $tenant->id,
                    'assessment_id' => $ass->id,
                    'title' => "Ujian Assessment {$i}",
                    'duration_seconds' => 3600,
                    'status' => 'LOCKED',
                    'created_by' => $teacherUser->id,
                    'start_at' => now()->subDays(5),
                    'end_at' => now()->subDays(1),
                    'locked_at' => now()->subDays(5),
                    'show_result' => true
                ]);
                $exams[] = $exam;

                // 50 Question Snapshots per exam = 500 Snapshots total
                for ($q = 1; $q <= 50; $q++) {
                    $bq = $bankQuestions[$q - 1];
                    ExamQuestionSnapshot::create([
                        'tenant_id' => $tenant->id,
                        'exam_id' => $exam->id,
                        'source_question_id' => $bq['question']->id,
                        'source_question_version_id' => $bq['version']->id,
                        'sequence' => $q,
                        'points' => 2.00,
                        'snapshot_stem' => "Pertanyaan #{$q} untuk Exam {$i}",
                        'snapshot_explanation' => "Pembahasan #{$q}",
                        'snapshot_options' => json_encode([
                            ['id' => 'opt_a', 'label' => 'A', 'text' => 'Option A'],
                            ['id' => 'opt_b', 'label' => 'B', 'text' => 'Option B'],
                        ]),
                    ]);
                }
            }

            // 100 Students
            $students = [];
            for ($s = 1; $s <= 100; $s++) {
                $student = User::create([
                    'tenant_id' => $tenant->id,
                    'name' => sprintf('Siswa Benchmark %03d', $s),
                    'email' => sprintf('siswa%03d@bench.sch.id', $s),
                    'password' => bcrypt('password'),
                ]);
                $student->roles()->attach($siswaRole);

                $profile = StudentProfile::create([
                    'tenant_id' => $tenant->id,
                    'user_id' => $student->id,
                    'nis' => sprintf('NIS_%03d', $s),
                    'nisn' => sprintf('NISN_%03d', $s),
                    'full_name' => $student->name,
                ]);
                $profile->rombels()->attach($rombel->id, ['id' => (string)Str::uuid(), 'tenant_id' => $tenant->id]);

                $students[] = $student;

                // For each student x 10 exams: create attempt & finalized score
                foreach ($exams as $examIdx => $exam) {
                    $participant = ExamParticipant::create([
                        'tenant_id' => $tenant->id,
                        'exam_id' => $exam->id,
                        'user_id' => $student->id,
                    ]);

                    $attempt = ExamAttempt::create([
                        'tenant_id' => $tenant->id,
                        'exam_id' => $exam->id,
                        'participant_id' => $participant->id,
                        'attempt_number' => 1,
                        'status' => 'SUBMITTED',
                        'started_at' => now()->subHours(2),
                        'submitted_at' => now()->subHour(),
                    ]);

                    $scoreVal = 70.0 + (($s + $examIdx) % 30);
                    $score = ExamAttemptScore::create([
                        'tenant_id' => $tenant->id,
                        'attempt_id' => $attempt->id,
                        'exam_id' => $exam->id,
                        'student_id' => $student->id,
                        'scoring_version' => 1,
                        'raw_score' => $scoreVal,
                        'max_score' => 100.0,
                        'percentage' => $scoreVal,
                        'grading_status' => 'FINALIZED',
                        'finalized_at' => now(),
                    ]);

                    ExamAttemptScoreCurrent::create([
                        'tenant_id' => $tenant->id,
                        'attempt_id' => $attempt->id,
                        'exam_id' => $exam->id,
                        'student_id' => $student->id,
                        'current_score_id' => $score->id,
                        'scoring_version' => 1,
                    ]);
                }
            }

            return [
                'tenant' => $tenant,
                'adminUser' => $adminUser,
                'academicYear' => $academicYear,
                'semester' => $semester,
                'subject' => $subject,
                'rombel' => $rombel,
                'cp' => $cp,
                'atps' => [$atp1, $atp2],
                'tps' => $tps,
                'exams' => $exams,
                'students' => $students,
            ];
        });
    }

    private function runBatchAchievementBenchmark(array $workload, int $runs): array
    {
        $service = app(CurriculumAchievementService::class);
        $students = $workload['students'];
        $subject = $workload['subject'];
        $academicYear = $workload['academicYear'];
        $semester = $workload['semester'];
        $rombel = $workload['rombel'];

        $runsData = [];

        for ($r = 1; $r <= $runs; $r++) {
            gc_collect_cycles();
            $service->clearCache();
            DB::table('tp_assessment_contributions')->delete();
            DB::table('atp_tp_sources')->delete();
            DB::table('cp_atp_sources')->delete();
            DB::table('tp_achievements')->delete();
            DB::table('atp_achievements')->delete();
            DB::table('cp_achievements')->delete();

            $start = microtime(true);

            DB::transaction(function () use ($students, $subject, $academicYear, $semester, $rombel, $service) {
                foreach ($students as $student) {
                    $service->calculateStudentAchievements(
                        $student, $subject, $academicYear, $semester, $rombel, 1
                    );
                }
            });

            $end = microtime(true);
            $peakMem = memory_get_peak_usage(true) / 1024 / 1024;
            $durationMs = ($end - $start) * 1000;

            $runsData[] = [
                'run' => $r,
                'duration_ms' => round($durationMs, 2),
                'peak_memory_mb' => round($peakMem, 2),
            ];

            $this->line(sprintf("  Run %d: %8.2f ms | Peak Memory: %6.2f MB", $r, $durationMs, $peakMem));
        }

        return $runsData;
    }

    private function runPdfExportBenchmark(array $workload, int $runs): array
    {
        $reportController = app(ReportController::class);
        $exam = $workload['exams'][0];
        $rombel = $workload['rombel'];
        $adminUser = $workload['adminUser'];
        $adminUser->load('roles');

        app('auth')->setUser($adminUser);

        // Warm-up run for Dompdf (class loading, CSS/font engine initialization) - excluded from official stats
        $this->line("  [Warm-up] Initializing Dompdf & rendering warm-up PDF...");
        $warmupStart = microtime(true);
        $warmupReq = Request::create("/api/v1/reports/exams/{$exam->id}/export/pdf", 'GET', [
            'rombel_id' => $rombel->id,
        ]);
        $warmupReq->setUserResolver(fn() => $adminUser);
        $warmupResp = $reportController->exportPdf($warmupReq, $exam);
        $warmupBytes = strlen($warmupResp->getContent());
        $warmupDuration = round((microtime(true) - $warmupStart) * 1000, 2);
        $this->line(sprintf("  [Warm-up] Completed in %8.2f ms | PDF Size: %d bytes", $warmupDuration, $warmupBytes));

        $runsData = [];

        for ($r = 1; $r <= $runs; $r++) {
            gc_collect_cycles();
            $start = microtime(true);

            $request = Request::create("/api/v1/reports/exams/{$exam->id}/export/pdf", 'GET', [
                'rombel_id' => $rombel->id,
            ]);
            $request->setUserResolver(fn() => $adminUser);

            $response = $reportController->exportPdf($request, $exam);
            $content = $response->getContent();

            $end = microtime(true);
            $peakMem = memory_get_peak_usage(true) / 1024 / 1024;
            $durationMs = ($end - $start) * 1000;
            $bytes = strlen($content);

            $runsData[] = [
                'run' => $r,
                'duration_ms' => round($durationMs, 2),
                'peak_memory_mb' => round($peakMem, 2),
                'pdf_bytes' => $bytes,
            ];

            $this->line(sprintf("  Run %d: %8.2f ms | Peak Memory: %6.2f MB | PDF Size: %d bytes", $r, $durationMs, $peakMem, $bytes));
        }

        return $runsData;
    }

    private function buildSummaryReport(array $op1Results, array $op2Results, int $runs): array
    {
        $op1Stats = $this->calculateStats($op1Results);
        $op2Stats = $this->calculateStats($op2Results);

        $maxPeakMemory = max($op1Stats['peak_memory_mb_max'], $op2Stats['peak_memory_mb_max']);

        $evals = [
            'batch_calculation_duration' => [
                'threshold' => 'Mean <= 2000.0 ms',
                'actual' => sprintf('%.2f ms', $op1Stats['duration_ms_mean']),
                'passed' => $op1Stats['duration_ms_mean'] <= 2000.0,
            ],
            'pdf_export_duration' => [
                'threshold' => 'Mean <= 3000.0 ms',
                'actual' => sprintf('%.2f ms', $op2Stats['duration_ms_mean']),
                'passed' => $op2Stats['duration_ms_mean'] <= 3000.0,
            ],
            'peak_memory_consumption' => [
                'threshold' => '<= 128.0 MB',
                'actual' => sprintf('%.2f MB', $maxPeakMemory),
                'passed' => $maxPeakMemory <= 128.0,
            ],
            'batch_calculation_consistency' => [
                'threshold' => 'Duration CV <= 15.0%',
                'actual' => sprintf('%.2f%%', $op1Stats['duration_ms_cv']),
                'passed' => $op1Stats['duration_ms_cv'] <= 15.0,
            ],
            'pdf_export_consistency' => [
                'threshold' => 'Duration CV <= 15.0%',
                'actual' => sprintf('%.2f%%', $op2Stats['duration_ms_cv']),
                'passed' => $op2Stats['duration_ms_cv'] <= 15.0,
            ],
        ];

        $allPassed = true;
        foreach ($evals as $ev) {
            if (!$ev['passed']) {
                $allPassed = false;
                break;
            }
        }

        return [
            'benchmark_name' => 'Phase 7D.5 Performance Benchmark',
            'timestamp' => now()->toIso8601String(),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'workload_definition' => [
                'tenant_count' => 1,
                'rombel_count' => 1,
                'student_count' => 100,
                'exam_count' => 10,
                'question_snapshot_count' => 500,
                'cp_count' => 1,
                'atp_count' => 2,
                'tp_count' => 5,
                'runs_per_operation' => $runs,
            ],
            'operation_1_batch_achievement_calculation' => [
                'runs' => $op1Results,
                'stats' => $op1Stats,
            ],
            'operation_2_pdf_report_export' => [
                'runs' => $op2Results,
                'stats' => $op2Stats,
            ],
            'governance_threshold_evaluations' => $evals,
            'overall_status' => $allPassed ? 'PASSED' : 'FAILED',
        ];
    }

    private function calculateStats(array $runsData): array
    {
        $durations = array_column($runsData, 'duration_ms');
        $memories = array_column($runsData, 'peak_memory_mb');
        $n = count($durations);

        $meanDur = array_sum($durations) / $n;

        $variance = 0.0;
        if ($n > 1) {
            foreach ($durations as $d) {
                $variance += pow($d - $meanDur, 2);
            }
            $variance /= ($n - 1);
        }
        $stdDev = sqrt($variance);
        $cv = $meanDur > 0 ? ($stdDev / $meanDur) * 100 : 0.0;

        return [
            'duration_ms_runs' => $durations,
            'duration_ms_mean' => round($meanDur, 2),
            'duration_ms_std_dev' => round($stdDev, 2),
            'duration_ms_cv' => round($cv, 2),
            'duration_ms_min' => round(min($durations), 2),
            'duration_ms_max' => round(max($durations), 2),
            'peak_memory_mb_mean' => round(array_sum($memories) / $n, 2),
            'peak_memory_mb_max' => round(max($memories), 2),
        ];
    }

    private function printSummaryTable(array $summary): void
    {
        $this->info("\n=================================================");
        $this->info("BENCHMARK SUMMARY & GOVERNANCE EVALUATION");
        $this->info("=================================================");

        $op1 = $summary['operation_1_batch_achievement_calculation']['stats'];
        $op2 = $summary['operation_2_pdf_report_export']['stats'];

        $this->table(
            ['Metric', 'Batch Achievement Calc', '100-Student PDF Export'],
            [
                ['Mean Duration', "{$op1['duration_ms_mean']} ms", "{$op2['duration_ms_mean']} ms"],
                ['Std Dev', "{$op1['duration_ms_std_dev']} ms", "{$op2['duration_ms_std_dev']} ms"],
                ['Coeff of Variation (CV)', "{$op1['duration_ms_cv']}%", "{$op2['duration_ms_cv']}%"],
                ['Min Duration', "{$op1['duration_ms_min']} ms", "{$op2['duration_ms_min']} ms"],
                ['Max Duration', "{$op1['duration_ms_max']} ms", "{$op2['duration_ms_max']} ms"],
                ['Peak Memory (Max)', "{$op1['peak_memory_mb_max']} MB", "{$op2['peak_memory_mb_max']} MB"],
            ]
        );

        $this->info("\n--- Threshold Evaluations ---");
        $evalRows = [];
        foreach ($summary['governance_threshold_evaluations'] as $key => $eval) {
            $evalRows[] = [
                str_replace('_', ' ', strtoupper($key)),
                $eval['threshold'],
                $eval['actual'],
                $eval['passed'] ? 'PASS' : 'FAIL',
            ];
        }

        $this->table(['Check', 'Governance Threshold', 'Actual Result', 'Status'], $evalRows);

        $statusStr = $summary['overall_status'];
        if ($statusStr === 'PASSED') {
            $this->info("\n[OVERALL STATUS] PASSED - All governance benchmark thresholds satisfied!");
        } else {
            $this->error("\n[OVERALL STATUS] FAILED - One or more governance benchmark thresholds not met.");
        }
    }
}
