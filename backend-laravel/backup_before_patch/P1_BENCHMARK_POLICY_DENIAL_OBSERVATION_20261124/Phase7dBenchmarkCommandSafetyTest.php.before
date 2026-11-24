<?php

namespace Tests\Feature;

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

        $observedTemporarySchemas = 0;
        $policyDecisions = [];
        Gate::after(function ($user, string $ability, ?bool $result) use (&$policyDecisions): void {
            if ($ability === 'viewExamResults') {
                $policyDecisions[] = $result;
            }
        });

        // Omit the official grant only in this failure-path test. The real
        // ReportController and ReportPolicy must deny export; no policy is mocked.
        $bootstrapper = \Mockery::mock(RolePermissionBootstrapper::class);
        $bootstrapper->shouldReceive('bootstrap')->once()->andReturnNull();
        $this->app->instance(RolePermissionBootstrapper::class, $bootstrapper);

        $thrown = false;
        $exitCode = 0;
        try {
            $exitCode = Artisan::call('benchmark:phase7d', [
                '--driver' => 'mysql',
                '--runs' => 1,
                '--allow-mysql' => true,
                '--json' => true,
            ]);
        } catch (\Throwable) {
            $thrown = true;
        }

        $this->assertTrue($thrown || $exitCode !== 0, 'The real Policy denial must fail the benchmark command.');
        $this->assertSame([false], $policyDecisions, 'The real ReportPolicy must deny export because report.view was not granted.');
        $this->assertSame(1, $observedTemporarySchemas, 'The Policy denial must occur after the disposable schema exists.');

        $after = DB::connection('mysql')->select(
            "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME LIKE 'cbt_phase7d_%'"
        );
        $this->assertSame([], $after, 'The temporary schema must be absent after the failed benchmark.');
    }


}
