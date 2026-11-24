<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Policies\ExamPolicy;
use App\Services\RolePermissionBootstrapper;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class Phase7dBenchmarkCommandSafetyTest extends TestCase
{
    private ?string $previousOptIn = null;

    protected function setUp(): void
    {
        parent::setUp();

        $value = getenv('CBT_BENCHMARK_ALLOW_MYSQL');
        $this->previousOptIn = $value === false ? null : $value;

        config([
            'app.env' => 'testing',
            'database.default' => 'mysql',
            'database.connections.mysql.driver' => 'mysql',
            'database.connections.mysql.host' => '127.0.0.1',
            'database.connections.mysql.database' => env('DB_DATABASE', 'cbt_ci_test'),
        ]);
    }

    protected function tearDown(): void
    {
        $this->setOptIn($this->previousOptIn);
        parent::tearDown();
    }

    public function test_benchmark_requires_explicit_command_flag(): void
    {
        $this->setOptIn('1');

        $exitCode = Artisan::call('benchmark:phase7d', [
            '--driver' => 'mysql',
            '--runs' => 1,
        ]);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('--allow-mysql', Artisan::output());
    }

    public function test_benchmark_requires_environment_opt_in(): void
    {
        $this->setOptIn(null);

        $exitCode = Artisan::call('benchmark:phase7d', [
            '--driver' => 'mysql',
            '--runs' => 1,
            '--allow-mysql' => true,
        ]);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('CBT_BENCHMARK_ALLOW_MYSQL=1', Artisan::output());
    }

    public function test_benchmark_rejects_remote_mysql_host(): void
    {
        $this->setOptIn('1');
        config(['database.connections.mysql.host' => 'db.example.invalid']);

        $exitCode = Artisan::call('benchmark:phase7d', [
            '--driver' => 'mysql',
            '--runs' => 1,
            '--allow-mysql' => true,
        ]);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('MySQL lokal', Artisan::output());
    }

    public function test_benchmark_rejects_database_without_disposable_prefix(): void
    {
        $this->setOptIn('1');
        config(['database.connections.mysql.database' => 'cbt_madrasah_prod']);

        $exitCode = Artisan::call('benchmark:phase7d', [
            '--driver' => 'mysql',
            '--runs' => 1,
            '--allow-mysql' => true,
        ]);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('prefix cbt_ci_', Artisan::output());
    }

    public function test_benchmark_rejects_production_environment_even_with_opt_in(): void
    {
        $this->setOptIn('1');
        $this->app['env'] = 'production';

        $exitCode = Artisan::call('benchmark:phase7d', [
            '--driver' => 'mysql',
            '--runs' => 1,
            '--allow-mysql' => true,
        ]);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('APP_ENV=local/testing', Artisan::output());
    }

    private function setOptIn(?string $value): void
    {
        if ($value === null) {
            putenv('CBT_BENCHMARK_ALLOW_MYSQL');
            unset($_ENV['CBT_BENCHMARK_ALLOW_MYSQL'], $_SERVER['CBT_BENCHMARK_ALLOW_MYSQL']);
            return;
        }

        putenv('CBT_BENCHMARK_ALLOW_MYSQL=' . $value);
        $_ENV['CBT_BENCHMARK_ALLOW_MYSQL'] = $value;
        $_SERVER['CBT_BENCHMARK_ALLOW_MYSQL'] = $value;
    }
    public function test_temporary_mysql_schema_is_removed_when_pdf_export_fails(): void
    {
        $this->setOptIn('1');
        DB::purge('mysql');

        $control = DB::connection('mysql');
        $before = $control->select(
            "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME LIKE 'cbt_phase7d_%'"
        );
        $this->assertSame([], $before, 'A previous benchmark schema must not be left behind.');

        $gate = Gate::getFacadeRoot();
        $afterCallbacksProperty = new \ReflectionProperty($gate, 'afterCallbacks');
        $originalAfterCallbacks = $afterCallbacksProperty->getValue($gate);
        $policyObservations = [];
        $schemaCountsAtPolicyEvaluation = [];
        Gate::after(function ($user, string $ability, $result, array $arguments) use (&$policyObservations, &$schemaCountsAtPolicyEvaluation, $control): void {
            if ($ability === 'viewExamResults') {
                $policyObservations[] = [
                    'user' => $user,
                    'result' => $result,
                    'has_report_view' => $user->hasPermission('report.view'),
                    'arguments' => $arguments,
                ];
                $schemasAtDecision = $control->select(
                    "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME LIKE 'cbt_phase7d_%'"
                );
                $schemaCountsAtPolicyEvaluation[] = count($schemasAtDecision);
            }
        });

        $originalBootstrapper = app(RolePermissionBootstrapper::class);
        $bootstrapper = \Mockery::mock(RolePermissionBootstrapper::class);
        $bootstrapper->shouldReceive('bootstrap')->once()->andReturnNull();
        $this->app->instance(RolePermissionBootstrapper::class, $bootstrapper);

        $control->flushQueryLog();
        $control->enableQueryLog();
        $exception = null;

        try {
            Artisan::call('benchmark:phase7d', [
                '--driver' => 'mysql',
                '--runs' => 1,
                '--allow-mysql' => true,
                '--json' => true,
            ]);
        } catch (\Throwable $caught) {
            $exception = $caught;
        } finally {
            $control->disableQueryLog();
            $this->app->instance(RolePermissionBootstrapper::class, $originalBootstrapper);
            $afterCallbacksProperty->setValue($gate, $originalAfterCallbacks);
        }

        $queries = $control->getQueryLog();
        $createdSchemas = [];
        $droppedSchemas = [];
        foreach ($queries as $query) {
            $sql = $query['query'];
            if (preg_match('/^\s*CREATE\s+DATABASE\s+`(cbt_phase7d_[a-z0-9]+)`/i', $sql, $matches) === 1) {
                $createdSchemas[] = $matches[1];
            }
            if (preg_match('/^\s*DROP\s+DATABASE(?:\s+IF\s+EXISTS)?\s+`(cbt_phase7d_[a-z0-9]+)`/i', $sql, $matches) === 1) {
                $droppedSchemas[] = $matches[1];
            }
        }

        $this->assertCount(1, $createdSchemas, 'This invocation must create exactly one temporary benchmark schema.');
        $schema = $createdSchemas[0];
        $this->assertMatchesRegularExpression('/^cbt_phase7d_[a-z0-9]+$/', $schema);
        $this->assertSame([$schema], $droppedSchemas, 'The invocation must drop the exact schema it created.');

        $this->assertInstanceOf(\Illuminate\Auth\Access\AuthorizationException::class, $exception);

        $this->assertStringContainsString('Running migrations on isolated MySQL benchmark database...', Artisan::output());
        $this->assertStringContainsString('Workload seeded in ', Artisan::output());

        $this->assertInstanceOf(ExamPolicy::class, Gate::getPolicyFor(Exam::class));
        $this->assertCount(1, $policyObservations, 'The real report authorization must be evaluated exactly once.');
        $this->assertSame([1], $schemaCountsAtPolicyEvaluation, 'The disposable schema must still exist when the real controller policy denies export.');
        $observation = $policyObservations[0];
        $this->assertFalse(
            $observation['has_report_view'],
            'The benchmark teacher must not have report.view in this denial-path test.'
        );
        $this->assertFalse($observation['result'], 'The real ReportPolicy must deny the report ability.');
        $this->assertCount(1, $observation['arguments']);
        $this->assertInstanceOf(Exam::class, $observation['arguments'][0]);

        $after = DB::connection('mysql')->select(
            'SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?',
            [$schema]
        );
        $this->assertSame([], $after, 'INFORMATION_SCHEMA must prove the temporary schema is absent after cleanup.');
    }


}
