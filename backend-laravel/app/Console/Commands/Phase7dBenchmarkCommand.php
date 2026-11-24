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
use App\Models\MultiCurriculumVersion;
use App\Models\LearningOutcome;
use App\Models\TeacherSubjectAssignment;
use App\Models\TenantCurriculumRegistration;
use App\Services\RolePermissionBootstrapper;
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
    protected $signature = 'benchmark:phase7d {--runs=5 : Number of iterations} {--json : Output JSON report} {--driver=mysql : Disposable benchmark database driver} {--allow-mysql : Explicitly permit a local disposable MySQL database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Phase 7D.5 benchmark harness using a disposable local MySQL database';

    private string $benchmarkDatabaseVersion = '';

    public function handle(): int
    {
        $runs = (int) $this->option('runs');
        if ($runs < 1 || $runs > 20) {
            $this->error('Jumlah run harus berada pada rentang 1–20.');
            return self::FAILURE;
        }

        $safetyError = $this->validateMysqlBenchmarkSafety();
        if ($safetyError !== null) {
            $this->error($safetyError);
            return self::FAILURE;
        }

        $mysqlConfig = config('database.connections.mysql');
        $originalDefaultConnection = config('database.default');
        $originalBenchmarkConnection = config('database.connections.phase7d_benchmark');
        $tenantContext = app(TenantContext::class);
        $previousTenantId = $tenantContext->getTenantId();
        $authGuard = auth();
        $previousAuthUser = $authGuard->user();

        $temporaryDatabaseName = 'cbt_phase7d_' . Str::lower(Str::random(12));
        $benchmarkConnectionName = 'phase7d_benchmark';
        $databaseCreated = false;
        $benchmarkFailure = null;
        $cleanupFailure = null;
        $op1Results = [];
        $op2Results = [];

        $this->info('=================================================');
        $this->info('Phase 7D.5 Disposable MySQL Benchmark Harness');
        $this->info("Runs per operation: {$runs}");
        $this->info('Database: temporary MySQL schema; production database is never migrated.');
        $this->info('=================================================');

        try {
            DB::connection('mysql')->statement(
                "CREATE DATABASE `{$temporaryDatabaseName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
            );
            $databaseCreated = true;

            $benchmarkConnectionConfig = $mysqlConfig;
            $benchmarkConnectionConfig['database'] = $temporaryDatabaseName;
            unset($benchmarkConnectionConfig['url']);

            config([
                "database.connections.{$benchmarkConnectionName}" => $benchmarkConnectionConfig,
                'database.default' => $benchmarkConnectionName,
            ]);
            DB::purge($benchmarkConnectionName);

            $this->benchmarkDatabaseVersion = (string) DB::connection($benchmarkConnectionName)
                ->selectOne('SELECT VERSION() AS version')
                ->version;

            $this->info('Running migrations on isolated MySQL benchmark database...');
            $migrationExitCode = Artisan::call('migrate:fresh', [
                '--database' => $benchmarkConnectionName,
                '--force' => true,
            ]);
            if ($migrationExitCode !== 0) {
                throw new \RuntimeException('Benchmark MySQL migration failed.');
            }

            $this->info('Bootstrapping official curriculum reference data...');
            app(\Database\Seeders\CurriculumReferenceSourceSeeder::class)->run();
            app(\Database\Seeders\CurriculumRegistryBootstrapSeeder::class)->run();

            $this->info('Seeding representative workload...');
            $startTime = microtime(true);
            $workload = $this->seedWorkload();
            $seedDuration = round((microtime(true) - $startTime) * 1000, 2);
            $this->info("Workload seeded in {$seedDuration} ms.");

            $this->info("\n--- Operation 1: Batch Achievement Calculation (100 Students) ---");
            $op1Results = $this->runBatchAchievementBenchmark($workload, $runs);

            $this->info("\n--- Operation 2: Combined 100-student PDF Report Export ---");
            $op2Results = $this->runPdfExportBenchmark($workload, $runs);
        } catch (\Throwable $exception) {
            $benchmarkFailure = $exception;
        } finally {
            try {
                DB::disconnect($benchmarkConnectionName);
                DB::purge($benchmarkConnectionName);
            } catch (\Throwable) {
                // Cleanup continues; the control MySQL connection remains separate.
            }

            config([
                'database.default' => $originalDefaultConnection,
                "database.connections.{$benchmarkConnectionName}" => $originalBenchmarkConnection,
            ]);

            try {
                DB::purge($benchmarkConnectionName);
            } catch (\Throwable) {
                // Preserve original application connection configuration.
            }

            // Always attempt DROP, even if CREATE DATABASE reported an error after server-side creation.
            try {
                DB::connection('mysql')->statement("DROP DATABASE IF EXISTS `{$temporaryDatabaseName}`");
                $remaining = DB::connection('mysql')->select(
                    'SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?',
                    [$temporaryDatabaseName]
                );
                if ($remaining !== []) {
                    throw new \RuntimeException('Temporary benchmark database still exists after cleanup.');
                }
                $this->info('Temporary MySQL database removed; absence verified.');
            } catch (\Throwable $exception) {
                $cleanupFailure = $exception;
            }

            try {
                $tenantContext->setTenantId($previousTenantId);
            } catch (\Throwable) {
                // Do not prevent database cleanup.
            }

            try {
                if ($previousAuthUser !== null) {
                    $authGuard->setUser($previousAuthUser);
                } elseif (method_exists($authGuard, 'forgetUser')) {
                    $authGuard->forgetUser();
                }
            } catch (\Throwable) {
                // Do not prevent database cleanup.
            }
        }

        if ($cleanupFailure !== null) {
            throw new \RuntimeException(
                'Benchmark temporary database cleanup failed.',
                0,
                $cleanupFailure
            );
        }

        if ($benchmarkFailure !== null) {
            throw $benchmarkFailure;
        }

        $summary = $this->buildSummaryReport($op1Results, $op2Results, $runs);

        if ($this->option('json')) {
            $this->line(json_encode($summary, JSON_PRETTY_PRINT));
        } else {
            $this->printSummaryTable($summary);
        }

        $reportDir = storage_path('reports');
        if (!file_exists($reportDir)) {
            mkdir($reportDir, 0755, true);
        }
        file_put_contents($reportDir . '/phase7d_benchmark_report.json', json_encode($summary, JSON_PRETTY_PRINT));
        $this->info("\nBenchmark report saved to: " . $reportDir . '/phase7d_benchmark_report.json');

        return 0;
    }

    private function validateMysqlBenchmarkSafety(): ?string
    {
        if ($this->option('driver') !== 'mysql') {
            return 'Benchmark hanya mendukung MySQL 8; SQLite tidak digunakan karena migrasi target memakai constraint MySQL.';
        }

        if (!$this->option('allow-mysql')) {
            return 'Benchmark memerlukan opt-in eksplisit --allow-mysql.';
        }

        if (env('CBT_BENCHMARK_ALLOW_MYSQL') !== '1') {
            return 'Set CBT_BENCHMARK_ALLOW_MYSQL=1 hanya pada lingkungan test/local disposable.';
        }

        if (!app()->environment(['local', 'testing'])) {
            return 'Benchmark dilarang berjalan di luar APP_ENV=local/testing.';
        }

        if (config('database.default') !== 'mysql') {
            return 'Default DB_CONNECTION harus mysql dan menunjuk ke database disposable.';
        }

        $mysqlConfig = config('database.connections.mysql', []);
        if (($mysqlConfig['driver'] ?? null) !== 'mysql') {
            return 'Konfigurasi koneksi mysql tidak tersedia.';
        }

        $host = strtolower((string) ($mysqlConfig['host'] ?? ''));
        if (!in_array($host, ['localhost', '127.0.0.1', '::1', 'mysql'], true)) {
            return 'Benchmark hanya diizinkan pada MySQL lokal; remote database ditolak.';
        }

        $baseDatabase = (string) ($mysqlConfig['database'] ?? '');
        if (preg_match('/^(cbt_ci_|cbt_test_|cbt_benchmark_|testing)/i', $baseDatabase) !== 1) {
            return 'Nama database dasar harus memakai prefix cbt_ci_, cbt_test_, cbt_benchmark_, atau testing.';
        }

        return null;
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
            app(RolePermissionBootstrapper::class)->bootstrap();

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

            $globalCurriculumVersion = MultiCurriculumVersion::query()
                ->where('tenant_id', Tenant::SYSTEM_TENANT_ID)
                ->where('version_code', 'KMA-1503-2025')
                ->firstOrFail();

            $curriculumRegistration = TenantCurriculumRegistration::query()->create([
                'tenant_id' => $tenant->id,
                'multi_curriculum_version_id' => $globalCurriculumVersion->id,
                'curriculum_owner_id' => Tenant::SYSTEM_TENANT_ID,
                'status' => 'ACTIVE',
                'registered_at' => now(),
            ]);

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
                'employment_status' => 'PNS',
                'nip' => '199001012026041001',
                'employee_identifier' => '199001012026041001',
            ]);

            $rombel = Rombel::create([
                'tenant_id' => $tenant->id,
                'academic_year_id' => $academicYear->id,
                'grade_level_id' => $gradeLevel->id,
                'name' => 'Kelas 5 Bench',
                'teacher_id' => $teacherProfile->id,
                'status' => 'ACTIVE'
            ]);

            $assignment = TeacherSubjectAssignment::query()->create([
                'teacher_profile_id' => $teacherProfile->id,
                'subject_id' => $subject->id,
                'rombel_id' => $rombel->id,
                'academic_year_id' => $academicYear->id,
                'semester_id' => $semester->id,
                'starts_at' => now()->subDay()->toDateString(),
                'ends_at' => now()->addYear()->toDateString(),
                'status' => 'ACTIVE',
                'multi_curriculum_version_id' => $globalCurriculumVersion->id,
                'curriculum_owner_id' => Tenant::SYSTEM_TENANT_ID,
                'tenant_curriculum_registration_id' => $curriculumRegistration->id,
                'curriculum_context_snapshot' => [
                    'curriculum_version_id' => $globalCurriculumVersion->id,
                    'curriculum_owner_id' => Tenant::SYSTEM_TENANT_ID,
                    'tenant_curriculum_registration_id' => $curriculumRegistration->id,
                    'version_code' => $globalCurriculumVersion->version_code,
                    'source_id' => $globalCurriculumVersion->source_id,
                ],
                'created_by' => $teacherUser->id,
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
                    'teacher_subject_assignment_id' => $assignment->id,
                    'curriculum_version_id' => $globalCurriculumVersion->id,
                    'curriculum_owner_id' => Tenant::SYSTEM_TENANT_ID,
                    'tenant_curriculum_registration_id' => $curriculumRegistration->id,
                    'curriculum_context_snapshot' => $assignment->curriculum_context_snapshot,
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
                    // Synthetic 10-digit NISN-format fixture; not a real student identity.
                    'nisn' => sprintf('%010d', 9000000000 + $s),
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
                'teacherUser' => $teacherUser,
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
        $teacherUser = $workload['teacherUser'];
        $teacherUser->load('roles');

        app('auth')->setUser($teacherUser);

        // Warm-up run for Dompdf (class loading, CSS/font engine initialization) - excluded from official stats
        $this->line("  [Warm-up] Initializing Dompdf & rendering warm-up PDF...");
        $warmupStart = microtime(true);
        $warmupReq = Request::create("/api/v1/reports/exams/{$exam->id}/export/pdf", 'GET', [
            'rombel_id' => $rombel->id,
        ]);
        $warmupReq->setUserResolver(fn() => $teacherUser);
        $warmupResp = $reportController->exportPdf($warmupReq, $exam);
        $warmupBytes = $this->persistAndVerifyPdfResponse($warmupResp, 'warm-up');
        $warmupDuration = round((microtime(true) - $warmupStart) * 1000, 2);
        $this->line(sprintf("  [Warm-up] Completed in %8.2f ms | PDF Size: %d bytes", $warmupDuration, $warmupBytes));

        $runsData = [];

        for ($r = 1; $r <= $runs; $r++) {
            gc_collect_cycles();
            $start = microtime(true);

            $request = Request::create("/api/v1/reports/exams/{$exam->id}/export/pdf", 'GET', [
                'rombel_id' => $rombel->id,
            ]);
            $request->setUserResolver(fn() => $teacherUser);

            $response = $reportController->exportPdf($request, $exam);
            $bytes = $this->persistAndVerifyPdfResponse($response, "run-{$r}");

            $end = microtime(true);
            $peakMem = memory_get_peak_usage(true) / 1024 / 1024;
            $durationMs = ($end - $start) * 1000;

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

    /**
     * Persist the actual controller response to a temporary .pdf file and verify
     * HTTP success, PDF signature, non-zero bytes, and file removal afterwards.
     */
    private function persistAndVerifyPdfResponse($response, string $label): int
    {
        if (!method_exists($response, 'getStatusCode') || $response->getStatusCode() !== 200) {
            throw new \RuntimeException("PDF {$label} did not return HTTP 200.");
        }

        $content = $response->getContent();
        if (!is_string($content) || $content === '') {
            throw new \RuntimeException("PDF {$label} response body is empty.");
        }

        $temporaryBase = tempnam(sys_get_temp_dir(), 'cbt-pdf-');
        if ($temporaryBase === false) {
            throw new \RuntimeException("Unable to allocate temporary PDF path for {$label}.");
        }

        $pdfPath = $temporaryBase . '.pdf';
        try {
            if (!rename($temporaryBase, $pdfPath)) {
                throw new \RuntimeException("Unable to prepare temporary PDF file for {$label}.");
            }

            $written = file_put_contents($pdfPath, $content, LOCK_EX);
            if ($written === false || $written !== strlen($content)) {
                throw new \RuntimeException("PDF {$label} could not be fully written to disk.");
            }

            clearstatcache(true, $pdfPath);
            $bytes = filesize($pdfPath);
            if ($bytes === false || $bytes <= 0) {
                throw new \RuntimeException("PDF {$label} file size must be greater than zero.");
            }

            $handle = fopen($pdfPath, 'rb');
            if ($handle === false) {
                throw new \RuntimeException("PDF {$label} file could not be reopened.");
            }
            try {
                $signature = fread($handle, 5);
            } finally {
                fclose($handle);
            }

            if ($signature !== '%PDF-') {
                throw new \RuntimeException("PDF {$label} file has an invalid PDF signature.");
            }

            $this->line("  Verified {$label}: HTTP 200 | PDF signature valid | file size {$bytes} bytes.");
            return $bytes;
        } finally {
            if (file_exists($pdfPath)) {
                unlink($pdfPath);
            }
            if (file_exists($temporaryBase)) {
                unlink($temporaryBase);
            }
            clearstatcache(true, $pdfPath);
            if (file_exists($pdfPath) || file_exists($temporaryBase)) {
                throw new \RuntimeException("Temporary PDF {$label} file cleanup could not be verified.");
            }
        }
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
            'database_driver' => 'mysql',
            'database_version' => $this->benchmarkDatabaseVersion,
            'benchmark_database_scope' => 'disposable temporary schema; dropped and absence verified after run',
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
