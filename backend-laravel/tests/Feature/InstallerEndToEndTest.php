<?php

namespace Tests\Feature;

use App\Services\InstallerService;
use App\Services\InstallerStateStore;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * One-time installer acceptance test for a dedicated empty MySQL 8 database.
 * Never points this test at a production or shared database.
 */
class InstallerEndToEndTest extends TestCase
{
    public function test_clean_installation_runs_from_empty_database_to_locked_state(): void
    {
        if (env('CBT_INSTALLER_E2E') !== '1') {
            $this->markTestSkipped('Set CBT_INSTALLER_E2E=1 only for an isolated disposable database.');
        }

        $host = (string) env('CBT_INSTALLER_E2E_DB_HOST', '127.0.0.1');
        $port = (int) env('CBT_INSTALLER_E2E_DB_PORT', 3306);
        $database = (string) env('CBT_INSTALLER_E2E_DB_DATABASE', '');
        $username = (string) env('CBT_INSTALLER_E2E_DB_USERNAME', '');
        $password = (string) env('CBT_INSTALLER_E2E_DB_PASSWORD', '');

        if (!str_starts_with($database, 'cbt_installer_test_')) {
            $this->fail('Installer E2E database name must start with cbt_installer_test_.');
        }
        if ($database === (string) config('database.connections.mysql.database')) {
            $this->fail('Installer E2E database must differ from the application default database.');
        }

        $service = app(InstallerService::class);
        $this->assertFalse($service->isInstalled(), 'Installer E2E cannot run when this app instance is already installed.');
        $inspection = $service->inspectDatabase(compact('host', 'port', 'database', 'username', 'password'));
        $this->assertTrue($inspection['success'], $inspection['message'] ?? 'Database inspection failed.');
        $this->assertTrue($inspection['is_empty'], 'Disposable database must be empty; this test never drops existing tables.');

        $paths = [
            base_path('.env'),
            storage_path('installed.lock'),
            app(InstallerStateStore::class)->statePath(),
            app(InstallerStateStore::class)->lockPath(),
            base_path('bootstrap/cache/config.php'),
        ];
        $originals = [];
        foreach ($paths as $path) {
            $originals[$path] = is_file($path) ? file_get_contents($path) : null;
        }
        $originalDatabaseConfig = config('database.connections.mysql');
        $originalDefaultConnection = config('database.default');
        $originalAppKey = config('app.key');
        $originalAppInstalled = config('app.installed');
        $store = app(InstallerStateStore::class);

        try {
            $save = $service->saveDatabaseConfig(compact('host', 'port', 'database', 'username', 'password'));
            $this->assertTrue($save['success'], $save['message']);

            $migration = $service->runMigrations();
            $this->assertTrue($migration['success'], $migration['message']);

            $suffix = strtoupper(bin2hex(random_bytes(3)));
            $created = $service->createInitialDataAndAdmin([
                'madrasah_name' => 'CBT Installer E2E Test',
                'madrasah_code' => 'CBTTEST' . $suffix,
                'admin_name' => 'Installer E2E Admin',
                'admin_email' => 'installer-e2e-' . strtolower($suffix) . '@example.test',
                'admin_password' => 'E2E-Password-' . $suffix . '!',
            ]);
            $this->assertTrue($created['success'], $created['message']);

            $this->assertTrue($service->lockInstaller(), 'Final verification and installer lock must succeed.');
            $this->assertTrue($service->isInstalled());
            $this->assertFileExists(storage_path('installed.lock'));
        } finally {
            DB::purge('mysql');
            config([
                'database.default' => $originalDefaultConnection,
                'database.connections.mysql' => $originalDatabaseConfig,
                'app.key' => $originalAppKey,
                'app.installed' => $originalAppInstalled,
            ]);
            DB::purge('mysql');

            foreach ($originals as $path => $contents) {
                if ($contents === null) {
                    if (is_file($path)) {
                        @unlink($path);
                    }
                    continue;
                }
                $directory = dirname($path);
                if (!is_dir($directory)) {
                    mkdir($directory, 0700, true);
                }
                $temporary = tempnam($directory, '.cbt-e2e-restore-');
                file_put_contents($temporary, $contents, LOCK_EX);
                @chmod($temporary, 0600);
                rename($temporary, $path);
            }
            DB::purge('mysql');
        }
    }
}
